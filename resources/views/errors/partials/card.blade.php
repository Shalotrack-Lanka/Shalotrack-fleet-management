@php
/*
|--------------------------------------------------------------------------
| Error card — ShaloTrack Fleet
|--------------------------------------------------------------------------
| Variables injected by bootstrap/app.php withExceptions() render():
|
| $referenceId — support ref (logged to app_errors channel with full context)
| $statusCode — HTTP status integer
| $technicalCategory — coarse category string from ErrorClassifier::classify()
| $technicalSummary — human-readable one-liner from ErrorClassifier::classify()
|
| Variables from individual error blades (e.g. errors/404.blade.php):
| $statusCode — passed explicitly so the card works even when $exception is absent
| $title — short human-readable title (overrides default for this code)
| $message — friendly explanation (overrides default for this code)
| $icon — optional SVG <path> d="..." override
    |
    | NOTE: $technicalMessage and $technicalFile are NOT displayed — they may
    | contain raw SQL, internal paths, or stack details. They stay in the log only.
    */

    // -------------------------------------------------------------------------
    // Status code
    // -------------------------------------------------------------------------
    $code = $statusCode
    ?? (isset($exception) && method_exists($exception, 'getStatusCode')
    ? $exception->getStatusCode()
    : 500);

    // -------------------------------------------------------------------------
    // Reference ID — from app's error pipeline (already logged server-side).
    // Fall back to a locally generated one only when rendered outside the pipeline
    // (e.g. artisan tinker, unit tests).
    // -------------------------------------------------------------------------
    $ref = $referenceId
    ?? strtoupper(date('Ymd') . '-' . substr(md5(microtime(true) . rand()), 0, 8));

    // -------------------------------------------------------------------------
    // Technical label shown to user — safe, pre-classified string.
    // Use $technicalSummary when available (from ErrorClassifier), otherwise
    // derive a safe label from the status code alone.
    // -------------------------------------------------------------------------
    $techLabel = $technicalSummary ?? match(true) {
    $code === 401 => 'Authentication failed',
    $code === 403 => 'Authorisation denied',
    $code === 404 => 'Route not found',
    $code === 419 => 'CSRF token mismatch',
    $code === 429 => 'Rate limit exceeded',
    $code === 503 => 'Maintenance mode active',
    $code >= 500 => 'Internal server error',
    default => 'HTTP ' . $code,
    };

    // -------------------------------------------------------------------------
    // Per-code config — accent colour, background, SVG icon path, fallback text
    // -------------------------------------------------------------------------
    $cfg = [
    401 => [
    'accent' => '#FA6908',
    'bg' => '#FFF7F0',
    'icon' => 'M12 1C8.676 1 6 3.676 6 7v1H4a1 1 0 00-1 1v12a1 1 0 001 1h16a1 1 0 001-1V9a1 1 0 00-1-1h-2V7c0-3.324-2.676-6-6-6zm0 2c2.276 0 4 1.724 4 4v1H8V7c0-2.276 1.724-4 4-4zm0 9a2 2 0 110 4 2 2 0 010-4z',
    'dtitle' => 'Session expired',
    'dmsg' => 'Your session is no longer active — this usually happens after a period of inactivity. Please log in again to continue where you left off.',
    ],
    403 => [
    'accent' => '#DC2626',
    'bg' => '#FFF5F5',
    'icon' => 'M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-1 6h2v2h-2V7zm0 4h2v6h-2v-6z',
    'dtitle' => 'Access denied',
    'dmsg' => 'You don\'t have permission to view this page. If you believe you should have access, contact your fleet administrator and quote the reference ID below.',
    ],
    404 => [
    'accent' => '#6366F1',
    'bg' => '#F5F5FF',
    'icon' => 'M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z',
    'dtitle' => 'Page not found',
    'dmsg' => 'The page you\'re looking for doesn\'t exist or may have been moved. Double-check the URL, or head back to the dashboard to navigate from there.',
    ],
    419 => [
    'accent' => '#D97706',
    'bg' => '#FFFBEB',
    'icon' => 'M12 2a10 10 0 100 20A10 10 0 0012 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z',
    'dtitle' => 'Session expired',
    'dmsg' => 'Your session security token has expired — this usually happens after leaving a page open for too long. Click Go back and try your action again; it should work immediately.',
    ],
    429 => [
    'accent' => '#D97706',
    'bg' => '#FFFBEB',
    'icon' => 'M13 2.05v2.02c3.95.49 7 3.85 7 7.93 0 3.21-1.81 6-4.72 7.28L13 17v5h5l-1.22-1.22C19.91 19.07 22 15.76 22 12c0-5.18-3.95-9.45-9-9.95zM11 2.05C5.95 2.55 2 6.82 2 12c0 3.76 2.09 7.07 5.22 8.78L6 22h5v-5l-2.28 2.28C6.81 18 5 15.21 5 12c0-4.08 3.05-7.44 7-7.93V2.05z',
    'dtitle' => 'Too many requests',
    'dmsg' => 'You\'ve sent too many requests in a short time and have been temporarily rate-limited. Wait a minute or two, then try again.',
    ],
    500 => [
    'accent' => '#DC2626',
    'bg' => '#FFF5F5',
    'icon' => 'M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z',
    'dtitle' => 'Something went wrong',
    'dmsg' => 'An unexpected error occurred on our end. Our engineering team has been notified. If you need this resolved urgently, call us and quote the reference details below.',
    ],
    503 => [
    'accent' => '#0EA5E9',
    'bg' => '#F0F9FF',
    'icon' => 'M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z',
    'dtitle' => 'Under maintenance',
    'dmsg' => 'ShaloTrack Fleet is currently undergoing scheduled maintenance. We\'ll be back online shortly. No data has been affected — vehicles are still being tracked in the background.',
    ],
    ];

    $c = $cfg[$code] ?? [
    'accent' => '#021F4A',
    'bg' => '#F8FAFC',
    'icon' => 'M12 2a10 10 0 100 20A10 10 0 0012 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z',
    'dtitle' => 'Unexpected error',
    'dmsg' => 'Something unexpected happened. Our engineering team has been notified.',
    ];

    $accent = $c['accent'];
    $bg = $c['bg'];
    $svgPath = $icon ?? $c['icon'];
    $displayTitle = $title ?? $c['dtitle'];
    $displayMessage = $message ?? $c['dmsg'];
    @endphp
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $code }} {{ $displayTitle }} — ShaloTrack Fleet</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
                background: #0f172a;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 24px;
            }

            .card {
                background: #ffffff;
                border-radius: 20px;
                max-width: 560px;
                width: 100%;
                overflow: hidden;
                box-shadow: 0 24px 64px rgba(0, 0, 0, 0.4);
            }

            /* ── Coloured top band ── */
            .band {
                background: {
                        {
                        $bg
                    }
                }

                ;
                border-bottom: 1px solid rgba(0, 0, 0, 0.06);
                padding: 36px 40px 28px;
                display: flex;
                align-items: flex-start;
                gap: 20px;
            }

            .icon-wrap {
                width: 52px;
                height: 52px;

                background: {
                        {
                        $accent
                    }
                }

                22;
                border-radius: 14px;
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .icon-wrap svg {
                width: 26px;
                height: 26px;

                fill: {
                        {
                        $accent
                    }
                }

                ;
            }

            .band-text {
                flex: 1;
            }

            .code-label {
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 1.5px;
                text-transform: uppercase;

                color: {
                        {
                        $accent
                    }
                }

                ;
                margin-bottom: 4px;
            }

            .error-title {
                font-size: 22px;
                font-weight: 800;
                color: #021F4A;
                line-height: 1.2;
            }

            /* ── Body ── */
            .body {
                padding: 24px 40px 32px;
            }

            .message {
                color: #4B5563;
                font-size: 14px;
                line-height: 1.7;
                margin-bottom: 20px;
            }

            /* ── Technical details box ── */
            .tech-box {
                background: #F8FAFC;
                border: 1px solid #E5E7EB;

                border-left: 4px solid {
                        {
                        $accent
                    }
                }

                ;
                border-radius: 8px;
                padding: 14px 16px;
                margin-bottom: 20px;
            }

            .tech-box-label {
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 1.2px;
                text-transform: uppercase;
                color: #9CA3AF;
                margin-bottom: 10px;
            }

            .tech-row {
                display: flex;
                align-items: baseline;
                gap: 10px;
                margin-bottom: 6px;
            }

            .tech-row:last-child {
                margin-bottom: 0;
            }

            .tech-key {
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 0.8px;
                text-transform: uppercase;
                color: #9CA3AF;
                width: 52px;
                flex-shrink: 0;
            }

            .tech-val {
                font-family: 'Courier New', monospace;
                font-size: 13px;
                font-weight: 700;
                color: #021F4A;
                word-break: break-all;
                letter-spacing: 0.5px;
            }

            .tech-val.ref-val {
                font-size: 15px;
                letter-spacing: 1.5px;

                color: {
                        {
                        $accent
                    }
                }

                ;
            }

            /* ── Copy hint ── */
            .copy-hint {
                margin-top: 10px;
                padding-top: 10px;
                border-top: 1px solid #E5E7EB;
                font-size: 11px;
                color: #9CA3AF;
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .btn-copy {
                background: none;
                border: 1px solid #D1D5DB;
                border-radius: 5px;
                padding: 3px 8px;
                font-size: 11px;
                font-weight: 700;
                color: #374151;
                cursor: pointer;
                transition: background 0.15s;
            }

            .btn-copy:hover {
                background: #F3F4F6;
            }

            /* ── Actions ── */
            .actions {
                display: flex;
                gap: 10px;
            }

            .btn {
                flex: 1;
                display: inline-block;
                padding: 11px 16px;
                border-radius: 10px;
                font-size: 13px;
                font-weight: 700;
                text-align: center;
                text-decoration: none;
                transition: opacity 0.15s, transform 0.1s;
                cursor: pointer;
                border: none;
            }

            .btn:hover {
                opacity: 0.88;
            }

            .btn:active {
                transform: scale(0.98);
            }

            .btn-primary {
                background: #021F4A;
                color: #ffffff;
            }

            .btn-secondary {
                background: #F3F4F6;
                color: #374151;
            }

            /* ── Support footer ── */
            .support {
                border-top: 1px solid #F3F4F6;
                padding: 16px 40px;
                font-size: 12px;
                color: #9CA3AF;
                text-align: center;
            }

            .support a {
                color: #FA6908;
                text-decoration: none;
                font-weight: 700;
            }
        </style>
    </head>

    <body>
        <div class="card">

            {{-- Coloured top band --}}
            <div class="band">
                <div class="icon-wrap">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="{{ $svgPath }}" />
                    </svg>
                </div>
                <div class="band-text">
                    <div class="code-label">Error {{ $code }}</div>
                    <div class="error-title">{{ $displayTitle }}</div>
                </div>
            </div>

            {{-- Body --}}
            <div class="body">
                <p class="message">{{ $displayMessage }}</p>

                {{-- Technical details — customer reads these out to support --}}
                <div class="tech-box">
                    <div class="tech-box-label">Technical details — read these out when you call us</div>

                    <div class="tech-row">
                        <span class="tech-key">Code</span>
                        <span class="tech-val">HTTP {{ $code }}</span>
                    </div>

                    <div class="tech-row">
                        <span class="tech-key">Type</span>
                        <span class="tech-val">{{ $techLabel }}</span>
                    </div>

                    <div class="tech-row">
                        <span class="tech-key">Ref</span>
                        <span class="tech-val ref-val" id="ref-id">{{ $ref }}</span>
                    </div>

                    <div class="copy-hint">
                        <span>Tap to copy all details</span>
                        <button class="btn-copy" onclick="copyDetails()" id="copy-btn">Copy</button>
                    </div>
                </div>

                {{-- Action buttons --}}
                <div class="actions">
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary">Back to dashboard</a>
                    <a href="javascript:history.back()" class="btn btn-secondary">Go back</a>
                </div>
            </div>

            {{-- Support footer --}}
            <div class="support">
                Still stuck? Call us with the details above — <a href="tel:+94000000000">+94 00 000 0000</a>
            </div>

        </div>
        <script>
            function copyDetails() {
                var text = [
                    'Error Code: HTTP {{ $code }}',
                    'Type: {{ $techLabel }}',
                    'Ref: {{ $ref }}',
                    'URL: ' + window.location.href,
                ].join('\n');

                navigator.clipboard.writeText(text).then(function() {
                    var btn = document.getElementById('copy-btn');
                    btn.textContent = 'Copied!';
                    setTimeout(function() {
                        btn.textContent = 'Copy';
                    }, 2500);
                }).catch(function() {
                    var el = document.getElementById('ref-id');
                    var range = document.createRange();
                    range.selectNode(el);
                    window.getSelection().removeAllRanges();
                    window.getSelection().addRange(range);
                });
            }
        </script>
    </body>

    </html>