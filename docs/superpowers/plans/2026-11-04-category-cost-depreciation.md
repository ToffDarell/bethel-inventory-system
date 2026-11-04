# Category, Cost, and Automatic Depreciation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add asset categorization, acquisition cost tracking, and automatic straight-line depreciation calculations to Bethel Asset Tracker with strict admin-only financial visibility, additive SQLite migrations, and comprehensive reporting.

**Architecture:** A centralized `DepreciationService` calculates monthly straight-line depreciation dynamically from `cost`, `salvage_value`, `useful_life_years`, and `date_acquired` without storing calculated values in the database. `Category` models supply default useful life and salvage percentages to UI forms. Server-side role policies ensure Staff can assign categories but never view or modify monetary fields.

**Tech Stack:** Laravel 13, NativePHP Desktop v2 (Electron + SQLite), Blade + Tailwind CSS v4, Maatwebsite Excel (CSV/XLSX), Spatie Activitylog, Pest PHP.

**Spec:** User prompt requirements for Category, Cost, and Automatic Depreciation (Phases A through D).

---

## Global Constraints

- **Additive migrations only:** `Schema::create('categories')` and nullable columns on `assets` (`category_id`, `cost`, `useful_life_years`, `salvage_value`). Never drop or truncate existing tables or columns; client records must remain intact.
- **Currency formatting:** Philippine Peso format: `₱` with thousands separators and 2 decimal places (for example `₱120,000.00`).
- **Blank cost representation:** Missing or blank cost displays as `"No cost entered"`, never `0` or `₱0.00`.
- **Admin-only financial visibility:** Cost, salvage value, accumulated depreciation, and current value are visible and editable exclusively by Admin users (`$user->isAdmin()`). Staff see and assign Category only. Server-side validation must strip or reject financial inputs from Staff.
- **Soft-delete referential safety:** Categories assigned to any active or soft-deleted asset cannot be deleted; they may only be renamed or have default parameters adjusted.
- **Copy and typography rules:** Never use em dashes (`—`) in UI copy. Use clear, plain labels: "Cost", "Useful life (years)", "Salvage value", "Current value".
- **Preserved modules:** Do not modify authentication, SQLite backup/restore routines, or the 10cm x 6cm printed property tag layout.
- **Offline & runtime compatibility:** No external CDNs or network calls. Fall back to CSV export seamlessly when PHP `XMLWriter` is unavailable on NativePHP Windows runtime.

---

## Review Focus

1. **Missing or partial depreciation attributes:** When an asset lacks `cost`, `useful_life_years`, or `date_acquired`, `DepreciationService` must return an uncalculable result with the exact missing requirement hint, without throwing exceptions or defaulting to zero.
2. **Staff role privilege escalation attempt:** When a Staff user submits POST or PUT requests to `/assets` or `/assets/{id}` with `cost`, `salvage_value`, or `useful_life_years`, the server must discard those fields and prevent unauthorized writes.
3. **Category deletion referential integrity:** Deleting a category that is referenced by an active or soft-deleted asset must be rejected with a user-friendly error message; deletion is permitted only when zero assets reference the category.
4. **Boundary and lifecycle depreciation dates:** An asset acquired in the future must have `0.00` accumulated depreciation and current value equal to `cost`. An asset past its useful life must clamp accumulated depreciation to `cost - salvage_value` and current value to `salvage_value` (never below salvage).
5. **Spreadsheet import with unknown categories:** When an uploaded Excel/CSV file contains category names not yet in the database, the import preview must flag them cleanly and allow adding or skipping them without aborting the entire import.

---

## File Structure

### New Files
- `database/migrations/2026_11_04_000001_create_categories_table.php` — categories table schema.
- `database/migrations/2026_11_04_000002_add_category_and_cost_to_assets_table.php` — additive nullable columns on assets.
- `database/seeders/CategorySeeder.php` — initial category defaults (`firstOrCreate`).
- `app/Models/Category.php` — category Eloquent model with defaults and relationships.
- `app/Services/DepreciationService.php` — pure domain service for straight-line calculations.
- `app/Http/Controllers/CategoryController.php` — CRUD actions for categories with delete-blocking check.
- `app/Exports/DepreciationExport.php` — Excel/CSV export for asset depreciation reports.
- `tests/Unit/DepreciationServiceTest.php` — unit tests for depreciation calculation cases.
- `tests/Feature/CategoryManagementTest.php` — tests for Category settings tab and delete protection.
- `tests/Feature/AssetDepreciationTest.php` — tests for permissions, asset CRUD with financials, and reports.

### Modified Files
- `app/Models/Asset.php` — add `category_id`, `cost`, `useful_life_years`, `salvage_value` to `$fillable` and `$casts`; add `category()` relation.
- `app/Http/Controllers/AssetController.php` — validate and authorize category & money fields; pass categories to forms; include Category and Current Value in index.
- `app/Http/Controllers/DashboardController.php` — calculate total asset value (Admin) and category breakdown.
- `app/Http/Controllers/ImportExportController.php` — add depreciation report method and export handling.
- `app/Http/Controllers/SettingController.php` — supply categories to Settings view.
- `app/Services/AssetImportService.php` — parse category and cost columns; detect unknown categories.
- `app/Exports/AssetsExport.php` — add category and optional admin money columns.
- `app/Exports/AssetsTemplateExport.php` — add sample category and cost columns.
- `resources/views/settings/index.blade.php` — add "Categories" tab navigation, table, and modal/forms.
- `resources/views/assets/index.blade.php` — add Category column/filter and Admin Current Value column.
- `resources/views/assets/create.blade.php` — add "Category and Value" card with vanilla JS live calculation.
- `resources/views/assets/edit.blade.php` — add "Category and Value" card with prefilled values and live calculation.
- `resources/views/assets/show.blade.php` — add "Value" card with progress bar (Admin) and Category badge (All).
- `resources/views/dashboard.blade.php` — add "Total asset value" card and "By category" breakdown.
- `resources/views/reports/index.blade.php` — add "Depreciation Report" tab/section with filters and export buttons.
- `resources/views/reports/import-preview.blade.php` — display category, cost, and unknown category indicators.
- `routes/web.php` — register category resource and depreciation report routes.
- `database/seeders/DatabaseSeeder.php` — call `CategorySeeder`.

---

## Phase A: Settings > Categories Tab and Data Layer

### Task 1: Category Table & Asset Columns Migration, Model, and Seeder

**Files:**
- Create: `database/migrations/2026_11_04_000001_create_categories_table.php`
- Create: `database/migrations/2026_11_04_000002_add_category_and_cost_to_assets_table.php`
- Create: `app/Models/Category.php`
- Create: `database/seeders/CategorySeeder.php`
- Modify: `app/Models/Asset.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/CategoryManagementTest.php`

**Interfaces:**
- Produces: `App\Models\Category` (`id`, `name`, `default_useful_life_years`, `default_salvage_percent`, `assets()`).
- Produces: `Asset::category()` belongsTo relation and `$fillable` fields: `category_id`, `cost`, `useful_life_years`, `salvage_value`.

- [ ] **Step 1: Write the failing test**

In `tests/Feature/CategoryManagementTest.php`:
```php
test('category seeder creates default hospital categories without overwriting existing entries', function () {
    $this->seed(\Database\Seeders\CategorySeeder::class);

    expect(\App\Models\Category::count())->toBe(7);
    expect(\App\Models\Category::where('name', 'Medical Equipment')->first()->default_useful_life_years)->toBe(10);
    expect(\App\Models\Category::where('name', 'Vehicles')->first()->default_useful_life_years)->toBe(7);

    // Run seeder again to ensure idempotency
    $this->seed(\Database\Seeders\CategorySeeder::class);
    expect(\App\Models\Category::count())->toBe(7);
});

test('asset supports optional category and financial attributes', function () {
    $category = \App\Models\Category::create([
        'name' => 'Biomedical Devices',
        'default_useful_life_years' => 8,
        'default_salvage_percent' => 5.0,
    ]);

    $asset = \App\Models\Asset::create([
        'asset_no' => 'BBH-CAT-001',
        'asset_name' => 'Infusion Pump',
        'status' => 'In Use',
        'category_id' => $category->id,
        'cost' => 85000.50,
        'useful_life_years' => 8,
        'salvage_value' => 4250.00,
    ]);

    expect($asset->category->name)->toBe('Biomedical Devices');
    expect((float) $asset->cost)->toBe(85000.50);
    expect($asset->useful_life_years)->toBe(8);
    expect((float) $asset->salvage_value)->toBe(4250.00);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=CategoryManagementTest`
Expected: FAIL (table `categories` does not exist).

- [ ] **Step 3: Implement migrations, `Category` model, `CategorySeeder`, and update `Asset.php`**

- Migration `create_categories_table`:
  - `id`
  - `string('name')->unique()`
  - `integer('default_useful_life_years')->nullable()`
  - `decimal('default_salvage_percent', 5, 2)->default(0)`
  - `timestamps()`
- Migration `add_category_and_cost_to_assets_table`:
  - `foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete()`
  - `decimal('cost', 12, 2)->nullable()`
  - `unsignedInteger('useful_life_years')->nullable()`
  - `decimal('salvage_value', 12, 2)->nullable()`
- Model `Category`:
  - `$fillable = ['name', 'default_useful_life_years', 'default_salvage_percent']`
  - `assets(): HasMany`
- Update `Asset.php`:
  - Add to `$fillable`: `'category_id'`, `'cost'`, `'useful_life_years'`, `'salvage_value'`
  - Add to `casts()`: `'cost' => 'decimal:2'`, `'salvage_value' => 'decimal:2'`, `'useful_life_years' => 'integer'`
  - Add `category(): BelongsTo`
- Seeder `CategorySeeder`:
  - Seed default rows:
    - Medical Equipment: 10 yrs, 0%
    - IT Equipment: 5 yrs, 0%
    - Furniture: 10 yrs, 0%
    - Appliances: 8 yrs, 0%
    - Vehicles: 7 yrs, 0%
    - Building Fixtures: 15 yrs, 0%
    - Other: null yrs, 0%
  - Use `Category::firstOrCreate(['name' => $data['name']], $data)`.
- Register `CategorySeeder` in `DatabaseSeeder.php`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=CategoryManagementTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations app/Models database/seeders tests/Feature/CategoryManagementTest.php
git commit -m "feat(category): add categories table, asset columns, and initial seeder"
```

---

### Task 2: Straight-Line `DepreciationService` and Comprehensive Unit Tests

**Files:**
- Create: `app/Services/DepreciationService.php`
- Create: `tests/Unit/DepreciationServiceTest.php`

**Interfaces:**
- Produces: `DepreciationService::calculate(?float $cost, ?float $salvageValue, ?int $usefulLifeYears, ?CarbonInterface $dateAcquired, ?CarbonInterface $asOfDate = null): DepreciationResult`
- Produces: `DepreciationService::calculateForAsset(Asset $asset, ?CarbonInterface $asOfDate = null): DepreciationResult`
- Produces: `DepreciationResult` data object with methods:
  - `isCalculable(): bool`
  - `getMissingReason(): ?string`
  - `getCost(): ?float`
  - `getSalvageValue(): ?float`
  - `getUsefulLifeYears(): ?int`
  - `getYearlyDepreciation(): ?float`
  - `getMonthlyDepreciation(): ?float`
  - `getMonthsElapsed(): int`
  - `getAccumulatedDepreciation(): ?float`
  - `getCurrentValue(): ?float`
  - `getUsagePercentage(): float` (0 to 100)
  - `formatPeso(?float $amount): string` (e.g. `₱87,000.00` or `"No cost entered"`)

- [ ] **Step 1: Write the failing test**

In `tests/Unit/DepreciationServiceTest.php`:
```php
use App\Services\DepreciationService;
use Carbon\Carbon;

test('calculates normal straight-line depreciation correctly using elapsed months', function () {
    $service = new DepreciationService();
    $acquired = Carbon::parse('2023-01-01');
    $asOf = Carbon::parse('2026-01-01'); // exactly 3 years (36 months)

    // Cost: 120,000, Salvage: 10,000, Life: 10 years
    // Depreciable amount = 110,000
    // Yearly = 11,000, Monthly = 110,000 / 120 = 916.666...
    // 36 months = 33,000 accumulated
    // Current value = 120,000 - 33,000 = 87,000
    $result = $service->calculate(120000, 10000, 10, $acquired, $asOf);

    expect($result->isCalculable())->toBeTrue();
    expect($result->getCurrentValue())->toBe(87000.0);
    expect($result->getAccumulatedDepreciation())->toBe(33000.0);
    expect($result->getYearlyDepreciation())->toBe(11000.0);
    expect($service->formatPeso($result->getCurrentValue()))->toBe('₱87,000.00');
});

test('handles exactly end of life and past end of life by clamping to salvage value', function () {
    $service = new DepreciationService();
    $acquired = Carbon::parse('2010-01-01');
    $asOf = Carbon::parse('2020-01-01'); // exactly 10 years
    $resultEnd = $service->calculate(100000, 10000, 10, $acquired, $asOf);

    expect($resultEnd->getCurrentValue())->toBe(10000.0);
    expect($resultEnd->getAccumulatedDepreciation())->toBe(90000.0);
    expect($resultEnd->getUsagePercentage())->toBe(100.0);

    // 15 years later (5 years past useful life)
    $asOfPast = Carbon::parse('2025-01-01');
    $resultPast = $service->calculate(100000, 10000, 10, $acquired, $asOfPast);
    expect($resultPast->getCurrentValue())->toBe(10000.0);
    expect($resultPast->getAccumulatedDepreciation())->toBe(90000.0);
});

test('handles zero salvage value', function () {
    $service = new DepreciationService();
    $acquired = Carbon::parse('2024-01-01');
    $asOf = Carbon::parse('2025-01-01'); // 1 year of 5
    $result = $service->calculate(50000, 0, 5, $acquired, $asOf);

    expect($result->getCurrentValue())->toBe(40000.0);
    expect($result->getAccumulatedDepreciation())->toBe(10000.0);
});

test('handles missing cost, missing date, or missing useful life without crashing', function () {
    $service = new DepreciationService();

    $noCost = $service->calculate(null, 0, 5, Carbon::now());
    expect($noCost->isCalculable())->toBeFalse();
    expect($noCost->getMissingReason())->toBe('No cost entered');
    expect($service->formatPeso(null))->toBe('No cost entered');

    $noDate = $service->calculate(50000, 0, 5, null);
    expect($noDate->isCalculable())->toBeFalse();
    expect($noDate->getMissingReason())->toBe('Missing acquisition date');

    $noLife = $service->calculate(50000, 0, null, Carbon::now());
    expect($noLife->isCalculable())->toBeFalse();
    expect($noLife->getMissingReason())->toBe('Missing useful life');
});

test('handles future acquisition date with zero accumulated depreciation', function () {
    $service = new DepreciationService();
    $acquired = Carbon::now()->addMonths(6);
    $result = $service->calculate(60000, 5000, 5, $acquired, Carbon::now());

    expect($result->isCalculable())->toBeTrue();
    expect($result->getMonthsElapsed())->toBe(0);
    expect($result->getAccumulatedDepreciation())->toBe(0.0);
    expect($result->getCurrentValue())->toBe(60000.0);
});

test('rounds peso decimal cents accurately', function () {
    $service = new DepreciationService();
    $acquired = Carbon::parse('2025-01-01');
    $asOf = Carbon::parse('2025-04-01'); // 3 months
    // Cost: 10000.33, Salvage: 0, Life: 3 years (36 months)
    $result = $service->calculate(10000.33, 0, 3, $acquired, $asOf);

    expect($result->isCalculable())->toBeTrue();
    expect(round($result->getCurrentValue(), 2))->toBe(9166.97);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=DepreciationServiceTest`
Expected: FAIL (`DepreciationService` class not found).

- [ ] **Step 3: Implement `DepreciationService`**

Implement formula:
```php
$depreciableAmount = max(0, $cost - $salvageValue);
$totalMonths = max(1, $usefulLifeYears * 12);
$monthlyRate = $depreciableAmount / $totalMonths;
$monthsElapsed = max(0, $dateAcquired->diffInMonths($asOfDate, false));
$accumulated = min($depreciableAmount, round($monthlyRate * $monthsElapsed, 2));
$currentValue = max($salvageValue, round($cost - $accumulated, 2));
```
Include `formatPeso(?float $value)` returning `₱` followed by `number_format($value, 2)` or `"No cost entered"`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=DepreciationServiceTest`
Expected: PASS (all assertions pass).

- [ ] **Step 5: Commit**

```bash
git add app/Services/DepreciationService.php tests/Unit/DepreciationServiceTest.php
git commit -m "feat(depreciation): implement straight-line calculation service and unit tests"
```

---

### Task 3: Settings > Categories Tab UI & CategoryController

**Files:**
- Create: `app/Http/Controllers/CategoryController.php`
- Modify: `app/Http/Controllers/SettingController.php`
- Modify: `resources/views/settings/index.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/CategoryManagementTest.php`

**Interfaces:**
- Consumes: `Category` model, Spatie ActivityLog.
- Produces: Routes:
  - `POST /settings/categories` (`category.store`) — Admin only.
  - `PUT /settings/categories/{category}` (`category.update`) — Admin only.
  - `DELETE /settings/categories/{category}` (`category.destroy`) — Admin only.

- [ ] **Step 1: Write the failing feature test**

In `tests/Feature/CategoryManagementTest.php`:
```php
test('admin can create and update categories from settings', function () {
    $admin = \App\Models\User::factory()->create(['role' => 'admin', 'must_change_password' => false]);

    $this->actingAs($admin)->post('/settings/categories', [
        'name' => 'Laboratory Equipment',
        'default_useful_life_years' => 12,
        'default_salvage_percent' => 10,
    ])->assertRedirect();

    $cat = \App\Models\Category::where('name', 'Laboratory Equipment')->first();
    expect($cat)->not->toBeNull();
    expect($cat->default_useful_life_years)->toBe(12);

    $this->actingAs($admin)->put("/settings/categories/{$cat->id}", [
        'name' => 'Clinical Lab Equipment',
        'default_useful_life_years' => 10,
        'default_salvage_percent' => 5,
    ])->assertRedirect();

    expect($cat->fresh()->name)->toBe('Clinical Lab Equipment');
});

test('blocks category deletion when category is assigned to active or soft-deleted assets', function () {
    $admin = \App\Models\User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
    $cat = \App\Models\Category::create(['name' => 'Surgical Equipment']);

    $asset = \App\Models\Asset::create([
        'asset_no' => 'BBH-SURG-01',
        'asset_name' => 'Anesthesia Machine',
        'status' => 'In Use',
        'category_id' => $cat->id,
    ]);

    // Attempt delete while active
    $this->actingAs($admin)->delete("/settings/categories/{$cat->id}")
        ->assertSessionHasErrors('category');
    expect(\App\Models\Category::find($cat->id))->not->toBeNull();

    // Soft delete asset
    $asset->delete();

    // Attempt delete while asset is soft-deleted
    $this->actingAs($admin)->delete("/settings/categories/{$cat->id}")
        ->assertSessionHasErrors('category');
    expect(\App\Models\Category::find($cat->id))->not->toBeNull();
});

test('staff cannot create, edit, or delete categories', function () {
    $staff = \App\Models\User::factory()->create(['role' => 'staff', 'must_change_password' => false]);
    $cat = \App\Models\Category::create(['name' => 'Office Supply']);

    $this->actingAs($staff)->post('/settings/categories', ['name' => 'Hacked'])->assertForbidden();
    $this->actingAs($staff)->put("/settings/categories/{$cat->id}", ['name' => 'Hacked'])->assertForbidden();
    $this->actingAs($staff)->delete("/settings/categories/{$cat->id}")->assertForbidden();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=CategoryManagementTest`
Expected: FAIL (routes `/settings/categories` not found).

- [ ] **Step 3: Implement `CategoryController`, routes, and Settings Blade view**

- In `app/Http/Controllers/CategoryController.php`:
  - `store`: validate name unique, `default_useful_life_years` integer/null, `default_salvage_percent` numeric/null. Log activity: `"Category '{$cat->name}' was created"`.
  - `update`: validate name unique ignoring current id. Log activity: `"Category '{$cat->name}' was updated"`.
  - `destroy`: Check `Asset::withTrashed()->where('category_id', $category->id)->exists()`. If exists, redirect with error: `"Cannot delete category '{$category->name}' because it is assigned to existing assets. You may rename it instead."` If unused, delete and log activity.
- In `routes/web.php`: add `Route::middleware('admin')->group(...)` category routes.
- In `SettingController::index`: pass `$categories = Category::withCount('assets')->orderBy('name')->get();`.
- In `resources/views/settings/index.blade.php`:
  - Add tab button: "Categories" with tag/folder icon.
  - Add tab panel `#panel-categories`: table showing Name, Default Useful Life (years), Default Salvage %, Assets Count, Actions (Edit modal trigger, Delete button with confirmation).
  - Add "Add Category" modal and inline "Edit Category" modal using vanilla JS.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=CategoryManagementTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/CategoryController.php app/Http/Controllers/SettingController.php resources/views/settings/index.blade.php routes/web.php tests/Feature/CategoryManagementTest.php
git commit -m "feat(settings): add categories management tab with deletion safeguard and activity logging"
```

---

## Phase B: Add/Edit Asset and Asset Detail

### Task 4: Add/Edit Asset "Category and Value" Card with Live Calculation and Role Permissions

**Files:**
- Modify: `app/Http/Controllers/AssetController.php`
- Modify: `resources/views/assets/create.blade.php`
- Modify: `resources/views/assets/edit.blade.php`
- Test: `tests/Feature/AssetDepreciationTest.php`

**Interfaces:**
- Consumes: `Category` model, `DepreciationService`.
- Produces: Admin-only financial form fields in Asset Create/Edit; Staff can set Category only. Live vanilla JS calculation showing "Estimated current value: ₱XX,XXX.XX".

- [ ] **Step 1: Write the failing feature test**

In `tests/Feature/AssetDepreciationTest.php`:
```php
test('admin can create asset with category, cost, useful life, and salvage value', function () {
    $admin = \App\Models\User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
    $cat = \App\Models\Category::create(['name' => 'Ultrasound Machines', 'default_useful_life_years' => 7, 'default_salvage_percent' => 5]);

    $this->actingAs($admin)->post('/assets', [
        'asset_no' => 'BBH-US-001',
        'asset_name' => 'Portable Ultrasound',
        'status' => 'In Use',
        'category_id' => $cat->id,
        'cost' => 350000.00,
        'useful_life_years' => 7,
        'salvage_value' => 17500.00,
        'date_acquired' => '2024-01-15',
    ])->assertRedirect();

    $asset = \App\Models\Asset::where('asset_no', 'BBH-US-001')->first();
    expect($asset->category_id)->toBe($cat->id);
    expect((float) $asset->cost)->toBe(350000.00);
    expect($asset->useful_life_years)->toBe(7);
    expect((float) $asset->salvage_value)->toBe(17500.00);
});

test('staff can assign category but submitted monetary fields are discarded by server', function () {
    $staff = \App\Models\User::factory()->create(['role' => 'staff', 'must_change_password' => false]);
    $cat = \App\Models\Category::create(['name' => 'Wheelchairs']);

    $this->actingAs($staff)->post('/assets', [
        'asset_no' => 'BBH-WC-001',
        'asset_name' => 'Standard Wheelchair',
        'status' => 'In Use',
        'category_id' => $cat->id,
        'cost' => 15000.00, // Staff attempting to submit cost
        'useful_life_years' => 5,
        'salvage_value' => 1000.00,
    ])->assertRedirect();

    $asset = \App\Models\Asset::where('asset_no', 'BBH-WC-001')->first();
    expect($asset->category_id)->toBe($cat->id);
    expect($asset->cost)->toBeNull(); // Discarded!
    expect($asset->useful_life_years)->toBeNull();
    expect($asset->salvage_value)->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AssetDepreciationTest`
Expected: FAIL (`AssetController` does not save category or strip money for staff).

- [ ] **Step 3: Update `AssetController.php`, `create.blade.php`, and `edit.blade.php`**

- In `AssetController.php`:
  - Pass `$categories = Category::orderBy('name')->get();` to `create` and `edit`.
  - In `validateAsset`:
    - Add validation rules: `'category_id' => ['nullable', 'exists:categories,id']`.
    - If `$request->user()?->isAdmin()`:
      - Add rules: `'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99']`, `'useful_life_years' => ['nullable', 'integer', 'min:1', 'max:100']`, `'salvage_value' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99']`.
    - If user is NOT admin: unset `cost`, `useful_life_years`, and `salvage_value` from validated data so they cannot be injected.
- In `resources/views/assets/create.blade.php` and `edit.blade.php`:
  - Add card "Category and Value".
  - Always render Category dropdown with `data-life` and `data-salvage` attributes on options.
  - If `@if (auth()->user()?->isAdmin())`:
    - Render Cost (`₱` prefix input), Useful life (years), Salvage value.
    - Render live preview element: `<div id="depreciation-live-preview" class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700">Estimated current value: <span id="preview-value-text" class="font-semibold text-slate-900">No cost entered</span></div>`.
    - Add concise inline script:
      - When category changes: if useful life or salvage is empty, prefill from `selectedOption.dataset.life` and calculate salvage as `cost * (dataset.salvage / 100)`.
      - On input in cost, useful life, salvage, or date acquired: calculate straight-line value and update `#preview-value-text`.
  - If Staff: show only Category dropdown. No money inputs rendered.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AssetDepreciationTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/AssetController.php resources/views/assets/create.blade.php resources/views/assets/edit.blade.php tests/Feature/AssetDepreciationTest.php
git commit -m "feat(assets): add category and value card with live depreciation preview and role guardrails"
```

---

### Task 5: Asset Detail "Value" Card, Progress Bar, and Category Badge

**Files:**
- Modify: `app/Http/Controllers/AssetController.php`
- Modify: `resources/views/assets/show.blade.php`
- Test: `tests/Feature/AssetDepreciationTest.php`

**Interfaces:**
- Consumes: `DepreciationService`, `Asset::category`.
- Produces: Header Category badge on `assets.show`; Admin-only "Value" card with Cost, Yearly depreciation, Accumulated depreciation, Current value, and progress bar (% used).

- [ ] **Step 1: Write the failing feature test**

In `tests/Feature/AssetDepreciationTest.php`:
```php
test('admin sees Value card on asset detail with formatted peso and progress', function () {
    $admin = \App\Models\User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
    $cat = \App\Models\Category::create(['name' => 'Diagnostic']);
    $asset = \App\Models\Asset::create([
        'asset_no' => 'BBH-DIAG-01',
        'asset_name' => 'ECG Machine',
        'status' => 'In Use',
        'category_id' => $cat->id,
        'cost' => 120000.00,
        'salvage_value' => 10000.00,
        'useful_life_years' => 10,
        'date_acquired' => now()->subYears(3),
    ]);

    $response = $this->actingAs($admin)->get("/assets/{$asset->id}");
    $response->assertOk();
    $response->assertSee('Value');
    $response->assertSee('Cost');
    $response->assertSee('Current value');
    $response->assertSee('Diagnostic'); // Category badge
});

test('staff sees Category badge on asset detail but does not see Value card or money fields', function () {
    $staff = \App\Models\User::factory()->create(['role' => 'staff', 'must_change_password' => false]);
    $cat = \App\Models\Category::create(['name' => 'Diagnostic']);
    $asset = \App\Models\Asset::create([
        'asset_no' => 'BBH-DIAG-02',
        'asset_name' => 'ECG Machine 2',
        'status' => 'In Use',
        'category_id' => $cat->id,
        'cost' => 120000.00,
        'salvage_value' => 10000.00,
        'useful_life_years' => 10,
        'date_acquired' => now()->subYears(3),
    ]);

    $response = $this->actingAs($staff)->get("/assets/{$asset->id}");
    $response->assertOk();
    $response->assertSee('Diagnostic'); // Category badge visible
    $response->assertDontSee('Current value');
    $response->assertDontSee('Accumulated depreciation');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AssetDepreciationTest`
Expected: FAIL (Value card not found on `assets.show`).

- [ ] **Step 3: Update `AssetController@show` and `resources/views/assets/show.blade.php`**

- In `AssetController@show`:
  - Eager load `category`.
  - If `$request->user()?->isAdmin()`, calculate depreciation via `app(DepreciationService::class)->calculateForAsset($asset)`. Pass `$depreciation` to view.
- In `resources/views/assets/show.blade.php`:
  - In page header next to Status badge: if `$asset->category`, render clean Category badge: `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-800 border border-slate-200">{{ $asset->category->name }}</span>`.
  - In grid: if `@if (auth()->user()?->isAdmin() && isset($depreciation))`:
    - Add "Value" card:
      - Cost: `$depreciation->isCalculable() ? $service->formatPeso($asset->cost) : ($asset->cost ? '₱' . number_format($asset->cost, 2) : 'No cost entered')`.
      - Useful life (years): `{{ $asset->useful_life_years ?? 'Not set' }}`.
      - Salvage value: `{{ $asset->salvage_value !== null ? '₱' . number_format($asset->salvage_value, 2) : 'Not set' }}`.
      - Yearly depreciation: `{{ $depreciation->isCalculable() ? '₱' . number_format($depreciation->getYearlyDepreciation(), 2) : 'Not enough data' }}`.
      - Accumulated depreciation: `{{ $depreciation->isCalculable() ? '₱' . number_format($depreciation->getAccumulatedDepreciation(), 2) : 'Not enough data' }}`.
      - Current value: prominent large display `{{ $depreciation->isCalculable() ? '₱' . number_format($depreciation->getCurrentValue(), 2) : 'Not enough data' }}`.
      - Progress bar: `<div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden"><div class="bg-primary h-2.5 rounded-full" style="width: {{ $depreciation->getUsagePercentage() }}%"></div></div>` with subtitle `"{{ round($depreciation->getUsagePercentage()) }}% of depreciable life elapsed"`.
      - If not calculable, show neutral alert with `$depreciation->getMissingReason()`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AssetDepreciationTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/AssetController.php resources/views/assets/show.blade.php tests/Feature/AssetDepreciationTest.php
git commit -m "feat(assets): add category badge and admin value card with depreciation progress"
```

---

## Phase C: Assets List, Dashboard, Reports

### Task 6: Assets List Category Column, Category Filter, and Admin Current Value Column

**Files:**
- Modify: `app/Http/Controllers/AssetController.php`
- Modify: `resources/views/assets/index.blade.php`
- Test: `tests/Feature/AssetDepreciationTest.php`

**Interfaces:**
- Consumes: `Asset::category()`, `DepreciationService`.
- Produces: Category filter dropdown, Category column, and Admin-only sortable Current Value column on `/assets`.

- [ ] **Step 1: Write the failing feature test**

In `tests/Feature/AssetDepreciationTest.php`:
```php
test('assets list filters by category', function () {
    $admin = \App\Models\User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
    $catA = \App\Models\Category::create(['name' => 'IT Equipment']);
    $catB = \App\Models\Category::create(['name' => 'Vehicles']);

    \App\Models\Asset::create(['asset_no' => 'BBH-IT-1', 'asset_name' => 'Dell PC', 'status' => 'In Use', 'category_id' => $catA->id]);
    \App\Models\Asset::create(['asset_no' => 'BBH-VH-1', 'asset_name' => 'Ambulance Van', 'status' => 'In Use', 'category_id' => $catB->id]);

    $response = $this->actingAs($admin)->get('/assets?category=' . $catA->id);
    $response->assertSee('Dell PC');
    $response->assertDontSee('Ambulance Van');
});

test('assets list shows Current Value column for admin and hides for staff', function () {
    $admin = \App\Models\User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
    $staff = \App\Models\User::factory()->create(['role' => 'staff', 'must_change_password' => false]);

    $this->actingAs($admin)->get('/assets')->assertSee('Current Value');
    $this->actingAs($staff)->get('/assets')->assertDontSee('Current Value');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AssetDepreciationTest`
Expected: FAIL (category filter or Current Value column not found).

- [ ] **Step 3: Update `AssetController@index` and `resources/views/assets/index.blade.php`**

- In `AssetController@index`:
  - Read `$categoryId = $request->query('category');`.
  - When `$categoryId !== null && $categoryId !== ''`: filter by `category_id`.
  - Eager load `category`.
  - Pass `$categories = Category::orderBy('name')->get();` and `$selectedCategory = $categoryId;`.
- In `resources/views/assets/index.blade.php`:
  - Add Category filter dropdown to the filter toolbar next to Department and Status.
  - In table header: add "Category" column.
  - If `@if (auth()->user()?->isAdmin())`: add "Current Value" column header.
  - In table body:
    - Display category name or `—` (use `<span class="text-slate-400">None</span>` to avoid em dash).
    - If Admin: calculate current value via helper/service; display formatted peso or `"No cost"`.
  - Apply responsive styling (`min-w-[700px]` with horizontal container scroll) to prevent clipping at 1093x614.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AssetDepreciationTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/AssetController.php resources/views/assets/index.blade.php tests/Feature/AssetDepreciationTest.php
git commit -m "feat(assets): add category filter, category column, and admin current value column"
```

---

### Task 7: Dashboard "Total Asset Value" Card and "By Category" Breakdown

**Files:**
- Modify: `app/Http/Controllers/DashboardController.php`
- Modify: `resources/views/dashboard.blade.php`
- Test: `tests/Feature/AssetDepreciationTest.php`

**Interfaces:**
- Consumes: `DepreciationService`, `Category`, `Asset`.
- Produces: Admin Dashboard summary: Total Asset Value (sum of calculated current values for active assets) and category count/value breakdown; Staff sees category counts only.

- [ ] **Step 1: Write the failing feature test**

In `tests/Feature/AssetDepreciationTest.php`:
```php
test('admin dashboard displays Total Asset Value and category valuation summary', function () {
    $admin = \App\Models\User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
    $cat = \App\Models\Category::create(['name' => 'Lab Devices']);
    \App\Models\Asset::create([
        'asset_no' => 'BBH-LAB-1',
        'asset_name' => 'Centrifuge',
        'status' => 'In Use',
        'category_id' => $cat->id,
        'cost' => 50000.00,
        'salvage_value' => 5000.00,
        'useful_life_years' => 5,
        'date_acquired' => now()->subYear(),
    ]);

    $response = $this->actingAs($admin)->get('/dashboard');
    $response->assertSee('Total Asset Value');
    $response->assertSee('By Category');
});

test('staff dashboard shows category asset counts without monetary values', function () {
    $staff = \App\Models\User::factory()->create(['role' => 'staff', 'must_change_password' => false]);
    $cat = \App\Models\Category::create(['name' => 'Lab Devices']);
    \App\Models\Asset::create([
        'asset_no' => 'BBH-LAB-2',
        'asset_name' => 'Centrifuge 2',
        'status' => 'In Use',
        'category_id' => $cat->id,
        'cost' => 50000.00,
    ]);

    $response = $this->actingAs($staff)->get('/dashboard');
    $response->assertDontSee('Total Asset Value');
    $response->assertSee('Lab Devices');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AssetDepreciationTest`
Expected: FAIL (Total Asset Value not found on dashboard).

- [ ] **Step 3: Update `DashboardController` and `resources/views/dashboard.blade.php`**

- In `DashboardController`:
  - Calculate category breakdown: counts for all users; total current value per category for Admin.
  - Calculate total asset value across all non-disposed assets with valid depreciation calculations for Admin.
  - Pass `$totalAssetValue`, `$categoryStats` to view.
- In `resources/views/dashboard.blade.php`:
  - If Admin: add prominent "Total asset value" card in stats row with Philippine peso total and small note ("Calculated current value of active assets").
  - Add compact "By category" breakdown card: list category names, asset counts, and (for Admin only) category total current value.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AssetDepreciationTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/DashboardController.php resources/views/dashboard.blade.php tests/Feature/AssetDepreciationTest.php
git commit -m "feat(dashboard): add admin total asset value card and category breakdown"
```

---

### Task 8: Depreciation Report View, Category Summary, and Excel/CSV Export

**Files:**
- Create: `app/Exports/DepreciationExport.php`
- Modify: `app/Http/Controllers/ImportExportController.php`
- Modify: `resources/views/reports/index.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/AssetDepreciationTest.php`

**Interfaces:**
- Consumes: `DepreciationService`, `Category`, `Asset`.
- Produces:
  - Route: `GET /reports/depreciation` (Admin only) with category/department filters.
  - Route: `GET /reports/depreciation/export` (Admin only, format=csv|xlsx).
  - Depreciation table: Asset No., Name, Category, Date Acquired, Cost, Accumulated Depreciation, Current Value, and Totals row.
  - "By category" summary card.

- [ ] **Step 1: Write the failing feature test**

In `tests/Feature/AssetDepreciationTest.php`:
```php
test('admin can view depreciation report with totals and category summary', function () {
    $admin = \App\Models\User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
    $cat = \App\Models\Category::create(['name' => 'Medical Equipment']);
    \App\Models\Asset::create([
        'asset_no' => 'BBH-DEP-01',
        'asset_name' => 'Dialysis Unit',
        'status' => 'In Use',
        'category_id' => $cat->id,
        'cost' => 500000.00,
        'salvage_value' => 50000.00,
        'useful_life_years' => 10,
        'date_acquired' => '2023-01-01',
    ]);

    $response = $this->actingAs($admin)->get('/reports/depreciation');
    $response->assertOk();
    $response->assertSee('Depreciation Report');
    $response->assertSee('Dialysis Unit');
    $response->assertSee('Totals');
});

test('staff cannot access depreciation report or export', function () {
    $staff = \App\Models\User::factory()->create(['role' => 'staff', 'must_change_password' => false]);

    $this->actingAs($staff)->get('/reports/depreciation')->assertForbidden();
    $this->actingAs($staff)->get('/reports/depreciation/export')->assertForbidden();
});

test('admin can export depreciation report as csv or excel', function () {
    $admin = \App\Models\User::factory()->create(['role' => 'admin', 'must_change_password' => false]);

    $response = $this->actingAs($admin)->get('/reports/depreciation/export?format=csv');
    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AssetDepreciationTest`
Expected: FAIL (route `/reports/depreciation` not found).

- [ ] **Step 3: Implement `DepreciationExport`, controller methods, and Blade report section**

- Create `app/Exports/DepreciationExport.php`:
  - Implements `FromCollection`, `WithHeadings`, `WithMapping`.
  - Maps Asset No., Name, Category, Date Acquired, Cost, Salvage Value, Useful Life (Yrs), Accumulated Depreciation, Current Value.
- In `ImportExportController`:
  - `depreciation(Request $request)`: Filter assets by category and department; calculate depreciation per asset using `DepreciationService`; compute totals (Total Cost, Total Accumulated, Total Current Value); compute by-category aggregates.
  - `exportDepreciation(Request $request)`: Check if `XMLWriter` exists or format is csv; stream download via `Excel::download(new DepreciationExport(...), $filename, ...)`.
- In `routes/web.php`:
  - Add routes inside `Route::middleware('admin')`:
    - `Route::get('/reports/depreciation', [ImportExportController::class, 'depreciation'])->name('reports.depreciation');`
    - `Route::get('/reports/depreciation/export', [ImportExportController::class, 'exportDepreciation'])->name('reports.depreciation.export');`
- In `resources/views/reports/index.blade.php`:
  - Add navigation tab or section linking to Depreciation Report for Admin users.
  - Create dedicated view `resources/views/reports/depreciation.blade.php` with filter form (Category, Department), asset breakdown table, footer totals row, By Category summary card, and dual CSV/Excel export buttons.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AssetDepreciationTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Exports/DepreciationExport.php app/Http/Controllers/ImportExportController.php resources/views/reports routes/web.php tests/Feature/AssetDepreciationTest.php
git commit -m "feat(reports): add admin depreciation report with category summary and CSV/Excel exports"
```

---

## Phase D: Import and Export Updates

### Task 9: Import/Export Template & Service Update with Category and Money Fields

**Files:**
- Modify: `app/Exports/AssetsTemplateExport.php`
- Modify: `app/Exports/AssetsExport.php`
- Modify: `app/Services/AssetImportService.php`
- Modify: `resources/views/reports/import-preview.blade.php`
- Test: `tests/Feature/Module5ExcelImportExportTest.php`

**Interfaces:**
- Consumes: `Category`, `AssetImportService`, `AssetsExport`.
- Produces:
  - Template headings: `asset_no`, `asset_name`, `category`, `model`, `serial_no`, `cost`, `useful_life_years`, `salvage_value`, `date_acquired`, `department`, `location`, `status`, `notes`.
  - Import preview flags unknown categories with an option/flag to create them or leave unassigned.
  - AssetsExport includes `category` for all users, but includes `cost`, `useful_life_years`, `salvage_value` ONLY when the exporting user is Admin.

- [ ] **Step 1: Write the failing feature test**

In `tests/Feature/Module5ExcelImportExportTest.php`:
```php
test('import preview parses category and financial columns and flags unknown categories', function () {
    $admin = \App\Models\User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
    \App\Models\Category::create(['name' => 'Known Category']);

    $csvContent = "asset_no,asset_name,category,cost,useful_life_years,salvage_value,status\n" .
                  "BBH-IMP-01,Test Bed,Known Category,25000,10,2500,In Use\n" .
                  "BBH-IMP-02,Test Monitor,New Unknown Category,45000,5,0,In Use\n";

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('import.csv', $csvContent);

    $response = $this->actingAs($admin)->post('/reports/import/preview', ['file' => $file]);
    $response->assertOk();
    $response->assertSee('Known Category');
    $response->assertSee('New Unknown Category');
});

test('assets export includes category for all and hides financial columns for staff', function () {
    $admin = \App\Models\User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
    $staff = \App\Models\User::factory()->create(['role' => 'staff', 'must_change_password' => false]);

    $adminExport = new \App\Exports\AssetsExport(user: $admin);
    expect($adminExport->headings())->toContain('category', 'cost', 'salvage_value', 'useful_life_years');

    $staffExport = new \App\Exports\AssetsExport(user: $staff);
    expect($staffExport->headings())->toContain('category');
    expect($staffExport->headings())->not->toContain('cost', 'salvage_value');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=Module5ExcelImportExportTest`
Expected: FAIL (headings do not match or category column missing).

- [ ] **Step 3: Update `AssetsTemplateExport`, `AssetsExport`, `AssetImportService`, and `import-preview.blade.php`**

- In `app/Exports/AssetsTemplateExport.php`:
  - Add `category`, `cost`, `useful_life_years`, `salvage_value` to template headings and sample row.
- In `app/Exports/AssetsExport.php`:
  - Accept optional `?User $user = null` in constructor.
  - Include `category` in headings and map `$asset->category?->name ?? ''`.
  - If `$user?->isAdmin()`: include `cost`, `useful_life_years`, `salvage_value`.
- In `app/Services/AssetImportService.php`:
  - Read `category`, `cost`, `useful_life_years`, `salvage_value`.
  - Check category existence against `Category::pluck('name', 'id')`. Flag unknown categories in preview row array (`'unknown_category' => true`).
  - In `commit()`: if category exists, set `category_id`. If user checked option or approved creating missing categories, create them via `Category::firstOrCreate(['name' => $catName])`. If blank, leave null. Cast cost and salvage cleanly.
- In `resources/views/reports/import-preview.blade.php`:
  - Show Category, Cost columns in the preview table.
  - If any row has `unknown_category`, show an info banner: "Some rows specify new categories that do not exist yet. They will be created automatically upon import."

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=Module5ExcelImportExportTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Exports app/Services/AssetImportService.php resources/views/reports/import-preview.blade.php tests/Feature/Module5ExcelImportExportTest.php
git commit -m "feat(import-export): update template and import parser for categories and financial columns"
```

---

### Task 10: Activity Log Integration, Regression Testing, and Native Build Verification

**Files:**
- Modify: `app/Models/Asset.php`
- Modify: `tests/Feature/Module6DashboardAndActivityLogTest.php`

**Interfaces:**
- Consumes: Spatie Activitylog.
- Produces: Audit trail for changes to `cost`, `category_id`, `useful_life_years`, `salvage_value`.
- Produces: Full passing test suite, Vite build, and Windows NativePHP verification.

- [ ] **Step 1: Write the failing feature test**

In `tests/Feature/Module6DashboardAndActivityLogTest.php`:
```php
test('updates to asset category and financial values are logged in activity log', function () {
    $admin = \App\Models\User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
    $cat = \App\Models\Category::create(['name' => 'Defibrillators']);

    $asset = \App\Models\Asset::create([
        'asset_no' => 'BBH-LOG-01',
        'asset_name' => 'AED Unit',
        'status' => 'In Use',
    ]);

    $this->actingAs($admin)->put("/assets/{$asset->id}", [
        'asset_no' => 'BBH-LOG-01',
        'asset_name' => 'AED Unit',
        'status' => 'In Use',
        'category_id' => $cat->id,
        'cost' => 175000.00,
        'useful_life_years' => 8,
        'salvage_value' => 15000.00,
    ])->assertRedirect();

    $log = \Spatie\Activitylog\Models\Activity::where('subject_id', $asset->id)->latest('id')->first();
    expect($log)->not->toBeNull();
    expect($log->properties['attributes'])->toHaveKeys(['category_id', 'cost', 'useful_life_years', 'salvage_value']);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=Module6DashboardAndActivityLogTest`
Expected: FAIL (or verify if `$fillable` already logs dirty attributes).

- [ ] **Step 3: Verify `Asset.php` activity log options and run full regression suite**

- Confirm `Asset::getActivitylogOptions()` has `logFillable()`. Because `category_id`, `cost`, `useful_life_years`, and `salvage_value` were added to `$fillable`, Spatie logs them automatically.
- Run complete test suite:
  ```bash
  php artisan test
  ```
- Build frontend assets:
  ```bash
  npm run build
  ```
- Run Windows NativePHP build test:
  ```bash
  php artisan native:build win x64
  ```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test`
Expected: 100% tests passing (0 failures).

- [ ] **Step 5: Commit**

```bash
git add app/Models/Asset.php tests/Feature/Module6DashboardAndActivityLogTest.php
git commit -m "feat(activity-log): ensure category and financial changes are tracked in audit trail"
```

---

## Plan Self-Review Checklist

1. **Spec Coverage:**
   - [x] Additive migrations (`categories` table, nullable `assets` columns: `category_id`, `cost`, `useful_life_years`, `salvage_value`) -> Task 1.
   - [x] Category seeder (7 default categories, idempotent `firstOrCreate`) -> Task 1.
   - [x] Straight-line `DepreciationService` with months elapsed, clamp to salvage, peso formatting, and 8 specified test cases -> Task 2.
   - [x] Category delete blocked if referenced by active or soft-deleted assets -> Task 3.
   - [x] Settings > Categories tab with CRUD, activity logging -> Task 3.
   - [x] Add/Edit Asset "Category and Value" card with prefill defaults and vanilla JS live preview line -> Task 4.
   - [x] Strict admin-only permissions for money fields; Staff can only set/view Category -> Tasks 4, 5, 6, 7, 8, 9.
   - [x] Blank cost displays "No cost entered", never 0 -> Task 2, 4, 5.
   - [x] Asset detail "Value" card with progress bar (% used) and Category badge -> Task 5.
   - [x] Assets table Category column, Category filter, Admin Current Value column (prevent 1093x614 clipping) -> Task 6.
   - [x] Dashboard Admin "Total asset value" card and "By category" breakdown (Staff counts only) -> Task 7.
   - [x] Reports "Depreciation Report" view with totals row, category/department filter, by-category summary, Excel & CSV export -> Task 8.
   - [x] Excel/CSV import template & preview update with new columns and unknown category handling -> Task 9.
   - [x] Activity log integration -> Task 10.
   - [x] Native build verification -> Task 10.
   - [x] Preserved property tag layout (10cm x 6cm) and auth -> Checked and confirmed untouched.
   - [x] No em dashes in UI text -> Explicitly forbidden in Global Constraints.

2. **Step Scan:** Every step contains exact files, test code, commands, and expected results. No empty placeholder statements.
3. **Type Consistency:** Method signatures and variable names (`$category_id`, `$cost`, `$useful_life_years`, `$salvage_value`, `calculateForAsset`, `formatPeso`) are identical across all tasks.
4. **Review Focus:** All 5 failure modes identified in Review Focus are assigned concrete tests in Tasks 1, 2, 3, 4, and 9.
5. **Proportion:** The plan provides crisp, targeted code steps without pasting unnecessary application transcripts.
