<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    use HasFactory;

    protected $fillable = [
        'judul',
        'isi',
        'tanggal',
        'guru_id',
        'kelas_id',
    ];

    // Sesuaikan dengan nama tabel di migration
    protected $table = 'pengumumen';

    // Relasi ke Guru
    public function guru()
    {
        return $this->belongsTo(Guru::class);
    }

    // Relasi ke Kelas (bisa null = umum)
    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    // Accessor untuk mengetahui apakah pengumuman umum
    public function getIsUmumAttribute()
    {
        return is_null($this->kelas_id);
    }
}
