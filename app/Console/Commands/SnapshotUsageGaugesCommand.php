<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\UsageMeter;
use Illuminate\Console\Command;

class SnapshotUsageGaugesCommand extends Command
{
    protected $signature = 'usage:snapshot-gauges {--tenant= : Snapshot a single workspace id}';

    protected $description = 'Refresh team_seats and storage_bytes gauges for usage billing';

    public function handle(UsageMeter $usageMeter): int
    {
        if (! config('usage.enabled')) {
            $this->warn('Usage billing is disabled (USAGE_BILLING_ENABLED=false).');

            return self::SUCCESS;
        }

        $query = Tenant::query()->orderBy('id');

        if ($id = $this->option('tenant')) {
            $query->whereKey($id);
        }

        $count = 0;

        $query->each(function (Tenant $tenant) use ($usageMeter, &$count): void {
            $usageMeter->snapshotGauges($tenant);
            $count++;
            $this->line("Snapshot: {$tenant->id}");
        });

        $this->info("Updated gauges for {$count} workspace(s).");

        return self::SUCCESS;
    }
}
