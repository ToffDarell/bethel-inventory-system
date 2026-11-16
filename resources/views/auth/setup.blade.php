<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Administrator Account - Bethel Asset Tracker</title>
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
            <div class="auth-diagonal-outer w-full h-full bg-brand-red relative">
                <div class="auth-diagonal-inner w-full h-full bg-[#F1F5F9] flex flex-col justify-between p-5 xl:p-8 pr-12 xl:pr-16 relative">
                    <div class="flex items-center justify-between text-xs text-slate-600 font-medium select-none pr-8">
                        <span>Hospital Property Register</span>
                        <span class="text-[11px] text-slate-600 font-mono">Initial System Setup</span>
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
                            <span>Clinical Equipment Monitor</span>
                            <span class="text-brand-red font-mono text-[9px] tracking-wider">SYSTEM READY</span>
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

        <!-- RIGHT (about 45%): Setup Form -->
        <div class="flex-1 w-full min-[900px]:w-[45%] h-full bg-white flex flex-col justify-center items-center px-6 sm:px-10 lg:px-12 py-5 overflow-y-auto">
            <div class="w-full max-w-[420px] my-auto py-4">
                <!-- Mobile header -->
                <div class="min-[900px]:hidden flex items-center gap-3 mb-4 pb-3 border-b border-slate-100 justify-center text-center">
                    <img src="{{ $logoDataUri }}" alt="Bethel Baptist Hospital Logo" class="h-8 w-auto object-contain">
                    <div class="text-left">
                        <p class="text-xs font-bold text-slate-900 leading-tight">Bethel Asset Tracker</p>
                        <p class="text-[10px] text-slate-500 font-medium">Initial System Setup</p>
                    </div>
                </div>

                <!-- Logo Mark (Desktop) -->
                <div class="auth-anim-field auth-delay-1 mb-3 hidden min-[900px]:flex justify-start">
                    <img src="{{ $logoDataUri }}" alt="Bethel Baptist Hospital Logo" class="h-9 w-auto object-contain">
                </div>

                <!-- Headings -->
                <div class="auth-anim-field auth-delay-2 mb-4">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                        Create administrator account
                    </h1>
                    <p class="text-xs text-slate-600 mt-1">
                        Welcome to Bethel Asset Tracker. Set up your master administrative credentials to begin.
                    </p>
                </div>

                <!-- Setup Form -->
                <form method="POST" action="{{ route('setup.store') }}" class="space-y-3.5" autocomplete="off" id="setupForm">
                    @csrf

                    <!-- Name Field -->
                    <div class="auth-anim-field auth-delay-3">
                        <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="name"
                               id="name"
                               required
                               autofocus
                               value="{{ old('name') }}"
                               placeholder="e.g. Hospital Administrator"
                               class="w-full px-3.5 py-2 border rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors duration-150 {{ $errors->has('name') ? 'border-red-400 bg-red-50/20' : 'border-slate-300' }}">
                        @error('name')
                            <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Username Field -->
                    <div class="auth-anim-field auth-delay-4">
                        <label for="username" class="block text-xs font-semibold text-slate-700 mb-1">
                            Administrator Username <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="username"
                               id="username"
                               required
                               value="{{ old('username') }}"
                               placeholder="e.g. admin"
                               class="w-full px-3.5 py-2 border rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors duration-150 {{ $errors->has('username') ? 'border-red-400 bg-red-50/20' : 'border-slate-300' }}">
                        @error('username')
                            <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Optional Email -->
                    <div class="auth-anim-field auth-delay-4">
                        <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">
                            Email Address <span class="text-slate-400 font-normal">(optional)</span>
                        </label>
                        <input type="email"
                               name="email"
                               id="email"
                               value="{{ old('email') }}"
                               placeholder="admin@hospital.local"
                               class="w-full px-3.5 py-2 border rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors duration-150 {{ $errors->has('email') ? 'border-red-400 bg-red-50/20' : 'border-slate-300' }}">
                        @error('email')
                            <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password Field with Strength Hint -->
                    <div class="auth-anim-field auth-delay-5">
                        <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">
                            Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password"
                                   name="password"
                                   id="password"
                                   required
                                   minlength="10"
                                   placeholder="Minimum 10 characters"
                                   class="w-full px-3.5 py-2 pr-10 border rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors duration-150 {{ $errors->has('password') ? 'border-red-400 bg-red-50/20' : 'border-slate-300' }}">
                            <button type="button"
                                    id="togglePassword"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
                                    aria-label="Toggle password visibility">
                                <svg id="eyeIcon" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg id="eyeOffIcon" class="h-4 w-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>

                        <!-- Strength meter bars & hint -->
                        <div class="mt-2" id="strengthContainer">
                            <div class="flex items-center gap-1.5 h-1.5 w-full">
                                <div id="strBar1" class="h-full flex-1 rounded-full bg-slate-200 transition-colors duration-200"></div>
                                <div id="strBar2" class="h-full flex-1 rounded-full bg-slate-200 transition-colors duration-200"></div>
                                <div id="strBar3" class="h-full flex-1 rounded-full bg-slate-200 transition-colors duration-200"></div>
                                <div id="strBar4" class="h-full flex-1 rounded-full bg-slate-200 transition-colors duration-200"></div>
                            </div>
                            <div class="flex justify-between items-center text-[11px] mt-1">
                                <span id="strText" class="text-slate-500 font-medium">Minimum 10 characters</span>
                                <span id="strHint" class="text-slate-400">Use letters, numbers, and symbols</span>
                            </div>
                        </div>

                        @error('password')
                            <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div class="auth-anim-field auth-delay-6">
                        <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1">
                            Confirm Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password"
                                   name="password_confirmation"
                                   id="password_confirmation"
                                   required
                                   placeholder="Re-enter password"
                                   class="w-full px-3.5 py-2 pr-10 border rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-blue/30 focus:border-brand-blue transition-colors duration-150 {{ $errors->has('password_confirmation') ? 'border-red-400 bg-red-50/20' : 'border-slate-300' }}">
                            <button type="button"
                                    id="togglePasswordConfirmation"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
                                    aria-label="Toggle password confirmation visibility">
                                <svg id="eyeIconConfirm" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg id="eyeOffIconConfirm" class="h-4 w-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>
                        <p id="matchMsg" class="text-xs mt-1 font-medium hidden"></p>
                    </div>

                    <!-- Submit Button -->
                    <div class="auth-anim-field auth-delay-6 pt-2">
                        <button type="submit"
                                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 transition-colors duration-150 cursor-pointer">
                            <span>Create Account & Continue</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    </div>
                </form>

                <!-- Offline Note -->
                <div class="mt-5 text-center text-xs text-slate-500 flex items-center justify-center gap-1.5 select-none auth-anim-field auth-delay-6">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Standalone Hospital Database</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Toggle Password
            const pwdInput = document.getElementById('password');
            const togglePwd = document.getElementById('togglePassword');
            const eyeIcon = document.getElementById('eyeIcon');
            const eyeOffIcon = document.getElementById('eyeOffIcon');

            if (togglePwd && pwdInput) {
                togglePwd.addEventListener('click', function () {
                    const isPwd = pwdInput.type === 'password';
                    pwdInput.type = isPwd ? 'text' : 'password';
                    eyeIcon.classList.toggle('hidden', isPwd);
                    eyeOffIcon.classList.toggle('hidden', !isPwd);
                });
            }

            // Toggle Password Confirmation
            const pwdConfirmInput = document.getElementById('password_confirmation');
            const togglePwdConfirm = document.getElementById('togglePasswordConfirmation');
            const eyeIconConfirm = document.getElementById('eyeIconConfirm');
            const eyeOffIconConfirm = document.getElementById('eyeOffIconConfirm');

            if (togglePwdConfirm && pwdConfirmInput) {
                togglePwdConfirm.addEventListener('click', function () {
                    const isPwd = pwdConfirmInput.type === 'password';
                    pwdConfirmInput.type = isPwd ? 'text' : 'password';
                    eyeIconConfirm.classList.toggle('hidden', isPwd);
                    eyeOffIconConfirm.classList.toggle('hidden', !isPwd);
                });
            }

            // Strength hint calculation
            const bars = [
                document.getElementById('strBar1'),
                document.getElementById('strBar2'),
                document.getElementById('strBar3'),
                document.getElementById('strBar4')
            ];
            const strText = document.getElementById('strText');
            const strHint = document.getElementById('strHint');
            const matchMsg = document.getElementById('matchMsg');

            function evaluateStrength(val) {
                if (!val || val.length === 0) {
                    return { score: 0, text: 'Minimum 10 characters', color: 'bg-slate-200' };
                }
                let score = 0;
                if (val.length >= 10) score += 1;
                if (val.length >= 14) score += 1;
                if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score += 1;
                if (/[0-9]/.test(val)) score += 1;
                if (/[^A-Za-z0-9]/.test(val)) score += 1;

                if (val.length < 10) {
                    return { score: 1, text: 'Too short (min 10 chars)', color: 'bg-red-500' };
                }
                if (score <= 2) {
                    return { score: 2, text: 'Fair strength', color: 'bg-amber-500' };
                }
                if (score <= 3) {
                    return { score: 3, text: 'Good strength', color: 'bg-blue-500' };
                }
                return { score: 4, text: 'Strong password', color: 'bg-emerald-500' };
            }

            if (pwdInput) {
                pwdInput.addEventListener('input', function () {
                    const res = evaluateStrength(pwdInput.value);
                    bars.forEach((bar, idx) => {
                        bar.className = 'h-full flex-1 rounded-full transition-colors duration-200 ' +
                            (idx < res.score ? res.color : 'bg-slate-200');
                    });
                    strText.textContent = res.text;
                    strText.className = 'font-semibold ' + (
                        res.score === 1 ? 'text-red-600' :
                        res.score === 2 ? 'text-amber-600' :
                        res.score === 3 ? 'text-blue-600' :
                        res.score === 4 ? 'text-emerald-600' : 'text-slate-500'
                    );
                    checkMatch();
                });
            }

            function checkMatch() {
                if (!pwdConfirmInput || !matchMsg) return;
                const v1 = pwdInput.value;
                const v2 = pwdConfirmInput.value;
                if (!v2) {
                    matchMsg.classList.add('hidden');
                    return;
                }
                matchMsg.classList.remove('hidden');
                if (v1 === v2) {
                    matchMsg.textContent = 'Passwords match';
                    matchMsg.className = 'text-xs mt-1 font-medium text-emerald-600';
                } else {
                    matchMsg.textContent = 'Passwords do not match';
                    matchMsg.className = 'text-xs mt-1 font-medium text-red-600';
                }
            }

            if (pwdConfirmInput) {
                pwdConfirmInput.addEventListener('input', checkMatch);
            }
        });
    </script>
</body>
</html>
