<?php

namespace App\Filament\Widgets;

use App\Models\Siswa;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Absensi;
use App\Models\Laporan;
use App\Models\Pengumuman;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Hitung total siswa
        $totalSiswa = Siswa::count();
        $siswaThisMonth = Siswa::whereMonth('created_at', now()->month)->count();

        // Hitung total guru
        $totalGuru = Guru::count();
        $guruWaliKelas = Guru::where('is_wali_kelas', true)->count();
        $guruMapel = Guru::where('is_guru_mapel', true)->count();

        // Hitung total kelas
        $totalKelas = Kelas::count();

        // Hitung absensi hari ini
        $absensiToday = Absensi::whereDate('tanggal', today())->count();
        $hadirToday = Absensi::whereDate('tanggal', today())
            ->where('status', 'Hadir')
            ->count();
        $alpaToday = Absensi::whereDate('tanggal', today())
            ->where('status', 'Alpa')
            ->count();

        // Hitung laporan bulan ini
        $laporanThisMonth = Laporan::whereMonth('tanggal', now()->month)->count();

        // Hitung pengumuman aktif
        $pengumumanAktif = Pengumuman::whereDate('tanggal', '<=', today())
            ->whereDate('tanggal', '>=', today()->subDays(7))
            ->count();

        return [
            Stat::make('Total Siswa', $totalSiswa)
                ->description($siswaThisMonth . ' siswa baru bulan ini')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success')
                ->chart([7, 12, 8, 15, 10, 18, $siswaThisMonth]),

            Stat::make('Total Guru', $totalGuru)
                ->description("Wali Kelas: {$guruWaliKelas} | Guru Mapel: {$guruMapel}")
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),

            Stat::make('Total Kelas', $totalKelas)
                ->description('Kelas aktif')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('warning'),

            Stat::make('Absensi Hari Ini', $absensiToday)
                ->description("Hadir: {$hadirToday} | Alpa: {$alpaToday}")
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color($alpaToday > 0 ? 'danger' : 'success'),

            Stat::make('Laporan Bulan Ini', $laporanThisMonth)
                ->description('Laporan siswa')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Pengumuman Aktif', $pengumumanAktif)
                ->description('7 hari terakhir')
                ->descriptionIcon('heroicon-m-megaphone')
                ->color('success'),
        ];
    }
}
