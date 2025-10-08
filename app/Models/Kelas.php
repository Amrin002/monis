<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;
    protected $fillable = [
        'nama',
        'wali_guru_id',
    ];

    protected $table = 'kelas';
    protected $guarded = ['id'];

    public function waliGuru()
    {
        return $this->belongsTo(Guru::class, 'wali_guru_id'); // ← Tambahkan foreign key
    }

    public function siswas()
    {
        return $this->hasMany(Siswa::class, 'kelas_id');
    }

    public function jadwals()
    {
        return $this->hasMany(Jadwal::class);
    }

    public function pengumuman()
    {
        return $this->hasMany(Pengumuman::class);
    }
}
