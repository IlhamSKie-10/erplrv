<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductionWorkOrder;
use Carbon\Carbon;

class CleanupExpiredPinnedJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:cleanup-pinned';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up pinned jobs that have expired';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $expiredJobs = ProductionWorkOrder::where('is_pinned', true)
            ->whereNotNull('pinned_expires_at')
            ->where('pinned_expires_at', '<', $now)
            ->get();

        $count = 0;
        foreach ($expiredJobs as $workOrder) {
            $workOrder->update([
                'is_pinned' => false,
                'pinned_expires_at' => null,
                'manual_sort_index' => null,
            ]);
            $count++;
        }

        $this->info("Cleaned up {$count} expired pinned jobs.");
    }
}
