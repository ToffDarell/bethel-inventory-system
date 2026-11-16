# Professional Exports (Styled Excel & PDF) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

## Overview

Upgrade Bethel Hospital's asset and depreciation exports to publication-grade, professional documents:
1. **Phase A: Styled Excel (.xlsx)**:
   - Header block: Local Bethel logo drawing, hospital name, address, report title, metadata ("As of", "Prepared by @username", "Generated <timestamp>"), and thin crimson brand accent line (#991B1B).
   - Depreciation policy row and tracking disclaimer in muted italic text.
   - Charcoal header table (#0F172A), zebra row striping (#F8FAFC), subtle grid borders (#E2E8F0), row heights (24 header, 20 rows).
   - Real Excel `SUM()` formulas for totals row (e.g., `=SUM(I7:I{n})`).
   - Currency format `"₱"#,##0.00`, text alignment (left text, right numbers, center dates/months).
   - Freeze panes below headers, AutoFilter, sheet named "Depreciation".
   - Second sheet "By Category" with asset counts, total cost, accumulated depreciation, current value, and SUM totals.
   - Print setup: landscape, fit 1 page wide, repeat header row on pages, footer with `Page &P of &N` and disclaimer.
   - Asset Inventory export styled identically (with financial columns for admin; strictly without financial columns for staff).
   - Import Template with "Read me" guidance sheet and sample data sheet (no logo on data sheet).
   - Rename CSV buttons to "Raw data (CSV)" and keep them plain.
2. **Phase B: PDF Exports** (DomPDF, DejaVu Sans for ₱ symbol, base64 logo, landscape, repeating headers, pagination, paper size setting, row cap guard, activity logging).
3. **Phase C: Tests and Verification** (Pest test suite, visual inspection, npm build, packaged test).

---

## Task Breakdown for Phase A

### Task A1: Styled Depreciation Report Excel Export
- Files: `app/Exports/DepreciationExport.php`, `app/Exports/Sheets/DepreciationMainSheet.php`, `app/Exports/Sheets/DepreciationCategorySheet.php`
- [ ] Create `DepreciationMainSheet` implementing `FromCollection`, `WithTitle`, `WithDrawings`, `WithCustomStartCell`, `WithStyles`, `WithColumnWidths`, `WithEvents`.
- [ ] Implement header block (rows 1-4) with Bethel logo drawing (A1:B4), hospital name (Row 1, bold, 16pt), address (Row 2, 9pt, #64748B), title + metadata (Row 3, bold 13pt), and crimson border line under row 4.
- [ ] Add policy summary and disclaimer (Row 5).
- [ ] Table headers at Row 6: charcoal background (`#0F172A`), white bold text, row height 24.
- [ ] Data rows starting at Row 7: zebra striping (`#F8FAFC` on even rows), thin `#E2E8F0` borders, row height 20.
- [ ] Format currency columns (`"₱"#,##0.00`), center Year and Months, right-align financials.
- [ ] Totals row: bold, light gray fill (`#F1F5F9`), top thin border, double bottom border, real `=SUM()` formulas for Cost, Accumulated, and Current Value.
- [ ] Enable AutoFilter, freeze panes below table header (`A7`), set sheet name to `Depreciation`.
- [ ] Print setup: Landscape, fit to 1 page wide, repeat row 6 on every page, footer with `Page &P of &N`.
- [ ] Create `DepreciationCategorySheet` with summary table and category totals formulas.
- [ ] Ensure `DepreciationExport` returns sheets for XLSX and handles plain CSV for raw export.

### Task A2: Styled Asset Inventory Excel Export
- Files: `app/Exports/AssetsExport.php`, `app/Exports/Sheets/AssetsInventorySheet.php`
- [ ] Implement `AssetsInventorySheet` with the same hospital header block, logo, and metadata.
- [ ] Support Admin view (including cost, useful life, salvage, accumulated, current value with `=SUM()` totals).
- [ ] Support Staff view (strictly omitting all financial columns, formulas, and totals).
- [ ] Apply matching charcoal header styling, zebra striping, currency formatting, freeze panes, AutoFilter, and landscape print setup.

### Task A3: Import Template with "Read Me" Instructions Sheet
- Files: `app/Exports/AssetsTemplateExport.php`, `app/Exports/Sheets/TemplateReadMeSheet.php`, `app/Exports/Sheets/TemplateDataSheet.php`
- [ ] Create `TemplateReadMeSheet` explaining each column's required format, allowed condition values (`Brand-new`, `Refurbish`, `Damage`, `In Good Condition`), status values, and example rules.
- [ ] Retain clean `TemplateDataSheet` with standard headers and sample rows without logo or drawing clutter so user can fill and upload directly.

### Task A4: Update UI Buttons & Action Labels
- Files: `resources/views/reports/index.blade.php`, `resources/views/reports/depreciation.blade.php`
- [ ] Rename CSV buttons to "Raw data (CSV)".
- [ ] Retain "Export Excel (.xlsx)" button clearly distinguished.

### Task A5: Visual Verification & Automated Tests
- Files: `tests/Feature/AssetClientFieldsImportExportTest.php`, `tests/Feature/AssetDepreciationTest.php`, `tests/Feature/ExportTempFilesCleanupTest.php`
- [ ] Verify test suite passes with 100% assertions.
- [ ] Verify generated .xlsx files have valid OpenXML zip structures and can be loaded by PhpSpreadsheet reader.
- [ ] Run `npm run build` and Pest test suite.
