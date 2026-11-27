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
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);
        // Global, so it sees the rendered 401/403 even when auth middleware throws before group middleware.
        $middleware->append(\App\Http\Middleware\LogDeniedRequests::class);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'approved.instructor' => \App\Http\Middleware\CheckApprovedInstructor::class,
            'setup.complete' => \App\Http\Middleware\EnsureSetupComplete::class,
            'phone.verified' => \App\Http\Middleware\EnsurePhoneIsVerified::class,
            'entitlement' => \App\Http\Middleware\EnsureEntitlement::class,
            'verified.role' => \App\Http\Middleware\CheckOrganizationVerified::class,
            'fresh.auth' => \App\Http\Middleware\EnsureFreshAuth::class,
            'staff.2fa' => \App\Http\Middleware\EnsureStaffTwoFactor::class,
        ]);

        // Class D (sensitive) actions: limited per user and IP, then a code or password within 10 minutes.
        $middleware->group('sensitive', [
            'throttle:sensitive',
            \App\Http\Middleware\EnsureFreshAuth::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\EnforceCanonicalAndSecurityHeaders::class,
            \App\Http\Middleware\EnsureSetupComplete::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\EnsurePhoneIsVerified::class,
        ]);

        $middleware->statefulApi();

        $middleware->api(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\EnsurePhoneIsVerified::class,
            \App\Http\Middleware\RememberUserLocale::class,
        ]);

        // Exclude payment callback routes, public AI chat, and public free tools from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'payment/razorpay/callback',
            'payment/sslcommerz/callback',
            'payment/mollie/callback',
            'payment/chapa/callback',
            'payment/chapa/webhook',
            'billing/webhook/*',
            'api/v1/billing/webhook/*',
            'ai-chat/send',
            'free-tools/calculate',
            'free-tools/ai-analysis',
            'free-tools/ask-advisor',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (\Illuminate\Http\Request $request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $headers = [
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS, HEAD',
                    'Access-Control-Allow-Headers' => 'Origin, X-Requested-With, Content-Type, Accept, Authorization, Cache-Control, Pragma, Range, x-xsrf-token',
                ];

                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The given data was invalid.',
                        'errors' => $e->errors(),
                    ], 422, $headers);
                }

                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthenticated.',
                    ], 401, $headers);
                }

                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException || $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Resource not found.',
                    ], 404, $headers);
                }

                $status = ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface)
                    ? $e->getStatusCode()
                    : 500;

                // Security: In production or when debug is off, NEVER leak server directory paths, lines, or SQL
                $isDebug = config('app.debug', false) && !app()->isProduction();

                $message = $isDebug
                    ? $e->getMessage()
                    : ($status === 500 ? 'An unexpected server error occurred. Please try again later.' : $e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], $status, $headers);
            }
        });
    })->create();
