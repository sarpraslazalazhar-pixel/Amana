<?php

namespace App\Http\Controllers\DataMaster;

use App\Http\Controllers\Controller;
use App\Models\Divisi;
use App\Models\PenanggungJawab;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class PenanggungJawabController extends Controller
{
    public function index(Request $request)
    {
        $query = PenanggungJawab::with(['divisi'])->withCount('aset');

        if ($request->filled('divisi_id')) {
            $query->where('divisi_id', $request->divisi_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'LIKE', "%{$search}%")
                    ->orWhere('kode_pic', 'LIKE', "%{$search}%")
                    ->orWhere('jabatan', 'LIKE', "%{$search}%")
                    ->orWhere('telepon', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $divisiList = Divisi::orderBy('kode_divisi')->get();

        // Ringkasan Statistik
        $summary = [
            'total_pic' => PenanggungJawab::count(),
            'total_aktif' => PenanggungJawab::where('status', 'aktif')->count(),
            'total_non_aktif' => PenanggungJawab::where('status', 'non_aktif')->count(),
            'total_aset_terdistribusi' => PenanggungJawab::withCount('aset')->get()->sum('aset_count'),
        ];

        $picList = $query->orderBy('kode_pic', 'asc')->paginate(15)->withQueryString();

        return view('data-master.penanggung-jawab.index', compact('picList', 'divisiList', 'summary'));
    }

    public function show($id)
    {
        $pic = PenanggungJawab::with(['divisi', 'aset' => function ($q) {
            $q->with(['kategori', 'lokasi', 'merk'])->orderBy('nama_aset');
        }])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'pic' => [
                'id' => $pic->id,
                'kode_pic' => $pic->kode_pic,
                'nama' => $pic->nama,
                'divisi' => $pic->divisi->nama_divisi ?? '-',
                'jabatan' => $pic->jabatan ?? '-',
                'telepon' => $pic->telepon,
                'email' => $pic->email,
                'status' => $pic->status,
                'total_aset' => $pic->aset->count(),
            ],
            'aset' => $pic->aset->map(function ($a) {
                return [
                    'id' => $a->id,
                    'kode_aset' => $a->kode_aset,
                    'nama_aset' => $a->nama_aset,
                    'kategori' => $a->kategori->nama_kategori ?? '-',
                    'lokasi' => $a->lokasi->nama_lokasi ?? '-',
                    'jenis' => ucfirst($a->jenis),
                    'status' => $a->status,
                    'detail_url' => route('aset.show', $a->id),
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        if ($request->filled('kode_pic')) {
            $request->merge([
                'kode_pic' => str_pad(trim($request->kode_pic), 3, '0', STR_PAD_LEFT),
            ]);
        }

        $request->validate([
            'kode_pic' => 'required|string|max:10|unique:penanggung_jawab,kode_pic',
            'nama' => 'required|string|max:150',
            'divisi_id' => 'nullable|exists:divisi,id',
            'jabatan' => 'nullable|string|max:100',
            'telepon' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
            'status' => 'required|in:aktif,non_aktif',
            'alamat' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);

        $pic = PenanggungJawab::create([
            'kode_pic' => $request->kode_pic,
            'nama' => $request->nama,
            'divisi_id' => $request->divisi_id,
            'jabatan' => $request->jabatan,
            'telepon' => $request->telepon,
            'email' => $request->email,
            'status' => $request->status,
            'alamat' => $request->alamat,
            'keterangan' => $request->keterangan,
        ]);

        AuditLogger::log('buat_master', "Menambahkan data master Penanggung Jawab [{$pic->kode_pic}] {$pic->nama}", null, null, 'PenanggungJawab');

        return redirect()->route('data.penanggung-jawab.index')->with('success', "Penanggung Jawab [{$pic->kode_pic}] {$pic->nama} berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $pic = PenanggungJawab::findOrFail($id);

        if ($request->filled('kode_pic')) {
            $request->merge([
                'kode_pic' => str_pad(trim($request->kode_pic), 3, '0', STR_PAD_LEFT),
            ]);
        }

        $request->validate([
            'kode_pic' => 'required|string|max:10|unique:penanggung_jawab,kode_pic,'.$pic->id,
            'nama' => 'required|string|max:150',
            'divisi_id' => 'nullable|exists:divisi,id',
            'jabatan' => 'nullable|string|max:100',
            'telepon' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
            'status' => 'required|in:aktif,non_aktif',
            'alamat' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);

        $pic->update([
            'kode_pic' => $request->kode_pic,
            'nama' => $request->nama,
            'divisi_id' => $request->divisi_id,
            'jabatan' => $request->jabatan,
            'telepon' => $request->telepon,
            'email' => $request->email,
            'status' => $request->status,
            'alamat' => $request->alamat,
            'keterangan' => $request->keterangan,
        ]);

        AuditLogger::log('edit_master', "Memperbarui data master Penanggung Jawab [{$pic->kode_pic}] {$pic->nama}", null, null, 'PenanggungJawab');

        return redirect()->route('data.penanggung-jawab.index')->with('success', "Penanggung Jawab [{$pic->kode_pic}] {$pic->nama} berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $pic = PenanggungJawab::withCount('aset')->findOrFail($id);

        if ($pic->aset_count > 0) {
            return redirect()->route('data.penanggung-jawab.index')->withErrors([
                'error' => "Penanggung Jawab [{$pic->kode_pic}] {$pic->nama} tidak dapat dihapus karena masih memegang {$pic->aset_count} aset aktif. Silakan lakukan mutasi aset terlebih dahulu atau ubah status menjadi Non-Aktif.",
            ]);
        }

        $nama = $pic->nama;
        $kode = $pic->kode_pic;
        $pic->delete();

        AuditLogger::log('hapus_master', "Menghapus data master Penanggung Jawab [{$kode}] {$nama}", null, null, 'PenanggungJawab');

        return redirect()->route('data.penanggung-jawab.index')->with('success', "Penanggung Jawab [{$kode}] {$nama} berhasil dihapus.");
    }
}
