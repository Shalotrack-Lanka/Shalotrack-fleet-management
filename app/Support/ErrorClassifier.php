<?php

namespace App\Support;

/**
 * ErrorClassifier
 *
 * Turns a raw \Throwable into two safe strings the support team can read in
 * seconds before ever looking at a stack trace:
 *
 *   category  — WHERE the problem is (Database / External API / Code bug …)
 *   summary   — WHAT to look at first (plain English, no raw SQL or paths)
 *
 * Security contract: neither 'category' nor 'summary' may ever contain raw
 * SQL, file paths, column names, or user-supplied data — this output is
 * rendered on the public-facing error page as the "Type" field.
 *
 * The actual $e->getMessage() and file/line are logged server-side only.
 */
class ErrorClassifier
{
    public static function classify(\Throwable $e): array
    {
        $class = get_class($e);

        // ── Database ──────────────────────────────────────────────────────────
        if ($e instanceof \Illuminate\Database\QueryException) {
            return [
                'category' => 'Database error',
                'summary'  => 'A database query failed — check the query, table schema, and DB connection, not application logic.',
            ];
        }

        // ── ShaloTrack C# API — could not connect at all ──────────────────────
        if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
            return [
                'category' => 'API — connection failed',
                'summary'  => 'Could not reach the ShaloTrack API. Verify the API container is running and SHALOTRACK_API_BASE_URL is correct.',
            ];
        }

        // ── ShaloTrack C# API — connected but returned an error ───────────────
        if ($e instanceof \Illuminate\Http\Client\RequestException) {
            return [
                'category' => 'API — bad response',
                'summary'  => 'The ShaloTrack API responded with an error status. Check the C# API logs at the same timestamp — not a portal-side bug.',
            ];
        }

        // ── Firebase / authentication ─────────────────────────────────────────
        if (str_contains($class, 'Firebase') || str_contains($class, 'Kreait')) {
            return [
                'category' => 'Authentication — Firebase',
                'summary'  => 'A Firebase authentication call failed. Check Firebase credentials in .env and confirm the Firebase project is active.',
            ];
        }

        // ── CSRF token mismatch (page left open too long, double-submit) ──────
        if ($e instanceof \Illuminate\Session\TokenMismatchException) {
            return [
                'category' => 'Session — CSRF mismatch',
                'summary'  => 'The CSRF token was stale or missing. Expected for pages left open a long time — not a bug unless it happens immediately after login.',
            ];
        }

        // ── Route referenced by name that no longer exists ────────────────────
        if ($e instanceof \Symfony\Component\Routing\Exception\RouteNotFoundException) {
            return [
                'category' => 'Routing — missing route name',
                'summary'  => 'Code called route() with a name that does not exist in routes/web.php. Look for a typo in the route name.',
            ];
        }

        // ── Laravel HTTP 404 — URL doesn't match any route ───────────────────
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return [
                'category' => 'Routing — route not matched',
                'summary'  => 'The requested URL did not match any registered route. Check routes/web.php or look for a broken link in the UI.',
            ];
        }

        // ── Laravel HTTP 403 — Gate::authorize() or middleware denied ─────────
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
            return [
                'category' => 'Authorisation denied',
                'summary'  => 'A Gate::authorize() call or middleware blocked access. Check the policy or middleware for the route that triggered this.',
            ];
        }

        // ── Wrong HTTP method on a route ──────────────────────────────────────
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
            return [
                'category' => 'Routing — wrong HTTP method',
                'summary'  => 'A request used the wrong HTTP verb for this route. Check that forms and fetch() calls use the correct method.',
            ];
        }

        // ── Rate limiter ──────────────────────────────────────────────────────
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException) {
            return [
                'category' => 'Rate limited',
                'summary'  => 'The rate limiter throttled this request. If legitimate traffic is hitting the limit, review the throttle() middleware configuration.',
            ];
        }

        // ── Any other Symfony HTTP exception (abort(408), abort(502) …) ───────
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
            $code = $e->getStatusCode();
            return [
                'category' => 'HTTP ' . $code,
                'summary'  => 'An HTTP exception was thrown directly. Search the codebase for abort(' . $code . ') to find the source.',
            ];
        }

        // ── PHP type errors and undefined-variable runtime errors ─────────────
        if ($e instanceof \TypeError || $e instanceof \ErrorException) {
            return [
                'category' => 'Code bug — PHP runtime error',
                'summary'  => 'A PHP type mismatch or undefined variable. Go straight to the file and line number in the log entry for this reference ID.',
            ];
        }

        // ── Validation — should never reach the error page normally ───────────
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return [
                'category' => 'Validation',
                'summary'  => 'A validation exception was not redirected back to the form. Check that the route returns redirect()->back()->withErrors() correctly.',
            ];
        }

        // ── Anything else ─────────────────────────────────────────────────────
        return [
            'category' => 'Unclassified',
            'summary'  => 'Does not match a known exception pattern. Read the exception class and message in the log entry for this reference ID.',
        ];
    }
}