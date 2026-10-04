<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * SOS — web parity with the Android HomeActivity SOS flow.
 *
 * POST /sos/{vehicleId}: asks the API to raise an SOS for the vehicle (location
 * is resolved server-side), then returns the customer's emergency contacts so
 * the page can offer one-tap "Call" links, as the app does.
 *
 * Safety: CSRF-protected, route-throttled (see routes/web.php), and every
 * trigger is written to the log with the session's customer id and IP so a
 * false/abusive SOS can be traced. The UI additionally requires a 3-second
 * press-and-hold, matching the app, so it can't fire from a stray click.
 */
class SosController extends Controller
{
    public function __construct(private ShalotrackApiService $api) {}

    public function trigger(string $vehicleId)
    {
        try {
            $this->api->triggerSos($vehicleId);

            Log::warning('SOS triggered from web portal', [
                'vehicle_id'  => $vehicleId,
                'customer_id' => Session::get('customer_id'),
                'ip'          => request()->ip(),
            ]);

            // Contacts are a convenience — never fail the SOS because of them.
            $contacts = [];
            try {
                $res  = $this->api->getEmergencyContacts();
                $list = $res['data'] ?? $res;
                foreach ((is_array($list) ? $list : []) as $c) {
                    $contacts[] = [
                        'name'         => (string) ($c['name'] ?? ''),
                        'phoneNumber'  => (string) ($c['phoneNumber'] ?? ''),
                        'relationship' => $c['relationship'] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                Log::info('SOS: emergency contacts unavailable', ['code' => $e->getCode()]);
            }

            return response()->json(['success' => true, 'contacts' => $contacts]);

        } catch (\Exception $e) {
            $code = (int) $e->getCode();

            if ($code === 401) {
                Session::flush();
                return response()->json(['success' => false, 'code' => 'TOKEN_EXPIRED', 'message' => 'Session expired.'], 401);
            }

            Log::error('SosController: trigger failed', [
                'vehicle_id' => $vehicleId,
                'code'       => $code,
                'message'    => $e->getMessage(),
            ]);

            if ($code === 402) {
                return response()->json([
                    'success' => false,
                    'message' => "This vehicle's subscription has expired. Renew it to use SOS.",
                ], 402);
            }
            if (in_array($code, [403, 404], true)) {
                return response()->json(['success' => false, 'message' => "SOS can't be sent for this vehicle."], $code);
            }

            return response()->json(['success' => false, 'message' => "Couldn't send SOS. Please try again."], 502);
        }
    }
}