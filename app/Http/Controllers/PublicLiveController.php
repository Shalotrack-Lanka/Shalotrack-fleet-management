<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * The public, no-login live page and its data feed.
 *
 * Privacy rules enforced here as well as in the API (defence in depth):
 *  - the response is rebuilt from an allowlist: plate, position, trail, expiry, server time;
 *    nothing else the API might ever add can leak through;
 *  - every failure (unknown / expired / stopped / lapsed) is the same "gone" answer;
 *  - nothing is cached, indexed, framed, or logged with the token in it.
 */
class PublicLiveController extends Controller
{
    private const GONE = 'This link has expired or is no longer available.';

    public function __construct(private ShalotrackApiService $api) {}

    public function page(string $token): Response
    {
        return $this->harden(response()->view('live.show', ['token' => $token]));
    }

    public function data(Request $request, string $token): JsonResponse
    {
        $withTrail = $request->boolean('trail', true);

        try {
            $res = $this->api->getPublicLive($token, $withTrail);
        } catch (\Exception $e) {
            $code = (int) $e->getCode();

            if ($code === 404) {
                return $this->harden(response()->json(['success' => false, 'gone' => true, 'message' => self::GONE], 404));
            }
            if ($code === 429) {
                return $this->harden(response()->json(['success' => false, 'message' => 'Busy. Retrying shortly.'], 429));
            }

            Log::warning('PublicLiveController: data failed', ['code' => $code]);   // never the token
            return $this->harden(response()->json(['success' => false, 'message' => 'Temporarily unavailable.'], 502));
        }

        $d = is_array($res['data'] ?? null) ? $res['data'] : null;
        $p = is_array($d['position'] ?? null) ? $d['position'] : null;

        if ($d === null || $p === null) {
            return $this->harden(response()->json(['success' => false, 'gone' => true, 'message' => self::GONE], 404));
        }

        $out = [
            'plateNumber' => (string) ($d['plateNumber'] ?? ''),
            'expiresAt'   => self::utc($d['expiresAt'] ?? null),
            'serverTime'  => self::utc($d['serverTime'] ?? null),
            'position'    => [
                'latitude'   => (float) ($p['latitude'] ?? 0),
                'longitude'  => (float) ($p['longitude'] ?? 0),
                'speedKmh'   => (float) ($p['speedKmh'] ?? 0),
                'heading'    => (float) ($p['heading'] ?? 0),
                'isMoving'   => (bool) ($p['isMoving'] ?? false),
                'lastUpdate' => self::utc($p['lastUpdate'] ?? null),
            ],
        ];

        if ($withTrail) {
            $out['trail'] = array_values(array_map(
                fn ($t) => [
                    'latitude'  => (float) ($t['latitude'] ?? 0),
                    'longitude' => (float) ($t['longitude'] ?? 0),
                    'time'      => self::utc($t['time'] ?? null),
                ],
                array_filter(is_array($d['trail'] ?? null) ? $d['trail'] : [], 'is_array')
            ));
        }

        return $this->harden(response()->json(['success' => true, 'data' => $out]));
    }

    /**
     * ISO-8601 in UTC with an explicit Z. The API may serialise without an offset; a browser
     * would then read it as local time and be off by the viewer's UTC offset.
     */
    private static function utc(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }
        try {
            return Carbon::parse($value, 'UTC')->utc()->format('Y-m-d\TH:i:s\Z');
        } catch (\Throwable) {
            return null;
        }
    }

    private function harden(\Symfony\Component\HttpFoundation\Response $r): \Symfony\Component\HttpFoundation\Response
    {
        $r->headers->set('Cache-Control', 'no-store, private');
        $r->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $r->headers->set('X-Frame-Options', 'DENY');
        $r->headers->set('X-Content-Type-Options', 'nosniff');
        // The token lives in the URL path: send only the origin to third parties (Google Maps).
        $r->headers->set('Referrer-Policy', 'strict-origin');
        return $r;
    }
}