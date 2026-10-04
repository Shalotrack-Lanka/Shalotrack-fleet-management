{{-- ══════════════════════════════════════════════════════════════
     MarkerGlide — smooth live movement for one OR many Google Maps markers.
     Include INSIDE @section('content') (all-inline rule — never @push).

     The device reports every few seconds; setting a marker straight to each fix
     makes it hop. This glides from where the marker is NOW to the new fix over
     the real time between the two fixes (0.5–25 s), at constant speed, turning
     the arrow the short way round. A fix that arrives mid-glide retargets from
     the current spot. GPS noise (< 3 m) and impossible jumps (> ~200 km/h) are
     rejected; after a long gap, a hidden tab, or prefers-reduced-motion it snaps.

     MarkerGlide.move(key, marker, pos, heading, devMs, hooks) → 'moved' | 'noise' | 'jump' | 'snap'
       hooks.paint(deg)   called while the heading changes (rebuild the icon there)
       hooks.frame(pos)   called every animation frame with the interpolated spot
     MarkerGlide.forget(key) / MarkerGlide.reset()  — drop state
     ══════════════════════════════════════════════════════════════ --}}
<script>
    window.MarkerGlide = (function() {
        const MIN_MS = 500,
            MAX_MS = 25000,
            MIN_MOVE_M = 3,
            MAX_SPEED_MPS = 55.6,
            SPEED_CHECK_MS = 2000,
            FIRST_FIX_MAX_M = 500,
            FIRST_FIX_MS = 3000;
        const REDUCED = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
        const S = Object.create(null); // key → {anim, raf, heading, shown, last:{wall,devMs,pos,first}, marker, hooks}

        function haversineM(a, b) {
            const R = 6371000,
                r = Math.PI / 180;
            const dLat = (b.lat - a.lat) * r,
                dLng = (b.lng - a.lng) * r;
            const x = Math.sin(dLat / 2) ** 2 + Math.cos(a.lat * r) * Math.cos(b.lat * r) * Math.sin(dLng / 2) ** 2;
            return 2 * R * Math.asin(Math.min(1, Math.sqrt(x)));
        }
        /** Signed shortest turn between compass bearings (350→10 is +20). */
        function hDelta(from, to) {
            return ((to - from + 540) % 360) - 180;
        }
        const norm = h => (Number.isFinite(h) ? ((h % 360) + 360) % 360 : null);

        function paint(st, deg) {
            if (st.shown !== null && Math.abs(hDelta(st.shown, deg)) < 1.5) return;
            st.shown = deg;
            if (st.hooks && st.hooks.paint) st.hooks.paint(deg);
        }

        function stop(st) {
            if (st.raf) cancelAnimationFrame(st.raf);
            st.raf = 0;
            st.anim = null;
        }

        function frame(key, ts) {
            const st = S[key];
            if (!st) return;
            st.raf = 0;
            const a = st.anim;
            if (!a) return;
            const f = Math.max(0, Math.min(1, (ts - a.t0) / a.dur));
            const pos = {
                lat: a.from.lat + (a.to.lat - a.from.lat) * f,
                lng: a.from.lng + (a.to.lng - a.from.lng) * f
            };
            st.marker.setPosition(pos);
            st.heading = (a.h0 + a.dh * f + 360) % 360;
            paint(st, st.heading);
            if (st.hooks && st.hooks.frame) st.hooks.frame(pos);
            if (f < 1) st.raf = requestAnimationFrame(t => frame(key, t));
            else st.anim = null;
        }

        function move(key, marker, pos, heading, devMs, hooks) {
            const wall = Date.now();
            const h = norm(heading);
            let st = S[key];
            const p0 = marker.getPosition();
            const cur = p0 ? {
                lat: p0.lat(),
                lng: p0.lng()
            } : pos;

            if (!st) {
                st = S[key] = {
                    anim: null,
                    raf: 0,
                    heading: h ?? 0,
                    shown: null,
                    marker,
                    hooks,
                    // first fix for this marker: assume the spot shown is ~one report old
                    last: {
                        wall: wall - FIRST_FIX_MS,
                        devMs: NaN,
                        pos: cur,
                        first: true
                    }
                };
            }
            st.marker = marker;
            st.hooks = hooks || st.hooks;
            const heading2 = h ?? st.heading; // fix without a heading → keep the current one

            const dist = haversineM(st.last.pos, pos);
            if (dist < MIN_MOVE_M) { // noise floor: stay put, still let the arrow turn
                if (!st.anim && h !== null) {
                    st.heading = h;
                    paint(st, h);
                }
                return 'noise';
            }

            const devDelta = (Number.isFinite(devMs) && Number.isFinite(st.last.devMs)) ? devMs - st.last.devMs : NaN;
            const elapsed = (devDelta > 0 && devDelta <= 60000) ? devDelta : wall - st.last.wall;
            const firstFix = !!st.last.first;
            // A first fix is compared with a possibly hours-old resting spot, so it is never a "jump".
            // Otherwise ignore impossible jumps — but if the device keeps reporting from the new place
            // (3 in a row), the old spot was the wrong one: accept and snap.
            const jumpy = !firstFix && elapsed >= SPEED_CHECK_MS && dist / (elapsed / 1000) > MAX_SPEED_MPS;
            if (jumpy && (st.jumps = (st.jumps || 0) + 1) < 3) return 'jump';
            const forceSnap = jumpy;
            st.jumps = 0;
            st.last = {
                wall,
                devMs,
                pos,
                first: false
            };

            const snap = forceSnap || REDUCED || document.hidden || elapsed > MAX_MS || (firstFix && dist > FIRST_FIX_MAX_M);
            if (snap) {
                stop(st);
                marker.setPosition(pos);
                st.heading = heading2;
                paint(st, heading2);
                if (st.hooks && st.hooks.frame) st.hooks.frame(pos);
                return 'snap';
            }

            st.anim = {
                from: cur,
                to: pos,
                h0: st.heading,
                dh: hDelta(st.heading, heading2),
                t0: performance.now(),
                dur: Math.max(MIN_MS, Math.min(elapsed, MAX_MS))
            };
            if (!st.raf) st.raf = requestAnimationFrame(t => frame(key, t));
            return 'moved';
        }

        // rAF is throttled in a hidden tab: finish any glide instantly when it hides.
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) return;
            Object.keys(S).forEach(k => {
                const st = S[k];
                if (!st.anim) return;
                const to = st.anim.to;
                stop(st);
                st.marker.setPosition(to);
                if (st.hooks && st.hooks.frame) st.hooks.frame(to);
            });
        });

        return {
            move,
            forget(k) {
                if (S[k]) {
                    stop(S[k]);
                    delete S[k];
                }
            },
            reset() {
                Object.keys(S).forEach(k => {
                    stop(S[k]);
                    delete S[k];
                });
            },
            _state: S
        };
    })();
</script>