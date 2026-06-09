<?php

namespace App\Filament\Widgets;

use App\Services\PlatformAnalytics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SaaSAnalyticsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $analytics = app(PlatformAnalytics::class);

        return [
            Stat::make(__('app.filament.analytics_total_workspaces'), $analytics->totalWorkspaces())
                ->description(__('app.filament.analytics_new_this_month', ['count' => $analytics->workspacesThisMonth()]))
                ->descriptionIcon(Heroicon::OutlinedArrowTrendingUp)
                ->color('primary'),
            Stat::make(__('app.filament.analytics_active_subscriptions'), $analytics->activeSubscriptions())
                ->description(__('app.filament.analytics_stripe_subscribers'))
                ->descriptionIcon(Heroicon::OutlinedCreditCard)
                ->color('success'),
            Stat::make(__('app.filament.analytics_platform_users'), $analytics->platformUsers())
                ->description(__('app.filament.analytics_central_users'))
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->color('info'),
        ];
    }
}
