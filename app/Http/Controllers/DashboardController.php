<?php

namespace App\Http\Controllers;

use App\Models\Aset;
use App\Models\AuditLog;
use App\Models\JurnalAset;
use App\Models\Kategori;
use App\Models\KeuanganAset;
use App\Models\RiwayatAset;
use App\Services\PenyusutanCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // =========================================================================
        // 1. DATA RANGKUMAN (SUMMARY KPI)
        // =========================================================================
        $asetAktifQuery = Aset::where('status', 'aktif');
        $totalAktif = (clone $asetAktifQuery)->count();
        $totalTetap = (clone $asetAktifQuery)->where('jenis', 'tetap')->count();
        $totalKelolaan = (clone $asetAktifQuery)->where('jenis', 'kelolaan')->count();
        $totalNonAktif = Aset::where('status', 'non_aktif')->count();

        // Total Nilai Awal (Perolehan)
        $nilaiAwalTetap = (float) (clone $asetAktifQuery)->where('jenis', 'tetap')->sum('harga_total');
        $nilaiAwalKelolaan = (float) (clone $asetAktifQuery)->where('jenis', 'kelolaan')->sum('harga_total');
        $totalNilaiAwal = $nilaiAwalTetap + $nilaiAwalKelolaan;

        // Penyusutan Akhir Bulan (Beban Bulanan Berjalan)
        $penyusutanBulanTetap = (float) (clone $asetAktifQuery)->where('jenis', 'tetap')->sum('penyusutan_per_bulan');
        $penyusutanBulanKelolaan = (float) (clone $asetAktifQuery)->where('jenis', 'kelolaan')->sum('penyusutan_per_bulan');
        $totalPenyusutanBulan = $penyusutanBulanTetap + $penyusutanBulanKelolaan;

        // Total Nilai Sekarang (Nilai Buku Saat Ini) & Akumulasi Penyusutan
        $asetAktifList = (clone $asetAktifQuery)->get();
        $nilaiSekarangTetap = 0.0;
        $nilaiSekarangKelolaan = 0.0;

        foreach ($asetAktifList as $aset) {
            $nb = PenyusutanCalculator::hitungNilaiBukuSaatIni($aset);
            if ($aset->jenis === 'tetap') {
                $nilaiSekarangTetap += $nb;
            } else {
                $nilaiSekarangKelolaan += $nb;
            }
        }

        $totalNilaiSekarang = $nilaiSekarangTetap + $nilaiSekarangKelolaan;
        $akumulasiTetap = max(0, $nilaiAwalTetap - $nilaiSekarangTetap);
        $akumulasiKelolaan = max(0, $nilaiAwalKelolaan - $nilaiSekarangKelolaan);
        $totalAkumulasiPenyusutan = $akumulasiTetap + $akumulasiKelolaan;

        $persentaseNilaiBuku = $totalNilaiAwal > 0
            ? round(($totalNilaiSekarang / $totalNilaiAwal) * 100, 1)
            : 100.0;

        $persentasePenyusutan = $totalNilaiAwal > 0
            ? round(($totalAkumulasiPenyusutan / $totalNilaiAwal) * 100, 1)
            : 0.0;

        // =========================================================================
        // 2. DATA GRAFIK 1: ASET TETAP & KELOLAAN PER KATEGORI (UNIT & NILAI RUPIAH)
        // =========================================================================
        $kategoriList = Kategori::orderBy('nama_kategori')->get();
        $chartKategoriLabels = [];
        $chartKategoriJumlahTetap = [];
        $chartKategoriJumlahKelolaan = [];
        $chartKategoriNilaiTetap = [];
        $chartKategoriNilaiKelolaan = [];

        $kategoriAggregates = DB::table('aset')
            ->select('kategori_id', 'jenis', DB::raw('COUNT(*) as total_unit'), DB::raw('SUM(harga_total) as total_harga'))
            ->where('status', 'aktif')
            ->groupBy('kategori_id', 'jenis')
            ->get()
            ->groupBy('kategori_id');

        foreach ($kategoriList as $kat) {
            $agg = $kategoriAggregates->get($kat->id, collect());
            $tetapItem = $agg->firstWhere('jenis', 'tetap');
            $kelolaanItem = $agg->firstWhere('jenis', 'kelolaan');

            $jTetap = $tetapItem ? (int) $tetapItem->total_unit : 0;
            $jKelolaan = $kelolaanItem ? (int) $kelolaanItem->total_unit : 0;
            $nTetap = $tetapItem ? (float) $tetapItem->total_harga : 0.0;
            $nKelolaan = $kelolaanItem ? (float) $kelolaanItem->total_harga : 0.0;

            $chartKategoriLabels[] = $kat->nama_kategori;
            $chartKategoriJumlahTetap[] = $jTetap;
            $chartKategoriJumlahKelolaan[] = $jKelolaan;
            $chartKategoriNilaiTetap[] = round($nTetap);
            $chartKategoriNilaiKelolaan[] = round($nKelolaan);
        }

        // =========================================================================
        // 3. PERSENTASE PERUBAHAN PER BULAN (MoM GROWTH)
        // =========================================================================
        $startThisMonth = Carbon::now()->startOfMonth();
        $endThisMonth = Carbon::now()->endOfMonth();
        $startLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endLastMonth = Carbon::now()->subMonth()->endOfMonth();

        $unitThisMonth = Aset::whereBetween('tanggal_pembelian', [$startThisMonth, $endThisMonth])->count();
        $unitLastMonth = Aset::whereBetween('tanggal_pembelian', [$startLastMonth, $endLastMonth])->count();
        $nilaiThisMonth = (float) Aset::whereBetween('tanggal_pembelian', [$startThisMonth, $endThisMonth])->sum('harga_total');
        $nilaiLastMonth = (float) Aset::whereBetween('tanggal_pembelian', [$startLastMonth, $endLastMonth])->sum('harga_total');

        $unitChangePct = $unitLastMonth > 0
            ? round((($unitThisMonth - $unitLastMonth) / $unitLastMonth) * 100, 1)
            : ($unitThisMonth > 0 ? 100.0 : 0.0);

        $nilaiChangePct = $nilaiLastMonth > 0
            ? round((($nilaiThisMonth - $nilaiLastMonth) / $nilaiLastMonth) * 100, 1)
            : ($nilaiThisMonth > 0 ? 100.0 : 0.0);

        $momGrowth = [
            'unit_this_month' => $unitThisMonth,
            'unit_last_month' => $unitLastMonth,
            'unit_change_pct' => $unitChangePct,
            'nilai_this_month' => $nilaiThisMonth,
            'nilai_last_month' => $nilaiLastMonth,
            'nilai_change_pct' => $nilaiChangePct,
        ];

        // =========================================================================
        // 4. DATA GRAFIK 2: TOTAL NILAI ASET TETAP VS KELOLAAN (DENGAN PENYUSUTAN)
        // =========================================================================
        $chartNilaiPenyusutan = [
            'labels' => ['Aset Tetap', 'Aset Kelolaan'],
            'nilai_awal' => [round($nilaiAwalTetap), round($nilaiAwalKelolaan)],
            'penyusutan' => [round($akumulasiTetap), round($akumulasiKelolaan)],
            'nilai_sekarang' => [round($nilaiSekarangTetap), round($nilaiSekarangKelolaan)],
        ];

        // =========================================================================
        // 5. DATA 5 WIDGET AKTIVITAS TERBARU
        // =========================================================================
        // Widget 1: 5 Aset Terbaru
        $asetTerbaru = Aset::with(['kategori', 'lokasi', 'penanggungJawab'])
            ->latest('created_at')
            ->take(5)
            ->get()
            ->each(fn (Aset $aset) => $aset->nilai_buku = PenyusutanCalculator::hitungNilaiBukuSaatIni($aset));

        // Widget 2: 5 Riwayat Mutasi Terbaru (Pindah Penanggung Jawab)
        $riwayatTerbaru = RiwayatAset::with(['aset.kategori', 'penanggungJawab', 'lokasi', 'user'])
            ->latest('created_at')
            ->take(5)
            ->get();

        // Widget 3: 5 Catatan Pengeluaran & Biaya Aset Terbaru
        $keuanganTerbaru = KeuanganAset::with(['aset', 'user'])
            ->latest('created_at')
            ->take(5)
            ->get();

        // Widget 4: 5 Jurnal Terakhir (Ditambah atau Diubah)
        $jurnalTerakhir = JurnalAset::with(['aset', 'user'])
            ->latest('updated_at')
            ->take(5)
            ->get();

        // Widget 5: 5 Jejak Audit Sistem Terbaru
        $auditLogTerbaru = AuditLog::with(['user', 'aset'])
            ->latest('created_at')
            ->take(5)
            ->get();

        return view('dashboard.index', compact(
            'totalAktif',
            'totalTetap',
            'totalKelolaan',
            'totalNonAktif',
            'totalNilaiAwal',
            'nilaiAwalTetap',
            'nilaiAwalKelolaan',
            'totalNilaiSekarang',
            'nilaiSekarangTetap',
            'nilaiSekarangKelolaan',
            'totalAkumulasiPenyusutan',
            'akumulasiTetap',
            'akumulasiKelolaan',
            'totalPenyusutanBulan',
            'penyusutanBulanTetap',
            'penyusutanBulanKelolaan',
            'persentaseNilaiBuku',
            'persentasePenyusutan',
            'chartKategoriLabels',
            'chartKategoriJumlahTetap',
            'chartKategoriJumlahKelolaan',
            'chartKategoriNilaiTetap',
            'chartKategoriNilaiKelolaan',
            'momGrowth',
            'chartNilaiPenyusutan',
            'asetTerbaru',
            'riwayatTerbaru',
            'keuanganTerbaru',
            'jurnalTerakhir',
            'auditLogTerbaru'
        ));
    }
}
