<?php

namespace App\Http\Controllers;

use App\Services\ReportBuilder;
use App\Services\ReverseGeocoder;
use App\Support\Chart;
use App\Support\CsvExport;
use App\Support\LocalTime;
use App\Support\PdfExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * PDF + CSV downloads for the Vehicle Stats page and the Reports page.
 *
 * Both are rendered on the server from fresh API data (never from numbers or
 * images posted by the browser), so what you download always matches the API,
 * is time-zone-correct, and can't be tampered with by editing the page.
 */
class ReportExportController extends Controller
{
    public function __construct(private ReportBuilder $reports, private ReverseGeocoder $geocoder) {}

    // ── GET|POST /stats/{vehicleId}/export?period=today|week|month|all&format=pdf|csv ──

    public function stats(Request $request, string $vehicleId): Response|JsonResponse
    {
        $v = $request->validate([
            'period' => 'required|in:today,week,month,all',
            'format' => 'nullable|in:pdf,csv',
        ]);
        $format = $v['format'] ?? 'pdf';

        try {
            $vehicle = $this->reports->vehicle($vehicleId);
            $stats   = $this->reports->stats($vehicleId, ['period' => $v['period']]);

            return $this->deliverStats($format, $vehicle, $stats, null, 'Vehicle Statistics Report', $v['period']);
        } catch (\Throwable $e) {
            return $this->fail($e, 'StatsExport');
        }
    }

    // ── GET /reports/export?type=km|stops|alerts&format=pdf|csv&vehicleId=&from=&to= ──

    public function report(Request $request): Response|JsonResponse
    {
        $v = $request->validate([
            'type'      => 'required|in:km,stops,alerts',
            'format'    => 'required|in:pdf,csv',
            'vehicleId' => 'required|uuid',
            'from'      => 'required|date_format:Y-m-d',
            'to'        => 'required|date_format:Y-m-d',
        ]);

        try {
            $vehicle = $this->reports->vehicle($v['vehicleId']);
            $range   = $this->rangeLabel($v['from'], $v['to']);

            if ($v['type'] === 'km') {
                $stats = $this->reports->stats($v['vehicleId'], ['from' => $v['from'], 'to' => $v['to']]);
                $trips = $this->reports->trips($v['vehicleId'], $v['from'], $v['to'])['trips'];
                return $this->deliverStats($v['format'], $vehicle, $stats, $trips, 'Trip & KM Report', "km_{$v['from']}_{$v['to']}", $range);
            }

            if ($v['type'] === 'stops') {
                $stops = $this->reports->trips($v['vehicleId'], $v['from'], $v['to'])['stops'];
                return $this->deliverStops($v['format'], $vehicle, $stops, $range, "stops_{$v['from']}_{$v['to']}");
            }

            $report = $this->reports->alerts($v['vehicleId'], $v['from'], $v['to']);
            return $this->deliverAlerts($v['format'], $vehicle, $report, $range, "alerts_{$v['from']}_{$v['to']}");
        } catch (\Throwable $e) {
            return $this->fail($e, 'ReportExport');
        }
    }

    // ── Stats / KM ───────────────────────────────────────────────────────────

    private function deliverStats(string $format, array $vehicle, array $stats, ?array $trips, string $title, string $fileKey, ?string $range = null): Response
    {
        $range ??= $this->periodRangeLabel($stats);
        $name = $this->fileBase($vehicle) . "_{$fileKey}";

        if ($format === 'csv') {
            $s = $stats['summary'];
            $rows = [
                [$title],
                ['Vehicle', $vehicle['plate']],
                ['Name', $vehicle['name']],
                ['Period', $stats['periodLabel']],
                ['From (Sri Lanka time)', $stats['from']?->format('Y-m-d H:i')],
                ['To (Sri Lanka time)', $stats['to']?->format('Y-m-d H:i')],
                ['Generated (Sri Lanka time)', LocalTime::now()->format('Y-m-d H:i')],
                [],
                ['SUMMARY'],
                ['Metric', 'Value', 'Unit'],
                ['Total distance', round($s['distanceKm'], 2), 'km'],
                ['Trips', $s['trips'], 'count'],
                ['Stops', $s['stops'], 'count'],
                ['Driving time', round($s['drivingMin']), 'min'],
                ['Idle time', round($s['idleMin']), 'min'],
                ['Ignition-on time', round($s['ignitionMin']), 'min'],
                ['Max speed', round($s['maxSpeed'], 1), 'km/h'],
                ['Average speed', round($s['avgSpeed'], 1), 'km/h'],
                ['Overspeed alerts', $s['overspeed'], 'count'],
                [],
                ['DAILY BREAKDOWN (Sri Lanka calendar days)'],
                ['Date', 'Day', 'Distance (km)', 'Trips', 'Stops', 'Avg speed (km/h)', 'Max speed (km/h)', 'Ignition on (min)'],
            ];
            foreach ($stats['daily'] as $r) {
                $rows[] = [$r['date'], $r['weekday'], round($r['distanceKm'], 2), $r['trips'], $r['stops'],
                    round($r['avgSpeed'], 1), round($r['maxSpeed'], 1), round($r['ignitionMin'])];
            }
            $rows[] = ['TOTAL', '', round($s['distanceKm'], 2), $s['trips'], $s['stops'],
                round($s['avgSpeed'], 1), round($s['maxSpeed'], 1), round($s['ignitionMin'])];

            if ($trips) {
                $rows[] = [];
                $rows[] = ['TRIP DETAILS (latest first)'];
                $rows[] = ['#', 'Date', 'Start', 'End', 'Duration (min)', 'Distance (km)', 'Max speed (km/h)', 'Avg speed (km/h)', 'Status'];
                foreach ($trips as $i => $t) {
                    $rows[] = [$i + 1, $t['start']->format('Y-m-d'), $t['start']->format('H:i:s'),
                        $t['end']?->format('H:i:s'), round($t['minutes'], 1), round($t['distanceKm'], 3),
                        round($t['maxSpeed'], 1), round($t['avgSpeed'], 1), $t['inProgress'] ? 'In progress' : 'Completed'];
                }
            }
            return CsvExport::download($name . '.csv', $rows);
        }

        // PDF — charts only when there is more than one day to compare
        $charts = null;
        if ($stats['totalDays'] > 1) {
            $labels = array_column($stats['daily'], 'label');
            $charts = [
                'dist' => Chart::bars($labels, [
                    ['name' => 'Distance', 'color' => '#FA6908', 'values' => array_column($stats['daily'], 'distanceKm')],
                ], 520, 170, 'km'),
                'ts' => Chart::bars($labels, [
                    ['name' => 'Trips', 'color' => '#FA6908', 'values' => array_column($stats['daily'], 'trips')],
                    ['name' => 'Stops', 'color' => '#021F4A', 'values' => array_column($stats['daily'], 'stops')],
                ], 520, 150),
                'ign' => Chart::bars($labels, [
                    ['name' => 'Ignition on', 'color' => '#0ea5e9', 'values' => array_column($stats['daily'], 'ignitionMin')],
                ], 520, 150, 'min'),
            ];
        }

        return PdfExport::download('exports.stats', [
            'reportTitle' => $title,
            'vehicle'     => $vehicle,
            'rangeLabel'  => $range,
            'generatedAt' => LocalTime::now()->format('d M Y, h:i A'),
            'stats'       => $stats,
            'trips'       => $trips,
            'charts'      => $charts,
        ], $name . '.pdf');
    }

    // ── Stops ────────────────────────────────────────────────────────────────

    private function deliverStops(string $format, array $vehicle, array $stops, string $range, string $fileKey): Response
    {
        $name = $this->fileBase($vehicle) . "_{$fileKey}";

        // Cached addresses only (no network, so a download is never slowed down by
        // the geocoder). Stops looked up while viewing the report are included.
        foreach ($stops as &$st) {
            $st['address'] = ($st['lat'] !== null && $st['lng'] !== null)
                ? $this->geocoder->peek((float) $st['lat'], (float) $st['lng'])
                : null;
        }
        unset($st);

        if ($format === 'csv') {
            $rows = [
                ['Stop Report'],
                ['Vehicle', $vehicle['plate']],
                ['Name', $vehicle['name']],
                ['Range (Sri Lanka time)', $range],
                ['Generated (Sri Lanka time)', LocalTime::now()->format('Y-m-d H:i')],
                [],
                ['#', 'Date', 'Arrived', 'Departed', 'Duration (min)', 'Address', 'Latitude', 'Longitude', 'Google Maps link', 'Status'],
            ];
            foreach ($stops as $i => $s) {
                $hasPos = $s['lat'] !== null && $s['lng'] !== null;
                $rows[] = [$i + 1, $s['start']->format('Y-m-d'), $s['start']->format('H:i:s'), $s['end']?->format('H:i:s'),
                    round($s['minutes'], 1), $s['address'] ?? '', $hasPos ? (float) $s['lat'] : '', $hasPos ? (float) $s['lng'] : '',
                    $hasPos ? 'https://www.google.com/maps?q=' . (float) $s['lat'] . ',' . (float) $s['lng'] : '',
                    $s['inProgress'] ? 'Still stopped' : 'Completed'];
            }
            return CsvExport::download($name . '.csv', $rows);
        }

        return PdfExport::download('exports.stops', [
            'reportTitle' => 'Stop Report',
            'vehicle'     => $vehicle,
            'rangeLabel'  => $range,
            'generatedAt' => LocalTime::now()->format('d M Y, h:i A'),
            'stops'       => $stops,
        ], $name . '.pdf');
    }

    // ── Alerts ───────────────────────────────────────────────────────────────

    private function deliverAlerts(string $format, array $vehicle, array $report, string $range, string $fileKey): Response
    {
        $name = $this->fileBase($vehicle) . "_{$fileKey}";

        if ($format === 'csv') {
            $rows = [
                ['Alert Report'],
                ['Vehicle', $vehicle['plate']],
                ['Name', $vehicle['name']],
                ['Range (Sri Lanka time)', $range],
                ['Generated (Sri Lanka time)', LocalTime::now()->format('Y-m-d H:i')],
                [],
                ['ALERTS BY TYPE'],
                ['Type', 'Count'],
            ];
            foreach ($report['counts'] as $type => $n) $rows[] = [$type, $n];
            $rows[] = ['TOTAL', $report['total']];
            $rows[] = [];
            $rows[] = ['ALL ALERTS (latest first)'];
            $rows[] = ['#', 'Date', 'Time', 'Type', 'Message', 'Latitude', 'Longitude', 'Read'];
            foreach ($report['alerts'] as $i => $a) {
                $rows[] = [$i + 1, $a['at']->format('Y-m-d'), $a['at']->format('H:i:s'), $a['type'], $a['message'],
                    $a['lat'] !== null ? (float) $a['lat'] : '', $a['lng'] !== null ? (float) $a['lng'] : '', $a['isRead']];
            }
            return CsvExport::download($name . '.csv', $rows);
        }

        return PdfExport::download('exports.alerts', [
            'reportTitle' => 'Alert Report',
            'vehicle'     => $vehicle,
            'rangeLabel'  => $range,
            'generatedAt' => LocalTime::now()->format('d M Y, h:i A'),
            'report'      => $report,
        ], $name . '.pdf');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function rangeLabel(string $from, string $to): string
    {
        $f = \Carbon\Carbon::parse($from)->format('d M Y');
        $t = \Carbon\Carbon::parse($to)->format('d M Y');
        return $f === $t ? $f : "{$f} – {$t}";
    }

    private function periodRangeLabel(array $stats): string
    {
        $f = $stats['from']?->format('d M Y');
        $t = $stats['to']?->format('d M Y');
        $label = $stats['periodLabel'];
        if (!$f || !$t) return $label;
        return $f === $t ? "{$label} · {$f}" : "{$label} · {$f} – {$t}";
    }

    private function fileBase(array $vehicle): string
    {
        $plate = preg_replace('/[^A-Za-z0-9]+/', '-', $vehicle['plate'] ?? 'vehicle');
        return 'shalotrack_' . trim($plate, '-');
    }

    private function fail(\Throwable $e, string $context): JsonResponse
    {
        $code = (int) $e->getCode();

        if ($e instanceof \InvalidArgumentException) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        if ($code === 401) {
            Session::flush();
            return response()->json(['success' => false, 'expired' => true, 'message' => 'Session expired. Please log in again.'], 401);
        }
        Log::error("{$context}: failed", ['error' => $e->getMessage(), 'code' => $code]);

        if ($code === 403 || $code === 404) {
            return response()->json(['success' => false, 'message' => 'That vehicle is not available to your account.'], 404);
        }
        if ($code === 400 || $code === 422) {
            return response()->json(['success' => false, 'message' => $e->getMessage() ?: 'Invalid report request.'], 422);
        }
        return response()->json(['success' => false, 'message' => 'Could not generate the file. Please try again.'], 502);
    }
}