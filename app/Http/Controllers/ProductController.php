<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Plan;
use App\Models\Product;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::all();

        return view('product.index', compact('products'));
    }

    public function showSubscriptionPage()
    {
        $plans = Plan::all();

        return view('product.subscription.index', compact('plans'));
    } 

    public function checkout(Request $request)
    {
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));

        $lineItems = [];
        $products = Product::all();
        $totalPrice = 0;

        foreach ($products as $product) {
            if($request->user()->hasActiveSubscription())
            {
                $product->price = round($product->price * 0.9, 2);
            }

            $totalPrice += $product->price;

            $lineItems[] = [
                'price_data' => [
                  'currency' => 'usd',
                  'product_data' => [
                    'name' => $product->name,
                    // 'image' => [$product->image],
                  ],
                  'unit_amount' => $product->price * 100,
                ],
                'quantity' => 1,
              ];
        }

        $checkout_session = $stripe->checkout->sessions->create([
            'line_items' => $lineItems,
            'mode' => 'payment',
            'customer_creation' => 'always',
            'success_url' => route('checkout.success', [], true) . "?session_id={CHECKOUT_SESSION_ID}",
            'cancel_url' => route('checkout.cancel', [], true),
        ]);

        $order = new Order();

        $order->status = 'unpaid';
        $order->total_price = $totalPrice;
        $order->session_id = $checkout_session->id;
        $order->save();

        return redirect($checkout_session->url);
    }

    public function success(Request $request)
    {
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));
        
        $sessionId = $request->get('session_id');

        $customer = null;

        try 
        {
            $session = $stripe->checkout->sessions->retrieve($sessionId);

            if(! $session) 
            {
                throw new NotFoundHttpException;
            }

            $customer = $stripe->customers->retrieve($session->customer);

            $order = Order::where('session_id', $session->id)->first();

            if(!$order)
            {
                throw new NotFoundHttpException;
            }

            if($order->status == 'unpaid')
            {
                $order->status = 'paid';
                $order->save();
            }
            
            return view('product.success', compact('customer'));
        } 
        catch (\Throwable $th) 
        {
            throw new NotFoundHttpException;
        }

    }

    public function cancel()
    {
        return view('product.cancel');
    }

    public function webhook()
    {
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

        try 
        {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } 
        catch(\UnexpectedValueException $e) {
            // Invalid payload
            return response('', 400);
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            return response('', 400);
        }

        // Handle the event
        switch ($event->type) {
        case 'checkout.session.completed':
            $session = $event->data->object;
            $order = Order::where('session_id', $session->id)->first();

            if(!$order)
            {
                throw new NotFoundHttpException;
            }

            if($order->status == 'unpaid')
            {
                $order->status = 'paid';
                $order->save();
            }
            break;

        // case 'customer.subscription.created':

        //     $subscription = $event->data->object;
        //     $subItems = $event->items->data[0];
        //     $plan = $subItems->plan;
        //     $userSub = UserSubscription::where('subscription_id', $subscription->id)->first();
        //     $user = Auth::user();

        //     if(!isset($userSub))
        //     {
        //         $plan = Plan::where('price_id', $plan->id)->first();
        //         $subscription = new UserSubscription();
        //         $subscription->user_id = $user->id;
        //         $subscription->plan_id = $plan->id;
        //         $subscription->active_until = now()->addDays($plan->duration_in_days);
        //         $subscription->subscription_id = $subscription->id;
        //         $subscription->save();
        //     }
        //     break;

        // case 'customer.subscription.updated':
        //     # code...
        //     break;

        // case 'customer.subscription.deleted':
        //     # code...
        //     break;

        default:
            echo 'Received unknown event type ' . $event->type;
        }

        return response('', 200);
    }

    public function subscribe(Request $request)
    {
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));

        $plan = Plan::findOrFail($request->sub_id);

        $customer = $stripe->customers->create([
            'name' => Auth::user()->name,
            'email' => Auth::user()->email,
            'payment_method' => $request->payment_method
        ]);

        $subscription = $stripe->subscriptions->create([
            'customer' => $customer->id,
            'items' => [
            ['price' => $plan->price_id],
            ],
            'default_payment_method' => $request->payment_method,
            'expand' => ['latest_invoice.payment_intent'],
        ]);

        if($subscription->status == 'active')
        {
            session()->put('subscriptionId', $subscription->id);

            return redirect()->route('user.subscription.success', [
                'sub_id' => $request->sub_id,
                'subscription_id' => $subscription->id,
            ]);
        }

        return redirect()->route('user.subscription.error');
    }

    public function subscriptionSuccess(Request $request)
    {
        if(session()->has('subscriptionId'))
        {
            $subscriptionId = session()->get('subscriptionId');

            session()->forget('subscriptionId');

            if($subscriptionId == $request->subscription_id)
            { 
                $plan = Plan::findOrFail($request->sub_id);
                $user = $request->user();

                $subscription = new UserSubscription();
                $subscription->user_id = $user->id;
                $subscription->plan_id = $plan->id;
                $subscription->active_until = now()->addDays($plan->duration_in_days);
                $subscription->subscription_id = $request->subscription_id;
                $subscription->save();

                return redirect()->route('product.index');
            }
        }
        return redirect()->route('user.subscription.show');
    }
    
    public function subscriptionError()
    {
        return view('product.subscription.cancel');
    }

    public function subscriptionCancel(Request $request)
    {
        $subscription = UserSubscription::where('user_id', $request->user()->id)->first();

        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));
        
        $unsubscribe = $stripe->subscriptions->cancel(
            $subscription->subscription_id,
            []
        );

        if($unsubscribe->status == 'canceled')
        {
            session()->put('subscriptionId', $subscription->subscription_id);

            return redirect()->route('user.subscription.cancel.success', [
                'subscription_id' => $subscription->subscription_id,
            ]);
        }
    }

    public function subscriptionCancelSuccess(Request $request)
    {
        if(session()->has('subscriptionId'))
        {
            $subscriptionId = session()->get('subscriptionId');

            session()->forget('subscriptionId');

            if($subscriptionId == $request->subscription_id)
            { 
                $subscription = UserSubscription::where('subscription_id', $request->subscription_id);
                $subscription->delete();

                return redirect()->route('product.index');
            }
        }
        return redirect()->route('user.subscription.show');
    }

    public function checkSubscriptionAvailability($userId)
    {
        $subscription = UserSubscription::where('user_id', $userId)->first();

        if(isset($subscription))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function subscriptionUpdate(Request $request)
    {
        $subscription = UserSubscription::where('user_id', $request->user()->id)->first();
        $plan = Plan::findOrFail($request->sub_id);

        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));

        $prevSubscription = $stripe->subscriptions->retrieve(
            $subscription->subscription_id,
        );

        $subItemId = $prevSubscription->items->data[0]->id;

        $subscriptionUpdate = $stripe->subscriptions->update(
            $subscription->subscription_id,
            ['items' => [
                [
                    'id' => $subItemId,
                    'price' => $plan->price_id
                ],
            ]]
        );

        if($subscriptionUpdate->status == 'active')
        {
            session()->put('subscriptionId', $subscriptionUpdate->id);

            return redirect()->route('user.subscription.update.success', [
                'subscription_id' => $subscriptionUpdate->id,
                'plan_id' => $request->sub_id,
            ]);
        }
        else
        {
            return redirect()->route('product.index');
        }
    }

    public function subscriptionUpdateSuccess(Request $request)
    {
        if(session()->has('subscriptionId'))
        {
            $subscriptionId = session()->get('subscriptionId');

            session()->forget('subscriptionId');

            if($subscriptionId == $request->subscription_id)
            { 
                $plan = Plan::findOrFail($request->plan_id);

                $subscriptionUpdate = UserSubscription::where('subscription_id', $request->subscription_id)->first();
                $subscriptionUpdate->plan_id = $plan->id;
                $subscriptionUpdate->active_until = now()->addDays($plan->duration_in_days);
                $subscriptionUpdate->subscription_id = $request->subscription_id;
                $subscriptionUpdate->save();

                return redirect()->route('product.index');
            }
        }
        return redirect()->route('user.subscription.show');
    }
  
}
