<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'brand')) {
                $table->string('brand')->nullable()->after('asset_name');
            }
            if (! Schema::hasColumn('assets', 'section')) {
                $table->string('section')->nullable()->after('department');
            }
            if (! Schema::hasColumn('assets', 'assigned_personnel')) {
                $table->string('assigned_personnel')->nullable()->after('section');
            }
            if (! Schema::hasColumn('assets', 'asset_condition')) {
                $table->string('asset_condition')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['brand', 'section', 'assigned_personnel', 'asset_condition']);
        });
    }
};
