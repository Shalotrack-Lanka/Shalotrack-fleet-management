@extends('layouts.app')
@section('title', 'Saved Places — ShaloTrack Fleet')
@section('page-title', 'Saved Places')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@section('content')

    @if($error)
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">{{ $error }}</div>
    @endif

    <div class="grid grid-cols-3 gap-6">

        {{-- Map (2/3) --}}
        <div class="col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800" id="map-title">Saved Places Map</h3>
                <div id="pin-instructions" class="hidden text-xs text-[#FA6908] font-medium">Click on the map to drop a pin</div>
            </div>
            <div id="map" class="w-full" style="height: 500px;"></div>

            {{-- Pin controls --}}
            <div id="pin-controls" class="hidden px-6 py-4 border-t border-gray-100 bg-orange-50/30 space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Place Name *</label>
                    <input type="text" id="pin-name" placeholder="e.g. Home, Office, Depot"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908]" />
                </div>
                <p id="pin-coords" class="text-xs text-gray-400">No location selected. Click on the map.</p>
                <p id="pin-error" class="text-red-600 text-sm hidden"></p>
                <div class="flex gap-3">
                    <button onclick="cancelPin()" class="flex-1 py-2 border border-gray-200 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50">Cancel</button>
                    <button id="pin-save-btn" onclick="savePlace()" class="flex-1 py-2 bg-[#FA6908] text-white text-sm font-semibold rounded-lg hover:bg-orange-600">Save Place</button>
                </div>
            </div>
        </div>

        {{-- Places list (1/3) --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">Saved Places</h3>
                <button onclick="openPinMode()"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-[#FA6908] text-white text-xs font-semibold rounded-lg hover:bg-orange-600 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Place
                </button>
            </div>

            @if(empty($places))
                <div class="p-6 text-center">
                    <p class="text-gray-400 text-sm mb-1">No saved places yet.</p>
                    <p class="text-gray-300 text-xs">Click "Add Place" and drop a pin on the map.</p>
                </div>
            @else
                <div class="divide-y divide-gray-50 overflow-y-auto" style="max-height: 540px;">
                    @foreach($places as $place)
                        <div class="px-5 py-4 hover:bg-gray-50 transition cursor-pointer"
                             id="place-{{ $place['placeId'] }}"
                             onclick="focusPlace({{ $place['latitude'] }}, {{ $place['longitude'] }})">
                            <div class="flex items-center justify-between mb-1">
                                <p class="font-semibold text-gray-800 text-sm">{{ $place['name'] }}</p>
                                <button onclick="event.stopPropagation(); confirmDeletePlace('{{ $place['placeId'] }}', '{{ $place['name'] }}')"
                                        class="text-gray-300 hover:text-red-500 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                            <p class="text-xs text-gray-400">
                                {{ number_format($place['latitude'], 4) }}, {{ number_format($place['longitude'], 4) }}
                            </p>
                            @if($place['visitCount'] ?? 0)
                                <p class="text-xs text-gray-400 mt-0.5">{{ $place['visitCount'] }} visit(s)</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Delete confirm --}}
    <div id="delete-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40" onclick="closeDeleteModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
                <h3 class="font-semibold text-gray-800 mb-2">Delete Place</h3>
                <p class="text-sm text-gray-500 mb-6">Delete <strong id="delete-name"></strong>?</p>
                <input type="hidden" id="delete-id" />
                <div class="flex gap-3">
                    <button onclick="closeDeleteModal()" class="flex-1 py-2 border border-gray-200 text-gray-600 text-sm rounded-lg">Cancel</button>
                    <button id="delete-btn" onclick="submitDeletePlace()" class="flex-1 py-2 bg-red-500 text-white text-sm font-semibold rounded-lg hover:bg-red-600">Delete</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const CSRF  = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const places = @json($places);

    const map = L.map('map', { center: [7.8731, 80.7718], zoom: 8 });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors', maxZoom: 19 }).addTo(map);

    // Plot existing places
    const bounds = [];
    places.forEach(p => {
        if (!p.latitude || !p.longitude) return;
        const lat = parseFloat(p.latitude), lng = parseFloat(p.longitude);
        L.marker([lat, lng], { icon: L.divIcon({
            className: '',
            html: `<div style="background:#FA6908;color:white;border-radius:50% 50% 50% 0;width:28px;height:28px;transform:rotate(-45deg);border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;">
                <svg style="transform:rotate(45deg)" width="12" height="12" fill="white" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            </div>`,
            iconSize: [28, 28], iconAnchor: [14, 28], popupAnchor: [0, -30],
        })}).bindPopup(`<strong>${p.name}</strong>`).addTo(map);
        bounds.push([lat, lng]);
    });
    if (bounds.length > 0) map.fitBounds(bounds, { padding: [60, 60], maxZoom: 13 });

    // Pin mode
    let pinMode = false, pinMarker = null, pinLatLng = null;

    function openPinMode() {
        pinMode = true;
        document.getElementById('pin-controls').classList.remove('hidden');
        document.getElementById('pin-instructions').classList.remove('hidden');
        document.getElementById('map-title').textContent = 'Click to place pin';
        map.getContainer().style.cursor = 'crosshair';
    }

    function cancelPin() {
        pinMode = false;
        if (pinMarker) { map.removeLayer(pinMarker); pinMarker = null; }
        pinLatLng = null;
        document.getElementById('pin-controls').classList.add('hidden');
        document.getElementById('pin-instructions').classList.add('hidden');
        document.getElementById('map-title').textContent = 'Saved Places Map';
        document.getElementById('pin-name').value = '';
        document.getElementById('pin-error').classList.add('hidden');
        map.getContainer().style.cursor = '';
    }

    map.on('click', function(e) {
        if (!pinMode) return;
        pinLatLng = e.latlng;
        if (pinMarker) map.removeLayer(pinMarker);
        pinMarker = L.marker([e.latlng.lat, e.latlng.lng]).addTo(map);
        document.getElementById('pin-coords').textContent = `${e.latlng.lat.toFixed(6)}, ${e.latlng.lng.toFixed(6)}`;
    });

    async function savePlace() {
        const errEl = document.getElementById('pin-error');
        errEl.classList.add('hidden');
        const name = document.getElementById('pin-name').value.trim();
        if (!name)     { errEl.textContent = 'Enter a name for this place.'; errEl.classList.remove('hidden'); return; }
        if (!pinLatLng){ errEl.textContent = 'Click on the map to select a location.'; errEl.classList.remove('hidden'); return; }

        const btn = document.getElementById('pin-save-btn');
        btn.disabled = true; btn.textContent = 'Saving...';

        const res = await fetch('/saved-places', {
            method: 'POST', credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ name, latitude: pinLatLng.lat, longitude: pinLatLng.lng }),
        });
        const data = await res.json().catch(() => ({}));
        btn.disabled = false; btn.textContent = 'Save Place';

        if (data.success) { cancelPin(); window.location.reload(); }
        else { errEl.textContent = data.message ?? 'Failed to save place.'; errEl.classList.remove('hidden'); }
    }

    function focusPlace(lat, lng) { map.setView([parseFloat(lat), parseFloat(lng)], 15, { animate: true }); }

    function confirmDeletePlace(id, name) {
        document.getElementById('delete-id').value = id;
        document.getElementById('delete-name').textContent = name;
        document.getElementById('delete-modal').classList.remove('hidden');
    }
    function closeDeleteModal() { document.getElementById('delete-modal').classList.add('hidden'); }

    async function submitDeletePlace() {
        const id = document.getElementById('delete-id').value;
        const btn = document.getElementById('delete-btn');
        btn.disabled = true; btn.textContent = 'Deleting...';
        const res = await fetch(`/saved-places/${id}`, { method: 'DELETE', credentials: 'include', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
        const data = await res.json().catch(() => ({}));
        btn.disabled = false; btn.textContent = 'Delete';
        if (data.success) { closeDeleteModal(); window.location.reload(); }
    }
</script>
@endpush