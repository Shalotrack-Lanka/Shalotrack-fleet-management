{{-- Shared Google Maps theme (dark navy to match the glass UI, or light). The choice is saved per
     browser and shared with the dashboard. Each page registers its map: STMap.register(map).
     Use STMap.styles() as the `styles` option when creating a map. --}}
<script>
    window.STMap = (function() {
        const DARK = [{
                elementType: 'geometry',
                stylers: [{
                    color: '#0b2a55'
                }]
            },
            {
                elementType: 'labels.text.stroke',
                stylers: [{
                    color: '#06182f'
                }]
            },
            {
                elementType: 'labels.text.fill',
                stylers: [{
                    color: '#8fa8c8'
                }]
            },
            {
                featureType: 'administrative.locality',
                elementType: 'labels.text.fill',
                stylers: [{
                    color: '#c3d3ea'
                }]
            },
            {
                featureType: 'poi',
                stylers: [{
                    visibility: 'off'
                }]
            },
            {
                featureType: 'transit',
                stylers: [{
                    visibility: 'off'
                }]
            },
            {
                featureType: 'landscape',
                elementType: 'geometry',
                stylers: [{
                    color: '#0d305f'
                }]
            },
            {
                featureType: 'road',
                elementType: 'geometry',
                stylers: [{
                    color: '#1b4379'
                }]
            },
            {
                featureType: 'road',
                elementType: 'geometry.stroke',
                stylers: [{
                    color: '#0b2a55'
                }]
            },
            {
                featureType: 'road',
                elementType: 'labels.text.fill',
                stylers: [{
                    color: '#7f9bc2'
                }]
            },
            {
                featureType: 'road.highway',
                elementType: 'geometry',
                stylers: [{
                    color: '#2a5a9a'
                }]
            },
            {
                featureType: 'water',
                elementType: 'geometry',
                stylers: [{
                    color: '#031428'
                }]
            },
            {
                featureType: 'water',
                elementType: 'labels.text.fill',
                stylers: [{
                    color: '#4a6a95'
                }]
            },
        ];
        const LIGHT = [{
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
        ];
        let theme = 'dark';
        try {
            if (localStorage.getItem('st_map_theme') === 'light') theme = 'light';
        } catch (_) {}
        const maps = [];
        const ICON_DARK = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/></svg>';
        const ICON_LIGHT = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>';

        function paintButton() {
            const b = document.getElementById('map-theme-btn');
            if (!b) return;
            b.setAttribute('aria-label', theme === 'dark' ? 'Switch map to light' : 'Switch map to dark');
            b.innerHTML = theme === 'dark' ? ICON_DARK : ICON_LIGHT;
        }

        function styles() {
            return theme === 'dark' ? DARK : LIGHT;
        }

        function apply() {
            maps.forEach(m => m.setOptions({
                styles: styles()
            }));
            paintButton();
        }
        document.addEventListener('DOMContentLoaded', function() {
            paintButton();
            const b = document.getElementById('map-theme-btn');
            if (b) b.addEventListener('click', function() {
                theme = theme === 'dark' ? 'light' : 'dark';
                try {
                    localStorage.setItem('st_map_theme', theme);
                } catch (_) {}
                apply();
            });
        });
        return {
            styles,
            theme: () => theme,
            register(m) {
                maps.push(m);
                m.setOptions({
                    styles: styles()
                });
                return m;
            }
        };
    })();
</script>

<script>
    /* Never fail silently: if Google rejects the key (referrer, billing, API not enabled) or the
   script never loads, tell the user instead of showing an empty page. The exact reason is
   always in the browser console (Google logs e.g. RefererNotAllowedMapError). */
    (function() {
        function notice() {
            if (document.getElementById('st-map-fail')) return;
            var d = document.createElement('div');
            d.id = 'st-map-fail';
            d.setAttribute('role', 'alert');
            d.style.cssText = 'position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);z-index:5;max-width:min(88vw,380px);padding:16px 20px;border-radius:20px;text-align:center;font:500 14px/1.45 system-ui,sans-serif;color:#e2e8f0;background:rgba(7,34,82,.88);border:1px solid rgba(255,255,255,.22);box-shadow:0 12px 36px rgba(0,8,30,.5)';
            d.textContent = 'The map could not be loaded. Please refresh, or contact support if this keeps happening.';
            document.body.appendChild(d);
        }
        window.gm_authFailure = function() {
            console.error('Google Maps rejected the API key (check key restrictions, billing and enabled APIs).');
            notice();
        };
        window.addEventListener('load', function() {
            setTimeout(function() {
                // This partial is in the shared layout, so it also runs on pages that have no
                // map at all (Reports, Stats, Settings...). Only complain when the page actually
                // asked for Google Maps and it never arrived.
                if (!document.querySelector('script[src*="maps.googleapis.com/maps/api/js"]')) return;
                if (!(window.google && window.google.maps && window.google.maps.Map)) notice();
            }, 8000);
        });
    })();
</script>
