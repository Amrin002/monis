<?php
// app/Filament/Guru/Resources/AbsensiKelasResource.php

namespace App\Filament\Guru\Resources;

use App\Filament\Guru\Resources\AbsensiKelasResource\Pages;
use App\Filament\Guru\Resources\AbsensiKelasResource\Pages\InputAbsensiKelas;
use App\Models\Absensi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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
                Forms\Components\Select::make('siswa_id')
                    ->label('Siswa')
                    ->relationship('siswa', 'nama')
                    ->required()
                    ->disabled(),

                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->required()
                    ->disabled(),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'hadir' => 'Hadir',
                        'izin' => 'Izin',
                        'sakit' => 'Sakit',
                        'alpa' => 'Alpa',
                    ])
                    ->required(),

                Forms\Components\Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->nullable()
                    ->rows(3),
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

                Tables\Columns\TextColumn::make('siswa.nama')
                    ->label('Nama Siswa')
                    ->searchable(),

                Tables\Columns\TextColumn::make('siswa.nis')
                    ->label('NIS')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'hadir',
                        'primary' => 'izin',
                        'warning' => 'sakit',
                        'danger' => 'alpa',
                    ])
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(30)
                    ->toggleable(),
            ])
            ->filters([
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
            'index' => Pages\ListAbsensiKelas::route('/'),
            'input' => Pages\InputAbsensiKelas::route('/input'),
            'edit' => Pages\EditAbsensiKelas::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $guru = Auth::user()->guru;

        // Hanya tampilkan absensi dari kelas yang diwali oleh guru ini
        // Dan hanya absensi yang tidak terkait dengan jadwal (absensi harian kelas)
        return parent::getEloquentQuery()
            ->whereHas('siswa.kelas', function ($query) use ($guru) {
                $query->where('wali_guru_id', $guru->id);
            })
            ->whereNull('jadwal_id'); // Hanya absensi harian, bukan per mata pelajaran
    }

    public static function canViewAny(): bool
    {
        $guru = Auth::user()->guru;
        return $guru && $guru->isWaliKelas();
    }
}
