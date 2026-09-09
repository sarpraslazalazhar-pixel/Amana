<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Merk extends Model
{
    use HasFactory;

    protected $table = 'merk';

    protected $fillable = ['nama_merk'];

    public function aset()
    {
        return $this->hasMany(Aset::class, 'merk_id');
    }
}
