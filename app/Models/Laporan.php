<?php
// app/Models/Laporan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Laporan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal' => 'date',
        'terkirim_ke_wali' => 'boolean',
        'tanggal_kirim_ke_wali' => 'datetime',
    ];

    /**
     * Relasi ke Siswa
     */
    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * Relasi ke Guru (pembuat laporan)
     */
    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    /**
     * Relasi ke Jadwal
     */
    public function jadwal()
    {
        return $this->belongsTo(Jadwal::class);
    }

    /**
     * Accessor: Status pengiriman dalam bentuk text
     */
    public function getStatusPengirimanAttribute(): string
    {
        if ($this->terkirim_ke_wali) {
            $tanggal = Carbon::parse($this->tanggal_kirim_ke_wali)->format('d/m/Y H:i');
            return "Terkirim pada {$tanggal}";
        }

        return 'Belum Terkirim';
    }

    /**
     * Accessor: Nama wali kelas
     */
    public function getNamaWaliKelasAttribute(): ?string
    {
        return $this->siswa->kelas->waliGuru->nama ?? null;
    }

    /**
     * Method: Tandai laporan sebagai terkirim
     */
    public function tandaiTerkirim(): bool
    {
        return $this->update([
            'terkirim_ke_wali' => true,
            'tanggal_kirim_ke_wali' => now(),
        ]);
    }

    /**
     * Method: Reset status pengiriman
     */
    public function resetPengiriman(): bool
    {
        return $this->update([
            'terkirim_ke_wali' => false,
            'tanggal_kirim_ke_wali' => null,
        ]);
    }

    /**
     * Scope: Hanya laporan yang sudah terkirim
     */
    public function scopeTerkirim($query)
    {
        return $query->where('terkirim_ke_wali', true);
    }

    /**
     * Scope: Hanya laporan yang belum terkirim
     */
    public function scopeBelumTerkirim($query)
    {
        return $query->where('terkirim_ke_wali', false);
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
