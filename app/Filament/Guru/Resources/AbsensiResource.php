<?php
// app/Filament/Guru/Resources/AbsensiResource.php

namespace App\Filament\Guru\Resources;

use App\Filament\Guru\Resources\AbsensiResource\Pages;
use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Siswa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;

use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

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

                Tables\Columns\TextColumn::make('siswa.nama')
                    ->label('Siswa')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'hadir',
                        'warning' => 'izin',
                        'danger' => 'sakit',
                        'secondary' => 'alpa',
                    ])
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(30)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jadwal_id')
                    ->label('Jadwal')
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

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'hadir' => 'Hadir',
                        'izin' => 'Izin',
                        'sakit' => 'Sakit',
                        'alpa' => 'Alpa',
                    ]),

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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
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
            'create' => Pages\CreateAbsensi::route('/create'),
            'input' => Pages\InputAbsensi::route('/input'),
            'edit' => Pages\EditAbsensi::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $guru = Auth::user()->guru;

        // Hanya tampilkan absensi dari jadwal yang diajar oleh guru ini
        return parent::getEloquentQuery()
            ->whereHas('jadwal.mapel', function ($query) use ($guru) {
                $query->where('guru_id', $guru->id);
            });
    }

    public static function canViewAny(): bool
    {
        $guru = Auth::user()->guru;
        return $guru && $guru->isGuruMapel();
    }
}
