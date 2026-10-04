{{--
    Shared PDF layout for every export (stats, trips/km, stops, alerts).
    dompdf renders CSS 2.1 only: use tables and floats, not flexbox/grid.
    Variables: $reportTitle, $vehicle (array), $rangeLabel, $generatedAt
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 30px 32px 46px 32px; }
    body, h2, h3, p, div, table { margin: 0; padding: 0; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #1e293b; line-height: 1.35; }

    .hdr { width: 100%; border-collapse: collapse; border-bottom: 2.5px solid #021F4A; margin-bottom: 14px; }
    .hdr td { padding: 0 0 10px 0; vertical-align: top; }
    .brand { font-size: 20px; font-weight: bold; color: #021F4A; }
    .brand b { color: #FA6908; }
    .brand-sub { font-size: 8px; color: #64748b; margin-top: 1px; letter-spacing: .6px; text-transform: uppercase; }
    .r { text-align: right; }
    .rt { font-size: 13px; font-weight: bold; color: #021F4A; }
    .rs { font-size: 9px; color: #475569; margin-top: 2px; }
    .rd { font-size: 8px; color: #94a3b8; margin-top: 5px; }
    .pill { background: #ede9fe; color: #6d28d9; font-size: 7.5px; padding: 1px 5px; border-radius: 8px; }
    .pill-shared { background: #e0f2fe; color: #0369a1; font-size: 7.5px; padding: 1px 5px; border-radius: 8px; }

    h2 { font-size: 8.5px; text-transform: uppercase; letter-spacing: .8px; color: #64748b; margin: 14px 0 6px 0; }

    table.tiles { width: 100%; border-collapse: separate; border-spacing: 6px; margin: 0 -6px; }
    table.tiles td.tile { width: 33.33%; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; vertical-align: top; }
    .tl { font-size: 7.5px; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
    .tv { font-size: 17px; font-weight: bold; color: #021F4A; margin-top: 3px; }
    .tu { font-size: 8.5px; font-weight: normal; color: #94a3b8; }
    .alert { color: #dc2626; }

    .note { border: 1px solid #fde68a; background: #fffbeb; color: #92400e; padding: 7px 10px; border-radius: 5px; margin: 8px 0; }

    table.hl { width: 100%; border-collapse: separate; border-spacing: 6px; margin: 0 -6px; }
    table.hl td { width: 33.33%; background: #f8fafc; border-radius: 5px; padding: 6px 9px; }
    .hl-l { font-size: 7.5px; color: #64748b; text-transform: uppercase; }
    .hl-v { font-size: 11px; font-weight: bold; color: #021F4A; margin-top: 2px; }

    .chart { border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px 6px 10px; margin-bottom: 8px; page-break-inside: avoid; }
    .chart h3 { font-size: 8px; color: #475569; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
    .chart img { width: 100%; height: auto; }
    .legend { font-size: 8px; color: #475569; margin-top: 2px; }
    .sw { display: inline-block; width: 8px; height: 8px; margin-right: 3px; }

    table.data { width: 100%; border-collapse: collapse; }
    table.data thead { display: table-header-group; }
    table.data th { background: #021F4A; color: #fff; font-size: 7.5px; text-transform: uppercase; letter-spacing: .4px; padding: 5px 6px; text-align: left; }
    table.data td { padding: 4.5px 6px; border-bottom: 1px solid #eef2f7; font-size: 8.5px; }
    table.data tr { page-break-inside: avoid; }
    table.data tr.alt td { background: #f8fafc; }
    table.data tr.total td { background: #eef2ff; font-weight: bold; color: #021F4A; border-top: 1.5px solid #021F4A; }
    td.n, th.n { text-align: right; }
    .mut { color: #94a3b8; }

    .ftr { margin-top: 16px; padding-top: 6px; border-top: 1px solid #e2e8f0; font-size: 7.5px; color: #94a3b8; }
</style>
</head>
<body>
@php
    $plate = $vehicle['plate'] ?? 'Vehicle';
    $vname = $vehicle['name'] ?? '';
@endphp

<table class="hdr">
    <tr>
        <td style="width:45%">
            <div class="brand">Shalo<b>Track</b></div>
            <div class="brand-sub">Fleet Management</div>
        </td>
        <td class="r" style="width:55%">
            <div class="rt">{{ $reportTitle }}</div>
            <div class="rs">
                {{ $plate }}@if($vname && strcasecmp(trim($vname), trim($plate)) !== 0) · {{ $vname }}@endif
                @if(!empty($vehicle['isDemo'])) <span class="pill">DEMO</span>@endif
                @if(!empty($vehicle['isShared'])) <span class="pill-shared">SHARED{{ !empty($vehicle['owner']) ? ' by ' . $vehicle['owner'] : '' }}</span>@endif
            </div>
            <div class="rs">{{ $rangeLabel }}</div>
            <div class="rd">Generated {{ $generatedAt }} (Sri Lanka time)</div>
        </td>
    </tr>
</table>

@yield('body')

<div class="ftr">ShaloTrack Fleet Management &mdash; Confidential &middot; {{ $plate }} &middot; {{ $reportTitle }}</div>
</body>
</html>