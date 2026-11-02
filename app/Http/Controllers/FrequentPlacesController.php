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

        // The API refuses ranges over 90 days. "N days back, from midnight" is N days plus part of a
        // day, which is why the 90-day view failed: count today as day 1 so the range stays inside the cap.
        $to   = LocalTime::now();
        $from = $to->copy()->subDays($days - 1)->startOfDay();

        try {
            $res   = $this->api->getTripSummary(
                $vehicleId,
                LocalTime::toApiUtc($from->format('Y-m-d\TH:i')),
                LocalTime::toApiUtc($to->format('Y-m-d\TH:i'))
            );
            // The API returns a report object; the trips are in its "trips" list.
            $report = $res['data'] ?? $res;
            $trips  = is_array($report['trips'] ?? null) ? array_values($report['trips']) : [];

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
            Log::error('FrequentPlacesController: data failed', [
                'status' => $code, 'type' => class_basename($e), 'error' => $e->getMessage(),
            ]);
            if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
                return response()->json(['success' => false, 'message' => 'Loading took too long. Try the 30-day view, or try again in a moment.'], 422);
            }
            return response()->json(['success' => false, 'message' => 'Could not load trips. Please try again.'], 422);
        }
    }
}