<?php

namespace App\Services;

use App\Models\Aset;
use Carbon\Carbon;

class PenyusutanCalculator
{
    /**
     * Hitung Harga Total (Jumlah Unit * Harga Satuan)
     */
    public static function hitungHargaTotal(int $jumlahUnit, float $hargaSatuan): float
    {
        return $jumlahUnit * $hargaSatuan;
    }

    /**
     * Hitung Penyusutan per Bulan (Garis Lurus)
     * Penyusutan/Bulan = (Harga Total - Nilai Residu) / (Umur Ekonomis Tahun * 12)
     */
    public static function hitungPenyusutanPerBulan(float $hargaTotal, int $umurEkonomisTahun, float $nilaiResidu = 0): float
    {
        $totalBulan = $umurEkonomisTahun * 12;
        if ($totalBulan <= 0) {
            return 0;
        }

        $nilaiYangDisusutkan = max(0, $hargaTotal - $nilaiResidu);

        return round($nilaiYangDisusutkan / $totalBulan, 2);
    }

    /**
     * Hitung Nilai Buku Saat Ini
     * Nilai Buku = Harga Total - (Penyusutan per Bulan * Jumlah Bulan Berjalan)
     */
    public static function hitungNilaiBukuSaatIni(Aset $aset): float
    {
        if ($aset->status === 'non_aktif') {
            // Jika non-aktif, penyusutan dibekukan
            $tglAcuan = $aset->updated_at ? Carbon::parse($aset->updated_at) : Carbon::now();
        } else {
            $tglAcuan = Carbon::now();
        }

        $tglBeli = Carbon::parse($aset->tanggal_pembelian);
        $nilaiResidu = (float) ($aset->nilai_residu ?? 0);

        if ($tglAcuan->lessThanOrEqualTo($tglBeli)) {
            return (float) $aset->harga_total;
        }

        $bulanBerjalan = $tglBeli->diffInMonths($tglAcuan);
        $totalPenyusutan = $aset->penyusutan_per_bulan * $bulanBerjalan;

        $nilaiBuku = $aset->harga_total - $totalPenyusutan;

        // Nilai buku tidak boleh lebih rendah dari nilai residu/sisa
        return max($nilaiResidu, round($nilaiBuku, 2));
    }

    /**
     * Hitung Akumulasi Total Penyusutan Berjalan
     */
    public static function hitungTotalPenyusutanBerjalan(Aset $aset): float
    {
        $nilaiBuku = static::hitungNilaiBukuSaatIni($aset);

        return max(0, round($aset->harga_total - $nilaiBuku, 2));
    }

    /**
     * Format Usia Aset Berdasarkan Tanggal Pembelian
     * Contoh: "5 Tahun 7 Bulan", "2 Bulan", atau "Kurang dari 1 bulan"
     */
    public static function formatUsiaAset(Carbon|string $tanggalPembelian, ?Carbon $tglAcuan = null): string
    {
        $tglBeli = is_string($tanggalPembelian) ? Carbon::parse($tanggalPembelian) : $tanggalPembelian->copy();
        $tglAcuan = $tglAcuan ?? Carbon::now();

        if ($tglAcuan->lessThanOrEqualTo($tglBeli)) {
            return 'Kurang dari 1 bulan';
        }

        $diff = $tglBeli->diff($tglAcuan);
        $years = $diff->y;
        $months = $diff->m;

        if ($years > 0 && $months > 0) {
            return "{$years} Tahun {$months} Bulan";
        } elseif ($years > 0) {
            return "{$years} Tahun";
        } elseif ($months > 0) {
            return "{$months} Bulan";
        }

        return 'Kurang dari 1 bulan';
    }
}
