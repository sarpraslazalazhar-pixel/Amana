<?php

namespace App\Console\Commands;

use App\Models\AgendaAset;
use App\Models\Aset;
use App\Models\JurnalAset;
use App\Services\ImageOptimizerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class OptimizeExistingImagesCommand extends Command
{
    /**
     * Nama dan signature perintah console.
     *
     * @var string
     */
    protected $signature = 'amana:optimize-images
                            {--dry-run : Jalankan simulasi tanpa mengubah file fisik atau database}
                            {--quality=80 : Kualitas kompresi WebP (0-100)}
                            {--max-dim=1200 : Resolusi maksimal sisi terpanjang}';

    /**
     * Deskripsi perintah console.
     *
     * @var string
     */
    protected $description = 'Konversi dan optimasi seluruh foto aset dan lampiran gambar lama ke format WebP untuk menghemat ruang server';

    public function handle(ImageOptimizerService $optimizer): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $quality = (int) $this->option('quality');
        $maxDim = (int) $this->option('max-dim');

        $this->info('====================================================');
        $this->info('  AMANA — Optimasi & Konversi Gambar ke WebP');
        $this->info('====================================================');

        if ($isDryRun) {
            $this->warn(' [MODE SIMULASI (DRY-RUN)] File dan database TIDAK akan diubah.');
        }

        $totalConverted = 0;
        $totalSkipped = 0;
        $totalErrors = 0;
        $initialBytes = 0;
        $optimizedBytes = 0;

        $disk = Storage::disk('public');

        // 1. Optimasi Foto Utama Aset
        $this->newLine();
        $this->comment('Memeriksa Foto Utama Aset...');
        $asets = Aset::whereNotNull('foto_utama')->get();

        foreach ($asets as $aset) {
            $oldPath = $aset->foto_utama;

            // Jika sudah berformat webp, lewati
            if (str_ends_with(strtolower($oldPath), '.webp')) {
                $totalSkipped++;

                continue;
            }

            if (! $disk->exists($oldPath)) {
                $this->line("  - [SKIP] File tidak ditemukan di storage: {$oldPath}");
                $totalSkipped++;

                continue;
            }

            $raw = $disk->get($oldPath);
            $origSize = strlen($raw);
            $initialBytes += $origSize;

            if ($isDryRun) {
                $this->line("  - [SIMULASI] {$aset->kode_aset}: {$oldPath} (".$this->formatBytes($origSize).') -> WebP');
                $totalConverted++;

                continue;
            }

            try {
                $newWebpBinary = $optimizer->optimizeRawContent($raw, $maxDim, $quality);
                $newSize = strlen($newWebpBinary);
                $optimizedBytes += $newSize;

                $newPath = 'aset_foto/'.Str::random(40).'.webp';
                $disk->put($newPath, $newWebpBinary);

                // Update database
                $aset->update(['foto_utama' => $newPath]);

                // Hapus file lama
                $disk->delete($oldPath);

                $savedPercent = $origSize > 0 ? round((($origSize - $newSize) / $origSize) * 100, 1) : 0;
                $this->info("  - [OK] {$aset->kode_aset}: {$oldPath} -> {$newPath} (".$this->formatBytes($origSize).' -> '.$this->formatBytes($newSize).", hemat {$savedPercent}%)");
                $totalConverted++;
            } catch (Throwable $e) {
                $this->error("  - [GAGAL] {$aset->kode_aset}: ".$e->getMessage());
                $totalErrors++;
            }
        }

        // 2. Optimasi Lampiran Jurnal Aset
        $this->newLine();
        $this->comment('Memeriksa Lampiran Jurnal Aset...');
        $jurnals = JurnalAset::whereNotNull('lampiran')->get();

        foreach ($jurnals as $jurnal) {
            $oldPath = $jurnal->lampiran;

            if (! $optimizer->isImage($oldPath)) {
                // Dokumen seperti PDF atau Excel dilewati
                $totalSkipped++;

                continue;
            }

            if (str_ends_with(strtolower($oldPath), '.webp')) {
                $totalSkipped++;

                continue;
            }

            if (! $disk->exists($oldPath)) {
                $totalSkipped++;

                continue;
            }

            $raw = $disk->get($oldPath);
            $origSize = strlen($raw);
            $initialBytes += $origSize;

            if ($isDryRun) {
                $this->line("  - [SIMULASI Jurnal #{$jurnal->id}] {$oldPath} (".$this->formatBytes($origSize).') -> WebP');
                $totalConverted++;

                continue;
            }

            try {
                $newWebpBinary = $optimizer->optimizeRawContent($raw, $maxDim, $quality);
                $newSize = strlen($newWebpBinary);
                $optimizedBytes += $newSize;

                $newPath = 'jurnal_lampiran/'.Str::random(40).'.webp';
                $disk->put($newPath, $newWebpBinary);

                $jurnal->update(['lampiran' => $newPath]);
                $disk->delete($oldPath);

                $savedPercent = $origSize > 0 ? round((($origSize - $newSize) / $origSize) * 100, 1) : 0;
                $this->info("  - [OK Jurnal #{$jurnal->id}] {$oldPath} -> {$newPath} (hemat {$savedPercent}%)");
                $totalConverted++;
            } catch (Throwable $e) {
                $this->error("  - [GAGAL Jurnal #{$jurnal->id}] ".$e->getMessage());
                $totalErrors++;
            }
        }

        // 3. Optimasi Lampiran Penyelesaian Agenda
        $this->newLine();
        $this->comment('Memeriksa Lampiran Penyelesaian Agenda...');
        $agendas = AgendaAset::whereNotNull('lampiran_penyelesaian')->get();

        foreach ($agendas as $agenda) {
            $oldPath = $agenda->lampiran_penyelesaian;

            if (! $optimizer->isImage($oldPath) || str_ends_with(strtolower($oldPath), '.webp') || ! $disk->exists($oldPath)) {
                $totalSkipped++;

                continue;
            }

            $raw = $disk->get($oldPath);
            $origSize = strlen($raw);
            $initialBytes += $origSize;

            if ($isDryRun) {
                $this->line("  - [SIMULASI Agenda #{$agenda->id}] {$oldPath} (".$this->formatBytes($origSize).') -> WebP');
                $totalConverted++;

                continue;
            }

            try {
                $newWebpBinary = $optimizer->optimizeRawContent($raw, $maxDim, $quality);
                $newSize = strlen($newWebpBinary);
                $optimizedBytes += $newSize;

                $newPath = 'jurnal_lampiran/'.Str::random(40).'.webp';
                $disk->put($newPath, $newWebpBinary);

                $agenda->update(['lampiran_penyelesaian' => $newPath]);
                $disk->delete($oldPath);

                $savedPercent = $origSize > 0 ? round((($origSize - $newSize) / $origSize) * 100, 1) : 0;
                $this->info("  - [OK Agenda #{$agenda->id}] {$oldPath} -> {$newPath} (hemat {$savedPercent}%)");
                $totalConverted++;
            } catch (Throwable $e) {
                $this->error("  - [GAGAL Agenda #{$agenda->id}] ".$e->getMessage());
                $totalErrors++;
            }
        }

        // Rangkuman
        $this->newLine();
        $this->info('----------------------------------------------------');
        $this->info('  RANGKUMAN HASIL OPTIMASI');
        $this->info('----------------------------------------------------');
        $this->line("  • File Berhasil Dikonversi : {$totalConverted}");
        $this->line("  • File Dilewati/Sudah WebP : {$totalSkipped}");
        $this->line("  • File Gagal/Error         : {$totalErrors}");

        if (! $isDryRun && $initialBytes > 0) {
            $savedBytes = $initialBytes - $optimizedBytes;
            $savedPct = round(($savedBytes / $initialBytes) * 100, 1);
            $this->info('  • Ukuran Awal              : '.$this->formatBytes($initialBytes));
            $this->info('  • Ukuran Setelah WebP      : '.$this->formatBytes($optimizedBytes));
            $this->info('  • Total Ruang Dihemat      : '.$this->formatBytes($savedBytes)." ({$savedPct}%)");
        }
        $this->info('====================================================');

        return Command::SUCCESS;
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}
