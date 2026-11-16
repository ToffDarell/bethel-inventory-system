<?php

use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

test('fresh database shows setup screen on root and login routes', function () {
    expect(User::count())->toBe(0);

    // Root URL redirects to /setup
    $rootRes = $this->get('/');
    $rootRes->assertRedirect(route('setup'));

    // Login URL redirects to /setup
    $loginRes = $this->get('/login');
    $loginRes->assertRedirect(route('setup'));

    // Setup screen renders properly
    $setupRes = $this->get('/setup');
    $setupRes->assertOk();
    $setupRes->assertSee('Create administrator account');
    $setupRes->assertSee('Administrator Username');
    $setupRes->assertSee('Minimum 10 characters');
    $setupRes->assertSee('Create Account', false);
});

test('setup screen cannot be reached once an administrator or user exists', function () {
    $admin = User::factory()->create([
        'username' => 'existingadmin',
        'role' => 'admin',
        'must_change_password' => false,
    ]);

    expect(User::count())->toBe(1);

    // Unauthenticated visitor trying to visit /setup is redirected to /login
    $res = $this->get('/setup');
    $res->assertRedirect(route('login'));

    // Authenticated admin trying to visit /setup is redirected to /dashboard
    $authRes = $this->actingAs($admin)->get('/setup');
    $authRes->assertRedirect(route('dashboard'));

    // Unauthenticated visitor attempting to post to /setup when user exists is also redirected
    \Illuminate\Support\Facades\Auth::logout();
    $postRes = $this->post('/setup', [
        'name' => 'Intruder',
        'username' => 'intruder',
        'password' => 'SecurePass123!@#',
        'password_confirmation' => 'SecurePass123!@#',
    ]);
    $postRes->assertRedirect(route('login'));
    expect(User::where('username', 'intruder')->exists())->toBeFalse();
});

test('setup validates required inputs and password rules (min 10 chars, confirmed)', function () {
    expect(User::count())->toBe(0);

    // 1. Empty submission
    $resEmpty = $this->post('/setup', []);
    $resEmpty->assertSessionHasErrors(['name', 'username', 'password']);

    // 2. Short password (under 10 characters)
    $resShort = $this->post('/setup', [
        'name' => 'Admin Test',
        'username' => 'admintest',
        'password' => 'short9ch',
        'password_confirmation' => 'short9ch',
    ]);
    $resShort->assertSessionHasErrors(['password']);

    // 3. Mismatched confirmation
    $resMismatch = $this->post('/setup', [
        'name' => 'Admin Test',
        'username' => 'admintest',
        'password' => 'ValidPassword123!',
        'password_confirmation' => 'DifferentPassword123!',
    ]);
    $resMismatch->assertSessionHasErrors(['password']);

    expect(User::count())->toBe(0);
});

test('submitting setup creates admin with must_change_password false, logs activity, and logs in to dashboard', function () {
    expect(User::count())->toBe(0);

    $response = $this->post('/setup', [
        'name' => 'Chief Technology Officer',
        'username' => 'sysadmin',
        'email' => 'sysadmin@hospital.org',
        'password' => 'MasterClinicalKey2026!',
        'password_confirmation' => 'MasterClinicalKey2026!',
    ]);

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('success');

    // Admin user was created in database
    expect(User::count())->toBe(1);
    $admin = User::first();
    expect($admin->name)->toBe('Chief Technology Officer');
    expect($admin->username)->toBe('sysadmin');
    expect($admin->email)->toBe('sysadmin@hospital.org');
    expect($admin->role)->toBe('admin');
    expect($admin->must_change_password)->toBeFalse();
    expect(Hash::check('MasterClinicalKey2026!', $admin->password))->toBeTrue();

    // Authenticated in session
    $this->assertAuthenticatedAs($admin);

    // Activity was recorded
    $log = Activity::latest('id')->first();
    expect($log)->not->toBeNull();
    expect($log->description)->toContain('Initial administrator account created: Chief Technology Officer (sysadmin)');

    // Baseline settings and categories exist
    expect(Setting::get('hospital_name'))->not->toBeNull();
    expect(Category::count())->toBeGreaterThan(0);
});

test('no known passwords exist after a production seed and dev accounts only seed when APP_ENV=local', function () {
    // 1. Simulate production environment
    config(['app.env' => 'production']);
    expect(config('app.env') === 'local')->toBeFalse();

    User::truncate();
    expect(User::count())->toBe(0);

    // Run DatabaseSeeder in production
    app(DatabaseSeeder::class)->run();

    // In production, no users are seeded at all (zero users, zero default passwords)
    expect(User::count())->toBe(0);
    expect(User::where('username', 'admin')->exists())->toBeFalse();
    expect(User::where('username', 'staff')->exists())->toBeFalse();

    // Verify baseline settings and categories are still seeded
    expect(Setting::get('hospital_name'))->toBe('Bethel Baptist Hospital, Inc.');
    expect(Category::where('name', 'Medical Equipment')->exists())->toBeTrue();

    // 2. Simulate local environment
    config(['app.env' => 'local']);
    expect(config('app.env') === 'local')->toBeTrue();

    // Run DatabaseSeeder in local
    app(DatabaseSeeder::class)->run();

    // In local, dev admin is seeded
    expect(User::where('username', 'admin')->exists())->toBeTrue();
    $admin = User::where('username', 'admin')->first();
    expect(Hash::check('password123', $admin->password))->toBeTrue();
});
