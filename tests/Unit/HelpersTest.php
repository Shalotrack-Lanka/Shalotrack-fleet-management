<?php

namespace Tests\Unit;

use App\Services\FrequentPlaces;
use App\Support\LocalTime;
use PHPUnit\Framework\TestCase;

/** F6: the small pure helpers every page relies on (time zone, distance, grouping). */
class HelpersTest extends TestCase
{
    // ---- LocalTime ----------------------------------------------------------------------------------------

    public function test_utc_becomes_sri_lanka_time_and_a_bare_value_is_read_as_utc(): void
    {
        $this->assertSame('28 Oct 2026, 08:00', LocalTime::format('2026-10-28T02:30:00Z'));
        $this->assertSame('28 Oct 2026, 08:00', LocalTime::format('2026-10-28 02:30:00'));       // no zone = UTC
        $this->assertSame('28 Oct 2026, 08:00', LocalTime::format('2026-10-28T08:00:00+05:30')); // own offset honoured
    }

    public function test_the_day_changes_at_sri_lanka_midnight_not_utc_midnight(): void
    {
        $this->assertSame('29 Oct 2026, 00:15', LocalTime::format('2026-10-28T18:45:00Z'));
    }

    public function test_empty_or_broken_values_use_the_fallback(): void
    {
        foreach ([null, '', '   ', 'not a date', [], 12.5] as $bad) {
            $this->assertSame('—', LocalTime::format($bad));
        }
        $this->assertSame('n/a', LocalTime::format(null, 'd M', 'n/a'));
    }

    public function test_a_picked_range_goes_to_the_api_as_utc(): void
    {
        $this->assertSame('2026-10-28T02:30:00Z', LocalTime::toApiUtc('2026-10-28T08:00'));
        $this->assertSame('2026-10-28T02:30:00Z', LocalTime::toApiUtc('2026-10-28T02:30:00Z'));
        $this->assertSame('2026-10-28T02:30:00Z', LocalTime::toApiUtc('2026-10-28T08:00:00+05:30'));
        $this->assertSame('2026-10-27T18:30:00Z', LocalTime::toApiUtc('2026-10-28'));            // a bare date = local midnight
    }

    public function test_whole_day_ranges_cover_the_local_day_exactly(): void
    {
        $this->assertSame(['2026-10-27T18:30:00Z', '2026-10-28T18:29:59Z'], LocalTime::dayRangeUtc('2026-10-28', '2026-10-28'));
        $this->assertSame(['2026-10-27T18:30:00Z', '2026-10-30T18:29:59Z'], LocalTime::dayRangeUtc('2026-10-28', '2026-10-30'));
    }

    public function test_impossible_calendar_dates_are_refused(): void
    {
        foreach ([['2026-02-30', '2026-03-01'], ['2026-13-01', '2026-13-02'], ['28/10/2026', '2026-10-28'], ['2026-10-28', 'x'], ['', ''], ['2026-1-5', '2026-10-28']] as [$f, $t]) {
            $this->assertNull(LocalTime::dayRangeUtc($f, $t), "$f → $t");
        }
        $this->assertNotNull(LocalTime::dayRangeUtc('2028-02-29', '2028-02-29'));   // a real leap day
    }

    public function test_durations_read_naturally(): void
    {
        $this->assertSame('0 min', LocalTime::duration(0));
        $this->assertSame('0 min', LocalTime::duration(-5));
        $this->assertSame('0 min', LocalTime::duration(null));
        $this->assertSame('45 min', LocalTime::duration(45));
        $this->assertSame('1h', LocalTime::duration(60));
        $this->assertSame('2h 05m', LocalTime::duration(125));
        $this->assertSame('2h 05m', LocalTime::duration('125.4'));
    }

    // ---- distance + frequent places -------------------------------------------------------------------------

    public function test_distance_is_right_on_known_pairs(): void
    {
        $this->assertEqualsWithDelta(0.0, FrequentPlaces::distanceM(6.9, 79.8, 6.9, 79.8), 0.001);
        $this->assertEqualsWithDelta(111195, FrequentPlaces::distanceM(0, 0, 1, 0), 200);          // one degree of latitude
        $this->assertEqualsWithDelta(FrequentPlaces::distanceM(6.9, 79.8, 7.3, 80.6), FrequentPlaces::distanceM(7.3, 80.6, 6.9, 79.8), 0.001);
        $this->assertEqualsWithDelta(115000, FrequentPlaces::distanceM(6.9271, 79.8612, 7.2906, 80.6337), 100000);   // Colombo-Kandy ballpark
    }

    private function t(float $lat, float $lng, array $over = []): array
    {
        return $over + ['endLatitude' => $lat, 'endLongitude' => $lng, 'endTime' => '2026-10-20T10:00:00Z', 'durationMinutes' => 10];
    }

    public function test_visits_within_150_metres_merge_and_beyond_it_do_not(): void
    {
        $places = (new FrequentPlaces())->build([
            $this->t(6.9000, 79.8000), $this->t(6.9010, 79.8000),   // ~111 m: merged
            $this->t(6.9100, 79.8000), $this->t(6.9115, 79.8000),   // ~167 m apart from each other: two separate singles
        ]);

        $this->assertCount(1, $places);
        $this->assertSame(2, $places[0]['visits']);
    }

    public function test_a_place_seen_once_is_not_frequent(): void
    {
        $this->assertSame([], (new FrequentPlaces())->build([$this->t(6.9, 79.8)]));
    }

    public function test_unfinished_and_unusable_trips_are_ignored(): void
    {
        $trips = [
            $this->t(6.9, 79.8, ['inProgress' => true]),
            $this->t(0, 0), $this->t(0, 0),                              // no GPS fix
            $this->t(91, 79.8), $this->t(6.9, 181),                      // impossible
            ['endLatitude' => 'abc', 'endLongitude' => 79.8],
            ['endLatitude' => null, 'endLongitude' => null],
            'not an array', null,
        ];

        $this->assertSame([], (new FrequentPlaces())->build($trips));
    }

    public function test_the_busiest_place_comes_first_and_the_list_is_capped(): void
    {
        $trips = [];
        for ($i = 0; $i < 20; $i++) {                                    // 20 places, 2 visits each, well apart
            $trips[] = $this->t(1 + $i * 0.1, 79.8);
            $trips[] = $this->t(1 + $i * 0.1, 79.8);
        }
        for ($i = 0; $i < 5; $i++) {
            $trips[] = $this->t(50.0, 10.0);                             // the busiest: 5 visits
        }

        $places = (new FrequentPlaces())->build($trips);

        $this->assertCount(FrequentPlaces::MAX_PLACES, $places);
        $this->assertSame(5, $places[0]['visits']);
        $this->assertEqualsWithDelta(50.0, $places[0]['lat'], 0.0001);
    }

    public function test_a_saved_place_names_a_nearby_group_and_a_blank_name_still_labels_it(): void
    {
        $two = [$this->t(6.9, 79.8), $this->t(6.9, 79.8)];
        $build = fn ($saved) => (new FrequentPlaces())->build($two, $saved)[0]['savedName'];

        $this->assertSame('Home', $build([['name' => 'Home', 'latitude' => 6.9005, 'longitude' => 79.8]]));
        $this->assertSame('Saved place', $build([['name' => '   ', 'latitude' => 6.9, 'longitude' => 79.8]]));
        $this->assertNull($build([['name' => 'Far', 'latitude' => 7.9, 'longitude' => 79.8]]));
        $this->assertNull($build([['name' => 'Broken', 'latitude' => 'x', 'longitude' => null], 'junk']));
        $this->assertSame(100, mb_strlen($build([['name' => str_repeat('é', 300), 'latitude' => 6.9, 'longitude' => 79.8]])));
    }
}
