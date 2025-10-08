<?php
// app/Filament/Resources/JadwalResource/Pages/EditJadwal.php

namespace App\Filament\Resources\JadwalResource\Pages;

use App\Filament\Resources\JadwalResource;
use App\Models\Jadwal;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditJadwal extends EditRecord
{
    protected static string $resource = JadwalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validasi bentrok sebelum update
        $hasil = Jadwal::cekBentrokJadwal(
            $data['kelas_id'],
            $data['mapel_id'],
            $data['hari'],
            $data['jam_mulai'],
            $data['jam_selesai'],
            $this->record->id // Ignore jadwal yang sedang diedit
        );

        if ($hasil['bentrok']) {
            $icon = $hasil['tipe'] === 'guru' ? '👨‍🏫' : '🏫';

            Notification::make()
                ->danger()
                ->title("{$icon} Gagal Update Jadwal")
                ->body($hasil['pesan'])
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
        return 'Jadwal berhasil diupdate';
    }
}
