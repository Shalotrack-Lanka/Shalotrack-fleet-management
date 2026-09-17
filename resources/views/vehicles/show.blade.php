@extends('layouts.app')

@section('title', ($vehicle['vehicleNumber'] ?? 'Vehicle') . ' — ShaloTrack Fleet')
@section('page-title', $vehicle['vehicleNumber'] ?? 'Vehicle Detail')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@section('content')

    @if($error || !$vehicle)
        <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ $error ?? 'Vehicle not found.' }}
            <a href="/vehicles" class="ml-auto text-red-600 underline text-sm">← Back to Vehicles</a>
        </div>
    @else

        {{-- Back link --}}
        <div class="mb-6">
            <a href="/vehicles" class="text-sm text-gray-500 hover:text-[#FA6908] transition flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Vehicles
            </a>
        </div>

        <div class="grid grid-cols-3 gap-6">

            {{-- Vehicle info panel --}}
            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800">Vehicle Details</h3>
                        @if($vehicle['hasGpsDevice'] ?? false)
                            <span class="flex items-center gap-1 text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full font-medium">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>GPS Linked
                            </span>
                        @else
                            <span class="text-xs text-gray-400 bg-gray-50 px-2 py-1 rounded-full">No GPS</span>
                        @endif
                    </div>

                    <dl class="space-y-3">
                        @foreach([
                            'Plate Number'  => $vehicle['vehicleNumber'],
                            'Make'          => $vehicle['make'],
                            'Model'         => $vehicle['model'],
                            'Year'          => $vehicle['year'],
                            'Color'         => $vehicle['color'] ?? '—',
                            'Type'          => $vehicle['vehicleType'] ?? '—',
                            'Fuel'          => $vehicle['fuelType'] ?? '—',
                            'Chassis No.'   => $vehicle['chassisNumber'] ?? '—',
                            'Engine No.'    => $vehicle['engineNumber'] ?? '—',
                        ] as $label => $value)
                            <div class="flex items-center justify-between text-sm">
                                <dt class="text-gray-400">{{ $label }}</dt>
                                <dd class="text-gray-700 font-medium">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                {{-- GPS Device info --}}
                @if($vehicle['hasGpsDevice'] ?? false)
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                        <h3 class="font-semibold text-gray-800 mb-4">GPS Device</h3>
                        <dl class="space-y-3">
                            @foreach([
                                'IMEI'          => $vehicle['imei'] ?? '—',
                                'SIM Number'    => $vehicle['simNumber'] ?? '—',
                                'Device Model'  => $vehicle['deviceModel'] ?? '—',
                                'Network'       => $vehicle['networkProvider'] ?? '—',
                                'Status'        => $vehicle['activationStatus'] ?? '—',
                            ] as $label => $value)
                                <div class="flex items-center justify-between text-sm">
                                    <dt class="text-gray-400">{{ $label }}</dt>
                                    <dd class="text-gray-700 font-mono text-xs">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif
            </div>

            {{-- Live map (2/3 width) --}}
            <div class="col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800">Live Location</h3>
                    @if($vehicle['hasGpsDevice'] ?? false)
                        <span class="text-xs text-gray-400" id="last-update-label">Loading...</span>
                    @else
                        <span class="text-xs text-gray-400">No GPS device linked</span>
                    @endif
                </div>
                <div id="map" class="w-full" style="height: 500px;"></div>
            </div>

        </div>

    @endif

@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const vehicleId   = '{{ $vehicle["vehicleId"] ?? "" }}';
    const hasGps      = {{ ($vehicle['hasGpsDevice'] ?? false) ? 'true' : 'false' }};
    const CSRF        = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const map = L.map('map', { center: [7.8731, 80.7718], zoom: 8 });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    let marker = null;

    function makeIcon(online) {
        return L.divIcon({
            className: '',
            html: `<div style="width:40px;height:40px;background:${online ? '#FA6908' : '#9CA3AF'};border:3px solid white;border-radius:50%;box-shadow:0 2px 8px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;">
                <svg width="20" height="20" fill="white" viewBox="0 0 24 24">
                    <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                </svg>
            </div>`,
            iconSize: [40, 40],
            iconAnchor: [20, 20],
            popupAnchor: [0, -24],
        });
    }

    async function loadLocation() {
        if (!hasGps || !vehicleId) return;

        try {
            const res  = await fetch(`/api/CurrentLocations/vehicle/${vehicleId}`, {
                credentials: 'include',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            });

            // Location data comes from C# API via Laravel proxy
            // Fetch directly from the API using session token via /api/signalr-token approach
            // For now we fetch the page's API proxy endpoint
            if (!res.ok) {
                document.getElementById('last-update-label').textContent = 'Location unavailable';
                return;
            }

            const json = await res.json();
            const loc  = json?.data ?? json;

            if (!loc?.latitude || !loc?.longitude) {
                document.getElementById('last-update-label').textContent = 'No location data yet';
                return;
            }

            const lat = parseFloat(loc.latitude);
            const lng = parseFloat(loc.longitude);
            const online = true; // If we got data, device was recently active

            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng], { icon: makeIcon(online) })
                    .bindPopup(`
                        <div style="min-width:160px">
                            <p style="font-weight:600">{{ $vehicle['vehicleNumber'] ?? '' }}</p>
                            <p style="font-size:12px;color:#6B7280">{{ ($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? '') }}</p>
                            <p style="font-size:12px;margin-top:4px">${Math.round(loc.speed ?? 0)} km/h · ${loc.ignitionStatus ? 'Ignition on' : 'Ignition off'}</p>
                        </div>
                    `)
                    .addTo(map);
                map.setView([lat, lng], 15);
            }

            if (loc.lastUpdate) {
                const d = new Date(loc.lastUpdate);
                document.getElementById('last-update-label').textContent = 'Updated ' + d.toLocaleTimeString();
            }

        } catch (e) {
            document.getElementById('last-update-label').textContent = 'Location unavailable';
        }
    }

    if (hasGps) {
        loadLocation();
        setInterval(loadLocation, 15000);
    } else {
        // Show Sri Lanka center with no-GPS message
        L.popup({ closeButton: false })
            .setLatLng([7.8731, 80.7718])
            .setContent('<p style="text-align:center;color:#9CA3AF;font-size:13px">No GPS device linked to this vehicle.</p>')
            .openOn(map);
    }
</script>
@endpush