<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class AlertController extends Controller
{
    public function __construct(private ShalotrackApiService $api) {}

    /**
     * GET /alerts
     * Alerts list page — paginated, filterable by vehicle.
     */
    public function index(Request $request)
    {
        try {
            $page      = max(1, (int) $request->query('page', 1));
            $vehicleId = $request->query('vehicle') ?: null;

            $response = $this->api->getMyAlerts($page, 20, $vehicleId);
            $data     = $response['data'] ?? $response;

            $alerts     = is_array($data['items'] ?? null) ? $data['items'] : (is_array($data) && isset($data[0]) ? $data : []);
            $totalCount = $data['totalCount'] ?? count($alerts);
            $totalPages = $data['totalPages'] ?? ceil($totalCount / 20);

            // Also get vehicles for the filter dropdown
            $profile    = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;
            $vehicles   = [];
            if ($customerId) {
                $vResponse = $this->api->getVehiclesByCustomer($customerId);
                $vehicles  = $vResponse['data'] ?? $vResponse;
                $vehicles  = is_array($vehicles) ? $vehicles : [];
            }

            return view('alerts.index', [
                'alerts'      => $alerts,
                'vehicles'    => $vehicles,
                'currentPage' => $page,
                'totalPages'  => (int) $totalPages,
                'totalCount'  => (int) $totalCount,
                'vehicleFilter' => $vehicleId,
                'error'       => null,
            ]);

        } catch (\Exception $e) {
            Log::error('AlertController: Failed to load', ['error' => $e->getMessage()]);
            if ($e->getCode() === 401) {
                Session::flush();
                return redirect('/login?expired=1');
            }
            return view('alerts.index', [
                'alerts'      => [],
                'vehicles'    => [],
                'currentPage' => 1,
                'totalPages'  => 1,
                'totalCount'  => 0,
                'vehicleFilter' => null,
                'error'       => 'Could not load alerts. Please refresh.',
            ]);
        }
    }

    /**
     * POST /alerts/{id}/read
     * Mark a single alert as read via AJAX.
     */
    public function markRead(Request $request, string $id)
    {
        try {
            $this->api->markAlertRead($id);
            Cache::forget($this->badgeCacheKey());   // badge must reflect the read immediately
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('AlertController: markRead failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to mark as read.'], 422);
        }
    }

    /**
     * GET /alerts/unread-count  (AJAX, polled by the layout's alert badge)
     *
     * The API has no count endpoint, so we look at the newest 50 alerts and
     * count the unread ones; "capped" tells the UI to show "50+". The result is
     * cached for 30 s per customer so a handful of open tabs (each polling once a
     * minute) cost the C# API at most one call per 30 s per customer.
     */
    public function unreadCount()
    {
        try {
            $result = Cache::remember($this->badgeCacheKey(), 30, function () {
                $response = $this->api->getMyAlerts(1, 50);
                $data     = $response['data'] ?? $response;
                $items    = is_array($data['items'] ?? null)
                    ? $data['items']
                    : (is_array($data) && isset($data[0]) ? $data : []);

                $unread = count(array_filter($items, fn($a) => !($a['isRead'] ?? false)));
                return ['count' => $unread, 'capped' => $unread >= 50];
            });

            return response()->json($result);

        } catch (\Exception $e) {
            if ((int) $e->getCode() === 401) {
                Session::flush();
                return response()->json(['code' => 'TOKEN_EXPIRED'], 401);
            }
            // A badge is decoration — never surface errors, just show nothing.
            return response()->json(['count' => null], 200);
        }
    }

    private function badgeCacheKey(): string
    {
        return 'alert-badge:' . (Session::get('customer_id') ?? session()->getId());
    }
}