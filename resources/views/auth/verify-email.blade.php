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
<body class="min-h-screen flex items-center justify-center bg-[#021F4A] px-4">

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
            <p class="text-gray-500 text-sm mb-2">
                We've sent a verification link to
            </p>
            <p class="text-[#FA6908] font-semibold text-sm mb-6">{{ $email }}</p>

            <p class="text-gray-400 text-xs mb-6">
                Click the link in the email to activate your account.
                Check your spam folder if you don't see it.
            </p>

            {{-- Status message --}}
            <p id="status-msg" class="text-sm mb-4 hidden"></p>

            <div class="space-y-3">
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

        function showStatus(msg, color = 'text-green-600') {
            const el = document.getElementById('status-msg');
            el.textContent = msg;
            el.className = `text-sm mb-4 ${color}`;
            el.classList.remove('hidden');
        }

        // Check if email has been verified
        async function checkVerification() {
            const btn = document.getElementById('check-btn');
            btn.disabled = true;
            btn.textContent = 'Checking...';

            try {
                // Wait for Firebase to reload the user
                await auth.currentUser?.reload();
                const user = auth.currentUser;

                if (user && user.emailVerified) {
                    showStatus('Email verified! Redirecting...', 'text-green-600');
                    // Tell Laravel the email is now verified
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    await fetch('/email/mark-verified', {
                        method: 'POST',
                        credentials: 'include',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    });
                    window.location.href = '/dashboard';
                } else {
                    showStatus('Email not verified yet. Please check your inbox and click the link.', 'text-red-500');
                    btn.disabled = false;
                    btn.textContent = "I've verified my email";
                }
            } catch (e) {
                showStatus('Could not check verification status. Please try again.', 'text-red-500');
                btn.disabled = false;
                btn.textContent = "I've verified my email";
            }
        }

        // Resend verification email
        async function resendEmail() {
            const btn = document.getElementById('resend-btn');
            btn.disabled = true;
            btn.textContent = 'Sending...';

            try {
                const user = auth.currentUser;
                if (user) {
                    await user.sendEmailVerification();
                    showStatus('Verification email sent! Check your inbox.', 'text-green-600');
                } else {
                    showStatus('Session expired. Please log in again.', 'text-red-500');
                }
            } catch (e) {
                if (e.code === 'auth/too-many-requests') {
                    showStatus('Too many attempts. Please wait a few minutes.', 'text-red-500');
                } else {
                    showStatus('Failed to send email. Please try again.', 'text-red-500');
                }
            } finally {
                setTimeout(() => {
                    btn.disabled = false;
                    btn.textContent = 'Resend verification email';
                }, 30000); // 30s cooldown
            }
        }

        // Auto-check every 5 seconds
        setInterval(async () => {
            try {
                await auth.currentUser?.reload();
                if (auth.currentUser?.emailVerified) {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    await fetch('/email/mark-verified', {
                        method: 'POST',
                        credentials: 'include',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    });
                    window.location.href = '/dashboard';
                }
            } catch (e) {}
        }, 5000);
    </script>

</body>
</html>