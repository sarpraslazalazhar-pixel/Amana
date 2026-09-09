<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    use HasFactory;

    protected $table = 'kategori';

    protected $fillable = ['nama_kategori', 'kode_kategori', 'keterangan'];

    public function aset()
    {
        return $this->hasMany(Aset::class, 'kategori_id');
    }

    public function barang()
    {
        return $this->hasMany(Barang::class, 'kategori_id');
    }
}
