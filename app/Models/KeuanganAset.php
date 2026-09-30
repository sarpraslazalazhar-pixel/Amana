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
        'lampiran',
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

    public function getLampiranUrlAttribute(): ?string
    {
        if (! $this->lampiran) {
            return null;
        }

        return asset('storage/' . ltrim($this->lampiran, '/'));
    }

    public function getIsImageAttribute(): bool
    {
        if (! $this->lampiran) {
            return false;
        }

        $ext = strtolower(pathinfo($this->lampiran, PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg', 'avif'], true);
    }

    public function getIsPdfAttribute(): bool
    {
        if (! $this->lampiran) {
            return false;
        }

        return strtolower(pathinfo($this->lampiran, PATHINFO_EXTENSION)) === 'pdf';
    }

    public function getLampiranFileNameAttribute(): ?string
    {
        if (! $this->lampiran) {
            return null;
        }

        return basename($this->lampiran);
    }
}
