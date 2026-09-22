<?php

namespace App\Http\Controllers;

use App\Models\Aset;
use App\Models\LampiranAset;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LampiranAsetController extends Controller
{
    /**
     * Format file yang diizinkan.
     */
    private const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx'];

    /**
     * Maksimal lampiran per aset.
     */
    private const MAX_FILES_PER_ASET = 5;

    /**
     * Upload lampiran baru ke aset.
     */
    public function store(Request $request, $asetId)
    {
        $aset = Aset::findOrFail($asetId);

        // Cek batas maksimal lampiran
        $currentCount = $aset->lampiran()->count();
        if ($currentCount >= self::MAX_FILES_PER_ASET) {
            return back()->withErrors([
                'lampiran_file' => 'Maksimal ' . self::MAX_FILES_PER_ASET . ' lampiran per aset. Hapus lampiran lama terlebih dahulu.',
            ]);
        }

        $allowedMimes = implode(',', self::ALLOWED_EXTENSIONS);

        $request->validate([
            'lampiran_file' => "required|file|max:5120|mimes:{$allowedMimes}",
            'lampiran_label' => 'nullable|string|max:255',
        ], [
            'lampiran_file.required' => 'File lampiran wajib dipilih.',
            'lampiran_file.file' => 'File tidak valid.',
            'lampiran_file.max' => 'Ukuran file maksimal 5 MB.',
            'lampiran_file.mimes' => 'Format file harus: ' . strtoupper(implode(', ', self::ALLOWED_EXTENSIONS)) . '.',
        ]);

        $file = $request->file('lampiran_file');

        // Simpan file ke storage
        $path = $file->store('lampiran_aset', 'public');

        $lampiran = LampiranAset::create([
            'aset_id' => $aset->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'file_type' => $file->getMimeType(),
            'label' => $request->lampiran_label,
            'uploaded_by' => auth()->id(),
        ]);

        AuditLogger::log(
            'upload_lampiran',
            "Lampiran '{$lampiran->file_name}' diunggah ke aset {$aset->kode_aset}",
            $aset
        );

        return back()->with('success', "Lampiran \"{$lampiran->file_name}\" berhasil diunggah.");
    }

    /**
     * Download file lampiran.
     */
    public function download($id)
    {
        $lampiran = LampiranAset::findOrFail($id);
        $disk = Storage::disk('public');

        if (! $disk->exists($lampiran->file_path)) {
            return back()->withErrors(['lampiran' => 'File tidak ditemukan di server.']);
        }

        return $disk->download($lampiran->file_path, $lampiran->file_name);
    }

    /**
     * Hapus lampiran (hanya Super Admin).
     */
    public function destroy($id)
    {
        if (! auth()->check() || auth()->user()->role !== 'super_admin') {
            abort(403, 'Hanya Super Admin yang dapat menghapus lampiran.');
        }

        $lampiran = LampiranAset::with('aset')->findOrFail($id);
        $aset = $lampiran->aset;
        $fileName = $lampiran->file_name;

        // Hapus file fisik dari storage
        $disk = Storage::disk('public');
        if ($disk->exists($lampiran->file_path)) {
            $disk->delete($lampiran->file_path);
        }

        $lampiran->delete();

        AuditLogger::log(
            'hapus_lampiran',
            "Lampiran '{$fileName}' dihapus dari aset {$aset->kode_aset}",
            $aset
        );

        return back()->with('success', "Lampiran \"{$fileName}\" berhasil dihapus.");
    }
}
