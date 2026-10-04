{{--
    Statistics / KM report PDF.
    $stats  — from ReportBuilder::stats()
    $trips  — optional list from ReportBuilder::trips()['trips'] (adds a trip table)
    $charts — ['dist'=>dataUri,'ts'=>dataUri,'ign'=>dataUri] or null for single-day periods
--}}
@extends('exports.layout')

@section('body')
@php
    use App\Support\LocalTime;
    $s = $stats['summary'];
    $fmtKm = fn($v) => number_format($v, 2);
    $hasActivity = $s['trips'] > 0 || $s['distanceKm'] > 0 || $s['stops'] > 0;
@endphp

<h2>Summary</h2>
<table class="tiles">
    <tr>
        <td class="tile"><div class="tl">Total Distance</div><div class="tv">{{ $fmtKm($s['distanceKm']) }} <span class="tu">km</span></div></td>
        <td class="tile"><div class="tl">Trips</div><div class="tv">{{ $s['trips'] }}</div></td>
        <td class="tile"><div class="tl">Stops</div><div class="tv">{{ $s['stops'] }}</div></td>
    </tr>
    <tr>
        <td class="tile"><div class="tl">Driving Time</div><div class="tv">{{ LocalTime::duration($s['drivingMin']) }}</div></td>
        <td class="tile"><div class="tl">Idle Time</div><div class="tv">{{ LocalTime::duration($s['idleMin']) }}</div></td>
        <td class="tile"><div class="tl">Ignition On</div><div class="tv">{{ LocalTime::duration($s['ignitionMin']) }}</div></td>
    </tr>
    <tr>
        <td class="tile"><div class="tl">Max Speed</div><div class="tv">{{ number_format($s['maxSpeed'], 1) }} <span class="tu">km/h</span></div></td>
        <td class="tile"><div class="tl">Average Speed</div><div class="tv">{{ number_format($s['avgSpeed'], 1) }} <span class="tu">km/h</span></div></td>
        <td class="tile"><div class="tl">Overspeed Alerts</div><div class="tv {{ $s['overspeed'] > 0 ? 'alert' : '' }}">{{ $s['overspeed'] }}</div></td>
    </tr>
</table>

@if(!$hasActivity)
    <div class="note">No trips or stops were recorded for this vehicle in the selected period, so the figures above are zero.</div>
@else
    @if($stats['totalDays'] > 1)
    <table class="hl">
        <tr>
            <td><div class="hl-l">Active days</div><div class="hl-v">{{ $stats['activeDays'] }} of {{ $stats['totalDays'] }}</div></td>
            <td><div class="hl-l">Average per active day</div><div class="hl-v">{{ $fmtKm($stats['avgPerActiveDay']) }} km</div></td>
            <td><div class="hl-l">Longest day</div><div class="hl-v">@if($stats['bestDay']){{ $stats['bestDay']['label'] }} · {{ $fmtKm($stats['bestDay']['distanceKm']) }} km @else — @endif</div></td>
        </tr>
    </table>
    @endif
@endif

@if(!empty($charts))
<h2>Daily Charts</h2>
<div class="chart">
    <h3>Distance per day (km)</h3>
    <img src="{{ $charts['dist'] }}" alt="Distance per day">
</div>
<div class="chart">
    <h3>Trips &amp; stops per day</h3>
    <img src="{{ $charts['ts'] }}" alt="Trips and stops per day">
    <div class="legend"><span class="sw" style="background:#FA6908"></span>Trips &nbsp; <span class="sw" style="background:#021F4A"></span>Stops</div>
</div>
<div class="chart">
    <h3>Ignition-on time per day (minutes)</h3>
    <img src="{{ $charts['ign'] }}" alt="Ignition on time per day">
</div>
@endif

@if(count($stats['daily']))
<h2>Daily Breakdown</h2>
<table class="data">
    <thead>
        <tr>
            <th>Date</th>
            <th class="n">Distance (km)</th>
            <th class="n">Trips</th>
            <th class="n">Stops</th>
            <th class="n">Avg speed</th>
            <th class="n">Max speed</th>
            <th class="n">Ignition on</th>
        </tr>
    </thead>
    <tbody>
        @foreach($stats['daily'] as $i => $r)
        <tr class="{{ $i % 2 ? 'alt' : '' }}">
            <td>{{ $r['weekday'] }}, {{ $r['label'] }}</td>
            <td class="n">{{ $r['distanceKm'] > 0 ? $fmtKm($r['distanceKm']) : '—' }}</td>
            <td class="n">{{ $r['trips'] ?: '—' }}</td>
            <td class="n">{{ $r['stops'] ?: '—' }}</td>
            <td class="n">{{ $r['avgSpeed'] > 0 ? number_format($r['avgSpeed'], 1) : '—' }}</td>
            <td class="n">{{ $r['maxSpeed'] > 0 ? number_format($r['maxSpeed'], 0) : '—' }}</td>
            <td class="n">{{ $r['ignitionMin'] > 0 ? LocalTime::duration($r['ignitionMin']) : '—' }}</td>
        </tr>
        @endforeach
        <tr class="total">
            <td>Total</td>
            <td class="n">{{ $fmtKm($s['distanceKm']) }}</td>
            <td class="n">{{ $s['trips'] }}</td>
            <td class="n">{{ $s['stops'] }}</td>
            <td class="n">{{ number_format($s['avgSpeed'], 1) }}</td>
            <td class="n">{{ number_format($s['maxSpeed'], 0) }}</td>
            <td class="n">{{ LocalTime::duration($s['ignitionMin']) }}</td>
        </tr>
    </tbody>
</table>
@endif

@if(!empty($trips))
<h2>Trip Details (latest first)</h2>
<table class="data">
    <thead>
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Start</th>
            <th>End</th>
            <th class="n">Duration</th>
            <th class="n">Distance</th>
            <th class="n">Max</th>
            <th class="n">Avg</th>
        </tr>
    </thead>
    <tbody>
        @foreach($trips as $i => $t)
        <tr class="{{ $i % 2 ? 'alt' : '' }}">
            <td class="mut">{{ $i + 1 }}</td>
            <td>{{ $t['start']->format('d M Y') }}</td>
            <td>{{ $t['start']->format('h:i A') }}</td>
            <td>{{ $t['inProgress'] ? 'In progress' : ($t['end'] ? $t['end']->format('h:i A') : '—') }}</td>
            <td class="n">{{ LocalTime::duration($t['minutes']) }}</td>
            <td class="n">{{ number_format($t['distanceKm'], 2) }} km</td>
            <td class="n">{{ number_format($t['maxSpeed'], 0) }}</td>
            <td class="n">{{ number_format($t['avgSpeed'], 1) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@if(!empty($tripsCut))
<p class="mut" style="margin-top:6px;font-size:9px">Showing the latest {{ $tripsCut['shown'] }} of {{ $tripsCut['total'] }} rows. Download the CSV for every row.</p>
@endif
@endif
@endsection