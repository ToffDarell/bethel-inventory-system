# Client Asset Fields Implementation Plan (v1.2.0)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans (Native execution in phases) to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add Section, Assigned Personnel, Brand, and Asset Condition to Bethel Asset Tracker to match the client's asset detail list, rename select UI labels, update Year Bought handling, and bump the application version to 1.2.0 with strictly additive migrations.

**Architecture:** Additive SQLite schema migration introduces nullable columns (`brand`, `section`, `assigned_personnel`, `asset_condition`) on `assets`. `Year Bought` reuses the existing `date_acquired` column stored as `YYYY-01-01`. Dedicated model constants validate `Asset Condition` (`Brand-new`, `Refurbish`, `Damage`, `In Good Condition`). Forms, tables, search endpoints, and spreadsheet import/export services are updated with backward-compatible column mapping.

**Tech Stack:** Laravel 13, NativePHP Desktop v2 (Electron + SQLite), Blade + Tailwind CSS v4, Maatwebsite Excel (CSV/XLSX), Spatie Activitylog, Pest PHP, Playwright.

**Spec:** Client Field List specification provided in prompt.

---

## Global Constraints

- **Additive migrations only:** Never drop or rename existing tables or columns; client records must remain intact.
- **Do NOT add a `year_bought` column:** Reuses the existing `date_acquired` column stored as `YYYY-01-01`.
- **Condition values exact match:** Exactly `Brand-new`, `Refurbish`, `Damage`, `In Good Condition`.
- **Status stays separate:** Keep `status` (`In Use`, `Under Repair`, `Stored`, `Disposed`) intact; never merge with `asset_condition`.
- **Labels (UI only):** Asset Name shows as "Asset Description", Notes shows as "Remarks", Serial No. shows as "Serial Number", Date Acquired shows as "Year Bought".
- **Preserved modules:** Do not modify authentication, SQLite backup/restore routines, or the 10cm x 6cm printed property tag layout.
- **Copy and typography:** No em dashes in UI text.
- **Role security:** Staff users must never see cost, salvage value, or depreciation anywhere.
- **Git workflow rule:** Never run `git add` or `git commit`. Keep all modified files unstaged for user review.

---

## Review Focus

1. **Year Bought preservation when editing:** When an asset with a full historical date (e.g. `2021-08-14`) is edited without changing the year input (`2021`), the original `2021-08-14` date must not be overwritten with `2021-01-01`.
2. **Depreciation calculation on year-only acquisition date:** When an asset has `date_acquired` set to `YYYY-01-01` via Year Bought input, `DepreciationService` must accurately calculate straight-line depreciation elapsed from January 1 of that year.
3. **Import preview unknown condition handling:** When an Excel/CSV file imports an unrecognized condition value, the preview must flag it with a warning badge and not silently alter or drop the record.
4. **Column visibility preference persistence:** When a user toggles Brand or Assigned Personnel columns on `/assets`, the preference must persist across page loads in `localStorage` without layout shifting or horizontal clipping at `1093x614`.
5. **Staff financial redaction across all new views:** Staff members viewing the asset form, detail view, export, or audit logs must never see cost or depreciation fields.

---

## Phase A: Migrations, Model Constants, Depreciation & Seeders

### Task 1: Additive Migration & Model Setup
**Files:**
- Create: `database/migrations/2026_11_04_000003_add_client_fields_to_assets_table.php`
- Modify: `app/Models/Asset.php`
- Modify: `database/factories/AssetFactory.php`
- Test: `tests/Feature/AssetClientFieldsMigrationTest.php`

**Interfaces:**
- Produces: `Asset::CONDITIONS = ['Brand-new', 'Refurbish', 'Damage', 'In Good Condition']`
- Produces: Nullable columns `brand`, `section`, `assigned_personnel`, `asset_condition` in `assets` table

- [ ] **Step 1: Write the failing migration and model test**
```php
test('assets table has new client nullable columns and condition constants', function () {
    expect(Asset::CONDITIONS)->toBe(['Brand-new', 'Refurbish', 'Damage', 'In Good Condition']);
    $asset = Asset::create([
        'asset_no' => 'BBH-TEST-001',
        'asset_name' => 'Centrifuge',
        'brand' => 'Eppendorf',
        'model' => '5424R',
        'section' => 'Hematology',
        'assigned_personnel' => 'Dr. Cruz',
        'asset_condition' => 'Brand-new',
        'status' => 'In Use',
    ]);
    expect($asset->brand)->toBe('Eppendorf')
        ->and($asset->section)->toBe('Hematology')
        ->and($asset->assigned_personnel)->toBe('Dr. Cruz')
        ->and($asset->asset_condition)->toBe('Brand-new');
});
```

- [ ] **Step 2: Run test to verify failure**
Run: `php vendor/pestphp/pest/bin/pest tests/Feature/AssetClientFieldsMigrationTest.php`
Expected: FAIL (missing columns / constants).

- [ ] **Step 3: Implement migration, update `Asset.php` and `AssetFactory.php`**
- Create additive migration `2026_11_04_000003_add_client_fields_to_assets_table.php` adding nullable `brand`, `section`, `assigned_personnel`, `asset_condition`.
- Add `CONDITIONS` constant and fillable attributes to `app/Models/Asset.php`.
- Run `php artisan migrate`.

- [ ] **Step 4: Run test to verify pass**
Run: `php vendor/pestphp/pest/bin/pest tests/Feature/AssetClientFieldsMigrationTest.php`
Expected: PASS.

- [ ] **Step 5: Verify migration safety on copy of existing database**
Run script to copy current database, run migration, and assert existing records are preserved and brand starts blank.

---

### Task 2: DepreciationService Year-Only Support & Test Cases
**Files:**
- Modify: `app/Services/DepreciationService.php`
- Test: `tests/Unit/DepreciationServiceTest.php`

- [ ] **Step 1: Update unit tests in `tests/Unit/DepreciationServiceTest.php`**
Add/update tests for:
1. Year-only acquisition (cost 120,000, salvage 10,000, life 10 years, bought 3 years ago on January 1 = 87,000.00).
2. Full-date acquisition case.
3. Future year acquisition date.
4. Missing acquisition date ("Missing acquisition date" / "Not enough data").

- [ ] **Step 2: Run test to verify behavior**
Run: `php vendor/pestphp/pest/bin/pest tests/Unit/DepreciationServiceTest.php`
Expected: PASS.

- [ ] **Step 3: Phase A Verification Gate**
Run: `php vendor/pestphp/pest/bin/pest` and `npm run build`.

---

## Phase B: Add/Edit Asset Form, Validation & Detail View

### Task 3: Form Card Restructuring & Year Bought Handling
**Files:**
- Modify: `resources/views/assets/partials/form-fields.blade.php`
- Modify: `resources/views/assets/create.blade.php`
- Modify: `resources/views/assets/edit.blade.php`
- Modify: `app/Http/Controllers/AssetController.php`
- Test: `tests/Feature/AssetClientFieldsFormTest.php`

**Layout Sequence:**
1. **Identification:** Asset No., Asset Description (`asset_name`), Brand, Model, Serial Number (`serial_no`), Category.
2. **Assignment:** Department (datalist), Section (datalist), Location, Assigned Personnel (datalist).
3. **Condition and Status:** Status dropdown and Asset Condition dropdown side by side on wide screens.
4. **Purchase & Financials:** Year Bought (4-digit year input, 1950 to current year) for everyone; Cost, Useful life, Salvage value, and live estimate for Admin only.
5. **Remarks:** Remarks (`notes`, textarea).

- [ ] **Step 1: Write failing feature test for Add/Edit validation and Year Bought preservation**
Verify:
- `asset_condition` is required on store/update.
- Submitting `year_bought: 2023` stores `2023-01-01` in `date_acquired`.
- Updating asset without changing year preserves existing full date.
- Staff submissions discard monetary fields.

- [ ] **Step 2: Run test to verify failure**
Run: `php vendor/pestphp/pest/bin/pest tests/Feature/AssetClientFieldsFormTest.php`

- [ ] **Step 3: Implement controller validation and form fields**
- In `AssetController`: validate `brand`, `section`, `assigned_personnel`, and required `asset_condition`. Add logic for `year_bought` & `date_acquired` mapping. Pass distinct `sections` and `assigned_personnel` lists for autocompletion.
- In `form-fields.blade.php`: reconstruct the 5 cards with shortened plain-sentence helper text.
- Add condition badges component or partial.

- [ ] **Step 4: Run test to verify pass**
Run: `php vendor/pestphp/pest/bin/pest tests/Feature/AssetClientFieldsFormTest.php`
Expected: PASS.

---

### Task 4: Asset Detail Page Updates
**Files:**
- Modify: `resources/views/assets/show.blade.php`
- Modify: `resources/views/components/badge.blade.php` (or create condition badge helper)

- [ ] **Step 1: Update `show.blade.php`**
- Show Asset Condition badge next to Status badge in header:
  - `Brand-new`: soft blue
  - `In Good Condition`: soft green
  - `Refurbish`: soft amber
  - `Damage`: soft red tint (never a solid red block)
- Display grouped fields: Brand, Model, Serial Number, Section, Assigned Personnel, Year Bought (year only), Remarks.
- Ensure staff users see no monetary fields.

- [ ] **Step 2: Browser Verification via Playwright**
- Capture screenshots of Add Asset, Edit Asset, and Asset Detail at `1366x768` and `1093x614` as Admin and as Staff.

- [ ] **Step 3: Phase B Verification Gate**
Run: `php vendor/pestphp/pest/bin/pest` and `npm run build`.

---

## Phase C: Asset List, Search, Filters & Dashboard

### Task 5: Assets List Columns, Toggle & Filters
**Files:**
- Modify: `resources/views/assets/index.blade.php`
- Modify: `app/Http/Controllers/AssetController.php`
- Test: `tests/Feature/AssetClientFieldsListTest.php`

- [ ] **Step 1: Write failing feature test for filters and list columns**
Verify:
- Filter by `condition` and `section`.
- Default table columns include Asset Condition badge and Section.
- Quick search finds records by brand, section, and assigned personnel.

- [ ] **Step 2: Implement list filters and column toggle**
- Add Section and Asset Condition dropdowns to the sticky filter bar.
- Add "Columns" toggle menu for Brand and Assigned Personnel with `localStorage` memory (`bethel_assets_columns_prefs`).
- Ensure no clipping or horizontal overflow at `1093x614`.

- [ ] **Step 3: Update Global Search and Find Asset**
- In `routes/web.php` (`quick-search`) and `app/Http/Controllers/ScanController.php`: include `brand`, `section`, `assigned_personnel`.

---

### Task 6: Dashboard Attention Alert for Damaged Assets
**Files:**
- Modify: `app/Http/Controllers/DashboardController.php`
- Modify: `resources/views/dashboard.blade.php`

- [ ] **Step 1: Add Damaged assets count and link**
- Query count of active assets where `asset_condition = 'Damage'`.
- In Dashboard "Needs attention" section: add "N assets in Damage condition" linking to `/assets?condition=Damage`.

- [ ] **Step 2: Browser Verification via Playwright**
- Capture `/assets` and `/dashboard` screenshots at `1366x768` and `1093x614`.

- [ ] **Step 3: Phase C Verification Gate**
Run: `php vendor/pestphp/pest/bin/pest` and `npm run build`.

---

## Phase D: Import, Export, Activity Log & Release v1.2.0

### Task 7: Excel Import & Export Updates
**Files:**
- Modify: `app/Services/AssetImportService.php`
- Modify: `app/Exports/AssetsExport.php`
- Modify: `app/Exports/AssetsTemplateExport.php`
- Modify: `resources/views/reports/preview.blade.php`
- Test: `tests/Feature/AssetClientFieldsImportExportTest.php`

- [ ] **Step 1: Write failing import/export tests**
- Verify import handles `asset_description`, `brand`, `model`, `serial_number`, `department`, `section`, `assigned_personnel`, `category`, `year_bought`, `asset_condition`, `status`, `remarks`.
- Verify backward compatibility for old headers.
- Verify unknown condition values are flagged in preview without crashing.

- [ ] **Step 2: Implement import mapping, preview flagging, and export columns**
- Update `AssetImportService` column resolution and validation.
- Update `AssetsExport` and `AssetsTemplateExport`.
- Log changes in Spatie Activitylog.

---

### Task 8: Version Bump to 1.2.0 & Desktop Build Verification
**Files:**
- Modify: `package.json` (version `1.2.0`)
- Modify: `config/nativephp.php` (version `1.2.0`)
- Modify: `resources/views/settings/index.blade.php` (displays `v1.2.0`)

- [ ] **Step 1: Version update**
- Bump versions to `1.2.0`.
- Run full test suite: `php vendor/pestphp/pest/bin/pest`.
- Run frontend build: `npm run build`.

- [ ] **Step 2: Desktop build & migration check**
- Run `php artisan native:build win x64`.
- Verify installer build output and test database upgrade retention.

- [ ] **Step 3: Phase D Final Review Subagent**
- Dispatch review subagent to inspect the unstaged diff, test coverage, and visual screenshots.

---

## Deliverables Checklist per Phase

| Phase | Automated Tests | Frontend Build | Playwright Screenshots | Specific Artifacts |
|---|---|---|---|---|
| **Phase A** | Pest unit & feature tests pass | `npm run build` | N/A (Backend) | Migration safety test proof on database copy |
| **Phase B** | Form validation & Year Bought tests pass | `npm run build` | Add/Edit & Show at 1366x768 & 1093x614 (Admin + Staff) | Screenshots of new 5-card form layout & condition badges |
| **Phase C** | Filter & column toggle tests pass | `npm run build` | Index & Dashboard at 1366x768 & 1093x614 | Column toggle verified at narrow viewports |
| **Phase D** | Import/Export tests pass | `npm run build` | Import preview with condition badge | Installer `.exe` built, v1.2.0 bump confirmed |
