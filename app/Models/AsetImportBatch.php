<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsetImportBatch extends Model
{
    use HasFactory;

    protected $table = 'aset_import_batches';

    protected $fillable = [
        'nama_file',
        'total_baris',
        'baris_siap',
        'baris_belum_lengkap',
        'baris_duplikat',
        'status',
        'created_by',
    ];

    public function items()
    {
        return $this->hasMany(AsetImportItem::class, 'batch_id')->orderBy('baris_ke');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recalculateCounts(): void
    {
        $total = $this->items()->count();
        $siap = $this->items()->where('is_ready', true)->count();
        $belumLengkap = $this->items()->where('is_ready', false)->count();
        $duplikat = $this->items()->where('is_duplicate', true)->count();

        $this->update([
            'total_baris' => $total,
            'baris_siap' => $siap,
            'baris_belum_lengkap' => $belumLengkap,
            'baris_duplikat' => $duplikat,
        ]);
    }
}
