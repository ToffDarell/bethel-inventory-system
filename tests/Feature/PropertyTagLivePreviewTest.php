<?php

use App\Models\Setting;
use App\Models\User;

test('admin can access settings property tag tab with live 1:1 preview and long sample asset', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Setting::set('tag_width_cm', '10');
    Setting::set('tag_height_cm', '6');
    Setting::set('asset_no_prefix', 'BBH');
    Setting::set('show_barcode', '0');

    $response = $this->actingAs($admin)->get('/settings?tab=tag');

    $response->assertOk();
    $response->assertSee('Property Tag Dimensions & Code Prefix');
    $response->assertSee('Live 1:1 Physical Tag Preview');
    $response->assertSee('tag-preview-wrapper');
    $response->assertSee('Multi-Parameter Vital Signs Monitor with Integrated Capnography & Defibrillator Module');
    $response->assertSee('BBH-SURG-OR2-2026-00049281');
    $response->assertSee('10 cm');
    $response->assertSee('6 cm');
});

test('staff viewing settings never sees money values in the property tag preview or tag tab', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    $response = $this->actingAs($staff)->get('/settings?tab=tag');

    $response->assertOk();
    $response->assertSee('Live 1:1 Physical Tag Preview');
    // Staff must never see money values anywhere in this view
    $response->assertDontSee('₱');
    $response->assertDontSee('Total Asset Value');
    $response->assertDontSee('Accumulated Depreciation');
});

test('admin can update property tag dimensions and prefix', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->post('/settings', [
        'hospital_name' => 'Bethel Baptist Hospital, Inc.',
        'hospital_address' => 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700',
        'asset_no_prefix' => 'BBH-CLINIC',
        'tag_width_cm' => '12.5',
        'tag_height_cm' => '7.5',
        'show_barcode' => '1',
        'backup_folder' => 'C:\\xampp8.2\\htdocs\\BettleHospital\\storage\\app\\backups',
    ]);

    $response->assertRedirect('/settings');
    expect(Setting::get('asset_no_prefix'))->toBe('BBH-CLINIC');
    expect(Setting::get('tag_width_cm'))->toBe('12.5');
    expect(Setting::get('tag_height_cm'))->toBe('7.5');
    expect(Setting::get('show_barcode'))->toBe('1');
});

test('property tag blade views contain no em dashes in text', function () {
    $tagCardContent = file_get_contents(resource_path('views/tags/partials/tag-card.blade.php'));
    $settingsContent = file_get_contents(resource_path('views/settings/index.blade.php'));

    expect(str_contains($tagCardContent, '—'))->toBeFalse();
    expect(str_contains($settingsContent, '—'))->toBeFalse();
});

test('property tag partial renders full long asset number and 93-character asset name without ellipsis or truncation', function () {
    $longName = 'Multi-Parameter Vital Signs Monitor with Integrated Capnography and Defibrillator System 93ch';
    expect(mb_strlen($longName))->toBe(93);

    $longAssetNo = 'BBH-SURG-OR2-2026-00049281';

    $asset = new \App\Models\Asset([
        'asset_no' => $longAssetNo,
        'asset_name' => $longName,
        'status' => 'In Use',
        'model' => 'Mindray BeneVision N17',
        'serial_no' => 'SN-98427189',
        'location' => 'Operating Room Suite 3',
        'department' => 'Surgery',
        'date_acquired' => now(),
    ]);

    $renderedHtml = view('tags.partials.tag-card', [
        'asset' => $asset,
        'showBarcode' => false,
    ])->render();

    // Full strings must appear in the rendered HTML
    expect($renderedHtml)->toContain($longAssetNo);
    expect($renderedHtml)->toContain($longName);

    // Asset No cell must not contain ellipsis or truncate
    preg_match('/<td[^>]*class="[^"]*preview-asset-no-cell[^"]*"[^>]*>(.*?)<\/td>/s', $renderedHtml, $noMatches);
    expect($noMatches)->not->toBeEmpty();
    $noCellHtml = $noMatches[0];
    expect($noCellHtml)->not->toContain('text-overflow: ellipsis');
    expect($noCellHtml)->not->toContain('text-overflow:ellipsis');
    expect($noCellHtml)->not->toContain('truncate');

    // Asset Name cell must not contain ellipsis or truncate
    preg_match('/<td[^>]*class="[^"]*preview-asset-name-cell[^"]*"[^>]*>(.*?)<\/td>/s', $renderedHtml, $nameMatches);
    expect($nameMatches)->not->toBeEmpty();
    $nameCellHtml = $nameMatches[0];
    expect($nameCellHtml)->not->toContain('text-overflow: ellipsis');
    expect($nameCellHtml)->not->toContain('text-overflow:ellipsis');
    expect($nameCellHtml)->not->toContain('truncate');
});

test('visiting settings with tab=backup opens the backup tab', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/settings?tab=backup');

    $response->assertOk();
    $response->assertSee('Backup & Restore');
    $response->assertSee('id="tab-btn-backup"', false);
    $response->assertSee('id="panel-backup"', false);

    // Verify backup tab button is marked aria-selected="true"
    $content = $response->getContent();
    preg_match('/<button[^>]*id="tab-btn-backup"[^>]*>/', $content, $btnMatches);
    expect($btnMatches)->not->toBeEmpty();
    expect($btnMatches[0])->toContain('aria-selected="true"');

    // Verify panel-backup is visible (not hidden) in server-rendered HTML
    preg_match('/<div[^>]*id="panel-backup"[^>]*>/', $content, $panelMatches);
    expect($panelMatches)->not->toBeEmpty();
    expect($panelMatches[0])->not->toContain('hidden');

    // Verify hospital panel is hidden
    preg_match('/<div[^>]*id="panel-hospital"[^>]*>/', $content, $hospMatches);
    expect($hospMatches)->not->toBeEmpty();
    expect($hospMatches[0])->toContain('hidden');
});
