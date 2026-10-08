<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="strict-origin">
    <title>Live location · ShaloTrack</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        html,body{height:100%}
        body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#f1f5f9;color:#0f172a;display:flex;flex-direction:column}
        header{background:#021F4A;color:#fff;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px}
        .brand{font-weight:700;font-size:15px;letter-spacing:.2px}
        .brand span{color:#FA6908}
        .plate{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:16px;font-weight:700;background:#fff;color:#021F4A;padding:3px 10px;border-radius:6px;white-space:nowrap}
        #map{flex:1;min-height:0;background:#e2e8f0}
        .bar{background:#fff;border-top:1px solid #e2e8f0;padding:10px 16px;display:grid;grid-template-columns:repeat(3,1fr);gap:8px;text-align:center}
        .bar b{display:block;font-size:15px}
        .bar small{color:#64748b;font-size:11px}
        .status{display:flex;align-items:center;gap:6px;font-size:12px;color:#cbd5e1}
        .dot{width:8px;height:8px;border-radius:50%;background:#94a3b8}
        .dot.live{background:#22c55e}.dot.stale{background:#f59e0b}.dot.off{background:#ef4444}
        #gone{position:fixed;inset:0;background:rgba(15,23,42,.92);color:#fff;display:none;align-items:center;justify-content:center;text-align:center;padding:24px;z-index:10}
        #gone.show{display:flex}
        #gone h1{font-size:20px;margin-bottom:8px}
        #gone p{color:#cbd5e1;font-size:14px}
        .note{position:absolute;left:12px;right:12px;bottom:84px;background:#fff;border-radius:8px;padding:8px 12px;font-size:12px;color:#475569;box-shadow:0 2px 8px rgba(0,0,0,.15);display:none}
        .note.show{display:block}
        main{position:relative;flex:1;min-height:0;display:flex;flex-direction:column}
    </style>
</head>
<body data-token="{{ $token }}">
    <header>
        <div>
            <div class="brand">Shalo<span>Track</span> Live</div>
            <div class="status"><span id="dot" class="dot"></span><span id="status">Connecting…</span></div>
        </div>
        <div id="plate" class="plate">—</div>
    </header>

    <main>
        <div id="map" role="img" aria-label="Live map of the shared vehicle"></div>
        <div id="note" class="note" role="status"></div>
    </main>

    <div class="bar">
        <div><b id="speed">—</b><small>Speed</small></div>
        <div><b id="updated">—</b><small>Last update</small></div>
        <div><b id="expires">—</b><small>Link ends in</small></div>
    </div>

    <div id="gone" role="alert">
        <div>
            <h1 id="goneTitle">This link is no longer available</h1>
            <p id="goneText">It has expired or the owner stopped sharing.</p>
        </div>
    </div>

@include('partials.vehicle-icons')
<script>
    const TOKEN = document.body.dataset.token;
    const POLL_MS = 10000;          // position
    const TRAIL_EVERY = 6;          // full trail every 6th poll (about 60 s)
    let map = null, marker = null, line = null;
    let trail = [];                 // [{lat,lng}]
    let skew = 0;                   // server clock minus browser clock (ms)
    let expiresAt = null;
    let tick = 0, failures = 0, timer = null, stopped = false, mapReady = false, pending = null;

    const $ = (id) => document.getElementById(id);

    function setStatus(kind, text) {
        $('dot').className = 'dot ' + kind;
        $('status').textContent = text;
    }

    function fmtDuration(ms) {
        if (ms <= 0) return 'ended';
        const m = Math.floor(ms / 60000), h = Math.floor(m / 60);
        return h > 0 ? h + 'h ' + (m % 60) + 'm' : m + ' min';
    }

    function ageText(iso) {
        if (!iso) return '—';
        const s = Math.max(0, Math.round((Date.now() + skew - Date.parse(iso)) / 1000));
        if (s < 60) return s + ' s ago';
        if (s < 3600) return Math.floor(s / 60) + ' min ago';
        return Math.floor(s / 3600) + ' h ago';
    }

    function showGone(title, text) {
        stopped = true;
        clearTimeout(timer);
        if (title) $('goneTitle').textContent = title;
        if (text) $('goneText').textContent = text;
        $('gone').classList.add('show');
    }

    function initMap() {
        map = new google.maps.Map($('map'), {
            center: { lat: 7.8731, lng: 80.7718 }, zoom: 8,
            mapTypeControl: false, streetViewControl: false, fullscreenControl: true,
            styles: [{ featureType: 'poi', stylers: [{ visibility: 'off' }] }],
        });
        line = new google.maps.Polyline({
            map, path: [], strokeColor: '#FA6908', strokeOpacity: .9, strokeWeight: 4,
        });
        mapReady = true;
        if (pending) { draw(pending); pending = null; }
    }

    let vehicleType = null, lastDraw = null;
    const arrowIcon = (deg) => ({ path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW, scale: 6, fillColor: '#021F4A', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 2, rotation: deg });
    VehicleIcons.onReady(() => { if (lastDraw && marker) draw(lastDraw); });

    function draw(d) {
        lastDraw = d;
        if (!mapReady) { pending = d; return; }
        const p = d.position;
        const pos = { lat: p.latitude, lng: p.longitude };
        if (d.vehicleType) { vehicleType = d.vehicleType; VehicleIcons.preload([vehicleType]); }
        // No signal for 10 min → grey (offline) icon, same rule as the status pill.
        const online = (Date.now() + skew - Date.parse(p.lastUpdate || 0)) / 1000 <= 600;
        const custom = () => VehicleIcons.icon(vehicleType, VehicleIcons.state(online), p.heading || 0);

        if (!marker) {
            marker = new google.maps.Marker({
                map, position: pos, title: d.plateNumber,
                icon: custom() || arrowIcon(p.heading || 0),
            });
            map.setCenter(pos); map.setZoom(15);
        } else {
            marker.setPosition(pos);
            marker.setIcon(custom() || arrowIcon(p.heading || 0));
            if (!map.getBounds() || !map.getBounds().contains(pos)) map.panTo(pos);
        }
        line.setPath(trail);
    }

    function render(d, full) {
        $('plate').textContent = d.plateNumber || '—';
        $('speed').textContent = Math.round(d.position.speedKmh) + ' km/h';
        $('updated').textContent = ageText(d.position.lastUpdate);
        if (d.serverTime) skew = Date.parse(d.serverTime) - Date.now();
        expiresAt = d.expiresAt ? Date.parse(d.expiresAt) : null;

        if (full && Array.isArray(d.trail)) {
            trail = d.trail.map(t => ({ lat: t.latitude, lng: t.longitude }));
        }
        // Keep the line reaching the latest fix between full trail refreshes.
        const last = trail[trail.length - 1];
        if (!last || last.lat !== d.position.latitude || last.lng !== d.position.longitude) {
            trail.push({ lat: d.position.latitude, lng: d.position.longitude });
            if (trail.length > 2000) trail.shift();
        }

        const age = (Date.now() + skew - Date.parse(d.position.lastUpdate || 0)) / 1000;
        if (age > 600) setStatus('stale', 'No recent signal');
        else setStatus('live', d.position.isMoving ? 'Moving' : 'Stopped');
        draw(d);
    }

    async function poll() {
        if (stopped) return;
        const full = tick % TRAIL_EVERY === 0;
        tick++;
        let delay = POLL_MS;
        try {
            const res = await fetch('/live/' + encodeURIComponent(TOKEN) + '/data?trail=' + (full ? '1' : '0'), {
                headers: { 'Accept': 'application/json' }, cache: 'no-store', credentials: 'omit',
            });
            if (res.status === 404) { showGone(); return; }
            if (!res.ok) throw new Error('http');
            const body = await res.json();
            if (!body || !body.success) throw new Error('bad');
            failures = 0;
            $('note').classList.remove('show');
            render(body.data, full || trail.length === 0);
        } catch {
            failures++;
            setStatus('off', 'Reconnecting…');
            delay = Math.min(60000, POLL_MS * Math.pow(2, Math.min(failures, 3)));
            if (failures >= 3) { $('note').textContent = 'Connection problem. Trying again…'; $('note').classList.add('show'); }
        }
        timer = setTimeout(poll, delay);
    }

    // Countdown + "x s ago" keep moving between polls; ends the page when the link ends.
    setInterval(() => {
        if (stopped) return;
        if (expiresAt) {
            const left = expiresAt - (Date.now() + skew);
            $('expires').textContent = fmtDuration(left);
            if (left <= 0) showGone('This link has ended', 'The sharing time is over.');
        }
    }, 1000);

    window.initMap = initMap;
    poll();
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&callback=initMap&loading=async"></script>
</body>
</html>