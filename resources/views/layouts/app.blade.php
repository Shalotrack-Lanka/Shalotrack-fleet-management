{{-- placeholder --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'ShaloTrack Fleet')</title>
    @vite(['resources/css/app.css'])

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

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .5;
            }
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

        .tile-icon.orange {
            background: #fff7f0;
        }

        .tile-icon.orange svg {
            fill: #FA6908;
        }

        .tile-icon.navy {
            background: #eef2ff;
        }

        .tile-icon.navy svg {
            fill: #021F4A;
        }

        .tile-icon.red {
            background: #fef2f2;
        }

        .tile-icon.red svg {
            fill: #ef4444;
        }

        /* ── Chart cards ─────────────────────────────────────────────────────────── */
        .chart-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px;
        }

        @media (min-width: 900px) {
            .chart-grid {
                grid-template-columns: 1fr 1fr;
            }

            .chart-grid .chart-card.full {
                grid-column: 1 / -1;
            }
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
    </style>
</head>

<body class="bg-gray-100 min-h-screen">

    {{-- ---- Sidebar ---- --}}
    <div class="flex min-h-screen">

        <aside class="w-64 bg-[#021F4A] text-white flex flex-col fixed inset-y-0 left-0 z-50">

            {{-- Logo --}}
            <div class="px-6 py-5 border-b border-blue-900">
                <h1 class="text-xl font-bold tracking-tight">
                    Shalo<span class="text-[#FA6908]">Track</span>
                </h1>
                <p class="text-blue-300 text-xs mt-0.5">Fleet Management</p>
            </div>

            {{-- Nav links --}}
            <nav class="flex-1 px-4 py-6 space-y-1">
                <a href="/dashboard"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->is('dashboard') ? 'bg-[#FA6908] text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </a>

                <a href="/vehicles"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->is('vehicles*') ? 'bg-[#FA6908] text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 2h8l2-2h2a1 1 0 000-2h-1" />
                    </svg>
                    Vehicles
                </a>

                <a href="/trips"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->is('trips*') ? 'bg-[#FA6908] text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    Trip History
                </a>

                <a href="/reports"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->is('reports*') ? 'bg-[#FA6908] text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Reports
                </a>

                <a href="/alerts"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->is('alerts') ? 'bg-[#FA6908] text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    Alerts
                </a>

                <a href="/geofences"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->is('geofences*') ? 'bg-[#FA6908] text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4" />
                    </svg>
                    Geofences
                </a>

                <a href="/sharing"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->is('sharing*') ? 'bg-[#FA6908] text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                    Sharing
                </a>

                <a href="/complaints"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->is('complaints*') ? 'bg-[#FA6908] text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-6l-4 4v-4z" />
                    </svg>
                    Complaints
                </a>


                <a href="/emergency-contacts"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->is('emergency-contacts') ? 'bg-[#FA6908] text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    Emergency Contacts
                </a>

                <a href="/saved-places"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->is('saved-places') ? 'bg-[#FA6908] text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Saved Places
                </a>

                <a href="/stats"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->is('stats*') ? 'bg-[#FA6908] text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Vehicle Stats
                </a>
            </nav>

            {{-- Bottom Spacer --}}
            <div class="px-4 py-4 border-blue-900 space-y-1">
                <!-- Empty container to keep sidebar layout intact after removing profile and logout -->
            </div>

        </aside>

        {{-- ---- Main content ---- --}}
        <main class="flex-1 ml-64 min-h-screen">

            {{-- Top bar --}}
            <header class="bg-white border-b border-gray-200 px-8 py-4 flex items-center justify-between sticky top-0 z-40">
                <h2 class="text-lg font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h2>

                {{-- Profile Dropdown Area --}}
                <div class="relative group inline-block text-left">
                    <div class="flex items-center gap-3 cursor-pointer py-2">
                        <div class="text-right">
                            @if(Session::get('customer_name'))
                            <p id="header-name" class="text-sm font-semibold text-gray-800">{{ Session::get('customer_name') }}</p>
                            <p class="text-xs text-gray-400">{{ Session::get('firebase_phone') }}</p>
                            @else
                            <p id="header-name" class="text-sm text-gray-500">{{ Session::get('firebase_phone') }}</p>
                            @endif
                        </div>
                        <div id="header-avatar" class="w-9 h-9 rounded-full bg-[#FA6908] flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                            {{ strtoupper(substr(Session::get('customer_name') ?? Session::get('firebase_phone', 'U'), 0, 1)) }}
                        </div>
                    </div>

                    <!-- Dropdown Menu -->
                    <div class="absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 border border-gray-100 overflow-hidden">
                        <a href="/profile" class="flex items-center gap-2 px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-orange-50 hover:text-orange-600 transition-colors">
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

            {{-- Page content --}}
            <div class="p-8">
                @yield('content')
            </div>
        </main>
    </div>


    @stack('scripts')
</body>

</html>