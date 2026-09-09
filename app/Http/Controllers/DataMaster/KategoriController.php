<?php

namespace App\Http\Controllers\DataMaster;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Kategori;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    public function index(Request $request)
    {
        $kategoriList = Kategori::withCount(['barang', 'aset'])->orderBy('kode_kategori')->get();

        $selectedKategoriId = $request->get('kategori_id', $kategoriList->first()->id ?? null);
        $selectedKategori = $selectedKategoriId ? Kategori::withCount(['barang', 'aset'])->find($selectedKategoriId) : null;

        $barangQuery = Barang::with(['kategori'])->withCount('aset');

        if ($selectedKategoriId) {
            $barangQuery->where('kategori_id', $selectedKategoriId);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $barangQuery->where(function ($q) use ($search) {
                $q->where('nama_barang', 'LIKE', "%{$search}%")
                    ->orWhere('kode_barang', 'LIKE', "%{$search}%");
            });
        }

        $barangList = $barangQuery->orderBy('kode_barang')->paginate(15)->withQueryString();

        $nextCode = $selectedKategoriId ? $this->getNextBarangCode($selectedKategoriId) : '01';

        $summary = [
            'total_kategori' => Kategori::count(),
            'total_barang' => Barang::count(),
            'total_aset_terkategori' => Kategori::withCount('aset')->get()->sum('aset_count'),
            'kategori_terbanyak' => Kategori::withCount('aset')->orderByDesc('aset_count')->first(),
        ];

        return view('data-master.kategori.index', compact(
            'kategoriList',
            'selectedKategori',
            'barangList',
            'nextCode',
            'summary'
        ));
    }

    public function show($id)
    {
        $kategori = Kategori::withCount('aset')->findOrFail($id);
        $barang = Barang::where('kategori_id', $kategori->id)
            ->withCount('aset')
            ->orderBy('kode_barang')
            ->get();

        return response()->json([
            'status' => 'success',
            'kategori' => [
                'id' => $kategori->id,
                'kode_kategori' => $kategori->kode_kategori,
                'nama_kategori' => $kategori->nama_kategori,
                'keterangan' => $kategori->keterangan,
                'total_barang' => $barang->count(),
                'total_aset' => $kategori->aset_count,
            ],
            'next_kode_barang' => $this->getNextBarangCode($kategori->id),
            'barang' => $barang->map(function ($b) use ($kategori) {
                return [
                    'id' => $b->id,
                    'kode_barang' => $b->kode_barang,
                    'kode_gabungan' => $kategori->kode_kategori.$b->kode_barang,
                    'nama_barang' => $b->nama_barang,
                    'keterangan' => $b->keterangan,
                    'total_aset' => $b->aset_count,
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_kategori' => 'required|string|size:2|unique:kategori,kode_kategori',
            'nama_kategori' => 'required|string|max:100',
            'keterangan' => 'nullable|string',
        ]);

        $kode = strtoupper(trim($request->kode_kategori));

        $kategori = Kategori::create([
            'kode_kategori' => $kode,
            'nama_kategori' => $request->nama_kategori,
            'keterangan' => $request->keterangan,
        ]);

        AuditLogger::log('buat_master', "Menambahkan data master Kategori [{$kategori->kode_kategori}] {$kategori->nama_kategori}", null, null, 'Kategori');

        return redirect()->route('data.kategori.index', ['kategori_id' => $kategori->id])
            ->with('success', "Kategori [{$kategori->kode_kategori}] {$kategori->nama_kategori} berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $kategori = Kategori::findOrFail($id);

        $request->validate([
            'kode_kategori' => 'required|string|size:2|unique:kategori,kode_kategori,'.$kategori->id,
            'nama_kategori' => 'required|string|max:100',
            'keterangan' => 'nullable|string',
        ]);

        $kode = strtoupper(trim($request->kode_kategori));

        $kategori->update([
            'kode_kategori' => $kode,
            'nama_kategori' => $request->nama_kategori,
            'keterangan' => $request->keterangan,
        ]);

        AuditLogger::log('edit_master', "Memperbarui data master Kategori [{$kategori->kode_kategori}] {$kategori->nama_kategori}", null, null, 'Kategori');

        return redirect()->route('data.kategori.index', ['kategori_id' => $kategori->id])
            ->with('success', "Kategori [{$kategori->kode_kategori}] {$kategori->nama_kategori} berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $kategori = Kategori::withCount(['aset', 'barang'])->findOrFail($id);

        if ($kategori->aset_count > 0) {
            return redirect()->route('data.kategori.index')->withErrors([
                'error' => "Kategori [{$kategori->kode_kategori}] {$kategori->nama_kategori} tidak dapat dihapus karena masih digunakan oleh {$kategori->aset_count} unit aset aktif.",
            ]);
        }

        // Cek apakah ada barang yang memiliki aset
        $barangWithAset = Barang::where('kategori_id', $kategori->id)->has('aset')->count();
        if ($barangWithAset > 0) {
            return redirect()->route('data.kategori.index')->withErrors([
                'error' => "Kategori [{$kategori->kode_kategori}] {$kategori->nama_kategori} tidak dapat dihapus karena memiliki jenis barang yang masih terhubung ke unit aset.",
            ]);
        }

        $nama = $kategori->nama_kategori;
        $kode = $kategori->kode_kategori;

        // Hapus sub-barang yang belum ada asetnya
        Barang::where('kategori_id', $kategori->id)->delete();
        $kategori->delete();

        AuditLogger::log('hapus_master', "Menghapus data master Kategori [{$kode}] {$nama}", null, null, 'Kategori');

        return redirect()->route('data.kategori.index')
            ->with('success', "Kategori [{$kode}] {$nama} beserta sub-barangnya berhasil dihapus.");
    }

    public function storeBarang(Request $request, $kategori_id)
    {
        $kategori = Kategori::findOrFail($kategori_id);

        $request->validate([
            'kode_barang' => 'required|string|size:2',
            'nama_barang' => 'required|string|max:150',
            'keterangan' => 'nullable|string',
        ]);

        $kodeBarang = str_pad(trim($request->kode_barang), 2, '0', STR_PAD_LEFT);

        $exists = Barang::where('kategori_id', $kategori->id)
            ->where('kode_barang', $kodeBarang)
            ->exists();

        if ($exists) {
            return redirect()->route('data.kategori.index', ['kategori_id' => $kategori->id])
                ->withErrors(['error' => "Kode barang [{$kodeBarang}] sudah digunakan pada kategori {$kategori->nama_kategori}."]);
        }

        $barang = Barang::create([
            'kategori_id' => $kategori->id,
            'kode_barang' => $kodeBarang,
            'nama_barang' => $request->nama_barang,
            'keterangan' => $request->keterangan,
        ]);

        AuditLogger::log('buat_master', "Menambahkan Jenis Barang [{$kategori->kode_kategori}{$barang->kode_barang}] {$barang->nama_barang}", null, null, 'Barang');

        return redirect()->route('data.kategori.index', ['kategori_id' => $kategori->id])
            ->with('success', "Jenis Barang [{$kategori->kode_kategori}{$barang->kode_barang}] {$barang->nama_barang} berhasil ditambahkan.");
    }

    public function updateBarang(Request $request, $id)
    {
        $barang = Barang::with('kategori')->findOrFail($id);

        $request->validate([
            'kode_barang' => 'required|string|size:2',
            'nama_barang' => 'required|string|max:150',
            'keterangan' => 'nullable|string',
        ]);

        $kodeBarang = str_pad(trim($request->kode_barang), 2, '0', STR_PAD_LEFT);

        $exists = Barang::where('kategori_id', $barang->kategori_id)
            ->where('kode_barang', $kodeBarang)
            ->where('id', '!=', $barang->id)
            ->exists();

        if ($exists) {
            return redirect()->route('data.kategori.index', ['kategori_id' => $barang->kategori_id])
                ->withErrors(['error' => "Kode barang [{$kodeBarang}] sudah digunakan oleh barang lain pada kategori ini."]);
        }

        $barang->update([
            'kode_barang' => $kodeBarang,
            'nama_barang' => $request->nama_barang,
            'keterangan' => $request->keterangan,
        ]);

        AuditLogger::log('edit_master', "Memperbarui Jenis Barang [{$barang->kategori->kode_kategori}{$barang->kode_barang}] {$barang->nama_barang}", null, null, 'Barang');

        return redirect()->route('data.kategori.index', ['kategori_id' => $barang->kategori_id])
            ->with('success', "Jenis Barang [{$barang->kategori->kode_kategori}{$barang->kode_barang}] {$barang->nama_barang} berhasil diperbarui.");
    }

    public function destroyBarang($id)
    {
        $barang = Barang::with('kategori')->withCount('aset')->findOrFail($id);

        if ($barang->aset_count > 0) {
            return redirect()->route('data.kategori.index', ['kategori_id' => $barang->kategori_id])
                ->withErrors(['error' => "Jenis Barang [{$barang->kategori->kode_kategori}{$barang->kode_barang}] {$barang->nama_barang} tidak dapat dihapus karena masih digunakan oleh {$barang->aset_count} unit aset."]);
        }

        $kode = $barang->kategori->kode_kategori.$barang->kode_barang;
        $nama = $barang->nama_barang;
        $kategoriId = $barang->kategori_id;

        $barang->delete();

        AuditLogger::log('hapus_master', "Menghapus Jenis Barang [{$kode}] {$nama}", null, null, 'Barang');

        return redirect()->route('data.kategori.index', ['kategori_id' => $kategoriId])
            ->with('success', "Jenis Barang [{$kode}] {$nama} berhasil dihapus.");
    }

    protected function getNextBarangCode($kategoriId)
    {
        $maxCode = Barang::where('kategori_id', $kategoriId)->max('kode_barang');
        if (! $maxCode) {
            return '01';
        }
        $next = intval($maxCode) + 1;

        return str_pad($next, 2, '0', STR_PAD_LEFT);
    }
}
