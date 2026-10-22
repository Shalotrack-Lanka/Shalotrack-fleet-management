<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Verify Email — ShaloTrack Fleet</title>
    @vite(['resources/css/app.css'])
    <script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-auth-compat.js"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-[#021F4A] px-4 py-8">

    <div class="w-full max-w-md">

        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white tracking-tight">
                Shalo<span class="text-[#FA6908]">Track</span>
            </h1>
            <p class="text-blue-200 text-sm mt-2">Fleet Management Portal</p>
        </div>

        <div class="bg-white rounded-2xl shadow-2xl p-8 text-center">

            {{-- Email icon --}}
            <div class="w-16 h-16 bg-orange-50 rounded-full flex items-center justify-center mx-auto mb-5">
                <svg class="w-8 h-8 text-[#FA6908]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>

            <h2 class="text-xl font-bold text-gray-800 mb-2">Verify your email</h2>
            <p id="lead-msg" class="text-gray-500 text-sm mb-2">
                Sending a verification link to
            </p>
            <p class="text-[#FA6908] font-semibold text-sm mb-6 break-all">{{ $email }}</p>

            <p class="text-gray-400 text-xs mb-6">
                Open the email and click the link, then come back to this page: it continues on its own.
                Check your spam folder if you don't see it.
            </p>

            {{-- Status message --}}
            <p id="status-msg" class="text-sm mb-4 hidden"></p>

            <div id="resign-box" class="hidden mb-4">
                <a href="/email/resign-in"
                   class="block w-full py-2.5 px-4 bg-[#FA6908] hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition">
                    Sign in again
                </a>
                <p class="text-gray-400 text-xs mt-2">We will text you a new code. Your details are kept.</p>
            </div>

            <div class="space-y-3" id="actions">
                <button onclick="checkVerification()"
                        id="check-btn"
                        class="w-full py-2.5 px-4 bg-[#FA6908] hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition">
                    I've verified my email
                </button>

                <button onclick="resendEmail()"
                        id="resend-btn"
                        class="w-full py-2 text-sm text-gray-500 hover:text-gray-700 transition">
                    Resend verification email
                </button>

                <a href="/register" id="edit-link"
                   class="block w-full py-2 text-sm text-gray-500 hover:text-gray-700 transition">
                    Wrong email? Edit my details
                </a>

                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit" class="w-full py-2 text-sm text-gray-400 hover:text-gray-600 transition">
                        Sign out and use a different account
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center text-blue-200 text-xs mt-6">
            © {{ date('Y') }} ShaloTrack Lanka (Pvt) Ltd
        </p>
    </div>

    <script>
        const firebaseConfig = {
            apiKey:    "{{ config('services.firebase.api_key') }}",
            authDomain:"{{ config('services.firebase.auth_domain') }}",
            projectId: "{{ config('shalotrack.firebase_project_id') }}",
            appId:     "{{ config('services.firebase.app_id') }}",
        };

        firebase.initializeApp(firebaseConfig);
        const auth = firebase.auth();

        const TARGET_EMAIL = @json($email);
        const csrfToken    = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const sameEmail    = (a, b) => (a || '').trim().toLowerCase() === (b || '').trim().toLowerCase();

        let currentUser = null;   // Firebase user restored from the phone sign-in
        let completing  = false;  // one profile-creation request at a time
        let stopped     = false;  // a hard error (details rejected): stop polling

        function showStatus(msg, color = 'text-green-600') {
            const el = document.getElementById('status-msg');
            el.textContent = msg;
            el.className = `text-sm mb-4 ${color}`;
            el.classList.remove('hidden');
        }

        // The browser has no valid Firebase sign-in: nothing on this page can work, so say so and offer one click out.
        function needSignIn() {
            stopped = true;
            currentUser = null;
            showStatus('Your sign-in expired. Please sign in again.', 'text-red-500');
            document.getElementById('resign-box').classList.remove('hidden');
            ['check-btn', 'resend-btn'].forEach(id => document.getElementById(id).classList.add('hidden'));
        }

        const DEAD_SESSION = ['auth/user-token-expired', 'auth/user-disabled', 'auth/user-not-found', 'auth/invalid-user-token', 'auth/requires-recent-login'];
        const isDeadSession = (e) => !!e && DEAD_SESSION.includes(e.code);

        function setCheckButton(busy) {
            const btn = document.getElementById('check-btn');
            btn.disabled = busy;
            btn.textContent = busy ? 'Checking...' : "I've verified my email";
        }

        // ---- 1. Ask Firebase to email the link (same call the Android app makes) ----
        // verifyBeforeUpdateEmail attaches TARGET_EMAIL to the phone account only AFTER the link is clicked,
        // which is what makes the account's email_verified claim true.
        async function sendLink(user, manual) {
            const settings = { url: window.location.origin + '/email/verify' };
            try {
                await user.verifyBeforeUpdateEmail(TARGET_EMAIL, settings);
            } catch (e) {
                // Same fallback as the Android app: attach the email, then send the verification mail.
                if (['auth/operation-not-allowed', 'auth/internal-error'].includes(e.code) && typeof user.updateEmail === 'function') {
                    await user.updateEmail(TARGET_EMAIL);
                    await user.sendEmailVerification(settings);
                } else {
                    throw e;
                }
            }
            try { sessionStorage.setItem('ev_sent_' + TARGET_EMAIL.toLowerCase(), String(Date.now())); } catch (e) { /* storage blocked */ }
            document.getElementById('lead-msg').textContent = "We've sent a verification link to";
            if (manual) showStatus('Verification email sent! Check your inbox.', 'text-green-600');
        }

        function linkError(e) {
            switch (e && e.code) {
                case 'auth/requires-recent-login':
                    return 'For your security, please sign in again, then continue. (Use the "Sign in again" button.)';
                case 'auth/email-already-in-use':
                    return 'That email already belongs to another account. Use "Edit my details" to enter a different email.';
                case 'auth/invalid-email':
                    return 'That email address does not look right. Use "Edit my details" to correct it.';
                case 'auth/too-many-requests':
                    return 'Too many attempts. Please wait a few minutes before asking for another email.';
                case 'auth/network-request-failed':
                    return 'Network problem. Check your internet connection and try again.';
                default:
                    return 'We could not send the verification email. Please try again. (ref: ' + ((e && e.code) || 'unknown') + ')';
            }
        }

        function alreadyVerified(user) {
            return user.emailVerified && sameEmail(user.email, TARGET_EMAIL);
        }

        // ---- 2. Verified? Create the profile (server checks the token again) ----
        async function complete(user) {
            if (completing || stopped) return false;
            completing = true;
            try {
                // A fresh ID token now carries Firebase's own email_verified claim.
                const idToken = await user.getIdToken(true);
                const res = await fetch('/email/mark-verified', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ idToken }),
                });
                const j = await res.json().catch(() => ({}));

                if (res.status === 401 || j.expired) {
                    window.location.href = '/login?expired=1';
                    return true;
                }
                if (res.ok && j.success) {
                    showStatus('Email verified! Setting up your account...', 'text-green-600');
                    stopped = true;   // the profile exists now: never post twice while the browser navigates
                    window.location.href = j.redirect || '/dashboard';
                    return true;
                }

                const ref = j.reference ? ' (ref: ' + j.reference + ')' : '';
                showStatus((j.message || 'We could not finish creating your account. Please try again.') + ref, 'text-red-500');
                if (j.restart) stopped = true;   // the customer has to change something: no point retrying
            } catch (e) {
                showStatus('Could not finish creating your account. Please try again.', 'text-red-500');
            } finally {
                completing = false;
            }
            return false;
        }

        // The "I've verified my email" button
        async function checkVerification() {
            if (!currentUser) { needSignIn(); return; }
            setCheckButton(true);
            try {
                await currentUser.reload();
                if (alreadyVerified(currentUser)) {
                    stopped = false;
                    if (await complete(currentUser)) return;
                } else {
                    showStatus('Email not verified yet. Open the link in your email, then press this button again.', 'text-red-500');
                }
            } catch (e) {
                if (isDeadSession(e)) { needSignIn(); return; }
                showStatus('Could not check verification status. Please try again.', 'text-red-500');
            }
            setCheckButton(false);
        }

        // Resend verification email
        async function resendEmail() {
            const btn = document.getElementById('resend-btn');
            if (!currentUser) { needSignIn(); return; }
            btn.disabled = true;
            btn.textContent = 'Sending...';
            try {
                await sendLink(currentUser, true);
            } catch (e) {
                if (isDeadSession(e) && e.code !== 'auth/requires-recent-login') { needSignIn(); return; }
                showStatus(linkError(e), 'text-red-500');
            } finally {
                setTimeout(() => {
                    btn.disabled = false;
                    btn.textContent = 'Resend verification email';
                }, 30000); // 30s cooldown
            }
        }

        // ---- Start: wait for Firebase to restore the phone sign-in, then send the link once ----
        let unsubscribe = null;
        unsubscribe = auth.onAuthStateChanged(async (user) => {
            if (unsubscribe) unsubscribe();
            if (!user) { needSignIn(); return; }
            currentUser = user;

            try {
                await user.reload();
                if (alreadyVerified(user)) {            // verified earlier (e.g. the page was reloaded after the click)
                    await complete(user);
                } else {
                    let recent = false;                 // do not email again on every refresh
                    try { recent = Date.now() - Number(sessionStorage.getItem('ev_sent_' + TARGET_EMAIL.toLowerCase()) || 0) < 10 * 60 * 1000; } catch (e) {}
                    if (recent) {
                        document.getElementById('lead-msg').textContent = "We've sent a verification link to";
                    } else {
                        await sendLink(user, false);
                    }
                }
            } catch (e) {
                if (isDeadSession(e) && e.code !== 'auth/requires-recent-login') { needSignIn(); return; }
                showStatus(linkError(e), 'text-red-500');
            }
        });

        // Check every 5 seconds: the customer may click the link on their phone
        setInterval(async () => {
            if (!currentUser || completing || stopped) return;
            try {
                await currentUser.reload();
                if (alreadyVerified(currentUser)) await complete(currentUser);
            } catch (e) {
                if (isDeadSession(e)) needSignIn();   // revoked (e.g. the email-change link was opened): stop polling
            }
        }, 5000);
    </script>

</body>
</html>