{{--
    resources/views/stats/pdf.blade.php
    dompdf template — use tables/floats, not flexbox/grid (dompdf CSS2 only)
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: DejaVu Sans, sans-serif; /* dompdf bundled font — supports UTF-8 */
        font-size: 11px;
        color: #1e293b;
        background: #fff;
    }

    /* ── Header ── */
    .header {
        border-bottom: 2.5px solid #021F4A;
        padding-bottom: 12px;
        margin-bottom: 18px;
    }
    .header table { width: 100%; }
    .brand        { font-size: 22px; font-weight: 700; color: #021F4A; }
    .brand-orange { color: #FA6908; }
    .brand-sub    { font-size: 10px; color: #64748b; margin-top: 2px; }
    .report-title { font-size: 13px; font-weight: 700; color: #021F4A; text-align: right; }
    .report-sub   { font-size: 10px; color: #64748b; text-align: right; margin-top: 3px; }
    .report-date  { font-size: 9px;  color: #94a3b8; text-align: right; margin-top: 8px; }

    /* ── Section headings ── */
    .section-label {
        font-size: 9px;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: 8px;
    }

    /* ── Stat tiles — 3 per row via table ── */
    .tiles-table { width: 100%; border-collapse: separate; border-spacing: 8px 8px; margin-top: -8px; }
    .tile {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px;
        width: 33.33%;
        vertical-align: top;
    }
    .tile-label {
        font-size: 9px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 6px;
    }
    .tile-value {
        font-size: 21px;
        font-weight: 700;
        color: #021F4A;
        line-height: 1.1;
    }
    .tile-unit { font-size: 10px; color: #94a3b8; font-weight: 400; }
    .tile-alert { color: #ef4444; }

    /* ── Charts ── */
    .charts-section { margin-top: 18px; }
    .chart-box {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 10px;
    }
    .chart-box img { width: 100%; height: auto; display: block; }

    .chart-half-table { width: 100%; border-collapse: separate; border-spacing: 8px 0; }
    .chart-half { width: 50%; vertical-align: top; }
    .chart-half .chart-box { margin-bottom: 0; }

    /* ── Footer ── */
    .footer {
        margin-top: 20px;
        padding-top: 8px;
        border-top: 1px solid #e2e8f0;
        font-size: 9px;
        color: #94a3b8;
    }
    .footer table { width: 100%; }
</style>
</head>
<body>

{{-- ── Header ──────────────────────────────────────────────────────────────── --}}
@php
    // Passed directly from client state — no server re-fetch needed
    $plate   = $vehiclePlate ?? 'N/A';
    $name    = $vehicleName  ?? '';
    $isDemo  = $vehicleIsDemo ?? false;
    $pLabel  = $periodLabel ?? $period;

    $distance  = number_format($data['totalDistanceKm'] ?? 0, 2);
    $trips     = $data['totalTripCount'] ?? '–';
    $stops     = $data['totalStopCount'] ?? '–';
    $maxSpeed  = number_format($data['maxSpeed'] ?? 0, 1);
    $avgSpeed  = number_format($data['averageSpeed'] ?? 0, 1);
    $overspeed = $data['overspeedIncidentCount'] ?? 0;

    $fmtMin = static function(float $mins): string {
        $m = (int) round($mins);
        if ($m === 0) return '0 min';
        if ($m < 60)  return "{$m} min";
        $h = intdiv($m, 60); $r = $m % 60;
        return $r > 0 ? "{$h}h {$r}m" : "{$h}h";
    };

    $driving  = $fmtMin($data['totalDrivingMinutes'] ?? 0);
    $idle     = $fmtMin($data['totalIdleMinutes'] ?? 0);
    $ignition = $fmtMin($data['totalIgnitionOnMinutes'] ?? 0);
@endphp

<div class="header">
    <table>
        <tr>
            <td style="width:50%;">
                <div class="brand">Shalo<span class="brand-orange">Track</span></div>
                <div class="brand-sub">Fleet Management</div>
            </td>
            <td style="width:50%;">
                <div class="report-title">{{ $plate }}{{ $name ? ' · ' . $name : '' }}{{ $isDemo ? ' · Demo' : '' }}</div>
                <div class="report-sub">{{ $pLabel }} Statistics Report</div>
                <div class="report-date">Generated: {{ $generatedAt }} (Asia/Colombo)</div>
            </td>
        </tr>
    </table>
</div>

{{-- ── Stat tiles ───────────────────────────────────────────────────────────── --}}
<div class="section-label">Summary</div>
<table class="tiles-table">
    <tr>
        <td class="tile">
            <div class="tile-label">Total Distance</div>
            <div class="tile-value">{{ $distance }} <span class="tile-unit">km</span></div>
        </td>
        <td class="tile">
            <div class="tile-label">Trips</div>
            <div class="tile-value">{{ $trips }}</div>
        </td>
        <td class="tile">
            <div class="tile-label">Stops</div>
            <div class="tile-value">{{ $stops }}</div>
        </td>
    </tr>
    <tr>
        <td class="tile">
            <div class="tile-label">Driving Time</div>
            <div class="tile-value">{{ $driving }}</div>
        </td>
        <td class="tile">
            <div class="tile-label">Idle Time</div>
            <div class="tile-value">{{ $idle }}</div>
        </td>
        <td class="tile">
            <div class="tile-label">Ignition On</div>
            <div class="tile-value">{{ $ignition }}</div>
        </td>
    </tr>
    <tr>
        <td class="tile">
            <div class="tile-label">Max Speed</div>
            <div class="tile-value">{{ $maxSpeed }} <span class="tile-unit">km/h</span></div>
        </td>
        <td class="tile">
            <div class="tile-label">Avg Speed</div>
            <div class="tile-value">{{ $avgSpeed }} <span class="tile-unit">km/h</span></div>
        </td>
        <td class="tile">
            <div class="tile-label">Overspeed Alerts</div>
            <div class="tile-value {{ $overspeed > 0 ? 'tile-alert' : '' }}">{{ $overspeed }}</div>
        </td>
    </tr>
</table>

{{-- ── Charts ────────────────────────────────────────────────────────────────── --}}
@if($chartDist || $chartTS || $chartIgn)
<div class="charts-section">
    <div class="section-label" style="margin-top:4px;">Daily Breakdown</div>

    @if($chartDist)
    <div class="chart-box">
        <div class="section-label" style="margin-bottom:6px;">Daily Distance (km)</div>
        <img src="{{ $chartDist }}" alt="Daily Distance Chart">
    </div>
    @endif

    @if($chartTS || $chartIgn)
    <table class="chart-half-table">
        <tr>
            @if($chartTS)
            <td class="chart-half">
                <div class="chart-box">
                    <div class="section-label" style="margin-bottom:6px;">Daily Trips &amp; Stops</div>
                    <img src="{{ $chartTS }}" alt="Trips & Stops Chart">
                </div>
            </td>
            @endif
            @if($chartIgn)
            <td class="chart-half">
                <div class="chart-box">
                    <div class="section-label" style="margin-bottom:6px;">Daily Ignition On Time (min)</div>
                    <img src="{{ $chartIgn }}" alt="Ignition On Chart">
                </div>
            </td>
            @endif
        </tr>
    </table>
    @endif
</div>
@endif

{{-- ── Footer ───────────────────────────────────────────────────────────────── --}}
<div class="footer">
    <table>
        <tr>
            <td>ShaloTrack Fleet Management &mdash; Confidential</td>
            <td style="text-align:right;">{{ $plate }} &middot; {{ $pLabel }}</td>
        </tr>
    </table>
</div>

</body>
</html>