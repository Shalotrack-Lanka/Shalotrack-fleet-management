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
    // Device status (battery, power, GPS fix, last contact)
    // -------------------------------------------------------------------------

    public function getVehicleDeviceStatus(string $vehicleId): array
    {
        return $this->get("/api/DeviceStatus/vehicle/{$vehicleId}");
    }

    // -------------------------------------------------------------------------
    // Temporary live-location links (owner side, authenticated)
    // -------------------------------------------------------------------------

    public function getLiveShares(string $vehicleId): array
    {
        return $this->get("/api/LiveShare/vehicle/{$vehicleId}");
    }

    public function createLiveShare(string $vehicleId, int $hours): array
    {
        return $this->post("/api/LiveShare/vehicle/{$vehicleId}", ['durationHours' => $hours]);
    }

    public function revokeLiveShare(string $linkId): void
    {
        $this->delete("/api/LiveShare/{$linkId}");
    }

    /**
     * The anonymous public read for a live link. Deliberately does NOT use handle()/client():
     * there is no session token here, and handle() logs the request path, which contains the
     * link token. Nothing about this call (token, body) is ever logged.
     *
     * @throws \Exception NOT_FOUND (404) for any expired/stopped/unknown link, RATE_LIMITED (429),
     *                    API_ERROR (500) for anything else.
     */
    public function getPublicLive(string $token, bool $withTrail): array
    {
        $response = Http::timeout($this->timeout)
            ->acceptJson()
            ->get($this->baseUrl . '/api/public/live/' . rawurlencode($token), [
                'trail' => $withTrail ? 'true' : 'false',
            ]);

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        match (true) {
            $response->status() === 404 => throw new \Exception('NOT_FOUND', 404),
            $response->status() === 429 => throw new \Exception('RATE_LIMITED', 429),
            default                     => throw new \Exception('API_ERROR', 500),
        };
    }

    // -------------------------------------------------------------------------
    // Weekly summary push (the signed-in customer's own on/off switch)
    // -------------------------------------------------------------------------

    public function getWeeklySummarySetting(): array
    {
        return $this->get('/api/WeeklySummary/settings');
    }

    public function saveWeeklySummarySetting(bool $enabled): array
    {
        return $this->put('/api/WeeklySummary/settings', ['enabled' => $enabled]);
    }

    /** Everything held about the signed-in customer (download my data). */
    public function exportAccountData(): array
    {
        return $this->get('/api/Account/export');
    }

    // -------------------------------------------------------------------------
    // Per-vehicle alert settings (speed limit, idle alert)
    // -------------------------------------------------------------------------

    public function getVehicleAlertSettings(string $vehicleId): array
    {
        return $this->get("/api/VehicleAlertSettings/vehicle/{$vehicleId}");
    }

    public function saveVehicleAlertSettings(string $vehicleId, int $speedLimitKmh, bool $idleEnabled, int $idleMinutes): array
    {
        return $this->put("/api/VehicleAlertSettings/vehicle/{$vehicleId}", [
            'speedLimitKmh'    => $speedLimitKmh,
            'idleAlertEnabled' => $idleEnabled,
            'idleAlertMinutes' => $idleMinutes,
        ]);
    }

    // -------------------------------------------------------------------------
    // Vehicle reminders (revenue licence / insurance / service due)
    // -------------------------------------------------------------------------

    public function getVehicleReminders(string $vehicleId): array
    {
        return $this->get("/api/VehicleReminders/vehicle/{$vehicleId}");
    }

    public function saveVehicleReminder(string $vehicleId, int $type, string $dueDate, ?string $notes): array
    {
        return $this->put("/api/VehicleReminders/vehicle/{$vehicleId}", [
            'type'    => $type,
            'dueDate' => $dueDate,
            'notes'   => $notes,
        ]);
    }

    public function deleteVehicleReminder(string $reminderId): void
    {
        $this->delete("/api/VehicleReminders/{$reminderId}");
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
    // Vehicles the signed-in customer can TRACK (owned + accepted shares)
    // -------------------------------------------------------------------------

    /**
     * Owned vehicles plus vehicles other customers have shared with this customer
     * (accepted shares only), all in one list with the same keys.
     *
     * Trip history, stats and reports are allowed by the API for owner, accepted
     * share and demo vehicle alike, so every portal screen that lets the customer
     * pick "a vehicle to look at" should use this instead of getVehiclesByCustomer().
     * Shared entries carry isShared=true and ownerName so the UI can label them.
     *
     * A failure to load shares is NOT fatal: the owned list is still returned.
     * An owned-list failure is thrown as before (callers already handle 401 etc.).
     */
    public function getTrackableVehicles(string $customerId): array
    {
        $response = $this->getVehiclesByCustomer($customerId);
        $owned    = $response['data'] ?? $response;
        $owned    = is_array($owned) ? array_values($owned) : [];

        $shared = [];
        try {
            $sharesRes = $this->getSharedWithMe();
            $shares    = $sharesRes['data'] ?? $sharesRes;
            $shares    = is_array($shares) ? $shares : [];

            $ownedIds = array_map(fn($v) => strtolower((string) ($v['vehicleId'] ?? '')), $owned);

            foreach ($shares as $sh) {
                if (($sh['status'] ?? '') !== 'Accepted' || empty($sh['vehicleId'])) {
                    continue;
                }
                if (in_array(strtolower((string) $sh['vehicleId']), $ownedIds, true)) {
                    continue; // never list a vehicle twice
                }
                $shared[] = [
                    'vehicleId'     => $sh['vehicleId'],
                    'vehicleNumber' => $sh['vehicleNumber'] ?? 'Unknown',
                    'make'          => $sh['make']  ?? '',
                    'model'         => $sh['model'] ?? '',
                    'year'          => $sh['year']  ?? null,
                    'vehicleType'   => $sh['vehicleType'] ?? null,
                    'hasGpsDevice'  => true,   // shared for tracking, so a GPS device is linked
                    'isDemoVehicle' => false,
                    'isShared'      => true,
                    'ownerName'     => $sh['otherPartyName'] ?? null,
                    'shareId'       => $sh['shareId'] ?? null,
                    'color'         => null,
                    'fuelType'      => null,
                    'imei'          => null,
                ];
            }
        } catch (\Throwable $e) {
            if ((int) $e->getCode() === 401) {
                throw $e;   // session expired — let the caller redirect to login
            }
            Log::warning('getTrackableVehicles: shared vehicles unavailable', ['error' => $e->getMessage()]);
        }

        return array_merge($owned, $shared);
    }

    // -------------------------------------------------------------------------
    // Alerts
    // -------------------------------------------------------------------------

    /**
     * GET /api/Alerts/report — bounded [from, to] window with per-type counts.
     * Same endpoint the Android "Alert Report" uses. $from / $to are UTC ISO strings.
     */
    public function getAlertReport(string $vehicleId, string $from, string $to): array
    {
        return $this->get('/api/Alerts/report', [
            'vehicleId' => $vehicleId,
            'from'      => $from,
            'to'        => $to,
        ]);
    }

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
        return $this->get('/api/Geofences');
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
    // Complaints
    // -------------------------------------------------------------------------

    public function getMyComplaints(): array
    {
        return $this->get('/api/Complaints/mine');
    }

    public function getComplaint(string $complaintId): array
    {
        return $this->get("/api/Complaints/{$complaintId}");
    }

    /**
     * @param int $category ComplaintCategory ordinal: DeviceIssue=0, Billing=1, AppBug=2, Other=3
     */
    public function fileComplaint(string $vehicleId, int $category, string $description): array
    {
        return $this->post('/api/Complaints', [
            'vehicleId'   => $vehicleId,
            'category'    => $category,
            'description' => $description,
        ]);
    }

    public function replyToComplaint(string $complaintId, string $message): array
    {
        return $this->post("/api/Complaints/{$complaintId}/reply", [
            'message' => $message,
        ]);
    }

    // -------------------------------------------------------------------------
    // Renewals (api/Renewals — enum NAMES as strings, not ordinals)
    // duration: ThreeMonths | SixMonths | OneYear | TwoYears | ThreeYears | SixYears
    // The server owns pricing; the client never sends an amount.
    // -------------------------------------------------------------------------

    public function getRenewalPackages(): array
    {
        return $this->get('/api/Renewals/packages');
    }

    public function getMyRenewals(): array
    {
        return $this->get('/api/Renewals');
    }

    public function createRenewal(string $vehicleId, string $duration, ?string $paymentReference): array
    {
        return $this->post('/api/Renewals', array_filter([
            'vehicleId'        => $vehicleId,
            'duration'         => $duration,
            'paymentReference' => $paymentReference,
        ], fn($v) => $v !== null));
    }

    /**
     * Forward a bank slip to the API. The part MUST be named "file".
     * $bytes is the raw upload; the file name is chosen by the caller from the
     * sniffed content type, never from the browser-supplied name.
     */
    public function uploadRenewalSlip(string $renewalId, string $bytes, string $mime, string $fileName): array
    {
        $response = Http::withToken(Session::get('firebase_token'))
            ->timeout(max($this->timeout, 30))   // slips are up to 2 MB
            ->acceptJson()
            ->attach('file', $bytes, $fileName, ['Content-Type' => $mime])
            ->post($this->baseUrl . "/api/Renewals/{$renewalId}/slip");

        return $this->handle($response, 'POST', "/api/Renewals/{$renewalId}/slip");
    }

    public function cancelRenewal(string $renewalId): array
    {
        return $this->patch("/api/Renewals/{$renewalId}/cancel");
    }

    // -------------------------------------------------------------------------
    // SOS — POST api/SOS/{vehicleId}/trigger. No body: the API resolves the
    // location itself from CurrentLocations, exactly as for the Android app.
    // -------------------------------------------------------------------------

    public function triggerSos(string $vehicleId): array
    {
        return $this->post("/api/SOS/{$vehicleId}/trigger");
    }

    // -------------------------------------------------------------------------
    // Emergency Contacts
    // -------------------------------------------------------------------------

    public function getEmergencyContacts(): array
    {
        return $this->get('/api/EmergencyContacts');
    }

    public function createEmergencyContact(array $data): array
    {
        return $this->post('/api/EmergencyContacts', $data);
    }

    public function deleteEmergencyContact(string $contactId): void
    {
        $this->delete("/api/EmergencyContacts/{$contactId}");
    }

    // -------------------------------------------------------------------------
    // Saved Places
    // -------------------------------------------------------------------------

    public function getSavedPlaces(): array
    {
        return $this->get('/api/SavedPlaces');
    }

    public function createSavedPlace(array $data): array
    {
        return $this->post('/api/SavedPlaces', $data);
    }

    public function deleteSavedPlace(string $placeId): void
    {
        $this->delete("/api/SavedPlaces/{$placeId}");
    }

    // -------------------------------------------------------------------------
    // Vehicle Statistics
    // -------------------------------------------------------------------------

    public function getVehicleStats(string $vehicleId, string $period = 'week'): array
    {
        return $this->get("/api/VehicleStats/{$vehicleId}", ['period' => $period]);
    }

    /**
     * Custom date-range stats — mirrors Android's getVehicleStatsForRange().
     * $from / $to are ISO-8601 date strings validated by the controller before
     * they reach here; we pass them verbatim as the API expects them.
     */
    public function getVehicleStatsForRange(string $vehicleId, string $from, string $to): array
    {
        return $this->get("/api/VehicleStats/{$vehicleId}", ['from' => $from, 'to' => $to]);
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

    private static function apiMessage(mixed $body): ?string
    {
        if (!is_array($body)) {
            return null;
        }
        $message = is_string($body['message'] ?? null) ? trim($body['message']) : null;
        $first   = (is_array($body['errors'] ?? null) && is_string($body['errors'][0] ?? null))
            ? trim($body['errors'][0]) : null;

        $text = trim(($message ?? '') . ' ' . ($first ?? ''));
        return $text === '' ? null : mb_substr($text, 0, 300);
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
            'body'   => \App\Support\Pii::apiBody($body),   // never the full body: validation errors echo personal data
        ]);

        // 402 + SUBSCRIPTION_RENEWAL_REQUIRED: a vehicle's subscription lapsed.
        // Remember it in the session so the layout can show a "Renew now" banner
        // on the next page render (same signal the Android app's interceptor
        // turns into its app-wide renewal prompt).
        if ($status === 402 && str_contains((string) $response->body(), 'SUBSCRIPTION_RENEWAL_REQUIRED')) {
            Session::put('renewal_required', true);
            throw new \Exception('RENEWAL_REQUIRED', 402);
        }

        match (true) {
            $status === 401 => throw new \Exception('UNAUTHENTICATED', 401),
            $status === 403 => throw new \Exception('FORBIDDEN', 403),
            $status === 404 => throw new \Exception('NOT_FOUND', 404),
            $status >= 500  => throw new \Exception('API_ERROR', 500),
            // 4xx: carry the API's own customer-facing message (same fields the
            // mobile app reads: "message" + first of "errors[]") so controllers
            // can show e.g. "You already have an open renewal for this vehicle".
            default         => throw new \Exception(self::apiMessage($body) ?? 'REQUEST_FAILED', $status),
        };
    }
}