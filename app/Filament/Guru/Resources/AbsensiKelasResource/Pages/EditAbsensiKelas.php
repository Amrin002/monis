<?php

namespace App\Filament\Guru\Resources\AbsensiKelasResource\Pages;

use App\Filament\Guru\Resources\AbsensiKelasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAbsensiKelas extends EditRecord
{
    protected static string $resource = AbsensiKelasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
