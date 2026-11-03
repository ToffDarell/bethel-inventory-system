<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Setting;
use App\Models\User;
use App\Services\BackupService;
use App\Services\TagAssetService;
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
            'hospitalAddress' => Setting::get('hospital_address', 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700'),
            'assetNoPrefix' => Setting::get('asset_no_prefix', 'BBH'),
            'tagWidthCm' => Setting::get('tag_width_cm', '10'),
            'tagHeightCm' => Setting::get('tag_height_cm', '6'),
            'showBarcode' => Setting::get('show_barcode', '0') === '1',
            'backupFolder' => $this->backupService->getBackupFolder(),
            'logoDataUri' => $this->tagAssetService->hospitalLogoBase64(),
            'tagAssetService' => $this->tagAssetService,
            'sampleAsset' => Asset::first() ?? new Asset([
                'asset_no' => 'BBHIND-S300002315',
                'asset_name' => 'Automated External Defibrillator with Monitor',
                'status' => 'In Use',
                'model' => 'HeartStart FRx',
                'serial_no' => 'SN-998822',
                'location' => 'Emergency Room',
                'department' => 'Emergency Room',
            ]),
            'backups' => $backups,
            'backupOverdue' => $backupOverdue,
            'activeDatabasePath' => $this->backupService->getDatabasePath(),
            'users' => $request->user()?->isAdmin() ? User::orderBy('name')->get() : collect(),
        ]);
    }

    public function update(Request $request)
    {
        if ($request->has('show_barcode') && ! $request->user()?->isAdmin()) {
            abort(403, 'Unauthorized. Only administrators can change property tag barcode settings.');
        }

        $validated = $request->validate([
            'hospital_name' => ['required', 'string', 'max:255'],
            'hospital_address' => ['required', 'string', 'max:500'],
            'asset_no_prefix' => ['required', 'string', 'max:30'],
            'tag_width_cm' => ['required', 'numeric', 'min:3', 'max:30'],
            'tag_height_cm' => ['required', 'numeric', 'min:2', 'max:30'],
            'show_barcode' => ['nullable', 'in:0,1'],
            'backup_folder' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,webp', 'max:5120'],
        ]);

        Setting::set('hospital_name', trim($validated['hospital_name']));
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

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            Setting::set('logo_path', $path);
        }

        activity()
            ->causedBy($request->user())
            ->log('Updated hospital and property tag settings.');

        return redirect()
            ->route('settings.index')
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
                            ->route('settings.index')
                            ->with('success', "Backup folder updated to: {$folder}");
                    }
                }

                return redirect()->route('settings.index');
            } catch (Throwable) {
                // Fall through if dialog is unavailable
            }
        }

        return redirect()
            ->route('settings.index')
            ->with('success', 'You can type or paste your backup folder path in the Backup Folder field below and click Save Settings.');
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
                ->route('settings.index')
                ->with('success', 'Backup created successfully: ' . basename($createdPath));
        } catch (Throwable $e) {
            return redirect()
                ->route('settings.index')
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
                ->route('settings.index')
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
                ->route('settings.index')
                ->with('restart_required', true)
                ->with(
                    'success',
                    'Database restored successfully! Your previous database was backed up as "' . basename($result['pre_restore_backup']) . '". Please restart the application now.'
                );
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('settings.index')
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

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(['admin', 'staff'])],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'must_change_password' => true,
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->log("Created {$user->role} user account: {$user->email}");

        return redirect()
            ->route('settings.index')
            ->with('success', "User account \"{$user->name}\" ({$user->email}) created. They will be asked to set a new password on first login.");
    }
}
