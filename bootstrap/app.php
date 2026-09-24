<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',

        // FIX: routes/api.php was registered through Laravel's stateless
        // `api` middleware group, which has NO session middleware. Every
        // /api/* route is guarded by FirebaseAuthenticated, which reads the
        // Firebase token from the session — so /api/signalr-token (and every
        // other /api/* route) always returned 401, and live tracking fell
        // back to polling / redirected to login on every page that uses it.
        //
        // These endpoints are called by our own Blade pages with the browser
        // session cookie, so they belong in the `web` group (session +
        // encrypted cookies + CSRF on any non-GET), just under the /api prefix.
        then: function () {
            Route::middleware('web')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // Production path: Browser → Cloudflare → ALB → container.
        // Trust the forwarded headers from the ALB (the only thing that can
        // reach the container — enforced by the EC2 security group).
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'firebase.auth' => \App\Http\Middleware\FirebaseAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();