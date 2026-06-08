<?php

namespace App\Filament\Widgets;

use App\Models\Tenant;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WorkspacesStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $centralDomain = config('app.central_domain');

        return [
            Stat::make(__('app.filament.stats_workspaces'), Tenant::count())
                ->description(__('app.filament.stats_workspaces_desc'))
                ->descriptionIcon(Heroicon::OutlinedRectangleStack)
                ->color('primary'),
            Stat::make(__('app.filament.stats_admins'), User::count())
                ->description(__('app.filament.stats_admins_desc'))
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->color('success'),
            Stat::make(__('app.filament.stats_domain'), $centralDomain)
                ->description(__('app.filament.stats_domain_desc'))
                ->descriptionIcon(Heroicon::OutlinedGlobeAlt)
                ->color('gray'),
        ];
    }
}
