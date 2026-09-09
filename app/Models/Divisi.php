<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Divisi extends Model
{
    use HasFactory;

    protected $table = 'divisi';

    protected $fillable = ['kode_divisi', 'nama_divisi', 'keterangan'];

    public function aset()
    {
        return $this->hasMany(Aset::class, 'divisi_id');
    }

    public function penanggungJawab()
    {
        return $this->hasMany(PenanggungJawab::class, 'divisi_id');
    }
}
