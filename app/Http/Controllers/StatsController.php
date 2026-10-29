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
    public function index(): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        try {
            $profile    = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;

            // Owned + accepted shares (the API serves stats for both)
            $vehicles = $customerId ? $this->api->getTrackableVehicles($customerId) : [];

            return view('stats.stats', ['vehicles' => $vehicles, 'error' => null]);
        } catch (\Exception $e) {
            Log::error('StatsController::index — failed to load vehicles', [
                'error' => $e->getMessage(),
            ]);

            if ($e->getCode() === 401) {
                Session::flush();
                return redirect('/login?expired=1');
            }

            return view('stats.stats', [
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
            $code = (int) $e->getCode();

            // Always log the status and exception type: a generic message to the user must never mean
            // an undiagnosable log line (the API's real reason used to be invisible here).
            Log::error('StatsController::data — API call failed', [
                'vehicleId' => $vehicleId,
                'period'    => $period,
                'status'    => $code,
                'type'      => class_basename($e),
                'error'     => $e->getMessage(),
            ]);

            // ── Session expired — tell the client to redirect ──────────────
            if ($code === 401) {
                return response()->json([
                    'success' => false,
                    'expired' => true,
                    'message' => 'Session expired. Please log in again.',
                ], 401);
            }

            // HTTP 200 + success:false is deliberate: the page's fetch() treats any non-2xx
            // as a bare "Server error (NNN)" and would drop this message.
            return response()->json([
                'success' => false,
                'message' => $this->errorMessage($e),
            ]);
        }
    }

    /** Honest, safe message for a failed stats call (never echoes raw API bodies). */
    private function errorMessage(\Exception $e): string
    {
        $code = (int) $e->getCode();

        return match (true) {
            $e instanceof \Illuminate\Http\Client\ConnectionException
            => 'Loading took too long. Try a shorter period, or try again in a moment.',
            $code === 402 => 'Subscription expired — renew to view statistics.',
            $code === 403 => 'You do not have access to this vehicle.',
            $code === 404 => 'Vehicle not found.',
            $code === 429 => 'Too many requests — please wait a moment and try again.',
            // 400: the API's own explanation (e.g. a range that is too large) is safe to show.
            $code === 400 && $e->getMessage() !== '' && !str_contains($e->getMessage(), '_')
            => $e->getMessage(),
            default       => 'Could not load statistics. Please try again.',
        };
    }
}
