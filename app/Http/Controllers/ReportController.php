<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ReportController extends Controller
{
    public function __construct(private ShalotrackApiService $api) {}

    /**
     * GET /reports
     * Fleet reports page — load vehicle list for the selector.
     */
    public function index()
    {
        try {
            $profile    = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;
            $vehicles   = [];

            if ($customerId) {
                $vRes     = $this->api->getVehiclesByCustomer($customerId);
                $vehicles = $vRes['data'] ?? $vRes;
                $vehicles = is_array($vehicles) ? $vehicles : [];
            }

            return view('reports.index', [
                'vehicles' => $vehicles,
                'error'    => null,
            ]);

        } catch (\Exception $e) {
            Log::error('ReportController: index failed', ['error' => $e->getMessage()]);
            if ($e->getCode() === 401) {
                Session::flush();
                return redirect('/login?expired=1');
            }
            return view('reports.index', [
                'vehicles' => [],
                'error'    => 'Could not load vehicles. Please refresh.',
            ]);
        }
    }

    /**
     * GET /reports/data?vehicleId=&from=&to=
     * AJAX endpoint — returns trip summary JSON for the selected vehicle + date range.
     * Delegates to the same API method used by TripController::summary().
     */
    public function data(Request $request)
    {
        $vehicleId = $request->query('vehicleId');
        $from      = $request->query('from');   // ISO-8601, e.g. 2026-09-01T00:00:00Z
        $to        = $request->query('to');     // ISO-8601, e.g. 2026-09-24T23:59:59Z

        if (!$vehicleId || !$from || !$to) {
            return response()->json([
                'success' => false,
                'message' => 'vehicleId, from, and to are required.',
            ], 422);
        }

        try {
            $result = $this->api->getTripSummary($vehicleId, $from, $to);
            $data   = $result['data'] ?? $result;

            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);

        } catch (\Exception $e) {
            Log::error('ReportController: data failed', [
                'vehicleId' => $vehicleId,
                'error'     => $e->getMessage(),
            ]);

            if ($e->getCode() === 401) {
                return response()->json(['success' => false, 'message' => 'Session expired.'], 401);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to load report data. Please try again.',
            ], 422);
        }
    }
}