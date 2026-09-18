<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * ShalotrackApiService
 *
 * Single responsibility: all outbound HTTP calls to the C# API.
 * Token is always read from the encrypted server-side session.
 * Controllers never build HTTP requests directly.
 */
class ShalotrackApiService
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('shalotrack.api_base_url'), '/');
        $this->timeout = (int) config('shalotrack.api_timeout', 10);
    }

    // -------------------------------------------------------------------------
    // Customer / Profile
    // -------------------------------------------------------------------------

    public function getMyProfile(): array
    {
        return $this->get('/api/Customers/me');
    }

    public function createProfile(array $data): array
    {
        return $this->post('/api/Customers', $data);
    }

    public function updateProfile(string $customerId, array $data): array
    {
        return $this->put("/api/Customers/{$customerId}", $data);
    }

    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------

    public function getDashboard(string $customerId): array
    {
        return $this->get("/api/Customers/{$customerId}/dashboard");
    }

    // -------------------------------------------------------------------------
    // Vehicles
    // -------------------------------------------------------------------------

    public function getVehiclesByCustomer(string $customerId): array
    {
        return $this->get("/api/Vehicles/customer/{$customerId}");
    }

    public function getVehicle(string $vehicleId): array
    {
        return $this->get("/api/Vehicles/{$vehicleId}");
    }

    public function createVehicle(array $data): array
    {
        return $this->post('/api/Vehicles', $data);
    }

    public function updateVehicle(string $vehicleId, array $data): array
    {
        return $this->put("/api/Vehicles/{$vehicleId}", $data);
    }

    public function deleteVehicle(string $vehicleId): void
    {
        $this->delete("/api/Vehicles/{$vehicleId}");
    }

    // -------------------------------------------------------------------------
    // GPS Devices
    // -------------------------------------------------------------------------

    /**
     * Customer-facing IMEI lookup.
     * Physical possession of the IMEI is the authorization.
     * Returns { deviceId, imei, ... } or 404.
     */
    public function lookupDeviceByImei(string $imei): array
    {
        return $this->get("/api/GpsDevices/lookup/{$imei}");
    }

    // -------------------------------------------------------------------------
    // Device Assignments (Link / Unlink)
    // -------------------------------------------------------------------------

    /**
     * Link a GPS device to a vehicle.
     * Requires: { VehicleId: guid, DeviceId: guid }
     * DeviceId is obtained by calling lookupDeviceByImei() first.
     */
    public function assignDevice(string $vehicleId, string $deviceId): array
    {
        return $this->post('/api/DeviceAssignments/assign', [
            'VehicleId' => $vehicleId,
            'DeviceId'  => $deviceId,
        ]);
    }

    /**
     * Unlink a GPS device from a vehicle.
     * Requires the assignment ID (from the vehicle's device assignment record).
     */
    public function unassignDevice(string $assignmentId): array
    {
        return $this->patch("/api/DeviceAssignments/{$assignmentId}/unassign");
    }

    // -------------------------------------------------------------------------
    // Current Locations
    // -------------------------------------------------------------------------

    public function getVehicleLocation(string $vehicleId): array
    {
        return $this->get("/api/CurrentLocations/vehicle/{$vehicleId}");
    }

    // -------------------------------------------------------------------------
    // GPS Tracking (Trip History)
    // -------------------------------------------------------------------------

    public function getTripHistory(string $vehicleId, string $from, string $to): array
    {
        return $this->get('/api/GpsTracking', [
            'vehicleId' => $vehicleId,
            'from'      => $from,
            'to'        => $to,
            'pageSize'  => 1000, // Max points for route playback
        ]);
    }

    /**
     * GET /api/GpsTracking/trips
     * Returns trip summaries — start/end points, distance, speed stats.
     */
    public function getTripSummary(string $vehicleId, string $from, string $to): array
    {
        return $this->get('/api/GpsTracking/trips', [
            'vehicleId' => $vehicleId,
            'from'      => $from,
            'to'        => $to,
        ]);
    }

    // -------------------------------------------------------------------------
    // Alerts
    // -------------------------------------------------------------------------

    public function getMyAlerts(int $page = 1, int $pageSize = 20, ?string $vehicleId = null): array
    {
        $params = ['page' => $page, 'pageSize' => $pageSize];
        if ($vehicleId) $params['vehicleId'] = $vehicleId;
        return $this->get('/api/Alerts', $params);
    }

    public function markAlertRead(string $alertId): array
    {
        // alertId is a long (int64) in the C# API
        return $this->patch("/api/Alerts/{$alertId}/read");
    }

    // -------------------------------------------------------------------------
    // Geofences
    // -------------------------------------------------------------------------

    public function getMyGeofences(): array
    {
        return $this->get('/api/Geofences/mine');
    }

    public function createGeofence(array $data): array
    {
        return $this->post('/api/Geofences', $data);
    }

    public function updateGeofence(string $geofenceId, array $data): array
    {
        return $this->put("/api/Geofences/{$geofenceId}", $data);
    }

    public function deleteGeofence(string $geofenceId): void
    {
        $this->delete("/api/Geofences/{$geofenceId}");
    }

    // -------------------------------------------------------------------------
    // Vehicle Sharing
    // -------------------------------------------------------------------------

    public function getMyShares(?string $vehicleId = null): array
    {
        $params = $vehicleId ? ['vehicleId' => $vehicleId] : [];
        return $this->get('/api/VehicleShares/my-shares', $params);
    }

    public function getSharedWithMe(): array
    {
        return $this->get('/api/VehicleShares/shared-with-me');
    }

    public function getPendingInvites(): array
    {
        return $this->get('/api/VehicleShares/pending-invites');
    }

    public function inviteShare(string $vehicleId, string $phoneNumber): array
    {
        return $this->post('/api/VehicleShares/invite', [
            'VehicleId'   => $vehicleId,
            'PhoneNumber' => $phoneNumber,
        ]);
    }

    public function respondToShare(string $shareId, bool $accept): array
    {
        return $this->post("/api/VehicleShares/{$shareId}/respond", [
            'Accept' => $accept,
        ]);
    }

    public function revokeShare(string $shareId): void
    {
        $this->delete("/api/VehicleShares/{$shareId}");
    }

    // -------------------------------------------------------------------------
    // Internal HTTP helpers
    // -------------------------------------------------------------------------

    private function get(string $path, array $query = []): array
    {
        $response = $this->client()->get($this->baseUrl . $path, $query);
        return $this->handle($response, 'GET', $path);
    }

    private function post(string $path, array $data = []): array
    {
        $response = $this->client()->post($this->baseUrl . $path, $data);
        return $this->handle($response, 'POST', $path);
    }

    private function put(string $path, array $data = []): array
    {
        $response = $this->client()->put($this->baseUrl . $path, $data);
        return $this->handle($response, 'PUT', $path);
    }

    private function patch(string $path, array $data = []): array
    {
        $response = $this->client()->patch($this->baseUrl . $path, $data);
        return $this->handle($response, 'PATCH', $path);
    }

    private function delete(string $path): void
    {
        $response = $this->client()->delete($this->baseUrl . $path);
        $this->handle($response, 'DELETE', $path);
    }

    private function client()
    {
        $token = Session::get('firebase_token');
        return Http::withToken($token)
            ->timeout($this->timeout)
            ->acceptJson()
            ->withHeaders(['Content-Type' => 'application/json']);
    }

    private function handle(Response $response, string $method, string $path): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $status = $response->status();
        $body   = $response->json();

        Log::error("ShalotrackApiService: {$method} {$path} failed", [
            'status' => $status,
            'body'   => $body,
        ]);

        match (true) {
            $status === 401 => throw new \Exception('UNAUTHENTICATED', 401),
            $status === 403 => throw new \Exception('FORBIDDEN', 403),
            $status === 404 => throw new \Exception('NOT_FOUND', 404),
            $status >= 500  => throw new \Exception('API_ERROR', 500),
            default         => throw new \Exception('REQUEST_FAILED', $status),
        };
    }
}
// NOTE: This append is invalid — the file already has a closing brace.
// The getTripSummary method must be added inside the class.
// See the full file rewrite below.