<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In - Bethel Asset Tracker</title>
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
                        <span>Hospital Property Register</span>
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
                            <span>Clinical Equipment Monitor</span>
                            <span class="text-brand-red font-mono text-[9px] tracking-wider">HEARTBEAT OK</span>
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
                        <p class="text-[10px] text-slate-500 font-medium">Hospital Property Register</p>
                    </div>
                </div>

                <!-- Small Logo Mark (Desktop) -->
                <div class="auth-anim-field auth-delay-1 mb-3.5 hidden min-[900px]:flex justify-start">
                    <img src="{{ $logoDataUri }}" alt="Bethel Baptist Hospital Logo" class="h-9 w-auto object-contain">
                </div>

                <!-- Headings -->
                <div class="auth-anim-field auth-delay-2 mb-5">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                        Welcome back
                    </h1>
                    <p class="text-xs text-slate-600 mt-1">
                        Sign in to Bethel Asset Tracker
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

                @if (session('success'))
                    <div class="auth-anim-field auth-delay-2 mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-950 flex items-start gap-2 shadow-2xs" role="alert">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xs font-semibold">{{ session('success') }}</p>
                    </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('login.submit') }}" class="space-y-3.5">
                    @csrf

                    <!-- Email Field -->
                    <div class="auth-anim-field auth-delay-3">
                        <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Email Address <span class="text-brand-red" aria-hidden="true">*</span>
                        </label>
                        <input id="email"
                               name="email"
                               type="email"
                               value="{{ old('email', 'admin@hospital.local') }}"
                               required
                               autofocus
                               placeholder="staff@hospital.local"
                               autocomplete="username"
                               class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                    </div>

                    <!-- Password Field -->
                    <div class="auth-anim-field auth-delay-4">
                        <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Password <span class="text-brand-red" aria-hidden="true">*</span>
                        </label>
                        <div class="relative">
                            <input id="password"
                                   name="password"
                                   type="password"
                                   required
                                   placeholder="Enter your password"
                                   autocomplete="current-password"
                                   class="w-full min-h-[44px] pl-3.5 pr-11 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                            <button type="button"
                                    id="toggle-login-password"
                                    onclick="toggleLoginPassword(this)"
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

                    <!-- Full-Width Charcoal Sign In Button -->
                    <div class="auth-anim-field auth-delay-5 pt-1">
                        <button type="submit"
                                class="w-full min-h-[46px] flex items-center justify-center gap-2 px-6 py-2.5 rounded-lg bg-primary hover:bg-primary-hover active:translate-y-[1px] text-white font-semibold text-sm shadow-xs transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                            </svg>
                            <span>Sign In</span>
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
        function toggleLoginPassword(btn) {
            const input = document.getElementById('password');
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
    </script>
</body>
</html>
