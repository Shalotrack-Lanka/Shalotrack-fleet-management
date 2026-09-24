@extends('layouts.app')

@section('title', 'Geofences — ShaloTrack Fleet')
@section('page-title', 'Geofences')

@section('content')

<style>
    .gf-wrap {
        display: grid;
        grid-template-columns: 1fr 380px;
        height: calc(100vh - 130px);
        border-radius: .875rem;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 8px rgba(2, 31, 74, .08);
        position: relative;
    }

    #gf-map {
        width: 100%;
        height: 100%;
    }

    /* Force crosshair on the entire map stack when in draw mode */
    #gf-map.draw-mode,
    #gf-map.draw-mode .gm-style,
    #gf-map.draw-mode .gm-style>div,
    #gf-map.draw-mode canvas {
        cursor: crosshair !important;
    }

    .gf-map-banner {
        display: none;
        position: absolute;
        top: 12px;
        left: 50%;
        transform: translateX(-50%);
        background: #021F4A;
        color: #fff;
        font-size: .8125rem;
        font-weight: 600;
        padding: .5rem 1.25rem;
        border-radius: 2rem;
        z-index: 10;
        box-shadow: 0 2px 12px rgba(0, 0, 0, .35);
        pointer-events: none;
        white-space: nowrap;
        letter-spacing: .01em;
    }

    .gf-sidebar {
        background: #fff;
        border-left: 1px solid #e5e7eb;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .gf-sidebar-head {
        padding: 1.125rem 1.125rem .875rem;
        border-bottom: 1px solid #f3f4f6;
        flex-shrink: 0;
        background: #fff;
    }

    .gf-sidebar-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: .75rem;
    }

    .gf-sidebar-title {
        font-weight: 700;
        color: #021F4A;
        font-size: .9375rem;
    }

    .gf-add-btn {
        display: inline-flex;
        align-items: center;
        gap: .375rem;
        padding: .4rem .875rem;
        background: #FA6908;
        color: #fff;
        border: none;
        border-radius: .5rem;
        font-size: .8125rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s;
        line-height: 1;
    }

    .gf-add-btn:hover {
        background: #e55e00;
    }

    .gf-search-wrap {
        position: relative;
    }

    .gf-search-ico {
        position: absolute;
        left: .75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        pointer-events: none;
    }

    .gf-search-inp {
        width: 100%;
        padding: .5rem .75rem .5rem 2.25rem;
        border: 1.5px solid #e5e7eb;
        border-radius: .625rem;
        font-size: .8125rem;
        outline: none;
        box-sizing: border-box;
        background: #f9fafb;
        color: #111827;
        transition: border-color .15s, box-shadow .15s, background .15s;
    }

    .gf-search-inp:focus {
        background: #fff;
        border-color: #FA6908;
        box-shadow: 0 0 0 3px rgba(250, 105, 8, .12);
    }

    .gf-panel {
        display: none;
        flex-direction: column;
        overflow: hidden;
        flex: 1;
        min-height: 0;
    }

    .gf-panel.gf-visible {
        display: flex;
    }

    .gf-list-count {
        padding: .5rem 1.125rem;
        font-size: .75rem;
        color: #9ca3af;
        border-bottom: 1px solid #f9fafb;
        flex-shrink: 0;
    }

    .gf-list {
        overflow-y: auto;
        flex: 1;
    }

    .gf-list::-webkit-scrollbar {
        width: 4px;
    }

    .gf-list::-webkit-scrollbar-thumb {
        background: #e5e7eb;
        border-radius: 2px;
    }

    .gf-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem 1.5rem;
        text-align: center;
        height: 100%;
        box-sizing: border-box;
    }

    .gf-empty-ico {
        color: #e5e7eb;
        margin-bottom: 1rem;
    }

    .gf-empty-t {
        font-size: .9375rem;
        font-weight: 500;
        color: #9ca3af;
        margin-bottom: .375rem;
    }

    .gf-empty-s {
        font-size: .8125rem;
        color: #d1d5db;
        line-height: 1.5;
    }

    .gf-card {
        padding: .875rem 1.125rem;
        border-bottom: 1px solid #f9fafb;
        transition: background .12s;
    }

    .gf-card:last-child {
        border-bottom: none;
    }

    .gf-card:hover {
        background: #fafafa;
    }

    .gf-card-header {
        display: flex;
        align-items: center;
        gap: .625rem;
        margin-bottom: .25rem;
        cursor: pointer;
    }

    .gf-card-dot {
        width: .5rem;
        height: .5rem;
        border-radius: 50%;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .gf-dot-on {
        background: #22c55e;
    }

    .gf-dot-off {
        background: #d1d5db;
    }

    .gf-card-name {
        font-weight: 600;
        color: #111827;
        font-size: .875rem;
        flex: 1;
    }

    .gf-card-meta {
        font-size: .75rem;
        color: #6b7280;
        margin-bottom: .4375rem;
        padding-left: 1.125rem;
    }

    .gf-card-tags {
        display: flex;
        gap: .3rem;
        padding-left: 1.125rem;
        margin-bottom: .5rem;
        flex-wrap: wrap;
    }

    .gf-tag {
        padding: .125rem .4rem;
        border-radius: .25rem;
        font-size: .6875rem;
        font-weight: 600;
    }

    .gf-tag-enter {
        background: #f0fdf4;
        color: #15803d;
    }

    .gf-tag-exit {
        background: #fef2f2;
        color: #b91c1c;
    }

    .gf-tag-inactive {
        background: #f3f4f6;
        color: #9ca3af;
    }

    .gf-card-actions {
        display: flex;
        gap: .875rem;
        padding-left: 1.125rem;
    }

    .gf-act {
        font-size: .75rem;
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
        color: #9ca3af;
        transition: color .12s;
        font-weight: 500;
    }

    .gf-act-edit:hover {
        color: #FA6908;
    }

    .gf-act-del:hover {
        color: #ef4444;
    }

    .gf-shared-lbl {
        font-size: .75rem;
        color: #93c5fd;
        padding-left: 1.125rem;
    }

    .gf-form-body {
        overflow-y: auto;
        flex: 1;
        padding: 1.125rem;
    }

    .gf-form-body::-webkit-scrollbar {
        width: 4px;
    }

    .gf-form-body::-webkit-scrollbar-thumb {
        background: #e5e7eb;
        border-radius: 2px;
    }

    .gf-back-row {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-bottom: 1.125rem;
    }

    .gf-back-btn {
        background: none;
        border: none;
        padding: .25rem;
        cursor: pointer;
        color: #6b7280;
        border-radius: .375rem;
        transition: background .12s, color .12s;
        line-height: 0;
    }

    .gf-back-btn:hover {
        background: #f3f4f6;
        color: #111827;
    }

    .gf-form-title {
        font-weight: 700;
        color: #021F4A;
        font-size: .9375rem;
    }

    .gf-draw-hint {
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: .625rem;
        padding: .75rem .875rem;
        margin-bottom: 1rem;
        font-size: .8125rem;
        color: #c2410c;
        line-height: 1.5;
    }

    .gf-field {
        margin-bottom: .875rem;
    }

    .gf-field-lbl {
        display: block;
        font-size: .6875rem;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-bottom: .3rem;
    }

    .gf-field-inp {
        width: 100%;
        padding: .5625rem .75rem;
        border: 1.5px solid #e5e7eb;
        border-radius: .625rem;
        font-size: .875rem;
        outline: none;
        box-sizing: border-box;
        background: #fff;
        color: #111827;
        transition: border-color .15s, box-shadow .15s;
    }

    .gf-field-inp:focus {
        border-color: #FA6908;
        box-shadow: 0 0 0 3px rgba(250, 105, 8, .12);
    }

    .gf-radius-hdr {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: .375rem;
    }

    .gf-radius-val {
        font-size: .875rem;
        font-weight: 700;
        color: #FA6908;
    }

    .gf-radius-range {
        width: 100%;
        accent-color: #FA6908;
        cursor: pointer;
        margin-bottom: .25rem;
    }

    .gf-radius-hints {
        display: flex;
        justify-content: space-between;
        font-size: .6875rem;
        color: #d1d5db;
    }

    .gf-coord-box {
        background: #f9fafb;
        border: 1.5px solid #f3f4f6;
        border-radius: .625rem;
        padding: .625rem .875rem;
        font-size: .75rem;
        color: #6b7280;
        line-height: 1.6;
        margin-bottom: .875rem;
    }

    .gf-coord-box strong {
        color: #374151;
    }

    .gf-divider {
        border: none;
        border-top: 1px solid #f3f4f6;
        margin: .875rem 0;
    }

    .gf-checks {
        display: flex;
        gap: 1.25rem;
        margin-bottom: .625rem;
    }

    .gf-check-lbl {
        display: flex;
        align-items: center;
        gap: .4rem;
        font-size: .875rem;
        color: #374151;
        cursor: pointer;
        user-select: none;
    }

    .gf-check-lbl input[type=checkbox] {
        accent-color: #FA6908;
        width: 15px;
        height: 15px;
    }

    .gf-form-err {
        display: none;
        font-size: .8125rem;
        color: #dc2626;
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: .5rem;
        padding: .625rem .875rem;
        margin-top: .5rem;
    }

    .gf-form-foot {
        padding: .875rem 1.125rem;
        border-top: 1px solid #f3f4f6;
        display: flex;
        gap: .625rem;
        flex-shrink: 0;
        background: #fff;
    }

    .gf-btn-cancel {
        flex: 1;
        padding: .625rem;
        border: 1.5px solid #e5e7eb;
        background: #fff;
        color: #6b7280;
        border-radius: .625rem;
        font-size: .875rem;
        font-weight: 500;
        cursor: pointer;
        transition: background .12s;
    }

    .gf-btn-cancel:hover {
        background: #f9fafb;
    }

    .gf-btn-save {
        flex: 2;
        padding: .625rem;
        background: #FA6908;
        color: #fff;
        border: none;
        border-radius: .625rem;
        font-size: .875rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s;
    }

    .gf-btn-save:hover {
        background: #e55e00;
    }

    .gf-btn-save:disabled {
        opacity: .6;
        cursor: not-allowed;
    }

    .gf-modal-wrap {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .gf-modal-wrap.gf-open {
        display: flex;
    }

    .gf-modal-bg {
        position: absolute;
        inset: 0;
        background: rgba(2, 31, 74, .45);
    }

    .gf-modal {
        position: relative;
        background: #fff;
        border-radius: 1rem;
        box-shadow: 0 24px 64px rgba(0, 0, 0, .22);
        width: 100%;
        max-width: 22rem;
        padding: 1.5rem;
    }

    .gf-modal h3 {
        font-weight: 700;
        color: #111827;
        font-size: 1rem;
        margin-bottom: .375rem;
    }

    .gf-modal>p {
        font-size: .875rem;
        color: #6b7280;
        margin-bottom: 1.25rem;
    }

    .gf-modal-err {
        font-size: .8125rem;
        color: #dc2626;
        margin-bottom: .75rem;
        display: none;
    }

    .gf-modal-btns {
        display: flex;
        gap: .75rem;
    }

    .gf-modal-cancel {
        flex: 1;
        padding: .5rem;
        border: 1px solid #e5e7eb;
        color: #6b7280;
        background: #fff;
        border-radius: .5rem;
        font-size: .875rem;
        cursor: pointer;
    }

    .gf-modal-del {
        flex: 1;
        padding: .5rem;
        background: #ef4444;
        color: #fff;
        border: none;
        border-radius: .5rem;
        font-size: .875rem;
        font-weight: 600;
        cursor: pointer;
    }

    .gf-modal-del:disabled {
        opacity: .6;
        cursor: not-allowed;
    }

    .gf-banner {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .875rem 1.125rem;
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: .75rem;
        color: #dc2626;
        font-size: .875rem;
        margin-bottom: 1.25rem;
    }

    @media (max-width: 860px) {
        .gf-wrap {
            grid-template-columns: 1fr;
            grid-template-rows: 50vh 1fr;
            height: auto;
        }

        .gf-sidebar {
            border-left: none;
            border-top: 1px solid #e5e7eb;
            min-height: 400px;
        }
    }
</style>

@if($error)
<div class="gf-banner">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    {{ $error }}
</div>
@endif

<div class="gf-wrap">

    <div id="gf-map"></div>

    <div class="gf-map-banner" id="gf-map-banner">
        Click and drag on the map to draw a circle
    </div>

    <div class="gf-sidebar">
        <div class="gf-sidebar-head">
            <div class="gf-sidebar-top">
                <span class="gf-sidebar-title" id="gf-sidebar-title">Geofences</span>
                <button class="gf-add-btn" id="gf-add-btn" onclick="openDrawMode()">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    Add
                </button>
            </div>
            <div class="gf-search-wrap" id="gf-search-wrap">
                <svg class="gf-search-ico" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input class="gf-search-inp" type="text" placeholder="Search geofences…" oninput="filterList(this.value)" />
            </div>
        </div>

        {{-- LIST PANEL --}}
        <div class="gf-panel gf-visible" id="gf-panel-list">
            <p class="gf-list-count">{{ count($geofences) }} geofence{{ count($geofences) !== 1 ? 's' : '' }}</p>
            <div class="gf-list">
                @if(empty($geofences))
                <div class="gf-empty">
                    <svg class="gf-empty-ico" width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.3" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.3" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <p class="gf-empty-t">No geofences yet</p>
                    <p class="gf-empty-s">Click "Add" and draw a circle<br>on the map to create your first zone.</p>
                </div>
                @else
                @foreach($geofences as $gf)
                <div class="gf-card" data-name="{{ strtolower($gf['name']) }}">
                    <div class="gf-card-header" onclick="focusGeofence('{{ $gf['geofenceId'] }}')">
                        <span class="gf-card-dot {{ ($gf['isActive'] ?? true) ? 'gf-dot-on' : 'gf-dot-off' }}"></span>
                        <span class="gf-card-name">{{ $gf['name'] }}</span>
                    </div>
                    <p class="gf-card-meta">
                        {{ $gf['vehicleNumber'] ?? 'All vehicles' }} · {{ number_format($gf['radiusMeters']) }} m radius
                    </p>
                    <div class="gf-card-tags">
                        @if($gf['alertOnEnter'] ?? false)<span class="gf-tag gf-tag-enter">Enter alert</span>@endif
                        @if($gf['alertOnExit'] ?? false)<span class="gf-tag gf-tag-exit">Exit alert</span>@endif
                        @if(!($gf['isActive'] ?? true))<span class="gf-tag gf-tag-inactive">Inactive</span>@endif
                    </div>
                    @if($gf['isOwner'] ?? true)
                    <div class="gf-card-actions">
                        <button class="gf-act gf-act-edit" data-idx="{{ $loop->index }}" onclick="startEdit(GEOFENCES[+this.dataset.idx])">Edit</button>
                        <button class="gf-act gf-act-del" data-id="{{ $gf['geofenceId'] }}" data-name="{{ $gf['name'] }}" onclick="openDeleteModal(this.dataset.id, this.dataset.name)">Delete</button>
                    </div>
                    @else
                    <p class="gf-shared-lbl">Shared — view only</p>
                    @endif
                </div>
                @endforeach
                @endif
            </div>
        </div>

        {{-- FORM PANEL --}}
        <div class="gf-panel" id="gf-panel-form">
            <div class="gf-form-body">
                <div class="gf-back-row">
                    <button class="gf-back-btn" onclick="cancelMode()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </button>
                    <span class="gf-form-title" id="gf-form-title">New Geofence</span>
                </div>

                <div class="gf-draw-hint" id="gf-draw-hint">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="flex-shrink:0;margin-top:1px">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Click on the map and drag outward to draw your geofence circle.</span>
                </div>

                <div class="gf-field">
                    <label class="gf-field-lbl">Geofence Name *</label>
                    <input type="text" id="gf-name" class="gf-field-inp" placeholder="e.g. Main Depot, Office, Restricted Zone" maxlength="100" />
                </div>

                <div class="gf-field">
                    <label class="gf-field-lbl">Vehicle Scope</label>
                    <select id="gf-vehicle" class="gf-field-inp">
                        <option value="">All my vehicles</option>
                        @foreach($vehicles as $v)
                        <option value="{{ $v['vehicleId'] }}">{{ $v['vehicleNumber'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="gf-field">
                    <div class="gf-radius-hdr">
                        <label class="gf-field-lbl" style="margin:0">Radius</label>
                        <span class="gf-radius-val"><span id="gf-radius-num">500</span> m</span>
                    </div>
                    <input type="range" id="gf-radius-range" class="gf-radius-range"
                        min="50" max="5000" value="500" step="50"
                        oninput="onRadiusChange(this.value)" />
                    <div class="gf-radius-hints"><span>50 m</span><span>5 km</span></div>
                </div>

                <div class="gf-coord-box" id="gf-coord-box">No circle drawn yet.</div>

                <hr class="gf-divider" />

                <div class="gf-checks">
                    <label class="gf-check-lbl"><input type="checkbox" id="gf-enter" checked /> Alert on Enter</label>
                    <label class="gf-check-lbl"><input type="checkbox" id="gf-exit" checked /> Alert on Exit</label>
                </div>

                <div id="gf-active-wrap" style="display:none;margin-bottom:.625rem;">
                    <label class="gf-check-lbl"><input type="checkbox" id="gf-active" checked /> Active</label>
                </div>

                <div class="gf-form-err" id="gf-form-err"></div>
            </div>

            <div class="gf-form-foot">
                <button class="gf-btn-cancel" onclick="cancelMode()">Cancel</button>
                <button class="gf-btn-save" id="gf-save-btn" onclick="onSave()">
                    <span id="gf-save-lbl">Save Geofence</span>
                </button>
            </div>
        </div>

    </div>
</div>

<div class="gf-modal-wrap" id="gf-del-modal">
    <div class="gf-modal-bg" onclick="closeDeleteModal()"></div>
    <div class="gf-modal">
        <h3>Delete Geofence</h3>
        <p>Delete <strong id="gf-del-name"></strong>? This cannot be undone.</p>
        <input type="hidden" id="gf-del-id" />
        <p class="gf-modal-err" id="gf-del-err"></p>
        <div class="gf-modal-btns">
            <button class="gf-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button class="gf-modal-del" id="gf-del-btn" onclick="submitDelete()">Delete</button>
        </div>
    </div>
</div>

<script>
    'use strict';

    const CSRF = '{{ csrf_token() }}';
    const GEOFENCES = @json($geofences);

    /* ---- State ---- */
    let S = {
        mode: 'none',
        editId: null,
        center: null,
        radius: 500,
        workCircle: null,
    };

    let gmap;
    const circleMap = {};

    /* Active map event listener handles (so we can remove them cleanly) */
    let _downH = null,
        _moveH = null,
        _upH = null;

    /* ============================================================
       Map init — Google Maps callback
    ============================================================ */
    function initMap() {
        gmap = new google.maps.Map(document.getElementById('gf-map'), {
            center: {
                lat: 7.8731,
                lng: 80.7718
            },
            zoom: 8,
            mapTypeId: 'roadmap',
            streetViewControl: false,
            mapTypeControl: true,
            mapTypeControlOptions: {
                style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
                position: google.maps.ControlPosition.TOP_RIGHT,
            },
            zoomControlOptions: {
                position: google.maps.ControlPosition.RIGHT_CENTER,
            },
            fullscreenControl: true,
            fullscreenControlOptions: {
                position: google.maps.ControlPosition.RIGHT_BOTTOM
            },
            gestureHandling: 'greedy',
            clickableIcons: false,
        });

        renderAll();
    }

    /* ============================================================
       Render existing geofences
    ============================================================ */
    function renderAll() {
        const bounds = new google.maps.LatLngBounds();
        let hasAny = false;

        GEOFENCES.forEach(g => {
            if (!g.latitude || !g.longitude) return;
            const lat = parseFloat(g.latitude);
            const lng = parseFloat(g.longitude);
            const isOwner = g.isOwner ?? true;
            const isActive = g.isActive ?? true;

            const circle = new google.maps.Circle({
                map: gmap,
                center: {
                    lat,
                    lng
                },
                radius: g.radiusMeters,
                fillColor: isOwner ? '#FA6908' : '#3B82F6',
                fillOpacity: isActive ? 0.10 : 0.04,
                strokeColor: isOwner ? '#FA6908' : '#3B82F6',
                strokeWeight: isActive ? 2 : 1,
                strokeOpacity: isActive ? 0.8 : 0.4,
                clickable: true,
                editable: false,
            });

            const info = new google.maps.InfoWindow({
                content: `<div style="font-family:system-ui,sans-serif;padding:2px 0;min-width:150px">
                <div style="font-weight:700;color:#021F4A;font-size:13px;margin-bottom:3px">${esc(g.name)}</div>
                <div style="color:#6b7280;font-size:12px">${esc(g.vehicleNumber ?? 'All vehicles')}</div>
                <div style="color:#9ca3af;font-size:12px">${g.radiusMeters} m radius</div>
            </div>`,
            });

            circle.addListener('click', () => info.open({
                map: gmap,
                anchor: circle
            }));
            circleMap[g.geofenceId] = circle;
            bounds.extend({
                lat,
                lng
            });
            hasAny = true;
        });

        if (hasAny) gmap.fitBounds(bounds, {
            top: 60,
            right: 60,
            bottom: 60,
            left: 60
        });
    }

    /* ============================================================
       Manual circle drawing — no DrawingManager
    ============================================================ */
    function _enableDraw() {
        /* Lock map panning, show crosshair */
        gmap.setOptions({
            draggable: false,
            scrollwheel: false
        });
        document.getElementById('gf-map').classList.add('draw-mode');
        _showBanner(true);

        _downH = gmap.addListener('mousedown', (e) => {
            const origin = e.latLng;

            /* Start a zero-radius circle at click point */
            if (S.workCircle) S.workCircle.setMap(null);
            S.workCircle = new google.maps.Circle({
                map: gmap,
                center: origin,
                radius: 1,
                fillColor: '#FA6908',
                fillOpacity: 0.18,
                strokeColor: '#FA6908',
                strokeWeight: 2.5,
                clickable: false,
                editable: false,
                draggable: false,
                zIndex: 5,
            });

            /* Expand radius as mouse moves */
            _moveH = gmap.addListener('mousemove', (e2) => {
                const r = Math.max(_haversine(origin, e2.latLng), 50);
                S.workCircle.setCenter(origin);
                S.workCircle.setRadius(r);
            });

            /* Finalise on mouse up */
            _upH = gmap.addListener('mouseup', () => {
                _removeMoveUp();
                _disableDraw();
                _finaliseCircle();
            });
        });

        /* Safety net: if user releases outside the map element */
        document.addEventListener('mouseup', _onDocMouseUp, {
            once: true
        });
    }

    function _onDocMouseUp() {
        /* Only fires when the map's own mouseup didn't catch it */
        if (_moveH || _upH) {
            _removeMoveUp();
            _disableDraw();
            if (S.workCircle && S.workCircle.getRadius() > 50) {
                _finaliseCircle();
            } else {
                if (S.workCircle) {
                    S.workCircle.setMap(null);
                    S.workCircle = null;
                }
            }
        }
    }

    function _removeMoveUp() {
        if (_moveH) {
            google.maps.event.removeListener(_moveH);
            _moveH = null;
        }
        if (_upH) {
            google.maps.event.removeListener(_upH);
            _upH = null;
        }
    }

    function _disableDraw() {
        if (_downH) {
            google.maps.event.removeListener(_downH);
            _downH = null;
        }
        _removeMoveUp();
        gmap.setOptions({
            draggable: true,
            scrollwheel: true
        });
        document.getElementById('gf-map').classList.remove('draw-mode');
        _showBanner(false);
    }

    function _finaliseCircle() {
        if (!S.workCircle) return;
        S.workCircle.setEditable(true);
        S.workCircle.setDraggable(true);
        S.workCircle.setOptions({
            clickable: true
        });
        _syncFromCircle();
        document.getElementById('gf-draw-hint').style.display = 'none';
        google.maps.event.addListener(S.workCircle, 'radius_changed', _syncFromCircle);
        google.maps.event.addListener(S.workCircle, 'center_changed', _syncFromCircle);
    }

    /* Haversine — distance in metres between two google.maps.LatLng */
    function _haversine(ll1, ll2) {
        const R = 6371000;
        const φ1 = ll1.lat() * Math.PI / 180;
        const φ2 = ll2.lat() * Math.PI / 180;
        const Δφ = (ll2.lat() - ll1.lat()) * Math.PI / 180;
        const Δλ = (ll2.lng() - ll1.lng()) * Math.PI / 180;
        const a = Math.sin(Δφ / 2) ** 2 + Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ / 2) ** 2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    /* ============================================================
       Draw mode (public entry point)
    ============================================================ */
    function openDrawMode() {
        _clearWork();
        S.mode = 'draw';
        S.editId = null;
        S.center = null;
        S.radius = 500;

        _resetForm();
        document.getElementById('gf-form-title').textContent = 'New Geofence';
        document.getElementById('gf-save-lbl').textContent = 'Save Geofence';
        document.getElementById('gf-draw-hint').style.display = 'flex';
        document.getElementById('gf-active-wrap').style.display = 'none';
        document.getElementById('gf-coord-box').textContent = 'No circle drawn yet.';

        _showPanel('form');
        _enableDraw();
    }

    /* ============================================================
       Edit mode
    ============================================================ */
    function startEdit(gf) {
        _clearWork();
        S.mode = 'edit';
        S.editId = gf.geofenceId;
        S.radius = gf.radiusMeters ?? 500;
        S.center = (gf.latitude && gf.longitude) ? {
                lat: parseFloat(gf.latitude),
                lng: parseFloat(gf.longitude)
            } :
            null;

        _resetForm();
        document.getElementById('gf-name').value = gf.name ?? '';
        document.getElementById('gf-vehicle').value = gf.vehicleId ?? '';
        document.getElementById('gf-enter').checked = gf.alertOnEnter ?? true;
        document.getElementById('gf-exit').checked = gf.alertOnExit ?? true;
        document.getElementById('gf-active').checked = gf.isActive ?? true;
        document.getElementById('gf-form-title').textContent = 'Edit Geofence';
        document.getElementById('gf-save-lbl').textContent = 'Update Geofence';
        document.getElementById('gf-active-wrap').style.display = 'flex';
        _syncSlider(S.radius);

        if (S.center) {
            document.getElementById('gf-draw-hint').style.display = 'none';
            _updateCoordBox();

            S.workCircle = new google.maps.Circle({
                map: gmap,
                center: S.center,
                radius: S.radius,
                fillColor: '#021F4A',
                fillOpacity: 0.2,
                strokeColor: '#021F4A',
                strokeWeight: 2.5,
                clickable: true,
                editable: true,
                draggable: true,
                zIndex: 5,
            });
            google.maps.event.addListener(S.workCircle, 'radius_changed', _syncFromCircle);
            google.maps.event.addListener(S.workCircle, 'center_changed', _syncFromCircle);

            if (circleMap[S.editId]) {
                circleMap[S.editId].setOptions({
                    fillOpacity: 0.03,
                    strokeOpacity: 0.25
                });
            }

            gmap.panTo(S.center);
            gmap.setZoom(14);
        } else {
            document.getElementById('gf-draw-hint').style.display = 'flex';
            document.getElementById('gf-coord-box').textContent = 'No location saved — draw on the map.';
            _enableDraw();
        }

        _showPanel('form');
    }

    /* ============================================================
       Radius slider
    ============================================================ */
    function onRadiusChange(val) {
        S.radius = parseInt(val, 10);
        document.getElementById('gf-radius-num').textContent = val;
        if (S.workCircle) {
            S.workCircle.setRadius(S.radius);
            _updateCoordBox();
        }
    }

    /* ============================================================
       Save
    ============================================================ */
    async function onSave() {
        const errEl = document.getElementById('gf-form-err');
        errEl.style.display = 'none';

        const name = document.getElementById('gf-name').value.trim();
        if (!name) {
            _showErr(errEl, 'Enter a geofence name.');
            return;
        }
        if (!S.center) {
            _showErr(errEl, 'Draw a circle on the map first.');
            return;
        }

        const btn = document.getElementById('gf-save-btn');
        btn.disabled = true;
        document.getElementById('gf-save-lbl').textContent = S.mode === 'edit' ? 'Updating…' : 'Saving…';

        const payload = {
            name,
            latitude: S.center.lat,
            longitude: S.center.lng,
            radiusMeters: S.radius,
            vehicleId: document.getElementById('gf-vehicle').value || null,
            alertOnEnter: document.getElementById('gf-enter').checked,
            alertOnExit: document.getElementById('gf-exit').checked,
        };
        if (S.mode === 'edit') payload.isActive = document.getElementById('gf-active').checked;

        const url = S.mode === 'edit' ? `/geofences/${S.editId}` : '/geofences';
        const method = S.mode === 'edit' ? 'PUT' : 'POST';

        try {
            const res = await fetch(url, {
                method,
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json().catch(() => ({}));

            if (res.status === 401) {
                window.location.href = '/login?expired=1';
                return;
            }

            if (data.success) {
                window.location.reload();
            } else {
                btn.disabled = false;
                document.getElementById('gf-save-lbl').textContent = S.mode === 'edit' ? 'Update Geofence' : 'Save Geofence';
                _showErr(errEl, data.message ?? 'Operation failed — please try again.');
            }
        } catch {
            btn.disabled = false;
            document.getElementById('gf-save-lbl').textContent = S.mode === 'edit' ? 'Update Geofence' : 'Save Geofence';
            _showErr(errEl, 'Network error — please check your connection.');
        }
    }

    /* ============================================================
       Cancel
    ============================================================ */
    function cancelMode() {
        _disableDraw();
        _clearWork();

        GEOFENCES.forEach(g => {
            if (!circleMap[g.geofenceId]) return;
            const active = g.isActive ?? true;
            circleMap[g.geofenceId].setOptions({
                fillOpacity: active ? 0.10 : 0.04,
                strokeOpacity: active ? 0.8 : 0.4,
            });
        });

        S.mode = 'none';
        S.editId = null;
        S.center = null;
        _showPanel('list');
    }

    /* ============================================================
       Focus geofence from list
    ============================================================ */
    function focusGeofence(id) {
        const g = GEOFENCES.find(x => x.geofenceId === id);
        if (!g?.latitude) return;
        gmap.panTo({
            lat: parseFloat(g.latitude),
            lng: parseFloat(g.longitude)
        });
        gmap.setZoom(14);
        if (circleMap[id]) google.maps.event.trigger(circleMap[id], 'click');
    }

    /* ============================================================
       List search
    ============================================================ */
    function filterList(q) {
        const term = q.toLowerCase().trim();
        document.querySelectorAll('.gf-card').forEach(card => {
            card.style.display = (!term || card.dataset.name.includes(term)) ? '' : 'none';
        });
    }

    /* ============================================================
       Delete modal
    ============================================================ */
    function openDeleteModal(id, name) {
        document.getElementById('gf-del-id').value = id;
        document.getElementById('gf-del-name').textContent = name;
        document.getElementById('gf-del-err').style.display = 'none';
        document.getElementById('gf-del-btn').disabled = false;
        document.getElementById('gf-del-btn').textContent = 'Delete';
        document.getElementById('gf-del-modal').classList.add('gf-open');
    }

    function closeDeleteModal() {
        document.getElementById('gf-del-modal').classList.remove('gf-open');
    }

    async function submitDelete() {
        const id = document.getElementById('gf-del-id').value;
        const btn = document.getElementById('gf-del-btn');
        const err = document.getElementById('gf-del-err');
        btn.disabled = true;
        btn.textContent = 'Deleting…';
        err.style.display = 'none';
        try {
            const res = await fetch(`/geofences/${id}`, {
                method: 'DELETE',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF
                },
            });
            const data = await res.json().catch(() => ({}));
            if (res.status === 401) {
                window.location.href = '/login?expired=1';
                return;
            }
            if (data.success) {
                closeDeleteModal();
                window.location.reload();
            } else {
                btn.disabled = false;
                btn.textContent = 'Delete';
                _showErr(err, data.message ?? 'Delete failed.');
            }
        } catch {
            btn.disabled = false;
            btn.textContent = 'Delete';
            _showErr(err, 'Network error — please try again.');
        }
    }

    /* ============================================================
       Private helpers
    ============================================================ */
    function _clearWork() {
        if (S.workCircle) {
            S.workCircle.setMap(null);
            S.workCircle = null;
        }
    }

    function _syncFromCircle() {
        if (!S.workCircle) return;
        const c = S.workCircle.getCenter();
        S.center = {
            lat: c.lat(),
            lng: c.lng()
        };
        S.radius = Math.round(S.workCircle.getRadius());
        _syncSlider(S.radius);
        _updateCoordBox();
    }

    function _syncSlider(val) {
        document.getElementById('gf-radius-range').value = val;
        document.getElementById('gf-radius-num').textContent = val;
    }

    function _updateCoordBox() {
        if (!S.center) return;
        document.getElementById('gf-coord-box').innerHTML =
            `<strong>Center:</strong> ${S.center.lat.toFixed(6)}, ${S.center.lng.toFixed(6)}<br>` +
            `<strong>Radius:</strong> ${S.radius} m`;
    }

    function _resetForm() {
        document.getElementById('gf-name').value = '';
        document.getElementById('gf-vehicle').value = '';
        document.getElementById('gf-enter').checked = true;
        document.getElementById('gf-exit').checked = true;
        document.getElementById('gf-form-err').style.display = 'none';
        document.getElementById('gf-save-btn').disabled = false;
        _syncSlider(500);
        S.radius = 500;
    }

    function _showPanel(which) {
        document.getElementById('gf-panel-list').classList.toggle('gf-visible', which === 'list');
        document.getElementById('gf-panel-form').classList.toggle('gf-visible', which === 'form');
        document.getElementById('gf-search-wrap').style.display = which === 'list' ? '' : 'none';
        document.getElementById('gf-add-btn').style.display = which === 'list' ? '' : 'none';
        document.getElementById('gf-sidebar-title').textContent =
            which === 'list' ? 'Geofences' :
            (S.mode === 'edit' ? 'Edit Geofence' : 'New Geofence');
    }

    function _showBanner(show) {
        document.getElementById('gf-map-banner').style.display = show ? 'block' : 'none';
    }

    function _showErr(el, msg) {
        el.textContent = msg;
        el.style.display = 'block';
    }

    function esc(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
</script>

{{-- Google Maps JS — no 'drawing' library needed anymore --}}
<script async
    src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&callback=initMap&loading=async">
</script>

@endsection