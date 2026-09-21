<?php

namespace App\Services;

use App\Models\Aset;
use App\Models\Barang;
use App\Models\Divisi;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\PenanggungJawab;
use Carbon\Carbon;
use InvalidArgumentException;

class KodeAsetGenerator
{
    /**
     * Generate Kode Aset 9-Komponen sesuai spesifikasi MODUL_KODE_ASET.md:
     *
     * Dinamis (17 karakter):
     * [Kategori(2)][Barang(2)][D][PIC(3)][Divisi(1)][Cara(1)][Status(1)][Tahun(4)][Urutan(2)]
     * Contoh: EL14D050212201520
     *
     * Statis (17 karakter):
     * [Kategori(2)][Barang(2)][S][Lokasi(3)][Divisi(1)][Cara(1)][Status(1)][Tahun(4)][Urutan(2)]
     * Contoh: EL01S111211201501
     *
     * @param  array  $params  [
     *                         'kategori_id' => int,
     *                         'barang_id' => int,
     *                         'sifat_barang' => 'D'|'S',
     *                         'penanggung_jawab_id' => int|null,
     *                         'lokasi_id' => int|null,
     *                         'divisi_id' => int,
     *                         'cara_perolehan' => '1'|'2',
     *                         'status_barang' => '1'|'2',
     *                         'tanggal_pembelian' => string,
     *                         ]
     * @return array ['kode_aset' => string, 'nomor_urut' => int, 'components' => array]
     */
    public static function generate(array $params): array
    {
        $validated = self::resolveComponents($params);

        // Cari nomor urut berikutnya berdasarkan kombinasi: Kategori + Barang + Divisi + Tahun
        $nomorUrut = self::calculateNextSequence(
            $validated['kategori_id'],
            $validated['barang_id'],
            $validated['divisi_id'],
            $validated['tahun']
        );

        $urutanPad = str_pad((string) $nomorUrut, 2, '0', STR_PAD_LEFT);

        $kodeAset = sprintf(
            '%s%s%s%s%s%s%s%s%s',
            $validated['kode_kategori'],
            $validated['kode_barang'],
            $validated['sifat_barang'],
            $validated['kode_keempat'],
            $validated['kode_divisi'],
            $validated['cara_perolehan'],
            $validated['status_barang'],
            $validated['tahun'],
            $urutanPad
        );

        return [
            'kode_aset' => $kodeAset,
            'nomor_urut' => $nomorUrut,
            'components' => array_merge($validated, ['nomor_urut' => $urutanPad]),
        ];
    }

    /**
     * Preview kode aset untuk live form preview tanpa mengubah sequence lock.
     */
    public static function preview(array $params): array
    {
        try {
            return self::generate($params);
        } catch (\Throwable $e) {
            return [
                'kode_aset' => null,
                'nomor_urut' => 1,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Regenerasi Kode Aset saat Mutasi / Perpindahan Tangan.
     * Aturan:
     * - Nomor urut asli aset TETAP dipertahankan (karena fisik unit sama).
     * - Sifat Dinamis (D): Kode ke-4 (PIC 3 digit) berubah jika PIC berubah; Divisi berubah jika PIC memiliki divisi atau diubah manual.
     * - Sifat Statis (S): Kode ke-4 (Lokasi 3 digit) berubah jika Lokasi berubah; Divisi berubah jika diubah manual.
     */
    public static function regenerateForMutation(Aset $aset, ?int $newPjId, ?int $newLokasiId, ?int $newDivisiId = null): array
    {
        $sifatBarang = strtoupper(trim($aset->sifat_barang ?: 'D'));
        if (! in_array($sifatBarang, ['D', 'S'], true)) {
            $sifatBarang = 'D';
        }

        $targetPjId = $newPjId ?: $aset->penanggung_jawab_id;
        $targetLokasiId = $newLokasiId ?: $aset->lokasi_id;

        // Tentukan Divisi baru
        $targetDivisiId = $newDivisiId;
        if (! $targetDivisiId) {
            if ($sifatBarang === 'D' && $targetPjId) {
                $pjModel = PenanggungJawab::find($targetPjId);
                $targetDivisiId = $pjModel?->divisi_id ?: $aset->divisi_id;
            } else {
                $targetDivisiId = $aset->divisi_id;
            }
        }
        if ($targetDivisiId && ! Divisi::where('id', $targetDivisiId)->exists()) {
            $targetDivisiId = null;
        }

        // Ambil komponen Kategori & Barang
        $parsed = self::parse($aset->kode_aset);

        $kodeKategori = null;
        if ($aset->kategori) {
            $kodeKategori = strtoupper(substr(trim($aset->kategori->kode_kategori), 0, 2));
            $kodeKategori = str_pad($kodeKategori, 2, 'X');
        } elseif (! empty($parsed['kode_kategori'])) {
            $kodeKategori = $parsed['kode_kategori'];
        } else {
            $kodeKategori = 'EL';
        }

        $kodeBarang = null;
        if ($aset->barang) {
            $kodeBarang = str_pad(trim($aset->barang->kode_barang), 2, '0', STR_PAD_LEFT);
        } elseif (! empty($parsed['kode_barang'])) {
            $kodeBarang = $parsed['kode_barang'];
        } else {
            $kodeBarang = '01';
        }

        // Komponen ke-4: PIC (Dinamis) atau Lokasi (Statis)
        if ($sifatBarang === 'D') {
            $pj = $targetPjId ? PenanggungJawab::find($targetPjId) : null;
            $kodePic = $pj ? (! empty($pj->kode_pic) ? trim($pj->kode_pic) : (string) $pj->id) : '000';
            $kodeKeempat = str_pad($kodePic, 3, '0', STR_PAD_LEFT);
        } else {
            $lokasi = $targetLokasiId ? Lokasi::find($targetLokasiId) : null;
            $kodeLok = $lokasi ? (! empty($lokasi->kode_lokasi) ? trim($lokasi->kode_lokasi) : (string) $lokasi->id) : '000';
            $kodeKeempat = str_pad($kodeLok, 3, '0', STR_PAD_LEFT);
        }

        // Komponen ke-5: Divisi (1 digit)
        $divisi = $targetDivisiId ? Divisi::find($targetDivisiId) : null;
        $kodeDivisi = $divisi ? substr(trim($divisi->kode_divisi), 0, 1) : '1';

        // Komponen 6 & 7: Cara & Status Perolehan
        $caraPerolehan = (string) ($aset->cara_perolehan ?: ($parsed['cara_perolehan'] ?? '1'));
        if (! in_array($caraPerolehan, ['1', '2'], true)) {
            $caraPerolehan = '1';
        }

        $statusBarang = (string) ($aset->status_barang ?: ($parsed['status_barang'] ?? '1'));
        if (! in_array($statusBarang, ['1', '2'], true)) {
            $statusBarang = '1';
        }

        // Komponen 8: Tahun (4 digit)
        $tahun = $aset->tanggal_pembelian
            ? Carbon::parse($aset->tanggal_pembelian)->format('Y')
            : ($parsed['tahun'] ?? date('Y'));

        // Komponen 9: Nomor Urut asli dipertahankan
        $nomorUrut = $aset->nomor_urut;
        if (! $nomorUrut && ! empty($parsed['nomor_urut'])) {
            $nomorUrut = (int) $parsed['nomor_urut'];
        }
        if (! $nomorUrut) {
            $nomorUrut = (int) substr($aset->kode_aset, -2);
        }
        if (! $nomorUrut || $nomorUrut <= 0) {
            $nomorUrut = 1;
        }
        $urutanPad = str_pad((string) $nomorUrut, 2, '0', STR_PAD_LEFT);

        $newKodeAset = sprintf(
            '%s%s%s%s%s%s%s%s%s',
            $kodeKategori,
            $kodeBarang,
            $sifatBarang,
            $kodeKeempat,
            $kodeDivisi,
            $caraPerolehan,
            $statusBarang,
            $tahun,
            $urutanPad
        );

        $isChanged = ($newKodeAset !== $aset->kode_aset);

        return [
            'changed' => $isChanged,
            'old_code' => $aset->kode_aset,
            'new_code' => $newKodeAset,
            'new_divisi_id' => $targetDivisiId,
            'new_penanggung_jawab_id' => $targetPjId,
            'new_lokasi_id' => $targetLokasiId,
            'sifat_barang' => $sifatBarang,
            'nomor_urut' => $nomorUrut,
        ];
    }

    /**
     * Parse and validate components from input.
     */
    public static function resolveComponents(array $params): array
    {
        $kategoriId = (int) ($params['kategori_id'] ?? 0);
        $barangId = (int) ($params['barang_id'] ?? 0);
        $sifatBarang = strtoupper(trim($params['sifat_barang'] ?? 'D'));
        $divisiId = (int) ($params['divisi_id'] ?? 0);
        $caraPerolehan = (string) ($params['cara_perolehan'] ?? '1');
        $statusBarang = (string) ($params['status_barang'] ?? '1');
        $tanggalPembelian = $params['tanggal_pembelian'] ?? date('Y-m-d');

        if (! in_array($sifatBarang, ['D', 'S'], true)) {
            $sifatBarang = 'D';
        }

        if (! in_array($caraPerolehan, ['1', '2'], true)) {
            $caraPerolehan = '1';
        }

        if (! in_array($statusBarang, ['1', '2'], true)) {
            $statusBarang = '1';
        }

        $kategori = Kategori::find($kategoriId) ?? Kategori::first();
        if (! $kategori) {
            throw new InvalidArgumentException('Master Kategori belum tersedia di database.');
        }

        $barang = Barang::find($barangId) ?? Barang::where('kategori_id', $kategori->id)->first() ?? Barang::first();
        if (! $barang) {
            throw new InvalidArgumentException('Master Barang belum tersedia di database.');
        }

        $divisi = Divisi::find($divisiId) ?? Divisi::first();
        if (! $divisi) {
            throw new InvalidArgumentException('Master Divisi belum tersedia di database.');
        }

        $kodeKategori = strtoupper(substr(trim($kategori->kode_kategori), 0, 2));
        $kodeKategori = str_pad($kodeKategori, 2, 'X');

        $kodeBarang = str_pad(trim($barang->kode_barang), 2, '0', STR_PAD_LEFT);
        $kodeDivisi = substr(trim($divisi->kode_divisi), 0, 1);

        $tahun = Carbon::parse($tanggalPembelian)->format('Y');

        if ($sifatBarang === 'D') {
            $pjId = (int) ($params['penanggung_jawab_id'] ?? 0);
            $pj = PenanggungJawab::find($pjId) ?? PenanggungJawab::first();
            $kodePic = $pj ? (! empty($pj->kode_pic) ? trim($pj->kode_pic) : (string) $pj->id) : '001';
            $kodeKeempat = str_pad($kodePic, 3, '0', STR_PAD_LEFT);
            $labelKeempat = $pj ? "Amil: {$pj->nama} ({$kodeKeempat})" : "Amil Default ({$kodeKeempat})";
        } else {
            $lokasiId = (int) ($params['lokasi_id'] ?? 0);
            $lokasi = Lokasi::find($lokasiId) ?? Lokasi::first();
            $kodeLok = $lokasi ? (! empty($lokasi->kode_lokasi) ? trim($lokasi->kode_lokasi) : (string) $lokasi->id) : '001';
            $kodeKeempat = str_pad($kodeLok, 3, '0', STR_PAD_LEFT);
            $labelKeempat = $lokasi ? "Lokasi: {$lokasi->nama_lokasi} ({$kodeKeempat})" : "Lokasi Default ({$kodeKeempat})";
        }

        return [
            'kategori_id' => $kategori->id,
            'nama_kategori' => $kategori->nama_kategori,
            'kode_kategori' => $kodeKategori,

            'barang_id' => $barang->id,
            'nama_barang' => $barang->nama_barang,
            'kode_barang' => $kodeBarang,

            'sifat_barang' => $sifatBarang,
            'sifat_label' => $sifatBarang === 'D' ? 'Dinamis (Amil)' : 'Statis (Lokasi)',

            'kode_keempat' => $kodeKeempat,
            'label_keempat' => $labelKeempat,

            'divisi_id' => $divisi->id,
            'nama_divisi' => $divisi->nama_divisi,
            'kode_divisi' => $kodeDivisi,

            'cara_perolehan' => $caraPerolehan,
            'cara_perolehan_label' => $caraPerolehan === '1' ? 'Beli' : 'Hibah / Donasi',

            'status_barang' => $statusBarang,
            'status_barang_label' => $statusBarang === '1' ? 'Baru' : 'Second',

            'tahun' => $tahun,
        ];
    }

    /**
     * Hitung nomor urut berikutnya (01 - 99) berdasarkan kombinasi [Kategori + Barang + Divisi + Tahun].
     */
    public static function calculateNextSequence(int $kategoriId, int $barangId, int $divisiId, string $tahun): int
    {
        $maxSequence = Aset::where('kategori_id', $kategoriId)
            ->where('barang_id', $barangId)
            ->where('divisi_id', $divisiId)
            ->whereYear('tanggal_pembelian', $tahun)
            ->max('nomor_urut');

        $next = ($maxSequence !== null) ? ((int) $maxSequence + 1) : 1;

        if ($next > 99) {
            throw new InvalidArgumentException('Batas nomor urut (99) untuk jenis barang, divisi, dan tahun ini telah tercapai.');
        }

        return $next;
    }

    /**
     * Parse string kode aset ke dalam representasi komponen (mendukung format standar 17 digit maupun varian 14-18 digit).
     */
    public static function parse(string $kode): ?array
    {
        $kode = strtoupper(trim($kode));

        // Normalisasi typo prefix & noise pada legacy data
        if (str_starts_with($kode, 'ELK18')) {
            $kode = 'EL18'.substr($kode, 5);
        } elseif (str_starts_with($kode, 'E14') && ! str_starts_with($kode, 'EL14')) {
            $kode = 'EL14'.substr($kode, 3);
        }
        if (str_contains($kode, 'SYBER')) {
            $kode = str_replace('SYBER', '211', $kode);
        }
        if (str_contains($kode, '1332G11')) {
            $kode = str_replace('1332G11', '133211', $kode);
        }

        // Normalisasi lokasi 4-digit dengan leading zero pada Statis: S0111 -> S111, S0112 -> S112
        $kode = preg_replace('/S0(\d{3})/', 'S$1', $kode);

        // 1. Cek format standar 17 karakter: [Kat:2][Brg:2][Sifat:1][Loc/PIC:3][Div:1][Cara:1][Status:1][Thn:4][Urut:2]
        if (strlen($kode) === 17 && preg_match('/^(?P<kat>[A-Z]{2})(?P<brg>\d{2})(?P<sifat>[DS])(?P<keempat>\d{3})(?P<div>[1-6])(?P<cara>[12])(?P<status>[12])(?P<thn>19\d{2}|20\d{2})(?P<urut>\d{2})$/', $kode, $m)) {
            return [
                'kode_kategori' => $m['kat'],
                'kode_barang' => $m['brg'],
                'sifat_barang' => $m['sifat'],
                'kode_keempat' => $m['keempat'],
                'kode_divisi' => $m['div'],
                'cara_perolehan' => $m['cara'],
                'status_barang' => $m['status'],
                'tahun' => $m['thn'],
                'nomor_urut' => $m['urut'],
                'kode_resmi' => $kode,
            ];
        }

        // 2. Cek format extended combined legacy (panjang >= 19): [Kat:2][Brg:2][Sifat:1][p1:3][Div:1][p2:3]...
        if (strlen($kode) >= 19 && preg_match('/^(?P<kat>[A-Z]{2})(?P<brg>\d{2})(?P<sifat>[DS])(?P<p1>\d{3})(?P<div>[1-6])(?P<p2>\d{3})(?:(?P<cara>[0-2]))?(?:(?P<status>[0-2]))?(?P<thn>19\d{2}|20\d{2})(?:(?P<urut>\d{1,2}))?$/', $kode, $m)) {
            $kat = $m['kat'];
            $brg = $m['brg'];
            $sifat = $m['sifat'];
            $p1 = $m['p1'];
            $div = $m['div'];
            $p2 = $m['p2'];
            $cara = (! empty($m['cara']) && in_array($m['cara'], ['1', '2'], true)) ? $m['cara'] : '1';
            $status = (! empty($m['status']) && in_array($m['status'], ['1', '2'], true)) ? $m['status'] : '1';
            $thn = $m['thn'];
            $urut = ! empty($m['urut']) ? str_pad($m['urut'], 2, '0', STR_PAD_LEFT) : '01';

            $pic = $sifat === 'D' ? $p1 : $p2;
            $lokasi = $sifat === 'D' ? $p2 : $p1;
            $keempat = $sifat === 'D' ? $pic : $lokasi;
            $normalized = $kat.$brg.$sifat.$keempat.$div.$cara.$status.$thn.$urut;

            return [
                'kode_kategori' => $kat,
                'kode_barang' => $brg,
                'sifat_barang' => $sifat,
                'kode_keempat' => $keempat,
                'kode_pic' => $pic,
                'kode_lokasi' => $lokasi,
                'kode_divisi' => $div,
                'cara_perolehan' => $cara,
                'status_barang' => $status,
                'tahun' => $thn,
                'nomor_urut' => $urut,
                'kode_resmi' => $normalized,
            ];
        }

        // 3. Cek format kendaraan legacy khusus: KD01D0503000220115 / KD01D050...
        if (preg_match('/^(?P<kat>KD)(?P<brg>\d{2})(?P<sifat>[DS])(?P<pic>\d{3})(?P<lokasi>\d{3,4})(?:[0-2])?(?:[0-2])?(?P<thn>19\d{2}|20\d{2})(?:(?P<urut>\d{1,2}))?$/', $kode, $m)) {
            $urut = ! empty($m['urut']) ? str_pad($m['urut'], 2, '0', STR_PAD_LEFT) : '01';
            $normalized = 'KD01D050211'.$m['thn'].$urut;

            return [
                'kode_kategori' => 'KD',
                'kode_barang' => '01',
                'sifat_barang' => 'D',
                'kode_keempat' => '050',
                'kode_pic' => '050',
                'kode_lokasi' => substr($m['lokasi'], 0, 3),
                'kode_divisi' => '2', // Suryamin is Divisi 2
                'cara_perolehan' => '1',
                'status_barang' => '1',
                'tahun' => $m['thn'],
                'nomor_urut' => $urut,
                'kode_resmi' => $normalized,
            ];
        }

        // 4. Cek format fleksibel legacy (13 - 18 karakter, urutan di akhir opsional)
        // Pola: [Kat:2][Brg:2][Sifat:1][Keempat:3][Div:1-6][Cara:1-2 optional][Status:1-2 optional][Thn:4][Urut:1-2 optional]
        if (preg_match('/^(?P<kat>[A-Z]{2})(?P<brg>\d{2})(?P<sifat>[DS])(?P<keempat>\d{3})(?P<div>[1-6])(?:(?P<cara>[12]))?(?:(?P<status>[12]))?(?P<thn>19\d{2}|20\d{2})(?:(?P<urut>\d{1,2}))?$/', $kode, $m)) {
            $cara = (! empty($m['cara']) && in_array($m['cara'], ['1', '2'], true)) ? $m['cara'] : '1';
            $status = (! empty($m['status']) && in_array($m['status'], ['1', '2'], true)) ? $m['status'] : '1';
            $urut = ! empty($m['urut']) ? str_pad($m['urut'], 2, '0', STR_PAD_LEFT) : '01';
            $normalized = $m['kat'].$m['brg'].$m['sifat'].$m['keempat'].$m['div'].$cara.$status.$m['thn'].$urut;

            return [
                'kode_kategori' => $m['kat'],
                'kode_barang' => $m['brg'],
                'sifat_barang' => $m['sifat'],
                'kode_keempat' => $m['keempat'],
                'kode_divisi' => $m['div'],
                'cara_perolehan' => $cara,
                'status_barang' => $status,
                'tahun' => $m['thn'],
                'nomor_urut' => $urut,
                'kode_resmi' => $normalized,
            ];
        }

        // 5. Cek format kombinasi Lokasi(3 digit) + PIC(2-3 digit) + Divisi(1 digit):
        // Contoh: EL14S20088611202314, EL14D200935202514, EL14D520693201914
        if (preg_match('/^(?P<kat>[A-Z]{2})(?P<brg>\d{2})(?P<sifat>[DS])(?P<lokasi>\d{3})(?P<pic>\d{2,3}?)(?P<div>[1-6])(?:(?P<cara>[0-2]))?(?:(?P<status>[0-2]))?(?P<thn>19\d{2}|20\d{2})(?:(?P<urut>\d{1,2}))?$/', $kode, $m)) {
            $kat = $m['kat'];
            $brg = $m['brg'];
            $sifat = $m['sifat'];
            $lokasi = $m['lokasi'];
            $pic = str_pad($m['pic'], 3, '0', STR_PAD_LEFT);
            $div = $m['div'];
            $cara = (! empty($m['cara']) && in_array($m['cara'], ['1', '2'], true)) ? $m['cara'] : '1';
            $status = (! empty($m['status']) && in_array($m['status'], ['1', '2'], true)) ? $m['status'] : '1';
            $thn = $m['thn'];
            $urut = ! empty($m['urut']) ? str_pad($m['urut'], 2, '0', STR_PAD_LEFT) : '01';

            $keempat = $sifat === 'D' ? $pic : $lokasi;
            $normalized = $kat.$brg.$sifat.$keempat.$div.$cara.$status.$thn.$urut;

            return [
                'kode_kategori' => $kat,
                'kode_barang' => $brg,
                'sifat_barang' => $sifat,
                'kode_keempat' => $keempat,
                'kode_pic' => $pic,
                'kode_lokasi' => $lokasi,
                'kode_divisi' => $div,
                'cara_perolehan' => $cara,
                'status_barang' => $status,
                'tahun' => $thn,
                'nomor_urut' => $urut,
                'kode_resmi' => $normalized,
            ];
        }

        // 6. Cek format 15 karakter lama: [Kat:2][Brg:2][Sifat:1][Keempat:3][Div:1][Cara:1][Thn:4][Urut:1-2 optional]
        if (preg_match('/^(?P<kat>[A-Z]{2})(?P<brg>\d{2})(?P<sifat>[DS])(?P<keempat>\d{3})(?P<div>[1-6])(?P<cara>[12])(?P<thn>19\d{2}|20\d{2})(?:(?P<urut>\d{1,2}))?$/', $kode, $m)) {
            $urut = ! empty($m['urut']) ? str_pad($m['urut'], 2, '0', STR_PAD_LEFT) : '01';
            $normalized = $m['kat'].$m['brg'].$m['sifat'].$m['keempat'].$m['div'].$m['cara'].'1'.$m['thn'].$urut;

            return [
                'kode_kategori' => $m['kat'],
                'kode_barang' => $m['brg'],
                'sifat_barang' => $m['sifat'],
                'kode_keempat' => $m['keempat'],
                'kode_divisi' => $m['div'],
                'cara_perolehan' => $m['cara'],
                'status_barang' => '1',
                'tahun' => $m['thn'],
                'nomor_urut' => $urut,
                'kode_resmi' => $normalized,
            ];
        }

        return null;
    }
}
