<?php

namespace Tests\Feature\Concerns;

use Illuminate\Support\Facades\Http;

/**
 * A fake C# API for the report / export tests: one customer, one vehicle, one trip, one stop, one alert.
 * All times are UTC, as the real API sends them; the portal must show them in Sri Lanka time (UTC+5:30).
 */
trait ReportApiFixtures
{
    use SignedInFleetUser;

    /** Trip starts 20:00 UTC on 27 Oct = 01:30 on 28 Oct in Colombo. */
    protected function fakeReportApi(array $over = []): void
    {
        $vehicle = $over['vehicle'] ?? ['vehicleId' => self::UUID, 'vehicleNumber' => 'CAB-1234', 'make' => 'Toyota', 'model' => 'Axio', 'isDemoVehicle' => false];

        $this->fakeApi([
            'Customers/me'                  => $this->ok(['data' => ['customerId' => self::UUID2]]),
            'Vehicles/customer/*'           => $this->ok(['data' => [$vehicle]]),
            'VehicleShares/shared-with-me'  => $this->ok(['data' => []]),
            'VehicleStats/*'                => $over['stats'] ?? $this->ok(['data' => [
                'totalDistanceKm' => 12.5, 'totalTripCount' => 2, 'totalStopCount' => 1, 'totalDrivingMinutes' => 45,
                'totalIdleMinutes' => 10, 'totalIgnitionOnMinutes' => 60, 'maxSpeed' => 80, 'averageSpeed' => 40,
                'overspeedIncidentCount' => 1,
                'periodFrom' => '2026-10-27T18:30:00Z', 'periodTo' => '2026-10-28T18:29:59Z',
                'dailyBreakdown' => [[
                    'date' => '2026-10-28', 'distanceKm' => 12.5, 'tripCount' => 2, 'stopCount' => 1,
                    'averageSpeed' => 40, 'maxSpeed' => 80, 'ignitionOnMinutes' => 60,
                ]],
            ]]),
            'GpsTracking/trips*'            => $over['trips'] ?? $this->ok(['data' => [
                'trips' => [[
                    'startTime' => '2026-10-27T20:00:00Z', 'endTime' => '2026-10-27T20:45:00Z', 'durationMinutes' => 45,
                    'distanceKm' => 12.5, 'maxSpeed' => 80, 'avgSpeed' => 40, 'inProgress' => false,
                ]],
                'stops' => [[
                    'startTime' => '2026-10-27T20:45:00Z', 'endTime' => null, 'durationMinutes' => 5,
                    'latitude' => 6.9271, 'longitude' => -79.8612, 'inProgress' => true,
                ]],
            ]]),
            'Alerts/report*'                => $over['alerts'] ?? $this->ok(['data' => [
                'totalCount' => 1, 'countsByType' => ['Overspeed' => 1],
                'alerts' => [[
                    'triggeredAt' => '2026-10-27T20:10:00',   // no Z on purpose: the API sends UTC without an offset
                    'alertType' => 'Overspeed', 'message' => 'Speed 80 km/h', 'latitude' => 6.9, 'longitude' => 79.8, 'isRead' => false,
                ]],
            ]]),
        ]);
    }

    /** Parse a CSV download into rows (BOM removed). */
    protected function csvRows(string $content): array
    {
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content, 'Excel needs the UTF-8 BOM');
        $fh = fopen('php://memory', 'w+');
        fwrite($fh, substr($content, 3));
        rewind($fh);
        $rows = [];
        while (($r = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
            $rows[] = $r;
        }
        fclose($fh);
        return $rows;
    }
}
