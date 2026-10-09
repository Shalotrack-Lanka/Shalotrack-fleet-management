{{-- Shared Google Maps theme (dark navy to match the glass UI, or light). The choice is saved per
     browser and shared with the dashboard. Each page registers its map: STMap.register(map).
     Use STMap.styles() as the `styles` option when creating a map. --}}
<script>
    window.STMap = (function () {
        const DARK = [
            { elementType: 'geometry', stylers: [{ color: '#0b2a55' }] },
            { elementType: 'labels.text.stroke', stylers: [{ color: '#06182f' }] },
            { elementType: 'labels.text.fill', stylers: [{ color: '#8fa8c8' }] },
            { featureType: 'administrative.locality', elementType: 'labels.text.fill', stylers: [{ color: '#c3d3ea' }] },
            { featureType: 'poi', stylers: [{ visibility: 'off' }] },
            { featureType: 'transit', stylers: [{ visibility: 'off' }] },
            { featureType: 'landscape', elementType: 'geometry', stylers: [{ color: '#0d305f' }] },
            { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#1b4379' }] },
            { featureType: 'road', elementType: 'geometry.stroke', stylers: [{ color: '#0b2a55' }] },
            { featureType: 'road', elementType: 'labels.text.fill', stylers: [{ color: '#7f9bc2' }] },
            { featureType: 'road.highway', elementType: 'geometry', stylers: [{ color: '#2a5a9a' }] },
            { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#031428' }] },
            { featureType: 'water', elementType: 'labels.text.fill', stylers: [{ color: '#4a6a95' }] },
        ];
        const LIGHT = [
            { featureType: 'poi', elementType: 'labels', stylers: [{ visibility: 'off' }] },
            { featureType: 'transit', elementType: 'labels', stylers: [{ visibility: 'off' }] },
        ];
        let theme = 'dark';
        try { if (localStorage.getItem('st_map_theme') === 'light') theme = 'light'; } catch (_) {}
        const maps = [];
        const ICON_DARK = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/></svg>';
        const ICON_LIGHT = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>';
        function paintButton() {
            const b = document.getElementById('map-theme-btn');
            if (!b) return;
            b.setAttribute('aria-label', theme === 'dark' ? 'Switch map to light' : 'Switch map to dark');
            b.innerHTML = theme === 'dark' ? ICON_DARK : ICON_LIGHT;
        }
        function styles() { return theme === 'dark' ? DARK : LIGHT; }
        function apply() { maps.forEach(m => m.setOptions({ styles: styles() })); paintButton(); }
        document.addEventListener('DOMContentLoaded', function () {
            paintButton();
            const b = document.getElementById('map-theme-btn');
            if (b) b.addEventListener('click', function () {
                theme = theme === 'dark' ? 'light' : 'dark';
                try { localStorage.setItem('st_map_theme', theme); } catch (_) {}
                apply();
            });
        });
        return { styles, theme: () => theme, register(m) { maps.push(m); m.setOptions({ styles: styles() }); return m; } };
    })();
</script>