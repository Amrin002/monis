<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Laporan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Relasi ke Siswa
     */
    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * Relasi ke Guru (wali kelas)
     */
    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }
    public function jadwal()
    {
        return $this->belongsTo(Jadwal::class);
    }

    /**
     * Cek apakah guru sudah membuat laporan untuk siswa ini di tanggal tertentu
     */
    public static function sudahAdaLaporanHariIni($guruId, $siswaId, $jadwalId, $tanggal, $ignoreLaporanId = null): bool
    {
        $query = static::where('guru_id', $guruId)
            ->where('siswa_id', $siswaId)
            ->where('jadwal_id', $jadwalId)
            ->whereDate('tanggal', $tanggal);

        // Ignore laporan saat ini (untuk edit)
        if ($ignoreLaporanId) {
            $query->where('id', '!=', $ignoreLaporanId);
        }

        return $query->exists();
    }
}
