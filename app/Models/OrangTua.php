<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrangTua extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'nama',
        'no_hp',
        'alamat',
    ];

    protected $table = 'orang_tuas';
    protected $guarded = ['id'];

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function siswas()
    {
        return $this->hasMany(Siswa::class, 'orang_tua_id');
    }
}
