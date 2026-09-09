<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QrConfig extends Model
{
    use HasFactory;

    protected $table = 'qr_config';

    protected $fillable = ['key', 'value', 'group', 'type', 'label', 'urutan'];

    /**
     * Default konfigurasi sistem AMANA
     */
    public static function getDefaults(): array
    {
        return [
            // --- 1. Informasi Umum ---
            'show_nama_aset' => ['value' => '1', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Nama Aset', 'urutan' => 1],
            'show_kategori' => ['value' => '1', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Kategori', 'urutan' => 2],
            'show_kode_aset' => ['value' => '1', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Kode Aset', 'urutan' => 3],
            'show_kode_sistem' => ['value' => '0', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Kode Sistem', 'urutan' => 4],
            'show_keterangan_tambahan' => ['value' => '1', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Keterangan Tambahan', 'urutan' => 5],
            'show_foto' => ['value' => '1', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Foto', 'urutan' => 6],
            'show_lampiran' => ['value' => '0', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Lampiran', 'urutan' => 7],
            'show_status_aset' => ['value' => '1', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Status Aset (Aktif/Non-Aktif)', 'urutan' => 8],

            // --- 2. Jika Status Aset Non-Aktif ---
            'show_nonaktif_tanggal' => ['value' => '1', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Tanggal Non-Aktif', 'urutan' => 9],
            'show_nonaktif_sebab' => ['value' => '1', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Sebab', 'urutan' => 10],
            'show_nonaktif_keterangan' => ['value' => '1', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Keterangan Non-Aktif', 'urutan' => 11],

            // --- 3. Detil Aset ---
            'show_merk' => ['value' => '1', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Merk', 'urutan' => 12],
            'show_tipe' => ['value' => '1', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Tipe', 'urutan' => 13],
            'show_produsen' => ['value' => '0', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Produsen', 'urutan' => 14],
            'show_no_seri' => ['value' => '1', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'No. Seri / Kode Produksi', 'urutan' => 15],
            'show_tahun_produksi' => ['value' => '0', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Tahun Produksi', 'urutan' => 16],
            'show_deskripsi' => ['value' => '1', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Deskripsi', 'urutan' => 17],

            // --- 4. Pembelian ---
            'show_tanggal_pembelian' => ['value' => '0', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Tanggal Pembelian', 'urutan' => 18],
            'show_toko_distributor' => ['value' => '0', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Toko / Distributor', 'urutan' => 19],
            'show_no_invoice' => ['value' => '0', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'No. Invoice', 'urutan' => 20],
            'show_jumlah_unit' => ['value' => '0', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Jumlah Unit', 'urutan' => 21],
            'show_harga_satuan' => ['value' => '0', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Harga Satuan', 'urutan' => 22],
            'show_harga_total' => ['value' => '0', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Harga Total', 'urutan' => 23],

            // --- 5. Umur & Penyusutan ---
            'show_umur_ekonomi' => ['value' => '0', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Umur Ekonomi', 'urutan' => 24],
            'show_penyusutan_per_bulan' => ['value' => '0', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Nilai Penyusutan per Bulan (Rp)', 'urutan' => 25],
            'show_usia_aset' => ['value' => '0', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Usia Aset', 'urutan' => 26],
            'show_nilai_sekarang' => ['value' => '0', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Nilai Sekarang (Rp)', 'urutan' => 27],

            // --- 6. Keuangan, Agenda, dan Jurnal ---
            'show_keuangan' => ['value' => '0', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Transaksi Pengeluaran / Pemasukan Aset', 'urutan' => 28],
            'show_agenda' => ['value' => '0', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Agenda Aset', 'urutan' => 29],
            'show_jurnal' => ['value' => '0', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Jurnal Aset', 'urutan' => 30],

            // --- 7. Riwayat ---
            'riwayat_mode' => ['value' => 'terakhir', 'group' => 'public_scan', 'type' => 'select', 'label' => 'Mode Riwayat (terakhir, semua, tidak_tampil)', 'urutan' => 31],
            'show_riwayat_tanggal' => ['value' => '1', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Tanggal Riwayat', 'urutan' => 32],
            'show_riwayat_penanggung_jawab' => ['value' => '1', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Penanggung Jawab Riwayat', 'urutan' => 33],
            'show_riwayat_lokasi' => ['value' => '1', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Lokasi Riwayat', 'urutan' => 34],
            'show_riwayat_jumlah' => ['value' => '1', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Jumlah Riwayat', 'urutan' => 35],
            'show_riwayat_kondisi' => ['value' => '1', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Kondisi Riwayat', 'urutan' => 36],
            'show_riwayat_kelengkapan' => ['value' => '1', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Kelengkapan Riwayat', 'urutan' => 37],
            'show_riwayat_keterangan' => ['value' => '0', 'group' => 'public_scan', 'type' => 'boolean', 'label' => 'Keterangan Riwayat', 'urutan' => 38],

            // --- 8. Keterangan Label QR-Code ---
            'label_judul_pilihan' => ['value' => 'preset', 'group' => 'qr_label', 'type' => 'select', 'label' => 'Pilihan Judul Label', 'urutan' => 39],
            'label_judul_preset' => ['value' => 'MILIK LAZ AL AZHAR', 'group' => 'qr_label', 'type' => 'string', 'label' => 'Judul Preset', 'urutan' => 40],
            'label_judul_custom' => ['value' => '', 'group' => 'qr_label', 'type' => 'string', 'label' => 'Judul Custom', 'urutan' => 41],
            'label_baris_1' => ['value' => 'nama_aset', 'group' => 'qr_label', 'type' => 'select', 'label' => 'Baris Pertama', 'urutan' => 42],
            'label_baris_2' => ['value' => 'kode_aset', 'group' => 'qr_label', 'type' => 'select', 'label' => 'Baris Kedua', 'urutan' => 43],
        ];
    }

    /**
     * Mengambil seluruh konfigurasi sebagai key => value ter-casting
     */
    public static function getAllConfig(): array
    {
        $defaults = self::getDefaults();
        $stored = self::all()->keyBy('key');

        $result = [];
        foreach ($defaults as $key => $meta) {
            $record = $stored->get($key);
            $rawVal = $record ? $record->value : $meta['value'];

            if ($meta['type'] === 'boolean') {
                $result[$key] = filter_var($rawVal, FILTER_VALIDATE_BOOLEAN) || $rawVal === '1' || $rawVal === 1;
            } else {
                $result[$key] = (string) $rawVal;
            }
        }

        return $result;
    }

    /**
     * Mengambil nilai tunggal
     */
    public static function getValue(string $key, $default = null)
    {
        $all = self::getAllConfig();

        return $all[$key] ?? $default;
    }

    /**
     * Mendapatkan judul label efektif
     */
    public static function getEffectiveLabelTitle(): string
    {
        $all = self::getAllConfig();
        if (($all['label_judul_pilihan'] ?? 'preset') === 'custom' && ! empty($all['label_judul_custom'])) {
            return mb_substr($all['label_judul_custom'], 0, 25);
        }

        return mb_substr($all['label_judul_preset'] ?? 'MILIK LAZ AL AZHAR', 0, 25);
    }

    /**
     * Simpan banyak konfigurasi sekaligus
     */
    public static function setMany(array $data): void
    {
        $defaults = self::getDefaults();

        foreach ($defaults as $key => $meta) {
            $val = null;
            if ($meta['type'] === 'boolean') {
                // Checkbox yang tidak dicentang tidak terkirim via POST form
                $val = isset($data[$key]) && ($data[$key] === '1' || $data[$key] === 1 || $data[$key] === true || $data[$key] === 'on') ? '1' : '0';
            } else {
                $val = isset($data[$key]) ? (string) $data[$key] : $meta['value'];
            }

            self::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $val,
                    'group' => $meta['group'],
                    'type' => $meta['type'],
                    'label' => $meta['label'],
                    'urutan' => $meta['urutan'],
                ]
            );
        }
    }
}
