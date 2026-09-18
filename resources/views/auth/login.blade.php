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
<body class="min-h-screen flex items-center justify-center bg-[#021F4A] px-4">

    {{-- reCAPTCHA container — required by Firebase Phone Auth, invisible to user --}}
    <div id="recaptcha-container"></div>

    <div class="w-full max-w-md">

        {{-- Logo --}}
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-white tracking-tight">
                Shalo<span class="text-[#FA6908]">Track</span>
            </h1>
            <p class="text-blue-200 text-sm mt-2">Fleet Management Portal</p>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-2xl p-8">

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

            {{-- Step 1: Phone number input --}}
            <div id="step-phone">
                <h2 class="text-xl font-semibold text-gray-800 mb-1">Welcome back</h2>
                <p class="text-gray-500 text-sm mb-6">Enter your phone number to continue.</p>

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
                                placeholder="071 234 5678"
                                class="flex-1 px-3 py-2.5 text-sm outline-none bg-white"
                                autofocus
                            />
                        </div>
                    </div>

                    <p id="phone-error" class="text-red-600 text-sm hidden"></p>

                    <button
                        id="send-otp-btn"
                        onclick="sendOtp()"
                        class="w-full py-2.5 px-4 bg-[#FA6908] hover:bg-orange-600 disabled:bg-orange-300 text-white text-sm font-semibold rounded-lg transition"
                    >
                        Send Verification Code
                    </button>
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
                            class="w-full px-4 py-2.5 text-center text-2xl tracking-[0.5em] border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent transition"
                        />
                    </div>

                    <p id="otp-error" class="text-red-600 text-sm hidden"></p>

                    <button
                        id="verify-otp-btn"
                        onclick="verifyOtp()"
                        class="w-full py-2.5 px-4 bg-[#FA6908] hover:bg-orange-600 disabled:bg-orange-300 text-white text-sm font-semibold rounded-lg transition"
                    >
                        Verify &amp; Sign In
                    </button>

                    <button
                        type="button"
                        onclick="goBack()"
                        class="w-full py-2 text-sm text-gray-500 hover:text-gray-700 transition"
                    >
                        ← Use a different number
                    </button>
                </div>
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
            apiKey:    "{{ config('services.firebase.api_key') }}",
            authDomain:"{{ config('services.firebase.auth_domain') }}",
            projectId: "{{ config('shalotrack.firebase_project_id') }}",
            appId:     "{{ config('services.firebase.app_id') }}",
        };

        firebase.initializeApp(firebaseConfig);
        const auth = firebase.auth();

        // ---- reCAPTCHA (invisible, required by Firebase Phone Auth) ----
        let recaptchaVerifier;
        let confirmationResult;

        window.addEventListener('load', () => {
            recaptchaVerifier = new firebase.auth.RecaptchaVerifier('recaptcha-container', {
                size: 'invisible',
            });
        });

        // ---- Normalise Sri Lankan phone number to E.164 ----
        function normalisePhone(raw) {
            const digits = raw.replace(/\D/g, '');
            if (digits.startsWith('947') && digits.length === 11) return `+${digits}`;
            if (digits.startsWith('07')  && digits.length === 10)  return `+94${digits.slice(1)}`;
            if (digits.startsWith('7')   && digits.length === 9)   return `+94${digits}`;
            return null;
        }

        // ---- Helpers ----
        function showError(elId, msg) {
            const el = document.getElementById(elId);
            el.textContent = msg;
            el.classList.remove('hidden');
        }

        function hideError(elId) {
            document.getElementById(elId).classList.add('hidden');
        }

        function setLoading(btnId, loading, label) {
            const btn = document.getElementById(btnId);
            btn.disabled = loading;
            btn.textContent = loading ? 'Please wait...' : label;
        }

        // ---- Step 1: Send OTP ----
        async function sendOtp() {
            hideError('phone-error');
            const raw = document.getElementById('phone-input').value.trim();
            const e164 = normalisePhone(raw);

            if (!e164) {
                showError('phone-error', 'Enter a valid Sri Lankan phone number (e.g. 071 234 5678).');
                return;
            }

            setLoading('send-otp-btn', true, 'Send Verification Code');

            try {
                confirmationResult = await auth.signInWithPhoneNumber(e164, recaptchaVerifier);

                // Show OTP step
                document.getElementById('step-phone').classList.add('hidden');
                document.getElementById('step-otp').classList.remove('hidden');
                document.getElementById('otp-subtitle').textContent = `We sent a 6-digit code to ${raw}.`;
                document.getElementById('otp-input').focus();

            } catch (err) {
                console.error('OTP send error:', err);
                showError('phone-error', getFriendlyError(err.code));
                // Reset reCAPTCHA on failure
                recaptchaVerifier.clear();
                recaptchaVerifier = new firebase.auth.RecaptchaVerifier('recaptcha-container', { size: 'invisible' });
            } finally {
                setLoading('send-otp-btn', false, 'Send Verification Code');
            }
        }

        // ---- Step 2: Verify OTP ----
        async function verifyOtp() {
            hideError('otp-error');
            const code = document.getElementById('otp-input').value.trim();

            if (!/^\d{6}$/.test(code)) {
                showError('otp-error', 'Enter the 6-digit code sent to your phone.');
                return;
            }

            setLoading('verify-otp-btn', true, 'Verify & Sign In');

            try {
                const result = await confirmationResult.confirm(code);
                const idToken = await result.user.getIdToken();

                // POST token to Laravel — server verifies and creates session
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                const response = await fetch('/login', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ token: idToken }),
                });

                if (response.ok || response.redirected) {
                    const data = await response.json().catch(() => ({}));
                    // Use redirect from server if provided, otherwise default to dashboard
                    window.location.href = data.redirect ?? '/dashboard';
                } else {
                    const data = await response.json().catch(() => ({}));
                    showError('otp-error', data.message ?? 'Authentication failed. Please try again.');
                }

            } catch (err) {
                console.error('OTP verify error:', err);
                showError('otp-error', getFriendlyError(err.code));
            } finally {
                setLoading('verify-otp-btn', false, 'Verify & Sign In');
            }
        }

        // ---- Go back to phone step ----
        function goBack() {
            document.getElementById('step-otp').classList.add('hidden');
            document.getElementById('step-phone').classList.remove('hidden');
            document.getElementById('otp-input').value = '';
            hideError('otp-error');
        }

        // ---- Map Firebase error codes to user-friendly messages ----
        function getFriendlyError(code) {
            switch (code) {
                case 'auth/invalid-phone-number':     return 'Invalid phone number. Please check and try again.';
                case 'auth/too-many-requests':        return 'Too many attempts. Please wait a few minutes and try again.';
                case 'auth/invalid-verification-code':return 'Incorrect code. Please check and try again.';
                case 'auth/code-expired':             return 'The code has expired. Please request a new one.';
                case 'auth/quota-exceeded':           return 'SMS quota exceeded. Please contact support.';
                default:                              return 'Something went wrong. Please try again.';
            }
        }
    </script>

</body>
</html>