<?php
// app/Filament/Guru/Resources/LaporanSiswaResource.php

namespace App\Filament\Guru\Resources;

use App\Filament\Guru\Resources\LaporanSiswaResource\Pages;
use App\Models\Laporan;
use App\Models\Siswa;
use App\Models\Jadwal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class LaporanSiswaResource extends Resource
{
    protected static ?string $model = Laporan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Laporan Siswa';

    protected static ?string $modelLabel = 'Laporan Siswa';

    protected static ?string $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 2;

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

                                return Siswa::whereHas('kelas.jadwals.mapel', function ($query) use ($guru) {
                                    $query->where('guru_id', $guru->id);
                                })
                                    ->with('kelas')
                                    ->get()
                                    ->unique('id')
                                    ->mapWithKeys(function ($siswa) {
                                        $kelasNama = $siswa->kelas ? $siswa->kelas->nama : 'Tanpa Kelas';
                                        return [$siswa->id => "{$siswa->nama} - {$siswa->nis} ({$kelasNama})"];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set) {
                                $set('jadwal_id', null);
                            })
                            ->helperText('Pilih siswa yang Anda ajar'),

                        Forms\Components\Hidden::make('guru_id')
                            ->default(fn() => Auth::user()->guru->id),

                        Forms\Components\Select::make('jadwal_id')
                            ->label('Jadwal Mata Pelajaran')
                            ->options(function (callable $get) {
                                $siswaId = $get('siswa_id');
                                $guru = Auth::user()->guru;

                                if (!$siswaId) {
                                    return [];
                                }

                                $siswa = Siswa::with('kelas')->find($siswaId);

                                if (!$siswa || !$siswa->kelas) {
                                    return [];
                                }

                                $kelasId = $siswa->kelas->id;
                                $hariIni = static::getNamaHariIndonesia(now()->dayOfWeek);

                                $jadwals = Jadwal::where('kelas_id', $kelasId)
                                    ->whereHas('mapel', function ($query) use ($guru) {
                                        $query->where('guru_id', $guru->id);
                                    })
                                    ->with(['mapel', 'kelas'])
                                    ->get()
                                    ->sortBy(function ($jadwal) use ($hariIni) {
                                        return $jadwal->hari === $hariIni ? 0 : 1;
                                    });

                                return $jadwals->mapWithKeys(function ($jadwal) use ($hariIni) {
                                    $mapelNama = $jadwal->mapel ? $jadwal->mapel->nama_matapelajaran : 'N/A';
                                    $kelasNama = $jadwal->kelas ? $jadwal->kelas->nama : 'N/A';
                                    $jamKe = $jadwal->getJamKe();
                                    $marker = $jadwal->hari === $hariIni ? '🟢 ' : '';

                                    return [$jadwal->id => "{$marker}{$kelasNama} - {$mapelNama} (Jam ke-{$jamKe}, {$jadwal->hari})"];
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $get, $state) {
                                if (!$state) return;

                                $jadwal = Jadwal::find($state);
                                if (!$jadwal) return;

                                $hariIni = static::getNamaHariIndonesia(now()->dayOfWeek);

                                if ($jadwal->hari !== $hariIni) {
                                    Notification::make()
                                        ->warning()
                                        ->title('Perhatian: Jadwal Berbeda Hari')
                                        ->body("Anda memilih jadwal hari {$jadwal->hari}, sedangkan hari ini adalah {$hariIni}. Pastikan ini adalah laporan yang benar.")
                                        ->duration(8000)
                                        ->send();
                                }
                            })
                            ->helperText(fn() => 'Jadwal dengan 🟢 adalah jadwal hari ini (' . static::getNamaHariIndonesia(now()->dayOfWeek) . ')'),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Laporan')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->maxDate(now())
                            ->reactive()
                            ->afterStateUpdated(function (callable $get, $state) {
                                $siswaId = $get('siswa_id');
                                $jadwalId = $get('jadwal_id');
                                $guruId = Auth::user()->guru->id;

                                if ($siswaId && $jadwalId && $state) {
                                    $sudahAda = Laporan::sudahAdaLaporanHariIni(
                                        $guruId,
                                        $siswaId,
                                        $jadwalId,
                                        $state,
                                        $get('id')
                                    );

                                    if ($sudahAda) {
                                        Notification::make()
                                            ->danger()
                                            ->title('Laporan Sudah Ada!')
                                            ->body('Anda sudah membuat laporan untuk siswa ini pada mata pelajaran dan tanggal yang sama!')
                                            ->persistent()
                                            ->send();
                                    }
                                }
                            }),
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
                            ->helperText('Tulis laporan detail mengenai perkembangan akademik atau catatan penting siswa dalam mata pelajaran Anda'),
                    ]),
            ]);
    }

    protected static function getNamaHariIndonesia(int $dayOfWeek): string
    {
        $hari = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        return $hari[$dayOfWeek] ?? 'Unknown';
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
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jadwal.mapel.nama_matapelajaran')
                    ->label('Mata Pelajaran')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jadwal.hari')
                    ->label('Hari')
                    ->badge()
                    ->sortable()
                    ->color(fn(string $state): string => match ($state) {
                        'Senin' => 'danger',
                        'Selasa' => 'warning',
                        'Rabu' => 'success',
                        'Kamis' => 'info',
                        'Jumat' => 'primary',
                        'Sabtu' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('jadwal_info')
                    ->label('Jam Ke')
                    ->getStateUsing(function (Laporan $record) {
                        if (!$record->jadwal) {
                            return '-';
                        }
                        $jamKe = $record->jadwal->getJamKe();
                        return "Jam ke-{$jamKe}";
                    })
                    ->badge()
                    ->color('warning'),

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
                // FILTER KELAS - MENGGUNAKAN QUERY
                Tables\Filters\SelectFilter::make('kelas')
                    ->label('Filter Kelas')
                    ->options(function () {
                        $guru = Auth::user()->guru;

                        return \App\Models\Kelas::whereHas('jadwals.mapel', function ($query) use ($guru) {
                            $query->where('guru_id', $guru->id);
                        })
                            ->orderBy('nama')
                            ->pluck('nama', 'nama');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['value']) && !empty($data['value'])) {
                            return $query->whereHas('siswa.kelas', function ($q) use ($data) {
                                $q->where('nama', $data['value']);
                            });
                        }
                        return $query;
                    })
                    ->searchable(),

                // FILTER HARI - MENGGUNAKAN QUERY
                Tables\Filters\SelectFilter::make('hari')
                    ->label('Filter Hari')
                    ->options([
                        'Senin' => 'Senin',
                        'Selasa' => 'Selasa',
                        'Rabu' => 'Rabu',
                        'Kamis' => 'Kamis',
                        'Jumat' => 'Jumat',
                        'Sabtu' => 'Sabtu',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['value']) && !empty($data['value'])) {
                            return $query->whereHas('jadwal', function ($q) use ($data) {
                                $q->where('hari', $data['value']);
                            });
                        }
                        return $query;
                    }),

                // FILTER RANGE TANGGAL
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari')
                            ->label('Dari Tanggal')
                            ->native(false),
                        Forms\Components\DatePicker::make('sampai')
                            ->label('Sampai Tanggal')
                            ->native(false),
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

                // FILTER SISWA
                Tables\Filters\SelectFilter::make('siswa_id')
                    ->label('Filter Siswa')
                    ->options(function () {
                        $guru = Auth::user()->guru;

                        return Siswa::whereHas('kelas.jadwals.mapel', function ($query) use ($guru) {
                            $query->where('guru_id', $guru->id);
                        })
                            ->orderBy('nama')
                            ->pluck('nama', 'id');
                    })
                    ->searchable()
                    ->preload(),

                // FILTER MATA PELAJARAN
                Tables\Filters\SelectFilter::make('jadwal_id')
                    ->label('Filter Mata Pelajaran')
                    ->relationship('jadwal.mapel', 'nama_matapelajaran')
                    ->searchable()
                    ->preload(),
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
            ->emptyStateHeading('Belum ada laporan siswa')
            ->emptyStateDescription('Mulai buat laporan untuk siswa yang Anda ajar.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanSiswas::route('/'),
            'create' => Pages\CreateLaporanSiswa::route('/create'),
            'view' => Pages\ViewLaporanSiswa::route('/{record}'),
            'edit' => Pages\EditLaporanSiswa::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $guru = Auth::user()->guru;

        return parent::getEloquentQuery()
            ->where('guru_id', $guru->id)
            ->whereNotNull('jadwal_id')
            ->whereHas('jadwal.mapel', function ($query) use ($guru) {
                $query->where('guru_id', $guru->id);
            })
            ->with(['jadwal.kelas', 'jadwal.mapel', 'siswa.kelas']); // Eager loading
    }

    public static function canViewAny(): bool
    {
        $guru = Auth::user()->guru;
        return $guru && $guru->isGuruMapel();
    }

    public static function getNavigationBadge(): ?string
    {
        $guru = Auth::user()->guru;

        return Laporan::where('guru_id', $guru->id)
            ->whereNotNull('jadwal_id')
            ->whereHas('jadwal.mapel', function ($query) use ($guru) {
                $query->where('guru_id', $guru->id);
            })
            ->count();
    }
}
