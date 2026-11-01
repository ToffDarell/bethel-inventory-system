@extends('layouts.app')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-3xl font-bold text-slate-900">Settings &amp; Backup</h2>
            <p class="text-base text-slate-600 mt-1">
                Configure hospital details, property tag dimensions, and database backups.
            </p>
        </div>

        @if (auth()->user()?->isAdmin())
            <a href="{{ route('settings.activity-log') }}"
               class="inline-flex items-center gap-2 px-5 py-3 rounded-lg border-2 border-slate-300 bg-white hover:bg-slate-50 text-slate-800 font-bold text-base transition">
                <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>View Activity Log</span>
            </a>
        @endif
    </div>

    {{-- Restart Required Banner after Restore (Adjustment #4) --}}
    @if (session('restart_required'))
        <div class="mb-6 p-5 rounded-xl bg-blue-50 border-2 border-blue-600 text-blue-950 flex flex-wrap items-center justify-between gap-4" role="alert">
            <div>
                <p class="font-bold text-lg">Application Restart Required</p>
                <p class="text-base text-blue-900 mt-0.5">
                    The SQLite database was restored from backup. Please restart Bethel Asset Tracker now to reload the restored data cleanly.
                </p>
            </div>
            <form method="POST" action="{{ route('settings.relaunch') }}">
                @csrf
                <button type="submit"
                        class="px-6 py-3 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-bold text-base shadow-sm cursor-pointer">
                    Restart Application Now
                </button>
            </form>
        </div>
    @endif

    {{-- 7-Day No Backup Warning Banner (Adjustment #3) --}}
    @if ($backupOverdue)
        <div class="mb-6 p-5 rounded-xl bg-amber-50 border-2 border-amber-500 text-amber-950 flex flex-wrap items-center justify-between gap-4" role="alert">
            <div class="flex items-start gap-3">
                <svg class="w-7 h-7 text-amber-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <p class="font-bold text-lg">Backup Reminder: No database backup has been created in the last 7 days.</p>
                    <p class="text-base text-amber-900 mt-0.5">
                        Click <strong>Backup Now</strong> to save a safe copy of your hospital asset records to <code>{{ $backupFolder }}</code>.
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('settings.backup') }}">
                @csrf
                <button type="submit"
                        class="px-5 py-3 rounded-lg bg-amber-700 hover:bg-amber-800 text-white font-bold text-base shadow-sm cursor-pointer">
                    Backup Now
                </button>
            </form>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 p-4 rounded-lg bg-red-50 border-2 border-red-400 text-red-900" role="alert">
            <p class="font-bold text-base mb-1">Please check the message below:</p>
            <ul class="list-disc list-inside text-base space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="space-y-8">
        <!-- SECTION 1: Database Backup & Restore -->
        <div class="p-6 bg-white border-2 border-slate-200 rounded-xl">
            <div class="flex flex-wrap items-center justify-between gap-4 pb-4 mb-6 border-b border-slate-200">
                <div>
                    <h3 class="text-2xl font-bold text-slate-900">Database Backup &amp; Restore</h3>
                    <p class="text-base text-slate-600 mt-1">
                        Keeps the last 30 backups automatically. Backups also run on app start (every 24 hours) and before any database update.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <form method="POST" action="{{ route('settings.pick-folder') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-3 rounded-lg border-2 border-slate-300 bg-white hover:bg-slate-50 text-slate-800 font-bold text-base transition cursor-pointer">
                            <span>Choose Backup Folder&hellip;</span>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('settings.backup') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-bold text-base shadow-sm transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                            </svg>
                            <span>Backup Now</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 text-sm">
                <div class="p-4 rounded-lg bg-slate-50 border border-slate-200">
                    <p class="font-bold uppercase tracking-wide text-slate-500 text-xs">Backup Folder</p>
                    <p class="font-mono font-bold text-slate-900 mt-1 break-all">{{ $backupFolder }}</p>
                </div>
                <div class="p-4 rounded-lg bg-slate-50 border border-slate-200">
                    <p class="font-bold uppercase tracking-wide text-slate-500 text-xs">Active SQLite Database File</p>
                    <p class="font-mono font-bold text-slate-900 mt-1 break-all">{{ $activeDatabasePath }}</p>
                </div>
            </div>

            <!-- Saved Backups List -->
            <h4 class="text-lg font-bold text-slate-900 mb-3">Saved Backups ({{ count($backups) }} of 30 max)</h4>
            <div class="border border-slate-200 rounded-lg overflow-hidden mb-6">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-200 text-xs font-bold uppercase tracking-wide text-slate-700">
                            <th class="p-3">Backup File</th>
                            <th class="p-3">Created</th>
                            <th class="p-3">Size</th>
                            @if (auth()->user()?->isAdmin())
                                <th class="p-3 text-right">Restore</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-base">
                        @forelse (array_slice($backups, 0, 10) as $backup)
                            <tr class="hover:bg-slate-50">
                                <td class="p-3 font-mono font-bold text-sm text-slate-900">{{ $backup['filename'] }}</td>
                                <td class="p-3 text-sm text-slate-700">{{ $backup['modified_at']->format('M d, Y h:i:s A') }}</td>
                                <td class="p-3 text-sm text-slate-600 font-mono">{{ number_format($backup['size_kb'], 1) }} KB</td>
                                @if (auth()->user()?->isAdmin())
                                    <td class="p-3 text-right">
                                        <form method="POST"
                                              action="{{ route('settings.restore') }}"
                                              onsubmit="return confirm('Restore database from {{ addslashes($backup['filename']) }}?\n\nYour current database will be backed up automatically first, and the application will restart.');">
                                            @csrf
                                            <input type="hidden" name="backup_filename" value="{{ $backup['filename'] }}">
                                            <button type="submit"
                                                    class="px-3.5 py-1.5 rounded-lg border border-amber-400 bg-amber-50 hover:bg-amber-100 text-amber-900 font-bold text-xs cursor-pointer">
                                                Restore This Backup
                                            </button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()?->isAdmin() ? 4 : 3 }}" class="p-6 text-center text-slate-600">
                                    No backups found in <code>{{ $backupFolder }}</code> yet. Click <strong>Backup Now</strong> above to create your first backup.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (auth()->user()?->isAdmin())
                <div class="p-4 rounded-lg bg-slate-50 border border-slate-200">
                    <h4 class="text-base font-bold text-slate-900 mb-1">Restore from Another Backup File (Admin Only)</h4>
                    <p class="text-sm text-slate-600 mb-3">
                        Select a <code>.sqlite</code> backup file from another folder or USB drive. We will verify its SQLite integrity, back up your current database first, and then restore it.
                    </p>
                    <form method="POST"
                          action="{{ route('settings.restore') }}"
                          enctype="multipart/form-data"
                          onsubmit="return confirm('Are you sure you want to restore the database from the selected file? Your current database will be backed up first and the app will restart.');"
                          class="flex flex-wrap items-center gap-3">
                        @csrf
                        <input type="file"
                               name="backup_file"
                               required
                               accept=".sqlite,.db"
                               class="flex-1 min-w-[260px] px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                        <button type="submit"
                                class="px-5 py-2.5 rounded-lg bg-amber-700 hover:bg-amber-800 text-white font-bold text-sm cursor-pointer">
                            Validate &amp; Restore File
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <!-- SECTION 2: Hospital & Property Tag Configuration -->
        <div class="p-6 bg-white border-2 border-slate-200 rounded-xl max-w-3xl">
            <h3 class="text-2xl font-bold text-slate-900 mb-2">Hospital &amp; Property Tag Settings</h3>
            <p class="text-base text-slate-600 mb-6 pb-4 border-b border-slate-200">
                These details are printed at the top of every property tag.
            </p>

            <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div>
                    <label for="hospital_name" class="block text-base font-bold text-slate-900 mb-2">
                        Hospital Name <span class="text-red-600">*</span>
                    </label>
                    <input id="hospital_name"
                           name="hospital_name"
                           type="text"
                           required
                           value="{{ old('hospital_name', $hospitalName) }}"
                           class="w-full px-4 py-3 text-lg border-2 border-slate-300 rounded-lg focus:outline-none focus:border-blue-700">
                </div>

                <div>
                    <label for="hospital_address" class="block text-base font-bold text-slate-900 mb-2">
                        Hospital Address <span class="text-red-600">*</span>
                    </label>
                    <input id="hospital_address"
                           name="hospital_address"
                           type="text"
                           required
                           value="{{ old('hospital_address', $hospitalAddress) }}"
                           class="w-full px-4 py-3 text-lg border-2 border-slate-300 rounded-lg focus:outline-none focus:border-blue-700">
                </div>

                <div>
                    <label for="logo" class="block text-base font-bold text-slate-900 mb-2">
                        Hospital Logo (Embedded as Base64 on Property Tags)
                    </label>
                    <div class="flex items-center gap-4 mb-3">
                        <div class="p-2 border border-slate-300 rounded-lg bg-slate-50">
                            <img src="{{ $logoDataUri }}" alt="Current Hospital Logo" class="w-14 h-14 object-contain">
                        </div>
                        <div class="text-sm text-slate-600">
                            Upload a PNG, JPG, or SVG logo to replace the current emblem on printed tags.
                        </div>
                    </div>
                    <input id="logo"
                           name="logo"
                           type="file"
                           accept=".png,.jpg,.jpeg,.svg,.webp"
                           class="w-full px-4 py-2.5 text-base border-2 border-slate-300 rounded-lg bg-white">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="asset_no_prefix" class="block text-base font-bold text-slate-900 mb-2">
                            Asset No. Prefix <span class="text-red-600">*</span>
                        </label>
                        <input id="asset_no_prefix"
                               name="asset_no_prefix"
                               type="text"
                               required
                               value="{{ old('asset_no_prefix', $assetNoPrefix) }}"
                               class="w-full px-4 py-3 text-lg font-mono font-bold border-2 border-slate-300 rounded-lg focus:outline-none focus:border-blue-700">
                    </div>

                    <div>
                        <label for="tag_width_cm" class="block text-base font-bold text-slate-900 mb-2">
                            Tag Width (cm) <span class="text-red-600">*</span>
                        </label>
                        <input id="tag_width_cm"
                               name="tag_width_cm"
                               type="number"
                               step="0.1"
                               min="3"
                               max="30"
                               required
                               value="{{ old('tag_width_cm', $tagWidthCm) }}"
                               class="w-full px-4 py-3 text-lg border-2 border-slate-300 rounded-lg focus:outline-none focus:border-blue-700">
                    </div>

                    <div>
                        <label for="tag_height_cm" class="block text-base font-bold text-slate-900 mb-2">
                            Tag Height (cm) <span class="text-red-600">*</span>
                        </label>
                        <input id="tag_height_cm"
                               name="tag_height_cm"
                               type="number"
                               step="0.1"
                               min="2"
                               max="30"
                               required
                               value="{{ old('tag_height_cm', $tagHeightCm) }}"
                               class="w-full px-4 py-3 text-lg border-2 border-slate-300 rounded-lg focus:outline-none focus:border-blue-700">
                    </div>
                </div>

                <div>
                    <label for="backup_folder" class="block text-base font-bold text-slate-900 mb-2">
                        Backup Folder Path
                    </label>
                    <input id="backup_folder"
                           name="backup_folder"
                           type="text"
                           value="{{ old('backup_folder', $backupFolder) }}"
                           class="w-full px-4 py-3 text-base font-mono border-2 border-slate-300 rounded-lg focus:outline-none focus:border-blue-700">
                    <p class="text-xs text-slate-500 mt-1">
                        Leave blank to use the default <code>Documents\BethelBackups</code> folder.
                    </p>
                </div>

                <div class="pt-4 border-t border-slate-200">
                    <button type="submit"
                            class="px-6 py-3.5 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-bold text-lg shadow-sm transition cursor-pointer">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>

        <!-- SECTION 3: Admin User Accounts Management -->
        @if (auth()->user()?->isAdmin())
            <div class="p-6 bg-white border-2 border-slate-200 rounded-xl max-w-3xl">
                <h3 class="text-2xl font-bold text-slate-900 mb-2">User Accounts (Admin Only)</h3>
                <p class="text-base text-slate-600 mb-6 pb-4 border-b border-slate-200">
                    Manage Admin and Staff login accounts for this PC.
                </p>

                <div class="mb-6 border border-slate-200 rounded-lg overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-100 border-b border-slate-200 text-xs font-bold uppercase text-slate-700">
                                <th class="p-3">Name</th>
                                <th class="p-3">Email</th>
                                <th class="p-3">Role</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-base">
                            @foreach ($users as $u)
                                <tr>
                                    <td class="p-3 font-semibold text-slate-900">{{ $u->name }}</td>
                                    <td class="p-3 font-mono text-sm text-slate-700">{{ $u->email }}</td>
                                    <td class="p-3 uppercase text-xs font-bold text-blue-700">{{ $u->role }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <h4 class="text-lg font-bold text-slate-900 mb-4">Add Staff or Admin User</h4>
                <form method="POST" action="{{ route('settings.users.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="user_name" class="block text-base font-bold text-slate-900 mb-1">Full Name <span class="text-red-600">*</span></label>
                        <input id="user_name" name="name" type="text" required class="w-full px-4 py-2.5 text-base border-2 border-slate-300 rounded-lg">
                    </div>
                    <div>
                        <label for="user_email" class="block text-base font-bold text-slate-900 mb-1">Email / Login ID <span class="text-red-600">*</span></label>
                        <input id="user_email" name="email" type="email" required placeholder="e.g. staff@hospital.local" class="w-full px-4 py-2.5 text-base border-2 border-slate-300 rounded-lg">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="user_role" class="block text-base font-bold text-slate-900 mb-1">Role <span class="text-red-600">*</span></label>
                            <select id="user_role" name="role" class="w-full px-4 py-2.5 text-base border-2 border-slate-300 rounded-lg bg-white">
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div>
                            <label for="user_password" class="block text-base font-bold text-slate-900 mb-1">Temporary Password <span class="text-red-600">*</span></label>
                            <input id="user_password" name="password" type="password" required minlength="8" class="w-full px-4 py-2.5 text-base border-2 border-slate-300 rounded-lg">
                        </div>
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="px-5 py-3 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-bold text-base cursor-pointer">
                            Create User Account
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endsection
