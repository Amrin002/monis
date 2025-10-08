<?php
// app/Filament/Guru/Resources/LaporanWaliKelasResource/Widgets/LaporanWaliKelasStatsWidget.php

namespace App\Filament\Guru\Resources\LaporanWalikelasResource\Widgets;

use App\Models\LaporanWalikelas;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class LaporanWalikelasStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $guru = Auth::user()->guru;

        $totalLaporan = LaporanWalikelas::where('guru_id', $guru->id)->count();
        $laporanBulanIni = LaporanWalikelas::where('guru_id', $guru->id)->bulanIni()->count();
        $laporanMingguIni = LaporanWalikelas::where('guru_id', $guru->id)->mingguIni()->count();

        return [
            Stat::make('Total Laporan', $totalLaporan)
                ->description('Semua laporan yang dibuat')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('success'),

            Stat::make('Laporan Bulan Ini', $laporanBulanIni)
                ->description('Laporan di bulan ' . now()->format('F'))
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('Laporan Minggu Ini', $laporanMingguIni)
                ->description('Laporan minggu ini')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
        ];
    }
}
