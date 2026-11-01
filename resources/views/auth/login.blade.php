<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In - Bethel Asset Tracker</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-clinical-grid text-neutral-950 font-sans antialiased min-h-screen flex flex-col items-center justify-center p-6">
    @php
        $logoDataUri = app(\App\Services\QrCodeService::class)->hospitalLogoBase64();
    @endphp

    <div class="w-full max-w-md bg-white border-2 border-neutral-900 rounded-2xl shadow-xl overflow-hidden animate-fade-up">
        <!-- Top Red Brand Bar -->
        <div class="h-2 bg-red-600"></div>

        <!-- Official Hospital Logo Header -->
        <div class="px-8 pt-7 pb-5 text-center border-b border-neutral-200 bg-white">
            <img src="{{ $logoDataUri }}"
                 alt="Bethel Baptist Hospital, Inc. Logo"
                 class="h-24 w-auto object-contain mx-auto mb-3">
            <h1 class="text-xl font-extrabold tracking-tight text-neutral-950 uppercase">Bethel Asset Tracker</h1>
            <p class="text-xs text-neutral-600 font-semibold mt-0.5">{{ \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.') }}</p>
            <p class="text-xs text-neutral-500 mt-0.5">{{ \App\Models\Setting::get('hospital_address', 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700') }}</p>
        </div>

        <div class="p-8">
            @if (! empty($showInitialCredentialsHint))
                <div class="mb-6 p-4 rounded-xl bg-neutral-950 border-l-4 border-red-600 text-white text-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-bold text-base text-red-400 mb-1">First-Time Setup Login</p>
                            <p class="text-neutral-200">Email: <code class="font-mono font-bold text-white">admin@hospital.local</code></p>
                            <p class="text-neutral-200">Temporary Password: <code class="font-mono font-bold text-white">password123</code></p>
                        </div>
                        <button type="button"
                                onclick="document.getElementById('email').value='admin@hospital.local'; document.getElementById('password').value='password123'; document.getElementById('password').focus();"
                                class="px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white font-bold text-xs shrink-0 cursor-pointer transition">
                            Auto-Fill
                        </button>
                    </div>
                    <p class="text-xs text-neutral-400 mt-2">You will be asked to choose your own permanent password right after signing in.</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-50 border-2 border-red-600 text-red-950 flex items-start gap-3" role="alert">
                    <svg class="w-6 h-6 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="font-bold text-base">{{ $errors->first() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-base font-bold text-neutral-950 mb-2">
                        Email Address <span class="text-red-600">*</span>
                    </label>
                    <input id="email"
                           name="email"
                           type="email"
                           value="{{ old('email', 'admin@hospital.local') }}"
                           required
                           autofocus
                           class="w-full px-4 py-3.5 text-lg border-2 border-neutral-300 rounded-xl bg-white focus:outline-none focus:border-red-600 transition">
                </div>

                <div>
                    <label for="password" class="block text-base font-bold text-neutral-950 mb-2">
                        Password <span class="text-red-600">*</span>
                    </label>
                    <div class="relative">
                        <input id="password"
                               name="password"
                               type="password"
                               required
                               placeholder="Enter your password"
                               class="w-full pl-4 pr-20 py-3.5 text-lg border-2 border-neutral-300 rounded-xl bg-white focus:outline-none focus:border-red-600 transition">
                        <button type="button"
                                onclick="const p = document.getElementById('password'); const isPwd = p.type === 'password'; p.type = isPwd ? 'text' : 'password'; this.textContent = isPwd ? 'Hide' : 'Show';"
                                aria-label="Toggle password visibility"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 px-3 py-1.5 rounded-lg text-xs font-bold text-neutral-800 bg-neutral-100 hover:bg-neutral-200 cursor-pointer">
                            Show
                        </button>
                    </div>
                </div>

                <div class="pt-1">
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2.5 px-6 py-4 rounded-xl bg-red-600 hover:bg-red-700 active:translate-y-[1px] text-white font-bold text-lg shadow-sm transition cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        <span>Sign In</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <p class="mt-6 text-xs font-bold text-neutral-600">
        Bethel Baptist Hospital, Inc. &bull; Offline Desktop Property Register
    </p>
</body>
</html>
