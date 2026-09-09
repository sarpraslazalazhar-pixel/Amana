<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeuanganAset extends Model
{
    protected $table = 'keuangan_aset';

    protected $fillable = [
        'aset_id',
        'agenda_id',
        'tipe',
        'tanggal',
        'jenis_transaksi',
        'nominal',
        'keterangan',
        'user_id',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:2',
    ];

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class);
    }

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(AgendaAset::class, 'agenda_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getIsDariAgendaAttribute(): bool
    {
        return ! empty($this->agenda_id) || str_starts_with((string) $this->jenis_transaksi, 'Pemeliharaan / Agenda:');
    }
}
