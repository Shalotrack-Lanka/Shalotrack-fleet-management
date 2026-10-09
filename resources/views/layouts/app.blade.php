{{-- placeholder --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'ShaloTrack Fleet')</title>
    @vite(['resources/css/app.css'])

    <script>
        // 'Tap' on touch screens, 'Click' with a mouse — pages use CLICK_WORD in JS strings
        // and .only-fine / .only-coarse spans in markup.
        window.CLICK_WORD = (window.matchMedia && matchMedia('(pointer: coarse)').matches) ? 'Tap' : 'Click';
    </script>

    <!-- CSS Stack for page specific styles -->
    @stack('styles')
    @stack('head')

    <style>
        /* ── Layout ─────────────────────────────────────────────────────────────── */
        .stats-wrap {
            display: flex;
            height: calc(100vh - 64px);
            overflow: hidden;
            background: #f1f5f9;
        }

        /* ── Sidebar ─────────────────────────────────────────────────────────────── */
        .sidebar {
            width: 280px;
            min-width: 280px;
            background: #fff;
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .sidebar-header {
            padding: 18px 16px 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .sidebar-header h2 {
            font-size: 15px;
            font-weight: 700;
            color: #021F4A;
            margin: 0 0 10px;
        }

        .sidebar-search {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 13px;
            outline: none;
            color: #334155;
            background: #f8fafc;
            transition: border-color .15s;
        }

        .sidebar-search:focus {
            border-color: #FA6908;
        }

        .vehicle-list {
            flex: 1;
            overflow-y: auto;
            padding: 8px 0;
        }

        .vehicle-list::-webkit-scrollbar {
            width: 4px;
        }

        .vehicle-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .vehicle-card {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 11px 16px;
            cursor: pointer;
            border-left: 3px solid transparent;
            transition: background .15s, border-color .15s;
        }

        .vehicle-card:hover {
            background: #f8fafc;
        }

        .vehicle-card.active {
            background: #fff7f0;
            border-left-color: #FA6908;
        }

        .vehicle-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #021F4A;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .vehicle-icon svg {
            width: 20px;
            height: 20px;
            fill: #fff;
        }

        .vehicle-icon.demo {
            background: #FA6908;
        }

        .vehicle-info {
            flex: 1;
            min-width: 0;
        }

        .vehicle-plate {
            font-size: 13px;
            font-weight: 700;
            color: #021F4A;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .vehicle-name {
            font-size: 11px;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 1px;
        }

        /* ── Main panel ──────────────────────────────────────────────────────────── */
        .stats-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-width: 0;
        }

        /* Period bar */
        .period-bar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0 24px;
            display: flex;
            align-items: center;
            gap: 4px;
            height: 52px;
            flex-shrink: 0;
        }

        .period-btn {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            background: transparent;
            color: #64748b;
            transition: background .15s, color .15s;
        }

        .period-btn:hover {
            background: #f1f5f9;
            color: #021F4A;
        }

        .period-btn.active {
            background: #FA6908;
            color: #fff;
        }

        /* Scrollable content */
        .stats-content {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
        }

        .stats-content::-webkit-scrollbar {
            width: 6px;
        }

        .stats-content::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        /* Empty / placeholder */
        .stats-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            gap: 12px;
            color: #94a3b8;
        }

        .stats-empty svg {
            width: 64px;
            height: 64px;
            opacity: .4;
        }

        .stats-empty p {
            font-size: 15px;
            margin: 0;
        }

        /* Loading skeleton */
        .skeleton-wrap {
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: .5; }
        }

        .skeleton-box {
            background: #e2e8f0;
            border-radius: 10px;
        }

        /* Vehicle header inside stats panel */
        .stats-vehicle-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
        }

        .stats-vehicle-header .icon-big {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #021F4A;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stats-vehicle-header .icon-big svg {
            width: 26px;
            height: 26px;
            fill: #fff;
        }

        .stats-vehicle-header .plate {
            font-size: 20px;
            font-weight: 800;
            color: #021F4A;
            line-height: 1;
        }

        .stats-vehicle-header .meta {
            font-size: 13px;
            color: #64748b;
            margin-top: 3px;
        }

        /* ── Stat tiles ──────────────────────────────────────────────────────────── */
        .tiles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 14px;
            margin-bottom: 28px;
        }

        .tile {
            background: #fff;
            border-radius: 12px;
            padding: 16px 18px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
        }

        .tile-label {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 6px;
        }

        .tile-value {
            font-size: 24px;
            font-weight: 800;
            color: #021F4A;
            line-height: 1;
        }

        .tile-unit {
            font-size: 12px;
            font-weight: 500;
            color: #64748b;
            margin-left: 3px;
        }

        .tile-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        .tile-icon svg {
            width: 18px;
            height: 18px;
        }

        .tile-icon.orange { background: #fff7f0; }
        .tile-icon.orange svg { fill: #FA6908; }
        .tile-icon.navy   { background: #eef2ff; }
        .tile-icon.navy svg   { fill: #021F4A; }
        .tile-icon.red    { background: #fef2f2; }
        .tile-icon.red svg    { fill: #ef4444; }

        /* ── Chart cards ─────────────────────────────────────────────────────────── */
        .chart-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px;
        }

        @media (min-width: 900px) {
            .chart-grid { grid-template-columns: 1fr 1fr; }
            .chart-grid .chart-card.full { grid-column: 1 / -1; }
        }

        .chart-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px 22px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
        }

        .chart-card h3 {
            font-size: 13px;
            font-weight: 700;
            color: #021F4A;
            margin: 0 0 16px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .chart-wrap {
            position: relative;
            height: 180px;
        }

        .only-coarse { display: none; }
        @media (pointer: coarse) {
            .only-coarse { display: inline; }
            .only-fine { display: none; }
        }

        /* iOS Safari zooms the whole page when a field under 16px gets focus.
           Many page-level styles set 13-14px, so force 16px on phones only. */
        @media (max-width: 767px) {
            input:not([type="checkbox"]):not([type="radio"]):not([type="range"]):not([type="file"]),
            select,
            textarea {
                font-size: 16px !important;
            }
        }

    </style>
</head>

<body class="@hasSection('immersive') bg-[#010F25] @else bg-gray-100 @endif min-h-screen">

    <div class="min-h-screen">

        {{-- No sidebar: navigation is the floating glass orb/dock (partials/glass-nav). --}}

        {{-- ---- Main content ---- --}}
        <main class="min-w-0 min-h-screen">

            {{-- Top bar (the immersive dashboard has floating chips instead) --}}
            @hasSection('immersive')
            @else
            <header class="bg-white border-b border-gray-200 px-4 md:px-8 py-4 flex items-center justify-between sticky top-0 z-40">

                <a href="/dashboard" class="mr-3 md:mr-4 flex-shrink-0 text-lg font-bold tracking-tight text-[#021F4A]" aria-label="ShaloTrack dashboard">
                    Shalo<span class="text-[#FA6908]">Track</span>
                </a>

                <h2 class="text-base md:text-lg font-semibold text-gray-800 flex-1 min-w-0 truncate">
                    @yield('page-title', 'Dashboard')
                </h2>

                {{-- Profile Dropdown Area --}}
                <div class="relative group inline-block text-left ml-3" id="profile-menu">
                    <button type="button" id="profile-menu-btn" aria-haspopup="true" aria-expanded="false" aria-controls="profile-menu-panel"
                        class="flex items-center gap-2 md:gap-3 cursor-pointer py-1 bg-transparent border-0 text-left">
                        <div class="text-right hidden sm:block">
                            @if(Session::get('customer_name'))
                            <p id="header-name" class="text-sm font-semibold text-gray-800 truncate max-w-[140px]">{{ Session::get('customer_name') }}</p>
                            <p class="text-xs text-gray-400">{{ Session::get('firebase_phone') }}</p>
                            @else
                            <p id="header-name" class="text-sm text-gray-500 truncate max-w-[140px]">{{ Session::get('firebase_phone') }}</p>
                            @endif
                        </div>
                        <div id="header-avatar"
                            class="w-9 h-9 rounded-full bg-[#FA6908] flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                            {{ strtoupper(substr(Session::get('customer_name') ?? Session::get('firebase_phone', 'U'), 0, 1)) }}
                        </div>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="profile-menu-panel" class="absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible group-[.menu-open]:opacity-100 group-[.menu-open]:visible transition-all duration-200 z-50 border border-gray-100 overflow-hidden">
                        <a href="/profile"
                            class="flex items-center gap-2 px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-orange-50 hover:text-orange-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profile
                        </a>
                        <form method="POST" action="/logout" class="m-0">
                            @csrf
                            <a href="/logout"
                                onclick="event.preventDefault(); this.closest('form').submit();"
                                class="flex items-center gap-2 px-4 py-3 text-sm font-semibold text-red-600 hover:bg-red-50 transition-colors border-t border-gray-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Logout
                            </a>
                        </form>
                    </div>
                </div>
            </header>
            @endif

            {{-- Renewal-required banner: set when the API answers 402
                 SUBSCRIPTION_RENEWAL_REQUIRED; cleared on visiting /renewals.
                 Same signal the Android app turns into its app-wide prompt. --}}
            @if(Session::get('renewal_required') && !request()->is('renewals*'))
            <div id="renewal-banner" class="@hasSection('immersive') fixed top-[78px] left-1/2 -translate-x-1/2 z-30 w-[calc(100%-32px)] max-w-2xl rounded-2xl shadow-lg @else border-b @endif bg-amber-50 border-amber-200 px-4 md:px-8 py-3 flex items-center gap-3 text-sm text-amber-800">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
                <p class="flex-1 min-w-0">The subscription for one of your vehicles has expired, so live tracking and history are paused.</p>
                <a href="/renewals" class="font-semibold text-[#FA6908] hover:text-orange-700 whitespace-nowrap">Renew now</a>
                <button onclick="document.getElementById('renewal-banner').remove()" class="text-amber-500 hover:text-amber-700" aria-label="Dismiss">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            @endif

            {{-- Page content --}}
            <div class="@hasSection('immersive') @else p-4 md:p-8 pb-28 md:pb-28 @endif">
                @yield('content')
            </div>
        </main>
    </div>

    @include('partials.glass-nav')

    <script>
        // ── Profile menu: tap/click (hover alone does not exist on touch) ──────
        const profileMenu = document.getElementById('profile-menu');
        const profileBtn  = document.getElementById('profile-menu-btn');
        function closeProfileMenu() {
            if (!profileMenu) return;
            profileMenu.classList.remove('menu-open');
            profileBtn.setAttribute('aria-expanded', 'false');
        }
        if (profileBtn) {
            profileBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                const open = profileMenu.classList.toggle('menu-open');
                profileBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            document.addEventListener('click', function (e) {
                if (!profileMenu.contains(e.target)) closeProfileMenu();
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { closeProfileMenu(); }
        });
    </script>

    {{-- Dialog accessibility, applied to every modal in the portal without touching each page:
         role="dialog" + aria-modal + a label, focus moves in on open and returns on close,
         Tab stays inside, Esc closes (by pressing the dialog's own Close/Cancel button, so each
         page's cleanup code still runs). Modals are found by markup convention (ids ending in
         "-modal", .ec-overlay, .sh-overlay, .dd-overlay, .gf-modal-wrap, [role=dialog]). --}}
    <script>
    (function () {
        const SEL = '[role="dialog"],[id$="-modal"],.ec-overlay,.sh-overlay,.dd-overlay,.gf-modal-wrap';
        const FOCUSABLE = 'a[href],button:not([disabled]),input:not([disabled]):not([type="hidden"]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])';
        const visible = el => el.getClientRects().length > 0 && getComputedStyle(el).visibility !== 'hidden';
        const focusables = dlg => [...dlg.querySelectorAll(FOCUSABLE)].filter(visible);
        const stack = [];
        let uid = 0;

        function dialogOf(root) {
            // The panel is the child that holds the controls; a sibling like .modal-backdrop is skipped.
            return root.querySelector('[role="dialog"]')
                || [...root.children].find(c => c.querySelector(FOCUSABLE))
                || root;
        }

        function prepare(root) {
            const dlg = root.getAttribute('role') === 'dialog' ? root : dialogOf(root);
            if (dlg.getAttribute('role') !== 'dialog') dlg.setAttribute('role', 'dialog');
            dlg.setAttribute('aria-modal', 'true');
            if (!dlg.hasAttribute('aria-label') && !dlg.hasAttribute('aria-labelledby')) {
                const h = dlg.querySelector('h1,h2,h3,[class*="title"]');
                if (h) {
                    if (!h.id) h.id = 'dlg-title-' + (++uid);
                    dlg.setAttribute('aria-labelledby', h.id);
                }
            }
            if (!dlg.hasAttribute('tabindex')) dlg.setAttribute('tabindex', '-1');
            return dlg;
        }

        function opened(root) {
            const dlg = prepare(root);
            stack.push({ root, dlg, prev: document.activeElement });
            if (!root.contains(document.activeElement)) {
                const f = focusables(dlg);
                const target = f.find(e => /^(INPUT|SELECT|TEXTAREA)$/.test(e.tagName)) || f[0] || dlg;
                setTimeout(() => target.focus({ preventScroll: true }), 0);
            }
        }

        function closed(root) {
            const i = stack.findIndex(s => s.root === root);
            if (i < 0) return;
            const [s] = stack.splice(i, 1);
            if (s.prev && document.contains(s.prev) && !root.contains(s.prev)) s.prev.focus({ preventScroll: true });
        }

        function closeControl(dlg) {
            const btns = [...dlg.querySelectorAll('button,[role="button"],a')].filter(visible);
            return btns.find(b => /close/i.test(b.getAttribute('aria-label') || '') || /close/i.test(b.className))
                || btns.find(b => /^(cancel|close|keep my account|no|done|ok)/i.test((b.textContent || '').trim()));
        }

        document.addEventListener('keydown', function (e) {
            const top = stack[stack.length - 1];
            if (!top) return;
            if (e.key === 'Escape' && !e.defaultPrevented) {
                const c = closeControl(top.dlg);
                if (c) { e.preventDefault(); e.stopPropagation(); c.click(); }
                return;
            }
            if (e.key !== 'Tab') return;
            const f = focusables(top.dlg);
            if (!f.length) { e.preventDefault(); top.dlg.focus(); return; }
            const first = f[0], last = f[f.length - 1];
            if (!top.dlg.contains(document.activeElement)) { e.preventDefault(); first.focus(); }
            else if (e.shiftKey && (document.activeElement === first || document.activeElement === top.dlg)) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }, true);

        function init() {
            const all = [...document.querySelectorAll(SEL)];
            // Outermost only (a [role=dialog] inside .dd-overlay is part of that overlay).
            const roots = all.filter(el => !el.parentElement || !el.parentElement.closest(SEL));
            const state = new Map(roots.map(r => [r, false]));
            const check = () => state.forEach((was, r) => {
                const now = visible(r);
                if (now && !was) { state.set(r, true); opened(r); }
                else if (!now && was) { state.set(r, false); closed(r); }
            });
            new MutationObserver(check).observe(document.body, { subtree: true, attributes: true, attributeFilter: ['class', 'style', 'hidden'] });
            check();
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
    })();
    </script>

    @stack('scripts')
</body>

</html>