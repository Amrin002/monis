<?php
// app/Filament/Resources/JadwalResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\JadwalResource\Pages;
use App\Filament\Resources\JadwalResource\RelationManagers;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mapel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;

class JadwalResource extends Resource
{
    protected static ?string $model = Jadwal::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Manajemen Akademik';
    protected static ?string $navigationLabel = 'Jadwal Pelajaran';
    protected static ?string $pluralModelLabel = 'Jadwal Pelajaran';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Jadwal')
                    ->schema([
                        Forms\Components\Select::make('kelas_id')
                            ->label('Kelas')
                            ->options(Kelas::all()->pluck('nama', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->helperText('Pilih kelas untuk jadwal ini'),

                        Forms\Components\Select::make('mapel_id')
                            ->label('Mata Pelajaran')
                            ->options(Mapel::with('guru')->get()->mapWithKeys(function ($mapel) {
                                $guruNama = $mapel->guru ? $mapel->guru->nama : 'Tanpa Guru';
                                return [$mapel->id => "{$mapel->nama_matapelajaran} - Guru: {$guruNama}"];
                            }))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->helperText('Pilih mata pelajaran (otomatis memilih guru)'),

                        Forms\Components\Select::make('hari')
                            ->label('Hari')
                            ->options([
                                'Senin' => 'Senin',
                                'Selasa' => 'Selasa',
                                'Rabu' => 'Rabu',
                                'Kamis' => 'Kamis',
                                'Jumat' => 'Jumat',
                                'Sabtu' => 'Sabtu',
                            ])
                            ->required()
                            ->reactive()
                            ->native(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Waktu Pelajaran')
                    ->schema([
                        Forms\Components\TimePicker::make('jam_mulai')
                            ->label('Jam Mulai')
                            ->required()
                            ->seconds(false)
                            ->native(false)
                            ->reactive()
                            ->afterStateUpdated(function (callable $get, $state) {
                                static::validateJadwalBentrok($get, $state);
                            }),

                        Forms\Components\TimePicker::make('jam_selesai')
                            ->label('Jam Selesai')
                            ->required()
                            ->seconds(false)
                            ->native(false)
                            ->after('jam_mulai')
                            ->reactive()
                            ->afterStateUpdated(function (callable $get, $state) {
                                static::validateJadwalBentrok($get, $state);
                            }),
                    ])
                    ->columns(2)
                    ->description('⚠️ Sistem akan cek otomatis: (1) Bentrok kelas yang sama, (2) Bentrok guru yang sama'),
            ]);
    }

    /**
     * Validasi bentrok jadwal secara real-time
     */
    protected static function validateJadwalBentrok(callable $get, $state): void
    {
        $kelasId = $get('kelas_id');
        $mapelId = $get('mapel_id');
        $hari = $get('hari');
        $jamMulai = $get('jam_mulai');
        $jamSelesai = $get('jam_selesai');
        $jadwalId = $get('id'); // Untuk mode edit

        // Hanya validasi jika semua field terisi
        if (!$kelasId || !$mapelId || !$hari || !$jamMulai || !$jamSelesai) {
            return;
        }

        $hasil = Jadwal::cekBentrokJadwal($kelasId, $mapelId, $hari, $jamMulai, $jamSelesai, $jadwalId);

        if ($hasil['bentrok']) {
            $icon = $hasil['tipe'] === 'guru' ? '👨‍🏫' : '🏫';

            Notification::make()
                ->danger()
                ->title("{$icon} Jadwal Bentrok!")
                ->body($hasil['pesan'])
                ->persistent()
                ->send();
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kelas.nama')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('mapel.nama_matapelajaran')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->description(fn(Jadwal $record) => $record->mapel->guru ? "Guru: {$record->mapel->guru->nama}" : ''),

                Tables\Columns\TextColumn::make('hari')
                    ->label('Hari')
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Senin' => 'danger',
                        'Selasa' => 'warning',
                        'Rabu' => 'success',
                        'Kamis' => 'info',
                        'Jumat' => 'primary',
                        'Sabtu' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('jam_mulai')
                    ->label('Jam Mulai')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jam_selesai')
                    ->label('Jam Selesai')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('durasi')
                    ->label('Durasi')
                    ->getStateUsing(function (Jadwal $record) {
                        $mulai = \Carbon\Carbon::parse($record->jam_mulai);
                        $selesai = \Carbon\Carbon::parse($record->jam_selesai);
                        $diff = $mulai->diffInMinutes($selesai);
                        return $diff . ' menit';
                    })
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('jam_ke')
                    ->label('Jam Ke')
                    ->getStateUsing(fn(Jadwal $record) => "Jam ke-{$record->jam_ke}")
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('hari', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('kelas')
                    ->relationship('kelas', 'nama')
                    ->searchable()
                    ->preload()
                    ->label('Filter Kelas'),

                Tables\Filters\SelectFilter::make('mapel')
                    ->relationship('mapel', 'nama_matapelajaran')
                    ->searchable()
                    ->preload()
                    ->label('Filter Mata Pelajaran'),

                Tables\Filters\SelectFilter::make('hari')
                    ->options([
                        'Senin' => 'Senin',
                        'Selasa' => 'Selasa',
                        'Rabu' => 'Rabu',
                        'Kamis' => 'Kamis',
                        'Jumat' => 'Jumat',
                        'Sabtu' => 'Sabtu',
                    ])
                    ->label('Filter Hari'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJadwals::route('/'),
            'create' => Pages\CreateJadwal::route('/create'),
            'edit' => Pages\EditJadwal::route('/{record}/edit'),
        ];
    }
}
