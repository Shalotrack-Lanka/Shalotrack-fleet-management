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
            $profile = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;

            if (!$customerId) {
                return view('dashboard.index', [
                    'error'     => 'Could not load your profile. Please try again.',
                    'dashboard' => null,
                    'phone'     => Session::get('firebase_phone'),
                ]);
            }

            // Step 2 — get dashboard data (vehicles + live locations in one call)
            $dashboard = $this->api->getDashboard($customerId);
            $data = $dashboard['data'] ?? $dashboard;
// Ensure vehicles is always an array
if (isset($data['vehicles']) && !is_array($data['vehicles'])) {
    $data['vehicles'] = [];
}
$data['vehicles'] = $data['vehicles'] ?? [];

            return view('dashboard.index', [
                'dashboard' => $data,
                'phone'     => Session::get('firebase_phone'),
                'error'     => null,
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard: Failed to load', ['error' => $e->getMessage()]);

            // If the API returns 401, the Firebase token has expired
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