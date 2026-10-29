<?php

namespace App\Http\Controllers\DataMaster;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class MitraController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_if(auth()->user()?->role !== 'super_admin', 403, 'Akses ditolak. Hanya Super Admin yang dapat mengelola Data Master Mitra.');
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $query = Mitra::withCount('aset');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'LIKE', "%{$search}%")
                    ->orWhere('nama_kontak', 'LIKE', "%{$search}%")
                    ->orWhere('telepon', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $summary = [
            'total' => Mitra::count(),
            'terpakai' => Mitra::has('aset')->count(),
            'aktif' => Mitra::where('status', 'aktif')->count(),
        ];

        $mitraList = $query->orderBy('nama', 'asc')->paginate(15)->withQueryString();

        return view('data-master.mitra.index', compact('mitraList', 'summary'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $request->merge(['nama' => trim((string) $request->nama)]);

        $validated = $request->validate([
            'nama' => [
                'required',
                'string',
                'max:200',
                function ($attribute, $value, $fail) {
                    if (Mitra::whereRaw('LOWER(nama) = ?', [strtolower(trim((string) $value))])->exists()) {
                        $fail('Nama mitra sudah digunakan.');
                    }
                },
            ],
            'jenis' => ['sometimes', 'required', 'in:toko,distributor,jasa'],
            'nama_kontak' => ['nullable', 'string', 'max:150'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'alamat' => ['nullable', 'string'],
            'keterangan' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', 'in:aktif,nonaktif'],
        ]);
        if (empty($validated['jenis'])) {
            $validated['jenis'] = 'toko';
        }

        if (empty($validated['status'])) {
            $validated['status'] = 'aktif';
        }

        $mitra = Mitra::create($validated);

        AuditLogger::log('buat_master', "Menambahkan data master Mitra baru [{$mitra->nama}]", null, null, 'Mitra');

        if ($request->wantsJson()) {
            return response()->json([
                'id' => $mitra->id,
                'nama' => $mitra->nama,
                'jenis' => $mitra->jenis,
            ], 201);
        }

        return redirect()->route('data.mitra.index')->with('success', "Mitra [{$mitra->nama}] berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAdmin();

        $mitra = Mitra::findOrFail($id);

        $request->merge(['nama' => trim((string) $request->nama)]);

        $validated = $request->validate([
            'nama' => [
                'required',
                'string',
                'max:200',
                function ($attribute, $value, $fail) use ($mitra) {
                    if (Mitra::where('id', '!=', $mitra->id)->whereRaw('LOWER(nama) = ?', [strtolower(trim((string) $value))])->exists()) {
                        $fail('Nama mitra sudah digunakan.');
                    }
                },
            ],
            'jenis' => ['required', 'in:toko,distributor,jasa'],
            'nama_kontak' => ['nullable', 'string', 'max:150'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'alamat' => ['nullable', 'string'],
            'keterangan' => ['nullable', 'string'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ]);

        $lama = $mitra->nama;
        $mitra->update($validated);

        AuditLogger::log('edit_master', "Memperbarui data master Mitra [{$lama}] menjadi [{$mitra->nama}]", null, null, 'Mitra');

        return redirect()->route('data.mitra.index')->with('success', "Mitra [{$mitra->nama}] berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $this->authorizeAdmin();

        $mitra = Mitra::withCount('aset')->findOrFail($id);

        if ($mitra->aset_count > 0) {
            return redirect()->route('data.mitra.index')->withErrors([
                'error' => "Mitra [{$mitra->nama}] tidak dapat dihapus karena masih digunakan oleh {$mitra->aset_count} aset.",
            ]);
        }

        $nama = $mitra->nama;
        $mitra->delete();

        AuditLogger::log('hapus_master', "Menghapus data master Mitra [{$nama}]", null, null, 'Mitra');

        return redirect()->route('data.mitra.index')->with('success', "Mitra [{$nama}] berhasil dihapus.");
    }
}
