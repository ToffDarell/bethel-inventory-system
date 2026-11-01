<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In - Bethel Asset Tracker</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-md bg-white border-2 border-slate-200 rounded-xl shadow-sm p-8">
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-xl bg-blue-700 text-white flex items-center justify-center mx-auto mb-4">
                <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Bethel Asset Tracker</h1>
            <p class="text-base text-slate-600 mt-1">{{ \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.') }}</p>
        </div>

        @if (! empty($showInitialCredentialsHint))
            <div class="mb-6 p-4 rounded-lg bg-blue-50 border-2 border-blue-300 text-blue-950 text-sm">
                <p class="font-bold text-base mb-1">First-Time Setup Login</p>
                <p>Email: <code class="font-mono font-bold">admin@hospital.local</code></p>
                <p>Temporary Password: <code class="font-mono font-bold">password123</code></p>
                <p class="text-xs text-blue-800 mt-1">You will be asked to choose a new password after signing in.</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-lg bg-red-50 border-2 border-red-400 text-red-900">
                <p class="font-semibold text-base">{{ $errors->first() }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}" class="space-y-6">
            @csrf

            <div>
                <label for="email" class="block text-base font-bold text-slate-900 mb-2">
                    Email Address <span class="text-red-600">*</span>
                </label>
                <input id="email"
                       name="email"
                       type="email"
                       value="{{ old('email', 'admin@hospital.local') }}"
                       required
                       autofocus
                       class="w-full px-4 py-3 text-lg border-2 border-slate-300 rounded-lg focus:outline-none focus:border-blue-700">
            </div>

            <div>
                <label for="password" class="block text-base font-bold text-slate-900 mb-2">
                    Password <span class="text-red-600">*</span>
                </label>
                <input id="password"
                       name="password"
                       type="password"
                       required
                       class="w-full px-4 py-3 text-lg border-2 border-slate-300 rounded-lg focus:outline-none focus:border-blue-700">
            </div>

            <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-6 py-4 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-bold text-lg shadow-sm transition cursor-pointer">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                <span>Sign In</span>
            </button>
        </form>
    </div>
</body>
</html>
