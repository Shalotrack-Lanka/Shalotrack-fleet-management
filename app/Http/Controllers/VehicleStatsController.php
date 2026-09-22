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
            if ($e->getCode() === 401) {
                Session::flush();
                return redirect('/login?expired=1');
            }
            return view('vehicle-stats.index', [
                'vehicles' => [],
                'error'    => 'Could not load vehicles. Please refresh.',
            ]);
        }
    }

    public function show(Request $request, string $vehicleId)
    {
        // Custom date range takes precedence over period preset.
        $from = $request->query('from');
        $to   = $request->query('to');

        if ($from !== null || $to !== null) {
            return $this->showRange($vehicleId, $from, $to);
        }

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

    private function showRange(string $vehicleId, ?string $from, ?string $to)
    {
        // Validate — both fields are required for a custom range.
        if (empty($from) || empty($to)) {
            return response()->json(['success' => false, 'message' => 'Both from and to dates are required.'], 422);
        }

        // Parse as dates; reject anything that isn't a real date.
        $fromDate = \DateTime::createFromFormat('Y-m-d', $from);
        $toDate   = \DateTime::createFromFormat('Y-m-d', $to);

        if (!$fromDate || !$toDate || $fromDate->format('Y-m-d') !== $from || $toDate->format('Y-m-d') !== $to) {
            return response()->json(['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD.'], 422);
        }

        if ($fromDate > $toDate) {
            return response()->json(['success' => false, 'message' => '"From" date must be before "To" date.'], 422);
        }

        // Cap at 366 days to prevent accidentally huge API requests.
        $diffDays = $fromDate->diff($toDate)->days;
        if ($diffDays > 366) {
            return response()->json(['success' => false, 'message' => 'Date range cannot exceed 366 days.'], 422);
        }

        // Convert YYYY-MM-DD → ISO-8601 with midnight UTC, matching the API's expectation.
        $fromIso = $from . 'T00:00:00Z';
        $toIso   = $to   . 'T23:59:59Z';

        try {
            $response = $this->api->getVehicleStatsForRange($vehicleId, $fromIso, $toIso);
            $stats    = $response['data'] ?? $response;

            return response()->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            Log::error('VehicleStatsController: range stats failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Could not load statistics for that range.'], 422);
        }
    }
}
