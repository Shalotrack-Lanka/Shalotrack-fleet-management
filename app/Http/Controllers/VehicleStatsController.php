<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class VehicleStatsController extends Controller
{
    public function __construct(private ShalotrackApiService $api) {}

    public function index()
    {
        try {
            $profile    = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;
            $vehicles   = [];

            if ($customerId) {
                $vResponse = $this->api->getVehiclesByCustomer($customerId);
                $vehicles  = $vResponse['data'] ?? $vResponse;
                $vehicles  = is_array($vehicles) ? $vehicles : [];
                $vehicles  = array_filter($vehicles, fn($v) => $v['hasGpsDevice'] ?? false);
                $vehicles  = array_values($vehicles);
            }

            return view('vehicle-stats.index', [
                'vehicles' => $vehicles,
                'error'    => null,
            ]);
        } catch (\Exception $e) {
            Log::error('VehicleStatsController: load failed', ['error' => $e->getMessage()]);
            if ($e->getCode() === 401) { Session::flush(); return redirect('/login?expired=1'); }
            return view('vehicle-stats.index', [
                'vehicles' => [],
                'error'    => 'Could not load vehicles. Please refresh.',
            ]);
        }
    }

    public function show(Request $request, string $vehicleId)
    {
        $period = $request->query('period', 'week');
        if (!in_array($period, ['day', 'week', 'month'])) $period = 'week';

        try {
            $response = $this->api->getVehicleStats($vehicleId, $period);
            $stats    = $response['data'] ?? $response;

            return response()->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            Log::error('VehicleStatsController: stats failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Could not load stats.'], 422);
        }
    }
}