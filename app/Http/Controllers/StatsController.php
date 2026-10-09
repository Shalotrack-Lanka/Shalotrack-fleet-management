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

            // Owned + accepted shares (the API serves stats for both)
            $vehicles = $customerId ? $this->api->getTrackableVehicles($customerId) : [];

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
     * GET /stats/compare
     * Side-by-side comparison of 2-3 vehicles. The page reuses the per-vehicle
     * /stats/{id}/data endpoint (same ownership checks in the API), so there is no
     * new data path here: this only lists the vehicles the customer may pick from.
     */
    public function compare(): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        try {
            $profile    = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;
            $all        = $customerId ? $this->api->getTrackableVehicles($customerId) : [];

            // Only vehicles that can have stats, reduced to the fields the page needs.
            $vehicles = [];
            foreach ($all as $v) {
                if (empty($v['hasGpsDevice'])) {
                    continue;
                }
                $id = (string) ($v['vehicleId'] ?? $v['id'] ?? '');
                if ($id === '') {
                    continue;
                }
                $vehicles[] = [
                    'id'     => $id,
                    'plate'  => (string) ($v['vehicleNumber'] ?? 'N/A'),
                    'name'   => trim(($v['make'] ?? '') . ' ' . ($v['model'] ?? '')),
                    'demo'   => (bool) ($v['isDemoVehicle'] ?? false),
                    'shared' => (bool) ($v['isShared'] ?? false),
                ];
            }

            return view('stats.compare', ['vehicles' => $vehicles, 'error' => null]);
        } catch (\Exception $e) {
            Log::error('StatsController::compare — failed to load vehicles', ['error' => $e->getMessage()]);

            if ($e->getCode() === 401) {
                Session::flush();
                return redirect('/login?expired=1');
            }

            return view('stats.compare', ['vehicles' => [], 'error' => 'Could not load vehicles. Please refresh the page.']);
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
}