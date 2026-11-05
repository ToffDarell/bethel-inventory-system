<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'acquired_year_only')) {
                $table->boolean('acquired_year_only')->nullable()->default(false)->after('date_acquired');
            }
        });

        // Backfill: if date_acquired is January 1, set acquired_year_only to true, false otherwise
        DB::table('assets')->whereNotNull('date_acquired')->chunkById(200, function ($assets) {
            foreach ($assets as $asset) {
                $date = Carbon::parse($asset->date_acquired);
                $isJan1 = ($date->month === 1 && $date->day === 1);
                DB::table('assets')->where('id', $asset->id)->update([
                    'acquired_year_only' => $isJan1,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'acquired_year_only')) {
                $table->dropColumn('acquired_year_only');
            }
        });
    }
};
