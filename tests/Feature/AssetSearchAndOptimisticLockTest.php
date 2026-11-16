<?php

use App\Models\Asset;
use App\Models\AssetNumberHistory;
use App\Models\Department;
use App\Models\User;
use App\Services\AssetImportService;
use Carbon\Carbon;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'must_change_password' => false,
    ]);
    $this->staff = User::factory()->create([
        'role' => 'staff',
        'must_change_password' => false,
    ]);

    $this->dept = Department::create([
        'name' => 'Surgery',
        'code' => 'SURG',
        'next_number' => 1,
        'active' => true,
        'code_confirmed' => true,
    ]);
});

test('optimistic locking rejects edit save if asset was modified concurrently', function () {
    $asset = Asset::create([
        'asset_no' => 'BBHSURG-0001',
        'asset_name' => 'Surgical Light',
        'department_id' => $this->dept->id,
        'department' => $this->dept->name,
        'status' => 'In Use',
    ]);

    $originalTimestamp = $asset->updated_at->timestamp;

    // Simulate another user updating the asset 5 seconds later
    Carbon::setTestNow(now()->addSeconds(10));
    $asset->update(['notes' => 'Updated by User B']);
    Carbon::setTestNow();

    // User A submits form which was rendered with originalTimestamp
    $response = $this->actingAs($this->admin)->put("/assets/{$asset->id}", [
        'lock_version' => $originalTimestamp,
        'asset_no' => 'BBHSURG-0001',
        'asset_name' => 'Surgical Light Modified',
        'department_id' => $this->dept->id,
        'status' => 'In Use',
    ]);

    $response->assertSessionHasErrors('asset_no');
    expect($asset->fresh()->asset_name)->toBe('Surgical Light'); // Unchanged
});

test('find asset and quick search locate assets by former numbers with old number note', function () {
    $asset = Asset::create([
        'asset_no' => 'BBHSURG-0010',
        'asset_name' => 'Electrosurgical Generator',
        'department_id' => $this->dept->id,
        'department' => $this->dept->name,
        'status' => 'In Use',
    ]);

    AssetNumberHistory::create([
        'asset_id' => $asset->id,
        'old_no' => 'BBHER-0004',
        'new_no' => 'BBHSURG-0010',
        'reason' => 'Transferred from ER',
    ]);

    // 1. Find Asset lookup (/find/lookup)
    $response = $this->actingAs($this->staff)->get('/find/lookup?q=BBHER-0004');
    $response->assertRedirect("/assets/{$asset->id}");
    $response->assertSessionHas('info');

    // 2. API Quick Search (/api/assets/quick-search)
    $apiResponse = $this->actingAs($this->staff)->getJson('/api/assets/quick-search?q=BBHER-0004');
    $apiResponse->assertOk();
    $data = $apiResponse->json();
    expect(count($data))->toBeGreaterThanOrEqual(1);
    expect($data[0]['id'])->toBe($asset->id);
    expect($data[0]['old_number_matched'])->toBe('BBHER-0004');
});

test('import matching recognizes and rejects duplicate against old numbers in history', function () {
    $asset = Asset::create([
        'asset_no' => 'BBHSURG-0020',
        'asset_name' => 'Anesthesia Machine',
        'status' => 'In Use',
    ]);

    AssetNumberHistory::create([
        'asset_id' => $asset->id,
        'old_no' => 'BBH-HIST-999',
        'new_no' => 'BBHSURG-0020',
    ]);

    $service = app(AssetImportService::class);
    // Test that the history number is detected as existing
    $exists = Asset::withTrashed()->whereRaw('LOWER(asset_no) = LOWER(?)', ['BBH-HIST-999'])->exists()
        || AssetNumberHistory::whereRaw('LOWER(old_no) = LOWER(?)', ['BBH-HIST-999'])->exists();

    expect($exists)->toBeTrue();

    // Test preview of CSV with old number marks it as duplicate
    $csvContent = "asset_no,asset_name\nBBH-HIST-999,Duplicate Machine";
    $uploaded = \Illuminate\Http\UploadedFile::fake()->createWithContent('import.csv', $csvContent);
    $preview = $service->preview($uploaded);

    expect($preview['duplicate_count'])->toBe(1);
    expect($preview['rows'][0]['is_duplicate'])->toBeTrue();
    expect($preview['rows'][0]['status_label'])->toBe('Duplicate (Skipped)');
});

test('dashboard shows count of tags needing reprinting and staff shield is preserved', function () {
    Asset::create([
        'asset_no' => 'BBHSURG-0030',
        'asset_name' => 'Suction Unit',
        'status' => 'In Use',
        'cost' => 85000.00,
        'needs_tag_reprint' => true,
    ]);

    // Admin dashboard (singular: 1 tag needs reprinting)
    $adminResp = $this->actingAs($this->admin)->get('/dashboard');
    $adminResp->assertOk();
    $adminResp->assertSee('1 tag needs reprinting');
    $adminResp->assertDontSee('1 tag need reprinting');
    $adminResp->assertDontSee('₱');

    // Staff dashboard (singular: 1 tag needs reprinting)
    $staffResp = $this->actingAs($this->staff)->get('/dashboard');
    $staffResp->assertOk();
    $staffResp->assertSee('1 tag needs reprinting');
    // Staff financial shield check: no 85,000 or peso value
    $staffResp->assertDontSee('85,000');
    $staffResp->assertDontSee('₱');

    // Add second asset to verify plural: 2 tags need reprinting
    Asset::create([
        'asset_no' => 'BBHSURG-0031',
        'asset_name' => 'Infusion Pump',
        'status' => 'In Use',
        'cost' => 45000.00,
        'needs_tag_reprint' => true,
    ]);

    $pluralResp = $this->actingAs($this->admin)->get('/dashboard');
    $pluralResp->assertOk();
    $pluralResp->assertSee('2 tags need reprinting');
});
