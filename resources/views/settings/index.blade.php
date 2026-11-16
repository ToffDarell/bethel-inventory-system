@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <!-- Page Header -->
        <x-page-header
            title="Settings & Backup"
            subtitle="Configure hospital branding, property tag dimensions, user accounts, and SQLite database backup registry."
        >
            @if (auth()->user()?->isAdmin())
                <x-slot:actions>
                    <x-button variant="secondary" :href="route('settings.activity-log')">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>View Activity Log</span>
                    </x-button>
                </x-slot:actions>
            @endif
        </x-page-header>

        {{-- Restart Required Banner after Restore --}}
        @if (session('restart_required'))
            <div class="p-5 rounded-xl bg-amber-50 border border-amber-300 text-amber-950 flex flex-wrap items-center justify-between gap-4 shadow-2xs" role="alert">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <p class="font-semibold text-base text-amber-950">Application Restart Required</p>
                        <p class="text-xs text-amber-800 mt-0.5">
                            The SQLite database was restored from backup. Please restart Bethel Asset Tracker now to reload all inventory records cleanly.
                        </p>
                    </div>
                </div>
                <form method="POST" action="{{ route('settings.relaunch') }}">
                    @csrf
                    <x-button variant="primary" type="submit">
                        Restart Application Now
                    </x-button>
                </form>
            </div>
        @endif

        {{-- 7-Day Backup Reminder Warning Banner (Required test assertion: "No database backup has been created in the last 7 days") --}}
        @if ($backupOverdue)
            <div class="p-5 rounded-xl bg-amber-50/90 border border-amber-300 text-amber-950 flex flex-wrap items-center justify-between gap-4 shadow-2xs" role="alert">
                <div class="flex items-start gap-3.5">
                    <svg class="w-5 h-5 text-amber-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <p class="font-semibold text-sm text-amber-950">
                            Backup Reminder: No database backup has been created in the last 7 days.
                        </p>
                        <p class="text-xs text-amber-800 mt-0.5">
                            Create a safe offline snapshot of your hospital asset register to protect against hardware failure or accidental data loss.
                            Saving to: <code class="font-mono text-xs bg-white/80 px-1.5 py-0.5 rounded border border-amber-200 text-amber-900">{{ $backupFolder }}</code>
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('settings.backup') }}">
                    @csrf
                    <x-button variant="primary" type="submit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        <span>Backup Now</span>
                    </x-button>
                </form>
            </div>
        @endif

        {{-- Validation Errors Banner --}}
        @if ($errors->any())
            <x-alert type="error" title="Please review the following issues:">
                <ul class="list-disc list-inside space-y-0.5 mt-1 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        @if (session('admin_recovery_code_once'))
            <div class="p-4 rounded-xl bg-amber-50 border border-amber-300 text-amber-950 shadow-2xs" role="region" aria-label="One-time administrator recovery code">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="font-bold text-sm text-amber-950">One-Time Admin Recovery Code</p>
                        <p class="text-xs text-amber-900 mt-0.5">Save this code now. It will not be shown again after you leave this page.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <code class="px-3 py-1.5 rounded-lg bg-white border border-amber-300 font-mono text-sm font-bold tracking-wider text-slate-900 select-all">{{ session('admin_recovery_code_once') }}</code>
                        <button type="button"
                                onclick="navigator.clipboard?.writeText('{{ session('admin_recovery_code_once') }}'); this.textContent='Copied';"
                                class="px-3 py-1.5 rounded-lg bg-amber-900 hover:bg-amber-950 text-white text-xs font-semibold cursor-pointer">
                            Copy
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Tab Navigation Bar -->
        @php
            $currentTab = request()->query('tab', 'hospital');
            if (! in_array($currentTab, ['hospital', 'tag', 'backup', 'display', 'categories', 'departments', 'vendors', 'asset-templates', 'depreciation', 'users'], true)) {
                $currentTab = 'hospital';
            }
        @endphp
        <div class="border-b border-slate-200" id="settings-tab-container">
            <nav class="flex flex-nowrap overflow-x-auto gap-1 -mb-px" aria-label="Settings Tabs" role="tablist">
                <button type="button"
                        onclick="switchTab('hospital')"
                        id="tab-btn-hospital"
                        role="tab"
                        aria-controls="panel-hospital"
                        aria-selected="{{ $currentTab === 'hospital' ? 'true' : 'false' }}"
                        class="tab-btn whitespace-nowrap shrink-0 min-h-[44px] px-4 py-2.5 text-sm {{ $currentTab === 'hospital' ? 'font-semibold border-b-2 border-primary text-primary' : 'font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }} transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4 {{ $currentTab === 'hospital' ? 'text-primary' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span>Hospital Profile</span>
                </button>

                <button type="button"
                        onclick="switchTab('tag')"
                        id="tab-btn-tag"
                        role="tab"
                        aria-controls="panel-tag"
                        aria-selected="{{ $currentTab === 'tag' ? 'true' : 'false' }}"
                        class="tab-btn whitespace-nowrap shrink-0 min-h-[44px] px-4 py-2.5 text-sm {{ $currentTab === 'tag' ? 'font-semibold border-b-2 border-primary text-primary' : 'font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }} transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4 {{ $currentTab === 'tag' ? 'text-primary' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    <span>Property Tag</span>
                </button>

                <button type="button"
                        onclick="switchTab('backup')"
                        id="tab-btn-backup"
                        role="tab"
                        aria-controls="panel-backup"
                        aria-selected="{{ $currentTab === 'backup' ? 'true' : 'false' }}"
                        class="tab-btn whitespace-nowrap shrink-0 min-h-[44px] px-4 py-2.5 text-sm {{ $currentTab === 'backup' ? 'font-semibold border-b-2 border-primary text-primary' : 'font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }} transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4 {{ $currentTab === 'backup' ? 'text-primary' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7M4 7c0-2 1.5-3 3.5-3h9c2 0 3.5 1 3.5 3M4 7h16m-8 4v6m-3-3l3 3 3-3"/>
                    </svg>
                    <span>Backup &amp; Restore</span>
                    @if ($backupOverdue)
                        <span class="w-2 h-2 rounded-full bg-amber-500" title="Backup overdue"></span>
                    @endif
                </button>

                <button type="button"
                        onclick="switchTab('display')"
                        id="tab-btn-display"
                        role="tab"
                        aria-controls="panel-display"
                        aria-selected="{{ $currentTab === 'display' ? 'true' : 'false' }}"
                        class="tab-btn whitespace-nowrap shrink-0 min-h-[44px] px-4 py-2.5 text-sm {{ $currentTab === 'display' ? 'font-semibold border-b-2 border-primary text-primary' : 'font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }} transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4 {{ $currentTab === 'display' ? 'text-primary' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/>
                    </svg>
                    <span>Display Settings</span>
                </button>

                @if (auth()->user()?->isAdmin())
                    <button type="button"
                            onclick="switchTab('categories')"
                            id="tab-btn-categories"
                            role="tab"
                            aria-controls="panel-categories"
                            aria-selected="{{ $currentTab === 'categories' ? 'true' : 'false' }}"
                            class="tab-btn whitespace-nowrap shrink-0 min-h-[44px] px-4 py-2.5 text-sm {{ $currentTab === 'categories' ? 'font-semibold border-b-2 border-primary text-primary' : 'font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }} transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4 {{ $currentTab === 'categories' ? 'text-primary' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                        </svg>
                        <span>Categories</span>
                    </button>

                    <button type="button"
                            onclick="switchTab('departments')"
                            id="tab-btn-departments"
                            role="tab"
                            aria-controls="panel-departments"
                            aria-selected="{{ $currentTab === 'departments' ? 'true' : 'false' }}"
                            class="tab-btn whitespace-nowrap shrink-0 min-h-[44px] px-4 py-2.5 text-sm {{ $currentTab === 'departments' ? 'font-semibold border-b-2 border-primary text-primary' : 'font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }} transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4 {{ $currentTab === 'departments' ? 'text-primary' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.75a1.5 1.5 0 011.5-1.5h1.5a1.5 1.5 0 011.5 1.5V21"/>
                        </svg>
                        <span>Departments &amp; Sections</span>
                        @php
                            $unconfirmedCount = (isset($departments) ? $departments->where('code_confirmed', false)->count() : 0)
                                + (isset($sections) ? $sections->where('code_confirmed', false)->count() : 0);
                        @endphp
                        @if ($unconfirmedCount > 0)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800" title="{{ $unconfirmedCount }} department or section codes need review">
                                {{ $unconfirmedCount }}
                            </span>
                        @endif
                    </button>

                    <button type="button"
                            onclick="switchTab('vendors')"
                            id="tab-btn-vendors"
                            role="tab"
                            aria-controls="panel-vendors"
                            aria-selected="{{ $currentTab === 'vendors' ? 'true' : 'false' }}"
                            class="tab-btn whitespace-nowrap shrink-0 min-h-[44px] px-3.5 py-2.5 text-sm {{ $currentTab === 'vendors' ? 'font-semibold border-b-2 border-primary text-primary' : 'font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }} transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4 {{ $currentTab === 'vendors' ? 'text-primary' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                        </svg>
                        <span>Vendors</span>
                    </button>

                    <button type="button"
                            onclick="switchTab('asset-templates')"
                            id="tab-btn-asset-templates"
                            role="tab"
                            aria-controls="panel-asset-templates"
                            aria-selected="{{ $currentTab === 'asset-templates' ? 'true' : 'false' }}"
                            class="tab-btn whitespace-nowrap shrink-0 min-h-[44px] px-3.5 py-2.5 text-sm {{ $currentTab === 'asset-templates' ? 'font-semibold border-b-2 border-primary text-primary' : 'font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }} transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4 {{ $currentTab === 'asset-templates' ? 'text-primary' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                        </svg>
                        <span>Asset Templates</span>
                    </button>

                    <button type="button"
                            onclick="switchTab('depreciation')"
                            id="tab-btn-depreciation"
                            role="tab"
                            aria-controls="panel-depreciation"
                            aria-selected="{{ $currentTab === 'depreciation' ? 'true' : 'false' }}"
                            class="tab-btn whitespace-nowrap shrink-0 min-h-[44px] px-4 py-2.5 text-sm {{ $currentTab === 'depreciation' ? 'font-semibold border-b-2 border-primary text-primary' : 'font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }} transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4 {{ $currentTab === 'depreciation' ? 'text-primary' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Depreciation</span>
                    </button>

                    <button type="button"
                            onclick="switchTab('users')"
                            id="tab-btn-users"
                            role="tab"
                            aria-controls="panel-users"
                            aria-selected="{{ $currentTab === 'users' ? 'true' : 'false' }}"
                            class="tab-btn whitespace-nowrap shrink-0 min-h-[44px] px-4 py-2.5 text-sm {{ $currentTab === 'users' ? 'font-semibold border-b-2 border-primary text-primary' : 'font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }} transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4 {{ $currentTab === 'users' ? 'text-primary' : 'text-slate-400' }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span>User Accounts</span>
                    </button>
                @endif
            </nav>
        </div>

        <!-- TAB 1: HOSPITAL PROFILE -->
        <div id="panel-hospital" role="tabpanel" class="tab-panel {{ $currentTab === 'hospital' ? '' : 'hidden' }} space-y-6">
            <x-card title="Hospital Information & Official Emblem" subtitle="Hospital name and address appear on all printed property tags, reports, and audit certificates.">
                <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="max-w-2xl space-y-5">
                    @csrf
                    {{-- Hidden preserved values from other sections to pass validation --}}
                    <input type="hidden" name="asset_no_prefix" value="{{ $assetNoPrefix }}">
                    <input type="hidden" name="tag_width_cm" value="{{ $tagWidthCm }}">
                    <input type="hidden" name="tag_height_cm" value="{{ $tagHeightCm }}">
                    <input type="hidden" name="backup_folder" value="{{ $backupFolder }}">

                    <div>
                        <label for="hospital_name" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Hospital Name <span class="text-brand-red">*</span>
                        </label>
                        <input id="hospital_name"
                               name="hospital_name"
                               type="text"
                               required
                               value="{{ old('hospital_name', $hospitalName) }}"
                               class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-slate-900 transition-colors">
                        <p class="text-xs text-slate-500 mt-1">Official registered name of the healthcare institution.</p>
                    </div>

                    <div>
                        <label for="hospital_short_name" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Short name <span class="text-brand-red">*</span>
                        </label>
                        <input id="hospital_short_name"
                               name="hospital_short_name"
                               type="text"
                               minlength="2"
                               maxlength="10"
                               required
                               value="{{ old('hospital_short_name', $hospitalShortName ?? 'BBH') }}"
                               class="w-full max-w-xs min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-slate-900 transition-colors">
                        <p class="text-xs text-slate-500 mt-1">Short hospital abbreviation shown under the system title in the top navigation bar (2 to 10 letters or numbers, default BBH).</p>
                    </div>

                    <div>
                        <label for="hospital_address" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Hospital Address <span class="text-brand-red">*</span>
                        </label>
                        <input id="hospital_address"
                               name="hospital_address"
                               type="text"
                               required
                               value="{{ old('hospital_address', $hospitalAddress) }}"
                               class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-slate-900 transition-colors">
                        <p class="text-xs text-slate-500 mt-1">Physical street address printed on barcode tags.</p>
                    </div>

                    <div>
                        <label for="pdf_paper_size" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Default PDF Export Paper Size
                        </label>
                        <select id="pdf_paper_size"
                                name="pdf_paper_size"
                                class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-slate-900 transition-colors">
                            <option value="a4" {{ old('pdf_paper_size', $pdfPaperSize) === 'a4' ? 'selected' : '' }}>A4 (210 x 297 mm) - Standard</option>
                            <option value="letter" {{ old('pdf_paper_size', $pdfPaperSize) === 'letter' ? 'selected' : '' }}>Letter (8.5 x 11 in)</option>
                            <option value="legal" {{ old('pdf_paper_size', $pdfPaperSize) === 'legal' ? 'selected' : '' }}>Legal (8.5 x 14 in)</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-1">Default page size for PDF inventory and depreciation report exports.</p>
                    </div>

                    <div class="pt-2 border-t border-slate-100">
                        <label for="logo" class="block text-xs font-semibold text-slate-700 mb-2">
                            Hospital Logo (Embedded as Base64 on Property Tags)
                        </label>
                        <div class="flex items-center gap-5 mb-3 p-4 bg-slate-50 border border-slate-200 rounded-xl">
                            <div class="p-2 border border-slate-200 rounded-lg bg-white shrink-0 shadow-2xs">
                                <img src="{{ $logoDataUri }}" alt="Current Hospital Logo" class="h-14 w-auto object-contain">
                            </div>
                            <div class="text-xs text-slate-600 leading-relaxed">
                                <p class="font-semibold text-slate-800 mb-0.5">Bethel Baptist Hospital, Inc. (BBH) Emblem</p>
                                <p>Rendered on all asset labels and report headers. Supported file types: PNG, JPG, JPEG, SVG, or WEBP (up to 5MB).</p>
                            </div>
                        </div>
                        <input id="logo"
                               name="logo"
                               type="file"
                               accept=".png,.jpg,.jpeg,.svg,.webp"
                               class="w-full min-h-[44px] px-3.5 py-2 text-xs bg-white border border-slate-300 rounded-lg file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-slate-100 file:text-slate-800 hover:file:bg-slate-200 focus:outline-none focus:border-slate-900 transition-colors">
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <x-button variant="primary" type="submit">
                            Save Hospital Profile
                        </x-button>
                    </div>
                </form>
            </x-card>
        </div>

        <!-- TAB 2: PROPERTY TAG CONFIGURATION -->
        <div id="panel-tag" role="tabpanel" class="tab-panel {{ $currentTab === 'tag' ? '' : 'hidden' }} space-y-6">
            <x-card title="Property Tag Dimensions & Code Prefix" subtitle="Configure physical label dimensions, barcode output, and asset code sequencing for equipment tags.">
                <form method="POST" action="{{ route('settings.update') }}" id="tag-settings-form">
                    @csrf
                    {{-- Hidden preserved values from other sections --}}
                    <input type="hidden" name="hospital_name" value="{{ $hospitalName }}">
                    <input type="hidden" name="hospital_short_name" value="{{ $hospitalShortName ?? 'BBH' }}">
                    <input type="hidden" name="hospital_address" value="{{ $hospitalAddress }}">
                    <input type="hidden" name="backup_folder" value="{{ $backupFolder }}">
                    <input type="hidden" name="pdf_paper_size" value="{{ $pdfPaperSize }}">

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                        <!-- Left Column: Form Controls (Inputs & Toggles) -->
                        <div class="lg:col-span-5 space-y-5">
                            <div>
                                <label for="asset_no_prefix" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Asset No. Prefix <span class="text-brand-red">*</span>
                                </label>
                                <input id="asset_no_prefix"
                                       name="asset_no_prefix"
                                       type="text"
                                       required
                                       value="{{ old('asset_no_prefix', $assetNoPrefix) }}"
                                       class="w-full max-w-xs min-h-[44px] px-3.5 py-2 text-sm font-mono font-bold bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-slate-900 transition-colors">
                                <p class="text-xs text-slate-500 mt-1">Starting prefix used when auto-generating asset codes (e.g., <code>BBHI-000001</code>).</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                                <div>
                                    <label for="tag_width_cm" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                        Physical Tag Width (cm) <span class="text-brand-red">*</span>
                                    </label>
                                    <input id="tag_width_cm"
                                           name="tag_width_cm"
                                           type="number"
                                           step="0.1"
                                           min="3"
                                           max="30"
                                           required
                                           value="{{ old('tag_width_cm', $tagWidthCm) }}"
                                           class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-slate-900 transition-colors">
                                    <p class="text-xs text-slate-500 mt-1">Standard tag width in cm (default: 10 cm).</p>
                                </div>

                                <div>
                                    <label for="tag_height_cm" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                        Physical Tag Height (cm) <span class="text-brand-red">*</span>
                                    </label>
                                    <input id="tag_height_cm"
                                           name="tag_height_cm"
                                           type="number"
                                           step="0.1"
                                           min="2"
                                           max="30"
                                           required
                                           value="{{ old('tag_height_cm', $tagHeightCm) }}"
                                           class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-slate-900 transition-colors">
                                    <p class="text-xs text-slate-500 mt-1">Standard tag height in cm (default: 6 cm).</p>
                                </div>
                            </div>

                            @if (auth()->user()?->isAdmin())
                                <div class="pt-4 border-t border-slate-100">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1 min-w-0">
                                            <label for="show_barcode_toggle" class="block text-sm font-semibold text-slate-900 cursor-pointer">
                                                Show barcode on tag
                                            </label>
                                            <p class="text-xs text-slate-500 mt-0.5">
                                                Turn this on only if you use a barcode scanner. The barcode encodes the Asset No.
                                            </p>
                                        </div>
                                        <div class="shrink-0 pt-0.5">
                                            <input type="hidden" name="show_barcode" value="0">
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox"
                                                       id="show_barcode_toggle"
                                                       name="show_barcode"
                                                       value="1"
                                                       {{ $showBarcode ? 'checked' : '' }}
                                                       onchange="updateBarcodePreview(this.checked)"
                                                       class="sr-only peer">
                                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="pt-4 border-t border-slate-100 flex items-center justify-start gap-3">
                                <x-button variant="primary" type="submit">
                                    Save Tag Settings
                                </x-button>
                            </div>
                        </div>

                        <!-- Right Column: Live 1:1 Tag Preview -->
                        <div class="lg:col-span-7">
                            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-5 space-y-3.5">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-900 tracking-tight flex items-center gap-2">
                                            <span>Live 1:1 Physical Tag Preview</span>
                                            <span id="preview-dim-badge" class="px-2 py-0.5 rounded text-[11px] font-mono font-semibold bg-white border border-slate-200 text-slate-700 tabular-nums">
                                                {{ $tagWidthCm }} cm &times; {{ $tagHeightCm }} cm
                                            </span>
                                        </h4>
                                        <p class="text-[11px] text-slate-500 mt-0.5">
                                            Renders at exact physical print dimensions (1 cm on screen = 1 cm in print).
                                        </p>
                                    </div>
                                    <span id="barcode-preview-status" class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $showBarcode ? 'bg-primary-soft text-primary' : 'bg-slate-200 text-slate-700' }}">
                                        {{ $showBarcode ? 'Barcode Enabled' : 'No Barcode' }}
                                    </span>
                                </div>

                                <div class="p-4 bg-white rounded-lg border border-slate-200/80 shadow-2xs flex items-center justify-center overflow-x-auto min-h-[220px]">
                                    <div class="bg-white shadow-xs inline-block transition-all duration-150" id="tag-preview-wrapper">
                                        @include('tags.partials.tag-card', [
                                            'asset' => $sampleAsset,
                                            'showBarcode' => $showBarcode,
                                            'preview' => true,
                                            'tagAssetService' => $tagAssetService,
                                            'tagWidthCm' => $tagWidthCm,
                                            'tagHeightCm' => $tagHeightCm,
                                        ])
                                    </div>
                                </div>

                                <div class="text-[11px] text-slate-500 flex items-start gap-1.5 leading-relaxed pt-1">
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>
                                        Sample asset uses a long name and long Asset No. to test label boundaries before printing. Updates dynamically as you type without saving.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </x-card>
        </div>

        <!-- TAB 3: BACKUP & RESTORE -->
        <div id="panel-backup" role="tabpanel" class="tab-panel {{ $currentTab === 'backup' ? '' : 'hidden' }} space-y-6">
            <!-- Backup Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-card padding="p-5">
                    <div class="flex items-center justify-between gap-3 mb-2.5">
                        <span class="text-xs font-semibold text-slate-700">Active SQLite Database</span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span>Connected</span>
                        </span>
                    </div>
                    <div class="font-mono text-xs font-medium text-slate-900 break-all bg-slate-50 p-3 rounded-lg border border-slate-200 min-h-[46px] flex items-center">
                        @if (auth()->user()?->isAdmin())
                            {{ $activeDatabasePath }}
                        @else
                            <span class="text-slate-500 font-sans italic">Protected (Administrator access only)</span>
                        @endif
                    </div>
                </x-card>

                <x-card padding="p-5">
                    <div class="flex items-center justify-between gap-3 mb-2.5">
                        <span class="text-xs font-semibold text-slate-700">Backup Directory</span>
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button"
                                    onclick="document.getElementById('backup-folder-editor').classList.toggle('hidden'); document.getElementById('backup_folder_input')?.focus();"
                                    class="min-h-[32px] inline-flex items-center gap-1.5 px-3 py-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-xs font-semibold text-slate-700 transition-colors cursor-pointer">
                                <span>Edit Path</span>
                            </button>
                            <form method="POST" action="{{ route('settings.pick-folder') }}" class="shrink-0">
                                @csrf
                                <button type="submit"
                                        class="min-h-[32px] inline-flex items-center gap-1.5 px-3 py-1 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 transition-colors shadow-2xs cursor-pointer">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776"/>
                                    </svg>
                                    <span>Choose Folder...</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="font-mono text-xs font-medium text-slate-900 break-all bg-slate-50 p-3 rounded-lg border border-slate-200 min-h-[46px] flex items-center">
                        {{ $backupFolder }}
                    </div>

                    <form id="backup-folder-editor"
                          method="POST"
                          action="{{ route('settings.update') }}"
                          class="{{ session('show_backup_folder_editor') ? '' : 'hidden' }} mt-3 pt-3 border-t border-slate-200 space-y-2">
                        @csrf
                        <input type="hidden" name="active_tab" value="backup">
                        <input type="hidden" name="hospital_name" value="{{ $hospitalName }}">
                        <input type="hidden" name="hospital_short_name" value="{{ $hospitalShortName ?? 'BBH' }}">
                        <input type="hidden" name="hospital_address" value="{{ $hospitalAddress }}">
                        <input type="hidden" name="asset_no_prefix" value="{{ $assetNoPrefix }}">
                        <input type="hidden" name="tag_width_cm" value="{{ $tagWidthCm }}">
                        <input type="hidden" name="tag_height_cm" value="{{ $tagHeightCm }}">
                        <input type="hidden" name="pdf_paper_size" value="{{ $pdfPaperSize }}">
                        <label for="backup_folder_input" class="block text-xs font-semibold text-slate-700">
                            Backup Folder Path
                        </label>
                        <div class="flex flex-wrap items-center gap-2">
                            <input id="backup_folder_input"
                                   name="backup_folder"
                                   type="text"
                                   value="{{ old('backup_folder', $backupFolder) }}"
                                   placeholder="e.g. D:\BethelBackups"
                                   class="flex-1 min-w-[220px] min-h-[38px] px-3 py-1.5 text-xs font-mono bg-white border border-slate-300 rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary">
                            <x-button variant="primary" type="submit" size="sm">
                                Save Backup Folder
                            </x-button>
                        </div>
                    </form>
                </x-card>
            </div>

            <!-- Backup Action Bar -->
            <x-card padding="p-5">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Create Database Backup</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Automated backups run on app startup every 24 hours. Retains the 30 most recent snapshots automatically.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('settings.backup') }}">
                        @csrf
                        <x-button variant="primary" type="submit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                            </svg>
                            <span>Backup Now</span>
                        </x-button>
                    </form>
                </div>
            </x-card>

            <!-- Saved Backups Table -->
            <x-card title="Saved Backup Archives ({{ count($backups) }} of 30 max)" subtitle="Stored in local backup folder. Ordered by most recent snapshot.">
                <x-table>
                    <x-slot:header>
                        <tr>
                            <th class="py-3 px-4">Backup Archive File</th>
                            <th class="py-3 px-4">Timestamp</th>
                            <th class="py-3 px-4">Size</th>
                            @if (auth()->user()?->isAdmin())
                                <th class="py-3 px-4 text-right">Restore Action</th>
                            @endif
                        </tr>
                    </x-slot:header>

                    @forelse (array_slice($backups, 0, 10) as $backup)
                        <tr>
                            <td class="py-3 px-4 font-mono text-xs font-semibold text-slate-900">
                                {{ $backup['filename'] }}
                            </td>
                            <td class="py-3 px-4 text-xs text-slate-600 whitespace-nowrap">
                                {{ $backup['modified_at']->format('M d, Y h:i:s A') }}
                            </td>
                            <td class="py-3 px-4 font-mono text-xs text-slate-600">
                                {{ number_format($backup['size_kb'], 1) }} KB
                            </td>
                            @if (auth()->user()?->isAdmin())
                                <td class="py-3 px-4 text-right">
                                    <form method="POST"
                                          action="{{ route('settings.restore') }}"
                                          onsubmit="return confirm('Restore database from {{ addslashes($backup['filename']) }}?\n\nYour current database will be backed up automatically first, and the application will restart.');">
                                        @csrf
                                        <input type="hidden" name="backup_filename" value="{{ $backup['filename'] }}">
                                        <button type="submit"
                                                class="min-h-[36px] px-3 py-1.5 rounded-lg border border-red-200 text-red-700 bg-white hover:bg-red-50 hover:border-red-400 font-semibold text-xs cursor-pointer transition-colors shadow-2xs">
                                            Restore Archive
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()?->isAdmin() ? 4 : 3 }}" class="p-8 text-center text-sm text-slate-500">
                                No backups found in <code class="font-mono text-xs">{{ $backupFolder }}</code> yet. Click <strong>Backup Now</strong> above to create your first backup.
                            </td>
                        </tr>
                    @endforelse
                </x-table>
            </x-card>

            <!-- External File Restore (Admin Only) -->
            @if (auth()->user()?->isAdmin())
                <div class="p-6 rounded-xl bg-white border border-red-200 shadow-2xs">
                    <div class="flex items-start gap-3.5 mb-4">
                        <div class="w-9 h-9 rounded-lg bg-red-50 border border-red-200 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-red-950">Restore from External SQLite Backup File (Admin Only)</h3>
                            <p class="text-xs text-red-800 mt-0.5 leading-relaxed">
                                Danger action: Select a <code>.sqlite</code> or <code>.db</code> file from a USB flash drive or network location. The system will verify SQLite header integrity, create a safety snapshot of the active database, and replace the database with the restored file.
                            </p>
                        </div>
                    </div>

                    <form method="POST"
                          action="{{ route('settings.restore') }}"
                          enctype="multipart/form-data"
                          onsubmit="return confirm('WARNING: Are you sure you want to restore the hospital database from the selected file?\n\nYour active database will be saved first, and Bethel Asset Tracker will restart.');"
                          class="flex flex-wrap items-center gap-3 pt-3 border-t border-red-100">
                        @csrf
                        <input type="file"
                               name="backup_file"
                               required
                               accept=".sqlite,.db"
                               class="flex-1 min-w-[280px] min-h-[44px] px-3.5 py-2 text-xs bg-white border border-red-300 rounded-lg file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-red-50 file:text-red-900 hover:file:bg-red-100 focus:outline-none focus:border-red-600 transition-colors">
                        <button type="submit"
                                class="min-h-[44px] px-5 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold text-sm shadow-2xs transition-colors cursor-pointer">
                            Validate &amp; Restore Database
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <!-- TAB: PERSONAL DISPLAY SETTINGS (ALL AUTHENTICATED USERS) -->
        @php
            $prefTextSize = auth()->user()?->effectiveTextSize() ?? 'default';
            $prefDensity = auth()->user()?->effectiveDensity() ?? 'comfortable';
            $prefTheme = auth()->user()?->effectiveTheme() ?? 'light';
        @endphp
        <div id="panel-display" role="tabpanel" class="tab-panel {{ $currentTab === 'display' ? '' : 'hidden' }} space-y-6">
            <x-card title="Personal Display Settings" subtitle="Customize text size, table density, and theme for your own account. Changes apply only to you and do not affect printed property tags or exported reports.">
                <form method="POST" action="{{ route('preferences.display.update') }}" id="settings-tab-display-form" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    @csrf
                    <div class="lg:col-span-6 space-y-5">
                        <!-- 1. Text Size -->
                        <fieldset class="space-y-2">
                            <legend class="block text-xs font-semibold text-slate-700">Text size</legend>
                            <div class="grid grid-cols-3 gap-2.5">
                                @foreach (['small' => 'Small (14px)', 'default' => 'Default (16px)', 'large' => 'Large (18px)'] as $sKey => $sLabel)
                                    <label class="cursor-pointer">
                                        <input type="radio"
                                               name="text_size"
                                               value="{{ $sKey }}"
                                               class="sr-only display-pref-radio"
                                               onchange="previewTabDisplayPreferences()"
                                               {{ $prefTextSize === $sKey ? 'checked' : '' }}>
                                        <span class="display-pref-option min-h-[42px] px-3 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold flex items-center justify-center gap-1.5 text-center transition-colors">
                                            <svg class="display-pref-check w-3.5 h-3.5 shrink-0 hidden" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span>{{ $sLabel }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="text-xs text-slate-500">Scales the base on-screen text size while keeping printed property tags and Excel/PDF exports unchanged.</p>
                        </fieldset>

                        <!-- 2. Density -->
                        <fieldset class="space-y-2 pt-2 border-t border-slate-100">
                            <legend class="block text-xs font-semibold text-slate-700">Table &amp; card density</legend>
                            <div class="grid grid-cols-2 gap-2.5">
                                @foreach (['comfortable' => 'Comfortable (Default)', 'compact' => 'Compact'] as $dKey => $dLabel)
                                    <label class="cursor-pointer">
                                        <input type="radio"
                                               name="density"
                                               value="{{ $dKey }}"
                                               class="sr-only display-pref-radio"
                                               onchange="previewTabDisplayPreferences()"
                                               {{ $prefDensity === $dKey ? 'checked' : '' }}>
                                        <span class="display-pref-option min-h-[42px] px-3 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold flex items-center justify-center gap-1.5 text-center transition-colors">
                                            <svg class="display-pref-check w-3.5 h-3.5 shrink-0 hidden" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span>{{ $dLabel }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="text-xs text-slate-500">Compact mode reduces row height and card padding so more equipment rows fit on screen at once.</p>
                        </fieldset>

                        <!-- 3. Theme -->
                        <fieldset class="space-y-2 pt-2 border-t border-slate-100">
                            <legend class="block text-xs font-semibold text-slate-700">Color theme</legend>
                            <div class="grid grid-cols-2 gap-2.5">
                                @foreach (['light' => 'Light', 'dark' => 'Dark'] as $tKey => $tLabel)
                                    <label class="cursor-pointer">
                                        <input type="radio"
                                               name="theme"
                                               value="{{ $tKey }}"
                                               class="sr-only display-pref-radio"
                                               onchange="previewTabDisplayPreferences()"
                                               {{ $prefTheme === $tKey ? 'checked' : '' }}>
                                        <span class="display-pref-option min-h-[42px] px-3 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold flex items-center justify-center gap-1.5 text-center transition-colors">
                                            <svg class="display-pref-check w-3.5 h-3.5 shrink-0 hidden" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span>{{ $tLabel }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="text-xs text-slate-500">Switches between clinical Light and high-contrast Dark mode. Property tag previews remain white like physical paper.</p>
                        </fieldset>

                        <div class="pt-4 border-t border-slate-100 flex items-center justify-start gap-3">
                            <x-button variant="primary" type="submit">
                                Save Display Settings
                            </x-button>
                        </div>
                    </div>

                    <!-- Right Column: Live Preview Area -->
                    <div class="lg:col-span-6">
                        <div class="p-5 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-bold text-slate-900">Live Display Preview</span>
                                <span id="settings-tab-display-summary" class="text-xs font-medium text-slate-500">
                                    {{ ucfirst($prefTextSize) }} text &bull; {{ ucfirst($prefDensity) }} &bull; {{ ucfirst($prefTheme) }}
                                </span>
                            </div>

                            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
                                <div class="density-card-header px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Sample Equipment Card</p>
                                        <p class="text-xs text-slate-500">Emergency Room &bull; Triage</p>
                                    </div>
                                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-primary text-white">
                                        Sample Action
                                    </span>
                                </div>
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-700">
                                        <tr>
                                            <th class="py-2.5 px-3.5">Asset No.</th>
                                            <th class="py-2.5 px-3.5">Description</th>
                                            <th class="py-2.5 px-3.5 text-right">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr class="h-[48px]">
                                            <td class="py-2.5 px-3.5 font-mono font-semibold text-slate-900">BBHI-ER-0001</td>
                                            <td class="py-2.5 px-3.5 text-slate-800">Patient Monitor</td>
                                            <td class="py-2.5 px-3.5 text-right">
                                                <x-badge status="In Use" />
                                            </td>
                                        </tr>
                                        <tr class="h-[48px]">
                                            <td class="py-2.5 px-3.5 font-mono font-semibold text-slate-900">BBHI-ER-0002</td>
                                            <td class="py-2.5 px-3.5 text-slate-800">Defibrillator</td>
                                            <td class="py-2.5 px-3.5 text-right">
                                                <x-badge status="Under Repair" />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </x-card>
        </div>

        <!-- TAB: CATEGORIES (ADMIN ONLY) -->
        @if (auth()->user()?->isAdmin())
            <div id="panel-categories" role="tabpanel" class="tab-panel {{ $currentTab === 'categories' ? '' : 'hidden' }} space-y-6">
                <!-- Categories Table -->
                <x-card title="Asset Categories & Depreciation Presets" subtitle="Manage equipment categories, default useful life spans, and estimated disposal percentages. Categories in use cannot be deleted.">
                    <x-table>
                        <x-slot:header>
                            <tr>
                                <th class="py-3 px-4">Category Name</th>
                                <th class="py-3 px-4">Default Useful Life</th>
                                <th class="py-3 px-4">Disposal %</th>
                                <th class="py-3 px-4 text-center">Assigned Assets</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </x-slot:header>

                        @forelse ($categories as $cat)
                            <tr>
                                <td class="py-3 px-4 font-semibold text-slate-900">
                                    {{ $cat->name }}
                                </td>
                                <td class="py-3 px-4 text-slate-700">
                                    {{ $cat->default_useful_life_years ? $cat->default_useful_life_years . ' years' : 'Not configured' }}
                                </td>
                                <td class="py-3 px-4 text-slate-700">
                                    @if ($cat->default_salvage_percent === null)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                            Disposal % not set
                                        </span>
                                    @elseif ((float) $cat->default_salvage_percent === 0.0)
                                        <div class="inline-flex items-center gap-1.5">
                                            <span class="font-mono text-xs tabular-nums text-slate-700">0.00%</span>
                                            @if ($cat->disposal_reviewed)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-300" title="0% disposal confirmed and reviewed.">
                                                    0% confirmed
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800 border border-amber-300" title="Category configured with 0% disposal. Review whether a disposal value applies.">
                                                    Needs review (0%)
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="font-mono text-xs tabular-nums text-slate-700">{{ number_format((float) $cat->default_salvage_percent, 2) }}%</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $cat->assets_count > 0 ? 'bg-sky-50 text-primary border border-sky-200' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $cat->assets_count }} {{ Str::plural('asset', $cat->assets_count) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        @if ((float) $cat->default_salvage_percent === 0.0 && ! $cat->disposal_reviewed)
                                            <form method="POST" action="{{ route('settings.categories.mark-reviewed', $cat) }}" class="inline">
                                                @csrf
                                                <button type="submit"
                                                        class="px-2.5 py-1 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded border border-emerald-200 transition-colors cursor-pointer"
                                                        title="Confirm 0% disposal percentage for this category">
                                                    Mark as reviewed
                                                </button>
                                            </form>
                                        @endif
                                        @if (strtolower($cat->name) === 'uncategorized')
                                            <span class="text-xs text-slate-400 italic" title="System placeholder cannot be edited or deleted">Protected</span>
                                        @else
                                            <button type="button"
                                                    onclick="openEditCategoryModal({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ $cat->default_useful_life_years ?? '' }}', '{{ $cat->default_salvage_percent !== null ? $cat->default_salvage_percent : '' }}', {{ (int) $cat->assets_count }})"
                                                    class="px-2.5 py-1 text-xs font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded border border-slate-300 transition-colors cursor-pointer">
                                                Edit
                                            </button>
                                            @if ($cat->assets_count === 0)
                                                <form method="POST" action="{{ route('settings.categories.destroy', $cat) }}" onsubmit="return confirm('Are you sure you want to delete category &quot;{{ $cat->name }}&quot;?');" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="px-2.5 py-1 text-xs font-medium text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 rounded border border-red-200 transition-colors cursor-pointer">
                                                        Delete
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-xs text-slate-400 italic" title="Category is in use and cannot be deleted">Locked</span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-500">
                                    No categories defined yet. Create your first category below.
                                </td>
                            </tr>
                        @endforelse

                        <!-- Uncategorized System Placeholder Row -->
                        <tr class="bg-slate-50/60 border-t border-slate-200">
                            <td class="py-3 px-4 font-semibold text-slate-900">
                                <div class="inline-flex items-center gap-2">
                                    <span>Uncategorized</span>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        Placeholder
                                    </span>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-slate-400 italic">
                                None
                            </td>
                            <td class="py-3 px-4 text-slate-400 italic">
                                None
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ ($uncategorizedAssetsCount ?? 0) > 0 ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $uncategorizedAssetsCount ?? 0 }} {{ Str::plural('asset', $uncategorizedAssetsCount ?? 0) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    @if (($uncategorizedAssetsCount ?? 0) > 0)
                                        <a href="{{ route('assets.index', ['category' => 'uncategorized']) }}"
                                           class="px-2.5 py-1 text-xs font-semibold text-primary bg-sky-50 hover:bg-sky-100 rounded border border-sky-200 transition-colors cursor-pointer">
                                            Set category
                                        </a>
                                    @endif
                                    <span class="text-xs text-slate-400 italic" title="System placeholder cannot be edited or deleted">Protected</span>
                                </div>
                            </td>
                        </tr>
                    </x-table>
                </x-card>

                <!-- Add Category Form -->
                <x-card title="Add New Category" subtitle="Add a new property classification. Newly created assets selecting this category will inherit these defaults.">
                    <form method="POST" action="{{ route('settings.categories.store') }}" class="max-w-xl space-y-4">
                        @csrf
                        <div>
                            <label for="cat_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                Category Name <span class="text-crimson">*</span>
                            </label>
                            <input type="text"
                                   id="cat_name"
                                   name="name"
                                   required
                                   value="{{ old('name') }}"
                                   placeholder="e.g. Diagnostic Equipment, Laboratory, Office Furniture"
                                   class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="cat_life" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                    Default Useful Life (years) <span class="text-crimson">*</span>
                                </label>
                                <input type="number"
                                       id="cat_life"
                                       name="default_useful_life_years"
                                       min="1"
                                       max="100"
                                       required
                                       value="{{ old('default_useful_life_years') }}"
                                       placeholder="e.g. 10"
                                       class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                <p class="text-[11px] text-slate-500 mt-1">Useful life for straight-line calculations</p>
                            </div>
                            <div>
                                <label for="cat_salvage" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                    Disposal Value (%) <span class="text-xs font-normal text-slate-400 ml-1">(Optional)</span>
                                </label>
                                <input type="number"
                                       id="cat_salvage"
                                       name="default_salvage_percent"
                                       min="0"
                                       max="100"
                                       step="0.01"
                                       value="{{ old('default_salvage_percent') }}"
                                       placeholder="Optional (e.g. 10.00)"
                                       class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                <p class="text-[11px] text-slate-500 mt-1">Default disposal % of cost. Leave empty if not set.</p>
                            </div>
                        </div>

                        <div class="pt-2">
                            <x-button variant="primary" type="submit">
                                Add Category
                            </x-button>
                        </div>
                    </form>
                </x-card>
            </div>

            <!-- Edit Category Modal Dialog -->
            <div id="edit-category-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="modal-category-title">
                <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 id="modal-category-title" class="text-base font-bold text-slate-900">Edit Category</h3>
                        <button type="button" onclick="closeEditCategoryModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-md">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form id="edit-category-form" method="POST" action="" onsubmit="return handleEditCategorySubmit(this);" class="p-6 space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label for="edit_cat_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Category Name <span class="text-crimson">*</span></label>
                            <input type="text" id="edit_cat_name" name="name" required class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="edit_cat_life" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Useful Life (years) <span class="text-crimson">*</span></label>
                                <input type="number" id="edit_cat_life" name="default_useful_life_years" min="1" max="100" required class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="edit_cat_salvage" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Disposal Value (%) <span class="text-xs font-normal text-slate-400 ml-1">(Optional)</span></label>
                                <input type="number" id="edit_cat_salvage" name="default_salvage_percent" min="0" max="100" step="0.01" placeholder="Optional (e.g. 10.00)" class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                            <button type="button" onclick="closeEditCategoryModal()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">Cancel</button>
                            <x-button variant="primary" type="submit">Save Changes</x-button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- TAB: DEPARTMENTS & NUMBERING (ADMIN ONLY) -->
        @if (auth()->user()?->isAdmin())
            <div id="panel-departments" role="tabpanel" class="tab-panel {{ $currentTab === 'departments' ? '' : 'hidden' }} space-y-6">
                <!-- Asset Numbering Settings -->
                <x-card title="Asset Numbering Rules" subtitle="Configure automated asset number generation prefix, sequence length, scope, and reassignment behavior.">
                    <form method="POST" action="{{ route('settings.numbering.update') }}" class="space-y-4 max-w-2xl">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label for="setting_asset_prefix" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Prefix <span class="text-crimson">*</span></label>
                                <input type="text" id="setting_asset_prefix" name="asset_no_prefix" value="{{ $assetNoPrefix }}" required maxlength="20" class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary font-mono uppercase">
                                <p class="text-xs text-slate-500 mt-1">Hospital prefix (e.g., BBHI)</p>
                            </div>
                            <div>
                                <label for="setting_asset_digits" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Sequence Digits <span class="text-crimson">*</span></label>
                                <input type="number" id="setting_asset_digits" name="asset_number_digits" value="{{ $assetNumberDigits ?? 4 }}" min="3" max="8" required class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary font-mono">
                                <p class="text-xs text-slate-500 mt-1">Zero-padding (e.g., 4 = 0001)</p>
                            </div>
                            <div>
                                <label for="setting_asset_scope" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Counter Scope <span class="text-crimson">*</span></label>
                                <select id="setting_asset_scope" name="asset_number_scope" class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="department" {{ ($assetNumberScope ?? 'department') === 'department' ? 'selected' : '' }}>Per Department</option>
                                    <option value="global" {{ ($assetNumberScope ?? 'department') === 'global' ? 'selected' : '' }}>Global Sequence</option>
                                </select>
                                <p class="text-xs text-slate-500 mt-1">Separate or shared counter</p>
                            </div>
                        </div>

                        <div class="pt-2 space-y-2.5">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="include_section_in_asset_no" value="1" {{ ($includeSectionInAssetNo ?? true) ? 'checked' : '' }} class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary">
                                <span class="text-sm text-slate-700 font-medium">Include section code in Asset No. (e.g., BBHICU-ICU2-0001)</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="reassign_number_on_dept_change" value="1" {{ ($reassignNumberOnDeptChange ?? true) ? 'checked' : '' }} class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary">
                                <span class="text-sm text-slate-700 font-medium">Assign a new number when the department changes (auto-generates next number and prompts tag reprint)</span>
                            </label>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex justify-end">
                            <x-button variant="primary" type="submit">Save Numbering Settings</x-button>
                        </div>
                    </form>
                </x-card>

                <!-- Departments Table -->
                <x-card title="Hospital Departments" subtitle="Manage department codes and sequence counters. Department codes are used in automated asset identification.">
                    <div class="mb-4 flex justify-between items-center">
                        <div class="text-sm text-slate-600">
                            Total Registered: <span class="font-semibold text-slate-900">{{ count($departments ?? []) }}</span>
                        </div>
                        <button type="button"
                                onclick="openAddDepartmentModal()"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-primary hover:bg-slate-800 rounded-lg transition-colors cursor-pointer shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Add Department
                        </button>
                    </div>

                    <x-table>
                        <x-slot:header>
                            <tr>
                                <th class="py-3 px-4">Department Name</th>
                                <th class="py-3 px-4 text-center">Code</th>
                                <th class="py-3 px-4 text-center">Code Status</th>
                                <th class="py-3 px-4 text-center">Next Number</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-center">Assigned Assets</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </x-slot:header>

                        @forelse ($departments ?? [] as $dept)
                            <tr>
                                <td class="py-3 px-4 font-semibold text-slate-900">
                                    {{ $dept->name }}
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-bold text-slate-800">
                                    <span class="inline-block px-2 py-0.5 bg-slate-100 border border-slate-300 rounded text-xs">
                                        {{ $dept->code }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if ($dept->code_confirmed)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Confirmed
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-300 animate-pulse" title="Suggested code from backfill. Please review and confirm.">
                                            Needs Review
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center font-mono text-slate-700">
                                    {{ $dept->next_number }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $dept->active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $dept->active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="inline-flex flex-wrap items-center justify-center gap-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $dept->assets_count > 0 ? 'bg-sky-50 text-primary border border-sky-200' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $dept->assets_count }} {{ Str::plural('asset', $dept->assets_count) }}
                                        </span>
                                        @if (($dept->trashed_assets_count ?? 0) > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200" title="Archived assets still reference this department">
                                                +{{ $dept->trashed_assets_count }} archived
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        @if (! $dept->code_confirmed)
                                            <form method="POST" action="{{ route('settings.departments.confirm-code', $dept) }}" class="inline">
                                                @csrf
                                                <button type="submit"
                                                        class="px-2.5 py-1 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded border border-emerald-300 transition-colors cursor-pointer">
                                                    Confirm Code
                                                </button>
                                            </form>
                                        @endif
                                        <button type="button"
                                                onclick="openEditDepartmentModal({{ $dept->id }}, '{{ addslashes($dept->name) }}', '{{ $dept->code }}', {{ $dept->next_number }}, {{ $dept->active ? 'true' : 'false' }}, {{ $dept->code_confirmed ? 'true' : 'false' }})"
                                                class="px-2.5 py-1 text-xs font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded border border-slate-300 transition-colors cursor-pointer">
                                            Edit
                                        </button>
                                        @if (($dept->assets_count ?? 0) === 0 && ($dept->trashed_assets_count ?? 0) === 0)
                                            <form method="POST" action="{{ route('settings.departments.destroy', $dept) }}" onsubmit="return confirm('Are you sure you want to remove department &quot;{{ addslashes($dept->name) }}&quot;? Any sections under it with no assigned assets will also be removed.');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="px-2.5 py-1 text-xs font-medium text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 rounded border border-red-200 transition-colors cursor-pointer">
                                                    Remove
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400 italic" title="Department has assigned assets and cannot be removed">Locked</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-slate-500">
                                    No departments registered yet.
                                </td>
                            </tr>
                        @endforelse
                    </x-table>
                </x-card>

                <!-- Sections Table -->
                <x-card title="Hospital Sections" subtitle="Manage short section codes inside each department. Section codes keep asset numbers standardized (e.g., BBHSURG-TB1-0001).">
                    <div class="mb-4 flex flex-wrap justify-between items-center gap-3">
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="text-sm text-slate-600">
                                Total Registered: <span class="font-semibold text-slate-900">{{ count($sections ?? []) }}</span>
                            </div>
                            <select id="sections-dept-filter"
                                    onchange="filterSectionsByDepartment(this.value)"
                                    class="min-h-[36px] px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                <option value="">All Departments</option>
                                @foreach ($departments ?? [] as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button"
                                onclick="openAddSectionModal()"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-primary hover:bg-slate-800 rounded-lg transition-colors cursor-pointer shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Add Section
                        </button>
                    </div>

                    <x-table>
                        <x-slot:header>
                            <tr>
                                <th class="py-3 px-4">Department</th>
                                <th class="py-3 px-4">Section Name</th>
                                <th class="py-3 px-4 text-center">Code</th>
                                <th class="py-3 px-4 text-center">Code Status</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-center">Assigned Assets</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </x-slot:header>

                        @forelse ($sections ?? [] as $sec)
                            <tr class="section-row" data-department-id="{{ $sec->department_id }}">
                                <td class="py-3 px-4 text-slate-700">
                                    {{ $sec->department?->name ?? '-' }}
                                    @if ($sec->department?->code)
                                        <span class="font-mono text-xs text-slate-500">({{ $sec->department->code }})</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-900">
                                    {{ $sec->name }}
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-bold text-slate-800">
                                    <span class="inline-block px-2 py-0.5 bg-slate-100 border border-slate-300 rounded text-xs">
                                        {{ $sec->code }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if ($sec->code_confirmed)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Confirmed
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-300 animate-pulse" title="Suggested code from backfill. Please review and confirm.">
                                            Needs Review
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $sec->active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $sec->active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="inline-flex flex-wrap items-center justify-center gap-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $sec->assets_count > 0 ? 'bg-sky-50 text-primary border border-sky-200' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $sec->assets_count }} {{ Str::plural('asset', $sec->assets_count) }}
                                        </span>
                                        @if (($sec->trashed_assets_count ?? 0) > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200" title="Archived assets still reference this section">
                                                +{{ $sec->trashed_assets_count }} archived
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        @if (! $sec->code_confirmed)
                                            <form method="POST" action="{{ route('settings.sections.confirm-code', $sec) }}" class="inline">
                                                @csrf
                                                <button type="submit"
                                                        class="px-2.5 py-1 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded border border-emerald-300 transition-colors cursor-pointer">
                                                    Confirm Code
                                                </button>
                                            </form>
                                        @endif
                                        <button type="button"
                                                onclick="openEditSectionModal({{ $sec->id }}, {{ $sec->department_id }}, '{{ addslashes($sec->name) }}', '{{ $sec->code }}', {{ $sec->active ? 'true' : 'false' }}, {{ $sec->code_confirmed ? 'true' : 'false' }})"
                                                class="px-2.5 py-1 text-xs font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded border border-slate-300 transition-colors cursor-pointer">
                                            Edit
                                        </button>
                                        @if (($sec->assets_count ?? 0) === 0 && ($sec->trashed_assets_count ?? 0) === 0)
                                            <form method="POST" action="{{ route('settings.sections.destroy', $sec) }}" onsubmit="return confirm('Are you sure you want to remove section &quot;{{ addslashes($sec->name) }}&quot;?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="px-2.5 py-1 text-xs font-medium text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 rounded border border-red-200 transition-colors cursor-pointer">
                                                    Remove
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400 italic" title="Section has assigned assets and cannot be removed">Locked</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-slate-500">
                                    No sections registered yet. Click Add Section to create one.
                                </td>
                            </tr>
                        @endforelse
                    </x-table>
                </x-card>

                <!-- Add Section Modal -->
                <div id="add-section-modal" class="fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4 hidden">
                    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h3 class="text-base font-semibold text-slate-900">Add Section</h3>
                            <button type="button" onclick="closeAddSectionModal()" class="text-slate-400 hover:text-slate-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <form method="POST" action="{{ route('settings.sections.store') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label for="add_sec_dept" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Department <span class="text-crimson">*</span></label>
                                <select id="add_sec_dept" name="department_id" required class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="">-- Select Department --</option>
                                    @foreach ($departments ?? [] as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="add_sec_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Section Name <span class="text-crimson">*</span></label>
                                    <input type="text" id="add_sec_name" name="name" required placeholder="e.g. Trauma Bay 1" class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                </div>
                                <div>
                                    <label for="add_sec_code" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Code (2-6 chars) <span class="text-crimson">*</span></label>
                                    <input type="text" id="add_sec_code" name="code" required minlength="2" maxlength="6" placeholder="e.g. TB1" class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary font-mono uppercase">
                                </div>
                            </div>
                            <div>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="active" value="1" checked class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary">
                                    <span class="text-sm text-slate-700">Active Section</span>
                                </label>
                            </div>
                            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                                <button type="button" onclick="closeAddSectionModal()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">Cancel</button>
                                <x-button variant="primary" type="submit">Create Section</x-button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Edit Section Modal -->
                <div id="edit-section-modal" class="fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4 hidden">
                    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h3 class="text-base font-semibold text-slate-900">Edit Section</h3>
                            <button type="button" onclick="closeEditSectionModal()" class="text-slate-400 hover:text-slate-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <form id="edit-section-form" method="POST" action="" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <div>
                                <label for="edit_sec_dept" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Department <span class="text-crimson">*</span></label>
                                <select id="edit_sec_dept" name="department_id" required class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                    @foreach ($departments ?? [] as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="edit_sec_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Section Name <span class="text-crimson">*</span></label>
                                    <input type="text" id="edit_sec_name" name="name" required class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                </div>
                                <div>
                                    <label for="edit_sec_code" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Code (2-6 chars) <span class="text-crimson">*</span></label>
                                    <input type="text" id="edit_sec_code" name="code" required minlength="2" maxlength="6" class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary font-mono uppercase">
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div>
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" id="edit_sec_active" name="active" value="1" class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary">
                                        <span class="text-sm text-slate-700">Active Section</span>
                                    </label>
                                </div>
                                <div>
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" id="edit_sec_confirmed" name="code_confirmed" value="1" class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary">
                                        <span class="text-sm text-slate-700">Section Code Confirmed</span>
                                    </label>
                                </div>
                            </div>
                            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                                <button type="button" onclick="closeEditSectionModal()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">Cancel</button>
                                <x-button variant="primary" type="submit">Save Changes</x-button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Add Department Modal -->
                <div id="add-department-modal" class="fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4 hidden">
                    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h3 class="text-base font-semibold text-slate-900">Add Department</h3>
                            <button type="button" onclick="closeAddDepartmentModal()" class="text-slate-400 hover:text-slate-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <form method="POST" action="{{ route('settings.departments.store') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label for="add_dept_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Department Name <span class="text-crimson">*</span></label>
                                <input type="text" id="add_dept_name" name="name" required placeholder="e.g. Intensive Care Unit" class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="add_dept_code" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Code (2-6 chars) <span class="text-crimson">*</span></label>
                                    <input type="text" id="add_dept_code" name="code" required minlength="2" maxlength="6" placeholder="e.g. ICU" class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary font-mono uppercase">
                                </div>
                                <div>
                                    <label for="add_dept_next" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Initial Counter</label>
                                    <input type="number" id="add_dept_next" name="next_number" value="1" min="1" class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary font-mono">
                                </div>
                            </div>
                            <div>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="active" value="1" checked class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary">
                                    <span class="text-sm text-slate-700">Active Department</span>
                                </label>
                            </div>
                            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                                <button type="button" onclick="closeAddDepartmentModal()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">Cancel</button>
                                <x-button variant="primary" type="submit">Create Department</x-button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Edit Department Modal -->
                <div id="edit-department-modal" class="fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4 hidden">
                    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h3 class="text-base font-semibold text-slate-900">Edit Department</h3>
                            <button type="button" onclick="closeEditDepartmentModal()" class="text-slate-400 hover:text-slate-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <form id="edit-department-form" method="POST" action="" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <div>
                                <label for="edit_dept_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Department Name <span class="text-crimson">*</span></label>
                                <input type="text" id="edit_dept_name" name="name" required class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="edit_dept_code" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Code (2-6 chars) <span class="text-crimson">*</span></label>
                                    <input type="text" id="edit_dept_code" name="code" required minlength="2" maxlength="6" class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary font-mono uppercase">
                                </div>
                                <div>
                                    <label for="edit_dept_next" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Sequence Counter <span class="text-crimson">*</span></label>
                                    <input type="number" id="edit_dept_next" name="next_number" required min="1" class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary font-mono">
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div>
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" id="edit_dept_active" name="active" value="1" class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary">
                                        <span class="text-sm text-slate-700">Active Department</span>
                                    </label>
                                </div>
                                <div>
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" id="edit_dept_confirmed" name="code_confirmed" value="1" class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary">
                                        <span class="text-sm text-slate-700">Department Code Confirmed</span>
                                    </label>
                                </div>
                            </div>
                            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                                <button type="button" onclick="closeEditDepartmentModal()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">Cancel</button>
                                <x-button variant="primary" type="submit">Save Changes</x-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- TAB: VENDORS (ADMIN ONLY) -->
        @if (auth()->user()?->isAdmin())
            <div id="panel-vendors" role="tabpanel" class="tab-panel {{ $currentTab === 'vendors' ? '' : 'hidden' }} space-y-6">
                <x-card title="Vendor & Supplier Directory" subtitle="Manage suppliers, hardware stores, and donation sources linked to hospital assets.">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <form method="GET" action="{{ route('settings.index') }}" class="flex items-center gap-2 flex-1 max-w-md">
                            <input type="hidden" name="tab" value="vendors">
                            <input type="search"
                                   name="vendor_search"
                                   value="{{ $vendorSearch ?? '' }}"
                                   placeholder="Search vendor name, contact person, phone, or address..."
                                   class="w-full min-h-[40px] px-3 py-1.5 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            <button type="submit" class="min-h-[40px] px-4 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 cursor-pointer">
                                Search
                            </button>
                            @if (! empty($vendorSearch))
                                <a href="{{ route('settings.index', ['tab' => 'vendors']) }}" class="text-xs font-medium text-slate-500 hover:text-slate-800 whitespace-nowrap">
                                    Clear
                                </a>
                            @endif
                        </form>

                        <button type="button"
                                onclick="openAddVendorModal()"
                                class="min-h-[40px] inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-primary hover:bg-primary-hover rounded-lg shadow-2xs transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Add Vendor</span>
                        </button>
                    </div>

                    <x-table>
                        <x-slot:header>
                            <tr>
                                <th class="py-3 px-4">Vendor Name</th>
                                <th class="py-3 px-4">Contact Person</th>
                                <th class="py-3 px-4">Phone</th>
                                <th class="py-3 px-4">Address</th>
                                <th class="py-3 px-4">Assets</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </x-slot:header>

                        @forelse ($vendors ?? collect() as $v)
                            @php
                                $vendorAssetTotal = (int) $v->assets_count + (int) $v->trashed_assets_count;
                            @endphp
                            <tr class="{{ $v->active ? '' : 'bg-slate-50/70 opacity-80' }}">
                                <td class="py-3 px-4">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="font-semibold text-slate-900">{{ $v->name }}</span>
                                        @if ($v->is_donation)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                Donation
                                            </span>
                                        @endif
                                    </div>
                                    @if ($v->notes)
                                        <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-1">{{ $v->notes }}</p>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-xs text-slate-700">{{ $v->contact_person ?: '-' }}</td>
                                <td class="py-3 px-4 text-xs text-slate-700 font-mono">{{ $v->phone ?: '-' }}</td>
                                <td class="py-3 px-4 text-xs text-slate-600 max-w-xs truncate" title="{{ $v->address }}">{{ $v->address ?: '-' }}</td>
                                <td class="py-3 px-4 text-xs font-mono font-semibold text-slate-700 tabular-nums">{{ $vendorAssetTotal }}</td>
                                <td class="py-3 px-4">
                                    @if ($v->active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button"
                                                onclick="openEditVendorModal({{ $v->id }}, {{ \Illuminate\Support\Js::from($v->name) }}, {{ \Illuminate\Support\Js::from($v->address ?? '') }}, {{ \Illuminate\Support\Js::from($v->contact_person ?? '') }}, {{ \Illuminate\Support\Js::from($v->phone ?? '') }}, {{ $v->is_donation ? 'true' : 'false' }}, {{ \Illuminate\Support\Js::from($v->notes ?? '') }})"
                                                class="px-2.5 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 cursor-pointer">
                                            Edit
                                        </button>
                                        @if ($v->active)
                                            <form method="POST" action="{{ route('settings.vendors.deactivate', $v) }}" class="inline">
                                                @csrf
                                                <button type="submit"
                                                        class="px-2.5 py-1.5 text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100 cursor-pointer">
                                                    Deactivate
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('settings.vendors.reactivate', $v) }}" class="inline">
                                                @csrf
                                                <button type="submit"
                                                        class="px-2.5 py-1.5 text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 cursor-pointer">
                                                    Reactivate
                                                </button>
                                            </form>
                                        @endif
                                        @if ($vendorAssetTotal === 0)
                                            <form method="POST" action="{{ route('settings.vendors.destroy', $v) }}" class="inline" onsubmit="return confirm('Delete this unused vendor?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="px-2.5 py-1.5 text-xs font-semibold text-red-700 bg-white border border-red-200 rounded-lg hover:bg-red-50 cursor-pointer">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 px-4 text-center text-xs text-slate-500">
                                    No vendors found matching your filter.
                                </td>
                            </tr>
                        @endforelse
                    </x-table>
                </x-card>

                <!-- Add Vendor Modal -->
                <div id="add-vendor-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" onclick="closeAddVendorModal()">
                    <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden" onclick="event.stopPropagation()">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="text-base font-bold text-slate-900">Add Vendor</h3>
                            <button type="button" onclick="closeAddVendorModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <form method="POST" action="{{ route('settings.vendors.store') }}" class="p-6 space-y-4">
                            @csrf
                            <div>
                                <label for="add_vendor_name" class="block text-xs font-semibold text-slate-700 mb-1">Vendor Name <span class="text-brand-red">*</span></label>
                                <input type="text" id="add_vendor_name" name="name" required class="w-full min-h-[42px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="add_vendor_contact" class="block text-xs font-semibold text-slate-700 mb-1">Contact Person</label>
                                    <input type="text" id="add_vendor_contact" name="contact_person" class="w-full min-h-[42px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                </div>
                                <div>
                                    <label for="add_vendor_phone" class="block text-xs font-semibold text-slate-700 mb-1">Phone</label>
                                    <input type="text" id="add_vendor_phone" name="phone" class="w-full min-h-[42px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                </div>
                            </div>
                            <div>
                                <label for="add_vendor_address" class="block text-xs font-semibold text-slate-700 mb-1">Address</label>
                                <input type="text" id="add_vendor_address" name="address" class="w-full min-h-[42px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="is_donation" value="1" class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary">
                                    <span class="text-sm text-slate-700 font-medium">Donation source (marks assets from this entry with a Donation badge)</span>
                                </label>
                            </div>
                            <div>
                                <label for="add_vendor_notes" class="block text-xs font-semibold text-slate-700 mb-1">Notes</label>
                                <textarea id="add_vendor_notes" name="notes" rows="2" class="w-full px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"></textarea>
                            </div>
                            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                                <button type="button" onclick="closeAddVendorModal()" class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 cursor-pointer">Cancel</button>
                                <x-button variant="primary" type="submit">Add Vendor</x-button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Edit Vendor Modal -->
                <div id="edit-vendor-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" onclick="closeEditVendorModal()">
                    <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden" onclick="event.stopPropagation()">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="text-base font-bold text-slate-900">Edit Vendor</h3>
                            <button type="button" onclick="closeEditVendorModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <form id="edit-vendor-form" method="POST" action="" class="p-6 space-y-4">
                            @csrf
                            @method('PUT')
                            <div>
                                <label for="edit_vendor_name" class="block text-xs font-semibold text-slate-700 mb-1">Vendor Name <span class="text-brand-red">*</span></label>
                                <input type="text" id="edit_vendor_name" name="name" required class="w-full min-h-[42px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="edit_vendor_contact" class="block text-xs font-semibold text-slate-700 mb-1">Contact Person</label>
                                    <input type="text" id="edit_vendor_contact" name="contact_person" class="w-full min-h-[42px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                </div>
                                <div>
                                    <label for="edit_vendor_phone" class="block text-xs font-semibold text-slate-700 mb-1">Phone</label>
                                    <input type="text" id="edit_vendor_phone" name="phone" class="w-full min-h-[42px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                </div>
                            </div>
                            <div>
                                <label for="edit_vendor_address" class="block text-xs font-semibold text-slate-700 mb-1">Address</label>
                                <input type="text" id="edit_vendor_address" name="address" class="w-full min-h-[42px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="edit_vendor_donation" name="is_donation" value="1" class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary">
                                    <span class="text-sm text-slate-700 font-medium">Donation source</span>
                                </label>
                            </div>
                            <div>
                                <label for="edit_vendor_notes" class="block text-xs font-semibold text-slate-700 mb-1">Notes</label>
                                <textarea id="edit_vendor_notes" name="notes" rows="2" class="w-full px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"></textarea>
                            </div>
                            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                                <button type="button" onclick="closeEditVendorModal()" class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 cursor-pointer">Cancel</button>
                                <x-button variant="primary" type="submit">Save Changes</x-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- TAB: ASSET TEMPLATES (ADMIN ONLY) -->
        @if (auth()->user()?->isAdmin())
            <div id="panel-asset-templates" role="tabpanel" class="tab-panel {{ $currentTab === 'asset-templates' ? '' : 'hidden' }} space-y-6">
                <x-card title="Asset Masterlist (Equipment Templates)" subtitle="Standard equipment definitions to speed up asset entry without repeating brand, model, or category setup.">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <form method="GET" action="{{ route('settings.index') }}" class="flex items-center gap-2 flex-1 max-w-md">
                            <input type="hidden" name="tab" value="asset-templates">
                            <input type="search"
                                   name="template_search"
                                   value="{{ $templateSearch ?? '' }}"
                                   placeholder="Search equipment name, brand, model, or category..."
                                   class="w-full min-h-[40px] px-3 py-1.5 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            <button type="submit" class="min-h-[40px] px-4 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 cursor-pointer">
                                Search
                            </button>
                            @if (! empty($templateSearch))
                                <a href="{{ route('settings.index', ['tab' => 'asset-templates']) }}" class="text-xs font-medium text-slate-500 hover:text-slate-800 whitespace-nowrap">
                                    Clear
                                </a>
                            @endif
                        </form>

                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button"
                                    onclick="toggleTemplateImportPanel()"
                                    class="min-h-[40px] inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg transition-colors cursor-pointer">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                                </svg>
                                <span>Import templates from Excel</span>
                            </button>

                            <button type="button"
                                    onclick="openAddTemplateModal()"
                                    class="min-h-[40px] inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-primary hover:bg-primary-hover rounded-lg shadow-2xs transition-colors cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span>Add Template</span>
                            </button>
                        </div>
                    </div>

                    <div id="template-import-panel" class="hidden mb-5 p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Import templates from Excel</p>
                                <p class="text-xs text-slate-600 mt-0.5">Columns: name (required), brand, model, category, default_remarks. Unknown categories are flagged and left blank.</p>
                            </div>
                            <a href="{{ route('settings.asset-templates.import-sample') }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-primary bg-white border border-slate-300 rounded-lg hover:bg-slate-100">
                                Download sample file (.xlsx)
                            </a>
                        </div>
                        <form method="POST" action="{{ route('settings.asset-templates.import.preview') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
                            @csrf
                            <input type="file"
                                   name="file"
                                   accept=".xlsx,.xls,.csv"
                                   required
                                   class="text-xs text-slate-700 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border file:border-slate-300 file:text-xs file:font-semibold file:bg-white file:text-slate-700 hover:file:bg-slate-50">
                            <x-button variant="primary" type="submit">Preview Import</x-button>
                        </form>
                    </div>

                    <x-table>
                        <x-slot:header>
                            <tr>
                                <th class="py-3 px-4">Equipment Name</th>
                                <th class="py-3 px-4">Brand &amp; Model</th>
                                <th class="py-3 px-4">Default Category</th>
                                <th class="py-3 px-4">Default Remarks</th>
                                <th class="py-3 px-4">Assets</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </x-slot:header>

                        @forelse ($assetTemplates ?? collect() as $tpl)
                            @php
                                $tplAssetTotal = (int) $tpl->assets_count + (int) $tpl->trashed_assets_count;
                            @endphp
                            <tr id="template-display-row-{{ $tpl->id }}" class="{{ $tpl->active ? '' : 'bg-slate-50/70 opacity-80' }}">
                                <td class="py-3 px-4 font-semibold text-slate-900">{{ $tpl->name }}</td>
                                <td class="py-3 px-4 text-xs text-slate-700">
                                    @if ($tpl->brand || $tpl->model)
                                        <span class="font-medium text-slate-800">{{ $tpl->brand ?: '-' }}</span>
                                        @if ($tpl->model)
                                            <span class="text-slate-500"> / {{ $tpl->model }}</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-xs text-slate-700">
                                    @if ($tpl->category)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 text-slate-800 border border-slate-200 font-medium">
                                            {{ $tpl->category->name }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-xs text-slate-600 max-w-xs truncate" title="{{ $tpl->default_remarks ?? '' }}">
                                    {{ $tpl->default_remarks ?: '-' }}
                                </td>
                                <td class="py-3 px-4 text-xs font-mono font-semibold text-slate-700 tabular-nums">{{ $tplAssetTotal }}</td>
                                <td class="py-3 px-4">
                                    @if ($tpl->active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">Inactive</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button"
                                                onclick="toggleInlineTemplateEdit({{ $tpl->id }})"
                                                class="px-2.5 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 cursor-pointer">
                                            Edit
                                        </button>
                                        @if ($tpl->active)
                                            <form method="POST"
                                                  action="{{ route('settings.asset-templates.deactivate', $tpl) }}"
                                                  class="inline"
                                                  @if ($tplAssetTotal > 0)
                                                      onsubmit="return confirm('{{ $tplAssetTotal }} asset(s) use this template. Deactivating hides it from new selections while existing assets remain unchanged. Continue?');"
                                                  @endif>
                                                @csrf
                                                <button type="submit"
                                                        class="px-2.5 py-1.5 text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100 cursor-pointer">
                                                    Deactivate
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('settings.asset-templates.reactivate', $tpl) }}" class="inline">
                                                @csrf
                                                <button type="submit"
                                                        class="px-2.5 py-1.5 text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 cursor-pointer">
                                                    Reactivate
                                                </button>
                                            </form>
                                        @endif
                                        @if ($tplAssetTotal === 0)
                                            <form method="POST" action="{{ route('settings.asset-templates.destroy', $tpl) }}" class="inline" onsubmit="return confirm('Delete this unused asset template?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="px-2.5 py-1.5 text-xs font-semibold text-red-700 bg-white border border-red-200 rounded-lg hover:bg-red-50 cursor-pointer">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            <tr id="template-edit-row-{{ $tpl->id }}" class="hidden bg-sky-50/40">
                                <td colspan="7" class="p-4">
                                    <form method="POST" action="{{ route('settings.asset-templates.update', $tpl) }}" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                                        @csrf
                                        @method('PUT')
                                        <div class="md:col-span-2">
                                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Equipment Name <span class="text-brand-red">*</span></label>
                                            <input type="text" name="name" value="{{ $tpl->name }}" required class="w-full min-h-[38px] px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Brand</label>
                                            <input type="text" name="brand" value="{{ $tpl->brand }}" class="w-full min-h-[38px] px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Model</label>
                                            <input type="text" name="model" value="{{ $tpl->model }}" class="w-full min-h-[38px] px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Default Category</label>
                                            <select name="category_id" class="w-full min-h-[38px] px-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                                <option value="">-- Blank --</option>
                                                @foreach ($categories ?? [] as $cat)
                                                    <option value="{{ $cat->id }}" @selected($tpl->category_id === $cat->id)>{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Default Remarks</label>
                                            <input type="text" name="default_remarks" value="{{ $tpl->default_remarks }}" class="w-full min-h-[38px] px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                        </div>
                                        <div class="md:col-span-6 flex justify-end gap-2 pt-1">
                                            <button type="button" onclick="toggleInlineTemplateEdit({{ $tpl->id }})" class="px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 cursor-pointer">
                                                Cancel
                                            </button>
                                            <x-button variant="primary" type="submit">Save Template</x-button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 px-4 text-center text-xs text-slate-500">
                                    No asset templates found matching your search.
                                </td>
                            </tr>
                        @endforelse
                    </x-table>
                </x-card>

                <!-- Add Asset Template Modal -->
                <div id="add-template-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" onclick="closeAddTemplateModal()">
                    <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden" onclick="event.stopPropagation()">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="text-base font-bold text-slate-900">Add Asset Template</h3>
                            <button type="button" onclick="closeAddTemplateModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <form method="POST" action="{{ route('settings.asset-templates.store') }}" class="p-6 space-y-4">
                            @csrf
                            <div>
                                <label for="add_template_name" class="block text-xs font-semibold text-slate-700 mb-1">Equipment Name <span class="text-brand-red">*</span></label>
                                <input type="text" id="add_template_name" name="name" required placeholder="e.g. Patient Monitor, Counter Table" class="w-full min-h-[42px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="add_template_brand" class="block text-xs font-semibold text-slate-700 mb-1">Brand</label>
                                    <input type="text" id="add_template_brand" name="brand" class="w-full min-h-[42px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                </div>
                                <div>
                                    <label for="add_template_model" class="block text-xs font-semibold text-slate-700 mb-1">Model</label>
                                    <input type="text" id="add_template_model" name="model" class="w-full min-h-[42px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                </div>
                            </div>
                            <div>
                                <label for="add_template_category" class="block text-xs font-semibold text-slate-700 mb-1">Default Category</label>
                                <select id="add_template_category" name="category_id" class="w-full min-h-[42px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="">-- Optional: Select Default Category --</option>
                                    @foreach ($categories ?? [] as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="add_template_remarks" class="block text-xs font-semibold text-slate-700 mb-1">Default Remarks</label>
                                <textarea id="add_template_remarks" name="default_remarks" rows="2" class="w-full px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"></textarea>
                            </div>
                            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                                <button type="button" onclick="closeAddTemplateModal()" class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 cursor-pointer">Cancel</button>
                                <x-button variant="primary" type="submit">Add Template</x-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- TAB: DEPRECIATION POLICY (ADMIN ONLY) -->
        @if (auth()->user()?->isAdmin())
            <div id="panel-depreciation" role="tabpanel" class="tab-panel {{ $currentTab === 'depreciation' ? '' : 'hidden' }} space-y-6">
                <x-card title="Depreciation Policy & Rules" subtitle="Configure calculation conventions, disposal percentages, and minimum cost thresholds for tracking asset values.">
                    <form method="POST" action="{{ route('settings.depreciation.update') }}" onsubmit="return confirm('This changes the estimated values of all assets.');" class="space-y-6">
                        @csrf

                        <!-- Notice Banner -->
                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg flex items-start gap-3">
                            <svg class="w-5 h-5 text-blue-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                            </svg>
                            <div class="text-xs text-blue-900 leading-relaxed">
                                <p class="font-semibold text-blue-950 mb-0.5">Estimated Values for Tracking Only</p>
                                <p>These settings govern the straight-line depreciation estimates shown in reports. They do not alter physical asset records and are not an official accounting record.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Start Convention -->
                            <div>
                                <label for="depreciation_start_convention" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                    Start Convention *
                                </label>
                                <select id="depreciation_start_convention"
                                        name="depreciation_start_convention"
                                        required
                                        onchange="handleConventionChange(this.value)"
                                        class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="january_1" @selected(old('depreciation_start_convention', $depreciationStartConvention ?? 'january_1') === 'january_1')>
                                        January 1 of Year Bought (Default, Full-Year)
                                    </option>
                                    <option value="july_1" @selected(old('depreciation_start_convention', $depreciationStartConvention ?? 'january_1') === 'july_1')>
                                        July 1 of Year Bought (Half-Year convention)
                                    </option>
                                    <option value="december_31" @selected(old('depreciation_start_convention', $depreciationStartConvention ?? 'january_1') === 'december_31')>
                                        December 31 of Year Bought (Year-End)
                                    </option>
                                    <option value="custom" @selected(old('depreciation_start_convention', $depreciationStartConvention ?? 'january_1') === 'custom')>
                                        Custom Month &amp; Day of Year Bought
                                    </option>
                                </select>
                                <p class="text-[11px] text-slate-500 mt-1">Used only when only the purchase year is known. Assets imported with an exact date are never shifted.</p>

                                <!-- Custom Month & Day Picker (shown when Custom is selected) -->
                                <div id="custom-convention-picker" class="mt-3 p-3 bg-slate-50 border border-slate-200 rounded-lg space-y-2 {{ old('depreciation_start_convention', $depreciationStartConvention ?? 'january_1') === 'custom' ? '' : 'hidden' }}">
                                    <span class="block text-xs font-semibold text-slate-700">Choose Custom Start Date</span>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label for="depreciation_custom_month" class="block text-[11px] font-medium text-slate-600 mb-0.5">Month</label>
                                            <select id="depreciation_custom_month"
                                                    name="depreciation_custom_month"
                                                    class="w-full min-h-[38px] px-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                                @foreach ([1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'] as $mNum => $mName)
                                                    <option value="{{ $mNum }}" @selected((int) old('depreciation_custom_month', $depreciationCustomMonth ?? 1) === $mNum)>
                                                        {{ $mName }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label for="depreciation_custom_day" class="block text-[11px] font-medium text-slate-600 mb-0.5">Day (1-31)</label>
                                            <input type="number"
                                                   id="depreciation_custom_day"
                                                   name="depreciation_custom_day"
                                                   min="1"
                                                   max="31"
                                                   value="{{ old('depreciation_custom_day', $depreciationCustomDay ?? 1) }}"
                                                   class="w-full min-h-[38px] px-2.5 py-1.5 text-xs font-sans tabular-nums bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-slate-500">Assets will start depreciating on this month and day of their purchase year.</p>
                                </div>
                            </div>

                            <!-- Default Disposal Percent -->
                            <div>
                                <label for="depreciation_default_salvage_percent" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                    Default Disposal Value Percent (%) *
                                </label>
                                <div class="relative rounded-lg">
                                    <input type="number"
                                           id="depreciation_default_salvage_percent"
                                           name="depreciation_default_salvage_percent"
                                           min="0"
                                           max="90"
                                           step="0.01"
                                           required
                                           value="{{ old('depreciation_default_salvage_percent', $depreciationDefaultSalvagePercent ?? '0') }}"
                                           placeholder="0"
                                           class="w-full min-h-[44px] px-3.5 py-2 text-sm font-sans tabular-nums bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400 text-sm font-medium">
                                        %
                                    </div>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1">Global fallback (0% to 90%) used when an asset category does not specify a disposal percent.</p>
                            </div>

                            <!-- Minimum Cost to Depreciate -->
                            <div>
                                <label for="depreciation_min_cost" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                    Minimum Cost to Depreciate (₱) *
                                </label>
                                <div class="relative rounded-lg">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm font-semibold">
                                        ₱
                                    </div>
                                    <input type="number"
                                           id="depreciation_min_cost"
                                           name="depreciation_min_cost"
                                           min="0"
                                           step="0.01"
                                           required
                                           value="{{ old('depreciation_min_cost', $depreciationMinCost ?? '0.00') }}"
                                           placeholder="0.00"
                                           class="w-full min-h-[44px] pl-8 pr-3.5 py-2 text-sm font-sans tabular-nums bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1">Default 0. Assets below this cost show "Below minimum cost" and are excluded from valuation totals.</p>
                            </div>
                        </div>

                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg">
                            <label class="inline-flex items-start gap-3 cursor-pointer">
                                <input type="checkbox"
                                       id="depreciation_use_category_rules"
                                       name="depreciation_use_category_rules"
                                       value="1"
                                       data-initial="{{ $depreciationUseCategoryRules ? '1' : '0' }}"
                                       data-uncategorized="{{ $uncategorizedAssetsCount ?? 0 }}"
                                       onchange="handleCategoryRuleSwitch(this)"
                                       {{ $depreciationUseCategoryRules ? 'checked' : '' }}
                                       class="w-4 h-4 mt-0.5 text-primary rounded border-slate-300 focus:ring-primary">
                                <div>
                                    <span class="text-sm font-semibold text-slate-900 block">
                                        Derive useful life and disposal value strictly from Category rules (v1.3.4 Rule)
                                    </span>
                                    <span class="text-xs text-slate-600 block mt-0.5 leading-relaxed">
                                        When enabled, asset valuation strictly derives useful life and disposal value from category settings, ignoring legacy per-asset overrides. When disabled (default until approved), assets use their legacy recorded salvage and useful life values.
                                    </span>
                                    @if (! $depreciationUseCategoryRules)
                                        <div class="mt-2.5 p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-900 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            <span>
                                                <strong class="font-semibold">{{ $uncategorizedAssetsCount ?? 0 }} {{ \Illuminate\Support\Str::plural('asset', $uncategorizedAssetsCount ?? 0) }} have no category and will not be valued</strong>
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </label>
                        </div>

                        <div class="pt-4 border-t border-slate-200 flex items-center justify-between">
                            <span class="text-xs text-slate-500">Changes will be logged in the activity audit trail.</span>
                            <button type="submit"
                                    class="min-h-[44px] px-6 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-lg shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                Save Depreciation Policy
                            </button>
                        </div>
                    </form>
                </x-card>
            </div>
        @endif

        <!-- TAB 4: USER ACCOUNTS (ADMIN ONLY) -->
        @if (auth()->user()?->isAdmin())
            <div id="panel-users" role="tabpanel" class="tab-panel {{ $currentTab === 'users' ? '' : 'hidden' }} space-y-6">
                <!-- User Accounts Table -->
                <x-card title="Registered User Accounts" subtitle="Staff and Admin user credentials authorized to access Bethel Asset Tracker on this computer.">
                    <x-table>
                        <x-slot:header>
                            <tr>
                                <th class="py-3 px-4">Full Name</th>
                                <th class="py-3 px-4">Username</th>
                                <th class="py-3 px-4">Email</th>
                                <th class="py-3 px-4">Assigned Role</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </x-slot:header>

                        @foreach ($users as $u)
                            <tr>
                                <td class="py-3 px-4 font-semibold text-slate-900">
                                    {{ $u->name }}
                                    @if ($u->id === auth()->id())
                                        <span class="ml-1.5 text-xs font-normal text-slate-400">(You)</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-mono text-xs font-semibold text-slate-800">
                                    {{ $u->username }}
                                </td>
                                <td class="py-3 px-4 text-xs text-slate-600">
                                    {{ $u->email ?: '-' }}
                                </td>
                                <td class="py-3 px-4">
                                    @if ($u->isAdmin())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-primary border border-sky-200">
                                            Admin
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                            Staff
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    @if ($u->must_change_password)
                                        <span class="text-xs font-medium text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                            Temp Password
                                        </span>
                                    @else
                                        <span class="text-xs font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                            Active
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    @php
                                        $adminCount = $users->where('role', 'admin')->count();
                                        $isSoleAdmin = $u->isAdmin() && $adminCount <= 1;
                                    @endphp
                                    <div class="inline-flex flex-wrap items-center justify-end gap-1.5">
                                        @if ($u->isAdmin())
                                            <form method="POST" action="{{ route('settings.users.recovery-code', $u) }}" class="inline" onsubmit="return confirm('Generate a new one-time recovery code? Any previous code for this admin will stop working.');">
                                                @csrf
                                                <button type="submit"
                                                        class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100 cursor-pointer">
                                                    New Recovery Code
                                                </button>
                                            </form>
                                        @endif
                                        @if ($u->id !== auth()->id())
                                            <form method="POST" action="{{ route('settings.users.role', $u) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="role" value="{{ $u->isAdmin() ? 'staff' : 'admin' }}">
                                                <button type="submit"
                                                        @disabled($isSoleAdmin)
                                                        title="{{ $isSoleAdmin ? 'Cannot demote the last administrator' : 'Switch role' }}"
                                                        class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                                                    {{ $u->isAdmin() ? 'Make Staff' : 'Make Admin' }}
                                                </button>
                                            </form>
                                            <button type="button"
                                                    onclick="openResetPasswordModal({{ $u->id }}, '{{ addslashes($u->name) }}', '{{ addslashes($u->username) }}')"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 hover:text-slate-900 transition-colors cursor-pointer">
                                                Reset Password
                                            </button>
                                            <form method="POST" action="{{ route('settings.users.destroy', $u) }}" class="inline" onsubmit="return confirm('Delete this user account?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        @disabled($isSoleAdmin)
                                                        title="{{ $isSoleAdmin ? 'Cannot delete the last administrator' : 'Delete user' }}"
                                                        class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold text-red-700 bg-white border border-red-200 rounded-lg hover:bg-red-50 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </x-table>
                </x-card>

                <!-- Add User Form -->
                <x-card title="Add Staff or Admin Account" subtitle="Create a new login. The user will be required to set their own private password upon first sign in.">
                    <form method="POST" action="{{ route('settings.users.store') }}" class="max-w-xl space-y-4">
                        @csrf
                        <div>
                            <label for="user_name" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Full Name <span class="text-brand-red">*</span>
                            </label>
                            <input id="user_name"
                                   name="name"
                                   type="text"
                                   required
                                   value="{{ old('name') }}"
                                   placeholder="e.g. Maria Santos, RN"
                                   class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-slate-900 transition-colors">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="user_username" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Username <span class="text-brand-red">*</span>
                                </label>
                                <input id="user_username"
                                       name="username"
                                       type="text"
                                       required
                                       minlength="3"
                                       maxlength="30"
                                       pattern="[a-zA-Z0-9._-]+"
                                       value="{{ old('username') }}"
                                       placeholder="e.g. maria.santos"
                                       class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-slate-900 transition-colors">
                                <p class="text-[11px] text-slate-500 mt-1">3 to 30 characters: letters, numbers, dot, dash, underscore.</p>
                            </div>

                            <div>
                                <label for="user_email" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Email Address <span class="text-slate-400 font-normal">(Optional)</span>
                                </label>
                                <input id="user_email"
                                       name="email"
                                       type="email"
                                       value="{{ old('email') }}"
                                       placeholder="e.g. maria.santos@hospital.local"
                                       class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-slate-900 transition-colors">
                                <p class="text-[11px] text-slate-500 mt-1">Optional secondary login identifier.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="user_role" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    System Role <span class="text-brand-red">*</span>
                                </label>
                                <select id="user_role"
                                        name="role"
                                        class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-slate-900 transition-colors">
                                    <option value="staff" {{ old('role') === 'staff' ? 'selected' : '' }}>Staff (Standard Inventory)</option>
                                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin (Full System Access)</option>
                                </select>
                            </div>

                            <div>
                                <label for="user_password" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Temporary Password <span class="text-brand-red">*</span>
                                </label>
                                <div class="relative">
                                    <input id="user_password"
                                           name="password"
                                           type="password"
                                           required
                                           minlength="8"
                                           placeholder="Minimum 8 characters"
                                           class="w-full min-h-[44px] pl-3.5 pr-11 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-slate-900 transition-colors">
                                    <button type="button"
                                            onclick="togglePasswordVisibility('user_password', this)"
                                            aria-label="Toggle temporary password visibility"
                                            title="Show / hide password"
                                            class="absolute right-1.5 top-1/2 -translate-y-1/2 p-2 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 cursor-pointer transition-colors">
                                        <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <svg class="w-4 h-4 eye-slash hidden" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                            <x-button variant="primary" type="submit">
                                Create User Account
                            </x-button>
                        </div>
                    </form>
                </x-card>

                <!-- Reset Password Modal (Admin Only) -->
                <div id="reset-password-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all" onclick="event.stopPropagation()">
                        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Reset User Password</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Assign a temporary password for <span id="reset-modal-username" class="font-semibold font-mono text-slate-800"></span></p>
                            </div>
                            <button type="button" onclick="closeResetPasswordModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <form id="reset-password-form" method="POST" action="" class="p-6 space-y-4">
                            @csrf
                            <div>
                                <label for="reset_password_input" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Temporary Password <span class="text-brand-red">*</span>
                                </label>
                                <div class="relative">
                                    <input id="reset_password_input"
                                           name="password"
                                           type="password"
                                           required
                                           minlength="8"
                                           placeholder="Minimum 8 characters"
                                           class="w-full min-h-[44px] pl-3.5 pr-11 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-slate-900 transition-colors">
                                    <button type="button"
                                            onclick="togglePasswordVisibility('reset_password_input', this)"
                                            aria-label="Toggle temporary password visibility"
                                            title="Show / hide password"
                                            class="absolute right-1.5 top-1/2 -translate-y-1/2 p-2 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 cursor-pointer transition-colors">
                                        <svg class="w-4 h-4 eye-open" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <svg class="w-4 h-4 eye-slash hidden" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                                    </button>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1.5">User will be prompted to set their own permanent password on their next sign in.</p>
                            </div>

                            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                                <button type="button" onclick="closeResetPasswordModal()" class="min-h-[40px] px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg shadow-2xs hover:bg-slate-50 hover:border-slate-400 hover:text-slate-900 transition-colors cursor-pointer">
                                    Cancel
                                </button>
                                <button type="submit" class="min-h-[40px] px-5 py-2 text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-lg shadow-2xs transition-colors cursor-pointer">
                                    Save Temporary Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- System Diagnostics & Database Environment -->
        <x-card title="System Diagnostics & Desktop Environment" subtitle="Technical workstation environment and local SQLite storage details for hospital IT staff">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200">
                    <span class="text-slate-600 font-medium block">Application Version</span>
                    <strong class="text-slate-900 font-semibold text-sm mt-0.5 block">v{{ config('nativephp.version', '1.3.6') }}</strong>
                    <span class="text-slate-600 text-[11px] mt-0.5 block">Native Desktop Build</span>
                </div>
                <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200">
                    <span class="text-slate-600 font-medium block">Database Engine</span>
                    <strong class="text-slate-900 font-semibold text-sm mt-0.5 block">SQLite 3 (WAL mode)</strong>
                    <span class="text-slate-600 text-[11px] mt-0.5 block">Zero-config local storage</span>
                </div>
                <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200">
                    <span class="text-slate-600 font-medium block">Network Mode</span>
                    <div class="flex items-center gap-1.5 mt-1">
                        <span class="w-2 h-2 rounded-full bg-status-success-dot"></span>
                        <strong class="text-status-success-text font-semibold text-xs">Working offline</strong>
                    </div>
                    <span class="text-slate-600 text-[11px] mt-0.5 block">Local hospital workstation</span>
                </div>
                <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200">
                    <span class="text-slate-600 font-medium block">Backup Retention</span>
                    <strong class="text-slate-900 font-semibold text-sm mt-0.5 block">30 Rolling Snapshots</strong>
                    <span class="text-slate-600 text-[11px] mt-0.5 block">Automated 24h cycle</span>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex flex-col gap-1.5 text-xs text-slate-600 font-mono">
                @if (auth()->user()?->isAdmin())
                    <div><span class="text-slate-600 font-sans font-medium">Database File:</span> {{ $activeDatabasePath }}</div>
                @endif
                <div><span class="text-slate-600 font-sans font-medium">Backup Directory:</span> {{ $backupFolder }}</div>
            </div>
        </x-card>
    </div>

    <!-- Tab Switching Script -->
    <script>
        function switchTab(tabId) {
            // Update Tab Panels
            document.querySelectorAll('.tab-panel').forEach(function (panel) {
                panel.classList.add('hidden');
            });
            const targetPanel = document.getElementById('panel-' + tabId);
            if (targetPanel) {
                targetPanel.classList.remove('hidden');
            }

            // Update Tab Buttons
            document.querySelectorAll('.tab-btn').forEach(function (btn) {
                btn.classList.remove('border-primary', 'text-primary', 'font-semibold');
                btn.classList.add('border-transparent', 'text-slate-500', 'font-medium');
                btn.setAttribute('aria-selected', 'false');
                const svg = btn.querySelector('svg');
                if (svg) {
                    svg.classList.remove('text-primary');
                    svg.classList.add('text-slate-400');
                }
            });

            const activeBtn = document.getElementById('tab-btn-' + tabId);
            if (activeBtn) {
                activeBtn.classList.remove('border-transparent', 'text-slate-500', 'font-medium');
                activeBtn.classList.add('border-primary', 'text-primary', 'font-semibold');
                activeBtn.setAttribute('aria-selected', 'true');
                const svg = activeBtn.querySelector('svg');
                if (svg) {
                    svg.classList.remove('text-slate-400');
                    svg.classList.add('text-primary');
                }
                if (typeof activeBtn.scrollIntoView === 'function') {
                    activeBtn.scrollIntoView({ behavior: 'auto', block: 'nearest', inline: 'nearest' });
                }
            }

            // Store active tab in URL hash for persistent page reloads
            if (history.replaceState) {
                history.replaceState(null, null, '#' + tabId);
            }

            if (tabId === 'tag') {
                initTagLivePreview();
            }
        }

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

        function updateBarcodePreview(checked) {
            const strips = document.querySelectorAll('#tag-preview-wrapper .barcode-strip');
            strips.forEach(function (strip) {
                if (checked) {
                    strip.classList.remove('hidden');
                    strip.style.display = '';
                } else {
                    strip.classList.add('hidden');
                    strip.style.display = 'none';
                }
            });
            const status = document.getElementById('barcode-preview-status');
            if (status) {
                status.textContent = checked ? 'Barcode Enabled' : 'No Barcode';
                status.className = checked
                    ? 'inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-primary-soft text-primary'
                    : 'inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-200 text-slate-700';
            }
        }

        function initTagLivePreview() {
            const widthInput = document.getElementById('tag_width_cm');
            const heightInput = document.getElementById('tag_height_cm');
            const prefixInput = document.getElementById('asset_no_prefix');
            const dimBadge = document.getElementById('preview-dim-badge');
            const tagCard = document.querySelector('#tag-preview-wrapper .property-tag-card');

            function updateDimensions() {
                if (!tagCard) return;
                const rawW = parseFloat(widthInput ? widthInput.value : 10);
                const rawH = parseFloat(heightInput ? heightInput.value : 6);
                const w = (!isNaN(rawW) && rawW > 0) ? rawW : 10;
                const h = (!isNaN(rawH) && rawH > 0) ? rawH : 6;

                tagCard.style.width = w + 'cm';
                tagCard.style.height = h + 'cm';

                if (dimBadge) {
                    dimBadge.innerHTML = w.toFixed(1) + ' cm &times; ' + h.toFixed(1) + ' cm';
                }
            }

            function updatePrefix() {
                const prefix = (prefixInput && prefixInput.value.trim()) ? prefixInput.value.trim() : 'BBH';
                const assetNoText = prefix + '-SURG-OR2-2026-00049281';

                document.querySelectorAll('#tag-preview-wrapper .preview-asset-no-cell').forEach(function (el) {
                    el.textContent = assetNoText;
                });
                document.querySelectorAll('#tag-preview-wrapper .preview-barcode-text').forEach(function (el) {
                    el.textContent = '*' + assetNoText + '*';
                });
            }

            if (widthInput && !widthInput._hasLivePreviewListener) {
                widthInput.addEventListener('input', updateDimensions);
                widthInput._hasLivePreviewListener = true;
            }
            if (heightInput && !heightInput._hasLivePreviewListener) {
                heightInput.addEventListener('input', updateDimensions);
                heightInput._hasLivePreviewListener = true;
            }
            if (prefixInput && !prefixInput._hasLivePreviewListener) {
                prefixInput.addEventListener('input', updatePrefix);
                prefixInput._hasLivePreviewListener = true;
            }

            updateDimensions();
            updatePrefix();
        }

        function openEditCategoryModal(id, name, life, salvage, assetsCount) {
            if (name.toLowerCase() === 'uncategorized') {
                alert('The Uncategorized placeholder cannot be edited.');
                return;
            }
            const modal = document.getElementById('edit-category-modal');
            const form = document.getElementById('edit-category-form');
            if (!modal || !form) return;

            form.action = '/settings/categories/' + id;
            form.dataset.assetsCount = assetsCount !== undefined ? assetsCount : 0;
            document.getElementById('edit_cat_name').value = name;
            document.getElementById('edit_cat_life').value = life || '';
            document.getElementById('edit_cat_salvage').value = (salvage !== null && salvage !== undefined && salvage !== '') ? salvage : '';
            modal.classList.remove('hidden');
        }

        function handleEditCategorySubmit(form) {
            const count = parseInt(form.dataset.assetsCount || '0', 10);
            if (count > 0) {
                return confirm("This changes the estimated values of " + count + " assets");
            }
            return true;
        }

        function handleCategoryRuleSwitch(cb) {
            if (cb.checked && cb.dataset.initial !== '1') {
                const uncategorized = parseInt(cb.dataset.uncategorized || '0', 10);
                const noun = uncategorized === 1 ? 'asset has' : 'assets have';
                const msg = uncategorized + " " + noun + " no category and will not be valued. Do you want to enable category-driven depreciation rules?";
                if (!confirm(msg)) {
                    cb.checked = false;
                }
            }
        }

        function closeEditCategoryModal() {
            const modal = document.getElementById('edit-category-modal');
            if (modal) modal.classList.add('hidden');
        }

        function openResetPasswordModal(userId, userName, username) {
            const modal = document.getElementById('reset-password-modal');
            const form = document.getElementById('reset-password-form');
            const label = document.getElementById('reset-modal-username');
            const input = document.getElementById('reset_password_input');
            if (!modal || !form) return;
            form.action = '/settings/users/' + userId + '/reset-password';
            if (label) label.textContent = userName + ' (@' + username + ')';
            if (input) input.value = '';
            modal.classList.remove('hidden');
            setTimeout(() => { if (input) input.focus(); }, 50);
        }

        function closeResetPasswordModal() {
            const modal = document.getElementById('reset-password-modal');
            if (modal) modal.classList.add('hidden');
        }

        function openAddDepartmentModal() {
            const modal = document.getElementById('add-department-modal');
            if (modal) modal.classList.remove('hidden');
        }

        function closeAddDepartmentModal() {
            const modal = document.getElementById('add-department-modal');
            if (modal) modal.classList.add('hidden');
        }

        function openEditDepartmentModal(id, name, code, nextNumber, active, confirmed) {
            const modal = document.getElementById('edit-department-modal');
            const form = document.getElementById('edit-department-form');
            if (!modal || !form) return;

            form.action = '/settings/departments/' + id;
            document.getElementById('edit_dept_name').value = name;
            document.getElementById('edit_dept_code').value = code;
            document.getElementById('edit_dept_next').value = nextNumber;
            document.getElementById('edit_dept_active').checked = !!active;
            document.getElementById('edit_dept_confirmed').checked = !!confirmed;
            modal.classList.remove('hidden');
        }

        function closeEditDepartmentModal() {
            const modal = document.getElementById('edit-department-modal');
            if (modal) modal.classList.add('hidden');
        }

        function filterSectionsByDepartment(deptId) {
            const rows = document.querySelectorAll('.section-row');
            rows.forEach(function (row) {
                if (!deptId || String(row.dataset.departmentId) === String(deptId)) {
                    row.classList.remove('hidden');
                } else {
                    row.classList.add('hidden');
                }
            });
        }

        function openAddSectionModal() {
            const modal = document.getElementById('add-section-modal');
            const filterSelect = document.getElementById('sections-dept-filter');
            const addDeptSelect = document.getElementById('add_sec_dept');
            if (filterSelect && addDeptSelect && filterSelect.value) {
                addDeptSelect.value = filterSelect.value;
            }
            if (modal) modal.classList.remove('hidden');
        }

        function closeAddSectionModal() {
            const modal = document.getElementById('add-section-modal');
            if (modal) modal.classList.add('hidden');
        }

        function openEditSectionModal(id, departmentId, name, code, active, confirmed) {
            const modal = document.getElementById('edit-section-modal');
            const form = document.getElementById('edit-section-form');
            if (!modal || !form) return;

            form.action = '/settings/sections/' + id;
            document.getElementById('edit_sec_dept').value = departmentId;
            document.getElementById('edit_sec_name').value = name;
            document.getElementById('edit_sec_code').value = code;
            document.getElementById('edit_sec_active').checked = !!active;
            document.getElementById('edit_sec_confirmed').checked = !!confirmed;
            modal.classList.remove('hidden');
        }

        function closeEditSectionModal() {
            const modal = document.getElementById('edit-section-modal');
            if (modal) modal.classList.add('hidden');
        }

        function handleConventionChange(value) {
            const picker = document.getElementById('custom-convention-picker');
            if (picker) {
                if (value === 'custom') {
                    picker.classList.remove('hidden');
                } else {
                    picker.classList.add('hidden');
                }
            }
        }

        function openAddVendorModal() {
            const modal = document.getElementById('add-vendor-modal');
            if (modal) modal.classList.remove('hidden');
        }

        function closeAddVendorModal() {
            const modal = document.getElementById('add-vendor-modal');
            if (modal) modal.classList.add('hidden');
        }

        function openEditVendorModal(id, name, address, contactPerson, phone, isDonation, notes) {
            const modal = document.getElementById('edit-vendor-modal');
            const form = document.getElementById('edit-vendor-form');
            if (!modal || !form) return;

            form.action = '/settings/vendors/' + id;
            document.getElementById('edit_vendor_name').value = name || '';
            document.getElementById('edit_vendor_address').value = address || '';
            document.getElementById('edit_vendor_contact').value = contactPerson || '';
            document.getElementById('edit_vendor_phone').value = phone || '';
            document.getElementById('edit_vendor_donation').checked = !!isDonation;
            document.getElementById('edit_vendor_notes').value = notes || '';
            modal.classList.remove('hidden');
        }

        function closeEditVendorModal() {
            const modal = document.getElementById('edit-vendor-modal');
            if (modal) modal.classList.add('hidden');
        }

        function openAddTemplateModal() {
            const modal = document.getElementById('add-template-modal');
            if (modal) modal.classList.remove('hidden');
        }

        function closeAddTemplateModal() {
            const modal = document.getElementById('add-template-modal');
            if (modal) modal.classList.add('hidden');
        }

        function toggleInlineTemplateEdit(id) {
            const editRow = document.getElementById('template-edit-row-' + id);
            if (!editRow) return;
            editRow.classList.toggle('hidden');
        }

        function toggleTemplateImportPanel() {
            const panel = document.getElementById('template-import-panel');
            if (!panel) return;
            panel.classList.toggle('hidden');
        }

        function previewTabDisplayPreferences() {
            const form = document.getElementById('settings-tab-display-form');
            if (!form) return;
            const textSize = form.querySelector('input[name="text_size"]:checked')?.value || 'default';
            const density = form.querySelector('input[name="density"]:checked')?.value || 'comfortable';
            const theme = form.querySelector('input[name="theme"]:checked')?.value || 'light';

            if (typeof applyDisplayPrefsToDocument === 'function') {
                applyDisplayPrefsToDocument({
                    text_size: textSize,
                    density: density,
                    theme: theme,
                });
            }
            const summary = document.getElementById('settings-tab-display-summary');
            if (summary) {
                const cap = s => s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
                summary.textContent = cap(textSize) + ' text \u2022 ' + cap(density) + ' \u2022 ' + cap(theme);
            }
        }

        // Initialize tab based on URL param or hash if provided
        document.addEventListener('DOMContentLoaded', function () {
            initTagLivePreview();
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            const hash = window.location.hash.replace('#', '');
            const initialTab = tabParam || hash;
            if (initialTab && ['hospital', 'tag', 'backup', 'display', 'categories', 'departments', 'vendors', 'asset-templates', 'depreciation', 'users'].includes(initialTab)) {
                switchTab(initialTab);
            }
        });
    </script>
@endsection
