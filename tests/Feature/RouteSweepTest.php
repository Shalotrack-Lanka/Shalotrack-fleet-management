<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/**
 * F6: a safety net that grows by itself. It reads the router, so a route added later is covered without anyone
 * remembering to write a test for it: every page behind the sign-in must turn a stranger away and never call the API.
 */
class RouteSweepTest extends TestCase
{
    use SignedInFleetUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
    }

    /** @return list<array{string,string}> every route that is behind FirebaseAuthenticated */
    private function protectedRoutes(): array
    {
        $out = [];
        foreach (Route::getRoutes() as $route) {
            if (! in_array(\App\Http\Middleware\FirebaseAuthenticated::class, $route->gatherMiddleware(), true)) {
                continue;
            }
            $uri = preg_replace('/\{[^}]+\}/', self::UUID, $route->uri());
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $out[] = [$method, '/' . $uri];
            }
        }
        return $out;
    }

    public function test_the_sweep_really_found_the_portal(): void
    {
        $this->assertGreaterThan(40, count($this->protectedRoutes()), 'the router read found too few protected routes');
    }

    public function test_a_stranger_is_turned_away_from_every_protected_route_and_the_api_is_never_called(): void
    {
        Http::fake();
        $wrong = [];

        foreach ($this->protectedRoutes() as [$method, $uri]) {
            // as the page's own JavaScript calls it (JSON): a clean 401
            $json = $this->json($method, $uri, []);
            if ($json->getStatusCode() !== 401) {
                $wrong[] = "$method $uri (json) -> {$json->getStatusCode()}";
            }
            // as a browser: sent to the login page
            $web = $this->call($method, $uri);
            // /api/* is always a JSON 401 (a redirect would be followed silently by fetch()); pages go to /login
            $ok = str_starts_with($uri, '/api/')
                ? $web->getStatusCode() === 401
                : ($web->isRedirect() && str_contains((string) $web->headers->get('Location'), '/login'));
            if (! $ok) {
                $wrong[] = "$method $uri (browser) -> {$web->getStatusCode()}";
            }
        }

        $this->assertSame([], $wrong, "routes that did not turn a stranger away:\n" . implode("\n", $wrong));
        Http::assertNothingSent();
    }
}
