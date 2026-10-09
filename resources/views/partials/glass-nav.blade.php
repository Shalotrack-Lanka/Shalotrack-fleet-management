{{-- Floating glass navigation: one orb at the bottom centre that opens an Apple-style dock
     (a 4-column glass sheet on phones). Replaces the old left sidebar on every page.
     Also defines the shared glass classes (.gl, .gl-red) used by the immersive dashboard. --}}
@php
    $glassNavItems = [
        ['Dashboard',          '/dashboard',          'dashboard',            'M3 11l9-8 9 8v10a1 1 0 01-1 1h-5v-7H9v7H4a1 1 0 01-1-1z'],
        ['Vehicles',           '/vehicles',           'vehicles*',            'M5 17h14M6 17v2M18 17v2M4 13l1.6-4.8A2 2 0 017.5 7h9a2 2 0 011.9 1.2L20 13v4H4zM7.5 14h.01M16.5 14h.01'],
        ['Trip History',       '/trips',              'trips*',               'M9 4L3 6v14l6-2 6 2 6-2V4l-6 2zM9 4v14M15 6v14'],
        ['Reports',            '/reports',            'reports*',             'M5 20V10M12 20V4M19 20v-7'],
        ['Geofences',          '/geofences',          'geofences*',           'M12 21a9 9 0 100-18 9 9 0 000 18zM12 15a3 3 0 100-6 3 3 0 000 6z'],
        ['Sharing',            '/sharing',            'sharing*',             'M18 8a3 3 0 100-6 3 3 0 000 6zM6 15a3 3 0 100-6 3 3 0 000 6zM18 22a3 3 0 100-6 3 3 0 000 6zM8.6 13.5l6.8 4M15.4 6.5l-6.8 4'],
        ['Renewals',           '/renewals',           'renewals*',            'M20 11a8 8 0 10-2.3 6.3M20 4v7h-7'],
        ['Complaints',         '/complaints',         'complaints*',          'M21 12a8 8 0 01-11.6 7.1L4 20l1-4.6A8 8 0 1121 12z'],
        ['Emergency Contacts', '/emergency-contacts', 'emergency-contacts*',  'M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z'],
        ['Saved Places',       '/saved-places',       'saved-places*',        'M12 21s-7-6-7-11a7 7 0 1114 0c0 5-7 11-7 11zM12 12a2.5 2.5 0 100-5 2.5 2.5 0 000 5z'],
        ['Frequent Places',    '/places',             'places*',              'M12 3l2.6 5.6 6 .7-4.5 4.1 1.2 6L12 16.4 6.7 19.4l1.2-6L3.4 9.3l6-.7z'],
        ['Vehicle Stats',      '/stats',              'stats*',               'M21 12A9 9 0 1112 3v9zM15 3.5A9 9 0 0120.5 9H15z'],
    ];
@endphp

<style>
    :root {
        --gl-bg: linear-gradient(135deg, rgba(255,255,255,.17), rgba(255,255,255,.05)), rgba(2,31,74,.46);
        --gl-border: rgba(255,255,255,.22);
        --gl-shadow: 0 14px 44px rgba(0,8,30,.45), inset 0 1px 0 rgba(255,255,255,.38);
    }
    .gl {
        color: #fff;
        background: var(--gl-bg);
        backdrop-filter: blur(22px) saturate(170%);
        -webkit-backdrop-filter: blur(22px) saturate(170%);
        border: 1px solid var(--gl-border);
        box-shadow: var(--gl-shadow);
    }
    .gl-red {
        background: linear-gradient(135deg, rgba(255,70,70,.42), rgba(255,70,70,.14)), rgba(60,5,5,.4);
        border-color: rgba(255,120,120,.45);
    }
    /* No blur support, or the user asked for less transparency: solid navy instead of glass. */
    @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
        .gl { background: rgba(2,31,74,.95); }
        .gl-red { background: rgba(127,29,29,.95); }
    }
    @media (prefers-reduced-transparency: reduce) {
        .gl { background: rgba(2,31,74,.96); backdrop-filter: none; -webkit-backdrop-filter: none; }
        .gl-red { background: rgba(127,29,29,.96); }
    }

    #glass-nav [hidden] { display: none !important; }
    #glass-nav { font-family: 'Outfit', system-ui, -apple-system, 'Segoe UI', sans-serif; }

    #gn-scrim { position: fixed; inset: 0; z-index: 44; background: rgba(1,12,34,.38); }

    #gn-orb {
        position: fixed; left: 50%; bottom: max(22px, env(safe-area-inset-bottom)); z-index: 46;
        width: 68px; height: 68px; margin-left: -34px; border-radius: 50%; padding: 0; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: transform .25s ease, opacity .2s ease;
    }
    #gn-orb:hover { transform: scale(1.08); }
    #gn-orb::before {
        content: ''; position: absolute; inset: -6px; border-radius: 50%; z-index: -1; filter: blur(9px);
        background: conic-gradient(from 0deg, rgba(250,105,8,0), rgba(250,105,8,.75), rgba(120,200,255,.55), rgba(250,105,8,0));
        animation: gn-glow 3.2s ease-in-out infinite;
    }
    #gn-orb svg { width: 28px; height: 28px; fill: #fff; }
    #gn-orb:focus-visible, .gn-item:focus-visible, #gn-close:focus-visible { outline: 3px solid #fff; outline-offset: 3px; }

    #gn-dock {
        position: fixed; left: 50%; bottom: max(22px, env(safe-area-inset-bottom)); z-index: 46;
        transform: translateX(-50%);
        padding: 12px 14px; border-radius: 30px;
        animation: gn-in .32s cubic-bezier(.2,.9,.3,1.2) both;
    }
    #gn-dock ul { list-style: none; margin: 0; padding: 0; display: flex; align-items: flex-end; gap: 8px; }
    #gn-dock li { display: block; }

    .gn-item {
        position: relative; display: flex; align-items: center; justify-content: center;
        width: 54px; height: 54px; border-radius: 17px; color: #fff; text-decoration: none;
        border: 1px solid rgba(255,255,255,.2);
        background: linear-gradient(160deg, rgba(255,255,255,.2), rgba(255,255,255,.05));
        transform-origin: 50% 100%;
        transition: transform .18s cubic-bezier(.3,.9,.4,1.2), background .15s;
    }
    .gn-item svg, #gn-close svg { width: 26px; height: 26px; stroke: #fff; fill: none; stroke-width: 1.7; stroke-linecap: round; stroke-linejoin: round; }
    .gn-item .gn-name { display: none; }
    .gn-item[aria-current="page"] { background: linear-gradient(160deg, rgba(250,105,8,.55), rgba(250,105,8,.18)); border-color: rgba(250,105,8,.7); }
    .gn-item[aria-current="page"]::before {
        content: ''; position: absolute; bottom: -9px; left: 50%; width: 6px; height: 6px; margin-left: -3px;
        border-radius: 50%; background: #FA6908; box-shadow: 0 0 8px #FA6908;
    }
    .gn-item::after {
        content: attr(data-label); position: absolute; bottom: calc(100% + 8px); left: 50%; transform: translateX(-50%);
        white-space: nowrap; font: 600 11px 'Outfit', system-ui, sans-serif; color: #fff;
        background: rgba(2,15,40,.9); padding: 4px 9px; border-radius: 8px; opacity: 0; pointer-events: none; transition: opacity .15s;
    }
    .gn-item:focus-visible::after { opacity: 1; }
    .gn-sep { width: 1px; height: 38px; margin: 0 4px 8px; background: rgba(255,255,255,.25); }
    #gn-close {
        width: 54px; height: 54px; border-radius: 17px; padding: 0; cursor: pointer; color: #fff;
        display: flex; align-items: center; justify-content: center;
        border: 1px solid rgba(255,255,255,.2); background: linear-gradient(160deg, rgba(255,255,255,.2), rgba(255,255,255,.05));
    }

    /* Magnification only where there is a real hover (not touch) and room for a row of 12. */
    @media (hover: hover) and (min-width: 820px) {
        .gn-item:hover { transform: scale(1.55) translateY(-6px); z-index: 3; }
        .gn-item:hover::after { opacity: 1; }
        li:has(+ li > .gn-item:hover) > .gn-item,
        li:hover + li > .gn-item { transform: scale(1.24) translateY(-2px); z-index: 2; }
    }

    /* Phones and small tablets: a glass sheet, 4 icons across, with names. */
    @media (max-width: 819px) {
        #gn-orb { width: 64px; height: 64px; margin-left: -32px; }
        #gn-dock { left: 14px; right: 14px; transform: none; bottom: max(16px, env(safe-area-inset-bottom)); border-radius: 32px; padding: 22px 12px 14px; }
        #gn-dock ul { display: grid; grid-template-columns: repeat(4, 1fr); row-gap: 16px; justify-items: center; align-items: start; }
        .gn-item { width: auto; height: auto; border: 0; background: none; flex-direction: column; gap: 6px; transform: none !important; padding: 0; }
        .gn-item::after, .gn-item::before { display: none !important; }
        .gn-item .gn-ic {
            width: 56px; height: 56px; border-radius: 18px; display: flex; align-items: center; justify-content: center;
            border: 1px solid rgba(255,255,255,.2); background: linear-gradient(160deg, rgba(255,255,255,.22), rgba(255,255,255,.06));
        }
        .gn-item[aria-current="page"] .gn-ic { background: linear-gradient(160deg, rgba(250,105,8,.6), rgba(250,105,8,.2)); border-color: rgba(250,105,8,.7); }
        .gn-item .gn-name { display: block; font: 500 11px/1.15 'Outfit', system-ui, sans-serif; text-align: center; max-width: 76px; }
        #gn-dock li.gn-sep { display: none; }
        #gn-dock li.gn-close-li { grid-column: 1 / -1; margin-top: 4px; }
    }
    @media (min-width: 820px) { .gn-ic { display: contents; } }

    @keyframes gn-glow { 0%,100% { opacity: .55; } 50% { opacity: 1; } }
    @keyframes gn-in { from { opacity: 0; transform: translateX(-50%) translateY(26px) scale(.86); } to { opacity: 1; transform: translateX(-50%); } }
    @media (max-width: 819px) { @keyframes gn-in { from { opacity: 0; transform: translateY(26px) scale(.94); } to { opacity: 1; transform: none; } } }
    @media (prefers-reduced-motion: reduce) {
        #gn-orb::before, #gn-dock { animation: none; }
        .gn-item, #gn-orb { transition: none; }
    }
</style>

<div id="glass-nav">
    <div id="gn-scrim" hidden></div>

    <nav id="gn-dock" class="gl" aria-label="Main navigation" hidden>
        <ul>
            @foreach($glassNavItems as [$label, $href, $match, $path])
            <li>
                <a class="gn-item" href="{{ $href }}" data-label="{{ $label }}" aria-label="{{ $label }}"
                    @if(request()->is($match)) aria-current="page" @endif>
                    <span class="gn-ic"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $path }}"/></svg></span>
                    <span class="gn-name" aria-hidden="true">{{ $label }}</span>
                </a>
            </li>
            @endforeach
            <li class="gn-sep" role="presentation"></li>
            <li class="gn-close-li">
                <button type="button" id="gn-close" aria-label="Close navigation">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </li>
        </ul>
    </nav>

    <button type="button" id="gn-orb" class="gl" aria-label="Open navigation" aria-expanded="false" aria-controls="gn-dock">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="5" cy="5" r="2"/><circle cx="12" cy="5" r="2"/><circle cx="19" cy="5" r="2"/><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/><circle cx="5" cy="19" r="2"/><circle cx="12" cy="19" r="2"/><circle cx="19" cy="19" r="2"/></svg>
    </button>
</div>

<script>
(function () {
    const orb = document.getElementById('gn-orb');
    const dock = document.getElementById('gn-dock');
    const scrim = document.getElementById('gn-scrim');
    const closeBtn = document.getElementById('gn-close');
    if (!orb || !dock) return;

    function setOpen(open, returnFocus) {
        dock.hidden = !open;
        scrim.hidden = !open;
        orb.hidden = open;
        orb.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            const target = dock.querySelector('[aria-current="page"]') || dock.querySelector('a');
            if (target) target.focus({ preventScroll: true });
        } else if (returnFocus) {
            orb.focus({ preventScroll: true });
        }
    }
    window.glassNavClose = function () { setOpen(false, false); };

    orb.addEventListener('click', function (e) { e.stopPropagation(); setOpen(true); });
    closeBtn.addEventListener('click', function () { setOpen(false, true); });
    scrim.addEventListener('click', function () { setOpen(false, false); });
    document.addEventListener('click', function (e) {
        if (!dock.hidden && !dock.contains(e.target) && !orb.contains(e.target)) setOpen(false, false);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !dock.hidden) { setOpen(false, true); }
    });
    // Back/forward cache can restore the page with the dock open.
    window.addEventListener('pageshow', function () { setOpen(false, false); });
})();
</script>