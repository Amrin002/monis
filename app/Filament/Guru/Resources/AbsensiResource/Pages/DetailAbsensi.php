<?php

namespace App\Filament\Guru\Resources\AbsensiResource\Pages;

use App\Filament\Guru\Resources\AbsensiResource;
use App\Models\Absensi;
use App\Models\Jadwal;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DetailAbsensi extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = AbsensiResource::class;

    protected static string $view = 'filament.guru.resources.absensi-resource.pages.detail-absensi';

    protected static ?string $title = 'Detail Absensi';

    public $jadwalId;
    public $tanggal;
    public $jadwalInfo;

    public function mount(): void
    {
        $this->jadwalId = request()->query('jadwal');
        $this->tanggal = request()->query('tanggal');

        $this->jadwalInfo = Jadwal::with(['kelas', 'mapel.guru'])
            ->find($this->jadwalId);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Absensi::query()
                    ->where('jadwal_id', $this->jadwalId)
                    ->whereDate('tanggal', $this->tanggal)
                    ->with('siswa')
                    ->join('siswas', 'absensis.siswa_id', '=', 'siswas.id')
                    ->select('absensis.*', 'siswas.nama as siswa_nama', 'siswas.nis as siswa_nis')
                    ->orderBy('siswas.nama', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('siswa_nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('siswa_nama')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),

                // ==========================================
                // BAGIAN YANG DIUBAH (START)
                // ==========================================
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'hadir',
                        'warning' => ['izin', 'sakit'], // Izin dan Sakit menjadi kuning
                        'danger' => 'alpa', // Alpa menjadi merah
                    ])
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->sortable(),
                // ==========================================
                // BAGIAN YANG DIUBAH (END)
                // ==========================================

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(50)
                    ->placeholder('-')
                    ->wrap(),
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
            ])
            ->actions([])
            ->bulkActions([])
            ->paginated(false);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->url(\App\Filament\Guru\Resources\AbsensiResource::getUrl('index'))
                ->color('gray'),

            \Filament\Actions\Action::make('edit')
                ->label('Edit Absensi')
                ->icon('heroicon-o-pencil')
                ->url(\App\Filament\Guru\Resources\AbsensiResource::getUrl('input', [
                    'jadwal_id' => $this->jadwalId,
                    'tanggal' => $this->tanggal
                ]))
                ->color('warning'),
        ];
    }
}
