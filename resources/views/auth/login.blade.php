<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In - Bethel Asset Tracker</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-canvas text-slate-900 font-sans antialiased min-h-screen flex selection:bg-slate-200 selection:text-slate-900">
    @php
        $logoDataUri = app(\App\Services\TagAssetService::class)->hospitalLogoBase64();
        $hospitalName = \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.');
        $hospitalAddress = \App\Models\Setting::get('hospital_address', 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700');
    @endphp

    <div class="min-h-screen w-full flex flex-col lg:flex-row">
        <!-- Left Brand & Clinical Identity Panel (White/Light Slate, Hospital Logo, Motto, Animated Crimson ECG Line) -->
        <div class="lg:w-1/2 xl:w-5/12 bg-white border-b lg:border-b-0 lg:border-r border-slate-200 p-8 lg:p-12 flex flex-col justify-between relative overflow-hidden">
            <!-- Top Status Pill -->
            <div class="flex items-center justify-between gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold select-none border border-slate-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>Offline System</span>
                </span>
                <span class="text-xs text-slate-500 hidden sm:inline select-none">Malaybalay City, Bukidnon</span>
            </div>

            <!-- Central Hero Branding + Animated ECG -->
            <div class="my-auto py-8">
                <!-- Floating Logo -->
                <div class="inline-flex items-center justify-center p-3 rounded-2xl bg-white border border-slate-200 shadow-2xs mb-6">
                    <img src="{{ $logoDataUri }}"
                         alt="Bethel Baptist Hospital, Inc. Logo"
                         class="h-20 lg:h-24 w-auto object-contain">
                </div>

                <h1 class="text-2xl lg:text-3xl font-bold tracking-tight text-slate-900 leading-tight">
                    Bethel Asset Tracker
                </h1>
                <p class="text-sm font-semibold text-slate-700 mt-1.5">
                    {{ $hospitalName }}
                </p>
                <p class="text-xs text-slate-500 mt-0.5 max-w-sm">
                    {{ $hospitalAddress }}
                </p>

                <!-- ECG Pulse Animation (Crimson line on light clinical panel) -->
                <div class="mt-8 pt-6 border-t border-slate-100 max-w-md">
                    <div class="flex items-center justify-between text-[11px] font-semibold text-slate-500 mb-2">
                        <span>Clinical Equipment Monitor</span>
                        <span class="text-brand-red font-mono text-[10px] tracking-wider">HEARTBEAT OK</span>
                    </div>
                    <div class="relative w-full h-14 bg-slate-50 rounded-xl border border-slate-200/80 p-2 overflow-hidden flex items-center justify-center">
                        <svg viewBox="0 0 500 80" class="w-full h-full text-brand-red" fill="none" preserveAspectRatio="none">
                            <path d="M0,40 L100,40 L115,35 L125,45 L135,40 L160,40 L170,12 L180,68 L190,5 L200,52 L208,40 L240,40 L250,33 L260,40 L340,40 L350,12 L360,68 L370,5 L380,52 L388,40 L500,40"
                                  stroke="currentColor"
                                  stroke-width="2.25"
                                  stroke-linecap="round"
                                  stroke-linejoin="round"
                                  class="ecg-pulse-line" />
                        </svg>
                    </div>
                </div>

                <!-- Feature Highlights -->
                <div class="mt-8 grid grid-cols-2 gap-3 text-xs text-slate-600 max-w-md">
                    <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                        <p class="font-semibold text-slate-800">Hospital Equipment</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">Physical property registration &amp; tracking</p>
                    </div>
                    <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                        <p class="font-semibold text-slate-800">Barcode Tags</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">A4 2&times;5 property tags (10cm &times; 6cm)</p>
                    </div>
                </div>
            </div>

            <!-- Footer Motto -->
            <div class="pt-6 border-t border-slate-100 text-xs text-slate-500 flex items-center justify-between">
                <span>Bethel Baptist Hospital, Inc.</span>
                <span class="italic text-slate-600">Preach the Word, Heal the Sick</span>
            </div>
        </div>

        <!-- Right Authentication Form Panel -->
        <div class="flex-1 flex flex-col justify-center items-center p-6 lg:p-12 bg-canvas">
            <div class="w-full max-w-md">
                <!-- Form Card -->
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
                    <div class="mb-6">
                        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Staff Sign In</h2>
                        <p class="text-xs text-slate-600 mt-1">Enter your hospital credentials to access the register</p>
                    </div>

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
                                        class="min-h-[36px] px-3 py-1.5 rounded-lg bg-primary hover:bg-primary-hover text-white font-semibold text-xs shrink-0 cursor-pointer transition-colors shadow-2xs">
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
                            <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Email Address <span class="text-brand-red">*</span>
                            </label>
                            <input id="email"
                                   name="email"
                                   type="email"
                                   value="{{ old('email', 'admin@hospital.local') }}"
                                   required
                                   autofocus
                                   placeholder="staff@hospital.local"
                                   class="w-full min-h-[48px] px-3.5 py-2.5 text-base bg-white border border-slate-300 rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="password" class="block text-xs font-semibold text-slate-700">
                                    Password <span class="text-brand-red">*</span>
                                </label>
                            </div>
                            <div class="relative">
                                <input id="password"
                                       name="password"
                                       type="password"
                                       required
                                       placeholder="Enter your password"
                                       class="w-full min-h-[48px] pl-3.5 pr-12 py-2.5 text-base bg-white border border-slate-300 rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
                                <button type="button"
                                        id="toggle-login-password"
                                        onclick="toggleLoginPassword(this)"
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

                        <div class="pt-2">
                            <button type="submit"
                                    class="w-full min-h-[48px] flex items-center justify-center gap-2.5 px-6 py-3 rounded-lg bg-primary hover:bg-primary-hover active:translate-y-[1px] text-white font-semibold text-base shadow-xs transition-colors cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                                </svg>
                                <span>Sign In</span>
                            </button>
                        </div>
                    </form>
                </div>

                <p class="mt-6 text-center text-xs text-slate-500 font-medium">
                    Bethel Baptist Hospital, Inc. &bull; Offline Desktop Property Register
                </p>
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
