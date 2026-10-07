<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use App\Support\PdfExport;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * The signed-in customer's own data rights (download now; delete in the next part). Thin proxy to
 * the C# API, which always acts on the caller's own record, so there is no id to send or tamper with.
 */
class AccountController extends Controller
{
    /** Alerts shown in the PDF (the JSON has them all). Keeps the PDF small and fast. */
    private const PDF_ALERT_LIMIT = 200;

    public function __construct(private ShalotrackApiService $api) {}

    /**
     * "Download my data" as a readable PDF or a machine-readable JSON file. Both come from the same
     * API call, so they always agree. Nothing is stored on this server.
     */
    public function export(Request $request): Response|JsonResponse
    {
        $v = $request->validate(['format' => ['nullable', 'in:pdf,json']]);
        $format = $v['format'] ?? 'pdf';

        try {
            $res  = $this->api->exportAccountData();
            $data = $res['data'] ?? null;
            if (!is_array($data)) {
                return response()->json(['success' => false, 'message' => 'Export unavailable.'], 502);
            }

            $stamp = now('Asia/Colombo')->format('Y-m-d');

            if ($format === 'pdf') {
                return PdfExport::download(
                    'exports.my-data',
                    [
                        'd'           => $data,
                        'generatedAt' => now('Asia/Colombo')->format('d M Y, H:i'),
                        'alertLimit'  => self::PDF_ALERT_LIMIT,
                        'fmt'         => fn ($value, bool $time = true) => self::formatDate($value, $time),
                    ],
                    "shalotrack-my-data-{$stamp}.pdf"
                );
            }

            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

            return response($json, 200, [
                'Content-Type'           => 'application/json; charset=utf-8',
                'Content-Disposition'    => "attachment; filename=\"shalotrack-my-data-{$stamp}.json\"",
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

    // -------------------------------------------------------------------------
    // Delete my account
    // -------------------------------------------------------------------------

    /** The page a customer sees while deletion is scheduled: the date, cancel, download, sign out. */
    public function deletionPage(): View|RedirectResponse
    {
        try {
            $status = $this->api->getDeletionStatus()['data'] ?? [];
        } catch (\Exception $e) {
            if ((int) $e->getCode() === 401) {
                Session::flush();
                return redirect('/login?expired=1');
            }
            Log::warning('AccountController: deletion status failed', ['code' => (int) $e->getCode()]);
            $status = [];
        }

        if (empty($status['pending'])) {
            Session::forget('deletion_pending');
            return redirect('/profile');
        }

        return view('account.deletion', [
            'scheduledFor' => self::formatDate($status['scheduledFor'] ?? null, false),
            'daysLeft'     => (int) ($status['daysLeft'] ?? 0),
        ]);
    }

    public function deletionStatus(): JsonResponse
    {
        try {
            $data = $this->api->getDeletionStatus()['data'] ?? [];
            return response()->json(['success' => true, 'pending' => (bool) ($data['pending'] ?? false)]);
        } catch (\Exception $e) {
            return $this->deletionFail($e, 'Status unavailable.');
        }
    }

    /** Schedules deletion. The API checks the confirm word and that the sign-in is recent. */
    public function requestDeletion(Request $request): JsonResponse
    {
        $v = $request->validate(['confirm' => ['required', 'string', 'max:20']]);

        try {
            $this->api->requestAccountDeletion($v['confirm']);
            Session::put('deletion_pending', true);
            return response()->json(['success' => true, 'redirect' => '/account/deletion']);
        } catch (\Exception $e) {
            return $this->deletionFail($e, 'Could not schedule the deletion. Please try again.');
        }
    }

    public function cancelDeletion(): JsonResponse
    {
        try {
            $this->api->cancelAccountDeletion();
            Session::forget('deletion_pending');
            return response()->json(['success' => true, 'redirect' => '/dashboard']);
        } catch (\Exception $e) {
            return $this->deletionFail($e, 'Could not cancel. Please try again.');
        }
    }

    private function deletionFail(\Exception $e, string $message): JsonResponse
    {
        $code = (int) $e->getCode();

        if ($code === 401) {
            Session::flush();
            return response()->json(['success' => false, 'code' => 'TOKEN_EXPIRED', 'message' => 'Session expired.'], 401);
        }

        // Signed in too long ago for something this serious: the page tells the customer to sign in again.
        if ($e->getMessage() === 'REAUTH_REQUIRED') {
            return response()->json([
                'success' => false,
                'code'    => 'REAUTH_REQUIRED',
                'message' => 'For your security, please sign out and sign in again, then confirm within 10 minutes.',
            ], 403);
        }

        Log::warning('AccountController: deletion call failed', ['code' => $code]);

        $status = in_array($code, [400, 429], true) ? $code : 502;
        $text = match ($status) {
            429     => 'Too many attempts. Please wait a while.',
            400     => 'Type DELETE to confirm.',
            default => $message,
        };

        return response()->json(['success' => false, 'message' => $text], $status);
    }

    /**
     * Dates for the PDF in Sri Lanka time. A value carrying a zone (Z or +hh:mm) is converted;
     * a bare value is already Sri Lanka local time (how the database stores it), so it is shown as is.
     */
    private static function formatDate(mixed $value, bool $time = true): string
    {
        if (!is_string($value) || $value === '') {
            return '-';
        }

        try {
            $hasZone = (bool) preg_match('/(Z|[+-]\d{2}:?\d{2})$/', $value);
            $c = $hasZone ? Carbon::parse($value)->setTimezone('Asia/Colombo') : Carbon::parse($value, 'Asia/Colombo');
            return $c->format($time ? 'd M Y, H:i' : 'd M Y');
        } catch (\Throwable) {
            return '-';
        }
    }
}