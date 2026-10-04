<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Subscription renewals — web parity with the Android RenewalActivity.
 *
 * Rules (identical to the mobile app, enforced server-side by the C# API):
 *  - The customer never sends or chooses an amount; the API owns the price list.
 *  - Nothing is ever marked "paid" here: we create a request, forward the bank
 *    slip, and show the status the API reports.
 *  - Slips: JPG / PNG / PDF, max 2 MB, validated by CONTENT (magic bytes), never
 *    by the browser-supplied name or type. Slips are never written to this
 *    server's disk and never logged — the bytes are streamed on to the API.
 *  - Only OWNED vehicles can be renewed (shared vehicles excluded).
 */
class RenewalController extends Controller
{
    private const DURATIONS = ['ThreeMonths', 'SixMonths', 'OneYear', 'TwoYears', 'ThreeYears', 'SixYears'];

    public function __construct(private ShalotrackApiService $api) {}

    // -------------------------------------------------------------------------
    // GET /renewals
    // -------------------------------------------------------------------------

    public function index()
    {
        // The customer is on the renewal page — the "renew now" nudge has done its job.
        // If a vehicle is still lapsed, the next 402 from the API sets it again.
        Session::forget('renewal_required');

        try {
            $profile    = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;

            $vehicles = [];
            if ($customerId) {
                $vRes     = $this->api->getVehiclesByCustomer($customerId);
                $vehicles = $vRes['data'] ?? $vRes;
                $vehicles = is_array($vehicles) ? $vehicles : [];
                // Demo vehicles are read-only and have no subscription to renew.
                $vehicles = array_values(array_filter(
                    $vehicles,
                    fn($v) => !($v['isDemoVehicle'] ?? false)
                ));
            }

            $renewalsRes = $this->api->getMyRenewals();
            $renewals    = $renewalsRes['data'] ?? $renewalsRes;
            $renewals    = is_array($renewals) ? $renewals : [];

            // The price list is non-fatal: if it fails we still show history.
            $packages = [];
            try {
                $pRes     = $this->api->getRenewalPackages();
                $packages = $pRes['data'] ?? $pRes;
                $packages = is_array($packages) ? $packages : [];
                $packages = array_values(array_filter(
                    $packages,
                    fn($p) => !empty($p['duration']) && (float) ($p['priceLkr'] ?? 0) > 0
                ));
            } catch (\Exception $e) {
                if ($e->getCode() === 401) {
                    throw $e;
                }
                Log::warning('RenewalController: package list unavailable', ['code' => $e->getCode()]);
            }

            return view('renewals.index', [
                'vehicles'  => $vehicles,
                'renewals'  => $renewals,
                'packages'  => $packages,
                'error'     => null,
            ]);

        } catch (\Exception $e) {
            Log::error('RenewalController: failed to load', ['code' => $e->getCode(), 'error' => $e->getMessage()]);
            if ($e->getCode() === 401) {
                Session::flush();
                return redirect('/login?expired=1');
            }
            return view('renewals.index', [
                'vehicles' => [],
                'renewals' => [],
                'packages' => [],
                'error'    => 'Could not load renewals. Please refresh.',
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // POST /renewals  (AJAX) — create a renewal request
    // -------------------------------------------------------------------------

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicleId'        => 'required|uuid',
            'duration'         => 'required|string|in:' . implode(',', self::DURATIONS),
            'paymentReference' => 'nullable|string|max:100',
        ]);

        try {
            $response = $this->api->createRenewal(
                $validated['vehicleId'],
                $validated['duration'],
                isset($validated['paymentReference']) && trim($validated['paymentReference']) !== ''
                    ? trim($validated['paymentReference'])
                    : null
            );

            return response()->json(['success' => true, 'data' => $response['data'] ?? $response]);

        } catch (\Exception $e) {
            return $this->fail($e, 'create', "Couldn't create the renewal request.");
        }
    }

    // -------------------------------------------------------------------------
    // POST /renewals/{id}/slip  (AJAX, multipart) — upload / replace the bank slip
    // -------------------------------------------------------------------------

    public function uploadSlip(Request $request, string $id)
    {
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $id)) {
            return response()->json(['success' => false, 'message' => 'Invalid renewal.'], 404);
        }

        // Size + extension-by-content check (finfo), 2 MB cap = API cap.
        $request->validate([
            'file' => ['required', 'file', 'max:2048', 'mimes:jpg,jpeg,png,pdf'],
        ], [
            'file.max'   => 'The slip must be 2 MB or smaller.',
            'file.mimes' => 'The slip must be a JPG, PNG or PDF.',
        ]);

        $file  = $request->file('file');
        $bytes = file_get_contents($file->getRealPath());

        if ($bytes === false || $bytes === '' || strlen($bytes) > 2 * 1024 * 1024) {
            return response()->json(['success' => false, 'message' => 'The slip must be 2 MB or smaller.'], 422);
        }

        // Second, independent check: magic bytes. File name + MIME are chosen
        // HERE from what the bytes are — nothing from the browser is forwarded.
        if (str_starts_with($bytes, "\xFF\xD8\xFF")) {
            [$mime, $name] = ['image/jpeg', 'slip.jpg'];
        } elseif (str_starts_with($bytes, "\x89PNG\r\n\x1A\n")) {
            [$mime, $name] = ['image/png', 'slip.png'];
        } elseif (str_starts_with($bytes, '%PDF-')) {
            [$mime, $name] = ['application/pdf', 'slip.pdf'];
        } else {
            return response()->json(['success' => false, 'message' => 'The slip must be a JPG, PNG or PDF.'], 422);
        }

        try {
            $response = $this->api->uploadRenewalSlip($id, $bytes, $mime, $name);
            return response()->json(['success' => true, 'data' => $response['data'] ?? $response]);

        } catch (\Exception $e) {
            return $this->fail($e, 'slip', "Couldn't upload the slip. Please try again.");
        }
    }

    // -------------------------------------------------------------------------
    // POST /renewals/{id}/cancel  (AJAX)
    // -------------------------------------------------------------------------

    public function cancel(string $id)
    {
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $id)) {
            return response()->json(['success' => false, 'message' => 'Invalid renewal.'], 404);
        }

        try {
            $this->api->cancelRenewal($id);
            return response()->json(['success' => true, 'message' => 'Renewal request cancelled.']);

        } catch (\Exception $e) {
            return $this->fail($e, 'cancel', "Couldn't cancel the request.");
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Uniform JSON failure.
     *  401      → session flushed, page redirects to /login?expired=1
     *  403/404  → fixed friendly text
     *  400/409/422 → the API's own customer-safe message (e.g. "open renewal exists")
     *  anything else → fallback text, 502 (upstream problem, not the customer's fault)
     * Never echoes exception text from 5xx/unknown failures.
     */
    private function fail(\Exception $e, string $action, string $fallback)
    {
        $code = (int) $e->getCode();

        if ($code === 401) {
            Session::flush();
            return response()->json(['success' => false, 'code' => 'TOKEN_EXPIRED', 'message' => 'Session expired.'], 401);
        }

        Log::error("RenewalController: {$action} failed", ['code' => $code, 'message' => $e->getMessage()]);

        if ($code === 403) {
            return response()->json(['success' => false, 'message' => 'You can only renew vehicles you own.'], 403);
        }
        if ($code === 404) {
            return response()->json(['success' => false, 'message' => 'That renewal request was not found.'], 404);
        }
        if (in_array($code, [400, 409, 413, 415, 422], true)) {
            $msg = $e->getMessage();
            $msg = ($msg !== '' && $msg !== 'REQUEST_FAILED') ? $msg : $fallback;
            return response()->json(['success' => false, 'message' => $msg], 422);
        }

        return response()->json(['success' => false, 'message' => $fallback], 502);
    }
}