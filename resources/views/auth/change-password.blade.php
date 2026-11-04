<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Set New Password - Bethel Asset Tracker</title>
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
        <!-- LEFT (about 55%): Brand panel with diagonal cut (hidden below 900px) -->
        <div class="hidden min-[900px]:block w-[55%] h-full relative shrink-0">
            <!-- Diagonal Accent Container with Crimson Right Edge -->
            <div class="auth-diagonal-outer w-full h-full bg-brand-red relative">
                <!-- Inner Panel with #F1F5F9 Background -->
                <div class="auth-diagonal-inner w-full h-full bg-[#F1F5F9] flex flex-col justify-between p-5 xl:p-8 pr-12 xl:pr-16 relative">
                    <!-- Top Minimal Hospital Meta Pill -->
                    <div class="flex items-center justify-between text-xs text-slate-600 font-medium select-none pr-8">
                        <span>Account Security Setup</span>
                        <span class="text-[11px] text-slate-600 font-mono">Station ID: BBH-DESK-01</span>
                    </div>

                    <!-- Centered Illustration & Institutional Typography -->
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

                    <!-- Bottom Crimson ECG Heartbeat Line (Offset from diagonal cut) -->
                    <div class="pt-2 pr-28 xl:pr-32">
                        <div class="flex items-center justify-between text-[10px] font-semibold text-slate-500 mb-1">
                            <span>Clinical Security Protocol</span>
                            <span class="text-brand-red font-mono text-[9px] tracking-wider">PROTECTED</span>
                        </div>
                        <div class="relative w-full h-8 xl:h-9 bg-white/70 rounded-lg border border-slate-200/80 p-1 overflow-hidden flex items-center justify-center">
                            <svg viewBox="0 0 500 80" class="w-full h-full text-brand-red" fill="none" preserveAspectRatio="none">
                                <path d="M0,40 L100,40 L115,35 L125,45 L135,40 L160,40 L170,12 L180,68 L190,5 L200,52 L208,40 L240,40 L250,33 L260,40 L340,40 L350,12 L360,68 L370,5 L380,52 L388,40 L500,40"
                                      stroke="currentColor"
                                      stroke-width="1.5"
                                      stroke-opacity="0.25"
                                      stroke-linecap="round"
                                      stroke-linejoin="round" />
                                <path d="M0,40 L100,40 L115,35 L125,45 L135,40 L160,40 L170,12 L180,68 L190,5 L200,52 L208,40 L240,40 L250,33 L260,40 L340,40 L350,12 L360,68 L370,5 L380,52 L388,40 L500,40"
                                      stroke="currentColor"
                                      stroke-width="2.25"
                                      stroke-linecap="round"
                                      stroke-linejoin="round"
                                      class="ecg-pulse-line" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT (about 45%): White Panel with Form, vertically centered -->
        <div class="flex-1 w-full min-[900px]:w-[45%] h-full bg-white flex flex-col justify-center items-center px-6 sm:px-10 lg:px-12 py-5 overflow-y-auto min-[900px]:overflow-hidden">
            <div class="w-full max-w-[400px] my-auto">
                <!-- Mobile / below 900px compact header -->
                <div class="min-[900px]:hidden flex items-center gap-3 mb-6 pb-4 border-b border-slate-100 justify-center text-center">
                    <img src="{{ $logoDataUri }}" alt="Bethel Baptist Hospital Logo" class="h-8 w-auto object-contain">
                    <div class="text-left">
                        <p class="text-xs font-bold text-slate-900 leading-tight">Bethel Asset Tracker</p>
                        <p class="text-[10px] text-slate-500 font-medium">Account Security Setup</p>
                    </div>
                </div>

                <!-- Small Logo Mark (Desktop) -->
                <div class="auth-anim-field auth-delay-1 mb-3.5 hidden min-[900px]:flex justify-start">
                    <img src="{{ $logoDataUri }}" alt="Bethel Baptist Hospital Logo" class="h-9 w-auto object-contain">
                </div>

                <!-- Headings -->
                <div class="auth-anim-field auth-delay-2 mb-5">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                        Set New Password
                    </h1>
                    <p class="text-xs text-slate-600 mt-1">
                        Replace your temporary password to continue
                    </p>
                </div>

                <!-- Error Messages Banner -->
                @if ($errors->any())
                    <div class="auth-anim-field auth-delay-2 mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-950 flex items-start gap-2 shadow-2xs" role="alert">
                        <svg class="w-4 h-4 text-brand-red shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-xs">
                            <p class="font-semibold">{{ $errors->first() }}</p>
                        </div>
                    </div>
                @endif

                <!-- Password Form -->
                <form method="POST" action="{{ route('password.change.update') }}" class="space-y-3.5" id="change-password-form">
                    @csrf

                    <!-- New Password Field -->
                    <div class="auth-anim-field auth-delay-3">
                        <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            New Password (at least 8 characters) <span class="text-brand-red" aria-hidden="true">*</span>
                        </label>
                        <div class="relative">
                            <input id="password"
                                   name="password"
                                   type="password"
                                   required
                                   autofocus
                                   minlength="8"
                                   placeholder="Enter new private password"
                                   autocomplete="new-password"
                                   class="w-full min-h-[44px] pl-3.5 pr-11 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                            <button type="button"
                                    onclick="togglePasswordVisibility('password', this)"
                                    aria-label="Toggle password visibility"
                                    title="Show or hide password"
                                    class="absolute right-1 top-1/2 -translate-y-1/2 min-w-[44px] min-h-[44px] flex items-center justify-center rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 cursor-pointer transition-colors">
                                <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <svg class="w-4 h-4 eye-slash hidden" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="auth-anim-field auth-delay-4">
                        <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Confirm New Password <span class="text-brand-red" aria-hidden="true">*</span>
                        </label>
                        <div class="relative">
                            <input id="password_confirmation"
                                   name="password_confirmation"
                                   type="password"
                                   required
                                   minlength="8"
                                   placeholder="Re-enter your new password"
                                   autocomplete="new-password"
                                   class="w-full min-h-[44px] pl-3.5 pr-11 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                            <button type="button"
                                    onclick="togglePasswordVisibility('password_confirmation', this)"
                                    aria-label="Toggle password confirmation visibility"
                                    title="Show or hide password"
                                    class="absolute right-1 top-1/2 -translate-y-1/2 min-w-[44px] min-h-[44px] flex items-center justify-center rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 cursor-pointer transition-colors">
                                <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <svg class="w-4 h-4 eye-slash hidden" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Password Match Helper -->
                    <div id="password-match-indicator" class="hidden text-xs py-1.5 px-2.5 rounded-md"></div>

                    <!-- Full-Width Charcoal Submit Button -->
                    <div class="auth-anim-field auth-delay-5 pt-1">
                        <button type="submit"
                                class="w-full min-h-[46px] flex items-center justify-center gap-2 px-6 py-2.5 rounded-lg bg-primary hover:bg-primary-hover active:translate-y-[1px] text-white font-semibold text-sm shadow-xs transition-colors cursor-pointer">
                            <span>Save Password &amp; Continue</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                            </svg>
                        </button>
                    </div>
                </form>

                <!-- Small "Working offline" green dot note at bottom -->
                <div class="auth-anim-field auth-delay-6 flex items-center justify-center gap-2 mt-5 text-xs text-slate-500 font-medium select-none">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                    <span>Working offline</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(fieldId, btn) {
            const input = document.getElementById(fieldId);
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

        const pwd = document.getElementById('password');
        const pwdConf = document.getElementById('password_confirmation');
        const matchInd = document.getElementById('password-match-indicator');

        function checkPasswordMatch() {
            if (!pwd || !pwdConf || !matchInd) return;
            const v1 = pwd.value;
            const v2 = pwdConf.value;
            if (!v2) {
                matchInd.className = 'hidden text-xs py-1.5 px-2.5 rounded-md';
                matchInd.textContent = '';
                return;
            }
            if (v1 === v2) {
                matchInd.className = 'text-xs py-1.5 px-2.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200 block';
                matchInd.textContent = 'Passwords match';
            } else {
                matchInd.className = 'text-xs py-1.5 px-2.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200 block';
                matchInd.textContent = 'Passwords do not match yet';
            }
        }

        if (pwd && pwdConf) {
            pwd.addEventListener('input', checkPasswordMatch);
            pwdConf.addEventListener('input', checkPasswordMatch);
        }
    </script>
</body>
</html>
