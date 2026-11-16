# Bethel Asset Tracker v1.3.0 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan phase-by-phase. Wait for user "go" before Phase A, and wait for "next" after Phase A, B, and C. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement three core client enhancements for Bethel Asset Tracker: category-driven depreciation with disposal value terminology, department-scoped atomic asset numbering with historical lineage and reprint tracking, and a streamlined institutional top navigation bar replacing the collapsible sidebar.

**Architecture:** 
1. **Depreciation & Valuation Engine**: Centralize useful life and disposal percent lookups on `Category` (with global setting fallback). Deprecate per-asset override inputs on Create/Edit screens while keeping database columns intact. Present a read-only live preview calculated on the fly.
2. **Department Registry & Concurrency-Safe Numbering**: Add an additive `departments` table and `asset_number_histories` audit table. Implement database transactions with exclusive SQLite write locks (`DB::transaction` with row locking / retry) for atomic sequence allocation (`{PREFIX}{DEPT_CODE}-{SEQ}`). Implement optimistic concurrency locking on Asset edit forms via `updated_at` timestamp verification.
3. **Institutional Top Navigation**: Replace sidebar layout with an accessible, 56px to 64px fixed top context bar housing logo, primary navigation items with icons, a prominent Add Asset action, and account menu, fully responsive down to 1093x614.

**Tech Stack:** Laravel 11, PHP 8.2+ / 8.5, SQLite 3 (WAL mode), Blade, Tailwind CSS v4, Dompdf, PhpSpreadsheet, NativePHP (Electron desktop).

**Spec:** User prompt instructions for Phases A, B, and C (2026-11-09).

## Global Constraints

- Do not run `git add` or `git commit`.
- Do not touch `%APPDATA%` folder (operate on temporary database copies for test simulations).
- Additive database migrations only; zero dropped columns, zero dropped tables, zero data loss.
- Staff Financial Shield: Staff role must never see monetary values, purchase costs, accumulated depreciation, disposal values, or current values anywhere in views, JSON responses, or exports. Add specific tests for every new view and endpoint.
- Locked Design Tokens: Primary charcoal `#0F172A`, clinical brand red `#B91C1C` strictly for danger/destructive actions, Inter typography, tabular numbers on all metrics, badges matching institutional palette.
- Copy Standard: Zero em dashes in UI text, tooltips, placeholders, or reports.
- Zero External Dependencies: No new composer packages, npm packages, or CDN links.
- Real Data Integrity: Every counter, metric, and percentage on screen computed from real database state.
- Quality Gates: Run full test suite (`php artisan test`) and frontend compilation (`npm run build`) after each phase, capturing layout verification screenshots at both 1366x768 and 1093x614.

## Review Focus

1. **Staff Financial Shield Leak**: Ensure new department views, asset history timelines, and category rule confirmation dialogs never leak asset purchase costs, disposal values, or depreciation amounts to staff users. Pinning test in Task A5 and Task B8.
2. **Concurrent Sequence Collision**: Ensure parallel requests creating assets within the same department cannot generate identical asset numbers under high concurrency. Pinning test in Task B8.
3. **Optimistic Locking Conflict**: Ensure two users editing the same asset concurrently do not overwrite each other silently; the second submitter must receive a clean, friendly notification that the record was modified. Pinning test in Task B8.
4. **Historical Asset Number Resolution**: Ensure global search, Find Asset, and barcode/QR tag lookups seamlessly resolve retired numbers to the active asset record with an "Old number" indicator. Pinning test in Task B8.
5. **Horizontal Viewport Constraint at 1093x614**: Ensure the new top navigation bar and all data screens fit cleanly on 1093px wide displays without wrapping, truncation, or horizontal workspace overflow. Pinning verification in Task C4.

---

## Phase A: Depreciation from Category and Cost, and "Disposal Value"

### Task A1: Terminology Rename ("Salvage value" to "Disposal value")

**Files:**
- Modify: `resources/views/reports/depreciation.blade.php`
- Modify: `resources/views/reports/index.blade.php`
- Modify: `resources/views/settings/index.blade.php`
- Modify: `resources/views/assets/show.blade.php`
- Modify: `app/Exports/Sheets/DepreciationMainSheet.php`
- Modify: `app/Exports/Sheets/DepreciationRawCsvSheet.php`
- Modify: `app/Exports/AssetsTemplateExport.php`
- Modify: `app/Services/DepreciationService.php`
- Test: `tests/Feature/AssetDepreciationTest.php`
- Test: `tests/Feature/ProfessionalExcelExportsTest.php`

**Interfaces:**
- Consumes: Existing export and report views.
- Produces: UI labels, export headers, and settings labels using "Disposal value" and "Disposal value %". Import parsers accept both `disposal_value` / `disposal` and legacy `salvage_value` / `salvage` headers.

- [ ] **Step 1: Write failing test for terminology and backward-compatible import headers**
  Verify exports and reports render "Disposal value" and import accepts `salvage_value` as an alias.
- [ ] **Step 2: Run test to verify failure**
  Run: `php artisan test --filter=AssetDepreciationTest`
- [ ] **Step 3: Update views, export sheets, templates, and import aliases**
  Rename "Salvage value" -> "Disposal value" in Blade templates and export headers. Update import mapping to accept `salvage` and `salvage_value` aliases.
- [ ] **Step 4: Run test to verify passes**
  Run: `php artisan test --filter=AssetDepreciationTest`

---

### Task A2: Category Rule Requirements, Confirmation Dialog, and Activity Log

**Files:**
- Modify: `app/Http/Controllers/SettingController.php`
- Modify: `resources/views/settings/index.blade.php`
- Modify: `app/Models/Category.php`
- Test: `tests/Feature/CategorySettingsTest.php`

**Interfaces:**
- Consumes: `Category` model, `SettingController::updateCategory`.
- Produces: Required validation on `useful_life_years` and `default_salvage_percent` (disposal percent). Modal dialog warning "This changes the estimated values of N assets" before applying changes. Audit log entry recorded via `activity()`.

- [ ] **Step 1: Write failing test for category validation, asset impact count, and activity log**
  Test that category update requires useful life (> 0) and disposal percent (0-100), and records an activity log entry with before and after parameters.
- [ ] **Step 2: Run test to verify failure**
  Run: `php artisan test --filter=CategorySettingsTest`
- [ ] **Step 3: Implement controller validation and impact count query**
  In `SettingController`, count active and archived assets using the category. In `settings/index.blade.php`, add confirmation modal showing impacted asset count before submitting.
- [ ] **Step 4: Run test to verify passes**
  Run: `php artisan test --filter=CategorySettingsTest`

---

### Task A3: Read-Only Simulation Report on Backup Data

**Files:**
- Create: `app/Console/Commands/SimulateDepreciationRulesCommand.php`
- Test: `tests/Feature/DepreciationRuleSimulationTest.php`

**Interfaces:**
- Consumes: SQLite database copy in temp storage.
- Produces: CLI / tabular before and after report showing Asset No., Asset Name, Category, Cost, Old Current Value, New Current Value, and Difference.

- [ ] **Step 1: Create simulation command operating on a temporary database clone**
  Clone current `database/database.sqlite` to `storage/temp/simulation.sqlite`. Calculate current values under old per-asset logic vs new category-driven logic.
- [ ] **Step 2: Execute simulation and print report**
  Run: `php artisan app:simulate-depreciation-rules`
- [ ] **Step 3: Present report to user and wait for explicit OK**
  Present asset-by-asset comparison table to user before enabling category-driven rule in application code.

---

### Task A4: Update `DepreciationService` & Asset Forms

**Files:**
- Modify: `app/Services/DepreciationService.php`
- Modify: `resources/views/assets/create.blade.php`
- Modify: `resources/views/assets/edit.blade.php`
- Modify: `resources/views/assets/show.blade.php`
- Test: `tests/Feature/DepreciationServiceCategoryRuleTest.php`

**Interfaces:**
- Consumes: `Asset::$category_id`, `Asset::$cost`, `Category::$default_useful_life_years`, `Category::$default_salvage_percent`.
- Produces: `DepreciationService::calculateForAsset` deriving useful life and disposal value strictly from category rules. Add and Edit screens remove per-asset inputs and render live read-only preview.

- [ ] **Step 1: Write failing test for category-driven depreciation math**
  Pin exact example from spec: cost 120,000, category life 10 years, disposal 10%, 46 months -> disposal value 12,000, accumulated 41,400, current value 78,600. Test unassigned category/cost returns "Select a category and enter a cost to calculate".
- [ ] **Step 2: Run test to verify failure**
  Run: `php artisan test --filter=DepreciationServiceCategoryRuleTest`
- [ ] **Step 3: Implement calculation logic in `DepreciationService`**
  Derive useful life from `$asset->category?->default_useful_life_years` and disposal value from `round($cost * ($disposalPercent / 100), 2)`.
- [ ] **Step 4: Update Add and Edit asset Blade views**
  Remove editable input fields for salvage value and useful life. Add read-only summary card with real-time JavaScript estimate as category and cost change.
- [ ] **Step 5: Run test to verify passes**
  Run: `php artisan test --filter=DepreciationServiceCategoryRuleTest`

---

### Task A5: Phase A Quality Gate, Staff Shield, and Verification

**Files:**
- Test: `tests/Feature/StaffFinancialShieldTest.php`
- Verify: Full test suite and frontend build.

- [ ] **Step 1: Run complete test suite**
  Run: `php artisan test`
- [ ] **Step 2: Compile frontend assets**
  Run: `npm run build`
- [ ] **Step 3: Capture verification screenshots**
  Take screenshots at 1366x768 and 1093x614.
- [ ] **Step 4: Report Phase A completion and wait for "next"**

---

## Phase B: Asset Numbers by Department

### Task B1: Additive Database Migrations

**Files:**
- Create: `database/migrations/2026_11_10_000001_create_departments_table.php`
- Create: `database/migrations/2026_11_10_000002_add_department_id_and_reprint_to_assets_table.php`
- Create: `database/migrations/2026_11_10_000003_create_asset_number_histories_table.php`
- Modify: `app/Models/Asset.php`
- Create: `app/Models/Department.php`
- Create: `app/Models/AssetNumberHistory.php`
- Test: `tests/Feature/DepartmentMigrationTest.php`

**Interfaces:**
- Consumes: None (new additive tables).
- Produces: `departments` (id, name, code, next_number, active, timestamps), `assets.department_id` (nullable), `assets.needs_tag_reprint` (boolean default false), `asset_number_histories` (id, asset_id, old_no, new_no, reason, changed_by, changed_at, timestamps).

- [ ] **Step 1: Write migration test for additive schema integrity**
- [ ] **Step 2: Run migration test to verify failure**
- [ ] **Step 3: Create migration files and Eloquent models with relations**
- [ ] **Step 4: Run migration test to verify passes**
  Run: `php artisan test --filter=DepartmentMigrationTest`

---

### Task B2: Department Data Backfill & Suggested Codes

**Files:**
- Create: `app/Services/DepartmentBackfillService.php`
- Test: `tests/Feature/DepartmentBackfillTest.php`

**Interfaces:**
- Consumes: Distinct text values from `assets.department`.
- Produces: Backfilled `departments` rows with unique suggested uppercase codes (2-6 chars, alphanumeric), linking `assets.department_id` while keeping `assets.department` string in sync.

- [ ] **Step 1: Write failing test for backfill algorithm**
  Test distinct departments generate clean 2-6 character uppercase codes (e.g., "Emergency Room" -> "ER", "Biomedical Engineering" -> "BIOMED", "Surgery" -> "SURG").
- [ ] **Step 2: Implement `DepartmentBackfillService`**
- [ ] **Step 3: Run test to verify passes**
  Run: `php artisan test --filter=DepartmentBackfillTest`

---

### Task B3: Settings > Departments Tab

**Files:**
- Modify: `resources/views/settings/index.blade.php`
- Modify: `app/Http/Controllers/SettingController.php`
- Test: `tests/Feature/DepartmentSettingsTest.php`

**Interfaces:**
- Consumes: `Department` model, `SettingController`.
- Produces: New "Departments" tab in Settings: add department, rename, update code, toggle active status, and view/adjust sequence counter.

- [ ] **Step 1: Write failing test for Department settings management**
- [ ] **Step 2: Implement Department management in controller and settings tab**
- [ ] **Step 3: Run test to verify passes**
  Run: `php artisan test --filter=DepartmentSettingsTest`

---

### Task B4: Atomic Concurrent Asset Number Generator

**Files:**
- Create: `app/Services/AssetNumberService.php`
- Modify: `config/settings.php` (or `Setting` model defaults)
- Test: `tests/Feature/AssetNumberConcurrentGenerationTest.php`

**Interfaces:**
- Consumes: `Department`, prefix setting (`BBH`), sequence padding setting (default 4 digits), counter scope setting (per-department vs global).
- Produces: `AssetNumberService::generateNextNumber(Department $dept): string` running inside an exclusive transaction lock.

- [ ] **Step 1: Write failing concurrency test with parallel database transactions**
  Simulate two concurrent creations in the same department; assert two distinct, sequential asset numbers are produced.
- [ ] **Step 2: Implement `AssetNumberService` with write lock and retry logic**
- [ ] **Step 3: Run concurrency test to verify passes**
  Run: `php artisan test --filter=AssetNumberConcurrentGenerationTest`

---

### Task B5: Department Transfer, Number History, & Tag Reprint Banner

**Files:**
- Modify: `app/Http/Controllers/AssetController.php`
- Modify: `app/Http/Controllers/AssetMoveController.php`
- Modify: `resources/views/assets/edit.blade.php`
- Modify: `resources/views/assets/move/create.blade.php`
- Modify: `resources/views/assets/show.blade.php`
- Test: `tests/Feature/AssetDepartmentTransferNumberingTest.php`

**Interfaces:**
- Consumes: `Setting::get('auto_reassign_dept_number', true)`.
- Produces: When an asset changes department, automatically assign next department number, write to `asset_number_histories`, set `needs_tag_reprint = true`, and display post-save banner with Print Tag button. Non-standard legacy numbers prompt admin with choice modal ("Keep current number" vs "Assign new number").

- [ ] **Step 1: Write failing test for transfer numbering, history logging, and reprint flag**
- [ ] **Step 2: Implement department change detection, history recording, and reprint prompt**
- [ ] **Step 3: Update Asset show page with number history timeline and reprint banner**
- [ ] **Step 4: Run test to verify passes**
  Run: `php artisan test --filter=AssetDepartmentTransferNumberingTest`

---

### Task B6: Search Resolution, Dashboard Alert, & Optimistic Locking

**Files:**
- Modify: `app/Http/Controllers/GlobalSearchController.php`
- Modify: `app/Http/Controllers/DashboardController.php`
- Modify: `app/Http/Controllers/AssetController.php`
- Modify: `resources/views/dashboard.blade.php`
- Modify: `resources/views/assets/edit.blade.php`
- Test: `tests/Feature/AssetSearchAndOptimisticLockTest.php`

**Interfaces:**
- Consumes: `asset_number_histories`, `assets.needs_tag_reprint`, `assets.updated_at`.
- Produces:
  - Global Search and Find Asset resolve former asset numbers with an "Old number" tag.
  - Dashboard Needs Attention card displays "N tags need reprinting" linking to filtered list. Tag print action clears the flag.
  - Asset edit form passes hidden `original_updated_at`. If `asset->updated_at` changed since form load, abort with friendly conflict notification.

- [ ] **Step 1: Write failing test for old number search, reprint dashboard alert, and optimistic lock rejection**
- [ ] **Step 2: Implement search join on history table, dashboard attention counter, and lock check**
- [ ] **Step 3: Run test to verify passes**
  Run: `php artisan test --filter=AssetSearchAndOptimisticLockTest`

---

### Task B7: Phase B Quality Gate, Staff Shield, and Verification

**Files:**
- Test: Full test suite.
- Verify: Full test suite, frontend build, and responsive screenshots.

- [ ] **Step 1: Run complete test suite**
  Run: `php artisan test`
- [ ] **Step 2: Compile frontend assets**
  Run: `npm run build`
- [ ] **Step 3: Capture verification screenshots at 1366x768 and 1093x614**
- [ ] **Step 4: Report Phase B completion and wait for "next"**

---

## Phase C: Top Navigation Like the Old System

### Task C1: Top Navigation Bar Component

**Files:**
- Create: `resources/views/layouts/navigation.blade.php`
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `resources/css/app.css`
- Test: `tests/Feature/TopNavigationResponsiveTest.php`

**Interfaces:**
- Consumes: Active route matching, authentication state, role permissions.
- Produces: 56px to 64px fixed institutional top bar containing:
  - Left: Bethel Hospital logo and application title.
  - Center/Left: Large labeled buttons with icons: Dashboard, Assets, Find Asset, Reports, Settings (role-gated).
  - Right: Prominent primary Add Asset button, user profile avatar, and Sign Out dialog trigger.
  - Guaranteed fit at 1093x614 without bar horizontal scrolling.

- [ ] **Step 1: Write failing layout test verifying top nav elements and role visibility**
- [ ] **Step 2: Build top navigation bar component using locked design tokens**
- [ ] **Step 3: Remove sidebar markup, collapse JavaScript, and related styling**
- [ ] **Step 4: Run layout test to verify passes**
  Run: `php artisan test --filter=TopNavigationResponsiveTest`

---

### Task C2: Page Streamlining & Visual Polish

**Files:**
- Modify: `resources/views/dashboard.blade.php`
- Modify: `resources/views/assets/index.blade.php`
- Modify: `resources/views/assets/create.blade.php`
- Modify: `resources/views/assets/edit.blade.php`
- Modify: `resources/views/reports/index.blade.php`
- Test: `tests/Feature/PageActionHierarchyTest.php`

**Interfaces:**
- Consumes: Existing blade views.
- Produces: Streamlined pages with one unmistakable primary action, plain clinical terminology, large click targets (minimum 38-44px), zero em dashes.

- [ ] **Step 1: Audit each page for focal point and click target sizing**
- [ ] **Step 2: Adjust buttons, headers, and spacing according to interface-design standards**
- [ ] **Step 3: Wait for user screenshot of the old system and apply visual adjustments**

---

### Task C3: Phase C Quality Gate & Verification

- [ ] **Step 1: Run complete test suite**
  Run: `php artisan test`
- [ ] **Step 2: Compile frontend assets**
  Run: `npm run build`
- [ ] **Step 3: Capture verification screenshots at 1366x768 and 1093x614**
- [ ] **Step 4: Report Phase C completion and wait for "next"**

---

## Finish Phase: Version 1.3.0 Desktop Packaging & Verification

### Task F1: Version Bump & Installer Build

**Files:**
- Modify: `package.json` (bump to 1.3.0)
- Modify: `config/nativephp.php` (bump to 1.3.0)
- Modify: `resources/views/settings/index.blade.php` (display 1.3.0)
- Test: `tests/Feature/VersionDisplayTest.php`

- [ ] **Step 1: Bump version numbers across configuration files**
- [ ] **Step 2: Run full automated test suite (must be 100% green)**
  Run: `php artisan test`
- [ ] **Step 3: Build frontend assets**
  Run: `npm run build`
- [ ] **Step 4: Package Windows Native Desktop installer**
  Run: `php artisan native:build win x64 --no-interaction`
- [ ] **Step 5: Run update simulation test on cloned database copy**
  Verify pre-migration backup creation, department backfill integrity, and asset count preservation.
- [ ] **Step 6: Report final artifacts, build duration, and verification metrics**
