<?php

namespace Tests\Feature\Concerns;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Real RS256 Firebase-style tokens for tests, signed with a throw-away key generated here.
 * Google's public-key list is faked, so nothing leaves the machine and no real account is involved.
 */
trait FirebaseTestTokens
{
    protected const PROJECT = 'test-project';
    protected const KID = 'test-kid-1';
    protected const UID = 'firebase-uid-123';

    /** @var array<string, array{key: \OpenSSLAsymmetricKey, cert: string}> */
    private static array $pairs = [];

    protected static function pair(string $name = 'good'): array
    {
        if (!isset(self::$pairs[$name])) {
            $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            $csr = openssl_csr_new(['commonName' => "test-$name"], $key);
            $crt = openssl_csr_sign($csr, null, $key, 1);
            openssl_x509_export($crt, $pem);
            self::$pairs[$name] = ['key' => $key, 'cert' => $pem];
        }

        return self::$pairs[$name];
    }

    protected static function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    /** A signed token. $claims / $header override the defaults; $signWith picks which key pair signs it. */
    protected function token(array $claims = [], array $header = [], string $signWith = 'good'): string
    {
        $now = time();
        $payload = array_replace([
            'iss' => 'https://securetoken.google.com/' . self::PROJECT, 'aud' => self::PROJECT, 'sub' => self::UID,
            'iat' => $now - 10, 'exp' => $now + 3000, 'phone_number' => '+94771234567', 'auth_time' => $now - 10,
        ], $claims);
        $head = array_replace(['alg' => 'RS256', 'kid' => self::KID, 'typ' => 'JWT'], $header);

        $data = self::b64(json_encode($head)) . '.' . self::b64(json_encode($payload));
        openssl_sign($data, $sig, self::pair($signWith)['key'], OPENSSL_ALGO_SHA256);

        return $data . '.' . self::b64($sig);
    }

    /** Fake Google's published keys (kid => certificate) and start with an empty key cache. */
    protected function googleKeys(?array $keys = null, int $status = 200): void
    {
        Cache::flush();
        Http::swap(new \Illuminate\Http\Client\Factory());   // Http::fake() keeps the FIRST answer on one instance, so start fresh
        Http::fake(['www.googleapis.com/robot/v1/metadata/x509/*' => Http::response($keys ?? [self::KID => self::pair('good')['cert']], $status)]);
    }
}
