<?php

namespace App\Filament\Widgets;

use App\Models\Laporan;
use App\Models\Pengumuman;
use App\Models\Siswa;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestActivitiesWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Aktivitas Terbaru')
            ->query(
                Laporan::query()
                    ->with(['siswa', 'waliGuru'])
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('siswa.nama')
                    ->label('Siswa')
                    ->description(fn(Laporan $record): string => $record->siswa->kelas ? "Kelas: {$record->siswa->kelas->nama}" : '-'),

                Tables\Columns\TextColumn::make('waliGuru.nama')
                    ->label('Pembuat')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->html()
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn(Laporan $record): string => route('filament.admin.resources.laporans.edit', ['record' => $record]))
                    ->openUrlInNewTab(),
            ]);
    }
}
