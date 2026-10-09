@extends('layouts.app')

@section('page-title', 'Compare Vehicles')
@section('title', 'Compare Vehicles')

@section('content')
{{-- Styles live inside @section('content'): the layout never renders @stack('styles'). --}}
<style>
    .cmp { max-width: 1120px; margin: 0 auto; padding: 20px 16px 48px; }
    .cmp-back { display: inline-block; font-size: 13px; font-weight: 600; color: #475569; text-decoration: none; margin-bottom: 10px; }
    .cmp-back:hover { color: #FA6908; }
    .cmp-title { font-size: 22px; font-weight: 800; color: #021F4A; margin: 0 0 4px; }
    .cmp-sub { font-size: 14px; color: #64748b; margin: 0 0 18px; line-height: 1.5; }

    .cmp-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; margin-bottom: 18px; }
    .cmp-label { font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #64748b; margin: 0 0 8px; }
    .cmp-row { display: flex; flex-wrap: wrap; gap: 8px; }
    .cmp-row + .cmp-label { margin-top: 16px; }

    .cmp-period { border: 1px solid #cbd5e1; background: #fff; color: #334155; font-size: 13.5px; font-weight: 600;
                  padding: 9px 14px; border-radius: 999px; cursor: pointer; min-height: 40px; }
    .cmp-period[aria-pressed="true"] { background: #021F4A; border-color: #021F4A; color: #fff; }

    .cmp-chip { display: inline-flex; align-items: center; gap: 8px; border: 1.5px solid #cbd5e1; background: #fff; color: #0f172a;
                font-size: 13.5px; padding: 8px 12px 8px 10px; border-radius: 12px; cursor: pointer; min-height: 44px; text-align: left; }
    .cmp-chip:disabled { opacity: .45; cursor: not-allowed; }
    .cmp-chip[aria-pressed="true"] { background: #f8fafc; }
    .cmp-dot { width: 12px; height: 12px; border-radius: 50%; border: 2px solid #94a3b8; flex-shrink: 0; }
    .cmp-chip small { display: block; font-size: 11.5px; color: #64748b; font-weight: 500; }
    .cmp-chip strong { font-weight: 700; letter-spacing: .01em; }
    .cmp-search { width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 12px; font-size: 16px; margin-bottom: 10px; }
    .cmp-hint { font-size: 12.5px; color: #64748b; margin: 10px 0 0; }

    .cmp-status { font-size: 13.5px; color: #475569; margin: 0 0 12px; min-height: 20px; }
    .cmp-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 10px; padding: 10px 12px; font-size: 13.5px; margin-bottom: 12px; }
    .cmp-empty { background: #fff; border: 1px dashed #cbd5e1; border-radius: 14px; padding: 36px 16px; text-align: center; color: #64748b; font-size: 14.5px; line-height: 1.6; }

    .cmp-insights { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 14px; padding: 14px 16px; margin-bottom: 18px; }
    .cmp-insights h2 { font-size: 13px; font-weight: 800; color: #9a3412; margin: 0 0 6px; text-transform: uppercase; letter-spacing: .04em; }
    .cmp-insights ul { margin: 0; padding-left: 18px; font-size: 14px; color: #431407; line-height: 1.6; }

    .cmp-legend { display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 14px; }
    .cmp-legend span { display: inline-flex; align-items: center; gap: 7px; font-size: 13.5px; font-weight: 700; color: #0f172a; }
    .cmp-legend i { width: 12px; height: 12px; border-radius: 3px; display: inline-block; }

    .cmp-grid { display: grid; grid-template-columns: 1fr; gap: 14px; }
    @media (min-width: 720px)  { .cmp-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1100px) { .cmp-grid { grid-template-columns: repeat(3, 1fr); } }

    .cmp-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px 16px; }
    .cmp-card h3 { font-size: 13px; font-weight: 700; color: #64748b; margin: 0 0 10px; text-transform: uppercase; letter-spacing: .04em; }
    .cmp-bar-row { margin-bottom: 11px; }
    .cmp-bar-row:last-child { margin-bottom: 0; }
    .cmp-bar-top { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; font-size: 13px; margin-bottom: 4px; }
    .cmp-bar-plate { font-weight: 700; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cmp-bar-val { font-weight: 800; color: #021F4A; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .cmp-bar-val em { font-style: normal; font-weight: 600; font-size: 11.5px; color: #64748b; }
    .cmp-track { height: 10px; background: #f1f5f9; border-radius: 6px; overflow: hidden; }
    .cmp-fill { height: 100%; border-radius: 6px; transition: width .45s ease; }
    .cmp-tag { display: inline-block; margin-left: 6px; font-size: 10.5px; font-weight: 800; padding: 2px 7px; border-radius: 999px; vertical-align: middle; }
    .cmp-tag.good { background: #dcfce7; color: #166534; }
    .cmp-tag.top  { background: #e0e7ff; color: #3730a3; }
    .cmp-tag.bad  { background: #fee2e2; color: #991b1b; }
    .cmp-na { font-size: 13px; color: #94a3b8; }
    @media (prefers-reduced-motion: reduce) { .cmp-fill { transition: none; } }
</style>

<div class="cmp">
    <a class="cmp-back" href="/stats">← Vehicle stats</a>
    <h1 class="cmp-title">Compare vehicles</h1>
    <p class="cmp-sub">Pick two or three vehicles to see distance, idle time, speed and overspeed side by side.</p>

    @if ($error)
        <div class="cmp-error" role="alert">{{ $error }}</div>
    @endif

    <section class="cmp-panel" aria-label="Choose what to compare">
        <p class="cmp-label" id="period-label">Period</p>
        <div class="cmp-row" role="group" aria-labelledby="period-label" id="period-group">
            <button type="button" class="cmp-period" data-period="today" aria-pressed="false">Today</button>
            <button type="button" class="cmp-period" data-period="week" aria-pressed="true">Last 7 days</button>
            <button type="button" class="cmp-period" data-period="month" aria-pressed="false">Last 30 days</button>
            <button type="button" class="cmp-period" data-period="all" aria-pressed="false">All time</button>
        </div>

        <p class="cmp-label" id="veh-label">Vehicles <span id="veh-count" style="text-transform:none;letter-spacing:0;font-weight:600"></span></p>
        <input type="search" id="veh-search" class="cmp-search" placeholder="Search by plate or name…" aria-label="Search vehicles" hidden>
        <div class="cmp-row" role="group" aria-labelledby="veh-label" id="veh-list"></div>
        <p class="cmp-hint" id="veh-hint">You can compare up to 3 vehicles.</p>
    </section>

    <p class="cmp-status" id="cmp-status" role="status" aria-live="polite"></p>
    <div id="cmp-errors"></div>
    <div id="cmp-results"></div>
</div>

<script>
(function () {
    'use strict';

    const VEHICLES = @json($vehicles);
    const MAX = 3;
    const COLORS = ['#FA6908', '#021F4A', '#0d9488'];
    const PERIODS = ['today', 'week', 'month', 'all'];
    const PERIOD_LABEL = { today: 'today', week: 'the last 7 days', month: 'the last 30 days', all: 'all time' };

    // direction: 'low' = lower is better, 'high' = highest is just notable (no judgement)
    const METRICS = [
        { key: 'totalDistanceKm',        label: 'Distance',           unit: 'km',  dec: 1, mark: 'high' },
        { key: 'totalTripCount',         label: 'Trips',              unit: '',    dec: 0, mark: 'high' },
        { key: 'totalStopCount',         label: 'Stops',              unit: '',    dec: 0, mark: 'high' },
        { key: 'totalDrivingMinutes',    label: 'Driving time',       dur: true,   mark: 'high' },
        { key: 'totalIdleMinutes',       label: 'Idle time',          dur: true,   mark: 'low'  },
        { key: 'idleShare',              label: 'Idle share of engine-on time', unit: '%', dec: 0, mark: 'low', derived: true },
        { key: 'totalIgnitionOnMinutes', label: 'Engine-on time',     dur: true,   mark: 'high' },
        { key: 'maxSpeed',               label: 'Top speed',          unit: 'km/h', dec: 0, mark: 'high' },
        { key: 'averageSpeed',           label: 'Average speed',      unit: 'km/h', dec: 1, mark: 'high' },
        { key: 'overspeedIncidentCount', label: 'Overspeed alerts',   unit: '',    dec: 0, mark: 'low'  },
    ];

    const $ = (id) => document.getElementById(id);
    const byId = new Map(VEHICLES.map((v) => [v.id, v]));

    // ── State (mirrored into the URL so a comparison can be bookmarked) ───────────
    const params = new URLSearchParams(location.search);
    let period = PERIODS.includes(params.get('period')) ? params.get('period') : 'week';
    let selected = (params.get('v') || '').split(',').filter((id) => byId.has(id)).slice(0, MAX);
    selected = [...new Set(selected)];
    let results = new Map();        // id -> stats object | { error: true }
    let runToken = 0;

    function syncUrl() {
        const q = new URLSearchParams();
        if (selected.length) q.set('v', selected.join(','));
        q.set('period', period);
        try { history.replaceState(null, '', location.pathname + '?' + q.toString()); } catch (e) { /* ignore */ }
    }

    function el(tag, cls, text) {
        const n = document.createElement(tag);
        if (cls) n.className = cls;
        if (text != null) n.textContent = text;
        return n;
    }

    function colorOf(id) { return COLORS[selected.indexOf(id)] || '#94a3b8'; }

    // ── Pickers ──────────────────────────────────────────────────────────────────
    function renderPeriods() {
        document.querySelectorAll('#period-group .cmp-period').forEach((b) => {
            b.setAttribute('aria-pressed', b.dataset.period === period ? 'true' : 'false');
        });
    }

    function renderVehicles() {
        const list = $('veh-list');
        const q = ($('veh-search').value || '').trim().toLowerCase();
        list.textContent = '';

        if (VEHICLES.length === 0) {
            list.appendChild(el('p', 'cmp-na', 'No GPS-enabled vehicles yet. Link a GPS device to a vehicle first.'));
            return;
        }

        VEHICLES.forEach((v) => {
            const on = selected.includes(v.id);
            const hay = (v.plate + ' ' + v.name).toLowerCase();
            if (q && !hay.includes(q) && !on) return;

            const b = el('button', 'cmp-chip');
            b.type = 'button';
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
            b.disabled = !on && selected.length >= MAX;

            const dot = el('span', 'cmp-dot');
            if (on) { dot.style.background = colorOf(v.id); dot.style.borderColor = colorOf(v.id); }
            b.appendChild(dot);

            const t = el('span');
            t.appendChild(el('strong', null, v.plate));
            const sub = [v.name, v.demo ? 'Demo' : null, v.shared ? 'Shared' : null].filter(Boolean).join(' · ');
            if (sub) t.appendChild(el('small', null, sub));
            b.appendChild(t);

            b.addEventListener('click', () => toggle(v.id));
            list.appendChild(b);
        });

        $('veh-count').textContent = '(' + selected.length + ' of ' + MAX + ' selected)';
        $('veh-hint').textContent = selected.length >= MAX
            ? 'Maximum of 3 reached. Tap a selected vehicle to remove it.'
            : 'You can compare up to 3 vehicles.';
        $('veh-search').hidden = VEHICLES.length <= 8;
    }

    function toggle(id) {
        if (selected.includes(id)) selected = selected.filter((x) => x !== id);
        else if (selected.length < MAX) selected = selected.concat(id);
        results.forEach((_, k) => { if (!selected.includes(k)) results.delete(k); });
        renderVehicles();
        load(false);
    }

    // ── Loading ──────────────────────────────────────────────────────────────────
    async function fetchStats(id, token) {
        try {
            const res = await fetch('/stats/' + encodeURIComponent(id) + '/data?period=' + encodeURIComponent(period), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            });
            if (res.status === 401) { window.location.href = '/login?expired=1'; return; }
            const body = await res.json().catch(() => null);
            if (token !== runToken) return;                       // a newer request replaced this one
            results.set(id, body && body.success && body.data ? body.data : { error: true });
        } catch (e) {
            if (token === runToken) results.set(id, { error: true });
        }
    }

    async function load(forceAll) {
        syncUrl();
        const token = ++runToken;
        if (forceAll) results = new Map();

        if (selected.length < 2) {
            $('cmp-status').textContent = '';
            render();
            return;
        }

        const missing = selected.filter((id) => !results.has(id));
        if (missing.length) {
            $('cmp-status').textContent = 'Loading ' + PERIOD_LABEL[period] + '…';
            await Promise.all(missing.map((id) => fetchStats(id, token)));
            if (token !== runToken) return;
        }
        $('cmp-status').textContent = '';
        render();
    }

    // ── Derived values ───────────────────────────────────────────────────────────
    function valueOf(m, d) {
        if (!d || d.error) return null;
        if (m.derived) {
            const ign = Number(d.totalIgnitionOnMinutes);
            const idle = Number(d.totalIdleMinutes);
            if (!isFinite(ign) || ign <= 0 || !isFinite(idle)) return null;
            return Math.min(100, (idle / ign) * 100);
        }
        const n = Number(d[m.key]);
        return isFinite(n) ? n : null;
    }

    function fmtVal(m, v) {
        if (v == null) return '–';
        if (m.dur) {
            const mins = Math.round(v);
            if (mins < 60) return mins + ' min';
            const h = Math.floor(mins / 60), r = mins % 60;
            return h + 'h' + (r ? ' ' + r + 'm' : '');
        }
        return v.toLocaleString('en-US', { minimumFractionDigits: m.dec, maximumFractionDigits: m.dec });
    }

    // ── Rendering ────────────────────────────────────────────────────────────────
    function render() {
        const out = $('cmp-results');
        const errs = $('cmp-errors');
        out.textContent = '';
        errs.textContent = '';

        if (selected.length < 2) {
            const e = el('div', 'cmp-empty');
            e.appendChild(el('strong', null, selected.length === 0 ? 'Pick two or three vehicles to compare.' : 'Pick one more vehicle.'));
            e.appendChild(document.createElement('br'));
            e.appendChild(document.createTextNode('Their numbers will appear here, side by side.'));
            out.appendChild(e);
            return;
        }

        const failed = selected.filter((id) => !results.has(id) || (results.get(id) && results.get(id).error));
        failed.forEach((id) => {
            const e = el('div', 'cmp-error', 'Could not load statistics for ' + byId.get(id).plate + '. Try again in a moment.');
            e.setAttribute('role', 'alert');
            errs.appendChild(e);
        });

        const ok = selected.filter((id) => !failed.includes(id));
        if (ok.length < 2) return;                      // nothing meaningful to compare yet

        // Legend
        const legend = el('div', 'cmp-legend');
        ok.forEach((id) => {
            const s = el('span');
            const sw = el('i'); sw.style.background = colorOf(id);
            s.appendChild(sw); s.appendChild(document.createTextNode(byId.get(id).plate));
            legend.appendChild(s);
        });

        // Insights
        const ins = insights(ok);
        if (ins.length) {
            const box = el('section', 'cmp-insights');
            box.appendChild(el('h2', null, 'At a glance, ' + PERIOD_LABEL[period]));
            const ul = el('ul');
            ins.forEach((t) => ul.appendChild(el('li', null, t)));
            box.appendChild(ul);
            out.appendChild(box);
        }
        out.appendChild(legend);

        // Metric cards
        const grid = el('div', 'cmp-grid');
        METRICS.forEach((m) => {
            const vals = ok.map((id) => valueOf(m, results.get(id)));
            if (vals.every((v) => v == null)) return;

            const max = Math.max.apply(null, vals.map((v) => (v == null ? 0 : v)));
            const present = vals.filter((v) => v != null);
            const allSame = present.length > 1 && present.every((v) => v === present[0]);
            const best = m.mark === 'low' ? Math.min.apply(null, present) : Math.max.apply(null, present);
            const worst = Math.max.apply(null, present);

            const card = el('section', 'cmp-card');
            card.appendChild(el('h3', null, m.label));

            ok.forEach((id, i) => {
                const v = vals[i];
                const row = el('div', 'cmp-bar-row');
                const top = el('div', 'cmp-bar-top');
                const plate = el('span', 'cmp-bar-plate', byId.get(id).plate);
                const val = el('span', 'cmp-bar-val');

                if (v == null) {
                    val.appendChild(el('span', 'cmp-na', 'no data'));
                } else {
                    val.appendChild(document.createTextNode(fmtVal(m, v)));
                    if (m.unit && !m.dur) val.appendChild(el('em', null, ' ' + m.unit));
                    if (!allSame && present.length > 1) {
                        if (m.mark === 'low' && v === best) {
                            val.appendChild(el('span', 'cmp-tag good', 'Lowest'));
                        } else if (m.mark === 'low' && v === worst && v > 0) {
                            val.appendChild(el('span', 'cmp-tag bad', 'Highest'));
                        } else if (m.mark === 'high' && v === best) {
                            val.appendChild(el('span', 'cmp-tag top', 'Most'));
                        }
                    }
                }
                top.appendChild(plate); top.appendChild(val);
                row.appendChild(top);

                const track = el('div', 'cmp-track');
                track.setAttribute('role', 'img');
                track.setAttribute('aria-label', byId.get(id).plate + ' ' + m.label + ' ' + fmtVal(m, v));
                const fill = el('div', 'cmp-fill');
                fill.style.background = colorOf(id);
                const pct = v == null || max <= 0 ? 0 : Math.max(v > 0 ? 2 : 0, (v / max) * 100);
                fill.style.width = '0%';
                track.appendChild(fill);
                row.appendChild(track);
                card.appendChild(row);
                requestAnimationFrame(() => requestAnimationFrame(() => { fill.style.width = pct + '%'; }));
            });
            grid.appendChild(card);
        });
        out.appendChild(grid);
    }

    function insights(ids) {
        const notes = [];
        const pick = (key, cmp) => {
            let bestId = null, bestV = null;
            ids.forEach((id) => {
                const m = METRICS.find((x) => x.key === key);
                const v = valueOf(m, results.get(id));
                if (v == null) return;
                if (bestV == null || cmp(v, bestV)) { bestV = v; bestId = id; }
            });
            return bestId == null ? null : { id: bestId, v: bestV };
        };
        const metric = (k) => METRICS.find((x) => x.key === k);
        const allZero = (k) => ids.every((id) => !(valueOf(metric(k), results.get(id)) > 0));

        if (!allZero('totalDistanceKm')) {
            const d = pick('totalDistanceKm', (a, b) => a > b);
            if (d) notes.push(byId.get(d.id).plate + ' covered the most distance: ' + fmtVal(metric('totalDistanceKm'), d.v) + ' km.');
        }
        const idle = pick('idleShare', (a, b) => a > b);
        if (idle && idle.v >= 10) {
            notes.push(byId.get(idle.id).plate + ' spent the most time idling: ' + Math.round(idle.v) + '% of its engine-on time.');
        }
        if (!allZero('overspeedIncidentCount')) {
            const o = pick('overspeedIncidentCount', (a, b) => a > b);
            if (o && o.v > 0) notes.push(byId.get(o.id).plate + ' had the most overspeed alerts (' + o.v + ').');
        } else {
            notes.push('No overspeed alerts for any of these vehicles. Nice.');
        }
        return notes;
    }

    // ── Wire up ──────────────────────────────────────────────────────────────────
    document.querySelectorAll('#period-group .cmp-period').forEach((b) => {
        b.addEventListener('click', () => {
            if (b.dataset.period === period) return;
            period = b.dataset.period;
            renderPeriods();
            load(true);
        });
    });
    $('veh-search').addEventListener('input', renderVehicles);

    renderPeriods();
    renderVehicles();
    load(true);
})();
</script>
@endsection