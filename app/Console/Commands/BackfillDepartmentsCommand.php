<?php

namespace App\Console\Commands;

use App\Services\DepartmentBackfillService;
use Illuminate\Console\Command;

class BackfillDepartmentsCommand extends Command
{
    protected $signature = 'departments:backfill';
    protected $description = 'Backfill departments from existing assets with suggested codes needing review';

    public function handle(DepartmentBackfillService $service): int
    {
        $this->info('Starting department backfill...');
        $report = $service->runBackfill();
        $this->info("Linked {$report['linked_assets_count']} assets.");
        foreach ($report['created'] as $created) {
            $this->line("- Created {$created['name']} (Code: {$created['code']}, Needs Review: Yes)");
        }
        return self::SUCCESS;
    }
}
