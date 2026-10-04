<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Coordinates -> human-readable place name ("Galle Road, Bambalapitiya, Colombo").
 *
 * The Android app does this with the phone's free built-in Geocoder. A browser
 * has no equivalent, and Google's Geocoding API is billed per request, so this
 * uses OpenStreetMap's Nominatim (free) behind a cache:
 *
 *  - Coordinates are rounded to 4 decimals (~11 m) so a vehicle that parks at
 *    the same place every day costs ONE lookup, ever. Hits live 30 days.
 *  - Misses ("no result" / provider down) are cached for 10 minutes so a failing
 *    provider is never hammered.
 *  - Nominatim's policy asks for <= 1 request/second and an identifying
 *    User-Agent; the controller is throttled per user and the browser queues its
 *    calls. If Nominatim answers 429 we simply return null and the UI shows the
 *    coordinates.
 *
 * Production note: Nominatim's public server is fine for this volume (cache
 * absorbs repeats). If usage grows, point GEOCODER_URL at a self-hosted
 * Nominatim or swap this class for a paid provider — callers only see resolve().
 */
class ReverseGeocoder
{
    private const HIT_TTL  = 60 * 60 * 24 * 30;
    private const MISS_TTL = 60 * 10;

    /** Cached address only — never calls the network. Used by PDF/CSV exports. */
    public function peek(float $lat, float $lng): ?string
    {
        $v = Cache::get($this->key($lat, $lng));
        return is_string($v) && $v !== '' ? $v : null;
    }

    /**
     * @param bool|null $fromCache set to true when answered without a provider call
     *                             (lets the browser skip its 1 request/second pacing)
     */
    public function resolve(float $lat, float $lng, ?bool &$fromCache = null): ?string
    {
        $fromCache = true;
        if (!$this->validCoords($lat, $lng)) {
            return null;
        }

        $key    = $this->key($lat, $lng);
        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached === '' ? null : $cached;   // '' = remembered miss
        }

        $fromCache = false;

        $address = $this->lookup($lat, $lng);
        Cache::put($key, $address ?? '', $address ? self::HIT_TTL : self::MISS_TTL);
        return $address;
    }

    private function lookup(float $lat, float $lng): ?string
    {
        $base = rtrim((string) config('services.geocoder.url', 'https://nominatim.openstreetmap.org'), '/');
        try {
            $res = Http::timeout(4)
                ->withHeaders([
                    // Required by Nominatim's usage policy.
                    'User-Agent'      => config('services.geocoder.user_agent', 'ShaloTrack-Fleet/1.0'),
                    'Accept-Language' => 'en',
                ])
                ->get($base . '/reverse', [
                    'format'         => 'jsonv2',
                    'lat'            => number_format($lat, 6, '.', ''),
                    'lon'            => number_format($lng, 6, '.', ''),
                    'zoom'           => 18,
                    'addressdetails' => 1,
                ]);

            if (!$res->successful()) {
                Log::info('ReverseGeocoder: provider status', ['status' => $res->status()]);
                return null;
            }
            return self::format($res->json());
        } catch (\Throwable $e) {
            Log::info('ReverseGeocoder: lookup failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Same idea as the Android AddressResolver: prefer clean structured parts
     * (road, area, town) over the provider's long display_name.
     *
     * @param mixed $json decoded Nominatim response
     */
    public static function format(mixed $json): ?string
    {
        if (!is_array($json) || isset($json['error'])) {
            return null;
        }
        $a = is_array($json['address'] ?? null) ? $json['address'] : [];

        $road = $a['road'] ?? $a['pedestrian'] ?? $a['footway'] ?? $a['path'] ?? null;
        $area = $a['suburb'] ?? $a['neighbourhood'] ?? $a['quarter'] ?? $a['city_district'] ?? $a['hamlet'] ?? null;
        $town = $a['city'] ?? $a['town'] ?? $a['village'] ?? $a['municipality'] ?? $a['county'] ?? null;

        $parts = [];
        foreach ([$road, $area, $town] as $p) {
            $p = is_string($p) ? trim($p) : '';
            if ($p !== '' && !in_array($p, $parts, true)) {
                $parts[] = $p;
            }
        }
        if ($parts) {
            return implode(', ', $parts);
        }

        $name = $json['name'] ?? null;
        if (is_string($name) && trim($name) !== '') {
            return trim($name);
        }
        // Last resort: first two segments of display_name.
        $display = is_string($json['display_name'] ?? null) ? $json['display_name'] : '';
        $segs = array_slice(array_map('trim', explode(',', $display)), 0, 2);
        $segs = array_values(array_filter($segs));
        return $segs ? implode(', ', $segs) : null;
    }

    private function key(float $lat, float $lng): string
    {
        return 'geo:' . number_format($lat, 4, '.', '') . ',' . number_format($lng, 4, '.', '');
    }

    private function validCoords(float $lat, float $lng): bool
    {
        return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180
            && !($lat == 0.0 && $lng == 0.0);
    }
}