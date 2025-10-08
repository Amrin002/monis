<?php

namespace App\Filament\Guru\Resources\LaporanKelasResource\Pages;

use App\Filament\Guru\Resources\LaporanKelasResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLaporanKelas extends CreateRecord
{
    protected static string $resource = LaporanKelasResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Laporan kelas berhasil dibuat';
    }
}
