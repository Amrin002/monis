<?php
// app/Filament/Guru/Resources/AbsensiKelasResource/Pages/DetailAbsensiKelas.php

namespace App\Filament\Guru\Resources\AbsensiKelasResource\Pages;

use App\Filament\Guru\Resources\AbsensiKelasResource;
use App\Models\Absensi;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class DetailAbsensiKelas extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = AbsensiKelasResource::class;

    protected static string $view = 'filament.guru.pages.guru.resources.absensi-kelas-resource.pages.detail-absensi-kelas';

    protected static ?string $title = 'Detail Absensi Kelas';

    public $tanggal;
    public $kelasInfo;

    public function mount(): void
    {
        $this->tanggal = request()->query('tanggal');

        $guru = Auth::user()->guru;
        $this->kelasInfo = $guru->kelasWali; // Pastikan menggunakan kelasWali
    }

    public function table(Table $table): Table
    {
        $guru = Auth::user()->guru;

        return $table
            ->query(
                Absensi::query()
                    ->whereHas('siswa.kelas', function ($query) use ($guru) {
                        $query->where('wali_guru_id', $guru->id);
                    })
                    ->whereNull('jadwal_id')
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
                ->url(AbsensiKelasResource::getUrl('index'))
                ->color('gray'),

            \Filament\Actions\Action::make('edit')
                ->label('Edit Absensi')
                ->icon('heroicon-o-pencil')
                ->url(AbsensiKelasResource::getUrl('input', [
                    'tanggal' => $this->tanggal
                ]))
                ->color('warning'),
        ];
    }
}
