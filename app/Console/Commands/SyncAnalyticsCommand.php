<?php

namespace App\Console\Commands;

use App\Services\AnalyticsSyncService;
use Illuminate\Console\Command;

class SyncAnalyticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync analytics counters from Redis to database';

    /**
     * Execute the console command.
     */
    public function handle(AnalyticsSyncService $syncService): int
    {
        $this->info('Starting analytics sync...');

        $result = $syncService->sync();

        $this->info("Synced {$result['synced']} paths");

        if (!empty($result['errors'])) {
            $this->error('Errors occurred during sync:');
            foreach ($result['errors'] as $error) {
                $this->warn("  - {$error}");
            }
            return 1;
        }

        $this->info('Sync completed successfully');
        return 0;
    }
}
