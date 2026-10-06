<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Per-vehicle speed limit and idle alert. Thin proxy to the C# API, which owns the data, the
 * allowed ranges and the ownership rules; this layer validates input shape and returns only
 * the fields the page needs. The ranges below mirror the API's (the API stays authoritative).
 */
class VehicleAlertSettingsController extends Controller
{
    public function __construct(private ShalotrackApiService $api) {}

    public function show(string $id): JsonResponse
    {
        try {
            $res = $this->api->getVehicleAlertSettings($id);
            return response()->json(['success' => true, 'settings' => $this->shape($res['data'] ?? [])]);
        } catch (\Exception $e) {
            return $this->fail($e, 'show', 'Alert settings unavailable.');
        }
    }

    public function save(Request $request, string $id): JsonResponse
    {
        $v = $request->validate([
            'speedLimitKmh'    => ['required', 'integer', 'between:20,200'],
            'idleAlertEnabled' => ['required', 'boolean'],
            'idleAlertMinutes' => ['required_if:idleAlertEnabled,true', 'nullable', 'integer', 'between:3,120'],
        ]);

        try {
            $res = $this->api->saveVehicleAlertSettings(
                $id,
                (int) $v['speedLimitKmh'],
                (bool) $v['idleAlertEnabled'],
                (int) ($v['idleAlertMinutes'] ?? 10)
            );
            return response()->json(['success' => true, 'settings' => $this->shape($res['data'] ?? [])]);
        } catch (\Exception $e) {
            return $this->fail($e, 'save', 'Could not save the alert settings.');
        }
    }

    /** Only what the card needs. */
    private function shape(mixed $r): array
    {
        $r = is_array($r) ? $r : [];
        return [
            'speedLimitKmh'        => (int) ($r['speedLimitKmh'] ?? 80),
            'idleAlertEnabled'     => (bool) ($r['idleAlertEnabled'] ?? false),
            'idleAlertMinutes'     => (int) ($r['idleAlertMinutes'] ?? 10),
            'defaultSpeedLimitKmh' => (int) ($r['defaultSpeedLimitKmh'] ?? 80),
            'minSpeedLimitKmh'     => (int) ($r['minSpeedLimitKmh'] ?? 20),
            'maxSpeedLimitKmh'     => (int) ($r['maxSpeedLimitKmh'] ?? 200),
            'minIdleMinutes'       => (int) ($r['minIdleMinutes'] ?? 3),
            'maxIdleMinutes'       => (int) ($r['maxIdleMinutes'] ?? 120),
        ];
    }

    private function fail(\Exception $e, string $action, string $message): JsonResponse
    {
        $code = (int) $e->getCode();

        if ($code === 401) {
            Session::flush();
            return response()->json(['success' => false, 'code' => 'TOKEN_EXPIRED', 'message' => 'Session expired.'], 401);
        }

        Log::warning("VehicleAlertSettingsController: {$action} failed", ['code' => $code]);

        $status = in_array($code, [400, 403, 404, 429], true) ? $code : 502;
        $text   = match ($status) {
            400      => 'Those values are not valid. Check the speed limit and idle time.',
            403, 404 => 'Alert settings are only available for vehicles you own.',
            429      => 'Too many requests. Please wait a moment.',
            default  => $message,
        };

        return response()->json(['success' => false, 'message' => $text], $status);
    }
}