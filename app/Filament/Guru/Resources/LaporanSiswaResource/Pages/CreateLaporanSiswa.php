<?php

namespace App\Filament\Guru\Resources\LaporanSiswaResource\Pages;

use App\Filament\Guru\Resources\LaporanSiswaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLaporanSiswa extends CreateRecord
{
    protected static string $resource = LaporanSiswaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Laporan siswa berhasil dibuat';
    }
}
