{{-- Dark-glass skin for every logged-in page except the (already glass) dashboard.
     Pages keep their markup: their light colours were turned into CSS variables
     (var(--g-xxx, <old colour>)) and this file gives those variables dark-glass values.
     Tailwind utility classes are re-mapped below. Scoped to body.st-bg so nothing else changes. --}}
<style>
    body.st-bg {
        color-scheme: dark;
        --g-s1: linear-gradient(135deg, rgba(255,255,255,.12), rgba(255,255,255,.03)), rgba(7,34,82,.82);
        --g-s1c: rgba(7,34,82,.82);
        --g-s2: rgba(255,255,255,.06);   --g-s2c: rgba(255,255,255,.06);
        --g-s3: rgba(255,255,255,.13);   --g-s3c: rgba(255,255,255,.13);
        --g-navy: rgba(255,255,255,.14); --g-navyc: rgba(255,255,255,.14);
        --g-redbg: rgba(239,68,68,.20);  --g-redbgc: rgba(239,68,68,.20);
        --g-orbg: rgba(250,105,8,.18);   --g-orbgc: rgba(250,105,8,.18);
        --g-grbg: rgba(52,211,153,.17);  --g-grbgc: rgba(52,211,153,.17);
        --g-blbg: rgba(96,165,250,.18);  --g-blbgc: rgba(96,165,250,.18);
        --g-ambg: rgba(245,158,11,.18);  --g-ambgc: rgba(245,158,11,.18);
        --g-t1: #f1f5f9; --g-t2: rgba(226,232,240,.80); --g-t3: rgba(203,213,225,.58);
        --g-redt: #fca5a5; --g-grt: #86efac; --g-blt: #93c5fd; --g-amt: #fcd34d;
        --g-b: rgba(255,255,255,.16); --g-redb: rgba(248,113,113,.45); --g-orb: rgba(250,105,8,.5);
        --g-amb: rgba(245,158,11,.45); --g-grb: rgba(52,211,153,.45); --g-blb: rgba(96,165,250,.45);
        --navy: #e2e8f0;           /* pages use var(--navy) for text; their navy fills were mapped to --g-navy above */
        --navy-mid: rgba(255,255,255,.2);
        color: var(--g-t1);
    }
    /* Google info bubbles stay white, so their text must stay dark. */
    .gm-style .gm-style-iw-c, .gm-style .gm-style-iw-d {
        --g-t1: #1f2937; --g-t2: #4b5563; --g-t3: #9ca3af; --g-redt: #dc2626; --g-grt: #16a34a; --g-blt: #0369a1; --g-amt: #c2410c;
        --g-s1: #fff; --g-s1c: #fff; --g-s2: #f8fafc; --g-s2c: #f8fafc; --g-s3: #f1f5f9; --g-s3c: #f1f5f9; --g-b: #e5e7eb; --navy: #021F4A;
    }

    /* ── Tailwind utilities, re-mapped ───────────────────────── */
    .st-bg .bg-white { background: var(--g-s1); }
    .st-bg .bg-gray-50, .st-bg .hover\:bg-gray-50:hover, .st-bg .hover\:bg-gray-50\/50:hover { background: var(--g-s2); }
    .st-bg .bg-gray-100, .st-bg .hover\:bg-gray-100:hover, .st-bg .hover\:bg-gray-200:hover { background: var(--g-s3); }
    .st-bg .bg-gray-300, .st-bg .bg-gray-400 { background: rgba(255,255,255,.3); }
    .st-bg .bg-red-50, .st-bg .hover\:bg-red-50:hover { background: var(--g-redbg); }
    .st-bg .bg-green-50, .st-bg .hover\:bg-green-100:hover { background: var(--g-grbg); }
    .st-bg .bg-blue-50, .st-bg .hover\:bg-blue-50:hover { background: var(--g-blbg); }
    .st-bg .bg-amber-50 { background: var(--g-ambg); }
    .st-bg .bg-orange-50, .st-bg .hover\:bg-orange-50:hover, .st-bg .bg-orange-50\/30 { background: var(--g-orbg); }
    .st-bg .bg-purple-50 { background: rgba(168,85,247,.18); }
    .st-bg .border-gray-50, .st-bg .border-gray-100, .st-bg .border-gray-200, .st-bg .border-gray-300 { border-color: var(--g-b); }
    .st-bg .divide-gray-50 > :not([hidden]) ~ :not([hidden]) { border-color: var(--g-b); }
    .st-bg .border-red-100, .st-bg .border-red-200, .st-bg .border-red-300, .st-bg .hover\:border-red-200:hover { border-color: var(--g-redb); }
    .st-bg .border-green-200 { border-color: var(--g-grb); }
    .st-bg .border-blue-100, .st-bg .border-blue-200 { border-color: var(--g-blb); }
    .st-bg .border-amber-200 { border-color: var(--g-amb); }
    .st-bg .border-orange-200, .st-bg .hover\:border-orange-200:hover { border-color: var(--g-orb); }
    .st-bg .border-purple-200 { border-color: rgba(168,85,247,.45); }
    .st-bg .text-gray-800, .st-bg .text-gray-700, .st-bg .text-\[\#021F4A\], .st-bg .text-blue-900 { color: var(--g-t1); }
    .st-bg .text-gray-600, .st-bg .text-gray-500, .st-bg .hover\:text-gray-600:hover { color: var(--g-t2); }
    .st-bg .text-gray-400, .st-bg .text-gray-300, .st-bg .text-gray-200 { color: var(--g-t3); }
    .st-bg .text-red-500, .st-bg .text-red-600, .st-bg .text-red-700, .st-bg .hover\:text-red-500:hover { color: var(--g-redt); }
    .st-bg .text-green-500, .st-bg .text-green-600, .st-bg .text-green-700 { color: var(--g-grt); }
    .st-bg .text-blue-500, .st-bg .text-blue-600, .st-bg .text-blue-700 { color: var(--g-blt); }
    .st-bg .text-amber-500, .st-bg .text-amber-600, .st-bg .text-amber-700 { color: var(--g-amt); }
    .st-bg .text-purple-700 { color: #d8b4fe; }
    .st-bg .text-gray-900, .st-bg h1, .st-bg h2, .st-bg h3 { color: var(--g-t1); }
    .st-bg ::placeholder { color: var(--g-t3); opacity: 1; }

    /* Form fields: glass, light text. (Page classes with their own colours were already variable-ised.) */
    .st-bg input:not([type="checkbox"]):not([type="radio"]):not([type="range"]):not([type="file"]):not([type="color"]),
    .st-bg select, .st-bg textarea { color: var(--g-t1); }
    .st-bg input:not([type="checkbox"]):not([type="radio"]):not([type="range"]):not([type="file"]):not([type="color"]):not([class*="bg-"]):not([style*="background"]),
    .st-bg select:not([class*="bg-"]), .st-bg textarea:not([class*="bg-"]) { background-color: var(--g-s2c); border-color: var(--g-b); }
    .st-bg select option { background: #0a2a5e; color: #f1f5f9; }

    /* Dark-navy icons and strokes drawn with presentation attributes */
    .st-bg svg[stroke="#021F4A"], .st-bg svg [stroke="#021F4A"], .st-bg svg[stroke="#1f2937"], .st-bg svg [stroke="#1f2937"] { stroke: #e2e8f0; }
    .st-bg svg[fill="#021F4A"], .st-bg svg [fill="#021F4A"] { fill: #e2e8f0; }

    /* Soft depth on cards */
    .st-bg .rounded-xl.bg-white, .st-bg .rounded-2xl.bg-white, .st-bg .rounded-lg.bg-white { box-shadow: 0 10px 30px rgba(0,8,30,.35), inset 0 1px 0 rgba(255,255,255,.22); }

    /* Dialog panels need to stay legible over the page behind them */
    .st-bg [role="dialog"]:not([class*="overlay"]):not([class*="wrap"]) { background-color: rgba(6,30,74,.97); }
</style>