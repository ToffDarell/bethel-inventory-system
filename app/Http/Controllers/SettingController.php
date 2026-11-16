<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetTemplate;
use App\Models\Category;
use App\Models\Department;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vendor;
use App\Services\BackupService;
use App\Services\TagAssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Native\Desktop\Dialog;
use Native\Desktop\Facades\App as NativeApp;
use Throwable;

class SettingController extends Controller
{
    public function __construct(
        protected BackupService $backupService,
        protected TagAssetService $tagAssetService,
    ) {}

    public function index(Request $request)
    {
        $backups = $this->backupService->listBackups();
        $backupOverdue = $this->backupService->isBackupOverdue(7);

        return view('settings.index', [
            'hospitalName' => Setting::get('hospital_name', 'Bethel Baptist Hospital, Inc.'),
            'hospitalShortName' => Setting::get('hospital_short_name', 'BBH'),
            'hospitalAddress' => Setting::get('hospital_address', 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700'),
            'assetNoPrefix' => Setting::get('asset_no_prefix', 'BBHI'),
            'tagWidthCm' => Setting::get('tag_width_cm', '10'),
            'tagHeightCm' => Setting::get('tag_height_cm', '6'),
            'showBarcode' => Setting::get('show_barcode', '0') === '1',
            'backupFolder' => $this->backupService->getBackupFolder(),
            'logoDataUri' => $this->tagAssetService->hospitalLogoBase64(),
            'tagAssetService' => $this->tagAssetService,
            'sampleAsset' => new Asset([
                'asset_no' => (Setting::get('asset_no_prefix', 'BBHI') ?: 'BBHI') . '-SURG-OR2-2026-00049281',
                'asset_name' => 'Multi-Parameter Vital Signs Monitor with Integrated Capnography & Defibrillator Module',
                'status' => 'In Use',
                'model' => 'Mindray BeneVision N17 Premium Series (High-Acuity ICU / OR)',
                'serial_no' => 'SN-MND-98427189-PH-2026',
                'location' => 'Suite 3',
                'department' => 'Surgery & Anesthesia',
                'section' => 'Operating Room 2',
                'date_acquired' => now()->subYears(2),
            ]),
            'backups' => $backups,
            'backupOverdue' => $backupOverdue,
            'activeDatabasePath' => $request->user()?->isAdmin() ? $this->backupService->getDatabasePath() : null,
            'users' => $request->user()?->isAdmin() ? User::orderBy('name')->get() : collect(),
            'categories' => Category::withCount('assets')->orderBy('name')->get(),
            'departments' => Department::withCount([
                'assets',
                'assets as trashed_assets_count' => fn ($q) => $q->onlyTrashed(),
            ])->orderBy('name')->get(),
            'sections' => Section::with('department')->withCount([
                'assets',
                'assets as trashed_assets_count' => fn ($q) => $q->onlyTrashed(),
            ])->orderBy('department_id')->orderBy('name')->get(),
            'vendorSearch' => trim((string) $request->query('vendor_search', '')),
            'vendors' => $request->user()?->isAdmin()
                ? Vendor::query()
                    ->withCount([
                        'assets',
                        'assets as trashed_assets_count' => fn ($q) => $q->onlyTrashed(),
                    ])
                    ->when(trim((string) $request->query('vendor_search', '')) !== '', function ($q) use ($request) {
                        $term = '%' . trim((string) $request->query('vendor_search', '')) . '%';
                        $q->where(function ($sub) use ($term) {
                            $sub->where('name', 'like', $term)
                                ->orWhere('contact_person', 'like', $term)
                                ->orWhere('phone', 'like', $term)
                                ->orWhere('address', 'like', $term);
                        });
                    })
                    ->orderByDesc('active')
                    ->orderBy('name')
                    ->get()
                : collect(),
            'templateSearch' => trim((string) $request->query('template_search', '')),
            'assetTemplates' => $request->user()?->isAdmin()
                ? AssetTemplate::query()
                    ->with('category:id,name')
                    ->withCount([
                        'assets',
                        'assets as trashed_assets_count' => fn ($q) => $q->onlyTrashed(),
                    ])
                    ->when(trim((string) $request->query('template_search', '')) !== '', function ($q) use ($request) {
                        $term = '%' . trim((string) $request->query('template_search', '')) . '%';
                        $q->where(function ($sub) use ($term) {
                            $sub->where('name', 'like', $term)
                                ->orWhere('brand', 'like', $term)
                                ->orWhere('model', 'like', $term)
                                ->orWhere('default_remarks', 'like', $term)
                                ->orWhereHas('category', fn ($catQ) => $catQ->where('name', 'like', $term));
                        });
                    })
                    ->orderByDesc('active')
                    ->orderBy('name')
                    ->orderBy('brand')
                    ->get()
                : collect(),
            'reassignNumberOnDeptChange' => Setting::get('reassign_number_on_dept_change', '1') === '1',
            'includeSectionInAssetNo' => Setting::get('include_section_in_asset_no', '1') === '1',
            'assetNumberDigits' => (int) Setting::get('asset_number_digits', 4),
            'assetNumberScope' => (string) Setting::get('asset_number_scope', 'department'),
            'depreciationUseCategoryRules' => app(\App\Services\DepreciationService::class)->isCategoryRuleMode(),
            'uncategorizedAssetsCount' => Asset::whereNull('category_id')->count(),
            'depreciationStartConvention' => Setting::get('depreciation_start_convention', 'january_1'),
            'depreciationCustomMonth' => (int) Setting::get('depreciation_custom_month', 1),
            'depreciationCustomDay' => (int) Setting::get('depreciation_custom_day', 1),
            'depreciationDefaultSalvagePercent' => Setting::get('depreciation_default_salvage_percent', '0'),
            'depreciationMinCost' => Setting::get('depreciation_min_cost', '0.00'),
            'pdfPaperSize' => Setting::get('pdf_paper_size', 'a4'),
        ]);
    }

    public function update(Request $request)
    {
        if ($request->has('show_barcode') && ! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can change property tag barcode settings.');
        }

        if ($request->has('short_name') && ! $request->has('hospital_short_name')) {
            $request->merge(['hospital_short_name' => $request->input('short_name')]);
        }

        if ($request->has('hospital_short_name') && is_string($request->input('hospital_short_name'))) {
            $request->merge(['hospital_short_name' => trim($request->input('hospital_short_name'))]);
        }

        $validated = $request->validate([
            'hospital_name' => ['required', 'string', 'max:255'],
            'hospital_short_name' => ['sometimes', 'required', 'string', 'min:2', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
            'hospital_address' => ['required', 'string', 'max:500'],
            'asset_no_prefix' => ['required', 'string', 'max:30'],
            'tag_width_cm' => ['required', 'numeric', 'min:3', 'max:30'],
            'tag_height_cm' => ['required', 'numeric', 'min:2', 'max:30'],
            'show_barcode' => ['nullable', 'in:0,1'],
            'backup_folder' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,webp', 'max:5120'],
            'pdf_paper_size' => ['nullable', 'string', 'in:a4,letter,legal,A4,LETTER,LEGAL'],
        ], [
            'hospital_short_name.required' => 'Please enter a short name (2 to 10 letters or numbers).',
            'hospital_short_name.min' => 'Short name must be between 2 and 10 characters.',
            'hospital_short_name.max' => 'Short name must be between 2 and 10 characters.',
            'hospital_short_name.regex' => 'Short name may only contain letters and numbers.',
        ]);

        Setting::set('hospital_name', trim($validated['hospital_name']));
        if (array_key_exists('hospital_short_name', $validated)) {
            Setting::set('hospital_short_name', trim($validated['hospital_short_name']));
        }
        Setting::set('hospital_address', trim($validated['hospital_address']));
        Setting::set('asset_no_prefix', trim($validated['asset_no_prefix']));
        Setting::set('tag_width_cm', (string) $validated['tag_width_cm']);
        Setting::set('tag_height_cm', (string) $validated['tag_height_cm']);

        if ($request->user()?->isAdmin() && $request->has('show_barcode')) {
            $newBarcode = $request->input('show_barcode') === '1' ? '1' : '0';
            $oldBarcode = Setting::get('show_barcode', '0');

            if ($newBarcode !== $oldBarcode) {
                Setting::set('show_barcode', $newBarcode);
                $statusWord = $newBarcode === '1' ? 'ON' : 'OFF';
                activity()
                    ->causedBy($request->user())
                    ->log("Barcode on tag turned {$statusWord} by {$request->user()->name}");
            }
        }

        $backupFolder = isset($validated['backup_folder']) && trim($validated['backup_folder']) !== ''
            ? trim($validated['backup_folder'])
            : Setting::defaultBackupFolder();
        Setting::set('backup_folder', $backupFolder);

        if ($request->has('pdf_paper_size')) {
            Setting::set('pdf_paper_size', strtolower(trim((string) $request->input('pdf_paper_size', 'a4'))));
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            Setting::set('logo_path', $path);
        }

        activity()
            ->causedBy($request->user())
            ->log('Updated hospital and property tag settings.');

        $activeTab = $request->input('active_tab');
        $redirectParams = is_string($activeTab) && $activeTab !== '' && $activeTab !== 'hospital'
            ? ['tab' => $activeTab]
            : [];

        return redirect()
            ->route('settings.index', $redirectParams)
            ->with('success', 'Settings saved successfully.');
    }

    public function pickFolder(Request $request)
    {
        if (config('nativephp-internal.running')) {
            try {
                $selected = Dialog::new()
                    ->title('Select Backup Folder for Bethel Asset Tracker')
                    ->folders()
                    ->open();

                if (! empty($selected)) {
                    $folder = is_array($selected) ? ($selected[0] ?? null) : $selected;
                    if (is_string($folder) && trim($folder) !== '') {
                        Setting::set('backup_folder', trim($folder));

                        return redirect()
                            ->route('settings.index', ['tab' => 'backup'])
                            ->with('success', "Backup folder updated to: {$folder}");
                    }
                }

                return redirect()->route('settings.index', ['tab' => 'backup']);
            } catch (Throwable) {
                // Fall through if dialog is unavailable
            }
        }

        // In local Windows web mode (XAMPP / php artisan serve), open a native Windows FolderBrowserDialog via PowerShell
        if (PHP_OS_FAMILY === 'Windows' && ! app()->runningUnitTests()) {
            try {
                $psScript = 'Add-Type -AssemblyName System.Windows.Forms; '
                    . '$f = New-Object System.Windows.Forms.FolderBrowserDialog; '
                    . '$f.Description = "Select Backup Folder for Bethel Asset Tracker"; '
                    . '$f.ShowNewFolderButton = $true; '
                    . 'if ($f.ShowDialog() -eq [System.Windows.Forms.DialogResult]::OK) { Write-Output $f.SelectedPath }';
                $cmd = 'powershell -NoProfile -STA -Command ' . escapeshellarg($psScript);
                $output = trim((string) @shell_exec($cmd));
                if ($output !== '' && is_dir($output)) {
                    Setting::set('backup_folder', $output);

                    return redirect()
                        ->route('settings.index', ['tab' => 'backup'])
                        ->with('success', "Backup folder updated to: {$output}");
                }
            } catch (Throwable) {
                // Fall through to manual input prompt on the Backup tab
            }
        }

        return redirect()
            ->route('settings.index', ['tab' => 'backup'])
            ->with('show_backup_folder_editor', true)
            ->with('success', 'You can type or paste your backup folder path in the Backup Folder field below and click Save Backup Folder.');
    }

    public function backup(Request $request)
    {
        try {
            $createdPath = $this->backupService->createBackup('manual');

            activity()
                ->causedBy($request->user())
                ->withProperties(['backup_file' => basename($createdPath)])
                ->log('Created manual database backup: ' . basename($createdPath));

            return redirect()
                ->route('settings.index', ['tab' => 'backup'])
                ->with('success', 'Backup created successfully: ' . basename($createdPath));
        } catch (Throwable $e) {
            return redirect()
                ->route('settings.index', ['tab' => 'backup'])
                ->withErrors(['backup' => 'Could not create backup: ' . $e->getMessage()]);
        }
    }

    public function restore(Request $request)
    {
        $request->validate([
            'backup_filename' => ['nullable', 'string'],
            'backup_file' => ['nullable', 'file'],
        ]);

        $sourcePath = null;
        $tempUploadedPath = null;

        if ($request->hasFile('backup_file')) {
            $tempUploadedPath = $request->file('backup_file')->getRealPath();
            $sourcePath = $tempUploadedPath;
        } elseif ($request->filled('backup_filename')) {
            $filename = basename((string) $request->input('backup_filename'));
            $sourcePath = $this->backupService->getBackupFolder() . DIRECTORY_SEPARATOR . $filename;
        }

        if (empty($sourcePath)) {
            return redirect()
                ->route('settings.index', ['tab' => 'backup'])
                ->withErrors(['restore' => 'Please select a backup from the list or choose a .sqlite backup file to restore.']);
        }

        try {
            $result = $this->backupService->restoreFromFile($sourcePath);

            activity()
                ->causedBy($request->user())
                ->withProperties([
                    'restored_from' => basename($sourcePath),
                    'pre_restore_backup' => basename($result['pre_restore_backup']),
                ])
                ->log('Restored database from backup: ' . basename($sourcePath));

            if (config('nativephp-internal.running')) {
                try {
                    NativeApp::relaunch();
                } catch (Throwable) {
                    // Fall back to UI restart banner
                }
            }

            return redirect()
                ->route('settings.index', ['tab' => 'backup'])
                ->with('restart_required', true)
                ->with(
                    'success',
                    'Database restored successfully! Your previous database was backed up as "' . basename($result['pre_restore_backup']) . '". Please restart the application now.'
                );
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('settings.index', ['tab' => 'backup'])
                ->withErrors(['restore' => $e->getMessage()]);
        }
    }

    public function relaunch()
    {
        if (config('nativephp-internal.running')) {
            try {
                NativeApp::relaunch();
            } catch (Throwable) {
                // Ignore
            }
        }

        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Application session restarted. Please sign in.');
    }

    public function storeUser(Request $request): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can add user accounts.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('users', 'username'),
            ],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(['admin', 'staff'])],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'name.required' => 'Full name is required.',
            'username.required' => 'Please enter a username.',
            'username.min' => 'Username must be at least 3 characters.',
            'username.max' => 'Username may not be greater than 30 characters.',
            'username.regex' => 'Username may only contain letters, numbers, dots, dashes, and underscores.',
            'username.unique' => 'This username is already taken.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already in use.',
            'role.required' => 'Please select a system role.',
            'password.required' => 'Temporary password is required.',
            'password.min' => 'Temporary password must be at least 8 characters.',
        ]);

        $user = User::create([
            'name' => trim($validated['name']),
            'username' => strtolower(trim($validated['username'])),
            'email' => ! empty(trim($validated['email'] ?? '')) ? strtolower(trim($validated['email'])) : null,
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'must_change_password' => true,
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->log("Created {$user->role} user account: {$user->username}");

        return redirect()
            ->route('settings.index', ['tab' => 'users'])
            ->with('success', "User account \"{$user->name}\" ({$user->username}) created. They will be asked to set a new password on first login.");
    }

    public function resetUserPassword(Request $request, User $user): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can reset user passwords.');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ], [
            'password.required' => 'Temporary password is required.',
            'password.min' => 'Temporary password must be at least 8 characters.',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => true,
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->log("Reset temporary password for user {$user->username}");

        return redirect()
            ->route('settings.index', ['tab' => 'users'])
            ->with('success', "Temporary password for {$user->name} ({$user->username}) has been updated. They must change it on their next login.");
    }

    public function updateUserRole(Request $request, User $user): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can change user roles.');
        }

        $validated = $request->validate([
            'role' => ['required', Rule::in(['admin', 'staff'])],
        ]);

        $newRole = $validated['role'];

        if ($user->role === $newRole) {
            return redirect()->route('settings.index', ['tab' => 'users']);
        }

        if ($user->isAdmin() && $newRole !== 'admin' && User::where('role', 'admin')->count() <= 1) {
            return redirect()
                ->route('settings.index', ['tab' => 'users'])
                ->with('error', 'Cannot demote the last administrator account. Assign another administrator first.');
        }

        $oldRole = $user->role;
        $user->update(['role' => $newRole]);

        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->log("Changed role for {$user->username} from {$oldRole} to {$newRole}");

        return redirect()
            ->route('settings.index', ['tab' => 'users'])
            ->with('success', "Role updated for {$user->name} ({$user->username}).");
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can remove user accounts.');
        }

        if ($user->id === $request->user()->id) {
            return redirect()
                ->route('settings.index', ['tab' => 'users'])
                ->with('error', 'You cannot delete the account you are currently signed in with.');
        }

        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return redirect()
                ->route('settings.index', ['tab' => 'users'])
                ->with('error', 'Cannot delete the last administrator account.');
        }

        $username = $user->username;
        $name = $user->name;
        $role = $user->role;

        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->log("Deleted {$role} user account: {$name} ({$username})");

        $user->delete();

        return redirect()
            ->route('settings.index', ['tab' => 'users'])
            ->with('success', "User account \"{$name}\" ({$username}) was deleted.");
    }

    public function regenerateRecoveryCode(Request $request, User $user): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can generate recovery codes.');
        }

        if (! $user->isAdmin()) {
            return redirect()
                ->route('settings.index', ['tab' => 'users'])
                ->with('error', 'Recovery codes are only issued for administrator accounts.');
        }

        $code = User::generateRecoveryCode();
        $user->update([
            'recovery_code_hash' => Hash::make($code),
            'recovery_code_generated_at' => now(),
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->log("Regenerated admin recovery code for {$user->username}");

        return redirect()
            ->route('settings.index', ['tab' => 'users'])
            ->with('success', "New recovery code generated for {$user->username}. Save it now because it will not be shown again.")
            ->with('admin_recovery_code_once', $code);
    }

    public function updateDepreciation(Request $request): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can change depreciation policy.');
        }

        $validated = $request->validate([
            'depreciation_start_convention' => ['required', 'string', 'in:january_1,july_1,december_31,custom'],
            'depreciation_custom_month' => ['nullable', 'required_if:depreciation_start_convention,custom', 'integer', 'min:1', 'max:12'],
            'depreciation_custom_day' => ['nullable', 'required_if:depreciation_start_convention,custom', 'integer', 'min:1', 'max:31'],
            'depreciation_default_salvage_percent' => ['required', 'numeric', 'min:0', 'max:90'],
            'depreciation_min_cost' => ['required', 'numeric', 'min:0'],
        ], [
            'depreciation_start_convention.required' => 'Please select a valid start convention.',
            'depreciation_start_convention.in' => 'Please select a valid start convention.',
            'depreciation_custom_month.required_if' => 'Please select a valid month for the custom start date.',
            'depreciation_custom_day.required_if' => 'Please enter a valid day for the custom start date.',
            'depreciation_default_salvage_percent.required' => 'Default disposal percent is required.',
            'depreciation_default_salvage_percent.numeric' => 'Default disposal percent must be a number.',
            'depreciation_default_salvage_percent.min' => 'Default disposal percent cannot be negative.',
            'depreciation_default_salvage_percent.max' => 'Default disposal percent cannot exceed 90%.',
            'depreciation_min_cost.required' => 'Minimum cost to depreciate is required.',
            'depreciation_min_cost.numeric' => 'Minimum cost to depreciate must be a number.',
            'depreciation_min_cost.min' => 'Minimum cost to depreciate cannot be negative.',
        ]);

        if ($validated['depreciation_start_convention'] === 'custom') {
            $month = (int) $validated['depreciation_custom_month'];
            $day = (int) $validated['depreciation_custom_day'];
            if (! checkdate($month, $day, 2024)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['depreciation_custom_day' => 'The selected month and day do not form a valid calendar date.']);
            }
            Setting::set('depreciation_custom_month', (string) $month);
            Setting::set('depreciation_custom_day', (string) $day);
        }

        $settings = [
            'depreciation_start_convention' => [
                'name' => 'Depreciation start convention',
                'new' => $validated['depreciation_start_convention'],
                'default' => 'january_1',
            ],
            'depreciation_default_salvage_percent' => [
                'name' => 'Default disposal percent',
                'new' => (string) round((float) $validated['depreciation_default_salvage_percent'], 2),
                'default' => '0',
            ],
            'depreciation_min_cost' => [
                'name' => 'Minimum cost to depreciate',
                'new' => (string) round((float) $validated['depreciation_min_cost'], 2),
                'default' => '0.00',
            ],
            'depreciation_use_category_rules' => [
                'name' => 'Category-driven depreciation rules',
                'new' => $request->boolean('depreciation_use_category_rules') ? '1' : '0',
                'default' => '0',
            ],
        ];

        foreach ($settings as $key => $meta) {
            $oldValue = Setting::get($key, $meta['default']);
            $newValue = $meta['new'];

            if ($oldValue !== $newValue) {
                Setting::set($key, $newValue);
                $oldDisplay = $oldValue;
                $newDisplay = $newValue;
                if ($newValue === 'custom') {
                    $customMonth = (int) ($validated['depreciation_custom_month'] ?? 1);
                    $customDay = (int) ($validated['depreciation_custom_day'] ?? 1);
                    $dateName = \Carbon\Carbon::create(2000, $customMonth, $customDay)->format('F j');
                    $newDisplay = "custom ({$dateName})";
                }
                activity()
                    ->causedBy($request->user())
                    ->log("{$meta['name']} changed from '{$oldDisplay}' to '{$newDisplay}' by {$request->user()->name}");
            }
        }

        return redirect()
            ->route('settings.index', ['tab' => 'depreciation'])
            ->with('success', 'Depreciation policy updated successfully.');
    }
}
