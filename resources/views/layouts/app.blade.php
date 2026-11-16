@php
    $hospitalName = \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.');
    $hospitalShortName = \App\Models\Setting::get('hospital_short_name', 'BBH');
    $logoDataUri = app(\App\Services\TagAssetService::class)->hospitalLogoBase64();
    $userTextSize = auth()->check() ? auth()->user()->effectiveTextSize() : 'default';
    $userDensity = auth()->check() ? auth()->user()->effectiveDensity() : 'comfortable';
    $userTheme = auth()->check() ? auth()->user()->effectiveTheme() : 'light';
@endphp
<!DOCTYPE html>
<html lang="en"
      class="h-full text-size-{{ $userTextSize }} density-{{ $userDensity }} theme-{{ $userTheme }}"
      data-text-size="{{ $userTextSize }}"
      data-density="{{ $userDensity }}"
      data-theme="{{ $userTheme }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Bethel Asset Tracker' }} - {{ $hospitalName }}</title>
    <link rel="icon" type="image/png" href="{{ asset('icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-canvas text-slate-900 font-sans antialiased text-[14.5px] min-h-screen flex flex-col selection:bg-sky-100 selection:text-sky-900 text-size-{{ $userTextSize }} density-{{ $userDensity }} theme-{{ $userTheme }}"
      data-text-size="{{ $userTextSize }}"
      data-density="{{ $userDensity }}"
      data-theme="{{ $userTheme }}">
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
                'active' => request()->routeIs('assets.*') && ! request()->routeIs('assets.find*') && ! request()->routeIs('assets.create'),
                'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
            ],
            [
                'label' => 'Find Asset',
                'route' => 'assets.find',
                'active' => request()->routeIs('assets.find*') || request()->routeIs('scan.*'),
                'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
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

    <!-- Institutional Top Navigation Bar (56-64px high, Fits 1093x614 Without Scrolling) -->
    <header class="no-print sticky top-0 z-30 h-16 bg-white border-b border-slate-200 px-3 sm:px-4 flex items-center justify-between gap-2 shadow-2xs select-none max-w-full overflow-x-clip">
        <!-- Left: Logo & Hospital Branding -->
        <div class="flex items-center gap-2 shrink-0 min-w-0">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 group" title="{{ $hospitalName }}">
                <div class="w-9 h-9 rounded-lg bg-slate-50 border border-slate-200/90 p-1 flex items-center justify-center shrink-0">
                    <img src="{{ $logoDataUri }}"
                         alt="Bethel Logo"
                         class="h-7 w-auto object-contain">
                </div>
                <div class="min-w-0 hidden xl:block">
                    <h1 class="text-sm font-bold text-slate-900 leading-tight tracking-tight whitespace-nowrap group-hover:text-primary transition-colors">
                        Bethel Asset Tracker
                    </h1>
                    <p class="text-[10.5px] text-slate-500 font-medium leading-none mt-0.5 whitespace-nowrap">
                        {{ $hospitalShortName }}
                    </p>
                </div>
            </a>
        </div>

        <!-- Center: Primary Navigation Buttons (5 items in order) -->
        <nav class="flex items-center gap-1 shrink-0" aria-label="Main Navigation">
            @foreach ($navItems as $item)
                @if ($item['active'])
                    <a href="{{ route($item['route']) }}"
                       data-nav-label="{{ $item['label'] }}"
                       class="min-h-[38px] inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-slate-900 text-white font-semibold text-xs tracking-tight shadow-2xs transition-colors cursor-pointer whitespace-nowrap">
                        <svg class="w-4 h-4 text-white shrink-0"
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
                       data-nav-label="{{ $item['label'] }}"
                       class="min-h-[38px] inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-slate-700 hover:text-slate-900 hover:bg-slate-100 font-medium text-xs tracking-tight transition-colors cursor-pointer whitespace-nowrap">
                        <svg class="w-4 h-4 text-slate-500 group-hover:text-slate-800 shrink-0 transition-colors"
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

        <!-- Right: Prominent Add Asset Button + Global Search + User Profile & Sign Out -->
        <div class="flex items-center gap-1.5 sm:gap-2 shrink-0 min-w-0">
            <!-- Prominent Add Asset Button -->
            <a href="{{ route('assets.create') }}"
               class="min-h-[38px] inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg {{ request()->routeIs('assets.create') ? 'bg-slate-900 text-white' : 'bg-primary hover:bg-primary-hover text-white' }} font-bold text-xs shadow-2xs active:translate-y-[1px] transition-colors shrink-0 whitespace-nowrap">
                <svg class="w-4 h-4 text-white shrink-0" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Add Asset</span>
            </a>

            <!-- Compact Search (Ctrl+K or /) -->
            <div class="relative w-32 xl:w-40 hidden lg:block">
                <div class="relative flex items-center">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input id="global-search-input"
                           type="search"
                           autocomplete="off"
                           placeholder="Search... (Ctrl+K)"
                           aria-label="Global Asset Search"
                           class="w-full h-8 pl-8 pr-10 text-xs bg-slate-50 hover:bg-white focus:bg-white border border-slate-200 rounded-lg placeholder:text-slate-400 text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors">
                    <kbd class="hidden xl:inline-flex items-center absolute right-2 px-1 py-0.5 text-[9px] font-medium text-slate-400 bg-white border border-slate-200 rounded select-none pointer-events-none">
                        Ctrl K
                    </kbd>
                </div>

                <!-- Instant Live Search Dropdown -->
                <div id="global-search-dropdown"
                     class="hidden absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-lg z-50 overflow-hidden divide-y divide-slate-100 max-h-96 overflow-y-auto">
                    <div id="global-search-loading" class="hidden px-4 py-3 text-xs text-slate-500 flex items-center gap-2">
                        <svg class="animate-spin h-3.5 w-3.5 text-primary" fill="none" viewBox="0 0 24 24">
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

            <!-- User Avatar & Sign Out -->
            @auth
                <div class="flex items-center gap-2 pl-2 border-l border-slate-200 shrink-0">
                    <div class="w-8 h-8 rounded-full bg-slate-800 text-white font-bold text-xs flex items-center justify-center shrink-0"
                         title="{{ auth()->user()->name }} ({{ auth()->user()->username }})">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="hidden xl:block text-left">
                        <p class="text-xs font-semibold text-slate-900 leading-tight truncate max-w-[100px]">{{ auth()->user()->username }}</p>
                        <p class="text-[10px] text-slate-500 capitalize leading-tight">{{ auth()->user()->role }}</p>
                    </div>

                    <button type="button"
                            id="signout-trigger-btn"
                            onclick="openSignOutModal()"
                            title="Sign out of Bethel Asset Tracker"
                            aria-haspopup="dialog"
                            class="min-h-[38px] inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 font-semibold text-xs hover:bg-slate-50 hover:text-slate-900 hover:border-slate-300 transition-colors cursor-pointer whitespace-nowrap">
                        <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span class="hidden sm:inline">Sign out</span>
                    </button>
                </div>
            @endauth
        </div>
    </header>

    <!-- Flash Message Alerts / Toasts Container -->
    @if (session('success') || session('error') || session('warning') || session('info'))
        <div class="no-print px-6 pt-4 max-w-7xl mx-auto w-full shrink-0">
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
    <main class="flex-1 p-6 lg:p-8 min-w-0 max-w-full">
        <div class="max-w-7xl mx-auto space-y-6 min-w-0 w-full">
            @yield('content')
        </div>
    </main>

    @auth
        <!-- Accessible Clinical Sign Out Modal -->
        <div id="signout-modal"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden transition-opacity"
             role="dialog"
             aria-modal="true"
             aria-labelledby="signout-modal-title"
             aria-describedby="signout-modal-desc"
             onclick="closeSignOutModal()">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150"
                 onclick="event.stopPropagation()">
                <div class="p-6">
                    <!-- Modal Header with Icon and Title -->
                    <div class="flex items-center gap-3 mb-2.5">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-slate-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </div>
                        <div>
                            <h3 id="signout-modal-title" class="text-base font-bold text-slate-900 leading-tight">
                                Confirm Sign Out
                            </h3>
                            <p class="text-[11px] text-slate-500 mt-0.5">End your current session</p>
                        </div>
                    </div>

                    <!-- Description -->
                    <p id="signout-modal-desc" class="text-xs text-slate-600 mt-2.5 leading-relaxed">
                        Are you sure you want to end your current session? Any unsaved edits will be lost.
                    </p>

                    <!-- User Account Card (Full Width & Aligned) -->
                    <div class="mt-3.5 p-3 rounded-xl bg-slate-50 border border-slate-200/90 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-800 text-white font-bold text-xs flex items-center justify-center shrink-0"
                             title="{{ auth()->user()->name }}">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1 text-xs">
                            <p class="font-semibold text-slate-900 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-slate-500 text-[11px] truncate mt-0.5">{{ '@' . auth()->user()->username }} &bull; {{ ucfirst(auth()->user()->role) }}</p>
                        </div>
                    </div>

                    <!-- Modal Actions Footer -->
                    <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button"
                                id="signout-cancel-btn"
                                onclick="closeSignOutModal()"
                                class="min-h-[38px] px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg shadow-2xs hover:bg-slate-50 hover:border-slate-400 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-1 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit"
                                    id="signout-confirm-btn"
                                    class="min-h-[38px] inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-lg shadow-2xs focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 focus-visible:ring-offset-1 transition-colors cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                <span>Sign Out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Personal Display Settings Modal -->
        <div id="display-settings-modal"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden"
             role="dialog"
             aria-modal="true"
             aria-labelledby="display-settings-modal-title"
             aria-describedby="display-settings-modal-desc"
             onclick="closeDisplaySettingsModal(true)">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-slate-200 overflow-hidden max-h-[92vh] overflow-y-auto"
                 onclick="event.stopPropagation()">
                <form method="POST" action="{{ route('preferences.display.update') }}" id="display-settings-form" class="p-5 sm:p-6 space-y-4">
                    @csrf
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/>
                                </svg>
                            </div>
                            <div>
                                <h3 id="display-settings-modal-title" class="text-base font-bold text-slate-900 leading-tight">
                                    Display settings
                                </h3>
                                <p id="display-settings-modal-desc" class="text-xs text-slate-500 mt-0.5">
                                    Personalize text size, table density, and theme for your account only
                                </p>
                            </div>
                        </div>
                        <button type="button"
                                onclick="closeDisplaySettingsModal(true)"
                                aria-label="Close display settings"
                                class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- 1. Text Size (3 options) -->
                    <fieldset class="space-y-1.5">
                        <legend class="block text-xs font-semibold text-slate-700">Text size</legend>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach (['small' => 'Small (14px)', 'default' => 'Default (16px)', 'large' => 'Large (18px)'] as $sizeKey => $sizeLabel)
                                <label class="cursor-pointer">
                                    <input type="radio"
                                           name="text_size"
                                           value="{{ $sizeKey }}"
                                           class="sr-only peer"
                                           onchange="previewDisplayPreferences()"
                                           {{ $userTextSize === $sizeKey ? 'checked' : '' }}>
                                    <span class="min-h-[38px] px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold flex items-center justify-center text-center peer-checked:border-primary peer-checked:ring-2 peer-checked:ring-primary peer-checked:text-slate-900 transition-colors">
                                        {{ $sizeLabel }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <!-- 2. Density (2 options) -->
                    <fieldset class="space-y-1.5">
                        <legend class="block text-xs font-semibold text-slate-700">Density</legend>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach (['comfortable' => 'Comfortable', 'compact' => 'Compact'] as $densityKey => $densityLabel)
                                <label class="cursor-pointer">
                                    <input type="radio"
                                           name="density"
                                           value="{{ $densityKey }}"
                                           class="sr-only peer"
                                           onchange="previewDisplayPreferences()"
                                           {{ $userDensity === $densityKey ? 'checked' : '' }}>
                                    <span class="min-h-[38px] px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold flex items-center justify-center text-center peer-checked:border-primary peer-checked:ring-2 peer-checked:ring-primary peer-checked:text-slate-900 transition-colors">
                                        {{ $densityLabel }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <!-- 3. Theme (2 options) -->
                    <fieldset class="space-y-1.5">
                        <legend class="block text-xs font-semibold text-slate-700">Theme</legend>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach (['light' => 'Light', 'dark' => 'Dark'] as $themeKey => $themeLabel)
                                <label class="cursor-pointer">
                                    <input type="radio"
                                           name="theme"
                                           value="{{ $themeKey }}"
                                           class="sr-only peer"
                                           onchange="previewDisplayPreferences()"
                                           {{ $userTheme === $themeKey ? 'checked' : '' }}>
                                    <span class="min-h-[38px] px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold flex items-center justify-center text-center peer-checked:border-primary peer-checked:ring-2 peer-checked:ring-primary peer-checked:text-slate-900 transition-colors">
                                        {{ $themeLabel }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <!-- Live Preview Area (Sample Card, Button, and Table Row) -->
                    <div id="display-settings-live-preview"
                         data-text-size="{{ $userTextSize }}"
                         data-density="{{ $userDensity }}"
                         data-theme="{{ $userTheme }}"
                         class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Live preview</span>
                            <span id="display-preview-summary" class="text-[11px] font-medium text-slate-500">
                                {{ ucfirst($userTextSize) }} text &bull; {{ ucfirst($userDensity) }} &bull; {{ ucfirst($userTheme) }}
                            </span>
                        </div>

                        <div class="bg-white border border-slate-200 rounded-lg overflow-hidden shadow-2xs">
                            <div class="density-card-header px-4 py-2.5 border-b border-slate-100 flex items-center justify-between gap-2">
                                <div>
                                    <p class="text-xs font-semibold text-slate-900">Sample Equipment Card</p>
                                    <p class="text-[11px] text-slate-500">Emergency Room &bull; Triage</p>
                                </div>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-primary text-white">
                                    Sample Action
                                </span>
                            </div>
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-semibold text-slate-700">
                                    <tr>
                                        <th class="py-2 px-3">Asset No.</th>
                                        <th class="py-2 px-3">Description</th>
                                        <th class="py-2 px-3 text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="h-[44px]">
                                        <td class="py-2 px-3 font-mono font-semibold text-slate-900">BBHI-ER-0001</td>
                                        <td class="py-2 px-3 text-slate-800">Patient Monitor</td>
                                        <td class="py-2 px-3 text-right">
                                            <x-badge status="In Use" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button"
                                id="display-settings-cancel-btn"
                                onclick="closeDisplaySettingsModal(true)"
                                class="min-h-[38px] px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                                id="display-settings-save-btn"
                                class="min-h-[38px] px-4 py-2 text-xs font-semibold text-white bg-primary hover:bg-primary-hover rounded-lg shadow-2xs cursor-pointer">
                            Save Display Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            const savedDisplayPrefs = {
                text_size: @json($userTextSize),
                density: @json($userDensity),
                theme: @json($userTheme),
            };

            function applyDisplayPrefsToDocument(prefs) {
                const html = document.documentElement;
                const body = document.body;
                if (!html || !body) return;

                ['small', 'default', 'large'].forEach(s => {
                    html.classList.remove('text-size-' + s);
                    body.classList.remove('text-size-' + s);
                });
                ['comfortable', 'compact'].forEach(d => {
                    html.classList.remove('density-' + d);
                    body.classList.remove('density-' + d);
                });
                ['light', 'dark'].forEach(t => {
                    html.classList.remove('theme-' + t);
                    body.classList.remove('theme-' + t);
                });

                html.setAttribute('data-text-size', prefs.text_size);
                html.setAttribute('data-density', prefs.density);
                html.setAttribute('data-theme', prefs.theme);
                html.classList.add('text-size-' + prefs.text_size, 'density-' + prefs.density, 'theme-' + prefs.theme);

                body.setAttribute('data-text-size', prefs.text_size);
                body.setAttribute('data-density', prefs.density);
                body.setAttribute('data-theme', prefs.theme);
                body.classList.add('text-size-' + prefs.text_size, 'density-' + prefs.density, 'theme-' + prefs.theme);

                const previewBox = document.getElementById('display-settings-live-preview');
                if (previewBox) {
                    previewBox.setAttribute('data-text-size', prefs.text_size);
                    previewBox.setAttribute('data-density', prefs.density);
                    previewBox.setAttribute('data-theme', prefs.theme);
                }

                const summary = document.getElementById('display-preview-summary');
                if (summary) {
                    const cap = s => s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
                    summary.textContent = cap(prefs.text_size) + ' text \u2022 ' + cap(prefs.density) + ' \u2022 ' + cap(prefs.theme);
                }
            }

            function previewDisplayPreferences() {
                const form = document.getElementById('display-settings-form');
                if (!form) return;
                const textSize = form.querySelector('input[name="text_size"]:checked')?.value || savedDisplayPrefs.text_size;
                const density = form.querySelector('input[name="density"]:checked')?.value || savedDisplayPrefs.density;
                const theme = form.querySelector('input[name="theme"]:checked')?.value || savedDisplayPrefs.theme;

                applyDisplayPrefsToDocument({
                    text_size: textSize,
                    density: density,
                    theme: theme,
                });
            }

            function openDisplaySettingsModal() {
                const modal = document.getElementById('display-settings-modal');
                if (modal) {
                    modal.classList.remove('hidden');
                    setTimeout(() => {
                        const firstRadio = modal.querySelector('input[name="text_size"]:checked');
                        if (firstRadio) firstRadio.focus();
                    }, 50);
                }
            }

            function closeDisplaySettingsModal(revertChanges) {
                const modal = document.getElementById('display-settings-modal');
                if (modal) {
                    modal.classList.add('hidden');
                }
                if (revertChanges) {
                    const form = document.getElementById('display-settings-form');
                    if (form) {
                        form.querySelectorAll('input[name="text_size"]').forEach(r => { r.checked = (r.value === savedDisplayPrefs.text_size); });
                        form.querySelectorAll('input[name="density"]').forEach(r => { r.checked = (r.value === savedDisplayPrefs.density); });
                        form.querySelectorAll('input[name="theme"]').forEach(r => { r.checked = (r.value === savedDisplayPrefs.theme); });
                    }
                    applyDisplayPrefsToDocument(savedDisplayPrefs);
                }
                const triggerBtn = document.getElementById('display-settings-trigger-btn');
                if (triggerBtn) triggerBtn.focus();
            }

            function openSignOutModal() {
                const modal = document.getElementById('signout-modal');
                if (modal) {
                    modal.classList.remove('hidden');
                    setTimeout(() => {
                        const cancelBtn = document.getElementById('signout-cancel-btn');
                        if (cancelBtn) cancelBtn.focus();
                    }, 50);
                }
            }

            function closeSignOutModal() {
                const modal = document.getElementById('signout-modal');
                if (modal) {
                    modal.classList.add('hidden');
                    const triggerBtn = document.getElementById('signout-trigger-btn');
                    if (triggerBtn) triggerBtn.focus();
                }
            }

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    const displayModal = document.getElementById('display-settings-modal');
                    if (displayModal && !displayModal.classList.contains('hidden')) {
                        closeDisplaySettingsModal(true);
                        return;
                    }
                    const modal = document.getElementById('signout-modal');
                    if (modal && !modal.classList.contains('hidden')) {
                        closeSignOutModal();
                    }
                }
            });
        </script>
    @endauth
</body>
</html>
