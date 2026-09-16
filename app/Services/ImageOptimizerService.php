<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Throwable;

class ImageOptimizerService
{
    /**
     * Resolusi maksimum default (lebar atau tinggi)
     */
    public const DEFAULT_MAX_DIMENSION = 1200;

    /**
     * Kualitas kompresi WebP default (0-100)
     */
    public const DEFAULT_QUALITY = 80;

    /**
     * Optimasi file gambar yang diunggah dan simpan ke disk dalam format WebP.
     *
     * @return string Path relatif file yang disimpan
     */
    public function optimizeAndStore(
        UploadedFile $file,
        string $directory = 'aset_foto',
        int $maxDimension = self::DEFAULT_MAX_DIMENSION,
        int $quality = self::DEFAULT_QUALITY,
        string $disk = 'public'
    ): string {
        // Buat nama file acak unik dengan ekstensi .webp
        $filename = Str::random(40).'.webp';
        $fullPath = trim($directory, '/').'/'.$filename;

        // Dapatkan path nyata file atau konten
        $source = $file->getRealPath() ?: $file;

        // Baca gambar dengan Intervention Image v4
        $image = Image::decode($source);

        // Skala turunkan gambar jika melebihi batas resolusi maksimum (mempertahankan rasio aspek)
        $image->scaleDown(width: $maxDimension, height: $maxDimension);

        // Konversi ke format WebP terkompresi
        $encoded = $image->encode(new WebpEncoder(quality: $quality));

        // Simpan ke storage disk
        Storage::disk($disk)->put($fullPath, (string) $encoded);

        return $fullPath;
    }

    /**
     * Optimasi konten gambar mentah (string binary atau path) dan kembalikan binary WebP.
     *
     * @return string Binary WebP
     */
    public function optimizeRawContent(
        mixed $source,
        int $maxDimension = self::DEFAULT_MAX_DIMENSION,
        int $quality = self::DEFAULT_QUALITY
    ): string {
        $image = Image::decode($source);
        $image->scaleDown(width: $maxDimension, height: $maxDimension);
        $encoded = $image->encode(new WebpEncoder(quality: $quality));

        return (string) $encoded;
    }

    /**
     * Cek apakah file atau ekstensi/MIME tergolong gambar yang dapat diproses.
     */
    public function isImage(mixed $file): bool
    {
        if ($file instanceof UploadedFile) {
            $mime = $file->getMimeType();
            if ($mime && str_starts_with($mime, 'image/')) {
                return true;
            }

            $ext = strtolower($file->getClientOriginalExtension());

            return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'avif', 'svg']);
        }

        if (is_string($file)) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'avif']);
        }

        return false;
    }

    /**
     * Simpan file lampiran secara cerdas:
     * Jika berupa gambar, dioptimasi dan dikonversi ke WebP.
     * Jika berupa dokumen (PDF/Word/Excel), disimpan apa adanya.
     *
     * @return string Path relatif
     */
    public function storeAttachmentSmart(
        UploadedFile $file,
        string $directory = 'jurnal_lampiran',
        string $disk = 'public'
    ): string {
        if ($this->isImage($file)) {
            try {
                return $this->optimizeAndStore($file, $directory, self::DEFAULT_MAX_DIMENSION, self::DEFAULT_QUALITY, $disk);
            } catch (Throwable) {
                // Fallback jika gagal decode gambar: simpan biasa
                return $file->store($directory, $disk);
            }
        }

        return $file->store($directory, $disk);
    }

    /**
     * Hapus file lama dari disk secara aman jika ada.
     */
    public function deleteOldImage(?string $path, string $disk = 'public'): void
    {
        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}
