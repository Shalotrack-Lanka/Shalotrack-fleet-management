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
        <div class="bg-white rounded-2xl shadow-2xl p-8">

            <h2 class="text-xl font-semibold text-gray-800 mb-1">Complete your profile</h2>
            <p class="text-gray-500 text-sm mb-6">
                Welcome! You're registered with
                <strong class="text-gray-700">{{ $phone }}</strong>.
                Fill in your details to continue.
            </p>

            {{-- Errors --}}
            @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="/register" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" name="fullName" value="{{ old('fullName') }}"
                           placeholder="John Silva"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent transition"
                           required />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           placeholder="john@example.com"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent transition"
                           required />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NIC Number *</label>
                    <input type="text" name="nicNumber" value="{{ old('nicNumber') }}"
                           placeholder="200271901539 or 880123456V"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent transition font-mono"
                           required />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea name="address" rows="2"
                              placeholder="No. 123, Main Street, Colombo"
                              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#FA6908] focus:border-transparent transition resize-none">{{ old('address') }}</textarea>
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="w-full py-2.5 px-4 bg-[#FA6908] hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition">
                        Create Account
                    </button>
                </div>

                <p class="text-center text-xs text-gray-400">
                    By creating an account you agree to ShaloTrack's terms of service.
                </p>
            </form>
        </div>

        <p class="text-center text-blue-200 text-xs mt-6">
            © {{ date('Y') }} ShaloTrack Lanka (Pvt) Ltd
        </p>
    </div>

</body>
</html>