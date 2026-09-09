<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lokasi extends Model
{
    use HasFactory;

    protected $table = 'lokasi';

    protected $fillable = [
        'nama_lokasi',
        'gedung',
        'alamat_lengkap',
        'latitude',
        'longitude',
        'kode_lokasi',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function aset()
    {
        return $this->hasMany(Aset::class, 'lokasi_id');
    }
}
