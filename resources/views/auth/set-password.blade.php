<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Create your password — ShaloTrack Fleet</title>
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

        <div class="bg-white rounded-2xl shadow-2xl p-8">

            <h2 class="text-xl font-bold text-gray-800 mb-1 text-center">Create your password</h2>
            <p class="text-gray-500 text-sm mb-1 text-center">You will sign in with this email and password. No SMS code needed.</p>
            <p class="text-[#FA6908] font-semibold text-sm mb-6 text-center break-all">{{ $email }}</p>

            {{-- Status message --}}
            <p id="status-msg" class="text-sm mb-4 hidden text-center" role="alert" aria-live="assertive"></p>

            <div id="resign-box" class="hidden mb-4">
                <form method="POST" action="/register/resign-in" class="m-0">
                    @csrf
                    <button type="submit"
                            class="block w-full py-2.5 px-4 bg-[#FA6908] hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition">
                        Sign in again
                    </button>
                </form>
                <p class="text-gray-400 text-xs mt-2 text-center">We will text you a new code. Your details are kept.</p>
            </div>

            <div id="password-box">
                <form onsubmit="return savePassword(event)" class="space-y-3" novalidate>
                    <input type="email" id="pw-user" autocomplete="username" class="hidden" tabindex="-1" aria-hidden="true" value="{{ $email }}" />
                    <input type="password" id="pw-1" autocomplete="new-password" placeholder="New password"
                           class="w-full px-3 py-2.5 text-base sm:text-sm border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent" />
                    <input type="password" id="pw-2" autocomplete="new-password" placeholder="Confirm password"
                           class="w-full px-3 py-2.5 text-base sm:text-sm border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent" />
                    <p class="text-xs text-gray-400">At least 8 characters, with a letter and a number.</p>
                    <button type="submit" id="pw-btn"
                            class="w-full py-2.5 px-4 bg-[#FA6908] hover:bg-orange-600 disabled:bg-orange-300 text-white text-sm font-semibold rounded-lg transition">
                        Save password &amp; finish
                    </button>
                    <button type="button" id="skip-btn" onclick="skipPassword()" class="w-full py-1 text-xs text-gray-400 hover:text-gray-600">
                        Skip for now (you can still sign in with a phone code)
                    </button>
                </form>
            </div>

            <div class="space-y-1 mt-6" id="actions">
                <a href="/register" id="edit-link"
                   class="block w-full py-2 text-sm text-center text-gray-500 hover:text-gray-700 transition">
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

        let currentUser = null;   // Firebase user restored from the phone sign-in
        let completing  = false;  // one profile-creation request at a time
        let stopped     = false;  // a hard error (details rejected) or a finished sign-up: no more requests

        function showStatus(msg, color = 'text-green-600') {
            const el = document.getElementById('status-msg');
            el.textContent = msg;
            el.className = `text-sm mb-4 text-center ${color}`;
            el.classList.remove('hidden');
        }

        // The browser has no valid Firebase sign-in: nothing on this page can work, so say so and offer one click out.
        function needSignIn() {
            stopped = true;
            currentUser = null;
            showStatus('Your sign-in expired. Please sign in again.', 'text-red-500');
            document.getElementById('resign-box').classList.remove('hidden');
            document.getElementById('password-box').classList.add('hidden');
            document.getElementById('edit-link').classList.add('hidden');
        }

        const DEAD_SESSION = ['auth/user-token-expired', 'auth/user-disabled', 'auth/user-not-found', 'auth/invalid-user-token'];
        const isDeadSession = (e) => !!e && DEAD_SESSION.includes(e.code);

        const passwordProblem = (pw) => pw.length < 8 ? 'Use at least 8 characters.'
            : (!/[A-Za-z]/.test(pw) || !/\d/.test(pw)) ? 'Include at least one letter and one number.' : '';

        function setBusy(busy) {
            document.getElementById('pw-btn').disabled = busy;
            document.getElementById('skip-btn').disabled = busy;
        }

        function passwordError(e) {
            switch (e && e.code) {
                case 'auth/requires-recent-login':
                    return 'For your security, please sign in again to set your password. Use "Sign in again" below, or skip for now.';
                case 'auth/email-already-in-use':
                case 'auth/credential-already-in-use':
                    return 'That email already belongs to another account. Use "Edit my details" to enter a different email, or skip for now.';
                case 'auth/invalid-email':
                    return 'That email address does not look right. Use "Edit my details" to correct it.';
                case 'auth/weak-password':
                    return 'That password is too weak. Use at least 8 characters with a letter and a number.';
                case 'auth/too-many-requests':
                    return 'Too many attempts. Please wait a few minutes and try again.';
                case 'auth/network-request-failed':
                    return 'Network problem. Check your internet connection and try again.';
                default:
                    return 'We could not save your password. Try again, or skip for now. (ref: ' + ((e && e.code) || 'unknown') + ')';
            }
        }

        // ---- Create the profile (server checks the fresh token again) ----
        async function complete(user) {
            if (completing || stopped) return false;
            completing = true;
            setBusy(true);
            try {
                // A fresh ID token now carries the email, if a password was attached.
                const idToken = await user.getIdToken(true);
                const res = await fetch('/register/complete', {
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
                    showStatus('Setting up your account...', 'text-green-600');
                    stopped = true;   // the profile exists now: never post twice while the browser navigates
                    window.location.href = j.redirect || '/dashboard';
                    return true;
                }

                const ref = j.reference ? ' (ref: ' + j.reference + ')' : '';
                showStatus((j.message || 'We could not finish creating your account. Please try again.') + ref, 'text-red-500');
                if (j.restart) stopped = true;   // the customer has to change something: no point retrying
            } catch (e) {
                if (isDeadSession(e)) { needSignIn(); return false; }
                showStatus('Could not finish creating your account. Please try again.', 'text-red-500');
            } finally {
                completing = false;
                if (!stopped) setBusy(false);
            }
            return false;
        }

        // Attach email + password to the phone account, then finish. The email is NOT verified (owner decision).
        async function savePassword(ev) {
            if (ev) ev.preventDefault();
            if (!currentUser) { needSignIn(); return false; }
            const a = document.getElementById('pw-1').value, b = document.getElementById('pw-2').value;
            const bad = passwordProblem(a);
            if (bad) { showStatus(bad, 'text-red-500'); return false; }
            if (a !== b) { showStatus('The two passwords do not match.', 'text-red-500'); return false; }
            setBusy(true);
            try {
                try {
                    await currentUser.linkWithCredential(firebase.auth.EmailAuthProvider.credential(TARGET_EMAIL, a));
                } catch (e) {
                    if (e && e.code === 'auth/provider-already-linked') { await currentUser.updatePassword(a); } else { throw e; }
                }
            } catch (e) {
                if (isDeadSession(e)) { needSignIn(); return false; }
                showStatus(passwordError(e), 'text-red-500');
                if (e && e.code === 'auth/requires-recent-login') document.getElementById('resign-box').classList.remove('hidden');
                setBusy(false);
                return false;
            }
            setBusy(false);
            await complete(currentUser);
            return false;
        }

        // Never block sign-up on this: phone-code sign-in keeps working without a password.
        async function skipPassword() {
            if (!currentUser) { needSignIn(); return; }
            await complete(currentUser);
        }

        // ---- Start: wait for Firebase to restore the phone sign-in ----
        let unsubscribe = null;
        unsubscribe = auth.onAuthStateChanged((user) => {
            if (unsubscribe) unsubscribe();
            if (!user) { needSignIn(); return; }
            currentUser = user;
            document.getElementById('pw-1').focus();
        });
    </script>

</body>
</html>