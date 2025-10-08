<?php
// app/Filament/Guru/Resources/LaporanSiswaResource.php

namespace App\Filament\Guru\Resources;

use App\Filament\Guru\Resources\LaporanSiswaResource\Pages;
use App\Models\Laporan;
use App\Models\Siswa;
use App\Models\Jadwal;
use App\Notifications\LaporanMapelTerkirimNotification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

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

                // KOLOM BARU: STATUS PENGIRIMAN
                Tables\Columns\IconColumn::make('terkirim_ke_wali')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn(Laporan $record): string => $record->status_pengiriman),

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
                // FILTER STATUS PENGIRIMAN
                Tables\Filters\TernaryFilter::make('terkirim_ke_wali')
                    ->label('Status Pengiriman')
                    ->placeholder('Semua Status')
                    ->trueLabel('Sudah Terkirim')
                    ->falseLabel('Belum Terkirim'),

                // FILTER KELAS
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

                // FILTER HARI
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
                // ACTION: KIRIM KE WALI KELAS (PER ROW)
                Tables\Actions\Action::make('kirim_ke_wali')
                    ->label('Kirim ke Wali Kelas')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Laporan ke Wali Kelas')
                    ->modalDescription(function (Laporan $record) {
                        $siswa = $record->siswa;
                        $kelas = $siswa->kelas ?? null;
                        $waliKelas = $kelas->waliGuru ?? null;

                        if (!$kelas) {
                            return 'Siswa ini tidak memiliki kelas.';
                        }

                        if (!$waliKelas) {
                            return "Kelas {$kelas->nama} tidak memiliki wali kelas.";
                        }

                        $mapel = $record->jadwal->mapel->nama_matapelajaran ?? '-';
                        $tanggal = Carbon::parse($record->tanggal)->format('d/m/Y');

                        return "Anda akan mengirim laporan untuk:\n\n" .
                            "• Siswa: {$siswa->nama} (NIS: {$siswa->nis})\n" .
                            "• Kelas: {$kelas->nama}\n" .
                            "• Mata Pelajaran: {$mapel}\n" .
                            "• Tanggal: {$tanggal}\n\n" .
                            "Kepada Wali Kelas: {$waliKelas->nama}";
                    })
                    ->modalIcon('heroicon-o-paper-airplane')
                    ->modalIconColor('success')
                    ->modalSubmitActionLabel('Kirim Sekarang')
                    ->action(function (Laporan $record) {
                        $siswa = $record->siswa;
                        $kelas = $siswa->kelas ?? null;
                        $waliKelas = $kelas->waliGuru ?? null;

                        // ✅ DEBUGGING - TAMBAHKAN INI
                        Log::info('=== DEBUG KIRIM NOTIFIKASI ===', [
                            'laporan_id' => $record->id,
                            'siswa_nama' => $siswa->nama,
                            'kelas_nama' => $kelas ? $kelas->nama : 'NULL',
                            'wali_kelas_nama' => $waliKelas ? $waliKelas->nama : 'NULL',
                            'wali_kelas_id' => $waliKelas ? $waliKelas->id : 'NULL',
                            'wali_kelas_user_id' => $waliKelas && $waliKelas->user ? $waliKelas->user->id : 'NULL',
                            'wali_kelas_email' => $waliKelas && $waliKelas->user ? $waliKelas->user->email : 'NULL',
                        ]);

                        if (!$kelas || !$waliKelas) {
                            Log::warning('Gagal kirim: kelas atau wali kelas tidak ada');
                            Notification::make()
                                ->danger()
                                ->title('Gagal Mengirim')
                                ->body($kelas ? 'Kelas ini tidak memiliki wali kelas.' : 'Siswa tidak memiliki kelas.')
                                ->send();
                            return;
                        }

                        // IMPLEMENTASI: Tandai laporan sebagai terkirim
                        $record->tandaiTerkirim();
                        Log::info('Laporan ditandai terkirim', ['laporan_id' => $record->id]);

                        // 🔔 KIRIM NOTIFIKASI ke User Wali Kelas
                        if ($waliKelas->user) {
                            try {
                                Log::info('Mencoba kirim notifikasi...', [
                                    'target_user_id' => $waliKelas->user->id,
                                    'target_email' => $waliKelas->user->email,
                                ]);

                                $waliKelas->user->notify(new LaporanMapelTerkirimNotification($record));

                                Log::info('✅ Notifikasi BERHASIL dikirim!');

                                // Verifikasi apakah notifikasi masuk ke database
                                $lastNotification = $waliKelas->user->notifications()->latest()->first();
                                Log::info('Notifikasi terakhir di database:', [
                                    'id' => $lastNotification ? $lastNotification->id : 'NULL',
                                    'type' => $lastNotification ? $lastNotification->type : 'NULL',
                                ]);
                            } catch (\Exception $e) {
                                Log::error('❌ ERROR saat kirim notifikasi: ' . $e->getMessage());
                                Log::error('Stack trace: ' . $e->getTraceAsString());

                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('Terjadi kesalahan saat mengirim notifikasi: ' . $e->getMessage())
                                    ->send();
                                return;
                            }
                        } else {
                            Log::warning('⚠️ Wali Kelas tidak memiliki akun user!', [
                                'wali_kelas_id' => $waliKelas->id,
                                'wali_kelas_nama' => $waliKelas->nama,
                            ]);

                            Notification::make()
                                ->warning()
                                ->title('Peringatan')
                                ->body("Laporan ditandai terkirim, tetapi {$waliKelas->nama} tidak memiliki akun user untuk menerima notifikasi.")
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->success()
                            ->title('Berhasil Dikirim!')
                            ->body("Laporan berhasil dikirim ke {$waliKelas->nama} (Wali Kelas {$kelas->nama})")
                            ->send();
                    })
                    ->visible(
                        fn(Laporan $record) =>
                        !$record->terkirim_ke_wali &&
                            $record->siswa->kelas &&
                            $record->siswa->kelas->waliGuru
                    ),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // BULK ACTION: KIRIM KE WALI KELAS
                    Tables\Actions\BulkAction::make('kirim_bulk_ke_wali')
                        ->label('Kirim ke Wali Kelas')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Kirim Laporan ke Wali Kelas')
                        ->modalDescription(function (Collection $records) {
                            // Filter hanya yang belum terkirim
                            $belumTerkirim = $records->where('terkirim_ke_wali', false);

                            if ($belumTerkirim->isEmpty()) {
                                return "⚠️ Semua laporan yang Anda pilih sudah terkirim!";
                            }

                            // Kelompokkan laporan berdasarkan kelas
                            $groupedByKelas = $belumTerkirim->groupBy(function ($laporan) {
                                return $laporan->siswa->kelas->id ?? 'no_class';
                            });

                            $summary = [];
                            $totalLaporan = $belumTerkirim->count();

                            foreach ($groupedByKelas as $kelasId => $laporans) {
                                if ($kelasId === 'no_class') {
                                    $summary[] = "• " . $laporans->count() . " laporan tanpa kelas";
                                    continue;
                                }

                                $kelas = $laporans->first()->siswa->kelas;
                                $waliKelas = $kelas->waliGuru;

                                if (!$waliKelas) {
                                    $summary[] = "• {$kelas->nama}: " . $laporans->count() . " laporan (⚠️ Tidak ada wali kelas)";
                                } else {
                                    $summary[] = "• {$kelas->nama}: " . $laporans->count() . " laporan → {$waliKelas->nama}";
                                }
                            }

                            return "Anda akan mengirim total {$totalLaporan} laporan:\n\n" . implode("\n", $summary);
                        })
                        ->modalIcon('heroicon-o-paper-airplane')
                        ->modalIconColor('success')
                        ->modalSubmitActionLabel('Kirim Semua')
                        ->action(function (Collection $records) {
                            Log::info('=== BULK SEND START ===', ['total_records' => $records->count()]);

                            // Filter hanya yang belum terkirim
                            $belumTerkirim = $records->where('terkirim_ke_wali', false);

                            if ($belumTerkirim->isEmpty()) {
                                Log::info('Semua laporan sudah terkirim');
                                Notification::make()
                                    ->warning()
                                    ->title('Tidak Ada yang Dikirim')
                                    ->body('Semua laporan yang dipilih sudah terkirim sebelumnya.')
                                    ->send();
                                return;
                            }

                            // Kelompokkan laporan berdasarkan kelas
                            $groupedByKelas = $belumTerkirim->groupBy(function ($laporan) {
                                return $laporan->siswa->kelas->id ?? 'no_class';
                            });

                            $berhasil = 0;
                            $gagal = 0;
                            $details = [];

                            foreach ($groupedByKelas as $kelasId => $laporans) {
                                if ($kelasId === 'no_class') {
                                    $gagal += $laporans->count();
                                    Log::warning('Laporan tanpa kelas', ['count' => $laporans->count()]);
                                    continue;
                                }

                                $kelas = $laporans->first()->siswa->kelas;
                                $waliKelas = $kelas->waliGuru;

                                if (!$waliKelas) {
                                    $gagal += $laporans->count();
                                    Log::warning('Kelas tanpa wali', ['kelas' => $kelas->nama, 'count' => $laporans->count()]);
                                    continue;
                                }

                                Log::info('Processing kelas', [
                                    'kelas' => $kelas->nama,
                                    'wali_kelas' => $waliKelas->nama,
                                    'user_id' => $waliKelas->user ? $waliKelas->user->id : 'NULL',
                                    'laporan_count' => $laporans->count(),
                                ]);

                                // IMPLEMENTASI: Tandai semua laporan sebagai terkirim
                                foreach ($laporans as $laporan) {
                                    $laporan->tandaiTerkirim();

                                    // 🔔 KIRIM NOTIFIKASI untuk setiap laporan
                                    if ($waliKelas->user) {
                                        try {
                                            $waliKelas->user->notify(new LaporanMapelTerkirimNotification($laporan));
                                            Log::info('Notifikasi terkirim', ['laporan_id' => $laporan->id]);
                                        } catch (\Exception $e) {
                                            Log::error('Error kirim notifikasi', [
                                                'laporan_id' => $laporan->id,
                                                'error' => $e->getMessage()
                                            ]);
                                        }
                                    }
                                }

                                $berhasil += $laporans->count();
                                $details[] = "{$laporans->count()} laporan ke {$waliKelas->nama} (Kelas {$kelas->nama})";
                            }

                            Log::info('=== BULK SEND COMPLETE ===', [
                                'berhasil' => $berhasil,
                                'gagal' => $gagal,
                            ]);

                            if ($berhasil > 0) {
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil Mengirim Laporan!')
                                    ->body("{$berhasil} laporan berhasil dikirim:\n" . implode("\n", $details))
                                    ->duration(10000)
                                    ->send();
                            }

                            if ($gagal > 0) {
                                Notification::make()
                                    ->warning()
                                    ->title('Perhatian')
                                    ->body("{$gagal} laporan tidak dapat dikirim (tidak ada wali kelas atau tanpa kelas)")
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

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
            ->with(['jadwal.kelas', 'jadwal.mapel', 'siswa.kelas.waliGuru']);
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
            ->where('terkirim_ke_wali', false) // Badge: Hanya yang belum terkirim
            ->whereHas('jadwal.mapel', function ($query) use ($guru) {
                $query->where('guru_id', $guru->id);
            })
            ->count();
    }
}
