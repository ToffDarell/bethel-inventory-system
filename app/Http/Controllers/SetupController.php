<?php

namespace App\Http\Controllers;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SetupController extends Controller
{
    private function ensureLocalMachine(Request $request): void
    {
        $ip = (string) $request->ip();
        if (! in_array($ip, ['127.0.0.1', '::1', '::ffff:127.0.0.1'], true)) {
            abort(403, 'Initial setup can only be accessed from this local computer.');
        }
    }

    public function show(Request $request): View|RedirectResponse
    {
        $this->ensureLocalMachine($request);

        if (Schema::hasTable('users') && User::count() > 0) {
            return redirect()->route(Auth::check() ? 'dashboard' : 'login');
        }

        return view('auth.setup');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureLocalMachine($request);

        if (Schema::hasTable('users') && User::count() > 0) {
            return redirect()->route(Auth::check() ? 'dashboard' : 'login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'alpha_dash', 'unique:users,username'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ], [
            'name.required' => 'Please enter your full name.',
            'username.required' => 'Please choose an administrator username.',
            'username.min' => 'Username must be at least 3 characters.',
            'username.alpha_dash' => 'Username may only contain letters, numbers, dashes, and underscores.',
            'username.unique' => 'This username is already taken.',
            'password.required' => 'Please enter a password.',
            'password.min' => 'Password must be at least 10 characters long.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        // Ensure baseline settings and categories are seeded
        app(DatabaseSeeder::class)->run();

        $recoveryCode = User::generateRecoveryCode();

        // Create the administrator user with must_change_password false
        $admin = User::create([
            'name' => trim($validated['name']),
            'username' => strtolower(trim($validated['username'])),
            'email' => ! empty($validated['email']) ? trim($validated['email']) : null,
            'password' => $validated['password'],
            'role' => 'admin',
            'must_change_password' => false,
            'recovery_code_hash' => Hash::make($recoveryCode),
            'recovery_code_generated_at' => now(),
        ]);

        activity()
            ->causedBy($admin)
            ->performedOn($admin)
            ->log("Initial administrator account created: {$admin->name} ({$admin->username})");

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'Administrator account created successfully. Welcome to Bethel Asset Tracker.')
            ->with('admin_recovery_code_once', $recoveryCode);
    }
}
