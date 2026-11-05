<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

test('login by username succeeds and records activity', function () {
    $user = User::factory()->create([
        'name' => 'Maria Santos',
        'username' => 'maria.santos',
        'email' => 'maria.santos@hospital.local',
        'password' => Hash::make('HospitalPass123!'),
        'must_change_password' => false,
    ]);

    $response = $this->post('/login', [
        'username' => 'maria.santos',
        'password' => 'HospitalPass123!',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

test('login by username is case-insensitive', function () {
    $user = User::factory()->create([
        'name' => 'Maria Santos',
        'username' => 'maria.santos',
        'password' => Hash::make('HospitalPass123!'),
        'must_change_password' => false,
    ]);

    // Uppercase
    $this->post('/login', [
        'username' => 'MARIA.SANTOS',
        'password' => 'HospitalPass123!',
    ])->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);

    auth()->logout();

    // Mixed case
    $this->post('/login', [
        'username' => 'Maria.Santos',
        'password' => 'HospitalPass123!',
    ])->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

test('login by old email still works and is case-insensitive', function () {
    $user = User::factory()->create([
        'name' => 'Dr. Smith',
        'username' => 'dr.smith',
        'email' => 'dr.smith@hospital.local',
        'password' => Hash::make('DoctorPass123!'),
        'must_change_password' => false,
    ]);

    // Lowercase email
    $this->post('/login', [
        'username' => 'dr.smith@hospital.local',
        'password' => 'DoctorPass123!',
    ])->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);

    auth()->logout();

    // Uppercase email
    $this->post('/login', [
        'username' => 'DR.SMITH@HOSPITAL.LOCAL',
        'password' => 'DoctorPass123!',
    ])->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

test('wrong password or unknown username returns exact error message', function () {
    User::factory()->create([
        'username' => 'known.user',
        'password' => Hash::make('CorrectPassword1!'),
    ]);

    // Wrong password
    $this->from('/login')->post('/login', [
        'username' => 'known.user',
        'password' => 'WrongPassword',
    ])
    ->assertRedirect('/login')
    ->assertSessionHasErrors(['username' => 'Username or password is incorrect']);

    $this->assertGuest();

    // Unknown username
    $this->from('/login')->post('/login', [
        'username' => 'nonexistent.user',
        'password' => 'SomePassword',
    ])
    ->assertRedirect('/login')
    ->assertSessionHasErrors(['username' => 'Username or password is incorrect']);

    $this->assertGuest();
});

test('forced password change applies on first login by username', function () {
    $this->seed(DatabaseSeeder::class);

    $this->post('/login', [
        'username' => 'admin',
        'password' => 'password123',
    ])->assertRedirect('/password/change');

    $this->get('/dashboard')->assertRedirect('/password/change');

    $this->post('/password/change', [
        'password' => 'NewAdminPass2026!',
        'password_confirmation' => 'NewAdminPass2026!',
    ])->assertRedirect('/dashboard');

    expect(auth()->user()->fresh()->must_change_password)->toBeFalse();
    $this->get('/dashboard')->assertOk()->assertSee('Dashboard');
});

test('settings users tab creates user with username and optional email', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'username' => 'chief_admin',
        'must_change_password' => false,
    ]);

    // User with email
    $response = $this->actingAs($admin)->post('/settings/users', [
        'name' => 'Juan Dela Cruz',
        'username' => 'juan.delacruz',
        'email' => 'juan@hospital.local',
        'role' => 'staff',
        'password' => 'tempPass123',
    ]);

    $response->assertRedirect('/settings?tab=users');
    $this->assertDatabaseHas('users', [
        'username' => 'juan.delacruz',
        'email' => 'juan@hospital.local',
        'role' => 'staff',
        'must_change_password' => true,
    ]);

    // User without email (optional)
    $responseNoEmail = $this->actingAs($admin)->post('/settings/users', [
        'name' => 'Pedro Penduko',
        'username' => 'pedro_penduko',
        'email' => '',
        'role' => 'staff',
        'password' => 'tempPass123',
    ]);

    $responseNoEmail->assertRedirect('/settings?tab=users');
    $this->assertDatabaseHas('users', [
        'username' => 'pedro_penduko',
        'email' => null,
        'role' => 'staff',
    ]);
});

test('duplicate username is rejected with validation error', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'username' => 'chief_admin',
        'must_change_password' => false,
    ]);

    User::factory()->create([
        'username' => 'existing.user',
    ]);

    $response = $this->actingAs($admin)->post('/settings/users', [
        'name' => 'Duplicate Candidate',
        'username' => 'existing.user',
        'email' => 'candidate@hospital.local',
        'role' => 'staff',
        'password' => 'tempPass123',
    ]);

    $response->assertSessionHasErrors('username');
});

test('username validation rules enforce length and allowed characters', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'username' => 'chief_admin',
        'must_change_password' => false,
    ]);

    // Too short (< 3)
    $this->actingAs($admin)->post('/settings/users', [
        'name' => 'Short',
        'username' => 'ab',
        'role' => 'staff',
        'password' => 'tempPass123',
    ])->assertSessionHasErrors('username');

    // Too long (> 30)
    $this->actingAs($admin)->post('/settings/users', [
        'name' => 'Long',
        'username' => str_repeat('a', 31),
        'role' => 'staff',
        'password' => 'tempPass123',
    ])->assertSessionHasErrors('username');

    // Disallowed characters (spaces or special symbols)
    $this->actingAs($admin)->post('/settings/users', [
        'name' => 'Symbols',
        'username' => 'user name!#',
        'role' => 'staff',
        'password' => 'tempPass123',
    ])->assertSessionHasErrors('username');

    // Valid characters (letters, numbers, dot, dash, underscore)
    $this->actingAs($admin)->post('/settings/users', [
        'name' => 'Valid Chars',
        'username' => 'user.name-123_ok',
        'role' => 'staff',
        'password' => 'tempPass123',
    ])->assertSessionHasNoErrors();
});

test('admin can reset another user password with forced change on next login', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'username' => 'super_admin',
        'must_change_password' => false,
    ]);

    $staff = User::factory()->create([
        'role' => 'staff',
        'username' => 'nurse_jane',
        'password' => Hash::make('OldPassword1!'),
        'must_change_password' => false,
    ]);

    $response = $this->actingAs($admin)->post("/settings/users/{$staff->id}/reset-password", [
        'password' => 'NewTemporaryPass999',
    ]);

    $response->assertRedirect('/settings?tab=users');
    expect($staff->fresh()->must_change_password)->toBeTrue()
        ->and(Hash::check('NewTemporaryPass999', $staff->fresh()->password))->toBeTrue();

    // Target user logs in with new temporary password and must change password
    auth()->logout();
    $this->post('/login', [
        'username' => 'nurse_jane',
        'password' => 'NewTemporaryPass999',
    ])->assertRedirect('/password/change');
});

test('staff cannot reset user passwords', function () {
    $staff1 = User::factory()->create([
        'role' => 'staff',
        'username' => 'staff_one',
        'must_change_password' => false,
    ]);

    $staff2 = User::factory()->create([
        'role' => 'staff',
        'username' => 'staff_two',
        'must_change_password' => false,
    ]);

    $this->actingAs($staff1)->post("/settings/users/{$staff2->id}/reset-password", [
        'password' => 'HackedPassword123',
    ])->assertForbidden();
});

test('migration backfills existing users cleanly on a copy database with colliding email prefixes', function () {
    $copyPath = storage_path('framework/testing_user_migration_copy.sqlite');
    if (File::exists($copyPath)) {
        File::delete($copyPath);
    }
    touch($copyPath);

    config(['database.connections.copy_test' => [
        'driver' => 'sqlite',
        'database' => $copyPath,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]]);

    $copyDb = DB::connection('copy_test');

    // Create old users table (without username column, email NOT NULL UNIQUE)
    $copyDb->getSchemaBuilder()->create('users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->string('role')->default('staff');
        $table->boolean('must_change_password')->default(false);
        $table->rememberToken();
        $table->timestamps();
    });

    // Populate with existing users
    $copyDb->table('users')->insert([
        [
            'name' => 'Hospital Administrator',
            'email' => 'admin@hospital.local',
            'password' => Hash::make('AdminPass123!'),
            'role' => 'admin',
            'must_change_password' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => 'Staff Nurse 1',
            'email' => 'staff@hospital.local',
            'password' => Hash::make('StaffPass1!'),
            'role' => 'staff',
            'must_change_password' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => 'Staff Nurse 2 (Colliding Prefix)',
            'email' => 'staff@clinic.external',
            'password' => Hash::make('StaffPass2!'),
            'role' => 'staff',
            'must_change_password' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => 'Uppercase Email User',
            'email' => 'DOCTOR.WHO@hospital.local',
            'password' => Hash::make('DoctorWhoPass!'),
            'role' => 'staff',
            'must_change_password' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    // Apply the username migration to the copy database
    $originalDefault = DB::getDefaultConnection();
    DB::setDefaultConnection('copy_test');

    try {
        $migration = require database_path('migrations/2026_11_05_000002_add_username_to_users_table.php');
        $migration->up();

        // Verify users backfilled
        $adminUser = $copyDb->table('users')->where('email', 'admin@hospital.local')->first();
        $staff1 = $copyDb->table('users')->where('email', 'staff@hospital.local')->first();
        $staff2 = $copyDb->table('users')->where('email', 'staff@clinic.external')->first();
        $doc = $copyDb->table('users')->where('email', 'DOCTOR.WHO@hospital.local')->first();

        expect($adminUser->username)->toBe('admin')
            ->and($staff1->username)->toBe('staff')
            ->and($staff2->username)->toBe('staff1') // Disambiguated with appended number
            ->and($doc->username)->toBe('doctor.who'); // Lowercased

        // Verify password hashes intact
        expect(Hash::check('AdminPass123!', $adminUser->password))->toBeTrue()
            ->and(Hash::check('StaffPass1!', $staff1->password))->toBeTrue()
            ->and(Hash::check('StaffPass2!', $staff2->password))->toBeTrue()
            ->and(Hash::check('DoctorWhoPass!', $doc->password))->toBeTrue();

        // Verify NULL email is now supported
        $copyDb->table('users')->insert([
            'name' => 'No Email User',
            'username' => 'no_email_user',
            'email' => null,
            'password' => Hash::make('secret'),
            'role' => 'staff',
            'must_change_password' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $newbie = $copyDb->table('users')->where('username', 'no_email_user')->first();
        expect($newbie)->not->toBeNull()
            ->and($newbie->email)->toBeNull();

    } finally {
        DB::setDefaultConnection($originalDefault);
        if (File::exists($copyPath)) {
            File::delete($copyPath);
        }
    }
});
