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
        return $this->belongsTo(Guru::class);
    }
}
