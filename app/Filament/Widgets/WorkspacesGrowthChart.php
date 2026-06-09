<?php

namespace App\Filament\Widgets;

use App\Services\PlatformAnalytics;
use Filament\Widgets\ChartWidget;

class WorkspacesGrowthChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = null;

    public function getHeading(): ?string
    {
        return __('app.filament.analytics_growth_chart');
    }

    protected function getData(): array
    {
        $series = app(PlatformAnalytics::class)->workspacesPerMonth();

        return [
            'datasets' => [
                [
                    'label' => __('app.filament.workspaces'),
                    'data' => $series->pluck('count')->all(),
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $series->pluck('label')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
