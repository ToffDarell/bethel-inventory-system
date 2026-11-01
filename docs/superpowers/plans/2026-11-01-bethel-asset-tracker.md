# Bethel Asset Tracker Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build an offline, single-PC Windows desktop property asset tracker and QR tag printer for Bethel Baptist Hospital, Inc. using Laravel 13, NativePHP Desktop v2 (Electron), and SQLite.

**Architecture:** A server-rendered Laravel 13 + Blade + Tailwind CSS v4 (bundled locally via Vite) application running inside NativePHP Desktop v2 with a local SQLite database stored in the OS user data folder outside the application bundle. Business logic is isolated into focused service classes (`AssetNumberGenerator`, `QrCodeService`, `BackupService`, `AssetImportService`), audited with `spatie/laravel-activitylog`, and protected by additive-only migrations with automatic pre-migration and 24-hour startup backups.

**Tech Stack:** PHP 8.3+ (8.5 runtime), Laravel 13 (`^13.17`), NativePHP Desktop v2 (`nativephp/desktop ^2.4`), SQLite, Tailwind CSS v4 (`@tailwindcss/vite`), `chillerlan/php-qrcode ^5.0` (maintained PHP 8.3–8.5 QR generator replacing unmaintained `simplesoftwareio/simple-qrcode`), `barryvdh/laravel-dompdf ^3.1`, `maatwebsite/excel ^4.0`, `spatie/laravel-activitylog ^4.12`, Pest v5.

**Spec:** Inline specification in conversation prompt (Bethel Asset Tracker — Modules 1–8).

## Package Compatibility Verification (Laravel 13 & PHP 8.3+ / 8.5)

1. **`simplesoftwareio/simple-qrcode` (`v4.2.0`) -> Replace with `chillerlan/php-qrcode` (`^5.0`):**
   - *Why:* `simplesoftwareio/simple-qrcode` has been unmaintained since 2021 and locks `bacon/bacon-qr-code` to `^2.0` (`2.0.8`), which contains implicit nullable parameter types (`ErrorCorrectionLevel $ecLevel = null`) that trigger `E_DEPRECATED` exceptions on PHP 8.4/8.5. Additionally, its PNG backend requires `ext-imagick`, which is not compiled into NativePHP's bundled static `php.exe` (only `ext-gd` is bundled).
   - *Alternative:* `chillerlan/php-qrcode: ^5.0` is actively maintained for PHP 8.2–8.5+, has zero Laravel version conflicts, and natively renders both crisp inline `<svg>` and base64 SVG/PNG (`ext-gd`) with zero external dependencies.
2. **`barryvdh/laravel-dompdf` (`^3.1`, `v3.1.2`) -> Compatible:**
   - Officially supports `illuminate/support ^13.0` and `php ^8.1`. Includes `dompdf/php-svg-lib`, enabling embedded base64 SVG QR codes in PDF downloads.
3. **`maatwebsite/excel` (`^4.0`, `v4.0.6`) -> Compatible:**
   - Officially supports `illuminate/support ^12.0 || ^13.0` and `php ^8.3` with `phpoffice/phpspreadsheet ^5.9`.
4. **`spatie/laravel-activitylog` (`^4.12`, `v4.12.3` / `^5.1`, `v5.1.3`) -> Compatible:**
   - Both `v4.12.3` (`php ^8.1`, `illuminate/support ^13.0`) and `v5.1.3` (`php ^8.4`, `illuminate/support ^13.0`) support Laravel 13. We use `^4.12 || ^5.1` (`spatie/laravel-activitylog`).
5. **Roles (`users.role`):**
   - Plain string column on `users` (`'admin'` or `'staff'`), no `spatie/laravel-permission`.

## User Adjustments (2026-11-01, binding — override anything below that conflicts)

1. `spatie/laravel-activitylog` pinned to `^4.12` (not v5).
2. After Module 1: run `php artisan native:build win` once, install the produced setup `.exe` to a clean path, verify it launches, then continue.
3. Default backup folder = `<Documents>\BethelBackups` when `backup_folder` setting is empty. Settings page shows a warning when the newest backup is older than 7 days (or none exists).
4. Restore flow: (a) auto-backup the current DB, (b) validate the chosen file (SQLite header + `PRAGMA integrity_check` on the file), (c) copy it in, (d) show "Restart required" and relaunch the app (`App::relaunch()` under NativePHP).
5. `assets` uses soft deletes (`deleted_at`, `SoftDeletes` trait). Soft-deleted `asset_no` values still count as taken for uniqueness.
6. Hospital logo is always embedded in tags as a base64 data URI (print view and PDF).
7. Production SQLite lives in `%APPDATA%\<app>\database\database.sqlite` (Electron `userData`), outside the install folder; verified after the Module 1 build.

## Global Constraints

- **Framework & UI:** Laravel 13, Blade + Tailwind CSS v4 bundled locally via Vite. No CDNs, no external fonts (system font stack only in `resources/css/app.css`), no external icon libraries (inline SVG only).
- **Database:** SQLite only. No `ENUM`, no `FULLTEXT`, no MySQL-only features. String column for `assets.status` (`'In Use'`, `'Under Repair'`, `'Stored'`, `'Disposed'`).
- **Offline Operation:** 100% offline desktop execution on one Windows PC; no LAN, no HTTPS setup, no phone camera scanning.
- **Update & Data Safety:** Migrations must be strictly additive (`Schema::create` or nullable `Schema::table` additions). Never run `migrate:fresh` or drop tables in app code. Never bundle `database/*.sqlite` into the production build.
- **Workflow Gate:** Execute one module (Task) at a time, run migrations and tests, present the verification checklist for that module, and wait for the user to say `"next"`.

## Review Focus

1. **Auto-generated `asset_no` collision when manual numbers share the prefix:** When an asset is saved with a blank `asset_no`, `AssetNumberGenerator::generate()` must skip any number already present in `assets.asset_no` and return the next unused `${prefix}-${6-digit padded number}` (or increment past existing numeric suffixes) so a unique constraint error never occurs (tested in Task 2).
2. **First-login password enforcement bypass:** Any authenticated user with `must_change_password === true` must be redirected by `ForcePasswordChangeMiddleware` to `GET /password/change` on every non-logout request until they submit a new password (tested in Task 1).
3. **USB scanner whitespace and case sensitivity:** `GET /scan/lookup?q=...` must `trim()` scanner input and match `asset_no` case-insensitively (`LOWER(asset_no) = LOWER(?)`), redirecting to `assets.show` when found or back to `scan.index` with a friendly message when not found (tested in Task 4).
4. **Excel import duplicate `asset_no` and optional fields (`date_acquired`, `serial_no`):** Import preview and commit must accept blank `date_acquired` and `serial_no`, auto-generate `asset_no` when blank, and skip rows whose `asset_no` duplicates either an existing DB record or an earlier row in the same spreadsheet (tested in Task 5).
5. **Corrupt backup restore & >30 backup retention:** `BackupService::restore()` must verify the 16-byte `"SQLite format 3\0"` file header before overwriting the live database, and `BackupService::pruneOldBackups(30)` must keep the 30 newest `.sqlite` backups and delete older ones (tested in Task 7).

## File Structure

- `app/Models/User.php` — `role` (`'admin'|'staff'`), `must_change_password`, `isAdmin(): bool`.
- `app/Models/Asset.php` — fillable asset fields, `STATUSES` constant, `transfers()` relation, `LogsActivity`.
- `app/Models/Transfer.php` — location transfer history entry, `asset()` and `mover()` relations.
- `app/Models/Setting.php` — key-value helper methods `Setting::get(string $key, mixed $default = null): mixed` and `Setting::set(string $key, mixed $value): void`.
- `app/Http/Middleware/ForcePasswordChangeMiddleware.php` — redirects users with `must_change_password = true` to password change route.
- `app/Http/Middleware/AdminOnlyMiddleware.php` — restricts admin routes (`role === 'admin'`).
- `app/Services/AssetNumberGenerator.php` — generates unique sequential asset numbers using configured prefix.
- `app/Services/QrCodeService.php` — generates inline SVG and base64 data-URI SVG QR codes via `chillerlan/php-qrcode`.
- `app/Services/AssetImportService.php` — parses uploaded Excel/CSV rows, flags duplicates, and commits non-duplicate assets.
- `app/Services/BackupService.php` — handles manual backup, 24h startup backup, pre-migration backup, 30-file retention pruning, and SQLite-validated restore.
- `app/Exports/AssetsExport.php` & `app/Exports/AssetsTemplateExport.php` — `maatwebsite/excel` export classes.
- `app/Http/Controllers/AuthController.php` — login, logout, and forced first-login password change.
- `app/Http/Controllers/DashboardController.php` — summary cards (total, by status, by department, recent assets).
- `app/Http/Controllers/AssetController.php` — CRUD, search, filters, detail view.
- `app/Http/Controllers/TagController.php` — single/bulk print view (`@page` / `@media print`) and optional DomPDF download.
- `app/Http/Controllers/ScanController.php` — USB barcode scanner page and lookup redirect.
- `app/Http/Controllers/TransferController.php` — move asset form/action and history logging.
- `app/Http/Controllers/ImportExportController.php` — Excel template download, import preview, import commit, and filtered export.
- `app/Http/Controllers/ActivityLogController.php` — admin-only activity log viewer.
- `app/Http/Controllers/SettingController.php` — hospital settings, logo upload, tag dimensions, NativePHP folder picker, backup/restore actions, staff user creation.
- `resources/views/layouts/app.blade.php` — clean white layout, high-contrast single primary color (`#1d4ed8`), left sidebar with only Dashboard, Assets, Scan, Reports, Settings.

---

### Task 1 (Module 1): Packages, Migrations, Models, Seeders, Auth, Layout & Sidebar

**Files:**
- Modify: `composer.json`
- Modify: `resources/css/app.css`
- Create: `database/migrations/2026_11_01_000001_add_role_and_force_password_change_to_users_table.php`
- Create: `database/migrations/2026_11_01_000002_create_assets_table.php`
- Create: `database/migrations/2026_11_01_000003_create_transfers_table.php`
- Create: `database/migrations/2026_11_01_000004_create_settings_table.php`
- Modify: `app/Models/User.php`
- Create: `app/Models/Asset.php`
- Create: `app/Models/Transfer.php`
- Create: `app/Models/Setting.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Create: `app/Http/Middleware/ForcePasswordChangeMiddleware.php`
- Create: `app/Http/Middleware/AdminOnlyMiddleware.php`
- Create: `app/Http/Controllers/AuthController.php`
- Create: `resources/views/layouts/app.blade.php`
- Create: `resources/views/auth/login.blade.php`
- Create: `resources/views/auth/change-password.blade.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Module1AuthAndSetupTest.php`

**Interfaces:**
- Produces:
  - `App\Models\User::isAdmin(): bool`
  - `App\Models\Asset` with `public const STATUSES = ['In Use', 'Under Repair', 'Stored', 'Disposed']`, `transfers(): HasMany`
  - `App\Models\Transfer` with `asset(): BelongsTo`, `mover(): BelongsTo`
  - `App\Models\Setting::get(string $key, mixed $default = null): mixed` and `Setting::set(string $key, mixed $value): void`
  - Middleware aliases `'force.password'` (`ForcePasswordChangeMiddleware`) and `'admin'` (`AdminOnlyMiddleware`)

- [ ] **Step 1: Install packages and publish activitylog migration**

Run:
```powershell
composer require chillerlan/php-qrcode:^5.0 barryvdh/laravel-dompdf:^3.1 maatwebsite/excel:^4.0 spatie/laravel-activitylog
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
```
Expected: Composer installs packages cleanly and creates `create_activity_log_table` migration in `database/migrations/`.

- [ ] **Step 2: Write the failing test in `tests/Feature/Module1AuthAndSetupTest.php`**

```php
<?php

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

test('seeder creates default admin requiring password change and hospital settings', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'admin@hospital.local')->first();
    expect($admin)->not->toBeNull()
        ->and($admin->role)->toBe('admin')
        ->and($admin->must_change_password)->toBeTrue()
        ->and(Hash::check('password123', $admin->password))->toBeTrue();

    expect(Setting::get('hospital_name'))->toBe('Bethel Baptist Hospital, Inc.')
        ->and(Setting::get('hospital_address'))->toBe('Fortich St., Sumpong, Malaybalay City, Bukidnon 8700')
        ->and(Setting::get('asset_no_prefix'))->toBe('BBH')
        ->and(Setting::get('tag_width_cm'))->toBe('10')
        ->and(Setting::get('tag_height_cm'))->toBe('6');
});

test('first login forces password change before accessing dashboard', function () {
    $this->seed(DatabaseSeeder::class);

    $this->post('/login', [
        'email' => 'admin@hospital.local',
        'password' => 'password123',
    ])->assertRedirect('/password/change');

    $this->get('/dashboard')->assertRedirect('/password/change');

    $this->post('/password/change', [
        'password' => 'NewHospitalPass1!',
        'password_confirmation' => 'NewHospitalPass1!',
    ])->assertRedirect('/dashboard');

    expect(auth()->user()->fresh()->must_change_password)->toBeFalse();
    $this->get('/dashboard')->assertOk()->assertSee('Dashboard');
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Module1AuthAndSetupTest.php`
Expected: FAIL (`Class "App\Models\Setting" not found`).

- [ ] **Step 4: Implement migrations, models, seeder, auth controller, middleware, and layout**

- Create the 4 additive migrations:
  - `users`: add `string('role')->default('staff')` and `boolean('must_change_password')->default(false)`.
  - `assets`: `id`, `string('asset_no')->unique()`, `string('asset_name')`, `string('model')->nullable()`, `string('serial_no')->nullable()`, `date('date_acquired')->nullable()`, `string('department')->nullable()`, `string('location')->nullable()`, `string('status')->default('In Use')->index()`, `text('notes')->nullable()`, `timestamps()`.
  - `transfers`: `id`, `foreignId('asset_id')->constrained('assets')->cascadeOnDelete()`, `string('from_location')->nullable()`, `string('to_location')`, `foreignId('moved_by')->nullable()->constrained('users')->nullOnDelete()`, `timestamp('moved_at')`, `text('notes')->nullable()`, `timestamps()`.
  - `settings`: `string('key')->primary()`, `text('value')->nullable()`, `timestamps()`.
- Implement `Setting::get(string $key, mixed $default = null): mixed` and `Setting::set(string $key, mixed $value): void` using `Setting::query()->updateOrCreate(['key' => $key], ['value' => $value])`.
- Implement `DatabaseSeeder::run()` using `updateOrCreate` so re-running never duplicates `admin@hospital.local` (default password `'password123'`, `role = 'admin'`, `must_change_password = true`) or existing `settings`.
- Register `'force.password'` and `'admin'` middleware aliases in `bootstrap/app.php`.
- Implement `AuthController` (`showLogin`, `login`, `logout`, `showChangePassword`, `updatePassword`) and `resources/views/layouts/app.blade.php` with the 5-item left sidebar (`Dashboard`, `Assets`, `Scan`, `Reports`, `Settings`) using inline SVGs and local Tailwind CSS (`npm run build`).

- [ ] **Step 5: Run migrations, build assets, and verify tests pass**

Run:
```powershell
php artisan migrate
npm run build
php artisan test --compact tests/Feature/Module1AuthAndSetupTest.php
```
Expected: PASS (2 tests, all assertions passing).

- [ ] **Step 6: Commit Module 1**

```powershell
git add .
git commit -m "feat(module-1): add migrations, models, seeder, auth with forced password change, and sidebar layout"
```

---

### Task 2 (Module 2): Assets CRUD, Auto/Manual Asset No., Search & Filters

**Files:**
- Create: `app/Services/AssetNumberGenerator.php`
- Create: `app/Http/Controllers/AssetController.php`
- Create: `database/factories/AssetFactory.php`
- Create: `resources/views/assets/index.blade.php`
- Create: `resources/views/assets/create.blade.php`
- Create: `resources/views/assets/edit.blade.php`
- Create: `resources/views/assets/show.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Module2AssetCrudTest.php`

**Interfaces:**
- Consumes: `App\Models\Asset`, `App\Models\Setting::get('asset_no_prefix', 'BBH')`
- Produces:
  - `App\Services\AssetNumberGenerator::generate(?string $prefix = null): string` (returns unique asset number e.g. `BBH-000001`, incrementing past any existing collisions)
  - Routes: `assets.index` (`GET /assets`), `assets.create` (`GET /assets/create`), `assets.store` (`POST /assets`), `assets.show` (`GET /assets/{asset}`), `assets.edit` (`GET /assets/{asset}/edit`), `assets.update` (`PUT /assets/{asset}`), `assets.destroy` (`DELETE /assets/{asset}`)

- [ ] **Step 1: Write the failing test in `tests/Feature/Module2AssetCrudTest.php`**

```php
<?php

use App\Models\Asset;
use App\Models\Setting;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'role' => 'admin',
        'must_change_password' => false,
    ]);
    Setting::set('asset_no_prefix', 'BBH');
});

test('creates asset with auto-generated asset_no when blank and skips existing collisions', function () {
    Asset::create([
        'asset_no' => 'BBH-000001',
        'asset_name' => 'Existing Wheelchair',
        'status' => 'In Use',
    ]);

    $this->actingAs($this->user)->post('/assets', [
        'asset_no' => '',
        'asset_name' => 'Patient Monitor',
        'model' => 'Mindray uMEC10',
        'serial_no' => '',
        'date_acquired' => '',
        'department' => 'ICU',
        'location' => 'ND-Station 3 Room 39',
        'status' => 'In Use',
        'notes' => 'Checked by biomed',
    ])->assertRedirect();

    $asset = Asset::where('asset_name', 'Patient Monitor')->first();
    expect($asset)->not->toBeNull()
        ->and($asset->asset_no)->toBe('BBH-000002')
        ->and($asset->serial_no)->toBeNull()
        ->and($asset->date_acquired)->toBeNull();
});

test('allows manual asset_no like BBHIND-S300002315 and filters by search, department, and status', function () {
    $this->actingAs($this->user)->post('/assets', [
        'asset_no' => 'BBHIND-S300002315',
        'asset_name' => 'Samsung Refrigerator',
        'department' => 'Pharmacy',
        'location' => 'Main Pharmacy',
        'status' => 'In Use',
    ])->assertRedirect();

    Asset::create([
        'asset_no' => 'BBH-000099',
        'asset_name' => 'Broken Bed',
        'department' => 'Ward 1',
        'location' => 'Storage Room',
        'status' => 'Under Repair',
    ]);

    $this->actingAs($this->user)
        ->get('/assets?search=BBHIND-S300002315&department=Pharmacy&status=In+Use')
        ->assertOk()
        ->assertSee('Samsung Refrigerator')
        ->assertDontSee('Broken Bed');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Module2AssetCrudTest.php`
Expected: FAIL (404 on `POST /assets`).

- [ ] **Step 3: Implement `AssetNumberGenerator`, `AssetController`, and Blade views**

- `AssetNumberGenerator::generate(?string $prefix = null): string`:
  ```php
  public function generate(?string $prefix = null): string
  {
      $prefix = trim($prefix ?? Setting::get('asset_no_prefix', 'BBH')) ?: 'BBH';
      $count = Asset::count() + 1;

      do {
          $candidate = sprintf('%s-%06d', $prefix, $count);
          $exists = Asset::where('asset_no', $candidate)->exists();
          $count++;
      } while ($exists);

      return $candidate;
  }
  ```
- Implement `AssetController` (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`) with search across `asset_no`, `asset_name`, `model`, `serial_no`, `location`, plus `department` and `status` filters, and 15-per-page pagination (`withQueryString()`).
- Build `resources/views/assets/index.blade.php` (search input, department/status dropdowns, bulk-select checkboxes wired to `GET /tags/print`, colored status badges, "Add Asset" button), single-column `create.blade.php` / `edit.blade.php`, and `show.blade.php` (details, QR tag preview partial, "Print Tag" and "Move Asset" buttons, transfer history table).

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Module2AssetCrudTest.php`
Expected: PASS.

- [ ] **Step 5: Commit Module 2**

```powershell
git add .
git commit -m "feat(module-2): add assets CRUD, unique asset number generator, search, and filters"
```

---

### Task 3 (Module 3): Property Tag Preview, Print Sheet, and DomPDF Download

**Files:**
- Create: `app/Services/QrCodeService.php`
- Create: `app/Http/Controllers/TagController.php`
- Create: `resources/views/tags/partials/tag-card.blade.php`
- Create: `resources/views/tags/print.blade.php`
- Create: `resources/views/tags/pdf.blade.php`
- Modify: `resources/views/assets/show.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Module3PropertyTagTest.php`

**Interfaces:**
- Consumes: `App\Models\Asset`, `App\Models\Setting`
- Produces:
  - `App\Services\QrCodeService::svg(string $text): string` (returns raw `<svg ...>...</svg>` markup)
  - `App\Services\QrCodeService::svgDataUri(string $text): string` (returns `data:image/svg+xml;base64,...` for DomPDF and `<img>` rendering)
  - Routes:
    - `GET /tags/print` (`tags.print`, accepts `?ids[]=1&ids[]=2` or `?asset_id=1`)
    - `GET /tags/pdf` (`tags.pdf`, accepts `?ids[]=1&ids[]=2` or `?asset_id=1`)

- [ ] **Step 1: Write the failing test in `tests/Feature/Module3PropertyTagTest.php`**

```php
<?php

use App\Models\Asset;
use App\Models\Setting;
use App\Models\User;
use App\Services\QrCodeService;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
    Setting::set('hospital_name', 'Bethel Baptist Hospital, Inc.');
    Setting::set('hospital_address', 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700');
    Setting::set('tag_width_cm', '10');
    Setting::set('tag_height_cm', '6');
});

test('renders single and bulk property tags with hospital header, fields, and QR SVG', function () {
    $asset1 = Asset::create([
        'asset_no' => 'BBHIND-S300002315',
        'asset_name' => 'Defibrillator',
        'model' => 'Philips HeartStart',
        'serial_no' => 'SN-998877',
        'date_acquired' => '2026-05-10',
        'location' => 'ND-Station 3 Room 39',
        'status' => 'In Use',
    ]);
    $asset2 = Asset::create([
        'asset_no' => 'BBH-000002',
        'asset_name' => 'Infusion Pump',
        'status' => 'In Use',
    ]);

    $svg = app(QrCodeService::class)->svg('BBHIND-S300002315');
    expect($svg)->toContain('<svg');

    $this->actingAs($this->user)
        ->get('/tags/print?ids[]='.$asset1->id.'&ids[]='.$asset2->id)
        ->assertOk()
        ->assertSee('Bethel Baptist Hospital, Inc.')
        ->assertSee('Fortich St., Sumpong, Malaybalay City, Bukidnon 8700')
        ->assertSee('BBHIND-S300002315')
        ->assertSee('Philips HeartStart')
        ->assertSee('SN-998877')
        ->assertSee('ND-Station 3 Room 39')
        ->assertSee('BBH-000002')
        ->assertSee('width: 10cm')
        ->assertSee('height: 6cm')
        ->assertSee('<svg', false);
});

test('downloads PDF of selected property tags via DomPDF', function () {
    $asset = Asset::create([
        'asset_no' => 'BBHIND-S300002315',
        'asset_name' => 'Defibrillator',
        'status' => 'In Use',
    ]);

    $response = $this->actingAs($this->user)->get('/tags/pdf?ids[]='.$asset->id);
    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Module3PropertyTagTest.php`
Expected: FAIL (`Class "App\Services\QrCodeService" not found`).

- [ ] **Step 3: Implement `QrCodeService`, `TagController`, and bordered hospital property tag views**

- `QrCodeService`: configure `chillerlan\QRCode\QROptions` with `outputType => QROutputInterface::MARKUP_SVG`, `imageBase64 => false` for `svg(string $text): string` and `imageBase64 => true` for `svgDataUri(string $text): string`.
- `resources/views/tags/partials/tag-card.blade.php`: bordered table matching the hospital's paper tag:
  - Header row: optional logo image (`Setting::get('logo_path')` rendered as base64 data URI if file exists), bold `"Bethel Baptist Hospital, Inc."`, and address (`Setting::get('hospital_address')`).
  - Bordered rows for `Date Acquired`, `Asset Name`, `Model`, `Serial No.`, `Location`, `Asset No.`, alongside the QR code cell encoding `$asset->asset_no`.
  - Sized using `width: {{ $tagWidthCm }}cm; height: {{ $tagHeightCm }}cm; break-inside: avoid; page-break-inside: avoid;`.
- `resources/views/tags/print.blade.php`: standalone print sheet with `@page { margin: 0.5cm; }` and `@media print { .no-print { display: none !important; } }`, top toolbar with `"Print Now"` (`window.print()`), `"Download PDF"`, and `"Back to Assets"`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Module3PropertyTagTest.php`
Expected: PASS.

- [ ] **Step 5: Commit Module 3**

```powershell
git add .
git commit -m "feat(module-3): add property tag QR rendering, print sheet, and PDF download"
```

---

### Task 4 (Module 4): Scan Page and Move Asset (Transfers & History)

**Files:**
- Create: `app/Http/Controllers/ScanController.php`
- Create: `app/Http/Controllers/TransferController.php`
- Create: `resources/views/scan/index.blade.php`
- Create: `resources/views/assets/move.blade.php`
- Modify: `resources/views/assets/show.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Module4ScanAndMoveTest.php`

**Interfaces:**
- Consumes: `App\Models\Asset`, `App\Models\Transfer`, `Spatie\Activitylog`
- Produces:
  - `GET /scan` (`scan.index`) — auto-focused scanner input page
  - `GET /scan/lookup` (`scan.lookup`, query param `q`) — redirects to `assets.show` on match, or back to `scan.index` with error flash + scanned code
  - `GET /assets/{asset}/move` (`assets.move.create`) and `POST /assets/{asset}/move` (`assets.move.store`)

- [ ] **Step 1: Write the failing test in `tests/Feature/Module4ScanAndMoveTest.php`**

```php
<?php

use App\Models\Asset;
use App\Models\Transfer;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Nurse Joy', 'role' => 'staff', 'must_change_password' => false]);
});

test('scanner lookup trims input, matches case-insensitively, and handles unknown code', function () {
    $asset = Asset::create([
        'asset_no' => 'BBHIND-S300002315',
        'asset_name' => 'ECG Machine',
        'location' => 'ER Room 1',
        'status' => 'In Use',
    ]);

    $this->actingAs($this->user)
        ->get('/scan/lookup?q=' . urlencode('  bbhind-s300002315 '))
        ->assertRedirect(route('assets.show', $asset));

    $this->actingAs($this->user)
        ->followingRedirects()
        ->get('/scan/lookup?q=UNKNOWN-999')
        ->assertOk()
        ->assertSee('No asset found with Asset No. "UNKNOWN-999"');
});

test('moving an asset updates its location and records transfer history with user and timestamp', function () {
    $asset = Asset::create([
        'asset_no' => 'BBH-000010',
        'asset_name' => 'Wheelchair #4',
        'department' => 'ER',
        'location' => 'ER Triage',
        'status' => 'In Use',
    ]);

    $this->actingAs($this->user)
        ->post(route('assets.move.store', $asset), [
            'to_location' => 'ND-Station 3 Room 39',
            'department' => 'Nursing Station 3',
            'notes' => 'Transferred for patient admission',
        ])
        ->assertRedirect(route('assets.show', $asset));

    expect($asset->fresh()->location)->toBe('ND-Station 3 Room 39')
        ->and($asset->fresh()->department)->toBe('Nursing Station 3');

    $transfer = Transfer::where('asset_id', $asset->id)->first();
    expect($transfer)->not->toBeNull()
        ->and($transfer->from_location)->toBe('ER Triage')
        ->and($transfer->to_location)->toBe('ND-Station 3 Room 39')
        ->and($transfer->moved_by)->toBe($this->user->id)
        ->and($transfer->notes)->toBe('Transferred for patient admission');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Module4ScanAndMoveTest.php`
Expected: FAIL (404 on `/scan/lookup`).

- [ ] **Step 3: Implement `ScanController`, `TransferController`, and views**

- `ScanController::lookup(Request $request)`: `trim((string) $request->query('q'))`, find via `Asset::whereRaw('LOWER(asset_no) = LOWER(?)', [$code])->first()`. If found, redirect to `route('assets.show', $asset)`; otherwise redirect to `route('scan.index')` with `not_found_code` flash message.
- `TransferController::store(Request $request, Asset $asset)`: inside `DB::transaction()`, create `Transfer` record (`from_location => $asset->location`, `to_location => $validated['to_location']`, `moved_by => auth()->id()`, `moved_at => now()`, `notes => $validated['notes'] ?? null`), update `$asset->location` (and optional `$asset->department`), log activity via `activity()->performedOn($asset)->causedBy(auth()->user())->log(...)`, and redirect to `route('assets.show', $asset)` with success flash.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Module4ScanAndMoveTest.php`
Expected: PASS.

- [ ] **Step 5: Commit Module 4**

```powershell
git add .
git commit -m "feat(module-4): add USB barcode scan lookup and asset location transfer history"
```

---

### Task 5 (Module 5): Excel Import (Sample Template + Preview + Skip Duplicates) & Export

**Files:**
- Create: `app/Exports/AssetsTemplateExport.php`
- Create: `app/Exports/AssetsExport.php`
- Create: `app/Services/AssetImportService.php`
- Create: `app/Http/Controllers/ImportExportController.php`
- Create: `resources/views/reports/index.blade.php`
- Create: `resources/views/reports/import-preview.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Module5ExcelImportExportTest.php`

**Interfaces:**
- Consumes: `App\Models\Asset`, `App\Services\AssetNumberGenerator`, `Maatwebsite\Excel\Facades\Excel`
- Produces:
  - `App\Services\AssetImportService::preview(UploadedFile $file): array{rows: array<int, array>, valid_count: int, duplicate_count: int}`
  - `App\Services\AssetImportService::commit(array $rows): array{imported: int, skipped: int}`
  - Routes under `/reports`:
    - `GET /reports` (`reports.index`)
    - `GET /reports/template` (`reports.template`)
    - `POST /reports/import/preview` (`reports.import.preview`)
    - `POST /reports/import/commit` (`reports.import.commit`)
    - `GET /reports/export` (`reports.export`)

- [ ] **Step 1: Write the failing test in `tests/Feature/Module5ExcelImportExportTest.php`**

```php
<?php

use App\Exports\AssetsTemplateExport;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
});

test('downloads sample excel template and exports assets list', function () {
    Asset::create([
        'asset_no' => 'BBHIND-S300002315',
        'asset_name' => 'Ultrasound Machine',
        'status' => 'In Use',
    ]);

    $this->actingAs($this->user)
        ->get('/reports/template')
        ->assertOk();

    $this->actingAs($this->user)
        ->get('/reports/export')
        ->assertOk();
});

test('previews uploaded spreadsheet, flags duplicate asset_no, allows blank date_acquired/serial_no, and imports non-duplicates', function () {
    Asset::create([
        'asset_no' => 'BBHIND-S300002315',
        'asset_name' => 'Existing Asset',
        'status' => 'In Use',
    ]);

    $csvContent = implode("\n", [
        'asset_no,asset_name,model,serial_no,date_acquired,department,location,status,notes',
        'BBHIND-S300002315,Duplicate Asset,Model X,SN1,2026-01-01,ER,Room 1,In Use,Should skip',
        'BBH-000500,New Bed,Stryker,,2026-02-15,Ward 2,Room 12,In Use,No serial',
        ',Auto Numbered AC,Carrier,,,Admin,Office 1,In Use,Blank asset_no and date_acquired',
    ]);

    $file = UploadedFile::fake()->createWithContent('assets.csv', $csvContent);

    $previewResponse = $this->actingAs($this->user)
        ->post('/reports/import/preview', ['file' => $file]);

    $previewResponse->assertOk()
        ->assertSee('New Bed')
        ->assertSee('Auto Numbered AC')
        ->assertSee('Duplicate (Skipped)');

    $this->actingAs($this->user)
        ->post('/reports/import/commit')
        ->assertRedirect('/reports');

    expect(Asset::count())->toBe(3)
        ->and(Asset::where('asset_name', 'Duplicate Asset')->exists())->toBeFalse()
        ->and(Asset::where('asset_name', 'New Bed')->first()->serial_no)->toBeNull()
        ->and(Asset::where('asset_name', 'Auto Numbered AC')->first()->date_acquired)->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Module5ExcelImportExportTest.php`
Expected: FAIL (404 on `/reports/template`).

- [ ] **Step 3: Implement `AssetsTemplateExport`, `AssetsExport`, `AssetImportService`, and `ImportExportController`**

- `AssetsTemplateExport`: headings `['asset_no', 'asset_name', 'model', 'serial_no', 'date_acquired', 'department', 'location', 'status', 'notes']` with 1 sample row (`BBHIND-S300002315`).
- `AssetsExport`: exports filtered or all assets with the same columns.
- `AssetImportService::preview(UploadedFile $file)`: reads rows via `Excel::toArray(new class implements ToArray {}, $file)[0]`, normalizes header keys, skips completely empty rows, marks rows where `asset_name` is missing as invalid, marks rows whose `asset_no` matches an existing DB `asset_no` (case-insensitive) or an earlier row in the same batch as `is_duplicate = true`, and stores the parsed preview rows in `session('asset_import_preview')`.
- `AssetImportService::commit(array $rows)`: iterates non-duplicate valid rows, generates `asset_no` via `AssetNumberGenerator` when blank, normalizes `status` to one of `Asset::STATUSES` (default `'In Use'`), parses optional `date_acquired` (handling Excel numeric dates via `\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject` as well as string dates), inserts into `assets`, and logs summary in activity log.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Module5ExcelImportExportTest.php`
Expected: PASS.

- [ ] **Step 5: Commit Module 5**

```powershell
git add .
git commit -m "feat(module-5): add Excel template download, import with preview and duplicate skipping, and export"
```

---

### Task 6 (Module 6): Dashboard Summary Cards and Admin Activity Log

**Files:**
- Create: `app/Http/Controllers/DashboardController.php`
- Create: `app/Http/Controllers/ActivityLogController.php`
- Create: `resources/views/dashboard.blade.php`
- Create: `resources/views/settings/activity-log.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Module6DashboardAndActivityLogTest.php`

**Interfaces:**
- Consumes: `App\Models\Asset`, `Spatie\Activitylog\Models\Activity`
- Produces:
  - `GET /dashboard` (`dashboard`) — displays total assets, count by status, count by department, and 5 most recently added assets
  - `GET /settings/activity-log` (`settings.activity-log`, protected by `admin` middleware)

- [ ] **Step 1: Write the failing test in `tests/Feature/Module6DashboardAndActivityLogTest.php`**

```php
<?php

use App\Models\Asset;
use App\Models\User;

test('dashboard displays total assets, counts by status and department, and recent assets', function () {
    $user = User::factory()->create(['role' => 'staff', 'must_change_password' => false]);

    Asset::create(['asset_no' => 'BBH-000001', 'asset_name' => 'ICU Ventilator', 'department' => 'ICU', 'status' => 'In Use']);
    Asset::create(['asset_no' => 'BBH-000002', 'asset_name' => 'ER Stretcher', 'department' => 'ER', 'status' => 'Under Repair']);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('ICU Ventilator')
        ->assertSee('ER Stretcher')
        ->assertSee('ICU')
        ->assertSee('Under Repair');
});

test('activity log page is accessible to admin and forbidden to staff', function () {
    $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
    $staff = User::factory()->create(['role' => 'staff', 'must_change_password' => false]);

    $this->actingAs($admin)
        ->post('/assets', [
            'asset_no' => 'BBH-000100',
            'asset_name' => 'Logged Microscope',
            'status' => 'In Use',
        ]);

    $this->actingAs($staff)
        ->get('/settings/activity-log')
        ->assertForbidden();

    $this->actingAs($admin)
        ->get('/settings/activity-log')
        ->assertOk()
        ->assertSee('Logged Microscope');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Module6DashboardAndActivityLogTest.php`
Expected: FAIL (404 on `/settings/activity-log`).

- [ ] **Step 3: Implement `DashboardController`, `ActivityLogController`, and views**

- `DashboardController::__invoke()`: queries `Asset::count()`, status counts grouped by `status` for all 4 `Asset::STATUSES`, department counts (`Asset::selectRaw("COALESCE(NULLIF(department, ''), 'Unassigned') as dept, COUNT(*) as total")->groupBy('dept')->orderByDesc('total')->get()`), and `Asset::latest()->take(8)->get()`.
- Configure `Asset` model with `Spatie\Activitylog\Traits\LogsActivity` (`LogOptions::defaults()->logFillable()->logOnlyDirty()->setDescriptionForEvent(...)`) so creating, updating, deleting, moving, importing, and backing up/restoring assets all appear in the admin Activity Log.
- `ActivityLogController::index()`: paginated list (`Activity::with(['causer', 'subject'])->latest()->paginate(25)`) restricted via `'admin'` middleware, linked inside the Settings section.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Module6DashboardAndActivityLogTest.php`
Expected: PASS.

- [ ] **Step 5: Commit Module 6**

```powershell
git add .
git commit -m "feat(module-6): add dashboard summary cards and admin-only activity log"
```

---

### Task 7 (Module 7): Settings, NativePHP Folder Picker, and Backup/Restore Engine

**Files:**
- Create: `app/Services/BackupService.php`
- Create: `app/Listeners/BackupDatabaseBeforeMigration.php`
- Create: `app/Http/Controllers/SettingController.php`
- Create: `resources/views/settings/index.blade.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `app/Providers/NativeAppServiceProvider.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Module7SettingsAndBackupTest.php`

**Interfaces:**
- Consumes: `App\Models\Setting`, `Native\Desktop\Dialog`
- Produces:
  - `App\Services\BackupService::getBackupFolder(): string`
  - `App\Services\BackupService::createBackup(string $prefix = 'backup'): string` (returns full path to created `.sqlite` backup file and prunes to last 30)
  - `App\Services\BackupService::runStartupBackupIfDue(): ?string` (creates backup if newest backup is older than 24 hours or none exists)
  - `App\Services\BackupService::pruneOldBackups(int $keep = 30): int`
  - `App\Services\BackupService::restoreFromFile(string $sourceFilePath): void` (validates 16-byte `"SQLite format 3\0"` header before copying over active SQLite file)
  - Routes:
    - `GET /settings` (`settings.index`)
    - `POST /settings` (`settings.update`)
    - `POST /settings/pick-folder` (`settings.pick-folder`)
    - `POST /settings/backup` (`settings.backup`)
    - `POST /settings/restore` (`settings.restore`, admin only)
    - `POST /settings/users` (`settings.users.store`, admin only — manage staff/admin accounts)

- [ ] **Step 1: Write the failing test in `tests/Feature/Module7SettingsAndBackupTest.php`**

```php
<?php

use App\Models\Setting;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->tempBackupDir = storage_path('framework/testing/backups_' . uniqid());
    $this->tempSourceDb = storage_path('framework/testing/source_' . uniqid() . '.sqlite');

    File::ensureDirectoryExists($this->tempBackupDir);
    // Valid 16-byte SQLite header + payload
    File::put($this->tempSourceDb, "SQLite format 3\0" . str_repeat('A', 100));

    Setting::set('backup_folder', $this->tempBackupDir);
});

afterEach(function () {
    File::deleteDirectory($this->tempBackupDir);
    File::delete($this->tempSourceDb);
});

test('creates timestamped backup, checks 24h auto-backup threshold, and prunes beyond 30 backups', function () {
    $service = new BackupService($this->tempSourceDb);

    // Seed 31 old backup files
    for ($i = 1; $i <= 31; $i++) {
        $file = $this->tempBackupDir . sprintf('/backup-2026-01-%02d_120000.sqlite', $i);
        File::put($file, "SQLite format 3\0");
        touch($file, strtotime("-{$i} days"));
    }

    $createdPath = $service->createBackup('manual');
    expect(File::exists($createdPath))->toBeTrue();

    $files = glob($this->tempBackupDir . '/*.sqlite');
    expect(count($files))->toBe(30);

    // Since a backup was just created (< 24h ago), startup backup should skip
    expect($service->runStartupBackupIfDue())->toBeNull();
});

test('rejects invalid non-SQLite file on restore and allows admin to restore valid backup', function () {
    $service = new BackupService($this->tempSourceDb);

    $corruptFile = $this->tempBackupDir . '/corrupt.sqlite';
    File::put($corruptFile, 'NOT A SQLITE FILE');

    expect(fn () => $service->restoreFromFile($corruptFile))
        ->toThrow(InvalidArgumentException::class, 'Invalid SQLite backup file.');

    $validBackup = $this->tempBackupDir . '/valid.sqlite';
    $expectedBytes = "SQLite format 3\0" . str_repeat('B', 120);
    File::put($validBackup, $expectedBytes);

    $service->restoreFromFile($validBackup);
    expect(File::get($this->tempSourceDb))->toBe($expectedBytes);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Module7SettingsAndBackupTest.php`
Expected: FAIL (`Class "App\Services\BackupService" not found`).

- [ ] **Step 3: Implement `BackupService`, `BackupDatabaseBeforeMigration`, `SettingController`, and `NativeAppServiceProvider` startup hooks**

- `BackupService`:
  - Accepts optional `?string $databasePath = null` in constructor; defaults to resolving the active SQLite file path from `DB::connection()->getDatabaseName()`.
  - Uses `DB::statement('PRAGMA wal_checkpoint(FULL);')` (when connected to a file-backed SQLite database) before `File::copy($sourceDb, $destination)` so WAL transactions are flushed into the `.sqlite` file before copying.
  - `pruneOldBackups(int $keep = 30)`: sorts `*.sqlite` files in the backup folder by `filemtime` descending and deletes any beyond `$keep`.
  - `runStartupBackupIfDue()`: checks newest `.sqlite` file in `getBackupFolder()`; if none exists or `filemtime < now()->subHours(24)->timestamp`, calls `createBackup('auto')`.
  - `restoreFromFile(string $sourceFilePath)`: verifies file exists, `filesize >= 16`, and `substr(file_get_contents($sourceFilePath, false, null, 0, 16), 0, 16) === "SQLite format 3\0"`. Creates a safety pre-restore backup, runs `DB::purge()`, copies `$sourceFilePath` over `$databasePath`, removes stale `-wal`/`-shm` files, and reconnects.
- Register `Illuminate\Database\Events\MigrationsStarted` listener `BackupDatabaseBeforeMigration` in `AppServiceProvider::boot()` so whenever migrations run against an existing non-empty SQLite file, a `pre-migration-YYYY-MM-DD_HHmmss.sqlite` backup is created first.
- In `SettingController::pickFolder()`: if `config('nativephp-internal.running')` is true, call `Dialog::new()->title('Select Backup Folder')->folders()->open()`, save to `Setting::set('backup_folder', $folder)`, and return back.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Module7SettingsAndBackupTest.php`
Expected: PASS.

- [ ] **Step 5: Commit Module 7**

```powershell
git add .
git commit -m "feat(module-7): add settings, NativePHP folder picker, and SQLite backup/restore engine"
```

---

### Task 8 (Module 8): Update-Safe NativePHP Desktop Configuration & Windows Build

**Files:**
- Modify: `.env.example`
- Modify: `config/nativephp.php`
- Modify: `app/Providers/NativeAppServiceProvider.php`
- Create: `app/Services/DesktopBootstrapService.php`
- Create: `docs/CLIENT-INSTALLATION-CHECKLIST.md`
- Test: `tests/Feature/Module8DesktopBootstrapTest.php`

**Interfaces:**
- Consumes: `App\Services\BackupService`, `Database\Seeders\DatabaseSeeder`
- Produces:
  - `App\Services\DesktopBootstrapService::boot(): void` (runs pre-migration backup if needed, runs `Artisan::call('migrate', ['--force' => true])`, seeds initial admin/settings if `User::count() === 0`, and triggers `BackupService::runStartupBackupIfDue()`)
  - Window configuration in `NativeAppServiceProvider::boot()`: title `"Bethel Asset Tracker"`, `width(1280)->height(820)->minWidth(1024)->minHeight(700)->rememberState()`
  - Update-safe `config/nativephp.php`: excludes `database/*.sqlite*` from bundle, sets `updater.enabled = false`, `nsis.delete_app_data_on_uninstall = false`

- [ ] **Step 1: Write the failing test in `tests/Feature/Module8DesktopBootstrapTest.php`**

```php
<?php

use App\Models\Setting;
use App\Models\User;
use App\Services\DesktopBootstrapService;

test('desktop bootstrap seeds default admin and settings when database is empty and configures update-safe nativephp settings', function () {
    expect(User::count())->toBe(0);

    app(DesktopBootstrapService::class)->boot();

    expect(User::where('email', 'admin@hospital.local')->exists())->toBeTrue()
        ->and(Setting::get('hospital_name'))->toBe('Bethel Baptist Hospital, Inc.');

    expect(config('nativephp.cleanup_exclude_files'))
        ->toContain('database/*.sqlite')
        ->and(config('nativephp.nsis.delete_app_data_on_uninstall'))->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Module8DesktopBootstrapTest.php`
Expected: FAIL (`Class "App\Services\DesktopBootstrapService" not found`).

- [ ] **Step 3: Implement `DesktopBootstrapService`, update `NativeAppServiceProvider` and `config/nativephp.php`, and write `docs/CLIENT-INSTALLATION-CHECKLIST.md`**

- `config/nativephp.php`:
  - Add `'database/*.sqlite'`, `'database/*.sqlite-shm'`, `'database/*.sqlite-wal'` to `'cleanup_exclude_files'`.
  - Set `'updater.enabled' => env('NATIVEPHP_UPDATER_ENABLED', false)` (offline hospital PC).
  - Keep `'nsis.delete_app_data_on_uninstall' => false`.
- `NativeAppServiceProvider::boot()`:
  - Call `app(DesktopBootstrapService::class)->boot();`
  - Open main window:
    ```php
    Window::open()
        ->title('Bethel Asset Tracker')
        ->width(1280)
        ->height(820)
        ->minWidth(1024)
        ->minHeight(700)
        ->rememberState();
    ```
- Create `docs/CLIENT-INSTALLATION-CHECKLIST.md` documenting first-time `.exe` installation, where SQLite data lives on Windows (`%APPDATA%\Bethel Asset Tracker\database\database.sqlite`), first-login password change for `admin@hospital.local`, setting the backup folder, printer page setup for tags, and safe `.exe` upgrade instructions.

- [ ] **Step 4: Run full test suite and build frontend + Windows desktop package**

Run:
```powershell
npm run build
php artisan test --compact
```
Expected: All Module 1–8 tests PASS. (Then in Module 8 execution, run `php artisan native:build win`.)

- [ ] **Step 5: Commit Module 8**

```powershell
git add .
git commit -m "feat(module-8): configure NativePHP window, startup bootstrap, and Windows build checklist"
```
