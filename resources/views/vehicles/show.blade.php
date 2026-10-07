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

<style>
    /* Live map: tall on desktop, ~55% of the screen on phones */
    .vd-map { width: 100%; height: 500px; }
    @media (max-width: 1023px) {
        .vd-map { height: 55vh; height: 55dvh; min-height: 300px; }
    }
</style>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">

    {{-- ── Left column: details ─────────────────────────────────────────── --}}
    <div class="space-y-4 md:space-y-6 order-2 lg:order-1">

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

        {{-- Vehicle health: filled in by JS from /api/vehicles/{id}/health (hidden when no device / no access) --}}
        @if(($vehicle['hasGpsDevice'] ?? false) && !($vehicle['isShared'] ?? false))
        <div id="health-card" class="hidden bg-white rounded-xl border border-gray-100 shadow-sm p-6" aria-live="polite">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-800">Vehicle Health</h3>
                <span id="health-badge" class="text-xs font-semibold px-2 py-1 rounded-full"></span>
            </div>
            <p id="health-headline" class="text-sm text-gray-600 mb-4"></p>
            <dl id="health-items" class="space-y-3"></dl>
            <p id="health-updated" class="text-xs text-gray-400 mt-4"></p>
        </div>
        @endif

        {{-- Reminders: revenue licence / insurance / service. Owner only (the API enforces it too). --}}
        @if(!($vehicle['isShared'] ?? false) && !($vehicle['isDemoVehicle'] ?? false))
        <div id="reminders-card" class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-1">
                <h3 class="font-semibold text-gray-800">Reminders</h3>
            </div>
            <p class="text-xs text-gray-400 mb-4">Get a push notification on your phone before these are due.</p>
            <ul id="reminders-list" class="space-y-3"></ul>
            <form id="reminder-form" class="hidden mt-4 pt-4 border-t border-gray-100 space-y-3" autocomplete="off" novalidate>
                <p id="reminder-form-title" class="text-sm font-medium text-gray-700"></p>
                <div>
                    <label for="reminder-date" class="block text-xs text-gray-400 mb-1">Due date</label>
                    <input id="reminder-date" type="date" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="reminder-notes" class="block text-xs text-gray-400 mb-1">Note (optional, only you see it)</label>
                    <input id="reminder-notes" type="text" maxlength="200" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <p id="reminder-error" class="hidden text-xs text-red-600" role="alert"></p>
                <div class="flex items-center gap-2">
                    <button id="reminder-save" type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">Save</button>
                    <button id="reminder-cancel" type="button" class="px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-100">Cancel</button>
                </div>
            </form>
        </div>
        @endif

        {{-- Alert settings: speed limit + idle alert. Owner only, needs a GPS device (the API enforces ownership too). --}}
        @if(($vehicle['hasGpsDevice'] ?? false) && !($vehicle['isShared'] ?? false) && !($vehicle['isDemoVehicle'] ?? false))
        <div id="alert-settings-card" class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-1">Alert settings</h3>
            <p class="text-xs text-gray-400 mb-4">Applies to everyone who gets this vehicle's alerts, including people it is shared with.</p>
            <form id="alert-settings-form" class="space-y-4" autocomplete="off" novalidate>
                <div>
                    <label for="as-speed" class="block text-xs text-gray-400 mb-1">Speed limit (km/h)</label>
                    <input id="as-speed" type="number" inputmode="numeric" step="1" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <p id="as-speed-hint" class="text-xs text-gray-400 mt-1"></p>
                </div>
                <div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input id="as-idle-on" type="checkbox" class="rounded border-gray-300">
                        Alert me when the engine is on but the vehicle is not moving
                    </label>
                    <div id="as-idle-row" class="hidden mt-2">
                        <label for="as-idle-min" class="block text-xs text-gray-400 mb-1">After how many minutes</label>
                        <input id="as-idle-min" type="number" inputmode="numeric" step="1" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <p id="as-idle-hint" class="text-xs text-gray-400 mt-1"></p>
                    </div>
                </div>
                <p id="as-msg" class="hidden text-xs" role="status"></p>
                <button id="as-save" type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">Save</button>
            </form>
        </div>
        @endif

        {{-- Live link: temporary, revocable, no-login link to this vehicle's live position. Owner only (the API enforces it too). --}}
        @if(($vehicle['hasGpsDevice'] ?? false) && !($vehicle['isShared'] ?? false) && !($vehicle['isDemoVehicle'] ?? false))
        <div id="live-link-card" class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-1">Share live location</h3>
            <p class="text-xs text-gray-400 mb-4">Anyone with the link can see this vehicle's plate number, live position and its route from the moment you create the link, until it ends or you stop it. No login needed.</p>
            <form id="ll-form" class="flex items-end gap-3" autocomplete="off">
                <div class="flex-1">
                    <label for="ll-hours" class="block text-xs text-gray-400 mb-1">Link works for</label>
                    <select id="ll-hours" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
                        <option value="1">1 hour</option>
                        <option value="2">2 hours</option>
                        <option value="4">4 hours</option>
                        <option value="8">8 hours</option>
                        <option value="12">12 hours</option>
                        <option value="24">24 hours</option>
                    </select>
                </div>
                <button id="ll-create" type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">Create link</button>
            </form>
            <p id="ll-msg" class="hidden text-xs mt-3" role="status"></p>
            <div id="ll-new" class="hidden mt-4 p-3 rounded-lg bg-blue-50 border border-blue-100">
                <p class="text-xs text-blue-900 mb-2">Copy it now. For security the link is shown only once; if you lose it, stop it and make a new one.</p>
                <input id="ll-url" type="text" readonly class="w-full border border-blue-200 rounded-lg px-3 py-2 text-xs font-mono bg-white">
                <div class="flex gap-2 mt-2">
                    <button id="ll-copy" type="button" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">Copy</button>
                    <button id="ll-share" type="button" class="hidden px-3 py-1.5 text-xs font-medium text-blue-700 bg-white border border-blue-200 rounded-lg hover:bg-blue-50">Share…</button>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-xs text-gray-400 mb-2">Active links</p>
                <ul id="ll-list" class="space-y-2"></ul>
                <p id="ll-empty" class="text-xs text-gray-400">None.</p>
            </div>
        </div>
        @endif

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
    <div class="lg:col-span-2 order-1 lg:order-2 bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
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
        <div id="gmap" class="vd-map"></div>
    </div>

</div>

@endif

{{-- ── Inline JS ────────────────────────────────────────────────────────────── --}}
@include('partials.marker-glide')

<script>
    const vehicleId = '{{ $vehicle["vehicleId"]     ?? "" }}'.toLowerCase();
    const hasGps = {{ ($vehicle['hasGpsDevice'] ?? false) ? 'true' : 'false' }};
    const vehicleNum = @json($vehicle['vehicleNumber'] ?? '');
    const vehicleMake = @json($vehicle['make'] ?? '');
    const vehicleMod = @json($vehicle['model'] ?? '');
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
    function esc(s) {
        return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function applyLocation(lat, lng, speed, ignition, lastUpdate) {
        const pos = {
            lat,
            lng
        };
        const popup = `<div style="min-width:160px;font-family:system-ui,sans-serif;padding:4px 2px">
            <p style="font-weight:700;font-size:14px;color:#021F4A;margin:0 0 2px">${esc(vehicleNum)}</p>
            <p style="font-size:12px;color:#6B7280;margin:0 0 4px">${esc(vehicleMake)} ${esc(vehicleMod)}</p>
            <p style="font-size:12px;margin:0">
                ${Math.round(speed ?? 0)} km/h &nbsp;·&nbsp;
                ${ignition ? 'Ignition on' : 'Ignition off'}
            </p>
        </div>`;

        if (marker) {
            // Glide to the new fix instead of hopping (noise/jumps are ignored).
            MarkerGlide.move('vehicle', marker, pos, null, lastUpdate ? Date.parse(lastUpdate) : NaN, {});
            if (!marker._online) {
                marker.setIcon(vehicleIcon(true));
                marker._online = true;
            }
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
            marker._online = true;
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

    // ── Vehicle health card ──────────────────────────────────────────────
    // Renders with textContent only (never innerHTML): every value comes from the
    // server but is still treated as untrusted text.
    const HEALTH_STYLE = {
        ok:      { badge: 'Healthy',   cls: 'text-green-700 bg-green-50 border border-green-200', val: 'text-green-700' },
        warn:    { badge: 'Attention', cls: 'text-amber-700 bg-amber-50 border border-amber-200', val: 'text-amber-700' },
        bad:     { badge: 'Problem',   cls: 'text-red-700 bg-red-50 border border-red-200',       val: 'text-red-700' },
        offline: { badge: 'Offline',   cls: 'text-gray-600 bg-gray-100 border border-gray-200',   val: 'text-gray-500' },
        info:    { badge: '',          cls: '',                                                   val: 'text-gray-700' },
    };
    let healthTimer = null;

    function renderHealth(h) {
        const card = document.getElementById('health-card');
        if (!card) return;
        if (!h || h.available === false) { card.classList.add('hidden'); return; }

        const st = HEALTH_STYLE[h.level] || HEALTH_STYLE.info;
        const badge = document.getElementById('health-badge');
        badge.textContent = st.badge;
        badge.className = 'text-xs font-semibold px-2 py-1 rounded-full ' + st.cls;
        document.getElementById('health-headline').textContent = h.headline || '';

        const dl = document.getElementById('health-items');
        dl.replaceChildren();
        (h.items || []).forEach(it => {
            const row = document.createElement('div');
            row.className = 'flex items-center justify-between text-sm';
            const dt = document.createElement('dt');
            dt.className = 'text-gray-400';
            dt.textContent = it.label;
            const dd = document.createElement('dd');
            dd.className = 'font-medium text-right ' + (HEALTH_STYLE[it.state] || HEALTH_STYLE.info).val;
            dd.textContent = it.value;
            row.append(dt, dd);
            dl.appendChild(row);
        });

        document.getElementById('health-updated').textContent =
            h.lastContact ? 'Last contact ' + h.lastContact + (h.lastContactAgo ? ' (' + h.lastContactAgo + ')' : '') : '';
        card.classList.remove('hidden');
    }

    async function loadHealth() {
        if (!hasGps || !vehicleId || !document.getElementById('health-card')) return;
        try {
            const res = await fetch(`/api/vehicles/${vehicleId}/health`, {
                credentials: 'include',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            });
            if (res.status === 401) { window.location.href = '/login?expired=1'; return; }
            if (!res.ok) return;            // keep whatever is on screen; the card is informational
            renderHealth(await res.json());
        } catch { /* network blip: keep last state */ }
    }

    // Poll gently, and not at all while the tab is hidden (saves the API and the user's data).
    function startHealthPoll() {
        loadHealth();
        healthTimer = setInterval(() => { if (!document.hidden) loadHealth(); }, 30000);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) loadHealth(); });
    }
    startHealthPoll();

    // ── Reminders (revenue licence / insurance / service) ─────────────────────
    const REMINDER_TYPES = [
        { type: 0, label: 'Revenue licence' },
        { type: 1, label: 'Insurance' },
        { type: 2, label: 'Service due' },
    ];
    let reminderByType = {};
    let reminderEditing = null;

    function reminderDaysText(d) {
        if (d === null || d === undefined) return '';
        if (d < 0)  return Math.abs(d) === 1 ? 'Overdue by 1 day' : 'Overdue by ' + Math.abs(d) + ' days';
        if (d === 0) return 'Due today';
        if (d === 1) return 'Due tomorrow';
        return 'In ' + d + ' days';
    }
    function reminderTone(d) {
        if (d === null || d === undefined) return 'text-gray-500';
        if (d < 0) return 'text-red-700';
        if (d <= 14) return 'text-amber-700';
        return 'text-green-700';
    }
    function reminderBtn(text, onClick) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'text-xs font-medium text-blue-600 hover:underline';
        b.textContent = text;
        b.addEventListener('click', onClick);
        return b;
    }

    function renderReminders() {
        const ul = document.getElementById('reminders-list');
        if (!ul) return;
        ul.replaceChildren();
        REMINDER_TYPES.forEach(t => {
            const r = reminderByType[t.type];
            const li = document.createElement('li');
            li.className = 'flex items-start justify-between gap-3 text-sm';

            const left = document.createElement('div');
            const name = document.createElement('div');
            name.className = 'text-gray-700 font-medium';
            name.textContent = t.label;
            const sub = document.createElement('div');
            sub.className = 'text-xs ' + (r ? reminderTone(r.daysLeft) : 'text-gray-400');
            sub.textContent = r ? (r.dueDate + ' · ' + reminderDaysText(r.daysLeft)) : 'Not set';
            left.append(name, sub);
            if (r && r.notes) {
                const n = document.createElement('div');
                n.className = 'text-xs text-gray-400 truncate max-w-[14rem]';
                n.textContent = r.notes;
                left.appendChild(n);
            }

            const right = document.createElement('div');
            right.className = 'flex items-center gap-3 shrink-0';
            right.appendChild(reminderBtn(r ? 'Edit' : 'Set', () => openReminderForm(t)));
            if (r) right.appendChild(reminderBtn('Remove', () => removeReminder(r)));

            li.append(left, right);
            ul.appendChild(li);
        });
    }

    function reminderError(msg) {
        const el = document.getElementById('reminder-error');
        if (!el) return;
        el.textContent = msg || '';
        el.classList.toggle('hidden', !msg);
    }

    function openReminderForm(t) {
        reminderEditing = t;
        const r = reminderByType[t.type];
        document.getElementById('reminder-form-title').textContent = t.label;
        const date = document.getElementById('reminder-date');
        date.value = r ? r.dueDate : '';
        document.getElementById('reminder-notes').value = r && r.notes ? r.notes : '';
        reminderError('');
        document.getElementById('reminder-form').classList.remove('hidden');
        date.focus();
    }
    function closeReminderForm() {
        reminderEditing = null;
        document.getElementById('reminder-form').classList.add('hidden');
        reminderError('');
    }

    async function reminderFetch(url, options) {
        const res = await fetch(url, Object.assign({
            credentials: 'include',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        }, options));
        if (res.status === 401) { window.location.href = '/login?expired=1'; return null; }
        let body = null;
        try { body = await res.json(); } catch { /* non-JSON error page */ }
        return { ok: res.ok, status: res.status, body };
    }

    async function loadReminders() {
        if (!document.getElementById('reminders-card') || !vehicleId) return;
        try {
            const r = await reminderFetch(`/api/vehicles/${vehicleId}/reminders`, { method: 'GET' });
            if (!r) return;
            if (!r.ok) {
                // Not the owner / not available: hide the card rather than show an error.
                if (r.status === 403 || r.status === 404) document.getElementById('reminders-card').classList.add('hidden');
                return;
            }
            reminderByType = {};
            (r.body.reminders || []).forEach(x => { reminderByType[x.type] = x; });
            renderReminders();
        } catch { /* network blip: leave the card as it is */ }
    }

    async function removeReminder(r) {
        if (!confirm('Remove this reminder?')) return;
        try {
            const res = await reminderFetch(`/api/reminders/${r.reminderId}`, { method: 'DELETE' });
            if (!res) return;
            if (res.ok) { delete reminderByType[r.type]; renderReminders(); closeReminderForm(); }
            else reminderError((res.body && res.body.message) || 'Could not remove the reminder.');
        } catch { reminderError('Network problem. Please try again.'); }
    }

    (function initReminders() {
        const form = document.getElementById('reminder-form');
        if (!form) return;
        document.getElementById('reminder-cancel').addEventListener('click', closeReminderForm);

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!reminderEditing) return;
            const dueDate = document.getElementById('reminder-date').value;
            if (!/^\d{4}-\d{2}-\d{2}$/.test(dueDate)) { reminderError('Choose a due date.'); return; }

            const btn = document.getElementById('reminder-save');
            btn.disabled = true;
            reminderError('');
            try {
                const res = await reminderFetch(`/api/vehicles/${vehicleId}/reminders`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        type: reminderEditing.type,
                        dueDate: dueDate,
                        notes: document.getElementById('reminder-notes').value.trim() || null,
                    }),
                });
                if (!res) return;
                if (res.ok && res.body && res.body.reminder) {
                    reminderByType[res.body.reminder.type] = res.body.reminder;
                    renderReminders();
                    closeReminderForm();
                } else {
                    const first = res.body && res.body.errors ? Object.values(res.body.errors)[0] : null;
                    reminderError((Array.isArray(first) ? first[0] : null) || (res.body && res.body.message) || 'Could not save the reminder.');
                }
            } catch { reminderError('Network problem. Please try again.'); }
            finally { btn.disabled = false; }
        });

        renderReminders();
        loadReminders();
    })();

    // ── Alert settings (speed limit + idle alert) ─────────────────────────────
    let alertSettings = null;

    function asMsg(text, isError) {
        const el = document.getElementById('as-msg');
        if (!el) return;
        el.textContent = text || '';
        el.className = 'text-xs ' + (isError ? 'text-red-600' : 'text-green-700') + (text ? '' : ' hidden');
    }

    function renderAlertSettings(s) {
        alertSettings = s;
        const speed = document.getElementById('as-speed');
        speed.min = s.minSpeedLimitKmh; speed.max = s.maxSpeedLimitKmh;
        speed.value = s.speedLimitKmh;
        document.getElementById('as-speed-hint').textContent =
            'Between ' + s.minSpeedLimitKmh + ' and ' + s.maxSpeedLimitKmh + '. Default ' + s.defaultSpeedLimitKmh + '.';

        const on = document.getElementById('as-idle-on');
        on.checked = s.idleAlertEnabled;
        const min = document.getElementById('as-idle-min');
        min.min = s.minIdleMinutes; min.max = s.maxIdleMinutes;
        min.value = s.idleAlertMinutes;
        document.getElementById('as-idle-hint').textContent =
            'Between ' + s.minIdleMinutes + ' and ' + s.maxIdleMinutes + ' minutes.';
        document.getElementById('as-idle-row').classList.toggle('hidden', !s.idleAlertEnabled);
    }

    async function asFetch(url, options) {
        const res = await fetch(url, Object.assign({
            credentials: 'include',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        }, options));
        if (res.status === 401) { window.location.href = '/login?expired=1'; return null; }
        let body = null;
        try { body = await res.json(); } catch { /* non-JSON error page */ }
        return { ok: res.ok, status: res.status, body };
    }

    (function initAlertSettings() {
        const form = document.getElementById('alert-settings-form');
        if (!form || !vehicleId) return;

        document.getElementById('as-idle-on').addEventListener('change', (e) => {
            document.getElementById('as-idle-row').classList.toggle('hidden', !e.target.checked);
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            asMsg('');
            const speed = Number(document.getElementById('as-speed').value);
            const idleOn = document.getElementById('as-idle-on').checked;
            const idleMin = Number(document.getElementById('as-idle-min').value);
            const s = alertSettings || { minSpeedLimitKmh: 20, maxSpeedLimitKmh: 200, minIdleMinutes: 3, maxIdleMinutes: 120 };

            if (!Number.isInteger(speed) || speed < s.minSpeedLimitKmh || speed > s.maxSpeedLimitKmh) {
                asMsg('Speed limit must be a whole number between ' + s.minSpeedLimitKmh + ' and ' + s.maxSpeedLimitKmh + '.', true); return;
            }
            if (idleOn && (!Number.isInteger(idleMin) || idleMin < s.minIdleMinutes || idleMin > s.maxIdleMinutes)) {
                asMsg('Idle time must be a whole number between ' + s.minIdleMinutes + ' and ' + s.maxIdleMinutes + ' minutes.', true); return;
            }

            const btn = document.getElementById('as-save');
            btn.disabled = true;
            try {
                const res = await asFetch(`/api/vehicles/${vehicleId}/alert-settings`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        speedLimitKmh: speed,
                        idleAlertEnabled: idleOn,
                        idleAlertMinutes: idleOn ? idleMin : (s.idleAlertMinutes || 10),
                    }),
                });
                if (!res) return;
                if (res.ok && res.body && res.body.settings) {
                    renderAlertSettings(res.body.settings);
                    asMsg('Saved.', false);
                } else {
                    const first = res.body && res.body.errors ? Object.values(res.body.errors)[0] : null;
                    asMsg((Array.isArray(first) ? first[0] : null) || (res.body && res.body.message) || 'Could not save the alert settings.', true);
                }
            } catch { asMsg('Network problem. Please try again.', true); }
            finally { btn.disabled = false; }
        });

        (async () => {
            try {
                const res = await asFetch(`/api/vehicles/${vehicleId}/alert-settings`, { method: 'GET' });
                if (!res) return;
                if (res.ok && res.body && res.body.settings) renderAlertSettings(res.body.settings);
                else if (res.status === 403 || res.status === 404) document.getElementById('alert-settings-card').classList.add('hidden');
            } catch { /* network blip: leave the form as it is */ }
        })();
    })();

    // ── Live link (temporary, revocable, no-login) ────────────────────────────
    // Built with textContent / DOM nodes only. The raw link is shown once, right after creation.
    (function initLiveLinks() {
        const card = document.getElementById('live-link-card');
        if (!card || !vehicleId) return;

        const listEl = document.getElementById('ll-list');
        const emptyEl = document.getElementById('ll-empty');

        function msg(text, isError) {
            const el = document.getElementById('ll-msg');
            el.textContent = text || '';
            el.className = 'text-xs mt-3 ' + (isError ? 'text-red-600' : 'text-green-700') + (text ? '' : ' hidden');
        }

        function fmt(iso) {
            const t = Date.parse(iso);
            return isNaN(t) ? '—' : new Date(t).toLocaleString([], { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
        }

        function renderLinks(links) {
            listEl.replaceChildren();
            links.forEach((l) => {
                const li = document.createElement('li');
                li.className = 'flex items-center justify-between gap-3 text-xs border border-gray-100 rounded-lg px-3 py-2';
                const label = document.createElement('span');
                label.className = 'text-gray-600';
                label.textContent = 'Ends ' + fmt(l.expiresAt);
                const stop = document.createElement('button');
                stop.type = 'button';
                stop.className = 'text-red-600 font-medium hover:underline';
                stop.textContent = 'Stop';
                stop.addEventListener('click', async () => {
                    stop.disabled = true;
                    try {
                        const res = await asFetch('/api/live-links/' + encodeURIComponent(l.linkId), { method: 'DELETE' });
                        if (!res) return;
                        if (res.ok) { msg('Link stopped.', false); await refresh(); }
                        else { msg((res.body && res.body.message) || 'Could not stop the link.', true); stop.disabled = false; }
                    } catch { msg('Network problem. Please try again.', true); stop.disabled = false; }
                });
                li.append(label, stop);
                listEl.appendChild(li);
            });
            emptyEl.classList.toggle('hidden', links.length > 0);
        }

        async function refresh() {
            try {
                const res = await asFetch('/api/vehicles/' + vehicleId + '/live-links', { method: 'GET' });
                if (!res) return;
                if (res.ok && res.body && Array.isArray(res.body.links)) renderLinks(res.body.links);
                else if (res.status === 403 || res.status === 404) card.classList.add('hidden');
            } catch { /* network blip: keep what is shown */ }
        }

        document.getElementById('ll-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            msg('');
            const hours = Number(document.getElementById('ll-hours').value);
            const btn = document.getElementById('ll-create');
            btn.disabled = true;
            try {
                const res = await asFetch('/api/vehicles/' + vehicleId + '/live-links', {
                    method: 'POST', body: JSON.stringify({ durationHours: hours }),
                });
                if (!res) return;
                if (res.ok && res.body && res.body.link && res.body.link.url) {
                    const url = res.body.link.url;
                    document.getElementById('ll-url').value = url;
                    document.getElementById('ll-new').classList.remove('hidden');
                    document.getElementById('ll-share').classList.toggle('hidden', !navigator.share);
                    await refresh();
                } else {
                    msg((res.body && res.body.message) || 'Could not create the link.', true);
                }
            } catch { msg('Network problem. Please try again.', true); }
            finally { btn.disabled = false; }
        });

        document.getElementById('ll-copy').addEventListener('click', async () => {
            const input = document.getElementById('ll-url');
            try { await navigator.clipboard.writeText(input.value); msg('Copied.', false); }
            catch { input.select(); msg('Press Ctrl+C to copy.', false); }
        });
        document.getElementById('ll-share').addEventListener('click', async () => {
            try { await navigator.share({ title: 'Live location', url: document.getElementById('ll-url').value }); } catch { /* cancelled */ }
        });

        refresh();
    })();

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
            applyLocation(parseFloat(loc.latitude), parseFloat(loc.longitude), loc.speed, loc.ignitionStatus ?? loc.ignition, loc.lastUpdate);
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
            applyLocation(lat, lng, data.speed, data.ignition ?? data.ignitionStatus, data.lastUpdate);
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

{{-- SignalR: self-hosted (npm run build → public/build/vendor), SRI computed by the build. --}}
@vendorScript('signalr')

{{-- Google Maps (defined before async load — initMap is the callback) --}}
<script async defer
    src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&callback=initMap&loading=async">
</script>

@endsection