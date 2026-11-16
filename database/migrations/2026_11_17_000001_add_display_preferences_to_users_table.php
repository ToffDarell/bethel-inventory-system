<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'text_size')) {
                $table->string('text_size', 20)->nullable()->default('default');
            }
            if (! Schema::hasColumn('users', 'density')) {
                $table->string('density', 20)->nullable()->default('comfortable');
            }
            if (! Schema::hasColumn('users', 'theme')) {
                $table->string('theme', 20)->nullable()->default('light');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $drop = [];
            foreach (['text_size', 'density', 'theme'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $drop[] = $col;
                }
            }
            if (! empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
