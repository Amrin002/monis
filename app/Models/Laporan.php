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
}
