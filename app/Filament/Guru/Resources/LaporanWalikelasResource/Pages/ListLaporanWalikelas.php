<?php

namespace App\Filament\Guru\Resources\LaporanWalikelasResource\Pages;

use App\Filament\Guru\Resources\LaporanWalikelasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLaporanWalikelas extends ListRecords
{
    protected static string $resource = LaporanWalikelasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanWaliKelasResource\Widgets\LaporanWalikelasStatsWidget::class,
        ];
    }
}
