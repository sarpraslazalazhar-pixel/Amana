@extends('layouts.app')

@section('title', 'Tambah Aset Baru')
@section('header-title', 'Form Input Aset Baru')

@section('content')
@php
    $lokasiItems = $lokasiList->map(fn($l) => [
        'id' => $l->id,
        'code' => $l->kode_lokasi,
        'title' => $l->nama_lokasi,
        'subtitle' => $l->gedung ?: 'Gedung'
    ])->values()->all();

    $pjItems = $pjList->map(fn($p) => [
        'id' => $p->id,
        'code' => $p->kode_pic ?: sprintf('%03d', $p->id),
        'title' => $p->nama,
        'subtitle' => ($p->jabatan ?: 'Amil') . ($p->divisi ? ' • ' . $p->divisi->nama_divisi : '')
    ])->values()->all();

    $merkItems = $merkList->map(fn($m) => [
        'id' => $m->id,
        'code' => '',
        'title' => $m->nama_merk,
        'subtitle' => ''
    ])->values()->all();
@endphp

<div class="max-w-5xl mx-auto space-y-6" x-data="asetCreateForm">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Tambah Aset Baru</h2>
            <p class="text-xs text-slate-500 mt-0.5">Kode Aset otomatis 9-komponen standar rumus aset LAZ Al-Azhar</p>
        </div>
        <a href="{{ route('aset.index') }}"
           class="px-4 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-extrabold text-xs uppercase tracking-wider border border-emerald-200 transition-colors inline-flex items-center gap-1 shadow-xs">
            « KEMBALI
        </a>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
            <p class="font-bold mb-1">Terdapat beberapa kesalahan pengisian form:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- LIVE PREVIEW KODE ASET 9-KOMPONEN (STICKY TOP) -->
    <div class="p-5 sm:p-6 bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-950 rounded-3xl border border-emerald-500/30 text-white shadow-lg space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-white/10 pb-3">
            <div class="flex items-center gap-2.5">
                <span class="p-2 rounded-xl bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    <i class="ti ti-qrcode text-lg"></i>
                </span>
                <div>
                    <h3 class="text-sm font-extrabold tracking-wide text-white uppercase">Live Preview Kode Aset</h3>
                    <p class="text-[11px] text-slate-400">Standar Rumus 9-Komponen (17 Karakter)</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span x-show="isLoadingPreview" class="text-xs text-emerald-400 animate-pulse flex items-center gap-1">
                    <i class="ti ti-loader-2 animate-spin"></i> Menghitung...
                </span>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold tracking-wider uppercase"
                      :class="sifatBarang === 'D' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30'"
                      x-text="sifatBarang === 'D' ? 'Aset Dinamis (Amil)' : 'Aset Statis (Lokasi)'">
                </span>
            </div>
        </div>

        <!-- Kode Aset Utama Display -->
        <div class="flex flex-col items-center justify-center p-4 rounded-2xl bg-black/30 border border-white/10 space-y-3">
            <div class="font-mono text-2xl sm:text-3xl font-extrabold tracking-widest text-emerald-400 drop-shadow-md select-all"
                 x-text="previewCode">
            </div>

            <!-- Breakdown 9 Segmen Pills -->
            <div class="grid grid-cols-3 sm:grid-cols-9 gap-1.5 w-full text-center text-[10px]">
                <div class="p-1.5 rounded-lg bg-white/5 border border-white/10">
                    <span class="text-slate-400 block font-medium">Kat</span>
                    <span class="font-mono font-bold text-cyan-300" x-text="previewComponents?.kode_kategori || '--'"></span>
                </div>
                <div class="p-1.5 rounded-lg bg-white/5 border border-white/10">
                    <span class="text-slate-400 block font-medium">Barang</span>
                    <span class="font-mono font-bold text-cyan-300" x-text="previewComponents?.kode_barang || '--'"></span>
                </div>
                <div class="p-1.5 rounded-lg bg-white/5 border border-white/10">
                    <span class="text-slate-400 block font-medium">Sifat</span>
                    <span class="font-mono font-bold text-emerald-300" x-text="sifatBarang"></span>
                </div>
                <div class="p-1.5 rounded-lg bg-white/5 border border-white/10">
                    <span class="text-slate-400 block font-medium" x-text="sifatBarang === 'D' ? 'PIC' : 'Lokasi'"></span>
                    <span class="font-mono font-bold text-emerald-300" x-text="previewComponents?.kode_keempat || '---'"></span>
                </div>
                <div class="p-1.5 rounded-lg bg-white/5 border border-white/10">
                    <span class="text-slate-400 block font-medium">Divisi</span>
                    <span class="font-mono font-bold text-amber-300" x-text="previewComponents?.kode_divisi || '-'"></span>
                </div>
                <div class="p-1.5 rounded-lg bg-white/5 border border-white/10">
                    <span class="text-slate-400 block font-medium">Cara</span>
                    <span class="font-mono font-bold text-amber-300" x-text="caraPerolehan"></span>
                </div>
                <div class="p-1.5 rounded-lg bg-white/5 border border-white/10">
                    <span class="text-slate-400 block font-medium">Status</span>
                    <span class="font-mono font-bold text-amber-300" x-text="statusBarang"></span>
                </div>
                <div class="p-1.5 rounded-lg bg-white/5 border border-white/10">
                    <span class="text-slate-400 block font-medium">Tahun</span>
                    <span class="font-mono font-bold text-purple-300" x-text="previewComponents?.tahun || '----'"></span>
                </div>
                <div class="p-1.5 rounded-lg bg-white/5 border border-white/10">
                    <span class="text-slate-400 block font-medium">Urutan</span>
                    <span class="font-mono font-bold text-emerald-300" x-text="previewComponents?.nomor_urut || '--'"></span>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('aset.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- 1. DATA ASET (Umum & Sifat) -->
        <div class="p-6 sm:p-7 bg-white rounded-3xl border border-slate-200/80 shadow-sm space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-xs">1</span>
                    DATA ASET (UMUM & SIFAT)
                </h3>
                <span class="text-xs text-slate-400 font-medium">Identitas & Komponen Kode Utama</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Aset Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama_aset" required placeholder="Contoh: Laptop Lenovo ThinkPad X1 Carbon Gen 11" value="{{ old('nama_aset') }}"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors font-medium">
                </div>

                <!-- Sifat Barang Switch (Dinamis / Statis) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sifat Barang <span class="text-rose-500">*</span></label>
                    <select name="sifat_barang" required x-model="sifatBarang" @change="updatePreview()"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors font-bold text-slate-800">
                        <option value="D">D — Dinamis (Dipegang Khusus oleh Amil)</option>
                        <option value="S">S — Statis (Ditempatkan di Ruangan/Bersama)</option>
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1">Dinamis menggunakan NIA Amil, Statis menggunakan Kode Lokasi.</p>
                </div>

                <!-- Divisi Pemilik / Pengelola -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Divisi <span class="text-rose-500">*</span></label>
                    <select name="divisi_id" required x-model="divisiId" @change="updatePreview()"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors font-medium">
                        @foreach($divisiList as $div)
                            <option value="{{ $div->id }}" {{ old('divisi_id') == $div->id ? 'selected' : '' }}>
                                Kode {{ $div->kode_divisi }} — {{ $div->nama_divisi }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1">Menentukan Jenis Aset secara otomatis.</p>
                </div>

                <!-- Jenis Aset (Otomatis dari Divisi) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Jenis Aset <span class="text-[10px] text-slate-400 font-normal">(Otomatis dari Divisi)</span>
                    </label>
                    <input type="hidden" name="jenis" :value="jenisAset">
                    <div class="px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-100/80 flex items-center justify-between font-bold text-slate-700 select-none">
                        <span class="truncate pr-2" x-text="jenisAsetDisplay"></span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider flex-shrink-0"
                              :class="jenisAset === 'kelolaan' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200'"
                              x-text="jenisAset === 'kelolaan' ? 'Kelolaan' : 'Tetap'">
                        </span>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Divisi 1-4 = Aset Tetap, Divisi 5-6 = Aset Kelolaan.</p>
                </div>

                <!-- Kategori Aset -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Barang <span class="text-rose-500">*</span></label>
                    <select name="kategori_id" required x-model="kategoriId" @change="onKategoriChange()"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors font-semibold">
                        @foreach($kategoriList as $kat)
                            <option value="{{ $kat->id }}" {{ old('kategori_id') == $kat->id ? 'selected' : '' }}>
                                {{ $kat->kode_kategori }} — {{ $kat->nama_kategori }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Master Barang (Item) Dependent Searchable Dropdown -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Barang / Jenis Barang <span class="text-rose-500">*</span></label>
                    <x-searchable-select name="barang_id"
                                         :dynamicItems="'filteredBarang'"
                                         model="barangId"
                                         change="updatePreview()"
                                         :required="true"
                                         placeholder="-- Cari / Pilih Jenis Barang --" />
                </div>

                <!-- Cara Perolehan -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cara Perolehan <span class="text-rose-500">*</span></label>
                    <select name="cara_perolehan" required x-model="caraPerolehan" @change="updatePreview()"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                        <option value="1">1 — Beli</option>
                        <option value="2">2 — Hibah / Donasi Barang</option>
                    </select>
                </div>

                <!-- Status Barang Saat Perolehan -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kondisi Perolehan <span class="text-rose-500">*</span></label>
                    <select name="status_barang" required x-model="statusBarang" @change="updatePreview()"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                        <option value="1">1 — Baru</option>
                        <option value="2">2 — Second</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- 2. DETAIL ASET (Spesifikasi Teknis & Lokasi) -->
        <div class="p-6 sm:p-7 bg-white rounded-3xl border border-slate-200/80 shadow-sm space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-xs">2</span>
                    DETAIL ASET (SPESIFIKASI & LOKASI)
                </h3>
                <span class="text-xs text-slate-400 font-medium">Spesifikasi Fisik, Lokasi & Amil</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-slate-700">Merk Aset <span class="text-slate-400 font-normal">(Opsional)</span></label>
                        @if(auth()->check() && auth()->user()->role === 'super_admin')
                            <button type="button" @click="bukaMerkModal()"
                                    class="inline-flex items-center gap-0.5 text-[10px] font-bold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-1.5 py-0.5 rounded-lg transition-colors">
                                <i class="ti ti-plus text-xs"></i> Tambah
                            </button>
                        @endif
                    </div>
                    <x-searchable-select name="merk_id"
                                         :dynamicItems="'merkItemsDynamic'"
                                         model="merkId"
                                         :required="false"
                                         placeholder="-- Pilih Merk (Opsional) --" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tipe / Model <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input type="text" name="tipe_model" placeholder="Contoh: ThinkPad X1 Carbon" value="{{ old('tipe_model') }}"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Produsen / Manufaktur <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input type="text" name="produsen" placeholder="Contoh: Lenovo Group Ltd." value="{{ old('produsen') }}"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">No. Seri / Kode Produksi <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input type="text" name="no_seri" placeholder="Contoh: SN-99482018471" value="{{ old('no_seri') }}"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun Produksi <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input type="number" name="tahun_produksi" min="1900" max="2099" placeholder="2024" value="{{ old('tahun_produksi', date('Y')) }}"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                </div>

                <!-- Lokasi Penempatan (Wajib untuk Kode jika Statis) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Lokasi Penempatan <span class="text-rose-500">*</span>
                        <span x-show="sifatBarang === 'S'" class="text-[10px] text-emerald-600 font-bold">(Masuk Kode Aset)</span>
                    </label>
                    <x-searchable-select name="lokasi_id"
                                         :items="$lokasiItems"
                                         model="lokasiId"
                                         change="updatePreview()"
                                         :required="true"
                                         placeholder="-- Cari / Pilih Lokasi --" />
                </div>

                <!-- Penanggung Jawab / Amil (Wajib untuk Kode jika Dinamis) -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Penanggung Jawab / Amil <span class="text-rose-500">*</span>
                        <span x-show="sifatBarang === 'D'" class="text-[10px] text-cyan-600 font-bold">(NIA Masuk Kode Aset)</span>
                    </label>
                    <x-searchable-select name="penanggung_jawab_id"
                                         :items="$pjItems"
                                         model="penanggungJawabId"
                                         change="updatePreview()"
                                         :required="true"
                                         placeholder="-- Cari / Pilih Penanggung Jawab --" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Kondisi Aset <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input type="text" name="deskripsi" placeholder="Contoh: Sangat baik / baru dibuka dari segel" value="{{ old('deskripsi') }}"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                </div>
            </div>
        </div>

        <!-- 3. PEMBELIAN & PENYUSUTAN (Nilai & Finansial) -->
        <div class="p-6 sm:p-7 bg-white rounded-3xl border border-slate-200/80 shadow-sm space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-xs">3</span>
                    PEMBELIAN & PENYUSUTAN
                </h3>
                <span class="text-xs text-slate-400 font-medium">Kalkulasi Garis Lurus</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Pembelian <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal_pembelian" required x-model="tanggalPembelian" @change="updatePreview()"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors font-semibold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Toko / Distributor <span class="text-rose-500">*</span></label>
                    <input type="text" name="toko_distributor" required placeholder="Contoh: PT Aneka Komputerindo" value="{{ old('toko_distributor') }}"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">No. Invoice <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input type="text" name="no_invoice" placeholder="Contoh: INV/202608/001" value="{{ old('no_invoice') }}"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Unit <span class="text-rose-500">*</span></label>
                    <input type="number" name="jumlah_unit" required min="1" x-model.number="jumlahUnit" value="{{ old('jumlah_unit', 1) }}"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Harga Satuan 
                        <span class="text-rose-500" x-show="!isGedungOrTanah">*</span>
                        <span class="text-slate-400 font-normal" x-show="isGedungOrTanah">(Opsional)</span>
                    </label>
                    <div class="flex rounded-xl border border-slate-200 bg-slate-50 focus-within:bg-white focus-within:ring-2 focus-within:ring-emerald-500/20 focus-within:border-emerald-500 transition-all overflow-hidden">
                        <span class="inline-flex items-center px-3.5 text-xs font-bold text-slate-500 bg-slate-100/90 border-r border-slate-200 select-none">Rp</span>
                        <input type="text" inputmode="numeric" :required="!isGedungOrTanah"
                               x-model="hargaSatuanDisplay"
                               @input="formatCurrency('hargaSatuan')"
                               placeholder="15.000.000"
                               class="w-full px-3.5 py-2.5 text-xs bg-transparent border-0 focus:outline-none focus:ring-0 font-bold text-slate-800">
                        <input type="hidden" name="harga_satuan" :value="hargaSatuan">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Umur Ekonomis (Tahun) 
                        <span class="text-rose-500" x-show="!isGedungOrTanah">*</span>
                        <span class="text-slate-400 font-normal" x-show="isGedungOrTanah">(Opsional)</span>
                        <span class="text-[11px] text-emerald-600 font-medium ml-1" x-show="isTanah">(Tanah tidak disusutkan)</span>
                    </label>
                    <input type="number" name="umur_ekonomis_tahun" :required="!isGedungOrTanah" :min="isGedungOrTanah ? 0 : 1" max="50"
                           :disabled="isTanah"
                           x-model.number="umurTahun" value="{{ old('umur_ekonomis_tahun', 5) }}"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors font-medium disabled:opacity-60 disabled:cursor-not-allowed">
                    <input type="hidden" name="umur_ekonomis_tahun" value="0" :disabled="!isTanah">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nilai Residu / Sisa <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <div class="flex rounded-xl border border-slate-200 bg-slate-50 focus-within:bg-white focus-within:ring-2 focus-within:ring-emerald-500/20 focus-within:border-emerald-500 transition-all overflow-hidden">
                        <span class="inline-flex items-center px-3.5 text-xs font-bold text-slate-500 bg-slate-100/90 border-r border-slate-200 select-none">Rp</span>
                        <input type="text" inputmode="numeric"
                               x-model="nilaiResiduDisplay"
                               @input="formatCurrency('nilaiResidu')"
                               placeholder="1.000.000"
                               class="w-full px-3.5 py-2.5 text-xs bg-transparent border-0 focus:outline-none focus:ring-0 font-bold text-slate-800">
                        <input type="hidden" name="nilai_residu" :value="nilaiResidu">
                    </div>
                </div>
            </div>

            <!-- Live Calculation Summary Box -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-slate-100">
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Estimasi Harga Total</span>
                    <span class="text-base font-extrabold text-slate-900" x-text="isGedungOrTanah && hargaTotal <= 0 ? '- (Belum dinilai)' : formatRupiah(hargaTotal)">Rp 0</span>
                </div>
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Estimasi Beban Penyusutan / Bulan</span>
                    <span class="text-base font-extrabold text-rose-600" x-text="isTanah ? '- (Tidak disusutkan)' : (isGedungOrTanah && hargaTotal <= 0 ? '- (Belum dinilai)' : formatRupiah(penyusutanBulan))">Rp 0</span>
                </div>
            </div>
        </div>

        <!-- 4. FOTO ASET -->
        <div class="p-6 sm:p-7 bg-white rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-xs">4</span>
                    FOTO ASET
                </h3>
                <span class="text-xs text-slate-400 font-medium">Format: JPG, PNG, WEBP (Maks. 10MB)</span>
            </div>

            <!-- Loading Indicator saat Kompresi Gambar -->
            <div x-show="isCompressingPhoto" x-cloak class="p-4 bg-emerald-50/70 rounded-2xl border border-emerald-200 flex items-center gap-3 animate-pulse">
                <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0">
                    <i class="ti ti-loader-2 text-lg animate-spin"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-emerald-900">Mengompresi & Mengonversi ke WebP...</p>
                    <p class="text-[11px] text-emerald-700">Resolusi disesuaikan maksimal 1200px agar upload cepat dan hemat memori.</p>
                </div>
            </div>

            <!-- Live Photo Preview Box dengan Badge Ukuran -->
            <div x-show="photoPreview && !isCompressingPhoto" x-cloak class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <img :src="photoPreview" alt="Pratinjau Foto" class="w-20 h-20 object-cover rounded-xl border border-slate-200 shadow-xs bg-white shrink-0">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1">
                                <i class="ti ti-check text-xs"></i> Terkompresi WebP
                            </span>
                            <template x-if="photoOriginalSize && photoCompressedSize">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-cyan-100 text-cyan-800 border border-cyan-200">
                                    <span x-text="photoOriginalSize"></span> → <span class="font-extrabold" x-text="photoCompressedSize"></span>
                                    (<span x-text="photoSavings"></span>)
                                </span>
                            </template>
                        </div>
                        <p class="text-xs font-semibold text-slate-700">Foto siap diunggah saat formulir disimpan.</p>
                        <button type="button" @click="clearPhoto()" class="text-xs text-rose-600 hover:text-rose-700 font-bold inline-flex items-center gap-1 pt-0.5">
                            <i class="ti ti-trash text-sm"></i> Batalkan / Hapus Foto
                        </button>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Upload Foto Utama Aset <span class="text-slate-400 font-normal">(Opsional)</span></label>
                <input type="file" name="foto_utama" accept="image/*" x-ref="photoInput" @change="handlePhotoChange($event)"
                       class="w-full text-xs text-slate-500 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                <p class="text-[11px] text-slate-400 mt-1">Otomatis di-resize (maks. 1200px) dan dikonversi ke WebP untuk performa optimal.</p>
            </div>
        </div>

        <!-- 5. KETERANGAN TAMBAHAN -->
        <div class="p-6 sm:p-7 bg-white rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-xs">5</span>
                    KETERANGAN TAMBAHAN
                </h3>
                <span class="text-xs text-slate-400 font-medium">Catatan Khusus Aset</span>
            </div>

            <div>
                <textarea name="keterangan_tambahan" rows="3" placeholder="Tuliskan catatan khusus atau informasi tambahan terkait aset..."
                          class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">{{ old('keterangan_tambahan') }}</textarea>
            </div>
        </div>

        <!-- Form Submit Bar -->
        <div class="flex items-center justify-end space-x-3 pt-2">
            <a href="{{ route('aset.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition-colors">
                Batal
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white text-xs font-extrabold uppercase tracking-wider shadow-md shadow-emerald-600/20 transition-all">
                Simpan & Terbitkan Kode Aset
            </button>
        </div>

    </form>

    <div x-show="merkModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
         @keydown.escape.window="merkModalOpen = false">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-6 space-y-4 my-8"
             @click.outside="merkModalOpen = false">

            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <i class="ti ti-tag-plus text-lg"></i>
                    </span>
                    <h3 class="text-base font-extrabold text-slate-900">Tambah Merk Baru</h3>
                </div>
                <button type="button" @click="merkModalOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <form @submit.prevent="simpanMerkBaru()" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Merk <span class="text-rose-500">*</span></label>
                    <input type="text" x-ref="merkInput" x-model="merkNamaBaru" maxlength="150" :disabled="merkSaving" placeholder="Contoh: Lenovo"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-bold text-slate-800 uppercase disabled:opacity-60">
                </div>

                <p x-show="merkError" x-text="merkError" x-cloak class="text-[11px] font-bold text-rose-600"></p>

                <div class="flex items-center justify-end space-x-3 pt-2 border-t border-slate-100">
                    <button type="button" @click="merkModalOpen = false"
                            class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition-colors">
                        Batal
                    </button>
                    <button type="submit" :disabled="merkSaving"
                            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 disabled:opacity-60 text-white text-xs font-extrabold uppercase tracking-wider shadow-md shadow-emerald-600/20 transition-all inline-flex items-center gap-1.5">
                        <i class="ti text-sm" :class="merkSaving ? 'ti-loader-2 animate-spin' : 'ti-check'"></i>
                        <span x-text="merkSaving ? 'Menyimpan...' : 'Simpan Merk'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('asetCreateForm', () => ({
        // Data Pembentuk Kode Aset 9-Komponen
        kategoriId: @js((string) old('kategori_id', $kategoriList->first()->id ?? '')),
        barangId: @js((string) old('barang_id', '')),
        sifatBarang: @js((string) old('sifat_barang', 'D')),
        penanggungJawabId: @js((string) old('penanggung_jawab_id', $pjList->first()->id ?? '')),
        lokasiId: @js((string) old('lokasi_id', $lokasiList->first()->id ?? '')),
        divisiId: @js((string) old('divisi_id', $divisiList->first()->id ?? '')),
        caraPerolehan: @js((string) old('cara_perolehan', '1')),
        statusBarang: @js((string) old('status_barang', '1')),
        tanggalPembelian: @js((string) old('tanggal_pembelian', date('Y-m-d'))),
        merkId: @js((string) old('merk_id', '')),
        merkItemsDynamic: @js($merkItems),
        merkModalOpen: false,
        merkSaving: false,
        merkError: '',
        merkNamaBaru: '',

        // Master Raw
        allKategori: @js($kategoriList),
        allBarang: @js($barangList),
        allDivisi: @js($divisiList),
        filteredBarang: [],

        // Preview Result
        previewCode: '-----------------',
        previewComponents: null,
        isLoadingPreview: false,

        // Finansial
        jumlahUnit: @js((int) old('jumlah_unit', 1)),
        hargaSatuan: @js((int) str_replace('.', '', (string) old('harga_satuan', 0))),
        hargaSatuanDisplay: @js(old('harga_satuan') ? number_format((float) str_replace('.', '', (string) old('harga_satuan')), 0, ',', '.') : ''),
        umurTahun: @js((int) old('umur_ekonomis_tahun', 5)),
        nilaiResidu: @js((int) str_replace('.', '', (string) old('nilai_residu', 0))),
        nilaiResiduDisplay: @js(old('nilai_residu') ? number_format((float) str_replace('.', '', (string) old('nilai_residu')), 0, ',', '.') : ''),
        photoPreview: null,
        photoPreviewUrl: null,
        isCompressingPhoto: false,
        photoOriginalSize: '',
        photoCompressedSize: '',
        photoSavings: '',

        init() {
            this.filterBarang();
            if (this.isTanah) {
                this.umurTahun = 0;
            }
            this.updatePreview();
        },

        get selectedKategori() {
            return (this.allKategori || []).find(k => String(k.id) === String(this.kategoriId));
        },

        get isGedungOrTanah() {
            return ['GD', 'TN'].includes(this.selectedKategori?.kode_kategori);
        },

        get isTanah() {
            return this.selectedKategori?.kode_kategori === 'TN';
        },

        filterBarang() {
            if (!this.kategoriId) {
                this.filteredBarang = [];
                this.barangId = '';
                return;
            }
            this.filteredBarang = this.allBarang
                .filter(b => String(b.kategori_id) === String(this.kategoriId))
                .map(b => ({
                    id: b.id,
                    code: b.kode_barang,
                    title: b.nama_barang,
                    subtitle: ''
                }));

            if (this.filteredBarang.length > 0) {
                if (!this.barangId || !this.filteredBarang.some(b => String(b.id) === String(this.barangId))) {
                    this.barangId = String(this.filteredBarang[0].id);
                }
            } else {
                this.barangId = '';
            }
        },

        onKategoriChange() {
            this.filterBarang();
            if (this.isTanah) {
                this.umurTahun = 0;
            }
            this.updatePreview();
        },

        updatePreview() {
            if (!this.kategoriId || !this.barangId || !this.divisiId) {
                this.previewCode = 'PILIH DATA LENGKAP';
                return;
            }

            this.isLoadingPreview = true;

            fetch('{{ route('aset.preview-kode') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    kategori_id: this.kategoriId,
                    barang_id: this.barangId,
                    sifat_barang: this.sifatBarang,
                    penanggung_jawab_id: this.penanggungJawabId,
                    lokasi_id: this.lokasiId,
                    divisi_id: this.divisiId,
                    cara_perolehan: this.caraPerolehan,
                    status_barang: this.statusBarang,
                    tanggal_pembelian: this.tanggalPembelian
                })
            })
            .then(res => res.json())
            .then(data => {
                this.isLoadingPreview = false;
                if (data.kode_aset) {
                    this.previewCode = data.kode_aset;
                    this.previewComponents = data.components;
                }
            })
            .catch(err => {
                this.isLoadingPreview = false;
                console.error(err);
            });
        },

        formatCurrency(field) {
            if (field === 'hargaSatuan') {
                let clean = String(this.hargaSatuanDisplay || '').replace(/\D/g, '');
                this.hargaSatuan = clean ? parseInt(clean, 10) : 0;
                this.hargaSatuanDisplay = this.hargaSatuan ? this.hargaSatuan.toLocaleString('id-ID') : '';
            } else if (field === 'nilaiResidu') {
                let clean = String(this.nilaiResiduDisplay || '').replace(/\D/g, '');
                this.nilaiResidu = clean ? parseInt(clean, 10) : 0;
                this.nilaiResiduDisplay = this.nilaiResidu ? this.nilaiResidu.toLocaleString('id-ID') : '';
            }
        },

        get hargaTotal() {
            return (Number(this.jumlahUnit) || 0) * (Number(this.hargaSatuan) || 0);
        },

        get selectedDivisi() {
            return (this.allDivisi || []).find(d => String(d.id) === String(this.divisiId));
        },

        get jenisAset() {
            if (this.selectedDivisi && (String(this.selectedDivisi.kode_divisi) === '5' || String(this.selectedDivisi.kode_divisi) === '6')) {
                return 'kelolaan';
            }
            return 'tetap';
        },

        get jenisAsetDisplay() {
            return this.jenisAset === 'kelolaan'
                ? 'Aset Kelolaan (Operasional Unit / Program)'
                : 'Aset Tetap (Milik Utama Al Azhar Peduli)';
        },

        get penyusutanBulan() {
            if (this.isTanah) return 0;
            let thn = Number(this.umurTahun) || 0;
            if (thn <= 0) return 0;
            let residu = Number(this.nilaiResidu) || 0;
            let disusutkan = Math.max(0, this.hargaTotal - residu);
            return Math.round(disusutkan / (thn * 12));
        },

        formatRupiah(val) {
            return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
        },

        formatBytes(bytes) {
            if (!bytes || bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },

        async handlePhotoChange(event) {
            const file = event.target.files?.[0];
            if (!file) return;

            this.photoOriginalSize = this.formatBytes(file.size);
            this.isCompressingPhoto = true;

            try {
                // Buat Image object dari file yang dipilih
                const img = new Image();
                const objectUrl = URL.createObjectURL(file);

                await new Promise((resolve, reject) => {
                    img.onload = () => {
                        URL.revokeObjectURL(objectUrl);
                        resolve();
                    };
                    img.onerror = () => {
                        URL.revokeObjectURL(objectUrl);
                        reject(new Error('Gagal memuat gambar.'));
                    };
                    img.src = objectUrl;
                });

                // Hitung dimensi baru proporsional (maksimum 1200px)
                const maxDim = 1200;
                let width = img.naturalWidth || img.width;
                let height = img.naturalHeight || img.height;

                if (width > maxDim || height > maxDim) {
                    if (width > height) {
                        height = Math.round((height * maxDim) / width);
                        width = maxDim;
                    } else {
                        width = Math.round((width * maxDim) / height);
                        height = maxDim;
                    }
                }

                // Render gambar ke canvas
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                // Konversi ke WebP blob (kualitas 80%) dengan fallback ke JPEG
                let blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/webp', 0.80));
                let fileExt = 'webp';
                let mimeType = 'image/webp';

                if (!blob) {
                    blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.80));
                    fileExt = 'jpg';
                    mimeType = 'image/jpeg';
                }

                if (!blob) {
                    throw new Error('Gagal mengekspor canvas.');
                }

                // Buat file WebP baru
                const baseName = file.name.substring(0, file.name.lastIndexOf('.')) || file.name;
                const compressedFile = new File([blob], `${baseName}.${fileExt}`, {
                    type: mimeType,
                    lastModified: Date.now()
                });

                // Ganti file di input file menggunakan DataTransfer API
                if (window.DataTransfer) {
                    const dt = new DataTransfer();
                    dt.items.add(compressedFile);
                    event.target.files = dt.files;
                }

                this.photoCompressedSize = this.formatBytes(compressedFile.size);
                const pct = Math.max(0, Math.round((1 - (compressedFile.size / file.size)) * 100));
                this.photoSavings = pct > 0 ? `Hemat ${pct}%` : 'Optimal';

                if (this.photoPreviewUrl) {
                    URL.revokeObjectURL(this.photoPreviewUrl);
                }
                this.photoPreviewUrl = URL.createObjectURL(blob);
                this.photoPreview = this.photoPreviewUrl;
            } catch (err) {
                console.warn('Kompresi client-side gagal, menggunakan file asli:', err);
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.photoPreview = e.target.result;
                };
                reader.readAsDataURL(file);
                this.photoCompressedSize = this.formatBytes(file.size);
                this.photoSavings = '0%';
            } finally {
                this.isCompressingPhoto = false;
            }
        },

        clearPhoto() {
            if (this.photoPreviewUrl) {
                URL.revokeObjectURL(this.photoPreviewUrl);
                this.photoPreviewUrl = null;
            }
            this.photoPreview = null;
            this.photoOriginalSize = '';
            this.photoCompressedSize = '';
            this.photoSavings = '';
            this.isCompressingPhoto = false;
            if (this.$refs.photoInput) {
                this.$refs.photoInput.value = '';
            }
        },

        bukaMerkModal() {
            this.merkError = '';
            this.merkNamaBaru = '';
            this.merkModalOpen = true;
            this.$nextTick(() => {
                if (this.$refs.merkInput) {
                    this.$refs.merkInput.focus();
                }
            });
        },

        async simpanMerkBaru() {
            const nama = (this.merkNamaBaru || '').trim();
            if (!nama) {
                this.merkError = 'Nama merk wajib diisi.';
                return;
            }
            this.merkSaving = true;
            this.merkError = '';
            try {
                const res = await fetch('{{ route('data.merk.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ nama_merk: nama })
                });
                if (res.ok) {
                    const data = await res.json();
                    if (!this.merkItemsDynamic.some(m => String(m.id) === String(data.id))) {
                        this.merkItemsDynamic.push({ id: data.id, code: '', title: data.nama_merk, subtitle: '' });
                    }
                    this.merkId = String(data.id);
                    this.merkModalOpen = false;
                } else if (res.status === 422) {
                    const data = await res.json().catch(() => null);
                    this.merkError = data?.errors?.nama_merk?.[0] || 'Nama merk sudah digunakan.';
                } else {
                    this.merkError = 'Gagal menyimpan merk. Coba lagi.';
                }
            } catch (err) {
                this.merkError = 'Gagal terhubung ke server.';
            } finally {
                this.merkSaving = false;
            }
        }
    }));
});
</script>
@endpush
@endsection
