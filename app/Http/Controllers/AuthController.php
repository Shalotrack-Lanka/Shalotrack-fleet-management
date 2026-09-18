<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
    // -------------------------------------------------------------------------
    // Web (Blade) methods
    // -------------------------------------------------------------------------

    /**
     * GET /login
     */
    public function showLogin(Request $request)
    {
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
            'token' => 'required|string',
        ]);

        $idToken   = $request->input('token');
        $projectId = config('shalotrack.firebase_project_id');

        try {
            $payload = $this->verifyFirebaseToken($idToken, $projectId);
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

        Log::info('FirebaseAuth: Login successful', [
            'uid'   => $payload['sub'] ?? null,
            'phone' => $payload['phone_number'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        // Check if this Firebase account has a customer profile yet.
        // New users coming from the web portal won't have one — send them
        // to registration. Existing users go straight to the dashboard.
        try {
            $apiService = app(\App\Services\ShalotrackApiService::class);
            $apiService->getMyProfile();
            return redirect('/dashboard');
        } catch (\Exception $e) {
            if ($e->getCode() === 404) {
                return redirect('/register');
            }
            return redirect('/dashboard');
        }
    }

    /**
     * POST /logout
     */
    public function logout(Request $request)
    {
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

    /**
     * Verify a Firebase ID token.
     *
     * Firebase ID tokens are RS256-signed JWTs.
     * Public keys are fetched from Google's JWKS endpoint and cached for 1 hour.
     *
     * @throws \Exception on invalid token
     */
    private function verifyFirebaseToken(string $idToken, string $projectId): array
    {
        // 1. Split JWT into parts
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new \Exception('Malformed JWT');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        // 2. Decode header and payload
        $header  = json_decode($this->base64UrlDecode($headerB64), true);
        $payload = json_decode($this->base64UrlDecode($payloadB64), true);

        if (!$header || !$payload) {
            throw new \Exception('Failed to decode JWT');
        }

        // 3. Validate algorithm
        if (($header['alg'] ?? '') !== 'RS256') {
            throw new \Exception('Unexpected algorithm: ' . ($header['alg'] ?? 'none'));
        }

        $kid = $header['kid'] ?? null;
        if (!$kid) {
            throw new \Exception('Missing kid in JWT header');
        }

        // 4. Fetch Firebase public keys (cached)
        $publicKeys = $this->getFirebasePublicKeys();

        if (!isset($publicKeys[$kid])) {
            throw new \Exception("Public key not found for kid: {$kid}");
        }

        // 5. Verify signature
        $publicKey = openssl_pkey_get_public($publicKeys[$kid]);
        if (!$publicKey) {
            throw new \Exception('Failed to load public key');
        }

        $data      = "{$headerB64}.{$payloadB64}";
        $signature = $this->base64UrlDecode($signatureB64);

        $verified = openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA256);

        if ($verified !== 1) {
            throw new \Exception('Invalid token signature');
        }

        // 6. Validate claims
        $now = time();

        if (($payload['exp'] ?? 0) < $now) {
            throw new \Exception('Token has expired');
        }

        if (($payload['iat'] ?? 0) > $now + 300) {
            throw new \Exception('Token issued in the future');
        }

        $expectedIssuer = "https://securetoken.google.com/{$projectId}";
        if (($payload['iss'] ?? '') !== $expectedIssuer) {
            throw new \Exception('Invalid issuer: ' . ($payload['iss'] ?? 'none'));
        }

        if (($payload['aud'] ?? '') !== $projectId) {
            throw new \Exception('Invalid audience: ' . ($payload['aud'] ?? 'none'));
        }

        if (empty($payload['sub'])) {
            throw new \Exception('Missing subject in token');
        }

        return $payload;
    }

    /**
     * Fetch Firebase's RS256 public keys from Google.
     * Keys rotate periodically — cached for 1 hour in file cache.
     */
    private function getFirebasePublicKeys(): array
    {
        $cacheKey = 'firebase_public_keys';

        // Try cache first
        $cached = cache()->get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $response = Http::timeout(5)->get(
            'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com'
        );

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch Firebase public keys');
        }

        $keys = $response->json();

        // Cache for 1 hour
        cache()->put($cacheKey, $keys, now()->addHour());

        return $keys;
    }

    /**
     * Base64URL decode (JWT uses base64url, not standard base64).
     */
    private function base64UrlDecode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }
}