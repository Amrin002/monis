<?php

namespace App\Filament\Guru\Resources;

use App\Filament\Guru\Resources\LaporanWalikelasResource\Pages;
use App\Filament\Guru\Resources\LaporanWalikelasResource\RelationManagers;
use App\Models\LaporanWalikelas;
use App\Models\Siswa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class LaporanWalikelasResource extends Resource
{
    protected static ?string $model = LaporanWalikelas::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Laporan Wali Kelas';

    protected static ?string $modelLabel = 'Laporan Wali Kelas';

    protected static ?string $pluralModelLabel = 'Laporan Wali Kelas';

    protected static ?string $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 3;

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

                                // Hanya siswa di kelas yang diampu sebagai wali kelas
                                if (!$guru->kelasWali) {
                                    return [];
                                }

                                return Siswa::where('kelas_id', $guru->kelasWali->id)
                                    ->orderBy('nama')
                                    ->get()
                                    ->mapWithKeys(function ($siswa) {
                                        return [$siswa->id => "{$siswa->nama} - {$siswa->nis}"];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Pilih siswa di kelas Anda'),

                        Forms\Components\Hidden::make('guru_id')
                            ->default(fn() => Auth::user()->guru->id),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Laporan')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->maxDate(now())
                            ->reactive()
                            ->afterStateUpdated(function (callable $get, $state) {
                                $siswaId = $get('siswa_id');
                                $guruId = Auth::user()->guru->id;

                                if ($siswaId && $state) {
                                    $sudahAda = LaporanWaliKelas::sudahAdaLaporan(
                                        $guruId,
                                        $siswaId,
                                        $state,
                                        $get('id')
                                    );

                                    if ($sudahAda) {
                                        Notification::make()
                                            ->danger()
                                            ->title('Laporan Sudah Ada!')
                                            ->body('Anda sudah membuat laporan untuk siswa ini pada tanggal yang sama!')
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
                            ->helperText('Tulis laporan mengenai perkembangan siswa secara keseluruhan (akademik, sikap, kehadiran, dll)'),
                    ]),

                // SECTION: Preview Laporan Guru Mapel (Optional - hanya di view/edit)
                Forms\Components\Section::make('Laporan dari Guru Mata Pelajaran')
                    ->schema([
                        Forms\Components\Placeholder::make('laporan_guru_mapel_info')
                            ->label('')
                            ->content(function ($record) {
                                if (!$record) {
                                    return 'Laporan guru mata pelajaran akan muncul di sini setelah data disimpan.';
                                }

                                $laporanGuruMapel = $record->getLaporanGuruMapel(
                                    now()->startOfMonth(),
                                    now()->endOfMonth()
                                );

                                if ($laporanGuruMapel->isEmpty()) {
                                    return '📭 Belum ada laporan dari guru mata pelajaran bulan ini.';
                                }

                                $html = '<div class="space-y-2">';
                                foreach ($laporanGuruMapel as $laporan) {
                                    $mapel = $laporan->jadwal->mapel->nama_matapelajaran ?? '-';
                                    $guru = $laporan->guru->nama ?? '-';
                                    $tanggal = $laporan->tanggal->format('d/m/Y');
                                    $keterangan = strip_tags($laporan->keterangan);
                                    $preview = substr($keterangan, 0, 100) . (strlen($keterangan) > 100 ? '...' : '');

                                    $html .= "
                                        <div class='p-3 bg-gray-50 dark:bg-gray-800 rounded-lg'>
                                            <div class='flex justify-between items-start mb-1'>
                                                <span class='font-semibold text-sm'>{$mapel}</span>
                                                <span class='text-xs text-gray-500'>{$tanggal}</span>
                                            </div>
                                            <div class='text-xs text-gray-600 dark:text-gray-400 mb-1'>Guru: {$guru}</div>
                                            <div class='text-sm text-gray-700 dark:text-gray-300'>{$preview}</div>
                                        </div>
                                    ";
                                }
                                $html .= '</div>';

                                return new \Illuminate\Support\HtmlString($html);
                            }),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn($record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('siswa.nama')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->description(fn(LaporanWaliKelas $record): string => "NIS: {$record->siswa->nis}"),

                Tables\Columns\TextColumn::make('siswa.kelas.nama')
                    ->label('Kelas')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Preview Keterangan')
                    ->html()
                    ->limit(80)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = strip_tags($column->getState());
                        if (strlen($state) <= 80) {
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

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diupdate')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                // FILTER SISWA
                Tables\Filters\SelectFilter::make('siswa_id')
                    ->label('Filter Siswa')
                    ->options(function () {
                        $guru = Auth::user()->guru;

                        if (!$guru->kelasWali) {
                            return [];
                        }

                        return Siswa::where('kelas_id', $guru->kelasWali->id)
                            ->orderBy('nama')
                            ->pluck('nama', 'id');
                    })
                    ->searchable()
                    ->preload(),

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

                // FILTER PERIODE
                Tables\Filters\SelectFilter::make('periode')
                    ->label('Filter Periode')
                    ->options([
                        'hari_ini' => 'Hari Ini',
                        'minggu_ini' => 'Minggu Ini',
                        'bulan_ini' => 'Bulan Ini',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'hari_ini' => $query->hariIni(),
                            'minggu_ini' => $query->mingguIni(),
                            'bulan_ini' => $query->bulanIni(),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

                // ACTION: Lihat Laporan Guru Mapel
                Tables\Actions\Action::make('lihat_laporan_mapel')
                    ->label('Laporan Guru Mapel')
                    ->icon('heroicon-o-academic-cap')
                    ->color('info')
                    ->modalHeading('Laporan dari Guru Mata Pelajaran')
                    ->modalWidth('5xl')
                    ->modalContent(function (LaporanWaliKelas $record) {
                        $laporanGuruMapel = $record->getLaporanGuruMapel();

                        if ($laporanGuruMapel->isEmpty()) {
                            return view('filament.components.empty-state', [
                                'message' => 'Belum ada laporan dari guru mata pelajaran untuk siswa ini.'
                            ]);
                        }

                        return view('filament.components.laporan-guru-mapel', [
                            'laporans' => $laporanGuruMapel,
                            'siswa' => $record->siswa
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada laporan wali kelas')
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
            'index' => Pages\ListLaporanWalikelas::route('/'),
            'create' => Pages\CreateLaporanWalikelas::route('/create'),
            'view' => Pages\ViewLaporanWalikelas::route('/{record}'),
            'edit' => Pages\EditLaporanWalikelas::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $guru = Auth::user()->guru;

        // Hanya tampilkan laporan untuk siswa di kelas yang diampu
        return parent::getEloquentQuery()
            ->where('guru_id', $guru->id)
            ->with(['siswa.kelas']);
    }

    public static function canViewAny(): bool
    {
        $guru = Auth::user()->guru;
        // Hanya wali kelas yang bisa akses
        return $guru && $guru->isWaliKelas() && $guru->kelasWali;
    }

    public static function getNavigationBadge(): ?string
    {
        $guru = Auth::user()->guru;

        if (!$guru || !$guru->kelasWali) {
            return null;
        }

        return LaporanWaliKelas::where('guru_id', $guru->id)
            ->whereDate('tanggal', today())
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
