<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LampiranAset extends Model
{
    protected $table = 'lampiran_aset';

    protected $fillable = [
        'aset_id',
        'file_path',
        'file_name',
        'file_size',
        'file_type',
        'label',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function aset()
    {
        return $this->belongsTo(Aset::class, 'aset_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Format ukuran file ke bentuk human-readable (KB/MB).
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0) . ' KB';
        }

        return $bytes . ' B';
    }

    /**
     * Cek apakah lampiran adalah file gambar.
     */
    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->file_type, 'image/');
    }

    /**
     * Cek apakah lampiran adalah file PDF.
     */
    public function getIsPdfAttribute(): bool
    {
        return $this->file_type === 'application/pdf';
    }

    /**
     * Dapatkan icon class Tabler Icons sesuai tipe file.
     */
    public function getIconClassAttribute(): string
    {
        if ($this->is_image) {
            return 'ti-photo';
        }

        if ($this->is_pdf) {
            return 'ti-file-type-pdf';
        }

        // Excel harus dicek sebelum Word karena MIME keduanya mengandung 'document'
        if (str_contains($this->file_type, 'sheet') || str_contains($this->file_type, 'excel')) {
            return 'ti-file-type-xls';
        }

        if (str_contains($this->file_type, 'word') || str_contains($this->file_type, 'document')) {
            return 'ti-file-type-doc';
        }

        return 'ti-file-text';
    }
}
