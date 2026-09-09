<?php

namespace App\Http\Controllers;

use App\Models\Aset;
use App\Models\QrConfig;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QrConfigController extends Controller
{
    /**
     * Tampilkan Halaman Pengaturan Konfigurasi QR
     */
    public function index()
    {
        $config = QrConfig::getAllConfig();

        // Ambil sampel data aset untuk Live Simulator interaktif
        $sampleAset = Aset::with([
            'kategori',
            'merk',
            'lokasi',
            'penanggungJawab',
            'riwayat.penanggungJawab',
            'riwayat.lokasi',
            'agenda',
            'keuangan',
            'jurnal',
        ])->first();

        // Jika belum ada aset di database, buat mock aset sampel
        if (! $sampleAset) {
            $sampleAset = new Aset([
                'id' => 1,
                'nama_aset' => 'Laptop Asus ROG Strix G15',
                'kode_aset' => 'EL14D05021202601',
                'tipe_model' => 'ROG Strix G15 G513',
                'produsen' => 'ASUS Inc.',
                'no_seri' => 'SN-2026-ROG-99881',
                'tahun_produksi' => 2025,
                'tanggal_pembelian' => '2026-01-15',
                'toko_distributor' => 'PT Asus Indo Jaya',
                'no_invoice' => 'INV/2026/01/9921',
                'jumlah_unit' => 1,
                'harga_satuan' => 18500000,
                'harga_total' => 18500000,
                'umur_ekonomis_tahun' => 4,
                'penyusutan_per_bulan' => 385416,
                'deskripsi' => 'Kondisi mulus, digunakan untuk tim multimedia.',
                'keterangan_tambahan' => 'Garansi distributor resmi 2 tahun.',
                'status' => 'aktif',
                'jenis' => 'tetap',
            ]);
        }

        return view('sistem.qr-config.index', compact('config', 'sampleAset'));
    }

    /**
     * Simpan Perubahan Konfigurasi QR
     */
    public function update(Request $request)
    {
        if (Auth::user()?->role !== 'super_admin') {
            abort(403, 'Hanya Super Admin yang dapat mengubah konfigurasi sistem.');
        }

        $request->validate([
            'label_judul_pilihan' => 'nullable|string|in:preset,custom',
            'label_judul_preset' => 'nullable|string|max:100',
            'label_judul_custom' => 'nullable|string|max:25',
            'label_baris_1' => 'nullable|string|max:50',
            'label_baris_2' => 'nullable|string|max:50',
            'riwayat_mode' => 'nullable|string|in:terakhir,semua,tidak_tampil',
        ]);

        QrConfig::setMany($request->all());

        AuditLogger::log(
            'edit_pengaturan',
            'Memperbarui Konfigurasi QR Code dan Tampilan Portal Scan Publik',
            null,
            null,
            'QrConfig'
        );

        return redirect()->route('pengaturan.qr-config.index')->with('success', 'Konfigurasi QR Code dan Portal Scan Publik berhasil disimpan!');
    }

    /**
     * Reset Konfigurasi ke Bawaan Sistem (Default)
     */
    public function reset()
    {
        if (Auth::user()?->role !== 'super_admin') {
            abort(403, 'Hanya Super Admin yang dapat mengubah konfigurasi sistem.');
        }

        QrConfig::setMany([]);

        AuditLogger::log(
            'reset_pengaturan',
            'Mereset Konfigurasi QR Code ke setelan bawaan sistem',
            null,
            null,
            'QrConfig'
        );

        return redirect()->route('pengaturan.qr-config.index')->with('success', 'Konfigurasi QR Code berhasil di-reset ke setelan standar.');
    }
}
