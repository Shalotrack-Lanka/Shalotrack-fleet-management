<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
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
                $response = $this->api->getVehiclesByCustomer($customerId);
                $all      = $response['data'] ?? $response;
                $all      = is_array($all) ? array_values($all) : [];

                // Sort: GPS-enabled first; within GPS, demo vehicle last so
                // owned vehicles appear at the top of the sidebar. Non-GPS
                // vehicles still show (grayed out) so the customer can see
                // their full fleet and knows they need to link a device.
                usort($all, function ($a, $b) {
                    $aGps  = (bool) ($a['hasGpsDevice']  ?? false);
                    $bGps  = (bool) ($b['hasGpsDevice']  ?? false);
                    $aDemo = (bool) ($a['isDemoVehicle'] ?? false);
                    $bDemo = (bool) ($b['isDemoVehicle'] ?? false);
                    if ($aGps !== $bGps)   return $bGps  <=> $aGps;   // GPS first
                    if ($aDemo !== $bDemo) return $aDemo <=> $bDemo;   // demo last within GPS
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

        // Normalise datetime-local format (YYYY-MM-DDTHH:MM) → ISO-8601 with seconds
        // so the C# API DateTime model-binder has a complete string to parse.
        $from = $request->input('from');
        $to   = $request->input('to');
        if ($from && strlen($from) === 16) $from .= ':00';
        if ($to   && strlen($to)   === 16) $to   .= ':00';

        try {
            $response = $this->api->getTripHistory($vehicleId, $from, $to);

            // DEBUG – remove once trip history is confirmed working.
            Log::debug('TripController: points raw response', [
                'vehicleId' => $vehicleId,
                'from'      => $from,
                'to'        => $to,
                'response'  => $response,
            ]);

            $points = $response['data'] ?? $response;
            $points = is_array($points) ? $points : [];

            return response()->json([
                'success' => true,
                'data'    => $points,
                'count'   => count($points),
            ]);

        } catch (\Exception $e) {
            Log::error('TripController: points failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Could not load trip data. Please try again.',
            ], 422);
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

        // Normalise datetime-local format (YYYY-MM-DDTHH:MM) → ISO-8601 with seconds.
        $from = $request->input('from');
        $to   = $request->input('to');
        if ($from && strlen($from) === 16) $from .= ':00';
        if ($to   && strlen($to)   === 16) $to   .= ':00';

        try {
            $response = $this->api->getTripSummary($vehicleId, $from, $to);

            // DEBUG – remove once trip history is confirmed working.
            Log::debug('TripController: summary raw response', [
                'vehicleId' => $vehicleId,
                'from'      => $from,
                'to'        => $to,
                'response'  => $response,
            ]);

            $data = $response['data'] ?? $response;

            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);

        } catch (\Exception $e) {
            Log::error('TripController: summary failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Could not load trip summary. Please try again.',
            ], 422);
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
        $from = $request->query('from', now()->startOfDay()->format('Y-m-d\TH:i'));
        $to   = $request->query('to',   now()->format('Y-m-d\TH:i'));

        try {
            // ── Fetch vehicle info ──────────────────────────────────────────
            $vehicle = null;
            $profile = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;

            if ($customerId) {
                $vehiclesRes = $this->api->getVehiclesByCustomer($customerId);
                $allVehicles = $vehiclesRes['data'] ?? $vehiclesRes;
                $allVehicles = is_array($allVehicles) ? $allVehicles : [];
                foreach ($allVehicles as $v) {
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
            $avgSpeed = count($trips) ? array_sum(array_column($trips, 'avgSpeed')) / count($trips) : 0;

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

            $filename = 'shalotrack_trip_report_' . now()->format('Y-m-d_His') . '.pdf';
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