<?php

namespace App\Filament\Guru\Resources\LaporanSiswaResource\Pages;

use App\Filament\Guru\Resources\LaporanSiswaResource;
use Filament\Actions;
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Laporan siswa berhasil diupdate';
    }
}
