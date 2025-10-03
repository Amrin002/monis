<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\LatestActivitiesWidget::class,
            \App\Filament\Widgets\StatsOverviewWidget::class,
            \App\Filament\Widgets\LaporanTimelineChartWidget::class,
            \App\Filament\Widgets\AbsensiChartWidget::class,
            \App\Filament\Widgets\SiswaPerKelasChartWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
