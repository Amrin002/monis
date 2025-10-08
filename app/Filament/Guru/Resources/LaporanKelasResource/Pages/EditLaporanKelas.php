<?php

namespace App\Filament\Guru\Resources\LaporanKelasResource\Pages;

use App\Filament\Guru\Resources\LaporanKelasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLaporanKelas extends EditRecord
{
    protected static string $resource = LaporanKelasResource::class;

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
        return 'Laporan kelas berhasil diupdate';
    }
}
