<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use App\Support\LocalTime;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class TripController extends Controller
{
    public function __construct(private ShalotrackApiService $api) {}

    /**
     * GET /trips
     * Tracking page — shows ALL customer vehicles in a sidebar list (GPS-enabled
     * ones are clickable for live/history; non-GPS are grayed out).
     * Trip data and live tracking are handled via AJAX / SignalR on the front end.
     */
    public function index()
    {
        try {
            $profile    = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;

            $vehicles = [];
            if ($customerId) {
                // Owned + accepted shares (the API allows trip history for both)
                $all = $this->api->getTrackableVehicles($customerId);

                // Sort: GPS-enabled first; owned before shared; demo vehicle last
                // so the customer's own vehicles appear at the top of the list.
                // Non-GPS vehicles still show (grayed out) so the customer can
                // see their full fleet and knows they need to link a device.
                usort($all, function ($a, $b) {
                    $aGps    = (bool) ($a['hasGpsDevice']  ?? false);
                    $bGps    = (bool) ($b['hasGpsDevice']  ?? false);
                    $aDemo   = (bool) ($a['isDemoVehicle'] ?? false);
                    $bDemo   = (bool) ($b['isDemoVehicle'] ?? false);
                    $aShared = (bool) ($a['isShared']      ?? false);
                    $bShared = (bool) ($b['isShared']      ?? false);
                    if ($aGps !== $bGps)       return $bGps    <=> $aGps;     // GPS first
                    if ($aDemo !== $bDemo)     return $aDemo   <=> $bDemo;    // demo last
                    if ($aShared !== $bShared) return $aShared <=> $bShared;  // owned before shared
                    return 0;
                });

                $vehicles = $all;
            }

            return view('trips.index', [
                'vehicles' => $vehicles,
                'error'    => null,
            ]);

        } catch (\Exception $e) {
            Log::error('TripController: Failed to load', ['error' => $e->getMessage()]);
            if ($e->getCode() === 401) {
                Session::flush();
                return redirect('/login?expired=1');
            }
            return view('trips.index', [
                'vehicles' => [],
                'error'    => 'Could not load vehicles. Please refresh.',
            ]);
        }
    }

    /**
     * GET /trips/{vehicleId}/points?from=&to=
     * Returns raw GPS points for route playback on map.
     * Called via AJAX from the trip history tab.
     */
    public function points(Request $request, string $vehicleId)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after:from',
        ]);

        // The API reads these as UTC. The browser sends either a UTC ISO string
        // ("…Z") or a bare Colombo wall-clock time; LocalTime resolves both to UTC.
        $from = LocalTime::toApiUtc($request->input('from'));
        $to   = LocalTime::toApiUtc($request->input('to'));

        try {
            $response = $this->api->getTripHistory($vehicleId, $from, $to);


            $points = $response['data'] ?? $response;
            $points = is_array($points) ? $points : [];

            return response()->json([
                'success' => true,
                'data'    => $points,
                'count'   => count($points),
            ]);

        } catch (\Exception $e) {
            return $this->historyError($e, 'points', 'Could not load trip data. Please try again.');
        }
    }

    /**
     * GET /trips/{vehicleId}/summary?from=&to=
     * Returns trip summaries (start/end, distance, speed stats).
     * Called via AJAX from the trip history tab.
     */
    public function summary(Request $request, string $vehicleId)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after:from',
        ]);

        // The API reads these as UTC. The browser sends either a UTC ISO string
        // ("…Z") or a bare Colombo wall-clock time; LocalTime resolves both to UTC.
        $from = LocalTime::toApiUtc($request->input('from'));
        $to   = LocalTime::toApiUtc($request->input('to'));

        try {
            $response = $this->api->getTripSummary($vehicleId, $from, $to);


            $data = $response['data'] ?? $response;

            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);

        } catch (\Exception $e) {
            return $this->historyError($e, 'summary', 'Could not load trip summary. Please try again.');
        }
    }

    /**
     * Turn an API failure into the honest message and status for the history tab.
     * Previously every failure (expired subscription, wrong vehicle, rate limit, bad range)
     * collapsed into one generic 422, which made real causes impossible to tell apart —
     * in the page and in the logs. The status code is now always logged.
     */
    private function historyError(\Exception $e, string $what, string $fallback)
    {
        $code = (int) $e->getCode();
        Log::warning("TripController: {$what} failed", ['status' => $code, 'error' => $e->getMessage()]);

        return match (true) {
            $code === 401 => response()->json(['success' => false, 'expired' => true], 401),
            $code === 402 => response()->json(['success' => false, 'message' => 'Subscription expired — renew to view trip history.'], 402),
            $code === 403 => response()->json(['success' => false, 'message' => 'You do not have access to this vehicle.'], 403),
            $code === 404 => response()->json(['success' => false, 'message' => 'Vehicle not found.'], 404),
            $code === 429 => response()->json(['success' => false, 'message' => 'Too many requests — please wait a moment and try again.'], 429),
            // 400: the API's own explanation of a bad range (e.g. longer than 90 days) is safe to show.
            $code === 400 && $e->getMessage() !== '' && !str_contains($e->getMessage(), '_') => response()->json(['success' => false, 'message' => $e->getMessage()], 422),
            default => response()->json(['success' => false, 'message' => $fallback], 422),
        };
    }

    /**
     * GET /trips/{vehicleId}/location
     * Last known position, so the Live tab can show a parked (or just-shared)
     * vehicle immediately instead of waiting for its next SignalR push — the
     * Android shared-vehicle map does the same first fetch. The API enforces
     * owner / accepted-share access; 403 and 404 are passed through as states.
     */
    public function location(string $vehicleId)
    {
        try {
            $response = $this->api->getVehicleLocation($vehicleId);
            $loc = $response['data'] ?? $response;

            return response()->json([
                'success' => true,
                'data'    => is_array($loc) ? $loc : null,
            ])->header('Cache-Control', 'no-store');
        } catch (\Exception $e) {
            $code = (int) $e->getCode();
            if ($code === 401) {
                return response()->json(['success' => false, 'expired' => true], 401);
            }
            if ($code === 404) {
                return response()->json(['success' => true, 'data' => null]);   // no fix yet
            }
            if ($code === 403) {
                return response()->json(['success' => false, 'message' => 'You do not have access to this vehicle.'], 403);
            }
            if ($code === 402) {
                return response()->json(['success' => false, 'message' => 'Subscription expired — renew to use live tracking.'], 402);
            }
            Log::error('TripController: location failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Could not load the last known location.'], 422);
        }
    }

    /**
     * GET /trips/{vehicleId}/report?from=&to=
     *
     * Generates an A4-landscape PDF report of all trips for the given vehicle
     * in the specified date range. Streamed directly to the browser.
     *
     * Uses barryvdh/laravel-dompdf (already in composer.lock).
     */
    public function report(Request $request, string $vehicleId)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date',
        ]);

        // Defaults are "today so far" in Sri Lanka time; everything goes to the
        // API as UTC (see LocalTime).
        $from = LocalTime::toApiUtc($request->query('from') ?: LocalTime::now()->startOfDay()->format('Y-m-d\TH:i'));
        $to   = LocalTime::toApiUtc($request->query('to')   ?: LocalTime::now()->format('Y-m-d\TH:i'));

        try {
            // ── Fetch vehicle info ──────────────────────────────────────────
            $vehicle = null;
            $profile = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;

            if ($customerId) {
                foreach ($this->api->getTrackableVehicles($customerId) as $v) {
                    if (strtolower((string) ($v['vehicleId'] ?? '')) === strtolower($vehicleId)) {
                        $vehicle = $v;
                        break;
                    }
                }
            }

            // ── Fetch trip summary from C# API ──────────────────────────────
            $summaryRes = $this->api->getTripSummary($vehicleId, $from, $to);
            $summary    = $summaryRes['data'] ?? $summaryRes;

            $trips = $summary['trips'] ?? [];
            $stops = $summary['stops'] ?? [];

            // ── Aggregate stats ──────────────────────────────────────────────
            $totalDistanceKm  = array_sum(array_column($trips, 'distanceKm'));
            $totalDurationMin = array_sum(array_column($trips, 'durationMinutes'));
            $maxSpeed = count($trips) ? max(array_column($trips, 'maxSpeed')) : 0;
            // Duration-weighted, same rule as the API's own stats (a 2-minute trip
            // must not count as much as a 2-hour trip).
            $avgSpeed = $totalDurationMin > 0
                ? array_sum(array_map(fn($t) => ($t['avgSpeed'] ?? 0) * ($t['durationMinutes'] ?? 0), $trips)) / $totalDurationMin
                : 0;

            // ── Logo (base64 embed, same pattern as admin portal) ────────────
            $logoPath   = public_path('images/logo.png');
            $logoBase64 = '';
            if (file_exists($logoPath)) {
                $ext        = pathinfo($logoPath, PATHINFO_EXTENSION);
                $logoBase64 = 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($logoPath));
            }

            // ── Render PDF ───────────────────────────────────────────────────
            $pdf = Pdf::loadView('trips.report', compact(
                'vehicle',
                'trips',
                'stops',
                'summary',
                'totalDistanceKm',
                'totalDurationMin',
                'maxSpeed',
                'avgSpeed',
                'from',
                'to',
                'logoBase64'
            ))->setPaper('a4', 'landscape');

            $filename = 'shalotrack_trip_report_' . LocalTime::now()->format('Y-m-d_His') . '.pdf';
            return $pdf->stream($filename);

        } catch (\Exception $e) {
            Log::error('TripController: report failed', [
                'vehicleId' => $vehicleId,
                'error'     => $e->getMessage(),
            ]);
            abort(500, 'Could not generate report. Please try again.');
        }
    }
}