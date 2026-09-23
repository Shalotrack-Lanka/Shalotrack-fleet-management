@extends('layouts.app')

@section('title', ($vehicle['vehicleNumber'] ?? 'Vehicle') . ' — ShaloTrack Fleet')
@section('page-title', $vehicle['vehicleNumber'] ?? 'Vehicle Detail')

@section('content')

@if($error || !$vehicle)
<div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-3">
    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    {{ $error ?? 'Vehicle not found.' }}
    <a href="/vehicles" class="ml-auto text-red-600 underline text-sm">← Back to Vehicles</a>
</div>
@else

<div class="mb-6">
    <a href="/vehicles" class="text-sm text-gray-500 hover:text-[#FA6908] transition flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back to Vehicles
    </a>
</div>

<div class="grid grid-cols-3 gap-6">

    {{-- ── Left column: details ─────────────────────────────────────────── --}}
    <div class="space-y-6">

        {{-- Vehicle Details --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800">Vehicle Details</h3>
                @if($vehicle['isDemoVehicle'] ?? false)
                <span class="text-xs text-[#FA6908] bg-orange-50 border border-orange-200 px-2 py-1 rounded-full font-semibold">Demo</span>
                @elseif($vehicle['hasGpsDevice'] ?? false)
                <span class="flex items-center gap-1 text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full font-medium">
                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>GPS Linked
                </span>
                @else
                <span class="text-xs text-gray-400 bg-gray-50 px-2 py-1 rounded-full">No GPS</span>
                @endif
            </div>
            <dl class="space-y-3">
                @foreach([
                'Plate Number' => $vehicle['vehicleNumber'] ?? '—',
                'Make' => $vehicle['make'] ?? '—',
                'Model' => $vehicle['model'] ?? '—',
                'Year' => $vehicle['year'] ?? '—',
                'Color' => $vehicle['color'] ?? '—',
                'Type' => $vehicle['vehicleType'] ?? '—',
                'Fuel' => $vehicle['fuelType'] ?? '—',
                'Chassis No.' => $vehicle['chassisNumber'] ?? '—',
                'Engine No.' => $vehicle['engineNumber'] ?? '—',
                ] as $label => $value)
                <div class="flex items-center justify-between text-sm">
                    <dt class="text-gray-400">{{ $label }}</dt>
                    <dd class="text-gray-700 font-medium text-right">{{ $value }}</dd>
                </div>
                @endforeach
            </dl>
        </div>

        {{-- GPS Device --}}
        @if($vehicle['hasGpsDevice'] ?? false)
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4">GPS Device</h3>
            <dl class="space-y-3">
                @foreach([
                'IMEI' => $vehicle['imei'] ?? '—',
                'SIM Number' => $vehicle['simNumber'] ?? '—',
                'Device Model' => $vehicle['deviceModel'] ?? '—',
                'Network' => $vehicle['networkProvider'] ?? '—',
                'Status' => $vehicle['activationStatus'] ?? '—',
                ] as $label => $value)
                <div class="flex items-center justify-between text-sm">
                    <dt class="text-gray-400">{{ $label }}</dt>
                    <dd class="text-gray-700 font-mono text-xs text-right">{{ $value }}</dd>
                </div>
                @endforeach
            </dl>
        </div>
        @endif
    </div>

    {{-- ── Right col: Google Map ────────────────────────────────────────── --}}
    <div class="col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Live Location</h3>
            @if($vehicle['hasGpsDevice'] ?? false)
            <span class="flex items-center gap-1.5 text-xs text-gray-400" id="last-update-label">
                <span class="w-2 h-2 bg-gray-300 rounded-full" id="status-dot"></span>
                Connecting…
            </span>
            @else
            <span class="text-xs text-gray-400">No GPS device linked</span>
            @endif
        </div>
        <div id="gmap" class="w-full" style="height:500px;"></div>
    </div>

</div>

@endif

{{-- ── Inline JS ────────────────────────────────────────────────────────────── --}}
<script>
    const vehicleId = '{{ $vehicle["vehicleId"]     ?? "" }}'.toLowerCase();
    const hasGps = {
        {
            ($vehicle['hasGpsDevice'] ?? false) ? 'true' : 'false'
        }
    };
    const vehicleNum = {
        {
            json_encode($vehicle['vehicleNumber'] ?? '')
        }
    };
    const vehicleMake = {
        {
            json_encode($vehicle['make'] ?? '')
        }
    };
    const vehicleMod = {
        {
            json_encode($vehicle['model'] ?? '')
        }
    };
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    let gmap = null;
    let marker = null;
    let fallbackTimer = null;

    // ── Status label ───────────────────────────────────────────────────────────
    function setStatus(state, text) {
        const label = document.getElementById('last-update-label');
        if (!label) return;
        const states = {
            live: 'flex items-center gap-1.5 text-xs text-green-600 font-medium',
            reconnecting: 'flex items-center gap-1.5 text-xs text-amber-500 font-medium',
            fallback: 'flex items-center gap-1.5 text-xs text-gray-500',
            offline: 'flex items-center gap-1.5 text-xs text-gray-400',
        };
        const dots = {
            live: '<span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>',
            reconnecting: '<span class="w-2 h-2 bg-amber-400 rounded-full animate-pulse"></span>',
            fallback: '<span class="w-2 h-2 bg-gray-400 rounded-full"></span>',
            offline: '<span class="w-2 h-2 bg-gray-300 rounded-full"></span>',
        };
        label.className = states[state] ?? states.offline;
        label.innerHTML = (dots[state] ?? dots.offline) + (text || state);
    }

    // ── Map marker ─────────────────────────────────────────────────────────────
    function vehicleIcon(online) {
        const color = online ? '#FA6908' : '#9CA3AF';
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40">
            <circle cx="20" cy="20" r="19" fill="${color}" stroke="white" stroke-width="2.5"/>
            <path fill="white" d="M28.6 17.4C28.2 16.3 27.2 15.5 26 15.5H14c-1.2 0-2.2.8-2.6 1.9L10 22v6.5c0 .6.4 1 1 1h1c.6 0 1-.4 1-1V28h16v.5c0 .6.4 1 1 1h1c.6 0 1-.4 1-1V22l-2.4-4.6zM14.5 25.5c-.8 0-1.5-.7-1.5-1.5s.7-1.5 1.5-1.5 1.5.7 1.5 1.5-.7 1.5-1.5 1.5zm11 0c-.8 0-1.5-.7-1.5-1.5s.7-1.5 1.5-1.5 1.5.7 1.5 1.5-.7 1.5-1.5 1.5zM12 21l1.5-4.5h13L28 21H12z"/>
        </svg>`;
        return {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
            scaledSize: new google.maps.Size(40, 40),
            anchor: new google.maps.Point(20, 20),
        };
    }

    // ── Place or update marker ─────────────────────────────────────────────────
    function applyLocation(lat, lng, speed, ignition) {
        const pos = {
            lat,
            lng
        };
        const popup = `<div style="min-width:160px;font-family:system-ui,sans-serif;padding:4px 2px">
            <p style="font-weight:700;font-size:14px;color:#021F4A;margin:0 0 2px">${vehicleNum}</p>
            <p style="font-size:12px;color:#6B7280;margin:0 0 4px">${vehicleMake} ${vehicleMod}</p>
            <p style="font-size:12px;margin:0">
                ${Math.round(speed ?? 0)} km/h &nbsp;·&nbsp;
                ${ignition ? 'Ignition on' : 'Ignition off'}
            </p>
        </div>`;

        if (marker) {
            marker.setPosition(pos);
            marker.setIcon(vehicleIcon(true));
            marker.getTitle(); // keeps reference
            if (marker._infoWindow) marker._infoWindow.setContent(popup);
        } else {
            const iw = new google.maps.InfoWindow({
                content: popup
            });
            marker = new google.maps.Marker({
                position: pos,
                map: gmap,
                icon: vehicleIcon(true),
                title: vehicleNum,
            });
            marker._infoWindow = iw;
            marker.addListener('click', () => iw.open({
                anchor: marker,
                map: gmap
            }));
            gmap.setCenter(pos);
            gmap.setZoom(15);
        }
    }

    // ── Map init ───────────────────────────────────────────────────────────────
    function initMap() {
        gmap = new google.maps.Map(document.getElementById('gmap'), {
            center: {
                lat: 7.8731,
                lng: 80.7718
            },
            zoom: 8,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
            styles: [{
                featureType: 'poi',
                stylers: [{
                    visibility: 'off'
                }]
            }],
        });

        if (!hasGps) {
            // Show info window explaining no GPS
            new google.maps.InfoWindow({
                content: '<p style="color:#9CA3AF;font-size:13px;padding:4px 2px;text-align:center">No GPS device linked to this vehicle.</p>',
                position: {
                    lat: 7.8731,
                    lng: 80.7718
                },
            }).open(gmap);
            return;
        }

        // Start SignalR; fall back to polling if it fails
        initSignalR();
    }

    // ── HTTP fallback poll ─────────────────────────────────────────────────────
    async function loadLocationOnce() {
        if (!hasGps || !vehicleId) return;
        try {
            const res = await fetch(`/api/CurrentLocations/vehicle/${vehicleId}`, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF
                },
            });
            if (res.status === 401) {
                window.location.href = '/login?expired=1';
                return;
            }
            if (!res.ok) {
                setStatus('offline', 'Location unavailable');
                return;
            }
            const json = await res.json();
            const loc = json?.data ?? json;
            if (!loc?.latitude || !loc?.longitude) {
                setStatus('offline', 'No location data yet');
                return;
            }
            applyLocation(parseFloat(loc.latitude), parseFloat(loc.longitude), loc.speed, loc.ignitionStatus ?? loc.ignition);
            const updated = loc.lastUpdate ? new Date(loc.lastUpdate).toLocaleTimeString() : '—';
            setStatus('fallback', `Polled ${updated}`);
        } catch {
            setStatus('offline', 'Location unavailable');
        }
    }

    function startFallbackPoll() {
        if (fallbackTimer) return;
        loadLocationOnce();
        fallbackTimer = setInterval(loadLocationOnce, 60_000);
    }

    function stopFallbackPoll() {
        if (fallbackTimer) {
            clearInterval(fallbackTimer);
            fallbackTimer = null;
        }
    }

    // ── SignalR ────────────────────────────────────────────────────────────────
    async function initSignalR() {
        let token;
        try {
            const res = await fetch('/api/signalr-token', {
                credentials: 'include'
            });
            if (res.status === 401) {
                window.location.href = '/login?expired=1';
                return;
            }
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            token = json.token;
            if (!token) throw new Error('empty token');
        } catch (err) {
            console.warn('[SignalR] Token fetch failed — fallback poll', err);
            setStatus('fallback', 'Polling…');
            startFallbackPoll();
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
            if ((data.vehicleId ?? '').toLowerCase() !== vehicleId) return;
            const lat = parseFloat(data.latitude);
            const lng = parseFloat(data.longitude);
            if (isNaN(lat) || isNaN(lng)) return;
            applyLocation(lat, lng, data.speed, data.ignition ?? data.ignitionStatus);
            const t = data.lastUpdate ? 'Updated ' + new Date(data.lastUpdate).toLocaleTimeString() : 'Live';
            setStatus('live', t);
        });

        conn.onreconnecting(() => {
            setStatus('reconnecting');
            startFallbackPoll();
        });
        conn.onreconnected(async () => {
            stopFallbackPoll();
            setStatus('live', 'Reconnected');
            try {
                await conn.invoke('JoinVehicleGroup', vehicleId);
            } catch (e) {
                console.warn('[SignalR] JoinVehicleGroup failed', e);
            }
        });
        conn.onclose(() => {
            setStatus('fallback', 'Polling (reconnect failed)');
            startFallbackPoll();
        });

        try {
            await conn.start();
            await conn.invoke('JoinVehicleGroup', vehicleId);
            setStatus('live', 'Live');
        } catch (err) {
            console.error('[SignalR] Start failed:', err);
            setStatus('fallback', 'Polling…');
            startFallbackPoll();
        }
    }
</script>

{{-- SignalR CDN (SRI-pinned via jsdelivr) --}}
<script src="https://cdn.jsdelivr.net/npm/@microsoft/signalr@8.0.7/dist/browser/signalr.min.js"
    integrity="sha256-Fh4C2R5TmKO0C85CNAO5IHIY5Vr5Z0ItIBo9SjNGpA=" crossorigin="anonymous"></script>

{{-- Google Maps (defined before async load — initMap is the callback) --}}
<script async defer
    src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&callback=initMap&loading=async">
</script>

@endsection