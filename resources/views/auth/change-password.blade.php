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

            <form method="POST" action="{{ route('password.change.update') }}" class="space-y-4" id="change-password-form">
                @csrf

                <div>
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        New Password (at least 8 characters) <span class="text-[#b91c1c]">*</span>
                    </label>
                    <div class="relative">
                        <input id="password"
                               name="password"
                               type="password"
                               required
                               autofocus
                               minlength="8"
                               placeholder="Enter new private password"
                               class="w-full min-h-[48px] pl-3.5 pr-12 py-2.5 text-base bg-white border border-slate-300 rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:border-[#0f5d8c] focus:ring-1 focus:ring-[#0f5d8c] transition-colors">
                        <button type="button"
                                onclick="togglePasswordVisibility('password', this)"
                                aria-label="Toggle password visibility"
                                title="Show / hide password"
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-2 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 cursor-pointer transition-colors">
                            <svg class="w-5 h-5 eye-open" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <svg class="w-5 h-5 eye-slash hidden" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Confirm New Password <span class="text-[#b91c1c]">*</span>
                    </label>
                    <div class="relative">
                        <input id="password_confirmation"
                               name="password_confirmation"
                               type="password"
                               required
                               placeholder="Re-type new password"
                               class="w-full min-h-[48px] pl-3.5 pr-12 py-2.5 text-base bg-white border border-slate-300 rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:border-[#0f5d8c] focus:ring-1 focus:ring-[#0f5d8c] transition-colors">
                        <button type="button"
                                onclick="togglePasswordVisibility('password_confirmation', this)"
                                aria-label="Toggle confirm password visibility"
                                title="Show / hide password"
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-2 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 cursor-pointer transition-colors">
                            <svg class="w-5 h-5 eye-open" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <svg class="w-5 h-5 eye-slash hidden" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Live Password Match Indicator -->
                    <div id="password-match-container" class="mt-2 text-xs font-semibold hidden">
                        <div id="match-success" class="flex items-center gap-1.5 text-emerald-700 hidden">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Passwords match</span>
                        </div>
                        <div id="match-error" class="flex items-center gap-1.5 text-amber-700 hidden">
                            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span>Passwords do not match</span>
                        </div>
                        <div id="match-length" class="flex items-center gap-1.5 text-slate-500 hidden">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>Password must be at least 8 characters</span>
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit"
                            id="submit-password-btn"
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

    <script>
        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            const openIcon = btn.querySelector('.eye-open');
            const slashIcon = btn.querySelector('.eye-slash');
            if (openIcon && slashIcon) {
                if (isPassword) {
                    openIcon.classList.add('hidden');
                    slashIcon.classList.remove('hidden');
                } else {
                    openIcon.classList.remove('hidden');
                    slashIcon.classList.add('hidden');
                }
            }
        }

        const pwdInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirmation');
        const matchContainer = document.getElementById('password-match-container');
        const matchSuccess = document.getElementById('match-success');
        const matchError = document.getElementById('match-error');
        const matchLength = document.getElementById('match-length');

        function checkPasswordMatch() {
            const pwd = pwdInput.value;
            const confirm = confirmInput.value;

            if (!confirm && !pwd) {
                matchContainer.classList.add('hidden');
                return;
            }

            matchContainer.classList.remove('hidden');

            if (pwd.length < 8) {
                matchLength.classList.remove('hidden');
                matchSuccess.classList.add('hidden');
                matchError.classList.add('hidden');
                return;
            } else {
                matchLength.classList.add('hidden');
            }

            if (!confirm) {
                matchSuccess.classList.add('hidden');
                matchError.classList.add('hidden');
                return;
            }

            if (pwd === confirm) {
                matchSuccess.classList.remove('hidden');
                matchError.classList.add('hidden');
            } else {
                matchSuccess.classList.add('hidden');
                matchError.classList.remove('hidden');
            }
        }

        pwdInput.addEventListener('input', checkPasswordMatch);
        confirmInput.addEventListener('input', checkPasswordMatch);
    </script>
</body>
</html>
