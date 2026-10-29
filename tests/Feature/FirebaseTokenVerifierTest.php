<?php

namespace Tests\Feature;

use App\Services\FirebaseTokenVerifier;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\FirebaseTestTokens;
use Tests\TestCase;

/**
 * F6: the gate to the whole portal. A forged, altered, expired or foreign token must never be accepted.
 * Tokens here are REAL RS256 signatures made with a throw-away key; Google's key list is faked.
 */
class FirebaseTokenVerifierTest extends TestCase
{
    use FirebaseTestTokens;

    private FirebaseTokenVerifier $v;

    protected function setUp(): void
    {
        parent::setUp();
        $this->v = new FirebaseTokenVerifier();
        $this->googleKeys();
    }

    private function verify(string $token): array
    {
        return $this->v->verify($token, self::PROJECT);
    }

    private function assertRejected(string $token, string $why): void
    {
        try {
            $this->verify($token);
        } catch (\Exception $e) {
            $this->assertStringContainsString($why, $e->getMessage());
            return;
        }
        $this->fail("A token that should be refused ($why) was accepted.");
    }

    public function test_a_genuine_token_is_accepted_and_its_claims_returned(): void
    {
        $p = $this->verify($this->token());

        $this->assertSame(self::UID, $p['sub']);
        $this->assertSame('+94771234567', $p['phone_number']);
    }

    public function test_the_signature_is_really_checked(): void
    {
        // Signed by a different key than the one Google publishes for this kid.
        $this->assertRejected($this->token([], [], 'attacker'), 'Invalid token signature');

        // Valid token with its payload edited afterwards (e.g. changing the uid to someone else's).
        [$h, $p, $s] = explode('.', $this->token());
        $evil = json_decode(base64_decode(strtr($p, '-_', '+/')), true);
        $evil['sub'] = 'someone-elses-uid';
        $this->assertRejected($h . '.' . self::b64(json_encode($evil)) . '.' . $s, 'Invalid token signature');

        // Signature cut off, or garbage.
        $this->assertRejected($h . '.' . $p . '.', 'Invalid token signature');
        $this->assertRejected($h . '.' . $p . '.AAAA', 'Invalid token signature');
    }

    public static function brokenTokens(): array
    {
        return [
            'empty'           => [''],
            'one part'        => ['abc'],
            'two parts'       => ['abc.def'],
            'four parts'      => ['a.b.c.d'],
            'not json'        => [self::b64('nope') . '.' . self::b64('nope') . '.sig'],
            'json null'       => [self::b64('null') . '.' . self::b64('null') . '.sig'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('brokenTokens')]
    public function test_malformed_tokens_are_refused_without_crashing(string $token): void
    {
        $this->expectException(\Exception::class);

        $this->verify($token);
    }

    public function test_only_rs256_is_accepted(): void
    {
        // "alg: none" and a downgraded HMAC are the classic ways to forge a JWT.
        $this->assertRejected($this->token([], ['alg' => 'none']), 'Unexpected algorithm');
        $this->assertRejected($this->token([], ['alg' => 'HS256']), 'Unexpected algorithm');
        $this->assertRejected($this->token([], ['alg' => 'rs256']), 'Unexpected algorithm');
        $this->assertRejected($this->token([], ['alg' => '']), 'Unexpected algorithm');
    }

    public function test_the_key_id_must_exist_and_match_a_published_key(): void
    {
        $this->assertRejected($this->token([], ['kid' => 'unknown-kid']), 'Public key not found');

        $header = self::b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));          // no kid at all
        $this->assertRejected($header . '.' . self::b64(json_encode(['sub' => 'x'])) . '.sig', 'Missing kid');
    }

    public function test_an_unusable_published_key_is_refused(): void
    {
        $this->googleKeys([self::KID => 'not a certificate']);

        $this->assertRejected($this->token(), 'Failed to load public key');
    }

    public static function badClaims(): array
    {
        // Times are written as 'now+N' and resolved when the test RUNS (a provider runs earlier, which would skew the clock).
        return [
            'expired'                  => [['exp' => 'now-1'], 'Token has expired'],
            'no expiry'                => [['exp' => 0], 'Token has expired'],
            'issued in the future'     => [['iat' => 'now+301'], 'issued in the future'],
            'wrong issuer'             => [['iss' => 'https://securetoken.google.com/other-project'], 'Invalid issuer'],
            'issuer is a look-alike'   => [['iss' => 'https://securetoken.google.com/test-project.evil'], 'Invalid issuer'],
            'no issuer'                => [['iss' => ''], 'Invalid issuer'],
            'token for another app'    => [['aud' => 'other-project'], 'Invalid audience'],
            'no subject'               => [['sub' => ''], 'Missing subject'],
        ];
    }

    public function test_a_list_where_a_single_name_belongs_is_refused_too(): void
    {
        $this->expectException(\Throwable::class);   // refused (the exact message is not important, the refusal is)

        $this->verify($this->token(['aud' => [self::PROJECT, 'other']]));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badClaims')]
    public function test_a_validly_signed_token_with_bad_claims_is_refused(array $claims, string $why): void
    {
        foreach ($claims as $k => $v) {
            if (is_string($v) && preg_match('/^now([+-]\d+)$/', $v, $m)) {
                $claims[$k] = time() + (int) $m[1];
            }
        }
        $this->assertRejected($this->token($claims), $why);
    }

    public function test_a_little_clock_difference_is_tolerated_but_not_a_big_one(): void
    {
        $this->assertSame(self::UID, $this->verify($this->token(['iat' => time() + 200]))['sub']);   // 200 s ahead: fine (limit is 300)
        $this->assertSame(self::UID, $this->verify($this->token(['exp' => time() + 2]))['sub']);      // about to expire, still valid now
    }

    public function test_a_token_for_this_project_is_not_valid_for_another(): void
    {
        $this->expectException(\Exception::class);

        $this->v->verify($this->token(), 'a-different-project');
    }

    public function test_googles_keys_are_fetched_once_and_then_cached(): void
    {
        $this->verify($this->token());
        $this->verify($this->token(['sub' => 'another-user']));
        $this->verify($this->token());

        Http::assertSentCount(1);
    }

    public function test_when_googles_keys_cannot_be_fetched_nobody_gets_in(): void
    {
        $this->googleKeys(null, 500);

        $this->assertRejected($this->token(), 'Failed to fetch Firebase public keys');
    }

    public function test_a_failed_key_fetch_is_not_cached_as_if_it_were_good(): void
    {
        $this->googleKeys(null, 500);
        try { $this->verify($this->token()); } catch (\Exception) {}

        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['www.googleapis.com/*' => Http::response([self::KID => self::pair('good')['cert']], 200)]);

        $this->assertSame(self::UID, $this->verify($this->token())['sub']);   // recovers on the next try
    }
}
