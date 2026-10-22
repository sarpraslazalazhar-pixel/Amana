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
     * Batas dimensi maksimum gambar mentah sebelum decode (Anti Pixel Flood / Decompression Bomb OOM).
     */
    public const MAX_RAW_DIMENSION = 8000;

    /**
     * Batas piksel maksimum (50 Megapiksel). Mencegah alokasi memori berlebih saat dekompresi GD/Imagick.
     */
    public const MAX_RAW_PIXELS = 50_000_000;

    /**
     * Validasi dimensi gambar dari header file tanpa mendekompresi ke memori penuh (Anti OOM / Decompression Bomb).
     *
     * @throws \InvalidArgumentException
     */
    public function assertSafeImageDimensions(mixed $source): void
    {
        $info = null;

        if ($source instanceof UploadedFile) {
            $path = $source->getRealPath();
            if ($path && file_exists($path)) {
                $info = @getimagesize($path);
            }
        } elseif (is_string($source)) {
            if (file_exists($source)) {
                $info = @getimagesize($source);
            } elseif (strlen($source) > 0) {
                $info = @getimagesizefromstring($source);
            }
        }

        if (is_array($info)) {
            $width = $info[0] ?? 0;
            $height = $info[1] ?? 0;

            if ($width > self::MAX_RAW_DIMENSION || $height > self::MAX_RAW_DIMENSION || ($width * $height) > self::MAX_RAW_PIXELS) {
                throw new \InvalidArgumentException(
                    "Dimensi gambar terlalu besar ({$width}x{$height} piksel). Batas maksimum adalah "
                    . self::MAX_RAW_DIMENSION . 'x' . self::MAX_RAW_DIMENSION . ' piksel untuk mencegah kehabisan memori server (OOM).'
                );
            }
        }
    }

    /**
     * Pastikan batas memori PHP aman saat memproses manipulasi gambar.
     */
    private function ensureMemoryLimit(): void
    {
        $currentLimit = ini_get('memory_limit');
        if ($currentLimit !== '-1' && (int) $currentLimit < 512) {
            @ini_set('memory_limit', '512M');
        }
    }

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

        // Cegah decompression bomb sebelum proses decode memori
        $this->assertSafeImageDimensions($source);
        $this->ensureMemoryLimit();

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
        $this->assertSafeImageDimensions($source);
        $this->ensureMemoryLimit();

        $image = Image::decode($source);
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
            }
        }

        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = Str::slug($baseName, '_');
        $extension = $file->getClientOriginalExtension() ?: ($file->guessExtension() ?: 'bin');
        $filename = time() . '_' . ($slug ?: 'file') . '.' . $extension;

        return $file->storeAs($directory, $filename, $disk);
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
