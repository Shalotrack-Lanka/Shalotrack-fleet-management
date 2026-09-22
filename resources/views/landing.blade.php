<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ShaloTrack — Real-Time Fleet Management for Sri Lanka</title>
    <meta name="description" content="Track your vehicles live, receive instant alerts, and manage your entire fleet from one dashboard. Built for Sri Lankan businesses." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        /* ── Grid map background ── */
        .map-grid {
            background-color: #021F4A;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* ── Road lines on the hero map ── */
        .road-h {
            position: absolute;
            height: 2px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 1px;
        }

        .road-v {
            position: absolute;
            width: 2px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 1px;
        }

        /* ── Vehicle marker pulse ── */
        @keyframes pulse-ring {
            0% {
                transform: scale(1);
                opacity: 0.6;
            }

            100% {
                transform: scale(2.8);
                opacity: 0;
            }
        }

        @keyframes vehicle-move {
            0% {
                transform: translate(0px, 0px) rotate(45deg);
            }

            25% {
                transform: translate(80px, -40px) rotate(90deg);
            }

            50% {
                transform: translate(140px, 20px) rotate(120deg);
            }

            75% {
                transform: translate(60px, 80px) rotate(60deg);
            }

            100% {
                transform: translate(0px, 0px) rotate(45deg);
            }
        }

        @keyframes vehicle2-move {
            0% {
                transform: translate(0px, 0px) rotate(200deg);
            }

            33% {
                transform: translate(-60px, 50px) rotate(240deg);
            }

            66% {
                transform: translate(-100px, -30px) rotate(180deg);
            }

            100% {
                transform: translate(0px, 0px) rotate(200deg);
            }
        }

        .vehicle-1 {
            animation: vehicle-move 12s ease-in-out infinite;
        }

        .vehicle-2 {
            animation: vehicle2-move 16s ease-in-out infinite;
        }

        .pulse-ring {
            position: absolute;
            inset: -10px;
            border-radius: 50%;
            background: #FA6908;
            animation: pulse-ring 2s ease-out infinite;
        }

        /* ── Hero load animation ── */
        @keyframes hero-in {
            from {
                opacity: 0;
                transform: translateY(24px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-content {
            animation: hero-in 0.8s ease-out 0.2s both;
        }

        .hero-visual {
            animation: hero-in 0.8s ease-out 0.5s both;
        }

        /* ── Stat counter ── */
        @keyframes count-up {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── Feature card hover ── */
        .feature-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(2, 31, 74, 0.12);
        }

        /* ── Alert animation ── */
        @keyframes alert-slide {

            0%,
            100% {
                transform: translateX(0);
                opacity: 1;
            }

            45% {
                transform: translateX(0);
                opacity: 1;
            }

            50% {
                transform: translateX(8px);
                opacity: 0;
            }

            55% {
                transform: translateX(-8px);
                opacity: 0;
            }

            60% {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .alert-badge {
            animation: alert-slide 4s ease-in-out infinite;
        }

        /* ── Speed counter ── */
        @keyframes speed-tick {
            0% {
                content: "18 km/h";
            }

            25% {
                content: "34 km/h";
            }

            50% {
                content: "52 km/h";
            }

            75% {
                content: "41 km/h";
            }

            100% {
                content: "18 km/h";
            }
        }

        /* ── Scroll reveal ── */
        .reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        @media (prefers-reduced-motion: reduce) {

            *,
            .vehicle-1,
            .vehicle-2,
            .pulse-ring,
            .hero-content,
            .hero-visual,
            .reveal {
                animation: none !important;
                transition: none !important;
                opacity: 1 !important;
                transform: none !important;
            }
        }
    </style>
</head>

<body class="bg-white text-gray-900 antialiased">

    {{-- ================================================================
         NAVBAR
    ================================================================ --}}
    <nav class="fixed top-0 left-0 right-0 z-50 bg-[#021F4A]/90 backdrop-blur-md border-b border-white/5">
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-2">
                {{-- Logo mark --}}
                <div class="w-7 h-7 bg-[#FA6908] rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg width="14" height="14" fill="white" viewBox="0 0 24 24">
                        <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z" />
                    </svg>
                </div>
                <span class="text-white font-bold text-lg tracking-tight">
                    Shalo<span class="text-[#FA6908]">Track</span>
                </span>
            </div>
            <div class="flex items-center gap-5">
                <a href="#features" class="text-blue-200 hover:text-white text-sm font-medium transition">Features</a>
                <a href="#how-it-works" class="text-blue-200 hover:text-white text-sm font-medium transition">How it works</a>
                <a href="/login" class="text-blue-200 hover:text-white text-sm font-medium transition">Sign in</a>
                <a href="/login"
                    class="px-4 py-2 bg-[#FA6908] hover:bg-orange-500 text-white text-sm font-semibold rounded-lg transition">
                    Get started
                </a>
            </div>
        </div>
    </nav>

    {{-- ================================================================
         HERO
    ================================================================ --}}
    <section class="map-grid min-h-screen flex items-center pt-16 overflow-hidden relative">

        {{-- Road lines --}}
        <div class="road-h" style="top:28%; left:0; right:0;"></div>
        <div class="road-h" style="top:62%; left:0; right:0;"></div>
        <div class="road-v" style="left:22%; top:0; bottom:0;"></div>
        <div class="road-v" style="left:55%; top:0; bottom:0;"></div>
        <div class="road-v" style="right:15%; top:0; bottom:0;"></div>

        <div class="max-w-6xl mx-auto px-6 py-24 w-full">
            <div class="grid grid-cols-2 gap-20 items-center">

                {{-- Left: Copy --}}
                <div class="hero-content">
                    <div class="inline-flex items-center gap-2 bg-white/10 text-blue-200 text-xs font-medium px-3 py-1.5 rounded-full mb-8">
                        <span class="w-1.5 h-1.5 bg-green-400 rounded-full animate-pulse"></span>
                        Live tracking active across Sri Lanka
                    </div>

                    <h1 class="text-6xl font-black text-white leading-[1.05] mb-6 tracking-tight">
                        Your fleet.<br />
                        Everywhere.<br />
                        <span class="text-[#FA6908]">Right now.</span>
                    </h1>

                    <p class="text-blue-200 text-lg leading-relaxed mb-10 max-w-md">
                        Real-time GPS tracking, instant alerts, and complete fleet control — built for Sri Lankan businesses that can't afford to lose sight of their vehicles.
                    </p>

                    <div class="flex items-center gap-4 mb-12">
                        <a href="/login"
                            class="px-6 py-3.5 bg-[#FA6908] hover:bg-orange-500 text-white font-bold rounded-xl transition text-sm shadow-lg shadow-orange-900/30">
                            Start tracking free
                        </a>
                        <a href="#features"
                            class="flex items-center gap-2 text-blue-200 hover:text-white text-sm font-medium transition group">
                            See how it works
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a>
                    </div>

                    {{-- Stats --}}
                    <div class="flex items-center gap-8 pt-8 border-t border-white/10">
                        <div>
                            <p class="text-3xl font-black text-white">GT06</p>
                            <p class="text-blue-400 text-xs mt-0.5">Device protocol</p>
                        </div>
                        <div class="w-px h-8 bg-white/10"></div>
                        <div>
                            <p class="text-3xl font-black text-white">20s</p>
                            <p class="text-blue-400 text-xs mt-0.5">Update interval</p>
                        </div>
                        <div class="w-px h-8 bg-white/10"></div>
                        <div>
                            <p class="text-3xl font-black text-white">24/7</p>
                            <p class="text-blue-400 text-xs mt-0.5">Always online</p>
                        </div>
                    </div>
                </div>

                {{-- Right: Animated dashboard card --}}
                <div class="hero-visual relative">

                    {{-- Glow effect --}}
                    <div class="absolute -inset-8 bg-[#FA6908]/10 rounded-3xl blur-3xl"></div>

                    {{-- Main card --}}
                    <div class="relative bg-[#0A2345] border border-white/10 rounded-2xl overflow-hidden shadow-2xl">

                        {{-- Card header --}}
                        <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-5 h-5 bg-[#FA6908] rounded flex items-center justify-center">
                                    <svg width="10" height="10" fill="white" viewBox="0 0 24 24">
                                        <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z" />
                                    </svg>
                                </div>
                                <span class="text-white text-sm font-semibold">Live Fleet</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-green-400">
                                <span class="w-1.5 h-1.5 bg-green-400 rounded-full animate-pulse"></span>
                                3 online
                            </div>
                        </div>

                        {{-- Animated map area --}}
                        <div class="relative bg-[#071A35] overflow-hidden" style="height: 220px;">

                            {{-- Grid --}}
                            <div class="absolute inset-0" style="background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px); background-size: 24px 24px;"></div>

                            {{-- Road lines --}}
                            <div class="absolute bg-white/5 h-0.5" style="top:40%; left:0; right:0;"></div>
                            <div class="absolute bg-white/5 h-0.5" style="top:70%; left:0; right:0;"></div>
                            <div class="absolute bg-white/5 w-0.5" style="left:35%; top:0; bottom:0;"></div>
                            <div class="absolute bg-white/5 w-0.5" style="left:70%; top:0; bottom:0;"></div>

                            {{-- Location labels --}}
                            <span class="absolute text-white/20 text-xs font-medium" style="top:12px; left:20px;">Colombo</span>
                            <span class="absolute text-white/20 text-xs font-medium" style="top:12px; right:20px;">Kandy</span>
                            <span class="absolute text-white/20 text-xs font-medium" style="bottom:12px; left:20px;">Galle</span>

                            {{-- Vehicle 1 (orange - online) --}}
                            <div class="vehicle-1 absolute" style="top: 80px; left: 100px;">
                                <div class="pulse-ring"></div>
                                <div class="relative w-8 h-8 bg-[#FA6908] rounded-full flex items-center justify-center shadow-lg shadow-orange-900/50 z-10">
                                    <svg width="14" height="14" fill="white" viewBox="0 0 24 24">
                                        <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z" />
                                    </svg>
                                </div>
                            </div>

                            {{-- Vehicle 2 (orange - online) --}}
                            <div class="vehicle-2 absolute" style="top: 120px; left: 240px;">
                                <div class="relative w-7 h-7 bg-[#FA6908]/80 rounded-full flex items-center justify-center shadow-md z-10">
                                    <svg width="12" height="12" fill="white" viewBox="0 0 24 24">
                                        <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z" />
                                    </svg>
                                </div>
                            </div>

                            {{-- Vehicle 3 (grey - offline) --}}
                            <div class="absolute" style="top: 155px; left: 310px;">
                                <div class="w-6 h-6 bg-gray-600 rounded-full flex items-center justify-center opacity-50">
                                    <svg width="10" height="10" fill="white" viewBox="0 0 24 24">
                                        <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z" />
                                    </svg>
                                </div>
                            </div>

                            {{-- Alert badge --}}
                            <div class="alert-badge absolute top-3 right-3 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-lg flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                Overspeed
                            </div>
                        </div>

                        {{-- Vehicle list --}}
                        <div class="divide-y divide-white/5">
                            <div class="flex items-center justify-between px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                                    <div>
                                        <p class="text-white text-xs font-semibold">WP BGU 1212</p>
                                        <p class="text-blue-400 text-xs">Toyota Prius · Colombo 7</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-[#FA6908] text-xs font-bold" id="speed-display">64 km/h</p>
                                    <p class="text-blue-500 text-xs">Ignition on</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                                    <div>
                                        <p class="text-white text-xs font-semibold">WP CAB 5678</p>
                                        <p class="text-blue-400 text-xs">Honda Fit · Nugegoda</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-white text-xs font-bold">28 km/h</p>
                                    <p class="text-blue-500 text-xs">Ignition on</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 bg-gray-500 rounded-full"></div>
                                    <div>
                                        <p class="text-gray-500 text-xs font-semibold">NW 3421</p>
                                        <p class="text-gray-600 text-xs">Isuzu · Last seen 2h ago</p>
                                    </div>
                                </div>
                                <p class="text-gray-600 text-xs">Offline</p>
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
    <section id="features" class="py-28 bg-white">
        <div class="max-w-6xl mx-auto px-6">

            <div class="max-w-xl mb-16 reveal">
                <p class="text-[#FA6908] text-sm font-semibold mb-3">Built for your business</p>
                <h2 class="text-4xl font-black text-[#021F4A] leading-tight mb-4">
                    Complete visibility.<br />Total control.
                </h2>
                <p class="text-gray-500 leading-relaxed">
                    Everything a Sri Lankan fleet operator needs, in one dashboard — no extra apps, no complicated setup.
                </p>
            </div>

            <div class="grid grid-cols-3 gap-6">
                @php
                $features = [
                ['bg'=>'#FFF3EB','fg'=>'#FA6908','title'=>'Live GPS Tracking','desc'=>'See your vehicles move in real time on an interactive map. Speed, heading, and ignition status — updated every 20 seconds.','icon'=>'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'],
                ['bg'=>'#FEF2F2','fg'=>'#EF4444','title'=>'Instant Alerts','desc'=>'Overspeed, geofence breach, ignition on/off, power cut, and low battery — pushed to your phone the moment they happen.','icon'=>'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
                ['bg'=>'#EFF6FF','fg'=>'#3B82F6','title'=>'Trip History','desc'=>'Replay any journey with a timeline scrubber. See the exact route, stops, speed at every point, and trip statistics.','icon'=>'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['bg'=>'#F0FDF4','fg'=>'#22C55E','title'=>'Geofence Zones','desc'=>'Draw zones on the map. Get alerted the moment a vehicle enters or leaves — a depot, a client site, or a restricted area.','icon'=>'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z'],
                ['bg'=>'#FAF5FF','fg'=>'#A855F7','title'=>'Vehicle Sharing','desc'=>'Share tracking access with your team, managers, or clients. Choose exactly which vehicles each person can see.','icon'=>'M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z'],
                ['bg'=>'#FFFBEB','fg'=>'#F59E0B','title'=>'Fleet Management','desc'=>'Add vehicles, link GPS devices, manage subscriptions, and keep your entire fleet organised in one place.','icon'=>'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 2h8l2-2h2a1 1 0 000-2h-1'],
                ];
                @endphp

                @foreach($features as $i => $f)
                <div class="feature-card border border-gray-100 rounded-2xl p-6 reveal" style="transition-delay: {{ $i * 60 }}ms">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background:{{ $f['bg'] }}">
                        <svg class="w-5 h-5" fill="none" stroke="{{ $f['fg'] }}" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $f['icon'] }}" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-[#021F4A] mb-2">{{ $f['title'] }}</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">{{ $f['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================================================================
         HOW IT WORKS
    ================================================================ --}}
    <section id="how-it-works" class="py-28" style="background:#F0F4FF;">
        <div class="max-w-5xl mx-auto px-6">
            <div class="text-center mb-16 reveal">
                <p class="text-[#FA6908] text-sm font-semibold mb-3">Simple setup</p>
                <h2 class="text-4xl font-black text-[#021F4A] mb-4">Up and running in minutes</h2>
                <p class="text-gray-500 max-w-md mx-auto">No technical expertise. No complicated installation. Just plug in the device and open the dashboard.</p>
            </div>

            <div class="relative">
                {{-- Connector line --}}
                <div class="absolute top-8 left-1/6 right-1/6 h-px bg-[#021F4A]/10 hidden md:block" style="left:16.5%; right:16.5%;"></div>

                <div class="grid grid-cols-3 gap-8">
                    @foreach([
                    ['Register','Enter your phone number. Verify with OTP. Your account is ready — no password, no friction.'],
                    ['Add vehicles','Enter your vehicle details and link your ShaloTrack GPS device by IMEI number.'],
                    ['Track live','Open the dashboard. Your vehicles appear on the map, live. Set alerts and geofences.'],
                    ] as $i => $step)
                    <div class="text-center reveal" style="transition-delay: {{ $i * 100 }}ms">
                        <div class="w-16 h-16 bg-[#021F4A] rounded-2xl flex items-center justify-center mx-auto mb-5 relative">
                            <span class="text-[#FA6908] font-black text-xl">{{ $i + 1 }}</span>
                            @if($i < 2)
                                <div class="absolute -right-4 top-1/2 -translate-y-1/2 text-gray-300 hidden md:block">→
                        </div>
                        @endif
                    </div>
                    <h3 class="font-bold text-[#021F4A] mb-2">{{ $step[0] }}</h3>
                    <p class="text-gray-500 text-sm leading-relaxed max-w-xs mx-auto">{{ $step[1] }}</p>
                </div>
                @endforeach
            </div>
        </div>
        </div>
    </section>

    {{-- ================================================================
         CTA
    ================================================================ --}}
    <section class="map-grid py-28 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-[#FA6908]/20 to-transparent"></div>
        <div class="max-w-3xl mx-auto px-6 text-center relative reveal">
            <h2 class="text-5xl font-black text-white mb-5 leading-tight">
                Your fleet is moving.<br />Are you watching?
            </h2>
            <p class="text-blue-200 text-lg mb-10 max-w-xl mx-auto leading-relaxed">
                Join fleet operators across Sri Lanka who track their vehicles with ShaloTrack — real-time, reliable, always on.
            </p>
            <a href="/login"
                class="inline-block px-8 py-4 bg-[#FA6908] hover:bg-orange-500 text-white font-bold rounded-xl transition text-base shadow-2xl shadow-orange-900/40">
                Start tracking for free
            </a>
            <p class="text-blue-400 text-sm mt-5">No credit card · Set up in minutes · Cancel anytime</p>
        </div>
    </section>

    {{-- ================================================================
         FOOTER
    ================================================================ --}}
    <footer class="bg-[#010F25] py-10">
        <div class="max-w-6xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 bg-[#FA6908] rounded flex items-center justify-center">
                    <svg width="10" height="10" fill="white" viewBox="0 0 24 24">
                        <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z" />
                    </svg>
                </div>
                <span class="text-white font-bold">Shalo<span class="text-[#FA6908]">Track</span></span>
                <span class="text-blue-600 text-xs ml-1">Lanka (Pvt) Ltd</span>
            </div>
            <div class="flex items-center gap-6 text-sm text-blue-500">
                <a href="/login" class="hover:text-blue-300 transition">Sign in</a>
                <a href="/login" class="hover:text-blue-300 transition">Register</a>
                <a href="#features" class="hover:text-blue-300 transition">Features</a>
            </div>
            <p class="text-blue-600 text-xs">© {{ date('Y') }} ShaloTrack Lanka (Pvt) Ltd</p>
        </div>
    </footer>

    <script>
        // Speed counter animation on the hero card
        const speeds = ['18 km/h', '42 km/h', '64 km/h', '31 km/h', '55 km/h', '28 km/h'];
        let si = 0;
        setInterval(() => {
            si = (si + 1) % speeds.length;
            const el = document.getElementById('speed-display');
            if (el) el.textContent = speeds[si];
        }, 2000);

        // Scroll reveal
        const revealEls = document.querySelectorAll('.reveal');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    e.target.classList.add('visible');
                    observer.unobserve(e.target);
                }
            });
        }, {
            threshold: 0.15
        });
        revealEls.forEach(el => observer.observe(el));
    </script>

</body>

</html>