@extends('layouts.app')
@section('title', 'Dashboard — ShaloTrack Fleet')
@section('page-title', 'Dashboard')

@section('content')
<style>
    /* ── Stat tiles ─────────────────────────────── */
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 20px;
        margin-bottom: 28px;
    }

    .stat-card {
        background: white;
        border-radius: 14px;
        border: 1px solid #f3f4f6;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .stat-icon.navy {
        background: #eef1f7;
    }

    .stat-icon.green {
        background: #f0fdf4;
    }

    .stat-icon.gray {
        background: #f9fafb;
    }

    .stat-icon.orange {
        background: #fff7ed;
    }

    .stat-label {
        font-size: 12px;
        color: #9ca3af;
        margin-bottom: 4px;
        font-weight: 500;
    }

    .stat-value {
        font-size: 28px;
        font-weight: 700;
        line-height: 1;
    }

    .stat-value.navy {
        color: #021F4A;
    }

    .stat-value.green {
        color: #16a34a;
    }

    .stat-value.gray {
        color: #9ca3af;
    }

    .stat-value.orange {
        color: #FA6908;
    }

    /* ── Main grid ──────────────────────────────── */
    .dash-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 320px;
        gap: 24px;
    }

    /* ── Map panel ──────────────────────────────── */
    #map {
        height: 500px;
        width: 100%;
    }

    /* ── Vehicle sidebar ────────────────────────── */
    .vlist {
        max-height: 538px;
        overflow-y: auto;
    }

    .vrow {
        padding: 14px 18px;
        border-bottom: 1px solid #f9fafb;
        cursor: pointer;
        transition: background 0.15s;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .vrow:last-child {
        border-bottom: none;
    }

    .vrow:hover {
        background: #fafafa;
    }

    .vrow.active {
        background: #fff7ed;
        border-left: 3px solid #FA6908;
    }

    .vrow-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .vrow-dot.online {
        background: #22c55e;
    }

    .vrow-dot.offline {
        background: #d1d5db;
    }

    .vrow-body {
        flex: 1;
        min-width: 0;
    }

    .vrow-plate {
        font-size: 13px;
        font-weight: 600;
        color: #1f2937;
    }

    .vrow-make {
        font-size: 11px;
        color: #9ca3af;
        margin-top: 1px;
    }

    .vrow-meta {
        font-size: 11px;
        color: #6b7280;
        margin-top: 3px;
    }

    .vrow-meta.offline-text {
        color: #d1d5db;
    }

    .badge-online {
        font-size: 10px;
        font-weight: 600;
        color: #16a34a;
        background: #f0fdf4;
        border-radius: 6px;
        padding: 2px 7px;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .badge-offline {
        font-size: 10px;
        color: #9ca3af;
        background: #f9fafb;
        border-radius: 6px;
        padding: 2px 7px;
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* ── Connection status pill ─────────────────── */
    .conn-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 500;
    }

    .conn-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
    }

    /* ── Section headers ────────────────────────── */
    .panel-header {
        padding: 16px 20px;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .panel-title {
        font-size: 14px;
        font-weight: 600;
        color: #1f2937;
    }

    /* ── Responsive ─────────────────────────────── */
    @media (max-width: 1100px) {
        .dash-grid {
            grid-template-columns: minmax(0, 1fr);
        }

        .vlist {
            max-height: 360px;
        }
    }

    @media (max-width: 767px) {
        .stat-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 18px;
        }

        .stat-card {
            padding: 14px;
            gap: 10px;
        }

        .stat-icon {
            width: 36px;
            height: 36px;
        }

        .stat-value {
            font-size: 22px;
        }

        .dash-grid {
            gap: 16px;
        }

        #map {
            height: 300px;
        }
    }
</style>

{{-- ─── ERROR BANNER ──────────────────────────────────── --}}
@if($error)
<div style="margin-bottom:20px;padding:14px 18px;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;color:#b91c1c;font-size:13px;display:flex;align-items:center;gap:10px;">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
        <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" />
    </svg>
    {{ $error }}
    <button onclick="window.location.reload()" style="margin-left:auto;color:#b91c1c;text-decoration:underline;background:none;border:none;cursor:pointer;font-size:13px;">Retry</button>
</div>
@endif

@if($dashboard)

{{-- ─── STAT TILES ──────────────────────────────────── --}}
<div class="stat-grid">

    {{-- Total --}}
    <div class="stat-card">
        <div class="stat-icon navy">
            <svg width="22" height="22" fill="none" stroke="#021F4A" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M1 17h22M5 17V9a2 2 0 012-2h10a2 2 0 012 2v8" stroke-linecap="round" />
                <path d="M4 17l-1 2M20 17l1 2" stroke-linecap="round" />
                <circle cx="7.5" cy="17" r="1.5" fill="#021F4A" stroke="none" />
                <circle cx="16.5" cy="17" r="1.5" fill="#021F4A" stroke="none" />
                <path d="M5 9h14" stroke-linecap="round" />
            </svg>
        </div>
        <div>
            <p class="stat-label">Total Vehicles</p>
            <p class="stat-value navy" id="stat-total">{{ $dashboard['vehicleCount'] ?? 0 }}</p>
        </div>
    </div>

    {{-- Online --}}
    <div class="stat-card">
        <div class="stat-icon green">
            <svg width="22" height="22" fill="none" stroke="#16a34a" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M5 12.55a11 11 0 0114.08 0" stroke-linecap="round" />
                <path d="M1.42 9a16 16 0 0121.16 0" stroke-linecap="round" />
                <path d="M8.53 16.11a6 6 0 016.95 0" stroke-linecap="round" />
                <circle cx="12" cy="20" r="1" fill="#16a34a" stroke="none" />
            </svg>
        </div>
        <div>
            <p class="stat-label">Online</p>
            <p class="stat-value green" id="stat-online">{{ $dashboard['onlineVehicles'] ?? 0 }}</p>
        </div>
    </div>

    {{-- Offline --}}
    <div class="stat-card">
        <div class="stat-icon gray">
            <svg width="22" height="22" fill="none" stroke="#9ca3af" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M1 1l22 22M16.72 11.06A10.94 10.94 0 0119 12.55M5 12.55a10.94 10.94 0 015.17-2.39M10.71 5.05A16 16 0 0122.56 9M1.42 9a15.91 15.91 0 014.7-2.88M8.53 16.11a6 6 0 016.95 0M12 20h.01" stroke-linecap="round" />
            </svg>
        </div>
        <div>
            <p class="stat-label">Offline</p>
            <p class="stat-value gray" id="stat-offline">{{ $dashboard['offlineVehicles'] ?? 0 }}</p>
        </div>
    </div>

    {{-- Moving --}}
    @php
    $movingCount = collect($dashboard['vehicles'] ?? [])
    ->filter(fn($v) => ($v['online'] ?? false) && ($v['speed'] ?? 0) > 0)
    ->count();
    @endphp
    <div class="stat-card">
        <div class="stat-icon orange">
            <svg width="22" height="22" fill="none" stroke="#FA6908" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M13 17h8m0 0l-4-4m4 4l-4 4M3 12h8m0 0L7 8m4 4l-4 4" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </div>
        <div>
            <p class="stat-label">Moving</p>
            <p class="stat-value orange" id="stat-moving">{{ $movingCount }}</p>
        </div>
    </div>

</div>

{{-- ─── SOS ─────────────────────────────────────────── --}}
@if(!empty($dashboard['vehicles']))
<div style="margin-bottom:20px;padding:12px 16px;background:#fef2f2;border:1px solid #fecaca;border-radius:14px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
    <div style="flex:1;min-width:200px;">
        <p style="font-size:13px;font-weight:600;color:#991b1b;">Emergency SOS</p>
        <p style="font-size:12px;color:#b91c1c;margin-top:2px;">Sends a distress signal for a vehicle. You must press and hold for 3 seconds to confirm.</p>
    </div>
    <button type="button" onclick="openSosModal()"
        style="padding:9px 20px;background:#dc2626;color:#fff;font-size:13px;font-weight:700;border:none;border-radius:10px;cursor:pointer;">
        SOS
    </button>
</div>
@endif

{{-- ─── MAP + VEHICLES ──────────────────────────────── --}}
<div class="dash-grid">

    {{-- Live map --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="panel-header">
            <h3 class="panel-title">Live Map</h3>
            <span id="realtime-status" class="conn-pill" style="color:#9ca3af;">
                <span class="conn-dot" style="background:#d1d5db;"></span>Connecting…
            </span>
        </div>
        <div id="map"></div>
    </div>

    {{-- Vehicles sidebar --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="panel-header">
            <h3 class="panel-title">Vehicles</h3>
            <span style="font-size:11px;color:#9ca3af;" id="vlist-summary">
                {{ count($dashboard['vehicles'] ?? []) }} total
            </span>
        </div>

        @if(empty($dashboard['vehicles']))
        <div style="padding:32px 20px;text-align:center;">
            <p style="color:#9ca3af;font-size:13px;">No vehicles found.</p>
            <a href="/vehicles" style="margin-top:10px;display:inline-block;color:#FA6908;font-size:13px;font-weight:500;">Add a vehicle →</a>
        </div>
        @else
        <div class="vlist" id="vehicle-list">
            @foreach($dashboard['vehicles'] as $vehicle)
            @php
            $vid = $vehicle['vehicleId'];
            $online = (bool)($vehicle['online'] ?? false);
            $plate = $vehicle['vehicleNumber'] ?? $vid;
            $make = trim(($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? ''));
            $speed = round($vehicle['speed'] ?? 0);
            $ignition = (bool)($vehicle['ignition'] ?? false);
            $lastSeen = $vehicle['lastUpdate'] ?? null;
            @endphp
            <div class="vrow" id="vrow-{{ $vid }}" onclick="focusVehicle('{{ $vid }}')">
                <div class="vrow-dot {{ $online ? 'online' : 'offline' }}" id="vdot-{{ $vid }}"></div>
                <div class="vrow-body">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                        <p class="vrow-plate">
                            {{ $plate }}
                            @if($vehicle['isDemoVehicle'] ?? $vehicle['isDemo'] ?? false)
                            <span style="font-size:10px;color:#FA6908;font-weight:600;margin-left:4px;">[DEMO]</span>
                            @endif
                            @if($vehicle['isShared'] ?? false)
                            <span style="font-size:10px;color:#7e22ce;font-weight:600;margin-left:4px;">[SHARED]</span>
                            @endif
                        </p>
                        <span id="vbadge-{{ $vid }}" class="{{ $online ? 'badge-online' : 'badge-offline' }}">
                            {{ $online ? 'Online' : 'Offline' }}
                        </span>
                    </div>
                    @if($make)
                    <p class="vrow-make">{{ $make }}</p>
                    @endif
                    <p class="vrow-meta{{ !$online ? ' offline-text' : '' }}" id="vmeta-{{ $vid }}">
                        @if($online)
                        {{ $speed }} km/h · {{ $ignition ? 'Ignition on' : 'Ignition off' }}
                        @elseif($lastSeen)
                        Last seen {{ \App\Support\LocalTime::ago($lastSeen) }}
                        @else
                        No location data
                        @endif
                    </p>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

</div>

@elseif(!$error)
<div style="text-align:center;padding:80px 20px;">
    <p style="color:#9ca3af;font-size:14px;">No data available. Please refresh.</p>
</div>
@endif

@include('partials.marker-glide')

{{-- ─── JS (inline — no @push dependency) ────────────── --}}
<script src="https://cdn.jsdelivr.net/npm/@microsoft/signalr@8.0.7/dist/browser/signalr.min.js"></script>

<script>
    'use strict';

    /* Vehicles deleted on the Vehicles tab this session. Workaround until the
       C# dashboard endpoint stops returning deleted vehicles (server-side fix). */
    const deletedIds = (() => {
        try {
            return new Set(JSON.parse(sessionStorage.getItem('st_deleted_vehicles') ?? '[]').map(i => String(i).toLowerCase()));
        } catch (_) {
            return new Set();
        }
    })();

    const vehiclesRaw = @json($dashboard['vehicles'] ?? []).filter(v => !deletedIds.has(String(v.vehicleId).toLowerCase()));

    /* Remove already-rendered rows of deleted vehicles, then fix counters */
    (function pruneDeleted() {
        if (!deletedIds.size) return;
        document.querySelectorAll('.vrow').forEach(r => {
            const id = r.id.replace('vrow-', '').toLowerCase();
            if (deletedIds.has(id)) r.remove();
        });
        const total = vehiclesRaw.length;
        const elT = document.getElementById('stat-total');
        const elS = document.getElementById('vlist-summary');
        if (elT) elT.textContent = total;
        if (elS) elS.textContent = total + ' total';
    })();

    /* vehicleMap keyed by lowercased vehicleId */
    const vehicleMap = {};
    vehiclesRaw.forEach(v => {
        vehicleMap[v.vehicleId.toLowerCase()] = {
            vehicleId: v.vehicleId,
            vehicleNumber: v.vehicleNumber,
            make: v.make,
            model: v.model,
            online: !!(v.online),
            speed: v.speed ?? 0,
            ignition: !!(v.ignition),
            latitude: v.latitude,
            longitude: v.longitude,
            heading: v.heading ?? v.bearing ?? null, // degrees 0–359, null = unknown
        };
    });

    if (deletedIds.size) recalcStats();   // online/offline/moving tiles must exclude deleted vehicles

    /* ── Map state ─────────────────────────────────────────── */
    let gmap = null;
    const markers = {};
    const infoWins = {};
    const trails = {};
    const polylines = {};
    const TRAIL_MAX = 60;

    /* ── XSS escape ───────────────────────────────────────── */
    function esc(s) {
        return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    /* ── Top-down car marker ──────────────────────────────────
     *
     *  Organic sedan silhouette viewed from directly above.
     *  The POINTED NOSE is the front — no separate direction arrow
     *  needed; the shape itself communicates heading.
     *
     *  Geometry (40×40 viewBox, anchor at centre 20,20):
     *    Body  : bezier path, pointed at y=5 (front), rounded at y=35 (rear)
     *    Wheels: 4 dark rects protruding from body sides
     *    Glass : semi-transparent paths for front + rear screens
     *    Lights: yellow headlights (front), red taillights (rear)
     *
     *  online  → ShaloTrack orange (#FA6908)
     *  offline → neutral grey     (#9CA3AF)
     *
     *  Heading (0–359°, 0 = north, clockwise): rotates the whole <g>
     *  so the pointed nose tracks the real bearing when available.
     * ──────────────────────────────────────────────────────── */
    function makeMarkerIcon(online, heading) {
        const bodyColor = online ? '#FA6908' : '#9CA3AF';
        const wheelColor = online ? '#7c2d08' : '#374151';
        const glassColor = 'rgba(210,240,255,0.55)';
        const rot = (heading != null && !isNaN(heading)) ? Math.round(heading) : 0;

        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40">
  <g transform="rotate(${rot},20,20)">

    <!-- Subtle ground shadow for depth on light map tiles -->
    <ellipse cx="20.5" cy="21" rx="13" ry="17" fill="rgba(0,0,0,0.10)"/>

    <!-- Wheels: rendered first so body covers their inner edges -->
    <rect x="7"  y="11" width="5" height="9" rx="2.5" fill="${wheelColor}"/>
    <rect x="28" y="11" width="5" height="9" rx="2.5" fill="${wheelColor}"/>
    <rect x="7"  y="22" width="5" height="9" rx="2.5" fill="${wheelColor}"/>
    <rect x="28" y="22" width="5" height="9" rx="2.5" fill="${wheelColor}"/>

    <!-- Car body
         Right front : cubic bezier from pointed tip (20,5) curves out to right side (28,14)
         Right side  : straight down to (28,27)
         Right rear  : rounds off to tail centre (20,35)
         Left rear   : mirrors right
         Left side   : straight up to (12,14)
         Left front  : bezier back to tip (20,5)
    -->
    <path d="M 20,5
             C 26.5,5 28,9 28,14
             L 28,27
             C 28,32.5 24.5,35 20,35
             C 15.5,35 12,32.5 12,27
             L 12,14
             C 12,9 13.5,5 20,5 Z"
          fill="${bodyColor}" stroke="white" stroke-width="1.5"/>

    <!-- Front windshield — trapezoid following the hood curve -->
    <path d="M 17.5,11
             C 17.5,9.5 22.5,9.5 22.5,11
             L 22,17
             C 22,18.5 18,18.5 18,17 Z"
          fill="${glassColor}"/>

    <!-- Rear windshield — slightly dimmer -->
    <path d="M 17,24
             C 17,22.5 23,22.5 23,24
             L 22.5,30
             C 22.5,31.5 17.5,31.5 17.5,30 Z"
          fill="${glassColor}" opacity="0.6"/>

    <!-- Headlights: warm yellow, split pair at nose -->
    <rect x="15.5" y="7"  width="3.5" height="2" rx="1" fill="rgba(255,255,180,0.95)"/>
    <rect x="21"   y="7"  width="3.5" height="2" rx="1" fill="rgba(255,255,180,0.95)"/>

    <!-- Taillights: red pair at boot -->
    <rect x="15.5" y="33" width="3.5" height="2" rx="1" fill="rgba(220,30,30,0.9)"/>
    <rect x="21"   y="33" width="3.5" height="2" rx="1" fill="rgba(220,30,30,0.9)"/>

  </g>
</svg>`;

        return {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
            scaledSize: new google.maps.Size(40, 40),
            anchor: new google.maps.Point(20, 20),
        };
    }

    /* ── InfoWindow HTML ──────────────────────────────────── */
    function buildInfoHtml(vehicleId) {
        const v = vehicleMap[vehicleId.toLowerCase()];
        if (!v) return '';
        const name = esc(v.vehicleNumber);
        const make = esc(`${v.make ?? ''} ${v.model ?? ''}`.trim());
        const speed = Math.round(v.speed ?? 0);
        return `<div style="font-family:-apple-system,sans-serif;padding:4px 2px;min-width:160px;">
        <p style="font-weight:700;font-size:13px;color:#1f2937;margin:0 0 3px;">${name}</p>
        ${make ? `<p style="font-size:11px;color:#9ca3af;margin:0 0 6px;">${make}</p>` : ''}
        <p style="font-size:12px;color:${v.online ? '#16a34a' : '#9ca3af'};margin:0;">
            ${v.online ? '● Online' : '○ Offline'}
        </p>
        ${v.online ? `<p style="font-size:12px;color:#374151;margin:4px 0 0;">${speed} km/h</p>` : ''}
    </div>`;
    }

    let activeInfoVid = null;

    function openInfo(vid) {
        Object.values(infoWins).forEach(w => w.close());
        activeInfoVid = vid;
        infoWins[vid]?.open({
            map: gmap,
            anchor: markers[vid]
        });
    }

    /* ── Focus vehicle from sidebar ───────────────────────── */
    function focusVehicle(vehicleId) {
        const vid = vehicleId.toLowerCase();
        const m = markers[vid];
        document.querySelectorAll('.vrow').forEach(r => r.classList.remove('active'));
        const row = document.getElementById('vrow-' + vehicleId);
        if (row) row.classList.add('active');
        if (m && gmap) {
            gmap.panTo(m.getPosition());
            gmap.setZoom(15);
            openInfo(vid);
        }
    }

    /* ── Google Maps callback ─────────────────────────────── */
    function initMap() {
        gmap = new google.maps.Map(document.getElementById('map'), {
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
            const heading = v.heading ?? v.bearing ?? null;
            trails[vid] = [{
                lat,
                lng
            }];

            markers[vid] = new google.maps.Marker({
                position: {
                    lat,
                    lng
                },
                map: gmap,
                title: v.vehicleNumber,
                icon: makeMarkerIcon(!!(v.online), heading),
                zIndex: 10,
            });

            infoWins[vid] = new google.maps.InfoWindow({
                content: buildInfoHtml(vid)
            });
            markers[vid].addListener('click', () => openInfo(vid));

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
            gmap.fitBounds(bounds, {
                top: 40,
                right: 40,
                bottom: 40,
                left: 40
            });
            /* prevent over-zooming on a single marker */
            google.maps.event.addListenerOnce(gmap, 'bounds_changed', () => {
                if (gmap.getZoom() > 14) gmap.setZoom(14);
            });
        }
    }

    /* ── Real-time marker update ──────────────────────────── */
    function updateMarker(vehicleId, data) {
        const lat = parseFloat(data.latitude);
        const lng = parseFloat(data.longitude);
        if (isNaN(lat) || isNaN(lng)) return;

        const pos = {
            lat,
            lng
        };
        /* Accept heading from any field name the C# hub may send */
        const heading = data.heading ?? data.bearing ?? data.course ?? null;
        const icon = makeMarkerIcon(true, heading);
        const devMs = data.lastUpdate ? Date.parse(data.lastUpdate) : NaN;

        if (!trails[vehicleId]) trails[vehicleId] = [];

        let rejected = false;
        const wasOffline = !!vehicleMap[vehicleId] && !vehicleMap[vehicleId].online;

        if (markers[vehicleId]) {
            /* Glide to the new fix instead of hopping. GPS noise and impossible jumps
               are rejected and must not touch the trail or the stored position. */
            const result = MarkerGlide.move(vehicleId, markers[vehicleId], pos, heading, devMs, {
                paint: deg => markers[vehicleId].setIcon(makeMarkerIcon(true, deg)),
                frame: p => {
                    const path = polylines[vehicleId]?.getPath();
                    if (path && path.getLength()) path.setAt(path.getLength() - 1, new google.maps.LatLng(p.lat, p.lng));
                },
            });
            rejected = result === 'noise' || result === 'jump';
            if (wasOffline) markers[vehicleId].setIcon(icon); /* grey -> orange as soon as it reports */
            if (!rejected) {
                trails[vehicleId].push(pos);
                if (trails[vehicleId].length > TRAIL_MAX) trails[vehicleId].shift();
            }
        } else {
            trails[vehicleId].push(pos);
            if (trails[vehicleId].length > TRAIL_MAX) trails[vehicleId].shift();
            const v = vehicleMap[vehicleId];
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

        if (!rejected) {
            if (polylines[vehicleId]) {
                polylines[vehicleId].setPath(trails[vehicleId]);
                /* the line's tip follows the marker, not the target, while it glides */
                const cur = markers[vehicleId].getPosition();
                const path = polylines[vehicleId].getPath();
                if (cur && path.getLength() > 1) path.setAt(path.getLength() - 1, cur);
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
        }

        /* Merge state into vehicleMap */
        if (vehicleMap[vehicleId]) {
            const wasOnline = vehicleMap[vehicleId].online;
            Object.assign(vehicleMap[vehicleId], {
                ...(rejected ? {} : {
                    latitude: data.latitude,
                    longitude: data.longitude
                }),
                online: true,
                speed: data.speed ?? 0,
                ignition: !!(data.ignition ?? data.ignitionStatus),
                heading,
            });
            /* Refresh info window if it's open */
            if (activeInfoVid === vehicleId) {
                infoWins[vehicleId]?.setContent(buildInfoHtml(vehicleId));
            }
            /* Recalc stat tiles only on status change */
            if (!wasOnline) recalcStats();
        }
    }

    /* ── Sidebar row update ───────────────────────────────── */
    function updateSidebar(vehicleId, data) {
        const dot = document.getElementById('vdot-' + vehicleId);
        const badge = document.getElementById('vbadge-' + vehicleId);
        const meta = document.getElementById('vmeta-' + vehicleId);

        if (dot) {
            dot.classList.remove('offline');
            dot.classList.add('online');
        }
        if (badge) {
            badge.className = 'badge-online';
            badge.textContent = 'Online';
        }
        if (meta) {
            const speed = Math.round(data.speed ?? 0);
            const ignition = (data.ignition ?? data.ignitionStatus) ? 'Ignition on' : 'Ignition off';
            meta.className = 'vrow-meta';
            meta.textContent = `${speed} km/h · ${ignition}`;
        }
    }

    /* ── Recalculate stat tiles from vehicleMap ───────────── */
    function recalcStats() {
        let online = 0,
            offline = 0,
            moving = 0;
        Object.values(vehicleMap).forEach(v => {
            if (v.online) {
                online++;
                if ((v.speed ?? 0) > 0) moving++;
            } else {
                offline++;
            }
        });
        const elO = document.getElementById('stat-online');
        const elX = document.getElementById('stat-offline');
        const elM = document.getElementById('stat-moving');
        if (elO) elO.textContent = online;
        if (elX) elX.textContent = offline;
        if (elM) elM.textContent = moving;
    }

    /* ── Connection status pill ───────────────────────────── */
    function setStatus(state) {
        const el = document.getElementById('realtime-status');
        if (!el) return;
        const cfg = {
            live: {
                color: '#16a34a',
                dot: '#22c55e',
                label: 'Live',
                pulse: true
            },
            reconnecting: {
                color: '#d97706',
                dot: '#f59e0b',
                label: 'Reconnecting…',
                pulse: true
            },
            disconnected: {
                color: '#9ca3af',
                dot: '#d1d5db',
                label: 'Disconnected',
                pulse: false
            },
        } [state];
        if (!cfg) return;
        const anim = cfg.pulse ? 'animation:pulse 2s infinite;' : '';
        el.style.color = cfg.color;
        el.innerHTML = `<span class="conn-dot" style="background:${cfg.dot};${anim}"></span>${cfg.label}`;
    }

    /* ── SignalR bootstrap ────────────────────────────────── */
    (async function initSignalR() {

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
            console.warn('[Dashboard SignalR] Token fetch failed', err);
            setStatus('disconnected');
            setTimeout(() => window.location.reload(), 90_000);
            return;
        }

        const connection = new signalR.HubConnectionBuilder()
            .withUrl('https://api.shalotrack.com/hubs/location', {
                accessTokenFactory: () => token,
            })
            .withAutomaticReconnect([2000, 5000, 10000, 30000])
            .configureLogging(signalR.LogLevel.Warning)
            .build();

        connection.on('LocationUpdated', (data) => {
            const vid = (data.vehicleId || '').toLowerCase();
            if (!vehicleMap[vid]) return;
            updateMarker(vid, data);
            updateSidebar(vid, data);
        });

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
            setTimeout(() => window.location.reload(), 10_000);
        });

        try {
            await connection.start();
            setStatus('live');
            await joinAllGroups();
        } catch (err) {
            console.error('[Dashboard SignalR] Initial connection failed:', err);
            setStatus('disconnected');
            setTimeout(() => window.location.reload(), 90_000);
        }

        async function joinAllGroups() {
            for (const vid of Object.keys(vehicleMap)) {
                try {
                    await connection.invoke('JoinVehicleGroup', vid);
                } catch (e) {
                    console.warn('[Dashboard SignalR] JoinVehicleGroup failed for', vid, e.message);
                }
            }
        }

    })();
</script>

{{-- ─── SOS MODAL ────────────────────────────────────── --}}
<div id="sos-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeSosModal()"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl w-full sm:max-w-sm relative p-6 text-center max-h-[90vh] max-h-[90dvh] overflow-y-auto">

            {{-- Step 1: choose vehicle + hold to confirm --}}
            <div id="sos-step-confirm">
                <h3 class="font-semibold text-gray-800 mb-1">Send SOS</h3>
                <p class="text-xs text-gray-500 mb-4">Choose the vehicle, then press and hold the button for 3 seconds.</p>
                <select id="sos-vehicle" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-red-500 mb-5"></select>
                <p id="sos-none" class="hidden text-sm text-gray-500 mb-2">SOS can only be sent for vehicles you own. Shared and demo vehicles are not eligible.</p>

                <button id="sos-hold" type="button" aria-label="Press and hold for 3 seconds to send SOS"
                    style="position:relative;width:132px;height:132px;border-radius:50%;border:none;background:#dc2626;color:#fff;font-weight:800;font-size:18px;cursor:pointer;touch-action:none;user-select:none;-webkit-user-select:none;overflow:hidden;">
                    <span id="sos-fill" style="position:absolute;left:0;right:0;bottom:0;height:0%;background:#7f1d1d;transition:none;"></span>
                    <span style="position:relative;line-height:1.2;">Hold<br>for SOS</span>
                </button>
                <p id="sos-error" class="text-red-600 text-sm mt-4 hidden"></p>
                <button onclick="closeSosModal()" class="mt-5 text-sm text-gray-400 hover:text-gray-600">Cancel</button>
            </div>

            {{-- Step 2: sent --}}
            <div id="sos-step-sent" class="hidden">
                <div style="width:48px;height:48px;border-radius:50%;background:#f0fdf4;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                    <svg width="24" height="24" fill="none" stroke="#16a34a" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </div>
                <h3 class="font-semibold text-gray-800 mb-1">SOS sent</h3>
                <p class="text-sm text-gray-500 mb-4">The monitoring centre has been alerted. You can also call your emergency contacts:</p>
                <div id="sos-contacts" class="space-y-2 text-left mb-4"></div>
                <a href="/emergency-contacts" id="sos-no-contacts" class="hidden text-sm text-[#FA6908] font-medium">Add emergency contacts →</a>
                <button onclick="closeSosModal()" class="block w-full mt-2 py-2.5 border border-gray-200 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    /* SOS — mirrors the Android press-and-hold-3s flow. */
    (function() {
        const SOS_CSRF = '{{ csrf_token() }}';
        const HOLD_MS = 3000;
        const holdBtn = document.getElementById('sos-hold');
        const fill = document.getElementById('sos-fill');
        if (!holdBtn) return;

        let raf = null,
            startedAt = 0,
            sending = false;

        window.openSosModal = function() {
            const sel = document.getElementById('sos-vehicle');
            sel.innerHTML = '';
            /* SOS is for vehicles the customer OWNS: the API rejects it for shared
               vehicles, and demo vehicles are read-only. */
            const eligible = (vehiclesRaw || []).filter(v => !v.isShared && !(v.isDemoVehicle ?? v.isDemo ?? false));
            eligible.forEach(v => {
                const o = document.createElement('option');
                o.value = v.vehicleId;
                o.textContent = v.vehicleNumber;
                sel.appendChild(o);
            });
            const none = eligible.length === 0;
            sel.classList.toggle('hidden', none);
            document.getElementById('sos-hold').classList.toggle('hidden', none);
            document.getElementById('sos-none').classList.toggle('hidden', !none);
            document.getElementById('sos-error').classList.add('hidden');
            document.getElementById('sos-step-confirm').classList.remove('hidden');
            document.getElementById('sos-step-sent').classList.add('hidden');
            document.getElementById('sos-modal').classList.remove('hidden');
        };

        window.closeSosModal = function() {
            cancelHold();
            document.getElementById('sos-modal').classList.add('hidden');
        };

        function setProgress(p) {
            fill.style.height = Math.round(p * 100) + '%';
        }

        function cancelHold() {
            if (raf) cancelAnimationFrame(raf);
            raf = null;
            setProgress(0);
        }

        function startHold() {
            if (sending || raf) return;
            if (!document.getElementById('sos-vehicle').value) return;
            startedAt = performance.now();
            const tick = (now) => {
                const p = Math.min(1, (now - startedAt) / HOLD_MS);
                setProgress(p);
                if (p >= 1) {
                    raf = null;
                    send();
                } else {
                    raf = requestAnimationFrame(tick);
                }
            };
            raf = requestAnimationFrame(tick);
        }

        holdBtn.addEventListener('pointerdown', (e) => {
            e.preventDefault();
            startHold();
        });
        ['pointerup', 'pointercancel', 'pointerleave'].forEach(t => holdBtn.addEventListener(t, cancelHold));
        holdBtn.addEventListener('contextmenu', e => e.preventDefault());
        /* Keyboard users: hold Space/Enter for 3 seconds. */
        holdBtn.addEventListener('keydown', (e) => {
            if ((e.key === ' ' || e.key === 'Enter') && !e.repeat) {
                e.preventDefault();
                startHold();
            }
        });
        holdBtn.addEventListener('keyup', cancelHold);

        async function send() {
            sending = true;
            const errEl = document.getElementById('sos-error');
            errEl.classList.add('hidden');
            const vid = document.getElementById('sos-vehicle').value;
            try {
                const res = await fetch('/sos/' + encodeURIComponent(vid), {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': SOS_CSRF
                    },
                });
                if (res.status === 401) {
                    window.location.href = '/login?expired=1';
                    return;
                }
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.success) {
                    showSent(data.contacts || []);
                } else {
                    errEl.textContent = res.status === 429 ?
                        'Too many SOS attempts. Wait a minute, then try again — or call your emergency contacts directly.' :
                        (data.message || "Couldn't send SOS. Please try again.");
                    errEl.classList.remove('hidden');
                }
            } catch (_) {
                errEl.textContent = 'Network error — SOS could not be sent. Try again.';
                errEl.classList.remove('hidden');
            } finally {
                sending = false;
                setProgress(0);
            }
        }

        function showSent(contacts) {
            document.getElementById('sos-step-confirm').classList.add('hidden');
            document.getElementById('sos-step-sent').classList.remove('hidden');
            const box = document.getElementById('sos-contacts');
            box.innerHTML = '';
            const valid = contacts.filter(c => c.phoneNumber);
            document.getElementById('sos-no-contacts').classList.toggle('hidden', valid.length > 0);
            valid.forEach(c => {
                const a = document.createElement('a');
                a.href = 'tel:' + String(c.phoneNumber).replace(/[^0-9+]/g, '');
                a.className = 'flex items-center justify-between px-3 py-2.5 border border-gray-200 rounded-lg hover:bg-gray-50';
                const left = document.createElement('div');
                const n = document.createElement('p');
                n.className = 'text-sm font-semibold text-gray-800';
                n.textContent = c.name + (c.relationship ? ' · ' + c.relationship : '');
                const p = document.createElement('p');
                p.className = 'text-xs text-gray-500';
                p.textContent = c.phoneNumber;
                left.appendChild(n);
                left.appendChild(p);
                const call = document.createElement('span');
                call.className = 'text-sm font-semibold text-green-600';
                call.textContent = 'Call';
                a.appendChild(left);
                a.appendChild(call);
                box.appendChild(a);
            });
        }
    })();
</script>

{{-- Google Maps — initMap defined above, must load after --}}
<script async defer
    src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&callback=initMap&loading=async">
</script>

@endsection