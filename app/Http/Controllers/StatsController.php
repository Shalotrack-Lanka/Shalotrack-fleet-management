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
     *
     * Renders the Vehicle Statistics page.
     * Loads all customer vehicles and filters to those with a GPS device or
     * the demo vehicle, then passes them to the Blade view.
     * Stats themselves are loaded client-side via the /stats/{id}/data AJAX endpoint.
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

                // Show all vehicles — those without GPS will just return zero stats.
                $vehicles = array_values($all);
            }

            return view('stats.index', [
                'vehicles' => $vehicles,
                'error'    => null,
            ]);

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
     *
     * AJAX endpoint consumed by the stats page JS.
     * Forwards the request to the C# VehicleStats API and returns JSON.
     *
     * Response shape on success:
     * {
     *   "success": true,
     *   "data": {
     *     "totalDistanceKm": ...,
     *     "totalTripCount": ...,
     *     "totalStopCount": ...,
     *     "totalIdleMinutes": ...,
     *     "totalDrivingMinutes": ...,
     *     "totalIgnitionOnMinutes": ...,
     *     "averageSpeed": ...,
     *     "maxSpeed": ...,
     *     "overspeedIncidentCount": ...,
     *     "dailyBreakdown": [ { date, distanceKm, tripCount, stopCount, ignitionOnMinutes, averageSpeed, maxSpeed } ]
     *   }
     * }
     */
    public function data(Request $request, string $vehicleId): \Illuminate\Http\JsonResponse
    {
        $period = $request->query('period', 'today');

        // Sanitise period — only accept the four values the C# API understands.
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

            return response()->json([
                'success' => false,
                'message' => 'Could not load statistics. Please try again.',
            ]);
        }
    }
}