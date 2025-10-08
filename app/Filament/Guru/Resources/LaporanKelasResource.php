<?php
// app/Filament/Guru/Resources/LaporanKelasResource.php

namespace App\Filament\Guru\Resources;

use App\Filament\Guru\Resources\LaporanKelasResource\Pages;
use App\Models\Laporan;
use App\Models\Siswa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LaporanKelasResource extends Resource
{
    protected static ?string $model = Laporan::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Laporan Kelas';

    protected static ?string $modelLabel = 'Laporan Kelas';

    protected static ?string $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Laporan')
                    ->schema([
                        Forms\Components\Select::make('siswa_id')
                            ->label('Siswa')
                            ->options(function () {
                                $guru = Auth::user()->guru;

                                // Hanya siswa dari kelas yang diwali oleh guru ini
                                return Siswa::whereHas('kelas', function ($query) use ($guru) {
                                    $query->where('wali_guru_id', $guru->id);
                                })
                                    ->with('kelas')
                                    ->get()
                                    ->mapWithKeys(function ($siswa) {
                                        $kelasNama = $siswa->kelas ? $siswa->kelas->nama : 'Tanpa Kelas';
                                        return [$siswa->id => "{$siswa->nama} - {$siswa->nis} ({$kelasNama})"];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Pilih siswa dari kelas yang Anda wali'),

                        Forms\Components\Hidden::make('guru_id')
                            ->default(fn() => Auth::user()->guru->id),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Laporan')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->maxDate(now()),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Isi Laporan')
                    ->schema([
                        Forms\Components\RichEditor::make('keterangan')
                            ->label('Keterangan / Catatan')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                                'undo',
                                'redo',
                            ])
                            ->helperText('Tulis laporan detail mengenai perkembangan, perilaku, atau catatan penting siswa'),
                    ]),
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
                    ->searchable()
                    ->description(fn(Laporan $record): string => "NIS: {$record->siswa->nis}"),

                Tables\Columns\TextColumn::make('siswa.kelas.nama')
                    ->label('Kelas')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Preview Keterangan')
                    ->html()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = strip_tags($column->getState());
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('siswa')
                    ->label('Filter Siswa')
                    ->options(function () {
                        $guru = Auth::user()->guru;

                        return Siswa::whereHas('kelas', function ($query) use ($guru) {
                            $query->where('wali_guru_id', $guru->id);
                        })
                            ->pluck('nama', 'id');
                    })
                    ->searchable()
                    ->preload(),

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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada laporan kelas')
            ->emptyStateDescription('Mulai buat laporan untuk siswa di kelas Anda.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanKelas::route('/'),
            'create' => Pages\CreateLaporanKelas::route('/create'),
            'view' => Pages\ViewLaporanKelas::route('/{record}'),
            'edit' => Pages\EditLaporanKelas::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $guru = Auth::user()->guru;

        // Hanya tampilkan laporan dari siswa di kelas yang diwali oleh guru ini
        return parent::getEloquentQuery()
            ->where('guru_id', $guru->id)
            ->whereHas('siswa.kelas', function ($query) use ($guru) {
                $query->where('wali_guru_id', $guru->id);
            });
    }

    public static function canViewAny(): bool
    {
        $guru = Auth::user()->guru;
        return $guru && $guru->isWaliKelas();
    }

    public static function getNavigationBadge(): ?string
    {
        $guru = Auth::user()->guru;

        return Laporan::where('guru_id', $guru->id)
            ->whereHas('siswa.kelas', function ($query) use ($guru) {
                $query->where('wali_guru_id', $guru->id);
            })
            ->count();
    }
}
