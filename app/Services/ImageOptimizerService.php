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
        $storedPath = $file->storeAs($directory, $filename, $disk);
        if ($storedPath === false) {
            throw new \RuntimeException('Gagal menyimpan berkas lampiran ke penyimpanan server.');
        }

        return $storedPath;
    }

    /**
     * Konversi nilai konfigurasi PHP INI (misal: 2M, 512K, 1G) ke byte.
     */
    public static function parseIniSizeToBytes(string|false $value): int
    {
        if ($value === false) {
            return PHP_INT_MAX;
        }

        $trimmed = trim($value);
        if ($trimmed === '' || $trimmed === '0' || $trimmed === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtolower(substr($trimmed, -1));
        $number = (float) $trimmed;

        return match ($unit) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    }

    /**
     * Dapatkan batas ukuran upload efektif dari konfigurasi PHP server (upload_max_filesize & post_max_size).
     */
    public static function getServerMaxUploadBytes(): int
    {
        $uploadMax = self::parseIniSizeToBytes(ini_get('upload_max_filesize'));
        $postMax = self::parseIniSizeToBytes(ini_get('post_max_size'));

        return min($uploadMax, $postMax);
    }

    /**
     * Format angka byte menjadi label ukuran ringkas (misal: 2 MB, 10 MB).
     */
    public static function formatBytesLabel(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 1).' GB';
        }
        if ($bytes >= 1024 * 1024) {
            $mb = $bytes / (1024 * 1024);

            return (fmod($mb, 1.0) === 0.0 ? (string) (int) $mb : (string) round($mb, 1)).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024).' KB';
        }

        return $bytes.' B';
    }

    /**
     * Buat pesan error upload yang informatif dalam Bahasa Indonesia ketika PHP gagal menerima berkas.
     */
    public static function formatUploadError(mixed $file, string $fieldLabel = 'lampiran'): string
    {
        if (is_array($file)) {
            foreach ($file as $item) {
                if ($item instanceof UploadedFile && ! $item->isValid()) {
                    $file = $item;
                    break;
                }
            }
        }

        if (! $file instanceof UploadedFile) {
            return "Gagal mengunggah {$fieldLabel} ke server. Pastikan ukuran file tidak melebihi batas upload server ("
                .(ini_get('upload_max_filesize') ?: '2M').').';
        }

        return match ($file->getError()) {
            UPLOAD_ERR_INI_SIZE => "Ukuran file {$fieldLabel} melebihi batas upload server PHP ("
                .(ini_get('upload_max_filesize') ?: '2M')
                .'). Perkecil/kompres file atau naikkan upload_max_filesize di server.',
            UPLOAD_ERR_FORM_SIZE => "Ukuran file {$fieldLabel} melebihi batas maksimum form HTML.",
            UPLOAD_ERR_PARTIAL => "Proses unggah {$fieldLabel} terputus sebelum selesai. Silakan coba lagi.",
            UPLOAD_ERR_NO_TMP_DIR => "Folder sementara (tmp) untuk unggah {$fieldLabel} tidak tersedia di server.",
            UPLOAD_ERR_CANT_WRITE => "Server gagal menulis file {$fieldLabel} ke disk penyimpanan sementara.",
            UPLOAD_ERR_EXTENSION => "Unggah {$fieldLabel} dihentikan oleh ekstensi PHP di server.",
            default => "Gagal mengunggah {$fieldLabel} ke server.",
        };
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
