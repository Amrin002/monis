<?php

namespace App\Filament\Widgets;

use App\Models\Kelas;
use Filament\Widgets\ChartWidget;

class SiswaPerKelasChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Distribusi Siswa per Kelas';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $kelasData = Kelas::withCount('siswas')
            ->orderBy('nama')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Siswa',
                    'data' => $kelasData->pluck('siswas_count')->toArray(),
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.5)',
                        'rgba(34, 197, 94, 0.5)',
                        'rgba(234, 179, 8, 0.5)',
                        'rgba(239, 68, 68, 0.5)',
                        'rgba(168, 85, 247, 0.5)',
                        'rgba(236, 72, 153, 0.5)',
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(34, 197, 94)',
                        'rgb(234, 179, 8)',
                        'rgb(239, 68, 68)',
                        'rgb(168, 85, 247)',
                        'rgb(236, 72, 153)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $kelasData->pluck('nama')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
