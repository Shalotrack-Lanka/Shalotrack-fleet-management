<?php

namespace App\Http\Controllers;

use App\Services\ReportBuilder;
use App\Services\ShalotrackApiService;
use App\Support\LocalTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ReportController extends Controller
{
    public function __construct(
        private ShalotrackApiService $api,
        private ReportBuilder $reports,
    ) {}

    /**
     * GET /reports
     * Reports page — the vehicle selector lists owned AND shared vehicles
     * (the API allows trip/stat reports for both).
     */
    public function index()
    {
        try {
            $profile    = $this->api->getMyProfile();
            $customerId = $profile['data']['customerId'] ?? null;
            $vehicles   = $customerId ? $this->api->getTrackableVehicles($customerId) : [];

            return view('reports.index', [
                'vehicles' => $vehicles,
                'error'    => null,
            ]);

        } catch (\Exception $e) {
            Log::error('ReportController: index failed', ['error' => $e->getMessage()]);
            if ($e->getCode() === 401) {
                Session::flush();
                return redirect('/login?expired=1');
            }
            return view('reports.index', [
                'vehicles' => [],
                'error'    => 'Could not load vehicles. Please refresh.',
            ]);
        }
    }

    /**
     * GET /reports/view?type=km|stops|alerts&vehicleId=&from=YYYY-MM-DD&to=YYYY-MM-DD
     * AJAX — returns the report already converted to Sri Lanka time and
     * pre-formatted, so the browser's own time zone never matters.
     */
    public function view(Request $request)
    {
        $v = $request->validate([
            'type'      => 'required|in:km,stops,alerts',
            'vehicleId' => 'required|uuid',
            'from'      => 'required|date_format:Y-m-d',
            'to'        => 'required|date_format:Y-m-d',
        ]);

        try {
            if ($v['type'] === 'km') {
                $stats = $this->reports->stats($v['vehicleId'], ['from' => $v['from'], 'to' => $v['to']]);
                $trips = $this->reports->trips($v['vehicleId'], $v['from'], $v['to'])['trips'];

                return response()->json(['success' => true, 'type' => 'km', 'data' => [
                    'summary' => $stats['summary'] + [
                        'drivingLabel'  => LocalTime::duration($stats['summary']['drivingMin']),
                        'idleLabel'     => LocalTime::duration($stats['summary']['idleMin']),
                        'ignitionLabel' => LocalTime::duration($stats['summary']['ignitionMin']),
                    ],
                    'daily'      => array_map(fn($r) => $r + ['ignitionLabel' => LocalTime::duration($r['ignitionMin'])], $stats['daily']),
                    'activeDays' => $stats['activeDays'],
                    'totalDays'  => $stats['totalDays'],
                    'bestDay'    => $stats['bestDay'],
                    'trips'      => array_map(fn($t) => [
                        'date'       => $t['start']->format('d M Y'),
                        'start'      => $t['start']->format('h:i A'),
                        'end'        => $t['inProgress'] ? null : $t['end']?->format('h:i A'),
                        'duration'   => LocalTime::duration($t['minutes']),
                        'distanceKm' => round($t['distanceKm'], 2),
                        'maxSpeed'   => round($t['maxSpeed']),
                        'avgSpeed'   => round($t['avgSpeed'], 1),
                        'inProgress' => $t['inProgress'],
                    ], $trips),
                ]]);
            }

            if ($v['type'] === 'stops') {
                $stops = $this->reports->trips($v['vehicleId'], $v['from'], $v['to'])['stops'];
                $mins  = array_column($stops, 'minutes');

                return response()->json(['success' => true, 'type' => 'stops', 'data' => [
                    'summary' => [
                        'count'        => count($stops),
                        'totalLabel'   => LocalTime::duration(array_sum($mins)),
                        'longestLabel' => LocalTime::duration($mins ? max($mins) : 0),
                        'averageLabel' => LocalTime::duration($mins ? array_sum($mins) / count($mins) : 0),
                    ],
                    'stops' => array_map(fn($s) => [
                        'date'       => $s['start']->format('d M Y'),
                        'arrived'    => $s['start']->format('h:i A'),
                        'departed'   => $s['inProgress'] ? null : $s['end']?->format('h:i A'),
                        'duration'   => LocalTime::duration($s['minutes']),
                        'lat'        => $s['lat'] !== null ? (float) $s['lat'] : null,
                        'lng'        => $s['lng'] !== null ? (float) $s['lng'] : null,
                        'inProgress' => $s['inProgress'],
                    ], $stops),
                ]]);
            }

            $report = $this->reports->alerts($v['vehicleId'], $v['from'], $v['to']);
            return response()->json(['success' => true, 'type' => 'alerts', 'data' => [
                'total'  => $report['total'],
                'counts' => $report['counts'],
                'alerts' => array_map(fn($a) => [
                    'date'    => $a['at']->format('d M Y'),
                    'time'    => $a['at']->format('h:i A'),
                    'type'    => $a['type'],
                    'message' => $a['message'],
                    'lat'     => $a['lat'] !== null ? (float) $a['lat'] : null,
                    'lng'     => $a['lng'] !== null ? (float) $a['lng'] : null,
                    'isRead'  => $a['isRead'],
                ], $report['alerts']),
            ]]);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            $code = (int) $e->getCode();
            Log::error('ReportController: view failed', [
                'type' => $v['type'], 'vehicleId' => $v['vehicleId'], 'error' => $e->getMessage(), 'code' => $code,
            ]);
            if ($code === 401) {
                Session::flush();
                return response()->json(['success' => false, 'expired' => true, 'message' => 'Session expired.'], 401);
            }
            if ($code === 403 || $code === 404) {
                return response()->json(['success' => false, 'message' => 'That vehicle is not available to your account.'], 404);
            }
            return response()->json(['success' => false, 'message' => 'Failed to load report data. Please try again.'], 422);
        }
    }
}