{{-- Stop report PDF. $stops: ReportBuilder::trips()['stops'] --}}
@extends('exports.layout')

@section('body')
@php
    use App\Support\LocalTime;
    $count   = count($stops);
    $total   = array_sum(array_column($stops, 'minutes'));
    $longest = $count ? max(array_column($stops, 'minutes')) : 0;
    $avg     = $count ? $total / $count : 0;
@endphp

<h2>Summary</h2>
<table class="tiles">
    <tr>
        <td class="tile"><div class="tl">Total Stops</div><div class="tv">{{ $count }}</div></td>
        <td class="tile"><div class="tl">Total Stopped Time</div><div class="tv">{{ LocalTime::duration($total) }}</div></td>
        <td class="tile"><div class="tl">Longest Stop</div><div class="tv">{{ LocalTime::duration($longest) }}</div></td>
    </tr>
    <tr>
        <td class="tile"><div class="tl">Average Stop</div><div class="tv">{{ LocalTime::duration($avg) }}</div></td>
        <td class="tile" colspan="2"><div class="tl">Note</div><div style="margin-top:4px;color:#475569">A stop is five or more continuous minutes with the vehicle stationary.</div></td>
    </tr>
</table>

@if($count === 0)
    <div class="note">No stops were recorded for this vehicle in the selected period.</div>
@else
<h2>Stops (latest first)</h2>
<table class="data">
    <thead>
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Arrived</th>
            <th>Departed</th>
            <th class="n">Duration</th>
            <th>Location</th>
        </tr>
    </thead>
    <tbody>
        @foreach($stops as $i => $st)
        <tr class="{{ $i % 2 ? 'alt' : '' }}">
            <td class="mut">{{ $i + 1 }}</td>
            <td>{{ $st['start']->format('d M Y') }}</td>
            <td>{{ $st['start']->format('h:i A') }}</td>
            <td>{{ $st['inProgress'] ? 'Still stopped' : ($st['end'] ? $st['end']->format('h:i A') : '—') }}</td>
            <td class="n">{{ LocalTime::duration($st['minutes']) }}</td>
            <td>
                @if(!empty($st['address']))<strong>{{ $st['address'] }}</strong><br>@endif
                <span class="{{ !empty($st['address']) ? 'mut' : '' }}">{{ ($st['lat'] !== null && $st['lng'] !== null) ? number_format((float) $st['lat'], 5) . ', ' . number_format((float) $st['lng'], 5) : '—' }}</span>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@if(!empty($cut))
<p class="mut" style="margin-top:6px;font-size:9px">Showing the latest {{ $cut['shown'] }} of {{ $cut['total'] }} rows. Download the CSV for every row.</p>
@endif
@endif
@endsection