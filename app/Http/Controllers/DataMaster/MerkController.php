<?php

namespace App\Http\Controllers\DataMaster;

use App\Http\Controllers\Controller;
use App\Models\Merk;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class MerkController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $query = Merk::withCount('aset');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where('nama_merk', 'LIKE', "%{$search}%");
        }

        $summary = [
            'total_merk' => Merk::count(),
            'merk_terpakai' => Merk::has('aset')->count(),
            'merk_tak_terpakai' => Merk::doesntHave('aset')->count(),
            'total_aset_bermerk' => (int) Merk::withCount('aset')->get()->sum('aset_count'),
        ];

        $merkList = $query->orderBy('nama_merk', 'asc')->paginate(15)->withQueryString();

        return view('data-master.merk.index', compact('merkList', 'summary'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'nama_merk' => 'required|string|max:150|unique:merk,nama_merk',
        ]);

        $merk = Merk::create($validated);

        AuditLogger::log('buat_master', "Menambahkan data master Merk baru [{$merk->nama_merk}]", null, null, 'Merk');

        if ($request->wantsJson()) {
            return response()->json([
                'id' => $merk->id,
                'nama_merk' => $merk->nama_merk,
            ], 201);
        }

        return redirect()->route('data.merk.index')->with('success', "Merk [{$merk->nama_merk}] berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAdmin();

        $merk = Merk::findOrFail($id);

        $validated = $request->validate([
            'nama_merk' => 'required|string|max:150|unique:merk,nama_merk,'.$merk->id,
        ]);

        $lama = $merk->nama_merk;
        $merk->update($validated);

        AuditLogger::log('edit_master', "Memperbarui data master Merk [{$lama}] menjadi [{$merk->nama_merk}]", null, null, 'Merk');

        return redirect()->route('data.merk.index')->with('success', "Merk [{$merk->nama_merk}] berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $this->authorizeAdmin();

        $merk = Merk::withCount('aset')->findOrFail($id);

        if ($merk->aset_count > 0) {
            return redirect()->route('data.merk.index')->withErrors([
                'error' => "Merk [{$merk->nama_merk}] tidak dapat dihapus karena masih digunakan oleh {$merk->aset_count} aset.",
            ]);
        }

        $nama = $merk->nama_merk;
        $merk->delete();

        AuditLogger::log('hapus_master', "Menghapus data master Merk [{$nama}]", null, null, 'Merk');

        return redirect()->route('data.merk.index')->with('success', "Merk [{$nama}] berhasil dihapus.");
    }

    private function authorizeAdmin(): void
    {
        abort_if(auth()->user()?->role !== 'super_admin', 403, 'Akses ditolak. Hanya Super Admin yang dapat mengelola Data Master Merk.');
    }
}
