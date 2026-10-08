<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#021F4A" />
    <title>ShaloTrack — Vehicle tracking for Sri Lanka</title>
    <meta name="description" content="See where every vehicle is, how fast it is going and whether the engine is on. Live GPS tracking, trip history, geofences and alerts for Sri Lankan businesses." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,100..900&display=swap" rel="stylesheet">
    <style>
        :root {
            --sea: #021F4A;
            --deep: #010F25;
            --land: #0C2F66;
            --orange: #FA6908;
            --orange-ink: #C2410C;
            --online: #22C55E;
            --paper: #F3F5F9;
            --ink: #0A1B33;
            --muted: #55627A;
            --line: #D9DFEA;
            --on-dark: #B7C6E2;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 5rem;
        }

        body {
            margin: 0;
            font-family: 'Archivo', system-ui, sans-serif;
            font-stretch: 100%;
            color: var(--ink);
            background: #fff;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        a {
            color: inherit;
        }

        :focus-visible {
            outline: 3px solid var(--orange);
            outline-offset: 3px;
            border-radius: 4px;
        }

        .wrap {
            width: 100%;
            max-width: 72rem;
            margin: 0 auto;
            padding: 0 1.25rem;
        }

        @media (min-width: 640px) {
            .wrap {
                padding: 0 2rem;
            }
        }

        /* Headline voice: condensed, heavy, like road signage */
        .display {
            font-stretch: 70%;
            font-weight: 800;
            letter-spacing: 0;
            word-spacing: .08em;
            line-height: 0.96;
            margin: 0;
        }

        /* ── Nav ── */
        .nav {
            position: fixed;
            inset: 0 0 auto 0;
            z-index: 50;
            background: rgba(2, 31, 74, 0.92);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .nav .wrap {
            height: 4rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: .55rem;
            color: #fff;
            text-decoration: none;
            font-weight: 800;
            font-size: 1.15rem;
            font-stretch: 85%;
        }

        .brand i {
            font-style: normal;
            color: var(--orange);
        }

        .brand-mark {
            width: 1.9rem;
            height: 1.9rem;
            border-radius: .5rem;
            background: var(--orange);
            display: grid;
            place-items: center;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .nav-links a {
            color: var(--on-dark);
            text-decoration: none;
            font-size: .9rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .nav-links a:hover {
            color: #fff;
        }

        .nav-links .hide-sm {
            display: none;
        }

        @media (min-width: 820px) {
            .nav-links .hide-sm {
                display: inline;
            }
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .8rem 1.5rem;
            border-radius: .6rem;
            background: var(--orange);
            color: var(--deep) !important;
            font-weight: 700;
            font-size: .95rem;
            text-decoration: none;
            white-space: nowrap;
            transition: background .15s;
        }

        .btn:hover {
            background: #FF7A1F;
        }

        .btn-sm {
            padding: .5rem 1rem;
            font-size: .875rem;
        }

        /* ── Hero ── */
        .hero {
            position: relative;
            overflow: hidden;
            background: var(--sea);
            color: #fff;
            padding: 6.5rem 0 3.5rem;
        }

        .hero-grid {
            display: grid;
            gap: 2.5rem;
            align-items: center;
        }

        @media (min-width: 960px) {
            .hero {
                padding: 7rem 0 4rem;
                min-height: 100vh;
                display: flex;
                align-items: center;
            }

            .hero .wrap {
                width: 100%;
            }

            .hero-grid {
                grid-template-columns: minmax(0, 1.05fr) minmax(0, .95fr);
                gap: 3rem;
            }
        }

        .hero h1 {
            font-size: clamp(3.4rem, 9.5vw, 6rem);
        }

        .hero h1 span {
            display: block;
        }

        .hero-lede {
            max-width: 30rem;
            font-size: 1.125rem;
            color: var(--on-dark);
            margin: 1.75rem 0 2rem;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1.25rem 1.75rem;
        }

        .link-quiet {
            color: var(--on-dark);
            font-weight: 500;
            text-decoration: underline;
            text-underline-offset: .3em;
            text-decoration-color: rgba(183, 198, 226, .4);
        }

        .link-quiet:hover {
            color: #fff;
            text-decoration-color: #fff;
        }

        /* Map */
        .map-stage {
            position: relative;
            max-width: 31rem;
            width: 100%;
            margin: 0 auto;
        }

        .map-stage svg {
            display: block;
            width: 100%;
            height: auto;
            overflow: visible;
        }

        .route {
            fill: none;
            stroke: var(--orange);
            stroke-width: 1.6;
            stroke-linecap: round;
            opacity: .75;
            stroke-dasharray: 1;
            stroke-dashoffset: 1;
            animation: draw 2.4s cubic-bezier(.5, 0, .2, 1) forwards;
        }

        .route.r2 {
            animation-delay: .25s;
        }

        .route.r3 {
            animation-delay: .5s;
        }

        .route.r4 {
            animation-delay: .75s;
        }

        @keyframes draw {
            to {
                stroke-dashoffset: 0;
            }
        }

        .city-dot {
            fill: #fff;
            opacity: .9;
        }

        .city-name {
            fill: #fff;
            opacity: .6;
            font-size: 11px;
            font-weight: 500;
            font-family: 'Archivo', sans-serif;
            letter-spacing: .02em;
        }

        .legend {
            position: absolute;
            top: .25rem;
            right: 0;
            font-size: .8rem;
            color: var(--on-dark);
            display: grid;
            gap: .3rem;
        }

        .legend span {
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .legend i {
            width: .6rem;
            height: .6rem;
            border-radius: 50%;
            display: block;
        }

        /* Live panel */
        .panel {
            background: #fff;
            color: var(--ink);
            border-radius: .9rem;
            padding: 1rem 1.1rem 1.1rem;
            box-shadow: 0 18px 40px rgba(0, 8, 24, .45);
            width: 100%;
            margin: 1rem 0 0;
        }

        @media (min-width: 960px) {
            .panel {
                position: absolute;
                top: 0;
                right: 0;
                width: 14.25rem;
                margin: 0;
                padding: .8rem .9rem .9rem;
            }

            .legend {
                top: auto;
                bottom: 1.5rem;
            }

            .panel .speed {
                font-size: 2.2rem;
            }
        }

        .panel-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
        }

        .plate {
            font-stretch: 75%;
            font-weight: 800;
            font-size: 1.05rem;
            letter-spacing: .04em;
            word-spacing: .2em;
            white-space: nowrap;
            background: #fff;
            color: #111;
            border: 2px solid #111;
            border-radius: .35rem;
            padding: .05rem .55rem;
            line-height: 1.35;
        }

        .sample {
            font-size: .72rem;
            color: var(--muted);
            white-space: nowrap;
        }

        .panel-model {
            margin: .35rem 0 .6rem;
            color: var(--muted);
            font-size: .9rem;
        }

        .panel-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 1rem;
        }

        .speed {
            font-stretch: 70%;
            font-weight: 800;
            font-size: 2.6rem;
            line-height: 1;
            font-variant-numeric: tabular-nums;
        }

        .speed small {
            font-size: 1rem;
            font-weight: 600;
            color: var(--muted);
            font-stretch: 100%;
            margin-left: .2rem;
        }

        .status {
            display: flex;
            align-items: center;
            gap: .45rem;
            font-size: .85rem;
            font-weight: 600;
        }

        .status i {
            width: .6rem;
            height: .6rem;
            border-radius: 50%;
            background: var(--online);
            display: block;
        }

        .tick {
            margin-top: .9rem;
        }

        .tick-bar {
            height: 4px;
            border-radius: 2px;
            background: var(--line);
            overflow: hidden;
        }

        .tick-bar b {
            display: block;
            height: 100%;
            width: 0;
            background: var(--orange);
        }

        .tick-text {
            margin-top: .4rem;
            font-size: .75rem;
            color: var(--muted);
            font-variant-numeric: tabular-nums;
        }

        /* ── Sections ── */
        section {
            position: relative;
        }

        .section {
            padding: 4.5rem 0;
        }

        @media (min-width: 820px) {
            .section {
                padding: 7rem 0;
            }
        }

        .h2 {
            font-size: clamp(2.5rem, 6vw, 4.2rem);
        }

        .lede {
            color: var(--muted);
            font-size: 1.05rem;
            max-width: 28rem;
            margin: 1.25rem 0 0;
        }

        /* Features */
        .features {
            background: var(--paper);
        }

        .features-grid {
            display: grid;
            gap: 2.5rem;
        }

        @media (min-width: 900px) {
            .features-grid {
                grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr);
                gap: 5rem;
            }

            .features-head {
                position: sticky;
                top: 7rem;
                align-self: start;
            }
        }

        .feature-list {
            list-style: none;
            margin: 0;
            padding: 0;
            border-top: 2px solid var(--ink);
        }

        .feature {
            display: grid;
            grid-template-columns: 2.5rem minmax(0, 1fr);
            gap: 1.1rem;
            padding: 1.6rem 0;
            border-bottom: 1px solid var(--line);
        }

        .feature svg {
            width: 2rem;
            height: 2rem;
            stroke: var(--ink);
            fill: none;
            stroke-width: 1.7;
            stroke-linecap: round;
            stroke-linejoin: round;
            margin-top: .15rem;
        }

        .feature svg .hot {
            stroke: var(--orange-ink);
        }

        .feature h3 {
            margin: 0 0 .3rem;
            font-size: 1.5rem;
            font-stretch: 75%;
            font-weight: 800;
            line-height: 1.1;
        }

        .feature p {
            margin: 0;
            color: var(--muted);
            max-width: 34rem;
        }

        /* Steps as stops on a route */
        .steps-head {
            max-width: 34rem;
        }

        .stops {
            list-style: none;
            margin: 3.5rem 0 0;
            padding: 0;
            display: grid;
            gap: 2.5rem;
            position: relative;
        }

        .stop {
            position: relative;
            padding-left: 4rem;
        }

        .stop::before {
            content: "";
            position: absolute;
            left: 1.2rem;
            top: 2.6rem;
            bottom: -2.9rem;
            border-left: 2px dashed #B8C2D6;
        }

        .stop:last-child::before {
            display: none;
        }

        .stop-n {
            position: absolute;
            left: 0;
            top: 0;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background: var(--sea);
            color: #fff;
            display: grid;
            place-items: center;
            font-weight: 800;
            font-stretch: 80%;
            font-size: 1.15rem;
            box-shadow: 0 0 0 5px #fff;
        }

        .stop h3 {
            margin: 0 0 .35rem;
            font-size: 1.7rem;
            font-stretch: 75%;
            font-weight: 800;
            line-height: 1.1;
        }

        .stop p {
            margin: 0;
            color: var(--muted);
            max-width: 22rem;
        }

        @media (min-width: 820px) {
            .stops {
                grid-template-columns: repeat(3, 1fr);
                gap: 2.5rem;
            }

            .stop {
                padding: 4rem 0 0;
            }

            .stop::before {
                left: 2.5rem;
                right: -2.5rem;
                top: 1.2rem;
                bottom: auto;
                border-left: 0;
                border-top: 2px dashed #B8C2D6;
                width: auto;
            }

            .stop-n {
                left: 0;
            }
        }

        /* Your data */
        .data {
            background: var(--sea);
            color: #fff;
        }

        .data-grid {
            display: grid;
            gap: 2.5rem;
        }

        @media (min-width: 900px) {
            .data-grid {
                grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr);
                gap: 5rem;
                align-items: start;
            }
        }

        .data .h2 {
            color: #fff;
        }

        .data-list {
            display: grid;
            gap: 0;
            border-top: 1px solid rgba(255, 255, 255, .25);
        }

        .data-item {
            padding: 1.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, .15);
        }

        .data-item h3 {
            margin: 0 0 .3rem;
            font-size: 1.5rem;
            font-stretch: 75%;
            font-weight: 800;
            line-height: 1.1;
        }

        .data-item p {
            margin: 0;
            color: var(--on-dark);
            max-width: 32rem;
        }

        /* CTA */
        .cta {
            background: var(--orange);
            color: var(--deep);
            text-align: left;
        }

        .cta .wrap {
            display: grid;
            gap: 2rem;
            align-items: end;
        }

        @media (min-width: 820px) {
            .cta .wrap {
                grid-template-columns: minmax(0, 1fr) auto;
            }
        }

        .cta h2 {
            font-size: clamp(2.8rem, 7vw, 5rem);
            color: var(--deep);
        }

        .cta .btn {
            background: var(--sea);
            color: #fff !important;
        }

        .cta .btn:hover {
            background: var(--deep);
        }

        .cta p {
            margin: 1rem 0 0;
            color: var(--deep);
            max-width: 26rem;
        }

        footer {
            background: var(--deep);
            color: var(--on-dark);
            padding: 2.5rem 0;
            font-size: .875rem;
        }

        footer .wrap {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem 2rem;
        }

        footer .links {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        footer a {
            text-decoration: none;
        }

        footer a:hover {
            color: #fff;
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            .route {
                animation: none;
                stroke-dashoffset: 0;
            }
        }
    </style>
</head>

<body>

    <nav class="nav" aria-label="Main">
        <div class="wrap">
            <a href="/" class="brand" aria-label="ShaloTrack home">
                <span class="brand-mark">
                    <svg width="16" height="16" fill="#fff" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z" />
                    </svg>
                </span>
                <span>Shalo<i>Track</i></span>
            </a>
            <div class="nav-links">
                <a href="#features" class="hide-sm">Features</a>
                <a href="#how-it-works" class="hide-sm">How it works</a>
                <a href="#your-data" class="hide-sm">Your data</a>
                <a href="/login">Sign in</a>
                <a href="/login" class="btn btn-sm">Get started</a>
            </div>
        </div>
    </nav>

    <main>
        {{-- HERO: the product itself. Sample vehicles drive real-shaped Sri Lankan routes. --}}
        <section class="hero" aria-labelledby="hero-title">
            <div class="wrap">
                <div class="hero-grid">
                    <div>
                        <h1 id="hero-title" class="display">
                            <span>Every vehicle.</span>
                            <span>On the map.</span>
                            <span>Right now.</span>
                        </h1>
                        <p class="hero-lede">
                            ShaloTrack shows where each of your vehicles is, how fast it is going and whether the engine is on. Open the app or the web dashboard and see it.
                        </p>
                        <div class="hero-actions">
                            <a href="/login" class="btn">Sign in with your phone</a>
                            <a href="#features" class="link-quiet">See what you get</a>
                        </div>
                    </div>

                    <div class="map-stage">
                        <svg id="map" viewBox="0 0 470 760" role="img" aria-label="Map of Sri Lanka with sample vehicles driving between Colombo, Kandy, Galle and Anuradhapura">
                            <defs>
                                <pattern id="dots" width="9" height="9" patternUnits="userSpaceOnUse">
                                    <circle cx="1.5" cy="1.5" r="1" fill="#fff" fill-opacity=".14" />
                                </pattern>
                            </defs>
                            <path d="M150.1,52.9 C180.7,62.5 166.0,68.8 199.4,100.5 C232.8,132.2 222.1,114.1 250.4,148.1 C278.7,182.1 261.2,162.8 284.4,202.5 C307.6,242.2 298.6,230.3 320.1,267.1 C341.6,303.9 330.9,280.7 349.0,313.0 C367.1,345.3 357.5,331.1 374.5,364.0 C391.5,396.9 384.7,378.7 400.0,411.6 C415.3,444.5 410.2,424.6 420.4,462.6 C430.6,500.6 429.5,492.6 430.6,525.5 C431.7,558.4 434.0,532.9 423.8,561.2 C413.6,589.5 422.1,579.9 400.0,610.5 C377.9,641.1 379.6,633.7 357.5,653.0 C335.4,672.3 352.4,658.1 333.7,668.3 C315.0,678.5 330.3,672.8 301.4,683.6 C272.5,694.4 279.3,691.0 247.0,700.6 C214.7,710.2 237.4,713.1 204.5,712.5 C171.6,711.9 179.6,725.5 148.4,698.9 C117.2,672.3 130.3,675.1 111.0,632.6 C91.7,590.1 99.7,600.3 90.6,571.4 C81.5,542.5 86.1,570.3 83.8,545.9 C81.5,521.5 86.1,535.1 83.8,498.3 C81.5,461.5 77.6,481.9 77.0,435.4 C76.4,388.9 85.5,394.0 82.1,358.9 C78.7,323.8 62.3,362.3 66.8,330.0 C71.3,297.7 92.3,307.3 95.7,262.0 C99.1,216.7 87.8,222.3 77.0,194.0 C66.2,165.7 49.2,205.3 63.4,177.0 C77.6,148.7 104.8,144.1 119.5,109.0 C134.2,73.9 97.4,90.3 107.6,71.6 C117.8,52.9 119.5,43.3 150.1,52.9 Z" fill="none" stroke="#fff" stroke-opacity=".035" stroke-width="64" stroke-linejoin="round" />
                            <path d="M150.1,52.9 C180.7,62.5 166.0,68.8 199.4,100.5 C232.8,132.2 222.1,114.1 250.4,148.1 C278.7,182.1 261.2,162.8 284.4,202.5 C307.6,242.2 298.6,230.3 320.1,267.1 C341.6,303.9 330.9,280.7 349.0,313.0 C367.1,345.3 357.5,331.1 374.5,364.0 C391.5,396.9 384.7,378.7 400.0,411.6 C415.3,444.5 410.2,424.6 420.4,462.6 C430.6,500.6 429.5,492.6 430.6,525.5 C431.7,558.4 434.0,532.9 423.8,561.2 C413.6,589.5 422.1,579.9 400.0,610.5 C377.9,641.1 379.6,633.7 357.5,653.0 C335.4,672.3 352.4,658.1 333.7,668.3 C315.0,678.5 330.3,672.8 301.4,683.6 C272.5,694.4 279.3,691.0 247.0,700.6 C214.7,710.2 237.4,713.1 204.5,712.5 C171.6,711.9 179.6,725.5 148.4,698.9 C117.2,672.3 130.3,675.1 111.0,632.6 C91.7,590.1 99.7,600.3 90.6,571.4 C81.5,542.5 86.1,570.3 83.8,545.9 C81.5,521.5 86.1,535.1 83.8,498.3 C81.5,461.5 77.6,481.9 77.0,435.4 C76.4,388.9 85.5,394.0 82.1,358.9 C78.7,323.8 62.3,362.3 66.8,330.0 C71.3,297.7 92.3,307.3 95.7,262.0 C99.1,216.7 87.8,222.3 77.0,194.0 C66.2,165.7 49.2,205.3 63.4,177.0 C77.6,148.7 104.8,144.1 119.5,109.0 C134.2,73.9 97.4,90.3 107.6,71.6 C117.8,52.9 119.5,43.3 150.1,52.9 Z" fill="none" stroke="#fff" stroke-opacity=".045" stroke-width="40" stroke-linejoin="round" />
                            <path d="M150.1,52.9 C180.7,62.5 166.0,68.8 199.4,100.5 C232.8,132.2 222.1,114.1 250.4,148.1 C278.7,182.1 261.2,162.8 284.4,202.5 C307.6,242.2 298.6,230.3 320.1,267.1 C341.6,303.9 330.9,280.7 349.0,313.0 C367.1,345.3 357.5,331.1 374.5,364.0 C391.5,396.9 384.7,378.7 400.0,411.6 C415.3,444.5 410.2,424.6 420.4,462.6 C430.6,500.6 429.5,492.6 430.6,525.5 C431.7,558.4 434.0,532.9 423.8,561.2 C413.6,589.5 422.1,579.9 400.0,610.5 C377.9,641.1 379.6,633.7 357.5,653.0 C335.4,672.3 352.4,658.1 333.7,668.3 C315.0,678.5 330.3,672.8 301.4,683.6 C272.5,694.4 279.3,691.0 247.0,700.6 C214.7,710.2 237.4,713.1 204.5,712.5 C171.6,711.9 179.6,725.5 148.4,698.9 C117.2,672.3 130.3,675.1 111.0,632.6 C91.7,590.1 99.7,600.3 90.6,571.4 C81.5,542.5 86.1,570.3 83.8,545.9 C81.5,521.5 86.1,535.1 83.8,498.3 C81.5,461.5 77.6,481.9 77.0,435.4 C76.4,388.9 85.5,394.0 82.1,358.9 C78.7,323.8 62.3,362.3 66.8,330.0 C71.3,297.7 92.3,307.3 95.7,262.0 C99.1,216.7 87.8,222.3 77.0,194.0 C66.2,165.7 49.2,205.3 63.4,177.0 C77.6,148.7 104.8,144.1 119.5,109.0 C134.2,73.9 97.4,90.3 107.6,71.6 C117.8,52.9 119.5,43.3 150.1,52.9 Z" fill="none" stroke="#fff" stroke-opacity=".06" stroke-width="20" stroke-linejoin="round" />
                            <path d="M150.1,52.9 C180.7,62.5 166.0,68.8 199.4,100.5 C232.8,132.2 222.1,114.1 250.4,148.1 C278.7,182.1 261.2,162.8 284.4,202.5 C307.6,242.2 298.6,230.3 320.1,267.1 C341.6,303.9 330.9,280.7 349.0,313.0 C367.1,345.3 357.5,331.1 374.5,364.0 C391.5,396.9 384.7,378.7 400.0,411.6 C415.3,444.5 410.2,424.6 420.4,462.6 C430.6,500.6 429.5,492.6 430.6,525.5 C431.7,558.4 434.0,532.9 423.8,561.2 C413.6,589.5 422.1,579.9 400.0,610.5 C377.9,641.1 379.6,633.7 357.5,653.0 C335.4,672.3 352.4,658.1 333.7,668.3 C315.0,678.5 330.3,672.8 301.4,683.6 C272.5,694.4 279.3,691.0 247.0,700.6 C214.7,710.2 237.4,713.1 204.5,712.5 C171.6,711.9 179.6,725.5 148.4,698.9 C117.2,672.3 130.3,675.1 111.0,632.6 C91.7,590.1 99.7,600.3 90.6,571.4 C81.5,542.5 86.1,570.3 83.8,545.9 C81.5,521.5 86.1,535.1 83.8,498.3 C81.5,461.5 77.6,481.9 77.0,435.4 C76.4,388.9 85.5,394.0 82.1,358.9 C78.7,323.8 62.3,362.3 66.8,330.0 C71.3,297.7 92.3,307.3 95.7,262.0 C99.1,216.7 87.8,222.3 77.0,194.0 C66.2,165.7 49.2,205.3 63.4,177.0 C77.6,148.7 104.8,144.1 119.5,109.0 C134.2,73.9 97.4,90.3 107.6,71.6 C117.8,52.9 119.5,43.3 150.1,52.9 Z" fill="var(--land)" stroke="#fff" stroke-opacity=".35" stroke-width="1.2" stroke-linejoin="round" />
                            <path d="M150.1,52.9 C180.7,62.5 166.0,68.8 199.4,100.5 C232.8,132.2 222.1,114.1 250.4,148.1 C278.7,182.1 261.2,162.8 284.4,202.5 C307.6,242.2 298.6,230.3 320.1,267.1 C341.6,303.9 330.9,280.7 349.0,313.0 C367.1,345.3 357.5,331.1 374.5,364.0 C391.5,396.9 384.7,378.7 400.0,411.6 C415.3,444.5 410.2,424.6 420.4,462.6 C430.6,500.6 429.5,492.6 430.6,525.5 C431.7,558.4 434.0,532.9 423.8,561.2 C413.6,589.5 422.1,579.9 400.0,610.5 C377.9,641.1 379.6,633.7 357.5,653.0 C335.4,672.3 352.4,658.1 333.7,668.3 C315.0,678.5 330.3,672.8 301.4,683.6 C272.5,694.4 279.3,691.0 247.0,700.6 C214.7,710.2 237.4,713.1 204.5,712.5 C171.6,711.9 179.6,725.5 148.4,698.9 C117.2,672.3 130.3,675.1 111.0,632.6 C91.7,590.1 99.7,600.3 90.6,571.4 C81.5,542.5 86.1,570.3 83.8,545.9 C81.5,521.5 86.1,535.1 83.8,498.3 C81.5,461.5 77.6,481.9 77.0,435.4 C76.4,388.9 85.5,394.0 82.1,358.9 C78.7,323.8 62.3,362.3 66.8,330.0 C71.3,297.7 92.3,307.3 95.7,262.0 C99.1,216.7 87.8,222.3 77.0,194.0 C66.2,165.7 49.2,205.3 63.4,177.0 C77.6,148.7 104.8,144.1 119.5,109.0 C134.2,73.9 97.4,90.3 107.6,71.6 C117.8,52.9 119.5,43.3 150.1,52.9 Z" fill="url(#dots)" />

                            <path class="route r1" pathLength="1" d="M87.2,545.9 C91.2,542.5 100.8,532.0 111.0,525.5 C121.2,519.0 136.5,512.8 148.4,506.8 C160.3,500.9 170.8,493.5 182.4,489.8 C194.0,486.1 212.2,485.6 218.1,484.7" />
                            <path class="route r2" pathLength="1" d="M148.4,698.9 C143.9,691.2 128.8,667.7 121.2,653.0 C113.5,638.3 107.6,624.1 102.5,610.5 C97.4,596.9 93.1,582.2 90.6,571.4 C88.0,560.6 87.8,550.1 87.2,545.9" />
                            <path class="route r3" pathLength="1" d="M87.2,545.9 C87.8,541.9 90.9,530.0 90.6,522.1 C90.3,514.2 86.3,502.3 85.5,498.3" />
                            <path class="route r4" pathLength="1" d="M87.2,545.9 C92.6,538.2 105.0,515.9 119.5,500.0 C133.9,484.1 165.7,471.9 173.9,450.7 C182.1,429.4 168.0,395.7 168.8,372.5 C169.7,349.3 177.3,321.5 179.0,311.3" />
                            <path id="p-kandy" d="M218.1,484.7 C212.2,485.6 194.0,486.1 182.4,489.8 C170.8,493.5 160.3,500.9 148.4,506.8 C136.5,512.8 121.2,519.0 111.0,525.5 C100.8,532.0 91.2,542.5 87.2,545.9" fill="none" />
                            <path id="p-south" d="M148.4,698.9 C143.9,691.2 128.8,667.7 121.2,653.0 C113.5,638.3 107.6,624.1 102.5,610.5 C97.4,596.9 93.1,582.2 90.6,571.4 C88.0,560.6 87.8,550.1 87.2,545.9" fill="none" />
                            <path id="p-negombo" d="M85.5,498.3 C86.3,502.3 90.3,514.2 90.6,522.1 C90.9,530.0 87.8,541.9 87.2,545.9" fill="none" />
                            <path id="p-north" d="M87.2,545.9 C92.6,538.2 105.0,515.9 119.5,500.0 C133.9,484.1 165.7,471.9 173.9,450.7 C182.1,429.4 168.0,395.7 168.8,372.5 C169.7,349.3 177.3,321.5 179.0,311.3" fill="none" />

                            <circle class="city-dot" cx="87.2" cy="545.9" r="2.6" /><text class="city-name" x="78.2" y="549.9" text-anchor="end">Colombo</text>
                            <circle class="city-dot" cx="218.1" cy="484.7" r="2.6" /><text class="city-name" x="227.1" y="488.7" text-anchor="start">Kandy</text>
                            <circle class="city-dot" cx="148.4" cy="698.9" r="2.6" /><text class="city-name" x="157.4" y="702.9" text-anchor="start">Galle</text>
                            <circle class="city-dot" cx="112.7" cy="81.8" r="2.6" /><text class="city-name" x="121.7" y="85.8" text-anchor="start">Jaffna</text>
                            <circle class="city-dot" cx="320.1" cy="267.1" r="2.6" /><text class="city-name" x="329.1" y="271.1" text-anchor="start">Trincomalee</text>
                            <circle class="city-dot" cx="179.0" cy="311.3" r="2.6" /><text class="city-name" x="188.0" y="315.3" text-anchor="start">Anuradhapura</text>
                            <circle class="city-dot" cx="400.0" cy="411.6" r="2.6" /><text class="city-name" x="409.0" y="415.6" text-anchor="start">Batticaloa</text>

                            {{-- Offline vehicle: blue icon, parked --}}
                            <image href="/vehicle-icons/motorcycle-blue.png" width="20" height="50" x="356" y="388" />

                            {{-- Online vehicles: green icons driving the routes. Icons point up, so rotate 90deg to face the direction of travel. --}}
                            <g opacity="0">
                                <animateMotion dur="15s" begin="3.2s" repeatCount="indefinite" rotate="auto">
                                    <mpath href="#p-kandy" />
                                </animateMotion>
                                <animate attributeName="opacity" values="0;1;1;0" keyTimes="0;.07;.93;1" dur="15s" begin="3.2s" repeatCount="indefinite" />
                                <image href="/vehicle-icons/suv-green.png" width="27" height="56" x="-13.5" y="-28.0" transform="rotate(90)" />
                            </g>
                            <g opacity="0">
                                <animateMotion dur="13s" begin="5.5s" repeatCount="indefinite" rotate="auto">
                                    <mpath href="#p-south" />
                                </animateMotion>
                                <animate attributeName="opacity" values="0;1;1;0" keyTimes="0;.07;.93;1" dur="13s" begin="5.5s" repeatCount="indefinite" />
                                <image href="/vehicle-icons/car-green.png" width="28" height="56" x="-14.0" y="-28.0" transform="rotate(90)" />
                            </g>
                            <g opacity="0">
                                <animateMotion dur="7s" begin="4.0s" repeatCount="indefinite" rotate="auto">
                                    <mpath href="#p-negombo" />
                                </animateMotion>
                                <animate attributeName="opacity" values="0;1;1;0" keyTimes="0;.07;.93;1" dur="7s" begin="4.0s" repeatCount="indefinite" />
                                <image href="/vehicle-icons/three-wheeler-green.png" width="33" height="56" x="-16.5" y="-28.0" transform="rotate(90)" />
                            </g>
                            <g opacity="0">
                                <animateMotion dur="17s" begin="2.6s" repeatCount="indefinite" rotate="auto">
                                    <mpath href="#p-north" />
                                </animateMotion>
                                <animate attributeName="opacity" values="0;1;1;0" keyTimes="0;.07;.93;1" dur="17s" begin="2.6s" repeatCount="indefinite" />
                                <image href="/vehicle-icons/truck-green.png" width="20" height="64" x="-10.0" y="-32.0" transform="rotate(90)" />
                            </g>
                        </svg>

                        <div class="legend" aria-hidden="true">
                            <span><i style="background:var(--online)"></i>Online</span>
                            <span><i style="background:#3B82F6"></i>Offline</span>
                        </div>

                        <div class="panel" aria-label="Sample vehicle">
                            <div class="panel-top">
                                <span class="plate">WP CAB-5678</span>
                                <span class="sample">Sample</span>
                            </div>
                            <p class="panel-model">Honda Fit</p>
                            <div class="panel-row">
                                <span class="speed"><span id="speed">62</span><small>km/h</small></span>
                                <span class="status"><i></i>Ignition on</span>
                            </div>
                            <div class="tick">
                                <div class="tick-bar"><b id="tick-bar"></b></div>
                                <div class="tick-text">Updated <span id="tick-s">0</span> s ago. New fix every 20 s.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- FEATURES --}}
        <section id="features" class="section features" aria-labelledby="features-title">
            <div class="wrap">
                <div class="features-grid">
                    <div class="features-head">
                        <h2 id="features-title" class="display h2">Everything about a vehicle, in one place.</h2>
                        <p class="lede">For owners, managers and drivers who need to know where a vehicle is without calling anyone.</p>
                    </div>
                    <ul class="feature-list">
                        <li class="feature">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                <circle class="hot" cx="12" cy="11" r="1.4" />
                            </svg>
                            <div>
                                <h3>Live map</h3>
                                <p>Watch vehicles move on the map. Speed, heading and ignition status refresh every 20 seconds.</p>
                            </div>
                        </li>
                        <li class="feature">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                <path class="hot" d="M19 4l1.5-1.5M20.5 7.5H22" />
                            </svg>
                            <div>
                                <h3>Alerts on your phone</h3>
                                <p>Overspeed, geofence entry and exit, ignition on and off, power cut and low battery reach the ShaloTrack app as they happen.</p>
                            </div>
                        </li>
                        <li class="feature">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                <path class="hot" d="M12 3v1.5" />
                            </svg>
                            <div>
                                <h3>Trip history</h3>
                                <p>Replay any journey with a scrubber: the route, every stop and the speed at each point.</p>
                            </div>
                        </li>
                        <li class="feature">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <circle class="hot" cx="12" cy="11" r="3" />
                            </svg>
                            <div>
                                <h3>Geofences</h3>
                                <p>Draw a zone around a depot, a client site or a no-go area. Know when a vehicle enters or leaves.</p>
                            </div>
                        </li>
                        <li class="feature">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                            </svg>
                            <div>
                                <h3>Sharing</h3>
                                <p>Give a manager, driver or client access to chosen vehicles only. Or send a live link that stops working when you say so.</p>
                            </div>
                        </li>
                        <li class="feature">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 2h8l2-2h2a1 1 0 000-2h-1" />
                                <path class="hot" d="M14 8h3l3 4" />
                            </svg>
                            <div>
                                <h3>Fleet management</h3>
                                <p>Add vehicles, link GPS devices by IMEI and get a reminder before a licence or insurance runs out.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        {{-- HOW IT WORKS: a real sequence, drawn as stops on a route --}}
        <section id="how-it-works" class="section" aria-labelledby="how-title">
            <div class="wrap">
                <div class="steps-head">
                    <h2 id="how-title" class="display h2">Three steps to your first vehicle on the map.</h2>
                    <p class="lede">No password to remember and nothing to install on the vehicle except the device.</p>
                </div>
                <ol class="stops">
                    <li class="stop">
                        <span class="stop-n">1</span>
                        <h3>Sign in</h3>
                        <p>Enter your phone number and the one-time code we send you. Your account is ready.</p>
                    </li>
                    <li class="stop">
                        <span class="stop-n">2</span>
                        <h3>Add a vehicle</h3>
                        <p>Enter the vehicle details and link your ShaloTrack GPS device with its IMEI number.</p>
                    </li>
                    <li class="stop">
                        <span class="stop-n">3</span>
                        <h3>Watch it move</h3>
                        <p>Open the dashboard. Your vehicle appears on the map. Add geofences and alerts when you are ready.</p>
                    </li>
                </ol>
            </div>
        </section>

        {{-- YOUR DATA --}}
        <section id="your-data" class="section data" aria-labelledby="data-title">
            <div class="wrap">
                <div class="data-grid">
                    <h2 id="data-title" class="display h2">Your vehicles. Your data. Your call.</h2>
                    <div class="data-list">
                        <div class="data-item">
                            <h3>Sign in with a code, not a password</h3>
                            <p>A one-time code goes to your phone. Nothing to reuse, leak or forget.</p>
                        </div>
                        <div class="data-item">
                            <h3>Share only what you choose</h3>
                            <p>Each person sees only the vehicles you give them, and you can take access back at any time.</p>
                        </div>
                        <div class="data-item">
                            <h3>Take your data or delete it</h3>
                            <p>Download a copy of your data or delete your account from your profile page.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section class="section cta" aria-labelledby="cta-title">
            <div class="wrap">
                <div>
                    <h2 id="cta-title" class="display">Your vehicles are moving. Open the map.</h2>
                    <p>Sign in with your phone number. No password needed.</p>
                </div>
                <a href="/login" class="btn">Sign in or register</a>
            </div>
        </section>
    </main>

    <footer>
        <div class="wrap">
            <span>© {{ date('Y') }} ShaloTrack Lanka (Pvt) Ltd</span>
            <div class="links">
                <a href="/login">Sign in</a>
                <a href="#features">Features</a>
                <a href="#how-it-works">How it works</a>
            </div>
        </div>
    </footer>

    <script>
        (function() {
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // Vehicles: freeze on a pleasant frame when the visitor prefers less motion.
            const svg = document.getElementById('map');
            if (reduce && svg && svg.pauseAnimations) {
                try {
                    svg.setCurrentTime(5);
                    svg.pauseAnimations();
                } catch (_) {
                    /* decoration only */ }
            }

            // Sample panel: speed wanders, and the 20-second update clock counts up.
            const speeds = [62, 58, 47, 33, 41, 55, 66, 61];
            let si = 0,
                s = 0;
            const speedEl = document.getElementById('speed');
            const sEl = document.getElementById('tick-s');
            const bar = document.getElementById('tick-bar');

            function paintTick() {
                sEl.textContent = String(s);
                bar.style.width = (s / 20 * 100) + '%';
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