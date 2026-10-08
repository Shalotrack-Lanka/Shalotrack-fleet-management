<!DOCTYPE html>
<html lang="en" class="scroll-smooth overflow-x-hidden">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#021F4A" />
    <title>ShaloTrack — Vehicle tracking for Sri Lanka</title>
    <meta name="description" content="See where every vehicle is, how fast it is going and whether the engine is on. Live GPS tracking, trip history, geofences and alerts for Sri Lankan businesses." />
    <meta name="robots" content="index, follow" />
    <link rel="canonical" href="https://fleet.shalotrack.com/" />

    {{-- Icons --}}
    <link rel="icon" href="/favicon.svg" type="image/svg+xml" />
    <link rel="icon" href="/favicon-32.png" type="image/png" sizes="32x32" />
    <link rel="apple-touch-icon" href="/apple-touch-icon.png" />

    {{-- Open Graph / social previews --}}
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="ShaloTrack" />
    <meta property="og:locale" content="en_LK" />
    <meta property="og:url" content="https://fleet.shalotrack.com/" />
    <meta property="og:title" content="ShaloTrack — Vehicle tracking for Sri Lanka" />
    <meta property="og:description" content="See where every vehicle is, how fast it is going and whether the engine is on. Live GPS tracking, trip history, geofences and alerts for Sri Lankan businesses." />
    <meta property="og:image" content="https://fleet.shalotrack.com/og-image.png" />
    <meta property="og:image:width" content="1200" />
    <meta property="og:image:height" content="630" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="ShaloTrack — Vehicle tracking for Sri Lanka" />
    <meta name="twitter:description" content="See where every vehicle is, how fast it is going and whether the engine is on. Live GPS tracking, trip history, geofences and alerts for Sri Lankan businesses." />
    <meta name="twitter:image" content="https://fleet.shalotrack.com/og-image.png" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:ital,wdth,wght@0,62..125,100..900;1,62..125,100..900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])

    {{-- Structured data: only facts that are verifiable and already stated elsewhere on this page --}}
    <script type="application/ld+json">
        @verbatim {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "ShaloTrack",
            "legalName": "ShaloTrack (Pvt) Ltd",
            "url": "https://fleet.shalotrack.com/",
            "logo": "https://fleet.shalotrack.com/apple-touch-icon.png",
            "email": "aloka@shalotrack.com",
            "telephone": "+94716553852",
            "contactPoint": [{
                "@type": "ContactPoint",
                "telephone": "+94716553852",
                "contactType": "customer support",
                "email": "aloka@shalotrack.com",
                "areaServed": "LK",
                "availableLanguage": ["en", "si"]
            }]
        }
        @endverbatim
    </script>
    <script type="application/ld+json">
        @verbatim {
            "@context": "https://schema.org",
            "@type": "SoftwareApplication",
            "name": "ShaloTrack",
            "applicationCategory": "BusinessApplication",
            "operatingSystem": "Web, Android",
            "url": "https://fleet.shalotrack.com/",
            "description": "Live GPS vehicle tracking, trip history, geofences and alerts for Sri Lankan vehicle owners and businesses.",
            "publisher": {
                "@type": "Organization",
                "name": "ShaloTrack",
                "url": "https://fleet.shalotrack.com/"
            }
        }
        @endverbatim
    </script>
    <script type="application/ld+json">
        @verbatim {
            "@context": "https://schema.org",
            "@type": "FAQPage",
            "mainEntity": [{
                    "@type": "Question",
                    "name": "Do I need to install anything on the vehicle?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Only the ShaloTrack GPS device. Link it to your vehicle with its IMEI number and the vehicle appears on your map."
                    }
                },
                {
                    "@type": "Question",
                    "name": "How often does the map update?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Every 20 seconds while the device is reporting."
                    }
                },
                {
                    "@type": "Question",
                    "name": "Can other people see my vehicles?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Only if you share them. You choose which vehicles each person sees, you can send a live link that expires, and you can remove access at any time."
                    }
                },
                {
                    "@type": "Question",
                    "name": "Do I need the app, or can I use the web?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Both work with the same account. Alerts are delivered to the ShaloTrack mobile app."
                    }
                },
                {
                    "@type": "Question",
                    "name": "How long do I stay signed in?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Up to 90 days after you sign in with your code, unless you sign out first."
                    }
                },
                {
                    "@type": "Question",
                    "name": "How do I delete my account?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "From your profile page. You can download a copy of your data first."
                    }
                }
            ]
        }
        @endverbatim
    </script>
    <style>
        /* Tailwind does the styling. This block only holds what utilities cannot express:
           keyframes, the display type voice, and the scroll-driven timelines (kept here so
           the shorthand/longhand order is guaranteed). */
        html {
            scroll-padding-top: 5rem;
        }

        body {
            font-family: 'Archivo', system-ui, sans-serif;
            font-stretch: 100%;
            -webkit-font-smoothing: antialiased;
        }

        .display {
            font-stretch: 70%;
            font-weight: 800;
            line-height: .96;
            word-spacing: .08em;
            letter-spacing: 0;
        }

        .cond {
            font-stretch: 75%;
            font-weight: 800;
            line-height: 1.1;
            word-spacing: .06em;
        }

        @keyframes rise {
            from {
                opacity: 0;
                transform: translateY(1.5rem);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }

        @keyframes draw {
            to {
                stroke-dashoffset: 0;
            }
        }

        @keyframes marquee {
            to {
                transform: translateX(-50%);
            }
        }

        @keyframes grow {
            from {
                transform: scaleX(0);
            }

            to {
                transform: scaleX(1);
            }
        }

        @keyframes drift {
            to {
                transform: translateY(3.5rem) scale(1.05);
            }
        }

        @keyframes glow {

            0%,
            100% {
                opacity: .55;
            }

            50% {
                opacity: 1;
            }
        }

        @keyframes grow-y {
            from {
                transform: scaleY(0);
            }

            to {
                transform: scaleY(1);
            }
        }

        @supports (animation-timeline: scroll()) {
            .scroll-progress {
                animation: grow linear both;
                animation-timeline: scroll(root);
            }

            .map-drift {
                animation: drift linear both;
                animation-timeline: scroll(root);
                animation-range: 0 90vh;
            }
        }

        /* Timeline fill: draws down as the "story" list scrolls through view. Chromium today;
           everywhere else just shows the plain grey connector line (see noscript/fallback rule below). */
        .story-track {
            view-timeline-name: --story-track;
            view-timeline-axis: block;
        }

        @supports (animation-timeline: view()) {
            .timeline-fill {
                animation: grow-y linear both;
                animation-timeline: --story-track;
                animation-range: entry 0% cover 70%;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .scroll-progress,
            .map-drift,
            .timeline-fill {
                animation: none !important;
            }
        }
    </style>
    <noscript>
        <style>
            [data-reveal] {
                opacity: 1 !important;
                transform: none !important;
            }

            .route-line {
                stroke-dashoffset: 0 !important;
            }
        </style>
    </noscript>
</head>

<body class="overflow-x-hidden bg-white text-[#0A1B33]">

    {{-- Scroll progress (scroll-driven animation where supported, JS fallback elsewhere) --}}
    <div id="progress" class="scroll-progress fixed inset-x-0 top-0 z-[60] h-1 origin-left scale-x-0 bg-[#FA6908] motion-reduce:hidden" aria-hidden="true"></div>

    {{-- ================= NAV ================= --}}
    <header id="nav" class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-[#021F4A]/70 backdrop-blur-md transition duration-300 data-[scrolled]:bg-[#021F4A]/95 data-[scrolled]:shadow-lg data-[scrolled]:shadow-black/30">
        <div class="relative mx-auto flex h-16 max-w-6xl items-center justify-between px-5 sm:px-8">
            <a href="/" class="flex items-center gap-2.5 rounded-md text-lg font-extrabold text-white [font-stretch:85%] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#FA6908]/60" aria-label="ShaloTrack home">
                <span class="grid size-8 place-items-center rounded-lg bg-[#FA6908]">
                    <svg class="size-4 fill-[#010F25]" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z" />
                    </svg>
                </span>
                <span>Shalo<span class="text-[#FA6908]">Track</span></span>
            </a>

            {{-- Mobile menu: a checkbox + Tailwind's peer variants, no JavaScript needed --}}
            <input id="menu-toggle" type="checkbox" class="peer sr-only md:hidden" aria-label="Open menu" />
            <label for="menu-toggle"
                class="flex size-10 cursor-pointer flex-col items-center justify-center gap-1.5 rounded-lg hover:bg-white/10 peer-focus-visible:ring-4 peer-focus-visible:ring-[#FA6908]/60 md:hidden
                       peer-checked:[&>span:first-child]:translate-y-2 peer-checked:[&>span:first-child]:rotate-45
                       peer-checked:[&>span:nth-child(2)]:opacity-0
                       peer-checked:[&>span:last-child]:-translate-y-2 peer-checked:[&>span:last-child]:-rotate-45">
                <span class="block h-0.5 w-5 rounded bg-white transition duration-200"></span>
                <span class="block h-0.5 w-5 rounded bg-white transition duration-200"></span>
                <span class="block h-0.5 w-5 rounded bg-white transition duration-200"></span>
            </label>

            <nav aria-label="Main"
                class="absolute inset-x-0 top-16 hidden flex-col gap-1 border-b border-white/10 bg-[#021F4A] p-5 shadow-xl peer-checked:flex
                       md:static md:flex md:flex-row md:items-center md:gap-7 md:border-0 md:bg-transparent md:p-0 md:shadow-none">
                <a href="#features" class="menu-link rounded-md py-2 text-sm font-medium text-[#B7C6E2] hover:text-white md:py-0">Features</a>
                <a href="#how-it-works" class="menu-link rounded-md py-2 text-sm font-medium text-[#B7C6E2] hover:text-white md:py-0">How it works</a>
                <a href="#your-data" class="menu-link rounded-md py-2 text-sm font-medium text-[#B7C6E2] hover:text-white md:py-0">Your data</a>
                <a href="#why-us" class="menu-link rounded-md py-2 text-sm font-medium text-[#B7C6E2] hover:text-white md:py-0">Why us</a>
                <a href="#story" class="menu-link rounded-md py-2 text-sm font-medium text-[#B7C6E2] hover:text-white md:py-0">Our story</a>
                <a href="#team" class="menu-link rounded-md py-2 text-sm font-medium text-[#B7C6E2] hover:text-white md:py-0">Team</a>
                <a href="#faq" class="menu-link rounded-md py-2 text-sm font-medium text-[#B7C6E2] hover:text-white md:py-0">Questions</a>
                <a href="/login" class="rounded-md py-2 text-sm font-medium text-[#B7C6E2] hover:text-white md:py-0">Sign in</a>
                <a href="/login" class="mt-2 inline-flex items-center justify-center rounded-lg bg-[#FA6908] px-4 py-2 text-sm font-bold text-[#010F25] transition hover:-translate-y-0.5 hover:bg-[#FF7A1F] active:translate-y-0 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#FA6908]/60 md:mt-0">Get started</a>
            </nav>
        </div>
    </header>

    <main>
        {{-- ================= HERO ================= --}}
        <section class="relative isolate overflow-hidden bg-[#021F4A] pb-14 pt-28 text-white lg:flex lg:min-h-screen lg:items-center lg:pb-4 lg:pt-16" aria-labelledby="hero-title">
            <div class="pointer-events-none absolute -right-32 top-1/4 -z-10 size-[44rem] rounded-full bg-[#1C57B0]/25 blur-3xl motion-safe:animate-[glow_6s_ease-in-out_infinite]" aria-hidden="true"></div>
            <div class="mx-auto w-full max-w-6xl px-5 sm:px-8">
                <div class="grid items-center gap-10 lg:grid-cols-[minmax(0,32rem)_minmax(0,1fr)] lg:gap-6">

                    <div>
                        <p class="mb-4 text-sm font-bold tracking-[0.2em] text-[#FA6908] motion-safe:animate-[rise_.7s_ease-out_both]">ALWAYS CONNECTED</p>
                        <h1 id="hero-title" class="display text-[clamp(3.4rem,9.5vw,5.25rem)]">
                            <span class="block motion-safe:animate-[rise_.9s_cubic-bezier(.2,.7,.2,1)_.1s_both]">Every vehicle,</span>
                            <span class="block motion-safe:animate-[rise_.9s_cubic-bezier(.2,.7,.2,1)_.25s_both]">always within</span>
                            <span class="block motion-safe:animate-[rise_.9s_cubic-bezier(.2,.7,.2,1)_.4s_both]">sight.</span>
                        </h1>
                        <p class="mt-7 max-w-md text-lg leading-relaxed text-[#B7C6E2] motion-safe:animate-[rise_.9s_cubic-bezier(.2,.7,.2,1)_.6s_both]">
                            ShaloTrack shows where each of your vehicles is, how fast it is going and whether the engine is on &mdash; position and alerts reported every 20 seconds. Open the app or the web dashboard and see it.
                        </p>
                        <div class="mt-8 flex flex-wrap items-center gap-x-7 gap-y-4 motion-safe:animate-[rise_.9s_cubic-bezier(.2,.7,.2,1)_.75s_both]">
                            <a href="/login" class="inline-flex items-center justify-center rounded-xl bg-[#FA6908] px-6 py-3.5 text-[0.95rem] font-bold text-[#010F25] shadow-lg shadow-black/25 transition hover:-translate-y-0.5 hover:bg-[#FF7A1F] active:translate-y-0 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#FA6908]/60">Sign in with your phone</a>
                            <a href="#features" class="font-medium text-[#B7C6E2] underline decoration-[#B7C6E2]/40 underline-offset-[.3em] transition hover:text-white hover:decoration-white focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#FA6908]/60">See what you get</a>
                        </div>
                    </div>

                    {{-- The map: real coastline (Natural Earth), main highways, sample vehicles --}}
                    <div class="relative mx-auto aspect-[646/844] w-full max-w-md lg:h-[min(calc(100svh-6.5rem),56rem)] lg:w-auto lg:max-w-none lg:justify-self-center">
                        <div class="map-drift size-full">
                            <svg id="map" viewBox="0 0 646 844" class="size-full overflow-visible" role="img" aria-label="Map of Sri Lanka with sample vehicles driving the main roads between Colombo, Kandy, Galle, Anuradhapura and Trincomalee">
                                <defs>
                                    <filter id="soft" x="-30%" y="-30%" width="160%" height="160%">
                                        <feGaussianBlur stdDeviation="24" />
                                    </filter>
                                    <path id="land" d="M565.7,578.0 L565.7,581.2 L568.4,591.9 L567.1,599.6 L558.2,630.0 L556.8,640.7 L554.1,645.8 L552.7,651.7 L547.2,660.1 L548.6,664.6 L546.6,670.4 L528.8,698.5 L522.6,704.6 L511.7,712.4 L507.6,713.7 L502.8,716.2 L467.9,744.0 L461.1,747.5 L437.8,755.9 L437.8,753.0 L436.4,751.7 L434.4,752.4 L433.7,753.7 L433.7,756.9 L432.3,758.2 L428.9,759.5 L420.7,764.0 L400.9,768.2 L391.3,771.4 L380.3,773.4 L375.6,775.3 L372.8,779.2 L368.0,778.2 L362.6,780.1 L348.2,791.1 L344.8,795.0 L333.8,795.0 L328.4,797.6 L324.3,800.8 L321.5,801.4 L311.3,797.6 L300.3,798.8 L297.6,798.2 L295.5,796.6 L293.5,792.4 L290.1,794.3 L287.3,794.7 L279.8,793.4 L278.4,791.4 L277.1,791.1 L275.0,792.4 L273.6,792.4 L273.0,792.1 L272.3,790.1 L262.0,786.3 L255.9,785.3 L251.8,781.8 L249.0,780.5 L247.6,782.1 L227.1,761.7 L227.1,760.8 L226.4,760.1 L226.4,757.6 L223.7,754.6 L214.8,735.9 L214.8,730.1 L212.8,726.9 L211.4,717.9 L208.0,711.7 L205.9,702.7 L204.6,699.1 L202.5,697.8 L202.5,695.6 L204.6,695.6 L203.2,684.6 L182.7,632.6 L180.6,618.1 L179.9,616.8 L179.9,602.9 L180.6,602.5 L182.7,595.8 L182.7,593.5 L173.8,561.2 L175.1,556.3 L174.5,559.2 L176.5,562.5 L179.9,572.2 L181.3,572.2 L181.3,570.2 L182.7,569.6 L182.7,563.7 L181.3,558.9 L180.6,558.2 L177.9,557.9 L176.5,557.0 L175.8,555.3 L177.2,552.4 L177.2,551.1 L168.3,482.9 L166.9,480.3 L167.6,479.3 L169.7,480.3 L169.7,477.0 L171.7,469.3 L168.3,462.2 L168.3,453.4 L164.2,431.7 L161.5,424.6 L159.4,414.5 L156.0,406.8 L151.2,385.1 L151.2,378.9 L152.6,379.6 L152.6,380.2 L153.3,376.6 L152.6,365.3 L151.2,362.0 L154.6,360.1 L157.4,356.2 L161.5,345.8 L166.9,336.8 L164.2,343.6 L160.8,350.1 L161.5,352.0 L162.8,353.3 L164.9,354.3 L166.9,354.3 L163.5,363.3 L160.8,364.6 L160.8,372.4 L157.4,382.8 L157.4,388.6 L162.1,393.5 L158.0,401.6 L164.2,405.8 L173.1,406.8 L177.2,403.5 L176.5,400.0 L175.1,397.4 L172.4,393.5 L170.4,392.2 L169.7,389.9 L175.8,377.9 L175.8,375.7 L173.1,368.9 L173.8,363.0 L173.1,361.4 L171.7,360.1 L173.1,356.2 L175.8,353.0 L176.5,346.8 L178.6,338.7 L179.9,319.9 L182.7,316.7 L184.0,312.8 L184.0,304.7 L184.7,300.8 L187.5,298.2 L190.9,296.2 L193.6,292.6 L197.7,280.6 L199.8,261.8 L195.0,250.1 L195.7,247.9 L195.7,234.2 L195.0,227.4 L193.6,225.8 L192.9,223.8 L199.1,221.9 L210.0,211.8 L218.2,206.9 L219.6,202.1 L221.7,197.5 L223.0,190.7 L229.2,174.8 L231.2,167.0 L231.2,153.0 L229.2,152.7 L223.7,149.7 L221.7,147.8 L218.9,139.0 L218.9,136.7 L221.7,134.1 L225.8,132.2 L236.0,129.3 L240.1,123.1 L244.2,121.1 L246.3,120.8 L244.9,117.5 L239.4,111.7 L236.0,109.1 L221.7,100.6 L218.9,97.0 L225.1,97.4 L231.2,99.7 L242.2,106.5 L253.1,109.1 L256.5,111.0 L262.0,116.9 L259.3,118.2 L260.6,120.8 L259.3,122.1 L263.4,124.7 L273.0,122.4 L290.1,115.6 L294.8,116.2 L305.1,118.8 L316.7,124.4 L325.6,126.0 L312.6,117.2 L308.5,114.9 L303.1,113.6 L297.6,110.1 L292.1,110.7 L290.7,112.3 L287.3,110.1 L280.5,110.4 L279.1,109.7 L265.4,98.0 L260.0,94.4 L247.6,89.2 L244.2,87.0 L242.2,87.0 L242.2,91.8 L245.6,94.8 L247.0,96.7 L245.6,99.0 L244.2,99.3 L241.5,97.7 L238.1,95.7 L231.2,89.6 L227.1,87.9 L234.7,95.7 L229.9,94.8 L216.2,87.9 L209.3,82.1 L204.6,80.5 L201.1,78.5 L201.1,79.8 L199.1,78.5 L197.0,72.0 L191.6,63.9 L195.7,61.9 L203.2,55.1 L206.6,53.4 L221.7,54.7 L229.2,53.8 L231.9,54.1 L233.3,56.7 L233.3,60.9 L234.0,62.2 L241.5,64.2 L243.5,66.5 L245.6,66.5 L247.6,65.5 L250.4,66.1 L273.6,90.9 L279.1,94.4 L293.5,101.0 L293.5,99.7 L286.6,96.1 L269.5,82.4 L255.9,66.5 L249.0,62.9 L238.1,60.3 L235.3,58.0 L234.7,55.1 L238.8,53.4 L244.2,52.5 L252.4,52.1 L255.9,52.8 L258.6,54.7 L260.0,60.9 L286.6,93.1 L298.3,100.0 L350.9,142.9 L354.4,148.1 L358.5,151.7 L360.5,155.3 L358.5,156.6 L357.1,155.9 L355.0,153.3 L353.7,152.7 L350.2,152.0 L348.9,152.3 L349.6,154.0 L359.8,163.7 L363.9,166.0 L361.9,163.4 L361.2,161.1 L361.9,159.8 L364.6,159.2 L365.3,159.8 L367.3,166.0 L376.9,183.9 L374.9,183.5 L371.5,181.9 L368.7,181.9 L367.3,184.5 L368.7,185.5 L372.1,185.8 L374.9,187.8 L375.6,192.0 L376.9,192.0 L376.9,191.0 L379.7,189.4 L380.3,193.9 L383.1,200.1 L386.5,205.6 L391.3,208.6 L391.3,209.9 L388.6,211.8 L385.8,207.9 L383.8,206.6 L378.3,205.3 L375.6,204.0 L377.6,206.9 L384.4,213.4 L384.4,217.0 L386.5,218.6 L384.4,220.6 L383.1,222.9 L385.8,225.1 L389.2,224.8 L392.0,222.5 L394.7,215.7 L401.5,224.2 L407.7,234.5 L410.4,237.1 L413.9,238.1 L416.6,240.4 L420.7,250.5 L422.1,251.4 L422.8,252.7 L427.5,254.7 L429.6,259.9 L432.3,269.9 L433.7,267.3 L436.4,268.6 L435.7,270.3 L443.3,279.0 L443.3,282.6 L437.8,286.8 L441.9,285.5 L446.0,294.6 L446.7,299.1 L441.9,300.1 L442.6,296.9 L441.2,294.6 L439.2,293.9 L437.8,297.5 L438.5,300.1 L439.9,301.7 L440.5,303.7 L439.2,306.6 L432.3,302.1 L428.9,301.4 L426.2,302.7 L423.4,305.9 L424.1,307.9 L427.5,307.2 L431.6,304.0 L439.2,313.4 L444.0,314.7 L452.2,314.4 L455.6,312.4 L453.5,307.9 L460.4,305.0 L462.4,305.3 L467.2,308.9 L468.6,311.5 L469.9,319.2 L472.7,329.0 L472.7,334.2 L470.6,332.5 L467.9,327.4 L465.8,326.4 L466.5,334.5 L467.9,336.8 L469.9,338.1 L472.7,338.1 L474.7,339.0 L478.2,355.2 L482.9,370.8 L484.3,379.6 L480.9,384.4 L478.8,375.3 L475.4,367.2 L474.1,367.2 L474.1,377.9 L476.8,379.2 L478.2,383.8 L481.6,386.0 L485.0,386.0 L486.4,381.8 L487.0,381.8 L489.1,384.7 L490.5,391.5 L495.3,398.7 L498.0,404.2 L500.7,401.6 L501.4,403.2 L500.7,404.2 L501.4,406.8 L505.5,404.2 L506.2,405.2 L505.5,412.0 L506.9,414.5 L508.9,416.5 L511.0,417.5 L512.4,420.1 L511.0,420.1 L509.6,421.0 L508.3,425.2 L510.3,431.7 L514.4,436.2 L519.9,435.6 L522.6,442.1 L531.5,451.5 L534.2,455.7 L534.2,459.2 L530.1,460.5 L528.1,459.2 L524.0,453.4 L521.2,451.1 L517.8,450.2 L515.8,450.2 L513.7,452.4 L512.4,456.6 L516.5,457.9 L518.5,457.3 L519.9,455.4 L524.0,457.0 L525.4,458.6 L525.4,460.5 L532.2,467.7 L534.2,471.2 L532.9,472.2 L534.2,476.4 L535.6,475.1 L534.9,472.5 L537.0,473.2 L542.5,478.0 L545.9,486.8 L544.5,492.3 L545.9,494.5 L545.9,495.5 L543.1,499.0 L544.5,502.0 L547.2,505.2 L545.2,506.8 L545.2,508.1 L547.2,508.4 L548.6,506.5 L550.0,506.5 L551.3,514.3 L551.3,517.2 L552.7,517.2 L552.7,514.3 L554.8,507.8 L554.8,504.5 L556.1,504.9 L557.5,506.5 L558.9,512.0 L561.6,516.8 L566.4,533.0 Z M539.0,469.3 L539.0,467.0 L537.0,461.8 L537.7,457.9 L537.0,454.1 L539.0,456.3 L543.8,465.4 L545.2,470.2 L550.0,477.7 L552.0,480.9 L555.4,497.8 L555.4,501.6 L552.7,503.9 L550.0,502.0 L548.6,500.7 L549.3,486.1 L548.6,481.9 L546.6,480.3 Z M154.6,197.5 L152.6,196.5 L151.9,193.0 L154.6,192.0 L163.5,192.0 L171.0,193.9 L179.2,197.2 L186.8,201.7 L192.9,207.9 L190.2,207.3 L184.7,204.7 L181.3,204.0 L190.2,214.1 L191.6,217.0 L187.5,216.4 L184.0,214.1 L180.6,210.5 L178.6,209.5 L171.0,202.4 L163.5,199.1 L158.7,197.8 Z M158.0,115.6 L154.6,118.5 L149.8,118.8 L145.7,117.2 L143.7,114.9 L143.7,107.1 L144.4,105.2 L145.7,105.2 L147.8,109.1 L156.0,111.0 Z M189.5,93.1 L186.1,90.9 L183.3,86.6 L178.6,77.2 L180.6,73.9 L181.3,69.7 L182.7,66.5 L186.8,65.5 L188.8,66.1 L188.1,70.0 L188.1,77.8 L188.8,79.5 L190.2,80.5 L191.6,81.4 L195.0,81.1 L195.7,81.8 L195.7,84.7 L196.3,86.0 L203.9,89.9 L204.6,91.8 L203.2,93.1 L201.1,93.1 L195.0,90.9 L191.6,92.8 Z" />
                                    <clipPath id="landclip">
                                        <use href="#land" />
                                    </clipPath>
                                    <radialGradient id="fadeGrad" cx=".55" cy=".5" r=".72">
                                        <stop offset=".55" stop-color="#fff" />
                                        <stop offset="1" stop-color="#000" />
                                    </radialGradient>
                                    <pattern id="dots" width="8" height="8" patternUnits="userSpaceOnUse">
                                        <circle cx="1.4" cy="1.4" r=".9" class="fill-white/10" />
                                    </pattern>
                                </defs>
                                <mask id="fade">
                                    <rect x="-60" y="-60" width="766" height="964" fill="url(#fadeGrad)" />
                                </mask>
                                <g mask="url(#fade)">
                                    <line x1="19.0" y1="0" x2="19.0" y2="844" class="stroke-white/[.06]" stroke-width="1" /><text x="23.0" y="838" class="fill-white/30 text-[10px]">79°E</text>
                                    <line x1="209.0" y1="0" x2="209.0" y2="844" class="stroke-white/[.06]" stroke-width="1" /><text x="213.0" y="838" class="fill-white/30 text-[10px]">80°E</text>
                                    <line x1="399.0" y1="0" x2="399.0" y2="844" class="stroke-white/[.06]" stroke-width="1" /><text x="403.0" y="838" class="fill-white/30 text-[10px]">81°E</text>
                                    <line x1="589.0" y1="0" x2="589.0" y2="844" class="stroke-white/[.06]" stroke-width="1" /><text x="593.0" y="838" class="fill-white/30 text-[10px]">82°E</text>
                                    <line x1="0" y1="786.9" x2="646" y2="786.9" class="stroke-white/[.06]" stroke-width="1" /><text x="4" y="782.9" class="fill-white/30 text-[10px]">6°N</text>
                                    <line x1="0" y1="595.7" x2="646" y2="595.7" class="stroke-white/[.06]" stroke-width="1" /><text x="4" y="591.7" class="fill-white/30 text-[10px]">7°N</text>
                                    <line x1="0" y1="404.1" x2="646" y2="404.1" class="stroke-white/[.06]" stroke-width="1" /><text x="4" y="400.1" class="fill-white/30 text-[10px]">8°N</text>
                                    <line x1="0" y1="211.9" x2="646" y2="211.9" class="stroke-white/[.06]" stroke-width="1" /><text x="4" y="207.9" class="fill-white/30 text-[10px]">9°N</text>
                                    <line x1="0" y1="19.3" x2="646" y2="19.3" class="stroke-white/[.06]" stroke-width="1" /><text x="4" y="15.3" class="fill-white/30 text-[10px]">10°N</text>
                                    <path d="M-57.0,-57.9 L183.3,-57.9 L182.0,-37.5 L180.6,-35.2 L170.4,-32.6 L166.9,-33.9 L166.9,-37.8 L162.8,-40.4 L139.6,-44.0 L136.8,-43.3 L135.5,-40.4 L145.0,-40.1 L152.6,-36.5 L158.0,-36.5 L163.5,-35.2 L166.2,-33.6 L165.6,-31.3 L163.5,-30.6 L124.5,-38.5 L123.8,-39.1 L124.5,-41.7 L127.9,-42.0 L134.1,-44.3 L134.1,-45.6 L130.7,-46.9 L126.6,-45.6 L119.7,-46.0 L120.4,-43.0 L106.1,-40.1 L102.0,-40.4 L94.4,-43.3 L76.6,-32.6 L70.5,-26.1 L70.5,-18.9 L66.4,-17.3 L64.3,-15.3 L63.6,-12.7 L67.1,8.8 L71.9,12.4 L65.0,17.3 L56.8,25.1 L45.9,40.4 L41.8,48.2 L38.3,52.1 L37.0,57.3 L26.7,68.1 L15.1,83.4 L14.4,89.2 L8.9,95.7 L7.6,99.7 L2.8,120.8 L2.8,124.7 L6.2,131.9 L26.7,151.4 L32.9,154.3 L41.1,155.9 L67.1,156.6 L80.1,149.7 L83.5,151.0 L84.9,153.0 L82.8,155.9 L82.1,158.5 L86.2,165.7 L105.4,181.3 L102.6,182.9 L87.6,170.9 L74.6,163.7 L63.0,162.4 L60.2,160.5 L57.5,160.8 L50.7,159.2 L43.8,162.7 L37.7,162.4 L35.6,163.1 L24.0,159.2 L17.1,158.5 L11.0,159.2 L5.5,163.1 L-0.6,163.1 L-10.9,165.0 L-16.4,167.9 L-23.2,169.6 L-26.6,171.5 L-30.1,174.8 L-32.8,175.7 L-42.4,176.4 L-45.1,177.7 L-47.8,182.9 L-50.6,184.2 L-57.0,185.3 Z" class="fill-[#0A2A5C] stroke-white/30" stroke-width="1" stroke-linejoin="round" />
                                    <text x="48" y="39" class="fill-white/35 text-[12px] font-medium">India</text>
                                    <text x="114" y="73" text-anchor="start" class="fill-white/30 text-[11px] italic tracking-[.22em]">Palk Strait</text>
                                    <text x="38" y="318" text-anchor="start" class="fill-white/30 text-[11px] italic tracking-[.22em]">Gulf of Mannar</text>
                                    <text x="351" y="825" text-anchor="start" class="fill-white/30 text-[11px] italic tracking-[.22em]">Indian Ocean</text>
                                    <text x="627" y="327" text-anchor="end" class="fill-white/30 text-[11px] italic tracking-[.22em]">Bay of Bengal</text>
                                    <text x="23" y="500" text-anchor="start" class="fill-white/30 text-[11px] italic tracking-[.22em]">Arabian Sea</text>
                                </g>
                                <use href="#land" fill="none" class="stroke-white" stroke-opacity=".03" stroke-width="60" stroke-linejoin="round" />
                                <use href="#land" fill="none" class="stroke-white" stroke-opacity=".045" stroke-width="36" stroke-linejoin="round" />
                                <use href="#land" fill="none" class="stroke-white" stroke-opacity=".07" stroke-width="18" stroke-linejoin="round" />
                                <use href="#land" class="fill-[#14468F]" />
                                <g clip-path="url(#landclip)">
                                    <g filter="url(#soft)">
                                        <ellipse cx="351" cy="596" rx="49" ry="57" class="fill-[#4F8FE0]" fill-opacity=".42" />
                                        <ellipse cx="323" cy="567" rx="38" ry="42" class="fill-[#4F8FE0]" fill-opacity=".3" />
                                        <ellipse cx="361" cy="519" rx="28" ry="34" class="fill-[#4F8FE0]" fill-opacity=".28" />
                                        <ellipse cx="294" cy="701" rx="27" ry="30" class="fill-[#4F8FE0]" fill-opacity=".22" />
                                        <ellipse cx="380" cy="634" rx="38" ry="34" class="fill-[#4F8FE0]" fill-opacity=".25" />
                                    </g>
                                    <use href="#land" fill="none" class="stroke-[#082450]" stroke-opacity=".55" stroke-width="22" stroke-linejoin="round" />
                                    <use href="#land" fill="url(#dots)" />
                                </g>
                                <use href="#land" fill="none" class="stroke-white/60" stroke-width="1.2" stroke-linejoin="round" />
                                <path d="M182.4,609.1 C185.0,607.1 192.7,601.7 199.5,595.7 C206.3,589.7 221.2,575.5 228.0,568.9 C234.8,562.3 238.0,554.8 245.1,551.7 C252.2,548.5 268.1,548.4 275.5,547.8 C282.9,547.3 286.5,549.0 294.5,547.8 C302.5,546.7 324.7,540.5 328.7,540.2 C332.7,539.9 322.8,542.2 321.1,545.9 C319.4,549.6 319.0,559.0 317.3,565.1 C315.6,571.1 308.8,578.4 309.7,586.1 C310.6,593.9 316.2,614.5 323.0,616.7 C329.8,619.0 350.5,603.7 355.3,601.4" fill="none" class="stroke-white/40" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M182.4,609.1 C183.8,615.4 189.0,641.1 191.9,651.2 C194.7,661.2 198.8,667.7 201.4,676.1 C204.0,684.4 206.4,696.9 209.0,706.7 C211.6,716.4 215.6,733.0 218.5,741.1 C221.3,749.1 223.2,754.2 228.0,760.2 C232.8,766.2 241.4,776.3 250.8,781.2 C260.2,786.1 281.3,790.4 290.7,792.7 C300.1,795.0 303.0,797.9 313.5,796.5 C324.0,795.0 344.8,788.0 361.0,783.1 C377.2,778.2 412.7,766.9 421.8,764.0" fill="none" class="stroke-white/40" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M182.4,609.1 C181.8,601.1 180.3,574.2 178.6,555.5 C176.9,536.8 171.3,508.2 171.0,484.6 C170.7,461.0 175.8,411.2 176.7,398.3" fill="none" class="stroke-white/20" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M328.7,540.2 C328.4,535.0 326.2,522.1 326.8,505.7 C327.4,489.3 333.1,447.3 332.5,430.9 C331.9,414.5 330.1,409.3 323.0,396.4 C315.9,383.4 287.8,365.0 285.0,344.6 C282.2,324.1 304.0,291.5 304.0,260.0 C304.0,228.6 287.8,158.0 285.0,135.0 C282.2,111.9 296.1,113.6 285.0,106.1 C273.9,98.5 222.0,88.0 210.9,84.9" fill="none" class="stroke-white/40" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M332.5,430.9 C335.3,425.7 335.0,416.8 351.5,396.4 C368.0,375.9 429.0,309.9 442.7,294.6" fill="none" class="stroke-white/20" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M182.4,609.1 C192.4,608.5 233.5,598.1 248.9,605.3 C264.3,612.4 275.3,647.5 285.0,656.9 C294.7,666.4 293.5,669.8 313.5,668.4 C333.4,667.0 402.3,650.5 418.0,647.4" fill="none" class="stroke-white/20" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M321.1,545.9 C326.2,554.2 341.9,593.7 355.3,601.4 C368.7,609.2 402.1,598.2 410.4,597.6" fill="none" class="stroke-white/20" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M279.3,501.8 C280.2,478.3 284.1,368.1 285.0,344.6" fill="none" class="stroke-white/20" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M182.4,609.1 C185.0,607.1 192.7,601.7 199.5,595.7 C206.3,589.7 221.2,575.5 228.0,568.9 C234.8,562.3 238.0,554.8 245.1,551.7 C252.2,548.5 268.1,548.4 275.5,547.8 C282.9,547.3 286.5,549.0 294.5,547.8 C302.5,546.7 323.6,541.3 328.7,540.2" pathLength="1" fill="none" class="route-line stroke-[#FA6908]/80 [stroke-dasharray:1] [stroke-dashoffset:1] motion-safe:animate-[draw_2.6s_cubic-bezier(.5,0,.2,1)_0.3s_both] motion-reduce:[stroke-dashoffset:0]" stroke-width="2" stroke-linecap="round" />
                                <path id="trip-suv" d="M182.4,609.1 C185.0,607.1 192.7,601.7 199.5,595.7 C206.3,589.7 221.2,575.5 228.0,568.9 C234.8,562.3 238.0,554.8 245.1,551.7 C252.2,548.5 268.1,548.4 275.5,547.8 C282.9,547.3 286.5,549.0 294.5,547.8 C302.5,546.7 323.6,541.3 328.7,540.2" fill="none" />
                                <path d="M250.8,781.2 C247.4,778.0 232.8,766.2 228.0,760.2 C223.2,754.2 221.3,749.1 218.5,741.1 C215.6,733.0 211.6,716.4 209.0,706.7 C206.4,696.9 204.0,684.4 201.4,676.1 C198.8,667.7 194.7,661.2 191.9,651.2 C189.0,641.1 183.8,615.4 182.4,609.1" pathLength="1" fill="none" class="route-line stroke-[#FA6908]/80 [stroke-dasharray:1] [stroke-dashoffset:1] motion-safe:animate-[draw_2.6s_cubic-bezier(.5,0,.2,1)_0.5s_both] motion-reduce:[stroke-dashoffset:0]" stroke-width="2" stroke-linecap="round" />
                                <path id="trip-car" d="M250.8,781.2 C247.4,778.0 232.8,766.2 228.0,760.2 C223.2,754.2 221.3,749.1 218.5,741.1 C215.6,733.0 211.6,716.4 209.0,706.7 C206.4,696.9 204.0,684.4 201.4,676.1 C198.8,667.7 194.7,661.2 191.9,651.2 C189.0,641.1 183.8,615.4 182.4,609.1" fill="none" />
                                <path d="M178.6,555.5 C179.2,563.5 181.8,601.1 182.4,609.1" pathLength="1" fill="none" class="route-line stroke-[#FA6908]/80 [stroke-dasharray:1] [stroke-dashoffset:1] motion-safe:animate-[draw_2.6s_cubic-bezier(.5,0,.2,1)_0.7s_both] motion-reduce:[stroke-dashoffset:0]" stroke-width="2" stroke-linecap="round" />
                                <path id="trip-tuk" d="M178.6,555.5 C179.2,563.5 181.8,601.1 182.4,609.1" fill="none" />
                                <path d="M328.7,540.2 C328.4,535.0 326.2,522.1 326.8,505.7 C327.4,489.3 333.1,447.3 332.5,430.9 C331.9,414.5 330.1,409.3 323.0,396.4 C315.9,383.4 290.7,352.3 285.0,344.6" pathLength="1" fill="none" class="route-line stroke-[#FA6908]/80 [stroke-dasharray:1] [stroke-dashoffset:1] motion-safe:animate-[draw_2.6s_cubic-bezier(.5,0,.2,1)_0.9s_both] motion-reduce:[stroke-dashoffset:0]" stroke-width="2" stroke-linecap="round" />
                                <path id="trip-truck" d="M328.7,540.2 C328.4,535.0 326.2,522.1 326.8,505.7 C327.4,489.3 333.1,447.3 332.5,430.9 C331.9,414.5 330.1,409.3 323.0,396.4 C315.9,383.4 290.7,352.3 285.0,344.6" fill="none" />
                                <path d="M442.7,294.6 C429.0,309.9 368.0,375.9 351.5,396.4 C335.0,416.8 335.3,425.7 332.5,430.9" pathLength="1" fill="none" class="route-line stroke-[#FA6908]/80 [stroke-dasharray:1] [stroke-dashoffset:1] motion-safe:animate-[draw_2.6s_cubic-bezier(.5,0,.2,1)_1.1s_both] motion-reduce:[stroke-dashoffset:0]" stroke-width="2" stroke-linecap="round" />
                                <path id="trip-van" d="M442.7,294.6 C429.0,309.9 368.0,375.9 351.5,396.4 C335.0,416.8 335.3,425.7 332.5,430.9" fill="none" />
                                <path d="M275.5,547.8 C270.9,548.4 252.2,548.5 245.1,551.7 C238.0,554.8 234.8,562.3 228.0,568.9 C221.2,575.5 206.3,589.7 199.5,595.7 C192.7,601.7 185.0,607.1 182.4,609.1" pathLength="1" fill="none" class="route-line stroke-[#FA6908]/80 [stroke-dasharray:1] [stroke-dashoffset:1] motion-safe:animate-[draw_2.6s_cubic-bezier(.5,0,.2,1)_1.3s_both] motion-reduce:[stroke-dashoffset:0]" stroke-width="2" stroke-linecap="round" />
                                <path id="trip-lorry2" d="M275.5,547.8 C270.9,548.4 252.2,548.5 245.1,551.7 C238.0,554.8 234.8,562.3 228.0,568.9 C221.2,575.5 206.3,589.7 199.5,595.7 C192.7,601.7 185.0,607.1 182.4,609.1" fill="none" />
                                <circle cx="182.4" cy="609.1" r="3.4" class="fill-white stroke-[#021F4A]" stroke-width="1.5" /><text x="173.4" y="613.1" text-anchor="end" class="fill-white/90 text-[13px] font-semibold" paint-order="stroke" stroke="#14468F" stroke-width="3" stroke-opacity=".6">Colombo</text>
                                <circle cx="328.7" cy="540.2" r="3.4" class="fill-white stroke-[#021F4A]" stroke-width="1.5" /><text x="337.7" y="544.2" text-anchor="start" class="fill-white/90 text-[13px] font-semibold" paint-order="stroke" stroke="#14468F" stroke-width="3" stroke-opacity=".6">Kandy</text>
                                <circle cx="210.9" cy="84.9" r="3.4" class="fill-white stroke-[#021F4A]" stroke-width="1.5" /><text x="219.9" y="88.9" text-anchor="start" class="fill-white/90 text-[13px] font-semibold" paint-order="stroke" stroke="#14468F" stroke-width="3" stroke-opacity=".6">Jaffna</text>
                                <circle cx="250.8" cy="781.2" r="3.4" class="fill-white stroke-[#021F4A]" stroke-width="1.5" /><text x="240.8" y="785.2" text-anchor="end" class="fill-white/90 text-[13px] font-semibold" paint-order="stroke" stroke="#14468F" stroke-width="3" stroke-opacity=".6">Galle</text>
                                <circle cx="442.7" cy="294.6" r="3.4" class="fill-white stroke-[#021F4A]" stroke-width="1.5" /><text x="451.7" y="298.6" text-anchor="start" class="fill-white/90 text-[13px] font-semibold" paint-order="stroke" stroke="#14468F" stroke-width="3" stroke-opacity=".6">Trincomalee</text>
                                <circle cx="285.0" cy="344.6" r="3.4" class="fill-white stroke-[#021F4A]" stroke-width="1.5" /><text x="294.0" y="348.6" text-anchor="start" class="fill-white/90 text-[13px] font-semibold" paint-order="stroke" stroke="#14468F" stroke-width="3" stroke-opacity=".6">Anuradhapura</text>
                                <circle cx="532.0" cy="457.8" r="3.4" class="fill-white stroke-[#021F4A]" stroke-width="1.5" /><text x="541.0" y="461.8" text-anchor="start" class="fill-white/90 text-[13px] font-semibold" paint-order="stroke" stroke="#14468F" stroke-width="3" stroke-opacity=".6">Batticaloa</text>
                                <circle cx="178.6" cy="555.5" r="2" class="fill-white/70" /><text x="170.6" y="559.5" text-anchor="end" class="hidden fill-white/55 text-[10.5px] md:block">Negombo</text>
                                <circle cx="355.3" cy="601.4" r="2" class="fill-white/70" /><text x="364.3" y="605.4" text-anchor="start" class="hidden fill-white/55 text-[10.5px] md:block">Nuwara Eliya</text>
                                <circle cx="285.0" cy="656.9" r="2" class="fill-white/70" /><text x="293.0" y="660.9" text-anchor="start" class="hidden fill-white/55 text-[10.5px] md:block">Ratnapura</text>
                                <circle cx="313.5" cy="796.5" r="2" class="fill-white/70" /><text x="322.5" y="789.5" text-anchor="start" class="hidden fill-white/55 text-[10.5px] md:block">Matara</text>
                                <circle cx="421.8" cy="764.0" r="2" class="fill-white/70" /><text x="430.8" y="768.0" text-anchor="start" class="hidden fill-white/55 text-[10.5px] md:block">Hambantota</text>
                                <circle cx="332.5" cy="430.9" r="2" class="fill-white/70" /><text x="341.5" y="434.9" text-anchor="start" class="hidden fill-white/55 text-[10.5px] md:block">Dambulla</text>
                                <circle cx="279.3" cy="501.8" r="2" class="fill-white/70" /><text x="271.3" y="505.8" text-anchor="end" class="hidden fill-white/55 text-[10.5px] md:block">Kurunegala</text>
                                <circle cx="304.0" cy="260.0" r="2" class="fill-white/70" /><text x="313.0" y="264.0" text-anchor="start" class="hidden fill-white/55 text-[10.5px] md:block">Vavuniya</text>
                                <circle cx="193.8" cy="215.8" r="2" class="fill-white/70" /><text x="185.8" y="219.8" text-anchor="end" class="hidden fill-white/55 text-[10.5px] md:block">Mannar</text>
                                <circle cx="410.4" cy="597.6" r="2" class="fill-white/70" /><text x="418.4" y="613.6" text-anchor="start" class="hidden fill-white/55 text-[10.5px] md:block">Badulla</text>
                                <g transform="translate(498.0 471.8) rotate(-25)">
                                    <image href="/vehicle-icons/motorcycle-blue.png" width="20" height="50" x="-10.0" y="-25.0" />
                                </g>
                                <g transform="translate(278.0 270.0) rotate(18)">
                                    <image href="/vehicle-icons/truck-blue.png" width="22" height="64" x="-11.0" y="-32.0" />
                                </g>
                                <g clip-path="url(#landclip)">
                                    <g>
                                        <animateTransform attributeName="transform" type="rotate" from="0 329 540" to="360 329 540" dur="7s" repeatCount="indefinite" />
                                        <path d="M328.7,540.2 L916.4,128.7 A717.4,717.4 0 0,1 962.1,203.4 Z" fill="#FA6908" fill-opacity="0.03" />
                                        <path d="M328.7,540.2 L962.1,203.4 A717.4,717.4 0 0,1 998.5,283.1 Z" fill="#FA6908" fill-opacity="0.045" />
                                        <path d="M328.7,540.2 L998.5,283.1 A717.4,717.4 0 0,1 1024.8,366.6 Z" fill="#FA6908" fill-opacity="0.07" />
                                        <path d="M328.7,540.2 L1024.8,366.6 A717.4,717.4 0 0,1 1040.8,452.8 Z" fill="#FA6908" fill-opacity="0.1" />
                                        <path d="M328.7,540.2 L1040.8,452.8 A717.4,717.4 0 0,1 1046.1,540.2 Z" fill="#FA6908" fill-opacity="0.16" />
                                        <path d="M328.7,540.2 L1046.1,540.2 A717.4,717.4 0 0,1 1040.8,627.6 Z" fill="#FA6908" fill-opacity="0.24" />
                                    </g>
                                    <circle cx="328.7" cy="540.2" r="2.5" class="fill-[#FA6908]" />
                                    <circle cx="328.7" cy="540.2" r="4" fill="none" class="stroke-[#FA6908]/60" stroke-width="1.2">
                                        <animate attributeName="r" values="4;22" dur="2.4s" repeatCount="indefinite" />
                                        <animate attributeName="opacity" values=".6;0" dur="2.4s" repeatCount="indefinite" />
                                    </circle>
                                </g>
                                <g opacity="0">
                                    <animateMotion dur="17s" begin="3.0s" repeatCount="indefinite" rotate="auto">
                                        <mpath href="#trip-suv" />
                                    </animateMotion>
                                    <animate attributeName="opacity" values="0;1;1;0" keyTimes="0;.06;.94;1" dur="17s" begin="3.0s" repeatCount="indefinite" />
                                    <circle r="2" class="fill-none stroke-[#22C55E]">
                                        <animate attributeName="r" values="2;15" dur="1.7s" repeatCount="indefinite" />
                                        <animate attributeName="stroke-width" values="2;0" dur="1.7s" repeatCount="indefinite" />
                                        <animate attributeName="opacity" values=".8;0" dur="1.7s" repeatCount="indefinite" />
                                    </circle>
                                    <image href="/vehicle-icons/suv-green.png" width="26" height="54" x="-13.0" y="-27.0" transform="rotate(90)" />
                                </g>
                                <g opacity="0">
                                    <animateMotion dur="15s" begin="5.0s" repeatCount="indefinite" rotate="auto">
                                        <mpath href="#trip-car" />
                                    </animateMotion>
                                    <animate attributeName="opacity" values="0;1;1;0" keyTimes="0;.06;.94;1" dur="15s" begin="5.0s" repeatCount="indefinite" />
                                    <circle r="2" class="fill-none stroke-[#22C55E]">
                                        <animate attributeName="r" values="2;15" dur="1.7s" repeatCount="indefinite" />
                                        <animate attributeName="stroke-width" values="2;0" dur="1.7s" repeatCount="indefinite" />
                                        <animate attributeName="opacity" values=".8;0" dur="1.7s" repeatCount="indefinite" />
                                    </circle>
                                    <image href="/vehicle-icons/car-green.png" width="27" height="54" x="-13.5" y="-27.0" transform="rotate(90)" />
                                </g>
                                <g opacity="0">
                                    <animateMotion dur="6s" begin="3.5s" repeatCount="indefinite" rotate="auto">
                                        <mpath href="#trip-tuk" />
                                    </animateMotion>
                                    <animate attributeName="opacity" values="0;1;1;0" keyTimes="0;.06;.94;1" dur="6s" begin="3.5s" repeatCount="indefinite" />
                                    <circle r="2" class="fill-none stroke-[#22C55E]">
                                        <animate attributeName="r" values="2;15" dur="1.7s" repeatCount="indefinite" />
                                        <animate attributeName="stroke-width" values="2;0" dur="1.7s" repeatCount="indefinite" />
                                        <animate attributeName="opacity" values=".8;0" dur="1.7s" repeatCount="indefinite" />
                                    </circle>
                                    <image href="/vehicle-icons/three-wheeler-green.png" width="32" height="54" x="-16.0" y="-27.0" transform="rotate(90)" />
                                </g>
                                <g opacity="0">
                                    <animateMotion dur="24s" begin="2.6s" repeatCount="indefinite" rotate="auto">
                                        <mpath href="#trip-truck" />
                                    </animateMotion>
                                    <animate attributeName="opacity" values="0;1;1;0" keyTimes="0;.06;.94;1" dur="24s" begin="2.6s" repeatCount="indefinite" />
                                    <circle r="2" class="fill-none stroke-[#22C55E]">
                                        <animate attributeName="r" values="2;15" dur="1.7s" repeatCount="indefinite" />
                                        <animate attributeName="stroke-width" values="2;0" dur="1.7s" repeatCount="indefinite" />
                                        <animate attributeName="opacity" values=".8;0" dur="1.7s" repeatCount="indefinite" />
                                    </circle>
                                    <image href="/vehicle-icons/truck-green.png" width="20" height="60" x="-10.0" y="-30.0" transform="rotate(90)" />
                                </g>
                                <g opacity="0">
                                    <animateMotion dur="14s" begin="6.0s" repeatCount="indefinite" rotate="auto">
                                        <mpath href="#trip-van" />
                                    </animateMotion>
                                    <animate attributeName="opacity" values="0;1;1;0" keyTimes="0;.06;.94;1" dur="14s" begin="6.0s" repeatCount="indefinite" />
                                    <circle r="2" class="fill-none stroke-[#22C55E]">
                                        <animate attributeName="r" values="2;15" dur="1.7s" repeatCount="indefinite" />
                                        <animate attributeName="stroke-width" values="2;0" dur="1.7s" repeatCount="indefinite" />
                                        <animate attributeName="opacity" values=".8;0" dur="1.7s" repeatCount="indefinite" />
                                    </circle>
                                    <image href="/vehicle-icons/van-green.png" width="24" height="54" x="-12.0" y="-27.0" transform="rotate(90)" />
                                </g>
                            </svg>
                        </div>

                        {{-- HUD corner brackets: a targeting-reticle frame around the tracked area --}}
                        <div class="pointer-events-none absolute -inset-2 motion-safe:animate-[glow_4s_ease-in-out_infinite]" aria-hidden="true">
                            <span class="absolute left-0 top-0 size-6 rounded-tl-md border-l-2 border-t-2 border-[#FA6908]/70"></span>
                            <span class="absolute right-0 top-0 size-6 rounded-tr-md border-r-2 border-t-2 border-[#FA6908]/70"></span>
                            <span class="absolute bottom-0 left-0 size-6 rounded-bl-md border-b-2 border-l-2 border-[#FA6908]/70"></span>
                            <span class="absolute bottom-0 right-0 size-6 rounded-br-md border-b-2 border-r-2 border-[#FA6908]/70"></span>
                        </div>

                        <div class="absolute bottom-6 right-0 grid gap-1.5 text-[.8rem] text-[#B7C6E2]" aria-hidden="true">
                            <span class="flex items-center gap-2"><i class="block size-2.5 rounded-full bg-[#22C55E]"></i>Online</span>
                            <span class="flex items-center gap-2"><i class="block size-2.5 rounded-full bg-[#3B82F6]"></i>Offline</span>
                        </div>

                        <div class="mt-4 w-full rounded-2xl bg-white p-4 text-[#0A1B33] shadow-2xl shadow-black/40 ring-1 ring-black/5 sm:max-w-xs lg:absolute lg:right-0 lg:top-2 lg:mt-0 lg:w-56 lg:p-3.5" aria-label="Sample vehicle">
                            <div class="flex items-center justify-between gap-3">
                                <span class="whitespace-nowrap rounded border-2 border-[#111] bg-white px-2 text-[1.05rem] font-extrabold leading-snug tracking-wide text-[#111] [font-stretch:75%] [word-spacing:.2em]">WP CAB-5678</span>
                                <span class="text-xs text-[#55627A]">Sample</span>
                            </div>
                            <p class="mb-2 mt-1.5 text-sm text-[#55627A]">Honda Fit</p>
                            <div class="flex items-baseline justify-between gap-3">
                                <span class="text-4xl font-extrabold leading-none tabular-nums [font-stretch:70%]"><span id="speed">62</span><small class="ml-1 text-base font-semibold text-[#55627A] [font-stretch:100%]">km/h</small></span>
                                <span class="flex items-center gap-1.5 whitespace-nowrap text-sm font-semibold"><i class="block size-2.5 rounded-full bg-[#22C55E]"></i>Ignition on</span>
                            </div>
                            <div class="mt-3">
                                <div class="h-1 overflow-hidden rounded-full bg-[#D9DFEA]"><b id="tick-bar" class="block h-full w-0 bg-[#FA6908]"></b></div>
                                <p class="mt-1.5 text-xs tabular-nums text-[#55627A]">Updated <span id="tick-s">0</span> s ago, every 20 s</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ================= VEHICLE MARQUEE ================= --}}
        <section class="overflow-hidden bg-[#010F25] py-7 text-white" aria-label="Vehicle types you can track">
            <p class="mx-auto mb-5 max-w-6xl px-5 text-sm text-[#B7C6E2] sm:px-8">Every vehicle type gets its own icon on the map.</p>
            <div class="overflow-hidden [mask-image:linear-gradient(to_right,transparent,black_10%,black_90%,transparent)]">
                <ul class="flex w-max items-center motion-safe:animate-[marquee_38s_linear_infinite] motion-safe:hover:[animation-play-state:paused]">
                    @for ($loop2 = 0; $loop2 < 2; $loop2++)
                        @foreach ([['car','Car'],['suv','SUV'],['van','Van'],['truck','Truck'],['motorcycle','Motorbike'],['three-wheeler','Tuk-tuk']] as $v)
                        <li class="flex items-center gap-4 px-8" @if($loop2===1) aria-hidden="true" @endif>
                        <span class="relative block h-14 w-28">
                            <img src="/vehicle-icons/{{ $v[0] }}-green.png" alt="" class="absolute left-1/2 top-1/2 h-28 w-auto max-w-none -translate-x-1/2 -translate-y-1/2 rotate-90" loading="lazy" width="56" height="112" />
                        </span>
                        <span class="text-2xl font-extrabold text-white/90 [font-stretch:75%]">{{ $v[1] }}</span>
                        </li>
                        @endforeach
                        @endfor
                </ul>
            </div>
        </section>

        {{-- ================= FACTS (count up on scroll) ================= --}}
        <section class="bg-[#F3F5F9] py-14 md:py-20" aria-label="At a glance">
            <dl class="mx-auto grid max-w-6xl grid-cols-2 gap-x-6 gap-y-10 px-5 sm:px-8 lg:grid-cols-4">
                @foreach ([
                [20, 's', 'between position updates'],
                ['&lt;1', 'h', 'typical device install and activation time'],
                [90, ' days', 'signed in on a device you trust'],
                [0, '', 'passwords to remember'],
                ] as $i => $f)
                <div data-reveal class="translate-y-8 opacity-0 transition duration-700 ease-out data-[in]:translate-y-0 data-[in]:opacity-100 motion-reduce:translate-y-0 motion-reduce:opacity-100 motion-reduce:transition-none" style="transition-delay: {{ $i * 90 }}ms">
                    <dt class="display text-6xl tabular-nums text-[#021F4A] md:text-7xl">@if(is_numeric($f[0]))<span data-count="{{ $f[0] }}">{{ $f[0] }}</span>@else{!! $f[0] !!}@endif<span class="text-3xl md:text-4xl">{{ $f[1] }}</span></dt>
                    <dd class="mt-2 max-w-[14rem] border-t-2 border-[#FA6908] pt-2 text-sm text-[#55627A]">{{ $f[2] }}</dd>
                </div>
                @endforeach
            </dl>
        </section>

        {{-- ================= WHY SHALOTRACK ================= --}}
        <section id="why-us" class="bg-white py-20 md:py-28" aria-labelledby="why-title">
            <div class="mx-auto max-w-6xl px-5 sm:px-8">
                <div class="max-w-xl">
                    <h2 id="why-title" class="display text-[clamp(2.5rem,6vw,4.2rem)] text-balance">What most trackers don't tell you.</h2>
                    <p class="mt-5 max-w-md text-[#55627A]">Cheap GPS trackers are everywhere in Sri Lanka. Most stop at a blinking dot. Here is where ShaloTrack is built differently.</p>
                </div>

                <div data-reveal class="mt-12 translate-y-8 overflow-hidden rounded-2xl border border-[#D9DFEA] opacity-0 transition duration-700 ease-out data-[in]:translate-y-0 data-[in]:opacity-100 motion-reduce:translate-y-0 motion-reduce:opacity-100 motion-reduce:transition-none">
                    <div class="grid grid-cols-[1.1fr_1fr_1fr] sm:grid-cols-[1.4fr_1fr_1fr]">
                        <div class="invisible border-b border-[#D9DFEA] bg-[#F3F5F9] p-3.5 sm:p-4" aria-hidden="true"></div>
                        <div class="border-b border-[#D9DFEA] bg-[#F3F5F9] p-3.5 text-center text-xs font-bold uppercase tracking-wider text-[#55627A] sm:p-4 sm:text-sm">A typical tracker</div>
                        <div class="border-b border-[#D9DFEA] bg-[#021F4A] p-3.5 text-center text-xs font-bold uppercase tracking-wider text-white sm:p-4 sm:text-sm">ShaloTrack</div>

                        @foreach ([
                        ['Sign in', 'A shared generic app, often not in your language', 'Your own phone number and an OTP &mdash; no password'],
                        ['Live map', 'Updates every few minutes, if at all', 'Updates every 20 seconds, with speed and ignition'],
                        ['Trip history', 'Little or none, or a paid add-on', '90 days of history, full replay, included'],
                        ['Alerts', 'A basic SMS, if anything', 'Overspeed, geofence, ignition and power-cut &mdash; on your phone'],
                        ['Your data', 'Held by whoever sold you the hardware', 'Export it or delete it yourself, anytime'],
                        ['Support', 'A call centre overseas, slow replies', 'Built and supported in Sri Lanka &mdash; call or WhatsApp us'],
                        ] as $i => $r)
                        <div class="flex items-center border-b border-[#D9DFEA] p-3.5 text-sm font-bold text-[#0A1B33] last:border-b-0 sm:p-4 sm:text-base">{{ $r[0] }}</div>
                        <div class="flex items-start gap-2 border-b border-[#D9DFEA] p-3.5 text-xs text-[#55627A] last:border-b-0 sm:p-4 sm:text-sm">
                            <svg class="mt-0.5 size-4 shrink-0 text-[#B8C2D6]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <path d="M6 6l12 12M18 6L6 18" />
                            </svg>
                            <span>{!! $r[1] !!}</span>
                        </div>
                        <div class="flex items-start gap-2 border-b border-[#D9DFEA] bg-[#FFF8F3] p-3.5 text-xs font-medium text-[#0A1B33] last:border-b-0 sm:p-4 sm:text-sm">
                            <svg class="mt-0.5 size-4 shrink-0 text-[#FA6908]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 13l4 4L19 7" />
                            </svg>
                            <span>{!! $r[2] !!}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- ================= FEATURES ================= --}}
        <section id="features" class="bg-white py-20 md:py-28" aria-labelledby="features-title">
            <div class="mx-auto grid max-w-6xl gap-10 px-5 sm:px-8 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20">
                <div class="self-start lg:sticky lg:top-28">
                    <h2 id="features-title" class="display text-[clamp(2.5rem,6vw,4.2rem)] text-balance">Everything about a vehicle, in one place.</h2>
                    <p class="mt-5 max-w-md text-[#55627A]">For owners, managers and drivers who need to know where a vehicle is without calling anyone.</p>
                </div>

                <ul class="divide-y divide-[#D9DFEA] border-t-2 border-[#0A1B33]">
                    @foreach ([
                    ['Live map', 'Watch vehicles move on the map. Speed, heading and ignition status refresh every 20 seconds.',
                    '
                    <path pathLength="1" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    <circle class="stroke-[#C2410C]" cx="12" cy="11" r="1.4" />'],
                    ['Alerts on your phone', 'Overspeed, geofence entry and exit, ignition on and off, power cut and low battery reach the ShaloTrack app as they happen.',
                    '
                    <path pathLength="1" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    <path pathLength="1" class="stroke-[#C2410C]" d="M19 4l1.5-1.5M20.5 7.5H22" />'],
                    ['Trip history', 'Replay any journey with a scrubber: the route, every stop and the speed at each point.',
                    '
                    <path pathLength="1" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    <path pathLength="1" class="stroke-[#C2410C]" d="M12 3v1.5" />'],
                    ['Geofences', 'Draw a zone around a depot, a client site or a no-go area. Know when a vehicle enters or leaves.',
                    '
                    <path pathLength="1" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <circle class="stroke-[#C2410C]" cx="12" cy="11" r="3" />'],
                    ['Sharing', 'Give a manager, driver or client access to chosen vehicles only. Or send a live link that stops working when you say so.',
                    '
                    <path pathLength="1" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />'],
                    ['Fleet management', 'Add vehicles, link GPS devices by IMEI and get a reminder before a licence or insurance runs out.',
                    '
                    <path pathLength="1" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 2h8l2-2h2a1 1 0 000-2h-1" />
                    <path pathLength="1" class="stroke-[#C2410C]" d="M14 8h3l3 4" />'],
                    ['Reports & exports', 'Download a PDF or CSV report of distance, stops and alerts for any vehicle and date range.',
                    '
                    <path pathLength="1" d="M7 3h7l5 5v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1z M14 3v5h5" />
                    <path pathLength="1" class="stroke-[#C2410C]" d="M9 13h6M9 16.5h6" />'],
                    ['Weekly summary', "A push every Monday with last week's distance, trips and overspeed events, so you don't have to check daily.",
                    '
                    <path pathLength="1" d="M8 7V3m8 4V3M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    <path pathLength="1" class="stroke-[#C2410C]" d="M3 11h18" />'],
                    ['Emergency contacts', 'Keep a call list on each vehicle, so a driver or dispatcher is one tap from help.',
                    '
                    <path pathLength="1" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />'],
                    ] as $i => $f)
                    <li data-reveal
                        class="group grid translate-x-10 grid-cols-[2.5rem_1fr] gap-4 py-7 opacity-0 transition duration-700 ease-out hover:bg-[#F3F5F9] data-[in]:translate-x-0 data-[in]:opacity-100 motion-reduce:translate-x-0 motion-reduce:opacity-100 motion-reduce:transition-none sm:px-3">
                        <svg viewBox="0 0 24 24" class="mt-0.5 size-8 fill-none stroke-[#0A1B33] stroke-[1.7] [stroke-linecap:round] [stroke-linejoin:round] group-hover:stroke-[#FA6908]
                                                      [&_path]:[stroke-dasharray:1] [&_path]:[stroke-dashoffset:1] [&_path]:transition-[stroke-dashoffset] [&_path]:duration-[1400ms] [&_path]:delay-300 group-data-[in]:[&_path]:[stroke-dashoffset:0] motion-reduce:[&_path]:[stroke-dashoffset:0]" aria-hidden="true">{!! $f[2] !!}</svg>
                        <div>
                            <h3 class="cond text-2xl transition-transform duration-300 group-hover:translate-x-1">{{ $f[0] }}</h3>
                            <p class="mt-1 max-w-lg text-[#55627A]">{{ $f[1] }}</p>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- ================= HOW IT WORKS ================= --}}
        <section id="how-it-works" class="bg-[#F3F5F9] py-20 md:py-28" aria-labelledby="how-title">
            <div class="mx-auto max-w-6xl px-5 sm:px-8">
                <div class="max-w-xl">
                    <h2 id="how-title" class="display text-[clamp(2.5rem,6vw,4.2rem)] text-balance">Three steps to your first vehicle on the map.</h2>
                    <p class="mt-5 max-w-md text-[#55627A]">No password to remember and nothing to install on the vehicle except the device.</p>
                </div>
                <ol class="mt-14 grid gap-12 md:grid-cols-3 md:gap-10">
                    @foreach ([
                    ['Sign in', 'Enter your phone number and the one-time code we send you. Your account is ready.'],
                    ['Add a vehicle', 'Enter the vehicle details and link your ShaloTrack GPS device with its IMEI number.'],
                    ['Watch it move', 'Open the dashboard. Your vehicle appears on the map. Add geofences and alerts when you are ready.'],
                    ] as $i => $s)
                    <li data-reveal
                        class="group relative scale-95 pl-16 opacity-0 transition duration-700 ease-out data-[in]:scale-100 data-[in]:opacity-100 motion-reduce:scale-100 motion-reduce:opacity-100 motion-reduce:transition-none md:pl-0 md:pt-16
                           after:absolute after:left-5 after:top-12 after:h-[calc(100%-1rem)] after:origin-top after:scale-y-0 after:border-l-2 after:border-dashed after:border-[#B8C2D6] after:transition-transform after:duration-700 after:delay-500 after:content-[''] data-[in]:after:scale-y-100
                           md:after:left-14 md:after:top-5 md:after:h-0 md:after:w-[calc(100%-2rem)] md:after:origin-left md:after:scale-x-0 md:after:scale-y-100 md:after:border-l-0 md:after:border-t-2 md:data-[in]:after:scale-x-100 last:after:hidden"
                        style="transition-delay: {{ $i * 150 }}ms">
                        <span class="absolute left-0 top-0 grid size-10 place-items-center rounded-full bg-[#021F4A] text-lg font-extrabold text-white shadow-[0_0_0_6px_#F3F5F9] [font-stretch:80%] transition group-hover:bg-[#FA6908] group-hover:text-[#010F25]">{{ $i + 1 }}</span>
                        <h3 class="cond text-[1.7rem]">{{ $s[0] }}</h3>
                        <p class="mt-1.5 max-w-xs text-[#55627A]">{{ $s[1] }}</p>
                    </li>
                    @endforeach
                </ol>
            </div>
        </section>

        {{-- ================= YOUR DATA ================= --}}
        <section id="your-data" class="bg-[#021F4A] py-20 text-white md:py-28" aria-labelledby="data-title">
            <div class="mx-auto grid max-w-6xl gap-10 px-5 sm:px-8 lg:grid-cols-[0.9fr_1.1fr] lg:gap-20">
                <h2 id="data-title" class="display text-[clamp(2.5rem,6vw,4.2rem)] text-balance">Your vehicles. Your data. Your call.</h2>
                <div class="divide-y divide-white/15 border-t border-white/30">
                    @foreach ([
                    ['Sign in with a code, not a password', 'A one-time code goes to your phone. Nothing to reuse, leak or forget.'],
                    ['Share only what you choose', 'Each person sees only the vehicles you give them, and you can take access back at any time.'],
                    ['Take your data or delete it', 'Download a copy of your data or delete your account from your profile page.'],
                    ] as $i => $d)
                    <div data-reveal class="-translate-x-8 py-6 opacity-0 transition duration-700 ease-out data-[in]:translate-x-0 data-[in]:opacity-100 motion-reduce:translate-x-0 motion-reduce:opacity-100 motion-reduce:transition-none" style="transition-delay: {{ $i * 120 }}ms">
                        <h3 class="cond text-2xl">{{ $d[0] }}</h3>
                        <p class="mt-1 max-w-lg text-[#B7C6E2]">{{ $d[1] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ================= VISION, MISSION & STORY ================= --}}
        <section id="story" class="bg-white py-20 md:py-28" aria-labelledby="story-title">
            <div class="mx-auto max-w-6xl px-5 sm:px-8">
                <div class="max-w-xl">
                    <h2 id="story-title" class="display text-[clamp(2.5rem,6vw,4.2rem)] text-balance">Why we're building this.</h2>
                    <p class="mt-5 max-w-md text-[#55627A]">A small team in Sri Lanka, building the tracking platform we wished existed.</p>
                </div>

                <div class="mt-12 grid gap-5 sm:grid-cols-2">
                    <div data-reveal class="translate-y-8 rounded-2xl bg-[#F3F5F9] p-7 opacity-0 ring-1 ring-[#D9DFEA] transition duration-700 ease-out data-[in]:translate-y-0 data-[in]:opacity-100 motion-reduce:translate-y-0 motion-reduce:opacity-100 motion-reduce:transition-none">
                        <p class="text-xs font-bold uppercase tracking-wider text-[#C2410C]">Vision</p>
                        <p class="cond mt-4 text-xl text-[#0A1B33] sm:text-2xl">A Sri Lanka where every vehicle owner can see, prove and protect what's theirs &mdash; from their own phone.</p>
                    </div>
                    <div data-reveal class="translate-y-8 rounded-2xl bg-[#021F4A] p-7 text-white opacity-0 transition duration-700 ease-out data-[in]:translate-y-0 data-[in]:opacity-100 motion-reduce:translate-y-0 motion-reduce:opacity-100 motion-reduce:transition-none" style="transition-delay: 100ms">
                        <p class="text-xs font-bold uppercase tracking-wider text-[#FA6908]">Mission</p>
                        <p class="cond mt-4 text-xl sm:text-2xl">Build tracking that's simple to set up, honest about your data, and backed by people who answer the phone.</p>
                    </div>
                </div>

                <div class="mt-16">
                    <h3 class="cond text-2xl text-[#0A1B33] sm:text-3xl">How ShaloTrack came together</h3>
                    <div class="story-track relative mt-10 pl-9 sm:pl-10">
                        <div class="absolute bottom-0 left-[3px] top-1 w-0.5 bg-[#D9DFEA] sm:left-[3.5px]"></div>
                        <div class="timeline-fill absolute bottom-0 left-[3px] top-1 w-0.5 origin-top bg-[#FA6908] sm:left-[3.5px]" aria-hidden="true"></div>
                        <ol class="grid gap-12">
                            @foreach ([
                            ['The gateway', 'A Python service built to speak the GT06 protocol directly to GPS hardware, turning raw device signals into real positions.'],
                            ['The backend', "A C#/.NET API connecting the gateway, the database and every app, so each vehicle's data has one source of truth."],
                            ['The admin portal', 'A Laravel admin portal for managing devices, dealers and customer accounts behind the scenes.'],
                            ['The Android app', "A native Android app, so customers could see their own vehicles without calling anyone."],
                            ['This web portal', "Live maps, trip history, geofences, alerts and reports in the browser &mdash; the page you're on now."],
                            ["What's next", 'The same security hardening already live here, coming to Android, then iOS.'],
                            ] as $i => $s)
                            <li data-reveal class="relative -translate-x-4 opacity-0 transition duration-700 ease-out data-[in]:translate-x-0 data-[in]:opacity-100 motion-reduce:translate-x-0 motion-reduce:opacity-100 motion-reduce:transition-none" style="transition-delay: {{ $i * 80 }}ms">
                                <span class="absolute -left-9 top-1 grid size-3.5 place-items-center rounded-full bg-[#FA6908] ring-4 ring-white sm:-left-10"></span>
                                <h4 class="cond text-xl text-[#0A1B33]">{{ $s[0] }}</h4>
                                <p class="mt-1 max-w-lg text-[#55627A]">{!! $s[1] !!}</p>
                            </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        {{-- ================= TEAM ================= --}}
        <section id="team" class="bg-[#F3F5F9] py-20 md:py-28" aria-labelledby="team-title">
            <div class="mx-auto max-w-6xl px-5 sm:px-8">
                <div class="max-w-xl">
                    <h2 id="team-title" class="display text-[clamp(2.5rem,6vw,4.2rem)] text-balance">Built in Sri Lanka, run by five people.</h2>
                    <p class="mt-5 max-w-md text-[#55627A]">One owner and four engineers design, build and support ShaloTrack end to end &mdash; so the person who fixes a problem is the person who understands it.</p>
                </div>

                {{-- Owner: a distinct, wider card above the engineering grid, marking a different kind of role --}}
                <div data-reveal
                    class="group mt-12 flex translate-y-8 flex-col items-start gap-5 rounded-2xl border-t-4 border-[#FA6908] bg-white p-7 opacity-0 shadow-sm ring-1 ring-[#D9DFEA] transition duration-700 ease-out hover:-translate-y-1 hover:shadow-xl data-[in]:translate-y-0 data-[in]:opacity-100 motion-reduce:translate-y-0 motion-reduce:opacity-100 motion-reduce:transition-none sm:flex-row sm:items-center sm:gap-7">
                    <span class="grid size-16 shrink-0 place-items-center rounded-full bg-[#021F4A] text-white" aria-hidden="true">
                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2l2.4 6.6L21 11l-6.6 2.4L12 20l-2.4-6.6L3 11l6.6-2.4L12 2z" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="cond text-2xl">Nuwan Aloka</h3>
                        <p class="mt-1 text-[#55627A]">Founder &amp; Owner &mdash; sets the product direction and roadmap.</p>
                    </div>
                </div>

                <p class="mb-5 mt-14 text-xs font-bold uppercase tracking-wider text-[#55627A]/70">Engineering</p>
                <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                    ['Suwen', 'S', 'Systems Lead — TCP gateway, .NET API, Android backend'],
                    ['Nuwan Akalanka', 'NA', 'Web & QA — Laravel admin portal, web UI/UX'],
                    ['Rohansa Edirisinghe', 'RE', 'Mobile & UI — Android UI/UX, quality assurance'],
                    ['Amoda', 'A', 'Infrastructure — cloud, DevOps/SRE, security and networking'],
                    ] as $i => $m)
                    <li data-reveal
                        class="group translate-y-8 rounded-2xl bg-white p-6 opacity-0 shadow-sm ring-1 ring-[#D9DFEA] transition duration-700 ease-out hover:-translate-y-1 hover:shadow-xl hover:ring-[#FA6908]/50 data-[in]:translate-y-0 data-[in]:opacity-100 motion-reduce:translate-y-0 motion-reduce:opacity-100 motion-reduce:transition-none"
                        style="transition-delay: {{ $i * 100 }}ms">
                        <span class="grid size-14 place-items-center rounded-full bg-[#021F4A] text-xl font-extrabold text-white transition duration-300 [font-stretch:80%] group-hover:bg-[#FA6908] group-hover:text-[#010F25]" aria-hidden="true">{{ $m[1] }}</span>
                        <h3 class="cond mt-5 text-2xl">{{ $m[0] }}</h3>
                        <p class="mt-1 text-[#55627A]">{{ $m[2] }}</p>
                    </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- ================= FAQ ================= --}}
        <section id="faq" class="bg-white py-20 md:py-28" aria-labelledby="faq-title">
            <div class="mx-auto grid max-w-6xl gap-10 px-5 sm:px-8 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20">
                <h2 id="faq-title" class="display text-[clamp(2.5rem,6vw,4.2rem)] text-balance">Questions people ask first.</h2>
                <div class="divide-y divide-[#D9DFEA] border-y border-[#D9DFEA]">
                    @foreach ([
                    ['Do I need to install anything on the vehicle?', 'Only the ShaloTrack GPS device. Link it to your vehicle with its IMEI number and the vehicle appears on your map.'],
                    ['How often does the map update?', 'Every 20 seconds while the device is reporting.'],
                    ['Can other people see my vehicles?', 'Only if you share them. You choose which vehicles each person sees, you can send a live link that expires, and you can remove access at any time.'],
                    ['Do I need the app, or can I use the web?', 'Both work with the same account. Alerts are delivered to the ShaloTrack mobile app.'],
                    ['How long do I stay signed in?', 'Up to 90 days after you sign in with your code, unless you sign out first.'],
                    ['How do I delete my account?', 'From your profile page. You can download a copy of your data first.'],
                    ] as $q)
                    <details class="group py-1 open:bg-[#F3F5F9] sm:px-3">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-6 rounded-md py-5 text-left text-lg font-bold focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#FA6908]/60 [&::-webkit-details-marker]:hidden">
                            <span>{{ $q[0] }}</span>
                            <svg class="size-5 shrink-0 stroke-[#0A1B33] transition-transform duration-300 group-open:rotate-45 group-open:stroke-[#FA6908]" viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" aria-hidden="true">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                        </summary>
                        <p class="max-w-xl pb-5 text-[#55627A]">{{ $q[1] }}</p>
                    </details>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ================= CTA ================= --}}
        <section class="bg-[#FA6908] py-20 text-[#010F25] md:py-28" aria-labelledby="cta-title">
            <div class="mx-auto grid max-w-6xl items-end gap-8 px-5 sm:px-8 md:grid-cols-[1fr_auto]">
                <div data-reveal class="translate-y-8 opacity-0 transition duration-700 ease-out data-[in]:translate-y-0 data-[in]:opacity-100 motion-reduce:translate-y-0 motion-reduce:opacity-100 motion-reduce:transition-none">
                    <h2 id="cta-title" class="display text-[clamp(2.8rem,7vw,5rem)] text-balance">Your vehicles are moving. Open the map.</h2>
                    <p class="mt-4 max-w-md">Sign in with your phone number. No password needed.</p>
                </div>
                <a href="/login" class="inline-flex items-center justify-center rounded-xl bg-[#021F4A] px-7 py-4 font-bold text-white transition hover:-translate-y-0.5 hover:bg-[#010F25] active:translate-y-0 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#010F25]/40">Sign in or register</a>
            </div>
        </section>
    </main>

    <footer class="bg-[#010F25] py-12 text-sm text-[#B7C6E2]">
        <div class="mx-auto max-w-6xl px-5 sm:px-8">
            <div class="grid gap-10 border-b border-white/10 pb-10 sm:grid-cols-3">
                <div>
                    <a href="/" class="flex items-center gap-2.5 text-lg font-extrabold text-white [font-stretch:85%]">
                        <span class="grid size-8 place-items-center rounded-lg bg-[#FA6908]">
                            <svg class="size-4 fill-[#010F25]" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z" />
                            </svg>
                        </span>
                        <span>Shalo<span class="text-[#FA6908]">Track</span></span>
                    </a>
                    <p class="mt-3 max-w-xs text-[#8697B8]">Always Connected. GPS vehicle tracking built and supported in Sri Lanka.</p>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-white/50">Contact</h3>
                    <ul class="mt-3 grid gap-2">
                        <li><a href="tel:+94716553852" class="hover:text-white">+94 71 655 3852</a></li>
                        <li><a href="mailto:aloka@shalotrack.com" class="hover:text-white">aloka@shalotrack.com</a></li>
                        <li><a href="https://wa.me/94716553852" class="hover:text-white" target="_blank" rel="noopener">WhatsApp us</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-white/50">Site</h3>
                    <ul class="mt-3 grid gap-2">
                        <li><a href="/login" class="hover:text-white">Sign in</a></li>
                        <li><a href="#features" class="hover:text-white">Features</a></li>
                        <li><a href="#why-us" class="hover:text-white">Why us</a></li>
                        <li><a href="#story" class="hover:text-white">Our story</a></li>
                        <li><a href="#team" class="hover:text-white">Team</a></li>
                        <li><a href="https://www.shalotrack.com/warranty-and-support.html" class="hover:text-white" target="_blank" rel="noopener">Support</a></li>
                    </ul>
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-x-8 gap-y-3 pt-6">
                <span>© {{ date('Y') }} ShaloTrack Lanka (Pvt) Ltd</span>
                <div class="flex flex-wrap gap-6">
                    <a href="https://www.shalotrack.com/privacy.html" class="hover:text-white" target="_blank" rel="noopener">Privacy</a>
                    <a href="https://www.shalotrack.com/terms.html" class="hover:text-white" target="_blank" rel="noopener">Terms</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        (function() {
            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var nav = document.getElementById('nav');
            var bar = document.getElementById('progress');
            var nativeScroll = window.CSS && CSS.supports && CSS.supports('animation-timeline', 'scroll()');

            // Nav shadow + progress bar fallback
            function onScroll() {
                var y = window.scrollY || 0;
                if (y > 8) nav.setAttribute('data-scrolled', '');
                else nav.removeAttribute('data-scrolled');
                if (!nativeScroll && !reduce) {
                    var max = document.documentElement.scrollHeight - window.innerHeight;
                    bar.style.transform = 'scaleX(' + (max > 0 ? Math.min(1, y / max) : 0) + ')';
                }
            }
            window.addEventListener('scroll', onScroll, {
                passive: true
            });
            onScroll();

            // Close the mobile menu after choosing a link
            var toggle = document.getElementById('menu-toggle');
            document.querySelectorAll('.menu-link').forEach(function(a) {
                a.addEventListener('click', function() {
                    toggle.checked = false;
                });
            });

            // Scroll reveal: Tailwind's data-[in]: variants do the styling, this just flips the attribute
            var els = document.querySelectorAll('[data-reveal]');

            function show(el) {
                el.setAttribute('data-in', '');
            }
            if (!('IntersectionObserver' in window) || reduce) {
                els.forEach(show);
            } else {
                var io = new IntersectionObserver(function(entries) {
                    entries.forEach(function(e) {
                        if (e.isIntersecting) {
                            show(e.target);
                            io.unobserve(e.target);
                        }
                    });
                }, {
                    threshold: 0.2,
                    rootMargin: '0px 0px -8% 0px'
                });
                els.forEach(function(el) {
                    io.observe(el);
                });
            }

            // Count-up when the facts scroll into view
            var counters = document.querySelectorAll('[data-count]');
            if (!reduce) counters.forEach(function(c) {
                c.textContent = '0';
            });

            function run(el) {
                var target = parseInt(el.getAttribute('data-count'), 10) || 0;
                if (reduce || target === 0) {
                    el.textContent = target;
                    return;
                }
                var t0 = null,
                    dur = 1100;
                el.textContent = '0';

                function step(ts) {
                    if (t0 === null) t0 = ts;
                    var p = Math.min(1, (ts - t0) / dur);
                    el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3)));
                    if (p < 1) requestAnimationFrame(step);
                }
                requestAnimationFrame(step);
            }
            if ('IntersectionObserver' in window) {
                var co = new IntersectionObserver(function(entries) {
                    entries.forEach(function(e) {
                        if (e.isIntersecting) {
                            run(e.target);
                            co.unobserve(e.target);
                        }
                    });
                }, {
                    threshold: 0.6
                });
                counters.forEach(function(c) {
                    co.observe(c);
                });
            }

            // Map: freeze vehicles on a pleasant frame for reduced motion
            var svg = document.getElementById('map');
            if (reduce && svg && svg.pauseAnimations) {
                try {
                    svg.setCurrentTime(6);
                    svg.pauseAnimations();
                } catch (_) {
                    /* decoration only */ }
            }

            // Sample panel: speed wanders, the 20 s update clock counts
            var speeds = [62, 58, 47, 33, 41, 55, 66, 61],
                si = 0,
                s = 0;
            var speedEl = document.getElementById('speed'),
                sEl = document.getElementById('tick-s'),
                tb = document.getElementById('tick-bar');

            function paintTick() {
                sEl.textContent = String(s);
                tb.style.width = (s / 20 * 100) + '%';
            }
            paintTick();
            setInterval(function() {
                s += 1;
                if (s >= 20) {
                    s = 0;
                    si = (si + 1) % speeds.length;
                    speedEl.textContent = String(speeds[si]);
                }
                paintTick();
            }, 1000);
        })();
    </script>
</body>

</html>