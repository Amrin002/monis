<?php
// app/Models/Absensi.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Absensi extends Model
{
    use HasFactory;

    protected $fillable = [
        'siswa_id',
        'jadwal_id',
        'tanggal',
        'status',
        'keterangan',
    ];

    // Relasi ke siswa
    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    // Relasi ke jadwal
    public function jadwal()
    {
        return $this->belongsTo(Jadwal::class, 'jadwal_id');
    }

    /**
     * Get ringkasan absensi per jadwal dan tanggal
     * Untuk menampilkan di tabel utama
     */
    public static function getRingkasanAbsensi($guruId)
    {
        return static::select(
            'jadwal_id',
            'tanggal',
            DB::raw('COUNT(DISTINCT siswa_id) as total_siswa'),
            DB::raw('SUM(CASE WHEN status = "hadir" THEN 1 ELSE 0 END) as jumlah_hadir'),
            DB::raw('SUM(CASE WHEN status = "izin" THEN 1 ELSE 0 END) as jumlah_izin'),
            DB::raw('SUM(CASE WHEN status = "sakit" THEN 1 ELSE 0 END) as jumlah_sakit'),
            DB::raw('SUM(CASE WHEN status = "alpa" THEN 1 ELSE 0 END) as jumlah_alpa')
        )
            ->whereHas('jadwal.mapel', function ($query) use ($guruId) {
                $query->where('guru_id', $guruId);
            })
            ->groupBy('jadwal_id', 'tanggal')
            ->orderBy('tanggal', 'desc');
    }

    /**
     * Hitung persentase kehadiran
     */
    public static function hitungPersentaseKehadiran($jadwalId, $tanggal)
    {
        $absensi = static::where('jadwal_id', $jadwalId)
            ->whereDate('tanggal', $tanggal)
            ->get();

        $total = $absensi->count();
        if ($total == 0) {
            return 0;
        }

        $hadir = $absensi->where('status', 'hadir')->count();
        return round(($hadir / $total) * 100);
    }
}
