<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/**
 * F3: who may use the write endpoints, how a bad id is handled, and what the customer is told when the API
 * refuses. One representative body per endpoint keeps this table-driven and cheap.
 */
class WriteAccessTest extends TestCase
{
    use SignedInFleetUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
    }

    /** Every state-changing route a signed-in customer has, with a body that passes validation. */
    public static function writeEndpoints(): array
    {
        $u = self::UUID;
        $vehicle = ['vehicleNumber' => 'CAB-1234', 'make' => 'Toyota', 'model' => 'Axio', 'year' => 2018];
        $fence = ['name' => 'Home', 'latitude' => 6.9, 'longitude' => 79.8, 'radiusMeters' => 200];

        return [
            'vehicle create'      => ['POST',   '/vehicles', $vehicle],
            'vehicle update'      => ['PUT',    "/vehicles/$u", $vehicle],
            'vehicle delete'      => ['DELETE', "/vehicles/$u", []],
            'device link'         => ['POST',   "/vehicles/$u/link-device", ['imei' => '123456789012345']],
            'device unlink'       => ['POST',   "/vehicles/$u/unlink-device", ['assignmentId' => $u]],
            'geofence create'     => ['POST',   '/geofences', $fence],
            'geofence update'     => ['PUT',    "/geofences/$u", $fence],
            'geofence delete'     => ['DELETE', "/geofences/$u", []],
            'share invite'        => ['POST',   '/sharing', ['vehicleId' => $u, 'phoneNumber' => '0771234567']],
            'share accept'        => ['POST',   "/sharing/$u/accept", []],
            'share decline'       => ['POST',   "/sharing/$u/decline", []],
            'share revoke'        => ['DELETE', "/sharing/$u", []],
            'contact create'      => ['POST',   '/emergency-contacts', ['name' => 'Mum', 'phoneNumber' => '0771234567']],
            'contact delete'      => ['DELETE', "/emergency-contacts/$u", []],
        ];
    }

    /** @dataProvider writeEndpoints */
    #[\PHPUnit\Framework\Attributes\DataProvider('writeEndpoints')]
    public function test_a_stranger_cannot_use_any_write_endpoint_and_the_api_is_never_called(string $method, string $url, array $body): void
    {
        Http::fake();

        $res = $this->json($method, $url, $body);

        $res->assertStatus(401)->assertJsonPath('code', 'UNAUTHENTICATED');
        Http::assertNothingSent();
    }

    /** @dataProvider writeEndpoints */
    #[\PHPUnit\Framework\Attributes\DataProvider('writeEndpoints')]
    public function test_an_expired_session_is_refused_and_cleared(string $method, string $url, array $body): void
    {
        Http::fake();

        $res = $this->withSession([
            'firebase_token' => 'old', 'firebase_uid' => 'u', 'firebase_token_expires_at' => time() - 10,
        ])->json($method, $url, $body);

        $res->assertStatus(401)->assertJsonPath('code', 'TOKEN_EXPIRED');
        Http::assertNothingSent();
    }

    /** @dataProvider writeEndpoints */
    #[\PHPUnit\Framework\Attributes\DataProvider('writeEndpoints')]
    public function test_the_customers_own_token_is_what_the_api_receives(string $method, string $url, array $body): void
    {
        $this->fakeApi(['*' => $this->ok(['success' => true, 'data' => ['customerId' => self::UUID, 'deviceId' => self::UUID2]])]);

        $this->asCustomer('uid-77', ['customer_id' => self::UUID])->json($method, $url, $body)->assertOk();

        $sent = Http::recorded();
        $this->assertNotEmpty($sent, 'the API must have been called');
        foreach ($sent as [$request]) {
            $this->assertSame('Bearer tok-uid-77', $request->header('Authorization')[0] ?? null, 'every API call carries the signed-in customer\'s token');
        }
    }

    /** @dataProvider writeEndpoints */
    #[\PHPUnit\Framework\Attributes\DataProvider('writeEndpoints')]
    public function test_a_401_from_the_api_signs_the_customer_out(string $method, string $url, array $body): void
    {
        $this->fakeApi(['*' => Http::response(['message' => 'x'], 401)]);

        $res = $this->asCustomer('uid-1', ['customer_id' => self::UUID])->json($method, $url, $body);

        $res->assertStatus(401)->assertJsonPath('expired', true)->assertJsonPath('code', 'TOKEN_EXPIRED');
        $this->assertNull(session('firebase_token'), 'the session must be flushed');
    }

    // ------------------------------------------------------------------ ids that are not ids

    public static function hostileIds(): array
    {
        return [
            'path separator' => ['a%2Fb'],
            'query string'   => ['abc%3Fadmin%3D1'],
            'fragment'       => ['x%23frag'],
            'dot-dot'        => ['..'],
        ];
    }

    /** @dataProvider hostileIds */
    #[\PHPUnit\Framework\Attributes\DataProvider('hostileIds')]
    public function test_an_id_that_could_redirect_the_api_call_never_reaches_the_api(string $id): void
    {
        Http::fake();

        foreach (['/vehicles/', '/geofences/', '/sharing/', '/emergency-contacts/'] as $base) {
            $this->asCustomer()->deleteJson($base . $id);
        }

        Http::assertNothingSent();
    }

    // ------------------------------------------------------------------ what the customer is told

    public static function apiFailures(): array
    {
        return [
            '402 subscription'   => [402, ['message' => 'pay'], 422, 'SUBSCRIPTION_EXPIRED'],
            '403 forbidden'      => [403, ['message' => 'no'], 422, 'FORBIDDEN'],
            '404 gone'           => [404, ['message' => 'no'], 422, 'NOT_FOUND'],
            '409 conflict'       => [409, ['message' => 'dup'], 422, 'CONFLICT'],
            '429 rate limited'   => [429, ['message' => 'slow'], 429, 'RATE_LIMITED'],
            '400 plain message'  => [400, ['message' => 'Name already used'], 422, 'REJECTED'],
            '500 server error'   => [500, ['message' => 'boom'], 422, 'FAILED'],
            '503 unavailable'    => [503, [], 422, 'FAILED'],
        ];
    }

    /** @dataProvider apiFailures */
    #[\PHPUnit\Framework\Attributes\DataProvider('apiFailures')]
    public function test_each_api_failure_becomes_one_clear_message_with_a_reference(int $apiStatus, array $apiBody, int $httpStatus, string $code): void
    {
        $this->fakeApi(['*' => Http::response($apiBody, $apiStatus)]);

        $res = $this->asCustomer()->deleteJson('/geofences/' . self::UUID);

        $res->assertStatus($httpStatus)->assertJsonPath('success', false)->assertJsonPath('code', $code);
        $this->assertNotSame('', (string) $res->json('message'));
        $this->assertMatchesRegularExpression('/^\d{8}-[0-9A-F]{8}$/', (string) $res->json('reference'), 'support needs a reference to find the log line');
    }

    public function test_machine_codes_and_internals_from_the_api_are_never_shown_to_the_customer(): void
    {
        $secrets = [
            ['message' => 'SQL_CONSTRAINT_VIOLATION', 'code' => 400],
            ['message' => 'System.Data.SqlClient.SqlException at /app/Repo.cs', 'code' => 400],
            ['message' => 'see https://internal.example/stack', 'code' => 422],
        ];

        foreach ($secrets as $s) {
            $this->fakeApi(['*' => Http::response(['message' => $s['message']], $s['code'])]);

            $res = $this->asCustomer()->deleteJson('/geofences/' . self::UUID);

            $this->assertSame('FAILED', $res->json('code'));
            $this->assertSame('Failed to delete geofence.', $res->json('message'), "leaked: {$s['message']}");
            Http::swap(app(\Illuminate\Http\Client\Factory::class));   // fresh fake for the next case
        }
    }

    public function test_an_api_timeout_asks_the_customer_to_retry(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

        $res = $this->asCustomer()->deleteJson('/geofences/' . self::UUID);

        $res->assertStatus(422)->assertJsonPath('code', 'API_TIMEOUT')->assertJsonPath('timeout', true);
        $this->assertStringNotContainsString('cURL', $res->getContent());
    }

    public function test_the_api_not_being_able_to_be_reached_never_echoes_the_exception_text(): void
    {
        Http::fake(fn () => throw new \RuntimeException('connection refused to 10.0.3.7:5001'));

        $res = $this->asCustomer()->deleteJson('/geofences/' . self::UUID);

        $this->assertStringNotContainsString('10.0.3.7', $res->getContent());
    }
}
