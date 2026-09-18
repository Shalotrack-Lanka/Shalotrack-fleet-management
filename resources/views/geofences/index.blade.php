@extends('layouts.app')

@section('title', 'Geofences — ShaloTrack Fleet')
@section('page-title', 'Geofences')

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

    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">{{ count($geofences) }} geofence(s)</p>
        <button onclick="openDrawMode()"
                class="flex items-center gap-2 px-4 py-2 bg-[#FA6908] hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Geofence
        </button>
    </div>

    <div class="grid grid-cols-3 gap-6">

        {{-- Map (2/3) --}}
        <div class="col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800" id="map-title">Geofence Map</h3>
                <div id="draw-instructions" class="hidden text-xs text-[#FA6908] font-medium">
                    Click on the map to place center, then adjust the radius slider below
                </div>
            </div>
            <div id="map" class="w-full" style="height: 500px;"></div>

            {{-- Draw controls (hidden until Add Geofence clicked) --}}
            <div id="draw-controls" class="hidden px-6 py-4 border-t border-gray-100 bg-orange-50/30 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Geofence Name *</label>
                        <input type="text" id="draw-name" placeholder="e.g. Home, Office, Depot"
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Scope</label>
                        <select id="draw-vehicle" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]">
                            <option value="">All my vehicles</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle['vehicleId'] }}">{{ $vehicle['vehicleNumber'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">
                        Radius: <strong id="radius-label">500</strong> metres
                    </label>
                    <input type="range" id="radius-slider" min="50" max="5000" value="500" step="50"
                           oninput="updateRadius(this.value)"
                           class="w-full accent-[#FA6908]" />
                </div>
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input type="checkbox" id="draw-enter" checked class="accent-[#FA6908]" />
                        Alert on Enter
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input type="checkbox" id="draw-exit" checked class="accent-[#FA6908]" />
                        Alert on Exit
                    </label>
                </div>
                <p id="draw-coords" class="text-xs text-gray-400">No location selected yet. Click on the map.</p>
                <p id="draw-error" class="text-red-600 text-sm hidden"></p>
                <div class="flex gap-3">
                    <button onclick="cancelDraw()"
                            class="flex-1 py-2 border border-gray-200 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button id="draw-save-btn" onclick="saveGeofence()"
                            class="flex-1 py-2 bg-[#FA6908] text-white text-sm font-semibold rounded-lg hover:bg-orange-600 transition">Save Geofence</button>
                </div>
            </div>
        </div>

        {{-- Geofence list (1/3) --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">Your Geofences</h3>
            </div>

            @if(empty($geofences))
                <div class="p-6 text-center">
                    <p class="text-gray-400 text-sm mb-2">No geofences yet.</p>
                    <p class="text-gray-300 text-xs">Click "Add Geofence" and click on the map to place one.</p>
                </div>
            @else
                <div class="divide-y divide-gray-50 overflow-y-auto" style="max-height: 560px;">
                    @foreach($geofences as $geofence)
                        <div class="px-5 py-4 hover:bg-gray-50 transition cursor-pointer"
                             onclick="focusGeofence({{ $geofence['latitude'] }}, {{ $geofence['longitude'] }}, {{ $geofence['radiusMeters'] }})">

                            <div class="flex items-center justify-between mb-1">
                                <p class="font-semibold text-gray-800 text-sm">{{ $geofence['name'] }}</p>
                                <div class="flex items-center gap-1">
                                    @if($geofence['isActive'] ?? true)
                                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                    @else
                                        <span class="w-2 h-2 bg-gray-300 rounded-full"></span>
                                    @endif
                                </div>
                            </div>

                            <p class="text-xs text-gray-400 mb-1">
                                {{ $geofence['vehicleNumber'] ?? 'All vehicles' }} ·
                                {{ number_format($geofence['radiusMeters']) }}m radius
                            </p>

                            <div class="flex items-center gap-2 text-xs text-gray-400 mb-2">
                                @if($geofence['alertOnEnter'] ?? false)
                                    <span class="px-1.5 py-0.5 bg-green-50 text-green-600 rounded">Enter</span>
                                @endif
                                @if($geofence['alertOnExit'] ?? false)
                                    <span class="px-1.5 py-0.5 bg-red-50 text-red-600 rounded">Exit</span>
                                @endif
                            </div>

                            @if($geofence['isOwner'] ?? true)
                                <div class="flex gap-2">
                                    <button onclick="event.stopPropagation(); confirmDeleteGeofence('{{ $geofence['geofenceId'] }}', '{{ $geofence['name'] }}')"
                                            class="text-xs text-gray-400 hover:text-red-500 transition">Delete</button>
                                </div>
                            @else
                                <p class="text-xs text-blue-400">Shared — view only</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Delete confirm modal --}}
    <div id="delete-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40" onclick="closeDeleteModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
                <h3 class="font-semibold text-gray-800 mb-2">Delete Geofence</h3>
                <p class="text-sm text-gray-500 mb-6">Delete <strong id="delete-name"></strong>? This cannot be undone.</p>
                <input type="hidden" id="delete-id" />
                <p id="delete-error" class="text-red-600 text-sm mb-4 hidden"></p>
                <div class="flex gap-3">
                    <button onclick="closeDeleteModal()" class="flex-1 py-2 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50">Cancel</button>
                    <button id="delete-btn" onclick="submitDeleteGeofence()" class="flex-1 py-2 bg-red-500 text-white text-sm font-semibold rounded-lg hover:bg-red-600">Delete</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // ---- Existing geofences from server ----
    const geofences = @json($geofences);

    // ---- Map setup ----
    const map = L.map('map', { center: [7.8731, 80.7718], zoom: 8 });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors', maxZoom: 19,
    }).addTo(map);

    // ---- Draw all existing geofences on map ----
    const bounds = [];
    geofences.forEach(g => {
        if (!g.latitude || !g.longitude) return;
        const lat = parseFloat(g.latitude);
        const lng = parseFloat(g.longitude);
        const circle = L.circle([lat, lng], {
            radius: g.radiusMeters,
            color: g.isOwner ? '#FA6908' : '#60A5FA',
            fillColor: g.isOwner ? '#FA6908' : '#60A5FA',
            fillOpacity: 0.15,
            weight: 2,
        }).bindPopup(`<strong>${g.name}</strong><br>${g.vehicleNumber ?? 'All vehicles'}<br>${g.radiusMeters}m radius`).addTo(map);
        bounds.push([lat, lng]);
    });
    if (bounds.length > 0) map.fitBounds(bounds, { padding: [60, 60], maxZoom: 13 });

    // ---- Draw mode ----
    let drawMode     = false;
    let drawCenter   = null;
    let drawCircle   = null;
    let drawMarker   = null;
    let drawRadius   = 500;

    function openDrawMode() {
        drawMode = true;
        document.getElementById('draw-controls').classList.remove('hidden');
        document.getElementById('draw-instructions').classList.remove('hidden');
        document.getElementById('map-title').textContent = 'Click on the map to place geofence center';
        map.getContainer().style.cursor = 'crosshair';
    }

    function cancelDraw() {
        drawMode = false;
        if (drawCircle) { map.removeLayer(drawCircle); drawCircle = null; }
        if (drawMarker) { map.removeLayer(drawMarker); drawMarker = null; }
        drawCenter = null;
        document.getElementById('draw-controls').classList.add('hidden');
        document.getElementById('draw-instructions').classList.add('hidden');
        document.getElementById('map-title').textContent = 'Geofence Map';
        document.getElementById('draw-coords').textContent = 'No location selected yet. Click on the map.';
        document.getElementById('draw-name').value = '';
        document.getElementById('draw-error').classList.add('hidden');
        map.getContainer().style.cursor = '';
    }

    map.on('click', function(e) {
        if (!drawMode) return;
        drawCenter = e.latlng;

        if (drawCircle) map.removeLayer(drawCircle);
        if (drawMarker) map.removeLayer(drawMarker);

        drawCircle = L.circle([e.latlng.lat, e.latlng.lng], {
            radius: drawRadius,
            color: '#FA6908',
            fillColor: '#FA6908',
            fillOpacity: 0.15,
            weight: 2,
        }).addTo(map);

        drawMarker = L.circleMarker([e.latlng.lat, e.latlng.lng], {
            radius: 5, color: '#FA6908', fillColor: '#FA6908', fillOpacity: 1,
        }).addTo(map);

        document.getElementById('draw-coords').textContent =
            `Center: ${e.latlng.lat.toFixed(6)}, ${e.latlng.lng.toFixed(6)}`;
    });

    function updateRadius(value) {
        drawRadius = parseInt(value);
        document.getElementById('radius-label').textContent = value;
        if (drawCircle) drawCircle.setRadius(drawRadius);
    }

    async function saveGeofence() {
        const errEl = document.getElementById('draw-error');
        errEl.classList.add('hidden');

        const name = document.getElementById('draw-name').value.trim();
        if (!name) { errEl.textContent = 'Please enter a geofence name.'; errEl.classList.remove('hidden'); return; }
        if (!drawCenter) { errEl.textContent = 'Please click on the map to place the geofence center.'; errEl.classList.remove('hidden'); return; }

        const btn = document.getElementById('draw-save-btn');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        const res = await fetch('/geofences', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({
                name,
                latitude:     drawCenter.lat,
                longitude:    drawCenter.lng,
                radiusMeters: drawRadius,
                vehicleId:    document.getElementById('draw-vehicle').value || null,
                alertOnEnter: document.getElementById('draw-enter').checked,
                alertOnExit:  document.getElementById('draw-exit').checked,
            }),
        });

        const data = await res.json().catch(() => ({}));
        btn.disabled = false;
        btn.textContent = 'Save Geofence';

        if (data.success) {
            cancelDraw();
            window.location.reload();
        } else {
            errEl.textContent = data.message ?? 'Failed to save geofence.';
            errEl.classList.remove('hidden');
        }
    }

    // ---- Focus geofence on map ----
    function focusGeofence(lat, lng, radius) {
        map.setView([parseFloat(lat), parseFloat(lng)], 14, { animate: true });
    }

    // ---- Delete ----
    function confirmDeleteGeofence(id, name) {
        document.getElementById('delete-id').value = id;
        document.getElementById('delete-name').textContent = name;
        document.getElementById('delete-error').classList.add('hidden');
        document.getElementById('delete-modal').classList.remove('hidden');
    }
    function closeDeleteModal() { document.getElementById('delete-modal').classList.add('hidden'); }

    async function submitDeleteGeofence() {
        const id  = document.getElementById('delete-id').value;
        const btn = document.getElementById('delete-btn');
        btn.disabled = true; btn.textContent = 'Deleting...';

        const res  = await fetch(`/geofences/${id}`, {
            method: 'DELETE',
            credentials: 'include',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        });
        const data = await res.json().catch(() => ({}));
        btn.disabled = false; btn.textContent = 'Delete';

        if (data.success) { closeDeleteModal(); window.location.reload(); }
        else {
            document.getElementById('delete-error').textContent = data.message ?? 'Failed to delete.';
            document.getElementById('delete-error').classList.remove('hidden');
        }
    }
</script>
@endpush