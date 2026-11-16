<?php

use App\Models\Asset;
use App\Models\Category;
use App\Models\Department;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('setup screen is restricted to the local machine and rate limited', function () {
    expect(User::count())->toBe(0);

    // Non-local IP gets 403 on GET and POST /setup
    $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.55'])
        ->get('/setup')
        ->assertForbidden();

    $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.55'])
        ->post('/setup', [
            'name' => 'Remote Intruder',
            'username' => 'intruder',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ])
        ->assertForbidden();

    // Repeated invalid POST /setup attempts from local IP hit rate limiter (429)
    for ($i = 0; $i < 10; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => '::1'])
            ->post('/setup', []);
    }

    $this->withServerVariables(['REMOTE_ADDR' => '::1'])
        ->post('/setup', [])
        ->assertStatus(429);
});

test('setup generates a one-time hashed admin recovery code that works on forgot password', function () {
    $response = $this->post('/setup', [
        'name' => 'Master Admin',
        'username' => 'masteradmin',
        'email' => 'master@hospital.local',
        'password' => 'InitialStrongKey123!',
        'password_confirmation' => 'InitialStrongKey123!',
    ]);

    $response->assertRedirect(route('dashboard'));
    $recoveryCode = session('admin_recovery_code_once');
    expect($recoveryCode)->toBeString()->toStartWith('BBH-');

    $admin = User::where('username', 'masteradmin')->firstOrFail();
    expect($admin->recovery_code_hash)->not->toBeNull()
        ->and($admin->recovery_code_hash)->not->toBe($recoveryCode)
        ->and(Hash::check($recoveryCode, $admin->recovery_code_hash))->toBeTrue();

    // Dashboard displays recovery code once and not inside subsequent visits
    $this->get('/dashboard')
        ->assertOk()
        ->assertSee($recoveryCode);
    $this->get('/dashboard')
        ->assertOk()
        ->assertDontSee($recoveryCode);

    // Sign out and use Forgot Password flow
    $this->post('/logout')->assertRedirect(route('login'));
    $this->get('/login')->assertOk()->assertSee('Forgot password?');
    $this->get('/forgot-password')->assertOk()->assertSee('Reset admin password');

    // Wrong recovery code fails
    $this->post('/forgot-password', [
        'username' => 'masteradmin',
        'recovery_code' => 'BBH-0000-0000-0000',
        'password' => 'NewRecoveredKey123!',
        'password_confirmation' => 'NewRecoveredKey123!',
    ])->assertSessionHasErrors('recovery_code');

    // Correct recovery code resets password and invalidates one-time code
    $this->post('/forgot-password', [
        'username' => 'masteradmin',
        'recovery_code' => $recoveryCode,
        'password' => 'NewRecoveredKey123!',
        'password_confirmation' => 'NewRecoveredKey123!',
    ])->assertRedirect(route('login'));

    $admin->refresh();
    expect(Hash::check('NewRecoveredKey123!', $admin->password))->toBeTrue()
        ->and($admin->recovery_code_hash)->toBeNull();

    // Admin can regenerate recovery code in Settings
    $this->actingAs($admin)
        ->post(route('settings.users.recovery-code', $admin))
        ->assertRedirect(route('settings.index', ['tab' => 'users']));

    $newCode = session('admin_recovery_code_once');
    $admin->refresh();
    expect($newCode)->toBeString()->toStartWith('BBH-')
        ->and(Hash::check($newCode, $admin->recovery_code_hash))->toBeTrue();
});

test('last administrator cannot be deleted or demoted', function () {
    $admin = User::factory()->create([
        'username' => 'soleadmin',
        'role' => 'admin',
        'must_change_password' => false,
    ]);
    $staff = User::factory()->create([
        'username' => 'normalstaff',
        'role' => 'staff',
        'must_change_password' => false,
    ]);

    // Attempt to demote sole admin -> blocked
    $this->actingAs($admin)
        ->patch(route('settings.users.role', $admin), ['role' => 'staff'])
        ->assertRedirect(route('settings.index', ['tab' => 'users']))
        ->assertSessionHas('error');

    expect($admin->fresh()->role)->toBe('admin');

    // Promote staff -> now 2 admins
    $this->actingAs($admin)
        ->patch(route('settings.users.role', $staff), ['role' => 'admin'])
        ->assertRedirect(route('settings.index', ['tab' => 'users']));
    expect($staff->fresh()->role)->toBe('admin');

    // Now demoting second admin works, leaving sole admin protected again
    $this->actingAs($admin)
        ->patch(route('settings.users.role', $staff), ['role' => 'staff'])
        ->assertRedirect(route('settings.index', ['tab' => 'users']));
    expect($staff->fresh()->role)->toBe('staff');

    // Staff cannot manage users
    $this->actingAs($staff)
        ->delete(route('settings.users.destroy', $admin))
        ->assertForbidden();

    // Admin cannot delete themselves
    $this->actingAs($admin)
        ->delete(route('settings.users.destroy', $admin))
        ->assertRedirect(route('settings.index', ['tab' => 'users']))
        ->assertSessionHas('error');

    expect(User::find($admin->id))->not->toBeNull();
});

test('admin dashboard shows dismissible first-run checklist and hides it for staff', function () {
    $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);
    $staff = User::factory()->create(['role' => 'staff', 'must_change_password' => false]);

    $this->actingAs($staff)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Initial Setup Checklist');

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Initial Setup Checklist')
        ->assertSee('Verify hospital name and address');

    $this->actingAs($staff)
        ->post('/dashboard/dismiss-checklist')
        ->assertForbidden();

    $this->actingAs($admin)
        ->post('/dashboard/dismiss-checklist')
        ->assertRedirect(route('dashboard'));

    expect(Setting::get('admin_checklist_dismissed'))->toBe('1');

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Initial Setup Checklist');
});

test('seeded categories default to null disposal percent and unreviewed', function () {
    (new CategorySeeder())->run();

    $categories = Category::all();
    expect($categories)->toHaveCount(7);

    foreach ($categories as $category) {
        expect($category->default_salvage_percent)->toBeNull()
            ->and($category->disposal_reviewed)->toBeFalse();
    }
});

test('import flags unrecognized departments as needs review and creates them unconfirmed on commit', function () {
    $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);

    $csv = implode("\n", [
        'asset_no,asset_description,department,status,asset_condition',
        'BBH-DEPT-01,Ventilator,Pulmonary Therapy,In Use,Brand-new',
    ]);
    $file = UploadedFile::fake()->createWithContent('dept_import.csv', $csv);

    $this->actingAs($admin)
        ->post('/reports/import/preview', ['file' => $file])
        ->assertOk()
        ->assertSee('Pulmonary Therapy')
        ->assertSee('Needs review');

    $this->actingAs($admin)
        ->post('/reports/import/commit')
        ->assertRedirect('/reports');

    $dept = Department::where('name', 'Pulmonary Therapy')->first();
    expect($dept)->not->toBeNull()
        ->and($dept->code_confirmed)->toBeFalse();

    $asset = Asset::where('asset_no', 'BBH-DEPT-01')->first();
    expect($asset)->not->toBeNull()
        ->and($asset->department_id)->toBe($dept->id)
        ->and($asset->department)->toBe('Pulmonary Therapy');
});

test('vendor seeder seeds all 10 initial vendors only when empty', function () {
    expect(Vendor::count())->toBe(0);

    (new \Database\Seeders\VendorSeeder())->run();

    expect(Vendor::count())->toBe(10)
        ->and(Vendor::where('is_donation', true)->count())->toBe(3)
        ->and(Vendor::where('name', 'DONATION FROM UNILAB')->first()->is_donation)->toBeTrue()
        ->and(Vendor::where('name', 'U.S DONATION')->first()->is_donation)->toBeTrue()
        ->and(Vendor::where('name', '3 J MEDICAL')->first()->is_donation)->toBeFalse();

    // Modifying existing data and re-running never overwrites or duplicates
    $gaisano = Vendor::where('name', 'GAISANO')->firstOrFail();
    $gaisano->update(['contact_person' => 'Preserved Contact']);

    (new \Database\Seeders\VendorSeeder())->run();
    expect(Vendor::count())->toBe(10)
        ->and($gaisano->fresh()->contact_person)->toBe('Preserved Contact');
});
