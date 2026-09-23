@extends('layouts.app')

@section('title', 'Trip History')

@section('content')
{{-- ============================================================
     TRIPS / LIVE-TRACKING PAGE
     All CSS and JS inline — layout never calls @stack('scripts')
     ============================================================ --}}
<style>
    /* ── Brand tokens ─────────────────────────────────────────── */
    :root {
        --orange: #FA6908;
        --navy: #021F4A;
        --navy-mid: #1a3a6b;
        --sidebar-w: 304px;
        --strip-h: 44px;
        --tiles-h: 82px;
        --playbar-h: 56px;
        --livebar-h: 48px;
    }

    /* ── Outer grid ───────────────────────────────────────────── */
    .trip-wrap {
        display: grid;
        grid-template-columns: var(--sidebar-w) 1fr;
        height: calc(100vh - 64px);
        /* 64 px = nav bar */
        overflow: hidden;
        background: #f4f6f9;
    }

    /* ═══════════════════════════════════════════════════════════
   SIDEBAR
   ═══════════════════════════════════════════════════════════ */
    .t-sidebar {
        display: flex;
        flex-direction: column;
        background: #fff;
        border-right: 1px solid #e2e8f0;
        overflow: hidden;
    }

    /* Vehicle selector */
    .sb-vehicle-row {
        padding: 12px 14px 10px;
        border-bottom: 1px solid #e2e8f0;
    }

    .sb-vehicle-row label {
        display: block;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: 5px;
    }

    .vehicle-select {
        width: 100%;
        padding: 8px 28px 8px 10px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 13px;
        color: var(--navy);
        background: #f8fafc;
        outline: none;
        -webkit-appearance: none;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3E%3Cpath fill='%236b7280' d='M5 8l5 5 5-5'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 8px center;
        background-size: 16px;
        cursor: pointer;
        transition: border-color .15s;
    }

    .vehicle-select:focus {
        border-color: var(--orange);
        box-shadow: 0 0 0 3px rgba(250, 105, 8, .12);
    }

    .vehicle-select option:disabled {
        color: #94a3b8;
    }

    /* Mode tabs */
    .sb-mode-tabs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        flex-shrink: 0;
    }

    .sb-tab {
        padding: 10px 0;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .04em;
        text-align: center;
        cursor: pointer;
        border: none;
        background: transparent;
        color: #64748b;
        border-bottom: 3px solid transparent;
        transition: color .15s, border-color .15s, background .15s;
    }

    .sb-tab.active {
        color: var(--orange);
        border-bottom-color: var(--orange);
        background: #fff;
    }

    /* Mode panels */
    .sb-panel {
        display: none;
        flex-direction: column;
        flex: 1;
        overflow: hidden;
    }

    .sb-panel.active {
        display: flex;
    }

    /* ── History panel ── */
    .date-range-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
        padding: 10px 12px 0;
    }

    .date-range-row input[type="date"] {
        width: 100%;
        padding: 7px 8px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 12px;
        color: var(--navy);
        background: #f8fafc;
        outline: none;
        transition: border-color .15s;
    }

    .date-range-row input[type="date"]:focus {
        border-color: var(--orange);
    }

    .load-row {
        padding: 8px 12px 10px;
        border-bottom: 1px solid #e2e8f0;
    }

    .btn-load {
        width: 100%;
        padding: 9px 0;
        background: var(--navy);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .04em;
        cursor: pointer;
        transition: background .15s;
    }

    .btn-load:hover {
        background: var(--navy-mid);
    }

    .btn-load:disabled {
        background: #94a3b8;
        cursor: not-allowed;
    }

    /* Grouped trips list */
    .trips-scroll {
        flex: 1;
        overflow-y: auto;
        padding: 6px 0 12px;
    }

    .date-group {
        margin-bottom: 2px;
    }

    .date-group-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 14px 5px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .09em;
        text-transform: uppercase;
        color: #64748b;
        cursor: pointer;
        user-select: none;
        transition: color .12s;
    }

    .date-group-header:hover {
        color: var(--navy);
    }

    .date-group-count {
        background: #e2e8f0;
        color: #475569;
        border-radius: 10px;
        padding: 1px 7px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0;
    }

    .trip-cards {
        padding: 0 8px;
    }

    .trip-card {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 4px;
        padding: 10px 11px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-bottom: 4px;
        cursor: pointer;
        background: #f8fafc;
        transition: all .14s;
    }

    .trip-card:hover {
        background: #fff;
        border-color: #cbd5e1;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .08);
    }

    .trip-card.selected {
        background: #fff7f0;
        border-color: var(--orange);
    }

    .trip-card.live-trip {
        border-left: 3px solid #22c55e;
    }

    .tc-row1 {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .tc-name {
        font-size: 12px;
        font-weight: 700;
        color: var(--navy);
    }

    .tc-time {
        font-size: 11px;
        color: #64748b;
        font-variant-numeric: tabular-nums;
    }

    .tc-row2 {
        display: flex;
        gap: 10px;
        font-size: 11px;
        color: #475569;
        flex-wrap: wrap;
    }

    .tc-stat {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .tc-icon {
        width: 12px;
        height: 12px;
        stroke: #94a3b8;
        stroke-width: 1.75;
        fill: none;
        stroke-linecap: round;
        stroke-linejoin: round;
        flex-shrink: 0;
    }

    .tc-live-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        font-size: 9px;
        font-weight: 700;
        letter-spacing: .06em;
        color: #22c55e;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 10px;
        padding: 1px 6px;
    }

    /* Empty / loading / error */
    .sb-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 36px 16px;
        color: #94a3b8;
        font-size: 12px;
        text-align: center;
        gap: 10px;
    }

    .sb-empty svg {
        width: 40px;
        height: 40px;
        opacity: .25;
    }

    .sb-loading {
        text-align: center;
        padding: 24px;
        color: #64748b;
        font-size: 12px;
    }

    .sb-error {
        margin: 8px 10px;
        padding: 11px 13px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 8px;
        font-size: 12px;
        color: #dc2626;
    }

    /* ── Live panel ── */
    .live-sb-body {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 28px 16px 20px;
        gap: 18px;
    }

    .live-connect-status {
        display: flex;
        align-items: center;
        gap: 9px;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
    }

    .live-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #94a3b8;
        flex-shrink: 0;
        transition: background .3s;
    }

    .live-dot.connected {
        background: #22c55e;
        animation: blink-dot 1.6s infinite;
    }

    .live-dot.connecting {
        background: #f59e0b;
        animation: blink-dot .7s infinite;
    }

    @keyframes blink-dot {

        0%,
        100% {
            opacity: 1
        }

        50% {
            opacity: .35
        }
    }

    .btn-live-connect {
        padding: 10px 32px;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: .04em;
        cursor: pointer;
        background: var(--orange);
        color: #fff;
        transition: background .15s;
    }

    .btn-live-connect:hover {
        background: #e55d00;
    }

    .btn-live-connect.disconnect {
        background: #ef4444;
    }

    .btn-live-connect.disconnect:hover {
        background: #dc2626;
    }

    .btn-live-connect:disabled {
        background: #94a3b8;
        cursor: not-allowed;
    }

    .live-hint {
        font-size: 11px;
        color: #94a3b8;
        text-align: center;
        line-height: 1.6;
    }

    /* ═══════════════════════════════════════════════════════════
   MAIN PANEL
   ═══════════════════════════════════════════════════════════ */
    .t-main {
        display: flex;
        flex-direction: column;
        overflow: hidden;
        position: relative;
    }

    /* Vehicle strip */
    .vehicle-strip {
        display: flex;
        align-items: center;
        gap: 10px;
        height: var(--strip-h);
        padding: 0 18px;
        background: var(--navy);
        flex-shrink: 0;
    }

    .vs-plate {
        font-size: 14px;
        font-weight: 800;
        letter-spacing: .07em;
        color: var(--orange);
    }

    .vs-name {
        font-size: 13px;
        color: #cbd5e1;
        flex: 1;
    }

    .vs-badge {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .08em;
        padding: 2px 9px;
        border-radius: 10px;
        text-transform: uppercase;
    }

    .badge-demo {
        background: #7c3aed;
        color: #fff;
    }

    /* Trip summary tiles */
    .trip-tiles {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        height: var(--tiles-h);
        border-bottom: 1px solid #e2e8f0;
        background: #fff;
        flex-shrink: 0;
        overflow: hidden;
        transition: height .2s ease;
    }

    .trip-tiles.hidden {
        height: 0;
        border-bottom: none;
    }

    .tile {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
        padding: 6px 4px;
        border-right: 1px solid #e2e8f0;
        text-align: center;
    }

    .tile:last-child {
        border-right: none;
    }

    .tile-icon-box {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: rgba(250, 105, 8, .09);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-bottom: 3px;
    }

    .tile-icon-box svg {
        width: 15px;
        height: 15px;
        stroke: var(--orange);
        stroke-width: 1.75;
        fill: none;
        stroke-linecap: round;
        stroke-linejoin: round;
        color: var(--orange);
        /* for fill="currentColor" on dot */
    }

    .tile-value {
        font-size: 15px;
        font-weight: 800;
        color: var(--navy);
        line-height: 1.15;
    }

    .tile-label {
        font-size: 9px;
        color: #94a3b8;
        font-weight: 700;
        letter-spacing: .07em;
        text-transform: uppercase;
    }

    /* Map */
    #map {
        flex: 1;
        min-height: 180px;
    }

    /* Live stats bar */
    .live-bar {
        display: flex;
        align-items: center;
        height: var(--livebar-h);
        padding: 0 18px;
        gap: 28px;
        background: var(--navy);
        border-top: 1px solid var(--navy-mid);
        flex-shrink: 0;
        overflow: hidden;
        transition: height .2s;
    }

    .live-bar.hidden {
        height: 0;
    }

    .ls-item {
        display: flex;
        flex-direction: column;
    }

    .ls-label {
        font-size: 9px;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: .08em;
        font-weight: 600;
    }

    .ls-value {
        font-size: 15px;
        font-weight: 700;
        color: #fff;
        line-height: 1.25;
    }

    .ls-value.orange {
        color: var(--orange);
    }

    .ls-right {
        margin-left: auto;
    }

    /* Playback bar */
    .playback-bar {
        display: flex;
        align-items: center;
        height: var(--playbar-h);
        padding: 0 14px;
        gap: 10px;
        background: var(--navy);
        border-top: 1px solid var(--navy-mid);
        flex-shrink: 0;
        overflow: hidden;
        transition: height .2s;
    }

    .playback-bar.hidden {
        height: 0;
    }

    .pb-play-btn {
        width: 36px;
        height: 36px;
        flex-shrink: 0;
        border: none;
        border-radius: 50%;
        background: var(--orange);
        color: #fff;
        font-size: 13px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background .15s;
    }

    .pb-play-btn:hover {
        background: #e55d00;
    }

    .pb-time {
        font-size: 11px;
        color: #cbd5e1;
        white-space: nowrap;
        flex-shrink: 0;
        min-width: 130px;
        font-variant-numeric: tabular-nums;
    }

    .pb-scrubber {
        flex: 1;
        height: 4px;
        -webkit-appearance: none;
        appearance: none;
        background: rgba(255, 255, 255, .2);
        border-radius: 2px;
        outline: none;
        cursor: pointer;
    }

    .pb-scrubber::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: var(--orange);
        cursor: pointer;
    }

    .pb-scrubber::-moz-range-thumb {
        width: 14px;
        height: 14px;
        border: none;
        border-radius: 50%;
        background: var(--orange);
        cursor: pointer;
    }

    .pb-speeds {
        display: flex;
        gap: 4px;
        flex-shrink: 0;
    }

    .pb-speed-btn {
        padding: 4px 8px;
        background: rgba(255, 255, 255, .12);
        color: #cbd5e1;
        border: none;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        transition: all .14s;
    }

    .pb-speed-btn:hover {
        background: rgba(255, 255, 255, .22);
        color: #fff;
    }

    .pb-speed-btn.active {
        background: var(--orange);
        color: #fff;
    }

    /* ── Responsive ── */
    @media (max-width: 820px) {
        .trip-wrap {
            grid-template-columns: 1fr;
            grid-template-rows: 260px 1fr;
        }

        .t-sidebar {
            height: 260px;
            border-right: none;
            border-bottom: 1px solid #e2e8f0;
        }

        .trip-tiles {
            grid-template-columns: repeat(3, 1fr);
            height: auto;
        }

        .tile:nth-child(4),
        .tile:nth-child(5) {
            display: none;
        }
    }
</style>

{{-- ══════════════════════════════════════════════════════════
     LAYOUT
     ══════════════════════════════════════════════════════════ --}}
<div class="trip-wrap">

    {{-- ─── SIDEBAR ─────────────────────────────────────────── --}}
    <aside class="t-sidebar">

        {{-- Vehicle selector --}}
        <div class="sb-vehicle-row">
            <label>Vehicle</label>
            <select id="vehicle-select" class="vehicle-select" onchange="onVehicleChange()">
                @foreach($vehicles as $v)
                @php
                $hasGps = (bool)($v['hasGpsDevice'] ?? false) || (bool)($v['isDemoVehicle'] ?? false);
                $isDemo = (bool)($v['isDemoVehicle'] ?? false);
                $plate = $v['vehicleNumber'] ?? $v['vehicleId'] ?? 'Vehicle';
                $make = trim(($v['make'] ?? '') . ' ' . ($v['model'] ?? ''));
                $vid = $v['vehicleId'] ?? '';
                $label = $plate . ($isDemo ? ' [DEMO]' : '') . ($make ? ' — ' . $make : '');
                @endphp
                <option
                    value="{{ $vid }}"
                    data-plate="{{ $plate }}"
                    data-name="{{ $make }}"
                    data-demo="{{ $isDemo ? '1' : '0' }}"
                    @if(!$hasGps) disabled @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Mode tabs: History | Live --}}
        <div class="sb-mode-tabs">
            <button id="tab-history" class="sb-tab active" onclick="setMode('history')">History</button>
            <button id="tab-live" class="sb-tab" onclick="setMode('live')">Live</button>
        </div>

        {{-- ── HISTORY PANEL ── --}}
        <div id="panel-history" class="sb-panel active">
            <div class="date-range-row">
                <input type="date" id="date-from" title="From">
                <input type="date" id="date-to" title="To">
            </div>
            <div class="load-row">
                <button id="btn-load" class="btn-load" onclick="loadTrips()">Load Trips</button>
            </div>

            <div id="trips-scroll" class="trips-scroll">
                <div class="sb-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z" />
                    </svg>
                    Pick a date range and tap Load
                </div>
            </div>
        </div>

        {{-- ── LIVE PANEL ── --}}
        <div id="panel-live" class="sb-panel">
            <div class="live-sb-body">
                <div class="live-connect-status">
                    <div id="live-dot" class="live-dot"></div>
                    <span id="live-status-text">Disconnected</span>
                </div>
                <button id="btn-live-connect" class="btn-live-connect" onclick="toggleLive()">Connect</button>
                <p class="live-hint">Streams real-time GPS for<br>the selected vehicle.</p>
            </div>
        </div>

    </aside>

    {{-- ─── MAIN PANEL ──────────────────────────────────────── --}}
    <main class="t-main">

        {{-- Vehicle strip --}}
        <div class="vehicle-strip">
            <span id="vs-plate" class="vs-plate">–</span>
            <span id="vs-name" class="vs-name">Select a vehicle</span>
            <span id="vs-badge" class="vs-badge"></span>
        </div>

        {{-- Trip summary tiles (hidden until a trip is selected) --}}
        <div id="trip-tiles" class="trip-tiles hidden">
            {{-- Distance --}}
            <div class="tile">
                <div class="tile-icon-box">
                    <svg viewBox="0 0 20 20">
                        <path d="M4 10h12M11.5 6.5L15 10l-3.5 3.5M8.5 6.5L5 10l3.5 3.5" />
                    </svg>
                </div>
                <div class="tile-value" id="tile-distance">–</div>
                <div class="tile-label">Distance</div>
            </div>
            {{-- Duration --}}
            <div class="tile">
                <div class="tile-icon-box">
                    <svg viewBox="0 0 20 20">
                        <circle cx="10" cy="10" r="7" />
                        <path d="M10 6.5V10l2.5 2" />
                    </svg>
                </div>
                <div class="tile-value" id="tile-duration">–</div>
                <div class="tile-label">Duration</div>
            </div>
            {{-- Max Speed --}}
            <div class="tile">
                <div class="tile-icon-box">
                    <svg viewBox="0 0 20 20">
                        <path d="M4.5 15.5A7.5 7.5 0 0 1 15.5 15.5" />
                        <circle cx="10" cy="10" r="1.25" fill="currentColor" stroke="none" />
                        <path d="M10 10 8 6.5" />
                    </svg>
                </div>
                <div class="tile-value" id="tile-max-speed">–</div>
                <div class="tile-label">Max Speed</div>
            </div>
            {{-- Avg Speed --}}
            <div class="tile">
                <div class="tile-icon-box">
                    <svg viewBox="0 0 20 20">
                        <path d="M3 14.5l4.5-5 3.5 3 5-6.5" />
                        <path d="M13.5 6H16v2.5" />
                    </svg>
                </div>
                <div class="tile-value" id="tile-avg-speed">–</div>
                <div class="tile-label">Avg Speed</div>
            </div>
            {{-- Stops --}}
            <div class="tile">
                <div class="tile-icon-box">
                    <svg viewBox="0 0 20 20">
                        <rect x="3.5" y="3.5" width="13" height="13" rx="2.5" />
                        <path d="M8 13.5V6.5h3a2.5 2.5 0 0 1 0 5H8" />
                    </svg>
                </div>
                <div class="tile-value" id="tile-stops">–</div>
                <div class="tile-label">Stops</div>
            </div>
        </div>

        {{-- Google Map --}}
        <div id="map"></div>

        {{-- Live stats bar --}}
        <div id="live-bar" class="live-bar hidden">
            <div class="ls-item">
                <span class="ls-label">Speed</span>
                <span class="ls-value orange" id="ls-speed">– km/h</span>
            </div>
            <div class="ls-item">
                <span class="ls-label">Heading</span>
                <span class="ls-value" id="ls-heading">–°</span>
            </div>
            <div class="ls-item">
                <span class="ls-label">Ignition</span>
                <span class="ls-value" id="ls-ignition">–</span>
            </div>
            <div class="ls-item">
                <span class="ls-label">Last update</span>
                <span class="ls-value" id="ls-updated">–</span>
            </div>
            <div class="ls-item ls-right">
                <span class="ls-label">SignalR</span>
                <span class="ls-value orange" id="ls-conn">Disconnected</span>
            </div>
        </div>

        {{-- Playback bar (shown when a history trip is loaded) --}}
        <div id="playback-bar" class="playback-bar hidden">
            <button id="pb-play" class="pb-play-btn" onclick="togglePlay()" title="Play / Pause">▶</button>
            <span id="pb-time" class="pb-time">– / –</span>
            <input id="pb-scrubber" class="pb-scrubber" type="range" min="0" value="0"
                oninput="onScrub(this.value)">
            <div class="pb-speeds">
                <button class="pb-speed-btn active" onclick="setSpeed(this,1)">1×</button>
                <button class="pb-speed-btn" onclick="setSpeed(this,2)">2×</button>
                <button class="pb-speed-btn" onclick="setSpeed(this,5)">5×</button>
                <button class="pb-speed-btn" onclick="setSpeed(this,10)">10×</button>
            </div>
        </div>

    </main>
</div>

{{-- ══════════════════════════════════════════════════════════
     JAVASCRIPT  — all inside @section so layout renders it
     ══════════════════════════════════════════════════════════ --}}
<script>
    /* ── Constants ─────────────────────────────────────────────── */
    const CSRF_TOKEN = '{{ csrf_token() }}';
    const ORANGE = '#FA6908';
    const NAVY = '#021F4A';

    /* ── Map layer references ───────────────────────────────────── */
    let map = null;
    let liveMarker = null; // animated arrow for SignalR live
    let playMarker = null; // animated arrow for history playback
    let startMarker = null; // green circle
    let endMarker = null; // red circle
    let routePolyline = null;
    let stopMarkers = []; // orange/navy dots

    /* ── State ──────────────────────────────────────────────────── */
    let currentVehicleId = '';
    let currentVehiclePlate = '';
    let currentVehicleName = '';
    let currentVehicleDemo = false;

    let allTrips = []; // loaded from summary endpoint
    let tripPoints = []; // GPS points for selected trip
    let currentTrip = null; // selected trip summary object
    let selectedCardEl = null;

    /* ── Playback ───────────────────────────────────────────────── */
    let isPlaying = false;
    let playIdx = 0;
    let playSpeed = 1;
    let playTimer = null;
    const TICK_MS = 200;

    /* ── SignalR / Live ─────────────────────────────────────────── */
    let signalrConn = null;
    let liveActive = false;

    /* ══════════════════════════════════════════════════════════════
       GOOGLE MAPS INIT — called by the API script's &callback=
       ══════════════════════════════════════════════════════════════ */
    function initMap() {
        map = new google.maps.Map(document.getElementById('map'), {
            center: {
                lat: 6.9271,
                lng: 79.8612
            }, // default: Colombo
            zoom: 11,
            mapTypeId: 'roadmap',
            gestureHandling: 'greedy',
            streetViewControl: false,
            mapTypeControl: false,
            fullscreenControl: true,
            zoomControl: true,
            styles: [{
                    featureType: 'poi',
                    elementType: 'labels',
                    stylers: [{
                        visibility: 'off'
                    }]
                },
                {
                    featureType: 'transit',
                    elementType: 'labels.icon',
                    stylers: [{
                        visibility: 'off'
                    }]
                },
            ],
        });

        // Apply initial vehicle from the <select>
        const sel = document.getElementById('vehicle-select');
        if (sel && sel.value) applyVehicleFromSelect();
    }

    /* ══════════════════════════════════════════════════════════════
       VEHICLE SELECTION
       ══════════════════════════════════════════════════════════════ */
    function applyVehicleFromSelect() {
        const sel = document.getElementById('vehicle-select');
        const opt = sel.options[sel.selectedIndex];
        if (!opt || !opt.value) return;

        currentVehicleId = opt.value;
        currentVehiclePlate = opt.dataset.plate || opt.value;
        currentVehicleName = opt.dataset.name || '';
        currentVehicleDemo = opt.dataset.demo === '1';

        // Update vehicle strip
        document.getElementById('vs-plate').textContent = currentVehiclePlate;
        document.getElementById('vs-name').textContent = currentVehicleName;
        const badge = document.getElementById('vs-badge');
        if (currentVehicleDemo) {
            badge.textContent = 'DEMO';
            badge.className = 'vs-badge badge-demo';
        } else {
            badge.textContent = '';
            badge.className = 'vs-badge';
        }

        // Clear previous state
        resetHistoryState();
        if (liveActive) stopLive();
    }

    function onVehicleChange() {
        applyVehicleFromSelect();
    }

    /* ══════════════════════════════════════════════════════════════
       MODE SWITCHING  (History ↔ Live)
       ══════════════════════════════════════════════════════════════ */
    function setMode(mode) {
        const isHistory = mode === 'history';

        document.getElementById('panel-history').classList.toggle('active', isHistory);
        document.getElementById('panel-live').classList.toggle('active', !isHistory);
        document.getElementById('tab-history').classList.toggle('active', isHistory);
        document.getElementById('tab-live').classList.toggle('active', !isHistory);

        if (isHistory) {
            if (liveActive) stopLive();
            hideLiveBar();
        } else {
            // switching to live: clear history overlays
            resetHistoryState();
            hideTiles();
            hidePlaybackBar();
        }
    }

    /* ══════════════════════════════════════════════════════════════
       LOAD TRIPS  (summary API → date-grouped sidebar)
       ══════════════════════════════════════════════════════════════ */
    async function loadTrips() {
        if (!currentVehicleId) {
            alert('Please select a vehicle first.');
            return;
        }

        const fromDate = document.getElementById('date-from').value;
        const toDate = document.getElementById('date-to').value;
        if (!fromDate || !toDate) {
            alert('Please select both From and To dates.');
            return;
        }
        if (fromDate > toDate) {
            alert('From date must be before To date.');
            return;
        }

        const fromDt = fromDate + 'T00:00';
        const toDt = toDate + 'T23:59';

        const btn = document.getElementById('btn-load');
        btn.disabled = true;
        btn.textContent = 'Loading…';

        document.getElementById('trips-scroll').innerHTML =
            '<div class="sb-loading">Loading trips…</div>';

        resetHistoryState();
        hideTiles();

        try {
            const url = `/trips/${currentVehicleId}/summary?from=${encodeURIComponent(fromDt)}&to=${encodeURIComponent(toDt)}`;
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
            });

            if (res.status === 401) {
                window.location.href = '/login?expired=1';
                return;
            }

            const json = await res.json();
            if (json.expired) {
                window.location.href = '/login?expired=1';
                return;
            }
            if (!json.success) {
                showSbError(json.message || 'Could not load trips.');
                return;
            }

            allTrips = json.data?.trips ?? json.data ?? [];
            renderGroupedTrips(allTrips);

        } catch (e) {
            console.error('loadTrips:', e);
            showSbError('Network error. Please try again.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Load Trips';
        }
    }

    /* ══════════════════════════════════════════════════════════════
       RENDER DATE-GROUPED TRIP LIST
       ══════════════════════════════════════════════════════════════ */
    function renderGroupedTrips(trips) {
        const container = document.getElementById('trips-scroll');

        if (!trips || !trips.length) {
            container.innerHTML = `
          <div class="sb-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
            </svg>
            No trips found for this date range.
          </div>`;
            return;
        }

        const todayMs = new Date().setHours(0, 0, 0, 0);
        const yestMs = todayMs - 864e5;

        // Attach original index, then sort newest → oldest
        const indexed = trips.map((t, i) => ({
            ...t,
            _i: i
        }));
        indexed.sort((a, b) => new Date(b.startTime) - new Date(a.startTime));

        // Build date-label → trips map (preserving insertion order = newest day first)
        const groups = new Map();
        indexed.forEach(trip => {
            const d = new Date(trip.startTime);
            const dayMs = new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
            let label;
            if (dayMs === todayMs) label = 'Today · ' + d.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short'
            });
            else if (dayMs === yestMs) label = 'Yesterday · ' + d.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short'
            });
            else label = d.toLocaleDateString('en-GB', {
                weekday: 'short',
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });

            if (!groups.has(label)) groups.set(label, []);
            groups.get(label).push(trip);
        });

        // Sort within each date group oldest → newest (Trip 1 = first of day)
        groups.forEach(g => g.sort((a, b) => new Date(a.startTime) - new Date(b.startTime)));

        // Build HTML
        let html = '';
        let groupIdx = 0;
        groups.forEach((groupTrips, dateLabel) => {
            const gid = 'dg' + (groupIdx++);
            html += `
        <div class="date-group">
          <div class="date-group-header" onclick="toggleGroup('${gid}')">
            <span>${escHtml(dateLabel)}</span>
            <span class="date-group-count">${groupTrips.length}</span>
          </div>
          <div id="${gid}" class="trip-cards">`;

            groupTrips.forEach((trip, localN) => {
                const tripNum = localN + 1;
                const start = fmtTime(trip.startTime);
                const end = trip.inProgress ? 'In Progress' : fmtTime(trip.endTime);
                const dist = trip.distanceKm ? trip.distanceKm.toFixed(1) + ' km' : '–';
                const dur = trip.durationMinutes ? fmtDuration(trip.durationMinutes) : '–';
                const mxSpd = trip.maxSpeed ? trip.maxSpeed.toFixed(0) + ' km/h' : '';

                html += `
            <div id="tc-${trip._i}" class="trip-card${trip.inProgress ? ' live-trip' : ''}"
                 onclick="selectTrip(${trip._i})">
              <div class="tc-row1">
                <span class="tc-name">Trip ${tripNum}</span>
                <span class="tc-time">${escHtml(start)} → ${escHtml(end)}</span>
              </div>
              <div class="tc-row2">
                <span class="tc-stat">
                  <svg class="tc-icon" viewBox="0 0 16 16"><path d="M2 8h12M10 5l3 3-3 3"/></svg>
                  ${dist}
                </span>
                <span class="tc-stat">
                  <svg class="tc-icon" viewBox="0 0 16 16"><circle cx="8" cy="8" r="5.5"/><path d="M8 5.5V8l2 1.5"/></svg>
                  ${dur}
                </span>
                ${mxSpd ? `<span class="tc-stat">
                  <svg class="tc-icon" viewBox="0 0 16 16"><path d="M9 3l-4 5h4l-3 5"/></svg>
                  ${mxSpd}
                </span>` : ''}
              </div>
              ${trip.inProgress ? '<span class="tc-live-badge">LIVE</span>' : ''}
            </div>`;
            });

            html += `</div></div>`;
        });

        container.innerHTML = html;
    }

    function toggleGroup(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = el.style.display === 'none' ? '' : 'none';
    }

    /* ══════════════════════════════════════════════════════════════
       SELECT TRIP → load GPS points, render on map
       ══════════════════════════════════════════════════════════════ */
    async function selectTrip(origIdx) {
        const trip = allTrips[origIdx];
        if (!trip) return;

        // Highlight card
        if (selectedCardEl) selectedCardEl.classList.remove('selected');
        selectedCardEl = document.getElementById('tc-' + origIdx);
        if (selectedCardEl) selectedCardEl.classList.add('selected');

        currentTrip = trip;

        // Show summary tiles immediately (stops shown after detectStops)
        showTiles(trip);

        // Clear old map overlays and stop any playback
        resetHistoryState();
        stopPlayback();

        // Build time range for points query
        const fromDt = toLocalDt(trip.startTime);
        const toDt = trip.inProgress ?
            toLocalDt(new Date().toISOString()) :
            toLocalDt(trip.endTime);

        try {
            const url = `/trips/${currentVehicleId}/points?from=${encodeURIComponent(fromDt)}&to=${encodeURIComponent(toDt)}`;
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
            });

            if (res.status === 401) {
                window.location.href = '/login?expired=1';
                return;
            }
            const json = await res.json();
            if (json.expired) {
                window.location.href = '/login?expired=1';
                return;
            }

            const pts = json.data ?? [];
            if (!json.success || !pts.length) {
                showSbError('No GPS points found for this trip.');
                return;
            }

            // Sort ascending by eventTime — API may return newest-first
            tripPoints = [...pts].sort((a, b) => new Date(a.eventTime) - new Date(b.eventTime));
            renderHistoryOnMap(tripPoints, currentTrip);
            initPlaybackBar(tripPoints.length);

        } catch (e) {
            console.error('selectTrip:', e);
            showSbError('Could not load GPS data for this trip.');
        }
    }

    /* ══════════════════════════════════════════════════════════════
       RENDER HISTORY ON MAP
       ══════════════════════════════════════════════════════════════ */
    function renderHistoryOnMap(points, trip) {
        if (!map || !points.length) return;

        const path = points.map(p => ({
            lat: +p.latitude,
            lng: +p.longitude
        }));

        /* ── Route polyline ── */
        routePolyline = new google.maps.Polyline({
            path,
            geodesic: true,
            strokeColor: ORANGE,
            strokeOpacity: .88,
            strokeWeight: 4,
            map,
        });

        /* ── Start marker (green) ── */
        startMarker = new google.maps.Marker({
            position: path[0],
            map,
            title: 'Trip Start',
            zIndex: 12,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 9,
                fillColor: '#22c55e',
                fillOpacity: 1,
                strokeColor: '#fff',
                strokeWeight: 2.5,
            },
        });
        startMarker.addListener('click', () => {
            new google.maps.InfoWindow({
                content: `<div style="font-size:12px;padding:4px 8px"><strong>Start</strong><br>${escHtml(fmtDateTime(points[0].eventTime))}</div>`,
            }).open(map, startMarker);
        });

        /* ── End marker (red) ── */
        if (!trip?.inProgress && path.length > 1) {
            endMarker = new google.maps.Marker({
                position: path[path.length - 1],
                map,
                title: 'Trip End',
                zIndex: 12,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 9,
                    fillColor: '#ef4444',
                    fillOpacity: 1,
                    strokeColor: '#fff',
                    strokeWeight: 2.5,
                },
            });
            endMarker.addListener('click', () => {
                new google.maps.InfoWindow({
                    content: `<div style="font-size:12px;padding:4px 8px"><strong>End</strong><br>${escHtml(fmtDateTime(points[points.length - 1].eventTime))}</div>`,
                }).open(map, endMarker);
            });
        }

        /* ── Playback arrow marker ── */
        playMarker = new google.maps.Marker({
            position: path[0],
            map,
            title: 'Vehicle',
            zIndex: 20,
            icon: makeArrowIcon(points[0].heading || 0),
        });

        /* ── Stop markers ── */
        const stops = detectStops(points);
        renderStopMarkers(stops);
        document.getElementById('tile-stops').textContent = stops.length;

        /* ── Fit bounds ── */
        const bounds = new google.maps.LatLngBounds();
        path.forEach(p => bounds.extend(p));
        map.fitBounds(bounds, 40);
    }

    /* ── Stop detection (client-side from GPS points) ── */
    function detectStops(points) {
        const STOP_KMH = 2; // ≤ 2 km/h = stopped
        const MIN_SECS = 60; // at least 60 s to count as a stop
        const MIN_PTS = 3; // at least 3 consecutive slow points

        const stops = [];
        let runStart = null;

        for (let i = 0; i < points.length; i++) {
            const slow = (+(points[i].speed) || 0) <= STOP_KMH;
            if (slow) {
                if (runStart === null) runStart = i;
            } else {
                if (runStart !== null) {
                    const n = i - runStart;
                    const durMs = new Date(points[i - 1].eventTime) - new Date(points[runStart].eventTime);
                    const durSecs = durMs / 1000;
                    if (n >= MIN_PTS && durSecs >= MIN_SECS) {
                        const mid = Math.floor((runStart + i) / 2);
                        stops.push({
                            lat: +points[mid].latitude,
                            lng: +points[mid].longitude,
                            durationMin: Math.round(durSecs / 60),
                        });
                    }
                    runStart = null;
                }
            }
        }
        return stops;
    }

    function renderStopMarkers(stops) {
        stopMarkers.forEach(m => m.setMap(null));
        stopMarkers = [];

        stops.forEach(stop => {
            const m = new google.maps.Marker({
                position: {
                    lat: stop.lat,
                    lng: stop.lng
                },
                map,
                title: 'Stop — ' + fmtDuration(stop.durationMin),
                zIndex: 8,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 6,
                    fillColor: NAVY,
                    fillOpacity: .9,
                    strokeColor: '#fff',
                    strokeWeight: 2,
                },
            });

            const iw = new google.maps.InfoWindow({
                content: `<div style="font-size:12px;padding:4px 8px">
                <strong>Stop</strong><br>Duration: ${escHtml(fmtDuration(stop.durationMin))}
              </div>`,
            });
            m.addListener('click', () => iw.open(map, m));
            stopMarkers.push(m);
        });
    }

    /* ══════════════════════════════════════════════════════════════
       SUMMARY TILES
       ══════════════════════════════════════════════════════════════ */
    function showTiles(trip) {
        document.getElementById('tile-distance').textContent = trip.distanceKm ? trip.distanceKm.toFixed(1) + ' km' : '–';
        document.getElementById('tile-duration').textContent = trip.durationMinutes ? fmtDuration(trip.durationMinutes) : '–';
        document.getElementById('tile-max-speed').textContent = trip.maxSpeed ? trip.maxSpeed.toFixed(0) + ' km/h' : '–';
        document.getElementById('tile-avg-speed').textContent = trip.avgSpeed ? trip.avgSpeed.toFixed(0) + ' km/h' : '–';
        document.getElementById('tile-stops').textContent = '…';
        document.getElementById('trip-tiles').classList.remove('hidden');
    }

    function hideTiles() {
        document.getElementById('trip-tiles').classList.add('hidden');
    }

    /* ══════════════════════════════════════════════════════════════
       PLAYBACK ENGINE
       ══════════════════════════════════════════════════════════════ */
    function initPlaybackBar(totalPoints) {
        const scrubber = document.getElementById('pb-scrubber');
        scrubber.max = Math.max(0, totalPoints - 1);
        scrubber.value = 0;
        playIdx = 0;
        document.getElementById('playback-bar').classList.remove('hidden');
        updatePbTime();
    }

    function hidePlaybackBar() {
        document.getElementById('playback-bar').classList.add('hidden');
    }

    function togglePlay() {
        isPlaying ? pausePlayback() : startPlayback();
    }

    function startPlayback() {
        if (!tripPoints.length) return;
        if (playIdx >= tripPoints.length - 1) playIdx = 0; // restart
        isPlaying = true;
        document.getElementById('pb-play').textContent = '⏸';
        clearInterval(playTimer);
        playTimer = setInterval(advanceTick, TICK_MS / playSpeed);
    }

    function pausePlayback() {
        isPlaying = false;
        document.getElementById('pb-play').textContent = '▶';
        clearInterval(playTimer);
        playTimer = null;
    }

    function stopPlayback() {
        pausePlayback();
        playIdx = 0;
    }

    function advanceTick() {
        if (playIdx >= tripPoints.length - 1) {
            pausePlayback();
            return;
        }
        playIdx++;
        applyPlayPosition(playIdx);
    }

    function onScrub(val) {
        playIdx = parseInt(val, 10);
        applyPlayPosition(playIdx);
    }

    function applyPlayPosition(idx) {
        const pt = tripPoints[idx];
        if (!pt || !playMarker) return;

        playMarker.setPosition({
            lat: +pt.latitude,
            lng: +pt.longitude
        });
        playMarker.setIcon(makeArrowIcon(pt.heading || 0));

        document.getElementById('pb-scrubber').value = idx;
        updatePbTime();
    }

    function updatePbTime() {
        const fmtPt = i => (tripPoints[i]?.eventTime) ?
            new Date(tripPoints[i].eventTime).toLocaleTimeString('en-GB', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            }) :
            '–';
        document.getElementById('pb-time').textContent =
            fmtPt(playIdx) + ' / ' + fmtPt(tripPoints.length - 1);
    }

    function setSpeed(btn, speed) {
        playSpeed = speed;
        document.querySelectorAll('.pb-speed-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        if (isPlaying) {
            clearInterval(playTimer);
            playTimer = setInterval(advanceTick, TICK_MS / playSpeed);
        }
    }

    /* ══════════════════════════════════════════════════════════════
       LIVE TRACKING  (SignalR)
       ══════════════════════════════════════════════════════════════ */
    function toggleLive() {
        liveActive ? stopLive() : startLive();
    }

    async function startLive() {
        if (!currentVehicleId) {
            alert('Select a vehicle first.');
            return;
        }

        setLiveStatus('connecting', 'Connecting…');
        const btn = document.getElementById('btn-live-connect');
        btn.disabled = true;

        try {
            const tokenRes = await fetch('/api/signalr-token', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
            });
            const tokenJson = await tokenRes.json();
            const token = tokenJson.token || tokenJson.data?.token;
            if (!token) throw new Error('No SignalR token returned.');

            signalrConn = new signalR.HubConnectionBuilder()
                .withUrl('https://api.shalotrack.com/hubs/location', {
                    accessTokenFactory: () => token,
                })
                .withAutomaticReconnect([0, 2000, 5000, 10000])
                .configureLogging(signalR.LogLevel.Warning)
                .build();

            signalrConn.on('LocationUpdate', onLiveUpdate);
            signalrConn.onreconnecting(() => setLiveStatus('connecting', 'Reconnecting…'));
            signalrConn.onreconnected(() => {
                setLiveStatus('connected', 'Connected');
            });
            signalrConn.onclose(() => {
                liveActive = false;
                setLiveStatus('disconnected', 'Disconnected');
                setLiveConnBtn(false);
            });

            await signalrConn.start();
            await signalrConn.invoke('Subscribe', currentVehicleId);

            liveActive = true;
            setLiveStatus('connected', 'Connected');
            setLiveConnBtn(true);
            document.getElementById('live-bar').classList.remove('hidden');

        } catch (e) {
            console.error('SignalR start:', e);
            setLiveStatus('disconnected', 'Failed — ' + e.message.slice(0, 40));
        } finally {
            btn.disabled = false;
        }
    }

    async function stopLive() {
        if (signalrConn) {
            try {
                await signalrConn.invoke('Unsubscribe', currentVehicleId);
            } catch (_) {}
            try {
                await signalrConn.stop();
            } catch (_) {}
            signalrConn = null;
        }
        liveActive = false;
        setLiveStatus('disconnected', 'Disconnected');
        setLiveConnBtn(false);
        hideLiveBar();
        if (liveMarker) {
            liveMarker.setMap(null);
            liveMarker = null;
        }
    }

    function onLiveUpdate(data) {
        if (!map) return;
        const lat = +(data.latitude ?? data.lat ?? 0);
        const lng = +(data.longitude ?? data.lng ?? data.lon ?? 0);
        if (!lat || !lng) return;

        const pos = {
            lat,
            lng
        };
        const heading = data.heading || 0;

        if (!liveMarker) {
            liveMarker = new google.maps.Marker({
                position: pos,
                map,
                title: currentVehiclePlate,
                zIndex: 50,
                icon: makeArrowIcon(heading, 6),
            });
            map.panTo(pos);
            if (map.getZoom() < 14) map.setZoom(15);
        } else {
            liveMarker.setPosition(pos);
            liveMarker.setIcon(makeArrowIcon(heading, 6));
        }

        document.getElementById('ls-speed').textContent = (+(data.speed || 0)).toFixed(1) + ' km/h';
        document.getElementById('ls-heading').textContent = heading.toFixed(0) + '°';
        document.getElementById('ls-ignition').textContent = data.ignitionOn ? 'ON' : 'OFF';
        document.getElementById('ls-updated').textContent = new Date().toLocaleTimeString('en-GB');
    }

    function setLiveStatus(state, text) {
        const dot = document.getElementById('live-dot');
        const span = document.getElementById('live-status-text');
        dot.className = 'live-dot' + (state === 'connected' ? ' connected' : state === 'connecting' ? ' connecting' : '');
        span.textContent = text;
        document.getElementById('ls-conn').textContent = text;
    }

    function setLiveConnBtn(connected) {
        const btn = document.getElementById('btn-live-connect');
        btn.textContent = connected ? 'Disconnect' : 'Connect';
        btn.className = 'btn-live-connect' + (connected ? ' disconnect' : '');
    }

    function hideLiveBar() {
        document.getElementById('live-bar').classList.add('hidden');
    }

    /* ══════════════════════════════════════════════════════════════
       RESET
       ══════════════════════════════════════════════════════════════ */
    function resetHistoryState() {
        if (routePolyline) {
            routePolyline.setMap(null);
            routePolyline = null;
        }
        if (startMarker) {
            startMarker.setMap(null);
            startMarker = null;
        }
        if (endMarker) {
            endMarker.setMap(null);
            endMarker = null;
        }
        if (playMarker) {
            playMarker.setMap(null);
            playMarker = null;
        }
        stopMarkers.forEach(m => m.setMap(null));
        stopMarkers = [];
        tripPoints = [];
        currentTrip = null;
    }

    /* ══════════════════════════════════════════════════════════════
       HELPERS
       ══════════════════════════════════════════════════════════════ */
    function makeArrowIcon(heading, scale) {
        return {
            path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
            scale: scale || 5,
            fillColor: ORANGE,
            fillOpacity: 1,
            strokeColor: '#fff',
            strokeWeight: 1.5,
            rotation: heading,
        };
    }

    function fmtTime(dtStr) {
        if (!dtStr) return '–';
        return new Date(dtStr).toLocaleTimeString('en-GB', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function fmtDateTime(dtStr) {
        if (!dtStr) return '–';
        return new Date(dtStr).toLocaleString('en-GB', {
            day: '2-digit',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function fmtDuration(minutes) {
        if (minutes === null || minutes === undefined) return '–';
        const h = Math.floor(minutes / 60);
        const m = Math.round(minutes % 60);
        return h > 0 ? `${h}h ${m}m` : `${m}m`;
    }

    function toLocalDt(dtStr) {
        // Normalise to YYYY-MM-DDTHH:MM (what the controller expects)
        return new Date(dtStr).toISOString().slice(0, 16);
    }

    function escHtml(s) {
        return String(s).replace(/[&<>"']/g, c =>
            ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [c]));
    }

    function showSbError(msg) {
        document.getElementById('trips-scroll').innerHTML =
            `<div class="sb-error">⚠ ${escHtml(msg)}</div>`;
    }

    /* ══════════════════════════════════════════════════════════════
       PAGE INIT
       ══════════════════════════════════════════════════════════════ */
    document.addEventListener('DOMContentLoaded', () => {
        // Default date range: last 7 days
        const today = new Date();
        const from = new Date();
        from.setDate(today.getDate() - 6);
        const fmt = d => d.toISOString().slice(0, 10);

        document.getElementById('date-from').value = fmt(from);
        document.getElementById('date-to').value = fmt(today);
    });
</script>

{{-- ── Google Maps API (defined AFTER initMap is declared above) ── --}}
<script async defer
    src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&callback=initMap">
</script>

{{-- ── SignalR CDN ── --}}
<script src="https://cdn.jsdelivr.net/npm/@microsoft/signalr@8.0.7/dist/browser/signalr.min.js"></script>

@endsection