<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'ShaloTrack Fleet')</title>
    @vite(['resources/css/app.css'])
    @stack('head')
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
                              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </a>

                <a href="/vehicles"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->is('vehicles*') ? 'bg-[#FA6908] text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 2h8l2-2h2a1 1 0 000-2h-1"/>
                    </svg>
                    Vehicles
                </a>

                <a href="/trips"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->is('trips*') ? 'bg-[#FA6908] text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                    Trip History
                </a>

                <a href="/alerts"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->is('alerts') ? 'bg-[#FA6908] text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    Alerts
                </a>

                <a href="/geofences"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->is('geofences*') ? 'bg-[#FA6908] text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4"/>
                    </svg>
                    Geofences
                </a>

                <a href="/sharing"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->is('sharing*') ? 'bg-[#FA6908] text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                    </svg>
                    Sharing
                </a>
            </nav>

                <a href="/emergency-contacts"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->is('emergency-contacts') ? 'bg-[#FA6908] text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    Emergency Contacts
                </a>

                <a href="/saved-places"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->is('saved-places') ? 'bg-[#FA6908] text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Saved Places
                </a>

                <a href="/stats"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->is('stats*') ? 'bg-[#FA6908] text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Vehicle Stats
                </a>
            </nav>

            {{-- Bottom: Profile + Logout --}}
            <div class="px-4 py-4 border-t border-blue-900 space-y-1">
                <a href="/profile"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ request()->is('profile') ? 'bg-[#FA6908] text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Profile
                </a>

                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-blue-200 hover:bg-blue-900 hover:text-white transition text-left">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        {{-- ---- Main content ---- --}}
        <main class="flex-1 ml-64 min-h-screen">

            {{-- Top bar --}}
            <header class="bg-white border-b border-gray-200 px-8 py-4 flex items-center justify-between sticky top-0 z-40">
                <h2 class="text-lg font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h2>
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        @if(Session::get('customer_name'))
                            <p class="text-sm font-semibold text-gray-800">{{ Session::get('customer_name') }}</p>
                            <p class="text-xs text-gray-400">{{ Session::get('firebase_phone') }}</p>
                        @else
                            <p class="text-sm text-gray-500">{{ Session::get('firebase_phone') }}</p>
                        @endif
                    </div>
                    <div class="w-9 h-9 rounded-full bg-[#FA6908] flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                        {{ strtoupper(substr(Session::get('customer_name') ?? Session::get('firebase_phone', 'U'), 0, 1)) }}
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