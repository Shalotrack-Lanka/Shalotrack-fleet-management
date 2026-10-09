<?php

namespace App\Http\Controllers;

use App\Services\FrequentPlaces;
use App\Services\ShalotrackApiService;
use App\Support\LocalTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * "Frequent places": where each vehicle usually ends its trips. Read-only; the C# API
 * decides which vehicles the customer may see, this layer only groups the trip ends.
 */
class FrequentPlacesController extends Controller
{
    private const PERIOD_DAYS = ['30' => 30, '90' => 90];

    public function __construct(private ShalotrackApiService $api, private FrequentPlaces $places) {}

    /** GET /places */
    public function index()
    {
        try {
            $profile    = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;
            $all        = $customerId ? $this->api->getTrackableVehicles($customerId) : [];

            $vehicles = [];
            foreach ($all as $v) {
                $id = (string) ($v['vehicleId'] ?? $v['id'] ?? '');
                if ($id === '' || empty($v['hasGpsDevice'])) {
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

            return view('places.frequent', ['vehicles' => $vehicles, 'error' => null]);
        } catch (\Exception $e) {
            Log::error('FrequentPlacesController: index failed', ['error' => $e->getMessage()]);
            if ($e->getCode() === 401) {
                Session::flush();
                return redirect('/login?expired=1');
            }
            return view('places.frequent', ['vehicles' => [], 'error' => 'Could not load vehicles. Please refresh the page.']);
        }
    }

    /** GET /places/{vehicleId}/data?period=30|90 */
    public function data(Request $request, string $vehicleId): JsonResponse
    {
        $period = (string) $request->query('period', '30');
        $days   = self::PERIOD_DAYS[$period] ?? 30;

        $to   = LocalTime::now();
        $from = $to->copy()->subDays($days)->startOfDay();

        try {
            $res   = $this->api->getTripSummary(
                $vehicleId,
                LocalTime::toApiUtc($from->format('Y-m-d\TH:i')),
                LocalTime::toApiUtc($to->format('Y-m-d\TH:i'))
            );
            $trips = $res['data'] ?? $res;
            $trips = is_array($trips) ? array_values($trips) : [];

            // Saved places only add a label; if they cannot be loaded the list still works.
            $saved = [];
            try {
                $sp    = $this->api->getSavedPlaces();
                $saved = is_array($sp['data'] ?? null) ? $sp['data'] : [];
            } catch (\Throwable) {
            }

            return response()->json([
                'success'    => true,
                'days'       => $days,
                'tripCount'  => count($trips),
                'places'     => $this->places->build($trips, $saved),
            ])->header('Cache-Control', 'no-store');
        } catch (\Exception $e) {
            $code = (int) $e->getCode();
            if ($code === 401) {
                return response()->json(['success' => false, 'expired' => true], 401);
            }
            if ($code === 403) {
                return response()->json(['success' => false, 'message' => 'You do not have access to this vehicle.'], 403);
            }
            if ($code === 402) {
                return response()->json(['success' => false, 'message' => 'Subscription expired. Renew to see this vehicle.'], 402);
            }
            Log::error('FrequentPlacesController: data failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Could not load trips. Please try again.'], 422);
        }
    }
}