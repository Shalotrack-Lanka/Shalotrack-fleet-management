<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Create Account — ShaloTrack Fleet</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen flex items-center justify-center bg-[#021F4A] px-4 py-8">

    <div class="w-full max-w-md">

        {{-- Logo --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white tracking-tight">
                Shalo<span class="text-[#FA6908]">Track</span>
            </h1>
            <p class="text-blue-200 text-sm mt-2">Fleet Management Portal</p>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-8">

            <h2 class="text-xl font-semibold text-gray-800 mb-1">Complete your profile</h2>
            <p class="text-gray-500 text-sm mb-6">
                Welcome! You're signed in with
                <strong class="text-gray-700">{{ $phone }}</strong>.
                Fill in your details to get started.
            </p>

            {{-- Error banner --}}
            @if($errors->any())
                <div class="mb-5 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-start gap-2">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="/register" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" name="fullName" value="{{ old('fullName', $pending['fullName'] ?? '') }}"
                           placeholder="John Silva" maxlength="150" autocomplete="name" data-person-name
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-base sm:text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent transition @error('fullName') border-red-400 @enderror"
                           required autofocus />
                    @error('fullName')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                    <input type="email" name="email" value="{{ old('email', $pending['email'] ?? '') }}"
                           placeholder="john@example.com"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-base sm:text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent transition @error('email') border-red-400 @enderror"
                           required />
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                    <input type="hidden" name="keepEmail" value="" />
                    @if (session('email_suggestion'))
                        <div class="mt-2 flex gap-2 text-xs">
                            <button type="button" id="use-suggestion"
                                    onclick="const f=this.form; f.elements.email.value=@js(session('email_suggestion')); f.elements.keepEmail.value=''; f.requestSubmit();"
                                    class="px-3 py-1.5 bg-[#FA6908] text-white font-semibold rounded-lg">Use {{ session('email_suggestion') }}</button>
                            <button type="button" id="keep-email"
                                    onclick="const f=this.form; f.elements.keepEmail.value='1'; f.requestSubmit();"
                                    class="px-3 py-1.5 border border-gray-300 text-gray-600 rounded-lg">Keep what I typed</button>
                        </div>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NIC Number *</label>
                    <input type="text" name="nicNumber" value="{{ old('nicNumber', $pending['nicNumber'] ?? '') }}"
                           placeholder="200175906423 or 980923858V"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-base sm:text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent transition font-mono @error('nicNumber') border-red-400 @enderror"
                           required />
                    @error('nicNumber')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea name="address" rows="2" maxlength="300" autocomplete="street-address" data-address
                              placeholder="123/A, Main Street, Colombo"
                              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-base sm:text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent transition resize-none">{{ old('address', $pending['address'] ?? '') }}</textarea>
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="w-full py-2.5 px-4 bg-[#FA6908] hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition">
                        Create Account
                    </button>
                </div>
            </form>

            {{-- Login link --}}
            <div class="mt-6 pt-5 border-t border-gray-100 text-center">
                {{-- <div>, not <p>: a <form> is not allowed inside <p>; the parser would close the <p> early and split the line. --}}
                <div class="text-sm text-gray-500">
                    Already have an account?
                    <form method="POST" action="/logout-and-login" class="inline m-0">
                        @csrf
                        <button type="submit" class="text-[#FA6908] font-medium hover:underline bg-transparent border-0 p-0 cursor-pointer">
                            Sign in with a different number
                        </button>
                    </form>
                </div>
            </div>

        </div>

        <p class="text-center text-blue-200 text-xs mt-6">
            © {{ date('Y') }} ShaloTrack Lanka (Pvt) Ltd
        </p>
    </div>

<script>
    // Full name: letters (any language), spaces, . ' - only, as you type. The server checks the same rules.
    (function () {
        const BAD = /[^\p{L}\p{M}\u200D .'’\-]/gu;
        document.addEventListener('input', function (e) {
            const el = e.target;
            if (!el || !el.matches || !el.matches('[data-person-name]') || e.isComposing) return;
            const clean = el.value.replace(BAD, '');
            if (clean !== el.value) {
                const pos = el.selectionStart - (el.value.length - clean.length);
                el.value = clean;
                try { el.setSelectionRange(Math.max(0, pos), Math.max(0, pos)); } catch (err) { /* not supported */ }
            }
        });
        // Address: letters, numbers, spaces, comma, slash, full stop and hyphen only.
        const ADDR_BAD = /[^\p{L}\p{M}\u200D0-9 ,.\/-]/gu;
        document.addEventListener('input', function (e) {
            const el = e.target;
            if (!el || !el.matches || !el.matches('[data-address]') || e.isComposing) return;
            const clean = el.value.replace(/[\r\n\t]+/g, ' ').replace(ADDR_BAD, '');
            if (clean !== el.value) {
                const pos = el.selectionStart - (el.value.length - clean.length);
                el.value = clean;
                try { el.setSelectionRange(Math.max(0, pos), Math.max(0, pos)); } catch (err) { /* not supported */ }
            }
        });
    })();
</script>
</body>
</html>