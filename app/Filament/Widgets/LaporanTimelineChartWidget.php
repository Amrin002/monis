<?php

namespace App\Filament\Widgets;

use App\Models\Laporan;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class LaporanTimelineChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Trend Laporan Siswa (6 Bulan Terakhir)';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $count = Laporan::whereYear('tanggal', $month->year)
                ->whereMonth('tanggal', $month->month)
                ->count();

            $data[] = $count;
            $labels[] = $month->format('M Y');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Laporan',
                    'data' => $data,
                    'fill' => true,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
