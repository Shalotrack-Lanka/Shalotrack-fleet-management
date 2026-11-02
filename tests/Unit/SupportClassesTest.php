<?php

namespace Tests\Unit;

use App\Support\Chart;
use App\Support\ChartSvg;
use App\Support\DeviceHealth;
use App\Support\VehicleIcon;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** F6: the pure helpers behind the health card, the PDF charts and the map icons. */
class SupportClassesTest extends TestCase
{
    private function now(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-10-29 12:00:00', 'UTC');
    }

    /** a device record last heard from $min minutes before "now" */
    private function rec(array $over = [], int $min = 2): array
    {
        $t = $this->now()->subMinutes($min)->toIso8601ZuluString();
        return $over + ['isOnline' => true, 'lastSeen' => $t, 'lastHeartbeat' => $t, 'batteryLevel' => 80, 'gpsSignal' => 5,
            'powerStatus' => 0, 'ignitionStatus' => true, 'movementStatus' => false, 'imei' => '123456789012345', 'deviceId' => 'secret-id'];
    }

    private function item(array $h, string $key): array
    {
        return collect($h['items'])->firstWhere('key', $key);
    }

    // ---- DeviceHealth ------------------------------------------------------------------------------------

    public function test_a_healthy_device_says_all_systems_normal(): void
    {
        $h = DeviceHealth::summarize($this->rec(), $this->now());

        $this->assertSame(['ok', 'All systems normal', true], [$h['level'], $h['headline'], $h['available']]);
        $this->assertSame('Online', $this->item($h, 'connection')['value']);
        $this->assertSame('80%', $this->item($h, 'battery')['value']);
        $this->assertSame('On', $this->item($h, 'ignition')['value']);
        $this->assertSame('Stopped', $this->item($h, 'movement')['value']);
        $this->assertSame('2 min ago', $h['lastContactAgo']);
    }

    public function test_the_summary_never_carries_the_imei_or_device_id(): void
    {
        $json = json_encode(DeviceHealth::summarize($this->rec(), $this->now()));

        $this->assertStringNotContainsString('123456789012345', $json);
        $this->assertStringNotContainsString('secret-id', $json);
    }

    public static function batteryLevels(): array
    {
        return [[100, 'ok', 'All systems normal'], [21, 'ok', 'All systems normal'], [20, 'warn', 'Tracker battery is low'], [11, 'warn', 'Tracker battery is low'],
            [10, 'bad', 'Tracker battery is critically low'], [0, 'bad', 'Tracker battery is critically low']];
    }

    #[DataProvider('batteryLevels')]
    public function test_battery_thresholds_match_the_phone_alert(int $pct, string $level, string $headline): void
    {
        $h = DeviceHealth::summarize($this->rec(['batteryLevel' => $pct]), $this->now());

        $this->assertSame([$level, $headline], [$h['level'], $h['headline']]);
    }

    public function test_a_battery_reading_outside_0_to_100_is_clamped_for_display(): void
    {
        $this->assertSame('100%', $this->item(DeviceHealth::summarize($this->rec(['batteryLevel' => 250]), $this->now()), 'battery')['value']);
        $this->assertSame('0%', $this->item(DeviceHealth::summarize($this->rec(['batteryLevel' => -5]), $this->now()), 'battery')['value']);
    }

    public function test_no_battery_reading_means_no_battery_row(): void
    {
        $h = DeviceHealth::summarize($this->rec(['batteryLevel' => null]), $this->now());

        $this->assertNull(collect($h['items'])->firstWhere('key', 'battery'));
    }

    public static function powerValues(): array
    {
        return [[1, true], ['1', true], ['Disconnected', true], ['disconnected', true], [0, false], ['Connected', false], [null, false], ['', false], [2, false]];
    }

    #[DataProvider('powerValues')]
    public function test_the_power_status_is_understood_as_number_or_name(mixed $v, bool $unplugged): void
    {
        $h = DeviceHealth::summarize($this->rec(['powerStatus' => $v]), $this->now());

        $this->assertSame($unplugged ? 'bad' : 'ok', $h['level']);
        $this->assertSame($unplugged ? 'Disconnected' : 'Connected', $this->item($h, 'power')['value']);
        if ($unplugged) {
            $this->assertSame('Tracker is disconnected from the vehicle’s power', $h['headline']);
        }
    }

    public function test_no_gps_fix_is_a_warning_and_a_fix_is_good(): void
    {
        $none = DeviceHealth::summarize($this->rec(['gpsSignal' => 0]), $this->now());
        $this->assertSame(['warn', 'Waiting for a GPS fix', 'No fix'], [$none['level'], $none['headline'], $this->item($none, 'gps')['value']]);

        $this->assertSame('Good', $this->item(DeviceHealth::summarize($this->rec(['gpsSignal' => 1]), $this->now()), 'gps')['value']);
    }

    public function test_offline_beats_every_other_problem(): void
    {
        $h = DeviceHealth::summarize($this->rec(['isOnline' => false, 'batteryLevel' => 3, 'powerStatus' => 1, 'gpsSignal' => 0], 90), $this->now());

        $this->assertSame('offline', $h['level']);
        $this->assertSame('Not reporting — last contact 1 h ago', $h['headline']);
    }

    public function test_a_device_that_says_online_but_has_gone_quiet_is_treated_as_offline(): void
    {
        $this->assertSame('ok', DeviceHealth::summarize($this->rec([], 30), $this->now())['level']);                  // exactly 30 min: still fine
        $this->assertSame('offline', DeviceHealth::summarize($this->rec([], 31), $this->now())['level']);
    }

    public function test_a_device_never_heard_from_says_not_reporting_yet(): void
    {
        $h = DeviceHealth::summarize(['isOnline' => true], $this->now());

        $this->assertSame(['offline', 'Not reporting yet', null], [$h['level'], $h['headline'], $h['lastContact']]);
    }

    public function test_the_latest_of_last_seen_and_heartbeat_counts(): void
    {
        $old = $this->now()->subMinutes(120)->toIso8601ZuluString();
        $new = $this->now()->subMinutes(1)->toIso8601ZuluString();

        $this->assertSame('ok', DeviceHealth::summarize($this->rec(['lastSeen' => $old, 'lastHeartbeat' => $new]), $this->now())['level']);
        $this->assertSame('ok', DeviceHealth::summarize($this->rec(['lastSeen' => $new, 'lastHeartbeat' => $old]), $this->now())['level']);
    }

    public static function ages(): array
    {
        return [[0, 'just now', 'ok'], [1, '1 min ago', 'ok'], [59, '59 min ago', 'offline'], [60, '1 h ago', 'offline'], [1439, '23 h ago', 'offline'], [1440, '1 d ago', 'offline'], [4320, '3 d ago', 'offline']];
    }

    #[DataProvider('ages')]
    public function test_how_long_ago_is_written_the_same_way_every_time(int $min, string $text, string $level): void
    {
        $h = DeviceHealth::summarize($this->rec([], $min), $this->now());

        $this->assertSame($text, $h['lastContactAgo']);
    }

    public function test_garbage_values_do_not_crash_the_card(): void
    {
        $h = DeviceHealth::summarize(['isOnline' => 'yes', 'lastSeen' => 'not a date', 'batteryLevel' => 'abc', 'gpsSignal' => [], 'powerStatus' => []], $this->now());

        $this->assertTrue($h['available']);
        $this->assertSame('offline', $h['level']);
    }

    public function test_unavailable_is_just_a_flag(): void
    {
        $this->assertSame(['available' => false], DeviceHealth::unavailable());
    }

    // ---- VehicleIcon -------------------------------------------------------------------------------------

    public static function vehicleTypes(): array
    {
        return [['Car', 'car'], ['SUV', 'suv'], ['Jeep', 'suv'], ['Three-Wheeler', 'three-wheeler'], ['tuk tuk', 'three-wheeler'], ['Motor Bike', 'motorcycle'],
            ['Scooter', 'motorcycle'], ['Lorry', 'truck'], ['Pickup', 'truck'], ['Van', 'van'], ['HIACE', 'van'], ['Taxi', 'car'], ['Bus', null], ['Mini Bus', null],
            ['Other', null], ['', null], [null, null], ['  ', null], ['🚗', null]];
    }

    #[DataProvider('vehicleTypes')]
    public function test_a_typed_vehicle_type_maps_to_one_icon_or_the_generic_marker(?string $type, ?string $key): void
    {
        $this->assertSame($key, VehicleIcon::key($type));
    }

    public function test_the_icon_url_is_a_fixed_static_file_and_the_colour_is_whitelisted(): void
    {
        $this->assertStringContainsString('vehicle-icons/car-blue.png?v=1', VehicleIcon::url('Car'));
        $this->assertStringContainsString('vehicle-icons/car-green.png', VehicleIcon::url('Car', 'green'));
        $this->assertStringContainsString('car-blue.png', VehicleIcon::url('Car', '../../etc/passwd'));
        $this->assertNull(VehicleIcon::url('Bus'));
    }

    public function test_every_icon_the_rules_can_name_exists_on_disk(): void
    {
        foreach (array_unique(array_filter(array_column(VehicleIcon::RULES, 1))) as $key) {
            foreach (['green', 'blue'] as $c) {
                $this->assertFileExists(public_path("vehicle-icons/$key-$c.png"));
            }
        }
    }

    // ---- ChartSvg + Chart --------------------------------------------------------------------------------

    private function series(): array
    {
        return [['name' => 'km', 'color' => '#FA6908', 'values' => [3, 0, 12.5]], ['name' => 'max', 'color' => '#2563eb', 'values' => [1, 2, 3]]];
    }

    public function test_the_svg_chart_is_well_formed_and_draws_one_rect_per_non_zero_bar(): void
    {
        $svg = ChartSvg::bars(['Mon', 'Tue', 'Wed'], $this->series(), 520, 190, 'km');

        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($svg), 'the SVG must be valid XML');
        $this->assertSame(1 + 5, $doc->getElementsByTagName('rect')->length);   // background + 5 non-zero bars
        $this->assertStringContainsString('>km<', $svg);
    }

    public function test_text_in_the_svg_chart_is_escaped_and_colours_are_whitelisted(): void
    {
        $svg = ChartSvg::bars(['<script>x</script>', 'a&b'], [['name' => 'n', 'color' => 'red;" onload="x', 'values' => [1, 2]]], 400, 150, '"><x>');

        $this->assertStringNotContainsString('<script>', $svg);
        $this->assertStringNotContainsString('onload', $svg);
        $this->assertStringContainsString('#FA6908', $svg);
        $this->assertTrue((new \DOMDocument())->loadXML($svg));
    }

    public function test_empty_and_all_zero_data_do_not_divide_by_zero(): void
    {
        foreach ([ChartSvg::bars([], [], 300, 100), ChartSvg::bars(['a'], [['name' => 'n', 'color' => '#000000', 'values' => [0]]], 300, 100)] as $svg) {
            $this->assertTrue((new \DOMDocument())->loadXML($svg));
            $this->assertStringNotContainsString('NAN', $svg);
            $this->assertStringNotContainsString('INF', $svg);
        }
    }

    public function test_the_svg_data_uri_decodes_back_to_the_same_svg(): void
    {
        $svg = ChartSvg::bars(['a'], $this->series(), 300, 100);

        $this->assertSame($svg, base64_decode(substr(ChartSvg::dataUri($svg), strlen('data:image/svg+xml;base64,'))));
    }

    public function test_many_labels_are_thinned_out_but_the_last_one_stays(): void
    {
        $labels = array_map(fn ($i) => "L$i", range(1, 40));

        $svg = ChartSvg::bars($labels, [['name' => 'n', 'color' => '#000000', 'values' => array_fill(0, 40, 1)]], 520, 190);

        $this->assertStringContainsString('>L40<', $svg);
        $this->assertLessThan(20, substr_count($svg, 'text-anchor="middle"'));
    }

    public function test_the_png_chart_is_a_real_png_data_uri(): void
    {
        $uri = Chart::bars(['Mon', 'Tue', 'Wed'], $this->series(), 520, 170, 'km');

        $this->assertStringStartsWith('data:image/', $uri);
        if (str_starts_with($uri, 'data:image/png;base64,')) {
            $png = base64_decode(substr($uri, strlen('data:image/png;base64,')), true);
            $this->assertNotFalse($png);
            $this->assertStringStartsWith("\x89PNG", $png);
            [$w, $h] = getimagesizefromstring($png);
            $this->assertSame([520 * 3, 170 * 3], [$w, $h]);
        }
    }

    public function test_the_png_chart_survives_empty_data_and_a_bad_colour(): void
    {
        $this->assertStringStartsWith('data:image/', Chart::bars([], [], 300, 120));
        $this->assertStringStartsWith('data:image/', Chart::bars(['a', 'b'], [['name' => 'n', 'color' => 'not-a-colour', 'values' => [0, 5]]], 300, 120, 'km'));
    }
}
