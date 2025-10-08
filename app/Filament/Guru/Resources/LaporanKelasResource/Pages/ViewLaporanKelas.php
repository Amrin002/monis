<?php

namespace App\Filament\Guru\Resources\LaporanKelasResource\Pages;

use App\Filament\Guru\Resources\LaporanKelasResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLaporanKelas extends ViewRecord
{
    protected static string $resource = LaporanKelasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
