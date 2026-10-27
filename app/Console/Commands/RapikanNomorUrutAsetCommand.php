<?php

namespace App\Console\Commands;

use App\Services\KodeAsetGenerator;
use Illuminate\Console\Command;

class RapikanNomorUrutAsetCommand extends Command
{
    /**
     * Nama dan signature perintah console.
     *
     * @var string
     */
    protected $signature = 'aset:rapikan-urutan
                            {--barang= : Filter per ID Barang tertentu}
                            {--divisi= : Filter per ID Divisi tertentu}
                            {--dry-run : Simulasi tanpa menyimpan ke database}';

    /**
     * Deskripsi perintah console.
     *
     * @var string
     */
    protected $description = 'Rapikan dan urutkan nomor urut serta kode aset berdasarkan kronologi umur aset (tanggal perolehan/pembelian ASC)';

    /**
     * Jalankan perintah console.
     */
    public function handle(): int
    {
        $barangId = $this->option('barang') !== null ? (int) $this->option('barang') : null;
        $divisiId = $this->option('divisi') !== null ? (int) $this->option('divisi') : null;
        $dryRun = (bool) $this->option('dry-run');

        $this->info('====================================================');
        $this->info('  AMANA — Rapikan Nomor Urut Aset (Umur/Kronologis)');
        $this->info('====================================================');

        if ($dryRun) {
            $this->warn(' [MODE SIMULASI (DRY-RUN)] Database TIDAK akan diubah.');
        }

        if ($barangId) {
            $this->line(" - Filter Barang ID: {$barangId}");
        }
        if ($divisiId) {
            $this->line(" - Filter Divisi ID: {$divisiId}");
        }

        $result = KodeAsetGenerator::rapikanUrutan($barangId, $divisiId, $dryRun);

        $this->newLine();
        $this->line("Total aset diperiksa : {$result['total_checked']}");
        $this->line("Total aset dirapikan : {$result['total_updated']}");

        if ($result['total_updated'] > 0) {
            $this->newLine();
            $this->table(
                ['ID', 'Nama Aset', 'Urut Lama', 'Urut Baru', 'Kode Lama', 'Kode Baru'],
                array_map(fn ($c) => [
                    $c['id'],
                    $c['nama_aset'],
                    $c['old_nomor_urut'],
                    $c['new_nomor_urut'],
                    $c['old_kode_aset'],
                    $c['new_kode_aset'],
                ], array_slice($result['changes'], 0, 50))
            );

            if (count($result['changes']) > 50) {
                $sisa = count($result['changes']) - 50;
                $this->comment("... dan {$sisa} perubahan aset lainnya.");
            }

            if ($dryRun) {
                $this->info('Simulasi selesai. Jalankan tanpa flag --dry-run untuk menerapkan.');
            } else {
                $this->info('Sukses! Nomor urut dan kode aset berhasil dirapikan.');
            }
        } else {
            $this->info('Semua aset sudah berurutan rapi sesuai tanggal pembelian. Tidak ada perubahan.');
        }

        return self::SUCCESS;
    }
}
