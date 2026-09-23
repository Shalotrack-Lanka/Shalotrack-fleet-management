@extends('layouts.app')

@section('title', 'Vehicle Statistics')

@section('content')
{{-- ═══════════════════════════════════════════════════════════════════════════
    STYLES — must live inside @section('content'), NOT @push('styles')
    (the layout's @stack('styles') is never called)
═══════════════════════════════════════════════════════════════════════════ --}}
<style>
    /* ── Layout ────────────────────────────────────────────────────────────────── */
    .stats-wrap {
        display: grid;
        grid-template-columns: 280px 1fr;
        height: calc(100vh - 64px);
        overflow: hidden;
        background: #f8fafc;
    }

    /* ── Sidebar ───────────────────────────────────────────────────────────────── */
    .sidebar {
        background: #fff;
        border-right: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .sidebar-header {
        padding: 20px 16px 12px;
        border-bottom: 1px solid #f1f5f9;
        flex-shrink: 0;
    }

    .sidebar-header h2 {
        font-size: 15px;
        font-weight: 700;
        color: #021F4A;
        margin: 0 0 12px;
        letter-spacing: 0.2px;
    }

    .sidebar-search {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 13px;
        color: #334155;
        outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
    }

    .sidebar-search:focus {
        border-color: #FA6908;
        box-shadow: 0 0 0 3px rgba(250, 105, 8, 0.12);
    }

    .vehicle-list {
        flex: 1;
        overflow-y: auto;
        padding: 8px;
    }

    .vehicle-list::-webkit-scrollbar {
        width: 4px;
    }

    .vehicle-list::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 4px;
    }

    .vehicle-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        border-radius: 10px;
        cursor: pointer;
        transition: background 0.12s;
        margin-bottom: 2px;
        border: 1px solid transparent;
    }

    .vehicle-card:hover {
        background: #f8fafc;
    }

    .vehicle-card.active {
        background: #fff7f0;
        border-color: #FA6908;
    }

    .vehicle-card.demo-card {
        opacity: 0.7;
    }

    .vehicle-icon {
        width: 38px;
        height: 38px;
        border-radius: 9px;
        background: #021F4A;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .vehicle-icon.demo {
        background: #FA6908;
    }

    .vehicle-icon svg {
        width: 20px;
        height: 20px;
        fill: #fff;
    }

    .vehicle-info {
        min-width: 0;
    }

    .vehicle-plate {
        font-size: 13px;
        font-weight: 700;
        color: #021F4A;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .vehicle-name {
        font-size: 11px;
        color: #64748b;
        margin-top: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ── Main area ─────────────────────────────────────────────────────────────── */
    .stats-main {
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    /* Period bar */
    .period-bar {
        padding: 10px 24px;
        background: #fff;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }

    .period-spacer {
        flex: 1;
    }

    .period-btn {
        padding: 6px 18px;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #475569;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s;
        white-space: nowrap;
    }

    .period-btn:hover {
        border-color: #FA6908;
        color: #FA6908;
    }

    .period-btn.active {
        background: #FA6908;
        color: #fff;
        border-color: #FA6908;
        font-weight: 600;
    }

    /* Export dropdown */
    .export-wrap {
        position: relative;
        margin-left: 8px;
    }

    .btn-export {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #475569;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s;
        white-space: nowrap;
        user-select: none;
    }

    .btn-export:hover,
    .btn-export.open {
        border-color: #021F4A;
        color: #021F4A;
        background: #f8fafc;
    }

    .btn-export svg {
        width: 14px;
        height: 14px;
        fill: currentColor;
    }

    .btn-export .chevron {
        transition: transform 0.2s;
    }

    .btn-export.open .chevron {
        transform: rotate(180deg);
    }

    .export-menu {
        display: none;
        position: absolute;
        right: 0;
        top: calc(100% + 6px);
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.10);
        min-width: 160px;
        z-index: 200;
        overflow: hidden;
    }

    .export-menu.open {
        display: block;
    }

    .export-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        font-size: 13px;
        color: #334155;
        cursor: pointer;
        transition: background 0.12s;
    }

    .export-item:hover {
        background: #f8fafc;
    }

    .export-item svg {
        width: 16px;
        height: 16px;
        fill: #64748b;
        flex-shrink: 0;
    }

    .export-item span {
        font-weight: 500;
    }

    .export-item small {
        display: block;
        font-size: 11px;
        color: #94a3b8;
        font-weight: 400;
    }


    /* Content scroller */
    .stats-content {
        flex: 1;
        overflow-y: auto;
        padding: 24px;
    }

    .stats-content::-webkit-scrollbar {
        width: 5px;
    }

    .stats-content::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    /* ── Empty / error states ──────────────────────────────────────────────────── */
    .stats-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 420px;
        color: #94a3b8;
        text-align: center;
    }

    .stats-empty svg {
        width: 64px;
        height: 64px;
        fill: #cbd5e1;
        margin-bottom: 16px;
    }

    .stats-empty p {
        font-size: 15px;
        margin: 0;
        color: #94a3b8;
    }

    .stats-empty small {
        font-size: 12px;
        color: #b0bec5;
        margin-top: 6px;
    }

    /* ── Skeleton loader ───────────────────────────────────────────────────────── */
    @keyframes shimmer {
        0% {
            background-position: -200% 0;
        }

        100% {
            background-position: 200% 0;
        }
    }

    .skeleton-box {
        background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
        background-size: 200% 100%;
        animation: shimmer 1.4s infinite;
        border-radius: 10px;
    }

    /* ── Vehicle header ────────────────────────────────────────────────────────── */
    .stats-vehicle-header {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
        padding-bottom: 20px;
        border-bottom: 1px solid #f1f5f9;
    }

    .icon-big {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        background: #021F4A;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .icon-big svg {
        width: 30px;
        height: 30px;
        fill: #fff;
    }

    .plate {
        font-size: 22px;
        font-weight: 800;
        color: #021F4A;
        letter-spacing: 0.5px;
    }

    .meta {
        font-size: 13px;
        color: #64748b;
        margin-top: 3px;
    }

    /* ── Stat tiles ────────────────────────────────────────────────────────────── */
    .tiles-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(168px, 1fr));
        gap: 14px;
        margin-bottom: 28px;
    }

    .tile {
        background: #fff;
        border-radius: 14px;
        padding: 18px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
        display: flex;
        flex-direction: column;
        gap: 10px;
        transition: box-shadow 0.15s;
    }

    .tile:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .tile-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .tile-icon.orange {
        background: rgba(250, 105, 8, 0.12);
    }

    .tile-icon.navy {
        background: rgba(2, 31, 74, 0.10);
    }

    .tile-icon.red {
        background: rgba(239, 68, 68, 0.12);
    }

    .tile-icon svg {
        width: 22px;
        height: 22px;
    }

    .tile-icon.orange svg {
        fill: #FA6908;
    }

    .tile-icon.navy svg {
        fill: #021F4A;
    }

    .tile-icon.red svg {
        fill: #ef4444;
    }

    .tile-label {
        font-size: 11.5px;
        color: #64748b;
        font-weight: 500;
        letter-spacing: 0.2px;
    }

    .tile-value {
        font-size: 26px;
        font-weight: 800;
        color: #021F4A;
        line-height: 1;
    }

    .tile-unit {
        font-size: 12px;
        color: #94a3b8;
        font-weight: 500;
        margin-left: 2px;
    }

    /* Duration tiles show h/m in smaller text */
    .tile-value .dur-h {
        font-size: 14px;
        font-weight: 600;
        color: #475569;
    }

    .tile-value .dur-m {
        font-size: 14px;
        font-weight: 600;
        color: #475569;
    }

    /* Alert tile: highlight if non-zero */
    .tile.alert-active .tile-value {
        color: #ef4444;
    }

    .tile.alert-active {
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    /* ── Charts ────────────────────────────────────────────────────────────────── */
    .chart-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
    }

    .chart-card {
        background: #fff;
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }

    .chart-card.full {
        grid-column: 1 / -1;
    }

    .chart-card h3 {
        font-size: 11px;
        font-weight: 700;
        color: #94a3b8;
        margin: 0 0 14px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }

    .chart-wrap {
        position: relative;
        height: 200px;
    }

    .chart-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 200px;
        color: #b0bec5;
        font-size: 13px;
    }

    /* ── Responsive ────────────────────────────────────────────────────────────── */
    @media (max-width: 900px) {
        .stats-wrap {
            grid-template-columns: 1fr;
            grid-template-rows: auto 1fr;
            height: auto;
        }

        .sidebar {
            border-right: none;
            border-bottom: 1px solid #e2e8f0;
            height: 240px;
        }

        .stats-main {
            height: calc(100vh - 304px);
        }

        .chart-grid {
            grid-template-columns: 1fr;
        }

        .chart-card.full {
            grid-column: 1;
        }
    }

    @media (max-width: 600px) {
        .tiles-grid {
            grid-template-columns: 1fr 1fr;
        }

        .period-bar {
            flex-wrap: wrap;
        }

        .stats-content {
            padding: 16px;
        }
    }
</style>

<div class="stats-wrap">

    {{-- ── Sidebar ─────────────────────────────────────────────────────────── --}}
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Fleet Statistics</h2>
            <input
                type="text"
                id="vehicle-search"
                class="sidebar-search"
                placeholder="Search vehicles…"
                oninput="filterVehicles(this.value)">
        </div>

        <div class="vehicle-list" id="vehicle-list">
            @forelse($vehicles as $v)
            @php
            $plate = $v['vehicleNumber'] ?? 'N/A';
            $name = trim(($v['make'] ?? '') . ' ' . ($v['model'] ?? ''));
            $demo = $v['isDemoVehicle'] ?? false;
            $vid = $v['vehicleId'] ?? $v['id'] ?? '';
            @endphp
            <div class="vehicle-card {{ $demo ? 'demo-card' : '' }}"
                data-id="{{ $vid }}"
                data-plate="{{ strtolower($plate) }}"
                data-name="{{ strtolower($name) }}"
                onclick="selectVehicle('{{ $vid }}', '{{ addslashes($plate) }}', '{{ addslashes($name) }}', {{ $demo ? 'true' : 'false' }})">
                <div class="vehicle-icon {{ $demo ? 'demo' : '' }}">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z" />
                    </svg>
                </div>
                <div class="vehicle-info">
                    <div class="vehicle-plate">{{ $plate }}</div>
                    @if($name)
                    <div class="vehicle-name">{{ $name }}{{ $demo ? ' · Demo' : '' }}</div>
                    @elseif($demo)
                    <div class="vehicle-name">Demo Vehicle</div>
                    @endif
                </div>
            </div>
            @empty
            <div style="padding:32px 16px; text-align:center; color:#94a3b8; font-size:13px; line-height:1.6;">
                No GPS-enabled vehicles found.<br>Assign a GPS device to a vehicle first.
            </div>
            @endforelse
        </div>
    </div>

    {{-- ── Main panel ──────────────────────────────────────────────────────── --}}
    <div class="stats-main">

        {{-- Period selector (shown once a vehicle is selected) --}}
        <div class="period-bar" id="period-bar" style="display:none;">
            <button class="period-btn active" data-period="today" onclick="setPeriod('today')">Today</button>
            <button class="period-btn" data-period="week" onclick="setPeriod('week')">This Week</button>
            <button class="period-btn" data-period="month" onclick="setPeriod('month')">This Month</button>
            <button class="period-btn" data-period="all" onclick="setPeriod('all')">All Time</button>
            <div class="period-spacer"></div>
            <div class="export-wrap" id="export-wrap">
                <button class="btn-export" id="btn-export" onclick="toggleExportMenu(event)">
                    <svg viewBox="0 0 24 24">
                        <path d="M19 9h-4V3H9v6H5l7 7 7-7zm-8 2V5h2v6h1.17L12 13.17 9.83 11H11zm-6 7h14v2H5v-2z" />
                    </svg>
                    Export
                    <svg class="chevron" viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor;">
                        <path d="M7 10l5 5 5-5z" />
                    </svg>
                </button>
                <div class="export-menu" id="export-menu">
                    <div class="export-item" onclick="exportCSV(); closeExportMenu();">
                        <svg viewBox="0 0 24 24">
                            <path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.89 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13zm-3 8H8v-1h2v1zm0-2H8v-1h2v1zm0-2H8v-1h2v1zm5 4h-4v-1h4v1zm0-2h-4v-1h4v1zm0-2h-4v-1h4v1z" />
                        </svg>
                        <div>
                            <span>CSV Spreadsheet</span>
                            <small>Open in Excel / Sheets</small>
                        </div>
                    </div>
                    <div class="export-item" onclick="exportPDF(); closeExportMenu();">
                        <svg viewBox="0 0 24 24">
                            <path d="M20 2H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8.5 7.5c0 .83-.67 1.5-1.5 1.5H9v2H7.5V7H10c.83 0 1.5.67 1.5 1.5v1zm5 2c0 .83-.67 1.5-1.5 1.5h-2.5V7H15c.83 0 1.5.67 1.5 1.5v3zm4-3H19v1h1.5V11H19v2h-1.5V7h3v1.5zM9 9.5h1v-1H9v1zM4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm10 5.5h1v-3h-1v3z" />
                        </svg>
                        <div>
                            <span>PDF Report</span>
                            <small>Print-ready A4 layout</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Content area --}}
        <div class="stats-content" id="stats-content">

            {{-- State: no vehicle selected --}}
            <div class="stats-empty" id="state-empty">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z" />
                </svg>
                <p>Select a vehicle to view its statistics</p>
                <small>{{ count($vehicles) }} GPS-enabled vehicle{{ count($vehicles) !== 1 ? 's' : '' }} available</small>
            </div>

            {{-- State: loading skeleton --}}
            <div id="state-loading" style="display:none;">
                <div class="skeleton-wrap">
                    <div class="skeleton-box" style="height:56px;width:240px;margin-bottom:24px;border-radius:14px;"></div>
                    <div class="tiles-grid" style="margin-bottom:28px;">
                        @for($i = 0; $i < 9; $i++)
                            <div class="skeleton-box" style="height:100px;">
                    </div>
                    @endfor
                </div>
                <div class="chart-grid">
                    <div class="skeleton-box" style="height:240px;grid-column:1/-1;"></div>
                    <div class="skeleton-box" style="height:240px;"></div>
                    <div class="skeleton-box" style="height:240px;"></div>
                </div>
            </div>
        </div>

        {{-- State: error --}}
        <div class="stats-empty" id="state-error" style="display:none;">
            <svg viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
            </svg>
            <p id="error-msg">Could not load statistics. Please try again.</p>
            <small><a href="#" onclick="loadStats();return false;" style="color:#FA6908;">Retry</a></small>
        </div>

        {{-- State: stats loaded --}}
        <div id="state-stats" style="display:none;">

            {{-- Vehicle header --}}
            <div class="stats-vehicle-header">
                <div class="icon-big" id="vh-icon">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z" />
                    </svg>
                </div>
                <div>
                    <div class="plate" id="vh-plate">–</div>
                    <div class="meta" id="vh-meta">–</div>
                </div>
            </div>

            {{-- 9 stat tiles --}}
            <div class="tiles-grid" id="tiles-grid">
                <div class="tile">
                    <div class="tile-icon orange">
                        <svg viewBox="0 0 24 24">
                            <path d="M21 3L3 10.53v.98l6.84 2.65L12.48 21h.98L21 3z" />
                        </svg>
                    </div>
                    <div class="tile-label">Total Distance</div>
                    <div>
                        <span class="tile-value" id="t-distance">–</span>
                        <span class="tile-unit">km</span>
                    </div>
                </div>

                <div class="tile">
                    <div class="tile-icon navy">
                        <svg viewBox="0 0 24 24">
                            <path d="M1 9l2 2c4.97-4.97 13.03-4.97 18 0l2-2C16.93 2.93 7.08 2.93 1 9zm8 8l3 3 3-3c-1.65-1.66-4.34-1.66-6 0zm-4-4l2 2c2.76-2.76 7.24-2.76 10 0l2-2C15.14 9.14 8.87 9.14 5 13z" />
                        </svg>
                    </div>
                    <div class="tile-label">Trips</div>
                    <div><span class="tile-value" id="t-trips">–</span></div>
                </div>

                <div class="tile">
                    <div class="tile-icon navy">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                        </svg>
                    </div>
                    <div class="tile-label">Stops</div>
                    <div><span class="tile-value" id="t-stops">–</span></div>
                </div>

                <div class="tile">
                    <div class="tile-icon orange">
                        <svg viewBox="0 0 24 24">
                            <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z" />
                        </svg>
                    </div>
                    <div class="tile-label">Driving Time</div>
                    <div><span class="tile-value" id="t-driving">–</span></div>
                </div>

                <div class="tile">
                    <div class="tile-icon navy">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z" />
                        </svg>
                    </div>
                    <div class="tile-label">Idle Time</div>
                    <div><span class="tile-value" id="t-idle">–</span></div>
                </div>

                <div class="tile">
                    <div class="tile-icon orange">
                        <svg viewBox="0 0 24 24">
                            <path d="M7 2v11h3v9l7-12h-4l4-8z" />
                        </svg>
                    </div>
                    <div class="tile-label">Ignition On</div>
                    <div><span class="tile-value" id="t-ignition">–</span></div>
                </div>

                <div class="tile">
                    <div class="tile-icon red">
                        <svg viewBox="0 0 24 24">
                            <path d="M20.38 8.57l-1.23 1.85a8 8 0 0 1-.22 7.58H5.07A8 8 0 0 1 15.58 6.85l1.85-1.23A10 10 0 0 0 3.35 19a2 2 0 0 0 1.72 1h13.85a2 2 0 0 0 1.74-1 10 10 0 0 0-.27-10.44zm-9.79 6.84a2 2 0 0 0 2.83 0l5.66-8.49-8.49 5.66a2 2 0 0 0 0 2.83z" />
                        </svg>
                    </div>
                    <div class="tile-label">Max Speed</div>
                    <div>
                        <span class="tile-value" id="t-maxspeed">–</span>
                        <span class="tile-unit">km/h</span>
                    </div>
                </div>

                <div class="tile">
                    <div class="tile-icon navy">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9l-2.79.93L11.5 9H10v.5l1.71 5.13c.18.54.72.87 1.29.87.57 0 1.05-.32 1.28-.84L16 10.5V10h-1l-.5 1z" />
                        </svg>
                    </div>
                    <div class="tile-label">Avg Speed</div>
                    <div>
                        <span class="tile-value" id="t-avgspeed">–</span>
                        <span class="tile-unit">km/h</span>
                    </div>
                </div>

                <div class="tile" id="tile-overspeed">
                    <div class="tile-icon red">
                        <svg viewBox="0 0 24 24">
                            <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z" />
                        </svg>
                    </div>
                    <div class="tile-label">Overspeed Alerts</div>
                    <div><span class="tile-value" id="t-overspeed">–</span></div>
                </div>
            </div>

            {{-- Charts --}}
            <div class="chart-grid" id="charts-grid">
                <div class="chart-card full">
                    <h3>Daily Distance (km)</h3>
                    <div class="chart-wrap" id="wrap-distance">
                        <canvas id="chart-distance"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h3>Daily Trips &amp; Stops</h3>
                    <div class="chart-wrap" id="wrap-trips-stops">
                        <canvas id="chart-trips-stops"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h3>Daily Ignition On Time (min)</h3>
                    <div class="chart-wrap" id="wrap-ignition">
                        <canvas id="chart-ignition"></canvas>
                    </div>
                </div>
            </div>

        </div>{{-- /#state-stats --}}
    </div>{{-- /#stats-content --}}
</div>{{-- /.stats-main --}}
</div>{{-- /.stats-wrap --}}

{{-- Chart.js — MUST be inline here, NOT in @push('scripts') --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    // ══════════════════════════════════════════════════════════════════════════════
    // State
    // ══════════════════════════════════════════════════════════════════════════════
    let selectedVehicleId = null;
    let selectedVehiclePlate = '';
    let selectedVehicleName = '';
    let selectedVehicleIsDemo = false;
    let activePeriod = 'today';
    let chartDistance = null;
    let chartTripsStops = null;
    let chartIgnition = null;
    let fetchController = null;
    let lastData = null; // cached for CSV export

    // ══════════════════════════════════════════════════════════════════════════════
    // Sidebar search
    // ══════════════════════════════════════════════════════════════════════════════
    function filterVehicles(query) {
        const q = query.toLowerCase().trim();
        document.querySelectorAll('#vehicle-list .vehicle-card').forEach(card => {
            const plate = card.dataset.plate || '';
            const name = card.dataset.name || '';
            card.style.display = (!q || plate.includes(q) || name.includes(q)) ? '' : 'none';
        });
    }

    // ══════════════════════════════════════════════════════════════════════════════
    // Vehicle selection
    // ══════════════════════════════════════════════════════════════════════════════
    function selectVehicle(vehicleId, plate, name, isDemo) {
        if (vehicleId === selectedVehicleId) return;

        document.querySelectorAll('#vehicle-list .vehicle-card').forEach(c => c.classList.remove('active'));
        const card = document.querySelector(`[data-id="${vehicleId}"]`);
        if (card) card.classList.add('active');

        selectedVehicleId = vehicleId;
        selectedVehiclePlate = plate;
        selectedVehicleName = name;
        selectedVehicleIsDemo = isDemo;

        document.getElementById('period-bar').style.display = 'flex';
        loadStats();
    }

    // ══════════════════════════════════════════════════════════════════════════════
    // Period selection
    // ══════════════════════════════════════════════════════════════════════════════
    function setPeriod(period) {
        if (period === activePeriod) return;
        activePeriod = period;
        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.period === period);
        });
        if (selectedVehicleId) loadStats();
    }

    // ══════════════════════════════════════════════════════════════════════════════
    // Load stats via AJAX
    // ══════════════════════════════════════════════════════════════════════════════
    async function loadStats() {
        if (fetchController) fetchController.abort();
        fetchController = new AbortController();
        showState('loading');
        lastData = null;

        try {
            const url = `/stats/${selectedVehicleId}/data?period=${activePeriod}`;
            const res = await fetch(url, {
                signal: fetchController.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
            });

            // Session expired — redirect to login immediately
            if (res.status === 401) {
                window.location.href = '/login?expired=1';
                return;
            }

            if (!res.ok) {
                showError(`Server error (${res.status}). Please try again.`);
                return;
            }

            const json = await res.json();

            // API-level session expiry (returned as JSON 401)
            if (json.expired) {
                window.location.href = '/login?expired=1';
                return;
            }

            if (!json.success) {
                showError(json.message || 'Could not load statistics.');
                return;
            }

            lastData = json.data;
            renderStats(json.data);

        } catch (err) {
            if (err.name === 'AbortError') return;
            showError('Network error. Please check your connection.');
        }
    }

    // ══════════════════════════════════════════════════════════════════════════════
    // Render stats
    // ══════════════════════════════════════════════════════════════════════════════
    function renderStats(d) {
        // Vehicle header
        document.getElementById('vh-plate').textContent = selectedVehiclePlate;
        const label = periodLabel(activePeriod);
        const parts = [selectedVehicleName, selectedVehicleIsDemo ? 'Demo' : null, label]
            .filter(Boolean);
        document.getElementById('vh-meta').textContent = parts.join(' · ');
        document.getElementById('vh-icon').style.background =
            selectedVehicleIsDemo ? '#FA6908' : '#021F4A';

        // Tiles — distance
        document.getElementById('t-distance').textContent = fmt(d.totalDistanceKm, 2);

        // Tiles — counts
        document.getElementById('t-trips').textContent = d.totalTripCount ?? '–';
        document.getElementById('t-stops').textContent = d.totalStopCount ?? '–';

        // Tiles — duration (show as "2h 15m" not raw minutes)
        document.getElementById('t-driving').innerHTML = fmtDuration(d.totalDrivingMinutes);
        document.getElementById('t-idle').innerHTML = fmtDuration(d.totalIdleMinutes);
        document.getElementById('t-ignition').innerHTML = fmtDuration(d.totalIgnitionOnMinutes);

        // Tiles — speeds
        document.getElementById('t-maxspeed').textContent = fmt(d.maxSpeed, 1);
        document.getElementById('t-avgspeed').textContent = fmt(d.averageSpeed, 1);

        // Overspeed — highlight red if non-zero
        const ovTile = document.getElementById('tile-overspeed');
        const ovVal = d.overspeedIncidentCount ?? 0;
        document.getElementById('t-overspeed').textContent = ovVal;
        ovTile.classList.toggle('alert-active', ovVal > 0);

        // Charts
        const daily = (d.dailyBreakdown ?? []).sort(
            (a, b) => new Date(a.date) - new Date(b.date)
        );

        if (daily.length === 0) {
            // No breakdown data — destroy charts and show placeholder
            destroyCharts();
            ['distance', 'trips-stops', 'ignition'].forEach(id => {
                const wrap = document.getElementById(`wrap-${id}`);
                if (wrap) {
                    wrap.innerHTML = '<div class="chart-empty">No daily data for this period</div>';
                }
            });
        } else {
            // Re-create canvas elements (in case they were replaced by "no data" div)
            ensureCanvas('wrap-distance', 'chart-distance');
            ensureCanvas('wrap-trips-stops', 'chart-trips-stops');
            ensureCanvas('wrap-ignition', 'chart-ignition');

            const labels = daily.map(row => fmtDate(row.date));
            const distData = daily.map(row => parseFloat(row.distanceKm ?? 0).toFixed(2));
            const tripData = daily.map(row => row.tripCount ?? 0);
            const stopData = daily.map(row => row.stopCount ?? 0);
            const ignData = daily.map(row => Math.round(row.ignitionOnMinutes ?? 0));

            chartDistance = buildBar('chart-distance', chartDistance, labels, distData, 'Distance (km)', '#FA6908');
            chartTripsStops = buildGrouped('chart-trips-stops', chartTripsStops, labels, {
                label: 'Trips',
                data: tripData,
                color: '#FA6908'
            }, {
                label: 'Stops',
                data: stopData,
                color: '#021F4A'
            });
            chartIgnition = buildBar('chart-ignition', chartIgnition, labels, ignData, 'Ignition On (min)', '#021F4A');
        }

        showState('stats');
    }

    // ══════════════════════════════════════════════════════════════════════════════
    // Chart helpers
    // ══════════════════════════════════════════════════════════════════════════════
    const BASE_OPTIONS = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
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
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        size: 11
                    },
                    color: '#94a3b8',
                    maxRotation: 45
                },
            },
            y: {
                grid: {
                    color: '#f1f5f9',
                    drawBorder: false
                },
                ticks: {
                    font: {
                        size: 11
                    },
                    color: '#94a3b8'
                },
                beginAtZero: true,
            },
        },
    };

    function buildBar(canvasId, old, labels, data, label, color) {
        if (old) old.destroy();
        const ctx = document.getElementById(canvasId).getContext('2d');
        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label,
                    data,
                    backgroundColor: color + 'CC',
                    borderColor: color,
                    borderWidth: 1,
                    borderRadius: 5,
                    borderSkipped: false,
                }],
            },
            options: {
                ...BASE_OPTIONS
            },
        });
    }

    function buildGrouped(canvasId, old, labels, ds1, ds2) {
        if (old) old.destroy();
        const ctx = document.getElementById(canvasId).getContext('2d');
        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                        label: ds1.label,
                        data: ds1.data,
                        backgroundColor: ds1.color + 'CC',
                        borderColor: ds1.color,
                        borderWidth: 1,
                        borderRadius: 4,
                        borderSkipped: false,
                    },
                    {
                        label: ds2.label,
                        data: ds2.data,
                        backgroundColor: ds2.color + 'CC',
                        borderColor: ds2.color,
                        borderWidth: 1,
                        borderRadius: 4,
                        borderSkipped: false,
                    },
                ],
            },
            options: {
                ...BASE_OPTIONS,
                plugins: {
                    ...BASE_OPTIONS.plugins,
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                size: 12
                            },
                            color: '#475569',
                            boxWidth: 12
                        },
                    },
                },
            },
        });
    }

    function destroyCharts() {
        if (chartDistance) {
            chartDistance.destroy();
            chartDistance = null;
        }
        if (chartTripsStops) {
            chartTripsStops.destroy();
            chartTripsStops = null;
        }
        if (chartIgnition) {
            chartIgnition.destroy();
            chartIgnition = null;
        }
    }

    function ensureCanvas(wrapperId, canvasId) {
        const wrap = document.getElementById(wrapperId);
        if (!wrap) return;
        if (!document.getElementById(canvasId)) {
            wrap.innerHTML = `<canvas id="${canvasId}"></canvas>`;
        }
    }

    // ══════════════════════════════════════════════════════════════════════════════
    // Export dropdown
    // ══════════════════════════════════════════════════════════════════════════════
    function toggleExportMenu(e) {
        e.stopPropagation();
        const btn = document.getElementById('btn-export');
        const menu = document.getElementById('export-menu');
        const open = menu.classList.contains('open');
        closeExportMenu();
        if (!open) {
            btn.classList.add('open');
            menu.classList.add('open');
        }
    }

    function closeExportMenu() {
        document.getElementById('btn-export')?.classList.remove('open');
        document.getElementById('export-menu')?.classList.remove('open');
    }
    // Close on outside click
    document.addEventListener('click', () => closeExportMenu());

    // ── CSV ───────────────────────────────────────────────────────────────────────
    function exportCSV() {
        if (!lastData) return;
        const d = lastData;

        const rows = [
            ['ShaloTrack Vehicle Statistics Export'],
            ['Vehicle', selectedVehiclePlate],
            ['Name', selectedVehicleName || ''],
            ['Period', periodLabel(activePeriod)],
            ['Generated', new Date().toLocaleString()],
            [],
            ['Metric', 'Value', 'Unit'],
            ['Total Distance', fmt(d.totalDistanceKm, 2), 'km'],
            ['Total Trips', d.totalTripCount ?? '', ''],
            ['Total Stops', d.totalStopCount ?? '', ''],
            ['Driving Time', Math.round(d.totalDrivingMinutes ?? 0), 'min'],
            ['Idle Time', Math.round(d.totalIdleMinutes ?? 0), 'min'],
            ['Ignition On Time', Math.round(d.totalIgnitionOnMinutes ?? 0), 'min'],
            ['Max Speed', fmt(d.maxSpeed, 1), 'km/h'],
            ['Avg Speed', fmt(d.averageSpeed, 1), 'km/h'],
            ['Overspeed Alerts', d.overspeedIncidentCount ?? '', ''],
        ];

        const daily = (d.dailyBreakdown ?? []).sort((a, b) => new Date(a.date) - new Date(b.date));
        if (daily.length > 0) {
            rows.push([], ['Daily Breakdown'],
                ['Date', 'Distance (km)', 'Trips', 'Stops', 'Ignition On (min)']);
            daily.forEach(row => rows.push([
                row.date ?? '',
                parseFloat(row.distanceKm ?? 0).toFixed(2),
                row.tripCount ?? 0,
                row.stopCount ?? 0,
                Math.round(row.ignitionOnMinutes ?? 0),
            ]));
        }

        const csv = rows.map(r => r.map(v => `"${String(v).replace(/"/g,'""')}"`).join(',')).join('\r\n');
        const blob = new Blob([csv], {
            type: 'text/csv;charset=utf-8;'
        });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `stats_${selectedVehiclePlate}_${activePeriod}_${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
    }

    // ── PDF (server-side via barryvdh/laravel-dompdf) ───────────────────────────
    async function exportPDF() {
        if (!lastData || !selectedVehicleId) return;

        const btn = document.getElementById('btn-export');
        btn.disabled = true;
        btn.textContent = 'Generating…';

        try {
            // Capture Chart.js canvases as PNG data-URLs to embed in the PDF
            function canvasPng(id) {
                const el = document.getElementById(id);
                return (el && el.tagName === 'CANVAS') ? el.toDataURL('image/png') : '';
            }

            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (!csrfMeta) throw new Error('CSRF token meta tag not found in layout.');

            const body = new URLSearchParams({
                _token: csrfMeta.content,
                period: activePeriod,
                vehicle_plate: selectedVehiclePlate,
                vehicle_name: selectedVehicleName,
                vehicle_is_demo: selectedVehicleIsDemo ? '1' : '0',
                chart_distance: canvasPng('chart-distance'),
                chart_trips_stops: canvasPng('chart-trips-stops'),
                chart_ignition: canvasPng('chart-ignition'),
            });

            const res = await fetch(`/stats/${selectedVehicleId}/export`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: body.toString(),
            });

            if (res.status === 401) {
                window.location.href = '/login?expired=1';
                return;
            }

            if (!res.ok) {
                const json = await res.json().catch(() => ({}));
                throw new Error(json.message || `Server error (${res.status})`);
            }

            // Stream the PDF blob to a download
            const blob = await res.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            const filename = res.headers.get('Content-Disposition')
                ?.match(/filename="?([^"]+)"?/)?.[1] ||
                `shalotrack_${selectedVehiclePlate}_${activePeriod}.pdf`;

            a.href = url;
            a.download = filename;
            a.click();
            URL.revokeObjectURL(url);

        } catch (err) {
            alert(`PDF export failed: ${err.message}`);
        } finally {
            btn.disabled = false;
            btn.innerHTML = `<svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zm-8 2V5h2v6h1.17L12 13.17 9.83 11H11zm-6 7h14v2H5v-2z"/></svg> Export <svg class="chevron" viewBox="0 0 24 24" style="width:12px;height:12px;fill:currentColor"><path d="M7 10l5 5 5-5z"/></svg>`;
        }
    }

    // ══════════════════════════════════════════════════════════════════════════════
    // Utilities
    // ══════════════════════════════════════════════════════════════════════════════
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

    /**
     * Format minutes as human-readable duration.
     * 0      → "0 min"
     * 45     → "45 min"
     * 90     → "1h 30m"
     * 1440   → "24h 0m"
     */
    function fmtDuration(totalMinutes) {
        const mins = Math.round(totalMinutes ?? 0);
        if (mins === 0) return '<span class="tile-value">0</span><span class="tile-unit"> min</span>';
        if (mins < 60) return `<span class="tile-value">${mins}</span><span class="tile-unit"> min</span>`;
        const h = Math.floor(mins / 60);
        const m = mins % 60;
        return `<span class="tile-value">${h}<span class="dur-h">h</span>${m > 0 ? ` ${m}<span class="dur-m">m</span>` : ''}</span>`;
    }

    function fmtDate(iso) {
        if (!iso) return '';
        const d = new Date(iso);
        return d.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short'
        });
    }

    function periodLabel(period) {
        return {
            today: 'Today',
            week: 'This Week',
            month: 'This Month',
            all: 'All Time'
        } [period] ?? '';
    }
</script>
@endsection