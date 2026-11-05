<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/**
 * F9: a signed-in customer's pages must not be kept by the browser.
 *
 * Found by hand on 3 Nov 2026 (case 6.3): after "Sign out", pressing Back showed the dashboard with the
 * customer's vehicles again, taken from the browser's own copy. The fix is one rule in SecurityHeaders:
 * HTML pages shown to a signed-in customer say "no-store".
 */
class SignedInPageCacheTest extends TestCase
{
    use SignedInFleetUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
    }

    public static function pages(): array
    {
        return array_map(fn ($p) => [$p], [
            '/dashboard', '/vehicles', '/trips', '/reports', '/stats', '/geofences', '/sharing',
            '/complaints', '/renewals', '/profile', '/emergency-contacts', '/saved-places',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_a_signed_in_page_is_never_kept_by_the_browser(string $page): void
    {
        $this->fakeApi(['*' => Http::response(['data' => []], 200)]);

        $cache = (string) $this->asCustomer()->get($page)->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cache, "$page may be shown again by the Back button after sign out");
        $this->assertStringContainsString('private', $cache);
    }

    public function test_a_stranger_gets_no_such_header_so_public_pages_are_unchanged(): void
    {
        $this->assertStringNotContainsString('no-store', (string) $this->get('/')->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('no-store', (string) $this->get('/login')->headers->get('Cache-Control'));
    }

    public function test_after_signing_out_the_same_page_is_no_longer_served(): void
    {
        $this->fakeApi(['*' => Http::response(['data' => []], 200)]);

        $this->asCustomer()->get('/profile')->assertOk();
        $this->post('/logout');

        $this->flushSession();
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_the_address_lookup_keeps_its_own_cache_rule(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response(['address' => ['road' => 'Galle Road']], 200)]);

        $cache = (string) $this->asCustomer()->getJson('/geocode/reverse?lat=6.9147&lng=79.8553')->assertOk()
            ->headers->get('Cache-Control');

        $this->assertStringContainsString('max-age=3600', $cache, 'JSON is not an HTML page: left alone');
        $this->assertStringNotContainsString('no-store', $cache);
    }

    public function test_a_response_that_already_says_no_store_is_not_rewritten(): void
    {
        $request = Request::create('/anything');
        $request->setLaravelSession(app('session.store'));
        $request->session()->put('firebase_token', 'tok');

        $download = (new SecurityHeaders())->handle($request, function () {
            $r = new Response('x', 200, ['Content-Type' => 'text/html; charset=UTF-8']);
            $r->headers->set('Cache-Control', 'no-store, max-age=0');
            return $r;
        });

        // Symfony adds "private" to a header that has none; what matters is that max-age=0 survived (our rule would drop it).
        $this->assertStringContainsString('max-age=0', (string) $download->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $download->headers->get('Cache-Control'));
    }

    public function test_the_rule_needs_a_real_sign_in_not_just_a_session(): void
    {
        $request = Request::create('/anything');
        $request->setLaravelSession(app('session.store'));

        $res = (new SecurityHeaders())->handle($request, fn () => new Response('x', 200, ['Content-Type' => 'text/html']));

        $this->assertStringNotContainsString('no-store', (string) $res->headers->get('Cache-Control'));
    }
}
