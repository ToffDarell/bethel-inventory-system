<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In - Bethel Asset Tracker</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased min-h-screen flex flex-col items-center justify-center p-6 selection:bg-slate-200 selection:text-slate-900">
    @php
        $logoDataUri = app(\App\Services\QrCodeService::class)->hospitalLogoBase64();
        $hospitalName = \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.');
        $hospitalAddress = \App\Models\Setting::get('hospital_address', 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700');
    @endphp

    <div class="w-full max-w-md bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <!-- Top Institutional Accent Line (3px) -->
        <div class="h-[3px] w-full bg-[#b91c1c]"></div>

        <!-- Official Hospital Logo Header -->
        <div class="px-8 pt-8 pb-6 text-center border-b border-slate-100 bg-white">
            <div class="inline-flex items-center justify-center p-2 mb-3 bg-white">
                <img src="{{ $logoDataUri }}"
                     alt="Bethel Baptist Hospital, Inc. Logo"
                     class="h-20 w-auto object-contain mx-auto">
            </div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900 uppercase">Bethel Asset Tracker</h1>
            <p class="text-xs font-semibold text-slate-700 mt-1">{{ $hospitalName }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5">{{ $hospitalAddress }}</p>
        </div>

        <div class="p-8">
            <!-- First-Time Setup Credentials Hint -->
            @if (! empty($showInitialCredentialsHint))
                <div class="mb-6 p-4 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 text-xs shadow-2xs">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-sm text-slate-900 mb-1">First-Time Setup Login</p>
                            <p class="text-slate-600">Email: <code class="font-mono font-semibold text-slate-900">admin@hospital.local</code></p>
                            <p class="text-slate-600">Password: <code class="font-mono font-semibold text-slate-900">password123</code></p>
                        </div>
                        <button type="button"
                                onclick="document.getElementById('email').value='admin@hospital.local'; document.getElementById('password').value='password123'; document.getElementById('password').focus();"
                                class="min-h-[36px] px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs shrink-0 cursor-pointer transition-colors shadow-2xs">
                            Auto-Fill
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-2">You will be prompted to set your personal administrator password immediately after signing in.</p>
                </div>
            @endif

            <!-- Errors Banner -->
            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-950 flex items-start gap-3 shadow-2xs" role="alert">
                    <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-xs">
                        <p class="font-semibold text-sm mb-0.5">{{ $errors->first() }}</p>
                        <p class="text-red-800">Please verify your email credentials or contact the hospital system administrator.</p>
                    </div>
                </div>
            @endif

            @if (session('success'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-950 flex items-start gap-3 shadow-2xs" role="alert">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs font-semibold">{{ session('success') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Email Address <span class="text-[#b91c1c]">*</span>
                    </label>
                    <input id="email"
                           name="email"
                           type="email"
                           value="{{ old('email', 'admin@hospital.local') }}"
                           required
                           autofocus
                           placeholder="staff@hospital.local"
                           class="w-full min-h-[48px] px-3.5 py-2.5 text-base bg-white border border-slate-300 rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:border-[#0f5d8c] focus:ring-1 focus:ring-[#0f5d8c] transition-colors">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                            Password <span class="text-[#b91c1c]">*</span>
                        </label>
                    </div>
                    <div class="relative">
                        <input id="password"
                               name="password"
                               type="password"
                               required
                               placeholder="Enter your password"
                               class="w-full min-h-[48px] pl-3.5 pr-20 py-2.5 text-base bg-white border border-slate-300 rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:border-[#0f5d8c] focus:ring-1 focus:ring-[#0f5d8c] transition-colors">
                        <button type="button"
                                onclick="const p = document.getElementById('password'); const isPwd = p.type === 'password'; p.type = isPwd ? 'text' : 'password'; this.textContent = isPwd ? 'Hide' : 'Show';"
                                aria-label="Toggle password visibility"
                                class="absolute right-2 top-1/2 -translate-y-1/2 min-h-[36px] px-3 py-1 rounded-md text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 cursor-pointer transition-colors">
                            Show
                        </button>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="w-full min-h-[48px] flex items-center justify-center gap-2.5 px-6 py-3 rounded-lg bg-[#0f5d8c] hover:bg-[#0c4a70] active:translate-y-[1px] text-white font-semibold text-base shadow-xs transition-colors cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                        </svg>
                        <span>Sign In</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <p class="mt-6 text-xs text-slate-500 font-medium">
        Bethel Baptist Hospital, Inc. &bull; Offline Desktop Property Register
    </p>
</body>
</html>
