<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Cashier\Subscription;

class PlatformAnalytics
{
    public function totalWorkspaces(): int
    {
        return Tenant::count();
    }

    public function workspacesThisMonth(): int
    {
        return Tenant::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    public function platformUsers(): int
    {
        return User::count();
    }

    public function activeSubscriptions(): int
    {
        return Subscription::query()
            ->where('stripe_status', 'active')
            ->count();
    }

    public function workspacesPerMonth(int $months = 6): Collection
    {
        $start = now()->subMonths($months - 1)->startOfMonth();

        $counts = Tenant::query()
            ->where('created_at', '>=', $start)
            ->get()
            ->groupBy(fn (Tenant $tenant): string => $tenant->created_at->format('Y-m'))
            ->map->count();

        return collect(range(0, $months - 1))
            ->map(function (int $offset) use ($start, $counts): array {
                $month = $start->copy()->addMonths($offset);

                return [
                    'label' => $month->format('M Y'),
                    'count' => $counts->get($month->format('Y-m'), 0),
                ];
            });
    }
}
