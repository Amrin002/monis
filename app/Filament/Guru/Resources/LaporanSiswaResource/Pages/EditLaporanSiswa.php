<?php

namespace App\Filament\Guru\Resources\LaporanSiswaResource\Pages;

use App\Filament\Guru\Resources\LaporanSiswaResource;
use App\Models\Laporan;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLaporanSiswa extends EditRecord
{
    protected static string $resource = LaporanSiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validasi sebelum update
        $sudahAda = Laporan::sudahAdaLaporanHariIni(
            $data['guru_id'],
            $data['siswa_id'],
            $data['jadwal_id'],
            $data['tanggal'],
            $this->record->id // Ignore laporan yang sedang diedit
        );

        if ($sudahAda) {
            Notification::make()
                ->danger()
                ->title('Gagal Update Laporan')
                ->body('Sudah ada laporan untuk siswa ini pada mata pelajaran dan tanggal yang sama!')
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

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Laporan siswa berhasil diupdate';
    }
}
