<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assets')) {
            Schema::create('assets', function (Blueprint $table) {
                $table->id();
                $table->string('asset_no')->unique();
                $table->string('asset_name');
                $table->string('model')->nullable();
                $table->string('serial_no')->nullable();
                $table->date('date_acquired')->nullable();
                $table->string('department')->nullable()->index();
                $table->string('location')->nullable();
                $table->string('status')->default('In Use')->index();
                $table->text('notes')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Additive-only migrations for desktop update safety
    }
};
