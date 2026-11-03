<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Set New Password - Bethel Asset Tracker</title>
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
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 text-amber-800 text-xs font-semibold select-none border border-amber-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    <span>Account Security Setup</span>
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
                    Secure Hospital Account
                </h1>
                <p class="text-sm font-semibold text-slate-700 mt-1.5">
                    {{ $hospitalName }}
                </p>
                <p class="text-xs text-slate-500 mt-0.5 max-w-sm">
                    First-Time Sign In Security Verification
                </p>

                <!-- ECG Pulse Animation (Crimson line on light clinical panel) -->
                <div class="mt-8 pt-6 border-t border-slate-100 max-w-md">
                    <div class="flex items-center justify-between text-[11px] font-semibold text-slate-500 mb-2">
                        <span>Clinical Security Protocol</span>
                        <span class="text-brand-red font-mono text-[10px] tracking-wider">PROTECTED</span>
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

                <!-- Password Policy Information -->
                <div class="mt-8 p-4 rounded-xl bg-slate-50 border border-slate-200/80 text-xs text-slate-600 max-w-md space-y-2">
                    <p class="font-semibold text-slate-800">Password Requirements</p>
                    <ul class="space-y-1 text-[11px] text-slate-500 list-disc list-inside">
                        <li>Minimum 8 characters</li>
                        <li>Keep your password private to protect patient equipment records</li>
                        <li>You will use this password for all future sessions</li>
                    </ul>
                </div>
            </div>

            <!-- Footer Motto -->
            <div class="pt-6 border-t border-slate-100 text-xs text-slate-500 flex items-center justify-between">
                <span>Bethel Baptist Hospital, Inc.</span>
                <span class="italic text-slate-600">Preach the Word, Heal the Sick</span>
            </div>
        </div>

        <!-- Right Form Panel -->
        <div class="flex-1 flex flex-col justify-center items-center p-6 lg:p-12 bg-canvas">
            <div class="w-full max-w-md">
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
                    <div class="mb-6">
                        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Set Your Password</h2>
                        <p class="text-xs text-slate-600 mt-1">Replace your temporary password with a secure private password</p>
                    </div>

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
                            <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                New Password (at least 8 characters) <span class="text-brand-red">*</span>
                            </label>
                            <div class="relative">
                                <input id="password"
                                       name="password"
                                       type="password"
                                       required
                                       autofocus
                                       minlength="8"
                                       placeholder="Enter new private password"
                                       class="w-full min-h-[48px] pl-3.5 pr-12 py-2.5 text-base bg-white border border-slate-300 rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
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
                            <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Confirm New Password <span class="text-brand-red">*</span>
                            </label>
                            <div class="relative">
                                <input id="password_confirmation"
                                       name="password_confirmation"
                                       type="password"
                                       required
                                       placeholder="Re-type new password"
                                       class="w-full min-h-[48px] pl-3.5 pr-12 py-2.5 text-base bg-white border border-slate-300 rounded-lg shadow-2xs placeholder:text-slate-400 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors">
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
                                    class="w-full min-h-[48px] flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-primary hover:bg-primary-hover active:translate-y-[1px] text-white font-semibold text-base shadow-xs transition-colors cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Save New Password &amp; Continue</span>
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

        document.addEventListener('DOMContentLoaded', () => {
            const pwd = document.getElementById('password');
            const confirm = document.getElementById('password_confirmation');
            const container = document.getElementById('password-match-container');
            const success = document.getElementById('match-success');
            const error = document.getElementById('match-error');
            const length = document.getElementById('match-length');
            const submitBtn = document.getElementById('submit-password-btn');

            function checkMatch() {
                const pVal = pwd.value;
                const cVal = confirm.value;

                if (!pVal && !cVal) {
                    container.classList.add('hidden');
                    return;
                }

                container.classList.remove('hidden');

                if (pVal.length < 8) {
                    length.classList.remove('hidden');
                    success.classList.add('hidden');
                    error.classList.add('hidden');
                    return;
                } else {
                    length.classList.add('hidden');
                }

                if (cVal.length > 0) {
                    if (pVal === cVal) {
                        success.classList.remove('hidden');
                        error.classList.add('hidden');
                    } else {
                        success.classList.add('hidden');
                        error.classList.remove('hidden');
                    }
                } else {
                    success.classList.add('hidden');
                    error.classList.add('hidden');
                }
            }

            if (pwd && confirm) {
                pwd.addEventListener('input', checkMatch);
                confirm.addEventListener('input', checkMatch);
            }
        });
    </script>
</body>
</html>
