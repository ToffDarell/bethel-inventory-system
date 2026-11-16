<?php

use App\Models\Asset;
use App\Models\AssetTemplate;
use App\Models\Category;
use App\Models\Department;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\AssetTemplateSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
    $this->staff = User::factory()->create(['role' => 'staff', 'must_change_password' => false]);
});

test('asset template seeder seeds 8 clean templates once and never overwrites existing rows', function () {
    $this->seed(CategorySeeder::class);
    $this->seed(AssetTemplateSeeder::class);

    expect(AssetTemplate::count())->toBe(8);
    expect(AssetTemplate::where('name', 'Patient Monitor')->where('brand', 'Mindray')->where('model', 'uMEC10')->exists())->toBeTrue()
        ->and(AssetTemplate::where('name', 'Counter Table')->first()->category?->name)->toBe('Furniture')
        ->and(AssetTemplate::where('name', 'Desktop Computer Set')->first()->category?->name)->toBe('IT Equipment');

    AssetTemplate::query()->first()->update(['name' => 'Custom Keep Row']);
    $this->seed(AssetTemplateSeeder::class);

    expect(AssetTemplate::count())->toBe(8)
        ->and(AssetTemplate::where('name', 'Custom Keep Row')->exists())->toBeTrue();
});

test('template uniqueness is case-insensitive and trimmed on name brand model', function () {
    $cat = Category::create([
        'name' => 'Medical Equipment',
        'default_useful_life_years' => 10,
        'default_salvage_percent' => null,
    ]);

    $this->actingAs($this->admin)->post('/settings/asset-templates', [
        'name' => 'Patient Monitor',
        'brand' => 'Mindray',
        'model' => 'uMEC10',
        'category_id' => $cat->id,
        'default_remarks' => 'Standard bedside unit',
    ])->assertRedirect('/settings?tab=asset-templates');

    expect(AssetTemplate::count())->toBe(1);

    $this->actingAs($this->admin)->post('/settings/asset-templates', [
        'name' => '  patient   monitor ',
        'brand' => ' MINDRAY ',
        'model' => 'umec10 ',
        'category_id' => $cat->id,
    ])->assertSessionHasErrors('template_name');

    expect(AssetTemplate::count())->toBe(1);
});

test('editing or deactivating a template never alters existing assets and preserves asset_template_id', function () {
    $dept = Department::create(['name' => 'Nursing', 'code' => 'ND', 'next_number' => 1, 'active' => true, 'code_confirmed' => true]);
    $sec = Section::create(['department_id' => $dept->id, 'name' => 'Station 1', 'code' => 'S1', 'next_number' => 1, 'active' => true, 'code_confirmed' => true]);
    $cat = Category::create(['name' => 'Medical Equipment', 'default_useful_life_years' => 10, 'default_salvage_percent' => 10]);

    $template = AssetTemplate::create([
        'name' => 'Infusion Pump',
        'brand' => 'B. Braun',
        'model' => 'Infusomat',
        'category_id' => $cat->id,
        'default_remarks' => 'Original note',
        'active' => true,
    ]);

    $this->actingAs($this->admin)->post('/assets', [
        'asset_template_id' => $template->id,
        'asset_name' => 'Infusion Pump',
        'brand' => 'B. Braun',
        'model' => 'Infusomat',
        'category_id' => $cat->id,
        'department_id' => $dept->id,
        'section_id' => $sec->id,
        'status' => 'In Use',
        'asset_condition' => 'Brand-new',
        'notes' => 'Original note',
    ])->assertRedirect();

    $asset = Asset::first();
    expect($asset->asset_template_id)->toBe($template->id);

    // Editing template does not change existing asset
    $this->actingAs($this->admin)->put("/settings/asset-templates/{$template->id}", [
        'name' => 'Updated Infusion Pump',
        'brand' => 'NewBrand',
        'model' => 'NewModel',
        'category_id' => null,
        'default_remarks' => 'Changed note',
    ])->assertRedirect('/settings?tab=asset-templates');

    $asset->refresh();
    expect($asset->asset_name)->toBe('Infusion Pump')
        ->and($asset->brand)->toBe('B. Braun')
        ->and($asset->model)->toBe('Infusomat')
        ->and($asset->category_id)->toBe($cat->id)
        ->and($asset->notes)->toBe('Original note');

    // Cannot hard delete while asset uses it
    $this->actingAs($this->admin)->delete("/settings/asset-templates/{$template->id}")
        ->assertSessionHas('error');
    expect(AssetTemplate::find($template->id))->not->toBeNull();

    // Deactivate template
    $this->actingAs($this->admin)->post("/settings/asset-templates/{$template->id}/deactivate")
        ->assertRedirect('/settings?tab=asset-templates');
    expect($template->fresh()->active)->toBeFalse();

    // Picker hides deactivated template
    $this->actingAs($this->staff)->getJson('/api/asset-templates/search?q=Infusion')
        ->assertOk()
        ->assertJsonCount(0);

    // Updating the existing asset still keeps working with its deactivated asset_template_id
    $this->actingAs($this->staff)->put("/assets/{$asset->id}", [
        'asset_template_id' => $template->id,
        'asset_name' => 'Infusion Pump Edited',
        'brand' => 'B. Braun',
        'model' => 'Infusomat',
        'category_id' => $cat->id,
        'department_id' => $dept->id,
        'section_id' => $sec->id,
        'status' => 'In Use',
        'asset_condition' => 'In Good Condition',
        'notes' => 'Original note',
    ])->assertRedirect("/assets/{$asset->id}");

    expect($asset->fresh()->asset_name)->toBe('Infusion Pump Edited')
        ->and($asset->fresh()->asset_template_id)->toBe($template->id);

    // Custom entry without selecting a template automatically creates a masterlist entry even when optional fields (brand, model, category, remarks) are blank
    $this->actingAs($this->staff)->post('/assets', [
        'asset_name' => 'Custom Bed',
        'department_id' => $dept->id,
        'section_id' => $sec->id,
        'status' => 'In Use',
        'asset_condition' => 'Brand-new',
    ])->assertRedirect();

    $customBedAsset = Asset::where('asset_name', 'Custom Bed')->first();
    $autoCreatedTemplate = AssetTemplate::where('name', 'Custom Bed')->first();
    expect($autoCreatedTemplate)->not->toBeNull()
        ->and($autoCreatedTemplate->brand)->toBeNull()
        ->and($autoCreatedTemplate->model)->toBeNull()
        ->and($autoCreatedTemplate->category_id)->toBeNull()
        ->and($customBedAsset->asset_template_id)->toBe($autoCreatedTemplate->id);

    $this->actingAs($this->staff)->post('/assets', [
        'asset_template_id' => $template->id,
        'asset_name' => 'Blocked Inactive Pick',
        'department_id' => $dept->id,
        'section_id' => $sec->id,
        'status' => 'In Use',
        'asset_condition' => 'Brand-new',
    ])->assertSessionHasErrors('asset_template_id');
});

test('staff gets 403 on all template management routes and picker json never leaks money or depreciation', function () {
    $cat = Category::create([
        'name' => 'Medical Equipment',
        'default_useful_life_years' => 12,
        'default_salvage_percent' => 15,
    ]);

    $template = AssetTemplate::create([
        'name' => 'Defibrillator',
        'brand' => 'Philips',
        'model' => 'HeartStart XL',
        'category_id' => $cat->id,
        'default_remarks' => 'Check paddles monthly',
        'active' => true,
    ]);

    // Staff 403 checks on every management route
    $this->actingAs($this->staff)->post('/settings/asset-templates', ['name' => 'Blocked'])->assertForbidden();
    $this->actingAs($this->staff)->put("/settings/asset-templates/{$template->id}", ['name' => 'Blocked'])->assertForbidden();
    $this->actingAs($this->staff)->post("/settings/asset-templates/{$template->id}/deactivate")->assertForbidden();
    $this->actingAs($this->staff)->post("/settings/asset-templates/{$template->id}/reactivate")->assertForbidden();
    $this->actingAs($this->staff)->delete("/settings/asset-templates/{$template->id}")->assertForbidden();
    $this->actingAs($this->staff)->get('/settings/asset-templates/import-sample')->assertForbidden();
    $this->actingAs($this->staff)->post('/settings/asset-templates/import/preview', [])->assertForbidden();
    $this->actingAs($this->staff)->post('/settings/asset-templates/import/commit')->assertForbidden();

    // Staff does not see "+ Add to masterlist" on asset create form, but sees overwrite prompt text in JS
    $this->actingAs($this->staff)->get('/assets/create')
        ->assertOk()
        ->assertSee('Select from masterlist')
        ->assertSee('Replace the current values?')
        ->assertDontSee('+ Add to masterlist');

    // Admin sees "+ Add to masterlist"
    $this->actingAs($this->admin)->get('/assets/create')
        ->assertOk()
        ->assertSee('+ Add to masterlist');

    // Picker JSON scan as staff
    $jsonResponse = $this->actingAs($this->staff)->getJson('/api/asset-templates/search?q=Defibrillator');
    $jsonResponse->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $template->id)
        ->assertJsonPath('0.name', 'Defibrillator')
        ->assertJsonPath('0.brand', 'Philips')
        ->assertJsonPath('0.model', 'HeartStart XL')
        ->assertJsonPath('0.category_id', $cat->id)
        ->assertJsonPath('0.category_name', 'Medical Equipment');

    $rawJson = strtolower((string) $jsonResponse->getContent());
    foreach (['disposal', 'salvage', 'useful_life', 'cost', 'value', 'percent', 'book', '12', '15'] as $forbiddenToken) {
        expect($rawJson)->not->toContain($forbiddenToken);
    }

    // Settings templates tab has no money columns/words
    $settingsHtml = strtolower((string) $this->actingAs($this->admin)->get('/settings?tab=asset-templates')->assertOk()->getContent());
    expect($settingsHtml)->toContain('asset masterlist')
        ->and($settingsHtml)->toContain('import templates from excel');
});

test('settings asset template excel import previews duplicates flags unknown categories without creating them and commits clean rows', function () {
    $med = Category::create(['name' => 'Medical Equipment', 'default_useful_life_years' => 10, 'default_salvage_percent' => null]);

    AssetTemplate::create([
        'name' => 'Patient Monitor',
        'brand' => 'Mindray',
        'model' => 'uMEC10',
        'category_id' => $med->id,
        'active' => true,
    ]);

    $this->actingAs($this->admin)
        ->get('/settings/asset-templates/import-sample')
        ->assertOk();

    $csv = implode("\n", [
        'name,brand,model,category,default_remarks',
        'Patient Monitor,Mindray,uMEC10,Medical Equipment,Duplicate existing',
        'Nebulizer,Omron,NE-C28,Medical Equipment,Valid known category',
        'Nebulizer,Omron,NE-C28,Medical Equipment,Duplicate inside sheet',
        'Wheelchair,Local,Folding,Unknown Mobility Category,Unknown category row',
        ',Local,MissingName,Medical Equipment,Invalid missing name',
    ]);

    $file = UploadedFile::fake()->createWithContent('templates.csv', $csv);

    $preview = $this->actingAs($this->admin)
        ->post('/settings/asset-templates/import/preview', ['file' => $file]);

    $preview->assertOk()
        ->assertSee('Duplicate (Skipped)')
        ->assertSee('Missing Name (Skipped)')
        ->assertSee('Category not found');

    // Commit
    $this->actingAs($this->admin)
        ->post('/settings/asset-templates/import/commit')
        ->assertRedirect('/settings?tab=asset-templates');

    expect(AssetTemplate::count())->toBe(3)
        ->and(Category::where('name', 'Unknown Mobility Category')->exists())->toBeFalse()
        ->and(AssetTemplate::where('name', 'Nebulizer')->first()->category_id)->toBe($med->id)
        ->and(AssetTemplate::where('name', 'Wheelchair')->first()->category_id)->toBeNull();
});

test('asset excel import matches asset_template column and auto fills blank asset fields', function () {
    $med = Category::create(['name' => 'Medical Equipment', 'default_useful_life_years' => 10, 'default_salvage_percent' => null]);
    Department::create(['name' => 'Nursing', 'code' => 'ND', 'next_number' => 1, 'active' => true, 'code_confirmed' => true]);

    $tpl = AssetTemplate::create([
        'name' => 'Patient Monitor',
        'brand' => 'Mindray',
        'model' => 'uMEC10',
        'category_id' => $med->id,
        'default_remarks' => 'Check leads',
        'active' => true,
    ]);

    $csv = implode("\n", [
        'asset_no,asset_description,brand,model,department,section,category,status,asset_condition,remarks,asset_template',
        'BBH-TPL-001,,,,Nursing,Station 1,,In Use,Brand-new,,Patient Monitor',
    ]);

    $file = UploadedFile::fake()->createWithContent('assets-with-template.csv', $csv);

    $this->actingAs($this->admin)
        ->post('/reports/import/preview', ['file' => $file])
        ->assertOk()
        ->assertSee('Patient Monitor')
        ->assertSee('Mindray')
        ->assertSee('uMEC10');

    $this->actingAs($this->admin)
        ->post('/reports/import/commit')
        ->assertRedirect('/reports');

    $imported = Asset::where('asset_no', 'BBH-TPL-001')->firstOrFail();
    expect($imported->asset_template_id)->toBe($tpl->id)
        ->and($imported->asset_name)->toBe('Patient Monitor')
        ->and($imported->brand)->toBe('Mindray')
        ->and($imported->model)->toBe('uMEC10')
        ->and($imported->category_id)->toBe($med->id)
        ->and($imported->notes)->toBe('Check leads');
});
