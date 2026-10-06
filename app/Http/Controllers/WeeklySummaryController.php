<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * The signed-in customer's own on/off switch for the weekly summary push. Thin proxy to the C#
 * API, which always acts on the caller's own record (resolved from the token), so there is no
 * id to send or tamper with.
 */
class WeeklySummaryController extends Controller
{
    public function __construct(private ShalotrackApiService $api) {}

    public function show(): JsonResponse
    {
        try {
            $res = $this->api->getWeeklySummarySetting();
            return response()->json(['success' => true, 'enabled' => (bool) ($res['data']['enabled'] ?? true)]);
        } catch (\Exception $e) {
            return $this->fail($e, 'show', 'Setting unavailable.');
        }
    }

    public function save(Request $request): JsonResponse
    {
        $v = $request->validate(['enabled' => ['required', 'boolean']]);

        try {
            $res = $this->api->saveWeeklySummarySetting((bool) $v['enabled']);
            return response()->json(['success' => true, 'enabled' => (bool) ($res['data']['enabled'] ?? $v['enabled'])]);
        } catch (\Exception $e) {
            return $this->fail($e, 'save', 'Could not save the setting.');
        }
    }

    private function fail(\Exception $e, string $action, string $message): JsonResponse
    {
        $code = (int) $e->getCode();

        if ($code === 401) {
            Session::flush();
            return response()->json(['success' => false, 'code' => 'TOKEN_EXPIRED', 'message' => 'Session expired.'], 401);
        }

        Log::warning("WeeklySummaryController: {$action} failed", ['code' => $code]);

        $status = in_array($code, [400, 404, 429], true) ? $code : 502;
        $text   = $status === 429 ? 'Too many requests. Please wait a moment.' : $message;

        return response()->json(['success' => false, 'message' => $text], $status);
    }
}