<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Tampilkan halaman profil pengguna yang sedang login.
     */
    public function index(): View
    {
        $user = Auth::user()->load('penanggungJawab.divisi');

        return view('profile.index', compact('user'));
    }

    /**
     * Perbarui data profil akun (Nama & Email).
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Alamat email ini telah digunakan oleh akun lain.',
        ]);

        $oldEmail = $user->email;
        $user->update([
            'name' => trim($request->name),
            'email' => strtolower(trim($request->email)),
        ]);

        AuditLogger::log(
            'edit_profil',
            "Pengguna [{$user->name}] memperbarui data profil akun ({$oldEmail} → {$user->email})",
            null,
            null,
            'User'
        );

        return redirect()->route('profile.index')
            ->with('success', 'Profil akun Anda berhasil diperbarui.');
    }

    /**
     * Perbarui kata sandi akun pengguna.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini yang Anda masukkan tidak sesuai.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal harus 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        AuditLogger::log(
            'ganti_password',
            "Pengguna [{$user->name}] berhasil memperbarui kata sandi akunnya",
            null,
            null,
            'User'
        );

        return redirect()->route('profile.index')
            ->with('success', 'Kata sandi akun Anda berhasil diperbarui.');
    }
}
