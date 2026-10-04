{{-- Alert report PDF. $report: ReportBuilder::alerts() --}}
@extends('exports.layout')

@section('body')
@php
    $max = $report['counts'] ? max($report['counts']) : 0;
@endphp

<h2>Summary</h2>
<table class="tiles">
    <tr>
        <td class="tile"><div class="tl">Total Alerts</div><div class="tv">{{ $report['total'] }}</div></td>
        <td class="tile"><div class="tl">Alert Types</div><div class="tv">{{ count($report['counts']) }}</div></td>
        <td class="tile"><div class="tl">Unread</div><div class="tv">{{ count(array_filter($report['alerts'], fn($a) => !$a['isRead'])) }}</div></td>
    </tr>
</table>

@if($report['total'] === 0)
    <div class="note">No alerts were triggered for this vehicle in the selected period.</div>
@else
<h2>Alerts by type</h2>
<table class="data">
    <thead><tr><th>Type</th><th class="n" style="width:50px">Count</th><th style="width:55%">Share</th></tr></thead>
    <tbody>
        @foreach($report['counts'] as $type => $n)
        <tr class="{{ $loop->odd ? '' : 'alt' }}">
            <td>{{ $type }}</td>
            <td class="n">{{ $n }}</td>
            <td><div style="background:#FA6908;height:7px;width:{{ $max ? max(2, round($n / $max * 100)) : 0 }}%"></div></td>
        </tr>
        @endforeach
    </tbody>
</table>

<h2>All alerts (latest first)</h2>
<table class="data">
    <thead>
        <tr><th>#</th><th>Date</th><th>Time</th><th>Type</th><th>Message</th></tr>
    </thead>
    <tbody>
        @foreach($report['alerts'] as $i => $a)
        <tr class="{{ $i % 2 ? 'alt' : '' }}">
            <td class="mut">{{ $i + 1 }}</td>
            <td>{{ $a['at']->format('d M Y') }}</td>
            <td>{{ $a['at']->format('h:i A') }}</td>
            <td>{{ $a['type'] }}</td>
            <td>{{ $a['message'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@if(!empty($cut))
<p class="mut" style="margin-top:6px;font-size:9px">Showing the latest {{ $cut['shown'] }} of {{ $cut['total'] }} rows. Download the CSV for every row.</p>
@endif
@endif
@endsection