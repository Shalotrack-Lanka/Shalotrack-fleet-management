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
 * - API requests (Accept: application/json) → return 401 JSON
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

        return $next($request);
    }

    private function unauthenticated(Request $request, bool $expired = false): Response
    {
        // API requests get JSON
        if ($request->expectsJson()) {
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
}