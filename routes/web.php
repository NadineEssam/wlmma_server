<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\FCMController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\SocialAuthController;
use App\Services\ZatcaInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;



/*
 * |--------------------------------------------------------------------------
 * | Web Routes
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you can register web routes for your application. These
 * | routes are loaded by the RouteServiceProvider and all of them will
 * | be assigned to the "web" middleware group. Make something great!
 * |
 */

Route::get('/', function () {
    // return view('welcome');
    // return app()->environment();
    // return config('mail.smtp.username');
    // return 'mail.smtp.username';
    return env('MAIL_USERNAME');
});
// Route::get('/fcm-token', [FCMController::class, 'index'])->name('fcm.index');
Route::get('/fcm-token', [FCMController::class, 'index'])->name('fcm.index');
Route::post('/fcm-token', [FCMController::class, 'store'])->name('fcm.store');

// HYPERPAY
Route::get('/payment/form', [PaymentController::class, 'form'])->name('hyper.index');  // not working

// Zatca
Route::get('/zatca/test', function (ZatcaInvoiceService $zatca) {
    return $zatca->sendTestInvoice();
});


// Twilio
// Route::post('/send-sms', [SMSController::class, 'sendSms']);
