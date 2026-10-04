<?php

namespace App\Services;

use App\Support\LocalTime;
use Carbon\Carbon;

/**
 * Fetches report data from the C# API and normalises it for the on-screen
 * reports, the PDF exports and the CSV exports — one source of truth, so the
 * numbers in a download are always the numbers on screen.
 *
 * Nothing here trusts the browser for DATA (only for which vehicle/range to
 * load); ownership/sharing is enforced by the API on every call.
 *
 * All timestamps leave this class already converted to Sri Lanka time.
 */
class ReportBuilder
{
    /** The API rejects report ranges longer than this (VehicleStatsService.MaxReportRangeDays). */
    public const MAX_RANGE_DAYS = 90;

    public const PERIODS = [
        'today' => 'Today',
        'week'  => 'Last 7 Days',
        'month' => 'Last 30 Days',
        'all'   => 'All Time',
    ];

    public function __construct(private ShalotrackApiService $api) {}

    // ── Vehicle ──────────────────────────────────────────────────────────────

    /** @return array{id:string,plate:string,name:string,isDemo:bool,isShared:bool,owner:?string} */
    public function vehicle(string $vehicleId): array
    {
        $info = ['id' => $vehicleId, 'plate' => 'Vehicle', 'name' => '', 'isDemo' => false, 'isShared' => false, 'owner' => null];
        try {
            $profile    = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;
            if (!$customerId) return $info;

            foreach ($this->api->getTrackableVehicles($customerId) as $v) {
                if (strtolower((string) ($v['vehicleId'] ?? '')) !== strtolower($vehicleId)) continue;
                $info['plate']    = (string) ($v['vehicleNumber'] ?? 'Vehicle');
                $info['name']     = trim(($v['make'] ?? '') . ' ' . ($v['model'] ?? ''));
                $info['isDemo']   = (bool) ($v['isDemoVehicle'] ?? false);
                $info['isShared'] = (bool) ($v['isShared'] ?? false);
                $info['owner']    = $v['ownerName'] ?? null;
                break;
            }
        } catch (\Throwable $e) {
            if ((int) $e->getCode() === 401) throw $e;
            // header details are cosmetic — export anyway
        }
        return $info;
    }

    // ── Stats (period or custom range) ──────────────────────────────────────

    /**
     * @param array{period?:string,from?:string,to?:string} $range  either a period key, or Colombo dates from/to (Y-m-d)
     * @return array<string,mixed>
     */
    public function stats(string $vehicleId, array $range): array
    {
        if (!empty($range['period'])) {
            $period   = $range['period'];
            $response = $this->api->getVehicleStats($vehicleId, $period);
            $label    = self::PERIODS[$period] ?? $period;
        } else {
            [$fromUtc, $toUtc] = $this->rangeUtc($range['from'], $range['to']);
            $response = $this->api->getVehicleStatsForRange($vehicleId, $fromUtc, $toUtc);
            $label    = 'Custom Range';
        }
        $d = $response['data'] ?? $response;

        $daily = [];
        foreach (($d['dailyBreakdown'] ?? []) as $row) {
            // Daily rows are already Sri Lanka calendar days — do NOT shift time zone.
            $day = substr((string) ($row['date'] ?? ''), 0, 10);
            if ($day === '') continue;
            $c = Carbon::createFromFormat('Y-m-d', $day);
            $daily[] = [
                'date'        => $day,
                'label'       => $c->format('d M'),
                'weekday'     => $c->format('D'),
                'distanceKm'  => (float) ($row['distanceKm'] ?? 0),
                'trips'       => (int)   ($row['tripCount'] ?? 0),
                'stops'       => (int)   ($row['stopCount'] ?? 0),
                'avgSpeed'    => (float) ($row['averageSpeed'] ?? 0),
                'maxSpeed'    => (float) ($row['maxSpeed'] ?? 0),
                'ignitionMin' => (float) ($row['ignitionOnMinutes'] ?? 0),
            ];
        }
        usort($daily, fn($a, $b) => strcmp($a['date'], $b['date']));

        $summary = [
            'distanceKm'  => (float) ($d['totalDistanceKm'] ?? 0),
            'trips'       => (int)   ($d['totalTripCount'] ?? 0),
            'stops'       => (int)   ($d['totalStopCount'] ?? 0),
            'drivingMin'  => (float) ($d['totalDrivingMinutes'] ?? 0),
            'idleMin'     => (float) ($d['totalIdleMinutes'] ?? 0),
            'ignitionMin' => (float) ($d['totalIgnitionOnMinutes'] ?? 0),
            'maxSpeed'    => (float) ($d['maxSpeed'] ?? 0),
            'avgSpeed'    => (float) ($d['averageSpeed'] ?? 0),
            'overspeed'   => (int)   ($d['overspeedIncidentCount'] ?? 0),
        ];

        // Highlights, derived from the daily rows
        $active = array_values(array_filter($daily, fn($r) => $r['distanceKm'] > 0));
        $best   = null;
        foreach ($active as $r) {
            if ($best === null || $r['distanceKm'] > $best['distanceKm']) $best = $r;
        }

        return [
            'periodKey'   => $range['period'] ?? 'custom',
            'periodLabel' => $label,
            'from'        => LocalTime::parse($d['periodFrom'] ?? null),
            'to'          => LocalTime::parse($d['periodTo'] ?? null),
            'summary'     => $summary,
            'daily'       => $daily,
            'activeDays'  => count($active),
            'totalDays'   => count($daily),
            'bestDay'     => $best,
            'avgPerActiveDay' => count($active) ? $summary['distanceKm'] / count($active) : 0.0,
        ];
    }

    // ── Trips + stops for a Colombo date range ──────────────────────────────

    /** @return array{trips:array<int,array>,stops:array<int,array>,from:Carbon,to:Carbon} */
    public function trips(string $vehicleId, string $fromDate, string $toDate): array
    {
        [$fromUtc, $toUtc] = $this->rangeUtc($fromDate, $toDate);
        $response = $this->api->getTripSummary($vehicleId, $fromUtc, $toUtc);
        $d = $response['data'] ?? $response;

        $trips = [];
        foreach (($d['trips'] ?? []) as $t) {
            $start = LocalTime::parse($t['startTime'] ?? null);
            if (!$start) continue;
            $end = LocalTime::parse($t['endTime'] ?? null);
            $trips[] = [
                'start'      => $start,
                'end'        => $end,
                'minutes'    => (float) ($t['durationMinutes'] ?? 0),
                'distanceKm' => (float) ($t['distanceKm'] ?? 0),
                'maxSpeed'   => (float) ($t['maxSpeed'] ?? 0),
                'avgSpeed'   => (float) ($t['avgSpeed'] ?? 0),
                'inProgress' => (bool)  ($t['inProgress'] ?? false),
                'startLat'   => $t['startLatitude'] ?? null,
                'startLng'   => $t['startLongitude'] ?? null,
                'endLat'     => $t['endLatitude'] ?? null,
                'endLng'     => $t['endLongitude'] ?? null,
            ];
        }
        usort($trips, fn($a, $b) => $b['start'] <=> $a['start']);   // latest first

        $stops = [];
        foreach (($d['stops'] ?? []) as $s) {
            $start = LocalTime::parse($s['startTime'] ?? null);
            if (!$start) continue;
            $stops[] = [
                'start'      => $start,
                'end'        => LocalTime::parse($s['endTime'] ?? null),
                'minutes'    => (float) ($s['durationMinutes'] ?? 0),
                'lat'        => $s['latitude'] ?? null,
                'lng'        => $s['longitude'] ?? null,
                'inProgress' => (bool) ($s['inProgress'] ?? false),
            ];
        }
        usort($stops, fn($a, $b) => $b['start'] <=> $a['start']);

        return [
            'trips' => $trips,
            'stops' => $stops,
            'from'  => LocalTime::parse($fromUtc),
            'to'    => LocalTime::parse($toUtc),
        ];
    }

    // ── Alert report ─────────────────────────────────────────────────────────

    /** @return array{total:int,counts:array<string,int>,alerts:array<int,array>,from:Carbon,to:Carbon} */
    public function alerts(string $vehicleId, string $fromDate, string $toDate): array
    {
        [$fromUtc, $toUtc] = $this->rangeUtc($fromDate, $toDate);
        $response = $this->api->getAlertReport($vehicleId, $fromUtc, $toUtc);
        $d = $response['data'] ?? $response;

        $alerts = [];
        foreach (($d['alerts'] ?? []) as $a) {
            $at = LocalTime::parse($a['triggeredAt'] ?? null);
            if (!$at) continue;
            $alerts[] = [
                'at'      => $at,
                'type'    => (string) ($a['alertType'] ?? 'Unknown'),
                'message' => (string) ($a['message'] ?? ''),
                'lat'     => $a['latitude'] ?? null,
                'lng'     => $a['longitude'] ?? null,
                'isRead'  => (bool) ($a['isRead'] ?? false),
            ];
        }
        usort($alerts, fn($a, $b) => $b['at'] <=> $a['at']);

        $counts = [];
        foreach (($d['countsByType'] ?? []) as $type => $n) {
            $counts[(string) $type] = (int) $n;
        }
        arsort($counts);

        return [
            'total'  => (int) ($d['totalCount'] ?? count($alerts)),
            'counts' => $counts,
            'alerts' => $alerts,
            'from'   => LocalTime::parse($fromUtc),
            'to'     => LocalTime::parse($toUtc),
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Colombo calendar dates -> UTC ISO strings, with the API's 90-day limit applied.
     *
     * @return array{0:string,1:string}
     * @throws \InvalidArgumentException with a customer-safe message
     */
    public function rangeUtc(string $fromDate, string $toDate): array
    {
        $range = LocalTime::dayRangeUtc($fromDate, $toDate);
        if (!$range) {
            throw new \InvalidArgumentException('Please choose a valid date range.');
        }
        if ($fromDate > $toDate) {
            throw new \InvalidArgumentException('"From" date cannot be after "To" date.');
        }
        $days = Carbon::parse($fromDate)->diffInDays(Carbon::parse($toDate)) + 1;
        if ($days > self::MAX_RANGE_DAYS) {
            throw new \InvalidArgumentException('Reports are limited to ' . self::MAX_RANGE_DAYS . ' days at a time. Please choose a shorter range.');
        }
        if ($fromDate > LocalTime::now()->format('Y-m-d')) {
            throw new \InvalidArgumentException('The start date is in the future — there is no data yet.');
        }
        if ($toDate > LocalTime::now()->format('Y-m-d')) {
            // Future days hold no data; clamp so the API never sees a "to" in the future.
            $range = LocalTime::dayRangeUtc($fromDate, LocalTime::now()->format('Y-m-d'));
            $range[1] = Carbon::now('UTC')->format('Y-m-d\TH:i:s\Z');
        }
        return $range;
    }
}