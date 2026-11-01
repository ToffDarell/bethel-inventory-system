<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Change Temporary Password - Bethel Asset Tracker</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-clinical-grid text-neutral-950 font-sans antialiased min-h-screen flex items-center justify-center p-6">
    @php
        $logoDataUri = app(\App\Services\QrCodeService::class)->hospitalLogoBase64();
    @endphp

    <div class="w-full max-w-lg bg-white border-2 border-neutral-900 rounded-2xl shadow-xl overflow-hidden animate-fade-up">
        <div class="h-2 bg-red-600"></div>

        <div class="p-8">
            <div class="flex items-center gap-4 pb-5 mb-6 border-b border-neutral-200">
                <img src="{{ $logoDataUri }}" alt="Bethel Baptist Hospital, Inc. Logo" class="h-16 w-auto object-contain shrink-0">
                <div>
                    <span class="inline-block px-3 py-0.5 rounded-full bg-red-100 text-red-900 font-bold text-xs uppercase tracking-wider mb-1">
                        First-Time Sign In Security Step
                    </span>
                    <h1 class="text-2xl font-extrabold text-neutral-950">Set Your New Password</h1>
                </div>
            </div>

            <p class="text-base text-neutral-700 mb-6">
                You signed in with a temporary password. Please choose a new password before continuing to the asset tracker.
            </p>

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-50 border-2 border-red-600 text-red-950">
                    <p class="font-bold text-base">{{ $errors->first() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('password.change.update') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="password" class="block text-base font-bold text-neutral-950 mb-2">
                        New Password (at least 8 characters) <span class="text-red-600">*</span>
                    </label>
                    <input id="password"
                           name="password"
                           type="password"
                           required
                           autofocus
                           class="w-full px-4 py-3.5 text-lg border-2 border-neutral-300 rounded-xl focus:outline-none focus:border-red-600 transition">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-base font-bold text-neutral-950 mb-2">
                        Confirm New Password <span class="text-red-600">*</span>
                    </label>
                    <input id="password_confirmation"
                           name="password_confirmation"
                           type="password"
                           required
                           class="w-full px-4 py-3.5 text-lg border-2 border-neutral-300 rounded-xl focus:outline-none focus:border-red-600 transition">
                </div>

                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 px-6 py-4 rounded-xl bg-red-600 hover:bg-red-700 active:translate-y-[1px] text-white font-bold text-lg shadow-sm transition cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Save New Password &amp; Continue</span>
                </button>
            </form>
        </div>
    </div>
</body>
</html>
