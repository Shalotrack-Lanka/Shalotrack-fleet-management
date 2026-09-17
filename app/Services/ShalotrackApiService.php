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
 *
 * Rules:
 * - Controllers NEVER build HTTP requests directly — only call this service.
 * - The Firebase token is ALWAYS read from the encrypted server-side session.
 * - Never read the token from the incoming browser request.
 * - All errors are caught here and surfaced as consistent exceptions.
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
    // Profile
    // -------------------------------------------------------------------------

    public function getMyProfile(): array
    {
        return $this->get('/api/Customers/me');
    }

    // -------------------------------------------------------------------------
    // Vehicles
    // -------------------------------------------------------------------------

    public function getMyVehicles(): array
    {
        return $this->get('/api/Vehicles/my');
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
    // Current Locations
    // -------------------------------------------------------------------------

    public function getVehicleLocation(string $vehicleId): array
    {
        return $this->get("/api/CurrentLocations/{$vehicleId}");
    }

    // -------------------------------------------------------------------------
    // Device Assignments (Link / Unlink GPS Device)
    // -------------------------------------------------------------------------

    public function linkDevice(array $data): array
    {
        return $this->post('/api/DeviceAssignments', $data);
    }

    public function unlinkDevice(string $assignmentId): void
    {
        $this->delete("/api/DeviceAssignments/{$assignmentId}");
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
        ]);
    }

    // -------------------------------------------------------------------------
    // Alerts
    // -------------------------------------------------------------------------

    public function getMyAlerts(): array
    {
        return $this->get('/api/Alerts/my');
    }

    public function markAlertRead(string $alertId): array
    {
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

    public function getMyShares(): array
    {
        return $this->get('/api/VehicleShares/my');
    }

    public function createShare(array $data): array
    {
        return $this->post('/api/VehicleShares', $data);
    }

    public function acceptShare(string $shareId): array
    {
        return $this->patch("/api/VehicleShares/{$shareId}/accept");
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
        $response = $this->client()
            ->get($this->baseUrl . $path, $query);

        return $this->handle($response, 'GET', $path);
    }

    private function post(string $path, array $data = []): array
    {
        $response = $this->client()
            ->post($this->baseUrl . $path, $data);

        return $this->handle($response, 'POST', $path);
    }

    private function put(string $path, array $data = []): array
    {
        $response = $this->client()
            ->put($this->baseUrl . $path, $data);

        return $this->handle($response, 'PUT', $path);
    }

    private function patch(string $path, array $data = []): array
    {
        $response = $this->client()
            ->patch($this->baseUrl . $path, $data);

        return $this->handle($response, 'PATCH', $path);
    }

    private function delete(string $path): void
    {
        $response = $this->client()
            ->delete($this->baseUrl . $path);

        $this->handle($response, 'DELETE', $path);
    }

    /**
     * Build the HTTP client with the Firebase token from session.
     * Token is NEVER sourced from the incoming browser request.
     */
    private function client()
    {
        $token = Session::get('firebase_token');

        return Http::withToken($token)
            ->timeout($this->timeout)
            ->acceptJson()
            ->withHeaders([
                'Content-Type' => 'application/json',
            ]);
    }

    /**
     * Handle API response — map errors to exceptions consistently.
     */
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

        // Map C# API status codes to meaningful exceptions
        match (true) {
            $status === 401 => throw new \Exception('UNAUTHENTICATED', 401),
            $status === 403 => throw new \Exception('FORBIDDEN', 403),
            $status === 404 => throw new \Exception('NOT_FOUND', 404),
            $status >= 500  => throw new \Exception('API_ERROR', 500),
            default         => throw new \Exception('REQUEST_FAILED', $status),
        };
    }
}