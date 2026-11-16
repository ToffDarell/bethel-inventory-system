<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'recovery_code_hash')) {
                $table->string('recovery_code_hash')->nullable()->after('must_change_password');
            }
            if (! Schema::hasColumn('users', 'recovery_code_generated_at')) {
                $table->timestamp('recovery_code_generated_at')->nullable()->after('recovery_code_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'recovery_code_generated_at')) {
                $table->dropColumn('recovery_code_generated_at');
            }
            if (Schema::hasColumn('users', 'recovery_code_hash')) {
                $table->dropColumn('recovery_code_hash');
            }
        });
    }
};
