@extends('layouts.app')

@section('title', 'Vehicle Statistics')

@push('styles')

@endpush

@section('content')
<div class="stats-wrap">

    {{-- ── Sidebar ──────────────────────────────────────────────────────────── --}}
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Vehicle Statistics</h2>
            <input
                type="text"
                id="vehicle-search"
                class="sidebar-search"
                placeholder="Search vehicles…"
                oninput="filterVehicles(this.value)"
            >
        </div>

        <div class="vehicle-list" id="vehicle-list">
            @forelse($vehicles as $v)
                @php
                    $plate = $v['vehicleNumber'] ?? 'N/A';
                    $name  = trim(($v['make'] ?? '') . ' ' . ($v['model'] ?? ''));
                    $demo  = $v['isDemoVehicle'] ?? false;
                    $id    = $v['vehicleId'] ?? $v['id'] ?? '';
                @endphp
                <div class="vehicle-card {{ $demo ? 'demo-card' : '' }}"
                     data-id="{{ $id }}"
                     data-plate="{{ strtolower($plate) }}"
                     data-name="{{ strtolower($name) }}"
                     onclick="selectVehicle('{{ $id }}', '{{ addslashes($plate) }}', '{{ addslashes($name) }}', {{ $demo ? 'true' : 'false' }})">
                    <div class="vehicle-icon {{ $demo ? 'demo' : '' }}">
                        {{-- Car icon --}}
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                        </svg>
                    </div>
                    <div class="vehicle-info">
                        <div class="vehicle-plate">{{ $plate }}</div>
                        @if($name)
                            <div class="vehicle-name">{{ $name }}{{ $demo ? ' (Demo)' : '' }}</div>
                        @elseif($demo)
                            <div class="vehicle-name">Demo Vehicle</div>
                        @endif
                    </div>
                </div>
            @empty
                <div style="padding:24px 16px; text-align:center; color:#94a3b8; font-size:13px;">
                    No vehicles with GPS found.<br>Please assign a GPS device to a vehicle first.
                </div>
            @endforelse
        </div>
    </div>

    {{-- ── Main panel ──────────────────────────────────────────────────────── --}}
    <div class="stats-main">

        {{-- Period selector bar (always visible) --}}
        <div class="period-bar" id="period-bar" style="display:none;">
            <button class="period-btn active" data-period="today"  onclick="setPeriod('today')">Today</button>
            <button class="period-btn"        data-period="week"   onclick="setPeriod('week')">This Week</button>
            <button class="period-btn"        data-period="month"  onclick="setPeriod('month')">This Month</button>
            <button class="period-btn"        data-period="all"    onclick="setPeriod('all')">All Time</button>
        </div>

        {{-- Content area --}}
        <div class="stats-content" id="stats-content">

            {{-- Empty: no vehicle selected --}}
            <div class="stats-empty" id="state-empty">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                </svg>
                <p>Select a vehicle to view its statistics</p>
            </div>

            {{-- Loading skeleton --}}
            <div id="state-loading" style="display:none;">
                <div class="skeleton-wrap">
                    <div class="skeleton-box" style="height:48px;width:260px;margin-bottom:24px;"></div>
                    <div class="tiles-grid" style="margin-bottom:28px;">
                        @for($i = 0; $i < 9; $i++)
                            <div class="skeleton-box" style="height:90px;"></div>
                        @endfor
                    </div>
                    <div class="chart-grid">
                        <div class="skeleton-box" style="height:220px;"></div>
                        <div class="skeleton-box" style="height:220px;"></div>
                        <div class="skeleton-box" style="height:220px;grid-column:1/-1;"></div>
                    </div>
                </div>
            </div>

            {{-- Error state --}}
            <div class="stats-empty" id="state-error" style="display:none;">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                <p id="error-msg">Could not load statistics. Please try again.</p>
            </div>

            {{-- Stats panel (populated by JS) --}}
            <div id="state-stats" style="display:none;">

                {{-- Vehicle header --}}
                <div class="stats-vehicle-header">
                    <div class="icon-big" id="vh-icon">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="plate" id="vh-plate">–</div>
                        <div class="meta" id="vh-meta">–</div>
                    </div>
                </div>

                {{-- 9 stat tiles --}}
                <div class="tiles-grid" id="tiles-grid">
                    <!-- Tile: Total Distance -->
                    <div class="tile">
                        <div class="tile-icon orange">
                            <svg viewBox="0 0 24 24"><path d="M21 3L3 10.53v.98l6.84 2.65L12.48 21h.98L21 3z"/></svg>
                        </div>
                        <div class="tile-label">Total Distance</div>
                        <div>
                            <span class="tile-value" id="t-distance">–</span>
                            <span class="tile-unit">km</span>
                        </div>
                    </div>
                    <!-- Tile: Trips -->
                    <div class="tile">
                        <div class="tile-icon navy">
                            <svg viewBox="0 0 24 24"><path d="M1 9l2 2c4.97-4.97 13.03-4.97 18 0l2-2C16.93 2.93 7.08 2.93 1 9zm8 8l3 3 3-3c-1.65-1.66-4.34-1.66-6 0zm-4-4l2 2c2.76-2.76 7.24-2.76 10 0l2-2C15.14 9.14 8.87 9.14 5 13z"/></svg>
                        </div>
                        <div class="tile-label">Trips</div>
                        <div>
                            <span class="tile-value" id="t-trips">–</span>
                        </div>
                    </div>
                    <!-- Tile: Stops -->
                    <div class="tile">
                        <div class="tile-icon navy">
                            <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                        </div>
                        <div class="tile-label">Stops</div>
                        <div>
                            <span class="tile-value" id="t-stops">–</span>
                        </div>
                    </div>
                    <!-- Tile: Driving Time -->
                    <div class="tile">
                        <div class="tile-icon orange">
                            <svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg>
                        </div>
                        <div class="tile-label">Driving Time</div>
                        <div>
                            <span class="tile-value" id="t-driving">–</span>
                            <span class="tile-unit">min</span>
                        </div>
                    </div>
                    <!-- Tile: Idle Time -->
                    <div class="tile">
                        <div class="tile-icon navy">
                            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/></svg>
                        </div>
                        <div class="tile-label">Idle Time</div>
                        <div>
                            <span class="tile-value" id="t-idle">–</span>
                            <span class="tile-unit">min</span>
                        </div>
                    </div>
                    <!-- Tile: Ignition On -->
                    <div class="tile">
                        <div class="tile-icon orange">
                            <svg viewBox="0 0 24 24"><path d="M7 2v11h3v9l7-12h-4l4-8z"/></svg>
                        </div>
                        <div class="tile-label">Ignition On</div>
                        <div>
                            <span class="tile-value" id="t-ignition">–</span>
                            <span class="tile-unit">min</span>
                        </div>
                    </div>
                    <!-- Tile: Max Speed -->
                    <div class="tile">
                        <div class="tile-icon red">
                            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9l-2.79.93L11.5 9H10v.5l1.71 5.13c.18.54.72.87 1.29.87.57 0 1.05-.32 1.28-.84L16 10.5V10h-1l-.5 1z"/></svg>
                        </div>
                        <div class="tile-label">Max Speed</div>
                        <div>
                            <span class="tile-value" id="t-maxspeed">–</span>
                            <span class="tile-unit">km/h</span>
                        </div>
                    </div>
                    <!-- Tile: Avg Speed -->
                    <div class="tile">
                        <div class="tile-icon navy">
                            <svg viewBox="0 0 24 24"><path d="M20.38 8.57l-1.23 1.85a8 8 0 0 1-.22 7.58H5.07A8 8 0 0 1 15.58 6.85l1.85-1.23A10 10 0 0 0 3.35 19a2 2 0 0 0 1.72 1h13.85a2 2 0 0 0 1.74-1 10 10 0 0 0-.27-10.44zm-9.79 6.84a2 2 0 0 0 2.83 0l5.66-8.49-8.49 5.66a2 2 0 0 0 0 2.83z"/></svg>
                        </div>
                        <div class="tile-label">Avg Speed</div>
                        <div>
                            <span class="tile-value" id="t-avgspeed">–</span>
                            <span class="tile-unit">km/h</span>
                        </div>
                    </div>
                    <!-- Tile: Overspeed Alerts -->
                    <div class="tile">
                        <div class="tile-icon red">
                            <svg viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
                        </div>
                        <div class="tile-label">Overspeed Alerts</div>
                        <div>
                            <span class="tile-value" id="t-overspeed">–</span>
                        </div>
                    </div>
                </div>

                {{-- Charts --}}
                <div class="chart-grid" id="charts-grid">
                    <div class="chart-card full">
                        <h3>Daily Distance (km)</h3>
                        <div class="chart-wrap">
                            <canvas id="chart-distance"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <h3>Daily Trips &amp; Stops</h3>
                        <div class="chart-wrap">
                            <canvas id="chart-trips-stops"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <h3>Daily Ignition On Time (min)</h3>
                        <div class="chart-wrap">
                            <canvas id="chart-ignition"></canvas>
                        </div>
                    </div>
                </div>
            </div><!-- /#state-stats -->

        </div><!-- /#stats-content -->
    </div><!-- /.stats-main -->
</div><!-- /.stats-wrap -->
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
// ──────────────────────────────────────────────────────────────────────────────
// State
// ──────────────────────────────────────────────────────────────────────────────
let selectedVehicleId   = null;
let selectedVehiclePlate = '';
let selectedVehicleName  = '';
let selectedVehicleIsDemo = false;
let activePeriod        = 'today';
let chartDistance       = null;
let chartTripsStops     = null;
let chartIgnition       = null;
let fetchController     = null; // AbortController for in-flight AJAX

// ──────────────────────────────────────────────────────────────────────────────
// Sidebar search
// ──────────────────────────────────────────────────────────────────────────────
function filterVehicles(query) {
    const q = query.toLowerCase().trim();
    document.querySelectorAll('#vehicle-list .vehicle-card').forEach(card => {
        const plate = card.dataset.plate || '';
        const name  = card.dataset.name  || '';
        card.style.display = (!q || plate.includes(q) || name.includes(q)) ? '' : 'none';
    });
}

// ──────────────────────────────────────────────────────────────────────────────
// Vehicle selection
// ──────────────────────────────────────────────────────────────────────────────
function selectVehicle(vehicleId, plate, name, isDemo) {
    if (vehicleId === selectedVehicleId) return;

    // Highlight active card
    document.querySelectorAll('#vehicle-list .vehicle-card').forEach(c => c.classList.remove('active'));
    const card = document.querySelector(`[data-id="${vehicleId}"]`);
    if (card) card.classList.add('active');

    selectedVehicleId    = vehicleId;
    selectedVehiclePlate = plate;
    selectedVehicleName  = name;
    selectedVehicleIsDemo = isDemo;

    // Show period bar
    document.getElementById('period-bar').style.display = 'flex';

    loadStats();
}

// ──────────────────────────────────────────────────────────────────────────────
// Period selection
// ──────────────────────────────────────────────────────────────────────────────
function setPeriod(period) {
    if (period === activePeriod) return;
    activePeriod = period;

    document.querySelectorAll('.period-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.period === period);
    });

    if (selectedVehicleId) loadStats();
}

// ──────────────────────────────────────────────────────────────────────────────
// Load stats from AJAX endpoint
// ──────────────────────────────────────────────────────────────────────────────
async function loadStats() {
    // Abort any in-flight request
    if (fetchController) fetchController.abort();
    fetchController = new AbortController();

    showState('loading');

    try {
        const url = `/stats/${selectedVehicleId}/data?period=${activePeriod}`;
        const res = await fetch(url, {
            signal: fetchController.signal,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        const json = await res.json();

        if (!json.success) {
            showError(json.message || 'Could not load statistics.');
            return;
        }

        renderStats(json.data);

    } catch (err) {
        if (err.name === 'AbortError') return; // Cancelled by a newer request — ignore
        showError('Network error. Please check your connection.');
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// Render stats into the panel
// ──────────────────────────────────────────────────────────────────────────────
function renderStats(d) {
    // Vehicle header
    document.getElementById('vh-plate').textContent = selectedVehiclePlate;
    const meta = [selectedVehicleName, selectedVehicleIsDemo ? 'Demo Vehicle' : null]
        .filter(Boolean).join(' · ');
    document.getElementById('vh-meta').textContent =
        meta || periodLabel(activePeriod);

    if (selectedVehicleIsDemo) {
        document.getElementById('vh-icon').style.background = '#FA6908';
    } else {
        document.getElementById('vh-icon').style.background = '#021F4A';
    }

    // Tiles
    document.getElementById('t-distance').textContent  = fmt(d.totalDistanceKm, 1);
    document.getElementById('t-trips').textContent     = d.totalTripCount ?? '–';
    document.getElementById('t-stops').textContent     = d.totalStopCount ?? '–';
    document.getElementById('t-driving').textContent   = Math.round(d.totalDrivingMinutes ?? 0);
    document.getElementById('t-idle').textContent      = Math.round(d.totalIdleMinutes ?? 0);
    document.getElementById('t-ignition').textContent  = Math.round(d.totalIgnitionOnMinutes ?? 0);
    document.getElementById('t-maxspeed').textContent  = fmt(d.maxSpeed, 1);
    document.getElementById('t-avgspeed').textContent  = fmt(d.averageSpeed, 1);
    document.getElementById('t-overspeed').textContent = d.overspeedIncidentCount ?? '–';

    // Charts
    const daily   = (d.dailyBreakdown ?? []).sort(
        (a, b) => new Date(a.date) - new Date(b.date)
    );

    const labels   = daily.map(row => fmtDate(row.date));
    const distData = daily.map(row => parseFloat(row.distanceKm ?? 0).toFixed(2));
    const tripData = daily.map(row => row.tripCount  ?? 0);
    const stopData = daily.map(row => row.stopCount  ?? 0);
    const ignData  = daily.map(row => Math.round(row.ignitionOnMinutes ?? 0));

    buildBarChart('chart-distance', chartDistance, labels, distData,
        'Distance (km)', '#FA6908', c => { chartDistance = c; });

    buildGroupedChart('chart-trips-stops', chartTripsStops, labels,
        { label: 'Trips', data: tripData, color: '#FA6908' },
        { label: 'Stops', data: stopData, color: '#021F4A' },
        c => { chartTripsStops = c; });

    buildBarChart('chart-ignition', chartIgnition, labels, ignData,
        'Ignition On (min)', '#021F4A', c => { chartIgnition = c; });

    showState('stats');
}

// ──────────────────────────────────────────────────────────────────────────────
// Chart helpers
// ──────────────────────────────────────────────────────────────────────────────
const CHART_DEFAULTS = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#021F4A',
            titleColor: '#fff',
            bodyColor: '#e2e8f0',
            padding: 10,
            cornerRadius: 8,
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { font: { size: 11 }, color: '#94a3b8', maxRotation: 45 },
        },
        y: {
            grid: { color: '#f1f5f9' },
            ticks: { font: { size: 11 }, color: '#94a3b8' },
            beginAtZero: true,
        },
    },
};

function buildBarChart(canvasId, existingChart, labels, data, label, color, save) {
    if (existingChart) existingChart.destroy();
    const ctx = document.getElementById(canvasId).getContext('2d');
    save(new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label,
                data,
                backgroundColor: color + 'CC', // 80% opacity
                borderColor: color,
                borderWidth: 1,
                borderRadius: 4,
            }],
        },
        options: { ...CHART_DEFAULTS },
    }));
}

function buildGroupedChart(canvasId, existingChart, labels, ds1, ds2, save) {
    if (existingChart) existingChart.destroy();
    const ctx = document.getElementById(canvasId).getContext('2d');
    save(new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: ds1.label,
                    data: ds1.data,
                    backgroundColor: ds1.color + 'CC',
                    borderColor: ds1.color,
                    borderWidth: 1,
                    borderRadius: 4,
                },
                {
                    label: ds2.label,
                    data: ds2.data,
                    backgroundColor: ds2.color + 'CC',
                    borderColor: ds2.color,
                    borderWidth: 1,
                    borderRadius: 4,
                },
            ],
        },
        options: {
            ...CHART_DEFAULTS,
            plugins: {
                ...CHART_DEFAULTS.plugins,
                legend: {
                    display: true,
                    position: 'top',
                    labels: { font: { size: 12 }, color: '#475569', boxWidth: 12 },
                },
            },
        },
    }));
}

// ──────────────────────────────────────────────────────────────────────────────
// Utility
// ──────────────────────────────────────────────────────────────────────────────
function showState(state) {
    ['empty', 'loading', 'error', 'stats'].forEach(s => {
        const el = document.getElementById(`state-${s}`);
        if (el) el.style.display = (s === state) ? '' : 'none';
    });
}

function showError(msg) {
    document.getElementById('error-msg').textContent = msg;
    showState('error');
}

function fmt(val, decimals = 0) {
    if (val == null || isNaN(val)) return '–';
    return parseFloat(val).toFixed(decimals);
}

function fmtDate(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
}

function periodLabel(period) {
    return { today: 'Today', week: 'This Week', month: 'This Month', all: 'All Time' }[period] ?? '';
}
</script>
@endpush