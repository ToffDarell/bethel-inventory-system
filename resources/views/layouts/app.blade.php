<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Bethel Asset Tracker' }} - {{ \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-neutral-950 font-sans antialiased text-base min-h-screen flex selection:bg-red-100 selection:text-red-950">
    @php
        $hospitalName = \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.');
        $hospitalAddress = \App\Models\Setting::get('hospital_address', 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700');
        $logoDataUri = app(\App\Services\QrCodeService::class)->hospitalLogoBase64();
    @endphp

    <!-- Left Sidebar (Black & Crimson Red Palette) -->
    <aside class="no-print w-72 bg-neutral-950 text-white border-r border-neutral-900 flex flex-col justify-between shrink-0 min-h-screen select-none">
        <div>
            <!-- Top Red Brand Stripe -->
            <div class="h-1.5 w-full bg-red-600"></div>

            <!-- Official Hospital Logo & Title Header -->
            <div class="p-5 bg-neutral-950 border-b border-neutral-800">
                <div class="bg-white rounded-xl p-3 mb-3.5 flex items-center justify-center shadow-sm border border-neutral-200">
                    <img src="{{ $logoDataUri }}"
                         alt="{{ $hospitalName }} Logo"
                         class="h-16 w-auto object-contain">
                </div>

                <div class="text-center">
                    <h1 class="text-base font-extrabold tracking-tight text-white leading-snug">Bethel Asset Tracker</h1>
                    <p class="text-xs text-neutral-400 font-medium truncate mt-0.5" title="{{ $hospitalName }}">{{ $hospitalName }}</p>
                </div>

                <div class="mt-3.5 pt-3 border-t border-neutral-800 flex items-center justify-between text-xs text-neutral-400">
                    <span class="inline-flex items-center gap-1.5 font-semibold text-neutral-300">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        Offline SQLite
                    </span>
                    <span class="font-mono text-[11px] bg-neutral-900 text-neutral-300 border border-neutral-800 px-2 py-0.5 rounded">
                        v{{ config('nativephp.version', '1.0.0') }}
                    </span>
                </div>
            </div>

            <!-- Primary Navigation (Strictly 5 items per specification) -->
            <div class="px-4 pt-5 pb-2">
                <p class="px-3 text-[11px] font-extrabold uppercase tracking-widest text-neutral-500 mb-2.5">Navigation</p>
                <nav class="space-y-1.5" aria-label="Main Navigation">
                    @php
                        $navItems = [
                            [
                                'label' => 'Dashboard',
                                'route' => 'dashboard',
                                'active' => request()->routeIs('dashboard'),
                                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                            ],
                            [
                                'label' => 'Assets',
                                'route' => 'assets.index',
                                'active' => request()->routeIs('assets.*'),
                                'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                            ],
                            [
                                'label' => 'Scan',
                                'route' => 'scan.index',
                                'active' => request()->routeIs('scan.*'),
                                'icon' => 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z',
                            ],
                            [
                                'label' => 'Reports',
                                'route' => 'reports.index',
                                'active' => request()->routeIs('reports.*'),
                                'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                            ],
                            [
                                'label' => 'Settings',
                                'route' => 'settings.index',
                                'active' => request()->routeIs('settings.*'),
                                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                            ],
                        ];
                    @endphp

                    @foreach ($navItems as $item)
                        <a href="{{ route($item['route']) }}"
                           class="group flex items-center gap-3.5 px-4 py-3.5 rounded-xl text-base font-bold transition duration-150 {{ $item['active'] ? 'bg-red-600 text-white shadow-sm' : 'text-neutral-300 hover:bg-neutral-900 hover:text-white' }}">
                            <svg class="w-6 h-6 shrink-0 {{ $item['active'] ? 'text-white' : 'text-neutral-400 group-hover:text-red-500' }}"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="2"
                                 viewBox="0 0 24 24"
                                 aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                            </svg>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>
        </div>

        <!-- User Account Footer -->
        @auth
            <div class="p-4 border-t border-neutral-800 bg-neutral-900/70">
                <div class="flex items-center gap-3 mb-3.5">
                    <div class="w-10 h-10 rounded-full bg-red-600 text-white font-extrabold text-sm flex items-center justify-center shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-bold text-white truncate">{{ auth()->user()->name }}</p>
                            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase tracking-wider bg-red-600/20 text-red-400 border border-red-500/30">
                                {{ ucfirst(auth()->user()->role) }}
                            </span>
                        </div>
                        <p class="text-xs text-neutral-400 truncate">{{ auth()->user()->email }} ({{ ucfirst(auth()->user()->role) }})</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-neutral-700 bg-neutral-950 text-neutral-200 font-bold text-sm hover:bg-neutral-800 hover:text-white active:translate-y-[1px] transition cursor-pointer">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Sign Out</span>
                    </button>
                </form>
            </div>
        @endauth
    </aside>

    <!-- Main Workspace -->
    <div class="flex-1 flex flex-col min-w-0 bg-white">
        <!-- Top Hospital Context Bar -->
        <header class="no-print px-8 py-3.5 bg-neutral-50 border-b border-neutral-200 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2.5 text-sm text-neutral-700 truncate">
                <span class="w-2.5 h-2.5 rounded-full bg-red-600 shrink-0"></span>
                <span class="font-extrabold text-neutral-950">{{ $hospitalName }}</span>
                <span class="text-neutral-300">&bull;</span>
                <span class="truncate text-neutral-600">{{ $hospitalAddress }}</span>
            </div>

            <div class="flex items-center gap-3 text-xs font-bold text-neutral-700">
                <span class="italic text-neutral-500 hidden sm:inline">&ldquo;Preach the Word, Heal the Sick&rdquo;</span>
                <span class="px-2.5 py-1 rounded-md bg-white border border-neutral-300 text-neutral-900">
                    {{ now()->format('l, M d, Y') }}
                </span>
            </div>
        </header>

        <!-- Main Page Content -->
        <main class="flex-1 p-8 overflow-y-auto bg-white">
            <div class="max-w-6xl mx-auto animate-fade-up">
                @if (session('success'))
                    <div class="mb-6 p-4 rounded-xl bg-neutral-950 border-l-4 border-red-600 text-white flex items-center justify-between gap-3 shadow-sm" role="alert">
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6 text-red-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="font-bold text-base">{{ session('success') }}</span>
                        </div>
                        <button type="button"
                                onclick="this.closest('[role=alert]').remove()"
                                aria-label="Dismiss notification"
                                class="p-1.5 rounded-lg text-neutral-400 hover:text-white hover:bg-neutral-800 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 p-4 rounded-xl bg-red-50 border-2 border-red-600 text-red-950 flex items-center justify-between gap-3 shadow-xs" role="alert">
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6 text-red-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="font-bold text-base">{{ session('error') }}</span>
                        </div>
                        <button type="button"
                                onclick="this.closest('[role=alert]').remove()"
                                aria-label="Dismiss error"
                                class="p-1.5 rounded-lg text-red-800 hover:bg-red-100 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
