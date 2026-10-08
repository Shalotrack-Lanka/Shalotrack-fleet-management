<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Verifies Firebase ID tokens (RS256 JWT) without an SDK: signature against Google's
 * published keys, then issuer, audience, expiry. Used for the OTP login and for every
 * token we get back from a refresh, so both paths trust exactly the same rules.
 */
class FirebaseTokenVerifier
{
    /**
     * Verify a Firebase ID token.
     *
     * Firebase ID tokens are RS256-signed JWTs.
     * Public keys are fetched from Google's JWKS endpoint and cached for 1 hour.
     *
     * @throws \Exception on invalid token
     */
    public function verify(string $idToken, string $projectId): array
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