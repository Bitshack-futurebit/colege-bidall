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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\RedirectParkedFeatures::class,
            \App\Http\Middleware\EnsureUserActive::class,
            \App\Http\Middleware\EnsureTermsAccepted::class,
            \App\Http\Middleware\ResolveWhiteLabel::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'auctioneer.active' => \App\Http\Middleware\EnsureAuctioneerActivated::class,
            'staff.permission' => \App\Http\Middleware\CheckStaffPermission::class,
        ]);

        // API requests: return 401 JSON instead of redirecting to login
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                abort(401, 'Unauthenticated.');
            }
            return route('login');
        });

        // Exclude API routes and payment webhooks from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'payment/webhook',
            'payment/blink/webhook',
            'direct-payment/webhook',
        ]);
    })
    ->withProviders([
        \App\Providers\AppServiceProvider::class,
        \App\Providers\PaymentServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        // Stale CSRF token (expired session / long-open tab): don't show the
        // scary "Page Expired" page. Send guests to a fresh login, and bounce
        // authenticated users back where they were — both with a clear message.
        // NOTE: Laravel's prepareException() converts TokenMismatchException to a
        // 419 HttpException before render callbacks run, so we match the 419 here.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, $request) {
            if ($e->getStatusCode() !== 419) {
                return null; // let all other HTTP errors render normally
            }
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your session expired. Please refresh and try again.'], 419);
            }
            $msg = 'Your session timed out for security. Please try again.';
            if (auth()->guest()) {
                return redirect()->route('login')
                    ->withInput($request->except(['password', '_token']))
                    ->with('status', $msg);
            }
            return back()
                ->withInput($request->except(['password', '_token']))
                ->with('status', $msg);
        });
    })->create();
