<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class StatsController extends Controller
{
    public function __construct(private readonly ShalotrackApiService $api) {}

    /**
     * GET /stats
     */
    public function index(): \Illuminate\View\View
    {
        try {
            $profile    = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;

            $vehicles = [];
            if ($customerId) {
                $response = $this->api->getVehiclesByCustomer($customerId);
                $all      = $response['data'] ?? $response;
                $all      = is_array($all) ? $all : [];
                $vehicles = array_values($all);
            }

            return view('stats.index', ['vehicles' => $vehicles, 'error' => null]);
        } catch (\Exception $e) {
            Log::error('StatsController::index — failed to load vehicles', [
                'error' => $e->getMessage(),
            ]);

            if ($e->getCode() === 401) {
                Session::flush();
                return redirect('/login?expired=1');
            }

            return view('stats.index', [
                'vehicles' => [],
                'error'    => 'Could not load vehicles. Please refresh the page.',
            ]);
        }
    }

    /**
     * GET /stats/{vehicleId}/data?period=today|week|month|all
     * AJAX — returns JSON
     */
    public function data(Request $request, string $vehicleId): \Illuminate\Http\JsonResponse
    {
        $period = $request->query('period', 'today');

        if (!in_array($period, ['today', 'week', 'month', 'all'], true)) {
            $period = 'today';
        }

        try {
            $response = $this->api->getVehicleStats($vehicleId, $period);
            $data     = $response['data'] ?? $response;

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('StatsController::data — API call failed', [
                'vehicleId' => $vehicleId,
                'period'    => $period,
                'error'     => $e->getMessage(),
            ]);

            // ── Session expired — tell the client to redirect ──────────────
            if ($e->getCode() === 401) {
                return response()->json([
                    'success' => false,
                    'expired' => true,
                    'message' => 'Session expired. Please log in again.',
                ], 401);
            }

            return response()->json([
                'success' => false,
                'message' => 'Could not load statistics. Please try again.',
            ]);
        }
    }

    /**
     * POST /stats/{vehicleId}/export
     * Generates a PDF via barryvdh/laravel-dompdf.
     */
    public function export(Request $request, string $vehicleId): \Symfony\Component\HttpFoundation\Response
    {
        $validated = $request->validate([
            'period'            => 'required|in:today,week,month,all',
            'vehicle_plate'     => 'nullable|string|max:50',
            'vehicle_name'      => 'nullable|string|max:100',
            'vehicle_is_demo'   => 'nullable|string',
            'chart_distance'    => 'nullable|string',
            'chart_trips_stops' => 'nullable|string',
            'chart_ignition'    => 'nullable|string',
        ]);

        try {
            $response = $this->api->getVehicleStats($vehicleId, $validated['period']);
            $data     = $response['data'] ?? $response;

            $periods = [
                'today' => 'Today',
                'week'  => 'This Week',
                'month' => 'This Month',
                'all'   => 'All Time',
            ];

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('stats.pdf', [
                'data'          => $data,
                'vehiclePlate'  => $validated['vehicle_plate']    ?? $vehicleId,
                'vehicleName'   => $validated['vehicle_name']     ?? '',
                'vehicleIsDemo' => ($validated['vehicle_is_demo'] ?? '0') === '1',
                'period'        => $validated['period'],
                'periodLabel'   => $periods[$validated['period']],
                'chartDist'     => $validated['chart_distance']    ?? null,
                'chartTS'       => $validated['chart_trips_stops'] ?? null,
                'chartIgn'      => $validated['chart_ignition']    ?? null,
                'generatedAt'   => now()->setTimezone('Asia/Colombo')->format('d M Y, H:i'),
            ])->setPaper('a4', 'portrait');

            $plate    = preg_replace('/[^A-Za-z0-9_-]/', '_', $validated['vehicle_plate'] ?? $vehicleId);
            $filename = "shalotrack_{$plate}_{$validated['period']}_" . now()->format('Y-m-d') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('StatsController::export — failed', [
                'vehicleId' => $vehicleId,
                'error'     => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not generate PDF. Please try again.',
            ], 500);
        }
    }
}
