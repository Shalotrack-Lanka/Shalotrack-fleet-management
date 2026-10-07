<?php

namespace App\Http\Controllers;

use App\Services\ShalotrackApiService;
use App\Support\PdfExport;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
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