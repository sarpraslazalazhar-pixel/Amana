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

    protected $appends = [
        'lampiran_url',
        'is_image',
        'is_pdf',
        'lampiran_file_name',
        'is_dari_agenda',
        'icon_class',
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

    public function getIconClassAttribute(): string
    {
        if ($this->is_image) {
            return 'ti-photo';
        }

        if ($this->is_pdf) {
            return 'ti-file-type-pdf';
        }

        $ext = strtolower(pathinfo((string) $this->lampiran, PATHINFO_EXTENSION));
        if (in_array($ext, ['xls', 'xlsx', 'csv'], true)) {
            return 'ti-file-type-xls';
        }

        if (in_array($ext, ['doc', 'docx'], true)) {
            return 'ti-file-type-doc';
        }

        return 'ti-paperclip';
    }
}
