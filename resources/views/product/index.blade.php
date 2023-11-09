@extends('layouts.master')

@section('content')
<div class="container">
    <h1 class="mt-3">Buy</h1>
       <div class="row">
           @foreach ($products as $product)
               <div class="col-md-4">
                    <div class="card mt-3">
                        <img src="{{$product->image}}" alt="" class="card-img-top">
                        <div class="card-body">
                            <h5 class="card-title"><strong>${{$product->price}}</strong></h5>
                            <p class="card-text">{{$product->name}}</p>
                        </div>
                    </div>
               </div>
           @endforeach

           <div class="row mt-2">
                <div class="col-auto">
                    @if (!optional(auth()->user())->hasActiveSubscription())
                        <p class="border-bottom border-primary">
                            Would you like a discount every time?
                            <a href="{{route('user.subscription.show')}}">Subscribe</a>
                        </p>
                    @else
                        <p class="border-bottom border-primary d-inline p-2">You get a <strong>10% off</strong> as part of your subscription (This will be applied in the chechout).</p>
                        <form action="{{route('user.subscription.cancel')}}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-dark btn-sm">Unsubscribe</button>
                        </form>
                    @endif
                </div>
            </div>

            <form action="{{route('checkout')}}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-primary my-3">Checkout</button>
            </form>

       </div>
   </div>
@endsection
