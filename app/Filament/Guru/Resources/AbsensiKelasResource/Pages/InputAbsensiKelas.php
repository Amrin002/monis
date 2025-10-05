<?php

namespace App\Filament\Guru\Resources\AbsensiKelasResource\Pages;

use App\Filament\Guru\Resources\AbsensiKelasResource;
use App\Filament\Guru\Resources\AbsensiResource;
use App\Models\Absensi;
use App\Models\Siswa;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InputAbsensiKelas extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = AbsensiKelasResource::class;

    protected static string $view = 'filament.guru.pages.guru.resources.absensi-kelas-resource.pages.input-absensi-kelas';
    use InteractsWithForms;
    protected static ?string $title = 'Input Absensi Harian Kelas';

    public ?array $data = [];
    public $siswaList = [];

    public function mount(): void
    {
        $guru = Auth::user()->guru;

        $this->form->fill([
            'tanggal' => now()->format('Y-m-d'),
        ]);

        // Auto load siswa
        if ($guru && $guru->isWaliKelas()) {
            $this->loadSiswa();
        }
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal Absensi')
                    ->default(now())
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->maxDate(now())
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state) {
                        $this->loadSiswa();
                    }),
            ])
            ->statePath('data');
    }

    public function loadSiswa(): void
    {
        $guru = Auth::user()->guru;

        if (!$guru || !$guru->isWaliKelas()) {
            $this->siswaList = [];
            return;
        }

        // Ambil kelas yang diwali
        $kelas = $guru->kelasWali;

        if (!$kelas) {
            $this->siswaList = [];
            return;
        }

        $tanggal = $this->data['tanggal'] ?? now()->format('Y-m-d');

        // Ambil semua siswa di kelas
        $this->siswaList = $kelas->siswas->map(function ($siswa) use ($tanggal) {
            $existingAbsensi = Absensi::where('siswa_id', $siswa->id)
                ->whereNull('jadwal_id') // Hanya absensi harian
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
                ->title('Tidak ada data siswa')
                ->warning()
                ->send();
            return;
        }

        try {
            DB::beginTransaction();

            $savedCount = 0;
            $updatedCount = 0;

            foreach ($this->siswaList as $siswa) {
                // Pastikan semua field ada sebelum save
                if (!isset($siswa['id']) || !isset($siswa['status'])) {
                    continue;
                }

                $absensiData = [
                    'siswa_id' => $siswa['id'],
                    'jadwal_id' => null,
                    'tanggal' => $data['tanggal'],
                    'status' => strtolower($siswa['status']), // Pastikan lowercase
                    'keterangan' => !empty($siswa['keterangan']) ? $siswa['keterangan'] : null,
                ];

                // Debug log - bisa dihapus nanti
                Log::info('Absensi Data:', $absensiData);

                $existing = Absensi::where('siswa_id', $siswa['id'])
                    ->whereNull('jadwal_id')
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

            $this->loadSiswa();
        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Gagal menyimpan absensi')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();

            // Debug log
            Log::error('Error simpan absensi:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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

    public function getKelasName(): string
    {
        $guru = Auth::user()->guru;
        return $guru && $guru->kelasWali ? $guru->kelasWali->nama : '-';
    }
}
