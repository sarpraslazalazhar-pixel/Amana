@extends('layouts.app')

@section('title', 'Impor Data Aset')
@section('header-title', 'Impor Data Aset Excel')

@section('content')
<div class="space-y-6">

    <!-- Header Title -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">Impor Data Aset</h2>
            <p class="text-xs text-slate-500">Migrasi & impor aset lama melalui Generator Kode Aset 9-Komponen dengan tahapan staging & resolusi cerdas.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('aset.export.index') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs border border-slate-200 shadow-sm transition-all">
                <i class="ti ti-file-export mr-1.5 text-emerald-600 text-sm"></i> Pusat Ekspor Aset
            </a>
            <a href="{{ route('aset.tetap') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition-all">
                <i class="ti ti-arrow-left mr-1.5 text-sm"></i> Kembali ke Daftar Aset
            </a>
        </div>
    </div>

    <!-- Alert Success / Error -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-3">
            <i class="ti ti-circle-check text-lg text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-3">
            <i class="ti ti-alert-circle text-lg text-rose-600"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Form Upload (Left 2 cols) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="p-6 bg-white rounded-2xl border border-slate-200/80 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900 mb-1 flex items-center gap-2">
                    <i class="ti ti-upload text-emerald-600 text-base"></i> Unggah File Excel / CSV
                </h3>
                <p class="text-xs text-slate-500 mb-5">Sistem akan membaca file, mencocokkan master secara otomatis (Fuzzy Matcher), dan menyiapkan staging untuk konfirmasi komponen kode aset.</p>

                <form action="{{ route('aset.import.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4" x-data="{ fileName: '', isDragging: false }">
                    @csrf

                    <div class="relative border-2 border-dashed rounded-2xl p-8 text-center transition-all cursor-pointer"
                         :class="isDragging ? 'border-emerald-500 bg-emerald-50/50' : 'border-slate-300 hover:border-emerald-500 bg-slate-50/50 hover:bg-emerald-50/20'"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; fileName = $refs.fileInput.files[0]?.name">

                        <input type="file" name="file" x-ref="fileInput" required accept=".xlsx,.xls,.csv"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                               @change="fileName = $event.target.files[0]?.name">

                        <div class="space-y-3 pointer-events-none">
                            <div class="w-12 h-12 mx-auto rounded-2xl bg-emerald-100/80 text-emerald-600 flex items-center justify-center">
                                <i class="ti ti-file-spreadsheet text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800" x-text="fileName ? fileName : 'Pilih file Excel / CSV atau seret ke sini'"></p>
                                <p class="text-[11px] text-slate-400 mt-0.5">Format didukung: .xlsx, .xls, .csv (Maksimal 20 MB)</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <span class="text-[11px] text-slate-400">
                            <i class="ti ti-shield-check text-emerald-600 mr-1"></i> Data akan diverifikasi sebelum disimpan ke tabel utama.
                        </span>
                        <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-semibold text-xs shadow-md shadow-emerald-600/15 transition-all">
                            <i class="ti ti-sparkles mr-1.5 text-sm"></i> Parse & Mulai Resolusi
                        </button>
                    </div>
                </form>
            </div>

            <!-- Petunjuk Alur & Kolom -->
            <div class="p-6 bg-white rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Tahapan & Cara Kerja Impor</h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/60 space-y-1">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 font-bold text-[10px]">1</span>
                        <h5 class="font-bold text-slate-800">Upload & Parsing</h5>
                        <p class="text-slate-500 text-[11px] leading-relaxed">File Excel dibaca dan diparsing otomatis ke tabel staging. Tanggal & nilai dihitung ulang.</p>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/60 space-y-1">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-cyan-100 text-cyan-700 font-bold text-[10px]">2</span>
                        <h5 class="font-bold text-slate-800">Resolusi Komponen</h5>
                        <p class="text-slate-500 text-[11px] leading-relaxed">Lengkapi komponen 9-digit pembentuk kode via saran otomatis & fitur Bulk-Assign.</p>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/60 space-y-1">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-purple-100 text-purple-700 font-bold text-[10px]">3</span>
                        <h5 class="font-bold text-slate-800">Generate & Commit</h5>
                        <p class="text-slate-500 text-[11px] leading-relaxed">Kode aset baru diterbitkan otomatis via generator resmi dalam database transaction.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Info & Klasifikasi Rule -->
        <div class="space-y-6">
            <div class="p-5 bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-2xl shadow-sm space-y-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center text-emerald-400">
                        <i class="ti ti-info-circle text-lg"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold">Aturan Klasifikasi Aset</h4>
                        <p class="text-[11px] text-slate-300">Otomatis dari kode Divisi</p>
                    </div>
                </div>

                <p class="text-xs text-slate-300 leading-relaxed">
                    Di aplikasi AMANA, jenis <strong>Aset Tetap</strong> dan <strong>Aset Kelolaan</strong> adalah turunan langsung dari <strong>Divisi</strong>:
                </p>

                <div class="space-y-1.5 text-[11px]">
                    <div class="flex items-center justify-between p-2 rounded-lg bg-white/5 border border-white/10">
                        <span>Divisi 1 (Direksi), 2 (Sekretariat), 3 (Fundraising), 4 (Keuangan)</span>
                        <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-semibold">Aset Tetap</span>
                    </div>
                    <div class="flex items-center justify-between p-2 rounded-lg bg-white/5 border border-white/10">
                        <span>Divisi 5 (Program), 6 (Wakaf)</span>
                        <span class="px-2 py-0.5 rounded bg-cyan-500/20 text-cyan-300 font-semibold">Aset Kelolaan</span>
                    </div>
                </div>
            </div>

            <div class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Format Kolom Excel yang Dikenali</h4>
                <ul class="text-xs text-slate-600 space-y-1.5 list-disc list-inside">
                    <li><span class="font-medium text-slate-800">Kode Aset / Lama</span> (Opsional, untuk pelacakan)</li>
                    <li><span class="font-medium text-slate-800">Nama Aset / Barang</span> (Wajib)</li>
                    <li><span class="font-medium text-slate-800">Kategori, Merk, Tipe</span></li>
                    <li><span class="font-medium text-slate-800">Penanggung Jawab / Lokasi</span></li>
                    <li><span class="font-medium text-slate-800">Tanggal Pembelian / Perolehan</span></li>
                    <li><span class="font-medium text-slate-800">Jumlah Unit & Harga Satuan</span></li>
                    <li><span class="font-medium text-slate-800">Umur Ekonomis & Nilai Residu</span></li>
                </ul>
            </div>
        </div>

    </div>

    <!-- Riwayat Sesi / Batch Impor -->
    <div class="p-6 bg-white rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Riwayat Sesi Impor (Staging Batches)</h3>
                <p class="text-xs text-slate-500">Daftar file yang pernah diunggah. Anda dapat melanjutkan proses resolusi baris yang berstatus draft.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">ID</th>
                        <th class="py-3 px-4">Nama File</th>
                        <th class="py-3 px-4 text-center">Total Baris</th>
                        <th class="py-3 px-4 text-center">Siap Generate</th>
                        <th class="py-3 px-4 text-center">Belum Lengkap</th>
                        <th class="py-3 px-4 text-center">Duplikat</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4">Tanggal Upload</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($batches as $b)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 px-4 font-mono font-bold text-slate-600">#{{ $b->id }}</td>
                            <td class="py-3 px-4 font-semibold text-slate-900">
                                <a href="{{ route('aset.import.preview', $b->id) }}" class="hover:text-emerald-600 flex items-center gap-1.5">
                                    <i class="ti ti-file-spreadsheet text-emerald-600 text-sm"></i>
                                    {{ $b->nama_file }}
                                </a>
                            </td>
                            <td class="py-3 px-4 text-center font-bold text-slate-800">{{ $b->total_baris }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    {{ $b->baris_siap }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $b->baris_belum_lengkap > 0 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-50 text-slate-500' }}">
                                    {{ $b->baris_belum_lengkap }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $b->baris_duplikat > 0 ? 'bg-cyan-50 text-cyan-700 border border-cyan-200' : 'bg-slate-50 text-slate-500' }}">
                                    {{ $b->baris_duplikat }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($b->status === 'completed')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-100 text-emerald-800">Selesai</span>
                                @elseif($b->status === 'draft')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-amber-100 text-amber-800">Draft Resolusi</span>
                                @elseif($b->status === 'failed')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-rose-100 text-rose-800">Gagal</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-slate-100 text-slate-700">{{ $b->status }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-500">
                                {{ $b->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3 px-4 text-right space-x-1">
                                <a href="{{ route('aset.import.preview', $b->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 transition-colors" title="Buka Halaman Resolusi">
                                    <i class="ti ti-eye"></i> Resolusi
                                </a>

                                <a href="{{ route('aset.import.summary', $b->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors" title="Unduh Ringkasan">
                                    <i class="ti ti-download"></i>
                                </a>

                                <form action="{{ route('aset.import.destroy', $b->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus sesi impor ini beserta data staging-nya?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Hapus Batch">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400">
                                <i class="ti ti-inbox text-2xl mb-1 block"></i>
                                Belum ada file impor yang diunggah.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-3 border-t border-slate-100">
            {{ $batches->links() }}
        </div>
    </div>

</div>
@endsection
