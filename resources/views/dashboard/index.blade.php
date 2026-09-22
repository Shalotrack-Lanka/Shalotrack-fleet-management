@extends('layouts.app')
@section('title', 'Dashboard — ShaloTrack Fleet')
@section('page-title', 'Dashboard')

@section('content')

@if($error)
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-3">
    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    {{ $error }}
    <button onclick="window.location.reload()" class="ml-auto text-red-600 underline text-sm">Retry</button>
</div>
@endif

@if($dashboard)

{{-- Stats row --}}
<div class="grid grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
        <p class="text-sm text-gray-500 mb-1">Total Vehicles</p>
        <p class="text-3xl font-bold text-[#021F4A]" id="stat-total">{{ $dashboard['vehicleCount'] ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
        <p class="text-sm text-gray-500 mb-1">Online</p>
        <p class="text-3xl font-bold text-green-600" id="stat-online">{{ $dashboard['onlineVehicles'] ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
        <p class="text-sm text-gray-500 mb-1">Offline</p>
        <p class="text-3xl font-bold text-gray-400" id="stat-offline">{{ $dashboard['offlineVehicles'] ?? 0 }}</p>
    </div>
</div>

{{-- Map + Vehicle list --}}
<div class="grid grid-cols-3 gap-6">

    {{-- Live map --}}
    <div class="col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Live Map</h3>
            <span id="realtime-status" class="flex items-center gap-1.5 text-xs text-gray-400">
                <span class="w-2 h-2 bg-gray-300 rounded-full"></span>Connecting…
            </span>
        </div>
        {{-- Map container — Google Maps SDK populates this div via initMap() --}}
        <div id="map" class="w-full" style="height: 500px;"></div>
    </div>

    {{-- Vehicle list sidebar --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Vehicles</h3>
        </div>

        @if(empty($dashboard['vehicles']))
        <div class="p-6 text-center">
            <p class="text-gray-400 text-sm">No vehicles found.</p>
            <a href="/vehicles" class="mt-3 inline-block text-[#FA6908] text-sm font-medium hover:underline">
                Add a vehicle →
            </a>
        </div>
        @else
        <div class="divide-y divide-gray-50 overflow-y-auto" style="max-height: 500px;">
            @foreach($dashboard['vehicles'] as $vehicle)
            <div id="vrow-{{ $vehicle['vehicleId'] }}"
                class="px-5 py-4 hover:bg-gray-50 transition cursor-pointer"
                onclick="focusVehicle('{{ $vehicle['vehicleId'] }}')">

                <div class="flex items-center justify-between mb-1">
                    <p class="font-semibold text-gray-800 text-sm">
                        {{ $vehicle['vehicleNumber'] }}
                        @if($vehicle['isShared'] ?? false)
                        <span class="ml-1 text-xs text-blue-500">(shared)</span>
                        @endif
                        @if($vehicle['isDemo'] ?? false)
                        <span class="ml-1 text-xs text-[#FA6908] font-semibold">(demo)</span>
                        @endif
                    </p>
                    @if($vehicle['online'] ?? false)
                    <span id="vbadge-{{ $vehicle['vehicleId'] }}"
                        class="flex items-center gap-1 text-xs text-green-600 font-medium">
                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>Online
                    </span>
                    @else
                    <span id="vbadge-{{ $vehicle['vehicleId'] }}"
                        class="flex items-center gap-1 text-xs text-gray-400">
                        <span class="w-2 h-2 bg-gray-300 rounded-full"></span>Offline
                    </span>
                    @endif
                </div>

                <p class="text-xs text-gray-400">{{ $vehicle['make'] }} {{ $vehicle['model'] }}</p>

                @if($vehicle['online'] ?? false)
                <p id="vmeta-{{ $vehicle['vehicleId'] }}" class="text-xs text-gray-500 mt-1">
                    {{ round($vehicle['speed'] ?? 0) }} km/h
                    · {{ ($vehicle['ignition'] ?? false) ? 'Ignition on' : 'Ignition off' }}
                </p>
                @elseif($vehicle['lastUpdate'] ?? null)
                <p id="vmeta-{{ $vehicle['vehicleId'] }}" class="text-xs text-gray-400 mt-1">
                    Last seen: {{ \Carbon\Carbon::parse($vehicle['lastUpdate'])->diffForHumans() }}
                </p>
                @else
                <p id="vmeta-{{ $vehicle['vehicleId'] }}" class="text-xs text-gray-400 mt-1">
                    No location data
                </p>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

@elseif(!$error)
<div class="text-center py-20">
    <p class="text-gray-400 text-sm">No data available. Please refresh.</p>
</div>
@endif

@endsection

@push('scripts')
{{-- Microsoft SignalR client --}}
<script src="https://cdn.jsdelivr.net/npm/@microsoft/signalr@8.0.7/dist/browser/signalr.min.js"></script>

<script>
    // ── Server-seeded vehicle data ────────────────────────────────────────────────
    const vehiclesRaw = @json($dashboard['vehicles'] ?? []);

    // Normalise vehicleIds to lowercase (matches Guid strings the C# hub pushes).
    const vehicleMap = {};
    vehiclesRaw.forEach(v => {
        vehicleMap[v.vehicleId.toLowerCase()] = v;
    });

    // ── Map state ─────────────────────────────────────────────────────────────────
    let gmap = null;
    const markers = {}; // vid (lc) → google.maps.Marker
    const infoWins = {}; // vid (lc) → google.maps.InfoWindow
    const trails = {}; // vid (lc) → [{lat, lng}, …]  (sliding window)
    const polylines = {}; // vid (lc) → google.maps.Polyline
    const TRAIL_MAX = 60; // max points kept per vehicle trail

    // ── Google Maps init callback (called by the Maps SDK after it loads) ─────────
    function initMap() {
        gmap = new google.maps.Map(document.getElementById('map'), {
            center: {
                lat: 7.8731,
                lng: 80.7718
            }, // Sri Lanka centroid
            zoom: 8,
            mapTypeId: 'roadmap',
            // Hide POI clutter so vehicle markers stand out
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

        const bounds = new google.maps.LatLngBounds();
        let hasPoint = false;

        vehiclesRaw.forEach(v => {
            if (!v.latitude || !v.longitude) return;
            const lat = parseFloat(v.latitude);
            const lng = parseFloat(v.longitude);
            if (isNaN(lat) || isNaN(lng)) return;

            const vid = v.vehicleId.toLowerCase();

            // Seed trail with the initial server-rendered position
            trails[vid] = [{
                lat,
                lng
            }];

            // Marker
            markers[vid] = new google.maps.Marker({
                position: {
                    lat,
                    lng
                },
                map: gmap,
                title: v.vehicleNumber,
                icon: makeMarkerIcon(v.online),
                zIndex: 10,
            });

            // InfoWindow (opened on marker click)
            infoWins[vid] = new google.maps.InfoWindow({
                content: buildInfoHtml(v.vehicleNumber, v.make, v.model, v.online, v.speed),
            });
            markers[vid].addListener('click', () => openInfo(vid));

            // Polyline — starts as one point; extended by each SignalR push
            polylines[vid] = new google.maps.Polyline({
                path: trails[vid],
                geodesic: true,
                strokeColor: '#FA6908',
                strokeOpacity: 0.75,
                strokeWeight: 4,
                map: gmap,
            });

            bounds.extend({
                lat,
                lng
            });
            hasPoint = true;
        });

        if (hasPoint) {
            // Fit map to show all vehicles with 40px padding on every side
            gmap.fitBounds(bounds, {
                top: 40,
                right: 40,
                bottom: 40,
                left: 40
            });
        }
    }

    // ── Marker icon factory ───────────────────────────────────────────────────────
    // Returns a data-URI SVG pin (car icon inside a coloured circle).
    function makeMarkerIcon(online) {
        const fill = online ? '#FA6908' : '#9CA3AF';
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40">
        <circle cx="20" cy="20" r="18" fill="${fill}" stroke="white" stroke-width="3"/>
        <path fill="white" transform="translate(9,9) scale(0.916)"
              d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.01L3 12v8c0 .55.45 1 1 1h1
                 c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16
                 c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0
                 c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
    </svg>`;
        return {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
            scaledSize: new google.maps.Size(40, 40),
            anchor: new google.maps.Point(20, 20),
        };
    }

    // ── InfoWindow HTML ───────────────────────────────────────────────────────────
    function buildInfoHtml(number, make, model, online, speed) {
        const n = esc(number);
        const m = esc(`${make ?? ''} ${model ?? ''}`.trim());
        return `<div style="min-width:160px;font-family:sans-serif;padding:4px 0;">
        <p style="font-weight:700;font-size:14px;margin:0 0 4px 0">${n}</p>
        <p style="color:#6B7280;font-size:12px;margin:0 0 4px 0">${m}</p>
        <p style="font-size:12px;color:${online ? '#16A34A' : '#9CA3AF'};margin:0">
            ${online ? '● Online' : '○ Offline'}
        </p>
        ${online ? `<p style="font-size:12px;color:#374151;margin:4px 0 0">${Math.round(speed ?? 0)} km/h</p>` : ''}
    </div>`;
    }

    function esc(s) {
        return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function openInfo(vid) {
        Object.values(infoWins).forEach(w => w.close());
        infoWins[vid]?.open({
            map: gmap,
            anchor: markers[vid]
        });
    }

    // ── Sidebar click → pan to vehicle ───────────────────────────────────────────
    function focusVehicle(vehicleId) {
        const vid = vehicleId.toLowerCase();
        const m = markers[vid];
        if (m && gmap) {
            gmap.panTo(m.getPosition());
            gmap.setZoom(15);
            openInfo(vid);
        }
    }

    // ── Update marker + extend polyline trail on each SignalR push ────────────────
    function updateMarker(vehicleId, data) {
        const lat = parseFloat(data.latitude);
        const lng = parseFloat(data.longitude);
        if (isNaN(lat) || isNaN(lng)) return;

        const pos = {
            lat,
            lng
        };
        const v = vehicleMap[vehicleId];
        const icon = makeMarkerIcon(true);

        // Extend trail (sliding window — oldest point dropped when > TRAIL_MAX)
        if (!trails[vehicleId]) trails[vehicleId] = [];
        trails[vehicleId].push(pos);
        if (trails[vehicleId].length > TRAIL_MAX) trails[vehicleId].shift();

        // Update or create marker
        if (markers[vehicleId]) {
            markers[vehicleId].setPosition(pos);
            markers[vehicleId].setIcon(icon);
        } else {
            markers[vehicleId] = new google.maps.Marker({
                position: pos,
                map: gmap,
                title: v?.vehicleNumber ?? '',
                icon,
                zIndex: 10,
            });
            infoWins[vehicleId] = new google.maps.InfoWindow();
            markers[vehicleId].addListener('click', () => openInfo(vehicleId));
        }

        // Refresh InfoWindow content with the latest speed
        infoWins[vehicleId]?.setContent(
            buildInfoHtml(v?.vehicleNumber, v?.make, v?.model, true, data.speed)
        );

        // Update or create polyline
        if (polylines[vehicleId]) {
            polylines[vehicleId].setPath(trails[vehicleId]);
        } else {
            polylines[vehicleId] = new google.maps.Polyline({
                path: trails[vehicleId],
                geodesic: true,
                strokeColor: '#FA6908',
                strokeOpacity: 0.75,
                strokeWeight: 4,
                map: gmap,
            });
        }

        // Keep JS state in sync so focusVehicle always has the latest position
        if (vehicleMap[vehicleId]) {
            Object.assign(vehicleMap[vehicleId], {
                latitude: data.latitude,
                longitude: data.longitude,
                online: true,
                speed: data.speed,
            });
        }
    }

    // ── Update sidebar row on each SignalR push ───────────────────────────────────
    function updateSidebar(vehicleId, data) {
        const badge = document.getElementById('vbadge-' + vehicleId);
        const meta = document.getElementById('vmeta-' + vehicleId);

        if (badge) {
            badge.className = 'flex items-center gap-1 text-xs text-green-600 font-medium';
            badge.innerHTML = '<span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>Online';
        }

        if (meta) {
            const speed = Math.round(data.speed ?? 0);
            const ignition = data.ignitionStatus ? 'Ignition on' : 'Ignition off';
            meta.className = 'text-xs text-gray-500 mt-1';
            meta.textContent = `${speed} km/h · ${ignition}`;
        }
    }

    // ── Real-time status indicator ────────────────────────────────────────────────
    function setStatus(state) {
        const el = document.getElementById('realtime-status');
        if (!el) return;
        const cfg = {
            live: {
                cls: 'flex items-center gap-1.5 text-xs text-green-600 font-medium',
                html: '<span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>Live'
            },
            reconnecting: {
                cls: 'flex items-center gap-1.5 text-xs text-amber-500 font-medium',
                html: '<span class="w-2 h-2 bg-amber-400 rounded-full animate-pulse"></span>Reconnecting…'
            },
            disconnected: {
                cls: 'flex items-center gap-1.5 text-xs text-gray-400',
                html: '<span class="w-2 h-2 bg-gray-300 rounded-full"></span>Disconnected'
            },
        } [state];
        if (cfg) {
            el.className = cfg.cls;
            el.innerHTML = cfg.html;
        }
    }

    // ── SignalR live-location connection ──────────────────────────────────────────
    (async function initSignalR() {
        // 1. Fetch Firebase JWT from the Laravel session — the only way to get
        //    an auth token into a WebSocket handshake (no custom headers allowed).
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
            console.warn('[SignalR] Token fetch failed — falling back to page reload', err);
            setStatus('disconnected');
            setTimeout(() => window.location.reload(), 90_000);
            return;
        }

        // 2. Build the hub connection.
        const connection = new signalR.HubConnectionBuilder()
            .withUrl('https://api.shalotrack.com/hubs/location', {
                accessTokenFactory: () => token,
            })
            .withAutomaticReconnect([2000, 5000, 10000, 30000])
            .configureLogging(signalR.LogLevel.Warning)
            .build();

        // 3. Register push handler BEFORE starting (never miss a message).
        connection.on('LocationUpdated', (data) => {
            const vid = (data.vehicleId || '').toLowerCase();
            if (!vehicleMap[vid]) return; // not in this customer's fleet — ignore
            updateMarker(vid, data);
            updateSidebar(vid, data);
        });

        // 4. Lifecycle hooks
        let fallbackTimer = null;

        connection.onreconnecting(() => {
            setStatus('reconnecting');
            if (!fallbackTimer) {
                fallbackTimer = setTimeout(() => window.location.reload(), 90_000);
            }
        });

        connection.onreconnected(async () => {
            setStatus('live');
            clearTimeout(fallbackTimer);
            fallbackTimer = null;
            await joinAllGroups();
        });

        connection.onclose(() => {
            setStatus('disconnected');
            // Auto-reconnect exhausted — force a page reload so data stays fresh.
            setTimeout(() => window.location.reload(), 10_000);
        });

        // 5. Start
        try {
            await connection.start();
            setStatus('live');
            await joinAllGroups();
        } catch (err) {
            console.error('[SignalR] Initial connection failed:', err);
            setStatus('disconnected');
            setTimeout(() => window.location.reload(), 90_000);
        }

        // Subscribe to the SignalR group for every vehicle in the customer's fleet.
        // The hub validates server-side — an invalid vehicleId is simply rejected.
        async function joinAllGroups() {
            for (const vid of Object.keys(vehicleMap)) {
                try {
                    await connection.invoke('JoinVehicleGroup', vid);
                } catch (e) {
                    console.warn('[SignalR] JoinVehicleGroup failed for', vid, e.message);
                }
            }
        }
    })();
</script>

{{--
    Google Maps JavaScript API
    ─────────────────────────
    SETUP REQUIRED:
      1. Add GOOGLE_MAPS_KEY=AIzaSy... to your .env
      2. Add to config/services.php:
            'google_maps' => ['key' => env('GOOGLE_MAPS_KEY')],
      3. Restrict the key in Google Cloud Console:
            • Application restrictions → HTTP referrers
            • Add: localhost, *.shalotrack.com (or your production domain)
            • API restrictions → Maps JavaScript API only

--}}
<script
    src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&callback=initMap"
    async defer>
</script>
@endpush