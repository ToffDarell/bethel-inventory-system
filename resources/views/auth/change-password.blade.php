<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Change Temporary Password - Bethel Asset Tracker</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-lg bg-white border-2 border-slate-200 rounded-xl shadow-sm p-8">
        <div class="mb-6">
            <span class="inline-block px-3 py-1 rounded-full bg-amber-100 text-amber-900 font-bold text-sm mb-3">
                First-Time Sign In Security Step
            </span>
            <h1 class="text-2xl font-bold text-slate-900">Set Your New Password</h1>
            <p class="text-base text-slate-700 mt-2">
                You signed in with a temporary password. Please choose a new password before continuing to the asset tracker.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-lg bg-red-50 border-2 border-red-400 text-red-900">
                <p class="font-semibold text-base">{{ $errors->first() }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('password.change.update') }}" class="space-y-6">
            @csrf

            <div>
                <label for="password" class="block text-base font-bold text-slate-900 mb-2">
                    New Password (at least 8 characters) <span class="text-red-600">*</span>
                </label>
                <input id="password"
                       name="password"
                       type="password"
                       required
                       autofocus
                       class="w-full px-4 py-3 text-lg border-2 border-slate-300 rounded-lg focus:outline-none focus:border-blue-700">
            </div>

            <div>
                <label for="password_confirmation" class="block text-base font-bold text-slate-900 mb-2">
                    Confirm New Password <span class="text-red-600">*</span>
                </label>
                <input id="password_confirmation"
                       name="password_confirmation"
                       type="password"
                       required
                       class="w-full px-4 py-3 text-lg border-2 border-slate-300 rounded-lg focus:outline-none focus:border-blue-700">
            </div>

            <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-6 py-4 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-bold text-lg shadow-sm transition cursor-pointer">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Save New Password &amp; Continue</span>
            </button>
        </form>
    </div>
</body>
</html>
