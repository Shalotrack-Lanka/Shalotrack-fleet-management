<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Session;

/**
 * One honest, friendly JSON failure for every AJAX write endpoint.
 *
 * Before this, each controller turned EVERY failure into "Failed to X" with HTTP 422, so an expired
 * session, an expired subscription, a rate limit and an API timeout all looked identical to the
 * customer, and an expired session never signed them out. Now:
 *
 *   401            → session cleared, page redirects to sign-in            (status 401, expired:true)
 *   timeout        → "took too long, try again"                            (status 422, timeout:true)
 *   402            → subscription expired — renew                          (status 422, code SUBSCRIPTION_EXPIRED)
 *   403 / 404 / 409→ the caller's specific text if given, else a safe default
 *   429            → "too many requests, wait a moment"                    (status 429)
 *   400 / 422      → the API's own message when it is plain text (no machine codes)
 *   anything else  → the caller's fallback text, never raw exception text
 *
 * Every response carries `reference` (matches the app_errors log line), so the toast can show it
 * and support can find the exact failure. HTTP 422 is kept for most failures on purpose: the pages'
 * fetch() code already reads the JSON message from a 422.
 */
final class ApiErrors
{
    /**
     * @param array<int,string> $messages status => text overrides, e.g. [403 => 'Not your vehicle.']
     */
    public static function json(\Throwable $e, string $action, string $fallback, array $messages = []): JsonResponse
    {
        $code = (int) $e->getCode();

        if ($code === 401) {
            Session::flush();

            return response()->json([
                'success' => false,
                'expired' => true,
                'code'    => 'TOKEN_EXPIRED',
                'message' => 'Your session has expired. Please sign in again.',
            ], 401);
        }

        $ref = ErrorLogger::log($e, 'warning', ['action' => $action]);

        if ($e instanceof ConnectionException) {
            return self::out(422, 'API_TIMEOUT', 'This is taking longer than expected. Please try again in a moment.', $ref, ['timeout' => true]);
        }

        return match (true) {
            $code === 402 => self::out(422, 'SUBSCRIPTION_EXPIRED', $messages[402] ?? "This vehicle's subscription has expired. Renew it to continue.", $ref),
            $code === 403 => self::out(422, 'FORBIDDEN',  $messages[403] ?? "You don't have access to do that.", $ref),
            $code === 404 => self::out(422, 'NOT_FOUND',  $messages[404] ?? 'That item no longer exists. Please refresh the page.', $ref),
            $code === 409 => self::out(422, 'CONFLICT',   $messages[409] ?? 'That conflicts with the current state. Please refresh and try again.', $ref),
            $code === 413 => self::out(422, 'TOO_LARGE',  $messages[413] ?? 'That file is too large.', $ref),
            $code === 429 => self::out(429, 'RATE_LIMITED', 'Too many requests. Please wait a moment and try again.', $ref),
            in_array($code, [400, 422], true) && (isset($messages[$code]) || self::isPlainMessage($e->getMessage()))
                          => self::out(422, 'REJECTED', $messages[$code] ?? $e->getMessage(), $ref),
            default       => self::out(422, 'FAILED', $fallback, $ref),
        };
    }

    /** A customer-safe API message is plain words; machine codes (SOME_CODE), paths and URLs are not. */
    public static function isPlainMessage(string $m): bool
    {
        return $m !== ''
            && mb_strlen($m) <= 200
            && ! str_contains($m, '_')
            && ! str_contains($m, '/')
            && ! preg_match('/[<>]/', $m)
            && ! preg_match('/\b(exception|stack|sql|http[s]?:)/i', $m);
    }

    /** HTTP status to use for a caught exception code — never an invalid one (e.g. a cURL errno). */
    public static function safeStatus(int $code, int $default = 422): int
    {
        return ($code >= 400 && $code <= 599) ? $code : $default;
    }

    private static function out(int $status, string $code, string $message, string $ref, array $extra = []): JsonResponse
    {
        return response()->json(['success' => false, 'code' => $code, 'message' => $message, 'reference' => $ref] + $extra, $status);
    }
}