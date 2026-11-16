<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'asset_template_id')) {
                $table->foreignId('asset_template_id')
                    ->nullable()
                    ->after('vendor_id')
                    ->constrained('asset_templates')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'asset_template_id')) {
                $table->dropConstrainedForeignId('asset_template_id');
            }
        });
    }
};
