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
                        <span class="flex items-center gap-1.5 text-xs text-gray-400" id="last-update-label">
                            <span class="w-2 h-2 bg-gray-300 rounded-full" id="status-dot"></span>Connecting…
                        </span>
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
{{-- Microsoft SignalR client --}}
<script src="https://cdn.jsdelivr.net/npm/@microsoft/signalr@8.0.7/dist/browser/signalr.min.js"></script>
<script>
    const vehicleId   = '{{ $vehicle["vehicleId"] ?? "" }}'.toLowerCase();
    const hasGps      = {{ ($vehicle['hasGpsDevice'] ?? false) ? 'true' : 'false' }};
    const vehicleNum  = '{{ addslashes($vehicle["vehicleNumber"] ?? "") }}';
    const vehicleMake = '{{ addslashes($vehicle["make"] ?? "") }}';
    const vehicleMod  = '{{ addslashes($vehicle["model"] ?? "") }}';
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

    function updateStatusLabel(state, extraText) {
        const label = document.getElementById('last-update-label');
        const dot   = document.getElementById('status-dot');
        if (!label || !dot) return;

        if (state === 'live') {
            dot.className  = 'w-2 h-2 bg-green-500 rounded-full animate-pulse';
            label.className = 'flex items-center gap-1.5 text-xs text-green-600 font-medium';
            label.innerHTML = `<span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>${extraText || 'Live'}`;
        } else if (state === 'reconnecting') {
            label.className = 'flex items-center gap-1.5 text-xs text-amber-500 font-medium';
            label.innerHTML = `<span class="w-2 h-2 bg-amber-400 rounded-full animate-pulse"></span>Reconnecting…`;
        } else if (state === 'fallback') {
            label.className = 'flex items-center gap-1.5 text-xs text-gray-500';
            label.innerHTML = `<span class="w-2 h-2 bg-gray-400 rounded-full"></span>${extraText || 'Polling…'}`;
        } else {
            label.className = 'flex items-center gap-1.5 text-xs text-gray-400';
            label.innerHTML = `<span class="w-2 h-2 bg-gray-300 rounded-full"></span>${extraText || 'Unavailable'}`;
        }
    }

    function applyLocation(lat, lng, speed, ignitionStatus) {
        const popupHtml = `
            <div style="min-width:160px">
                <p style="font-weight:600">${vehicleNum}</p>
                <p style="font-size:12px;color:#6B7280">${vehicleMake} ${vehicleMod}</p>
                <p style="font-size:12px;margin-top:4px">${Math.round(speed ?? 0)} km/h · ${ignitionStatus ? 'Ignition on' : 'Ignition off'}</p>
            </div>`;

        if (marker) {
            marker.setLatLng([lat, lng]);
            marker.setIcon(makeIcon(true));
            marker.getPopup()?.setContent(popupHtml);
        } else {
            marker = L.marker([lat, lng], { icon: makeIcon(true) })
                .bindPopup(popupHtml)
                .addTo(map);
            map.setView([lat, lng], 15);
        }
    }

    // -------------------------------------------------------------------------
    // HTTP fallback: called when SignalR is not connected.
    // Same endpoint as before, now only used as a safety net.
    // -------------------------------------------------------------------------
    let fallbackInterval = null;

    async function loadLocation() {
        if (!hasGps || !vehicleId) return;
        try {
            const res  = await fetch(`/api/CurrentLocations/vehicle/${vehicleId}`, {
                credentials: 'include',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            });
            if (!res.ok) {
                updateStatusLabel('offline', 'Location unavailable');
                return;
            }
            const json = await res.json();
            const loc  = json?.data ?? json;
            if (!loc?.latitude || !loc?.longitude) {
                updateStatusLabel('offline', 'No location data yet');
                return;
            }
            applyLocation(parseFloat(loc.latitude), parseFloat(loc.longitude), loc.speed, loc.ignitionStatus);
            if (loc.lastUpdate) {
                const t = new Date(loc.lastUpdate);
                updateStatusLabel('fallback', 'Polled ' + t.toLocaleTimeString());
            }
        } catch (e) {
            updateStatusLabel('offline', 'Location unavailable');
        }
    }

    function startFallbackPoll() {
        if (fallbackInterval) return;
        loadLocation(); // Immediate fetch
        fallbackInterval = setInterval(loadLocation, 60000); // Then every 60s
    }

    function stopFallbackPoll() {
        if (fallbackInterval) {
            clearInterval(fallbackInterval);
            fallbackInterval = null;
        }
    }

    // -------------------------------------------------------------------------
    // SignalR real-time connection
    // -------------------------------------------------------------------------
    if (hasGps && vehicleId) {
        (async function initSignalR() {
            // 1. Obtain Firebase JWT from the session-backed token endpoint.
            let token;
            try {
                const res  = await fetch('/api/signalr-token', { credentials: 'include' });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const json = await res.json();
                token = json.token;
                if (!token) throw new Error('empty token');
            } catch (err) {
                console.warn('[SignalR] Token fetch failed — starting HTTP fallback', err);
                updateStatusLabel('fallback', 'Polling…');
                startFallbackPoll();
                return;
            }

            // 2. Build connection.
            const connection = new signalR.HubConnectionBuilder()
                .withUrl('https://api.shalotrack.com/hubs/location', {
                    accessTokenFactory: () => token,
                })
                .withAutomaticReconnect([2000, 5000, 10000, 30000])
                .configureLogging(signalR.LogLevel.Warning)
                .build();

            // 3. Register handler BEFORE start() so no message is missed.
            connection.on('LocationUpdated', (data) => {
                // Guard: only process updates for this specific vehicle.
                const incomingId = (data.vehicleId || '').toLowerCase();
                if (incomingId !== vehicleId) return;

                const lat = parseFloat(data.latitude);
                const lng = parseFloat(data.longitude);
                if (isNaN(lat) || isNaN(lng)) return;

                applyLocation(lat, lng, data.speed, data.ignitionStatus);

                const lastUpdate = data.lastUpdate
                    ? 'Updated ' + new Date(data.lastUpdate).toLocaleTimeString()
                    : 'Live';
                updateStatusLabel('live', lastUpdate);
            });

            // 4. Lifecycle hooks.
            connection.onreconnecting(() => {
                updateStatusLabel('reconnecting');
                startFallbackPoll(); // Poll while reconnecting
            });

            connection.onreconnected(async () => {
                stopFallbackPoll();
                updateStatusLabel('live', 'Reconnected');
                try {
                    await connection.invoke('JoinVehicleGroup', vehicleId);
                } catch (e) {
                    console.warn('[SignalR] JoinVehicleGroup failed on reconnect', e);
                }
            });

            connection.onclose(() => {
                updateStatusLabel('fallback', 'Polling (reconnect failed)');
                startFallbackPoll();
            });

            // 5. Start.
            try {
                await connection.start();
                await connection.invoke('JoinVehicleGroup', vehicleId);
                updateStatusLabel('live', 'Live');
            } catch (err) {
                console.error('[SignalR] Connection failed:', err);
                updateStatusLabel('fallback', 'Polling…');
                startFallbackPoll();
            }
        })();
    } else if (!hasGps) {
        // No GPS device linked — show a placeholder
        L.popup({ closeButton: false })
            .setLatLng([7.8731, 80.7718])
            .setContent('<p style="text-align:center;color:#9CA3AF;font-size:13px">No GPS device linked to this vehicle.</p>')
            .openOn(map);
    }
</script>
@endpush