<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatAset extends Model
{
    protected $table = 'riwayat_aset';

    protected $fillable = [
        'aset_id',
        'sejak_tanggal',
        'penanggung_jawab_id',
        'lokasi_id',
        'divisi_id',
        'jumlah',
        'kondisi_persen',
        'kelengkapan_persen',
        'kode_aset_sebelumnya',
        'kode_aset_baru',
        'jenis_aksi',
        'keterangan',
        'user_id',
        'updated_by',
    ];

    protected $casts = [
        'sejak_tanggal' => 'date',
        'kondisi_persen' => 'integer',
        'kelengkapan_persen' => 'integer',
        'jumlah' => 'integer',
    ];

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class);
    }

    public function penanggungJawab(): BelongsTo
    {
        return $this->belongsTo(PenanggungJawab::class, 'penanggung_jawab_id');
    }

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id');
    }

    public function divisi(): BelongsTo
    {
        return $this->belongsTo(Divisi::class, 'divisi_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
