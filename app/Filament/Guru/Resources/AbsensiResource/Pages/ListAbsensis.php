<?php
// app/Filament/Guru/Resources/AbsensiResource/Pages/ListAbsensis.php

namespace App\Filament\Guru\Resources\AbsensiResource\Pages;

use App\Filament\Guru\Resources\AbsensiResource;
use App\Models\Absensi;
use App\Models\Jadwal;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ListAbsensis extends ListRecords
{
    protected static string $resource = AbsensiResource::class;

    protected static string $view = 'filament.guru.resources.absensi-resource.pages.list-absensis';

    public $modalData = [
        'jadwal_id' => null,
        'tanggal' => null,
        'kelas' => null,
        'mapel' => null,
    ];

    public function getAbsensiHariIni()
    {
        $guru = Auth::user()->guru;

        return Absensi::query()
            ->select(
                'jadwal_id',
                'tanggal',
                DB::raw('MIN(id) as id'),
                DB::raw('COUNT(DISTINCT siswa_id) as total_siswa'),
                DB::raw('SUM(CASE WHEN status = "hadir" THEN 1 ELSE 0 END) as jumlah_hadir')
            )
            ->whereHas('jadwal.mapel', function ($query) use ($guru) {
                $query->where('guru_id', $guru->id);
            })
            ->whereDate('tanggal', now())
            ->with(['jadwal.kelas', 'jadwal.mapel'])
            ->groupBy('jadwal_id', 'tanggal')
            ->orderBy('tanggal', 'desc')
            ->get()
            ->map(function ($item) {
                $persentase = 0;
                if ($item->total_siswa > 0) {
                    $persentase = round(($item->jumlah_hadir / $item->total_siswa) * 100);
                }

                return [
                    'id' => $item->id,
                    'jadwal_id' => $item->jadwal_id,
                    'tanggal' => $item->tanggal,
                    'kelas' => $item->jadwal->kelas->nama ?? '-',
                    'mapel' => $item->jadwal->mapel->nama_matapelajaran ?? '-',
                    'hari' => $item->jadwal->hari ?? '-',
                    'jam_ke' => $item->jadwal->jam_ke ?? '-',
                    'jam_mulai' => $item->jadwal->jam_mulai ?? '-',
                    'jam_selesai' => $item->jadwal->jam_selesai ?? '-',
                    'total_siswa' => $item->total_siswa,
                    'jumlah_hadir' => $item->jumlah_hadir,
                    'persentase' => $persentase,
                ];
            });
    }

    public function getAbsensiSebelumnya()
    {
        $guru = Auth::user()->guru;

        return Absensi::query()
            ->select(
                'jadwal_id',
                'tanggal',
                DB::raw('MIN(id) as id'),
                DB::raw('COUNT(DISTINCT siswa_id) as total_siswa'),
                DB::raw('SUM(CASE WHEN status = "hadir" THEN 1 ELSE 0 END) as jumlah_hadir')
            )
            ->whereHas('jadwal.mapel', function ($query) use ($guru) {
                $query->where('guru_id', $guru->id);
            })
            ->whereDate('tanggal', '<', now())
            ->with(['jadwal.kelas', 'jadwal.mapel'])
            ->groupBy('jadwal_id', 'tanggal')
            ->orderBy('tanggal', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($item) {
                $persentase = 0;
                if ($item->total_siswa > 0) {
                    $persentase = round(($item->jumlah_hadir / $item->total_siswa) * 100);
                }

                return [
                    'id' => $item->id,
                    'jadwal_id' => $item->jadwal_id,
                    'tanggal' => $item->tanggal,
                    'kelas' => $item->jadwal->kelas->nama ?? '-',
                    'mapel' => $item->jadwal->mapel->nama_matapelajaran ?? '-',
                    'hari' => $item->jadwal->hari ?? '-',
                    'jam_ke' => $item->jadwal->jam_ke ?? '-',
                    'jam_mulai' => $item->jadwal->jam_mulai ?? '-',
                    'jam_selesai' => $item->jadwal->jam_selesai ?? '-',
                    'total_siswa' => $item->total_siswa,
                    'jumlah_hadir' => $item->jumlah_hadir,
                    'persentase' => $persentase,
                ];
            });
    }

    /**
     * Tampilkan modal konfirmasi untuk kirim single absensi
     */
    public function kirimKeWaliKelas($jadwalId, $tanggal, $kelas, $mapel)
    {
        $this->modalData = [
            'jadwal_id' => $jadwalId,
            'tanggal' => \Carbon\Carbon::parse($tanggal)->format('d/m/Y'),
            'kelas' => $kelas,
            'mapel' => $mapel,
        ];

        $this->dispatch('open-modal', id: 'modal-kirim-single');
    }

    /**
     * Proses kirim single absensi (placeholder - implementasi nanti)
     */
    public function prosesKirimSingle()
    {
        // TODO: Implementasi setelah membuat rangkuman wali kelas

        Notification::make()
            ->title('Fitur dalam pengembangan')
            ->body('Fungsi kirim ke wali kelas akan diimplementasikan setelah pembuatan rangkuman wali kelas.')
            ->info()
            ->send();

        $this->dispatch('close-modal', id: 'modal-kirim-single');
    }

    /**
     * Tampilkan modal konfirmasi untuk kirim semua absensi hari ini
     */
    public function kirimSemuaKeWaliKelas()
    {
        $absensiHariIni = $this->getAbsensiHariIni();

        if ($absensiHariIni->count() === 0) {
            Notification::make()
                ->title('Tidak ada data')
                ->body('Tidak ada absensi hari ini yang dapat dikirim.')
                ->warning()
                ->send();
            return;
        }

        $this->dispatch('open-modal', id: 'modal-kirim-semua');
    }

    /**
     * Proses kirim semua absensi (placeholder - implementasi nanti)
     */
    public function prosesKirimSemua()
    {
        // TODO: Implementasi setelah membuat rangkuman wali kelas

        $absensiHariIni = $this->getAbsensiHariIni();

        Notification::make()
            ->title('Fitur dalam pengembangan')
            ->body("Fungsi kirim {$absensiHariIni->count()} absensi ke wali kelas akan diimplementasikan setelah pembuatan rangkuman wali kelas.")
            ->info()
            ->send();

        $this->dispatch('close-modal', id: 'modal-kirim-semua');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('input_absensi')
                ->label('Input Absensi')
                ->icon('heroicon-o-clipboard-document-check')
                ->url(fn() => static::getResource()::getUrl('input'))
                ->color('success'),
        ];
    }
}
