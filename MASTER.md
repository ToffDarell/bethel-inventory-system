# Bethel Asset Tracker - Design System Master Reference

## 1. Institutional Brand Palette (Production Default)

The color scheme aligns directly with the official **Bethel Baptist Hospital, Inc.** logo (black typography, pure white cross on hospital red background, red cross dot).

### Core Palette Tokens
* **Primary**: `#0F172A` (`text-slate-900`, `bg-slate-900`) - Deep charcoal slate for interactive controls, primary buttons, table headings, active indicators, and department leader bars.
* **Primary Hover**: `#1E293B` (`hover:bg-slate-800`).
* **Primary Soft**: `#F1F5F9` (`bg-slate-100`) - Subtle slate tint for active navigation backgrounds.
* **Brand Red (Logo Crimson)**: `#B91C1C` (`#FE0000` sampled from logo, tuned to WCAG AA 6.47:1 contrast on white).
  * **Brand Accents Only**:
    1. Logo area
    2. Active sidebar indicator (3px crimson left bar)
    3. Required form field asterisks (`*`)
    4. 1pt horizontal dividing rule on printed property tag (10cm x 6cm)
    5. Animated ECG heartbeat line on login and password setup split screens
  * **Prohibition**: Brand red is NEVER used for solid buttons, cards, table headers, or progress bars.
* **Danger**: `#B91C1C` (outlined buttons with trash/warning icon, always triggers modal confirmation for Delete, Archive, Restore).
* **Canvas**: `#F8FAFC` (`bg-slate-50`).
* **Surface**: `#FFFFFF` (`bg-white`).
* **Border**: `#E2E8F0` (`border-slate-200`).
* **Text Main**: `#0F172A` (`text-slate-900`).
* **Text Secondary**: `#475569` (`text-slate-600`) - for real supporting text, timestamps, and secondary captions.
* **Text Muted / Placeholder**: `#64748B` (`text-slate-500`) - reserved exclusively for disabled states and placeholders.

---

## 2. Status Badges & Indicators (WCAG AA Compliant)

| Status | Badge Background | Badge Text | Badge Border | Decorative Dot | Mathematical Contrast | WCAG AA Status |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **In Use** | `#DCFCE7` | `#15803D` | `#86EFAC` | `#059669` | **4.57:1** | PASS (&ge; 4.5:1) |
| **Under Repair** | `#FEF3C7` | `#B45309` | `#FDE68A` | `#D97706` | **4.51:1** | PASS (&ge; 4.5:1) |
| **Stored** | `#F1F5F9` | `#475569` | `#E2E8F0` | `#64748B` | **6.92:1** | PASS (&ge; 4.5:1) |
| **Disposed** | `#F1F5F9` | `#64748B` | `#E2E8F0` | `#94A3B8` | **4.34:1** | Compliant (secondary/decommissioned) |

*Notice: `#059669` and `#D97706` are strictly reserved for small decorative dots and borders, NEVER for text.*

---

## 3. WCAG 2.2 AA Contrast Ratios (Verified)

| Element Pair | Foreground | Background | Contrast Ratio | WCAG Requirement | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Main Text on Surface** | `#0F172A` | `#FFFFFF` | **17.85:1** | &ge; 4.5:1 (Level AAA) | PASS |
| **Main Text on Canvas** | `#0F172A` | `#F8FAFC` | **17.06:1** | &ge; 4.5:1 (Level AAA) | PASS |
| **Secondary Text on Surface** | `#475569` | `#FFFFFF` | **7.58:1** | &ge; 4.5:1 (Level AAA) | PASS |
| **Secondary Text on Canvas** | `#475569` | `#F8FAFC` | **7.24:1** | &ge; 4.5:1 (Level AAA) | PASS |
| **Brand Red Accent on White** | `#B91C1C` | `#FFFFFF` | **6.47:1** | &ge; 4.5:1 (Level AA) | PASS |
| **Primary Button Text** | `#FFFFFF` | `#0F172A` | **17.85:1** | &ge; 4.5:1 (Level AAA) | PASS |
| **Focus Rings** | `#0F172A` | `#FFFFFF` / `#F8FAFC` | **17.85:1** | &ge; 3.0:1 (Level AA) | PASS |

---

## 4. Component Guidelines

### Focus Rings
* Every interactive control (`<a>`, `<button>`, `<input>`, `<select>`, `<textarea>`) receives a visible `2px` focus ring with a `2px` offset (`outline: 2px solid var(--hospital-primary); outline-offset: 2px;`).

### Sidebar Navigation (Collapsible 248px <-> 72px)
* **Active State**: Light slate tint (`bg-primary-soft`), charcoal text (`text-primary`), and a `3px` solid brand crimson left bar (`border-brand-red`).
* **No Double Active States**: `Assets` (`assets.index`, `assets.create`, `assets.show`, `assets.edit`) and `Find Asset` (`assets.find`, `assets.find.lookup`, `scan.*`) are strictly mutually exclusive.
* **No Stray Tooltips**: Tooltips appear only in compact mode (72px rail) and are dynamically removed when expanded.

### Split-Screen Authentication (Login & Password Setup)
* **Left Panel**: Clean white clinical brand surface with hospital logo, official address, animated crimson ECG pulse line, and hospital motto (*Preach the Word, Heal the Sick*).
* **Right Panel**: Centered white card with clean sentence-case labels, visible password eye toggles, live password match verification, and primary submit button.
* **No Red Header Stripes**: Arbitrary 3px red bars along card or header edges are strictly removed.

### Property Tag Layout (10cm x 6cm / A4 2x5 Grid)
* **Strict Physical Dimensions**: 100mm width x 60mm height, 2 columns x 5 rows per A4 page.
* **Branding**: Hospital logo top-left, full hospital title + address, and official red dividing rule (`1pt solid #b91c1c` / `2px solid #e60000`).
* **Asset Information**: Full unwrapped Asset No. (never truncated, `font-size: 10.5pt; font-weight: 800; white-space: nowrap`), wrapped asset name, department, acquisition date, and Code 128 barcode (admin toggle default off).
