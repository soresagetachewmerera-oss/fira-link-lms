<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'approved.instructor' => \App\Http\Middleware\CheckApprovedInstructor::class,
            'setup.complete' => \App\Http\Middleware\EnsureSetupComplete::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\EnsureSetupComplete::class,
            \App\Http\Middleware\SetLocale::class,
        ]);
        
        // Exclude payment callback routes from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'payment/razorpay/callback',
            'payment/sslcommerz/callback',
            'payment/mollie/callback',
            'payment/chapa/callback',
            'payment/chapa/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
