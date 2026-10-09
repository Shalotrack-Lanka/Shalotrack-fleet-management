@extends('layouts.app')
@section('title', 'Dashboard — ShaloTrack Fleet')
@section('page-title', 'Dashboard')
{{-- Immersive: the layout drops its white top bar and padding; the map is the page. --}}
@section('immersive', '1')

@section('content')
<style>
    html,
    body {
        overflow: hidden;
    }

    /* ── Full-screen map ────────────────────────── */
    /* The map sits in a fixed wrapper with a definite size; #map just fills it. Google's script
       rewrites the container's own inline position (to relative), which collapsed a bare
       position:fixed #map to 0 height and left the page blank. The wrapper cannot collapse. */
    #map-wrap {
        position: fixed;
        inset: 0;
        z-index: 0;
        background: #0d305f;
    }

    #map {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
    }

    /* Soft vignette so the glass keeps its contrast at the edges. */
    .d-vignette {
        position: fixed;
        inset: 0;
        z-index: 1;
        pointer-events: none;
        background: radial-gradient(120% 90% at 50% 40%, rgba(2, 15, 40, 0) 55%, rgba(2, 15, 40, .45) 100%);
    }

    .d-chip {
        position: fixed;
        z-index: 20;
        height: 48px;
        border-radius: 24px;
        display: flex;
        align-items: center;
        font-size: 13px;
    }

    .d-brand {
        top: 18px;
        left: 20px;
        padding: 0 20px;
        gap: 10px;
        text-decoration: none;
    }

    .d-brand .dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #FA6908;
        box-shadow: 0 0 12px #FA6908;
    }

    .d-brand b {
        font-size: 18px;
        font-weight: 700;
        letter-spacing: .2px;
        color: #fff;
    }

    .d-brand b span {
        color: #FA6908;
    }

    /* ── Stats pill ─────────────────────────────── */
    .d-stats {
        top: 18px;
        left: 50%;
        transform: translateX(-50%);
        padding: 0 6px;
        gap: 2px;
        white-space: nowrap;
    }

    .d-stat {
        padding: 0 14px;
        display: flex;
        align-items: center;
        gap: 7px;
        color: rgba(255, 255, 255, .78);
    }

    .d-stat b {
        color: #fff;
        font-size: 16px;
        font-weight: 700;
    }

    .d-stat+.d-stat {
        border-left: 1px solid rgba(255, 255, 255, .2);
    }

    .d-stat i {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }

    .d-stat i.on {
        background: #34d399;
        box-shadow: 0 0 10px #34d399;
    }

    .d-stat i.off {
        background: #94a3b8;
    }

    .d-stat i.mv {
        background: #FA6908;
        box-shadow: 0 0 10px #FA6908;
    }

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

    /* ── User chip + map theme ──────────────────── */
    .d-user-wrap {
        position: fixed;
        top: 18px;
        right: 20px;
        z-index: 22;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .d-iconbtn {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        padding: 0;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .d-iconbtn svg {
        width: 20px;
        height: 20px;
        stroke: #fff;
        fill: none;
        stroke-width: 1.8;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .d-user-btn {
        height: 48px;
        border-radius: 24px;
        padding: 0 6px 0 18px;
        gap: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        font-size: 14px;
        font-weight: 500;
        font-family: inherit;
    }

    .d-user-btn .av {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #FA6908;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #fff;
    }

    .d-user-menu {
        position: absolute;
        top: 56px;
        right: 0;
        width: 200px;
        border-radius: 18px;
        padding: 6px;
    }

    .d-user-menu a,
    .d-user-menu button {
        display: flex;
        width: 100%;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border: 0;
        border-radius: 12px;
        background: none;
        color: #fff;
        font-family: inherit;
        font-weight: 600;
        font-size: 13px;
        line-height: 1;
        text-align: left;
        text-decoration: none;
        cursor: pointer;
    }

    .d-user-menu a:hover,
    .d-user-menu button:hover {
        background: rgba(255, 255, 255, .14);
    }

    .d-user-menu .out {
        color: #fca5a5;
    }

    .d-user-menu form {
        margin: 0;
    }

    /* ── Vehicle islands ────────────────────────── */
    #vehicle-panel {
        position: fixed;
        z-index: 15;
        top: 84px;
        right: 20px;
        bottom: 112px;
        width: 312px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        overflow-y: auto;
        padding: 6px 4px 10px 10px;
        scrollbar-width: none;
        pointer-events: none;
    }

    #vehicle-panel::-webkit-scrollbar {
        display: none;
    }

    @media (min-width: 768px) {
        #vehicle-panel {
            -webkit-mask-image: linear-gradient(to bottom, #000 calc(100% - 30px), transparent);
            mask-image: linear-gradient(to bottom, #000 calc(100% - 30px), transparent);
        }
    }

    #vehicle-panel>* {
        pointer-events: auto;
        flex: none;
    }

    .d-vhead {
        border-radius: 22px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 13px;
        font-weight: 600;
    }

    .d-vhead .sum {
        font-weight: 500;
        color: rgba(255, 255, 255, .7);
        font-size: 12px;
        margin-left: 8px;
    }

    .d-vhead button {
        background: none;
        border: 0;
        color: #fff;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 10px;
        font-family: inherit;
        font-weight: 600;
        font-size: 12px;
    }

    .d-vhead button:hover {
        background: rgba(255, 255, 255, .14);
    }

    #vehicle-panel.collapsed .vrow {
        display: none;
    }

    .vrow {
        border-radius: 22px;
        padding: 14px 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: border-color .2s, box-shadow .2s;
    }

    .vrow:hover {
        border-color: rgba(255, 255, 255, .5);
    }

    .vrow:focus-visible {
        outline: 3px solid #fff;
        outline-offset: 2px;
    }

    .vrow.active {
        border-color: rgba(250, 105, 8, .85);
        box-shadow: 0 0 0 1px rgba(250, 105, 8, .6), 0 0 26px rgba(250, 105, 8, .35), var(--gl-shadow);
    }

    .vrow-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .vrow-dot.online {
        background: #34d399;
        box-shadow: 0 0 12px #34d399;
    }

    .vrow-dot.offline {
        background: #94a3b8;
    }

    .vrow-body {
        flex: 1;
        min-width: 0;
    }

    .vrow-plate {
        font-size: 15px;
        font-weight: 700;
        letter-spacing: .2px;
        color: #fff;
        margin: 0;
    }

    .vrow-make {
        font-size: 12px;
        color: rgba(255, 255, 255, .7);
        margin: 2px 0 0;
    }

    .vrow-meta {
        font-size: 12px;
        color: rgba(255, 255, 255, .85);
        margin: 4px 0 0;
    }

    .vrow-meta.offline-text {
        color: rgba(255, 255, 255, .55);
    }

    .badge-online,
    .badge-offline {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .4px;
        border-radius: 999px;
        padding: 3px 9px;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .badge-online {
        color: #a7f3d0;
        background: rgba(52, 211, 153, .25);
    }

    .badge-offline {
        color: rgba(255, 255, 255, .7);
        background: rgba(148, 163, 184, .25);
    }

    .tag-demo {
        font-size: 10px;
        color: #fdba74;
        font-weight: 700;
        margin-left: 4px;
    }

    .tag-shared {
        font-size: 10px;
        color: #d8b4fe;
        font-weight: 700;
        margin-left: 4px;
    }

    /* Gentle float: only with a pointer + room + motion allowed, and only for small fleets (blur repaints). */
    @keyframes d-float {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-7px);
        }
    }

    @media (hover: hover) and (min-width: 768px) and (prefers-reduced-motion: no-preference) {
        .vrow.float {
            animation: d-float 7s ease-in-out infinite;
            animation-delay: calc(var(--i, 0) * -2.3s);
        }

        .vrow.float:hover {
            animation-play-state: paused;
        }
    }

    .d-empty {
        border-radius: 22px;
        padding: 22px 20px;
        text-align: center;
        font-size: 13px;
        color: rgba(255, 255, 255, .85);
    }

    .d-empty a {
        color: #fdba74;
        font-weight: 600;
        display: inline-block;
        margin-top: 8px;
    }

    /* ── SOS island ─────────────────────────────── */
    .d-sos {
        position: fixed;
        z-index: 20;
        left: 20px;
        bottom: 30px;
        height: 56px;
        padding: 0 22px 0 8px;
        border-radius: 28px;
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        font: inherit;
        text-align: left;
    }

    .d-sos:hover {
        transform: scale(1.03);
    }

    .d-sos:focus-visible {
        outline: 3px solid #fff;
        outline-offset: 3px;
    }

    .d-sos .c {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #ef4444;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 12px;
        box-shadow: 0 0 18px rgba(239, 68, 68, .8);
        color: #fff;
    }

    .d-sos .t {
        font-size: 13px;
        line-height: 1.25;
        color: #fff;
    }

    .d-sos .t small {
        display: block;
        color: rgba(255, 255, 255, .75);
        font-size: 11.5px;
    }

    .d-error {
        position: fixed;
        z-index: 21;
        top: 76px;
        left: 50%;
        transform: translateX(-50%);
        max-width: min(560px, calc(100% - 32px));
        border-radius: 18px;
        padding: 12px 16px;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .d-error button {
        margin-left: auto;
        background: none;
        border: 0;
        color: #fff;
        text-decoration: underline;
        cursor: pointer;
        font: inherit;
    }

    .d-center {
        position: fixed;
        z-index: 15;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    /* Google's own UI keeps clear of our islands. */
    .gm-style .gm-style-iw-c {
        border-radius: 16px;
    }

    /* ── Tablets / small laptops: tighten the top chips so they never collide ── */
    @media (min-width: 768px) and (max-width: 1099px) {
        .d-user-btn .nm {
            display: none;
        }

        .conn-pill .lbl {
            display: none;
        }

        .d-stat {
            padding: 0 10px;
        }

        .d-brand {
            padding: 0 16px;
        }
    }

    @media (min-width: 768px) and (max-width: 899px) {
        .d-stat {
            padding: 0 8px;
            gap: 5px;
            font-size: 12px;
        }

        .d-stat b {
            font-size: 14px;
        }

        .d-brand b {
            font-size: 16px;
        }

        .d-brand {
            padding: 0 14px;
            gap: 8px;
        }
    }

    /* ── Phones ─────────────────────────────────── */
    @media (max-width: 767px) {
        .d-brand {
            top: 14px;
            left: 14px;
            padding: 0 16px;
            height: 44px;
        }

        .d-user-wrap {
            top: 14px;
            right: 14px;
            gap: 8px;
        }

        .d-iconbtn {
            width: 44px;
            height: 44px;
        }

        .d-user-btn {
            height: 44px;
            padding: 0 4px;
        }

        .d-user-btn .nm {
            display: none;
        }

        .d-stats {
            top: 68px;
            left: 14px;
            right: 14px;
            transform: none;
            justify-content: space-around;
            height: 44px;
        }

        .d-stat {
            padding: 0 8px;
            font-size: 12px;
            gap: 5px;
        }

        .d-stat b {
            font-size: 15px;
        }

        .conn-pill .lbl {
            display: none;
        }

        .d-error {
            top: 120px;
        }

        .d-vhead {
            display: none;
        }

        /* Swipeable vehicle carousel: one card centred with the neighbours peeking in, snapping
           card by card. The panel itself must receive touches (iOS Safari does not scroll a
           pointer-events:none container, even when its children take pointer events), and
           touch-action:pan-x hands horizontal drags to it while the map keeps everything else. */
        #vehicle-panel {
            --vcard: min(78vw, 300px);
            top: auto;
            left: 0;
            right: 0;
            bottom: 104px;
            width: auto;
            flex-direction: row;
            gap: 12px;
            overflow-x: auto;
            overflow-y: hidden;
            padding: 6px calc(50vw - var(--vcard) / 2) 8px;
            scroll-snap-type: x mandatory;
            scroll-padding-inline: calc(50vw - var(--vcard) / 2);
            pointer-events: auto;
            touch-action: pan-x;
            overscroll-behavior-x: contain;
            -webkit-overflow-scrolling: touch;
        }

        #vehicle-panel>.vrow {
            flex: 0 0 var(--vcard);
            max-width: var(--vcard);
            scroll-snap-align: center;
            scroll-snap-stop: always;
            padding: 12px 14px;
        }

        #vehicle-panel .vrow-body {
            min-width: 0;
        }

        #vehicle-panel .vrow-plate {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        /* wrap, never clip: keeps [DEMO]/[SHARED] visible */
        .d-empty {
            flex: 0 0 100%;
        }

        .d-sos {
            left: 14px;
            bottom: 26px;
            height: 52px;
            width: 52px;
            padding: 0;
            justify-content: center;
        }

        .d-sos .t {
            display: none;
        }

        .d-sos .c {
            width: 38px;
            height: 38px;
        }
    }
</style>

<div id="map-wrap">
    <div id="map" role="application" aria-label="Live map of your vehicles"></div>
</div>
<div class="d-vignette" aria-hidden="true"></div>

@php
$dashVehicles = $dashboard['vehicles'] ?? [];
$dashName = Session::get('customer_name') ?: Session::get('firebase_phone', 'Account');
$movingCount = collect($dashVehicles)
->filter(fn($v) => ($v['online'] ?? false) && ($v['speed'] ?? 0) > 0)
->count();
@endphp

{{-- ─── TOP CHIPS ───────────────────────────────────── --}}
<a href="/dashboard" class="d-chip d-brand gl" aria-label="ShaloTrack dashboard">
    <span class="dot"></span><b>Shalo<span>Track</span></b>
</a>

@if($dashboard)
<div class="d-chip d-stats gl" role="status" aria-label="Fleet status">
    <div class="d-stat">Total <b id="stat-total">{{ $dashboard['vehicleCount'] ?? 0 }}</b></div>
    <div class="d-stat"><i class="on"></i>Online <b id="stat-online">{{ $dashboard['onlineVehicles'] ?? 0 }}</b></div>
    <div class="d-stat"><i class="off"></i>Offline <b id="stat-offline">{{ $dashboard['offlineVehicles'] ?? 0 }}</b></div>
    <div class="d-stat"><i class="mv"></i>Moving <b id="stat-moving">{{ $movingCount }}</b></div>
    <div class="d-stat"><span id="realtime-status" class="conn-pill" style="color:#cbd5e1;"><span class="conn-dot" style="background:#94a3b8;"></span><span class="lbl">Connecting…</span></span></div>
</div>
@endif

<div class="d-user-wrap">
    <button type="button" id="map-theme-btn" class="d-iconbtn gl" aria-label="Switch map to light" title="Map theme">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z" />
        </svg>
    </button>
    <div style="position:relative;">
        <button type="button" id="d-user-btn" class="d-user-btn gl" aria-haspopup="true" aria-expanded="false" aria-controls="d-user-menu">
            <span class="nm">{{ $dashName }}</span>
            <span class="av">{{ strtoupper(substr($dashName, 0, 1)) }}</span>
        </button>
        <div id="d-user-menu" class="d-user-menu gl" hidden>
            <a href="/profile">Profile</a>
            <form method="POST" action="/logout">
                @csrf
                <button type="submit" class="out">Logout</button>
            </form>
        </div>
    </div>
</div>

{{-- ─── ERROR BANNER ──────────────────────────────────── --}}
@if($error)
<div class="d-error gl gl-red" role="alert">
    {{ $error }}
    <button type="button" onclick="window.location.reload()">Retry</button>
</div>
@endif

@if($dashboard)

{{-- ─── VEHICLE ISLANDS ───────────────────────────────── --}}
<section id="vehicle-panel" aria-label="Vehicles">
    <div class="d-vhead gl">
        <span>Vehicles<span class="sum" id="vlist-summary">{{ count($dashVehicles) }} total</span></span>
        <button type="button" id="vpanel-toggle" aria-expanded="true" aria-controls="vehicle-panel">Hide</button>
    </div>

    @if(empty($dashVehicles))
    <div class="d-empty gl">
        No vehicles found.<br>
        <a href="/vehicles">Add a vehicle →</a>
    </div>
    @else
    @foreach($dashVehicles as $vehicle)
    @php
    $vid = $vehicle['vehicleId'];
    $online = (bool)($vehicle['online'] ?? false);
    $plate = $vehicle['vehicleNumber'] ?? $vid;
    $make = trim(($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? ''));
    $speed = round($vehicle['speed'] ?? 0);
    $ignition = (bool)($vehicle['ignition'] ?? false);
    $lastSeen = $vehicle['lastUpdate'] ?? null;
    @endphp
    <div class="vrow gl{{ count($dashVehicles) <= 10 ? ' float' : '' }}" id="vrow-{{ $vid }}" style="--i: {{ $loop->index }}"
        role="button" tabindex="0" onclick="focusVehicle('{{ $vid }}')"
        onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();focusVehicle('{{ $vid }}');}">
        <div class="vrow-dot {{ $online ? 'online' : 'offline' }}" id="vdot-{{ $vid }}"></div>
        @if($rowIcon = \App\Support\VehicleIcon::url($vehicle['vehicleType'] ?? $vehicle['type'] ?? null, $online ? 'green' : 'blue'))
        <img src="{{ $rowIcon }}" alt="" width="18" height="32" style="height:32px;width:auto;flex-shrink:0;" loading="lazy" decoding="async">
        @endif
        <div class="vrow-body">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                <p class="vrow-plate">
                    {{ $plate }}
                    @if($vehicle['isDemoVehicle'] ?? $vehicle['isDemo'] ?? false)
                    <span class="tag-demo">[DEMO]</span>
                    @endif
                    @if($vehicle['isShared'] ?? false)
                    <span class="tag-shared">[SHARED]</span>
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
    @endif
</section>

{{-- ─── SOS ───────────────────────────────────────────── --}}
@if(!empty($dashVehicles))
<button type="button" class="d-sos gl gl-red" onclick="openSosModal()" aria-label="Emergency SOS">
    <span class="c">SOS</span>
    <span class="t">Emergency<small>Press and hold 3 s</small></span>
</button>
@endif

@elseif(!$error)
<div class="d-center d-empty gl">No data available. Please refresh.</div>
@endif

@include('partials.marker-glide')
@include('partials.vehicle-icons')

{{-- ─── JS (inline — no @push dependency) ────────────── --}}
@vendorScript('signalr')

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
            vehicleType: v.vehicleType ?? v.type ?? null, // picks the custom icon; unknown → generic marker
        };
    });
    VehicleIcons.preload(vehiclesRaw.map(v => v.vehicleType ?? v.type));

    if (deletedIds.size) recalcStats(); // online/offline/moving tiles must exclude deleted vehicles

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
    /* Custom per-type icon when we have one (see partials/vehicle-icons); otherwise the generic
       marker below. `vid` = lowercased vehicleId, `speed` overrides the stored speed. */
    function makeMarkerIcon(online, heading, vid, speed) {
        const v = vehicleMap[vid] || {};
        const st = VehicleIcons.state(online);
        return VehicleIcons.icon(v.vehicleType, st, heading) || legacyMarkerIcon(online, heading);
    }

    function legacyMarkerIcon(online, heading) {
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
    function focusVehicle(vehicleId, fromSwipe) {
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
        // A swipe already put the card in place; only tap/keyboard selections need to scroll to it.
        if (!fromSwipe) row?.scrollIntoView({
            block: 'nearest',
            inline: 'center',
            behavior: 'smooth'
        });
    }

    /* Phone carousel: when the user swipes the vehicle strip and it settles on a card, select that
       vehicle on the map (like a ride-hailing app). Only reacts to the user's own drag, never to the
       scroll caused by a tap or by the page loading, so the first view still frames the whole fleet. */
    (function() {
        const panel = document.getElementById('vehicle-panel');
        if (!panel) return;
        const phone = () => window.matchMedia('(max-width: 767px)').matches;
        let dragging = false,
            timer = null;

        const settle = () => {
            if (!dragging || !phone()) return;
            dragging = false;
            const mid = panel.getBoundingClientRect().left + panel.clientWidth / 2;
            let best = null,
                bestD = Infinity;
            panel.querySelectorAll('.vrow').forEach(r => {
                const b = r.getBoundingClientRect();
                const d = Math.abs(b.left + b.width / 2 - mid);
                if (d < bestD) {
                    bestD = d;
                    best = r;
                }
            });
            if (best && !best.classList.contains('active')) focusVehicle(best.id.replace(/^vrow-/, ''), true);
        };

        panel.addEventListener('touchstart', () => {
            dragging = true;
        }, {
            passive: true
        });
        panel.addEventListener('scroll', () => {
            if (!dragging) return;
            clearTimeout(timer);
            timer = setTimeout(settle, 140); // scrollend is not available everywhere
        }, {
            passive: true
        });
    })();

    /* ── Google Maps callback ─────────────────────────────── */
    /* Map theme (dark/light) lives in partials/map-style (shared with the other map pages). */

    /* Vehicle panel: collapse so the whole map is visible. */
    document.getElementById('vpanel-toggle')?.addEventListener('click', function() {
        const panel = document.getElementById('vehicle-panel');
        const collapsed = panel.classList.toggle('collapsed');
        this.textContent = collapsed ? 'Show' : 'Hide';
        this.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    });

    /* User chip menu. */
    (function() {
        const btn = document.getElementById('d-user-btn');
        const menu = document.getElementById('d-user-menu');
        if (!btn || !menu) return;
        const set = open => {
            menu.hidden = !open;
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        };
        btn.addEventListener('click', e => {
            e.stopPropagation();
            set(menu.hidden);
        });
        document.addEventListener('click', e => {
            if (!menu.contains(e.target)) set(false);
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') set(false);
        });
    })();

    /* Space the map's framing around the floating islands. */
    function mapPadding() {
        const phone = window.innerWidth < 768;
        return phone ?
            {
                top: 130,
                right: 30,
                bottom: 230,
                left: 30
            } :
            {
                top: 100,
                right: 370,
                bottom: 130,
                left: 60
            };
    }

    function initMap() {
        const mapEl = document.getElementById('map');
        if (!mapEl) return;
        gmap = new google.maps.Map(mapEl, {
            center: {
                lat: 7.8731,
                lng: 80.7718
            },
            zoom: 8,
            disableDefaultUI: true,
            zoomControl: window.innerWidth >= 768,
            zoomControlOptions: {
                position: google.maps.ControlPosition.LEFT_CENTER
            },
            gestureHandling: 'greedy',
            clickableIcons: false,
            styles: STMap.styles(),
        });
        STMap.register(gmap);

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
                icon: makeMarkerIcon(!!(v.online), heading, vid),
                zIndex: 10,
            });

            infoWins[vid] = new google.maps.InfoWindow({
                content: buildInfoHtml(vid)
            });
            markers[vid]._deg = heading;
            markers[vid]._st = VehicleIcons.state(!!v.online);
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

        /* Frame the vehicles once the map has a real size. (Not on every resize: phone toolbars would keep resetting the view.) */
        const fit = () => {
            if (!hasPoint || !gmap) return;
            google.maps.event.trigger(gmap, 'resize');
            const el = document.getElementById('map');
            const pad = mapPadding();
            /* Padding larger than the map would give a nonsense zoom: fall back to no padding. */
            const usable = el.clientHeight > pad.top + pad.bottom + 80 && el.clientWidth > pad.left + pad.right + 80;
            gmap.fitBounds(bounds, usable ? pad : 20);
            /* prevent over-zooming on a single marker */
            google.maps.event.addListenerOnce(gmap, 'bounds_changed', () => {
                if (gmap.getZoom() > 14) gmap.setZoom(14);
            });
        };
        fit();
    }

    /* Images may finish loading after the first markers were drawn → swap the generic marker for the custom icon */
    VehicleIcons.onReady(() => {
        Object.keys(markers).forEach(id => {
            const v = vehicleMap[id];
            if (v) markers[id].setIcon(makeMarkerIcon(!!v.online, markers[id]._deg ?? v.heading, id));
        });
    });

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
        const icon = makeMarkerIcon(true, heading, vehicleId, data.speed);
        const devMs = data.lastUpdate ? Date.parse(data.lastUpdate) : NaN;

        if (!trails[vehicleId]) trails[vehicleId] = [];

        let rejected = false;
        const wasOffline = !!vehicleMap[vehicleId] && !vehicleMap[vehicleId].online;

        if (markers[vehicleId]) {
            /* Glide to the new fix instead of hopping. GPS noise and impossible jumps
               are rejected and must not touch the trail or the stored position. */
            const result = MarkerGlide.move(vehicleId, markers[vehicleId], pos, heading, devMs, {
                paint: deg => {
                    markers[vehicleId]._deg = deg;
                    markers[vehicleId].setIcon(makeMarkerIcon(true, deg, vehicleId, data.speed));
                },
                frame: p => {
                    const path = polylines[vehicleId]?.getPath();
                    if (path && path.getLength()) path.setAt(path.getLength() - 1, new google.maps.LatLng(p.lat, p.lng));
                },
            });
            rejected = result === 'noise' || result === 'jump';
            /* Redraw when the state flips (offline→online, moving↔idle) even if the heading did not change */
            const st = VehicleIcons.state(true);
            if (wasOffline || markers[vehicleId]._st !== st) {
                markers[vehicleId].setIcon(makeMarkerIcon(true, markers[vehicleId]._deg ?? heading, vehicleId, data.speed));
            }
            markers[vehicleId]._st = st;
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
                color: '#86efac',
                dot: '#34d399',
                label: 'Live',
                pulse: true
            },
            reconnecting: {
                color: '#fcd34d',
                dot: '#f59e0b',
                label: 'Reconnecting…',
                pulse: true
            },
            disconnected: {
                color: '#cbd5e1',
                dot: '#94a3b8',
                label: 'Disconnected',
                pulse: false
            },
        } [state];
        if (!cfg) return;
        const anim = cfg.pulse ? 'animation:pulse 2s infinite;' : '';
        el.style.color = cfg.color;
        el.setAttribute('aria-label', 'Live connection: ' + cfg.label);
        el.innerHTML = `<span class="conn-dot" style="background:${cfg.dot};${anim}"></span><span class="lbl">${cfg.label}</span>`;
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
                    <svg width="24" height="24" fill="none" stroke="#16a34a" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
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