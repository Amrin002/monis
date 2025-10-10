<?php
// app/Filament/Guru/Resources/AbsensiKelasResource.php

namespace App\Filament\Guru\Resources;

use App\Filament\Guru\Resources\AbsensiKelasResource\Pages;
use App\Models\Absensi;
use App\Models\Jadwal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AbsensiKelasResource extends Resource
{
    protected static ?string $model = Absensi::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Absensi Kelas';

    protected static ?string $modelLabel = 'Absensi Kelas';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->required()
                    ->default(now()),
            ]);
    }

    public static function table(Table $table): Table
    {
        $guru = Auth::user()->guru;

        return $table
            ->query(
                // Query untuk mendapatkan ringkasan absensi per tanggal
                Absensi::query()
                    ->select(
                        'tanggal',
                        DB::raw('MIN(id) as id'), // Ambil ID pertama untuk keperluan Filament
                        DB::raw('COUNT(DISTINCT siswa_id) as total_siswa'),
                        DB::raw('SUM(CASE WHEN status = "hadir" THEN 1 ELSE 0 END) as jumlah_hadir')
                    )
                    ->whereHas('siswa.kelas', function ($query) use ($guru) {
                        $query->where('wali_guru_id', $guru->id);
                    })
                    ->whereNull('jadwal_id') // Hanya absensi harian
                    ->groupBy('tanggal')
                    ->orderBy('tanggal', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('hari')
                    ->label('Hari')
                    ->getStateUsing(function ($record) {
                        $hari = [
                            'Sunday' => 'Minggu',
                            'Monday' => 'Senin',
                            'Tuesday' => 'Selasa',
                            'Wednesday' => 'Rabu',
                            'Thursday' => 'Kamis',
                            'Friday' => 'Jumat',
                            'Saturday' => 'Sabtu',
                        ];
                        $namaHari = \Carbon\Carbon::parse($record->tanggal)->format('l');
                        return $hari[$namaHari] ?? $namaHari;
                    }),

                Tables\Columns\TextColumn::make('kelas')
                    ->label('Kelas')
                    ->getStateUsing(function () {
                        $guru = Auth::user()->guru;
                        return $guru->kelasWali->nama ?? '-'; // Gunakan kelasWali
                    }),

                Tables\Columns\TextColumn::make('total_siswa')
                    ->label('Total Siswa')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('persentase_kehadiran')
                    ->label('% Kehadiran')
                    ->getStateUsing(function ($record) {
                        if ($record->total_siswa == 0) return '0%';
                        $persentase = round(($record->jumlah_hadir / $record->total_siswa) * 100);
                        return $persentase . '%';
                    })
                    ->badge()
                    ->color(fn($state) => match (true) {
                        intval($state) >= 80 => 'success',
                        intval($state) >= 60 => 'warning',
                        default => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn($record) => static::getUrl('detail', [
                        'tanggal' => $record->tanggal
                    ])),

                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->url(fn($record) => AbsensiKelasResource::getUrl('input', [
                        'tanggal' => $record->tanggal
                    ])),

                Tables\Actions\Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $guru = Auth::user()->guru;

                        // Hapus semua absensi pada tanggal tersebut untuk kelas ini
                        Absensi::whereHas('siswa.kelas', function ($query) use ($guru) {
                            $query->where('wali_guru_id', $guru->id);
                        })
                            ->whereNull('jadwal_id')
                            ->whereDate('tanggal', $record->tanggal)
                            ->delete();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('tanggal', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAbsensiKelas::route('/'),
            'input' => Pages\InputAbsensiKelas::route('/input'),
            'detail' => Pages\DetailAbsensiKelas::route('/detail'),
        ];
    }

    public static function canViewAny(): bool
    {
        $guru = Auth::user()->guru;
        return $guru && $guru->isWaliKelas();
    }
}
