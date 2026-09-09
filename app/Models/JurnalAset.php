<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class JurnalAset extends Model
{
    protected $table = 'jurnal_aset';

    protected $fillable = [
        'aset_id',
        'agenda_id',
        'tanggal',
        'kejadian',
        'lampiran',
        'tingkat_kerusakan',
        'status_penanganan',
        'user_id',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
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
        return ! empty($this->agenda_id) || str_starts_with((string) $this->kejadian, 'Penyelesaian Agenda:');
    }

    public function getLampiranUrlAttribute(): ?string
    {
        if (! $this->lampiran) {
            return null;
        }

        return Storage::disk('public')->url($this->lampiran);
    }
}
