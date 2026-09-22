<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>ShaloTrack Trip Report</title>
    <style>
        /* ── Base ──────────────────────────────────────────────────────────────── */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: #1a1a2e;
            background: #ffffff;
            padding: 20px 24px;
        }

        /* ── Header ────────────────────────────────────────────────────────────── */
        .header {
            width: 100%;
            background: #021F4A;
            border-radius: 8px;
            padding: 14px 20px;
            margin-bottom: 14px;
            overflow: hidden;
        }

        .header-inner {
            width: 100%;
        }

        .header-left {
            display: inline-block;
            vertical-align: middle;
            width: 60%;
        }

        .header-right {
            display: inline-block;
            vertical-align: middle;
            width: 39%;
            text-align: right;
        }

        .report-title {
            color: #FA6908;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .report-subtitle {
            color: #93a8c9;
            font-size: 9px;
            margin-top: 3px;
        }

        .logo-img {
            height: 36px;
            width: auto;
        }

        .logo-text {
            color: #ffffff;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .logo-sub {
            color: #FA6908;
            font-size: 8px;
            letter-spacing: 2px;
        }

        /* ── Info row ──────────────────────────────────────────────────────────── */
        .info-row {
            width: 100%;
            margin-bottom: 12px;
        }

        .info-card {
            display: inline-block;
            vertical-align: top;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
        }

        .info-card-vehicle {
            width: 46%;
            margin-right: 2%;
        }

        .info-card-date {
            width: 26%;
            margin-right: 2%;
        }

        .info-card-generated {
            width: 22%;
        }

        .card-label {
            color: #64748b;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .card-value-lg {
            color: #021F4A;
            font-size: 13px;
            font-weight: bold;
        }

        .card-value {
            color: #334155;
            font-size: 10px;
            margin-top: 2px;
        }

        .card-sub {
            color: #64748b;
            font-size: 8.5px;
            margin-top: 2px;
        }

        .demo-badge {
            display: inline-block;
            background: #fff7ed;
            border: 1px solid #fdba74;
            color: #c2410c;
            font-size: 7.5px;
            font-weight: bold;
            padding: 1px 6px;
            border-radius: 10px;
            letter-spacing: 0.5px;
            vertical-align: middle;
            margin-left: 4px;
        }

        /* ── KPI strip ─────────────────────────────────────────────────────────── */
        .kpi-strip {
            width: 100%;
            margin-bottom: 14px;
            background: #021F4A;
            border-radius: 6px;
            overflow: hidden;
        }

        .kpi-cell {
            display: inline-block;
            vertical-align: top;
            width: 24%;
            text-align: center;
            padding: 10px 6px;
            border-right: 1px solid #0d3066;
        }

        .kpi-cell:last-child {
            border-right: none;
        }

        .kpi-value {
            color: #FA6908;
            font-size: 18px;
            font-weight: bold;
            line-height: 1;
        }

        .kpi-unit {
            color: #93a8c9;
            font-size: 8px;
            font-weight: normal;
            margin-left: 1px;
        }

        .kpi-label {
            color: #93a8c9;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-top: 4px;
        }

        /* ── Section heading ───────────────────────────────────────────────────── */
        .section-heading {
            color: #021F4A;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 2px solid #FA6908;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }

        /* ── Table ─────────────────────────────────────────────────────────────── */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead tr {
            background: #021F4A;
        }

        thead th {
            color: #93a8c9;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 7px 8px;
            text-align: left;
            white-space: nowrap;
        }

        thead th.right {
            text-align: right;
        }

        tbody tr {
            background: #ffffff;
        }

        tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        tbody td {
            color: #334155;
            font-size: 9px;
            padding: 6px 8px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        tbody td.right {
            text-align: right;
        }

        tbody td.bold {
            font-weight: bold;
            color: #021F4A;
        }

        tbody td.orange {
            color: #FA6908;
            font-weight: bold;
        }

        .badge-inprogress {
            display: inline-block;
            background: #fff7ed;
            border: 1px solid #fdba74;
            color: #c2410c;
            font-size: 7px;
            padding: 1px 4px;
            border-radius: 8px;
            margin-left: 3px;
        }

        /* ── Stops table ───────────────────────────────────────────────────────── */
        .stops-section {
            margin-top: 18px;
        }

        /* ── Footer ────────────────────────────────────────────────────────────── */
        .footer {
            margin-top: 18px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            width: 100%;
        }

        .footer-left {
            display: inline-block;
            vertical-align: middle;
            width: 60%;
            color: #94a3b8;
            font-size: 8px;
        }

        .footer-right {
            display: inline-block;
            vertical-align: middle;
            width: 39%;
            text-align: right;
            color: #94a3b8;
            font-size: 8px;
        }

        .footer-brand {
            color: #FA6908;
            font-weight: bold;
        }
    </style>
</head>

<body>

    {{-- ── Header ──────────────────────────────────────────────────────────────── --}}
    <div class="header">
        <div class="header-inner">
            <div class="header-left">
                <div class="report-title">VEHICLE TRIP REPORT</div>
                <div class="report-subtitle">
                    ShaloTrack Fleet Management · Generated {{ now()->format('d M Y, h:i A') }}
                </div>
            </div>
            <div class="header-right">
                @if($logoBase64)
                <img src="{{ $logoBase64 }}" class="logo-img" alt="ShaloTrack" />
                @else
                <span class="logo-text">ShaloTrack</span>
                <br />
                <span class="logo-sub">FLEET</span>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Vehicle + date info cards ───────────────────────────────────────────── --}}
    <div class="info-row">
        <div class="info-card info-card-vehicle">
            <div class="card-label">Vehicle</div>
            <div class="card-value-lg">
                {{ $vehicle['vehicleNumber'] ?? 'Unknown Vehicle' }}
                @if($vehicle['isDemoVehicle'] ?? false)
                <span class="demo-badge">DEMO</span>
                @endif
            </div>
            <div class="card-value">
                {{ $vehicle['make'] ?? '' }} {{ $vehicle['model'] ?? '' }}
                @if(!empty($vehicle['year'])) · {{ $vehicle['year'] }} @endif
            </div>
            <div class="card-sub">
                {{ $vehicle['color'] ?? '' }}
                @if(!empty($vehicle['vehicleType'])) · {{ $vehicle['vehicleType'] }} @endif
                @if(!empty($vehicle['fuelType'])) · {{ $vehicle['fuelType'] }} @endif
            </div>
        </div>

        <div class="info-card info-card-date">
            <div class="card-label">Report Period</div>
            <div class="card-value">
                From: <strong>{{ \Carbon\Carbon::parse($from)->format('d M Y, H:i') }}</strong>
            </div>
            <div class="card-value" style="margin-top:4px;">
                To: &nbsp;&nbsp;<strong>{{ \Carbon\Carbon::parse($to)->format('d M Y, H:i') }}</strong>
            </div>
        </div>

        <div class="info-card info-card-generated">
            <div class="card-label">Summary</div>
            <div class="card-value">
                <strong>{{ count($trips) }}</strong> trip{{ count($trips) !== 1 ? 's' : '' }}
                · <strong>{{ count($stops) }}</strong> stop{{ count($stops) !== 1 ? 's' : '' }}
            </div>
            <div class="card-sub" style="margin-top:4px;">
                Total distance: <strong>{{ number_format($totalDistanceKm, 1) }} km</strong>
            </div>
            <div class="card-sub" style="margin-top:2px;">
                Total driving: <strong>{{ floor($totalDurationMin / 60) }}h {{ round(fmod($totalDurationMin, 60)) }}m</strong>
            </div>
        </div>
    </div>

    {{-- ── KPI strip ────────────────────────────────────────────────────────────── --}}
    <div class="kpi-strip">
        <div class="kpi-cell">
            <div class="kpi-value">{{ count($trips) }}</div>
            <div class="kpi-label">Trips</div>
        </div>
        <div class="kpi-cell">
            <div class="kpi-value">{{ number_format($totalDistanceKm, 1) }} <span class="kpi-unit">km</span></div>
            <div class="kpi-label">Total Distance</div>
        </div>
        <div class="kpi-cell">
            <div class="kpi-value">{{ round($maxSpeed) }} <span class="kpi-unit">km/h</span></div>
            <div class="kpi-label">Max Speed</div>
        </div>
        <div class="kpi-cell">
            <div class="kpi-value">{{ round($avgSpeed) }} <span class="kpi-unit">km/h</span></div>
            <div class="kpi-label">Avg Speed</div>
        </div>
    </div>

    {{-- ── Trips table ─────────────────────────────────────────────────────────── --}}
    <div class="section-heading">Trip Log</div>

    @if(count($trips) > 0)
    <table>
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:14%">Start Time</th>
                <th style="width:14%">End Time</th>
                <th style="width:9%" class="right">Duration</th>
                <th style="width:9%" class="right">Distance</th>
                <th style="width:9%" class="right">Max Speed</th>
                <th style="width:9%" class="right">Avg Speed</th>
                <th style="width:16%">Start Location</th>
                <th style="width:16%">End Location</th>
            </tr>
        </thead>
        <tbody>
            @foreach($trips as $i => $trip)
            <tr>
                <td class="bold">{{ $i + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($trip['startTime'])->format('d M y, H:i') }}</td>
                <td>
                    {{ \Carbon\Carbon::parse($trip['endTime'])->format('d M y, H:i') }}
                    @if($trip['inProgress'] ?? false)
                    <span class="badge-inprogress">Live</span>
                    @endif
                </td>
                <td class="right">
                    @php
                    $dur = (float) ($trip['durationMinutes'] ?? 0);
                    echo floor($dur / 60) . 'h ' . round(fmod($dur, 60)) . 'm';
                    @endphp
                </td>
                <td class="right bold">{{ number_format((float)($trip['distanceKm'] ?? 0), 1) }} km</td>
                <td class="right orange">{{ round((float)($trip['maxSpeed'] ?? 0)) }} km/h</td>
                <td class="right">{{ round((float)($trip['avgSpeed'] ?? 0)) }} km/h</td>
                <td style="font-size:8px; color:#64748b;">
                    {{ number_format((float)($trip['startLatitude'] ?? 0), 5) }},
                    {{ number_format((float)($trip['startLongitude'] ?? 0), 5) }}
                </td>
                <td style="font-size:8px; color:#64748b;">
                    {{ number_format((float)($trip['endLatitude'] ?? 0), 5) }},
                    {{ number_format((float)($trip['endLongitude'] ?? 0), 5) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="color:#94a3b8; font-style:italic; text-align:center; padding:20px 0;">
        No trips recorded in this period.
    </p>
    @endif

    {{-- ── Stops table (only if there are stops) ───────────────────────────────── --}}
    @if(count($stops) > 0)
    <div class="stops-section">
        <div class="section-heading">Stop Log</div>
        <table>
            <thead>
                <tr>
                    <th style="width:4%">#</th>
                    <th style="width:18%">Start Time</th>
                    <th style="width:18%">End Time</th>
                    <th style="width:10%" class="right">Duration</th>
                    <th>Location (Lat, Lng)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stops as $i => $stop)
                <tr>
                    <td class="bold">{{ $i + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($stop['startTime'] ?? '')->format('d M y, H:i') }}</td>
                    <td>{{ \Carbon\Carbon::parse($stop['endTime']   ?? '')->format('d M y, H:i') }}</td>
                    <td class="right">
                        @php
                        $dur = (float) ($stop['durationMinutes'] ?? 0);
                        echo floor($dur / 60) . 'h ' . round(fmod($dur, 60)) . 'm';
                        @endphp
                    </td>
                    <td style="font-size:8px; color:#64748b;">
                        {{ number_format((float)($stop['latitude'] ?? 0), 5) }},
                        {{ number_format((float)($stop['longitude'] ?? 0), 5) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ── Footer ───────────────────────────────────────────────────────────────── --}}
    <div class="footer">
        <div class="footer-left">
            This report is auto-generated by <span class="footer-brand">ShaloTrack Fleet Management</span>.
            All times are in Sri Lanka Standard Time (IST / UTC+5:30).
            @if($vehicle['isDemoVehicle'] ?? false)
            <br /><em style="color:#c2410c;">This vehicle is a read-only demo vehicle.</em>
            @endif
        </div>
        <div class="footer-right">
            Generated: {{ now()->format('d M Y, H:i:s') }}
            &nbsp;·&nbsp; shalotrack.com
        </div>
    </div>

</body>

</html>