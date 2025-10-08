<?php
// app/Models/LaporanWaliKelas.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LaporanWalikelas extends Model
{
    use HasFactory;

    protected $table = 'laporan_walikelas';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal' => 'date',
    ];

    /**
     * Relasi ke Siswa
     */
    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * Relasi ke Guru (Wali Kelas)
     */
    public function waliKelas()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    /**
     * Alias untuk guru (agar konsisten dengan model Laporan)
     */
    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    /**
     * Accessor: Format tanggal Indonesia
     */
    public function getTanggalFormatAttribute(): string
    {
        return Carbon::parse($this->tanggal)->format('d/m/Y');
    }

    /**
     * Accessor: Nama kelas siswa
     */
    public function getNamaKelasAttribute(): ?string
    {
        return $this->siswa->kelas->nama ?? null;
    }

    /**
     * Scope: Filter by wali kelas
     */
    public function scopeByWaliKelas($query, $guruId)
    {
        return $query->where('guru_id', $guruId);
    }

    /**
     * Scope: Filter by kelas
     */
    public function scopeByKelas($query, $kelasId)
    {
        return $query->whereHas('siswa', function ($q) use ($kelasId) {
            $q->where('kelas_id', $kelasId);
        });
    }

    /**
     * Scope: Filter by siswa
     */
    public function scopeBySiswa($query, $siswaId)
    {
        return $query->where('siswa_id', $siswaId);
    }

    /**
     * Scope: Filter by tanggal range
     */
    public function scopeByTanggalRange($query, $dari, $sampai)
    {
        return $query->whereBetween('tanggal', [$dari, $sampai]);
    }

    /**
     * Scope: Laporan hari ini
     */
    public function scopeHariIni($query)
    {
        return $query->whereDate('tanggal', today());
    }

    /**
     * Scope: Laporan minggu ini
     */
    public function scopeMingguIni($query)
    {
        return $query->whereBetween('tanggal', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Scope: Laporan bulan ini
     */
    public function scopeBulanIni($query)
    {
        return $query->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month);
    }

    /**
     * Cek apakah wali kelas sudah membuat laporan untuk siswa di tanggal tertentu
     */
    public static function sudahAdaLaporan($guruId, $siswaId, $tanggal, $ignoreLaporanId = null): bool
    {
        $query = static::where('guru_id', $guruId)
            ->where('siswa_id', $siswaId)
            ->whereDate('tanggal', $tanggal);

        // Ignore laporan saat ini (untuk edit)
        if ($ignoreLaporanId) {
            $query->where('id', '!=', $ignoreLaporanId);
        }

        return $query->exists();
    }

    /**
     * Get laporan guru mapel untuk siswa ini dalam periode tertentu
     */
    public function getLaporanGuruMapel($dari = null, $sampai = null)
    {
        $query = Laporan::where('siswa_id', $this->siswa_id)
            ->where('terkirim_ke_wali', true)
            ->with(['jadwal.mapel', 'guru']);

        if ($dari && $sampai) {
            $query->whereBetween('tanggal', [$dari, $sampai]);
        } elseif ($dari) {
            $query->whereDate('tanggal', '>=', $dari);
        } elseif ($sampai) {
            $query->whereDate('tanggal', '<=', $sampai);
        }

        return $query->orderBy('tanggal', 'desc')->get();
    }

    /**
     * Get statistik laporan untuk siswa
     */
    public static function getStatistikSiswa($siswaId, $guruId)
    {
        return [
            'total' => static::where('siswa_id', $siswaId)
                ->where('guru_id', $guruId)
                ->count(),
            'bulan_ini' => static::where('siswa_id', $siswaId)
                ->where('guru_id', $guruId)
                ->bulanIni()
                ->count(),
            'minggu_ini' => static::where('siswa_id', $siswaId)
                ->where('guru_id', $guruId)
                ->mingguIni()
                ->count(),
        ];
    }
}
