<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mitra extends Model
{
    use HasFactory;

    protected $table = 'mitra';

    protected $fillable = [
        'nama',
        'jenis',
        'nama_kontak',
        'telepon',
        'email',
        'alamat',
        'keterangan',
        'status',
    ];

    public function aset()
    {
        return $this->hasMany(Aset::class, 'mitra_id');
    }
}
