<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\LoginOtpController;
use App\Http\Controllers\Auth\VerificationController;

Route::middleware('guest')->group(function () {
    // Login routes
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/login/otp', [LoginOtpController::class, 'show'])->name('login.otp.show');
    Route::post('/login/otp', [LoginOtpController::class, 'verify'])->name('login.otp.verify');
    Route::post('/login/otp/resend', [LoginOtpController::class, 'resend'])->name('login.otp.resend');

    // Forgot password routes
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');

    // Reset password routes
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::get('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// Email Verification Routes
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify')->middleware(['signed', 'throttle:6,1']);

Route::middleware(['auth', 'throttle:6,1'])->group(function () {
    Route::get('/email/verify', [VerificationController::class, 'show'])->name('verification.notice');
    Route::post('/email/resend', [VerificationController::class, 'resend'])->name('verification.resend');
});
