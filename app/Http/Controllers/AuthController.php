<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * AuthController
 *
 * Handles Firebase token verification and session lifecycle.
 *
 * Verification method:
 * Firebase ID tokens are JWTs signed with RS256.
 * We verify them by:
 * 1. Fetching Firebase's public keys from Google
 * 2. Decoding the JWT header to find the key ID (kid)
 * 3. Verifying the signature using the matching public key
 * 4. Validating claims: issuer, audience, expiry
 *
 * This is the correct approach without the kreait SDK.
 */
class AuthController extends Controller
{
    public function __construct(private \App\Services\FirebaseTokenVerifier $verifier, private \App\Services\RememberLogin $remember) {}

    // -------------------------------------------------------------------------
    // Web (Blade) methods
    // -------------------------------------------------------------------------

    /**
     * GET /login
     */
    public function showLogin(Request $request)
    {
        // Landed here because the API said 401 (or the login could not be renewed): the remembered
        // login is no good any more, so drop it or every page would try it again.
        if ($request->query('expired') === '1') {
            $this->remember->forget();
        }

        // A returning customer whose session ended (idle, or the server was replaced by a deploy)
        // is signed straight back in from the "keep me signed in" cookie, no OTP.
        if (!Session::has('firebase_token') && $request->query('expired') !== '1' && $request->hasCookie(\App\Services\RememberLogin::cookieName())) {
            $this->remember->restore($request);
        }

        if (Session::has('firebase_token')) {
            return redirect('/dashboard');
        }

        return view('auth.login', [
            'expired' => $request->query('expired') === '1',
        ]);
    }

    /**
     * POST /login
     * Verifies Firebase ID token and creates Laravel session.
     */
    public function login(Request $request)
    {
        $request->validate([
            'token'         => 'required|string',
            'refresh_token' => 'nullable|string|min:20|max:2048',
        ]);

        $idToken   = $request->input('token');
        $projectId = config('shalotrack.firebase_project_id');

        try {
            $payload = $this->verifier->verify($idToken, $projectId);
        } catch (\Exception $e) {
            Log::warning('FirebaseAuth: Token verification failed', [
                'error' => $e->getMessage(),
                'ip'    => $request->ip(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication failed. Please try again.',
                ], 401);
            }

            return back()->withErrors(['phone' => 'Authentication failed. Please try again.']);
        }

        // Store in encrypted HTTP-only session — token never returned to browser
        Session::put('firebase_token',            $idToken);
        Session::put('firebase_token_expires_at', (int) ($payload['exp'] ?? 0));
        Session::put('firebase_uid',              $payload['sub'] ?? null);
        Session::put('firebase_phone',            $payload['phone_number'] ?? null);

        // "Keep me signed in": the browser also sends Firebase's refresh token, kept for up to 90 days
        // in one encrypted HttpOnly cookie (see RememberLogin). Without it the login ends with the 1-hour token.
        if ($request->filled('refresh_token') && !empty($payload['sub'])) {
            $this->remember->issue($request->input('refresh_token'), $payload['sub']);
        }

        Log::info('FirebaseAuth: Login successful', [
            'uid'   => $payload['sub'] ?? null,
            'phone' => $payload['phone_number'] ?? null,
        ]);

        // Check if this Firebase account has a customer profile yet.
        // New users coming from the web portal won't have one — send them
        // to registration. Existing users go straight to the dashboard.
        // This check MUST happen before the expectsJson() return so the
        // JS client gets the correct redirect URL.
        $hasProfile = true;
        try {
            $apiService = app(\App\Services\ShalotrackApiService::class);
            $apiService->getMyProfile();
        } catch (\Exception $e) {
            Log::info('FirebaseAuth: Profile check', [
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
            ]);
            if ((int) $e->getCode() === 404) {
                $hasProfile = false;
            }
        }

        // Is this account waiting for deletion? Then the only place it may go is the cancel page.
        Session::forget('deletion_pending');
        $deletionPending = false;
        if ($hasProfile) { // a 403 from the profile call (locked account) also leaves this true
            try {
                $status = app(\App\Services\ShalotrackApiService::class)->getDeletionStatus();
                $deletionPending = (bool) ($status['data']['pending'] ?? false);
            } catch (\Exception $ignored) {
                // Not available: treat as not pending; the API still enforces the lock.
            }
        }
        if ($deletionPending) {
            Session::put('deletion_pending', true);
            $hasProfile = true;
        }

        $target = $deletionPending ? '/account/deletion' : ($hasProfile ? '/dashboard' : '/register');

        if ($request->expectsJson()) {
            return response()->json([
                'success'  => true,
                'redirect' => $target,
            ]);
        }

        return redirect($target);
    }

    /**
     * POST /logout
     */
    public function logout(Request $request)
    {
        $this->remember->forget();
        Session::flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    // -------------------------------------------------------------------------
    // API methods
    // -------------------------------------------------------------------------

    /**
     * GET /api/auth/me
     */
    public function me()
    {
        $token     = Session::get('firebase_token');
        $expiresAt = Session::get('firebase_token_expires_at');

        if (!$token) {
            return response()->json(['authenticated' => false], 401);
        }

        if ($expiresAt && now()->timestamp >= $expiresAt) {
            Session::flush();
            return response()->json([
                'authenticated' => false,
                'code'          => 'TOKEN_EXPIRED',
            ], 401);
        }

        return response()->json([
            'authenticated' => true,
            'uid'           => Session::get('firebase_uid'),
            'phone'         => Session::get('firebase_phone'),
        ]);
    }

    // -------------------------------------------------------------------------
    // Firebase JWT verification (no external SDK)
    // -------------------------------------------------------------------------
}