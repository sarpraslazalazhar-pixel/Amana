<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'barang';

    protected $fillable = ['kategori_id', 'kode_barang', 'nama_barang', 'keterangan'];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function aset()
    {
        return $this->hasMany(Aset::class, 'barang_id');
    }
}
