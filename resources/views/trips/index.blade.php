@extends('layouts.app')

@section('title', 'Trip History — ShaloTrack Fleet')
@section('page-title', 'Trip History')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@section('content')

    @if($error)
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ $error }}
        </div>
    @endif

    @if(empty($vehicles))
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-16 text-center">
            <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
            <p class="text-gray-400 font-medium mb-2">No GPS-enabled vehicles</p>
            <p class="text-gray-300 text-sm mb-6">Link a GPS device to a vehicle to view trip history.</p>
            <a href="/vehicles" class="px-6 py-2.5 bg-[#FA6908] text-white text-sm font-semibold rounded-lg hover:bg-orange-600 transition">
                Go to Vehicles
            </a>
        </div>
    @else

        {{-- Filter bar --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-6">
            <div class="flex flex-wrap items-end gap-4">

                {{-- Vehicle selector --}}
                <div class="flex-1 min-w-48">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Vehicle</label>
                    <select id="vehicle-select"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent">
                        <option value="">Select a vehicle</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle['vehicleId'] }}">{{ $vehicle['vehicleNumber'] }} — {{ $vehicle['make'] }} {{ $vehicle['model'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Date from --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                    <input type="datetime-local" id="date-from"
                           value="{{ now()->startOfDay()->format('Y-m-d\TH:i') }}"
                           class="px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent" />
                </div>

                {{-- Date to --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                    <input type="datetime-local" id="date-to"
                           value="{{ now()->format('Y-m-d\TH:i') }}"
                           class="px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent" />
                </div>

                <button onclick="loadTrips()"
                        id="load-btn"
                        class="px-5 py-2 bg-[#FA6908] hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition">
                    Load History
                </button>
            </div>
            <p id="filter-error" class="text-red-600 text-sm mt-3 hidden"></p>
        </div>

        {{-- Main content: map + trip list --}}
        <div class="grid grid-cols-3 gap-6">

            {{-- Map (2/3) --}}
            <div class="col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800">Route Map</h3>
                    <div class="flex items-center gap-4 text-xs text-gray-400">
                        <span class="flex items-center gap-1">
                            <span class="w-3 h-1 bg-[#FA6908] rounded inline-block"></span> Route
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-3 h-3 bg-green-500 rounded-full inline-block"></span> Start
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-3 h-3 bg-red-500 rounded-full inline-block"></span> End
                        </span>
                    </div>
                </div>
                <div id="map" class="w-full" style="height: 500px;"></div>

                {{-- Playback scrubber (hidden until route loaded) --}}
                <div id="playback-bar" class="hidden px-6 py-4 border-t border-gray-100">
                    <div class="flex items-center gap-4">
                        <button id="play-btn" onclick="togglePlay()"
                                class="w-8 h-8 flex items-center justify-center bg-[#FA6908] text-white rounded-full hover:bg-orange-600 transition flex-shrink-0">
                            <svg id="play-icon" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            <svg id="pause-icon" class="w-4 h-4 hidden" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                            </svg>
                        </button>
                        <div class="flex-1">
                            <input type="range" id="scrubber" min="0" value="0"
                                   oninput="scrubTo(this.value)"
                                   class="w-full accent-[#FA6908]" />
                        </div>
                        <div class="text-xs text-gray-500 min-w-32 text-right" id="scrubber-time">—</div>
                    </div>
                    <div class="flex items-center gap-6 mt-2 text-xs text-gray-500">
                        <span>Speed: <strong id="current-speed" class="text-gray-800">—</strong> km/h</span>
                        <span>Heading: <strong id="current-heading" class="text-gray-800">—</strong>°</span>
                    </div>
                </div>
            </div>

            {{-- Trip list (1/3) --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Trips</h3>
                </div>

                {{-- Loading state --}}
                <div id="trips-loading" class="hidden p-6 text-center">
                    <div class="w-8 h-8 border-4 border-[#FA6908] border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
                    <p class="text-gray-400 text-sm">Loading trips...</p>
                </div>

                {{-- Empty state --}}
                <div id="trips-empty" class="p-6 text-center">
                    <p class="text-gray-400 text-sm">Select a vehicle and date range, then click Load History.</p>
                </div>

                {{-- Trip list --}}
                <div id="trips-list" class="divide-y divide-gray-50 overflow-y-auto hidden" style="max-height: 530px;"></div>

                {{-- Stats summary --}}
                <div id="trips-stats" class="hidden px-5 py-4 border-t border-gray-100 bg-gray-50 text-xs text-gray-500 space-y-1">
                    <div class="flex justify-between">
                        <span>Total trips</span>
                        <strong id="stat-trips" class="text-gray-800">—</strong>
                    </div>
                    <div class="flex justify-between">
                        <span>Total distance</span>
                        <strong id="stat-distance" class="text-gray-800">—</strong>
                    </div>
                    <div class="flex justify-between">
                        <span>Max speed</span>
                        <strong id="stat-maxspeed" class="text-gray-800">—</strong>
                    </div>
                    <div class="flex justify-between">
                        <span>Avg speed</span>
                        <strong id="stat-avgspeed" class="text-gray-800">—</strong>
                    </div>
                </div>
            </div>
        </div>

    @endif

@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // ---- Leaflet map ----
    const map = L.map('map', { center: [7.8731, 80.7718], zoom: 8 });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors', maxZoom: 19,
    }).addTo(map);

    let routeLayer   = null;
    let startMarker  = null;
    let endMarker    = null;
    let playMarker   = null;
    let allPoints    = [];
    let playIndex    = 0;
    let playTimer    = null;
    let isPlaying    = false;

    // ---- Load trips ----
    async function loadTrips() {
        const vehicleId = document.getElementById('vehicle-select').value;
        const from      = document.getElementById('date-from').value;
        const to        = document.getElementById('date-to').value;
        const errEl     = document.getElementById('filter-error');

        errEl.classList.add('hidden');

        if (!vehicleId) { errEl.textContent = 'Please select a vehicle.'; errEl.classList.remove('hidden'); return; }
        if (!from || !to) { errEl.textContent = 'Please select a date range.'; errEl.classList.remove('hidden'); return; }
        if (new Date(from) >= new Date(to)) { errEl.textContent = 'From date must be before To date.'; errEl.classList.remove('hidden'); return; }

        // Show loading
        document.getElementById('trips-loading').classList.remove('hidden');
        document.getElementById('trips-empty').classList.add('hidden');
        document.getElementById('trips-list').classList.add('hidden');
        document.getElementById('trips-stats').classList.add('hidden');
        document.getElementById('load-btn').disabled = true;
        document.getElementById('load-btn').textContent = 'Loading...';

        clearMap();

        try {
            // Fetch both in parallel
            const [pointsRes, summaryRes] = await Promise.all([
                fetch(`/trips/${vehicleId}/points?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`, {
                    credentials: 'include', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
                }),
                fetch(`/trips/${vehicleId}/summary?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`, {
                    credentials: 'include', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
                }),
            ]);

            const pointsData  = await pointsRes.json();
            const summaryData = await summaryRes.json();

            document.getElementById('trips-loading').classList.add('hidden');
            document.getElementById('load-btn').disabled = false;
            document.getElementById('load-btn').textContent = 'Load History';

            // Render route on map
            if (pointsData.success && pointsData.data?.length > 0) {
                renderRoute(pointsData.data);
            }

            // Render trip list
            if (summaryData.success && summaryData.data) {
                renderTripList(summaryData.data);
            } else {
                document.getElementById('trips-empty').classList.remove('hidden');
                document.getElementById('trips-empty').querySelector('p').textContent = 'No trips found for this period.';
            }

        } catch (e) {
            document.getElementById('trips-loading').classList.add('hidden');
            document.getElementById('load-btn').disabled = false;
            document.getElementById('load-btn').textContent = 'Load History';
            errEl.textContent = 'Failed to load trip data. Please try again.';
            errEl.classList.remove('hidden');
        }
    }

    // ---- Render route on map ----
    function renderRoute(points) {
        allPoints = points;

        const latlngs = points.map(p => [parseFloat(p.latitude), parseFloat(p.longitude)]);

        // Draw polyline
        routeLayer = L.polyline(latlngs, { color: '#FA6908', weight: 3, opacity: 0.8 }).addTo(map);
        map.fitBounds(routeLayer.getBounds(), { padding: [40, 40] });

        // Start marker (green)
        startMarker = L.circleMarker(latlngs[0], {
            radius: 8, color: '#16A34A', fillColor: '#16A34A', fillOpacity: 1, weight: 2
        }).bindPopup('Start').addTo(map);

        // End marker (red)
        endMarker = L.circleMarker(latlngs[latlngs.length - 1], {
            radius: 8, color: '#DC2626', fillColor: '#DC2626', fillOpacity: 1, weight: 2
        }).bindPopup('End').addTo(map);

        // Playback marker (car icon)
        playMarker = L.circleMarker(latlngs[0], {
            radius: 6, color: '#021F4A', fillColor: '#021F4A', fillOpacity: 1, weight: 2
        }).addTo(map);

        // Setup scrubber
        const scrubber = document.getElementById('scrubber');
        scrubber.max   = points.length - 1;
        scrubber.value = 0;
        document.getElementById('playback-bar').classList.remove('hidden');
        updateScrubberDisplay(0);
    }

    // ---- Render trip list from summary ----
    function renderTripList(data) {
        const trips = data.trips ?? [];
        const list  = document.getElementById('trips-list');
        list.innerHTML = '';

        if (trips.length === 0) {
            document.getElementById('trips-empty').classList.remove('hidden');
            document.getElementById('trips-empty').querySelector('p').textContent = 'No trips found for this period.';
            return;
        }

        trips.forEach((trip, i) => {
            const start    = new Date(trip.startTime);
            const end      = new Date(trip.endTime);
            const duration = Math.round(parseFloat(trip.durationMinutes));
            const distance = parseFloat(trip.distanceKm).toFixed(1);
            const maxSpeed = Math.round(parseFloat(trip.maxSpeed));
            const avgSpeed = Math.round(parseFloat(trip.avgSpeed));

            const div = document.createElement('div');
            div.className = 'px-5 py-4 hover:bg-gray-50 transition cursor-pointer';
            div.innerHTML = `
                <div class="flex items-center justify-between mb-1">
                    <p class="font-semibold text-gray-800 text-sm">Trip ${i + 1}${trip.inProgress ? ' <span class="text-xs text-orange-500">(in progress)</span>' : ''}</p>
                    <span class="text-xs text-gray-400">${distance} km</span>
                </div>
                <p class="text-xs text-gray-400">${start.toLocaleTimeString()} → ${end.toLocaleTimeString()}</p>
                <p class="text-xs text-gray-500 mt-1">${duration} min · Max ${maxSpeed} km/h · Avg ${avgSpeed} km/h</p>
            `;
            div.onclick = () => focusTripOnMap(trip);
            list.appendChild(div);
        });

        list.classList.remove('hidden');

        // Stats
        const totalDist  = trips.reduce((s, t) => s + parseFloat(t.distanceKm), 0).toFixed(1);
        const maxSpeedAll = Math.max(...trips.map(t => parseFloat(t.maxSpeed)));
        const avgSpeedAll = (trips.reduce((s, t) => s + parseFloat(t.avgSpeed), 0) / trips.length).toFixed(0);

        document.getElementById('stat-trips').textContent    = trips.length;
        document.getElementById('stat-distance').textContent = `${totalDist} km`;
        document.getElementById('stat-maxspeed').textContent = `${Math.round(maxSpeedAll)} km/h`;
        document.getElementById('stat-avgspeed').textContent = `${avgSpeedAll} km/h`;
        document.getElementById('trips-stats').classList.remove('hidden');
    }

    // ---- Focus on trip start/end on map ----
    function focusTripOnMap(trip) {
        if (!trip.startLatitude || !trip.startLongitude) return;
        const lat = parseFloat(trip.startLatitude);
        const lng = parseFloat(trip.startLongitude);
        map.setView([lat, lng], 14, { animate: true });
    }

    // ---- Scrubber ----
    function scrubTo(index) {
        if (!allPoints.length) return;
        index = parseInt(index);
        playIndex = index;
        const p   = allPoints[index];
        const lat = parseFloat(p.latitude);
        const lng = parseFloat(p.longitude);
        playMarker.setLatLng([lat, lng]);
        updateScrubberDisplay(index);
    }

    function updateScrubberDisplay(index) {
        const p = allPoints[index];
        if (!p) return;
        const t = new Date(p.eventTime);
        document.getElementById('scrubber-time').textContent    = t.toLocaleTimeString();
        document.getElementById('current-speed').textContent    = Math.round(parseFloat(p.speed ?? 0));
        document.getElementById('current-heading').textContent  = Math.round(parseFloat(p.heading ?? 0));
        document.getElementById('scrubber').value               = index;
    }

    // ---- Playback ----
    function togglePlay() {
        if (isPlaying) {
            pausePlayback();
        } else {
            startPlayback();
        }
    }

    function startPlayback() {
        if (!allPoints.length) return;
        if (playIndex >= allPoints.length - 1) playIndex = 0;
        isPlaying = true;
        document.getElementById('play-icon').classList.add('hidden');
        document.getElementById('pause-icon').classList.remove('hidden');
        playTimer = setInterval(() => {
            if (playIndex >= allPoints.length - 1) {
                pausePlayback();
                return;
            }
            playIndex++;
            scrubTo(playIndex);
        }, 100); // 100ms between points = fast playback
    }

    function pausePlayback() {
        isPlaying = false;
        clearInterval(playTimer);
        document.getElementById('play-icon').classList.remove('hidden');
        document.getElementById('pause-icon').classList.add('hidden');
    }

    // ---- Clear map layers ----
    function clearMap() {
        if (routeLayer)  { map.removeLayer(routeLayer);  routeLayer  = null; }
        if (startMarker) { map.removeLayer(startMarker); startMarker = null; }
        if (endMarker)   { map.removeLayer(endMarker);   endMarker   = null; }
        if (playMarker)  { map.removeLayer(playMarker);  playMarker  = null; }
        allPoints = [];
        pausePlayback();
        playIndex = 0;
        document.getElementById('playback-bar').classList.add('hidden');
    }
</script>
@endpush