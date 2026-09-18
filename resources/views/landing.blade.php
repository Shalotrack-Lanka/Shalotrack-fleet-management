<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ShaloTrack — Smart Fleet Management for Sri Lanka</title>
    <meta name="description" content="Track your vehicles in real-time, manage your fleet, and receive instant alerts. ShaloTrack is Sri Lanka's leading GPS fleet management platform." />
    @vite(['resources/css/app.css'])
</head>
<body class="bg-white text-gray-800 font-sans">

    {{-- ================================================================
         NAVBAR
    ================================================================ --}}
    <nav class="fixed top-0 left-0 right-0 z-50 bg-[#021F4A]/95 backdrop-blur-sm border-b border-blue-900">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-white tracking-tight">
                    Shalo<span class="text-[#FA6908]">Track</span>
                </h1>
                <p class="text-blue-300 text-xs">Fleet Management</p>
            </div>
            <div class="flex items-center gap-4">
                <a href="/login"
                   class="text-blue-200 hover:text-white text-sm font-medium transition">
                    Sign In
                </a>
                <a href="/login"
                   class="px-4 py-2 bg-[#FA6908] hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition">
                    Get Started
                </a>
            </div>
        </div>
    </nav>

    {{-- ================================================================
         HERO
    ================================================================ --}}
    <section class="min-h-screen bg-[#021F4A] flex items-center pt-20">
        <div class="max-w-6xl mx-auto px-6 py-20">
            <div class="grid grid-cols-2 gap-16 items-center">
                <div>
                    <span class="inline-block px-3 py-1 bg-[#FA6908]/20 text-[#FA6908] text-xs font-semibold rounded-full mb-6 tracking-wide uppercase">
                        Sri Lanka's Leading Fleet Platform
                    </span>
                    <h2 class="text-5xl font-bold text-white leading-tight mb-6">
                        Track Your Fleet.<br/>
                        <span class="text-[#FA6908]">In Real Time.</span>
                    </h2>
                    <p class="text-blue-200 text-lg mb-8 leading-relaxed">
                        Monitor your vehicles live on the map, receive instant alerts, manage geofences, and view detailed trip history — all from one powerful dashboard.
                    </p>
                    <div class="flex gap-4">
                        <a href="/login"
                           class="px-6 py-3 bg-[#FA6908] hover:bg-orange-600 text-white font-semibold rounded-xl transition text-sm">
                            Start Tracking Free
                        </a>
                        <a href="#features"
                           class="px-6 py-3 border border-blue-700 hover:border-blue-500 text-blue-200 hover:text-white font-semibold rounded-xl transition text-sm">
                            Learn More →
                        </a>
                    </div>
                    <div class="flex items-center gap-8 mt-10 pt-8 border-t border-blue-900">
                        <div>
                            <p class="text-2xl font-bold text-white">Real-time</p>
                            <p class="text-blue-400 text-xs">Live GPS tracking</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-white">24/7</p>
                            <p class="text-blue-400 text-xs">Always online</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-white">Instant</p>
                            <p class="text-blue-400 text-xs">Alert notifications</p>
                        </div>
                    </div>
                </div>

                {{-- Hero visual --}}
                <div class="relative">
                    <div class="bg-[#0A2D5E] rounded-2xl p-6 border border-blue-800 shadow-2xl">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-white font-semibold text-sm">Live Dashboard</p>
                                <p class="text-blue-400 text-xs">3 vehicles active</p>
                            </div>
                            <span class="flex items-center gap-1.5 text-xs text-green-400">
                                <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                                Live
                            </span>
                        </div>
                        {{-- Mock map --}}
                        <div class="bg-[#162B4A] rounded-xl h-48 mb-4 flex items-center justify-center relative overflow-hidden">
                            <div class="absolute inset-0 opacity-20">
                                <div class="absolute top-4 left-8 w-16 h-px bg-blue-400"></div>
                                <div class="absolute top-8 left-12 w-24 h-px bg-blue-400 rotate-45"></div>
                                <div class="absolute top-12 right-8 w-20 h-px bg-blue-400"></div>
                                <div class="absolute bottom-8 left-6 w-32 h-px bg-blue-400 -rotate-12"></div>
                                <div class="absolute bottom-12 right-4 w-16 h-px bg-blue-400 rotate-12"></div>
                            </div>
                            <div class="absolute top-10 left-16 w-8 h-8 bg-[#FA6908] rounded-full flex items-center justify-center shadow-lg">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z"/>
                                </svg>
                            </div>
                            <div class="absolute top-16 right-12 w-7 h-7 bg-[#FA6908] rounded-full flex items-center justify-center shadow-lg opacity-80">
                                <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z"/>
                                </svg>
                            </div>
                            <div class="absolute bottom-10 left-24 w-7 h-7 bg-gray-500 rounded-full flex items-center justify-center shadow-lg opacity-60">
                                <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z"/>
                                </svg>
                            </div>
                            <div class="text-center text-blue-400 text-xs">OpenStreetMap</div>
                        </div>
                        {{-- Vehicle cards --}}
                        <div class="space-y-2">
                            <div class="flex items-center justify-between bg-[#0D2040] rounded-lg px-3 py-2">
                                <div>
                                    <p class="text-white text-xs font-medium">WP BGU 1212</p>
                                    <p class="text-blue-400 text-xs">Toyota Prius</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-green-400 text-xs font-medium flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 bg-green-400 rounded-full animate-pulse"></span>Online
                                    </span>
                                    <p class="text-blue-400 text-xs">42 km/h</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between bg-[#0D2040] rounded-lg px-3 py-2">
                                <div>
                                    <p class="text-white text-xs font-medium">WP CAB 5678</p>
                                    <p class="text-blue-400 text-xs">Honda Fit</p>
                                </div>
                                <span class="text-gray-500 text-xs">Offline</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================================================================
         FEATURES
    ================================================================ --}}
    <section id="features" class="py-24 bg-gray-50">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-[#021F4A] mb-4">Everything you need to manage your fleet</h2>
                <p class="text-gray-500 text-lg max-w-2xl mx-auto">
                    Built specifically for Sri Lankan businesses, ShaloTrack gives you complete visibility and control over your vehicles.
                </p>
            </div>

            <div class="grid grid-cols-3 gap-8">

                @php
                $features = [
                    [
                        'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
                        'title' => 'Live GPS Tracking',
                        'desc'  => 'See exactly where your vehicles are at any moment on an interactive map. Updates every few seconds with speed, heading, and ignition status.',
                        'color' => 'bg-orange-50 text-[#FA6908]',
                    ],
                    [
                        'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
                        'title' => 'Instant Alerts',
                        'desc'  => 'Get notified immediately when a vehicle exceeds the speed limit, enters or exits a geofence, or goes offline unexpectedly.',
                        'color' => 'bg-red-50 text-red-500',
                    ],
                    [
                        'icon' => 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 2h8l2-2h2a1 1 0 000-2h-1',
                        'title' => 'Fleet Management',
                        'desc'  => 'Add and manage all your vehicles in one place. Link GPS devices, track maintenance, and keep your entire fleet organised.',
                        'color' => 'bg-blue-50 text-blue-500',
                    ],
                    [
                        'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4',
                        'title' => 'Trip History',
                        'desc'  => 'Replay any past journey on the map with a timeline scrubber. View speed, distance, and stops for complete trip analysis.',
                        'color' => 'bg-green-50 text-green-500',
                    ],
                    [
                        'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
                        'title' => 'Geofence Zones',
                        'desc'  => 'Draw virtual boundaries on the map. Get alerts when vehicles enter or leave designated areas like depots, client sites, or restricted zones.',
                        'color' => 'bg-purple-50 text-purple-500',
                    ],
                    [
                        'icon' => 'M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z',
                        'title' => 'Vehicle Sharing',
                        'desc'  => 'Share vehicle tracking access with team members, managers, or clients. Full control over who can see which vehicles.',
                        'color' => 'bg-yellow-50 text-yellow-600',
                    ],
                ];
                @endphp

                @foreach($features as $feature)
                    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition">
                        <div class="w-12 h-12 {{ $feature['color'] }} rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $feature['icon'] }}"/>
                            </svg>
                        </div>
                        <h3 class="font-bold text-gray-800 mb-2">{{ $feature['title'] }}</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">{{ $feature['desc'] }}</p>
                    </div>
                @endforeach

            </div>
        </div>
    </section>

    {{-- ================================================================
         HOW IT WORKS
    ================================================================ --}}
    <section class="py-24 bg-white">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-[#021F4A] mb-4">Get started in minutes</h2>
                <p class="text-gray-500">No complex setup. No hardware expertise needed.</p>
            </div>
            <div class="grid grid-cols-3 gap-8">
                @foreach([
                    ['01', 'Create your account', 'Register with your phone number — no password needed. OTP verification keeps your account secure.'],
                    ['02', 'Add your vehicles', 'Enter your vehicle details and link your ShaloTrack GPS device using the IMEI number.'],
                    ['03', 'Track in real time', 'Open the dashboard and see your fleet live on the map. Set alerts and geofences to stay in control.'],
                ] as $step)
                    <div class="text-center">
                        <div class="w-16 h-16 bg-[#021F4A] text-[#FA6908] rounded-2xl flex items-center justify-center text-2xl font-bold mx-auto mb-5">
                            {{ $step[0] }}
                        </div>
                        <h3 class="font-bold text-gray-800 mb-2">{{ $step[1] }}</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">{{ $step[2] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================================================================
         CTA
    ================================================================ --}}
    <section class="py-24 bg-[#021F4A]">
        <div class="max-w-3xl mx-auto px-6 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">
                Ready to take control of your fleet?
            </h2>
            <p class="text-blue-200 text-lg mb-8">
                Join businesses across Sri Lanka already using ShaloTrack to manage their vehicles smarter.
            </p>
            <a href="/login"
               class="inline-block px-8 py-4 bg-[#FA6908] hover:bg-orange-600 text-white font-bold rounded-xl transition text-lg">
                Start Tracking Today
            </a>
            <p class="text-blue-400 text-sm mt-4">No credit card required · Set up in minutes</p>
        </div>
    </section>

    {{-- ================================================================
         FOOTER
    ================================================================ --}}
    <footer class="bg-[#010F25] py-8">
        <div class="max-w-6xl mx-auto px-6 flex items-center justify-between">
            <div>
                <p class="text-white font-bold">Shalo<span class="text-[#FA6908]">Track</span></p>
                <p class="text-blue-400 text-xs mt-0.5">Fleet Management Portal</p>
            </div>
            <div class="flex items-center gap-6 text-sm text-blue-400">
                <a href="/login" class="hover:text-white transition">Sign In</a>
                <a href="/login" class="hover:text-white transition">Register</a>
            </div>
            <p class="text-blue-500 text-xs">© {{ date('Y') }} ShaloTrack Lanka (Pvt) Ltd</p>
        </div>
    </footer>

</body>
</html>