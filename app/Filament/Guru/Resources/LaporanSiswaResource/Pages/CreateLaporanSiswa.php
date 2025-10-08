<?php

namespace App\Filament\Guru\Resources\LaporanSiswaResource\Pages;

use App\Filament\Guru\Resources\LaporanSiswaResource;
use App\Models\Laporan;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateLaporanSiswa extends CreateRecord
{
    protected static string $resource = LaporanSiswaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validasi sebelum create
        $sudahAda = Laporan::sudahAdaLaporanHariIni(
            $data['guru_id'],
            $data['siswa_id'],
            $data['jadwal_id'],
            $data['tanggal']
        );

        if ($sudahAda) {
            Notification::make()
                ->danger()
                ->title('Gagal Membuat Laporan')
                ->body('Anda sudah membuat laporan untuk siswa ini pada mata pelajaran dan tanggal yang sama!')
                ->persistent()
                ->send();

            $this->halt();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Laporan siswa berhasil dibuat';
    }
}
