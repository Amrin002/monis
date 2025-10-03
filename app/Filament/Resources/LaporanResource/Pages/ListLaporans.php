<?php

namespace App\Filament\Resources\LaporanResource\Pages;

use App\Filament\Resources\LaporanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLaporans extends ListRecords
{
    protected static string $resource = LaporanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    // Eager loading untuk menghindari N+1 query problem
    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->with(['siswa.kelas', 'waliGuru']);
    }
}
