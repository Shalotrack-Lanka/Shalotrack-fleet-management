{{--
    "Download my data" PDF. dompdf renders CSS 2.1 only: tables and floats, no flexbox/grid.
    Variables: $d (API export), $generatedAt, $alertLimit, $fmt (date formatter closure).
    Every value goes through {{ }} (escaped); nothing is printed raw.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 30px 32px 46px 32px; }
    body, h2, h3, p, div, table { margin: 0; padding: 0; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #1e293b; line-height: 1.35; }
    .hdr { width: 100%; border-collapse: collapse; border-bottom: 2.5px solid #021F4A; margin-bottom: 12px; }
    .hdr td { padding: 0 0 10px 0; vertical-align: top; }
    .brand { font-size: 20px; font-weight: bold; color: #021F4A; }
    .brand b { color: #FA6908; }
    .brand-sub { font-size: 8px; color: #64748b; margin-top: 1px; letter-spacing: .6px; text-transform: uppercase; }
    .r { text-align: right; }
    .rt { font-size: 13px; font-weight: bold; color: #021F4A; }
    .rd { font-size: 8px; color: #94a3b8; margin-top: 5px; }
    .note { border: 1px solid #fde68a; background: #fffbeb; color: #92400e; padding: 7px 10px; border-radius: 5px; margin: 8px 0; font-size: 8.5px; }
    h2 { font-size: 8.5px; text-transform: uppercase; letter-spacing: .8px; color: #64748b; margin: 16px 0 6px 0; }
    table.kv { width: 100%; border-collapse: collapse; }
    table.kv td { padding: 4px 6px; border-bottom: 1px solid #eef2f7; font-size: 9px; vertical-align: top; }
    table.kv td.k { width: 28%; color: #64748b; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data thead { display: table-header-group; }
    table.data th { background: #021F4A; color: #fff; font-size: 7.5px; text-transform: uppercase; letter-spacing: .4px; padding: 5px 6px; text-align: left; }
    table.data td { padding: 4.5px 6px; border-bottom: 1px solid #eef2f7; font-size: 8.5px; vertical-align: top; }
    table.data tr { page-break-inside: avoid; }
    table.data tr.alt td { background: #f8fafc; }
    .mut { color: #94a3b8; }
    .empty { color: #94a3b8; font-size: 8.5px; padding: 4px 0; }
    .cmp { border: 1px solid #e2e8f0; border-radius: 5px; padding: 7px 9px; margin-bottom: 6px; page-break-inside: avoid; }
    .cmp .t { font-weight: bold; color: #021F4A; font-size: 9px; }
    .rep { margin: 4px 0 0 10px; padding-left: 7px; border-left: 2px solid #e2e8f0; color: #475569; }
    .ftr { margin-top: 16px; padding-top: 6px; border-top: 1px solid #e2e8f0; font-size: 7.5px; color: #94a3b8; }
</style>
</head>
<body>
@php
    $p = $d['profile'] ?? [];
    $vehicles   = $d['vehicles'] ?? [];
    $reminders  = $d['reminders'] ?? [];
    $places     = $d['savedPlaces'] ?? [];
    $geofences  = $d['geofences'] ?? [];
    $contacts   = $d['emergencyContacts'] ?? [];
    $complaints = $d['complaints'] ?? [];
    $renewals   = $d['renewals'] ?? [];
    $given      = $d['vehicleSharesGiven'] ?? [];
    $received   = $d['vehicleSharesReceived'] ?? [];
    $alerts     = $d['recentAlerts'] ?? [];
    $devices    = $d['notifications']['pushDevices'] ?? [];
    $yn = fn ($b) => $b ? 'Yes' : 'No';
@endphp

<table class="hdr">
    <tr>
        <td style="width:45%">
            <div class="brand">Shalo<b>Track</b></div>
            <div class="brand-sub">Fleet Management</div>
        </td>
        <td class="r" style="width:55%">
            <div class="rt">My personal data</div>
            <div class="rd">Generated {{ $generatedAt }} (Sri Lanka time)</div>
        </td>
    </tr>
</table>

<div class="note">This document lists the personal data ShaloTrack holds about you. It does not include push notification tokens or bank-slip images. Your location history is available from Trip History (PDF/CSV). A machine-readable JSON copy is also available from the same page.</div>

<h2>Profile</h2>
<table class="kv">
    <tr><td class="k">Name</td><td>{{ $p['fullName'] ?? '-' }}</td></tr>
    <tr><td class="k">Email</td><td>{{ $p['email'] ?? '-' }}</td></tr>
    <tr><td class="k">Phone</td><td>{{ $p['phoneNumber'] ?? '-' }}</td></tr>
    <tr><td class="k">NIC</td><td>{{ $p['nicNumber'] ?? '-' }}</td></tr>
    <tr><td class="k">Address</td><td>{{ $p['address'] ?? '-' }}</td></tr>
    <tr><td class="k">Account status</td><td>{{ $p['accountStatus'] ?? '-' }}</td></tr>
    <tr><td class="k">Weekly summary push</td><td>{{ $yn($p['weeklySummaryEnabled'] ?? false) }}</td></tr>
    <tr><td class="k">Member since</td><td>{{ $fmt($p['createdAt'] ?? null, false) }}</td></tr>
</table>

<h2>Vehicles ({{ count($vehicles) }})</h2>
@if(count($vehicles))
<table class="data">
    <thead><tr><th>Plate</th><th>Vehicle</th><th>Details</th><th>Speed limit</th><th>Idle alert</th><th>Tracker bound</th></tr></thead>
    <tbody>
    @foreach($vehicles as $i => $v)
        <tr class="{{ $i % 2 ? 'alt' : '' }}">
            <td>{{ $v['vehicleNumber'] ?? '-' }}</td>
            <td>{{ trim(($v['make'] ?? '') . ' ' . ($v['model'] ?? '') . ' ' . ($v['year'] ?? '')) }}</td>
            <td>
                {{ collect([$v['color'] ?? null, $v['vehicleType'] ?? null, $v['fuelType'] ?? null])->filter()->implode(' · ') ?: '-' }}
                @if(!empty($v['chassisNumber']))<br><span class="mut">Chassis {{ $v['chassisNumber'] }}</span>@endif
                @if(!empty($v['engineNumber']))<br><span class="mut">Engine {{ $v['engineNumber'] }}</span>@endif
            </td>
            <td>{{ $v['speedLimitKmh'] ?? '-' }} km/h</td>
            <td>{{ !empty($v['idleAlertMinutes']) ? $v['idleAlertMinutes'] . ' min' : 'Off' }}</td>
            <td>
                @if(!empty($v['deviceImei']))
                    {{ $v['deviceImei'] }}<br><span class="mut">since {{ $fmt($v['deviceBoundAt'] ?? null, false) }}</span>
                @else <span class="mut">None</span> @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
@else <div class="empty">None.</div> @endif

<h2>Reminders ({{ count($reminders) }})</h2>
@if(count($reminders))
<table class="data">
    <thead><tr><th>Vehicle</th><th>Type</th><th>Due</th><th>Notes</th></tr></thead>
    <tbody>
    @foreach($reminders as $i => $r)
        <tr class="{{ $i % 2 ? 'alt' : '' }}">
            <td>{{ $r['vehicleNumber'] ?? '-' }}</td><td>{{ $r['type'] ?? '-' }}</td>
            <td>{{ $fmt($r['dueDate'] ?? null, false) }}</td><td>{{ $r['notes'] ?? '' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@else <div class="empty">None.</div> @endif

<h2>Saved places ({{ count($places) }})</h2>
@if(count($places))
<table class="data">
    <thead><tr><th>Name</th><th>Latitude</th><th>Longitude</th><th>Radius</th><th>Visits</th><th>Last visit</th></tr></thead>
    <tbody>
    @foreach($places as $i => $x)
        <tr class="{{ $i % 2 ? 'alt' : '' }}">
            <td>{{ $x['name'] ?? '-' }}</td><td>{{ $x['latitude'] ?? '-' }}</td><td>{{ $x['longitude'] ?? '-' }}</td>
            <td>{{ $x['radiusMeters'] ?? '-' }} m</td><td>{{ $x['visitCount'] ?? 0 }}</td><td>{{ $fmt($x['lastVisitedAt'] ?? null) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@else <div class="empty">None.</div> @endif

<h2>Geofences ({{ count($geofences) }})</h2>
@if(count($geofences))
<table class="data">
    <thead><tr><th>Name</th><th>Vehicle</th><th>Latitude</th><th>Longitude</th><th>Radius</th><th>Alerts</th><th>Active</th></tr></thead>
    <tbody>
    @foreach($geofences as $i => $g)
        <tr class="{{ $i % 2 ? 'alt' : '' }}">
            <td>{{ $g['name'] ?? '-' }}</td><td>{{ $g['vehicleNumber'] ?? 'All' }}</td>
            <td>{{ $g['latitude'] ?? '-' }}</td><td>{{ $g['longitude'] ?? '-' }}</td><td>{{ $g['radiusMeters'] ?? '-' }} m</td>
            <td>{{ collect([!empty($g['alertOnEnter']) ? 'Enter' : null, !empty($g['alertOnExit']) ? 'Exit' : null])->filter()->implode(' + ') ?: 'None' }}</td>
            <td>{{ $yn($g['isActive'] ?? false) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@else <div class="empty">None.</div> @endif

<h2>Emergency contacts ({{ count($contacts) }})</h2>
@if(count($contacts))
<table class="data">
    <thead><tr><th>Name</th><th>Phone</th><th>Relationship</th></tr></thead>
    <tbody>
    @foreach($contacts as $i => $c)
        <tr class="{{ $i % 2 ? 'alt' : '' }}"><td>{{ $c['name'] ?? '-' }}</td><td>{{ $c['phoneNumber'] ?? '-' }}</td><td>{{ $c['relationship'] ?? '' }}</td></tr>
    @endforeach
    </tbody>
</table>
@else <div class="empty">None.</div> @endif

<h2>Complaints ({{ count($complaints) }})</h2>
@forelse($complaints as $c)
    <div class="cmp">
        <div class="t">{{ $c['vehicleNumber'] ?? '-' }} · {{ $c['category'] ?? '-' }} · {{ $c['status'] ?? '-' }}</div>
        <div class="mut">Opened {{ $fmt($c['createdAt'] ?? null) }}@if(!empty($c['resolvedAt'])) · Resolved {{ $fmt($c['resolvedAt']) }}@endif</div>
        <div style="margin-top:3px">{{ $c['description'] ?? '' }}</div>
        @foreach(($c['replies'] ?? []) as $r)
            <div class="rep"><b>{{ $r['from'] ?? '' }}</b> <span class="mut">{{ $fmt($r['createdAt'] ?? null) }}</span><br>{{ $r['message'] ?? '' }}</div>
        @endforeach
    </div>
@empty
    <div class="empty">None.</div>
@endforelse

<h2>Renewal requests ({{ count($renewals) }})</h2>
@if(count($renewals))
<table class="data">
    <thead><tr><th>Vehicle</th><th>Period</th><th>Payment</th><th>Amount</th><th>Status</th><th>Requested</th><th>Decided</th></tr></thead>
    <tbody>
    @foreach($renewals as $i => $r)
        <tr class="{{ $i % 2 ? 'alt' : '' }}">
            <td>{{ $r['vehicleNumber'] ?? '-' }}</td><td>{{ $r['duration'] ?? '-' }}</td><td>{{ $r['paymentMethod'] ?? '-' }}</td>
            <td>{{ isset($r['amountLkr']) ? 'LKR ' . number_format((float) $r['amountLkr'], 2) : '-' }}</td>
            <td>{{ $r['status'] ?? '-' }}@if(!empty($r['decisionReason']))<br><span class="mut">{{ $r['decisionReason'] }}</span>@endif</td>
            <td>{{ $fmt($r['createdAt'] ?? null, false) }}</td><td>{{ $fmt($r['decidedAt'] ?? null, false) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@else <div class="empty">None.</div> @endif

<h2>Vehicles you shared ({{ count($given) }})</h2>
@if(count($given))
<table class="data">
    <thead><tr><th>Vehicle</th><th>Shared with</th><th>Status</th><th>Invited</th></tr></thead>
    <tbody>
    @foreach($given as $i => $s)
        <tr class="{{ $i % 2 ? 'alt' : '' }}"><td>{{ $s['vehicleNumber'] ?? '-' }}</td><td>{{ $s['otherPartyPhoneMasked'] ?? '' }}</td><td>{{ $s['status'] ?? '-' }}</td><td>{{ $fmt($s['invitedAt'] ?? null, false) }}</td></tr>
    @endforeach
    </tbody>
</table>
@else <div class="empty">None.</div> @endif

<h2>Vehicles shared with you ({{ count($received) }})</h2>
@if(count($received))
<table class="data">
    <thead><tr><th>Vehicle</th><th>Owner</th><th>Status</th><th>Invited</th></tr></thead>
    <tbody>
    @foreach($received as $i => $s)
        <tr class="{{ $i % 2 ? 'alt' : '' }}"><td>{{ $s['vehicleNumber'] ?? '-' }}</td><td>{{ $s['otherPartyPhoneMasked'] ?? '' }}</td><td>{{ $s['status'] ?? '-' }}</td><td>{{ $fmt($s['invitedAt'] ?? null, false) }}</td></tr>
    @endforeach
    </tbody>
</table>
@else <div class="empty">None.</div> @endif

<h2>Notification devices ({{ count($devices) }})</h2>
@if(count($devices))
<table class="data">
    <thead><tr><th>Platform</th><th>Last seen</th></tr></thead>
    <tbody>
    @foreach($devices as $i => $x)
        <tr class="{{ $i % 2 ? 'alt' : '' }}"><td>{{ $x['platform'] ?? '-' }}</td><td>{{ $fmt($x['lastSeenAt'] ?? null) }}</td></tr>
    @endforeach
    </tbody>
</table>
@else <div class="empty">None.</div> @endif

<h2>Recent alerts, last 90 days ({{ count($alerts) }})</h2>
@if(count($alerts))
<table class="data">
    <thead><tr><th>When</th><th>Vehicle</th><th>Type</th><th>Message</th></tr></thead>
    <tbody>
    @foreach(array_slice($alerts, 0, $alertLimit) as $i => $a)
        <tr class="{{ $i % 2 ? 'alt' : '' }}">
            <td>{{ $fmt($a['triggeredAt'] ?? null) }}</td><td>{{ $a['vehicleNumber'] ?? '-' }}</td>
            <td>{{ $a['alertType'] ?? '-' }}</td><td>{{ $a['message'] ?? '' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@if(count($alerts) > $alertLimit)
    <div class="empty">Showing the latest {{ $alertLimit }} of {{ count($alerts) }}. The JSON download has all of them.</div>
@endif
@else <div class="empty">None.</div> @endif

<div class="ftr">ShaloTrack Fleet Management &mdash; Confidential &middot; Personal data export &middot; {{ $generatedAt }}</div>
</body>
</html>