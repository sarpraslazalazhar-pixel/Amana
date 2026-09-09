<?php

namespace App\Services;

use App\Models\Aset;
use App\Models\AsetImportBatch;
use App\Models\AsetImportItem;
use App\Models\Barang;
use App\Models\Divisi;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\Merk;
use App\Models\PenanggungJawab;
use App\Models\RiwayatAset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AsetImportService
{
    /**
     * Parse file Excel / CSV ke tabel staging (aset_import_batches & aset_import_items).
     */
    public static function parseAndStage(UploadedFile|string $file, ?string $originalName = null, ?int $userId = null): AsetImportBatch
    {
        $filePath = is_string($file) ? $file : $file->getRealPath();
        $fileName = $originalName ?? (is_string($file) ? basename($file) : $file->getClientOriginalName());

        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray(null, true, true, true);

        if (empty($rows)) {
            throw new \InvalidArgumentException('File kosong atau tidak dapat dibaca.');
        }

        // 1. Deteksi Baris Header (Mendukung 1-baris maupun 2-baris header bertingkat hingga baris ke-15)
        $headerRowIndices = [];
        $columnMap = [];

        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex > 15) {
                break;
            }
            $rowUpper = array_map(fn ($v) => strtoupper(trim((string) $v)), $row);

            // Cek apakah baris ini memuat indikator header
            $matches = 0;
            foreach ($rowUpper as $cell) {
                $cClean = strtoupper(trim(preg_replace('/[^A-Z0-9]/', ' ', $cell)));
                if (in_array($cClean, [
                    'NAMA ASET', 'KODE ASET', 'KODE ASET LAMA', 'KODE SISTEM', 'KODE SISTEM LAMA',
                    'KATEGORI', 'MERK', 'TIPE', 'PRODUSEN', 'DESKRIPSI', 'TANGGAL', 'PEMBELIAN',
                    'HARGA SATUAN', 'HARGA SATUAN RP', 'HARGA TOTAL', 'HARGA TOTAL RP', 'JUMLAH',
                    'UMUR EKONOMI', 'UMUR EKONOMI TAHUN', 'UMUR EKONOMIS', 'PENANGGUNG JAWAB', 'LOKASI',
                ], true)) {
                    $matches++;
                }
            }

            if ($matches >= 2) {
                $headerRowIndices[] = $rowIndex;
            }
        }

        $headerRowIndex = ! empty($headerRowIndices) ? max($headerRowIndices) : 1;

        // Bangun columnMap dari seluruh baris header yang ditemukan
        foreach ($headerRowIndices as $hIdx) {
            $hRow = $rows[$hIdx] ?? [];
            foreach ($hRow as $colKey => $colVal) {
                if (is_null($colVal) || trim((string) $colVal) === '') {
                    continue;
                }
                $rawVal = strtoupper(trim((string) $colVal));
                $cleanVal = strtoupper(trim(preg_replace('/[^A-Z0-9]/', ' ', $rawVal)));
                $noParen = strtoupper(trim(preg_replace('/\s*\(.*?\)\s*/', ' ', $rawVal)));
                $noParenClean = strtoupper(trim(preg_replace('/[^A-Z0-9]/', ' ', $noParen)));

                // Simpan variasi nama kolom ke columnMap
                $columnMap[$rawVal] = $colKey;
                $columnMap[$cleanVal] = $colKey;
                $columnMap[$noParen] = $colKey;
                $columnMap[$noParenClean] = $colKey;
            }
        }

        // Buat batch staging baru
        $batch = AsetImportBatch::create([
            'nama_file' => $fileName,
            'status' => 'draft',
            'created_by' => $userId ?? auth()->id(),
        ]);

        $itemCount = 0;
        $barisKe = 1;

        // 2. Iterasi baris data (setelah header)
        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex <= $headerRowIndex) {
                continue;
            }

            // Cek apakah baris kosong
            $nonEmptyValues = array_filter($row, fn ($v) => ! is_null($v) && trim((string) $v) !== '');
            if (empty($nonEmptyValues)) {
                continue;
            }

            // Ekstrak data mentah dengan pemetaan fleksibel
            $kodeAsetLama = self::extractColumn($row, $columnMap, ['KODE ASET (LAMA)', 'KODE ASET', 'KODE ASET LAMA', 'NO REGISTER', 'KODE']);
            $kodeSistemLama = self::extractColumn($row, $columnMap, ['KODE SISTEM (LAMA)', 'KODE SISTEM', 'KODE SISTEM LAMA', 'SYSTEM CODE']);
            $namaAsetMentah = self::extractColumn($row, $columnMap, ['NAMA ASET', 'NAMA BARANG', 'ASET', 'BARANG', 'DESKRIPSI BARANG']);

            // Jika nama aset kosong atau berupa teks header berulang, lewati
            if (empty($namaAsetMentah) || in_array(strtoupper($namaAsetMentah), ['NAMA ASET', 'NAMA BARANG', 'BARANG', 'ASET'], true)) {
                continue;
            }

            $kategoriMentah = self::extractColumn($row, $columnMap, ['KATEGORI', 'JENIS KATEGORI', 'GOLONGAN']);
            $merkMentah = self::extractColumn($row, $columnMap, ['MERK', 'BRAND', 'MERK/BRAND', 'MERK / TYPE']);
            $tipeModel = self::extractColumn($row, $columnMap, ['TIPE', 'MODEL', 'TIPE/MODEL', 'TYPE']);
            $produsen = self::extractColumn($row, $columnMap, ['PRODUSEN', 'MANUFAKTUR']);
            $noSeri = self::extractColumn($row, $columnMap, ['NO SERI', 'NOMOR SERI', 'SERIAL NUMBER', 'SN', 'KODE PRODUKSI']);
            $tahunProduksi = (int) self::extractColumn($row, $columnMap, ['TAHUN PRODUKSI', 'THN PRODUKSI', 'TH PRODUKSI', 'TAHUN']);
            $lokasiMentah = self::extractColumn($row, $columnMap, ['LOKASI', 'RUANG', 'TEMPAT', 'LOKASI BARANG']);
            $pjMentah = self::extractColumn($row, $columnMap, ['PENANGGUNG JAWAB', 'PJ', 'AMIL', 'PIC', 'PEMAKAI', 'PENGGUNA']);
            $deskripsiMentah = self::extractColumn($row, $columnMap, ['DESKRIPSI', 'KETERANGAN', 'SPESIFIKASI', 'DETAIL']);
            $tglBeliMentah = self::extractColumn($row, $columnMap, ['TANGGAL PEMBELIAN', 'TGL PEMBELIAN', 'TANGGAL', 'TGL BELI', 'TANGGAL PEROLEHAN', 'TGL PEROLEHAN', 'SEJAK TANGGAL']);
            $tokoDistributor = self::extractColumn($row, $columnMap, ['DISTRIBUTOR', 'TOKO', 'TOKO / DISTRIBUTOR', 'SUPPLIER', 'VENDOR']);
            $noInvoice = self::extractColumn($row, $columnMap, ['NO. INVOICE', 'NO INVOICE', 'NO NOTA', 'INVOICE', 'NO KUITANSI']);
            $jumlahUnit = (int) AsetFuzzyMatcher::parseNumeric(self::extractColumn($row, $columnMap, ['JUMLAH UNIT', 'JUMLAH', 'QTY', 'UNIT']), 1);

            $rawHargaSatuan = self::extractColumn($row, $columnMap, ['HARGA SATUAN (RP)', 'HARGA SATUAN', 'HARGA PER UNIT', 'NILAI SATUAN', 'HARGA']);
            $rawHargaTotal = self::extractColumn($row, $columnMap, ['HARGA TOTAL (RP)', 'HARGA TOTAL', 'TOTAL HARGA', 'NILAI TOTAL']);

            $hargaSatuan = AsetFuzzyMatcher::parseNumeric($rawHargaSatuan, 0);
            $hargaTotal = AsetFuzzyMatcher::parseNumeric($rawHargaTotal, 0);

            if ($hargaSatuan <= 0 && $hargaTotal > 0 && $jumlahUnit > 0) {
                $hargaSatuan = $hargaTotal / $jumlahUnit;
            }
            if ($hargaTotal <= 0 && $hargaSatuan > 0 && $jumlahUnit > 0) {
                $hargaTotal = $hargaSatuan * $jumlahUnit;
            }

            $umurEkonomis = (int) AsetFuzzyMatcher::parseNumeric(self::extractColumn($row, $columnMap, ['UMUR EKONOMI (TAHUN)', 'UMUR EKONOMI', 'UMUR EKONOMIS', 'UMUR EKONOMIS (TAHUN)', 'MASA MANFAAT', 'UMUR (THN)']), 5);
            $nilaiResidu = AsetFuzzyMatcher::parseNumeric(self::extractColumn($row, $columnMap, ['NILAI RESIDU', 'RESIDU']), 0);
            $keteranganTambahan = self::extractColumn($row, $columnMap, ['KETERANGAN TAMBAHAN', 'KETERANGAN (UMUM)', 'KETERANGAN', 'CATATAN']);

            $tanggalPembelian = AsetFuzzyMatcher::parseDate($tglBeliMentah) ?? date('Y-m-d');
            if ($umurEkonomis < 1) {
                $umurEkonomis = 5;
            }
            if ($jumlahUnit < 1) {
                $jumlahUnit = 1;
            }

            // 3. Cek apakah Kode Aset di Excel adalah format resmi 17-digit 9-Komponen (Auto-Unpack)
            $unpacked = ! empty($kodeAsetLama) ? KodeAsetGenerator::parse($kodeAsetLama) : null;

            if ($unpacked) {
                // Auto-unpack langsung dari 9 komponen kode aset resmi
                $matchedKategori = Kategori::where('kode_kategori', $unpacked['kode_kategori'])->first();
                $kategoriId = $matchedKategori?->id;

                $matchedBarang = $kategoriId
                    ? Barang::where('kategori_id', $kategoriId)->where('kode_barang', $unpacked['kode_barang'])->first()
                    : Barang::where('kode_barang', $unpacked['kode_barang'])->first();
                $barangId = $matchedBarang?->id;
                if ($matchedBarang && ! $kategoriId) {
                    $kategoriId = $matchedBarang->kategori_id;
                }

                if (! $matchedBarang) {
                    $matchedBarang = AsetFuzzyMatcher::matchBarang($namaAsetMentah, $kategoriId);
                    $barangId = $matchedBarang?->id;
                    if ($matchedBarang && ! $kategoriId) {
                        $kategoriId = $matchedBarang->kategori_id;
                    }
                }

                // Helper lookup Lokasi dengan toleransi leading zero & auto-mapping 340-342
                $findLokasi = function ($code) {
                    if (empty($code)) {
                        return null;
                    }
                    $c = ltrim($code, '0');
                    $p = str_pad($code, 3, '0', STR_PAD_LEFT);
                    $lok = Lokasi::where('kode_lokasi', $code)
                        ->orWhere('kode_lokasi', $p)
                        ->orWhere('kode_lokasi', $c)
                        ->first();

                    if (! $lok && in_array((string) $code, ['340', '341', '342'], true)) {
                        $lok = Lokasi::firstOrCreate(
                            ['kode_lokasi' => (string) $code],
                            [
                                'nama_lokasi' => "Kampus RGI Sawangan (Area {$code})",
                                'gedung' => 'Kampus RGI Sawangan',
                                'lantai' => 'Lantai 1',
                                'keterangan' => 'Area Tambahan Kampus RGI Sawangan',
                            ]
                        );
                    }

                    return $lok;
                };

                // Helper lookup PIC (NIA) dengan toleransi leading zero (072, 72, 026, 26, 231 -> 023)
                $findPj = function ($code) {
                    if (empty($code)) {
                        return null;
                    }
                    $c = ltrim($code, '0');
                    $p = str_pad($code, 3, '0', STR_PAD_LEFT);

                    $pj = PenanggungJawab::where('kode_pic', $code)
                        ->orWhere('kode_pic', $p)
                        ->orWhere('kode_pic', $c)
                        ->first();

                    if (! $pj && in_array((string) $code, ['231', '023'], true)) {
                        $pj = PenanggungJawab::where('kode_pic', '023')->first();
                    }

                    return $pj;
                };

                $sifatBarang = in_array($unpacked['sifat_barang'], ['D', 'S'], true) ? $unpacked['sifat_barang'] : 'D';
                $rawKeempat = $unpacked['kode_keempat'] ?? null;

                // Cek apakah kode extended memiliki pic/lokasi tersendiri
                $targetPicCode = $unpacked['kode_pic'] ?? ($sifatBarang === 'D' ? $rawKeempat : null);
                $targetLokCode = $unpacked['kode_lokasi'] ?? ($sifatBarang === 'S' ? $rawKeempat : null);

                $matchedPj = $findPj($targetPicCode);
                $matchedLokasi = $findLokasi($targetLokCode);

                if ($sifatBarang === 'D') {
                    if (! $matchedPj && $rawKeempat) {
                        $matchedPj = $findPj($rawKeempat);
                    }
                    if (! $matchedPj && $pjMentah) {
                        $matchedPj = AsetFuzzyMatcher::matchPenanggungJawab($pjMentah);
                    }
                    $pjId = $matchedPj?->id;
                    if (! $matchedLokasi) {
                        $matchedLokasi = AsetFuzzyMatcher::matchLokasi($lokasiMentah, $deskripsiMentah);
                    }
                    $lokasiId = $matchedLokasi?->id;

                    // Fallback jika tertulis D tapi kode_keempat sebenarnya adalah Lokasi
                    if (! $pjId && $rawKeempat) {
                        $fallbackLokasi = $findLokasi($rawKeempat);
                        if ($fallbackLokasi) {
                            $sifatBarang = 'S';
                            $lokasiId = $fallbackLokasi->id;
                            $matchedLokasi = $fallbackLokasi;
                        }
                    }
                } else {
                    if (! $matchedLokasi && $rawKeempat) {
                        $matchedLokasi = $findLokasi($rawKeempat);
                    }
                    if (! $matchedLokasi && $lokasiMentah) {
                        $matchedLokasi = AsetFuzzyMatcher::matchLokasi($lokasiMentah, $deskripsiMentah);
                    }
                    $lokasiId = $matchedLokasi?->id;
                    if (! $matchedPj) {
                        $matchedPj = AsetFuzzyMatcher::matchPenanggungJawab($pjMentah);
                    }
                    $pjId = $matchedPj?->id;

                    // Fallback jika tertulis S tapi kode_keempat sebenarnya adalah PIC
                    if (! $lokasiId && $rawKeempat) {
                        $fallbackPj = $findPj($rawKeempat);
                        if ($fallbackPj) {
                            $sifatBarang = 'D';
                            $pjId = $fallbackPj->id;
                            $matchedPj = $fallbackPj;
                        }
                    }
                }

                $matchedDivisi = Divisi::where('kode_divisi', $unpacked['kode_divisi'])->first();
                $divisiId = $matchedDivisi?->id ?? $matchedPj?->divisi_id;

                if (! $divisiId && $matchedLokasi) {
                    if (str_starts_with($matchedLokasi->kode_lokasi, '3')) {
                        $divisiId = Divisi::where('kode_divisi', '5')->value('id');
                    } else {
                        $divisiId = Divisi::where('kode_divisi', '1')->value('id');
                    }
                }

                $caraPerolehan = in_array($unpacked['cara_perolehan'], ['1', '2'], true) ? $unpacked['cara_perolehan'] : '1';
                $statusBarang = in_array($unpacked['status_barang'], ['1', '2'], true) ? $unpacked['status_barang'] : '1';

                // Jika tahun di kode ada, sesuaikan tanggal pembelian jika perlu
                if (! empty($unpacked['tahun']) && (! $tanggalPembelian || substr($tanggalPembelian, 0, 4) !== $unpacked['tahun'])) {
                    $tanggalPembelian = "{$unpacked['tahun']}-01-01";
                }
            } else {
                // Jalankan Resolusi Komponen Cerdas (Fuzzy Matcher)
                $matchedKategori = AsetFuzzyMatcher::matchKategori($kategoriMentah ?: $namaAsetMentah);
                $kategoriId = $matchedKategori?->id;

                $matchedBarang = AsetFuzzyMatcher::matchBarang($namaAsetMentah, $kategoriId);
                $barangId = $matchedBarang?->id;
                if ($matchedBarang && ! $kategoriId) {
                    $kategoriId = $matchedBarang->kategori_id;
                }

                $matchedPj = AsetFuzzyMatcher::matchPenanggungJawab($pjMentah);
                $pjId = $matchedPj?->id;

                $matchedLokasi = AsetFuzzyMatcher::matchLokasi($lokasiMentah, $deskripsiMentah);
                $lokasiId = $matchedLokasi?->id;

                // Tentukan Sifat Barang: 'D' jika ada PIC, 'S' jika hanya Lokasi
                $sifatBarang = ($pjId && ! empty($matchedPj?->kode_pic)) ? 'D' : ($lokasiId ? 'S' : 'D');

                // Ambil Divisi: prioritas dari PIC, fallback null
                $divisiId = $matchedPj?->divisi_id;

                $caraPerolehan = '1'; // default Beli
                $statusBarang = '1';  // default Baru
            }

            // Cek Duplikasi Kode Aset Lama di database
            $isDuplicate = false;
            $existingAset = null;
            if (! empty($kodeAsetLama)) {
                $existingAset = Aset::where('kode_aset_lama', $kodeAsetLama)
                    ->orWhere('kode_aset', $kodeAsetLama)
                    ->first();
                if ($existingAset) {
                    $isDuplicate = true;
                }
            }

            // Buat Record Staging Item
            $item = new AsetImportItem([
                'batch_id' => $batch->id,
                'baris_ke' => $barisKe++,
                'kode_aset_lama' => $kodeAsetLama,
                'kode_sistem_lama' => $kodeSistemLama,
                'nama_aset_mentah' => $namaAsetMentah,
                'kategori_mentah' => $kategoriMentah,
                'merk_mentah' => $merkMentah,
                'tipe_model' => $tipeModel,
                'produsen' => $produsen,
                'no_seri' => $noSeri,
                'tahun_produksi' => $tahunProduksi > 1900 ? $tahunProduksi : null,
                'lokasi_mentah' => $lokasiMentah,
                'pj_mentah' => $pjMentah,
                'deskripsi_mentah' => $deskripsiMentah,
                'tanggal_pembelian' => $tanggalPembelian,
                'toko_distributor' => $tokoDistributor ?: 'LAZ Al Azhar',
                'no_invoice' => $noInvoice,
                'jumlah_unit' => $jumlahUnit,
                'harga_satuan' => $hargaSatuan,
                'umur_ekonomis_tahun' => $umurEkonomis,
                'nilai_residu' => $nilaiResidu,
                'keterangan_tambahan' => $keteranganTambahan,

                'kategori_id' => $kategoriId,
                'barang_id' => $barangId,
                'sifat_barang' => $sifatBarang,
                'lokasi_id' => $lokasiId,
                'penanggung_jawab_id' => $pjId,
                'divisi_id' => $divisiId,
                'cara_perolehan' => $caraPerolehan,
                'status_barang' => $statusBarang,

                'is_duplicate' => $isDuplicate,
                'existing_aset_id' => $existingAset?->id,
            ]);

            $item->evaluateReadiness();
            $item->save();
            $itemCount++;
        }

        $batch->recalculateCounts();

        return $batch;
    }

    /**
     * Helper ekstrak nilai kolom dari baris array dengan pencarian cerdas.
     */
    private static function extractColumn(array $row, array $columnMap, array $possibleHeaders): ?string
    {
        // 1. Exact / Normalized key matching
        foreach ($possibleHeaders as $header) {
            $hUpper = strtoupper(trim($header));
            $hClean = strtoupper(trim(preg_replace('/[^A-Z0-9]/', ' ', $hUpper)));
            $noParen = strtoupper(trim(preg_replace('/\s*\(.*?\)\s*/', ' ', $hUpper)));
            $noParenClean = strtoupper(trim(preg_replace('/[^A-Z0-9]/', ' ', $noParen)));

            foreach ([$hUpper, $hClean, $noParen, $noParenClean] as $key) {
                if (isset($columnMap[$key]) && isset($row[$columnMap[$key]])) {
                    $val = trim((string) $row[$columnMap[$key]]);
                    if ($val !== '' && $val !== '-') {
                        return $val;
                    }
                }
            }
        }

        // 2. Substring matching in columnMap
        foreach ($possibleHeaders as $header) {
            $hClean = strtoupper(trim(preg_replace('/[^A-Z0-9]/', ' ', $header)));
            if (strlen($hClean) < 3) {
                continue;
            }

            foreach ($columnMap as $mapName => $colKey) {
                if ($mapName === $hClean || str_starts_with($mapName, $hClean) || str_contains($mapName, $hClean)) {
                    if (isset($row[$colKey])) {
                        $val = trim((string) $row[$colKey]);
                        if ($val !== '' && $val !== '-') {
                            return $val;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Update komponen per baris item staging.
     */
    public static function updateItem(AsetImportItem $item, array $data): AsetImportItem
    {
        $fillableKeys = [
            'kategori_id', 'barang_id', 'sifat_barang', 'lokasi_id',
            'penanggung_jawab_id', 'divisi_id', 'cara_perolehan', 'status_barang',
            'tanggal_pembelian', 'nama_aset_mentah', 'merk_mentah', 'tipe_model',
            'jumlah_unit', 'harga_satuan', 'umur_ekonomis_tahun', 'nilai_residu',
        ];

        foreach ($fillableKeys as $key) {
            if (array_key_exists($key, $data)) {
                $item->{$key} = $data[$key];
            }
        }

        $item->evaluateReadiness();
        $item->save();
        $item->batch->recalculateCounts();

        return $item;
    }

    /**
     * Bulk Assign nilai komponen ke baris-baris tertentu.
     */
    public static function bulkAssign(AsetImportBatch $batch, array $itemIds, array $assignments): int
    {
        $query = $batch->items();
        if (! empty($itemIds)) {
            $query->whereIn('id', $itemIds);
        }

        $items = $query->get();
        $count = 0;

        foreach ($items as $item) {
            if (! empty($assignments['divisi_id'])) {
                $item->divisi_id = (int) $assignments['divisi_id'];
            }
            if (! empty($assignments['sifat_barang']) && in_array($assignments['sifat_barang'], ['D', 'S'], true)) {
                $item->sifat_barang = $assignments['sifat_barang'];
            }
            if (! empty($assignments['cara_perolehan']) && in_array($assignments['cara_perolehan'], ['1', '2'], true)) {
                $item->cara_perolehan = $assignments['cara_perolehan'];
            }
            if (! empty($assignments['status_barang']) && in_array($assignments['status_barang'], ['1', '2'], true)) {
                $item->status_barang = $assignments['status_barang'];
            }
            if (! empty($assignments['lokasi_id'])) {
                $item->lokasi_id = (int) $assignments['lokasi_id'];
            }
            if (! empty($assignments['penanggung_jawab_id'])) {
                $item->penanggung_jawab_id = (int) $assignments['penanggung_jawab_id'];
            }
            if (! empty($assignments['kategori_id'])) {
                $item->kategori_id = (int) $assignments['kategori_id'];
            }
            if (! empty($assignments['barang_id'])) {
                $item->barang_id = (int) $assignments['barang_id'];
            }

            $item->evaluateReadiness();
            $item->save();
            $count++;
        }

        $batch->recalculateCounts();

        return $count;
    }

    /**
     * Commit Staging Items ke Tabel Utama Aset dalam Database Transaction.
     * Menggunakan generator kode dan kalkulator penyusutan yang sudah ada.
     */
    public static function commitBatch(AsetImportBatch $batch, string $duplicateAction = 'update', ?int $userId = null): array
    {
        $userId = $userId ?? auth()->id() ?? 1;
        $items = $batch->items()->get();

        $stats = [
            'total' => $items->count(),
            'success' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();

        try {
            foreach ($items as $item) {
                // 1. Validasi kesiapan komponen
                if (! $item->is_ready) {
                    $item->update([
                        'import_status' => 'failed',
                        'error_message' => 'Komponen belum lengkap: '.implode(', ', $item->missing_components ?? []),
                    ]);
                    $stats['failed']++;

                    continue;
                }

                // 2. Handle Duplikat
                if ($item->is_duplicate && $item->existing_aset_id) {
                    if ($duplicateAction === 'skip') {
                        $item->update([
                            'import_status' => 'skipped',
                            'error_message' => 'Dilewati karena kode aset lama sudah terdaftar.',
                        ]);
                        $stats['skipped']++;

                        continue;
                    }

                    if ($duplicateAction === 'update') {
                        $existing = Aset::find($item->existing_aset_id);
                        if ($existing) {
                            $hargaTotal = PenyusutanCalculator::hitungHargaTotal($item->jumlah_unit, $item->harga_satuan);
                            $penyusutanBulan = PenyusutanCalculator::hitungPenyusutanPerBulan($hargaTotal, $item->umur_ekonomis_tahun, $item->nilai_residu);

                            $existing->update([
                                'nama_aset' => $item->nama_aset_mentah,
                                'kode_sistem_lama' => $item->kode_sistem_lama ?: $existing->kode_sistem_lama,
                                'jumlah_unit' => $item->jumlah_unit,
                                'harga_satuan' => $item->harga_satuan,
                                'harga_total' => $hargaTotal,
                                'umur_ekonomis_tahun' => $item->umur_ekonomis_tahun,
                                'nilai_residu' => $item->nilai_residu,
                                'penyusutan_per_bulan' => $penyusutanBulan,
                                'keterangan_tambahan' => $item->keterangan_tambahan,
                            ]);

                            $item->update([
                                'import_status' => 'updated',
                                'generated_kode_aset' => $existing->kode_aset,
                            ]);
                            $stats['updated']++;

                            continue;
                        }
                    }
                }

                // 3. Buat Aset Baru melalui Generator Kode Aset yang sudah ada
                try {
                    // Cari atau buat Merk
                    $merkId = 1; // default merk
                    if (! empty($item->merk_mentah)) {
                        $merk = Merk::firstOrCreate(['nama_merk' => trim($item->merk_mentah)]);
                        $merkId = $merk->id;
                    } else {
                        $firstMerk = Merk::first();
                        $merkId = $firstMerk ? $firstMerk->id : Merk::create(['nama_merk' => 'Umum'])->id;
                    }

                    // Tentukan fallback lokasi atau PJ
                    $lokasiId = $item->lokasi_id;
                    $pjId = $item->penanggung_jawab_id;

                    if ($item->sifat_barang === 'D' && ! $lokasiId) {
                        $lokasiId = 1; // fallback ke lokasi utama jika dinamis
                    }
                    if ($item->sifat_barang === 'S' && ! $pjId) {
                        $pjId = 1; // fallback ke penanggung jawab default jika statis
                    }

                    // Cek apakah kode dari Excel adalah format kode 9-komponen dan nomor urutnya belum bentrok
                    $parsedDirect = ! empty($item->kode_aset_lama) ? KodeAsetGenerator::parse($item->kode_aset_lama) : null;
                    $useDirectCode = false;

                    if ($parsedDirect) {
                        $targetCode = $parsedDirect['kode_resmi'] ?? $item->kode_aset_lama;
                        $isCodeTaken = Aset::where('kode_aset', $targetCode)->exists();
                        if (! $isCodeTaken) {
                            $useDirectCode = true;
                            $kodeAset = $targetCode;
                            $nomorUrut = (int) $parsedDirect['nomor_urut'];
                        }
                    }

                    if (! $useDirectCode) {
                        // Panggil Generator Kode Aset 9-Komponen untuk mengamankan nomor urut unik
                        $genResult = KodeAsetGenerator::generate([
                            'kategori_id' => $item->kategori_id,
                            'barang_id' => $item->barang_id,
                            'sifat_barang' => $item->sifat_barang,
                            'penanggung_jawab_id' => $pjId,
                            'lokasi_id' => $lokasiId,
                            'divisi_id' => $item->divisi_id,
                            'cara_perolehan' => $item->cara_perolehan,
                            'status_barang' => $item->status_barang,
                            'tanggal_pembelian' => $item->tanggal_pembelian ? $item->tanggal_pembelian->format('Y-m-d') : date('Y-m-d'),
                        ]);

                        $kodeAset = $genResult['kode_aset'];
                        $nomorUrut = $genResult['nomor_urut'];
                    }

                    // Hitung Penyusutan
                    $hargaTotal = PenyusutanCalculator::hitungHargaTotal($item->jumlah_unit, $item->harga_satuan);
                    $penyusutanBulan = PenyusutanCalculator::hitungPenyusutanPerBulan($hargaTotal, $item->umur_ekonomis_tahun, $item->nilai_residu);

                    // Tentukan Klasifikasi / Jenis dari Divisi
                    $divisi = Divisi::find($item->divisi_id);
                    $jenis = ($divisi && in_array($divisi->kode_divisi, ['5', '6'], true)) ? 'kelolaan' : 'tetap';

                    $aset = Aset::create([
                        'nama_aset' => $item->nama_aset_mentah,
                        'sifat_barang' => $item->sifat_barang,
                        'kode_aset' => $kodeAset,
                        'kode_aset_lama' => $item->kode_aset_lama,
                        'kode_sistem_lama' => $item->kode_sistem_lama,
                        'kategori_id' => $item->kategori_id,
                        'barang_id' => $item->barang_id,
                        'divisi_id' => $item->divisi_id,
                        'cara_perolehan' => $item->cara_perolehan,
                        'status_barang' => $item->status_barang,
                        'nomor_urut' => $nomorUrut,
                        'merk_id' => $merkId,
                        'tipe_model' => $item->tipe_model,
                        'produsen' => $item->produsen,
                        'no_seri' => $item->no_seri,
                        'tahun_produksi' => $item->tahun_produksi,
                        'lokasi_id' => $lokasiId,
                        'penanggung_jawab_id' => $pjId,
                        'deskripsi' => $item->deskripsi_mentah,
                        'tanggal_pembelian' => $item->tanggal_pembelian,
                        'toko_distributor' => $item->toko_distributor ?: 'LAZ Al Azhar',
                        'no_invoice' => $item->no_invoice,
                        'jumlah_unit' => $item->jumlah_unit,
                        'harga_satuan' => $item->harga_satuan,
                        'harga_total' => $hargaTotal,
                        'umur_ekonomis_tahun' => $item->umur_ekonomis_tahun,
                        'nilai_residu' => $item->nilai_residu,
                        'penyusutan_per_bulan' => $penyusutanBulan,
                        'keterangan_tambahan' => $item->keterangan_tambahan,
                        'status' => 'aktif',
                        'jenis' => $jenis,
                        'created_by' => $userId,
                    ]);

                    // Catat Riwayat Pertama Aset
                    RiwayatAset::create([
                        'aset_id' => $aset->id,
                        'sejak_tanggal' => $item->tanggal_pembelian ?? now()->toDateString(),
                        'penanggung_jawab_id' => $pjId,
                        'lokasi_id' => $lokasiId,
                        'jumlah' => $item->jumlah_unit,
                        'kondisi_persen' => 100,
                        'kelengkapan_persen' => 100,
                        'jenis_aksi' => 'pembuatan',
                        'keterangan' => "Aset diimpor dari file {$batch->nama_file} (Kode Lama: ".($item->kode_aset_lama ?: '-').').',
                        'user_id' => $userId,
                    ]);

                    AuditLogger::log('import_aset', "Impor aset {$aset->kode_aset} ({$aset->nama_aset})", $aset);

                    $item->update([
                        'import_status' => 'success',
                        'generated_kode_aset' => $kodeAset,
                        'existing_aset_id' => $aset->id,
                    ]);
                    $stats['success']++;
                } catch (\Throwable $e) {
                    $item->update([
                        'import_status' => 'failed',
                        'error_message' => 'Gagal generate: '.$e->getMessage(),
                    ]);
                    $stats['failed']++;
                    $stats['errors'][] = "Baris {$item->baris_ke}: ".$e->getMessage();
                }
            }

            $batch->update(['status' => 'completed']);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $batch->update(['status' => 'failed']);
            throw $e;
        }

        return $stats;
    }
}
