<?php

namespace App\Http\Controllers\Sistem;

use App\Http\Controllers\Controller;
use App\Models\PenanggungJawab;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Pastikan hanya Super Admin yang dapat mengakses modul ini.
     */
    private function authorizeSuperAdmin(): void
    {
        if (Auth::user()?->role !== 'super_admin') {
            abort(403, 'Akses ditolak. Hanya Super Admin yang diizinkan mengelola data pengguna.');
        }
    }

    /**
     * Tampilkan daftar seluruh pengguna sistem dengan filter dan statistik.
     */
    public function index(Request $request)
    {
        $this->authorizeSuperAdmin();

        $query = User::with(['penanggungJawab.divisi']);

        // Filter Role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter Status Keaktifan
        if ($request->filled('status')) {
            if ($request->status === 'aktif') {
                $query->where('is_active', true);
            } elseif ($request->status === 'non_aktif') {
                $query->where('is_active', false);
            }
        }

        // Pencarian (Nama & Email)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // Ringkasan Statistik
        $summary = [
            'total_user' => User::count(),
            'total_super_admin' => User::where('role', 'super_admin')->count(),
            'total_viewer' => User::where('role', 'viewer')->count(),
            'total_aktif' => User::where('is_active', true)->count(),
            'total_non_aktif' => User::where('is_active', false)->count(),
        ];

        // Daftar Penanggung Jawab untuk opsi penautan akun
        $picList = PenanggungJawab::with(['divisi', 'user'])
            ->orderBy('nama', 'asc')
            ->get();

        $users = $query->orderBy('role', 'asc')
            ->orderBy('name', 'asc')
            ->paginate(15)
            ->withQueryString();

        return view('sistem.users.index', compact('users', 'summary', 'picList'));
    }

    /**
     * Simpan akun pengguna baru ke database.
     */
    public function store(Request $request)
    {
        $this->authorizeSuperAdmin();

        $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email',
            'role' => 'required|in:super_admin,viewer',
            'password' => 'required|string|min:6|confirmed',
            'is_active' => 'nullable|boolean',
            'penanggung_jawab_id' => 'nullable|exists:penanggung_jawab,id',
        ]);

        $user = User::create([
            'name' => trim($request->name),
            'email' => strtolower(trim($request->email)),
            'role' => $request->role,
            'password' => Hash::make($request->password),
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Tautkan dengan profil Penanggung Jawab / Amil jika dipilih
        if ($request->filled('penanggung_jawab_id')) {
            PenanggungJawab::where('user_id', $user->id)->update(['user_id' => null]);
            PenanggungJawab::where('id', $request->penanggung_jawab_id)->update(['user_id' => $user->id]);
        }

        AuditLogger::log(
            'tambah_pengguna',
            "Menambahkan pengguna baru [{$user->name}] ({$user->email}) dengan peran ".strtoupper($user->role),
            null,
            null,
            'User'
        );

        return redirect()->route('sistem.users.index')
            ->with('success', "Pengguna baru [{$user->name}] berhasil didaftarkan ke sistem.");
    }

    /**
     * Perbarui data profil, peran, dan status akun pengguna.
     */
    public function update(Request $request, User $user)
    {
        $this->authorizeSuperAdmin();

        $request->validate([
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => 'required|in:super_admin,viewer',
            'is_active' => 'required|boolean',
            'penanggung_jawab_id' => 'nullable|exists:penanggung_jawab,id',
        ]);

        // Proteksi Self-Lockout untuk akun yang sedang aktif digunakan
        if ($user->id === Auth::id()) {
            if ($request->role !== 'super_admin') {
                return redirect()->route('sistem.users.index')
                    ->withErrors(['error' => 'Anda tidak dapat menurunkan peran (demote) akun Anda sendiri dari Super Admin.']);
            }

            if (! $request->boolean('is_active')) {
                return redirect()->route('sistem.users.index')
                    ->withErrors(['error' => 'Anda tidak dapat menonaktifkan akun yang sedang aktif Anda gunakan saat ini.']);
            }
        }

        // Proteksi jika ini adalah satu-satunya Super Admin aktif dalam sistem
        if ($user->role === 'super_admin' && $request->role !== 'super_admin') {
            $otherSuperAdminCount = User::where('role', 'super_admin')
                ->where('id', '!=', $user->id)
                ->where('is_active', true)
                ->count();

            if ($otherSuperAdminCount === 0) {
                return redirect()->route('sistem.users.index')
                    ->withErrors(['error' => 'Tidak dapat mengubah peran ini karena sistem membutuhkan setidaknya satu Super Admin aktif.']);
            }
        }

        $user->update([
            'name' => trim($request->name),
            'email' => strtolower(trim($request->email)),
            'role' => $request->role,
            'is_active' => $request->boolean('is_active'),
        ]);

        // Sinkronisasi penautan Penanggung Jawab
        PenanggungJawab::where('user_id', $user->id)->update(['user_id' => null]);
        if ($request->filled('penanggung_jawab_id')) {
            PenanggungJawab::where('id', $request->penanggung_jawab_id)->update(['user_id' => $user->id]);
        }

        AuditLogger::log(
            'edit_pengguna',
            "Memperbarui data akun pengguna [{$user->name}] ({$user->email})",
            null,
            null,
            'User'
        );

        return redirect()->route('sistem.users.index')
            ->with('success', "Data pengguna [{$user->name}] berhasil diperbarui.");
    }

    /**
     * Reset kata sandi pengguna terpilih.
     */
    public function resetPassword(Request $request, User $user)
    {
        $this->authorizeSuperAdmin();

        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        AuditLogger::log(
            'reset_password',
            "Mereset kata sandi akun pengguna [{$user->name}] ({$user->email})",
            null,
            null,
            'User'
        );

        return redirect()->route('sistem.users.index')
            ->with('success', "Kata sandi untuk pengguna [{$user->name}] berhasil di-reset.");
    }

    /**
     * Toggle status aktif / non-aktif akun pengguna.
     */
    public function toggleStatus(User $user)
    {
        $this->authorizeSuperAdmin();

        // Proteksi self-lockout
        if ($user->id === Auth::id()) {
            return redirect()->route('sistem.users.index')
                ->withErrors(['error' => 'Anda tidak dapat menonaktifkan akun Anda sendiri yang sedang aktif digunakan.']);
        }

        // Proteksi admin aktif terakhir
        if ($user->role === 'super_admin' && $user->is_active) {
            $otherActiveSuperAdmin = User::where('role', 'super_admin')
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherActiveSuperAdmin === 0) {
                return redirect()->route('sistem.users.index')
                    ->withErrors(['error' => 'Tidak dapat menonaktifkan satu-satunya Super Admin aktif dalam sistem.']);
            }
        }

        $newStatus = ! $user->is_active;
        $user->update(['is_active' => $newStatus]);

        $statusLabel = $newStatus ? 'diaktifkan' : 'dinonaktifkan';

        AuditLogger::log(
            'ubah_status_pengguna',
            "Akun pengguna [{$user->name}] telah {$statusLabel}",
            null,
            null,
            'User'
        );

        return redirect()->route('sistem.users.index')
            ->with('success', "Akun pengguna [{$user->name}] berhasil {$statusLabel}.");
    }

    /**
     * Hapus akun pengguna secara permanen dengan proteksi ketat.
     */
    public function destroy(User $user)
    {
        $this->authorizeSuperAdmin();

        // Proteksi self-deletion
        if ($user->id === Auth::id()) {
            return redirect()->route('sistem.users.index')
                ->withErrors(['error' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan.']);
        }

        // Proteksi last super admin
        if ($user->role === 'super_admin') {
            $otherSuperAdmin = User::where('role', 'super_admin')
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherSuperAdmin === 0) {
                return redirect()->route('sistem.users.index')
                    ->withErrors(['error' => 'Tidak dapat menghapus satu-satunya Super Admin dalam sistem.']);
            }
        }

        // Lepaskan relasi penanggung jawab sebelum delete
        PenanggungJawab::where('user_id', $user->id)->update(['user_id' => null]);

        $name = $user->name;
        $email = $user->email;
        $user->delete();

        AuditLogger::log(
            'hapus_pengguna',
            "Menghapus akun pengguna [{$name}] ({$email}) dari sistem",
            null,
            null,
            'User'
        );

        return redirect()->route('sistem.users.index')
            ->with('success', "Pengguna [{$name}] ({$email}) berhasil dihapus dari sistem.");
    }
}
