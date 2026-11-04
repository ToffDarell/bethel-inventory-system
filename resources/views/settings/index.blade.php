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

        <!-- Tab Navigation Bar -->
        <div class="border-b border-slate-200" id="settings-tab-container">
            <nav class="flex flex-wrap gap-2 -mb-px" aria-label="Settings Tabs" role="tablist">
                <button type="button"
                        onclick="switchTab('hospital')"
                        id="tab-btn-hospital"
                        role="tab"
                        aria-controls="panel-hospital"
                        aria-selected="true"
                        class="tab-btn min-h-[44px] px-5 py-2.5 text-sm font-semibold border-b-2 border-primary text-primary transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span>Hospital Profile</span>
                </button>

                <button type="button"
                        onclick="switchTab('tag')"
                        id="tab-btn-tag"
                        role="tab"
                        aria-controls="panel-tag"
                        aria-selected="false"
                        class="tab-btn min-h-[44px] px-5 py-2.5 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    <span>Property Tag</span>
                </button>

                <button type="button"
                        onclick="switchTab('backup')"
                        id="tab-btn-backup"
                        role="tab"
                        aria-controls="panel-backup"
                        aria-selected="false"
                        class="tab-btn min-h-[44px] px-5 py-2.5 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7M4 7c0-2 1.5-3 3.5-3h9c2 0 3.5 1 3.5 3M4 7h16m-8 4v6m-3-3l3 3 3-3"/>
                    </svg>
                    <span>Backup &amp; Restore</span>
                    @if ($backupOverdue)
                        <span class="w-2 h-2 rounded-full bg-amber-500" title="Backup overdue"></span>
                    @endif
                </button>

                @if (auth()->user()?->isAdmin())
                    <button type="button"
                            onclick="switchTab('categories')"
                            id="tab-btn-categories"
                            role="tab"
                            aria-controls="panel-categories"
                            aria-selected="false"
                            class="tab-btn min-h-[44px] px-5 py-2.5 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300 transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                        </svg>
                        <span>Categories</span>
                    </button>

                    <button type="button"
                            onclick="switchTab('users')"
                            id="tab-btn-users"
                            role="tab"
                            aria-controls="panel-users"
                            aria-selected="false"
                            class="tab-btn min-h-[44px] px-5 py-2.5 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300 transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span>User Accounts</span>
                    </button>
                @endif
            </nav>
        </div>

        <!-- TAB 1: HOSPITAL PROFILE -->
        <div id="panel-hospital" role="tabpanel" class="tab-panel space-y-6">
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
        <div id="panel-tag" role="tabpanel" class="tab-panel hidden space-y-6">
            <x-card title="Property Tag Dimensions & Code Prefix" subtitle="Configure the default physical label dimensions and automatic code sequencing for new equipment tags.">
                <form method="POST" action="{{ route('settings.update') }}" class="max-w-2xl space-y-5">
                    @csrf
                    {{-- Hidden preserved values from other sections --}}
                    <input type="hidden" name="hospital_name" value="{{ $hospitalName }}">
                    <input type="hidden" name="hospital_address" value="{{ $hospitalAddress }}">
                    <input type="hidden" name="backup_folder" value="{{ $backupFolder }}">

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
                        <p class="text-xs text-slate-500 mt-1">Starting prefix used when auto-generating asset codes (e.g., <code>BBH-000001</code>).</p>
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
                            <p class="text-xs text-slate-500 mt-1">Standard hospital property tag width (default: 10 cm).</p>
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
                            <p class="text-xs text-slate-500 mt-1">Standard hospital property tag height (default: 6 cm).</p>
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
                                        Turn this on only if you use a barcode scanner. The barcode contains the Asset No.
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

                    <!-- Small Live Tag Preview -->
                    <div class="pt-5 border-t border-slate-100">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-slate-700">
                                Live Property Tag Preview
                            </span>
                            <span id="barcode-preview-status" class="text-xs font-medium {{ $showBarcode ? 'text-primary' : 'text-slate-500' }}">
                                {{ $showBarcode ? 'Barcode Enabled' : 'Traditional Paper Tag (No Barcode)' }}
                            </span>
                        </div>
                        <div class="p-4 bg-slate-100/90 rounded-xl border border-slate-200 flex items-center justify-center overflow-x-auto">
                            <div class="bg-white shadow-sm inline-block" id="tag-preview-wrapper">
                                @include('tags.partials.tag-card', [
                                    'asset' => $sampleAsset,
                                    'showBarcode' => $showBarcode,
                                    'preview' => true,
                                    'tagAssetService' => $tagAssetService,
                                ])
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <x-button variant="primary" type="submit">
                            Save Tag Settings
                        </x-button>
                    </div>
                </form>
            </x-card>
        </div>

        <!-- TAB 3: BACKUP & RESTORE -->
        <div id="panel-backup" role="tabpanel" class="tab-panel hidden space-y-6">
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
                        {{ $activeDatabasePath }}
                    </div>
                </x-card>

                <x-card padding="p-5">
                    <div class="flex items-center justify-between gap-3 mb-2.5">
                        <span class="text-xs font-semibold text-slate-700">Backup Directory</span>
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
                    <div class="font-mono text-xs font-medium text-slate-900 break-all bg-slate-50 p-3 rounded-lg border border-slate-200 min-h-[46px] flex items-center">
                        {{ $backupFolder }}
                    </div>
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

        <!-- TAB: CATEGORIES (ADMIN ONLY) -->
        @if (auth()->user()?->isAdmin())
            <div id="panel-categories" role="tabpanel" class="tab-panel hidden space-y-6">
                <!-- Categories Table -->
                <x-card title="Asset Categories & Depreciation Presets" subtitle="Manage equipment categories, default useful life spans, and estimated salvage percentages. Categories in use cannot be deleted.">
                    <x-table>
                        <x-slot:header>
                            <tr>
                                <th class="py-3 px-4">Category Name</th>
                                <th class="py-3 px-4">Default Useful Life</th>
                                <th class="py-3 px-4">Default Salvage %</th>
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
                                <td class="py-3 px-4 text-slate-700 font-mono">
                                    {{ number_format((float) $cat->default_salvage_percent, 2) }}%
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $cat->assets_count > 0 ? 'bg-sky-50 text-primary border border-sky-200' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $cat->assets_count }} {{ Str::plural('asset', $cat->assets_count) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button"
                                                onclick="openEditCategoryModal({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ $cat->default_useful_life_years ?? '' }}', '{{ $cat->default_salvage_percent }}')"
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
                                    Default Useful Life (years)
                                </label>
                                <input type="number"
                                       id="cat_life"
                                       name="default_useful_life_years"
                                       min="1"
                                       max="100"
                                       value="{{ old('default_useful_life_years') }}"
                                       placeholder="e.g. 10"
                                       class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                <p class="text-[11px] text-slate-500 mt-1">Useful life for straight-line calculations</p>
                            </div>
                            <div>
                                <label for="cat_salvage" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                    Default Salvage Value (%)
                                </label>
                                <input type="number"
                                       id="cat_salvage"
                                       name="default_salvage_percent"
                                       min="0"
                                       max="100"
                                       step="0.01"
                                       value="{{ old('default_salvage_percent', '0') }}"
                                       placeholder="0.00"
                                       class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                <p class="text-[11px] text-slate-500 mt-1">Default salvage % of cost (e.g. 10%)</p>
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
                    <form id="edit-category-form" method="POST" action="" class="p-6 space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label for="edit_cat_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Category Name <span class="text-crimson">*</span></label>
                            <input type="text" id="edit_cat_name" name="name" required class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="edit_cat_life" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Useful Life (years)</label>
                                <input type="number" id="edit_cat_life" name="default_useful_life_years" min="1" max="100" class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="edit_cat_salvage" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Salvage Value (%)</label>
                                <input type="number" id="edit_cat_salvage" name="default_salvage_percent" min="0" max="100" step="0.01" class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
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

        <!-- TAB 4: USER ACCOUNTS (ADMIN ONLY) -->
        @if (auth()->user()?->isAdmin())
            <div id="panel-users" role="tabpanel" class="tab-panel hidden space-y-6">
                <!-- User Accounts Table -->
                <x-card title="Registered User Accounts" subtitle="Staff and Admin user credentials authorized to access Bethel Asset Tracker on this computer.">
                    <x-table>
                        <x-slot:header>
                            <tr>
                                <th class="py-3 px-4">Full Name</th>
                                <th class="py-3 px-4">Email / Login ID</th>
                                <th class="py-3 px-4">Assigned Role</th>
                                <th class="py-3 px-4 text-right">Status</th>
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
                                <td class="py-3 px-4 font-mono text-xs text-slate-600">
                                    {{ $u->email }}
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
                                <td class="py-3 px-4 text-right">
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
                                   placeholder="e.g. Maria Santos, RN"
                                   class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-slate-900 transition-colors">
                        </div>

                        <div>
                            <label for="user_email" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Email / Login ID <span class="text-brand-red">*</span>
                            </label>
                            <input id="user_email"
                                   name="email"
                                   type="email"
                                   required
                                   placeholder="e.g. maria.santos@hospital.local"
                                   class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-slate-900 transition-colors">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="user_role" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    System Role <span class="text-brand-red">*</span>
                                </label>
                                <select id="user_role"
                                        name="role"
                                        class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-slate-900 transition-colors">
                                    <option value="staff">Staff (Standard Inventory)</option>
                                    <option value="admin">Admin (Full System Access)</option>
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
            </div>
        @endif

        <!-- System Diagnostics & Database Environment -->
        <x-card title="System Diagnostics & Desktop Environment" subtitle="Technical workstation environment and local SQLite storage details for hospital IT staff">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200">
                    <span class="text-slate-600 font-medium block">Application Version</span>
                    <strong class="text-slate-900 font-semibold text-sm mt-0.5 block">v1.0.0</strong>
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
                <div><span class="text-slate-600 font-sans font-medium">Database File:</span> {{ $activeDatabasePath }}</div>
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
            }

            // Store active tab in URL hash for persistent page reloads
            if (history.replaceState) {
                history.replaceState(null, null, '#' + tabId);
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
                status.textContent = checked ? 'Barcode Enabled' : 'Traditional Paper Tag (No Barcode)';
                status.className = checked ? 'text-xs font-medium text-primary' : 'text-xs font-medium text-slate-500';
            }
        }

        function openEditCategoryModal(id, name, life, salvage) {
            const modal = document.getElementById('edit-category-modal');
            const form = document.getElementById('edit-category-form');
            if (!modal || !form) return;

            form.action = '/settings/categories/' + id;
            document.getElementById('edit_cat_name').value = name;
            document.getElementById('edit_cat_life').value = life || '';
            document.getElementById('edit_cat_salvage').value = salvage || '0';
            modal.classList.remove('hidden');
        }

        function closeEditCategoryModal() {
            const modal = document.getElementById('edit-category-modal');
            if (modal) modal.classList.add('hidden');
        }

        // Initialize tab based on URL hash if provided
        document.addEventListener('DOMContentLoaded', function () {
            const hash = window.location.hash.replace('#', '');
            if (hash && ['hospital', 'tag', 'backup', 'categories', 'users'].includes(hash)) {
                switchTab(hash);
            }
        });
    </script>
@endsection
