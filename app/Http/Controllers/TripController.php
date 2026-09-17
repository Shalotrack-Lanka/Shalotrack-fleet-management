<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class TripController extends Controller
{
    public function __construct(private ShalotrackApiService $api) {}

    /**
     * GET /trips
     * Trip history page — lists all customer vehicles for selection,
     * then loads trip data via AJAX when a vehicle + date range is chosen.
     */
    public function index()
    {
        try {
            $profile    = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;

            $vehicles = [];
            if ($customerId) {
                $response = $this->api->getVehiclesByCustomer($customerId);
                $vehicles = $response['data'] ?? $response;
                $vehicles = is_array($vehicles) ? $vehicles : [];
                // Only vehicles with a GPS device can have trip history
                $vehicles = array_filter($vehicles, fn($v) => $v['hasGpsDevice'] ?? false);
                $vehicles = array_values($vehicles);
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
     * GET /api/trips/{vehicleId}/points?from=&to=
     * Returns raw GPS points for route playback on map.
     * Called via AJAX from the trip history page.
     */
    public function points(Request $request, string $vehicleId)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after:from',
        ]);

        try {
            $response = $this->api->getTripHistory(
                $vehicleId,
                $request->input('from'),
                $request->input('to')
            );

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
     * GET /api/trips/{vehicleId}/summary?from=&to=
     * Returns trip summaries (start/end, distance, speed stats).
     * Called via AJAX from the trip history page.
     */
    public function summary(Request $request, string $vehicleId)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after:from',
        ]);

        try {
            $response = $this->api->getTripSummary(
                $vehicleId,
                $request->input('from'),
                $request->input('to')
            );

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
}