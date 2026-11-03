<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Bethel Asset Tracker' }} - {{ \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f8fafc] text-slate-900 font-sans antialiased text-[14.5px] min-h-screen flex selection:bg-sky-100 selection:text-sky-900">
    @php
        $hospitalName = \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.');
        $logoDataUri = app(\App\Services\QrCodeService::class)->hospitalLogoBase64();
    @endphp

    <!-- Left Sidebar (Collapsible 248px <-> 72px, Pure White Clinical Surface) -->
    <aside id="app-sidebar"
           class="no-print w-[248px] bg-white text-slate-800 border-r border-slate-200 flex flex-col justify-between shrink-0 h-screen sticky top-0 select-none transition-all duration-200 z-30"
           data-collapsed="false">
        <div class="flex flex-col">
            <!-- Top Hospital Crimson Accent Line (3px) -->
            <div class="h-[3px] w-full bg-[#b91c1c]"></div>

            <!-- Official Hospital Logo & Branding -->
            <div class="p-3.5 border-b border-slate-200 flex items-center justify-between gap-3">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 min-w-0 group" title="Bethel Asset Tracker">
                    <div class="w-10 h-10 rounded-lg bg-slate-50 border border-slate-100 p-1 flex items-center justify-center shrink-0">
                        <img src="{{ $logoDataUri }}"
                             alt="Bethel Logo"
                             class="h-8 w-auto object-contain">
                    </div>
                    <div class="sidebar-expanded-text min-w-0">
                        <h1 class="text-sm font-bold text-slate-900 leading-tight truncate group-hover:text-[#0f5d8c] transition-colors">
                            Bethel Asset Tracker
                        </h1>
                        <p class="text-[11px] text-slate-500 font-medium truncate mt-0.5">
                            Hospital Inventory
                        </p>
                    </div>
                </a>
            </div>

            <!-- Primary Navigation (Strictly 5 items in order) -->
            <div class="px-2.5 py-4">
                <p class="sidebar-expanded-text px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-2 select-none">
                    Navigation
                </p>
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
                               title="{{ $item['label'] }}"
                               class="group flex items-center gap-3 min-h-[44px] px-3 py-2.5 rounded-lg bg-[#0f5d8c]/10 text-[#0f5d8c] font-semibold border-l-[3px] border-[#0f5d8c] transition-all">
                                <svg class="w-5 h-5 shrink-0 text-[#0f5d8c]"
                                     fill="none"
                                     stroke="currentColor"
                                     stroke-width="1.75"
                                     viewBox="0 0 24 24"
                                     aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                                </svg>
                                <span class="sidebar-expanded-text text-sm">{{ $item['label'] }}</span>
                            </a>
                        @else
                            <a href="{{ route($item['route']) }}"
                               title="{{ $item['label'] }}"
                               class="group flex items-center gap-3 min-h-[44px] px-3 py-2.5 rounded-lg text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium transition-colors">
                                <svg class="w-5 h-5 shrink-0 text-slate-500 group-hover:text-slate-800 transition-colors"
                                     fill="none"
                                     stroke="currentColor"
                                     stroke-width="1.75"
                                     viewBox="0 0 24 24"
                                     aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                                </svg>
                                <span class="sidebar-expanded-text text-sm">{{ $item['label'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </nav>
            </div>
        </div>

        <!-- Sidebar Bottom: Collapse Rail Toggle + User Profile -->
        <div>
            <!-- Sidebar Collapse / Expand Toggle Button -->
            <div class="px-2.5 py-2 border-t border-slate-100">
                <button id="sidebar-toggle-btn"
                        type="button"
                        title="Collapse sidebar"
                        class="w-full min-h-[44px] flex items-center justify-center gap-2 px-2.5 py-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors text-xs font-semibold cursor-pointer">
                    <svg class="w-4 h-4 shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                    </svg>
                    <span class="sidebar-expanded-text">Collapse sidebar</span>
                </button>
            </div>

            <!-- User Profile & Sign Out Footer -->
            @auth
                <div class="p-3 border-t border-slate-200 bg-white">
                    <div class="flex items-center gap-2.5 mb-2.5">
                        <div class="w-8 h-8 rounded-full bg-slate-800 text-white font-semibold text-xs flex items-center justify-center shrink-0"
                             title="{{ auth()->user()->name }} ({{ auth()->user()->email }})">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="sidebar-expanded-text min-w-0 flex-1">
                            <p class="text-xs font-semibold text-slate-900 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-[11px] text-slate-500 truncate">{{ ucfirst(auth()->user()->role) }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                title="Sign out"
                                class="w-full min-h-[44px] flex items-center justify-center gap-2 px-2 py-2 rounded-lg border border-slate-200 bg-white text-slate-700 font-semibold text-xs hover:bg-slate-50 hover:text-slate-900 hover:border-slate-300 transition-colors cursor-pointer">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span class="sidebar-expanded-text">Sign out</span>
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </aside>

    <!-- Main Workspace -->
    <div class="flex-1 flex flex-col min-w-0 bg-[#f8fafc]">
        <!-- Top Context Bar (Global Search, Offline Status, Date, User) -->
        <header class="no-print h-14 px-6 bg-white border-b border-slate-200 flex items-center justify-between gap-4 sticky top-0 z-20">
            <!-- Global Asset Search (Ctrl+K or /) -->
            <div class="relative max-w-md w-full">
                <div class="relative flex items-center">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input id="global-search-input"
                           type="search"
                           autocomplete="off"
                           placeholder="Search assets... (Press / or Ctrl+K)"
                           aria-label="Global Asset Search"
                           class="w-full h-9 pl-9 pr-14 text-xs bg-slate-50 hover:bg-white focus:bg-white border border-slate-200 rounded-lg placeholder:text-slate-400 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0f5d8c] focus:border-[#0f5d8c] transition-all">
                    <kbd class="hidden sm:inline-flex items-center absolute right-2.5 px-1.5 py-0.5 text-[10px] font-medium text-slate-400 bg-white border border-slate-200 rounded select-none pointer-events-none">
                        Ctrl K
                    </kbd>
                </div>

                <!-- Instant Live Search Dropdown -->
                <div id="global-search-dropdown"
                     class="hidden absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-lg z-50 overflow-hidden divide-y divide-slate-100 max-h-96 overflow-y-auto">
                    <div id="global-search-loading" class="hidden px-4 py-3 text-xs text-slate-500 flex items-center gap-2">
                        <svg class="animate-spin h-3.5 w-3.5 text-[#0f5d8c]" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Searching hospital inventory...</span>
                    </div>

                    <div id="global-search-results" class="divide-y divide-slate-100">
                        <!-- Populated by JS -->
                    </div>

                    <div id="global-search-empty" class="hidden px-4 py-4 text-center text-xs text-slate-500">
                        No assets found matching your query.
                    </div>
                </div>
            </div>

            <!-- Top Right: Working Offline Status, Date, Hospital Motto -->
            <div class="flex items-center gap-4 text-xs">
                <!-- Working Offline Badge (Calm Green Dot, Replaces Developer "Offline SQLite") -->
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold select-none"
                     title="Local SQLite storage active. System works fully offline inside hospital network.">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Working offline</span>
                </div>

                <!-- Hospital Motto -->
                <span class="text-slate-500 hidden xl:inline select-none">Preach the Word, Heal the Sick</span>

                <!-- Date in Normal Font (No monospace!) -->
                <span class="text-slate-700 font-medium select-none hidden md:inline">
                    {{ now()->format('l, F j, Y') }}
                </span>
            </div>
        </header>

        <!-- Flash Message Alerts / Toasts Container -->
        @if (session('success') || session('error') || session('warning') || session('info'))
            <div class="no-print px-6 pt-4 max-w-7xl mx-auto w-full">
                @if (session('success'))
                    <x-toast type="success" :message="session('success')" />
                @endif
                @if (session('error'))
                    <x-toast type="error" :message="session('error')" />
                @endif
                @if (session('warning'))
                    <x-toast type="warning" :message="session('warning')" />
                @endif
                @if (session('info'))
                    <x-toast type="info" :message="session('info')" />
                @endif
            </div>
        @endif

        <!-- Main Workspace Content -->
        <main class="flex-1 p-6 lg:p-8 overflow-y-auto">
            <div class="max-w-7xl mx-auto space-y-6">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
