<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * The signed-in customer's own data rights (download now; delete in the next part). Thin proxy to
 * the C# API, which always acts on the caller's own record, so there is no id to send or tamper with.
 */
class AccountController extends Controller
{
    public function __construct(private ShalotrackApiService $api) {}

    /** "Download my data": streams the API's export as a JSON file. Nothing is stored on this server. */
    public function export(): Response|JsonResponse
    {
        try {
            $res  = $this->api->exportAccountData();
            $data = $res['data'] ?? null;
            if (!is_array($data)) {
                return response()->json(['success' => false, 'message' => 'Export unavailable.'], 502);
            }

            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

            return response($json, 200, [
                'Content-Type'           => 'application/json; charset=utf-8',
                'Content-Disposition'    => 'attachment; filename="shalotrack-my-data-' . now()->format('Y-m-d') . '.json"',
                'Cache-Control'          => 'no-store, private',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (\Exception $e) {
            $code = (int) $e->getCode();

            if ($code === 401) {
                Session::flush();
                return response()->json(['success' => false, 'code' => 'TOKEN_EXPIRED', 'message' => 'Session expired.'], 401);
            }

            Log::warning('AccountController: export failed', ['code' => $code]);

            $status = $code === 429 ? 429 : 502;
            $text   = $status === 429
                ? 'You can download your data up to 3 times an hour. Please try again later.'
                : 'Could not prepare your data. Please try again.';

            return response()->json(['success' => false, 'message' => $text], $status);
        }
    }
}