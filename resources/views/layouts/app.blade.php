<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Bethel Asset Tracker' }} - {{ \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased text-base min-h-screen flex selection:bg-sky-100 selection:text-[#0f5d8c]">
    @php
        $hospitalName = \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.');
        $hospitalAddress = \App\Models\Setting::get('hospital_address', 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700');
        $logoDataUri = app(\App\Services\QrCodeService::class)->hospitalLogoBase64();
    @endphp

    <!-- Left Sidebar (Clean Clinical White Palette) -->
    <aside class="no-print w-72 bg-white text-slate-800 border-r border-slate-200 flex flex-col justify-between shrink-0 min-h-screen select-none">
        <div>
            <!-- Top Hospital Red Accent Line (3px) -->
            <div class="h-[3px] w-full bg-[#dc2626]"></div>

            <!-- Official Hospital Logo & Branding Area -->
            <div class="p-5 border-b border-slate-200">
                <div class="flex items-center justify-center p-2 mb-3 bg-white">
                    <img src="{{ $logoDataUri }}"
                         alt="{{ $hospitalName }} Logo"
                         class="h-16 w-auto object-contain">
                </div>

                <div class="text-center">
                    <h1 class="text-base font-bold text-slate-900 tracking-tight leading-snug">Bethel Asset Tracker</h1>
                    <p class="text-xs text-slate-500 font-medium truncate mt-0.5" title="{{ $hospitalName }}">{{ $hospitalName }}</p>
                </div>

                <div class="mt-3.5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="inline-flex items-center gap-1.5 font-medium text-slate-600">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Offline SQLite
                    </span>
                    <span class="font-mono text-[11px] bg-slate-100 text-slate-600 border border-slate-200 px-2 py-0.5 rounded font-semibold">
                        v{{ config('nativephp.version', '1.0.0') }}
                    </span>
                </div>
            </div>

            <!-- Primary Navigation (Strictly 5 items per specification) -->
            <div class="px-3 pt-5 pb-2">
                <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Navigation</p>
                <nav class="space-y-1" aria-label="Main Navigation">
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
                        @if ($item['active'])
                            <a href="{{ route($item['route']) }}"
                               class="flex items-center gap-3 px-3.5 py-3 rounded-r-lg bg-sky-50 text-[#0f5d8c] font-bold border-l-4 border-[#0f5d8c] shadow-2xs transition-colors">
                                <svg class="w-5 h-5 shrink-0 text-[#0f5d8c]"
                                     fill="none"
                                     stroke="currentColor"
                                     stroke-width="2"
                                     viewBox="0 0 24 24"
                                     aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                                </svg>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @else
                            <a href="{{ route($item['route']) }}"
                               class="group flex items-center gap-3 px-3.5 py-3 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900 font-medium transition-colors">
                                <svg class="w-5 h-5 shrink-0 text-slate-400 group-hover:text-slate-600 transition-colors"
                                     fill="none"
                                     stroke="currentColor"
                                     stroke-width="1.75"
                                     viewBox="0 0 24 24"
                                     aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                                </svg>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </nav>
            </div>
        </div>

        <!-- User Account Footer -->
        @auth
            <div class="p-4 border-t border-slate-200 bg-slate-50/70">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-full bg-[#0f5d8c] text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <p class="text-sm font-semibold text-slate-900 truncate">{{ auth()->user()->name }}</p>
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-200 text-slate-700">
                                {{ ucfirst(auth()->user()->role) }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full min-h-[44px] flex items-center justify-center gap-2 px-3 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 font-semibold text-xs hover:bg-slate-50 hover:text-slate-900 shadow-2xs transition cursor-pointer">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Sign Out</span>
                    </button>
                </form>
            </div>
        @endauth
    </aside>

    <!-- Main Workspace -->
    <div class="flex-1 flex flex-col min-w-0 bg-slate-50">
        <!-- Top Hospital Context Bar -->
        <header class="no-print px-8 py-3.5 bg-white border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2.5 text-sm text-slate-700 truncate">
                <span class="w-2.5 h-2.5 rounded-full bg-[#0f5d8c] shrink-0"></span>
                <span class="font-bold text-slate-900">{{ $hospitalName }}</span>
                <span class="text-slate-300">&bull;</span>
                <span class="truncate text-slate-500">{{ $hospitalAddress }}</span>
            </div>

            <div class="flex items-center gap-3 text-xs">
                <span class="italic text-slate-500 hidden sm:inline">&ldquo;Preach the Word, Heal the Sick&rdquo;</span>
                <span class="px-2.5 py-1 rounded-md bg-slate-100 border border-slate-200 text-slate-700 font-medium">
                    {{ now()->format('l, M d, Y') }}
                </span>
            </div>
        </header>

        <!-- Main Page Content -->
        <main class="flex-1 p-8 overflow-y-auto">
            <div class="max-w-6xl mx-auto space-y-6">
                @if (session('success'))
                    <x-alert type="success" :dismissible="true">
                        {{ session('success') }}
                    </x-alert>
                @endif

                @if (session('error'))
                    <x-alert type="error" :dismissible="true">
                        {{ session('error') }}
                    </x-alert>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
