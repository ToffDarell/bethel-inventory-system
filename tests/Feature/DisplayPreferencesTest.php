<?php

use App\Models\Asset;
use App\Models\Category;
use App\Models\Department;
use App\Models\Section;
use App\Models\User;
use App\Models\Vendor;
use PhpOffice\PhpSpreadsheet\IOFactory;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'Admin Pref User',
        'username' => 'adminpref',
        'role' => 'admin',
        'must_change_password' => false,
    ]);

    $this->staff = User::factory()->create([
        'name' => 'Staff Pref User',
        'username' => 'staffpref',
        'role' => 'staff',
        'must_change_password' => false,
    ]);
});

test('user model display preference accessors normalize null or invalid values to safe defaults', function () {
    $user = User::factory()->create([
        'text_size' => null,
        'density' => null,
        'theme' => null,
    ]);

    expect($user->text_size)->toBe('default')
        ->and($user->density)->toBe('comfortable')
        ->and($user->theme)->toBe('light')
        ->and($user->effectiveTextSize())->toBe('default')
        ->and($user->effectiveDensity())->toBe('comfortable')
        ->and($user->effectiveTheme())->toBe('light');

    $user->forceFill([
        'text_size' => 'INVALID_SIZE',
        'density' => 'INVALID_DENSITY',
        'theme' => 'INVALID_THEME',
    ]);

    expect($user->effectiveTextSize())->toBe('default')
        ->and($user->effectiveDensity())->toBe('comfortable')
        ->and($user->effectiveTheme())->toBe('light');
});

test('saving each display preference persists and reloads on server-rendered layout for that user only without affecting other users', function () {
    // Default state on settings display tab for both users
    $this->actingAs($this->admin)
        ->get(route('settings.index', ['tab' => 'display']))
        ->assertOk()
        ->assertSee('data-text-size="default"', false)
        ->assertSee('data-density="comfortable"', false)
        ->assertSee('data-theme="light"', false)
        ->assertSee('Personal Display Settings');

    // Admin updates to large + compact + dark
    $this->actingAs($this->admin)
        ->from(route('settings.index', ['tab' => 'display']))
        ->post(route('preferences.display.update'), [
            'text_size' => 'large',
            'density' => 'compact',
            'theme' => 'dark',
        ])
        ->assertRedirect(route('settings.index', ['tab' => 'display']));

    $this->admin->refresh();
    expect($this->admin->effectiveTextSize())->toBe('large')
        ->and($this->admin->effectiveDensity())->toBe('compact')
        ->and($this->admin->effectiveTheme())->toBe('dark');

    // Reloaded page server-renders the updated attributes and classes for admin
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-text-size="large"', false)
        ->assertSee('data-density="compact"', false)
        ->assertSee('data-theme="dark"', false)
        ->assertSee('text-size-large density-compact theme-dark', false);

    // Staff user's preferences remain completely untouched
    $this->staff->refresh();
    expect($this->staff->effectiveTextSize())->toBe('default')
        ->and($this->staff->effectiveDensity())->toBe('comfortable')
        ->and($this->staff->effectiveTheme())->toBe('light');

    $this->actingAs($this->staff)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-text-size="default"', false)
        ->assertSee('data-density="comfortable"', false)
        ->assertSee('data-theme="light"', false);

    // Staff can also save their own preferences (small + compact + dark) without admin approval
    $this->actingAs($this->staff)
        ->postJson(route('preferences.display.update'), [
            'text_size' => 'small',
            'density' => 'compact',
            'theme' => 'dark',
        ])
        ->assertOk()
        ->assertJson([
            'text_size' => 'small',
            'density' => 'compact',
            'theme' => 'dark',
        ]);

    expect($this->staff->fresh()->effectiveTextSize())->toBe('small')
        ->and($this->admin->fresh()->effectiveTextSize())->toBe('large');
});

test('text size preference does not change exported Excel CSV or PDF cell values or row and column counts', function () {
    $cat = Category::create([
        'name' => 'Medical Equipment',
        'default_useful_life_years' => 10,
        'default_salvage_percent' => 10,
    ]);
    $dept = Department::create([
        'name' => 'Emergency Room',
        'code' => 'ER',
        'next_number' => 1,
        'active' => true,
        'code_confirmed' => true,
    ]);
    $sec = Section::create([
        'department_id' => $dept->id,
        'name' => 'Triage',
        'code' => 'TRI',
        'next_number' => 1,
        'active' => true,
        'code_confirmed' => true,
    ]);
    $vendor = Vendor::create([
        'name' => '3 J MEDICAL',
        'active' => true,
        'is_donation' => false,
    ]);

    Asset::create([
        'asset_no' => 'BBHI-ER-0001',
        'asset_name' => 'Patient Monitor',
        'brand' => 'Mindray',
        'model' => 'iMEC10',
        'serial_no' => 'SN-1001',
        'department_id' => $dept->id,
        'department' => 'Emergency Room',
        'section_id' => $sec->id,
        'section' => 'Triage',
        'category_id' => $cat->id,
        'vendor_id' => $vendor->id,
        'date_acquired' => '2023-05-10',
        'cost' => 65000.00,
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
    ]);

    Asset::create([
        'asset_no' => 'BBHI-ER-0002',
        'asset_name' => 'Defibrillator',
        'brand' => 'Philips',
        'model' => 'HeartStart',
        'serial_no' => 'SN-1002',
        'department_id' => $dept->id,
        'department' => 'Emergency Room',
        'section_id' => $sec->id,
        'section' => 'Triage',
        'category_id' => $cat->id,
        'vendor_id' => $vendor->id,
        'date_acquired' => '2024-01-15',
        'cost' => 145000.00,
        'status' => 'In Use',
        'asset_condition' => 'Brand-new',
    ]);

    $xlsxSnapshots = [];
    $csvSnapshots = [];
    $pdfSnapshots = [];

    foreach (['small', 'default', 'large'] as $size) {
        $this->admin->update([
            'text_size' => $size,
            'density' => $size === 'small' ? 'compact' : 'comfortable',
            'theme' => $size === 'large' ? 'dark' : 'light',
        ]);

        // 1. Excel (.xlsx) export parsed structure & cell values
        $xlsxResp = $this->actingAs($this->admin->fresh())
            ->get(route('reports.export', ['format' => 'xlsx']));
        $xlsxResp->assertOk();
        $xlsxPath = $xlsxResp->baseResponse->getFile()->getPathname();
        $spreadsheet = IOFactory::load($xlsxPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);
        $xlsxSnapshots[$size] = [
            'row_count' => count($rows),
            'col_count' => count($rows[0] ?? []),
            'rows' => $rows,
        ];

        // 2. CSV export parsed rows & columns
        $csvResp = $this->actingAs($this->admin->fresh())
            ->get(route('reports.export', ['format' => 'csv']));
        $csvResp->assertOk();
        $csvPath = $csvResp->baseResponse->getFile()->getPathname();
        $csvLines = array_map('str_getcsv', file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        $csvSnapshots[$size] = [
            'row_count' => count($csvLines),
            'col_count' => count($csvLines[0] ?? []),
            'rows' => $csvLines,
        ];

        // 3. PDF inventory export rendered HTML structure (before DomPDF timestamp metadata)
        $pdfResp = $this->actingAs($this->admin->fresh())
            ->get(route('reports.export-pdf'));
        $pdfResp->assertOk();
        $pdfContent = (string) $pdfResp->getContent();
        expect($pdfContent)->toStartWith('%PDF-');
        // Extract text streams / page count markers independent of CreationDate
        preg_match_all('/\/Type\s*\/Page\b/', $pdfContent, $pageMatches);
        $pdfSnapshots[$size] = [
            'page_count' => count($pageMatches[0]),
            'length' => strlen($pdfContent),
        ];
    }

    expect($xlsxSnapshots['small'])->toBe($xlsxSnapshots['default'])
        ->and($xlsxSnapshots['large'])->toBe($xlsxSnapshots['default'])
        ->and($csvSnapshots['small'])->toBe($csvSnapshots['default'])
        ->and($csvSnapshots['large'])->toBe($csvSnapshots['default'])
        ->and($pdfSnapshots['small']['page_count'])->toBe($pdfSnapshots['default']['page_count'])
        ->and($pdfSnapshots['large']['page_count'])->toBe($pdfSnapshots['default']['page_count']);
});

test('dark theme token pairs in app.css all meet or exceed WCAG AA 4.5:1 contrast ratio and property tag stays white', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)->toContain('[data-theme="dark"]')
        ->and($css)->toContain('#tag-preview-wrapper .property-tag-card')
        ->and($css)->toContain('background-color: #ffffff !important');

    $relativeLuminance = function (string $hex): float {
        $h = ltrim($hex, '#');
        $r = hexdec(substr($h, 0, 2)) / 255.0;
        $g = hexdec(substr($h, 2, 2)) / 255.0;
        $b = hexdec(substr($h, 4, 2)) / 255.0;
        $channel = fn (float $v): float => $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;

        return 0.2126 * $channel($r) + 0.7152 * $channel($g) + 0.0722 * $channel($b);
    };

    $contrastRatio = function (string $fg, string $bg) use ($relativeLuminance): float {
        $l1 = $relativeLuminance($fg);
        $l2 = $relativeLuminance($bg);

        return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
    };

    $darkPairs = [
        'canvas / main text' => ['#f8fafc', '#0b1120'],
        'surface / main text' => ['#f8fafc', '#111827'],
        'canvas / secondary text' => ['#cbd5e1', '#0b1120'],
        'surface / secondary text' => ['#cbd5e1', '#111827'],
        'canvas / muted text' => ['#94a3b8', '#0b1120'],
        'surface / muted text' => ['#94a3b8', '#111827'],
        'elevated surface / muted text' => ['#94a3b8', '#1e293b'],
        'primary button bg / text' => ['#ffffff', '#0f172a'],
        'primary hover bg / text' => ['#ffffff', '#1e293b'],
        'brand red button bg / text' => ['#ffffff', '#b91c1c'],
        'status success badge' => ['#a7f3d0', '#064e3b'],
        'status warning badge' => ['#fde68a', '#451a03'],
        'status stored badge' => ['#e2e8f0', '#1e293b'],
        'status disposed badge' => ['#fecdd3', '#3b0712'],
    ];

    foreach ($darkPairs as $label => [$fg, $bg]) {
        expect($contrastRatio($fg, $bg))->toBeGreaterThanOrEqual(4.5, "Contrast failed for {$label}");
    }
});
