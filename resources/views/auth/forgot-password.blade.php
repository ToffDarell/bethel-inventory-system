<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Password Recovery - Bethel Asset Tracker</title>
    <link rel="icon" type="image/png" href="{{ asset('icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-slate-900 font-sans antialiased h-full h-[100dvh] overflow-hidden selection:bg-slate-200 selection:text-slate-900">
    @php
        $logoDataUri = app(\App\Services\TagAssetService::class)->hospitalLogoBase64();
        $hospitalName = \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.');
        $hospitalAddress = \App\Models\Setting::get('hospital_address', 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700');
    @endphp

    <div class="h-full h-[100dvh] w-full flex overflow-hidden">
        <div class="hidden min-[900px]:block w-[55%] h-full relative shrink-0">
            <div class="auth-diagonal-outer w-full h-full bg-brand-red relative">
                <div class="auth-diagonal-inner w-full h-full bg-[#F1F5F9] flex flex-col justify-between p-5 xl:p-8 pr-12 xl:pr-16 relative">
                    <div class="flex items-center justify-between text-xs text-slate-600 font-medium select-none pr-8">
                        <span>Hospital Property Register</span>
                        <span class="text-[11px] text-slate-600 font-mono">Offline Account Recovery</span>
                    </div>

                    <div class="my-auto py-2 flex flex-col items-center text-center">
                        <img src="{{ asset('images/bethel-login-illustration.svg') }}"
                             alt=""
                             class="w-[520px] max-w-[90%] max-h-[46vh] xl:max-h-[50vh] object-contain mx-auto auth-anim-illustration">

                        <div class="mt-3 xl:mt-4 auth-anim-field auth-delay-1">
                            <h2 class="text-base xl:text-lg font-bold tracking-tight text-slate-900">
                                {{ $hospitalName }}
                            </h2>
                            <p class="text-xs xl:text-sm font-medium text-slate-700 italic mt-0.5">
                                Preach the Word, Heal the Sick
                            </p>
                            <p class="text-[11px] xl:text-xs text-slate-600 mt-1 max-w-sm mx-auto">
                                {{ $hospitalAddress }}
                            </p>
                        </div>
                    </div>

                    <div class="pt-2 pr-28 xl:pr-32">
                        <div class="flex items-center justify-between text-[10px] font-semibold text-slate-500 mb-1">
                            <span>Recovery Verification</span>
                            <span class="text-brand-red font-mono text-[9px] tracking-wider">ONE-TIME KEY</span>
                        </div>
                        <div class="p-3 bg-white/80 rounded-lg border border-slate-200/80 text-xs text-slate-600 leading-relaxed">
                            Use the one-time Admin Recovery Code generated during setup or in Settings. Staff accounts can request a temporary password reset from an administrator.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 w-full min-[900px]:w-[45%] h-full bg-white flex flex-col justify-center items-center px-6 sm:px-10 lg:px-12 py-5 overflow-y-auto">
            <div class="w-full max-w-[420px] my-auto py-4">
                <div class="min-[900px]:hidden flex items-center gap-3 mb-4 pb-3 border-b border-slate-100 justify-center text-center">
                    <img src="{{ $logoDataUri }}" alt="Bethel Baptist Hospital Logo" class="h-8 w-auto object-contain">
                    <div class="text-left">
                        <p class="text-xs font-bold text-slate-900 leading-tight">Bethel Asset Tracker</p>
                        <p class="text-[10px] text-slate-500 font-medium">Admin Password Recovery</p>
                    </div>
                </div>

                <div class="auth-anim-field auth-delay-1 mb-3 hidden min-[900px]:flex justify-start">
                    <img src="{{ $logoDataUri }}" alt="Bethel Baptist Hospital Logo" class="h-9 w-auto object-contain">
                </div>

                <div class="auth-anim-field auth-delay-2 mb-4">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                        Reset admin password
                    </h1>
                    <p class="text-xs text-slate-600 mt-1">
                        Enter your administrator username and your saved Admin Recovery Code.
                    </p>
                </div>

                @if ($errors->any())
                    <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-950 flex items-start gap-2 shadow-2xs" role="alert">
                        <svg class="w-4 h-4 text-brand-red shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-xs">
                            <p class="font-semibold">{{ $errors->first() }}</p>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.forgot.submit') }}" class="space-y-3.5">
                    @csrf

                    <div>
                        <label for="username" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Administrator Username <span class="text-brand-red" aria-hidden="true">*</span>
                        </label>
                        <input id="username"
                               name="username"
                               type="text"
                               value="{{ old('username') }}"
                               required
                               autofocus
                               placeholder="Enter your admin username"
                               autocomplete="username"
                               class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                    </div>

                    <div>
                        <label for="recovery_code" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Admin Recovery Code <span class="text-brand-red" aria-hidden="true">*</span>
                        </label>
                        <input id="recovery_code"
                               name="recovery_code"
                               type="text"
                               value="{{ old('recovery_code') }}"
                               required
                               placeholder="BBH-XXXX-XXXX-XXXX"
                               autocomplete="off"
                               class="w-full min-h-[44px] px-3.5 py-2 text-sm font-mono uppercase bg-white border border-slate-300 rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            New Password (minimum 10 characters) <span class="text-brand-red" aria-hidden="true">*</span>
                        </label>
                        <div class="relative">
                            <input id="password"
                                   name="password"
                                   type="password"
                                   required
                                   minlength="10"
                                   placeholder="Enter a new strong password"
                                   autocomplete="new-password"
                                   oninput="checkPasswordMatch()"
                                   class="w-full min-h-[44px] pl-3.5 pr-11 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                            <button type="button"
                                    onclick="togglePasswordVisibility('password', this)"
                                    aria-label="Show password"
                                    aria-pressed="false"
                                    class="absolute inset-y-0 right-0 w-11 flex items-center justify-center text-slate-400 hover:text-slate-600 focus:outline-none focus:text-primary transition-colors cursor-pointer">
                                <svg data-eye-open class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <svg data-eye-closed class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                </svg>
                            </button>
                        </div>
                        <p id="password-length-msg" class="text-[11px] mt-1 font-medium text-slate-400" aria-live="polite">Minimum 10 characters required.</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Confirm New Password <span class="text-brand-red" aria-hidden="true">*</span>
                        </label>
                        <div class="relative">
                            <input id="password_confirmation"
                                   name="password_confirmation"
                                   type="password"
                                   required
                                   minlength="10"
                                   placeholder="Repeat the new password"
                                   autocomplete="new-password"
                                   oninput="checkPasswordMatch()"
                                   class="w-full min-h-[44px] pl-3.5 pr-11 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                            <button type="button"
                                    onclick="togglePasswordVisibility('password_confirmation', this)"
                                    aria-label="Show confirm password"
                                    aria-pressed="false"
                                    class="absolute inset-y-0 right-0 w-11 flex items-center justify-center text-slate-400 hover:text-slate-600 focus:outline-none focus:text-primary transition-colors cursor-pointer">
                                <svg data-eye-open class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <svg data-eye-closed class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                </svg>
                            </button>
                        </div>
                        <p id="password-match-msg" class="text-[11px] mt-1.5 font-semibold hidden" aria-live="polite"></p>
                    </div>

                    <div class="pt-1 space-y-2">
                        <button type="submit"
                                class="w-full min-h-[46px] flex items-center justify-center gap-2 px-6 py-2.5 rounded-lg bg-primary hover:bg-primary-hover text-white font-semibold text-sm shadow-xs transition-colors cursor-pointer">
                            Reset Password
                        </button>
                        <a href="{{ route('login') }}"
                           class="w-full min-h-[40px] flex items-center justify-center text-xs font-semibold text-slate-600 hover:text-slate-900">
                            Back to Sign In
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
            const openIcon = btn.querySelector('[data-eye-open]');
            const closedIcon = btn.querySelector('[data-eye-closed]');
            if (openIcon && closedIcon) {
                openIcon.classList.toggle('hidden', isHidden);
                closedIcon.classList.toggle('hidden', !isHidden);
            }
        }

        function checkPasswordMatch() {
            const pwInput = document.getElementById('password');
            const confirmInput = document.getElementById('password_confirmation');
            const matchMsg = document.getElementById('password-match-msg');
            const lengthMsg = document.getElementById('password-length-msg');
            if (!pwInput || !confirmInput || !matchMsg) return;

            const pw = pwInput.value;
            const confirmPw = confirmInput.value;

            if (lengthMsg) {
                if (!pw) {
                    lengthMsg.textContent = 'Minimum 10 characters required.';
                    lengthMsg.className = 'text-[11px] mt-1 font-medium text-slate-400';
                } else if (pw.length < 10) {
                    lengthMsg.textContent = pw.length + ' / 10 characters minimum';
                    lengthMsg.className = 'text-[11px] mt-1 font-semibold text-amber-600';
                } else {
                    lengthMsg.textContent = 'Minimum length met (' + pw.length + ' characters)';
                    lengthMsg.className = 'text-[11px] mt-1 font-semibold text-emerald-700';
                }
            }

            if (!confirmPw) {
                matchMsg.textContent = '';
                matchMsg.className = 'text-[11px] mt-1.5 font-semibold hidden';
                confirmInput.classList.remove('border-emerald-500', 'border-red-500');
                confirmInput.classList.add('border-slate-300');
                return;
            }

            if (pw === confirmPw) {
                matchMsg.textContent = '\u2713 Passwords match';
                matchMsg.className = 'text-[11px] mt-1.5 font-semibold text-emerald-700';
                confirmInput.classList.remove('border-slate-300', 'border-red-500');
                confirmInput.classList.add('border-emerald-500');
            } else {
                matchMsg.textContent = '\u2717 Passwords do not match';
                matchMsg.className = 'text-[11px] mt-1.5 font-semibold text-red-600';
                confirmInput.classList.remove('border-slate-300', 'border-emerald-500');
                confirmInput.classList.add('border-red-500');
            }
        }
    </script>
</body>
</html>
