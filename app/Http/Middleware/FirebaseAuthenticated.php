<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * FirebaseAuthenticated
 *
 * Guards all authenticated web routes.
 *
 * - API requests (/api/* or Accept: application/json) → return 401 JSON
 * - Web requests (browser) → redirect to /login
 * - Token is ALWAYS read from encrypted server-side session only.
 */
class FirebaseAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $token     = Session::get('firebase_token');
        $expiresAt = Session::get('firebase_token_expires_at');

        // No token in session → not logged in
        if (!$token) {
            return $this->unauthenticated($request);
        }

        // Token expired
        if ($expiresAt && now()->timestamp >= $expiresAt) {
            Session::flush();
            return $this->unauthenticated($request, true);
        }

        // Email verification check
        // Skip for the verification page itself and the mark-verified endpoint
        $path = $request->path();
        $skipVerification = in_array($path, ['email/verify', 'email/mark-verified', 'logout']);

        if (!$skipVerification && !Session::get('email_verified', true)) {
            // email_verified is null/not set for existing users (pre-verification feature)
            // Only enforce for users who have pending_verification_email set
            if (Session::has('pending_verification_email')) {
                if ($this->wantsJson($request)) {
                    return response()->json(['success' => false, 'code' => 'EMAIL_UNVERIFIED'], 403);
                }
                return redirect('/email/verify');
            }
        }

        return $next($request);
    }

    private function unauthenticated(Request $request, bool $expired = false): Response
    {
        // API requests get JSON
        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => false,
                'code'    => $expired ? 'TOKEN_EXPIRED' : 'UNAUTHENTICATED',
                'message' => $expired ? 'Session expired.' : 'Not authenticated.',
            ], 401);
        }

        // Browser requests get redirected
        $url = $expired ? '/login?expired=1' : '/login';
        return redirect($url);
    }

    /**
     * FIX: /api/* must always get a JSON 401, never a 302 to /login.
     *
     * The pages call fetch('/api/signalr-token') WITHOUT an Accept header,
     * so expectsJson() is false. A redirect would be silently followed by
     * fetch(), return the login HTML with status 200, and the page's
     * `res.status === 401` session-expiry check would never fire.
     */
    private function wantsJson(Request $request): bool
    {
        return $request->expectsJson() || $request->is('api/*');
    }
}