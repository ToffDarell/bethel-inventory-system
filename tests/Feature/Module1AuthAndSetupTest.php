<?php

use App\Models\Asset;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    config(['app.env' => 'local']);
});

test('seeder creates default admin requiring password change and hospital settings', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'admin@hospital.local')->first();
    expect($admin)->not->toBeNull()
        ->and($admin->username)->toBe('admin')
        ->and($admin->role)->toBe('admin')
        ->and($admin->must_change_password)->toBeTrue()
        ->and(Hash::check('password123', $admin->password))->toBeTrue();

    expect(Setting::get('hospital_name'))->toBe('Bethel Baptist Hospital, Inc.')
        ->and(Setting::get('hospital_address'))->toBe('Fortich St., Sumpong, Malaybalay City, Bukidnon 8700')
        ->and(Setting::get('asset_no_prefix'))->toBe('BBHI')
        ->and(Setting::get('tag_width_cm'))->toBe('10')
        ->and(Setting::get('tag_height_cm'))->toBe('6');
});

test('seeding twice does not duplicate the admin or reset a changed password', function () {
    $this->seed(DatabaseSeeder::class);
    $admin = User::where('email', 'admin@hospital.local')->first();
    $admin->update(['password' => 'MyOwnPass99!', 'must_change_password' => false]);

    $this->seed(DatabaseSeeder::class);

    expect(User::where('email', 'admin@hospital.local')->count())->toBe(1)
        ->and(Hash::check('MyOwnPass99!', $admin->fresh()->password))->toBeTrue()
        ->and($admin->fresh()->must_change_password)->toBeFalse();
});

test('first login forces password change before accessing dashboard', function () {
    $this->seed(DatabaseSeeder::class);

    $this->post('/login', [
        'email' => 'admin@hospital.local',
        'password' => 'password123',
    ])->assertRedirect('/password/change');

    $this->get('/dashboard')->assertRedirect('/password/change');

    $this->post('/password/change', [
        'password' => 'NewHospitalPass1!',
        'password_confirmation' => 'NewHospitalPass1!',
    ])->assertRedirect('/dashboard');

    expect(auth()->user()->fresh()->must_change_password)->toBeFalse();
    $this->get('/dashboard')->assertOk()->assertSee('Dashboard');
});

test('new password cannot reuse the temporary password', function () {
    $this->seed(DatabaseSeeder::class);
    $this->post('/login', ['email' => 'admin@hospital.local', 'password' => 'password123']);

    $this->post('/password/change', [
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('password');
});

test('wrong password shows a friendly error and guests are sent to login', function () {
    $this->seed(DatabaseSeeder::class);

    $this->get('/dashboard')->assertRedirect('/login');

    $this->from('/login')->post('/login', [
        'email' => 'admin@hospital.local',
        'password' => 'wrong',
    ])->assertRedirect('/login')->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('layout sidebar shows only the five main sections', function () {
    $user = User::factory()->create(['role' => 'staff', 'must_change_password' => false]);

    $this->actingAs($user)->get('/dashboard')
        ->assertOk()
        ->assertSeeInOrder(['Dashboard', 'Assets', 'Find Asset', 'Reports', 'Settings'])
        ->assertDontSee('fonts.googleapis', false)
        ->assertDontSee('cdn.', false);
});

test('admin middleware blocks staff', function () {
    $staff = User::factory()->create(['role' => 'staff', 'must_change_password' => false]);
    $admin = User::factory()->create(['role' => 'admin', 'must_change_password' => false]);

    expect($staff->isAdmin())->toBeFalse()->and($admin->isAdmin())->toBeTrue();
});

test('assets are soft deleted', function () {
    $asset = Asset::create(['asset_no' => 'BBH-000001', 'asset_name' => 'Bed', 'status' => 'In Use']);
    $asset->delete();

    expect(Asset::count())->toBe(0)
        ->and(Asset::withTrashed()->count())->toBe(1)
        ->and(Asset::STATUSES)->toBe(['In Use', 'Under Repair', 'Stored', 'Disposed']);
});
