<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Set New Password - Bethel Asset Tracker</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased min-h-screen flex items-center justify-center p-6 selection:bg-slate-200 selection:text-slate-900">
    @php
        $logoDataUri = app(\App\Services\QrCodeService::class)->hospitalLogoBase64();
        $hospitalName = \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.');
    @endphp

    <div class="w-full max-w-lg bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <!-- Top Institutional Accent Line (3px) -->
        <div class="h-[3px] w-full bg-[#b91c1c]"></div>

        <div class="p-8">
            <div class="flex items-center gap-4 pb-6 mb-6 border-b border-slate-100">
                <div class="p-2 border border-slate-200 rounded-xl bg-white shrink-0 shadow-2xs">
                    <img src="{{ $logoDataUri }}" alt="Bethel Baptist Hospital, Inc. Logo" class="h-14 w-auto object-contain">
                </div>
                <div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-slate-100 border border-slate-200 text-slate-700 font-medium text-xs mb-1">
                        First-Time Sign In Security Step
                    </span>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">Set Your New Password</h1>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $hospitalName }}</p>
                </div>
            </div>

            <p class="text-sm text-slate-600 mb-6 leading-relaxed">
                You signed in with a temporary password. Please choose a private password to secure your hospital account before continuing.
            </p>

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-950 flex items-start gap-3 shadow-2xs" role="alert">
                    <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-semibold">{{ $errors->first() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('password.change.update') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        New Password (at least 8 characters) <span class="text-[#b91c1c]">*</span>
                    </label>
                    <input id="password"
                           name="password"
                           type="password"
                           required
                           autofocus
                           placeholder="Enter new private password"
                           class="w-full min-h-[48px] px-3.5 py-2.5 text-base bg-white border border-slate-300 rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:border-[#0f5d8c] focus:ring-1 focus:ring-[#0f5d8c] transition-colors">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Confirm New Password <span class="text-[#b91c1c]">*</span>
                    </label>
                    <input id="password_confirmation"
                           name="password_confirmation"
                           type="password"
                           required
                           placeholder="Re-type new password"
                           class="w-full min-h-[48px] px-3.5 py-2.5 text-base bg-white border border-slate-300 rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:border-[#0f5d8c] focus:ring-1 focus:ring-[#0f5d8c] transition-colors">
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="w-full min-h-[48px] flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-[#0f5d8c] hover:bg-[#0c4a70] active:translate-y-[1px] text-white font-semibold text-base shadow-xs transition-colors cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Save New Password &amp; Continue</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
