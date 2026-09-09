<?php

namespace App\Http\Controllers;

use App\Models\Aset;
use App\Models\QrConfig;
use App\Models\RiwayatAset;
use App\Services\PenyusutanCalculator;
use Carbon\Carbon;

class PublicQrController extends Controller
{
    /**
     * Halaman Publik Scan QR Code tanpa login
     * Akses via /p/{kode_aset}
     */
    public function show($kode_aset)
    {
        $withRelations = [
            'kategori',
            'merk',
            'divisi',
            'lokasi',
            'penanggungJawab',
            'riwayat' => function ($q) {
                $q->with(['penanggungJawab', 'lokasi', 'divisi'])->orderBy('sejak_tanggal', 'desc')->orderBy('id', 'desc');
            },
            'agenda' => function ($q) {
                $q->orderBy('created_at', 'desc');
            },
            'keuangan' => function ($q) {
                $q->orderBy('tanggal', 'desc');
            },
            'jurnal' => function ($q) {
                $q->orderBy('tanggal', 'desc');
            },
        ];

        $isMutasiRedirect = false;
        $oldScannedCode = null;

        $aset = Aset::with($withRelations)
            ->where('kode_aset', $kode_aset)
            ->first();

        // Jika tidak ditemukan pada kode_aset saat ini, cari di riwayat mutasi / kode lama
        if (! $aset) {
            $riwayatOld = RiwayatAset::where('kode_aset_sebelumnya', $kode_aset)->first();
            if ($riwayatOld) {
                $aset = Aset::with($withRelations)->find($riwayatOld->aset_id);
                $isMutasiRedirect = true;
                $oldScannedCode = $kode_aset;
            } else {
                $aset = Aset::with($withRelations)
                    ->where('kode_aset_lama', $kode_aset)
                    ->orWhere('kode_sistem_lama', $kode_aset)
                    ->first();
                if ($aset) {
                    $isMutasiRedirect = true;
                    $oldScannedCode = $kode_aset;
                }
            }
        }

        if (! $aset) {
            abort(404, "Aset dengan kode '{$kode_aset}' tidak ditemukan.");
        }

        $config = QrConfig::getAllConfig();

        // Hitung Usia Aset berjalan
        $usiaAset = null;
        if ($aset->tanggal_pembelian) {
            $tglBeli = Carbon::parse($aset->tanggal_pembelian);
            $usiaTahun = $tglBeli->diffInYears(now());
            $usiaBulan = $tglBeli->diffInMonths(now()) % 12;
            $usiaAset = ($usiaTahun > 0 ? "{$usiaTahun} Tahun " : '')."{$usiaBulan} Bulan";
        }

        // Hitung Nilai Sekarang / Nilai Buku
        $nilaiBuku = $config['show_nilai_sekarang']
            ? PenyusutanCalculator::hitungNilaiBukuSaatIni($aset)
            : null;

        return view('public.qr-view', compact('aset', 'config', 'usiaAset', 'nilaiBuku', 'isMutasiRedirect', 'oldScannedCode'));
    }
}
