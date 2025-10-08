<?php

namespace App\Filament\Guru\Resources\LaporanSiswaResource\Pages;

use App\Filament\Guru\Resources\LaporanSiswaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLaporanSiswa extends ViewRecord
{
    protected static string $resource = LaporanSiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
