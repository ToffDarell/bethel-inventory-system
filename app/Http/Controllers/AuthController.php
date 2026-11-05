<?php

namespace App\Http\Controllers;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $this->ensureInitialAdminSeeded();

        $defaultAdmin = null;
        if (Schema::hasTable('users')) {
            $defaultAdmin = Schema::hasColumn('users', 'username')
                ? User::where('username', 'admin')->orWhere('email', 'admin@hospital.local')->first()
                : User::where('email', 'admin@hospital.local')->first();
        }

        return view('auth.login', [
            'showInitialCredentialsHint' => $defaultAdmin && $defaultAdmin->must_change_password,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $this->ensureInitialAdminSeeded();

        $login = trim((string) ($request->input('username') ?? $request->input('email') ?? ''));
        $password = (string) $request->input('password', '');
        $errorKey = $request->has('email') && ! $request->has('username') ? 'email' : 'username';

        if ($login === '' || $password === '') {
            return back()
                ->withInput($request->only('username', 'email'))
                ->withErrors([
                    $errorKey => 'Username or password is incorrect',
                    'username' => 'Username or password is incorrect',
                ]);
        }

        $throttleKey = Str::transliterate(Str::lower($login).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->withInput($request->only('username', 'email'))
                ->withErrors([
                    $errorKey => "Too many login attempts. Please try again in {$seconds} seconds.",
                    'username' => "Too many login attempts. Please try again in {$seconds} seconds.",
                ]);
        }

        $hasUsernameCol = Schema::hasTable('users') && Schema::hasColumn('users', 'username');
        $userQuery = User::query();

        if ($hasUsernameCol) {
            $userQuery->where(function ($q) use ($login) {
                $q->whereRaw('LOWER(username) = ?', [strtolower($login)])
                  ->orWhereRaw('LOWER(email) = ?', [strtolower($login)]);
            });
        } else {
            $userQuery->whereRaw('LOWER(email) = ?', [strtolower($login)]);
        }

        $user = $userQuery->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return back()
                ->withInput($request->only('username', 'email'))
                ->withErrors([
                    $errorKey => 'Username or password is incorrect',
                    'username' => 'Username or password is incorrect',
                ]);
        }

        RateLimiter::clear($throttleKey);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $user = Auth::user();
        activity()
            ->causedBy($user)
            ->log("User {$user->name} ({$user->username}) signed in");

        if ($user->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            activity()
                ->causedBy($user)
                ->log("User {$user->name} ({$user->username}) signed out");
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showChangePassword(): View
    {
        return view('auth.change-password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Please enter a new password.',
            'password.min' => 'Your new password must be at least 8 characters long.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        $user = $request->user();

        if (Hash::check($validated['password'], $user->password)) {
            return back()->withErrors([
                'password' => 'Please choose a new password that is different from your temporary password.',
            ]);
        }

        $user->update([
            'password' => $validated['password'],
            'must_change_password' => false,
        ]);

        activity()
            ->causedBy($user)
            ->log("User {$user->name} ({$user->username}) changed their password");

        return redirect()
            ->route('dashboard')
            ->with('success', 'Your password has been updated successfully.');
    }

    protected function ensureInitialAdminSeeded(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        try {
            if (Schema::hasTable('users')) {
                if (! Schema::hasColumn('users', 'username')) {
                    Artisan::call('migrate', ['--force' => true]);
                }
                if (User::count() === 0) {
                    app(DatabaseSeeder::class)->run();
                }
            }
        } catch (\Throwable) {
            // Ignore if database is not yet migrated
        }
    }
}
