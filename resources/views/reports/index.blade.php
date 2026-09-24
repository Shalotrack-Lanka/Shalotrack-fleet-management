@extends('layouts.app')

@section('title', 'Reports — ShaloTrack Fleet')
@section('page-title', 'Fleet Reports')

@section('content')

<style>
    /* ── Controls bar ── */
    .rpt-controls {
        background: #fff;
        border-radius: .875rem;
        border: 1px solid #e5e7eb;
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
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    .rpt-select,
    .rpt-date {
        padding: .5rem .875rem;
        border: 1.5px solid #e5e7eb;
        border-radius: .625rem;
        font-size: .875rem;
        color: #111827;
        background: #fff;
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
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: .75rem;
        color: #dc2626;
        padding: 1rem 1.25rem;
        font-size: .875rem;
        margin-bottom: 1.5rem;
        display: none;
    }

    .rpt-empty {
        background: #fff;
        border-radius: .875rem;
        border: 1px solid #e5e7eb;
        padding: 5rem 2rem;
        text-align: center;
    }

    .rpt-empty svg {
        width: 64px;
        height: 64px;
        color: #e5e7eb;
        margin: 0 auto 1rem;
    }

    .rpt-empty p {
        color: #9ca3af;
        font-size: .9375rem;
    }

    .rpt-empty .sub {
        color: #d1d5db;
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
        background: #fff;
        border-radius: .875rem;
        border: 1px solid #e5e7eb;
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
        background: #fff7ed;
    }

    .rpt-tile-icon.navy {
        background: #eef2ff;
    }

    .rpt-tile-icon.green {
        background: #f0fdf4;
    }

    .rpt-tile-icon.red {
        background: #fef2f2;
    }

    .rpt-tile-label {
        font-size: .6875rem;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: .05em;
        margin-bottom: .375rem;
    }

    .rpt-tile-value {
        font-size: 1.625rem;
        font-weight: 800;
        color: #021F4A;
        line-height: 1;
    }

    .rpt-tile-unit {
        font-size: .75rem;
        font-weight: 500;
        color: #6b7280;
        margin-left: .25rem;
    }

    /* ── Chart card ── */
    .rpt-card {
        background: #fff;
        border-radius: .875rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.5rem;
    }

    .rpt-card-title {
        font-size: .8125rem;
        font-weight: 700;
        color: #021F4A;
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
        background: #f9fafb;
        color: #6b7280;
        font-weight: 700;
        font-size: .6875rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        padding: .75rem 1rem;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
    }

    table.rpt-table tbody tr {
        border-bottom: 1px solid #f3f4f6;
        transition: background .1s;
    }

    table.rpt-table tbody tr:hover {
        background: #fafafa;
    }

    table.rpt-table tbody td {
        padding: .75rem 1rem;
        color: #374151;
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
        background: #f0fdf4;
        color: #15803d;
    }

    .rpt-badge-orange {
        background: #fff7ed;
        color: #c2410c;
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
        background: #fff;
        color: #021F4A;
        border: 1.5px solid #e5e7eb;
        border-radius: .5rem;
        font-size: .8125rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .12s, border-color .12s;
        text-decoration: none;
    }

    .rpt-export-btn:hover {
        background: #f9fafb;
        border-color: #FA6908;
        color: #FA6908;
    }

    .rpt-export-btn svg {
        width: 15px;
        height: 15px;
        flex-shrink: 0;
    }

    .rpt-export-btn.pdf {
        border-color: #fecaca;
        color: #dc2626;
    }

    .rpt-export-btn.pdf:hover {
        background: #fef2f2;
        border-color: #dc2626;
        color: #dc2626;
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

    /* ── Responsive ── */
    @media (max-width: 640px) {
        .rpt-controls {
            flex-direction: column;
        }

        .rpt-select,
        .rpt-date {
            min-width: 0;
            width: 100%;
        }

        .rpt-load-btn {
            width: 100%;
            text-align: center;
        }
    }

    /* ── Print / PDF styles ── */
    @media print {

        /* Freeze the page: hide sidebar, header, controls, only show results */
        body * {
            visibility: hidden !important;
        }

        #rpt-print-header,
        #rpt-print-header *,
        #rpt-results,
        #rpt-results * {
            visibility: visible !important;
        }

        #rpt-print-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: .5cm 1cm .25cm;
            background: #fff;
            border-bottom: 2px solid #FA6908;
        }

        #rpt-results {
            position: absolute;
            top: 2.5cm;
            left: 0;
            right: 0;
            padding: 0 1cm;
        }

        /* Hide export buttons when printing */
        .rpt-export-group {
            display: none !important;
        }

        .rpt-card {
            box-shadow: none;
            border: 1px solid #e5e7eb;
            break-inside: avoid;
        }

        .rpt-tile {
            break-inside: avoid;
        }

        @page {
            margin: 1cm;
            size: A4 landscape;
        }
    }
</style>

{{-- Hidden print header (shown only during print) --}}
<div id="rpt-print-header" style="display:none">
    <div style="display:flex;justify-content:space-between;align-items:center">
        <div>
            <div style="font-size:1.125rem;font-weight:800;color:#021F4A">ShaloTrack Fleet Report</div>
            <div id="rpt-print-meta" style="font-size:.75rem;color:#6b7280;margin-top:.2rem"></div>
        </div>
        <div style="color:#FA6908;font-weight:700;font-size:.875rem">SHALOTRACK</div>
    </div>
</div>

@if($error)
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-3">
    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    {{ $error }}
    <button onclick="window.location.reload()" class="ml-auto text-red-600 underline text-sm">Retry</button>
</div>
@endif

{{-- Controls --}}
<div class="rpt-controls">
    <div class="rpt-field">
        <label class="rpt-label">Vehicle</label>
        <select id="rpt-vehicle" class="rpt-select">
            <option value="">— Select vehicle —</option>
            @foreach($vehicles as $v)
            <option value="{{ $v['vehicleId'] }}">{{ $v['vehicleNumber'] }}{{ isset($v['make']) ? ' · ' . $v['make'] . ' ' . ($v['model'] ?? '') : '' }}</option>
            @endforeach
        </select>
    </div>
    <div class="rpt-field">
        <label class="rpt-label">From</label>
        <input type="date" id="rpt-from" class="rpt-date" />
    </div>
    <div class="rpt-field">
        <label class="rpt-label">To</label>
        <input type="date" id="rpt-to" class="rpt-date" />
    </div>
    <button class="rpt-load-btn" id="rpt-load-btn" onclick="loadReport()">
        Load Report
    </button>
</div>

{{-- Error banner --}}
<div class="rpt-error" id="rpt-error"></div>

{{-- Empty / prompt state --}}
<div class="rpt-empty" id="rpt-empty">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.3"
            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
    </svg>
    <p>Select a vehicle and date range to generate a report</p>
    <p class="sub">Trip history, distance, speed, and more</p>
</div>

{{-- Results section (hidden until data loads) --}}
<div id="rpt-results" style="display:none">

    {{-- Summary tiles --}}
    <div class="rpt-tiles" id="rpt-tiles">
        <div class="rpt-tile">
            <div class="rpt-tile-icon orange">
                <svg width="18" height="18" fill="none" stroke="#FA6908" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
            </div>
            <div class="rpt-tile-label">Total Trips</div>
            <div class="rpt-tile-value"><span id="tile-trips">—</span></div>
        </div>
        <div class="rpt-tile">
            <div class="rpt-tile-icon navy">
                <svg width="18" height="18" fill="none" stroke="#021F4A" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
            </div>
            <div class="rpt-tile-label">Total Distance</div>
            <div class="rpt-tile-value"><span id="tile-dist">—</span><span class="rpt-tile-unit">km</span></div>
        </div>
        <div class="rpt-tile">
            <div class="rpt-tile-icon green">
                <svg width="18" height="18" fill="none" stroke="#15803d" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" />
                    <polyline points="12 6 12 12 16 14" />
                </svg>
            </div>
            <div class="rpt-tile-label">Total Drive Time</div>
            <div class="rpt-tile-value"><span id="tile-time">—</span></div>
        </div>
        <div class="rpt-tile">
            <div class="rpt-tile-icon red">
                <svg width="18" height="18" fill="none" stroke="#ef4444" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <div class="rpt-tile-label">Max Speed</div>
            <div class="rpt-tile-value"><span id="tile-maxspeed">—</span><span class="rpt-tile-unit">km/h</span></div>
        </div>
        <div class="rpt-tile">
            <div class="rpt-tile-icon orange">
                <svg width="18" height="18" fill="none" stroke="#FA6908" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div class="rpt-tile-label">Avg Speed</div>
            <div class="rpt-tile-value"><span id="tile-avgspeed">—</span><span class="rpt-tile-unit">km/h</span></div>
        </div>
        <div class="rpt-tile">
            <div class="rpt-tile-icon navy">
                <svg width="18" height="18" fill="none" stroke="#021F4A" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <div class="rpt-tile-label">Stops</div>
            <div class="rpt-tile-value"><span id="tile-stops">—</span></div>
        </div>
    </div>

    {{-- Bar chart: distance per day --}}
    <div class="rpt-card" id="rpt-chart-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;gap:.75rem;flex-wrap:wrap">
            <div class="rpt-card-title" style="margin:0">Distance Per Day</div>
            <div class="rpt-export-group">
                <button class="rpt-export-btn" onclick="exportCsv()" title="Download as CSV spreadsheet">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export CSV
                </button>
                <button class="rpt-export-btn pdf" onclick="exportPdf()" title="Save as PDF via browser print dialog">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Export PDF
                </button>
            </div>
        </div>
        <div id="rpt-chart-wrap">
            <svg id="rpt-chart" height="200"></svg>
        </div>
    </div>

    {{-- Trips table — latest first --}}
    <div class="rpt-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;gap:.5rem;flex-wrap:wrap">
            <div class="rpt-card-title" style="margin:0">Trip Details</div>
            <span style="font-size:.6875rem;font-weight:600;color:#9ca3af;letter-spacing:.04em;text-transform:uppercase">
                ↓ Latest First
            </span>
        </div>
        <div class="rpt-table-wrap">
            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Duration</th>
                        <th>Distance</th>
                        <th>Max Speed</th>
                        <th>Avg Speed</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="rpt-tbody"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
    'use strict';

    // Default date range: last 7 days
    (function() {
        const now = new Date();
        const from = new Date(now);
        from.setDate(now.getDate() - 7);
        document.getElementById('rpt-to').value = now.toISOString().slice(0, 10);
        document.getElementById('rpt-from').value = from.toISOString().slice(0, 10);
    })();

    let _trips = [];
    let _vehicle = null;

    async function loadReport() {
        const vehicleId = document.getElementById('rpt-vehicle').value;
        const fromDate = document.getElementById('rpt-from').value;
        const toDate = document.getElementById('rpt-to').value;
        const errEl = document.getElementById('rpt-error');
        const btn = document.getElementById('rpt-load-btn');

        errEl.style.display = 'none';

        if (!vehicleId) {
            _showErr('Please select a vehicle.');
            return;
        }
        if (!fromDate || !toDate) {
            _showErr('Please select a date range.');
            return;
        }
        if (fromDate > toDate) {
            _showErr('"From" date cannot be after "To" date.');
            return;
        }

        // Build ISO timestamps (start of from-day, end of to-day)
        const fromISO = fromDate + 'T00:00:00Z';
        const toISO = toDate + 'T23:59:59Z';

        btn.disabled = true;
        btn.textContent = 'Loading…';
        document.getElementById('rpt-empty').style.display = 'none';
        document.getElementById('rpt-results').style.display = 'none';

        try {
            const url = `/reports/data?vehicleId=${encodeURIComponent(vehicleId)}&from=${encodeURIComponent(fromISO)}&to=${encodeURIComponent(toISO)}`;
            const res = await fetch(url, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json'
                },
            });

            if (res.status === 401) {
                window.location.href = '/login?expired=1';
                return;
            }

            const payload = await res.json().catch(() => ({}));

            if (!payload.success) {
                _showErr(payload.message ?? 'Failed to load report data.');
                return;
            }

            const data = payload.data ?? {};

            // Filter out in-progress trips, then sort LATEST FIRST
            _trips = (data.trips ?? [])
                .filter(t => !t.inProgress)
                .sort((a, b) => new Date(b.startTime) - new Date(a.startTime));

            _vehicle = vehicleId;
            _renderReport(data);

        } catch (e) {
            _showErr('Network error — please check your connection.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Load Report';
        }
    }

    function _renderReport(data) {
        const trips = _trips;

        if (trips.length === 0) {
            document.getElementById('rpt-empty').style.display = '';
            document.getElementById('rpt-empty').querySelector('p').textContent =
                'No completed trips found for the selected period.';
            document.getElementById('rpt-empty').querySelector('.sub').textContent =
                'Try extending the date range or checking a different vehicle.';
            return;
        }

        // ── Aggregates ──────────────────────────────────────────────────────
        const totalDist = trips.reduce((s, t) => s + (t.distanceKm ?? 0), 0);
        const totalMins = trips.reduce((s, t) => s + (t.durationMinutes ?? 0), 0);
        const maxSpeed = Math.max(...trips.map(t => t.maxSpeed ?? 0));
        const avgSpeed = trips.reduce((s, t) => s + (t.avgSpeed ?? 0), 0) / trips.length;
        const stopCount = data.stopCount ?? 0;

        // ── Tiles ────────────────────────────────────────────────────────────
        document.getElementById('tile-trips').textContent = trips.length;
        document.getElementById('tile-dist').textContent = totalDist.toFixed(1);
        document.getElementById('tile-time').textContent = _fmtDuration(totalMins);
        document.getElementById('tile-maxspeed').textContent = maxSpeed.toFixed(0);
        document.getElementById('tile-avgspeed').textContent = avgSpeed.toFixed(1);
        document.getElementById('tile-stops').textContent = stopCount;

        // ── Chart: distance per day (always chronological on chart) ─────────
        const byDay = {};
        trips.forEach(t => {
            const day = (t.startTime ?? '').slice(0, 10);
            if (day) byDay[day] = (byDay[day] ?? 0) + (t.distanceKm ?? 0);
        });
        const days = Object.keys(byDay).sort(); // chart always oldest→newest
        const values = days.map(d => byDay[d]);
        _renderBarChart(days, values);

        // ── Table: latest first (trips already sorted) ───────────────────────
        const tbody = document.getElementById('rpt-tbody');
        tbody.innerHTML = '';
        trips.forEach((t, i) => {
            const start = new Date(t.startTime ?? '');
            const end = new Date(t.endTime ?? '');
            const row = document.createElement('tr');
            if (i % 2 === 1) row.style.background = '#fafafa';
            row.innerHTML = `
                <td style="color:#9ca3af;font-size:.75rem">${i + 1}</td>
                <td style="font-weight:600;color:#021F4A">${_fmtDate(start)}</td>
                <td>${_fmtTime(start)}</td>
                <td>${t.endTime ? _fmtTime(end) : '<span style="color:#9ca3af">—</span>'}</td>
                <td>${_fmtDuration(t.durationMinutes ?? 0)}</td>
                <td><strong>${(t.distanceKm ?? 0).toFixed(2)}</strong> km</td>
                <td>${(t.maxSpeed ?? 0).toFixed(0)} km/h</td>
                <td>${(t.avgSpeed ?? 0).toFixed(1)} km/h</td>
                <td><span class="rpt-badge ${t.inProgress ? 'rpt-badge-orange' : 'rpt-badge-green'}">${t.inProgress ? 'In Progress' : 'Completed'}</span></td>
            `;
            tbody.appendChild(row);
        });

        document.getElementById('rpt-results').style.display = '';
    }

    function _renderBarChart(days, values) {
        const wrap = document.getElementById('rpt-chart-wrap');
        const svgW = wrap.clientWidth || 800;
        const svgH = 200;
        const padL = 48;
        const padR = 12;
        const padT = 16;
        const padB = 40;
        const chartW = svgW - padL - padR;
        const chartH = svgH - padT - padB;
        const n = days.length;
        const maxVal = Math.max(...values, 0.1);
        const barGap = Math.min(8, Math.max(2, chartW / n * 0.2));
        const barW = Math.max(4, (chartW - barGap * (n - 1)) / n);

        let markup = `<svg xmlns="http://www.w3.org/2000/svg" width="${svgW}" height="${svgH}" viewBox="0 0 ${svgW} ${svgH}">`;

        // Y-axis gridlines + labels
        const yTicks = 4;
        for (let i = 0; i <= yTicks; i++) {
            const y = padT + chartH - (i / yTicks) * chartH;
            const val = (maxVal * i / yTicks).toFixed(1);
            markup += `<line x1="${padL}" y1="${y}" x2="${padL + chartW}" y2="${y}" stroke="#f3f4f6" stroke-width="1"/>`;
            markup += `<text x="${padL - 6}" y="${y + 4}" text-anchor="end" font-size="10" fill="#9ca3af">${val}</text>`;
        }

        // Bars
        for (let i = 0; i < n; i++) {
            const x = padL + i * (barW + barGap);
            const bH = Math.max(2, (values[i] / maxVal) * chartH);
            const y = padT + chartH - bH;
            markup += `<rect x="${x.toFixed(1)}" y="${y.toFixed(1)}" width="${barW.toFixed(1)}" height="${bH.toFixed(1)}" rx="3" fill="#FA6908" opacity="0.85"/>`;

            if (n <= 14 || i === 0 || i === n - 1 || i % Math.ceil(n / 7) === 0) {
                const label = days[i].slice(5); // MM-DD
                markup += `<text x="${(x + barW / 2).toFixed(1)}" y="${svgH - 6}" text-anchor="middle" font-size="10" fill="#6b7280">${label}</text>`;
            }
        }

        // Axis lines
        markup += `<line x1="${padL}" y1="${padT}" x2="${padL}" y2="${padT + chartH}" stroke="#e5e7eb" stroke-width="1"/>`;
        markup += `<line x1="${padL}" y1="${padT + chartH}" x2="${padL + chartW}" y2="${padT + chartH}" stroke="#e5e7eb" stroke-width="1"/>`;
        markup += `<text x="12" y="${padT + chartH / 2}" text-anchor="middle" font-size="10" fill="#9ca3af" transform="rotate(-90,12,${padT + chartH / 2})">km</text>`;

        markup += '</svg>';
        wrap.innerHTML = markup;
    }

    function exportCsv() {
        if (!_trips.length) return;
        const header = ['#', 'Date', 'Start', 'End', 'Duration (min)', 'Distance (km)', 'Max Speed (km/h)', 'Avg Speed (km/h)', 'Status'];
        const rows = _trips.map((t, i) => {
            const start = new Date(t.startTime ?? '');
            const end = new Date(t.endTime ?? '');
            return [
                i + 1,
                _fmtDate(start),
                t.startTime ? start.toISOString() : '',
                t.endTime ? end.toISOString() : '',
                (t.durationMinutes ?? 0).toFixed(1),
                (t.distanceKm ?? 0).toFixed(3),
                (t.maxSpeed ?? 0).toFixed(1),
                (t.avgSpeed ?? 0).toFixed(1),
                t.inProgress ? 'In Progress' : 'Completed',
            ].map(v => `"${String(v).replace(/"/g, '""')}"`).join(',');
        });
        const csv = [header.join(','), ...rows].join('\n');
        const blob = new Blob([csv], {
            type: 'text/csv;charset=utf-8;'
        });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        const from = document.getElementById('rpt-from').value;
        const to = document.getElementById('rpt-to').value;
        a.href = url;
        a.download = `shalotrack-report-${from}-to-${to}.csv`;
        a.click();
        URL.revokeObjectURL(url);
    }

    function exportPdf() {
        if (!_trips.length) return;

        // Populate print header with context
        const vehicleSel = document.getElementById('rpt-vehicle');
        const vehicleLabel = vehicleSel.options[vehicleSel.selectedIndex]?.text ?? '';
        const from = document.getElementById('rpt-from').value;
        const to = document.getElementById('rpt-to').value;
        const generated = new Date().toLocaleString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        document.getElementById('rpt-print-meta').textContent =
            `Vehicle: ${vehicleLabel}  ·  Period: ${from} to ${to}  ·  Generated: ${generated}`;
        document.getElementById('rpt-print-header').style.display = '';

        window.print();

        // Restore after dialog closes
        setTimeout(() => {
            document.getElementById('rpt-print-header').style.display = 'none';
        }, 1000);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────
    function _fmtDate(d) {
        if (isNaN(d)) return '—';
        return d.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    }

    function _fmtTime(d) {
        if (isNaN(d)) return '—';
        return d.toLocaleTimeString('en-GB', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function _fmtDuration(mins) {
        if (!mins && mins !== 0) return '—';
        const h = Math.floor(mins / 60);
        const m = Math.round(mins % 60);
        return h > 0 ? `${h}h ${m}m` : `${m}m`;
    }

    function _showErr(msg) {
        const el = document.getElementById('rpt-error');
        el.textContent = msg;
        el.style.display = '';
        document.getElementById('rpt-empty').style.display = '';
    }

    // Allow Enter key on controls
    ['rpt-from', 'rpt-to', 'rpt-vehicle'].forEach(id => {
        document.getElementById(id)?.addEventListener('keydown', e => {
            if (e.key === 'Enter') loadReport();
        });
    });
</script>

@endsection