<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\PenanggungJawab;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class AsetFuzzyMatcher
{
    /**
     * Cocokkan teks mentah kategori ke model Kategori.
     */
    public static function matchKategori(?string $raw): ?Kategori
    {
        if (empty($raw)) {
            return null;
        }

        $cleaned = strtoupper(trim($raw));

        // 1. Cek kode kategori persis (EL, FN, KD)
        if (in_array($cleaned, ['EL', 'FN', 'KD'], true)) {
            return Kategori::where('kode_kategori', $cleaned)->first();
        }

        // 2. Cek keyword dengan word boundary agar tidak salah cocok (misal: "IT" di dalam "FURNITURE")
        if (preg_match('/\b(ELEKTRO|ELEKTRONIK|KOMPUTER|IT|EL)\b/i', $cleaned)) {
            return Kategori::where('kode_kategori', 'EL')->first();
        }

        if (preg_match('/\b(FURNI|FURNITURE|MEBEL|MEJA|KURSI|FN)\b/i', $cleaned)) {
            return Kategori::where('kode_kategori', 'FN')->first();
        }

        if (preg_match('/\b(KENDARAAN|MOTOR|MOBIL|SEPEDA|KD)\b/i', $cleaned)) {
            return Kategori::where('kode_kategori', 'KD')->first();
        }

        // 3. Fallback search nama_kategori
        return Kategori::where('nama_kategori', 'LIKE', "%{$raw}%")->first();
    }

    /**
     * Cocokkan nama aset / nama barang mentah ke model Barang (diprioritaskan dalam Kategori).
     */
    public static function matchBarang(string $namaAset, ?int $kategoriId = null): ?Barang
    {
        $query = Barang::query();
        if ($kategoriId) {
            $query->where('kategori_id', $kategoriId);
        }

        $barangList = $query->get();
        $cleaned = strtoupper(trim($namaAset));

        // 1. Cek kecocokan kata persis / substring pada nama barang
        $bestMatch = null;
        $highestScore = 0;

        foreach ($barangList as $b) {
            $bName = strtoupper($b->nama_barang);

            // Exact match
            if ($cleaned === $bName) {
                return $b;
            }

            // Word boundary match (e.g. "AC SPLIT" matches "AC")
            if (preg_match('/\b'.preg_quote($bName, '/').'\b/i', $cleaned)) {
                $score = strlen($bName) * 10;
                if ($score > $highestScore) {
                    $highestScore = $score;
                    $bestMatch = $b;
                }
            } elseif (str_contains($cleaned, $bName)) {
                $score = strlen($bName);
                if ($score > $highestScore) {
                    $highestScore = $score;
                    $bestMatch = $b;
                }
            }
        }

        if ($bestMatch) {
            return $bestMatch;
        }

        // 2. Alias pemetaan umum
        $aliasMap = [
            'NOTEBOOK' => 'Laptop',
            'KOMPUTER' => 'Komputer PC',
            'PC' => 'Komputer PC',
            'LCD' => 'LED',
            'MONITOR' => 'LED',
            'TAB' => 'Smartphone Tab',
            'IPAD' => 'Smartphone Tab',
            'HANDPHONE' => 'Smartphone',
            'HP' => 'Smartphone',
            'INFOCUS' => 'Proyektor',
            'PROJECTOR' => 'Proyektor',
            'KAMERA' => 'Kamera SLR/DSLR',
            'DSLR' => 'Kamera SLR/DSLR',
            'SOUND' => 'Mixer Sound',
            'SPEAKER' => 'Portable Speaker',
            'MIC' => 'Mic',
            'MEJA' => 'Meja kerja',
            'KURSI' => 'Kursi Kerja',
            'LEMARI' => 'Lemari',
            'ROUTER' => 'Router Wireless',
            'WIFI' => 'Router Wireless',
            'BRANGKAS' => 'Brankas',
            'CAMERA' => 'Kamera SLR/DSLR',
            'ACTION CAM' => 'Kamera SLR/DSLR',
            'PENGERAS SUARA' => 'Megaphone',
            'STORAGE' => 'Peti',
            'RAK KAYU' => 'Peti',
            'RAK SEPATU' => 'Rak Sepatu',
            'LEMARI KAYU' => 'Lemari',
            'LEMARI TEMPEL' => 'Lemari',
            'LEMARI KERJA' => 'Lemari',
            'TEMPAT SAMPAH' => 'Tong Sampah',
            'IPHONE' => 'Smartphone',
            'SMART PHONE' => 'Smartphone',
            'PC ALL IN ONE' => 'Komputer PC',
            'SMART TV' => 'TV',
            'TELEVISI' => 'TV',
            'HANDYCAM' => 'Handycam',
            'AMPLIFIER' => 'Amplifier',
            'EXT' => 'Hardisk eksternal',
            'HDD' => 'Hardisk eksternal',
            'FLASH' => 'Lampu Flash',
        ];

        foreach ($aliasMap as $keyword => $targetBarang) {
            if (preg_match('/\b'.preg_quote($keyword, '/').'\b/i', $cleaned) || str_contains($cleaned, $keyword)) {
                $candidate = $barangList->first(fn ($item) => strcasecmp($item->nama_barang, $targetBarang) === 0);
                if ($candidate) {
                    return $candidate;
                }
                // jika belum ketemu di kategori terpilih, cari global
                $globalCandidate = Barang::where('nama_barang', 'LIKE', "%{$targetBarang}%")->first();
                if ($globalCandidate) {
                    return $globalCandidate;
                }
            }
        }

        return null;
    }

    /**
     * Cocokkan teks PIC mentah ke PenanggungJawab (NIA 3 digit).
     */
    public static function matchPenanggungJawab(?string $raw): ?PenanggungJawab
    {
        if (empty($raw) || in_array(trim($raw), ['-', 'None', 'null', ''], true)) {
            return null;
        }

        $cleaned = trim($raw);

        // 1. Cek jika raw mengandung 3 digit kode_pic (contoh: 050 atau (050))
        if (preg_match('/\b(\d{3})\b/', $cleaned, $matches)) {
            $pj = PenanggungJawab::where('kode_pic', $matches[1])->first();
            if ($pj) {
                return $pj;
            }
        }

        // 2. Exact match nama
        $pj = PenanggungJawab::whereRaw('LOWER(nama) = ?', [strtolower($cleaned)])->first();
        if ($pj) {
            return $pj;
        }

        // 3. Like match nama (substring nama depan / nama lengkap)
        $candidates = PenanggungJawab::where('nama', 'LIKE', "%{$cleaned}%")
            ->orWhereRaw('? LIKE CONCAT("%", nama, "%")', [$cleaned])
            ->get();

        if ($candidates->count() === 1) {
            return $candidates->first();
        }

        // 4. Tokenize search (kata per kata)
        $words = preg_split('/\s+/', $cleaned);
        foreach ($words as $word) {
            if (strlen($word) >= 4) {
                $match = PenanggungJawab::where('nama', 'LIKE', "%{$word}%")->first();
                if ($match) {
                    return $match;
                }
            }
        }

        return null;
    }

    /**
     * Cocokkan teks Lokasi / Deskripsi mentah ke model Lokasi (Kode 3 digit).
     */
    public static function matchLokasi(?string $lokasiRaw, ?string $deskripsiRaw = null): ?Lokasi
    {
        $combined = trim(($lokasiRaw ?? '').' '.($deskripsiRaw ?? ''));
        if (empty($combined)) {
            return null;
        }

        // 1. Cek jika ada kode lokasi 3 digit eksplisit (100 - 640)
        if (preg_match('/\b([1-6]\d{2})\b/', $combined, $matches)) {
            $lokasi = Lokasi::where('kode_lokasi', $matches[1])->first();
            if ($lokasi) {
                return $lokasi;
            }
        }

        // 2. Cek keyword spesifik
        $keywords = [
            'LOBI' => '111',
            'RAPAT' => '112',
            'FUNDRAISING' => '113',
            'DIREKSI' => '114',
            'DIREKTUR' => '114',
            'PANTRY' => '115',
            'GUDANG' => '117',
            'KEUANGAN' => '121',
            'PROGRAM' => '122',
            'HRD' => '131',
            'CRM' => '132',
            'KELEMBAGAAN' => '133',
            'ROOFTOP' => '140',
            'KLB' => '200',
            'SAWANGAN' => '300',
            'RGI' => '300',
            'DASAMAS' => '400',
            'CIRENDEU' => '100',
        ];

        $upperCombined = strtoupper($combined);

        foreach ($keywords as $word => $targetCode) {
            if (str_contains($upperCombined, $word)) {
                $lokasi = Lokasi::where('kode_lokasi', $targetCode)->first();
                if ($lokasi) {
                    return $lokasi;
                }
            }
        }

        // 3. Fallback search nama_lokasi
        return Lokasi::where('nama_lokasi', 'LIKE', "%{$lokasiRaw}%")->first();
    }

    /**
     * Parse tanggal dari format Excel Serial atau string Indonesia.
     */
    public static function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Jika numeric integer/float besar (Excel timestamp format)
        if (is_numeric($value) && (float) $value > 20000 && (float) $value < 90000) {
            try {
                if (class_exists(ExcelDate::class)) {
                    $dateTime = ExcelDate::excelToDateTimeObject((float) $value);

                    return $dateTime->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $str = trim((string) $value);

        // Map nama bulan Indonesia ke Inggris
        $bulanIndo = [
            'Januari' => 'January', 'Jan' => 'Jan',
            'Februari' => 'February', 'Feb' => 'Feb',
            'Maret' => 'March', 'Mar' => 'Mar',
            'April' => 'April', 'Apr' => 'Apr',
            'Mei' => 'May',
            'Juni' => 'June', 'Jun' => 'Jun',
            'Juli' => 'July', 'Jul' => 'Jul',
            'Agustus' => 'August', 'Agu' => 'Aug', 'Ags' => 'Aug',
            'September' => 'September', 'Sep' => 'Sep',
            'Oktober' => 'October', 'Okt' => 'Oct',
            'November' => 'November', 'Nov' => 'Nov',
            'Desember' => 'December', 'Des' => 'Dec',
        ];

        foreach ($bulanIndo as $id => $en) {
            $str = preg_replace('/\b'.preg_quote($id, '/').'\b/i', $en, $str);
        }

        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Normalisasi nilai rupiah / angka numerik dari string Excel.
     */
    public static function parseNumeric($value, float $default = 0.0): float
    {
        if (empty($value)) {
            return $default;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $cleaned = trim((string) $value);
        // Hapus Rp, spasi, titik (pemisah ribuan) jika format indonesia
        $cleaned = preg_replace('/[^\d.,]/', '', $cleaned);

        // Jika ada titik dan koma (cth: 1.250.000,00)
        if (str_contains($cleaned, '.') && str_contains($cleaned, ',')) {
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        } elseif (str_contains($cleaned, '.')) {
            // Cek apakah titik pemisah ribuan (e.g. 1.500.000)
            if (preg_match('/\.\d{3}/', $cleaned)) {
                $cleaned = str_replace('.', '', $cleaned);
            }
        } elseif (str_contains($cleaned, ',')) {
            // Koma sebagai desimal (e.g. 1500000,50) atau ribuan
            if (preg_match('/,\d{3}/', $cleaned)) {
                $cleaned = str_replace(',', '', $cleaned);
            } else {
                $cleaned = str_replace(',', '.', $cleaned);
            }
        }

        return is_numeric($cleaned) ? (float) $cleaned : $default;
    }
}
