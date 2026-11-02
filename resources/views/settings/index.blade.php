@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <!-- Page Header -->
        <x-page-header
            title="Settings &amp; Backup"
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
                        class="tab-btn min-h-[44px] px-5 py-2.5 text-sm font-semibold border-b-2 border-[#b91c1c] text-slate-900 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#b91c1c]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
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
            <x-card title="Hospital Information &amp; Official Emblem" subtitle="Hospital name and address appear on all printed property tags, reports, and audit certificates.">
                <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="max-w-2xl space-y-5">
                    @csrf
                    {{-- Hidden preserved values from other sections to pass validation --}}
                    <input type="hidden" name="asset_no_prefix" value="{{ $assetNoPrefix }}">
                    <input type="hidden" name="tag_width_cm" value="{{ $tagWidthCm }}">
                    <input type="hidden" name="tag_height_cm" value="{{ $tagHeightCm }}">
                    <input type="hidden" name="backup_folder" value="{{ $backupFolder }}">

                    <div>
                        <label for="hospital_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                            Hospital Name <span class="text-[#b91c1c]">*</span>
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
                        <label for="hospital_address" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                            Hospital Address <span class="text-[#b91c1c]">*</span>
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
                        <label for="logo" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-2">
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
            <x-card title="Property Tag Dimensions &amp; Code Prefix" subtitle="Configure the default physical label dimensions and automatic code sequencing for new equipment tags.">
                <form method="POST" action="{{ route('settings.update') }}" class="max-w-2xl space-y-5">
                    @csrf
                    {{-- Hidden preserved values from other sections --}}
                    <input type="hidden" name="hospital_name" value="{{ $hospitalName }}">
                    <input type="hidden" name="hospital_address" value="{{ $hospitalAddress }}">
                    <input type="hidden" name="backup_folder" value="{{ $backupFolder }}">

                    <div>
                        <label for="asset_no_prefix" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                            Asset No. Prefix <span class="text-[#b91c1c]">*</span>
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
                            <label for="tag_width_cm" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Physical Tag Width (cm) <span class="text-[#b91c1c]">*</span>
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
                            <label for="tag_height_cm" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Physical Tag Height (cm) <span class="text-[#b91c1c]">*</span>
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
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active SQLite Database</p>
                            <p class="font-mono text-xs font-medium text-slate-900 mt-1.5 break-all bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                {{ $activeDatabasePath }}
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-800 border border-emerald-200 shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                            <span>Connected</span>
                        </span>
                    </div>
                </x-card>

                <x-card padding="p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Backup Directory</p>
                            <p class="font-mono text-xs font-medium text-slate-900 mt-1.5 break-all bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                {{ $backupFolder }}
                            </p>
                        </div>
                        <form method="POST" action="{{ route('settings.pick-folder') }}" class="shrink-0">
                            @csrf
                            <x-button variant="secondary" size="sm" type="submit">
                                <span>Choose Folder&hellip;</span>
                            </x-button>
                        </form>
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
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
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
                            <label for="user_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Full Name <span class="text-[#b91c1c]">*</span>
                            </label>
                            <input id="user_name"
                                   name="name"
                                   type="text"
                                   required
                                   placeholder="e.g. Maria Santos, RN"
                                   class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-slate-900 transition-colors">
                        </div>

                        <div>
                            <label for="user_email" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Email / Login ID <span class="text-[#b91c1c]">*</span>
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
                                <label for="user_role" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                    System Role <span class="text-[#b91c1c]">*</span>
                                </label>
                                <select id="user_role"
                                        name="role"
                                        class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-slate-900 transition-colors">
                                    <option value="staff">Staff (Standard Inventory)</option>
                                    <option value="admin">Admin (Full System Access)</option>
                                </select>
                            </div>

                            <div>
                                <label for="user_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                    Temporary Password <span class="text-[#b91c1c]">*</span>
                                </label>
                                <input id="user_password"
                                       name="password"
                                       type="password"
                                       required
                                       minlength="8"
                                       placeholder="Minimum 8 characters"
                                       class="w-full min-h-[44px] px-3.5 py-2 text-sm bg-white border border-slate-300 rounded-lg shadow-2xs focus:outline-none focus:border-slate-900 transition-colors">
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
                btn.classList.remove('border-[#b91c1c]', 'text-slate-900', 'font-semibold');
                btn.classList.add('border-transparent', 'text-slate-500', 'font-medium');
                btn.setAttribute('aria-selected', 'false');
                const svg = btn.querySelector('svg');
                if (svg) {
                    svg.classList.remove('text-[#b91c1c]');
                    svg.classList.add('text-slate-400');
                }
            });

            const activeBtn = document.getElementById('tab-btn-' + tabId);
            if (activeBtn) {
                activeBtn.classList.remove('border-transparent', 'text-slate-500', 'font-medium');
                activeBtn.classList.add('border-[#b91c1c]', 'text-slate-900', 'font-semibold');
                activeBtn.setAttribute('aria-selected', 'true');
                const svg = activeBtn.querySelector('svg');
                if (svg) {
                    svg.classList.remove('text-slate-400');
                    svg.classList.add('text-[#b91c1c]');
                }
            }

            // Store active tab in URL hash for persistent page reloads
            if (history.replaceState) {
                history.replaceState(null, null, '#' + tabId);
            }
        }

        // Initialize tab based on URL hash if provided
        document.addEventListener('DOMContentLoaded', function () {
            const hash = window.location.hash.replace('#', '');
            if (hash && ['hospital', 'tag', 'backup', 'users'].includes(hash)) {
                switchTab(hash);
            }
        });
    </script>
@endsection
