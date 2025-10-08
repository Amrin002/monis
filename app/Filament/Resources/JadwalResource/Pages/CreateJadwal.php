<?php
// app/Filament/Resources/JadwalResource/Pages/CreateJadwal.php

namespace App\Filament\Resources\JadwalResource\Pages;

use App\Filament\Resources\JadwalResource;
use App\Models\Jadwal;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateJadwal extends CreateRecord
{
    protected static string $resource = JadwalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validasi bentrok sebelum create
        $hasil = Jadwal::cekBentrokJadwal(
            $data['kelas_id'],
            $data['mapel_id'],
            $data['hari'],
            $data['jam_mulai'],
            $data['jam_selesai']
        );

        if ($hasil['bentrok']) {
            $icon = $hasil['tipe'] === 'guru' ? '👨‍🏫' : '🏫';

            Notification::make()
                ->danger()
                ->title("{$icon} Gagal Membuat Jadwal")
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

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Jadwal berhasil dibuat';
    }
}
