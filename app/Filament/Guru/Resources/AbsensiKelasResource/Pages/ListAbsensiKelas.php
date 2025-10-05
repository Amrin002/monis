<?php

namespace App\Filament\Guru\Resources\AbsensiKelasResource\Pages;

use App\Filament\Guru\Resources\AbsensiKelasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAbsensiKelas extends ListRecords
{
    protected static string $resource = AbsensiKelasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('input_absensi')
                ->label('Input Absensi Harian')
                ->icon('heroicon-o-calendar-days')
                ->url(fn() => AbsensiKelasResource::getUrl('input')) // ← Perbaiki ini
                ->color('success'),
        ];
    }
}
