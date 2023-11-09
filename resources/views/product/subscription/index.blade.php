@extends('layouts.master')

@push('styles')
    <style type="text/css">
        .StripeElement {
        box-sizing: border-box;

        height: 40px;

        padding: 10px 12px;

        border: 1px solid transparent;
        border-radius: 4px;
        background-color: white;

        box-shadow: 0 1px 3px 0 #e6ebf1;
        -webkit-transition: box-shadow 150ms ease;
        transition: box-shadow 150ms ease;
        }

        .StripeElement--focus {
        box-shadow: 0 1px 3px 0 #cfd7df;
        }

        .StripeElement--invalid {
        border-color: #fa755a;
        }

        .StripeElement--webkit-autofill {
        background-color: #fefde5 !important;
        }
    </style>
@endpush

@section('content')
<div class="container">

    <h1 class="mt-3">Subscribe</h1>

    <form action="{{ optional(auth()->user())->hasSubscription() ? route('user.subscription.update') : route('user.subscribe')}}" method="POST" id="paymentForm">
        @csrf

        <div class="form-group">
            <div class="row">
                @foreach ($plans as $plan)
                    <div class="col-auto">
                        <input  type="radio"
                                class="btn-check"
                                name="sub_id"
                                value="{{$plan->id}}"
                                id="plan{{$plan->id}}"
                                autocomplete="off">
                        <label class="btn btn-outline-info"
                            for="plan{{$plan->id}}">
                            <p class="h2 font-weight-bold text-capitalize">{{$plan->name}}</p>
                            <p class="display-6">{{$plan->interval_count}} {{$plan->interval}}</p>
                            <p class="display-6 text-capitalize">${{number_format($plan->price, 2, '.', '')}}</p>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>

        <div style="width: 30rem;">
            <label class="mt-3" for="card-element">
                Card details:
            </label>
            
            <div id="cardElement"></div>
            
            <small class="form-text text-muted" id="cardErrors" role="alert"></small>
            
            <input type="hidden" name="payment_method" id="paymentMethod">
        </div>


        <button type="submit" id="payButton" class="btn btn-outline-primary my-3">Subscribe</button>

    </form>

</div>
@endsection

@push('scripts')
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const stripe = Stripe('{{ env('STRIPE_PUBLISHABLE_KEY') }}');

        const elements = stripe.elements({ locale: 'en' });
        const cardElement = elements.create('card');

        cardElement.mount('#cardElement');
    </script>

    <script>
        const form = document.getElementById('paymentForm');
        const payButton = document.getElementById('payButton');

        payButton.addEventListener('click', async(e) => {
            e.preventDefault();

            const { paymentMethod, error } = await stripe.createPaymentMethod(
                'card', cardElement, {
                    billing_details: {
                        "name": "{{ auth()->user()->name }}",
                        "email": "{{ auth()->user()->email }}"
                    }
                }
            );

            if (error) {
                const displayError = document.getElementById('cardErrors');

                displayError.textContent = error.message;
            } else {
                const tokenInput = document.getElementById('paymentMethod');

                tokenInput.value = paymentMethod.id;
                form.submit();
            }
        });
    </script>
@endpush
