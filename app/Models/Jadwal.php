<?php
// app/Models/Jadwal.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Jadwal extends Model
{
    use HasFactory;

    protected $fillable = [
        'kelas_id',
        'mapel_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
    ];

    // Relasi ke Kelas
    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    // Relasi ke Mapel
    public function mapel()
    {
        return $this->belongsTo(Mapel::class);
    }

    // Relasi ke Absensi (jadwal punya banyak absensi)
    public function absensis()
    {
        return $this->hasMany(Absensi::class, 'jadwal_id');
    }

    public function laporans()
    {
        return $this->hasMany(Laporan::class);
    }

    /**
     * Hitung jam pelajaran ke berapa berdasarkan urutan jadwal di hari yang sama
     * untuk kelas yang sama
     */
    public function getJamKe(): int
    {
        $jadwalsHariIni = static::where('kelas_id', $this->kelas_id)
            ->where('hari', $this->hari)
            ->orderBy('jam_mulai', 'asc')
            ->get();

        $jamKe = 1;
        foreach ($jadwalsHariIni as $index => $jadwal) {
            if ($jadwal->id === $this->id) {
                $jamKe = $index + 1;
                break;
            }
        }

        return $jamKe;
    }

    /**
     * Accessor untuk mendapatkan jam_ke sebagai attribute
     */
    public function getJamKeAttribute(): int
    {
        return $this->getJamKe();
    }

    /**
     * Cek apakah jadwal bentrok dengan jadwal lain
     *
     * Cek 2 kondisi:
     * 1. Bentrok di kelas yang sama
     * 2. Bentrok guru (mapel) yang sama di kelas berbeda
     *
     * @param int $kelasId
     * @param int $mapelId
     * @param string $hari
     * @param string $jamMulai
     * @param string $jamSelesai
     * @param int|null $ignoreJadwalId - ID jadwal yang diabaikan (untuk edit)
     * @return array ['bentrok' => bool, 'jadwal' => Jadwal|null, 'pesan' => string|null, 'tipe' => string|null]
     */
    public static function cekBentrokJadwal($kelasId, $mapelId, $hari, $jamMulai, $jamSelesai, $ignoreJadwalId = null): array
    {
        $jamMulaiBaru = Carbon::createFromTimeString($jamMulai);
        $jamSelesaiBaru = Carbon::createFromTimeString($jamSelesai);

        // Validasi: jam selesai harus lebih besar dari jam mulai
        if ($jamSelesaiBaru->lte($jamMulaiBaru)) {
            return [
                'bentrok' => true,
                'jadwal' => null,
                'pesan' => 'Jam selesai harus lebih besar dari jam mulai!',
                'tipe' => 'waktu'
            ];
        }

        // Ambil mapel untuk mendapatkan guru_id
        $mapel = \App\Models\Mapel::with('guru')->find($mapelId);
        if (!$mapel || !$mapel->guru) {
            return [
                'bentrok' => false,
                'jadwal' => null,
                'pesan' => null,
                'tipe' => null
            ];
        }

        $guruId = $mapel->guru->id;

        // Query untuk cek bentrok
        $query = static::where('hari', $hari);

        // Ignore jadwal saat edit
        if ($ignoreJadwalId) {
            $query->where('id', '!=', $ignoreJadwalId);
        }

        $jadwalsExisting = $query->with(['mapel.guru', 'kelas'])->get();

        // Cek setiap jadwal existing
        foreach ($jadwalsExisting as $jadwal) {
            $jamMulaiExisting = Carbon::createFromTimeString($jadwal->jam_mulai);
            $jamSelesaiExisting = Carbon::createFromTimeString($jadwal->jam_selesai);

            // Cek apakah ada bentrok waktu
            $bentrokWaktu = static::isBentrokWaktu(
                $jamMulaiBaru,
                $jamSelesaiBaru,
                $jamMulaiExisting,
                $jamSelesaiExisting
            );

            if (!$bentrokWaktu) {
                continue; // Tidak bentrok waktu, skip
            }

            // Ada bentrok waktu, sekarang cek 2 kondisi:

            // KONDISI 1: Bentrok di kelas yang sama
            if ($jadwal->kelas_id == $kelasId) {
                $mapelNama = $jadwal->mapel->nama_matapelajaran ?? 'Unknown';
                $kelasNama = $jadwal->kelas->nama ?? 'Unknown';

                return [
                    'bentrok' => true,
                    'jadwal' => $jadwal,
                    'pesan' => "Bentrok dengan {$mapelNama} di kelas {$kelasNama} pada jam yang sama ({$jadwal->jam_mulai} - {$jadwal->jam_selesai}). Kelas tidak bisa memiliki 2 mata pelajaran di waktu bersamaan!",
                    'tipe' => 'kelas'
                ];
            }

            // KONDISI 2: Bentrok guru yang sama (mapel diampu guru yang sama)
            if ($jadwal->mapel->guru_id == $guruId) {
                $mapelNama = $jadwal->mapel->nama_matapelajaran ?? 'Unknown';
                $kelasNama = $jadwal->kelas->nama ?? 'Unknown';
                $guruNama = $jadwal->mapel->guru->nama ?? 'Unknown';

                return [
                    'bentrok' => true,
                    'jadwal' => $jadwal,
                    'pesan' => "Bentrok! Guru {$guruNama} sudah mengajar {$mapelNama} di kelas {$kelasNama} pada jam yang sama ({$jadwal->jam_mulai} - {$jadwal->jam_selesai}). Guru tidak bisa mengajar 2 kelas dalam waktu bersamaan!",
                    'tipe' => 'guru'
                ];
            }
        }

        return [
            'bentrok' => false,
            'jadwal' => null,
            'pesan' => null,
            'tipe' => null
        ];
    }

    /**
     * Helper untuk cek bentrok waktu
     */
    private static function isBentrokWaktu($jamMulai1, $jamSelesai1, $jamMulai2, $jamSelesai2): bool
    {
        // Cek apakah jam mulai 1 di antara jadwal 2
        if ($jamMulai1->gte($jamMulai2) && $jamMulai1->lt($jamSelesai2)) {
            return true;
        }

        // Cek apakah jam selesai 1 di antara jadwal 2
        if ($jamSelesai1->gt($jamMulai2) && $jamSelesai1->lte($jamSelesai2)) {
            return true;
        }

        // Cek apakah jadwal 1 menutupi jadwal 2
        if ($jamMulai1->lte($jamMulai2) && $jamSelesai1->gte($jamSelesai2)) {
            return true;
        }

        return false;
    }
}
