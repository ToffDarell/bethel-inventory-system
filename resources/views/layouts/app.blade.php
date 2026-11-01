<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Bethel Asset Tracker' }} - {{ \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-slate-900 font-sans antialiased text-base min-h-screen flex">
    <!-- Left Sidebar -->
    <aside class="no-print w-64 bg-slate-50 border-r border-slate-200 flex flex-col justify-between shrink-0 min-h-screen">
        <div>
            <div class="p-6 border-b border-slate-200 bg-blue-700 text-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-white/15 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold leading-tight">Bethel Asset Tracker</h1>
                        <p class="text-xs text-blue-100 truncate max-w-[165px]">{{ \App\Models\Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.') }}</p>
                    </div>
                </div>
            </div>

            <nav class="p-4 space-y-2" aria-label="Main Navigation">
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-4 py-3.5 rounded-lg text-base font-semibold transition {{ request()->routeIs('dashboard') ? 'bg-blue-700 text-white shadow-sm' : 'text-slate-800 hover:bg-slate-200/70' }}">
                    <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('assets.index') }}"
                   class="flex items-center gap-3 px-4 py-3.5 rounded-lg text-base font-semibold transition {{ request()->routeIs('assets.*') ? 'bg-blue-700 text-white shadow-sm' : 'text-slate-800 hover:bg-slate-200/70' }}">
                    <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span>Assets</span>
                </a>

                <a href="{{ route('scan.index') }}"
                   class="flex items-center gap-3 px-4 py-3.5 rounded-lg text-base font-semibold transition {{ request()->routeIs('scan.*') ? 'bg-blue-700 text-white shadow-sm' : 'text-slate-800 hover:bg-slate-200/70' }}">
                    <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                    <span>Scan</span>
                </a>

                <a href="{{ route('reports.index') }}"
                   class="flex items-center gap-3 px-4 py-3.5 rounded-lg text-base font-semibold transition {{ request()->routeIs('reports.*') ? 'bg-blue-700 text-white shadow-sm' : 'text-slate-800 hover:bg-slate-200/70' }}">
                    <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Reports</span>
                </a>

                <a href="{{ route('settings.index') }}"
                   class="flex items-center gap-3 px-4 py-3.5 rounded-lg text-base font-semibold transition {{ request()->routeIs('settings.*') ? 'bg-blue-700 text-white shadow-sm' : 'text-slate-800 hover:bg-slate-200/70' }}">
                    <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Settings</span>
                </a>
            </nav>
        </div>

        @auth
            <div class="p-4 border-t border-slate-200 bg-slate-100">
                <div class="mb-3">
                    <p class="text-sm font-bold text-slate-900 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-600 truncate">{{ auth()->user()->email }} ({{ ucfirst(auth()->user()->role) }})</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-800 font-semibold text-sm hover:bg-slate-50 transition cursor-pointer">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Sign Out</span>
                    </button>
                </form>
            </div>
        @endauth
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8 overflow-y-auto bg-white">
        <div class="max-w-6xl mx-auto">
            @if (session('success'))
                <div class="mb-6 p-4 rounded-lg bg-emerald-50 border-2 border-emerald-500 text-emerald-900 flex items-center gap-3" role="alert">
                    <svg class="w-6 h-6 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-semibold text-base">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 p-4 rounded-lg bg-red-50 border-2 border-red-500 text-red-900 flex items-center gap-3" role="alert">
                    <svg class="w-6 h-6 text-red-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-semibold text-base">{{ session('error') }}</span>
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</body>
</html>
