<?php

namespace App\Filament\Guru\Resources\LaporanKelasResource\Pages;

use App\Filament\Guru\Resources\LaporanKelasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLaporanKelas extends ListRecords
{
    protected static string $resource = LaporanKelasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->with(['siswa.kelas', 'guru']);
    }
}
