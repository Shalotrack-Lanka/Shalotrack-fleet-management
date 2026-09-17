<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class GeofenceController extends Controller
{
    public function __construct(private ShalotrackApiService $api) {}

    public function index()
    {
        try {
            $response  = $this->api->getMyGeofences();
            $data      = $response['data'] ?? $response;
            $geofences = is_array($data) ? $data : [];

            // Get vehicles for the create modal scope picker
            $profile    = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;
            $vehicles   = [];
            if ($customerId) {
                $vResponse = $this->api->getVehiclesByCustomer($customerId);
                $vehicles  = $vResponse['data'] ?? $vResponse;
                $vehicles  = is_array($vehicles) ? $vehicles : [];
            }

            return view('geofences.index', [
                'geofences' => $geofences,
                'vehicles'  => $vehicles,
                'error'     => null,
            ]);

        } catch (\Exception $e) {
            Log::error('GeofenceController: Failed to load', ['error' => $e->getMessage()]);
            if ($e->getCode() === 401) { Session::flush(); return redirect('/login?expired=1'); }
            return view('geofences.index', [
                'geofences' => [],
                'vehicles'  => [],
                'error'     => 'Could not load geofences. Please refresh.',
            ]);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'latitude'     => 'required|numeric|between:-90,90',
            'longitude'    => 'required|numeric|between:-180,180',
            'radiusMeters' => 'required|integer|min:50|max:50000',
            'vehicleId'    => 'nullable|uuid',
            'alertOnEnter' => 'boolean',
            'alertOnExit'  => 'boolean',
        ]);

        try {
            $response = $this->api->createGeofence([
                'Name'         => $validated['name'],
                'Latitude'     => (float) $validated['latitude'],
                'Longitude'    => (float) $validated['longitude'],
                'RadiusMeters' => (int) $validated['radiusMeters'],
                'VehicleId'    => $validated['vehicleId'] ?? null,
                'AlertOnEnter' => $validated['alertOnEnter'] ?? true,
                'AlertOnExit'  => $validated['alertOnExit'] ?? true,
            ]);

            return response()->json(['success' => true, 'data' => $response['data'] ?? $response]);

        } catch (\Exception $e) {
            Log::error('GeofenceController: store failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to create geofence.'], 422);
        }
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'latitude'     => 'required|numeric|between:-90,90',
            'longitude'    => 'required|numeric|between:-180,180',
            'radiusMeters' => 'required|integer|min:50|max:50000',
            'vehicleId'    => 'nullable|uuid',
            'alertOnEnter' => 'boolean',
            'alertOnExit'  => 'boolean',
            'isActive'     => 'boolean',
        ]);

        try {
            $response = $this->api->updateGeofence($id, [
                'Name'         => $validated['name'],
                'Latitude'     => (float) $validated['latitude'],
                'Longitude'    => (float) $validated['longitude'],
                'RadiusMeters' => (int) $validated['radiusMeters'],
                'VehicleId'    => $validated['vehicleId'] ?? null,
                'AlertOnEnter' => $validated['alertOnEnter'] ?? true,
                'AlertOnExit'  => $validated['alertOnExit'] ?? true,
                'IsActive'     => $validated['isActive'] ?? true,
            ]);

            return response()->json(['success' => true, 'data' => $response['data'] ?? $response]);

        } catch (\Exception $e) {
            Log::error('GeofenceController: update failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to update geofence.'], 422);
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->api->deleteGeofence($id);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('GeofenceController: destroy failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to delete geofence.'], 422);
        }
    }
}