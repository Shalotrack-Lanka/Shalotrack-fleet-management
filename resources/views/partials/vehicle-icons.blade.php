{{--
    VehicleIcons — shared helper for the custom top-down vehicle icons.

    Include once per page BEFORE the page script:  @include('partials.vehicle-icons')

      VehicleIcons.preload(['Car','Van',…])          start loading the images that page needs
      VehicleIcons.state(online)                     'online' (green) | 'offline' (grey)
      VehicleIcons.icon(type, state, headingDeg)     → google.maps.Icon, or null
      VehicleIcons.onReady(fn)                       run fn when a preload finishes

    icon() returns null when the type has no icon or the image is not loaded yet.
    The caller then keeps/uses its existing marker, so nothing ever breaks.

    Images are static PNGs from our own origin (CSP img-src 'self'); the rotated
    result is drawn on a canvas and handed to Google Maps as a data: URL, which
    img-src already allows. Heading is rounded to 10° buckets so a vehicle that
    turns smoothly produces at most 36 small bitmaps per state, cached forever.
--}}
<script>
    const VehicleIcons = (() => {
        const BASE = @json(asset('vehicle-icons'));
        const RULES = @json(\App\Support\VehicleIcon::RULES);
        const HEIGHT = { car: 44, suv: 46, van: 50, truck: 54, motorcycle: 40, 'three-wheeler': 42 }; // px on the map
        const BUCKET = 10;
        const DPR = Math.min(2, window.devicePixelRatio || 1);
        const imgs = Object.create(null); // "key-colour" → HTMLImageElement | 'failed'
        const cache = Object.create(null);
        const waiting = [];

        function key(type) {
            if (typeof type !== 'string') return null;
            const n = type.toLowerCase().replace(/[^a-z0-9]/g, '');
            if (!n) return null;
            for (const [needle, k] of RULES) { if (n.includes(needle)) return k; }
            return null;
        }
        const colourOf = state => (state === 'online' ? 'green' : 'blue'); // green = online, grey-blue = offline

        function load(k, colour) {
            const id = k + '-' + colour;
            if (imgs[id]) return;
            const im = new Image();
            imgs[id] = im;
            im.onload = () => { waiting.slice().forEach(fn => { try { fn(); } catch (_) {} }); };
            im.onerror = () => { imgs[id] = 'failed'; };
            im.src = BASE + '/' + id + '.png?v=1';
        }

        function preload(types) {
            new Set((types || []).map(key).filter(Boolean)).forEach(k => { load(k, 'green'); load(k, 'blue'); });
        }

        function state(online) {
            return online ? 'online' : 'offline';
        }

        function icon(type, st, heading) {
            const k = key(type);
            if (!k || typeof google === 'undefined' || !google.maps) return null;
            const colour = colourOf(st);
            const im = imgs[k + '-' + colour];
            if (!im || im === 'failed' || !im.complete || !im.naturalWidth) { load(k, colour); return null; }

            const deg = Number.isFinite(Number(heading)) ? ((Math.round(Number(heading) / BUCKET) * BUCKET) % 360 + 360) % 360 : 0;
            const ck = k + '|' + st + '|' + deg;
            if (cache[ck]) return cache[ck];

            const h = HEIGHT[k] || 44;
            const w = Math.round(h * im.naturalWidth / im.naturalHeight);
            const S = Math.ceil(Math.hypot(w, h)) + 4; // square big enough for any rotation
            const c = document.createElement('canvas');
            c.width = c.height = Math.round(S * DPR);
            const g = c.getContext('2d');
            g.scale(DPR, DPR);
            g.translate(S / 2, S / 2);
            g.rotate(deg * Math.PI / 180);
            g.drawImage(im, -w / 2, -h / 2, w, h);

            return (cache[ck] = {
                url: c.toDataURL('image/png'),
                scaledSize: new google.maps.Size(S, S),
                anchor: new google.maps.Point(S / 2, S / 2),
            });
        }

        return { key, preload, state, icon, onReady: fn => waiting.push(fn) };
    })();
</script>