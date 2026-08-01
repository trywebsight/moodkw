<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PaymentController;
use App\Livewire\CheckoutPage;
use App\Models\Order;
use Illuminate\Support\Facades\Route;

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::get('/', CheckoutPage::class)->name('home');

Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

Route::get('/areas', [AreaController::class, 'index'])->name('areas.index');

Route::get('/invoices/{order}', [InvoiceController::class, 'download'])->name('invoices.download');

Route::get('/pay/{order}', [PaymentController::class, 'pay'])
    ->middleware('signed')
    ->name('payment.pay');

Route::prefix('payment')->name('payment.')->group(function () {
    Route::post('/webhook', [PaymentController::class, 'webhook'])->name('webhook');
    Route::get('/redirect', [PaymentController::class, 'redirect'])->name('redirect');
    Route::get('/poll', [PaymentController::class, 'poll'])->name('poll');
    Route::get('/pending', [PaymentController::class, 'pending'])->name('pending');
    Route::get('/success', [PaymentController::class, 'success'])->name('success');
    Route::get('/failure', [PaymentController::class, 'failure'])->name('failure');
    Route::get('/cancel-pending', [PaymentController::class, 'cancelPending'])->name('cancel-pending');
    Route::post('/retry/{order}', [PaymentController::class, 'retry'])->name('retry');
});
