<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use App\Support\ErrorLogger;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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

        // CSP + Permissions-Policy on every web response (Report-Only until CSP_ENFORCE=true).
        $middleware->web(append: [\App\Http\Middleware\SecurityHeaders::class]);

        $middleware->alias([
            'firebase.auth' => \App\Http\Middleware\FirebaseAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Every reportable exception goes to the `app_errors` channel with a reference ID,
        // a plain-English category and its location. The same ID is printed on the error
        // page, so a customer's call finds the exact log line (same design as the Admin portal).
        $exceptions->reportable(function (\Throwable $e) {
            ErrorLogger::log($e, 'error');
        });

        // Branded error pages for every HTTP error and uncaught exception (never the default
        // Laravel page). Raw message / file are NOT shown unless ERROR_PAGE_DETAILS=true.
        $exceptions->render(function (\Throwable $e, $request) {
            // Redirects, validation errors and sign-out redirects keep Laravel's own handling
            // (validation stays {message, errors} with HTTP 422; the pages already read that).
            if ($e instanceof \Illuminate\Http\Exceptions\HttpResponseException
                || $e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof \Illuminate\Auth\AuthenticationException) {
                return null;
            }

            $status = ErrorLogger::status($e);

            // 4xx pages are not "reported" by Laravel, but the reference ID shown must still
            // find a log line: write one (warning level) so support can always look it up.
            if ($e instanceof HttpExceptionInterface && $status < 500) {
                ErrorLogger::log($e, 'warning');
            }

            // fetch()/AJAX callers get the same failure as safe JSON with the reference ID — never
            // the HTML error page (res.json() on it throws "Unexpected token <" in the browser).
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return ErrorLogger::json($e);
            }

            $view = view()->exists("errors.$status") ? "errors.$status" : 'errors.generic';

            return response()->view($view, ErrorLogger::viewData($e), $status);
        });

    })->create();