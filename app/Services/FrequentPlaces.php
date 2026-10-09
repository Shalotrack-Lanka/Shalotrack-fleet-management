<?php

namespace App\Services;

use App\Support\LocalTime;

/**
 * Turns trip summaries into "where does this vehicle usually go".
 *
 * Pure PHP, no I/O: every trip that has finished contributes its END point as one visit.
 * Visits closer than RADIUS_M to an existing group are merged into it (running centroid),
 * so a vehicle that parks in a slightly different spot each day still counts as one place.
 * Groups with fewer than MIN_VISITS visits are dropped: a place seen once is not "frequent".
 */
class FrequentPlaces
{
    public const RADIUS_M   = 150;
    public const MIN_VISITS = 2;
    public const MAX_PLACES = 15;

    /**
     * @param  array<int, array<string, mixed>> $trips          trip summaries from the API
     * @param  array<int, array<string, mixed>> $savedPlaces    customer's saved places (name, latitude, longitude)
     * @return array<int, array{lat: float, lng: float, visits: int, lastVisit: ?string, totalMinutes: int, savedName: ?string}>
     */
    public function build(array $trips, array $savedPlaces = []): array
    {
        $groups = [];

        foreach ($trips as $trip) {
            if (!is_array($trip)) {
                continue;
            }
            $lat = $trip['endLatitude']  ?? null;
            $lng = $trip['endLongitude'] ?? null;
            if (!is_numeric($lat) || !is_numeric($lng) || !self::valid((float) $lat, (float) $lng)) {
                continue;                                   // in-progress trip or a bad fix
            }
            if (!empty($trip['inProgress'])) {
                continue;
            }
            $lat = (float) $lat;
            $lng = (float) $lng;

            $when    = LocalTime::parse($trip['endTime'] ?? null);
            $minutes = (int) round((float) ($trip['durationMinutes'] ?? 0));

            $hit = null;
            foreach ($groups as $i => $g) {
                if (self::distanceM($lat, $lng, $g['lat'], $g['lng']) <= self::RADIUS_M) {
                    $hit = $i;
                    break;
                }
            }

            if ($hit === null) {
                $groups[] = ['lat' => $lat, 'lng' => $lng, 'visits' => 1, 'last' => $when, 'minutes' => $minutes];
                continue;
            }

            $g = &$groups[$hit];
            $n = $g['visits'];
            $g['lat'] = ($g['lat'] * $n + $lat) / ($n + 1);
            $g['lng'] = ($g['lng'] * $n + $lng) / ($n + 1);
            $g['visits']++;
            $g['minutes'] += $minutes;
            if ($when && (!$g['last'] || $when->greaterThan($g['last']))) {
                $g['last'] = $when;
            }
            unset($g);
        }

        $groups = array_values(array_filter($groups, fn ($g) => $g['visits'] >= self::MIN_VISITS));
        usort($groups, fn ($a, $b) => [$b['visits'], $b['minutes']] <=> [$a['visits'], $a['minutes']]);
        $groups = array_slice($groups, 0, self::MAX_PLACES);

        return array_map(function ($g) use ($savedPlaces) {
            return [
                'lat'          => round($g['lat'], 6),
                'lng'          => round($g['lng'], 6),
                'visits'       => $g['visits'],
                'lastVisit'    => $g['last']?->toIso8601String(),
                'totalMinutes' => $g['minutes'],
                'savedName'    => $this->savedNameNear($g['lat'], $g['lng'], $savedPlaces),
            ];
        }, $groups);
    }

    private function savedNameNear(float $lat, float $lng, array $savedPlaces): ?string
    {
        foreach ($savedPlaces as $p) {
            if (!is_array($p) || !is_numeric($p['latitude'] ?? null) || !is_numeric($p['longitude'] ?? null)) {
                continue;
            }
            if (self::distanceM($lat, $lng, (float) $p['latitude'], (float) $p['longitude']) <= self::RADIUS_M) {
                $name = trim((string) ($p['name'] ?? ''));
                return $name !== '' ? mb_substr($name, 0, 100) : 'Saved place';
            }
        }
        return null;
    }

    private static function valid(float $lat, float $lng): bool
    {
        // (0,0) is what a device reports with no GPS fix.
        return abs($lat) <= 90 && abs($lng) <= 180 && !($lat == 0.0 && $lng == 0.0);
    }

    /** Great-circle distance in metres. */
    public static function distanceM(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r  = 6371000;
        $p1 = deg2rad($lat1);
        $p2 = deg2rad($lat2);
        $dp = $p2 - $p1;
        $dl = deg2rad($lng2 - $lng1);
        $a  = sin($dp / 2) ** 2 + cos($p1) * cos($p2) * sin($dl / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a)));
    }
}