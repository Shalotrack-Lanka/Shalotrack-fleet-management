<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    public function __construct(private ShalotrackApiService $api) {}

    public function index(Request $request)
    {
        try {
            // Step 1 — get the customer's own profile to retrieve customerId
            $profile    = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;

            if (!$customerId) {
                return view('dashboard.index', [
                    'error'     => 'Could not load your profile. Please try again.',
                    'dashboard' => null,
                    'phone'     => Session::get('firebase_phone'),
                ]);
            }

            // Cache customer name in session for topbar display
            Session::put('customer_name', $profile['data']['fullName'] ?? null);
            Session::put('customer_id',   $profile['data']['customerId'] ?? null);

            // Step 2 — get dashboard data (vehicles + live locations in one call)
            $response = $this->api->getDashboard($customerId);

            // Unwrap the API envelope — C# API wraps data in { data: {...} }
            $data = $response['data'] ?? $response;

            // Guarantee vehicles is always an array regardless of API shape
            $data['vehicles']        = is_array($data['vehicles'] ?? null)        ? $data['vehicles']        : [];
            $data['vehicleCount']    = $data['vehicleCount']    ?? count($data['vehicles']);
            $data['onlineVehicles']  = $data['onlineVehicles']  ?? 0;
            $data['offlineVehicles'] = $data['offlineVehicles'] ?? 0;

            return view('dashboard.index', [
                'dashboard' => $data,
                'phone'     => Session::get('firebase_phone'),
                'error'     => null,
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard: Failed to load', [
                'error' => $e->getMessage(),
                'code'  => $e->getCode(),
            ]);

            if ($e->getCode() === 401) {
                Session::flush();
                return redirect('/login?expired=1');
            }

            return view('dashboard.index', [
                'error'     => 'Could not connect to the server. Please refresh the page.',
                'dashboard' => null,
                'phone'     => Session::get('firebase_phone'),
            ]);
        }
    }
}