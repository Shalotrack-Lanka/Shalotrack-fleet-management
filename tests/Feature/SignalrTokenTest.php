<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/**
 * Test 10.6: GET /api/signalr-token.
 *
 * This is the ONLY endpoint that hands the Firebase token to the browser (the live-map WebSocket cannot
 * send headers). So these tests pin: a stranger gets a JSON 401 (never a redirect that fetch() would follow
 * silently), an expired session is refused, the answer is never cached, only the signed-in customer's own
 * token is returned, and the portal never calls the API for it.
 */
class SignalrTokenTest extends TestCase
{
    use SignedInFleetUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
    }

    public function test_a_stranger_gets_a_json_401_not_a_redirect_and_no_token(): void
    {
        Http::fake();

        $res = $this->get('/api/signalr-token'); // no Accept header, exactly like the pages' fetch()

        $res->assertStatus(401)->assertJsonPath('code', 'UNAUTHENTICATED');
        $this->assertArrayNotHasKey('token', $res->json());
        Http::assertNothingSent();
    }

    public function test_an_expired_session_is_refused_with_token_expired(): void
    {
        Http::fake();

        $this->withSession([
            'firebase_token'            => 'tok-old',
            'firebase_uid'              => 'uid-1',
            'firebase_token_expires_at' => time() - 10,
        ])->get('/api/signalr-token')
            ->assertStatus(401)
            ->assertJsonPath('code', 'TOKEN_EXPIRED')
            ->assertJsonMissingPath('token');
    }

    public function test_a_signed_in_customer_gets_only_their_own_token(): void
    {
        Http::fake();

        $this->asCustomer('uid-7')->get('/api/signalr-token')
            ->assertOk()
            ->assertExactJson(['token' => 'tok-uid-7']);

        $this->asCustomer('uid-8')->get('/api/signalr-token')
            ->assertOk()
            ->assertExactJson(['token' => 'tok-uid-8']);
    }

    public function test_the_token_answer_is_never_cached(): void
    {
        $res = $this->asCustomer()->get('/api/signalr-token')->assertOk();

        $cache = (string) $res->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cache);
        $this->assertStringContainsString('private', $cache);
    }

    public function test_it_is_json_and_get_only(): void
    {
        $this->asCustomer()->get('/api/signalr-token')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json');

        $this->asCustomer()->post('/api/signalr-token')->assertStatus(405);
        $this->asCustomer()->put('/api/signalr-token')->assertStatus(405);
        $this->asCustomer()->delete('/api/signalr-token')->assertStatus(405);
    }

    public function test_the_portal_does_not_call_the_api_to_hand_out_the_token(): void
    {
        Http::fake();

        $this->asCustomer()->get('/api/signalr-token')->assertOk();

        Http::assertNothingSent();
    }

    public function test_no_other_api_route_returns_the_token(): void
    {
        Http::fake();

        // The token must come from this one endpoint only. Page-data endpoints must never echo it.
        $this->fakeApi(['vehicles/*' => $this->ok(['data' => []])]);

        $body = $this->asCustomer('uid-9')->get('/api/vehicles/' . self::UUID);
        $this->assertStringNotContainsString('tok-uid-9', (string) $body->getContent());
    }
}
