<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Keeps a customer signed in for up to REMEMBER_DAYS after one OTP login.
 *
 * Why it exists: the Firebase ID token we keep in the session lives only one hour, and
 * the session used to end with it. Firebase also hands out a refresh token that can mint
 * new ID tokens for months. We keep that token in ONE encrypted, HttpOnly, host-only
 * cookie (Laravel's EncryptCookies encrypts it with APP_KEY) and use it to quietly get a
 * fresh ID token whenever the current one is missing or about to expire.
 *
 * Why a cookie and not the session: a deploy replaces the server, taking file sessions
 * with it. A cookie survives that, needs no database, and costs nothing.
 *
 * Limits (deliberate):
 *  - hard cap of REMEMBER_DAYS from the OTP login. Refreshing never extends it.
 *  - Firebase refuses to refresh a disabled/deleted account or a revoked token → we drop the cookie.
 *  - the Firebase `auth_time` claim stays the real OTP time, so the API's "signed in recently"
 *    check for account deletion still demands a genuine new OTP.
 *  - logout and /login?expired=1 delete the cookie.
 */
class RememberLogin
{
    public const REMEMBER_DAYS = 90;

    /** Refresh when the ID token has less than this many seconds left. */
    public const REFRESH_SKEW = 300;

    public function __construct(private FirebaseTokenVerifier $verifier) {}

    /** "__Host-" when cookies are Secure (production): the browser then also pins it to this exact host. */
    public static function cookieName(): string
    {
        return config('session.secure') ? '__Host-st_remember' : 'st_remember';
    }

    /** Called right after a successful OTP login. */
    public function issue(string $refreshToken, string $uid): void
    {
        $this->queue($refreshToken, $uid, time());
    }

    public function forget(): void
    {
        Cookie::queue(Cookie::forget(self::cookieName()));
    }

    /**
     * Get a fresh ID token from the remember cookie and put it in the session.
     * Returns true when the session now holds a valid token.
     */
    public function restore(Request $request): bool
    {
        $raw = $request->cookie(self::cookieName());
        $c = is_string($raw) ? json_decode($raw, true) : null;

        if (!is_array($c) || !is_string($c['rt'] ?? null) || !is_string($c['uid'] ?? null) || !is_int($c['iat'] ?? null)
            || strlen($c['rt']) < 20 || strlen($c['rt']) > 2048) {
            if ($raw !== null) {
                $this->forget();
            }
            return false;
        }

        if (time() - $c['iat'] > self::REMEMBER_DAYS * 86400 || $c['iat'] > time() + 300) {
            $this->forget();
            return false;
        }

        try {
            $res = Http::asForm()->timeout(6)->post(
                'https://securetoken.googleapis.com/v1/token?key=' . urlencode((string) config('services.firebase.api_key')),
                ['grant_type' => 'refresh_token', 'refresh_token' => $c['rt']]
            );
        } catch (\Throwable $e) {
            Log::warning('RememberLogin: refresh request failed', ['error' => $e->getMessage()]);
            return false; // Google unreachable: keep the cookie, try again on the next request
        }

        if ($res->status() >= 500 || $res->status() === 429) {
            Log::warning('RememberLogin: Google busy', ['status' => $res->status()]);
            return false; // temporary: keep the cookie
        }

        if (!$res->successful()) {
            // TOKEN_EXPIRED / USER_DISABLED / USER_NOT_FOUND / INVALID_REFRESH_TOKEN: the login is over.
            Log::info('RememberLogin: refresh refused', ['status' => $res->status(), 'reason' => $res->json('error.message')]);
            $this->forget();
            return false;
        }

        $idToken = (string) $res->json('id_token');
        $newRefresh = (string) ($res->json('refresh_token') ?: $c['rt']);

        try {
            $payload = $this->verifier->verify($idToken, (string) config('shalotrack.firebase_project_id'));
        } catch (\Throwable $e) {
            Log::warning('RememberLogin: refreshed token failed verification', ['error' => $e->getMessage()]);
            $this->forget();
            return false;
        }

        // The refreshed token must still belong to the same person as the cookie.
        if (!hash_equals($c['uid'], (string) ($payload['sub'] ?? ''))) {
            Log::warning('RememberLogin: uid mismatch');
            $this->forget();
            return false;
        }

        $hadToken = Session::has('firebase_token');

        Session::put('firebase_token', $idToken);
        Session::put('firebase_token_expires_at', (int) ($payload['exp'] ?? 0));
        Session::put('firebase_uid', $payload['sub']);
        Session::put('firebase_phone', $payload['phone_number'] ?? null);

        if (!$hadToken) {
            // A brand-new session (the old one expired or the server was replaced): new id, and
            // re-learn the one flag the middleware depends on.
            Session::regenerate();
            $this->loadDeletionFlag();
        }

        $this->queue($newRefresh, $c['uid'], $c['iat']); // keeps the ORIGINAL start time: the 90 days never slide

        return true;
    }

    private function loadDeletionFlag(): void
    {
        Session::forget('deletion_pending');
        try {
            $status = app(ShalotrackApiService::class)->getDeletionStatus();
            if ((bool) ($status['data']['pending'] ?? false)) {
                Session::put('deletion_pending', true);
            }
        } catch (\Throwable) {
            // Not available: the API still enforces the lock.
        }
    }

    private function queue(string $refreshToken, string $uid, int $startedAt): void
    {
        $remaining = max(1, (int) ceil((($startedAt + self::REMEMBER_DAYS * 86400) - time()) / 60));

        Cookie::queue(Cookie::make(
            self::cookieName(),
            json_encode(['rt' => $refreshToken, 'uid' => $uid, 'iat' => $startedAt]),
            $remaining,   // minutes, so the browser drops it at the 90-day mark
            '/',
            null,         // no domain → host-only
            null,         // secure: follows config('session.secure')
            true,         // HttpOnly: page scripts can never read it
            false,
            'lax'
        ));
    }
}