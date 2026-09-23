@extends('layouts.app')
@section('title', 'Saved Places — ShaloTrack Fleet')
@section('page-title', 'Saved Places')

@section('content')
<style>
    #map {
        height: 500px;
        width: 100%;
    }

    /* Pin controls */
    #pin-controls {
        display: none;
    }

    #pin-instructions {
        display: none;
    }

    /* Sidebar list */
    .place-row {
        padding: 16px 20px;
        border-bottom: 1px solid #f3f4f6;
        cursor: pointer;
        transition: background 0.15s;
    }

    .place-row:last-child {
        border-bottom: none;
    }

    .place-row:hover {
        background: #fafafa;
    }

    .place-row.active {
        background: #fff7ed;
        border-left: 3px solid #FA6908;
    }

    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        background: #FA6908;
        color: white;
        font-size: 12px;
        font-weight: 600;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        transition: background 0.15s;
    }

    .btn-primary:hover {
        background: #e55a00;
    }

    .btn-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 16px;
        border: 1px solid #e5e7eb;
        color: #374151;
        font-size: 13px;
        font-weight: 500;
        border-radius: 8px;
        background: white;
        cursor: pointer;
        transition: background 0.15s;
    }

    .btn-secondary:hover {
        background: #f9fafb;
    }

    .btn-danger {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 16px;
        background: #ef4444;
        color: white;
        font-size: 13px;
        font-weight: 600;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        transition: background 0.15s;
    }

    .btn-danger:hover {
        background: #dc2626;
    }

    .input-field {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 13px;
        outline: none;
        box-sizing: border-box;
        transition: box-shadow 0.15s;
    }

    .input-field:focus {
        box-shadow: 0 0 0 2px rgba(250, 105, 8, 0.3);
        border-color: #FA6908;
    }

    /* Modal overlay */
    #delete-modal {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 999;
    }

    #delete-modal.open {
        display: block;
    }

    .modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.4);
    }

    .modal-box {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }

    .modal-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        width: 100%;
        max-width: 360px;
        padding: 24px;
        text-align: center;
    }

    /* Pin mode banner */
    #pin-banner {
        display: none;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        background: #fff7ed;
        border-radius: 8px;
        margin-bottom: 12px;
    }

    #pin-banner.visible {
        display: flex;
    }
</style>

@if($error)
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">{{ $error }}</div>
@endif

{{-- Pin mode banner above layout --}}
<div id="pin-banner">
    <svg width="16" height="16" fill="none" stroke="#FA6908" stroke-width="2" viewBox="0 0 24 24">
        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="#FA6908" stroke="none" />
    </svg>
    <span class="text-sm font-medium text-[#FA6908]">Click anywhere on the map to drop a pin</span>
    <button onclick="cancelPin()" class="ml-auto text-xs text-gray-400 hover:text-gray-600 underline">Cancel</button>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;">

    {{-- ─── MAP PANEL ─── --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800" id="map-title">Saved Places Map</h3>
            <button class="btn-primary" onclick="openPinMode()">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path d="M12 4v16M4 12h16" stroke-linecap="round" />
                </svg>
                Add Place
            </button>
        </div>

        <div id="map"></div>

        {{-- Pin save form --}}
        <div id="pin-controls" class="px-6 py-4 border-t border-gray-100 bg-orange-50/30 space-y-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Place Name *</label>
                <input type="text" id="pin-name" placeholder="e.g. Home, Office, Depot" class="input-field" />
            </div>
            <p id="pin-coords" class="text-xs text-gray-400">No location selected — click on the map.</p>
            <p id="pin-error" class="text-red-500 text-sm hidden"></p>
            <div style="display:flex;gap:12px;">
                <button class="btn-secondary" style="flex:1;" onclick="cancelPin()">Cancel</button>
                <button id="pin-save-btn" class="btn-primary" style="flex:1;justify-content:center;" onclick="savePlace()">Save Place</button>
            </div>
        </div>
    </div>

    {{-- ─── PLACES LIST ─── --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Saved Places</h3>
            <p class="text-xs text-gray-400 mt-0.5">{{ count($places) }} place{{ count($places) === 1 ? '' : 's' }} saved</p>
        </div>

        @if(empty($places))
        <div class="p-6 text-center">
            <div style="width:40px;height:40px;background:#fff7ed;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="#FA6908">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                </svg>
            </div>
            <p class="text-gray-500 text-sm font-medium mb-1">No saved places yet</p>
            <p class="text-gray-400 text-xs">Click "Add Place" to drop a pin on the map</p>
        </div>
        @else
        <div style="max-height:540px;overflow-y:auto;">
            @foreach($places as $place)
            <div class="place-row"
                id="place-{{ $place['placeId'] }}"
                onclick="focusPlace({{ $place['latitude'] }}, {{ $place['longitude'] }}, '{{ $place['placeId'] }}')">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:8px;height:8px;border-radius:50%;background:#FA6908;flex-shrink:0;"></div>
                        <p class="font-semibold text-gray-800" style="font-size:13px;">{{ $place['name'] }}</p>
                    </div>
                    <button onclick="event.stopPropagation(); confirmDeletePlace('{{ $place['placeId'] }}', '{{ addslashes($place['name']) }}')"
                        style="padding:4px;color:#d1d5db;background:none;border:none;cursor:pointer;border-radius:4px;transition:color 0.15s;"
                        onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#d1d5db'"
                        title="Delete place">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
                <p style="font-size:11px;color:#9ca3af;padding-left:16px;">
                    {{ number_format($place['latitude'], 5) }}, {{ number_format($place['longitude'], 5) }}
                </p>
                @if(($place['visitCount'] ?? 0) > 0)
                <p style="font-size:11px;color:#d1d5db;padding-left:16px;margin-top:2px;">
                    {{ $place['visitCount'] }} visit{{ $place['visitCount'] === 1 ? '' : 's' }}
                </p>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- ─── DELETE CONFIRM MODAL ─── --}}
<div id="delete-modal">
    <div class="modal-backdrop" onclick="closeDeleteModal()"></div>
    <div class="modal-box">
        <div class="modal-card">
            <div style="width:48px;height:48px;background:#fef2f2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <svg width="22" height="22" fill="none" stroke="#ef4444" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <h3 class="font-semibold text-gray-800 mb-1" style="font-size:15px;">Delete Place?</h3>
            <p class="text-sm text-gray-500 mb-6">
                "<strong id="delete-name"></strong>" will be permanently removed.
            </p>
            <input type="hidden" id="delete-id" />
            <div style="display:flex;gap:12px;">
                <button class="btn-secondary" style="flex:1;" onclick="closeDeleteModal()">Cancel</button>
                <button id="delete-btn" class="btn-danger" style="flex:1;" onclick="submitDeletePlace()">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
    'use strict';

    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const places = @json($places);

    // ─── map state ───
    let map, infoWindow;
    let pinMode = false;
    let pinMarker = null;
    let pinLatLng = null;

    // SVG pin icon factory — orange = existing places, navy = new pin
    function pinIcon(color) {
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="36" viewBox="0 0 28 36">
        <path d="M14 0C6.268 0 0 6.268 0 14c0 9.333 14 22 14 22s14-12.667 14-22C28 6.268 21.732 0 14 0z" fill="${color}"/>
        <circle cx="14" cy="14" r="5.5" fill="white"/>
    </svg>`;
        return {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
            scaledSize: new google.maps.Size(28, 36),
            anchor: new google.maps.Point(14, 36),
            labelOrigin: new google.maps.Point(14, 14),
        };
    }

    // ─── initMap (Google Maps callback) ───
    function initMap() {
        map = new google.maps.Map(document.getElementById('map'), {
            center: {
                lat: 7.8731,
                lng: 80.7718
            },
            zoom: 8,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
            zoomControl: true,
            styles: [{
                featureType: 'poi',
                elementType: 'labels',
                stylers: [{
                    visibility: 'off'
                }]
            }, ],
        });

        infoWindow = new google.maps.InfoWindow();

        const bounds = new google.maps.LatLngBounds();
        let hasMarkers = false;

        places.forEach(p => {
            if (!p.latitude || !p.longitude) return;
            const lat = parseFloat(p.latitude);
            const lng = parseFloat(p.longitude);

            const marker = new google.maps.Marker({
                position: {
                    lat,
                    lng
                },
                map,
                title: p.name,
                icon: pinIcon('#FA6908'),
            });

            marker.addListener('click', () => {
                infoWindow.setContent(
                    `<div style="font-family:-apple-system,sans-serif;padding:2px 4px;">
                    <strong style="font-size:13px;color:#1f2937;">${p.name}</strong>
                    <p style="font-size:11px;color:#9ca3af;margin:4px 0 0;">${lat.toFixed(5)}, ${lng.toFixed(5)}</p>
                </div>`
                );
                infoWindow.open(map, marker);
            });

            bounds.extend({
                lat,
                lng
            });
            hasMarkers = true;
        });

        if (hasMarkers) {
            map.fitBounds(bounds);
            // Don't zoom too far in on a single marker
            google.maps.event.addListenerOnce(map, 'bounds_changed', () => {
                if (map.getZoom() > 13) map.setZoom(13);
            });
        }

        // Pin placement click handler
        map.addListener('click', e => {
            if (!pinMode) return;
            pinLatLng = e.latLng;

            if (pinMarker) pinMarker.setMap(null);
            pinMarker = new google.maps.Marker({
                position: pinLatLng,
                map,
                title: 'New Place',
                icon: pinIcon('#021F4A'),
                zIndex: 100,
                animation: google.maps.Animation.DROP,
            });

            document.getElementById('pin-coords').textContent =
                `${e.latLng.lat().toFixed(6)}, ${e.latLng.lng().toFixed(6)}`;
        });
    }

    // ─── Pin mode ───
    function openPinMode() {
        pinMode = true;
        document.getElementById('pin-controls').style.display = 'block';
        document.getElementById('pin-banner').classList.add('visible');
        document.getElementById('map-title').textContent = 'Click to place pin';
        map.setOptions({
            draggableCursor: 'crosshair'
        });
        infoWindow.close();
    }

    function cancelPin() {
        pinMode = false;
        if (pinMarker) {
            pinMarker.setMap(null);
            pinMarker = null;
        }
        pinLatLng = null;
        document.getElementById('pin-controls').style.display = 'none';
        document.getElementById('pin-banner').classList.remove('visible');
        document.getElementById('pin-name').value = '';
        document.getElementById('pin-coords').textContent = 'No location selected — click on the map.';
        document.getElementById('pin-error').classList.add('hidden');
        document.getElementById('map-title').textContent = 'Saved Places Map';
        map.setOptions({
            draggableCursor: ''
        });
    }

    // ─── Save new place ───
    async function savePlace() {
        const errEl = document.getElementById('pin-error');
        errEl.classList.add('hidden');

        const name = document.getElementById('pin-name').value.trim();
        if (!name) {
            errEl.textContent = 'Enter a name for this place.';
            errEl.classList.remove('hidden');
            return;
        }
        if (!pinLatLng) {
            errEl.textContent = 'Click on the map to select a location.';
            errEl.classList.remove('hidden');
            return;
        }

        const btn = document.getElementById('pin-save-btn');
        btn.disabled = true;
        btn.textContent = 'Saving…';

        try {
            const res = await fetch('/saved-places', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({
                    name,
                    latitude: pinLatLng.lat(),
                    longitude: pinLatLng.lng(),
                }),
            });

            if (res.status === 401) {
                window.location.href = '/login?expired=1';
                return;
            }

            const data = await res.json().catch(() => ({}));

            if (data.success) {
                cancelPin();
                window.location.reload();
            } else {
                errEl.textContent = data.message ?? 'Failed to save place.';
                errEl.classList.remove('hidden');
            }
        } catch (_) {
            errEl.textContent = 'Network error. Please try again.';
            errEl.classList.remove('hidden');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Save Place';
        }
    }

    // ─── Focus a place from sidebar ───
    function focusPlace(lat, lng, id) {
        const pos = {
            lat: parseFloat(lat),
            lng: parseFloat(lng)
        };
        map.panTo(pos);
        map.setZoom(15);

        // Highlight active row
        document.querySelectorAll('.place-row').forEach(r => r.classList.remove('active'));
        const row = document.getElementById('place-' + id);
        if (row) row.classList.add('active');
    }

    // ─── Delete place ───
    function confirmDeletePlace(id, name) {
        document.getElementById('delete-id').value = id;
        document.getElementById('delete-name').textContent = name;
        document.getElementById('delete-modal').classList.add('open');
    }

    function closeDeleteModal() {
        document.getElementById('delete-modal').classList.remove('open');
    }

    async function submitDeletePlace() {
        const id = document.getElementById('delete-id').value;
        const btn = document.getElementById('delete-btn');
        btn.disabled = true;
        btn.textContent = 'Deleting…';

        try {
            const res = await fetch(`/saved-places/${id}`, {
                method: 'DELETE',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
            });

            if (res.status === 401) {
                window.location.href = '/login?expired=1';
                return;
            }

            const data = await res.json().catch(() => ({}));
            if (data.success) {
                closeDeleteModal();
                window.location.reload();
            }
        } catch (_) {
            // silently re-enable — reload would lose user context
        } finally {
            btn.disabled = false;
            btn.textContent = 'Delete';
        }
    }
</script>

{{-- Google Maps — initMap must be defined before this loads --}}
<script async defer
    src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&callback=initMap&loading=async">
</script>

@endsection