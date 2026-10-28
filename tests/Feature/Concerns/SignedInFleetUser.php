<?php

namespace Tests\Feature\Concerns;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Monolog\Handler\NullHandler;

/**
 * Shared set-up for the Fleet write-action tests (F3).
 *
 * Fleet has no database: every action is a call to the C# API. These tests therefore fake that API
 * (Http::fake), sign a customer in through the session, and check three things for every write:
 *   1. what the browser may send (validation) and what the portal forwards to the API (allowlist);
 *   2. who may call it (no session, expired session);
 *   3. what the customer is told when the API says no (ApiErrors mapping).
 * Nothing here touches a real database, the real API, or the real log files.
 */
trait SignedInFleetUser
{
    protected const API = 'https://api.test';
    protected const UUID = '11111111-1111-4111-8111-111111111111';
    protected const UUID2 = '22222222-2222-4222-8222-222222222222';

    protected function bootFleetWriteTest(): void
    {
        $this->withoutVite();
        config([
            'shalotrack.api_base_url' => self::API,
            // The error pipeline writes log lines; keep them out of storage/logs while testing.
            'logging.default' => 'null',
            'logging.channels.app_errors' => ['driver' => 'monolog', 'handler' => NullHandler::class],
        ]);
    }

    /** A signed-in customer. Pass extra session keys (e.g. customer_id) as needed. */
    protected function asCustomer(string $uid = 'uid-1', array $extra = []): static
    {
        return $this->withSession([
            'firebase_token'            => 'tok-' . $uid,
            'firebase_uid'              => $uid,
            'firebase_token_expires_at' => time() + 3000,
        ] + $extra);
    }

    /** Fake the API: ['path-fragment' => Http::response(...)]. First matching pattern wins. */
    protected function fakeApi(array $routes): void
    {
        Http::fake(collect($routes)->mapWithKeys(fn ($v, $k) => ['api.test/api/' . ltrim($k, '/') => $v])->all());
    }

    protected function ok(array $body = ['success' => true]): \GuzzleHttp\Promise\PromiseInterface
    {
        return Http::response($body, 200);
    }

    /** @return Request|null the one request the portal sent to the API for this method + path suffix */
    protected function sentTo(string $method, string $pathSuffix): ?Request
    {
        foreach (Http::recorded() as [$request]) {
            if ($request->method() === $method && str_ends_with(parse_url($request->url(), PHP_URL_PATH), $pathSuffix)) {
                return $request;
            }
        }
        return null;
    }

    protected function assertSentBody(string $method, string $pathSuffix, array $expected): void
    {
        $req = $this->sentTo($method, $pathSuffix);
        $this->assertNotNull($req, "$method …$pathSuffix was never sent to the API");
        $this->assertEquals($expected, $req->data(), "body forwarded by $method …$pathSuffix");
    }
}
