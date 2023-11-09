<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;


Route::get('/', [ProductController::class,'index'])->name('product.index');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';


Route::post('/checkout', [ProductController::class,'checkout'])->name('checkout');
Route::get('/success', [ProductController::class,'success'])->name('checkout.success');
Route::get('/cancel', [ProductController::class,'cancel'])->name('checkout.cancel');
Route::post('/webhook', [ProductController::class,'webhook'])->name('checkout.webhook');

Route::group(['middleware' =>['auth', 'verified'], 'prefix' => 'user', 'as' => 'user.'], function(){
   
    Route::post('/subscription', [ProductController::class,'subscribe'])->name('subscribe');
    Route::get('/subscription/show', [ProductController::class,'showSubscriptionPage'])->name('subscription.show');
    Route::get('/subscription/success', [ProductController::class,'subscriptionSuccess'])->name('subscription.success');
    Route::get('/subscription/error', [ProductController::class,'subscriptionError'])->name('subscription.error');
    Route::post('/subscription/cancel', [ProductController::class,'subscriptionCancel'])->name('subscription.cancel');
    Route::get('/subscription/cancel/success', [ProductController::class,'subscriptionCancelSuccess'])->name('subscription.cancel.success');
    Route::post('/subscription/update', [ProductController::class,'subscriptionUpdate'])->name('subscription.update');
    Route::get('/subscription/update/success', [ProductController::class,'subscriptionUpdateSuccess'])->name('subscription.update.success');
    
});