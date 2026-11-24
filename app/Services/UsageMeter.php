<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\UsageRecord;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Throwable;

class UsageMeter
{
    public function periodStart(): string
    {
        return now()->startOfMonth()->toDateString();
    }

    public function record(Tenant $tenant, string $meter, int $quantity = 1): ?UsageRecord
    {
        if (! config('usage.enabled') || ! $this->meterConfigured($meter) || $quantity < 1) {
            return null;
        }

        $keys = [
            'tenant_id' => $tenant->id,
            'meter' => $meter,
            'period_start' => $this->periodStart(),
        ];

        try {
            $record = UsageRecord::query()->firstOrCreate($keys, ['quantity' => 0]);
        } catch (UniqueConstraintViolationException) {
            $record = UsageRecord::query()
                ->where('tenant_id', $tenant->id)
                ->where('meter', $meter)
                ->whereDate('period_start', $this->periodStart())
                ->firstOrFail();
        }

        $record->increment('quantity', $quantity);

        if (config('usage.sync_to_stripe')) {
            $this->reportToStripe($tenant, $meter, $quantity);
        }

        return $record->fresh();
    }

    public function setGauge(Tenant $tenant, string $meter, int $quantity): ?UsageRecord
    {
        if (! config('usage.enabled') || ! $this->meterConfigured($meter) || $quantity < 0) {
            return null;
        }

        $record = UsageRecord::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'meter' => $meter,
                'period_start' => $this->periodStart(),
            ],
            ['quantity' => $quantity],
        );

        if (config('usage.sync_to_stripe') && $quantity > 0) {
            $this->reportToStripe($tenant, $meter, $quantity);
        }

        return $record;
    }

    public function snapshotTeamSeats(Tenant $tenant): ?UsageRecord
    {
        try {
            $count = 0;

            $tenant->run(function () use (&$count): void {
                $count = User::query()->count();
            });
        } catch (Throwable $exception) {
            Log::debug('usage.team_seats_snapshot_skipped', [
                'tenant_id' => $tenant->id,
                'message' => $exception->getMessage(),
            ]);

            return null;
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }

        return $this->setGauge($tenant, 'team_seats', $count);
    }

    /**
     * @return array<string, mixed>
     */
    public function summarize(Tenant $tenant): array
    {
        $periodStart = $this->periodStart();
        $records = UsageRecord::query()
            ->where('tenant_id', $tenant->id)
            ->where('period_start', $periodStart)
            ->get()
            ->keyBy('meter');

        $meters = [];

        foreach (config('usage.meters', []) as $key => $config) {
            $meters[$key] = [
                'label' => $config['label'],
                'description' => $config['description'] ?? null,
                'quantity' => (int) ($records->get($key)?->quantity ?? 0),
                'event_name' => $config['event_name'],
            ];
        }

        return [
            'period_start' => $periodStart,
            'period_end' => now()->endOfMonth()->toDateString(),
            'meters' => $meters,
        ];
    }

    private function meterConfigured(string $meter): bool
    {
        return isset(config('usage.meters', [])[$meter]);
    }

    private function reportToStripe(Tenant $tenant, string $meter, int $quantity): void
    {
        if (! filled($tenant->stripe_id)) {
            return;
        }

        $eventName = config("usage.meters.{$meter}.event_name");

        if (! filled($eventName)) {
            return;
        }

        try {
            $tenant->reportMeterEvent($eventName, $quantity);
        } catch (Throwable $exception) {
            Log::warning('usage.stripe_report_failed', [
                'tenant_id' => $tenant->id,
                'meter' => $meter,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
