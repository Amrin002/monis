<?php

namespace App\Filament\Guru\Resources\LaporanWalikelasResource\Pages;

use App\Filament\Guru\Resources\LaporanWalikelasResource;
use App\Models\LaporanWalikelas;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateLaporanWalikelas extends CreateRecord
{
    protected static string $resource = LaporanWalikelasResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validasi sebelum create
        $sudahAda = LaporanWalikelas::sudahAdaLaporan(
            $data['guru_id'],
            $data['siswa_id'],
            $data['tanggal']
        );

        if ($sudahAda) {
            Notification::make()
                ->danger()
                ->title('Gagal Membuat Laporan')
                ->body('Anda sudah membuat laporan untuk siswa ini pada tanggal yang sama!')
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
        return 'Laporan wali kelas berhasil dibuat';
    }
}
