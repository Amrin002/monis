<?php

namespace App\Filament\Guru\Resources\LaporanWalikelasResource\Pages;

use App\Filament\Guru\Resources\LaporanWalikelasResource;
use App\Models\LaporanWalikelas;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLaporanWalikelas extends EditRecord
{
    protected static string $resource = LaporanWalikelasResource::class;

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
        $sudahAda = LaporanWalikelas::sudahAdaLaporan(
            $data['guru_id'],
            $data['siswa_id'],
            $data['tanggal'],
            $this->record->id
        );

        if ($sudahAda) {
            Notification::make()
                ->danger()
                ->title('Gagal Update Laporan')
                ->body('Sudah ada laporan untuk siswa ini pada tanggal yang sama!')
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
        return 'Laporan wali kelas berhasil diupdate';
    }
}
