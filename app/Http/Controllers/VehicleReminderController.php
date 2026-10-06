<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

/**
 * Licence / insurance / service reminders. Thin proxy to the C# API, which owns the data and
 * the ownership rules; this layer validates input and returns only the fields the page needs.
 */
class VehicleReminderController extends Controller
{
    public function __construct(private ShalotrackApiService $api) {}

    public function index(string $id): JsonResponse
    {
        try {
            $res = $this->api->getVehicleReminders($id);
            return response()->json([
                'success'   => true,
                'reminders' => array_values(array_map([$this, 'shape'], $res['data'] ?? [])),
            ]);
        } catch (\Exception $e) {
            return $this->fail($e, 'index', 'Reminders unavailable.');
        }
    }

    public function save(Request $request, string $id): JsonResponse
    {
        $v = $request->validate([
            'type'    => ['required', 'integer', Rule::in([0, 1, 2])],
            'dueDate' => ['required', 'date_format:Y-m-d'],
            'notes'   => ['nullable', 'string', 'max:200'],
        ]);

        try {
            $res = $this->api->saveVehicleReminder(
                $id,
                (int) $v['type'],
                $v['dueDate'],
                isset($v['notes']) && trim($v['notes']) !== '' ? trim($v['notes']) : null
            );
            return response()->json(['success' => true, 'reminder' => $this->shape($res['data'] ?? [])]);
        } catch (\Exception $e) {
            return $this->fail($e, 'save', 'Could not save the reminder.');
        }
    }

    public function destroy(string $reminderId): JsonResponse
    {
        try {
            $this->api->deleteVehicleReminder($reminderId);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->fail($e, 'destroy', 'Could not remove the reminder.');
        }
    }

    /** Only what the card needs: no vehicle id echo, nothing else the API might add later. */
    private function shape(mixed $r): array
    {
        $r = is_array($r) ? $r : [];
        return [
            'reminderId' => $r['reminderId'] ?? null,
            'type'       => (int) ($r['type'] ?? 0),
            'dueDate'    => $r['dueDate'] ?? null,
            'daysLeft'   => isset($r['daysLeft']) ? (int) $r['daysLeft'] : null,
            'notes'      => $r['notes'] ?? null,
        ];
    }

    private function fail(\Exception $e, string $action, string $message): JsonResponse
    {
        $code = (int) $e->getCode();

        if ($code === 401) {
            Session::flush();
            return response()->json(['success' => false, 'code' => 'TOKEN_EXPIRED', 'message' => 'Session expired.'], 401);
        }

        // Class + status only: the API message can echo user input (notes), so it is not logged.
        Log::warning("VehicleReminderController: {$action} failed", ['code' => $code]);

        $status = in_array($code, [400, 403, 404, 429], true) ? $code : 502;
        $text   = match ($status) {
            400     => 'That reminder is not valid. Check the date and note.',
            403, 404 => 'Reminders are only available for vehicles you own.',
            429     => 'Too many requests. Please wait a moment.',
            default => $message,
        };

        return response()->json(['success' => false, 'message' => $text], $status);
    }
}