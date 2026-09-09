<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenanggungJawab extends Model
{
    use HasFactory;

    protected $table = 'penanggung_jawab';

    protected $fillable = [
        'nama',
        'kode_pic',
        'divisi_id',
        'jabatan',
        'alamat',
        'telepon',
        'email',
        'keterangan',
        'status',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function divisi()
    {
        return $this->belongsTo(Divisi::class, 'divisi_id');
    }

    public function aset()
    {
        return $this->hasMany(Aset::class, 'penanggung_jawab_id');
    }
}
