<?php

namespace App\Console\Commands;

use App\Services\FixedAssetService;
use Illuminate\Console\Command;

class RunDepreciation extends Command
{
    protected $signature   = 'dental:depreciate {period? : YYYY-MM, defaults to current month}';
    protected $description = 'Run monthly depreciation for all active fixed assets';

    public function handle(FixedAssetService $service): int
    {
        $period = $this->argument('period') ?? now()->format('Y-m');

        if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
            $this->error("Period must be in YYYY-MM format. Got: {$period}");
            return self::FAILURE;
        }

        $this->info("Running depreciation for period: {$period}");
        $result = $service->runMonthlyDepreciation($period);

        $this->info("Processed: {$result['processed']} assets");
        $this->line("Skipped (already done): {$result['skipped']} assets");

        return self::SUCCESS;
    }
}
