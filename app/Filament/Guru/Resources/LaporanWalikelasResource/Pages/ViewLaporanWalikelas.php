<?php
// app/Filament/Guru/Resources/LaporanWaliKelasResource/Pages/ViewLaporanWaliKelas.php

namespace App\Filament\Guru\Resources\LaporanWalikelasResource\Pages;

use App\Filament\Guru\Resources\LaporanWalikelasResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLaporanWalikelas extends ViewRecord
{
    protected static string $resource = LaporanWalikelasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
