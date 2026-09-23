@extends('layouts.app')
@section('title', 'Tracking — ShaloTrack Fleet')
@section('page-title', 'Tracking')

@section('content')

@if($error)
<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-3">
    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    {{ $error }}
</div>
@endif

{{-- ── Main layout: sidebar + right panel ───────────────────────────────── --}}
<div class="flex gap-5" style="min-height: calc(100vh - 120px);">

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- LEFT: Vehicle cards sidebar                                        --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div class="flex-shrink-0 w-72 bg-white rounded-xl border border-gray-100 shadow-sm flex flex-col">

        {{-- Header --}}
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Vehicles</h3>
            @if(count($vehicles) > 0)
            <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">
                {{ collect($vehicles)->where('hasGpsDevice', true)->count() }} GPS
            </span>
            @endif
        </div>

        {{-- Vehicle card list --}}
        <div class="flex-1 overflow-y-auto divide-y divide-gray-50" id="vehicle-list-scroll">

            @forelse($vehicles as $v)
            @php
            $vid = $v['vehicleId'] ?? '';
            $isDemo = (bool)($v['isDemoVehicle'] ?? false);
            $hasGps = (bool)($v['hasGpsDevice'] ?? false) || $isDemo;
            $num = $v['vehicleNumber'] ?? '—';
            $make = trim(($v['make'] ?? '') . ' ' . ($v['model'] ?? ''));
            $year = $v['year'] ?? '';
            @endphp

            @if($hasGps)
            <div id="vcard-{{ $vid }}"
                class="vehicle-card px-4 py-3.5 cursor-pointer hover:bg-orange-50 transition-colors select-none {{ $isDemo ? 'bg-orange-50/40 border-l-2 border-l-[#FA6908]' : '' }}"
                onclick="selectVehicle('{{ $vid }}')"
                data-vid="{{ $vid }}">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <p class="font-semibold text-gray-800 text-sm truncate">{{ $num }}</p>
                            @if($isDemo)
                            <span class="inline-flex items-center text-[10px] font-bold text-[#FA6908] bg-orange-50 border border-orange-200 px-1.5 py-px rounded-full uppercase tracking-wide leading-none">Demo</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $make }}{{ $year ? ' · '.$year : '' }}</p>
                        @if($isDemo)
                        <p class="text-xs text-[#FA6908] mt-0.5 font-medium">Try live tracking & history →</p>
                        @endif
                    </div>
                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-green-600 bg-green-50 border border-green-200 px-1.5 py-0.5 rounded-full flex-shrink-0 mt-0.5">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>GPS
                    </span>
                </div>
            </div>
            @else
            <div class="vehicle-card px-4 py-3.5 opacity-50 cursor-not-allowed select-none" data-vid="{{ $vid }}">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-medium text-gray-500 text-sm truncate">{{ $num }}</p>
                        <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $make }}{{ $year ? ' · '.$year : '' }}</p>
                        <p class="text-xs text-gray-400 mt-1">No GPS device linked</p>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[10px] font-medium text-gray-400 bg-gray-100 border border-gray-200 px-1.5 py-0.5 rounded-full flex-shrink-0 mt-0.5">No GPS</span>
                </div>
            </div>
            @endif

            @empty
            <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
                <svg class="w-12 h-12 text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
                <p class="text-gray-400 text-sm font-medium">No vehicles</p>
                <a href="/vehicles" class="mt-4 px-4 py-2 text-xs font-semibold text-[#FA6908] border border-[#FA6908] rounded-lg hover:bg-orange-50 transition">
                    Go to Vehicles
                </a>
            </div>
            @endforelse

        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- RIGHT: Map + Controls                                              --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div class="flex-1 flex flex-col gap-4 min-w-0">

        {{-- No vehicle selected --}}
        <div id="right-empty"
            class="flex-1 bg-white rounded-xl border border-gray-100 shadow-sm flex flex-col items-center justify-center py-20 text-center">
            <svg class="w-16 h-16 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <p class="text-gray-400 font-medium text-sm mb-1">Select a vehicle</p>
            <p class="text-gray-300 text-xs">Choose a GPS-enabled vehicle from the list to start tracking.</p>
        </div>

        {{-- Vehicle selected panel --}}
        <div id="right-panel" class="hidden flex-1 flex flex-col gap-4">

            {{-- Vehicle info strip --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-3 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 bg-orange-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-[#FA6908]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 1h8z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7h3l2 5v4h-5V7z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <p id="sel-vehicle-number" class="font-bold text-[#021F4A] text-sm truncate">—</p>
                            <span id="sel-demo-badge"
                                class="hidden inline-flex items-center text-[10px] font-bold text-[#FA6908] bg-orange-50 border border-orange-200 px-1.5 py-px rounded-full uppercase tracking-wide leading-none">Demo</span>
                        </div>
                        <p id="sel-vehicle-meta" class="text-xs text-gray-400 truncate">—</p>
                    </div>
                </div>
                <span id="live-conn-status" class="flex items-center gap-1.5 text-xs text-gray-400 flex-shrink-0">
                    <span class="w-2 h-2 bg-gray-300 rounded-full"></span>Disconnected
                </span>
            </div>

            {{-- Tab switcher --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-1.5 flex gap-1">
                <button id="tab-btn-live" onclick="switchTab('live')"
                    class="flex-1 py-2.5 text-sm font-semibold rounded-lg bg-[#021F4A] text-white transition">
                    Live Tracking
                </button>
                <button id="tab-btn-history" onclick="switchTab('history')"
                    class="flex-1 py-2.5 text-sm font-semibold rounded-lg text-gray-500 hover:text-gray-700 transition">
                    Trip History
                </button>
            </div>

            {{-- Live controls --}}
            <div id="filter-live" class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-3">
                <div class="flex flex-wrap items-center gap-3">
                    <button id="connect-btn" onclick="startTrackingSelected()"
                        class="px-5 py-2 bg-[#FA6908] text-white text-sm font-semibold rounded-lg hover:bg-orange-600 transition">
                        Connect
                    </button>
                    <button onclick="stopTracking()"
                        class="px-5 py-2 border border-gray-200 text-gray-500 text-sm font-semibold rounded-lg hover:border-gray-400 hover:text-gray-700 transition">
                        Disconnect
                    </button>
                    <label class="flex items-center gap-2 text-sm text-gray-600 select-none">
                        <input type="checkbox" id="follow-toggle" checked class="accent-[#FA6908] w-4 h-4">
                        Auto-follow
                    </label>
                </div>
                <p id="live-error" class="text-red-600 text-sm mt-2 hidden"></p>
            </div>

            {{-- History date controls --}}
            <div id="filter-history" class="hidden bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-3">
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                        <input type="datetime-local" id="date-from"
                            value="{{ now()->startOfDay()->format('Y-m-d\TH:i') }}"
                            class="px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                        <input type="datetime-local" id="date-to"
                            value="{{ now()->format('Y-m-d\TH:i') }}"
                            class="px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]" />
                    </div>
                    <button onclick="loadTrips()" id="load-btn"
                        class="px-5 py-2 bg-[#FA6908] text-white text-sm font-semibold rounded-lg hover:bg-orange-600 transition">
                        Load History
                    </button>
                    <a id="report-btn" href="#" target="_blank" onclick="return openReport()"
                        class="hidden px-4 py-2 border border-[#021F4A] text-[#021F4A] text-sm font-semibold rounded-lg hover:bg-[#021F4A] hover:text-white transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Generate Report
                    </a>
                </div>
                <p id="hist-error" class="text-red-600 text-sm mt-2 hidden"></p>
            </div>

            {{-- Map + Trip list row --}}
            <div class="flex gap-4">

                {{-- Map panel --}}
                <div class="flex-1 bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden min-w-0">
                    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                        <h3 id="map-title" class="font-semibold text-gray-800 text-sm">Live Map</h3>
                        <div id="map-legend" class="hidden flex items-center gap-4 text-xs text-gray-400">
                            <span class="flex items-center gap-1">
                                <span class="w-3 h-1 bg-[#FA6908] rounded inline-block"></span>Full route
                            </span>
                            <span id="legend-trip-route" class="hidden flex items-center gap-1">
                                <span class="w-3 h-1 bg-[#021F4A] rounded inline-block"></span>Trip
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="w-3 h-3 bg-green-500 rounded-full inline-block"></span>Start
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="w-3 h-3 bg-red-500 rounded-full inline-block"></span>End
                            </span>
                        </div>
                    </div>

                    <div id="map" class="w-full" style="height: 440px;"></div>

                    {{-- Live stats bar --}}
                    <div id="live-stats-bar"
                        class="hidden px-5 py-3 border-t border-gray-100 flex flex-wrap items-center gap-5 text-sm text-gray-500">
                        <span>Speed <strong id="live-speed" class="text-gray-800 ml-1">—</strong> <span class="text-xs">km/h</span></span>
                        <span>Heading <strong id="live-heading" class="text-gray-800 ml-1">—</strong><span class="text-xs">°</span></span>
                        <span>Ignition <strong id="live-ignition" class="text-gray-800 ml-1">—</strong></span>
                        <span class="ml-auto text-xs text-gray-400">Updated <strong id="live-updated" class="text-gray-600">—</strong></span>
                    </div>

                    {{-- ── Playback bar (history mode) ─────────────────────────── --}}
                    <div id="playback-bar" class="hidden px-5 py-4 border-t border-gray-100">

                        {{-- Trip playback header — shown only when a specific trip is playing --}}
                        <div id="playback-trip-header"
                            class="hidden flex items-center justify-between mb-3 pb-3 border-b border-gray-100">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-2 h-2 bg-[#021F4A] rounded-full flex-shrink-0"></span>
                                <span class="text-xs font-bold text-[#021F4A]" id="playback-trip-label">Trip 1</span>
                                <span class="text-xs text-gray-400 truncate" id="playback-trip-range"></span>
                            </div>
                            <button onclick="viewFullRoute()"
                                class="flex items-center gap-1 text-xs text-gray-400 hover:text-[#FA6908] transition flex-shrink-0 ml-4">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Full route
                            </button>
                        </div>

                        {{-- Play / pause + scrubber --}}
                        <div class="flex items-center gap-4">
                            <button id="play-btn" onclick="togglePlay()"
                                class="w-8 h-8 flex-shrink-0 flex items-center justify-center bg-[#FA6908] text-white rounded-full hover:bg-orange-600 transition">
                                <svg id="play-icon" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                                <svg id="pause-icon" class="w-4 h-4 hidden" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
                                </svg>
                            </button>
                            <div class="flex-1">
                                <input type="range" id="scrubber" min="0" value="0"
                                    oninput="scrubTo(this.value)"
                                    class="w-full accent-[#FA6908]" />
                            </div>
                            <div class="text-xs text-gray-500 min-w-28 text-right" id="scrubber-time">—</div>
                        </div>

                        {{-- Telemetry + speed controls --}}
                        <div class="flex items-center justify-between mt-2">
                            <div class="flex items-center gap-5 text-xs text-gray-500">
                                <span>Speed <strong id="scrub-speed" class="text-gray-800">—</strong> km/h</span>
                                <span>Heading <strong id="scrub-heading" class="text-gray-800">—</strong>°</span>
                            </div>
                            {{-- Playback speed multiplier --}}
                            <div class="flex items-center gap-1">
                                <span class="text-[10px] text-gray-400 mr-1 font-semibold tracking-wide">SPEED</span>
                                <button data-speed="1" onclick="setPlaySpeed(1)"
                                    class="speed-btn px-2 py-0.5 text-xs rounded border bg-[#021F4A] text-white border-[#021F4A] font-semibold transition">1×</button>
                                <button data-speed="2" onclick="setPlaySpeed(2)"
                                    class="speed-btn px-2 py-0.5 text-xs rounded border border-gray-200 text-gray-500 font-semibold hover:border-gray-400 transition">2×</button>
                                <button data-speed="5" onclick="setPlaySpeed(5)"
                                    class="speed-btn px-2 py-0.5 text-xs rounded border border-gray-200 text-gray-500 font-semibold hover:border-gray-400 transition">5×</button>
                                <button data-speed="10" onclick="setPlaySpeed(10)"
                                    class="speed-btn px-2 py-0.5 text-xs rounded border border-gray-200 text-gray-500 font-semibold hover:border-gray-400 transition">10×</button>
                            </div>
                        </div>

                    </div>{{-- /playback-bar --}}
                </div>{{-- /map panel --}}

                {{-- Trip list sidebar (history only) --}}
                <div id="panel-history-sidebar"
                    class="hidden w-64 flex-shrink-0 bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden flex flex-col">
                    <div class="px-5 py-3 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800 text-sm">Trips</h3>
                    </div>
                    <div id="trips-loading" class="hidden p-6 text-center">
                        <div class="w-7 h-7 border-4 border-[#FA6908] border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
                        <p class="text-gray-400 text-sm">Loading…</p>
                    </div>
                    <div id="trips-empty" class="p-6 text-center flex-1 flex items-center justify-center">
                        <p class="text-gray-400 text-sm">Select a date range and click Load History.</p>
                    </div>
                    <div id="trips-list"
                        class="hidden divide-y divide-gray-50 overflow-y-auto flex-1"
                        style="max-height: 420px;"></div>
                    <div id="trips-stats" class="hidden px-4 py-3 border-t border-gray-100 bg-gray-50 text-xs text-gray-500 space-y-1.5">
                        <div class="flex justify-between"><span>Total trips</span> <strong id="stat-trips" class="text-gray-800">—</strong></div>
                        <div class="flex justify-between"><span>Total distance</span> <strong id="stat-distance" class="text-gray-800">—</strong></div>
                        <div class="flex justify-between"><span>Max speed</span> <strong id="stat-maxspeed" class="text-gray-800">—</strong></div>
                        <div class="flex justify-between"><span>Avg speed</span> <strong id="stat-avgspeed" class="text-gray-800">—</strong></div>
                    </div>
                </div>

            </div>{{-- /map + trip row --}}

        </div>{{-- /right-panel --}}
    </div>{{-- /right col --}}
</div>{{-- /main flex --}}

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/@microsoft/signalr@8.0.7/dist/browser/signalr.min.js"></script>
<script>
    // ── Server data ───────────────────────────────────────────────────────────────
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const vehiclesArr = @json($vehicles ?? []);
    const vehiclesIdx = {};
    vehiclesArr.forEach(v => {
        vehiclesIdx[(v.vehicleId ?? '').toLowerCase()] = v;
    });

    // ── Selected vehicle ──────────────────────────────────────────────────────────
    let selectedVehicleId = null;

    // ── Google Maps ───────────────────────────────────────────────────────────────
    let gmap = null;

    // ── Live tracking ─────────────────────────────────────────────────────────────
    let liveVehicleId = null;
    let liveMarker = null;
    let livePolyline = null;
    let liveTrail = [];
    let liveInfoWin = null;
    let signalRConn = null;
    const LIVE_TRAIL = 60;

    // ── Trip history ──────────────────────────────────────────────────────────────
    let histPolyline = null; // full orange route polyline
    let histStartMark = null;
    let histEndMark = null;
    let histPlayMark = null; // animated arrow marker
    let tripHighlightLine = null; // dark-blue overlay for the currently playing trip
    let allPoints = []; // GPS points for the full loaded date range
    let tripPlayPoints = []; // GPS points for just the active trip
    let playIndex = 0;
    let playTimer = null;
    let isPlaying = false;
    let activePlayMode = 'full'; // 'full' | 'trip'
    let playSpeed = 1; // 1 | 2 | 5 | 10

    // ── Trip list ─────────────────────────────────────────────────────────────────
    let tripsList = []; // trip summary objects from the API
    let tripPointsCache = new Map(); // tripIndex → points[]  (avoid re-fetching)
    let selectedTripIdx = -1;

    // ── Tab ───────────────────────────────────────────────────────────────────────
    let currentTab = 'live';

    // =============================================================================
    // Helpers
    // =============================================================================

    /** Returns the point array that the scrubber and playback engine operate on. */
    function activePoints() {
        return (activePlayMode === 'trip' && tripPlayPoints.length > 0) ?
            tripPlayPoints :
            allPoints;
    }

    /**
     * Strip milliseconds and timezone suffix from an ISO-8601 string.
     * The PHP endpoint strips them server-side too, but belt-and-suspenders.
     */
    function isoForApi(iso) {
        return (iso ?? '')
            .replace(/\.\d+/, '') // strip ms
            .replace(/Z$/, '') // strip Z
            .replace(/[+-]\d{2}:\d{2}$/, ''); // strip ±hh:mm
    }

    function esc(s) {
        return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // =============================================================================
    // Vehicle selection
    // =============================================================================
    function selectVehicle(vehicleId) {
        const vid = (vehicleId ?? '').toLowerCase();

        document.querySelectorAll('.vehicle-card').forEach(c => {
            c.classList.remove('bg-orange-50', 'border-l-4', 'border-l-[#FA6908]');
        });
        const card = document.getElementById('vcard-' + vehicleId);
        if (card) {
            card.classList.add('bg-orange-50', 'border-l-4', 'border-l-[#FA6908]');
            card.scrollIntoView({
                block: 'nearest',
                behavior: 'smooth'
            });
        }

        selectedVehicleId = vehicleId;
        const v = vehiclesIdx[vid] ?? {};
        document.getElementById('sel-vehicle-number').textContent = v.vehicleNumber ?? '—';
        document.getElementById('sel-vehicle-meta').textContent = [(v.make ?? ''), (v.model ?? ''), (v.year ?? '')].filter(Boolean).join(' · ');
        document.getElementById('sel-demo-badge').classList.toggle('hidden', !v.isDemoVehicle);

        document.getElementById('right-empty').classList.add('hidden');
        document.getElementById('right-panel').classList.remove('hidden');

        if (currentTab === 'live' && liveVehicleId && liveVehicleId.toLowerCase() !== vid) stopTracking();
        if (currentTab === 'history') clearHistory();
    }

    // =============================================================================
    // Tab switching
    // =============================================================================
    function switchTab(tab) {
        currentTab = tab;
        const isLive = tab === 'live';

        document.getElementById('tab-btn-live').className = isLive ?
            'flex-1 py-2.5 text-sm font-semibold rounded-lg bg-[#021F4A] text-white transition' :
            'flex-1 py-2.5 text-sm font-semibold rounded-lg text-gray-500 hover:text-gray-700 transition';
        document.getElementById('tab-btn-history').className = !isLive ?
            'flex-1 py-2.5 text-sm font-semibold rounded-lg bg-[#021F4A] text-white transition' :
            'flex-1 py-2.5 text-sm font-semibold rounded-lg text-gray-500 hover:text-gray-700 transition';

        document.getElementById('filter-live').classList.toggle('hidden', !isLive);
        document.getElementById('filter-history').classList.toggle('hidden', isLive);
        document.getElementById('map-title').textContent = isLive ? 'Live Map' : 'Route Map';
        document.getElementById('map-legend').classList.toggle('hidden', isLive);
        document.getElementById('live-stats-bar').classList.toggle('hidden', !isLive || !liveMarker);
        document.getElementById('live-conn-status').classList.toggle('hidden', !isLive);
        document.getElementById('playback-bar').classList.toggle('hidden', isLive || !allPoints.length);
        document.getElementById('panel-history-sidebar').classList.toggle('hidden', isLive);

        if (isLive) clearHistory();
        else stopTracking();
    }

    // =============================================================================
    // Google Maps init (callback registered in the <script> tag below)
    // =============================================================================
    function initMap() {
        gmap = new google.maps.Map(document.getElementById('map'), {
            center: {
                lat: 7.8731,
                lng: 80.7718
            },
            zoom: 8,
            mapTypeId: 'roadmap',
            styles: [{
                    featureType: 'poi',
                    elementType: 'labels',
                    stylers: [{
                        visibility: 'off'
                    }]
                },
                {
                    featureType: 'transit',
                    elementType: 'labels',
                    stylers: [{
                        visibility: 'off'
                    }]
                },
            ],
        });
    }

    // =============================================================================
    // Icon helpers
    // =============================================================================
    function makeCarIcon(color) {
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40">
        <circle cx="20" cy="20" r="18" fill="${color}" stroke="white" stroke-width="3"/>
        <path fill="white" transform="translate(9,9) scale(0.916)"
              d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8
                 c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8
                 l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5
                 S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5
                 -.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
    </svg>`;
        return {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
            scaledSize: new google.maps.Size(40, 40),
            anchor: new google.maps.Point(20, 20),
        };
    }

    function makePlaybackIcon(heading) {
        return {
            path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
            fillColor: '#021F4A',
            fillOpacity: 1,
            strokeColor: '#ffffff',
            strokeWeight: 2,
            scale: 6,
            rotation: heading ?? 0,
        };
    }

    function makeDotIcon(color) {
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
        <circle cx="10" cy="10" r="8" fill="${color}" stroke="white" stroke-width="2.5"/>
    </svg>`;
        return {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
            scaledSize: new google.maps.Size(20, 20),
            anchor: new google.maps.Point(10, 10),
        };
    }

    // =============================================================================
    // LIVE TRACKING
    // =============================================================================
    function startTrackingSelected() {
        if (!selectedVehicleId) return;
        startTracking(selectedVehicleId);
    }

    async function startTracking(vehicleId) {
        document.getElementById('live-error').classList.add('hidden');
        if (!vehicleId) return;
        if (liveVehicleId && liveVehicleId.toLowerCase() === vehicleId.toLowerCase()) return;
        stopTracking();
        liveVehicleId = vehicleId;
        await connectLiveSignalR(vehicleId);
    }

    function stopTracking() {
        if (liveMarker) {
            liveMarker.setMap(null);
            liveMarker = null;
        }
        if (livePolyline) {
            livePolyline.setMap(null);
            livePolyline = null;
        }
        if (liveInfoWin) {
            liveInfoWin.close();
            liveInfoWin = null;
        }
        liveTrail = [];
        liveVehicleId = null;
        if (signalRConn) {
            signalRConn.stop().catch(() => {});
            signalRConn = null;
        }
        setLiveConnStatus('disconnected');
        document.getElementById('live-stats-bar').classList.add('hidden');
    }

    async function connectLiveSignalR(vehicleId) {
        setLiveConnStatus('connecting');
        let token;
        try {
            const res = await fetch('/api/signalr-token', {
                credentials: 'include'
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            token = json.token;
            if (!token) throw new Error('empty token');
        } catch (err) {
            setLiveConnStatus('disconnected');
            const errEl = document.getElementById('live-error');
            errEl.textContent = 'Could not get auth token. Please refresh and try again.';
            errEl.classList.remove('hidden');
            return;
        }

        const conn = new signalR.HubConnectionBuilder()
            .withUrl('https://api.shalotrack.com/hubs/location', {
                accessTokenFactory: () => token,
            })
            .withAutomaticReconnect([2000, 5000, 10000, 30000])
            .configureLogging(signalR.LogLevel.Warning)
            .build();

        conn.on('LocationUpdated', (data) => {
            if ((data.vehicleId || '').toLowerCase() !== vehicleId.toLowerCase()) return;
            onLiveLocationUpdated(data);
        });
        conn.onreconnecting(() => setLiveConnStatus('connecting'));
        conn.onreconnected(async () => {
            setLiveConnStatus('connected');
            try {
                await conn.invoke('JoinVehicleGroup', vehicleId);
            } catch {}
        });
        conn.onclose(() => setLiveConnStatus('disconnected'));

        try {
            await conn.start();
            await conn.invoke('JoinVehicleGroup', vehicleId);
            setLiveConnStatus('connected');
            signalRConn = conn;
        } catch (err) {
            setLiveConnStatus('disconnected');
        }
    }

    function onLiveLocationUpdated(data) {
        const lat = parseFloat(data.latitude);
        const lng = parseFloat(data.longitude);
        if (isNaN(lat) || isNaN(lng)) return;
        const pos = {
            lat,
            lng
        };

        liveTrail.push(pos);
        if (liveTrail.length > LIVE_TRAIL) liveTrail.shift();

        if (liveMarker) {
            liveMarker.setPosition(pos);
        } else {
            const v = vehiclesIdx[(liveVehicleId ?? '').toLowerCase()] ?? {};
            liveMarker = new google.maps.Marker({
                position: pos,
                map: gmap,
                title: v.vehicleNumber ?? 'Vehicle',
                icon: makeCarIcon('#FA6908'),
                zIndex: 20,
            });
            liveInfoWin = new google.maps.InfoWindow();
            liveMarker.addListener('click', () => liveInfoWin?.open({
                map: gmap,
                anchor: liveMarker
            }));
        }
        liveInfoWin?.setContent(buildLivePopupHtml(data));

        if (livePolyline) {
            livePolyline.setPath(liveTrail);
        } else {
            livePolyline = new google.maps.Polyline({
                path: liveTrail,
                geodesic: true,
                strokeColor: '#FA6908',
                strokeOpacity: 0.75,
                strokeWeight: 4,
                map: gmap,
            });
        }

        if (document.getElementById('follow-toggle')?.checked) gmap.panTo(pos);

        document.getElementById('live-speed').textContent = Math.round(data.speed ?? 0);
        document.getElementById('live-heading').textContent = Math.round(data.heading ?? 0);
        document.getElementById('live-ignition').textContent = data.ignitionStatus ? 'ON' : 'OFF';
        document.getElementById('live-updated').textContent = new Date().toLocaleTimeString();
        document.getElementById('live-stats-bar').classList.remove('hidden');
    }

    function buildLivePopupHtml(data) {
        const v = vehiclesIdx[(liveVehicleId ?? '').toLowerCase()] ?? {};
        return `<div style="min-width:160px;font-family:sans-serif;">
        <p style="font-weight:700;font-size:14px;margin:0 0 4px">${esc(v.vehicleNumber ?? '')}</p>
        <p style="font-size:12px;color:#6B7280;margin:0 0 4px">${esc((v.make ?? '') + ' ' + (v.model ?? ''))}</p>
        <p style="font-size:12px;color:#16A34A;margin:0">● Live</p>
        <p style="font-size:12px;color:#374151;margin:4px 0 0">
            ${Math.round(data.speed ?? 0)} km/h · ${data.ignitionStatus ? 'Ignition ON' : 'Ignition OFF'}
        </p>
    </div>`;
    }

    function setLiveConnStatus(state) {
        const el = document.getElementById('live-conn-status');
        if (!el) return;
        const cfg = {
            connected: {
                cls: 'flex items-center gap-1.5 text-xs text-green-600 font-medium flex-shrink-0',
                dot: 'w-2 h-2 bg-green-500 rounded-full animate-pulse',
                label: 'Connected'
            },
            connecting: {
                cls: 'flex items-center gap-1.5 text-xs text-amber-500 font-medium flex-shrink-0',
                dot: 'w-2 h-2 bg-amber-400 rounded-full animate-pulse',
                label: 'Connecting…'
            },
            disconnected: {
                cls: 'flex items-center gap-1.5 text-xs text-gray-400 flex-shrink-0',
                dot: 'w-2 h-2 bg-gray-300 rounded-full',
                label: 'Disconnected'
            },
        } [state] ?? {};
        el.className = cfg.cls;
        el.innerHTML = `<span class="${cfg.dot}"></span>${cfg.label}`;
    }

    // =============================================================================
    // TRIP HISTORY — load
    // =============================================================================
    function openReport() {
        const vehicleId = selectedVehicleId;
        const from = document.getElementById('date-from').value;
        const to = document.getElementById('date-to').value;
        if (!vehicleId || !from || !to) return false;
        window.open(`/trips/${vehicleId}/report?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`, '_blank');
        return false;
    }

    function clearHistory() {
        if (histPolyline) {
            histPolyline.setMap(null);
            histPolyline = null;
        }
        if (histStartMark) {
            histStartMark.setMap(null);
            histStartMark = null;
        }
        if (histEndMark) {
            histEndMark.setMap(null);
            histEndMark = null;
        }
        if (histPlayMark) {
            histPlayMark.setMap(null);
            histPlayMark = null;
        }
        if (tripHighlightLine) {
            tripHighlightLine.setMap(null);
            tripHighlightLine = null;
        }

        allPoints = [];
        tripPlayPoints = [];
        tripsList = [];
        tripPointsCache.clear();
        selectedTripIdx = -1;
        activePlayMode = 'full';
        pausePlayback();
        playIndex = 0;

        document.getElementById('playback-bar').classList.add('hidden');
        document.getElementById('playback-trip-header').classList.add('hidden');
        document.getElementById('report-btn').classList.add('hidden');
        document.getElementById('trips-list').classList.add('hidden');
        document.getElementById('trips-stats').classList.add('hidden');
        document.getElementById('trips-loading').classList.add('hidden');
        document.getElementById('legend-trip-route').classList.add('hidden');
        const emptyEl = document.getElementById('trips-empty');
        emptyEl.querySelector('p').textContent = 'Select a date range and click Load History.';
        emptyEl.classList.remove('hidden');
    }

    async function loadTrips() {
        const vehicleId = selectedVehicleId;
        const from = document.getElementById('date-from').value;
        const to = document.getElementById('date-to').value;
        const errEl = document.getElementById('hist-error');
        errEl.classList.add('hidden');

        if (!vehicleId) {
            errEl.textContent = 'No vehicle selected.';
            errEl.classList.remove('hidden');
            return;
        }
        if (!from || !to) {
            errEl.textContent = 'Please select a date range.';
            errEl.classList.remove('hidden');
            return;
        }
        if (new Date(from) >= new Date(to)) {
            errEl.textContent = '"From" must be before "To".';
            errEl.classList.remove('hidden');
            return;
        }

        clearHistory();

        document.getElementById('trips-loading').classList.remove('hidden');
        document.getElementById('trips-empty').classList.add('hidden');
        document.getElementById('load-btn').disabled = true;
        document.getElementById('load-btn').textContent = 'Loading…';

        try {
            const hdrs = {
                Accept: 'application/json',
                'X-CSRF-TOKEN': CSRF
            };
            const [ptRes, sumRes] = await Promise.all([
                fetch(`/trips/${vehicleId}/points?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`, {
                    credentials: 'include',
                    headers: hdrs
                }),
                fetch(`/trips/${vehicleId}/summary?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`, {
                    credentials: 'include',
                    headers: hdrs
                }),
            ]);
            const ptData = await ptRes.json();
            const sumData = await sumRes.json();

            document.getElementById('trips-loading').classList.add('hidden');
            document.getElementById('load-btn').disabled = false;
            document.getElementById('load-btn').textContent = 'Load History';

            if (ptData.success && ptData.data?.length > 0) {
                renderRoute(ptData.data);
            } else {
                showHistEmpty('No GPS points found for this period.');
            }

            if (sumData.success && sumData.data) {
                renderTripList(sumData.data);
                document.getElementById('report-btn').classList.remove('hidden');
            } else {
                showHistEmpty('No trips found for this period.');
            }
        } catch (e) {
            document.getElementById('trips-loading').classList.add('hidden');
            document.getElementById('load-btn').disabled = false;
            document.getElementById('load-btn').textContent = 'Load History';
            errEl.textContent = 'Failed to load trip data. Please try again.';
            errEl.classList.remove('hidden');
        }
    }

    function showHistEmpty(msg) {
        const el = document.getElementById('trips-empty');
        el.querySelector('p').textContent = msg;
        el.classList.remove('hidden');
    }

    // =============================================================================
    // Route rendering (full date range)
    // =============================================================================
    function renderRoute(points) {
        // Ensure chronological order: the API returns newest-first for pagination,
        // but route rendering needs oldest-first so path[0] = true start (green dot)
        // and path[last] = true end (red dot).
        points = [...points].sort((a, b) => new Date(a.eventTime) - new Date(b.eventTime));
        allPoints = points;

        const path = points
            .map(p => ({
                lat: parseFloat(p.latitude),
                lng: parseFloat(p.longitude)
            }))
            .filter(p => !isNaN(p.lat) && !isNaN(p.lng));
        if (path.length === 0) return;

        histPolyline = new google.maps.Polyline({
            path,
            geodesic: true,
            strokeColor: '#FA6908',
            strokeOpacity: 0.55,
            strokeWeight: 3,
            map: gmap,
        });

        const bounds = new google.maps.LatLngBounds();
        path.forEach(p => bounds.extend(p));
        gmap.fitBounds(bounds, {
            top: 40,
            right: 40,
            bottom: 60,
            left: 40
        });

        histStartMark = new google.maps.Marker({
            position: path[0],
            map: gmap,
            title: 'Start',
            icon: makeDotIcon('#16A34A'),
            zIndex: 10,
        });

        histEndMark = new google.maps.Marker({
            position: path[path.length - 1],
            map: gmap,
            title: 'End',
            icon: makeDotIcon('#DC2626'),
            zIndex: 10,
        });

        histPlayMark = new google.maps.Marker({
            position: path[0],
            map: gmap,
            title: 'Playback',
            icon: makePlaybackIcon(parseFloat(points[0]?.heading ?? 0)),
            zIndex: 20,
        });

        const sc = document.getElementById('scrubber');
        sc.max = points.length - 1;
        sc.value = 0;

        document.getElementById('playback-bar').classList.remove('hidden');
        updateScrubberDisplay(0);
    }

    // =============================================================================
    // Trip list rendering
    // =============================================================================
    function renderTripList(data) {
        tripsList = data.trips ?? [];
        const list = document.getElementById('trips-list');
        list.innerHTML = '';

        if (tripsList.length === 0) {
            showHistEmpty('No trips found for this period.');
            return;
        }

        tripsList.forEach((trip, i) => {
            const start = new Date(trip.startTime);
            const end = new Date(trip.endTime);
            const dur = Math.round(parseFloat(trip.durationMinutes));
            const dist = parseFloat(trip.distanceKm).toFixed(1);
            const maxSpd = Math.round(parseFloat(trip.maxSpeed));
            const avgSpd = Math.round(parseFloat(trip.avgSpeed));
            const timeFmt = {
                hour: '2-digit',
                minute: '2-digit'
            };

            const div = document.createElement('div');
            div.id = `trip-card-${i}`;
            div.className = 'trip-card px-4 py-3.5 hover:bg-gray-50 transition-colors cursor-pointer select-none';
            div.innerHTML = `
            <div class="flex items-center justify-between mb-1">
                <p class="font-semibold text-gray-800 text-sm">
                    Trip ${i + 1}${trip.inProgress ? ' <span class="text-[10px] text-orange-500 font-normal">(in progress)</span>' : ''}
                </p>
                <span class="text-xs text-gray-400 font-medium">${dist} km</span>
            </div>
            <p class="text-xs text-gray-400">${start.toLocaleTimeString([], timeFmt)} → ${end.toLocaleTimeString([], timeFmt)}</p>
            <p class="text-xs text-gray-500 mt-0.5">${dur} min · Max ${maxSpd} km/h · Avg ${avgSpd} km/h</p>
            <button id="play-btn-${i}"
                    class="play-trip-btn mt-2.5 w-full py-1.5 text-xs font-semibold text-white bg-[#FA6908]
                           rounded-lg flex items-center justify-center gap-1.5
                           hover:bg-orange-600 active:scale-95 transition">
                <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z"/>
                </svg>
                Play Trip ${i + 1}
            </button>
        `;

            // Card click → focus map (no playback)
            div.addEventListener('click', (e) => {
                if (e.target.closest('.play-trip-btn')) return;
                focusTripOnMap(trip);
                markTripCardActive(i, false);
            });

            // Play button click → fetch + animate
            div.querySelector('.play-trip-btn').addEventListener('click', (e) => {
                e.stopPropagation();
                playTrip(i);
            });

            list.appendChild(div);
        });

        list.classList.remove('hidden');

        // Summary stats
        const totalDist = tripsList.reduce((s, t) => s + parseFloat(t.distanceKm), 0).toFixed(1);
        const maxSpdAll = Math.max(...tripsList.map(t => parseFloat(t.maxSpeed)));
        const avgSpdAll = (tripsList.reduce((s, t) => s + parseFloat(t.avgSpeed), 0) / tripsList.length).toFixed(0);

        document.getElementById('stat-trips').textContent = tripsList.length;
        document.getElementById('stat-distance').textContent = `${totalDist} km`;
        document.getElementById('stat-maxspeed').textContent = `${Math.round(maxSpdAll)} km/h`;
        document.getElementById('stat-avgspeed').textContent = `${avgSpdAll} km/h`;
        document.getElementById('trips-stats').classList.remove('hidden');
    }

    function focusTripOnMap(trip) {
        if (!trip.startLatitude || !trip.startLongitude) return;
        gmap.setCenter({
            lat: parseFloat(trip.startLatitude),
            lng: parseFloat(trip.startLongitude)
        });
        gmap.setZoom(14);
    }

    // =============================================================================
    // PER-TRIP PLAYBACK
    // =============================================================================

    /**
     * Fetch GPS points for a single trip, then start animated playback.
     * Points are cached so replaying the same trip is instant.
     */
    async function playTrip(tripIndex) {
        const trip = tripsList[tripIndex];
        if (!trip) return;

        pausePlayback();

        // Loading state on the button
        const btn = document.getElementById(`play-btn-${tripIndex}`);
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `
            <svg class="w-3 h-3 flex-shrink-0 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg> Loading…`;
        }

        let points;
        if (tripPointsCache.has(tripIndex)) {
            points = tripPointsCache.get(tripIndex);
        } else {
            try {
                // Fetch points scoped to this trip's exact time window.
                // Narrow window → fewer points → higher effective resolution than the
                // full-range load (which hits the server-side 500-point page cap).
                const from = encodeURIComponent(isoForApi(trip.startTime));
                const to = encodeURIComponent(isoForApi(trip.endTime));
                const res = await fetch(
                    `/trips/${selectedVehicleId}/points?from=${from}&to=${to}`, {
                        credentials: 'include',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': CSRF
                        }
                    }
                );
                const data = await res.json();
                // Sort ascending so playback goes forward in time
                const raw = (data.success && Array.isArray(data.data)) ? data.data : [];
                points = [...raw].sort((a, b) => new Date(a.eventTime) - new Date(b.eventTime));
                tripPointsCache.set(tripIndex, points);
            } catch (e) {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = `<svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Play Trip ${tripIndex + 1}`;
                }
                return;
            }
        }

        if (btn) btn.disabled = false;

        // Not enough points — just pan the map
        if (points.length < 2) {
            markTripCardActive(tripIndex, false);
            focusTripOnMap(trip);
            if (btn) btn.innerHTML = `<svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Play Trip ${tripIndex + 1}`;
            return;
        }

        // ── Set up trip playback state ────────────────────────────────────────────
        tripPlayPoints = points;
        activePlayMode = 'trip';
        playIndex = 0;

        markTripCardActive(tripIndex, true);

        // Draw highlighted route overlay (dark blue on top of orange full route)
        if (tripHighlightLine) {
            tripHighlightLine.setMap(null);
            tripHighlightLine = null;
        }

        const tripPath = points
            .map(p => ({
                lat: parseFloat(p.latitude),
                lng: parseFloat(p.longitude)
            }))
            .filter(p => !isNaN(p.lat) && !isNaN(p.lng));

        tripHighlightLine = new google.maps.Polyline({
            path: tripPath,
            geodesic: true,
            strokeColor: '#021F4A',
            strokeOpacity: 1,
            strokeWeight: 5,
            map: gmap,
            zIndex: 5,
        });

        // Fit map to this trip
        const bounds = new google.maps.LatLngBounds();
        tripPath.forEach(p => bounds.extend(p));
        gmap.fitBounds(bounds, {
            top: 70,
            right: 40,
            bottom: 110,
            left: 40
        });

        // Show trip route entry in legend
        document.getElementById('legend-trip-route').classList.remove('hidden');

        // Move the playback arrow to the trip's first point
        if (histPlayMark) {
            const p0 = points[0];
            const lat = parseFloat(p0.latitude);
            const lng = parseFloat(p0.longitude);
            if (!isNaN(lat) && !isNaN(lng)) {
                histPlayMark.setPosition({
                    lat,
                    lng
                });
                histPlayMark.setIcon(makePlaybackIcon(parseFloat(p0.heading ?? 0)));
            }
        }

        // Update scrubber range to this trip's point count
        const sc = document.getElementById('scrubber');
        sc.max = points.length - 1;
        sc.value = 0;

        // Show trip label in playback bar
        const start = new Date(trip.startTime);
        const end = new Date(trip.endTime);
        const tfmt = {
            hour: '2-digit',
            minute: '2-digit'
        };
        document.getElementById('playback-trip-label').textContent = `Trip ${tripIndex + 1}`;
        document.getElementById('playback-trip-range').textContent =
            `${start.toLocaleTimeString([], tfmt)} – ${end.toLocaleTimeString([], tfmt)}`;
        document.getElementById('playback-trip-header').classList.remove('hidden');
        document.getElementById('playback-bar').classList.remove('hidden');

        updateScrubberDisplay(0);

        // Auto-start
        startPlayback();
    }

    /**
     * Reset from trip playback back to the full date-range view.
     */
    function viewFullRoute() {
        pausePlayback();

        if (tripHighlightLine) {
            tripHighlightLine.setMap(null);
            tripHighlightLine = null;
        }
        tripPlayPoints = [];
        activePlayMode = 'full';
        playIndex = 0;

        // Clear trip card highlights + reset all play buttons
        document.querySelectorAll('.trip-card').forEach(c => {
            c.classList.remove('bg-blue-50', 'border-l-2', 'border-l-[#021F4A]');
        });
        document.querySelectorAll('.play-trip-btn').forEach((btn, i) => {
            btn.disabled = false;
            btn.innerHTML = `<svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Play Trip ${i + 1}`;
            btn.classList.remove('bg-[#021F4A]', 'hover:bg-[#021F4A]');
            btn.classList.add('bg-[#FA6908]', 'hover:bg-orange-600');
        });
        selectedTripIdx = -1;

        document.getElementById('playback-trip-header').classList.add('hidden');
        document.getElementById('legend-trip-route').classList.add('hidden');

        // Reset scrubber to the full range
        const sc = document.getElementById('scrubber');
        sc.max = allPoints.length - 1;
        sc.value = 0;

        // Move arrow back to start of full route
        if (histPlayMark && allPoints.length > 0) {
            const p0 = allPoints[0];
            const lat = parseFloat(p0.latitude);
            const lng = parseFloat(p0.longitude);
            if (!isNaN(lat) && !isNaN(lng)) {
                histPlayMark.setPosition({
                    lat,
                    lng
                });
                histPlayMark.setIcon(makePlaybackIcon(parseFloat(p0.heading ?? 0)));
            }
        }

        // Refit map to full route
        if (allPoints.length > 0) {
            const bounds = new google.maps.LatLngBounds();
            allPoints.forEach(p => {
                const lat = parseFloat(p.latitude);
                const lng = parseFloat(p.longitude);
                if (!isNaN(lat) && !isNaN(lng)) bounds.extend({
                    lat,
                    lng
                });
            });
            gmap.fitBounds(bounds, {
                top: 40,
                right: 40,
                bottom: 60,
                left: 40
            });
        }

        updateScrubberDisplay(0);
    }

    /**
     * Visually highlight the active trip card and update play button states.
     * @param {number} index        Trip index (0-based)
     * @param {boolean} showPlaying Replace the play button with a "playing" indicator
     */
    function markTripCardActive(index, showPlaying) {
        document.querySelectorAll('.trip-card').forEach(c => {
            c.classList.remove('bg-blue-50', 'border-l-2', 'border-l-[#021F4A]');
        });

        const card = document.getElementById(`trip-card-${index}`);
        if (card) {
            card.classList.add('bg-blue-50', 'border-l-2', 'border-l-[#021F4A]');
            card.scrollIntoView({
                block: 'nearest',
                behavior: 'smooth'
            });
        }

        document.querySelectorAll('.play-trip-btn').forEach((btn, i) => {
            if (showPlaying && i === index) {
                btn.innerHTML = `
                <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                </svg> Now playing`;
                btn.classList.add('bg-[#021F4A]', 'hover:bg-[#021F4A]');
                btn.classList.remove('bg-[#FA6908]', 'hover:bg-orange-600');
            } else {
                btn.innerHTML = `
                <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z"/>
                </svg> Play Trip ${i + 1}`;
                btn.classList.remove('bg-[#021F4A]', 'hover:bg-[#021F4A]');
                btn.classList.add('bg-[#FA6908]', 'hover:bg-orange-600');
            }
        });
        selectedTripIdx = index;
    }

    // =============================================================================
    // PLAYBACK ENGINE
    // =============================================================================
    function scrubTo(index) {
        const pts = activePoints();
        if (!pts.length || !histPlayMark) return;
        index = parseInt(index, 10);
        playIndex = index;
        const p = pts[index];
        if (!p) return;
        const lat = parseFloat(p.latitude);
        const lng = parseFloat(p.longitude);
        if (!isNaN(lat) && !isNaN(lng)) {
            histPlayMark.setPosition({
                lat,
                lng
            });
            histPlayMark.setIcon(makePlaybackIcon(parseFloat(p.heading ?? 0)));
        }
        updateScrubberDisplay(index);
    }

    function updateScrubberDisplay(index) {
        const pts = activePoints();
        const p = pts[index];
        if (!p) return;
        document.getElementById('scrubber-time').textContent = new Date(p.eventTime).toLocaleTimeString();
        document.getElementById('scrub-speed').textContent = Math.round(parseFloat(p.speed ?? 0));
        document.getElementById('scrub-heading').textContent = Math.round(parseFloat(p.heading ?? 0));
        document.getElementById('scrubber').value = index;
    }

    function togglePlay() {
        isPlaying ? pausePlayback() : startPlayback();
    }

    function startPlayback() {
        const pts = activePoints();
        if (!pts.length) return;
        if (playIndex >= pts.length - 1) playIndex = 0;
        isPlaying = true;
        document.getElementById('play-icon').classList.add('hidden');
        document.getElementById('pause-icon').classList.remove('hidden');

        const interval = Math.max(16, Math.round(100 / playSpeed));
        playTimer = setInterval(() => {
            const p2 = activePoints();
            if (playIndex >= p2.length - 1) {
                pausePlayback();
                return;
            }
            playIndex++;
            scrubTo(playIndex);
        }, interval);
    }

    function pausePlayback() {
        isPlaying = false;
        clearInterval(playTimer);
        document.getElementById('play-icon').classList.remove('hidden');
        document.getElementById('pause-icon').classList.add('hidden');
    }

    /**
     * Set playback speed multiplier and update button states.
     * Restarts the timer immediately so the new speed takes effect mid-playback.
     */
    function setPlaySpeed(speed) {
        playSpeed = speed;
        document.querySelectorAll('.speed-btn').forEach(btn => {
            const active = parseFloat(btn.dataset.speed) === speed;
            btn.classList.toggle('bg-[#021F4A]', active);
            btn.classList.toggle('text-white', active);
            btn.classList.toggle('border-[#021F4A]', active);
            btn.classList.toggle('text-gray-500', !active);
            btn.classList.toggle('border-gray-200', !active);
        });

        if (isPlaying) {
            clearInterval(playTimer);
            const interval = Math.max(16, Math.round(100 / playSpeed));
            playTimer = setInterval(() => {
                const pts = activePoints();
                if (playIndex >= pts.length - 1) {
                    pausePlayback();
                    return;
                }
                playIndex++;
                scrubTo(playIndex);
            }, interval);
        }
    }
</script>

{{--
    Google Maps JavaScript API.
    Restrict this API key to HTTP referrers (localhost + *.shalotrack.com) in Google Cloud Console.
--}}
<script
    src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&callback=initMap"
    async defer>
</script>
@endpush