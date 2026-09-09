<?php

namespace App\Services;

use App\Models\Aset;
use App\Models\AuditLog;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\Merk;
use App\Models\PenanggungJawab;

class AuditLogger
{
    /**
     * Catat log audit secara manual / langsung
     */
    public static function log(
        string $aksi,
        string $deskripsi,
        ?Aset $aset = null,
        ?array $perubahan = null,
        string $modelType = 'Aset'
    ): AuditLog {
        return AuditLog::create([
            'user_id' => auth()->id() ?? 1,
            'aset_id' => $aset?->id,
            'kode_aset' => $aset?->kode_aset,
            'nama_aset' => $aset?->nama_aset,
            'aksi' => $aksi,
            'model_type' => $modelType,
            'deskripsi' => $deskripsi,
            'perubahan_data' => $perubahan,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Bandingkan data sebelum vs sesudah pada model Aset dan simpan audit log
     */
    public static function logAsetUpdate(Aset $aset, array $originalData, array $newData): ?AuditLog
    {
        $diffs = [];

        $fieldLabels = [
            'nama_aset' => 'Nama Aset',
            'kategori_id' => 'Kategori',
            'merk_id' => 'Merk',
            'lokasi_id' => 'Lokasi',
            'penanggung_jawab_id' => 'Penanggung Jawab',
            'tipe_model' => 'Tipe / Model',
            'produsen' => 'Produsen',
            'no_seri' => 'Nomor Seri',
            'tahun_produksi' => 'Tahun Produksi',
            'deskripsi' => 'Deskripsi',
            'tanggal_pembelian' => 'Tanggal Pembelian',
            'toko_distributor' => 'Toko / Distributor',
            'no_invoice' => 'No. Invoice',
            'jumlah_unit' => 'Jumlah Unit',
            'harga_satuan' => 'Harga Satuan',
            'harga_total' => 'Harga Total',
            'umur_ekonomis_tahun' => 'Umur Ekonomis',
            'nilai_residu' => 'Nilai Residu',
            'penyusutan_per_bulan' => 'Penyusutan / Bulan',
            'foto_utama' => 'Foto Utama',
            'keterangan_tambahan' => 'Catatan Tambahan',
            'jenis' => 'Jenis Aset',
            'status' => 'Status Aset',
        ];

        foreach ($newData as $key => $newVal) {
            if (! array_key_exists($key, $fieldLabels)) {
                continue;
            }

            $oldVal = $originalData[$key] ?? null;

            // Normalisasi perbandingan string/angka
            if (is_numeric($oldVal) && is_numeric($newVal)) {
                if ((float) $oldVal == (float) $newVal) {
                    continue;
                }
            } elseif (trim((string) $oldVal) === trim((string) $newVal)) {
                continue;
            }

            // Format nilai sebelum & sesudah agar ramah pengguna
            $formattedOld = self::formatValue($key, $oldVal);
            $formattedNew = self::formatValue($key, $newVal);

            $diffs[] = [
                'field' => $fieldLabels[$key],
                'key' => $key,
                'sebelum' => $formattedOld,
                'sesudah' => $formattedNew,
            ];
        }

        if (empty($diffs)) {
            return null;
        }

        $fieldNames = implode(', ', array_column($diffs, 'field'));
        $deskripsi = "Pembaruan data aset ({$fieldNames}) pada {$aset->kode_aset}";

        return self::log('update_data', $deskripsi, $aset, $diffs, 'Aset');
    }

    private static function formatValue(string $key, mixed $val): string
    {
        if ($val === null || $val === '') {
            return '-';
        }

        return match ($key) {
            'kategori_id' => Kategori::find($val)?->nama_kategori ?? "ID {$val}",
            'merk_id' => Merk::find($val)?->nama_merk ?? "ID {$val}",
            'lokasi_id' => Lokasi::find($val)?->nama_lokasi ?? "ID {$val}",
            'penanggung_jawab_id' => PenanggungJawab::find($val)?->nama ?? "ID {$val}",
            'harga_satuan', 'harga_total', 'nilai_residu', 'penyusutan_per_bulan' => 'Rp '.number_format((float) $val, 0, ',', '.'),
            'umur_ekonomis_tahun' => "{$val} Tahun",
            'foto_utama' => $val ? 'Ada Foto ('.basename($val).')' : 'Tidak ada foto',
            default => (string) $val,
        };
    }
}
