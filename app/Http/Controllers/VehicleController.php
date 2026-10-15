<?php

namespace App\Http\Controllers;

use App\Support\ApiErrors;
use App\Services\ShalotrackApiService;
use App\Support\DeviceHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class VehicleController extends Controller
{
    public function __construct(private ShalotrackApiService $api) {}

    // -------------------------------------------------------------------------
    // Vehicle list — owned + accepted shares merged into one collection
    // -------------------------------------------------------------------------

    public function index()
    {
        try {
            $profile    = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;

            if (!$customerId) {
                return view('vehicles.vehicles', [
                    'vehicles'   => [],
                    'customerId' => null,
                    'error'      => 'Could not load your profile.',
                ]);
            }

            Session::put('customer_id', $customerId);   // used server-side when creating a vehicle

            // Owned vehicles
            $response = $this->api->getVehiclesByCustomer($customerId);
            $owned    = $response['data'] ?? $response;
            $owned    = is_array($owned) ? $owned : [];

            // Shared vehicles — normalised to the same shape as owned, accepted only
            $sharesRes = $this->api->getSharedWithMe();
            $shares    = $sharesRes['data'] ?? $sharesRes;
            $shares    = is_array($shares) ? $shares : [];

            $shared = [];
            foreach ($shares as $s) {
                if (($s['status'] ?? '') !== 'Accepted') {
                    continue;
                }
                $shared[] = [
                    'vehicleId'     => $s['vehicleId'],
                    'vehicleNumber' => $s['vehicleNumber'],
                    'make'          => $s['make']  ?? '',
                    'model'         => $s['model'] ?? '',
                    'year'          => $s['year']  ?? null,
                    'vehicleType'   => $s['vehicleType'] ?? null,
                    'hasGpsDevice'  => true,   // vehicle was shared for tracking — GPS is linked
                    'isDemoVehicle' => false,
                    'isShared'      => true,
                    'ownerName'     => $s['otherPartyName'] ?? null,
                    'shareId'       => $s['shareId'],
                    'color'         => null,
                    'fuelType'      => null,
                    'imei'          => null,
                ];
            }

            return view('vehicles.vehicles', [
                'vehicles'   => array_merge($owned, $shared),
                'customerId' => $customerId,
                'error'      => null,
            ]);

        } catch (\Exception $e) {
            $this->handleApiException($e);
            return view('vehicles.vehicles', [
                'vehicles'   => [],
                'customerId' => null,
                'error'      => 'Could not load vehicles. Please refresh.',
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Vehicle detail
    // -------------------------------------------------------------------------

    public function show(string $id)
    {
        try {
            $response = $this->api->getVehicle($id);
            $vehicle  = $response['data'] ?? $response;

            return view('vehicles.show', [
                'vehicle' => $vehicle,
                'error'   => null,
            ]);

        } catch (\Exception $e) {
            if ($e->getCode() === 404) {
                abort(404);
            }
            $this->handleApiException($e);
            return view('vehicles.show', [
                'vehicle' => null,
                'error'   => 'Could not load vehicle details.',
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Vehicle detail as JSON (AJAX) — GET /api/vehicles/{id}
    // Used by the "Unlink GPS" flow on vehicles/index to read
    // currentAssignmentId. show() above returns a Blade view, so the page's
    // res.json() call on it always failed.
    // -------------------------------------------------------------------------

    public function showJson(string $id)
    {
        try {
            return response()->json($this->api->getVehicle($id));
        } catch (\Exception $e) {
            return $this->jsonApiError($e, 'showJson', 'Could not load vehicle.');
        }
    }

    // -------------------------------------------------------------------------
    // Current location (AJAX) — GET /api/CurrentLocations/vehicle/{id}
    // HTTP fallback poll on vehicles/show when SignalR is unavailable.
    // -------------------------------------------------------------------------

    public function location(string $id)
    {
        try {
            return response()->json($this->api->getVehicleLocation($id));
        } catch (\Exception $e) {
            return $this->jsonApiError($e, 'location', 'Location unavailable.');
        }
    }

    // -------------------------------------------------------------------------
    // Vehicle health (AJAX) — GET /api/vehicles/{id}/health
    //
    // Summarises the device's battery / power / GPS fix / last contact. Only the
    // summary leaves the server (no IMEI, no device id — see DeviceHealth).
    // Cached 15 s per user+vehicle: the card polls, and the underlying numbers
    // only change when the device reports, so this removes repeat API calls.
    // The key includes a hash of the Firebase uid because ownership differs per
    // user — one user's cached answer must never be served to another.
    // -------------------------------------------------------------------------

    public function health(string $id)
    {
        $key = 'device_health:' . hash('sha256', (string) Session::get('firebase_uid')) . ':' . $id;

        try {
            $summary = Cache::remember($key, 15, function () use ($id) {
                try {
                    $res    = $this->api->getVehicleDeviceStatus($id);
                    $record = $res['data'] ?? null;
                    return is_array($record) ? DeviceHealth::summarize($record) : DeviceHealth::unavailable();
                } catch (\Exception $e) {
                    // No device / not visible to this user (shared viewers get 404): hide the card, not an error.
                    if (in_array((int) $e->getCode(), [403, 404], true)) {
                        return DeviceHealth::unavailable();
                    }
                    throw $e;
                }
            });

            return response()->json($summary);
        } catch (\Exception $e) {
            return $this->jsonApiError($e, 'health', 'Health unavailable.');
        }
    }

    // -------------------------------------------------------------------------
    // Create vehicle (AJAX)
    // -------------------------------------------------------------------------

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customerId'    => 'nullable|uuid',   // ignored: the owner is always the signed-in customer
            'vehicleNumber' => 'required|string|max:20',
            'make'          => 'required|string|max:50',
            'model'         => 'required|string|max:50',
            'year'          => 'required|integer|min:1900|max:2100',
            'color'         => 'nullable|string|max:30',
            'vehicleType'   => 'nullable|string|max:30',
            'fuelType'      => 'nullable|string|max:30',
            'chassisNumber' => 'nullable|string|max:100',
            'engineNumber'  => 'nullable|string|max:100',
        ]);

        try {
            // SECURITY: never trust an owner id sent by the browser — it could name someone else's account.
            $customerId = $this->signedInCustomerId();

            $response = $this->api->createVehicle([
                'CustomerId'    => $customerId,
                'VehicleNumber' => strtoupper($validated['vehicleNumber']),
                'Make'          => $validated['make'],
                'Model'         => $validated['model'],
                'Year'          => (int) $validated['year'],
                'Color'         => $validated['color'] ?? null,
                'VehicleType'   => $validated['vehicleType'] ?? null,
                'FuelType'      => $validated['fuelType'] ?? null,
                'ChassisNumber' => $validated['chassisNumber'] ?? null,
                'EngineNumber'  => $validated['engineNumber'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle added successfully.',
                'data'    => $response['data'] ?? $response,
            ]);

        } catch (\Exception $e) {
            return ApiErrors::json($e, 'vehicle.create', 'Failed to add vehicle. Please try again.');
        }
    }

    // -------------------------------------------------------------------------
    // Update vehicle (AJAX)
    // -------------------------------------------------------------------------

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'vehicleNumber' => 'required|string|max:20',
            'make'          => 'required|string|max:50',
            'model'         => 'required|string|max:50',
            'year'          => 'required|integer|min:1900|max:2100',
            'color'         => 'nullable|string|max:30',
            'vehicleType'   => 'nullable|string|max:30',
            'fuelType'      => 'nullable|string|max:30',
            'chassisNumber' => 'nullable|string|max:100',
            'engineNumber'  => 'nullable|string|max:100',
        ]);

        try {
            $response = $this->api->updateVehicle($id, [
                'VehicleNumber' => strtoupper($validated['vehicleNumber']),
                'Make'          => $validated['make'],
                'Model'         => $validated['model'],
                'Year'          => (int) $validated['year'],
                'Color'         => $validated['color'] ?? null,
                'VehicleType'   => $validated['vehicleType'] ?? null,
                'FuelType'      => $validated['fuelType'] ?? null,
                'ChassisNumber' => $validated['chassisNumber'] ?? null,
                'EngineNumber'  => $validated['engineNumber'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle updated successfully.',
                'data'    => $response['data'] ?? $response,
            ]);

        } catch (\Exception $e) {
            return ApiErrors::json($e, 'vehicle.update', 'Failed to update vehicle. Please try again.');
        }
    }

    // -------------------------------------------------------------------------
    // Delete vehicle (AJAX)
    // -------------------------------------------------------------------------

    public function destroy(string $id)
    {
        try {
            $this->api->deleteVehicle($id);
            return response()->json([
                'success' => true,
                'message' => 'Vehicle deleted successfully.',
            ]);

        } catch (\Exception $e) {
            return ApiErrors::json($e, 'vehicle.delete', 'Failed to delete vehicle. Please try again.');
        }
    }

    // -------------------------------------------------------------------------
    // Link GPS device (AJAX)
    // Two-step: IMEI lookup → assign
    // -------------------------------------------------------------------------

    public function linkDevice(Request $request, string $id)
    {
        $request->validate([
            // IMEI is 15 digits (16-17 with software version): digits only, checked BEFORE any lookup.
            'imei' => ['required', 'string', 'regex:/^\s*[0-9][0-9 -]{13,20}[0-9]\s*$/'],
        ]);

        $imei = preg_replace('/\D/', '', $request->input('imei'));
        if (strlen($imei) < 15 || strlen($imei) > 17) {
            return response()->json([
                'success' => false,
                'code'    => 'INVALID_IMEI',
                'message' => 'Enter the 15-digit IMEI printed on the device or its box.',
            ], 422);
        }

        try {
            // Step 1: Lookup device by IMEI
            $lookup   = $this->api->lookupDeviceByImei($imei);
            $deviceId = $lookup['data']['deviceId'] ?? ($lookup['deviceId'] ?? null);

            if (!$deviceId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No GPS device found with that IMEI. Please check and try again.',
                ], 404);
            }

            // Step 2: Assign device to vehicle
            $response = $this->api->assignDevice($id, $deviceId);

            return response()->json([
                'success' => true,
                'message' => 'GPS device linked successfully.',
                'data'    => $response['data'] ?? $response,
            ]);

        } catch (\Exception $e) {
            return ApiErrors::json($e, 'vehicle.link', 'Failed to link device. Please try again.', [
                404 => 'No GPS device found with that IMEI.',
                403 => 'This device is not available for linking.',
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Unlink GPS device (AJAX)
    // -------------------------------------------------------------------------

    public function unlinkDevice(Request $request, string $id)
    {
        $request->validate([
            'assignmentId' => 'required|uuid',
        ]);

        try {
            $this->api->unassignDevice($request->input('assignmentId'));
            return response()->json([
                'success' => true,
                'message' => 'GPS device unlinked successfully.',
            ]);

        } catch (\Exception $e) {
            return ApiErrors::json($e, 'vehicle.unlink', 'Failed to unlink device. Please try again.');
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * JSON error response for the AJAX endpoints.
     * 401 → the page redirects to /login?expired=1 (session flushed here).
     * 403/404 → passed through as-is. Anything else → 502 (upstream failure).
     */
    private function jsonApiError(\Exception $e, string $action, string $message)
    {
        $code = (int) $e->getCode();

        if ($code === 401) {
            Session::flush();
            return response()->json([
                'success' => false,
                'code'    => 'TOKEN_EXPIRED',
                'message' => 'Session expired.',
            ], 401);
        }

        Log::error("VehicleController: {$action} failed", [
            'code'    => $code,
            'message' => $e->getMessage(),
        ]);

        $status = in_array($code, [403, 404], true) ? $code : 502;

        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    /** The signed-in customer's id, taken from the session (resolved from the API profile once). */
    private function signedInCustomerId(): string
    {
        $id = Session::get('customer_id');

        if (! is_string($id) || $id === '') {
            $profile = $this->api->getMyProfile();
            $id      = $profile['data']['customerId'] ?? ($profile['customerId'] ?? null);
            if (! is_string($id) || $id === '') {
                throw new \RuntimeException('Customer profile has no id.', 404);
            }
            Session::put('customer_id', $id);
        }

        return $id;
    }

    private function handleApiException(\Exception $e): void
    {
        if ($e->getCode() === 401) {
            Session::flush();
            redirect('/login?expired=1')->send();
        }
        Log::error('VehicleController: API error', [
            'code'    => $e->getCode(),
            'message' => $e->getMessage(),
        ]);
    }
}