<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Login — ShaloTrack Fleet</title>
    @vite(['resources/css/app.css'])

    {{-- Firebase JS SDK (loaded via CDN — no build step needed) --}}
    <script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-auth-compat.js"></script>
</head>

<body class="min-h-screen flex items-center justify-center bg-[#021F4A] px-4 py-8">

    <div class="w-full max-w-md">

        {{-- Logo --}}
        <div class="text-center mb-6 sm:mb-10">
            <h1 class="text-3xl font-bold text-white tracking-tight">
                Shalo<span class="text-[#FA6908]">Track</span>
            </h1>
            <p class="text-blue-200 text-sm mt-2">Fleet Management Portal</p>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-8">

            {{-- Session expired banner --}}
            @if($expired)
            <div class="mb-6 p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-700 text-sm text-center">
                Your session expired. Please log in again.
            </div>
            @endif

            {{-- Laravel validation errors --}}
            @if($errors->any())
            <div class="mb-6 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm text-center">
                {{ $errors->first() }}
            </div>
            @endif

            {{-- Sign-in method: email + password (no SMS) or phone code --}}
            <div id="login-tabs" class="grid grid-cols-2 gap-1 p-1 mb-6 bg-gray-100 rounded-lg text-sm font-medium">
                <button type="button" id="tab-email" onclick="showMethod('email')" class="py-2 rounded-md transition">Email &amp; password</button>
                <button type="button" id="tab-phone" onclick="showMethod('phone')" class="py-2 rounded-md transition">Phone code</button>
            </div>

            {{-- Step 1: Phone number input --}}
            @php $isNew = request()->query('new') === '1'; /* the landing page's "Get started" button */ @endphp
            <div id="step-phone">
                <h2 class="text-xl font-semibold text-gray-800 mb-1">{{ $isNew ? 'Create your account' : 'Welcome back' }}</h2>
                <p class="text-gray-500 text-sm mb-6">{{ $isNew ? "Enter your phone number. We'll verify it by SMS, then set up your account." : 'Enter your phone number to continue.' }}</p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <div class="flex rounded-lg border border-gray-300 overflow-hidden focus-within:ring-2 focus-within:ring-[#FA6908] focus-within:border-transparent transition">
                            <span class="px-3 py-2.5 bg-gray-50 text-gray-500 text-sm border-r border-gray-300">
                                🇱🇰 +94
                            </span>
                            <input
                                type="tel"
                                id="phone-input"
                                placeholder="71 234 5678" inputmode="numeric" autocomplete="tel-national"
                                class="flex-1 min-w-0 px-3 py-2.5 text-base sm:text-sm outline-none bg-white"
                                autofocus />
                        </div>
                    </div>

                    <div id="phone-error" role="alert" aria-live="assertive" class="hidden"></div>

                    <button
                        id="send-otp-btn"
                        onclick="sendOtp()"
                        class="w-full py-2.5 px-4 bg-[#FA6908] hover:bg-orange-600 disabled:bg-orange-300 text-white text-sm font-semibold rounded-lg transition">
                        Send Verification Code
                    </button>

                    @unless($isNew)
                    <p class="text-center text-xs text-gray-400">New to ShaloTrack? Just enter your number: we'll set up your account after you verify.</p>
                    @endunless
                </div>
            </div>

            {{-- Step 2: OTP input (hidden initially) --}}
            <div id="step-otp" class="hidden">
                <h2 class="text-xl font-semibold text-gray-800 mb-1">Enter verification code</h2>
                <p id="otp-subtitle" class="text-gray-500 text-sm mb-6"></p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">6-digit Code</label>
                        <input
                            type="text"
                            id="otp-input"
                            inputmode="numeric"
                            maxlength="6"
                            placeholder="______"
                            class="w-full px-4 py-2.5 text-center text-2xl tracking-[0.5em] border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent transition" />
                    </div>

                    <div id="otp-error" role="alert" aria-live="assertive" class="hidden"></div>

                    <button
                        id="verify-otp-btn"
                        onclick="verifyOtp()"
                        class="w-full py-2.5 px-4 bg-[#FA6908] hover:bg-orange-600 disabled:bg-orange-300 text-white text-sm font-semibold rounded-lg transition">
                        Verify &amp; Sign In
                    </button>

                    <button
                        type="button"
                        onclick="goBack()"
                        class="w-full py-2 text-sm text-gray-500 hover:text-gray-700 transition">
                        ← Use a different number
                    </button>
                </div>
            </div>


            {{-- Email + password sign-in (no SMS cost) --}}
            <div id="step-email" class="hidden">
                <h2 class="text-xl font-semibold text-gray-800 mb-1">Sign in with email</h2>
                <p class="text-gray-500 text-sm mb-6">Use your email and password.</p>
                <form id="email-form" class="space-y-4" onsubmit="return emailSignIn(event)" novalidate>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="email-input">Email</label>
                        <input type="email" id="email-input" autocomplete="username" inputmode="email"
                            class="w-full px-3 py-2.5 text-base sm:text-sm border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent transition" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="password-input">Password</label>
                        <input type="password" id="password-input" autocomplete="current-password"
                            class="w-full px-3 py-2.5 text-base sm:text-sm border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent transition" />
                    </div>
                    <div id="email-error" role="alert" aria-live="assertive" class="hidden"></div>
                    <button type="submit" id="email-signin-btn"
                        class="w-full py-2.5 px-4 bg-[#FA6908] hover:bg-orange-600 disabled:bg-orange-300 text-white text-sm font-semibold rounded-lg transition">
                        Sign In
                    </button>
                    <button type="button" onclick="showForgot()" class="w-full py-1 text-sm text-gray-500 hover:text-gray-700 transition">Forgot password?</button>
                    <p class="text-center text-xs text-gray-400">No password yet? Use <button type="button" onclick="showMethod('phone')" class="underline">Phone code</button> once, then you can set one.</p>
                </form>
            </div>

            {{-- Forgot password --}}
            <div id="step-forgot" class="hidden">
                <h2 class="text-xl font-semibold text-gray-800 mb-1">Reset your password</h2>
                <p class="text-gray-500 text-sm mb-6">Enter your email and we will send you a reset link.</p>
                <div class="space-y-4">
                    <input type="email" id="forgot-input" autocomplete="email" inputmode="email" placeholder="you@example.com"
                        class="w-full px-3 py-2.5 text-base sm:text-sm border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent transition" />
                    <p id="forgot-msg" class="text-sm hidden"></p>
                    <button type="button" id="forgot-btn" onclick="sendReset()"
                        class="w-full py-2.5 px-4 bg-[#FA6908] hover:bg-orange-600 disabled:bg-orange-300 text-white text-sm font-semibold rounded-lg transition">
                        Send reset link
                    </button>
                    <button type="button" onclick="showMethod('email')" class="w-full py-2 text-sm text-gray-500 hover:text-gray-700 transition">&larr; Back to sign in</button>
                    <p class="text-center text-xs text-gray-400">No access to that email? <button type="button" onclick="showMethod('phone')" class="underline">Sign in with a phone code</button> instead.</p>
                </div>
            </div>

            {{-- After a phone-code sign-in: offer a password so the next sign-in needs no SMS --}}
            <div id="step-setpw" class="hidden">
                <h2 class="text-xl font-semibold text-gray-800 mb-1">Create a password</h2>
                <p class="text-gray-500 text-sm mb-6">Next time, sign in with your email and this password. No SMS code needed.</p>
                <form class="space-y-4" onsubmit="return savePassword(event)" novalidate>
                    <input type="email" id="setpw-user" autocomplete="username" class="hidden" tabindex="-1" aria-hidden="true" />
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="setpw-1">New password</label>
                        <input type="password" id="setpw-1" autocomplete="new-password"
                            class="w-full px-3 py-2.5 text-base sm:text-sm border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent transition" />
                        <p class="text-xs text-gray-400 mt-1">At least 8 characters, with a letter and a number.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="setpw-2">Confirm password</label>
                        <input type="password" id="setpw-2" autocomplete="new-password"
                            class="w-full px-3 py-2.5 text-base sm:text-sm border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent transition" />
                    </div>
                    <div id="setpw-error" role="alert" aria-live="assertive" class="hidden"></div>
                    <button type="submit" id="setpw-btn"
                        class="w-full py-2.5 px-4 bg-[#FA6908] hover:bg-orange-600 disabled:bg-orange-300 text-white text-sm font-semibold rounded-lg transition">
                        Save password
                    </button>
                    <button type="button" onclick="skipPassword()" class="w-full py-2 text-sm text-gray-500 hover:text-gray-700 transition">Not now</button>
                </form>
            </div>

        </div>

        <p class="text-center text-blue-200 text-xs mt-6">
            © {{ date('Y') }} ShaloTrack Lanka (Pvt) Ltd
        </p>
    </div>

    <script>
        // ---- Firebase config ----
        // These values are safe to expose — they are not secrets.
        // Auth is enforced server-side by Firebase and the Laravel session.
        const firebaseConfig = {
            apiKey: "{{ config('services.firebase.api_key') }}",
            authDomain: "{{ config('services.firebase.auth_domain') }}",
            projectId: "{{ config('shalotrack.firebase_project_id') }}",
            appId: "{{ config('services.firebase.app_id') }}",
        };

        firebase.initializeApp(firebaseConfig);
        const auth = firebase.auth();

        // ---- Phone sign-in via ShaloTrack API (Text.lk SMS) ----
        // The API sends the SMS and, on a correct code, returns a Firebase CUSTOM token. We exchange it with
        // signInWithCustomToken, so Firebase is still the session issuer and /login on this server is unchanged.
        // No reCAPTCHA / Firebase phone auth any more. The browser talks to the API directly (CORS + CSP allow it);
        // credentials are omitted: no cookies are ever sent to the API.
        const API_BASE = @json(rtrim((string) config('shalotrack.api_base_url'), '/'));
        let otpPhone = null; // E.164 number the current code was sent to

        // ---- Normalise Sri Lankan phone number to E.164 ----
        // The +94 prefix is shown beside the box, so the user types only the national number.
        // All of these give the same result: 712345678 · 0712345678 · 94712345678 · +94 (0)71 234 5678
        function normalisePhone(raw) {
            let d = raw.replace(/\D/g, '');
            if (d.startsWith('94')) d = d.slice(2); // pasted with country code
            if (d.startsWith('0')) d = d.slice(1); // local trunk "0" is dropped in E.164
            return /^7\d{8}$/.test(d) ? `+94${d}` : null; // Sri Lankan mobiles: 7X XXX XXXX
        }

        // ---- Error cards ----
        // Same look as the Laravel $errors banner above (red) and the session-expired banner (amber).
        // Built with textContent only: server/API text is never injected as HTML.
        // kind: 'error' (red) | 'warn' (amber, "wait and retry"). ref: short support reference, shown small.
        const CARD_CLASSES = {
            error: 'p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm text-center',
            warn: 'p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-700 text-sm text-center',
        };

        function showError(elId, msg, opts) {
            const o = opts || {};
            const el = document.getElementById(elId);
            el.className = CARD_CLASSES[o.kind === 'warn' ? 'warn' : 'error'];
            el.replaceChildren();
            const text = document.createElement('p');
            text.textContent = msg;
            el.appendChild(text);
            if (o.ref) {
                const r = document.createElement('p');
                r.className = 'mt-1 text-xs opacity-70 font-mono';
                r.textContent = 'Ref: ' + o.ref;
                el.appendChild(r);
            }
        }

        function hideError(elId) {
            const el = document.getElementById(elId);
            el.classList.add('hidden');
            el.replaceChildren();
        }

        function setLoading(btnId, loading, label) {
            const btn = document.getElementById(btnId);
            btn.disabled = loading;
            btn.textContent = loading ? 'Please wait...' : label;
        }

        // ---- Resend cooldown ----
        // After a code is sent, or after the API says "too many", the button stays locked with a visible countdown
        // (every extra tap on an SMS endpoint costs money and lengthens the block). Kept in sessionStorage so a
        // page reload does not bypass it. The server enforces the real limits; this is only the polite front.
        const SEND_LABEL = 'Send Verification Code';
        let cooldownTimer = null;
        let cooldownUntil = 0;

        function startCooldown(seconds) {
            cooldownUntil = Date.now() + seconds * 1000;
            try {
                sessionStorage.setItem('otp_cooldown_until', String(cooldownUntil));
            } catch (e) {
                /* storage blocked: the in-memory timer still works */ }
            runCooldown();
        }

        function runCooldown() {
            const btn = document.getElementById('send-otp-btn');
            clearInterval(cooldownTimer);
            const tick = () => {
                const left = Math.ceil((cooldownUntil - Date.now()) / 1000);
                if (left <= 0) {
                    clearInterval(cooldownTimer);
                    cooldownUntil = 0;
                    btn.disabled = false;
                    btn.textContent = SEND_LABEL;
                    return;
                }
                btn.disabled = true;
                btn.textContent = 'Send again in ' + left + 's';
            };
            tick();
            if (cooldownUntil) cooldownTimer = setInterval(tick, 1000);
        }

        window.addEventListener('load', () => {
            try {
                const saved = parseInt(sessionStorage.getItem('otp_cooldown_until') || '0', 10);
                if (saved > Date.now()) {
                    cooldownUntil = saved;
                    runCooldown();
                }
            } catch (e) {
                /* storage blocked */ }
        });

        // ---- API call helper ----
        // Returns { ok, status, body } and never throws for HTTP errors. Network failure / timeout becomes
        // status 0 so the caller can show the right card. Retry-After is NOT readable cross-origin unless the API
        // exposes it (it does since the API CORS change); otherwise cooldowns fall back to fixed values.
        async function apiPost(path, payload) {
            const ctrl = new AbortController();
            const timer = setTimeout(() => ctrl.abort(), 15000);
            try {
                const res = await fetch(API_BASE + path, {
                    method: 'POST',
                    credentials: 'omit',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload),
                    signal: ctrl.signal,
                });
                const body = await res.json().catch(() => ({}));
                // Readable only once the API exposes Retry-After via CORS; null otherwise.
                const ra = parseInt(res.headers.get('Retry-After') || '', 10);
                return {
                    ok: res.ok,
                    status: res.status,
                    body,
                    retryAfter: Number.isFinite(ra) ? ra : null
                };
            } catch (e) {
                return {
                    ok: false,
                    status: 0,
                    body: {},
                    aborted: e && e.name === 'AbortError'
                };
            } finally {
                clearTimeout(timer);
            }
        }

        // Turn an API failure into { msg, kind, ref, cooldown }. API messages are already written for end users
        // (see PhoneVerificationService); we only fall back to our own wording when the body is not the API's JSON.
        function describeApiFailure(r, fallback) {
            const apiMsg = (r.body && typeof r.body.message === 'string' && r.body.message.length < 200) ? r.body.message : '';
            if (r.status === 0) {
                return {
                    msg: r.aborted ? 'The request took too long. Check your connection and try again.' :
                        'Could not reach the server. Check your internet connection and try again.',
                    kind: 'error',
                    ref: r.aborted ? 'timeout' : 'network'
                };
            }
            if (r.status === 429) {
                // Either the per-number limit from the service or the per-IP limiter: both mean "wait".
                return {
                    msg: apiMsg || 'Too many attempts. Please wait a few minutes and try again.',
                    kind: 'warn',
                    ref: '429',
                    cooldown: r.retryAfter ? Math.min(Math.max(r.retryAfter, 10), 3600) : 120
                };
            }
            if (r.status === 400) return {
                msg: apiMsg || fallback,
                kind: 'error',
                ref: null
            };
            if (r.status === 502) return {
                msg: apiMsg || 'We could not send the code right now. Please try again shortly, or sign in with email and password.',
                kind: 'error',
                ref: '502',
                cooldown: 60
            };
            if (r.status >= 500) return {
                msg: 'Our service had a problem. Please try again in a moment. If it keeps happening, contact support.',
                kind: 'error',
                ref: String(r.status)
            };
            if (r.status === 403) return {
                msg: 'This request was blocked. Please reload the page and try again.',
                kind: 'error',
                ref: '403'
            };
            return {
                msg: apiMsg || fallback,
                kind: 'error',
                ref: String(r.status)
            };
        }

        // ---- Step 1: Send OTP ----
        async function sendOtp() {
            if (cooldownUntil > Date.now()) return;
            hideError('phone-error');
            const raw = document.getElementById('phone-input').value.trim();
            const e164 = normalisePhone(raw);

            if (!e164) {
                showError('phone-error', 'Enter a valid Sri Lankan mobile number (e.g. 71 234 5678).');
                return;
            }

            setLoading('send-otp-btn', true, SEND_LABEL);
            const r = await apiPost('/api/auth/otp/request', {
                phoneNumber: e164
            });

            if (r.ok) {
                otpPhone = e164;
                startCooldown(60); // a code is on its way: no second one for a minute
                showPanel('step-otp');
                document.getElementById('otp-subtitle').textContent = `We sent a 6-digit code to ${raw}.`;
                document.getElementById('otp-input').focus();
                return;
            }

            const f = describeApiFailure(r, 'Enter a valid Sri Lankan mobile number.');
            showError('phone-error', f.msg, {
                kind: f.kind,
                ref: f.ref
            });
            if (f.cooldown) startCooldown(f.cooldown);
            if (cooldownUntil <= Date.now()) setLoading('send-otp-btn', false, SEND_LABEL);
        }

        // ---- Step 2: Verify OTP ----
        async function verifyOtp() {
            hideError('otp-error');
            const code = document.getElementById('otp-input').value.trim();

            if (!/^\d{6}$/.test(code)) {
                showError('otp-error', 'Enter the 6-digit code sent to your phone.');
                return;
            }
            if (!otpPhone) {
                // Page was reloaded between steps: the code cannot be checked without the number.
                showError('otp-error', 'Please request a new code.');
                goBack();
                return;
            }

            setLoading('verify-otp-btn', true, 'Verify & Sign In');
            try {
                const r = await apiPost('/api/auth/otp/verify', {
                    phoneNumber: otpPhone,
                    code
                });
                if (!r.ok) {
                    const f = describeApiFailure(r, 'Invalid or expired code.');
                    // After a wrong code the user can retry; after 429 they must wait, so the message is amber.
                    showError('otp-error', f.msg, {
                        kind: f.kind,
                        ref: f.ref
                    });
                    return;
                }
                const token = r.body && r.body.data && r.body.data.customToken;
                if (!token) {
                    showError('otp-error', 'Sign-in could not be completed. Please request a new code.', {
                        ref: 'no-token'
                    });
                    return;
                }

                let cred;
                try {
                    cred = await auth.signInWithCustomToken(token);
                } catch (err) {
                    console.error('Custom token sign-in error:', err);
                    showError('otp-error', getFirebaseError(err), {
                        ref: (err && err.code) || 'unknown'
                    });
                    return;
                }

                const data = await postLogin(cred.user);
                if (data.error) {
                    showError('otp-error', data.error, {
                        kind: data.kind,
                        ref: data.ref
                    });
                    try {
                        await auth.signOut();
                    } catch (e) {
                        /* already signed out */ }
                    return;
                }
                afterLogin(cred.user, data.redirect, true);
            } finally {
                setLoading('verify-otp-btn', false, 'Verify & Sign In');
            }
        }

        // ---- Shared: hand the Firebase ID token to Laravel, which verifies it and starts the session ----
        // Laravel answers errors as JSON { success:false, code, message, reference } (ErrorLogger::json).
        // The reference is the same id the custom error pages show and that is logged server-side.
        async function postLogin(user) {
            let response, data;
            try {
                const idToken = await user.getIdToken();
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                response = await fetch('/login', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    // refresh_token lets the server keep this login for up to 90 days (stored encrypted, HttpOnly)
                    body: JSON.stringify({
                        token: idToken,
                        refresh_token: user.refreshToken
                    }),
                });
                data = await response.json().catch(() => ({}));
            } catch (e) {
                return {
                    error: 'Could not reach the server. Check your internet connection and try again.',
                    ref: 'network'
                };
            }
            if (response.ok || response.redirected) return {
                redirect: data.redirect ?? '/dashboard'
            };

            const ref = data.reference || data.ref || null;
            if (response.status === 419) {
                return {
                    error: 'Your page was open too long. Reload it and sign in again.',
                    ref: ref || '419'
                };
            }
            if (response.status === 429) {
                return {
                    error: 'Too many sign-in attempts. Please wait a minute and try again.',
                    kind: 'warn',
                    ref: ref || '429'
                };
            }
            if (response.status >= 500) {
                return {
                    error: data.message || 'Something went wrong on our side. Please try again.',
                    ref: ref || String(response.status)
                };
            }
            return {
                error: data.message || 'Authentication failed. Please try again.',
                ref
            };
        }

        // ---- Sign-in method tabs ----
        const PANELS = ['step-phone', 'step-otp', 'step-email', 'step-forgot', 'step-setpw'];

        function showPanel(id) {
            PANELS.forEach(p => document.getElementById(p).classList.toggle('hidden', p !== id));
            document.getElementById('login-tabs').classList.toggle('hidden', id === 'step-setpw' || id === 'step-otp');
        }

        function showMethod(m) {
            showPanel(m === 'email' ? 'step-email' : 'step-phone');
            const on = 'bg-white text-gray-900 shadow-sm',
                off = 'text-gray-500 hover:text-gray-700';
            document.getElementById('tab-email').className = 'py-2 rounded-md transition ' + (m === 'email' ? on : off);
            document.getElementById('tab-phone').className = 'py-2 rounded-md transition ' + (m === 'phone' ? on : off);
            document.getElementById('login-tabs').classList.remove('hidden');
            try {
                localStorage.setItem('login_method', m);
            } catch (e) {
                /* storage blocked */ }
        }

        function showForgot() {
            showPanel('step-forgot');
            document.getElementById('login-tabs').classList.add('hidden');
            document.getElementById('forgot-input').value = document.getElementById('email-input').value;
        }
        window.addEventListener('load', () => {
            let m = 'phone'; // safe default: existing customers have no password yet
            const isNew = @json($isNew ?? false);
            if (!isNew) {
                try {
                    if (localStorage.getItem('login_method') === 'email') m = 'email';
                } catch (e) {}
            }
            showMethod(m);
        });

        // ---- Email + password sign-in ----
        async function emailSignIn(ev) {
            if (ev) ev.preventDefault();
            hideError('email-error');
            const email = document.getElementById('email-input').value.trim();
            const password = document.getElementById('password-input').value;
            if (!/^\S+@\S+\.\S+$/.test(email) || !password) {
                showError('email-error', 'Enter your email and password.');
                return false;
            }
            setLoading('email-signin-btn', true, 'Sign In');
            try {
                const cred = await auth.signInWithEmailAndPassword(email, password);
                const data = await postLogin(cred.user);
                if (data.error) {
                    showError('email-error', data.error, {
                        kind: data.kind,
                        ref: data.ref
                    });
                    await auth.signOut();
                    return false;
                }
                window.location.href = data.redirect;
            } catch (err) {
                console.error('Email sign-in error:', err);
                showError('email-error', getPasswordError(err), {
                    kind: (err && err.code === 'auth/too-many-requests') ? 'warn' : 'error'
                });
            } finally {
                setLoading('email-signin-btn', false, 'Sign In');
            }
            return false;
        }

        // One generic message for wrong password / unknown email: never reveal which emails have accounts.
        function getPasswordError(err) {
            const code = (err && err.code) ? String(err.code) : '';
            switch (code) {
                case 'auth/invalid-credential':
                case 'auth/wrong-password':
                case 'auth/user-not-found':
                case 'auth/invalid-login-credentials':
                case 'auth/invalid-email':
                case 'auth/missing-password':
                    return 'Incorrect email or password. If you have not set a password yet, use "Phone code".';
                case 'auth/too-many-requests':
                    return 'Too many attempts. Please wait a few minutes, or reset your password.';
                case 'auth/user-disabled':
                    return 'This account has been disabled. Please contact support.';
                case 'auth/network-request-failed':
                    return 'Network problem. Check your internet connection and try again.';
                case 'auth/operation-not-allowed':
                    return 'Email sign-in is not available right now. Use "Phone code". (ref: ' + code + ')';
                default:
                    return 'Something went wrong. Please try again. (ref: ' + (code || 'unknown') + ')';
            }
        }

        // ---- Forgot password (always the same answer: never reveal which emails exist) ----
        let resetCooldownUntil = 0;
        async function sendReset() {
            const msg = document.getElementById('forgot-msg');
            const email = document.getElementById('forgot-input').value.trim();
            if (!/^\S+@\S+\.\S+$/.test(email)) {
                msg.textContent = 'Enter a valid email address.';
                msg.className = 'text-sm text-red-600';
                return;
            }
            if (Date.now() < resetCooldownUntil) return;
            setLoading('forgot-btn', true, 'Send reset link');
            try {
                await auth.sendPasswordResetEmail(email, {
                    url: window.location.origin + '/login'
                });
            } catch (err) {
                if (err && (err.code === 'auth/too-many-requests' || err.code === 'auth/network-request-failed')) {
                    msg.textContent = err.code === 'auth/network-request-failed' ? 'Network problem. Please try again.' : 'Too many attempts. Please wait a few minutes.';
                    msg.className = 'text-sm text-red-600';
                    setLoading('forgot-btn', false, 'Send reset link');
                    return;
                }
                console.error('Password reset error:', err); // user-not-found etc.: same message below
            }
            resetCooldownUntil = Date.now() + 60000;
            msg.textContent = 'If an account exists for that email, a reset link is on its way. Check your inbox and spam folder.';
            msg.className = 'text-sm text-green-600';
            setLoading('forgot-btn', false, 'Send reset link');
        }

        // ---- After a phone-code sign-in: offer a password (once; "Not now" is remembered per browser) ----
        let pendingRedirect = '/dashboard';

        function hasPassword(user) {
            return (user.providerData || []).some(p => p.providerId === 'password');
        }

        function afterLogin(user, redirect, offerPassword) {
            pendingRedirect = redirect;
            let dismissed = false;
            try {
                dismissed = localStorage.getItem('pw_prompt_dismissed') === user.uid;
            } catch (e) {}
            const eligible = offerPassword && redirect === '/dashboard' && user.email && user.emailVerified && !hasPassword(user) && !dismissed;
            if (!eligible) {
                window.location.href = redirect;
                return;
            }
            document.getElementById('setpw-user').value = user.email;
            showPanel('step-setpw');
        }

        function skipPassword() {
            try {
                const u = auth.currentUser;
                if (u) localStorage.setItem('pw_prompt_dismissed', u.uid);
            } catch (e) {}
            window.location.href = pendingRedirect;
        }

        function passwordProblem(pw) {
            if (pw.length < 8) return 'Use at least 8 characters.';
            if (!/[A-Za-z]/.test(pw) || !/\d/.test(pw)) return 'Include at least one letter and one number.';
            return '';
        }
        async function savePassword(ev) {
            if (ev) ev.preventDefault();
            hideError('setpw-error');
            const user = auth.currentUser;
            const a = document.getElementById('setpw-1').value,
                b = document.getElementById('setpw-2').value;
            const bad = passwordProblem(a);
            if (bad) {
                showError('setpw-error', bad);
                return false;
            }
            if (a !== b) {
                showError('setpw-error', 'The two passwords do not match.');
                return false;
            }
            if (!user) {
                window.location.href = pendingRedirect;
                return false;
            }
            setLoading('setpw-btn', true, 'Save password');
            try {
                try {
                    await user.linkWithCredential(firebase.auth.EmailAuthProvider.credential(user.email, a));
                } catch (e) {
                    if (e && e.code === 'auth/provider-already-linked') {
                        await user.updatePassword(a);
                    } else {
                        throw e;
                    }
                }
                window.location.href = pendingRedirect;
            } catch (err) {
                console.error('Set password error:', err);
                showError('setpw-error', err && err.code === 'auth/requires-recent-login' ?
                    'For your security, please sign in with a phone code again, then set your password.' :
                    'We could not save your password. You can try again later from the sign-in page. (ref: ' + ((err && err.code) || 'unknown') + ')');
                setLoading('setpw-btn', false, 'Save password');
            }
            return false;
        }

        // ---- Go back to phone step ----
        function goBack() {
            showMethod('phone');
            document.getElementById('otp-input').value = '';
            hideError('otp-error');
            otpPhone = null;
        }

        // ---- Firebase errors left after the move to the API: only custom-token sign-in can fail here ----
        function getFirebaseError(err) {
            const code = (err && err.code) ? String(err.code) : '';
            switch (code) {
                case 'auth/invalid-custom-token':
                case 'auth/custom-token-mismatch':
                    return 'Sign-in could not be completed. Please request a new code.';
                case 'auth/network-request-failed':
                    return 'Network problem. Check your internet connection and try again.';
                case 'auth/too-many-requests':
                    return 'Too many attempts. Please wait a few minutes and try again.';
                case 'auth/user-disabled':
                    return 'This account has been disabled. Please contact support.';
                case 'auth/web-storage-unsupported':
                    return 'Your browser is blocking storage. Open this page in Chrome or Safari (not inside another app) and try again.';
                default:
                    return 'Something went wrong. Please try again.';
            }
        }
    </script>

</body>

</html>