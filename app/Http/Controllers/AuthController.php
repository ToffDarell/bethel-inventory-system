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
        if (Schema::hasTable('users') && User::count() === 0) {
            return redirect()->route('setup');
        }

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
        if (Schema::hasTable('users') && User::count() === 0) {
            return redirect()->route('setup');
        }

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

    public function showForgotPassword(): View|RedirectResponse
    {
        if (Schema::hasTable('users') && User::count() === 0) {
            return redirect()->route('setup');
        }

        return view('auth.forgot-password');
    }

    public function resetWithRecoveryCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:60'],
            'recovery_code' => ['required', 'string', 'max:64'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ], [
            'username.required' => 'Please enter your administrator username.',
            'recovery_code.required' => 'Please enter your admin recovery code.',
            'password.required' => 'Please enter a new password.',
            'password.min' => 'Password must be at least 10 characters long.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        $username = strtolower(trim($validated['username']));
        $throttleKey = 'recovery|'.$username.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'recovery_code' => "Too many recovery attempts. Please try again in {$seconds} seconds.",
                ]);
        }

        $user = User::whereRaw('LOWER(username) = ?', [$username])->first();
        $normalizedCode = User::normalizeRecoveryCode($validated['recovery_code']);

        if (! $user || ! $user->isAdmin() || empty($user->recovery_code_hash) || ! Hash::check($normalizedCode, $user->recovery_code_hash)) {
            RateLimiter::hit($throttleKey, 120);

            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'recovery_code' => 'The username or recovery code is invalid.',
                ]);
        }

        RateLimiter::clear($throttleKey);

        // One-time use: rotate immediately to a new recovery code and show it once after sign-in,
        // or invalidate the consumed code (clear hash) and let dashboard checklist remind admin to regenerate.
        $user->update([
            'password' => $validated['password'],
            'must_change_password' => false,
            'recovery_code_hash' => null,
            'recovery_code_generated_at' => null,
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->log("Administrator password reset using recovery code for {$user->username}");

        return redirect()
            ->route('login')
            ->with('success', 'Password reset complete. Sign in with your new password and generate a new recovery code in Settings.');
    }

    protected static bool $initialSeededChecked = false;

    protected function ensureInitialAdminSeeded(): void
    {
        if (static::$initialSeededChecked || app()->runningUnitTests()) {
            return;
        }

        static::$initialSeededChecked = true;

        try {
            if (Schema::hasTable('users')) {
                if (! Schema::hasColumn('users', 'username')) {
                    Artisan::call('migrate', ['--force' => true]);
                }
                if (config('app.env') === 'local' && User::count() === 0) {
                    app(DatabaseSeeder::class)->run();
                }
            }
        } catch (\Throwable) {
            // Ignore if database is not yet migrated
        }
    }
}
