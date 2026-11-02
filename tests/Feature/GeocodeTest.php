<?php

namespace Tests\Feature;

use App\Services\ReverseGeocoder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/** F6: turning coordinates into a place name (OpenStreetMap, free) without hammering it. */
class GeocodeTest extends TestCase
{
    use SignedInFleetUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
        Cache::flush();
    }

    private function nominatim(array|int $answer = ['address' => ['road' => 'Galle Road', 'suburb' => 'Bambalapitiya', 'city' => 'Colombo']]): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['nominatim.openstreetmap.org/*' => is_int($answer) ? Http::response([], $answer) : Http::response($answer, 200)]);
    }

    public function test_a_stranger_cannot_use_the_geocoder(): void
    {
        Http::fake();

        $this->getJson('/geocode/reverse?lat=6.9&lng=79.8')->assertStatus(401);

        Http::assertNothingSent();
    }

    public static function badCoordinates(): array
    {
        return ['no lat' => ['', '79.8'], 'no lng' => ['6.9', ''], 'lat 91' => ['91', '79.8'], 'lat -91' => ['-91', '79.8'], 'lng 181' => ['6.9', '181'], 'lng -181' => ['6.9', '-181'], 'text' => ['north', 'east']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badCoordinates')]
    public function test_bad_coordinates_never_reach_the_provider(string $lat, string $lng): void
    {
        Http::fake();

        $this->asCustomer()->getJson("/geocode/reverse?lat=$lat&lng=$lng")->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_the_address_is_built_from_road_area_and_town_and_sent_with_our_identity(): void
    {
        $this->nominatim();

        $res = $this->asCustomer()->getJson('/geocode/reverse?lat=6.9147&lng=79.8553')->assertOk();

        $this->assertSame('Galle Road, Bambalapitiya, Colombo', $res->json('address'));
        $this->assertFalse($res->json('cached'));
        $this->assertStringContainsString('private', $res->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=3600', $res->headers->get('Cache-Control'));

        Http::assertSent(fn ($r) => str_contains($r->url(), 'nominatim.openstreetmap.org/reverse')
            && $r->hasHeader('User-Agent') && $r['format'] === 'jsonv2' && $r['lat'] === '6.914700' && $r['lon'] === '79.855300');
    }

    public function test_the_same_place_is_asked_once_even_if_the_second_reading_differs_by_a_few_metres(): void
    {
        $this->nominatim();

        $first  = $this->asCustomer()->getJson('/geocode/reverse?lat=6.91470&lng=79.85530')->assertOk();
        $second = $this->asCustomer()->getJson('/geocode/reverse?lat=6.914704&lng=79.855296')->assertOk();   // same 4-decimal cell

        $this->assertFalse($first->json('cached'));
        $this->assertTrue($second->json('cached'));
        $this->assertSame($first->json('address'), $second->json('address'));
        Http::assertSentCount(1);
    }

    public function test_a_place_the_provider_does_not_know_is_a_normal_answer_and_the_miss_is_remembered(): void
    {
        $this->nominatim(['error' => 'Unable to geocode']);

        $a = $this->asCustomer()->getJson('/geocode/reverse?lat=1.0&lng=1.0')->assertOk();
        $b = $this->asCustomer()->getJson('/geocode/reverse?lat=1.0&lng=1.0')->assertOk();

        $this->assertNull($a->json('address'));
        $this->assertNull($b->json('address'));
        $this->assertTrue($b->json('cached'));
        Http::assertSentCount(1);   // a failing provider is not hammered
    }

    public function test_a_provider_outage_or_rate_limit_gives_null_not_an_error_page(): void
    {
        foreach ([429, 500, 503] as $i => $status) {
            Cache::flush();
            $this->nominatim($status);

            $this->asCustomer()->getJson('/geocode/reverse?lat=7.' . $i . '&lng=80.1')->assertOk()->assertJsonPath('address', null)->assertJsonPath('success', true);
        }
    }

    public function test_a_provider_that_cannot_be_reached_gives_null(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['nominatim.openstreetmap.org/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout')]);

        $this->asCustomer()->getJson('/geocode/reverse?lat=6.9&lng=79.8')->assertOk()->assertJsonPath('address', null);
    }

    public function test_the_formatter_prefers_clean_parts_and_never_repeats_one(): void
    {
        $this->assertSame('Main Street, Galle', ReverseGeocoder::format(['address' => ['pedestrian' => 'Main Street', 'town' => 'Galle', 'city' => 'Galle']]));
        $this->assertSame('Temple Road, Kandy', ReverseGeocoder::format(['address' => ['road' => 'Temple Road', 'city' => 'Kandy', 'town' => 'Kandy']]));
        $this->assertNull(ReverseGeocoder::format(['error' => 'x']));
        $this->assertNull(ReverseGeocoder::format('not json'));
        $this->assertNull(ReverseGeocoder::format(null));
    }

    public function test_peek_never_calls_the_network(): void
    {
        Http::fake();

        $this->assertNull((new ReverseGeocoder())->peek(6.9147, 79.8553));

        Http::assertNothingSent();
    }
}
