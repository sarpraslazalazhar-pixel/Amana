<?php

namespace App\Http\Controllers;

use App\Models\Aset;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\PenanggungJawab;
use App\Models\QrConfig;
use App\Services\QrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class QrPrintController extends Controller
{
    /**
     * Halaman Pemilih Aset & Konfigurasi Cetak QR
     */
    public function index(Request $request)
    {
        $query = Aset::with(['kategori', 'merk', 'lokasi', 'penanggungJawab']);

        // Filter Pencarian Teks
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama_aset', 'LIKE', "%{$search}%")
                    ->orWhere('kode_aset', 'LIKE', "%{$search}%")
                    ->orWhere('no_seri', 'LIKE', "%{$search}%")
                    ->orWhere('tipe_model', 'LIKE', "%{$search}%")
                    ->orWhereHas('merk', fn ($m) => $m->where('nama_merk', 'LIKE', "%{$search}%"));
            });
        }

        // Filter Kategori
        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        // Filter Lokasi
        if ($request->filled('lokasi_id')) {
            $query->where('lokasi_id', $request->lokasi_id);
        }

        // Filter Penanggung Jawab
        if ($request->filled('penanggung_jawab_id')) {
            $query->where('penanggung_jawab_id', $request->penanggung_jawab_id);
        }

        // Filter Jenis / Klasifikasi
        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        // Filter Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $totalFiltered = (clone $query)->count();
        $asetList = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        // Ambil list untuk dropdown filter
        $kategoriList = Kategori::orderBy('nama_kategori')->get();
        $lokasiList = Lokasi::orderBy('nama_lokasi')->get();
        $penanggungJawabList = PenanggungJawab::with('divisi')->orderBy('nama')->get();

        $templates = QrCodeService::getTemplates();
        $config = QrConfig::getAllConfig();

        // Selected IDs dari parameter (misal dikirim dari tabel aset)
        $preSelectedIds = [];
        if ($request->filled('selected_ids')) {
            $raw = $request->selected_ids;
            $preSelectedIds = is_array($raw) ? $raw : explode(',', (string) $raw);
            $preSelectedIds = array_filter(array_map('trim', $preSelectedIds));
        }

        return view('aset.qr-print.index', compact(
            'asetList',
            'kategoriList',
            'lokasiList',
            'penanggungJawabList',
            'templates',
            'config',
            'preSelectedIds',
            'totalFiltered'
        ));
    }

    /**
     * Halaman Live Interactive Sheet Preview
     */
    public function preview(Request $request)
    {
        $request->validate([
            'aset_ids' => 'required|array|min:1',
            'aset_ids.*' => 'required|integer|exists:aset,id',
            'template_key' => 'required|string',
        ]);

        $template = QrCodeService::getTemplate($request->template_key);
        $config = QrConfig::getAllConfig();

        $asetItems = Aset::with(['kategori', 'merk', 'lokasi', 'penanggungJawab'])
            ->whereIn('id', $request->aset_ids)
            ->orderBy('id', 'asc')
            ->get();

        $labels = [];
        foreach ($asetItems as $aset) {
            $labels[] = QrCodeService::prepareLabelData($aset, $config);
        }

        // Kelompokkan label ke dalam lembaran halaman sesuai per_page template
        $perPage = $template['per_page'] ?? 24;
        $pages = array_chunk($labels, $perPage);

        return view('aset.qr-print.preview', compact(
            'labels',
            'pages',
            'template',
            'config',
            'asetItems'
        ));
    }

    /**
     * Download Berkas Dokumen PDF
     */
    public function downloadPdf(Request $request)
    {
        $request->validate([
            'aset_ids' => 'required|array|min:1',
            'aset_ids.*' => 'required|integer|exists:aset,id',
            'template_key' => 'required|string',
        ]);

        $template = QrCodeService::getTemplate($request->template_key);
        $config = QrConfig::getAllConfig();

        $asetItems = Aset::with(['kategori', 'merk', 'lokasi', 'penanggungJawab'])
            ->whereIn('id', $request->aset_ids)
            ->orderBy('id', 'asc')
            ->get();

        $labels = [];
        foreach ($asetItems as $aset) {
            $labels[] = QrCodeService::prepareLabelData($aset, $config);
        }

        $perPage = $template['per_page'] ?? 24;
        $pages = array_chunk($labels, $perPage);

        $pdf = Pdf::loadView('aset.qr-print.pdf-sheet', compact('pages', 'template', 'config'));

        // Atur ukuran kertas DomPDF
        if ($template['paper_size'] === 'custom_103') {
            // TJ 103 ukuran kertas ~205mm x 165mm
            $pdf->setPaper([0, 0, 467.7, 581.1], 'portrait');
        } elseif ($template['paper_size'] === 'thermal_50x65') {
            // Thermal 50mm x 65mm = 141.7 pt x 184.2 pt
            $pdf->setPaper([0, 0, 141.7, 184.2], 'portrait');
        } else {
            $pdf->setPaper('a4', 'portrait');
        }

        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif',
            'dpi' => 150,
        ]);

        $filename = 'label_qr_aset_amana_'.date('Ymd_His').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Tampilan Cetak Langsung Browser (Window.print)
     */
    public function printDirect(Request $request)
    {
        $request->validate([
            'aset_ids' => 'required|array|min:1',
            'aset_ids.*' => 'required|integer|exists:aset,id',
            'template_key' => 'required|string',
        ]);

        $template = QrCodeService::getTemplate($request->template_key);
        $config = QrConfig::getAllConfig();

        $asetItems = Aset::with(['kategori', 'merk', 'lokasi', 'penanggungJawab'])
            ->whereIn('id', $request->aset_ids)
            ->orderBy('id', 'asc')
            ->get();

        $labels = [];
        foreach ($asetItems as $aset) {
            $labels[] = QrCodeService::prepareLabelData($aset, $config);
        }

        $perPage = $template['per_page'] ?? 24;
        $pages = array_chunk($labels, $perPage);

        return view('aset.qr-print.print-direct', compact(
            'labels',
            'pages',
            'template',
            'config'
        ));
    }
}
