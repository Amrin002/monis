<?php

namespace App\Filament\Widgets;

use App\Models\Absensi;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AbsensiChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Statistik Absensi (7 Hari Terakhir)';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = Absensi::where('tanggal', '>=', now()->subDays(6))
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Absensi',
                    'data' => [
                        $data['Hadir'] ?? 0,
                        $data['Izin'] ?? 0,
                        $data['Sakit'] ?? 0,
                        $data['Alpa'] ?? 0,
                    ],
                    'backgroundColor' => [
                        'rgb(34, 197, 94)',  // Green - Hadir
                        'rgb(234, 179, 8)',  // Yellow - Izin
                        'rgb(59, 130, 246)', // Blue - Sakit
                        'rgb(239, 68, 68)',  // Red - Alpa
                    ],
                ],
            ],
            'labels' => ['Hadir', 'Izin', 'Sakit', 'Alpa'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
