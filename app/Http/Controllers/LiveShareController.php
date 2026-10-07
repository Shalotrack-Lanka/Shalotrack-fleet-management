<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Owner side of the temporary live link: create, list and stop. Thin proxy to the C# API, which
 * owns ownership checks, the 1-24 h range and the 3-active-links cap.
 *
 * The raw token exists only in the create response (the API keeps just a hash). It is turned
 * into the shareable URL here, returned once with no-store, and never logged.
 */
class LiveShareController extends Controller
{
    public function __construct(private ShalotrackApiService $api) {}

    public function index(string $id): JsonResponse
    {
        try {
            $res   = $this->api->getLiveShares($id);
            $links = array_map(fn ($l) => $this->shape($l), array_values(is_array($res['data'] ?? null) ? $res['data'] : []));
            return $this->json(['success' => true, 'links' => $links]);
        } catch (\Exception $e) {
            return $this->fail($e, 'index', 'Live links unavailable.');
        }
    }

    public function store(Request $request, string $id): JsonResponse
    {
        $v = $request->validate(['durationHours' => ['required', 'integer', 'between:1,24']]);

        try {
            $res  = $this->api->createLiveShare($id, (int) $v['durationHours']);
            $data = is_array($res['data'] ?? null) ? $res['data'] : [];
            $token = is_string($data['token'] ?? null) ? $data['token'] : '';

            // The API always returns a 43-char base64url token; anything else is a bug, not a link.
            if (!preg_match('/^[A-Za-z0-9_-]{43}$/', $token)) {
                Log::error('LiveShareController: API returned a malformed token');
                return $this->json(['success' => false, 'message' => 'Could not create the link.'], 502);
            }

            $link        = $this->shape($data);
            $link['url'] = $request->getSchemeAndHttpHost() . '/live/' . $token;

            return $this->json(['success' => true, 'link' => $link], 201);
        } catch (\Exception $e) {
            return $this->fail($e, 'store', 'Could not create the link.');
        }
    }

    public function destroy(string $linkId): JsonResponse
    {
        try {
            $this->api->revokeLiveShare($linkId);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->fail($e, 'destroy', 'Could not stop the link.');
        }
    }

    /** Only what the card needs (never the token except through store()). */
    private function shape(mixed $l): array
    {
        $l = is_array($l) ? $l : [];
        return [
            'linkId'    => (string) ($l['linkId'] ?? ''),
            'createdAt' => (string) ($l['createdAt'] ?? ''),
            'expiresAt' => (string) ($l['expiresAt'] ?? ''),
        ];
    }

    private function json(array $body, int $status = 200): JsonResponse
    {
        return response()->json($body, $status)->header('Cache-Control', 'no-store, private');
    }

    private function fail(\Exception $e, string $action, string $message): JsonResponse
    {
        $code = (int) $e->getCode();

        if ($code === 401) {
            Session::flush();
            return $this->json(['success' => false, 'code' => 'TOKEN_EXPIRED', 'message' => 'Session expired.'], 401);
        }

        Log::warning("LiveShareController: {$action} failed", ['code' => $code]);

        $status = in_array($code, [400, 402, 404, 409, 429], true) ? $code : 502;
        $text   = match ($status) {
            400     => ($e->getMessage() && $e->getMessage() !== 'REQUEST_FAILED') ? $e->getMessage() : 'That request is not valid.',
            402     => 'Renew your subscription to share this vehicle.',
            404     => 'Vehicle or link not found.',
            409     => ($e->getMessage() && $e->getMessage() !== 'REQUEST_FAILED') ? $e->getMessage() : 'You already have the maximum number of active links. Stop one first.',
            429     => 'Too many requests. Please wait a moment.',
            default => $message,
        };

        return $this->json(['success' => false, 'message' => $text], $status);
    }
}