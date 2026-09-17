<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class VehicleController extends Controller
{
    public function __construct(private ShalotrackApiService $api) {}

    // -------------------------------------------------------------------------
    // Vehicle list
    // -------------------------------------------------------------------------

    public function index()
    {
        try {
            $profile    = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;

            if (!$customerId) {
                return view('vehicles.index', [
                    'vehicles'   => [],
                    'customerId' => null,
                    'error'      => 'Could not load your profile.',
                ]);
            }

            $response = $this->api->getVehiclesByCustomer($customerId);
            $vehicles = $response['data'] ?? $response;

            return view('vehicles.index', [
                'vehicles'   => is_array($vehicles) ? $vehicles : [],
                'customerId' => $customerId,
                'error'      => null,
            ]);

        } catch (\Exception $e) {
            $this->handleApiException($e);
            return view('vehicles.index', [
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
    // Create vehicle (AJAX)
    // -------------------------------------------------------------------------

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customerId'    => 'required|uuid',
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
            $response = $this->api->createVehicle([
                'CustomerId'    => $validated['customerId'],
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
            Log::error('VehicleController: create failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to add vehicle. Please try again.',
            ], 422);
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
            Log::error('VehicleController: update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update vehicle. Please try again.',
            ], 422);
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
            Log::error('VehicleController: delete failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete vehicle. Please try again.',
            ], 422);
        }
    }

    // -------------------------------------------------------------------------
    // Link GPS device (AJAX)
    // Two-step: IMEI lookup → assign
    // -------------------------------------------------------------------------

    public function linkDevice(Request $request, string $id)
    {
        $request->validate([
            'imei' => 'required|string|min:15|max:17',
        ]);

        $imei = preg_replace('/\D/', '', $request->input('imei'));

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
            Log::error('VehicleController: linkDevice failed', ['error' => $e->getMessage()]);

            $message = match ($e->getCode()) {
                404     => 'No GPS device found with that IMEI.',
                403     => 'This device is not available for linking.',
                default => 'Failed to link device. Please try again.',
            };

            return response()->json([
                'success' => false,
                'message' => $message,
            ], $e->getCode() ?: 422);
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
            Log::error('VehicleController: unlinkDevice failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlink device. Please try again.',
            ], 422);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

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