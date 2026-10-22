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

    protected $appends = [
        'file_url',
        'is_image',
        'is_pdf',
        'formatted_size',
        'icon_class',
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
     * Dapatkan URL publik file lampiran.
     */
    public function getFileUrlAttribute(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return asset('storage/' . ltrim($this->file_path, '/'));
    }


    /**
     * Cek apakah lampiran adalah file gambar.
     */
    public function getIsImageAttribute(): bool
    {
        $type = strtolower((string) $this->file_type);
        if (! empty($type) && str_starts_with($type, 'image/')) {
            return true;
        }

        $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg', 'avif'];

        if (in_array($type, $imageExtensions, true)) {
            return true;
        }

        $pathExt = strtolower(pathinfo((string) $this->file_path, PATHINFO_EXTENSION));
        if (in_array($pathExt, $imageExtensions, true)) {
            return true;
        }

        $nameExt = strtolower(pathinfo((string) $this->file_name, PATHINFO_EXTENSION));
        return in_array($nameExt, $imageExtensions, true);
    }

    /**
     * Cek apakah lampiran adalah file PDF.
     */
    public function getIsPdfAttribute(): bool
    {
        $type = strtolower((string) $this->file_type);
        if ($type === 'application/pdf' || $type === 'pdf') {
            return true;
        }

        $pathExt = strtolower(pathinfo((string) $this->file_path, PATHINFO_EXTENSION));
        if ($pathExt === 'pdf') {
            return true;
        }

        $nameExt = strtolower(pathinfo((string) $this->file_name, PATHINFO_EXTENSION));
        return $nameExt === 'pdf';
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

        $fileType = strtolower((string) $this->file_type);
        $ext = strtolower(pathinfo((string) ($this->file_name ?: $this->file_path), PATHINFO_EXTENSION));

        // Excel harus dicek sebelum Word karena MIME keduanya mengandung 'document'
        if (str_contains($fileType, 'sheet') || str_contains($fileType, 'excel') || in_array($ext, ['xls', 'xlsx', 'csv'], true)) {
            return 'ti-file-type-xls';
        }

        if (str_contains($fileType, 'word') || str_contains($fileType, 'document') || in_array($ext, ['doc', 'docx'], true)) {
            return 'ti-file-type-doc';
        }

        return 'ti-file-text';
    }
}
