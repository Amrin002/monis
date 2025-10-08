<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanResource\Pages;
use App\Filament\Resources\LaporanResource\RelationManagers;
use App\Models\Laporan;
use App\Models\Siswa;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Jadwal;
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
                            ->relationship('siswa', 'nama')
                            ->getOptionLabelFromRecordUsing(fn(Siswa $record) => "{$record->nama} - {$record->nis} ({$record->kelas->nama})")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set) {
                                // Reset guru dan jadwal ketika siswa berubah
                                $set('guru_id', null);
                                $set('jadwal_id', null);
                            })
                            ->helperText('Pilih siswa yang akan dilaporkan'),

                        Forms\Components\Select::make('guru_id')
                            ->label('Guru Pembuat')
                            ->options(function (callable $get) {
                                $siswaId = $get('siswa_id');

                                if (!$siswaId) {
                                    return [];
                                }

                                $siswa = Siswa::with('kelas')->find($siswaId);

                                if (!$siswa || !$siswa->kelas) {
                                    return [];
                                }

                                $kelasId = $siswa->kelas->id;
                                $guruIds = collect();

                                // 1. Ambil wali kelas dari kelas siswa
                                $waliKelas = Guru::where('id', $siswa->kelas->wali_guru_id)
                                    ->with('kelasWali')
                                    ->first();

                                if ($waliKelas) {
                                    $guruIds->push($waliKelas->id);
                                }

                                // 2. Ambil guru yang mengajar di kelas tersebut (punya jadwal)
                                $guruMapel = Guru::whereHas('mapels.jadwal', function ($query) use ($kelasId) {
                                    $query->where('kelas_id', $kelasId);
                                })->pluck('id');

                                $guruIds = $guruIds->merge($guruMapel)->unique();

                                // Ambil data guru dan format
                                return Guru::whereIn('id', $guruIds)
                                    ->with('kelasWali', 'mapels')
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
                            ->reactive()
                            ->afterStateUpdated(function (callable $set) {
                                // Reset jadwal ketika guru berubah
                                $set('jadwal_id', null);
                            })
                            ->helperText('Pilih guru pembuat laporan (Wali Kelas atau Guru Mapel yang mengajar di kelas ini)'),

                        Forms\Components\Select::make('jadwal_id')
                            ->label('Jadwal Mata Pelajaran (Opsional)')
                            ->options(function (callable $get) {
                                $siswaId = $get('siswa_id');
                                $guruId = $get('guru_id');

                                if (!$siswaId || !$guruId) {
                                    return [];
                                }

                                $siswa = Siswa::with('kelas')->find($siswaId);

                                if (!$siswa || !$siswa->kelas) {
                                    return [];
                                }

                                $kelasId = $siswa->kelas->id;
                                $guru = Guru::find($guruId);

                                // Jika guru adalah wali kelas saja (tidak mengajar mapel di kelas ini)
                                // maka tidak ada jadwal yang ditampilkan
                                if ($guru && $guru->is_wali_kelas && $guru->kelasWali && $guru->kelasWali->id == $kelasId) {
                                    // Cek apakah guru ini juga mengajar di kelas ini
                                    $hasMapelInKelas = Jadwal::where('kelas_id', $kelasId)
                                        ->whereHas('mapel', function ($query) use ($guruId) {
                                            $query->where('guru_id', $guruId);
                                        })
                                        ->exists();

                                    if (!$hasMapelInKelas) {
                                        return []; // Wali kelas murni, tidak ada jadwal
                                    }
                                }

                                // Ambil jadwal dari guru ini di kelas siswa
                                return Jadwal::where('kelas_id', $kelasId)
                                    ->whereHas('mapel', function ($query) use ($guruId) {
                                        $query->where('guru_id', $guruId);
                                    })
                                    ->with(['mapel', 'kelas'])
                                    ->get()
                                    ->mapWithKeys(function ($jadwal) {
                                        $mapelNama = $jadwal->mapel ? $jadwal->mapel->nama_matapelajaran : 'N/A';
                                        $kelasNama = $jadwal->kelas ? $jadwal->kelas->nama : 'N/A';
                                        return [$jadwal->id => "{$kelasNama} - {$mapelNama} ({$jadwal->hari}, {$jadwal->jam_mulai}-{$jadwal->jam_selesai})"];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('Pilih jika laporan terkait mata pelajaran tertentu. Kosongkan jika laporan umum dari wali kelas.'),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Laporan')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->columnSpanFull(),
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

                Tables\Columns\TextColumn::make('jadwal.mapel.nama_matapelajaran')
                    ->label('Mata Pelajaran')
                    ->badge()
                    ->color('warning')
                    ->default('Laporan Umum')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('guru.nama')
                    ->label('Pembuat Laporan')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Laporan $record): string => $record->guru->is_wali_kelas ? 'Wali Kelas' : 'Guru Mapel'),

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
                    ->relationship('siswa.kelas', 'nama')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('siswa_id')
                    ->label('Filter Siswa')
                    ->relationship('siswa', 'nama')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('guru_id')
                    ->label('Filter Pembuat')
                    ->relationship('guru', 'nama')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('jadwal_id')
                    ->label('Filter Mata Pelajaran')
                    ->relationship('jadwal.mapel', 'nama_matapelajaran')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['dari_tanggal'], fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date))
                            ->when($data['sampai_tanggal'], fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date));
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
            ->emptyStateDescription('Laporan siswa akan muncul di sini setelah guru membuat laporan.')
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
