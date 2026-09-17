@extends('layouts.app')

@section('title', 'Dashboard — ShaloTrack Fleet')
@section('page-title', 'Dashboard')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@section('content')

    @if($error)
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
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
                <p class="text-3xl font-bold text-[#021F4A]">{{ $dashboard['vehicleCount'] ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500 mb-1">Online</p>
                <p class="text-3xl font-bold text-green-600">{{ $dashboard['onlineVehicles'] ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500 mb-1">Offline</p>
                <p class="text-3xl font-bold text-gray-400">{{ $dashboard['offlineVehicles'] ?? 0 }}</p>
            </div>
        </div>

        {{-- Map + Vehicle list --}}
        <div class="grid grid-cols-3 gap-6">

            {{-- Live map --}}
            <div class="col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800">Live Map</h3>
                    <span class="text-xs text-gray-400">Auto-refreshes every 30s</span>
                </div>
                <div id="map" class="w-full" style="height: 500px;"></div>
            </div>

            {{-- Vehicle list --}}
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
                            <div class="px-5 py-4 hover:bg-gray-50 transition cursor-pointer"
                                 onclick="focusVehicle('{{ $vehicle['vehicleId'] }}', {{ $vehicle['latitude'] ?? 'null' }}, {{ $vehicle['longitude'] ?? 'null' }})">

                                <div class="flex items-center justify-between mb-1">
                                    <p class="font-semibold text-gray-800 text-sm">
                                        {{ $vehicle['vehicleNumber'] }}
                                        @if($vehicle['isShared'] ?? false)
                                            <span class="ml-1 text-xs text-blue-500">(shared)</span>
                                        @endif
                                        @if($vehicle['isDemo'] ?? false)
                                            <span class="ml-1 text-xs text-purple-500">(demo)</span>
                                        @endif
                                    </p>
                                    @if($vehicle['online'] ?? false)
                                        <span class="flex items-center gap-1 text-xs text-green-600 font-medium">
                                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>Online
                                        </span>
                                    @else
                                        <span class="flex items-center gap-1 text-xs text-gray-400">
                                            <span class="w-2 h-2 bg-gray-300 rounded-full"></span>Offline
                                        </span>
                                    @endif
                                </div>

                                <p class="text-xs text-gray-400">{{ $vehicle['make'] }} {{ $vehicle['model'] }}</p>

                                @if($vehicle['online'] ?? false)
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ round($vehicle['speed'] ?? 0) }} km/h
                                        · {{ ($vehicle['ignition'] ?? false) ? 'Ignition on' : 'Ignition off' }}
                                    </p>
                                @elseif($vehicle['lastUpdate'] ?? null)
                                    <p class="text-xs text-gray-400 mt-1">
                                        Last seen: {{ \Carbon\Carbon::parse($vehicle['lastUpdate'])->diffForHumans() }}
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
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const vehicles = @json($dashboard['vehicles'] ?? []);

        const map = L.map('map', {
            center: [7.8731, 80.7718],
            zoom: 8,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(map);

        function makeIcon(online) {
            return L.divIcon({
                className: '',
                html: `<div style="width:36px;height:36px;background:${online ? '#FA6908' : '#9CA3AF'};border:3px solid white;border-radius:50%;box-shadow:0 2px 6px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;">
                    <svg width="18" height="18" fill="white" viewBox="0 0 24 24">
                        <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                    </svg>
                </div>`,
                iconSize: [36, 36],
                iconAnchor: [18, 18],
                popupAnchor: [0, -20],
            });
        }

        const markers = {};
        const bounds  = [];

        vehicles.forEach(v => {
            if (!v.latitude || !v.longitude) return;
            const lat = parseFloat(v.latitude);
            const lng = parseFloat(v.longitude);
            if (isNaN(lat) || isNaN(lng)) return;

            const marker = L.marker([lat, lng], { icon: makeIcon(v.online) })
                .bindPopup(`
                    <div style="min-width:160px">
                        <p style="font-weight:600;margin-bottom:4px">${v.vehicleNumber}</p>
                        <p style="font-size:12px;color:#6B7280">${v.make} ${v.model}</p>
                        <p style="font-size:12px;margin-top:4px;color:${v.online ? '#16A34A' : '#9CA3AF'}">${v.online ? '● Online' : '○ Offline'}</p>
                        ${v.online ? `<p style="font-size:12px;color:#374151">${Math.round(v.speed)} km/h</p>` : ''}
                    </div>
                `)
                .addTo(map);

            markers[v.vehicleId] = marker;
            bounds.push([lat, lng]);
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [40, 40], maxZoom: 14 });
        }

        function focusVehicle(vehicleId, lat, lng) {
            if (!lat || !lng) return;
            map.setView([parseFloat(lat), parseFloat(lng)], 15, { animate: true });
            if (markers[vehicleId]) markers[vehicleId].openPopup();
        }

        setTimeout(() => window.location.reload(), 30000);
    </script>

    
@endpush
