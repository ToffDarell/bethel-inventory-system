<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username', 30)->nullable()->unique()->after('name');
            }
            $table->string('email')->nullable()->change();
        });

        // Backfill existing users from the part of their email before the "@", lowercased, made unique if needed
        $users = DB::table('users')->orderBy('id')->get();
        $usedUsernames = DB::table('users')
            ->whereNotNull('username')
            ->pluck('username')
            ->map(fn ($u) => strtolower((string) $u))
            ->all();

        foreach ($users as $user) {
            if (! empty($user->username)) {
                continue;
            }

            $email = (string) ($user->email ?? '');
            $base = '';
            if (str_contains($email, '@')) {
                $base = strtolower(trim(explode('@', $email)[0]));
            }

            // Keep only allowed characters: letters, numbers, dot, dash, underscore
            $base = preg_replace('/[^a-z0-9._-]/', '', $base);

            // Ensure at least 3 characters
            if (strlen($base) < 3) {
                $base = 'user' . $user->id;
            }

            // Cap base length at 25 so any numeric suffix fits comfortably within 30 chars
            if (strlen($base) > 25) {
                $base = substr($base, 0, 25);
            }

            $candidate = $base;
            $counter = 1;

            while (in_array(strtolower($candidate), $usedUsernames, true)
                || DB::table('users')->whereRaw('LOWER(username) = ?', [strtolower($candidate)])->where('id', '!=', $user->id)->exists()
            ) {
                $candidate = $base . $counter;
                $counter++;
            }

            $usedUsernames[] = strtolower($candidate);

            DB::table('users')->where('id', $user->id)->update([
                'username' => $candidate,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Additive-only migrations for desktop update safety. No column deletion.
    }
};
