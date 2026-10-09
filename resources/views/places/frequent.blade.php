@extends('layouts.app')

@section('page-title', 'Frequent Places')
@section('title', 'Frequent Places')

@section('content')
{{-- Styles live inside @section('content'): the layout never renders @stack('styles'). --}}
<style>
    .fp { max-width: 1280px; margin: 0 auto; padding: 20px 16px 40px; }
    .fp-title { font-size: 22px; font-weight: 800; color: #021F4A; margin: 0 0 4px; }
    .fp-sub { font-size: 14px; color: #64748b; margin: 0 0 16px; line-height: 1.5; }

    .fp-controls { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; background: #fff; border: 1px solid #e2e8f0;
                   border-radius: 14px; padding: 14px 16px; margin-bottom: 16px; }
    .fp-field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
    .fp-label { font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #64748b; }
    .fp-select { border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 12px; font-size: 16px; background: #fff; min-height: 44px; max-width: 100%; min-width: 220px; }
    .fp-seg { display: flex; gap: 8px; }
    .fp-seg button { border: 1px solid #cbd5e1; background: #fff; color: #334155; font-size: 14px; font-weight: 600; padding: 10px 14px;
                     border-radius: 999px; cursor: pointer; min-height: 44px; }
    .fp-seg button[aria-pressed="true"] { background: #021F4A; border-color: #021F4A; color: #fff; }

    .fp-status { font-size: 13.5px; color: #475569; margin: 0 0 10px; min-height: 20px; }
    .fp-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 10px; padding: 10px 12px; font-size: 13.5px; margin-bottom: 12px; }

    .fp-body { display: grid; grid-template-columns: 1fr; gap: 16px; }
    @media (min-width: 900px) { .fp-body { grid-template-columns: 380px 1fr; align-items: start; } }

    .fp-map-wrap { order: -1; background: #e2e8f0; border-radius: 14px; overflow: hidden; border: 1px solid #e2e8f0; height: 340px; }
    @media (min-width: 900px) { .fp-map-wrap { order: 0; height: 640px; position: sticky; top: 16px; } }
    #fp-map { width: 100%; height: 100%; }

    .fp-list { display: flex; flex-direction: column; gap: 10px; }
    .fp-empty { background: #fff; border: 1px dashed #cbd5e1; border-radius: 14px; padding: 28px 16px; text-align: center; color: #64748b; font-size: 14.5px; line-height: 1.6; }

    .fp-card { background: #fff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 12px 14px; display: flex; gap: 12px; cursor: pointer; }
    .fp-card:hover { border-color: #cbd5e1; }
    .fp-card.active { border-color: #FA6908; box-shadow: 0 0 0 3px rgba(250,105,8,.15); }
    .fp-rank { flex: 0 0 32px; height: 32px; border-radius: 50%; background: #FA6908; color: #fff; font-weight: 800; font-size: 14px;
               display: flex; align-items: center; justify-content: center; }
    .fp-info { min-width: 0; flex: 1; }
    .fp-addr { font-size: 14.5px; font-weight: 700; color: #0f172a; line-height: 1.35; overflow-wrap: anywhere; }
    .fp-meta { font-size: 12.5px; color: #64748b; margin-top: 3px; }
    .fp-saved { display: inline-block; margin-top: 6px; font-size: 11.5px; font-weight: 800; color: #166534; background: #dcfce7; padding: 2px 8px; border-radius: 999px; }
    .fp-actions { margin-top: 8px; }
    .fp-btn { border: 1px solid #FA6908; background: #fff; color: #c2410c; font-size: 13px; font-weight: 700; padding: 8px 12px; border-radius: 10px; cursor: pointer; min-height: 40px; }
    .fp-btn.primary { background: #FA6908; color: #fff; }
    .fp-btn.ghost { border-color: #cbd5e1; color: #475569; }
    .fp-btn:disabled { opacity: .6; cursor: default; }
    .fp-saveform { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
    .fp-saveform input { flex: 1 1 160px; min-width: 0; border: 1px solid #cbd5e1; border-radius: 10px; padding: 9px 11px; font-size: 16px; }
    .fp-msg { font-size: 12.5px; margin-top: 6px; }
    .fp-msg.err { color: #b91c1c; }
</style>

<div class="fp">
    <h1 class="fp-title">Frequent places</h1>
    <p class="fp-sub">The places a vehicle keeps ending its trips at. Spot depots, customers and parking habits, then save the ones that matter.</p>

    @if ($error)
        <div class="fp-error" role="alert">{{ $error }}</div>
    @endif

    <section class="fp-controls" aria-label="Choose a vehicle and period">
        <div class="fp-field">
            <label class="fp-label" for="fp-vehicle">Vehicle</label>
            <select id="fp-vehicle" class="fp-select"></select>
        </div>
        <div class="fp-field">
            <span class="fp-label" id="fp-period-label">Period</span>
            <div class="fp-seg" role="group" aria-labelledby="fp-period-label" id="fp-period">
                <button type="button" data-period="30" aria-pressed="true">Last 30 days</button>
                <button type="button" data-period="90" aria-pressed="false">Last 90 days</button>
            </div>
        </div>
    </section>

    <p class="fp-status" id="fp-status" role="status" aria-live="polite"></p>
    <div id="fp-errors"></div>

    <div class="fp-body">
        <div class="fp-map-wrap"><div id="fp-map" aria-label="Map of frequent places"></div></div>
        <div class="fp-list" id="fp-list"></div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const VEHICLES = @json($vehicles);
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const $ = (id) => document.getElementById(id);
    const byId = new Map(VEHICLES.map((v) => [v.id, v]));

    const params = new URLSearchParams(location.search);
    let vehicleId = byId.has(params.get('v')) ? params.get('v') : (VEHICLES[0] ? VEHICLES[0].id : null);
    let period = params.get('period') === '90' ? '90' : '30';
    let places = [];
    let activeIdx = -1;
    let runToken = 0;
    let state = 'idle';            // idle | loading | done | error

    let map = null;
    let markers = [];
    let mapReady = false;

    function el(tag, cls, text) {
        const n = document.createElement(tag);
        if (cls) n.className = cls;
        if (text != null) n.textContent = text;
        return n;
    }

    function syncUrl() {
        const q = new URLSearchParams();
        if (vehicleId) q.set('v', vehicleId);
        q.set('period', period);
        try { history.replaceState(null, '', location.pathname + '?' + q.toString()); } catch (e) { /* ignore */ }
    }

    // ── Controls ─────────────────────────────────────────────────────────────────
    function renderControls() {
        const sel = $('fp-vehicle');
        sel.textContent = '';
        if (VEHICLES.length === 0) {
            const o = el('option', null, 'No GPS-enabled vehicles');
            o.value = ''; sel.appendChild(o); sel.disabled = true;
        }
        VEHICLES.forEach((v) => {
            const label = v.plate + (v.name ? ' · ' + v.name : '') + (v.shared ? ' (shared)' : '') + (v.demo ? ' (demo)' : '');
            const o = el('option', null, label);
            o.value = v.id;
            if (v.id === vehicleId) o.selected = true;
            sel.appendChild(o);
        });
        document.querySelectorAll('#fp-period button').forEach((b) => {
            b.setAttribute('aria-pressed', b.dataset.period === period ? 'true' : 'false');
        });
    }

    // ── Data ─────────────────────────────────────────────────────────────────────
    async function load() {
        syncUrl();
        const token = ++runToken;
        $('fp-errors').textContent = '';
        places = []; activeIdx = -1;
        state = 'loading';
        render();

        if (!vehicleId) {
            state = 'done';
            $('fp-status').textContent = '';
            return;
        }

        $('fp-status').textContent = 'Looking at the last ' + period + ' days…';
        try {
            const res = await fetch('/places/' + encodeURIComponent(vehicleId) + '/data?period=' + encodeURIComponent(period), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            });
            if (res.status === 401) { window.location.href = '/login?expired=1'; return; }
            const body = await res.json().catch(() => null);
            if (token !== runToken) return;

            if (!res.ok || !body || !body.success) {
                state = 'error';
                $('fp-status').textContent = '';
                showError((body && body.message) || 'Could not load trips. Please try again.');
                return;
            }
            places = body.places || [];
            state = 'done';
            $('fp-status').textContent = body.tripCount + ' trip' + (body.tripCount === 1 ? '' : 's') + ' in the last ' + body.days + ' days.';
            render();
            fitMap();
            resolveAddresses(token);
        } catch (e) {
            if (token !== runToken) return;
            state = 'error';
            $('fp-status').textContent = '';
            showError('Could not reach the server. Check your connection and try again.');
        }
    }

    function showError(msg) {
        const e = el('div', 'fp-error', msg);
        e.setAttribute('role', 'alert');
        $('fp-errors').textContent = '';
        $('fp-errors').appendChild(e);
    }

    // ── Rendering ────────────────────────────────────────────────────────────────
    function renderEmpty(text) {
        const list = $('fp-list');
        list.textContent = '';
        list.appendChild(el('div', 'fp-empty', text));
    }

    function fmtDate(iso) {
        if (!iso) return '—';
        const d = new Date(iso);
        if (isNaN(d)) return '—';
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'Asia/Colombo' });
    }

    function render() {
        const list = $('fp-list');
        list.textContent = '';
        clearMarkers();

        if (places.length === 0) {
            if (state === 'done') {
                renderEmpty(vehicleId
                    ? 'No place was visited twice in this period. Try the last 90 days, or come back after a few more trips.'
                    : 'Link a GPS device to a vehicle to see where it goes.');
            }
            return;
        }

        places.forEach((p, i) => {
            const card = el('div', 'fp-card');
            card.id = 'fp-card-' + i;
            card.setAttribute('role', 'button');
            card.tabIndex = 0;
            card.addEventListener('click', () => setActive(i, true));
            card.addEventListener('keydown', (e) => {
                if ((e.key === 'Enter' || e.key === ' ') && e.target === card) { e.preventDefault(); setActive(i, true); }
            });

            card.appendChild(el('div', 'fp-rank', String(i + 1)));

            const info = el('div', 'fp-info');
            const addr = el('div', 'fp-addr', p.address || (p.lat.toFixed(5) + ', ' + p.lng.toFixed(5)));
            addr.id = 'fp-addr-' + i;
            info.appendChild(addr);
            info.appendChild(el('div', 'fp-meta', p.visits + ' visits · last on ' + fmtDate(p.lastVisit)));

            const actions = el('div', 'fp-actions');
            actions.id = 'fp-actions-' + i;
            if (p.savedName) {
                info.appendChild(el('span', 'fp-saved', 'Saved: ' + p.savedName));
            } else {
                const b = el('button', 'fp-btn', 'Save as place');
                b.type = 'button';
                b.addEventListener('click', (e) => { e.stopPropagation(); openSaveForm(i); });
                actions.appendChild(b);
                info.appendChild(actions);
            }
            card.appendChild(info);
            list.appendChild(card);
        });

        drawMarkers();
    }

    function setActive(i, pan) {
        activeIdx = i;
        document.querySelectorAll('.fp-card').forEach((c, idx) => c.classList.toggle('active', idx === i));
        const card = $('fp-card-' + i);
        if (card && pan) card.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        if (mapReady && places[i] && pan) {
            map.panTo({ lat: places[i].lat, lng: places[i].lng });
            if (map.getZoom() < 14) map.setZoom(14);
        }
    }

    // ── Map ──────────────────────────────────────────────────────────────────────
    window.initMap = function () {
        map = new google.maps.Map($('fp-map'), {
            center: { lat: 7.8731, lng: 80.7718 },
            zoom: 8,
            mapTypeId: 'roadmap',
            streetViewControl: false,
            mapTypeControl: false,
            fullscreenControl: true,
            gestureHandling: 'greedy',
            clickableIcons: false,
        });
        mapReady = true;
        drawMarkers();
        fitMap();
    };

    function clearMarkers() {
        markers.forEach((m) => m.setMap(null));
        markers = [];
    }

    function drawMarkers() {
        if (!mapReady) return;
        clearMarkers();
        const maxVisits = Math.max.apply(null, [1].concat(places.map((p) => p.visits)));
        places.forEach((p, i) => {
            const scale = 11 + Math.round((p.visits / maxVisits) * 9);
            const m = new google.maps.Marker({
                map,
                position: { lat: p.lat, lng: p.lng },
                title: p.visits + ' visits',
                label: { text: String(i + 1), color: '#ffffff', fontWeight: '800', fontSize: '13px' },
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale,
                    fillColor: '#FA6908',
                    fillOpacity: 0.95,
                    strokeColor: '#ffffff',
                    strokeWeight: 2,
                },
                zIndex: 1000 - i,
            });
            m.addListener('click', () => setActive(i, true));
            markers.push(m);
        });
    }

    function fitMap() {
        if (!mapReady || places.length === 0) return;
        const b = new google.maps.LatLngBounds();
        places.forEach((p) => b.extend({ lat: p.lat, lng: p.lng }));
        map.fitBounds(b, 60);
        google.maps.event.addListenerOnce(map, 'idle', () => { if (map.getZoom() > 16) map.setZoom(16); });
    }

    // ── Addresses (queued one at a time: the geocoder is rate limited) ───────────
    async function resolveAddresses(token) {
        for (let i = 0; i < places.length; i++) {
            if (token !== runToken) return;
            const p = places[i];
            try {
                const res = await fetch('/geocode/reverse?lat=' + encodeURIComponent(p.lat) + '&lng=' + encodeURIComponent(p.lng), {
                    credentials: 'same-origin', headers: { 'Accept': 'application/json' },
                });
                if (res.status === 401) { window.location.href = '/login?expired=1'; return; }
                const body = await res.json().catch(() => null);
                if (token !== runToken) return;
                if (body && body.address) {
                    p.address = body.address;
                    const a = $('fp-addr-' + i);
                    if (a) a.textContent = body.address;
                    if (!body.cached) await new Promise((r) => setTimeout(r, 1100));
                }
            } catch (e) { /* keep the coordinates */ }
        }
    }

    // ── Save as place (inline form, no dialog) ───────────────────────────────────
    function openSaveForm(i) {
        const p = places[i];
        const box = $('fp-actions-' + i);
        box.textContent = '';

        const form = el('form', 'fp-saveform');
        const input = el('input');
        input.type = 'text'; input.maxLength = 100; input.required = true;
        input.setAttribute('aria-label', 'Name for this place');
        input.placeholder = 'Name, e.g. Depot';
        form.appendChild(input);

        const save = el('button', 'fp-btn primary', 'Save'); save.type = 'submit';
        const cancel = el('button', 'fp-btn ghost', 'Cancel'); cancel.type = 'button';
        cancel.addEventListener('click', (e) => { e.stopPropagation(); closeSaveForm(i); });
        form.appendChild(save); form.appendChild(cancel);
        form.addEventListener('click', (e) => e.stopPropagation());

        const msg = el('div', 'fp-msg');
        msg.setAttribute('role', 'status');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const name = input.value.trim();
            if (!name) { msg.className = 'fp-msg err'; msg.textContent = 'Give the place a name.'; return; }
            save.disabled = true; msg.className = 'fp-msg'; msg.textContent = 'Saving…';
            try {
                const res = await fetch('/saved-places', {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ name, latitude: p.lat, longitude: p.lng }),
                });
                if (res.status === 401) { window.location.href = '/login?expired=1'; return; }
                const body = await res.json().catch(() => null);
                if (res.ok && body && body.success) {
                    p.savedName = name;
                    render();
                    setActive(i, false);
                    return;
                }
                save.disabled = false; msg.className = 'fp-msg err';
                msg.textContent = (body && body.message) || 'Could not save this place. Try again.';
            } catch (err) {
                save.disabled = false; msg.className = 'fp-msg err';
                msg.textContent = 'Could not reach the server. Try again.';
            }
        });

        box.appendChild(form);
        box.appendChild(msg);
        input.focus();
    }

    function closeSaveForm(i) {
        const box = $('fp-actions-' + i);
        box.textContent = '';
        const b = el('button', 'fp-btn', 'Save as place');
        b.type = 'button';
        b.addEventListener('click', (e) => { e.stopPropagation(); openSaveForm(i); });
        box.appendChild(b);
    }

    // ── Wire up ──────────────────────────────────────────────────────────────────
    $('fp-vehicle').addEventListener('change', (e) => { vehicleId = e.target.value || null; load(); });
    document.querySelectorAll('#fp-period button').forEach((b) => {
        b.addEventListener('click', () => {
            if (b.dataset.period === period) return;
            period = b.dataset.period;
            renderControls();
            load();
        });
    });

    renderControls();
    load();
})();
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&callback=initMap&loading=async"></script>
@endsection