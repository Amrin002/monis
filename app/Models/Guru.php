<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Guru extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];


    /**
     * Get the user that owns the guru.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function kelasWali()
    {
        return $this->hasOne(Kelas::class, 'wali_guru_id');
    }
    public function mapels()
    {
        return $this->hasMany(Mapel::class);
    }

    public function isWaliKelas(): bool
    {
        return (bool) $this->is_wali_kelas;
    }

    public function isGuruMapel(): bool
    {
        return (bool) $this->is_guru_mapel;
    }
    public function laporans()
    {
        return $this->hasMany(Laporan::class);
    }
    public function laporanWaliKelas()
    {
        return $this->hasMany(LaporanWalikelas::class, 'guru_id');
    }
    /**
     * Get siswa di kelas yang diampu (untuk wali kelas)
     */
    public function getSiswaKelasWali()
    {
        if (!$this->kelasWali) {
            return collect([]);
        }

        return $this->kelasWali->siswas;
    }
}
