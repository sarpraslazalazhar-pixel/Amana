<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\PenanggungJawab;
use App\Services\KalenderAsetService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class KalenderAsetController extends Controller
{
    protected KalenderAsetService $kalenderService;

    public function __construct(KalenderAsetService $kalenderService)
    {
        $this->kalenderService = $kalenderService;
    }

    /**
     * Halaman Utama Kalender Aset (Dual View: Grid Kalender + Tabel Rekap Harian)
     */
    public function index(Request $request)
    {
        $now = Carbon::now();
        $year = (int) $request->input('year', $now->year);
        $month = (int) $request->input('month', $now->month);

        // Validasi input tahun & bulan
        if ($year < 2000 || $year > 2100) {
            $year = $now->year;
        }
        if ($month < 1 || $month > 12) {
            $month = $now->month;
        }

        // Tipe event aktif yang difilter (agenda, jurnal, keuangan, riwayat)
        $defaultTypes = ['agenda', 'jurnal', 'keuangan', 'riwayat'];
        $types = $request->input('types', $defaultTypes);
        if (! is_array($types)) {
            $types = explode(',', (string) $types);
        }
        $types = array_intersect($types, $defaultTypes);
        if (empty($types)) {
            $types = $defaultTypes;
        }

        $filters = [
            'types' => $types,
            'kategori_id' => $request->input('kategori_id'),
            'lokasi_id' => $request->input('lokasi_id'),
            'penanggung_jawab_id' => $request->input('penanggung_jawab_id'),
            'aset_id' => $request->input('aset_id'),
            'q' => $request->input('q'),
        ];

        $kalenderData = $this->kalenderService->getKalenderBulan($year, $month, $filters);

        // Data master untuk opsi filter di toolbar
        $kategoriList = Kategori::orderBy('nama_kategori')->get();
        $lokasiList = Lokasi::orderBy('nama_lokasi')->get();
        $pjList = PenanggungJawab::orderBy('nama')->get();

        return view('kalender.index', array_merge($kalenderData, [
            'filters' => $filters,
            'selectedTypes' => $types,
            'kategoriList' => $kategoriList,
            'lokasiList' => $lokasiList,
            'pjList' => $pjList,
        ]));
    }
}
