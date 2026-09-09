<?php

namespace App\Http\Controllers;

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
use App\Services\AsetExportService;
use App\Services\AsetImportService;
use App\Services\AuditLogger;
use App\Services\KodeAsetGenerator;
use App\Services\PenyusutanCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AsetController extends Controller
{
    public function index()
    {
        return redirect()->route('aset.tetap');
    }

    public function tetap(Request $request)
    {
        return $this->renderGrup($request, 'tetap');
    }

    public function exportTetap(Request $request)
    {
        return AsetExportService::download(array_merge($request->all(), [
            'klasifikasi' => 'tetap',
            'status' => 'aktif',
        ]));
    }

    public function kelolaan(Request $request)
    {
        return $this->renderGrup($request, 'kelolaan');
    }

    public function exportKelolaan(Request $request)
    {
        return AsetExportService::download(array_merge($request->all(), [
            'klasifikasi' => 'kelolaan',
            'status' => 'aktif',
        ]));
    }

    public function nonAktif(Request $request)
    {
        return $this->renderGrup($request, 'non-aktif');
    }

    public function exportNonAktif(Request $request)
    {
        return AsetExportService::download(array_merge($request->all(), [
            'status' => 'non_aktif',
        ]));
    }

    private function grupConfig(string $grup): array
    {
        return match ($grup) {
            'tetap' => [
                'key' => 'tetap',
                'route' => 'aset.tetap',
                'export_route' => 'aset.tetap.export',
                'filters' => ['jenis' => 'tetap', 'status' => 'aktif'],
                'title' => 'Aset Tetap',
                'subtitle' => 'Daftar aset tetap aktif milik Al Azhar Peduli',
                'empty' => 'Belum ada aset tetap yang sesuai filter.',
                'show_status_column' => true,
            ],
            'kelolaan' => [
                'key' => 'kelolaan',
                'route' => 'aset.kelolaan',
                'export_route' => 'aset.kelolaan.export',
                'filters' => ['jenis' => 'kelolaan', 'status' => 'aktif'],
                'title' => 'Aset Kelolaan',
                'subtitle' => 'Daftar aset kelolaan aktif milik Al Azhar Peduli',
                'empty' => 'Belum ada aset kelolaan yang sesuai filter.',
                'show_status_column' => true,
            ],
            'non-aktif' => [
                'key' => 'non-aktif',
                'route' => 'aset.nonAktif',
                'export_route' => 'aset.nonAktif.export',
                'filters' => ['status' => 'non_aktif'],
                'title' => 'Aset Non Aktif',
                'subtitle' => 'Arsip aset yang sedang tidak digunakan',
                'empty' => 'Tidak ada aset non aktif yang sesuai filter.',
                'show_status_column' => false,
            ],
        };
    }

    private function buildGrupQuery(Request $request, string $grup)
    {
        $config = $this->grupConfig($grup);

        $query = Aset::with(['kategori', 'merk', 'lokasi', 'penanggungJawab']);

        foreach ($config['filters'] as $column => $value) {
            $query->where($column, $value);
        }

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        if ($request->filled('lokasi_id')) {
            $query->where('lokasi_id', $request->lokasi_id);
        }

        if ($request->filled('penanggung_jawab_id')) {
            $query->where('penanggung_jawab_id', $request->penanggung_jawab_id);
        }

        if ($request->filled('tgl_dari')) {
            $query->whereDate('tanggal_pembelian', '>=', $request->tgl_dari);
        }

        if ($request->filled('tgl_sampai')) {
            $query->whereDate('tanggal_pembelian', '<=', $request->tgl_sampai);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama_aset', 'LIKE', "%{$search}%")
                    ->orWhere('kode_aset', 'LIKE', "%{$search}%")
                    ->orWhere('no_seri', 'LIKE', "%{$search}%")
                    ->orWhere('tipe_model', 'LIKE', "%{$search}%")
                    ->orWhereHas('merk', function ($m) use ($search) {
                        $m->where('nama_merk', 'LIKE', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    private function renderGrup(Request $request, string $grup)
    {
        $config = $this->grupConfig($grup);
        $query = $this->buildGrupQuery($request, $grup);

        // Hitung Ringkasan Statistik
        $summary = [
            'total_aset' => (clone $query)->count(),
            'total_unit' => (int) ((clone $query)->sum('jumlah_unit') ?? 0),
            'total_nilai' => (float) ((clone $query)->sum('harga_total') ?? 0),
        ];

        $asetList = (clone $query)->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $kategoriList = Kategori::orderBy('nama_kategori')->get();
        $lokasiList = Lokasi::orderBy('nama_lokasi')->get();
        $penanggungJawabList = PenanggungJawab::with('divisi')->orderBy('nama')->get();

        return view('aset.index', compact('asetList', 'kategoriList', 'lokasiList', 'penanggungJawabList', 'summary', 'config'));
    }

    private function exportExcel(Request $request, string $grup)
    {
        $config = $this->grupConfig($grup);
        $query = $this->buildGrupQuery($request, $grup);
        $items = $query->orderBy('created_at', 'desc')->get();

        $filename = 'rekap_'.str_replace('-', '_', $config['key']).'_'.date('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($items) {
            $handle = fopen('php://output', 'w');

            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header CSV (menggunakan delimiter koma yang kompatibel dengan Excel)
            fputcsv($handle, [
                'No',
                'Kode Aset',
                'Nama Aset',
                'Merk',
                'Tipe/Model',
                'Kategori',
                'Lokasi',
                'Penanggung Jawab',
                'Tanggal Pembelian',
                'Toko/Distributor',
                'No Invoice',
                'Jumlah Unit',
                'Harga Satuan (Rp)',
                'Harga Total (Rp)',
                'Umur Ekonomis (Thn)',
                'Penyusutan / Bln (Rp)',
                'Status',
                'Jenis',
            ]);

            $no = 1;
            foreach ($items as $aset) {
                fputcsv($handle, [
                    $no++,
                    $aset->kode_aset,
                    $aset->nama_aset,
                    $aset->merk->nama_merk ?? '-',
                    $aset->tipe_model ?? '-',
                    $aset->kategori->nama_kategori ?? '-',
                    $aset->lokasi->nama_lokasi ?? '-',
                    $aset->penanggungJawab->nama ?? '-',
                    $aset->tanggal_pembelian ? date('d/m/Y', strtotime($aset->tanggal_pembelian)) : '-',
                    $aset->toko_distributor ?? '-',
                    $aset->no_invoice ?? '-',
                    $aset->jumlah_unit,
                    $aset->harga_satuan,
                    $aset->harga_total,
                    $aset->umur_ekonomis_tahun,
                    $aset->penyusutan_per_bulan,
                    ucfirst($aset->status),
                    ucfirst($aset->jenis),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function create()
    {
        $kategoriList = Kategori::with('barang')->orderBy('nama_kategori')->get();
        $barangList = Barang::with('kategori')->orderBy('nama_barang')->get();
        $divisiList = Divisi::orderBy('kode_divisi')->get();
        $merkList = Merk::orderBy('nama_merk')->get();
        $lokasiList = Lokasi::orderBy('kode_lokasi')->get();
        $pjList = PenanggungJawab::orderBy('nama')->get();

        return view('aset.create', compact('kategoriList', 'barangList', 'divisiList', 'merkList', 'lokasiList', 'pjList'));
    }

    public function previewKode(Request $request)
    {
        $preview = KodeAsetGenerator::preview($request->all());

        return response()->json($preview);
    }

    public function store(Request $request)
    {
        if ($request->filled('harga_satuan')) {
            $request->merge(['harga_satuan' => str_replace('.', '', (string) $request->harga_satuan)]);
        }
        if ($request->filled('nilai_residu')) {
            $request->merge(['nilai_residu' => str_replace('.', '', (string) $request->nilai_residu)]);
        }

        $request->validate([
            'nama_aset' => 'required|string|max:255',
            'sifat_barang' => 'required|in:D,S',
            'kategori_id' => 'required|exists:kategori,id',
            'barang_id' => 'required|exists:barang,id',
            'divisi_id' => 'required|exists:divisi,id',
            'cara_perolehan' => 'required|in:1,2',
            'status_barang' => 'required|in:1,2',
            'merk_id' => 'required|exists:merk,id',
            'lokasi_id' => 'required|exists:lokasi,id',
            'penanggung_jawab_id' => 'required|exists:penanggung_jawab,id',
            'tanggal_pembelian' => 'required|date',
            'toko_distributor' => 'required|string|max:255',
            'jumlah_unit' => 'required|integer|min:1',
            'harga_satuan' => 'required|numeric|min:0',
            'umur_ekonomis_tahun' => 'required|integer|min:1',
            'nilai_residu' => 'nullable|numeric|min:0',
            'jenis' => 'nullable|in:tetap,kelolaan',
            'tipe_model' => 'nullable|string|max:255',
            'produsen' => 'nullable|string|max:255',
            'no_seri' => 'nullable|string|max:255',
            'tahun_produksi' => 'nullable|integer|min:1900|max:2099',
            'no_invoice' => 'nullable|string|max:255',
            'deskripsi' => 'nullable|string',
            'keterangan_tambahan' => 'nullable|string',
            'foto_utama' => 'nullable|image|max:10240',
        ]);

        // Tentukan Jenis Aset otomatis dari Divisi (Divisi 5 & 6 = Kelolaan, 1-4 = Tetap)
        $divisi = Divisi::find($request->divisi_id);
        $jenis = ($divisi && in_array($divisi->kode_divisi, ['5', '6'])) ? 'kelolaan' : 'tetap';

        // Auto-generate Kode Aset 9-Komponen sesuai MODUL_KODE_ASET.md
        $genResult = KodeAsetGenerator::generate([
            'kategori_id' => $request->kategori_id,
            'barang_id' => $request->barang_id,
            'sifat_barang' => $request->sifat_barang,
            'penanggung_jawab_id' => $request->penanggung_jawab_id,
            'lokasi_id' => $request->lokasi_id,
            'divisi_id' => $request->divisi_id,
            'cara_perolehan' => $request->cara_perolehan,
            'status_barang' => $request->status_barang,
            'tanggal_pembelian' => $request->tanggal_pembelian,
        ]);

        $kodeAset = $genResult['kode_aset'];
        $nomorUrut = $genResult['nomor_urut'];

        // Hitung Keuangan & Penyusutan
        $hargaTotal = PenyusutanCalculator::hitungHargaTotal($request->jumlah_unit, $request->harga_satuan);
        $nilaiResidu = $request->nilai_residu ?? 0;
        $penyusutanBulan = PenyusutanCalculator::hitungPenyusutanPerBulan($hargaTotal, $request->umur_ekonomis_tahun, $nilaiResidu);

        $fotoPath = null;
        if ($request->hasFile('foto_utama')) {
            $file = $request->file('foto_utama');
            if ($file && $file->isValid() && $file->getRealPath()) {
                $fotoPath = $file->store('aset_foto', 'public');
            } else {
                return back()->withInput()->withErrors([
                    'foto_utama' => 'Foto gagal diunggah. Pastikan format file berupa gambar (JPG/PNG/WEBP) dan ukuran di bawah 10MB.',
                ]);
            }
        }

        $aset = Aset::create([
            'nama_aset' => $request->nama_aset,
            'sifat_barang' => $request->sifat_barang,
            'kode_aset' => $kodeAset,
            'kategori_id' => $request->kategori_id,
            'barang_id' => $request->barang_id,
            'divisi_id' => $request->divisi_id,
            'cara_perolehan' => $request->cara_perolehan,
            'status_barang' => $request->status_barang,
            'nomor_urut' => $nomorUrut,
            'merk_id' => $request->merk_id,
            'tipe_model' => $request->tipe_model,
            'produsen' => $request->produsen,
            'no_seri' => $request->no_seri,
            'tahun_produksi' => $request->tahun_produksi,
            'lokasi_id' => $request->lokasi_id,
            'penanggung_jawab_id' => $request->penanggung_jawab_id,
            'deskripsi' => $request->deskripsi,
            'tanggal_pembelian' => $request->tanggal_pembelian,
            'toko_distributor' => $request->toko_distributor,
            'no_invoice' => $request->no_invoice,
            'jumlah_unit' => $request->jumlah_unit,
            'harga_satuan' => $request->harga_satuan,
            'harga_total' => $hargaTotal,
            'umur_ekonomis_tahun' => $request->umur_ekonomis_tahun,
            'nilai_residu' => $nilaiResidu,
            'penyusutan_per_bulan' => $penyusutanBulan,
            'foto_utama' => $fotoPath,
            'keterangan_tambahan' => $request->keterangan_tambahan,
            'status' => 'aktif',
            'jenis' => $jenis,
            'created_by' => auth()->id() ?? 1,
        ]);

        RiwayatAset::create([
            'aset_id' => $aset->id,
            'sejak_tanggal' => $request->tanggal_pembelian,
            'penanggung_jawab_id' => $request->penanggung_jawab_id,
            'lokasi_id' => $request->lokasi_id,
            'jumlah' => $request->jumlah_unit,
            'kondisi_persen' => 100,
            'kelengkapan_persen' => 100,
            'jenis_aksi' => 'pembuatan',
            'keterangan' => 'Aset berhasil didaftarkan ke dalam sistem AMANA.',
            'user_id' => auth()->id() ?? 1,
        ]);

        AuditLogger::log('buat_aset', "Pendaftaran aset baru {$aset->kode_aset} ({$aset->nama_aset})", $aset);

        $grupRoute = $jenis === 'kelolaan' ? 'aset.kelolaan' : 'aset.tetap';

        return redirect()->route($grupRoute)->with('success', "Aset berhasil dibuat dengan Kode: {$kodeAset}");
    }

    public function show($id)
    {
        $aset = Aset::with([
            'kategori', 'barang', 'divisi', 'merk', 'lokasi', 'penanggungJawab',
            'riwayat.user', 'riwayat.updater', 'riwayat.penanggungJawab', 'riwayat.lokasi', 'riwayat.divisi',
            'agenda.user', 'agenda.updater',
            'keuangan.user', 'keuangan.updater',
            'jurnal.user', 'jurnal.updater',
        ])->findOrFail($id);

        $nilaiBuku = PenyusutanCalculator::hitungNilaiBukuSaatIni($aset);
        $lokasiList = Lokasi::orderBy('nama_lokasi')->get();
        $pjList = PenanggungJawab::with('divisi')->orderBy('nama')->get();
        $divisiList = Divisi::orderBy('kode_divisi')->get();

        return view('aset.show', compact('aset', 'nilaiBuku', 'lokasiList', 'pjList', 'divisiList'));
    }

    public function pdf(Request $request, $id)
    {
        $aset = Aset::with([
            'kategori', 'barang', 'divisi', 'merk', 'lokasi', 'penanggungJawab.divisi',
            'riwayat' => fn ($q) => $q->with(['user', 'penanggungJawab', 'lokasi', 'divisi'])->orderBy('sejak_tanggal', 'desc')->orderBy('id', 'desc'),
            'agenda' => fn ($q) => $q->with(['user'])->orderBy('status', 'asc')->orderBy('created_at', 'desc'),
            'keuangan' => fn ($q) => $q->with(['user'])->orderBy('tanggal', 'desc')->orderBy('id', 'desc'),
            'jurnal' => fn ($q) => $q->with(['user'])->orderBy('tanggal', 'desc')->orderBy('id', 'desc'),
        ])->findOrFail($id);

        // Kalkulasi Penyusutan & Nilai Buku
        $nilaiBuku = PenyusutanCalculator::hitungNilaiBukuSaatIni($aset);
        $totalPenyusutan = PenyusutanCalculator::hitungTotalPenyusutanBerjalan($aset);
        $usiaAset = PenyusutanCalculator::formatUsiaAset($aset->tanggal_pembelian);

        // Generate QR Code sebagai Base64 SVG
        $qrTargetUrl = route('public.qr', $aset->kode_aset);
        try {
            $qrSvg = QrCode::format('svg')->size(120)->margin(1)->generate($qrTargetUrl);
            $qrBase64 = 'data:image/svg+xml;base64,'.base64_encode($qrSvg);
        } catch (\Throwable $e) {
            $qrBase64 = null;
        }

        // Siapkan Foto Utama sebagai Base64 jika file fisik tersedia
        $fotoBase64 = null;
        if ($aset->foto_utama) {
            $disk = Storage::disk('public');
            if ($disk->exists($aset->foto_utama)) {
                $fileContent = $disk->get($aset->foto_utama);
                $mime = $disk->mimeType($aset->foto_utama) ?: 'image/jpeg';
                $fotoBase64 = 'data:'.$mime.';base64,'.base64_encode($fileContent);
            } elseif (file_exists(public_path($aset->foto_utama))) {
                $fileContent = file_get_contents(public_path($aset->foto_utama));
                $mime = mime_content_type(public_path($aset->foto_utama)) ?: 'image/jpeg';
                $fotoBase64 = 'data:'.$mime.';base64,'.base64_encode($fileContent);
            }
        }

        $lembagaName = config('app.institution_name', 'AL AZHAR Peduli');
        $printedAt = Carbon::now()->translatedFormat('d F Y, H:i').' WIB';

        $data = [
            'aset' => $aset,
            'nilaiBuku' => $nilaiBuku,
            'totalPenyusutan' => $totalPenyusutan,
            'usiaAset' => $usiaAset,
            'qrBase64' => $qrBase64,
            'qrTargetUrl' => $qrTargetUrl,
            'fotoBase64' => $fotoBase64,
            'lembagaName' => $lembagaName,
            'printedAt' => $printedAt,
        ];

        $pdf = Pdf::loadView('aset.pdf.kartu-aset', $data)
            ->setPaper('a4', 'portrait')
            ->setOption([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'dpi' => 150,
                'defaultFont' => 'Helvetica',
            ]);

        $fileName = "Kartu-Aset-{$aset->kode_aset}.pdf";

        if ($request->boolean('download')) {
            return $pdf->download($fileName);
        }

        return $pdf->stream($fileName);
    }

    public function edit($id)
    {
        $aset = Aset::with(['barang', 'divisi'])->findOrFail($id);
        $kategoriList = Kategori::with('barang')->orderBy('nama_kategori')->get();
        $barangList = Barang::where('kategori_id', $aset->kategori_id)->orderBy('nama_barang')->get();
        $divisiList = Divisi::orderBy('kode_divisi')->get();
        $merkList = Merk::orderBy('nama_merk')->get();
        $lokasiList = Lokasi::orderBy('nama_lokasi')->get();
        $pjList = PenanggungJawab::orderBy('nama')->get();

        return view('aset.edit', compact('aset', 'kategoriList', 'barangList', 'divisiList', 'merkList', 'lokasiList', 'pjList'));
    }

    public function update(Request $request, $id)
    {
        $aset = Aset::findOrFail($id);

        if ($request->filled('harga_satuan')) {
            $request->merge(['harga_satuan' => str_replace('.', '', (string) $request->harga_satuan)]);
        }
        if ($request->filled('nilai_residu')) {
            $request->merge(['nilai_residu' => str_replace('.', '', (string) $request->nilai_residu)]);
        }

        $request->validate([
            'nama_aset' => 'required|string|max:255',
            'divisi_id' => 'nullable|exists:divisi,id',
            'kategori_id' => 'required|exists:kategori,id',
            'merk_id' => 'required|exists:merk,id',
            'lokasi_id' => 'required|exists:lokasi,id',
            'penanggung_jawab_id' => 'required|exists:penanggung_jawab,id',
            'tanggal_pembelian' => 'required|date',
            'toko_distributor' => 'required|string|max:255',
            'jumlah_unit' => 'required|integer|min:1',
            'harga_satuan' => 'required|numeric|min:0',
            'umur_ekonomis_tahun' => 'required|integer|min:1',
            'nilai_residu' => 'nullable|numeric|min:0',
            'jenis' => 'nullable|in:tetap,kelolaan',
            'tipe_model' => 'nullable|string|max:255',
            'produsen' => 'nullable|string|max:255',
            'no_seri' => 'nullable|string|max:255',
            'tahun_produksi' => 'nullable|integer|min:1900|max:2099',
            'no_invoice' => 'nullable|string|max:255',
            'deskripsi' => 'nullable|string',
            'keterangan_tambahan' => 'nullable|string',
            'foto_utama' => 'nullable|image|max:10240',
        ]);

        // Tentukan Jenis Aset otomatis dari Divisi jika diisi, atau pertahankan
        if ($request->filled('divisi_id')) {
            $divisi = Divisi::find($request->divisi_id);
            $jenis = ($divisi && in_array($divisi->kode_divisi, ['5', '6'])) ? 'kelolaan' : 'tetap';
        } else {
            $jenis = $request->jenis ?? $aset->jenis;
        }

        $hargaTotal = PenyusutanCalculator::hitungHargaTotal($request->jumlah_unit, $request->harga_satuan);
        $nilaiResidu = $request->nilai_residu ?? 0;
        $penyusutanBulan = PenyusutanCalculator::hitungPenyusutanPerBulan($hargaTotal, $request->umur_ekonomis_tahun, $nilaiResidu);

        $fotoPath = $aset->foto_utama;
        if ($request->hasFile('foto_utama')) {
            $file = $request->file('foto_utama');
            if ($file && $file->isValid() && $file->getRealPath()) {
                if ($aset->foto_utama && Storage::disk('public')->exists($aset->foto_utama)) {
                    Storage::disk('public')->delete($aset->foto_utama);
                }
                $fotoPath = $file->store('aset_foto', 'public');
            } else {
                return back()->withInput()->withErrors([
                    'foto_utama' => 'Foto gagal diunggah. Pastikan format file berupa gambar (JPG/PNG/WEBP) dan ukuran di bawah 10MB.',
                ]);
            }
        }

        // 1. Capture original data for audit logging
        $originalData = $aset->only([
            'nama_aset', 'divisi_id', 'kategori_id', 'merk_id', 'tipe_model', 'produsen',
            'no_seri', 'tahun_produksi', 'lokasi_id', 'penanggung_jawab_id',
            'deskripsi', 'tanggal_pembelian', 'toko_distributor', 'no_invoice',
            'jumlah_unit', 'harga_satuan', 'harga_total', 'umur_ekonomis_tahun',
            'nilai_residu', 'penyusutan_per_bulan', 'foto_utama', 'keterangan_tambahan', 'jenis',
        ]);

        // 2. Deteksi perubahan Lokasi, Penanggung Jawab, atau Divisi untuk Riwayat Aset (Mutasi Fisik)
        $mutasiLogs = [];
        if ($aset->lokasi_id != $request->lokasi_id) {
            $lokasiLama = $aset->lokasi->nama_lokasi ?? '-';
            $lokasiBaru = Lokasi::find($request->lokasi_id)?->nama_lokasi ?? '-';
            $mutasiLogs[] = "Lokasi berpindah dari '{$lokasiLama}' ke '{$lokasiBaru}'";
        }
        if ($aset->penanggung_jawab_id != $request->penanggung_jawab_id) {
            $pjLama = $aset->penanggungJawab->nama ?? '-';
            $pjBaru = PenanggungJawab::find($request->penanggung_jawab_id)?->nama ?? '-';
            $mutasiLogs[] = "Penanggung Jawab dialihkan dari '{$pjLama}' ke '{$pjBaru}'";
        }
        if ($request->filled('divisi_id') && $aset->divisi_id != $request->divisi_id) {
            $divisiLama = $aset->divisi->nama_divisi ?? '-';
            $divisiBaru = Divisi::find($request->divisi_id)?->nama_divisi ?? '-';
            $mutasiLogs[] = "Divisi dialihkan dari '{$divisiLama}' ke '{$divisiBaru}'";
        }

        $oldKode = $aset->kode_aset;
        $mutationResult = null;

        // HANYA jalankan regenerasi kode dan buat record di riwayat_aset jika terjadi mutasi fisik
        if (count($mutasiLogs) > 0) {
            $targetDivisiId = $request->divisi_id ?? $aset->divisi_id;
            $mutationResult = KodeAsetGenerator::regenerateForMutation(
                $aset,
                (int) $request->penanggung_jawab_id,
                (int) $request->lokasi_id,
                $targetDivisiId ? (int) $targetDivisiId : null
            );

            if ($mutationResult['changed']) {
                $mutasiLogs[] = "Kode aset diperbarui dari '{$oldKode}' menjadi '{$mutationResult['new_code']}'";
            }

            RiwayatAset::create([
                'aset_id' => $aset->id,
                'sejak_tanggal' => now()->toDateString(),
                'penanggung_jawab_id' => $request->penanggung_jawab_id,
                'lokasi_id' => $request->lokasi_id,
                'divisi_id' => $mutationResult['new_divisi_id'] ?? $targetDivisiId,
                'jumlah' => $request->jumlah_unit,
                'kondisi_persen' => 100,
                'kelengkapan_persen' => 100,
                'kode_aset_sebelumnya' => $oldKode,
                'kode_aset_baru' => $mutationResult['changed'] ? $mutationResult['new_code'] : $oldKode,
                'jenis_aksi' => 'mutasi',
                'keterangan' => implode(' & ', $mutasiLogs).' (melalui pembaruan data aset).',
                'user_id' => auth()->id() ?? 1,
            ]);
        }

        $newData = [
            'nama_aset' => $request->nama_aset,
            'divisi_id' => ($mutationResult['new_divisi_id'] ?? null) ?: ($request->divisi_id ?? $aset->divisi_id),
            'kategori_id' => $request->kategori_id,
            'merk_id' => $request->merk_id,
            'tipe_model' => $request->tipe_model,
            'produsen' => $request->produsen,
            'no_seri' => $request->no_seri,
            'tahun_produksi' => $request->tahun_produksi,
            'lokasi_id' => $request->lokasi_id,
            'penanggung_jawab_id' => $request->penanggung_jawab_id,
            'deskripsi' => $request->deskripsi,
            'tanggal_pembelian' => $request->tanggal_pembelian,
            'toko_distributor' => $request->toko_distributor,
            'no_invoice' => $request->no_invoice,
            'jumlah_unit' => $request->jumlah_unit,
            'harga_satuan' => $request->harga_satuan,
            'harga_total' => $hargaTotal,
            'umur_ekonomis_tahun' => $request->umur_ekonomis_tahun,
            'nilai_residu' => $nilaiResidu,
            'penyusutan_per_bulan' => $penyusutanBulan,
            'foto_utama' => $fotoPath,
            'keterangan_tambahan' => $request->keterangan_tambahan,
            'jenis' => $jenis,
        ];

        if ($mutationResult && $mutationResult['changed']) {
            $newData['kode_aset_lama'] = $oldKode;
            $newData['kode_aset'] = $mutationResult['new_code'];
        }

        $aset->update($newData);

        // 3. Catat rincian perubahan data ke Modul Log Audit
        AuditLogger::logAsetUpdate($aset, $originalData, $newData);

        return redirect()->route('aset.show', $aset->id)->with('success', "Aset {$aset->kode_aset} berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $aset = Aset::findOrFail($id);
        $statusLama = $aset->status;
        $aset->status = $aset->status === 'aktif' ? 'non_aktif' : 'aktif';
        $aset->save();

        AuditLogger::log(
            'ubah_status',
            "Status aset {$aset->kode_aset} diubah dari '{$statusLama}' menjadi '{$aset->status}'",
            $aset,
            [['field' => 'Status Aset', 'sebelum' => ucfirst($statusLama), 'sesudah' => ucfirst($aset->status)]]
        );

        return redirect()->back()->with('success', "Status Aset {$aset->kode_aset} berhasil diubah ke {$aset->status}");
    }

    // ==========================================
    // MODUL PUSAT EKSPOR ASET
    // ==========================================

    public function exportIndex(Request $request)
    {
        $kategoriList = Kategori::orderBy('nama_kategori')->get();
        $divisiList = Divisi::orderBy('kode_divisi')->get();
        $merkList = Merk::orderBy('nama_merk')->get();
        $lokasiList = Lokasi::orderBy('nama_lokasi')->get();
        $pjList = PenanggungJawab::orderBy('nama')->get();

        $previewQuery = AsetExportService::buildQuery($request->all());
        $filteredCount = $previewQuery->count();
        $totalCount = Aset::count();

        return view('aset.export.index', compact(
            'kategoriList', 'divisiList', 'merkList', 'lokasiList', 'pjList',
            'filteredCount', 'totalCount'
        ));
    }

    public function exportDownload(Request $request)
    {
        return AsetExportService::download($request->all());
    }

    // ==========================================
    // MODUL IMPOR ASET & STAGING RESOLUSI
    // ==========================================

    public function importIndex()
    {
        $batches = AsetImportBatch::with('creator')->latest()->paginate(10);

        return view('aset.import.index', compact('batches'));
    }

    public function importUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:20480',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            return back()->withErrors(['file' => 'Format file tidak didukung. Harap unggah file .xlsx, .xls, atau .csv.']);
        }

        try {
            $batch = AsetImportService::parseAndStage(
                $file,
                $file->getClientOriginalName(),
                auth()->id()
            );

            return redirect()->route('aset.import.preview', $batch->id)
                ->with('success', "File berhasil diunggah! Terdeteksi {$batch->total_baris} baris data.");
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'Gagal membaca file: '.$e->getMessage()]);
        }
    }

    public function importPreview(Request $request, $batchId)
    {
        $batch = AsetImportBatch::findOrFail($batchId);

        $query = $batch->items();

        // Filter status preview
        $filterStatus = $request->input('status', 'all');
        if ($filterStatus === 'ready') {
            $query->where('is_ready', true);
        } elseif ($filterStatus === 'incomplete') {
            $query->where('is_ready', false);
        } elseif ($filterStatus === 'duplicate') {
            $query->where('is_duplicate', true);
        }

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama_aset_mentah', 'LIKE', "%{$search}%")
                    ->orWhere('kode_aset_lama', 'LIKE', "%{$search}%")
                    ->orWhere('pj_mentah', 'LIKE', "%{$search}%")
                    ->orWhere('lokasi_mentah', 'LIKE', "%{$search}%");
            });
        }

        $items = $query->paginate(25)->withQueryString();

        $kategoriList = Kategori::with('barang')->orderBy('nama_kategori')->get();
        $barangList = Barang::orderBy('nama_barang')->get();
        $divisiList = Divisi::orderBy('kode_divisi')->get();
        $lokasiList = Lokasi::orderBy('nama_lokasi')->get();
        $pjList = PenanggungJawab::orderBy('nama')->get();

        return view('aset.import.preview', compact(
            'batch', 'items', 'kategoriList', 'barangList', 'divisiList', 'lokasiList', 'pjList', 'filterStatus'
        ));
    }

    public function importUpdateItem(Request $request, $itemId)
    {
        $item = AsetImportItem::findOrFail($itemId);
        $updated = AsetImportService::updateItem($item, $request->all());

        return response()->json([
            'success' => true,
            'item_id' => $updated->id,
            'is_ready' => $updated->is_ready,
            'missing_components' => $updated->missing_components,
            'batch_summary' => [
                'total' => $updated->batch->total_baris,
                'ready' => $updated->batch->baris_siap,
                'incomplete' => $updated->batch->baris_belum_lengkap,
                'duplicate' => $updated->batch->baris_duplikat,
            ],
        ]);
    }

    public function importBulkAssign(Request $request, $batchId)
    {
        $batch = AsetImportBatch::findOrFail($batchId);
        $itemIds = $request->input('item_ids', []);

        // Jika mode "all_filtered" atau "all_incomplete"
        $mode = $request->input('selection_mode', 'selected');
        if ($mode === 'all_incomplete') {
            $itemIds = $batch->items()->where('is_ready', false)->pluck('id')->toArray();
        } elseif ($mode === 'all_batch') {
            $itemIds = $batch->items()->pluck('id')->toArray();
        }

        $assignments = $request->only([
            'divisi_id', 'sifat_barang', 'cara_perolehan', 'status_barang',
            'lokasi_id', 'penanggung_jawab_id', 'kategori_id', 'barang_id',
        ]);

        $updatedCount = AsetImportService::bulkAssign($batch, $itemIds, $assignments);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'updated_count' => $updatedCount,
                'batch_summary' => [
                    'total' => $batch->total_baris,
                    'ready' => $batch->baris_siap,
                    'incomplete' => $batch->baris_belum_lengkap,
                    'duplicate' => $batch->baris_duplikat,
                ],
            ]);
        }

        return redirect()->back()->with('success', "Berhasil menerapkan konfigurasi ke {$updatedCount} baris aset.");
    }

    public function importCommit(Request $request, $batchId)
    {
        $batch = AsetImportBatch::findOrFail($batchId);

        $duplicateAction = $request->input('duplicate_action', 'update');

        try {
            $stats = AsetImportService::commitBatch($batch, $duplicateAction, auth()->id());

            $msg = "Proses impor selesai! {$stats['success']} aset baru diterbitkan";
            if ($stats['updated'] > 0) {
                $msg .= ", {$stats['updated']} aset lama diperbarui";
            }
            if ($stats['skipped'] > 0) {
                $msg .= ", {$stats['skipped']} duplikat dilewati";
            }
            if ($stats['failed'] > 0) {
                $msg .= ", {$stats['failed']} baris gagal";
            }

            return redirect()->route('aset.import.preview', $batch->id)->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['commit' => 'Gagal melakukan impor: '.$e->getMessage()]);
        }
    }

    public function importDeleteBatch($batchId)
    {
        $batch = AsetImportBatch::findOrFail($batchId);
        $batch->delete();

        return redirect()->route('aset.import.index')->with('success', 'Draft sesi impor berhasil dihapus.');
    }

    public function importDownloadSummary($batchId)
    {
        $batch = AsetImportBatch::with(['items.kategori', 'items.barang', 'items.divisi'])->findOrFail($batchId);

        $filename = 'ringkasan_impor_batch_'.$batch->id.'_'.date('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($batch) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Baris', 'Kode Aset Baru', 'Kode Aset Lama', 'Nama Aset',
                'Kategori', 'Barang', 'Sifat', 'Divisi', 'Status Impor', 'Keterangan / Error',
            ]);

            foreach ($batch->items as $item) {
                fputcsv($handle, [
                    $item->baris_ke,
                    $item->generated_kode_aset ?: '-',
                    $item->kode_aset_lama ?: '-',
                    $item->nama_aset_mentah,
                    $item->kategori->nama_kategori ?? $item->kategori_mentah ?? '-',
                    $item->barang->nama_barang ?? '-',
                    $item->sifat_barang === 'D' ? 'Dinamis' : 'Statis',
                    $item->divisi->nama_divisi ?? '-',
                    strtoupper($item->import_status),
                    $item->error_message ?: ($item->is_ready ? 'Siap Generate' : 'Belum Lengkap: '.implode(', ', $item->missing_components ?? [])),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
