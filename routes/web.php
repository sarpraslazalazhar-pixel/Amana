<?php

use App\Http\Controllers\AsetController;
use App\Http\Controllers\AsetSubmoduleController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataMaster\KategoriController;
use App\Http\Controllers\DataMaster\LokasiController;
use App\Http\Controllers\DataMaster\MerkController;
use App\Http\Controllers\DataMaster\PenanggungJawabController;
use App\Http\Controllers\KalenderAsetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicQrController;
use App\Http\Controllers\QrConfigController;
use App\Http\Controllers\QrPrintController;
use App\Http\Controllers\Sistem\UserController;
use App\Services\AgendaReminderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Halaman Login & Process Login
Route::get('/login', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('auth.login');
})->name('login');

Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => ['required', 'string'],
        'password' => ['required', 'string'],
    ]);

    $loginInput = $request->input('email');
    $password = $request->input('password');
    $remember = $request->boolean('remember');

    $field = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

    if (Auth::attempt([$field => $loginInput, 'password' => $password], $remember)) {
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    return back()->withErrors([
        'email' => 'Email/Username atau kata sandi tidak sesuai.',
    ])->onlyInput('email');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

// Web Routes Utama AMANA (Perlu Login)
Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/kalender', [KalenderAsetController::class, 'index'])->name('kalender.index');

    // Daftar Aset per Grup (didaftarkan sebelum resource agar tidak tertelan route show)
    Route::get('/aset/tetap', [AsetController::class, 'tetap'])->name('aset.tetap');
    Route::get('/aset/tetap/export', [AsetController::class, 'exportTetap'])->name('aset.tetap.export');
    Route::get('/aset/kelolaan', [AsetController::class, 'kelolaan'])->name('aset.kelolaan');
    Route::get('/aset/kelolaan/export', [AsetController::class, 'exportKelolaan'])->name('aset.kelolaan.export');
    Route::get('/aset/non-aktif', [AsetController::class, 'nonAktif'])->name('aset.nonAktif');
    Route::get('/aset/non-aktif/export', [AsetController::class, 'exportNonAktif'])->name('aset.nonAktif.export');

    // Modul Pusat Ekspor & Impor Aset
    Route::get('/aset/export', [AsetController::class, 'exportIndex'])->name('aset.export.index');
    Route::get('/aset/export/download', [AsetController::class, 'exportDownload'])->name('aset.export.download');

    Route::get('/aset/import', [AsetController::class, 'importIndex'])->name('aset.import.index');
    Route::post('/aset/import/upload', [AsetController::class, 'importUpload'])->name('aset.import.upload');
    Route::get('/aset/import/{batch}', [AsetController::class, 'importPreview'])->name('aset.import.preview');
    Route::post('/aset/import/item/{item}', [AsetController::class, 'importUpdateItem'])->name('aset.import.item.update');
    Route::post('/aset/import/{batch}/bulk-assign', [AsetController::class, 'importBulkAssign'])->name('aset.import.bulk-assign');
    Route::post('/aset/import/{batch}/commit', [AsetController::class, 'importCommit'])->name('aset.import.commit');
    Route::delete('/aset/import/{batch}', [AsetController::class, 'importDeleteBatch'])->name('aset.import.destroy');
    Route::get('/aset/import/{batch}/summary', [AsetController::class, 'importDownloadSummary'])->name('aset.import.summary');

    // Modul Cetak Label QR Code Massal & PDF
    Route::get('/aset/qr/print', [QrPrintController::class, 'index'])->name('aset.qr.print');
    Route::post('/aset/qr/preview', [QrPrintController::class, 'preview'])->name('aset.qr.preview');
    Route::post('/aset/qr/download-pdf', [QrPrintController::class, 'downloadPdf'])->name('aset.qr.download-pdf');
    Route::post('/aset/qr/print-direct', [QrPrintController::class, 'printDirect'])->name('aset.qr.print-direct');

    Route::post('/aset/preview-kode', [AsetController::class, 'previewKode'])->name('aset.preview-kode');
    Route::get('/aset/{aset}/pdf', [AsetController::class, 'pdf'])->name('aset.pdf');
    Route::resource('aset', AsetController::class);

    // Sub-Modul Aset (Riwayat, Mutasi, Agenda, Keuangan, Jurnal, Ubah Status)
    Route::post('/aset/{aset}/preview-mutasi', [AsetSubmoduleController::class, 'previewMutasi'])->name('aset.preview-mutasi');
    Route::post('/aset/{aset}/mutasi', [AsetSubmoduleController::class, 'mutasi'])->name('aset.mutasi');
    Route::post('/aset/{aset}/riwayat', [AsetSubmoduleController::class, 'storeRiwayat'])->name('aset.riwayat.store');
    Route::put('/riwayat/{riwayat}', [AsetSubmoduleController::class, 'updateRiwayat'])->name('aset.riwayat.update');
    Route::delete('/riwayat/{riwayat}', [AsetSubmoduleController::class, 'destroyRiwayat'])->name('aset.riwayat.destroy');

    Route::post('/aset/{aset}/agenda', [AsetSubmoduleController::class, 'storeAgenda'])->name('aset.agenda.store');
    Route::put('/agenda/{agenda}', [AsetSubmoduleController::class, 'updateAgenda'])->name('aset.agenda.update');
    Route::delete('/agenda/{agenda}', [AsetSubmoduleController::class, 'destroyAgenda'])->name('aset.agenda.destroy');
    Route::post('/agenda/{agenda}/selesai', [AsetSubmoduleController::class, 'selesaikanAgenda'])->name('aset.agenda.selesaikan');
    Route::patch('/agenda/{agenda}/toggle', [AsetSubmoduleController::class, 'toggleAgendaStatus'])->name('aset.agenda.toggle');
    Route::get('/agenda/reminders', function (AgendaReminderService $service) {
        return response()->json($service->getReminderData());
    })->name('agenda.reminders');

    Route::post('/aset/{aset}/keuangan', [AsetSubmoduleController::class, 'storeKeuangan'])->name('aset.keuangan.store');
    Route::put('/keuangan/{keuangan}', [AsetSubmoduleController::class, 'updateKeuangan'])->name('aset.keuangan.update');
    Route::delete('/keuangan/{keuangan}', [AsetSubmoduleController::class, 'destroyKeuangan'])->name('aset.keuangan.destroy');

    Route::post('/aset/{aset}/jurnal', [AsetSubmoduleController::class, 'storeJurnal'])->name('aset.jurnal.store');
    Route::put('/jurnal/{jurnal}', [AsetSubmoduleController::class, 'updateJurnal'])->name('aset.jurnal.update');
    Route::delete('/jurnal/{jurnal}', [AsetSubmoduleController::class, 'destroyJurnal'])->name('aset.jurnal.destroy');
    Route::patch('/jurnal/{jurnal}/status', [AsetSubmoduleController::class, 'updateJurnalStatus'])->name('aset.jurnal.status');

    Route::post('/aset/{aset}/status', [AsetSubmoduleController::class, 'ubahStatus'])->name('aset.status.update');

    // Data Master
    Route::resource('data/lokasi', LokasiController::class)->names('data.lokasi');
    Route::resource('data/penanggung-jawab', PenanggungJawabController::class)->names('data.penanggung-jawab');
    Route::resource('data/kategori', KategoriController::class)->names('data.kategori');
    Route::resource('data/merk', MerkController::class)->names('data.merk')->except(['create', 'show', 'edit']);
    Route::post('data/kategori/{kategori}/barang', [KategoriController::class, 'storeBarang'])->name('data.kategori.barang.store');
    Route::put('data/kategori/barang/{barang}', [KategoriController::class, 'updateBarang'])->name('data.kategori.barang.update');
    Route::delete('data/kategori/barang/{barang}', [KategoriController::class, 'destroyBarang'])->name('data.kategori.barang.destroy');

    // Modul Log Audit
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');

    // Modul Pengaturan: Konfigurasi QR Code & Portal Scan Publik
    Route::get('/pengaturan/qr-config', [QrConfigController::class, 'index'])->name('pengaturan.qr-config.index');
    Route::post('/pengaturan/qr-config', [QrConfigController::class, 'update'])->name('pengaturan.qr-config.update');
    Route::post('/pengaturan/qr-config/reset', [QrConfigController::class, 'reset'])->name('pengaturan.qr-config.reset');

    // Modul Pengaturan: Manajemen Pengguna
    Route::prefix('sistem/users')->name('sistem.users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
        Route::patch('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Modul Pengaturan: Profil Akun Mandiri (Ubah Data Profil & Ganti Kata Sandi)
    Route::get('/profil', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profil', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Utilitas Admin: Buat Symlink Storage (berguna untuk hosting cPanel/LiteSpeed tanpa akses terminal SSH)
    Route::get('/admin/storage-link', function () {
        if (auth()->user()?->role !== 'super_admin') {
            abort(403, 'Akses terbatas hanya untuk Super Admin.');
        }

        try {
            Artisan::call('storage:link');
            $output = Artisan::output();

            return redirect()->route('dashboard')->with('success', 'Symlink storage berhasil diproses: '.trim($output));
        } catch (Throwable $e) {
            return redirect()->route('dashboard')->with('error', 'Gagal membuat symlink: '.$e->getMessage());
        }
    })->name('admin.storage.link');
});

// Public QR Scan Portal (Tanpa Login)
Route::get('/p/{kode_aset}', [PublicQrController::class, 'show'])->name('public.qr');

// Fallback Route untuk Public Storage jika symlink 'public/storage' belum/tidak tersedia di server hosting
Route::get('/storage/{path}', function (string $path) {
    // Cegah directory traversal attacks
    if (str_contains($path, '..') || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
        abort(404);
    }

    $disk = Storage::disk('public');
    if (! $disk->exists($path)) {
        abort(404);
    }

    $mimeType = $disk->mimeType($path) ?: 'application/octet-stream';

    return response($disk->get($path), 200, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->where('path', '.*')->name('storage.fallback');
