<?php
// app/Filament/Guru/Resources/AbsensiResource.php

namespace App\Filament\Guru\Resources;

use App\Filament\Guru\Resources\AbsensiResource\Pages;
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

class AbsensiResource extends Resource
{
    protected static ?string $model = Absensi::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Absensi Siswa';

    protected static ?string $modelLabel = 'Absensi';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('jadwal_id')
                    ->label('Jadwal Pelajaran')
                    ->options(function () {
                        $guru = Auth::user()->guru;

                        return Jadwal::whereHas('mapel', function ($query) use ($guru) {
                            $query->where('guru_id', $guru->id);
                        })
                            ->with(['kelas', 'mapel'])
                            ->get()
                            ->mapWithKeys(function ($jadwal) {
                                return [
                                    $jadwal->id => $jadwal->kelas->nama . ' - ' .
                                        $jadwal->mapel->nama_matapelajaran . ' (' .
                                        $jadwal->hari . ', ' .
                                        $jadwal->jam_mulai . '-' . $jadwal->jam_selesai . ')'
                                ];
                            });
                    })
                    ->required()
                    ->reactive()
                    ->searchable(),

                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->default(now())
                    ->required()
                    ->maxDate(now()),
            ]);
    }

    public static function table(Table $table): Table
    {
        $guru = Auth::user()->guru;

        return $table
            ->query(
                // Query untuk mendapatkan ringkasan absensi per jadwal per tanggal
                Absensi::query()
                    ->select(
                        'jadwal_id',
                        'tanggal',
                        DB::raw('MIN(id) as id'), // Ambil ID pertama untuk keperluan Filament
                        DB::raw('COUNT(DISTINCT siswa_id) as total_siswa'),
                        DB::raw('SUM(CASE WHEN status = "hadir" THEN 1 ELSE 0 END) as jumlah_hadir')
                    )
                    ->whereHas('jadwal.mapel', function ($query) use ($guru) {
                        $query->where('guru_id', $guru->id);
                    })
                    ->groupBy('jadwal_id', 'tanggal')
                    ->orderBy('tanggal', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('jadwal.kelas.nama')
                    ->label('Kelas')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('jadwal.mapel.nama_matapelajaran')
                    ->label('Mata Pelajaran')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('jadwal.hari')
                    ->label('Hari')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jam_ke')
                    ->label('Jam Ke-')
                    ->getStateUsing(function ($record) {
                        return $record->jadwal->jam_ke ?? '-';
                    }),

                Tables\Columns\TextColumn::make('persentase_kehadiran')
                    ->label('% Kehadiran')
                    ->getStateUsing(function ($record) {
                        $absensi = Absensi::where('jadwal_id', $record->jadwal_id)
                            ->whereDate('tanggal', $record->tanggal)
                            ->get();

                        $total = $absensi->count();
                        if ($total == 0) return '0%';

                        $hadir = $absensi->where('status', 'hadir')->count();
                        $persentase = round(($hadir / $total) * 100);

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
                Tables\Filters\SelectFilter::make('jadwal_id')
                    ->label('Kelas')
                    ->options(function () {
                        $guru = Auth::user()->guru;

                        return Jadwal::whereHas('mapel', function ($query) use ($guru) {
                            $query->where('guru_id', $guru->id);
                        })
                            ->with(['kelas', 'mapel'])
                            ->get()
                            ->mapWithKeys(function ($jadwal) {
                                return [
                                    $jadwal->id => $jadwal->kelas->nama . ' - ' .
                                        $jadwal->mapel->nama_matapelajaran
                                ];
                            });
                    })
                    ->searchable(),

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
                        'jadwal' => $record->jadwal_id,
                        'tanggal' => $record->tanggal
                    ])),

                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->url(fn($record) => AbsensiResource::getUrl('input', [
                        'jadwal_id' => $record->jadwal_id,
                        'tanggal' => $record->tanggal
                    ])),

                Tables\Actions\Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // Hapus semua absensi pada jadwal dan tanggal tersebut
                        Absensi::where('jadwal_id', $record->jadwal_id)
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
            'index' => Pages\ListAbsensis::route('/'),
            'input' => Pages\InputAbsensi::route('/input'),
            'detail' => Pages\DetailAbsensi::route('/detail'),
        ];
    }

    public static function canViewAny(): bool
    {
        $guru = Auth::user()->guru;
        return $guru && $guru->isGuruMapel();
    }
}
