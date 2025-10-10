<?php
// app/Filament/Guru/Resources/AbsensiResource/Pages/InputAbsensi.php

namespace App\Filament\Guru\Resources\AbsensiResource\Pages;

use App\Filament\Resources\AbsensiResource;
use App\Models\Absensi;
use App\Models\Jadwal;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InputAbsensi extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = AbsensiResource::class;

    protected static string $view = 'filament.guru.resources.absensi-resource.pages.input-absensi';

    protected static ?string $title = 'Input Absensi Siswa';

    public ?array $data = [];
    public $siswaList = [];

    public function mount(): void
    {
        // Cek apakah ada parameter dari URL (untuk edit)
        $jadwalId = request()->query('jadwal_id');
        $tanggal = request()->query('tanggal');

        $this->form->fill([
            'jadwal_id' => $jadwalId,
            'tanggal' => $tanggal ?? now()->format('Y-m-d'),
        ]);

        // Load siswa jika ada parameter
        if ($jadwalId) {
            $this->loadSiswa($jadwalId);
        }
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('jadwal_id')
                    ->label('Pilih Jadwal Pelajaran')
                    ->options(function () {
                        $guru = Auth::user()->guru;

                        if (!$guru) {
                            return [];
                        }

                        return Jadwal::whereHas('mapel', function ($query) use ($guru) {
                            $query->where('guru_id', $guru->id);
                        })
                            ->with(['kelas', 'mapel'])
                            ->get()
                            ->mapWithKeys(function ($jadwal) {
                                return [
                                    $jadwal->id => $jadwal->kelas->nama . ' - ' .
                                        $jadwal->mapel->nama_matapelajaran . ' (' .
                                        $jadwal->hari . ', ' .
                                        $jadwal->jam_mulai . '-' . $jadwal->jam_selesai . ')'
                                ];
                            });
                    })
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state) {
                        $this->loadSiswa($state);
                    })
                    ->searchable()
                    ->placeholder('-- Pilih Jadwal --'),

                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal Absensi')
                    ->default(now())
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->maxDate(now())
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state) {
                        if (!empty($this->data['jadwal_id'])) {
                            $this->loadSiswa($this->data['jadwal_id']);
                        }
                    }),
            ])
            ->statePath('data')
            ->columns(2);
    }

    public function loadSiswa($jadwalId): void
    {
        if (!$jadwalId) {
            $this->siswaList = [];
            return;
        }

        $jadwal = Jadwal::with('kelas.siswas')->find($jadwalId);

        if (!$jadwal) {
            $this->siswaList = [];
            return;
        }

        $tanggal = $this->data['tanggal'] ?? now()->format('Y-m-d');

        $this->siswaList = $jadwal->kelas->siswas->map(function ($siswa) use ($jadwalId, $tanggal) {
            $existingAbsensi = Absensi::where('siswa_id', $siswa->id)
                ->where('jadwal_id', $jadwalId)
                ->whereDate('tanggal', $tanggal)
                ->first();

            return [
                'id' => $siswa->id,
                'nama' => $siswa->nama,
                'nis' => $siswa->nis,
                'status' => $existingAbsensi->status ?? 'hadir',
                'keterangan' => $existingAbsensi->keterangan ?? '',
                'exists' => $existingAbsensi ? true : false,
            ];
        })->toArray();
    }

    public function simpanAbsensi(): void
    {
        $data = $this->form->getState();

        if (empty($this->siswaList)) {
            Notification::make()
                ->title('Pilih jadwal terlebih dahulu')
                ->warning()
                ->send();
            return;
        }

        try {
            DB::beginTransaction();

            $savedCount = 0;
            $updatedCount = 0;

            foreach ($this->siswaList as $siswa) {
                $absensiData = [
                    'siswa_id' => $siswa['id'],
                    'jadwal_id' => $data['jadwal_id'],
                    'tanggal' => $data['tanggal'],
                    'status' => $siswa['status'],
                    'keterangan' => $siswa['keterangan'] ?? null,
                ];

                $existing = Absensi::where('siswa_id', $siswa['id'])
                    ->where('jadwal_id', $data['jadwal_id'])
                    ->whereDate('tanggal', $data['tanggal'])
                    ->first();

                if ($existing) {
                    $existing->update($absensiData);
                    $updatedCount++;
                } else {
                    Absensi::create($absensiData);
                    $savedCount++;
                }
            }

            DB::commit();

            $message = [];
            if ($savedCount > 0) {
                $message[] = "{$savedCount} absensi baru disimpan";
            }
            if ($updatedCount > 0) {
                $message[] = "{$updatedCount} absensi diperbarui";
            }

            Notification::make()
                ->title('Absensi berhasil disimpan')
                ->body(implode(', ', $message))
                ->success()
                ->send();

            // Redirect ke halaman list setelah simpan
            $this->redirect(AbsensiResource::getUrl('index'));
        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Gagal menyimpan absensi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function setSemuaHadir(): void
    {
        foreach ($this->siswaList as $key => $siswa) {
            $this->siswaList[$key]['status'] = 'hadir';
        }

        Notification::make()
            ->title('Semua siswa ditandai hadir')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->url(AbsensiResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
