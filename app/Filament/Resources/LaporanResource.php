<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanResource\Pages;
use App\Filament\Resources\LaporanResource\RelationManagers;
use App\Models\Laporan;
use App\Models\Siswa;
use App\Models\Guru;
use App\Models\Kelas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LaporanResource extends Resource
{
    protected static ?string $model = Laporan::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Monitoring';
    protected static ?string $navigationLabel = 'Laporan Siswa';
    protected static ?string $pluralModelLabel = 'Laporan Siswa';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Laporan')
                    ->schema([
                        Forms\Components\Select::make('siswa_id')
                            ->label('Siswa')
                            ->options(function () {
                                return Siswa::with('kelas')
                                    ->get()
                                    ->mapWithKeys(function ($siswa) {
                                        $kelasNama = $siswa->kelas ? $siswa->kelas->nama : 'Tanpa Kelas';
                                        return [$siswa->id => "{$siswa->nama} - {$siswa->nis} ({$kelasNama})"];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Pilih siswa yang akan dilaporkan'),

                        Forms\Components\Select::make('wali_guru_id')
                            ->label('Guru Pembuat')
                            ->options(function () {
                                return Guru::with('kelasWali', 'mapels')
                                    ->get()
                                    ->mapWithKeys(function ($guru) {
                                        $roles = [];
                                        if ($guru->is_wali_kelas && $guru->kelasWali) {
                                            $roles[] = "Wali Kelas {$guru->kelasWali->nama}";
                                        }
                                        if ($guru->is_guru_mapel && $guru->mapels->isNotEmpty()) {
                                            $mapelNames = $guru->mapels->pluck('nama_matapelajaran')->take(2)->implode(', ');
                                            $roles[] = "Guru Mapel: {$mapelNames}";
                                        }

                                        $roleText = !empty($roles) ? ' (' . implode(' | ', $roles) . ')' : '';
                                        return [$guru->id => "{$guru->nama}{$roleText}"];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Pilih guru pembuat laporan (Wali Kelas atau Guru Mapel)'),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Laporan')
                            ->required()
                            ->default(now())
                            ->native(false),
                    ])
                    ->columns(3),

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
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('siswa.nama')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Laporan $record): string => "NIS: {$record->siswa->nis}"),

                Tables\Columns\TextColumn::make('siswa.kelas.nama')
                    ->label('Kelas')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->default('-'),

                Tables\Columns\TextColumn::make('waliGuru.nama')
                    ->label('Pembuat Laporan')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Laporan $record): string => $record->waliGuru->is_wali_kelas ? 'Wali Kelas' : 'Guru'),

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
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diupdate')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('kelas')
                    ->label('Filter Kelas')
                    ->options(Kelas::all()->pluck('nama', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['value']) && $data['value']) {
                            return $query->whereHas('siswa', function ($q) use ($data) {
                                $q->where('kelas_id', $data['value']);
                            });
                        }
                        return $query;
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('siswa')
                    ->label('Filter Siswa')
                    ->options(Siswa::all()->pluck('nama', 'id'))
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['value']) && $data['value']) {
                            return $query->where('siswa_id', $data['value']);
                        }
                        return $query;
                    }),

                Tables\Filters\SelectFilter::make('wali_guru')
                    ->label('Filter Pembuat')
                    ->options(Guru::all()->pluck('nama', 'id'))
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['value']) && $data['value']) {
                            return $query->where('wali_guru_id', $data['value']);
                        }
                        return $query;
                    }),

                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['dari_tanggal'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Dari: ' . \Carbon\Carbon::parse($data['dari_tanggal'])->format('d M Y'))
                                ->removeField('dari_tanggal');
                        }
                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Sampai: ' . \Carbon\Carbon::parse($data['sampai_tanggal'])->format('d M Y'))
                                ->removeField('sampai_tanggal');
                        }
                        return $indicators;
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
            ->emptyStateHeading('Belum ada laporan')
            ->emptyStateDescription('Laporan siswa akan muncul di sini setelah guru wali kelas membuat laporan.')
            ->emptyStateIcon('heroicon-o-document-text');
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
            'index' => Pages\ListLaporans::route('/'),
            'create' => Pages\CreateLaporan::route('/create'),
            'edit' => Pages\EditLaporan::route('/{record}/edit'),

        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
