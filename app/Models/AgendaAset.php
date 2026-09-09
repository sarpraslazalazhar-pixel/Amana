<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AgendaAset extends Model
{
    protected $table = 'agenda_aset';

    protected $fillable = [
        'aset_id',
        'tipe_agenda',
        'nama_agenda',
        'hari',
        'tanggal_hari',
        'bulan',
        'tanggal',
        'biaya_estimasi',
        'keterangan',
        'status',
        'tanggal_selesai',
        'catatan_penyelesaian',
        'lampiran_penyelesaian',
        'biaya_riil',
        'user_id',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_selesai' => 'date',
        'biaya_estimasi' => 'decimal:2',
        'biaya_riil' => 'decimal:2',
        'tanggal_hari' => 'integer',
        'bulan' => 'integer',
    ];

    protected static function booted(): void
    {
        static::retrieved(function (AgendaAset $agenda) {
            $agenda->cekDanResetPeriode();
        });
    }

    /**
     * Cek apakah agenda berulang (mingguan/bulanan/tahunan) telah berganti periode
     * dan otomatis reset status ke pending jika siklus baru telah dimulai.
     */
    public function cekDanResetPeriode(): bool
    {
        if ($this->status !== 'selesai' || $this->tipe_agenda === 'tanggal_tertentu' || ! $this->tanggal_selesai) {
            return false;
        }

        $now = now();
        $perluReset = false;

        switch ($this->tipe_agenda) {
            case 'mingguan':
                $perluReset = ! $this->tanggal_selesai->isSameWeek($now);
                break;
            case 'bulanan':
                $perluReset = $this->tanggal_selesai->format('Y-m') !== $now->format('Y-m');
                break;
            case 'tahunan':
                $perluReset = (int) $this->tanggal_selesai->year !== (int) $now->year;
                break;
        }

        if ($perluReset) {
            $this->status = 'pending';
            $this->saveQuietly();

            return true;
        }

        return false;
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getLampiranPenyelesaianUrlAttribute(): ?string
    {
        if (! $this->lampiran_penyelesaian) {
            return null;
        }

        return Storage::disk('public')->url($this->lampiran_penyelesaian);
    }

    /**
     * Helper untuk menampilkan label jadwal frekuensi yang manusiawi
     */
    public function getJadwalTeksAttribute(): string
    {
        $bulanNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return match ($this->tipe_agenda) {
            'mingguan' => 'Setiap hari '.ucfirst($this->hari ?? 'Senin'),
            'bulanan' => 'Setiap tanggal '.($this->tanggal_hari ?? 1).' per bulan',
            'tahunan' => 'Setiap tanggal '.($this->tanggal_hari ?? 1).' '.($bulanNames[$this->bulan] ?? 'Januari').' per tahun',
            default => $this->tanggal ? $this->tanggal->format('d M Y') : '-',
        };
    }
}
