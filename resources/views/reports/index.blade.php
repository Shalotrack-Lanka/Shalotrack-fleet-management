@extends('layouts.app')

@section('title', 'Reports — ShaloTrack Fleet')
@section('page-title', 'Fleet Reports')

@section('content')

<style>
    /* ── Controls bar ── */
    .rpt-controls {
        background: var(--g-s1,#fff);
        border-radius: .875rem;
        border: 1px solid var(--g-b,#e5e7eb);
        box-shadow: 0 1px 4px rgba(2, 31, 74, .06);
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.5rem;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: flex-end;
    }

    .rpt-field {
        display: flex;
        flex-direction: column;
        gap: .3rem;
    }

    .rpt-label {
        font-size: .6875rem;
        font-weight: 700;
        color: var(--g-t2,#6b7280);
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    .rpt-select,
    .rpt-date {
        padding: .5rem .875rem;
        border: 1.5px solid var(--g-b,#e5e7eb);
        border-radius: .625rem;
        font-size: .875rem;
        color: var(--g-t1,#111827);
        background: var(--g-s1,#fff);
        outline: none;
        transition: border-color .15s, box-shadow .15s;
        min-width: 180px;
    }

    .rpt-select:focus,
    .rpt-date:focus {
        border-color: #FA6908;
        box-shadow: 0 0 0 3px rgba(250, 105, 8, .12);
    }

    .rpt-load-btn {
        padding: .5625rem 1.5rem;
        background: #FA6908;
        color: #fff;
        border: none;
        border-radius: .625rem;
        font-size: .875rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s;
        white-space: nowrap;
        align-self: flex-end;
    }

    .rpt-load-btn:hover {
        background: #e55e00;
    }

    .rpt-load-btn:disabled {
        opacity: .6;
        cursor: not-allowed;
    }

    /* ── Error / empty states ── */
    .rpt-error {
        background: var(--g-redbg,#fef2f2);
        border: 1px solid var(--g-redb,#fecaca);
        border-radius: .75rem;
        color: var(--g-redt,#dc2626);
        padding: 1rem 1.25rem;
        font-size: .875rem;
        margin-bottom: 1.5rem;
        display: none;
    }

    .rpt-empty {
        background: var(--g-s1,#fff);
        border-radius: .875rem;
        border: 1px solid var(--g-b,#e5e7eb);
        padding: 5rem 2rem;
        text-align: center;
    }

    .rpt-empty svg {
        width: 64px;
        height: 64px;
        color: var(--g-t3,#e5e7eb);
        margin: 0 auto 1rem;
    }

    .rpt-empty p {
        color: var(--g-t3,#9ca3af);
        font-size: .9375rem;
    }

    .rpt-empty .sub {
        color: var(--g-t3,#d1d5db);
        font-size: .8125rem;
        margin-top: .25rem;
    }

    /* ── Summary tiles ── */
    .rpt-tiles {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .rpt-tile {
        background: var(--g-s1,#fff);
        border-radius: .875rem;
        border: 1px solid var(--g-b,#e5e7eb);
        padding: 1.125rem 1.25rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
    }

    .rpt-tile-icon {
        width: 36px;
        height: 36px;
        border-radius: .5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: .75rem;
    }

    .rpt-tile-icon.orange {
        background: var(--g-orbg,#fff7ed);
    }

    .rpt-tile-icon.navy {
        background: var(--g-blbg,#eef2ff);
    }

    .rpt-tile-icon.green {
        background: var(--g-grbg,#f0fdf4);
    }

    .rpt-tile-icon.red {
        background: var(--g-redbg,#fef2f2);
    }

    .rpt-tile-label {
        font-size: .6875rem;
        font-weight: 700;
        color: var(--g-t3,#9ca3af);
        text-transform: uppercase;
        letter-spacing: .05em;
        margin-bottom: .375rem;
    }

    .rpt-tile-value {
        font-size: 1.625rem;
        font-weight: 800;
        color: var(--g-t1,#021F4A);
        line-height: 1;
    }

    .rpt-tile-unit {
        font-size: .75rem;
        font-weight: 500;
        color: var(--g-t2,#6b7280);
        margin-left: .25rem;
    }

    /* ── Chart card ── */
    .rpt-card {
        background: var(--g-s1,#fff);
        border-radius: .875rem;
        border: 1px solid var(--g-b,#e5e7eb);
        box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.5rem;
    }

    .rpt-card-title {
        font-size: .8125rem;
        font-weight: 700;
        color: var(--g-t1,#021F4A);
        text-transform: uppercase;
        letter-spacing: .05em;
        margin-bottom: 1rem;
    }

    /* ── Bar chart ── */
    #rpt-chart-wrap {
        width: 100%;
        overflow-x: auto;
    }

    #rpt-chart {
        display: block;
        width: 100%;
    }

    /* ── Trips table ── */
    .rpt-table-wrap {
        overflow-x: auto;
    }

    table.rpt-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .8125rem;
    }

    table.rpt-table thead th {
        background: var(--g-s2,#f9fafb);
        color: var(--g-t2,#6b7280);
        font-weight: 700;
        font-size: .6875rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        padding: .75rem 1rem;
        text-align: left;
        border-bottom: 1px solid var(--g-b,#e5e7eb);
        white-space: nowrap;
    }

    table.rpt-table tbody tr {
        border-bottom: 1px solid var(--g-b,#f3f4f6);
        transition: background .1s;
    }

    table.rpt-table tbody tr:hover {
        background: var(--g-s2,#fafafa);
    }

    table.rpt-table tbody td {
        padding: .75rem 1rem;
        color: var(--g-t1,#374151);
        vertical-align: middle;
    }

    .rpt-badge {
        display: inline-flex;
        align-items: center;
        padding: .125rem .5rem;
        border-radius: 999px;
        font-size: .6875rem;
        font-weight: 600;
    }

    .rpt-badge-green {
        background: var(--g-grbg,#f0fdf4);
        color: var(--g-grt,#15803d);
    }

    .rpt-badge-orange {
        background: var(--g-orbg,#fff7ed);
        color: var(--g-amt,#c2410c);
    }

    /* ── Export buttons ── */
    .rpt-export-group {
        display: flex;
        gap: .5rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .rpt-export-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .4375rem 1rem;
        background: var(--g-s1,#fff);
        color: var(--g-t1,#021F4A);
        border: 1.5px solid var(--g-b,#e5e7eb);
        border-radius: .5rem;
        font-size: .8125rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .12s, border-color .12s;
        text-decoration: none;
    }

    .rpt-export-btn:hover {
        background: var(--g-s2,#f9fafb);
        border-color: #FA6908;
        color: #FA6908;
    }

    .rpt-export-btn svg {
        width: 15px;
        height: 15px;
        flex-shrink: 0;
    }

    .rpt-export-btn.pdf {
        border-color: var(--g-redb,#fecaca);
        color: var(--g-redt,#dc2626);
    }

    .rpt-export-btn.pdf:hover {
        background: var(--g-redbg,#fef2f2);
        border-color: #dc2626;
        color: var(--g-redt,#dc2626);
    }

    /* ── Loading skeleton ── */
    .rpt-skeleton {
        border-radius: .5rem;
        background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
        background-size: 200% 100%;
        animation: rpt-shimmer 1.4s infinite;
    }

    @keyframes rpt-shimmer {
        0% {
            background-position: 200% 0;
        }

        100% {
            background-position: -200% 0;
        }
    }

    /* ── Report type tabs + quick ranges ── */
    .rpt-tabs {
        display: flex;
        gap: .25rem;
        background: var(--g-s3,#f3f4f6);
        padding: .25rem;
        border-radius: .75rem;
        margin-bottom: 1rem;
        width: 100%;
        max-width: 30rem;
    }

    .rpt-tab {
        flex: 1 1 0;
        min-width: 0;
        padding: .5rem .5rem;
        font-size: .8125rem;
        font-weight: 600;
        color: var(--g-t2,#6b7280);
        background: transparent;
        border: none;
        border-radius: .5rem;
        cursor: pointer;
        text-align: center;
        transition: background .12s, color .12s;
    }

    .rpt-tab.active {
        background: var(--g-s1,#fff);
        color: #FA6908;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .1);
    }

    .rpt-chips {
        display: flex;
        flex-wrap: wrap;
        gap: .375rem;
        align-self: flex-end;
    }

    .rpt-chip {
        padding: .4375rem .75rem;
        font-size: .75rem;
        font-weight: 600;
        color: var(--g-t1,#374151);
        background: var(--g-s1,#fff);
        border: 1.5px solid var(--g-b,#e5e7eb);
        border-radius: 999px;
        cursor: pointer;
        white-space: nowrap;
    }

    .rpt-chip:hover {
        border-color: #FA6908;
        color: #FA6908;
    }

    .rpt-sub {
        font-size: .75rem;
        color: var(--g-t3,#9ca3af);
        margin: -.25rem 0 1rem;
    }

    .rpt-shared-tag {
        display: inline-block;
        margin-left: .375rem;
        padding: .0625rem .375rem;
        font-size: .625rem;
        font-weight: 700;
        color: var(--g-blt,#0369a1);
        background: var(--g-blbg,#e0f2fe);
        border-radius: 999px;
        vertical-align: middle;
    }

    .rpt-type-chip {
        display: inline-flex;
        align-items: center;
        gap: .375rem;
        padding: .1875rem .625rem;
        border-radius: 999px;
        background: var(--g-s3,#f3f4f6);
        color: var(--g-t1,#374151);
        font-size: .75rem;
        font-weight: 600;
        margin: 0 .375rem .375rem 0;
    }

    .rpt-type-chip b {
        color: #FA6908;
    }

    .rpt-addr {
        font-weight: 600;
        color: var(--g-t1,#021F4A);
        line-height: 1.3;
        margin-bottom: 2px;
    }
    .rpt-addr.pending { color: var(--g-t3,#9ca3af); font-weight: 500; }
    .rpt-addr-btn {
        background: none;
        border: 0;
        padding: 0;
        font: inherit;
        font-weight: 600;
        color: #FA6908;
        cursor: pointer;
    }

    .rpt-map-link {
        color: var(--g-blt,#0369a1);
        text-decoration: none;
        font-size: .75rem;
    }

    .rpt-map-link:hover {
        text-decoration: underline;
    }

    .rpt-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* ── Responsive ── */
    @media (max-width: 767px) {
        .rpt-controls {
            padding: 1rem;
            gap: .75rem;
        }

        .rpt-field {
            width: 100%;
        }

        .rpt-select,
        .rpt-date {
            min-width: 0;
            width: 100%;
            font-size: 16px;
            /* stops iOS zooming into the field */
        }

        .rpt-chips {
            width: 100%;
            align-self: stretch;
        }

        .rpt-load-btn {
            width: 100%;
            text-align: center;
            padding: .75rem 1rem;
        }

        .rpt-tiles {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .625rem;
        }

        .rpt-tile {
            padding: .875rem;
        }

        .rpt-tile-value {
            font-size: 1.25rem;
        }

        .rpt-card {
            padding: 1rem;
        }

        .rpt-empty {
            padding: 3rem 1.25rem;
        }

        /* Tables become stacked cards: no sideways scrolling on a phone */
        table.rpt-table.stack,
        table.rpt-table.stack tbody {
            display: block;
        }

        table.rpt-table.stack thead {
            display: none;
        }

        table.rpt-table.stack tr {
            display: block;
            padding: .625rem 0;
            border-bottom: 1px solid var(--g-b,#f3f4f6);
        }

        table.rpt-table.stack td {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: .1875rem 0;
            text-align: right;
        }

        table.rpt-table.stack td::before {
            content: attr(data-label);
            color: var(--g-t3,#9ca3af);
            font-size: .6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            text-align: left;
            flex-shrink: 0;
        }
    }
</style>

@if($error)
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-3">
    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    {{ $error }}
    <button onclick="window.location.reload()" class="ml-auto text-red-600 underline text-sm">Retry</button>
</div>
@endif

{{-- Report type --}}
<div class="rpt-tabs" role="tablist" aria-label="Report type">
    <button class="rpt-tab active" id="tab-km" role="tab" onclick="setType('km')">Trip &amp; KM</button>
    <button class="rpt-tab" id="tab-stops" role="tab" onclick="setType('stops')">Stops</button>
    <button class="rpt-tab" id="tab-alerts" role="tab" onclick="setType('alerts')">Alerts</button>
</div>

{{-- Controls --}}
<div class="rpt-controls">
    <div class="rpt-field" style="flex:1 1 220px">
        <label class="rpt-label" for="rpt-vehicle">Vehicle</label>
        <select id="rpt-vehicle" class="rpt-select">
            <option value="">— Select vehicle —</option>
            @foreach($vehicles as $v)
            <option value="{{ $v['vehicleId'] }}">{{ $v['vehicleNumber'] }}{{ !empty($v['make']) ? ' · ' . trim($v['make'] . ' ' . ($v['model'] ?? '')) : '' }}{{ !empty($v['isShared']) ? ' (shared)' : '' }}</option>
            @endforeach
        </select>
    </div>
    <div class="rpt-field">
        <label class="rpt-label" for="rpt-from">From</label>
        <input type="date" id="rpt-from" class="rpt-date" />
    </div>
    <div class="rpt-field">
        <label class="rpt-label" for="rpt-to">To</label>
        <input type="date" id="rpt-to" class="rpt-date" />
    </div>
    <div class="rpt-chips" aria-label="Quick ranges">
        <button type="button" class="rpt-chip" onclick="quickRange(0)">Today</button>
        <button type="button" class="rpt-chip" onclick="quickRange(6)">7 days</button>
        <button type="button" class="rpt-chip" onclick="quickRange(29)">30 days</button>
    </div>
    <button class="rpt-load-btn" id="rpt-load-btn" onclick="loadReport()">Load Report</button>
</div>
<p class="rpt-sub">All dates and times are Sri Lanka time. Reports cover up to 90 days at a time.</p>

<div class="rpt-error" id="rpt-error" role="alert"></div>

<div class="rpt-empty" id="rpt-empty">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.3"
            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
    </svg>
    <p id="rpt-empty-title">Select a vehicle and date range to generate a report</p>
    <p class="sub" id="rpt-empty-sub">Trip history, distance, speed, stops and alerts</p>
</div>

<div id="rpt-results" style="display:none">

    {{-- Export bar --}}
    <div style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;margin-bottom:1rem">
        <div style="font-size:.875rem;font-weight:700;color:var(--g-t1,#021F4A)" id="rpt-heading"></div>
        <div class="rpt-export-group">
            <button class="rpt-export-btn" id="btn-csv" onclick="download('csv')" title="Download as a spreadsheet (CSV)">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                CSV
            </button>
            <button class="rpt-export-btn pdf" id="btn-pdf" onclick="download('pdf')" title="Download as a PDF report">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                PDF
            </button>
        </div>
    </div>

    {{-- ── Trip & KM ── --}}
    <div id="view-km" style="display:none">
        <div class="rpt-tiles" id="km-tiles"></div>

        <div class="rpt-card" id="km-chart-card">
            <div class="rpt-card-title">Distance Per Day</div>
            <div id="rpt-chart-wrap"></div>
        </div>

        <div class="rpt-card">
            <div class="rpt-card-title">Daily Breakdown</div>
            <div class="rpt-table-wrap">
                <table class="rpt-table stack">
                    <thead>
                        <tr><th>Date</th><th>Distance</th><th>Trips</th><th>Stops</th><th>Avg speed</th><th>Max speed</th><th>Ignition on</th></tr>
                    </thead>
                    <tbody id="km-daily"></tbody>
                </table>
            </div>
        </div>

        <div class="rpt-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;gap:.5rem;flex-wrap:wrap">
                <div class="rpt-card-title" style="margin:0">Trip Details</div>
                <span style="font-size:.6875rem;font-weight:600;color:var(--g-t3,#9ca3af);letter-spacing:.04em;text-transform:uppercase">↓ Latest First</span>
            </div>
            <div class="rpt-table-wrap">
                <table class="rpt-table stack">
                    <thead>
                        <tr><th>#</th><th>Date</th><th>Start</th><th>End</th><th>Duration</th><th>Distance</th><th>Max speed</th><th>Avg speed</th></tr>
                    </thead>
                    <tbody id="km-trips"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Stops ── --}}
    <div id="view-stops" style="display:none">
        <div class="rpt-tiles" id="stops-tiles"></div>
        <div class="rpt-card">
            <div class="rpt-card-title">Stops · latest first</div>
            <div class="rpt-table-wrap">
                <table class="rpt-table stack">
                    <thead>
                        <tr><th>#</th><th>Date</th><th>Arrived</th><th>Departed</th><th>Duration</th><th>Location</th></tr>
                    </thead>
                    <tbody id="stops-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Alerts ── --}}
    <div id="view-alerts" style="display:none">
        <div class="rpt-tiles" id="alerts-tiles"></div>
        <div class="rpt-card" id="alerts-types-card">
            <div class="rpt-card-title">Alerts by type</div>
            <div id="alerts-types"></div>
        </div>
        <div class="rpt-card">
            <div class="rpt-card-title">All alerts · latest first</div>
            <div class="rpt-table-wrap">
                <table class="rpt-table stack">
                    <thead>
                        <tr><th>#</th><th>Date</th><th>Time</th><th>Type</th><th>Message</th></tr>
                    </thead>
                    <tbody id="alerts-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    'use strict';

    const TZ = 'Asia/Colombo';
    let _type = 'km';
    let _last = null; // exactly what is on screen — exports use the same parameters

    // Sri Lanka "today" as YYYY-MM-DD, whatever the browser's time zone is
    function lkToday(offsetDays = 0) {
        const d = new Date(Date.now() - offsetDays * 86400000);
        return d.toLocaleDateString('en-CA', { timeZone: TZ });
    }

    function quickRange(daysBack) {
        document.getElementById('rpt-to').value = lkToday(0);
        document.getElementById('rpt-from').value = lkToday(daysBack);
    }
    quickRange(6);

    const TYPE_TEXT = {
        km: ['Trip & KM report', 'Distance, speed, trips and daily totals'],
        stops: ['Stop report', 'Every stop of five minutes or more'],
        alerts: ['Alert report', 'Overspeed, geofence, ignition and other alerts'],
    };

    function setType(t) {
        _type = t;
        ['km', 'stops', 'alerts'].forEach(k => {
            document.getElementById('tab-' + k).classList.toggle('active', k === t);
            document.getElementById('tab-' + k).setAttribute('aria-selected', k === t ? 'true' : 'false');
        });
        document.getElementById('rpt-empty-sub').textContent = TYPE_TEXT[t][1];
        // Switching tabs re-runs the report when the inputs are already filled in
        if (document.getElementById('rpt-vehicle').value) loadReport();
        else hideResults();
    }

    function hideResults() {
        document.getElementById('rpt-results').style.display = 'none';
        document.getElementById('rpt-empty').style.display = '';
    }

    async function loadReport() {
        const vehicleId = document.getElementById('rpt-vehicle').value;
        const from = document.getElementById('rpt-from').value;
        const to = document.getElementById('rpt-to').value;
        const btn = document.getElementById('rpt-load-btn');

        document.getElementById('rpt-error').style.display = 'none';

        if (!vehicleId) return showErr('Please select a vehicle.');
        if (!from || !to) return showErr('Please select a date range.');
        if (from > to) return showErr('"From" date cannot be after "To" date.');
        const days = Math.round((new Date(to) - new Date(from)) / 86400000) + 1;
        if (days > 90) return showErr('Reports are limited to 90 days at a time. Please choose a shorter range.');

        const type = _type;
        btn.disabled = true;
        btn.textContent = 'Loading…';
        document.getElementById('rpt-empty').style.display = 'none';
        document.getElementById('rpt-results').style.display = 'none';

        try {
            const qs = new URLSearchParams({ type, vehicleId, from, to });
            const res = await fetch('/reports/view?' + qs, {
                credentials: 'include',
                headers: { 'Accept': 'application/json' },
            });
            if (res.status === 401) { window.location.href = '/login?expired=1'; return; }

            const payload = await res.json().catch(() => ({}));
            if (!res.ok || !payload.success) {
                return showErr(payload.message || (res.status === 429 ? 'Too many requests — please wait a moment.' : 'Failed to load report data.'));
            }
            if (type !== _type) return; // user switched tab while loading

            const sel = document.getElementById('rpt-vehicle');
            _last = { type, vehicleId, from, to };
            document.getElementById('rpt-heading').textContent =
                TYPE_TEXT[type][0] + ' · ' + sel.options[sel.selectedIndex].text + ' · ' + fmtRange(from, to);

            ['km', 'stops', 'alerts'].forEach(k => document.getElementById('view-' + k).style.display = k === type ? '' : 'none');
            const ok = ({ km: renderKm, stops: renderStops, alerts: renderAlerts })[type](payload.data);
            if (ok === false) return;   // empty report — showEmpty() already explained why
            document.getElementById('rpt-results').style.display = '';
        } catch (e) {
            showErr('Network error — please check your connection.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Load Report';
        }
    }

    // ── Renderers ────────────────────────────────────────────────────────────
    function tile(label, value, unit, tone) {
        return `<div class="rpt-tile"><div class="rpt-tile-label">${esc(label)}</div>
            <div class="rpt-tile-value"${tone ? ` style="color:${tone}"` : ''}>${esc(value)}${unit ? `<span class="rpt-tile-unit">${esc(unit)}</span>` : ''}</div></div>`;
    }

    function renderKm(d) {
        const s = d.summary;
        if (!s.trips && !s.distanceKm && !s.stops) {
            return showEmpty('No trips were recorded for this vehicle in the selected period.');
        }
        document.getElementById('km-tiles').innerHTML =
            tile('Total distance', s.distanceKm.toFixed(2), 'km') +
            tile('Trips', s.trips) +
            tile('Stops', s.stops) +
            tile('Driving time', s.drivingLabel) +
            tile('Idle time', s.idleLabel) +
            tile('Ignition on', s.ignitionLabel) +
            tile('Max speed', s.maxSpeed.toFixed(0), 'km/h') +
            tile('Average speed', s.avgSpeed.toFixed(1), 'km/h') +
            tile('Overspeed alerts', s.overspeed, '', s.overspeed > 0 ? '#dc2626' : '');

        const chartCard = document.getElementById('km-chart-card');
        chartCard.style.display = d.daily.length > 1 ? '' : 'none';
        if (d.daily.length > 1) renderBarChart(d.daily.map(r => r.label), d.daily.map(r => r.distanceKm));

        const dash = '<span style="color:var(--g-t3,#9ca3af)">—</span>';
        document.getElementById('km-daily').innerHTML = d.daily.map(r => `<tr>
            <td data-label="Date" style="font-weight:600;color:var(--g-t1,#021F4A)">${esc(r.weekday)}, ${esc(r.label)}</td>
            <td data-label="Distance">${r.distanceKm > 0 ? '<strong>' + r.distanceKm.toFixed(2) + '</strong> km' : dash}</td>
            <td data-label="Trips">${r.trips || dash}</td>
            <td data-label="Stops">${r.stops || dash}</td>
            <td data-label="Avg speed">${r.avgSpeed > 0 ? r.avgSpeed.toFixed(1) + ' km/h' : dash}</td>
            <td data-label="Max speed">${r.maxSpeed > 0 ? r.maxSpeed.toFixed(0) + ' km/h' : dash}</td>
            <td data-label="Ignition on">${r.ignitionMin > 0 ? esc(r.ignitionLabel) : dash}</td></tr>`).join('');

        document.getElementById('km-trips').innerHTML = d.trips.length ? d.trips.map((t, i) => `<tr>
            <td data-label="#" style="color:var(--g-t3,#9ca3af)">${i + 1}</td>
            <td data-label="Date" style="font-weight:600;color:var(--g-t1,#021F4A)">${esc(t.date)}</td>
            <td data-label="Start">${esc(t.start)}</td>
            <td data-label="End">${t.inProgress ? '<span class="rpt-badge rpt-badge-orange">In progress</span>' : (t.end ? esc(t.end) : dash)}</td>
            <td data-label="Duration">${esc(t.duration)}</td>
            <td data-label="Distance"><strong>${t.distanceKm.toFixed(2)}</strong> km</td>
            <td data-label="Max speed">${t.maxSpeed} km/h</td>
            <td data-label="Avg speed">${t.avgSpeed.toFixed(1)} km/h</td></tr>`).join('')
            : `<tr><td colspan="8" style="text-align:center;color:var(--g-t3,#9ca3af);padding:1.5rem">No individual trips in this period.</td></tr>`;
    }

    function mapLink(lat, lng) {
        if (lat === null || lng === null) return '<span style="color:var(--g-t3,#9ca3af)">—</span>';
        return `<a class="rpt-map-link" target="_blank" rel="noopener noreferrer" href="https://www.google.com/maps?q=${encodeURIComponent(lat)},${encodeURIComponent(lng)}">${lat.toFixed(5)}, ${lng.toFixed(5)} ↗</a>`;
    }

    function renderStops(d) {
        if (!d.stops.length) return showEmpty('No stops were recorded for this vehicle in the selected period.');
        const s = d.summary;
        document.getElementById('stops-tiles').innerHTML =
            tile('Total stops', s.count) + tile('Total stopped time', s.totalLabel) +
            tile('Longest stop', s.longestLabel) + tile('Average stop', s.averageLabel);
        document.getElementById('stops-body').innerHTML = d.stops.map((x, i) => `<tr>
            <td data-label="#" style="color:var(--g-t3,#9ca3af)">${i + 1}</td>
            <td data-label="Date" style="font-weight:600;color:var(--g-t1,#021F4A)">${esc(x.date)}</td>
            <td data-label="Arrived">${esc(x.arrived)}</td>
            <td data-label="Departed">${x.inProgress ? '<span class="rpt-badge rpt-badge-orange">Still stopped</span>' : (x.departed ? esc(x.departed) : '—')}</td>
            <td data-label="Duration"><strong>${esc(x.duration)}</strong></td>
            <td data-label="Location">${addrCell(x.lat, x.lng, i)}${mapLink(x.lat, x.lng)}</td></tr>`).join('');
        resolveStopAddresses();
    }

    /* ── Stop addresses (coordinates → place name) ─────────────────────────────
       Same approach as the Trips page: ask /geocode/reverse one place at a time
       (the server caches and paces the free OpenStreetMap lookups). The first
       AUTO_ADDR stops resolve on their own; the rest on tap. Downloads use
       whatever addresses are already cached, so view the report first if you
       want them in the PDF/CSV. */
    const AUTO_ADDR = 60;
    let addrRun = 0;

    function addrCell(lat, lng, i) {
        if (lat === null || lng === null) return '';
        return i < AUTO_ADDR ?
            `<div class="rpt-addr pending" data-lat="${lat}" data-lng="${lng}">Finding address…</div>` :
            `<div class="rpt-addr" data-lat="${lat}" data-lng="${lng}"><button type="button" class="rpt-addr-btn" onclick="resolveOne(this.parentElement)">Show address</button></div>`;
    }

    async function lookupAddress(lat, lng) {
        try {
            const res = await fetch(`/geocode/reverse?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            if (res.status === 401) { window.location.href = '/login?expired=1'; return { address: null, cached: true }; }
            if (res.status === 429) return { address: null, cached: false, backoff: true };
            if (!res.ok) return { address: null, cached: true };
            return await res.json();
        } catch (_) {
            return { address: null, cached: true };
        }
    }

    async function resolveOne(el) {
        el.classList.add('pending');
        el.textContent = 'Finding address…';
        const r = await lookupAddress(el.dataset.lat, el.dataset.lng);
        el.classList.remove('pending');
        el.textContent = r.address || 'Address unavailable';
    }

    async function resolveStopAddresses() {
        const run = ++addrRun;
        for (const el of document.querySelectorAll('#stops-body .rpt-addr.pending')) {
            if (run !== addrRun || !document.body.contains(el)) return; // a newer report replaced this one
            const r = await lookupAddress(el.dataset.lat, el.dataset.lng);
            el.classList.remove('pending');
            el.textContent = r.address || 'Address unavailable';
            if (r.backoff) await new Promise(res => setTimeout(res, 5000));
            else if (!r.cached) await new Promise(res => setTimeout(res, 1100)); // be polite to the provider
        }
    }

    function renderAlerts(d) {
        if (!d.total) return showEmpty('No alerts were triggered for this vehicle in the selected period.');
        const types = Object.entries(d.counts);
        const unread = d.alerts.filter(a => !a.isRead).length;
        document.getElementById('alerts-tiles').innerHTML =
            tile('Total alerts', d.total) + tile('Alert types', types.length) + tile('Unread', unread);
        document.getElementById('alerts-types').innerHTML = types
            .map(([t, n]) => `<span class="rpt-type-chip">${esc(t)} <b>${n}</b></span>`).join('');
        document.getElementById('alerts-body').innerHTML = d.alerts.map((a, i) => `<tr>
            <td data-label="#" style="color:var(--g-t3,#9ca3af)">${i + 1}</td>
            <td data-label="Date" style="font-weight:600;color:var(--g-t1,#021F4A)">${esc(a.date)}</td>
            <td data-label="Time">${esc(a.time)}</td>
            <td data-label="Type"><span class="rpt-badge rpt-badge-orange">${esc(a.type)}</span></td>
            <td data-label="Message" style="text-align:left">${esc(a.message)}</td></tr>`).join('');
    }

    function renderBarChart(labels, values) {
        const wrap = document.getElementById('rpt-chart-wrap');
        const svgW = Math.max(280, wrap.clientWidth || 800);
        const svgH = 200, padL = 44, padR = 10, padT = 14, padB = 36;
        const chartW = svgW - padL - padR, chartH = svgH - padT - padB;
        const n = labels.length;
        const maxVal = Math.max(...values, 0.1);
        const gap = Math.min(8, Math.max(2, chartW / n * 0.2));
        const barW = Math.max(3, (chartW - gap * (n - 1)) / n);
        const every = Math.max(1, Math.ceil(n / Math.max(3, Math.floor(chartW / 54))));

        let m = `<svg xmlns="http://www.w3.org/2000/svg" width="${svgW}" height="${svgH}" viewBox="0 0 ${svgW} ${svgH}" role="img" aria-label="Distance per day">`;
        for (let i = 0; i <= 4; i++) {
            const y = padT + chartH - (i / 4) * chartH;
            m += `<line x1="${padL}" y1="${y}" x2="${padL + chartW}" y2="${y}" stroke="#f3f4f6"/>`;
            m += `<text x="${padL - 6}" y="${y + 4}" text-anchor="end" font-size="10" fill="#9ca3af">${(maxVal * i / 4).toFixed(1)}</text>`;
        }
        for (let i = 0; i < n; i++) {
            const x = padL + i * (barW + gap);
            const bH = values[i] > 0 ? Math.max(2, (values[i] / maxVal) * chartH) : 0;
            if (bH) m += `<rect x="${x.toFixed(1)}" y="${(padT + chartH - bH).toFixed(1)}" width="${barW.toFixed(1)}" height="${bH.toFixed(1)}" rx="3" fill="#FA6908" opacity=".85"><title>${esc(labels[i])}: ${values[i].toFixed(2)} km</title></rect>`;
            if (i % every === 0 || i === n - 1) m += `<text x="${(x + barW / 2).toFixed(1)}" y="${svgH - 8}" text-anchor="middle" font-size="10" fill="#6b7280">${esc(labels[i])}</text>`;
        }
        m += `<line x1="${padL}" y1="${padT}" x2="${padL}" y2="${padT + chartH}" stroke="#e5e7eb"/><line x1="${padL}" y1="${padT + chartH}" x2="${padL + chartW}" y2="${padT + chartH}" stroke="#e5e7eb"/>`;
        m += `<text x="12" y="${padT + chartH / 2}" text-anchor="middle" font-size="10" fill="#9ca3af" transform="rotate(-90,12,${padT + chartH / 2})">km</text></svg>`;
        wrap.innerHTML = m;
    }

    // ── Downloads (server-rendered PDF / CSV of exactly what is on screen) ──
    async function download(format) {
        if (!_last) return;
        const btn = document.getElementById(format === 'pdf' ? 'btn-pdf' : 'btn-csv');
        const old = btn.innerHTML;
        btn.disabled = true;
        btn.textContent = format === 'pdf' ? 'Building PDF…' : 'Preparing…';
        try {
            const qs = new URLSearchParams({ ..._last, format });
            const res = await fetch('/reports/export?' + qs, { credentials: 'include' });
            if (res.status === 401) { window.location.href = '/login?expired=1'; return; }
            if (!res.ok) {
                const j = await res.json().catch(() => ({}));
                throw new Error(j.message || (res.status === 429 ? 'Too many downloads — please wait a minute.' : 'Could not generate the file.'));
            }
            const blob = await res.blob();
            const name = (res.headers.get('Content-Disposition') || '').match(/filename="?([^";]+)"?/)?.[1]
                || `shalotrack-${_last.type}-${_last.from}-to-${_last.to}.${format}`;
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = name;
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => URL.revokeObjectURL(url), 2000);
        } catch (e) {
            showErr(e.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = old;
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────
    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function fmtRange(from, to) {
        const f = d => new Date(d + 'T00:00:00Z').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' });
        return from === to ? f(from) : f(from) + ' – ' + f(to);
    }

    function showErr(msg) {
        const el = document.getElementById('rpt-error');
        el.textContent = msg;
        el.style.display = '';
        if (document.getElementById('rpt-results').style.display === 'none') {
            document.getElementById('rpt-empty').style.display = '';
        }
    }

    function showEmpty(msg) {
        document.getElementById('rpt-results').style.display = 'none';
        document.getElementById('rpt-empty').style.display = '';
        document.getElementById('rpt-empty-title').textContent = msg;
        document.getElementById('rpt-empty-sub').textContent = 'Try a longer date range or a different vehicle.';
        return false;
    }

    ['rpt-from', 'rpt-to', 'rpt-vehicle'].forEach(id => {
        document.getElementById(id)?.addEventListener('keydown', e => { if (e.key === 'Enter') loadReport(); });
    });
</script>

@endsection