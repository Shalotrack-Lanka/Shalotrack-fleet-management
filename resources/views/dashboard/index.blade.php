<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard — ShaloTrack Fleet</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center">
    <div class="text-center">
        <h1 class="text-3xl font-bold text-[#021F4A]">
            Shalo<span class="text-[#FA6908]">Track</span>
        </h1>
        <p class="text-gray-500 mt-2">You are logged in. Dashboard coming next.</p>
        <form method="POST" action="/logout" class="mt-6">
            @csrf
            <button type="submit"
                class="px-6 py-2 bg-[#FA6908] text-white rounded-lg hover:bg-orange-600 transition text-sm font-semibold">
                Logout
            </button>
        </form>
    </div>
</body>
</html>