<?php

namespace App\Http\Controllers\DataMaster;

use App\Http\Controllers\Controller;
use App\Models\Lokasi;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class LokasiController extends Controller
{
    public function index(Request $request)
    {
        $query = Lokasi::withCount('aset');

        if ($request->filled('gedung')) {
            $query->where('gedung', $request->gedung);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama_lokasi', 'LIKE', "%{$search}%")
                    ->orWhere('kode_lokasi', 'LIKE', "%{$search}%")
                    ->orWhere('gedung', 'LIKE', "%{$search}%")
                    ->orWhere('alamat_lengkap', 'LIKE', "%{$search}%");
            });
        }

        $gedungList = Lokasi::whereNotNull('gedung')
            ->distinct()
            ->orderBy('gedung')
            ->pluck('gedung');

        // Ringkasan Statistik
        $summary = [
            'total_lokasi' => Lokasi::count(),
            'total_gedung' => $gedungList->count(),
            'total_berkoordinat' => Lokasi::whereNotNull('latitude')->whereNotNull('longitude')->count(),
            'total_aset_terpetakan' => Lokasi::withCount('aset')->get()->sum('aset_count'),
        ];

        // Markers Data untuk Leaflet Map (Hanya yang punya koordinat)
        $markersData = Lokasi::withCount('aset')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'kode_lokasi' => $item->kode_lokasi,
                    'nama_lokasi' => $item->nama_lokasi,
                    'gedung' => $item->gedung ?? 'Lainnya',
                    'alamat_lengkap' => $item->alamat_lengkap ?? '-',
                    'lat' => (float) $item->latitude,
                    'lng' => (float) $item->longitude,
                    'aset_count' => (int) $item->aset_count,
                ];
            });

        $lokasiList = $query->orderBy('kode_lokasi', 'asc')->paginate(15)->withQueryString();

        return view('data-master.lokasi.index', compact('lokasiList', 'markersData', 'gedungList', 'summary'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_lokasi' => 'required|string|max:10|unique:lokasi,kode_lokasi',
            'nama_lokasi' => 'required|string|max:150',
            'gedung' => 'nullable|string|max:100',
            'alamat_lengkap' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $kode = str_pad(trim($request->kode_lokasi), 3, '0', STR_PAD_LEFT);

        $lokasi = Lokasi::create([
            'kode_lokasi' => $kode,
            'nama_lokasi' => $request->nama_lokasi,
            'gedung' => $request->gedung,
            'alamat_lengkap' => $request->alamat_lengkap,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        AuditLogger::log('buat_master', "Menambahkan data master Lokasi baru [{$lokasi->kode_lokasi}] {$lokasi->nama_lokasi}", null, null, 'Lokasi');

        return redirect()->route('data.lokasi.index')->with('success', "Lokasi [{$lokasi->kode_lokasi}] {$lokasi->nama_lokasi} berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $lokasi = Lokasi::findOrFail($id);

        $request->validate([
            'kode_lokasi' => 'required|string|max:10|unique:lokasi,kode_lokasi,'.$lokasi->id,
            'nama_lokasi' => 'required|string|max:150',
            'gedung' => 'nullable|string|max:100',
            'alamat_lengkap' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $kode = str_pad(trim($request->kode_lokasi), 3, '0', STR_PAD_LEFT);

        $lokasi->update([
            'kode_lokasi' => $kode,
            'nama_lokasi' => $request->nama_lokasi,
            'gedung' => $request->gedung,
            'alamat_lengkap' => $request->alamat_lengkap,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        AuditLogger::log('edit_master', "Memperbarui data master Lokasi [{$lokasi->kode_lokasi}] {$lokasi->nama_lokasi}", null, null, 'Lokasi');

        return redirect()->route('data.lokasi.index')->with('success', "Lokasi [{$lokasi->kode_lokasi}] {$lokasi->nama_lokasi} berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $lokasi = Lokasi::withCount('aset')->findOrFail($id);

        if ($lokasi->aset_count > 0) {
            return redirect()->route('data.lokasi.index')->withErrors([
                'error' => "Lokasi [{$lokasi->kode_lokasi}] {$lokasi->nama_lokasi} tidak dapat dihapus karena masih digunakan oleh {$lokasi->aset_count} aset.",
            ]);
        }

        $nama = $lokasi->nama_lokasi;
        $kode = $lokasi->kode_lokasi;
        $lokasi->delete();

        AuditLogger::log('hapus_master', "Menghapus data master Lokasi [{$kode}] {$nama}", null, null, 'Lokasi');

        return redirect()->route('data.lokasi.index')->with('success', "Lokasi [{$kode}] {$nama} berhasil dihapus.");
    }
}
