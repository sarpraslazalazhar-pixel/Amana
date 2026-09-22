@extends('layouts.app')

@section('title', 'Edit Aset - ' . $aset->kode_aset)
@section('header-title', 'Edit Aset: ' . $aset->nama_aset)

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

<div class="max-w-5xl mx-auto space-y-6" x-data="asetEditForm">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Edit Data Aset</h2>
                <span class="px-2.5 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 font-mono font-bold text-xs border border-emerald-200">
                    {{ $aset->kode_aset }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Perbarui informasi spesifikasi, pembelian, atau foto aset</p>
        </div>
        <a href="{{ route('aset.show', $aset->id) }}"
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

    <form method="POST" action="{{ route('aset.update', $aset->id) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- 1. DATA ASET (Umum) -->
        <div class="p-6 sm:p-7 bg-white rounded-3xl border border-slate-200/80 shadow-sm space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-xs">1</span>
                    DATA ASET (UMUM)
                </h3>
                <span class="text-xs text-slate-400 font-medium">Identitas Pokok Aset</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Aset <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama_aset" required value="{{ old('nama_aset', $aset->nama_aset) }}"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                </div>

                <!-- Divisi Pemilik / Pengelola -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Divisi <span class="text-rose-500">*</span></label>
                    <select name="divisi_id" required x-model="divisiId"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors font-medium">
                        @foreach($divisiList as $div)
                            <option value="{{ $div->id }}" {{ old('divisi_id', $aset->divisi_id) == $div->id ? 'selected' : '' }}>
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

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Aset <span class="text-rose-500">*</span></label>
                    <select name="kategori_id" required x-model="kategoriId" @change="onKategoriChange"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($kategoriList as $kat)
                            <option value="{{ $kat->id }}" {{ old('kategori_id', $aset->kategori_id) == $kat->id ? 'selected' : '' }}>
                                {{ $kat->nama_kategori }} ({{ $kat->kode_kategori }})
                            </option>
                        @endforeach
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
                <span class="text-xs text-slate-400 font-medium">Spesifikasi Fisik & Pemegang</span>
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
                    <input type="text" name="tipe_model" value="{{ old('tipe_model', $aset->tipe_model) }}"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Produsen / Manufaktur <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input type="text" name="produsen" value="{{ old('produsen', $aset->produsen) }}"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">No. Seri / Kode Produksi <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input type="text" name="no_seri" value="{{ old('no_seri', $aset->no_seri) }}"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun Produksi <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input type="number" name="tahun_produksi" min="1900" max="2099" value="{{ old('tahun_produksi', $aset->tahun_produksi) }}"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Lokasi Penempatan <span class="text-rose-500">*</span></label>
                    <x-searchable-select name="lokasi_id"
                                         :items="$lokasiItems"
                                         value="{{ old('lokasi_id', $aset->lokasi_id) }}"
                                         :required="true"
                                         placeholder="-- Cari / Pilih Lokasi --" />
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Penanggung Jawab / Pemegang <span class="text-rose-500">*</span></label>
                    <x-searchable-select name="penanggung_jawab_id"
                                         :items="$pjItems"
                                         value="{{ old('penanggung_jawab_id', $aset->penanggung_jawab_id) }}"
                                         :required="true"
                                         placeholder="-- Cari / Pilih Penanggung Jawab --" />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Kondisi Aset <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input type="text" name="deskripsi" value="{{ old('deskripsi', $aset->deskripsi) }}"
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
                <span class="text-xs text-slate-400 font-medium">Kalkulasi Otomatis Garis Lurus</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Pembelian <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal_pembelian" required value="{{ old('tanggal_pembelian', $aset->tanggal_pembelian ? \Carbon\Carbon::parse($aset->tanggal_pembelian)->format('Y-m-d') : '') }}"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Toko / Distributor <span class="text-rose-500">*</span></label>
                    <input type="text" name="toko_distributor" required value="{{ old('toko_distributor', $aset->toko_distributor) }}"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">No. Invoice <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input type="text" name="no_invoice" value="{{ old('no_invoice', $aset->no_invoice) }}"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Unit <span class="text-rose-500">*</span></label>
                    <input type="number" name="jumlah_unit" required min="1" x-model.number="jumlahUnit" value="{{ old('jumlah_unit', $aset->jumlah_unit) }}"
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
                               placeholder="10.000.000"
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
                           x-model.number="umurTahun" value="{{ old('umur_ekonomis_tahun', $aset->umur_ekonomis_tahun) }}"
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

        <!-- 4. FOTO & DOKUMEN -->
        <div class="p-6 sm:p-7 bg-white rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-xs">4</span>
                    FOTO ASET
                </h3>
                <span class="text-xs text-slate-400 font-medium">Format: JPG, PNG, WEBP (Maks. 10MB)</span>
            </div>

            <!-- Foto Saat Ini -->
            @if($aset->foto_utama)
                <div x-show="!photoPreview" class="flex items-center gap-4 p-3.5 bg-slate-50 rounded-2xl border border-slate-200/80">
                    <img src="{{ asset('storage/' . $aset->foto_utama) }}" alt="{{ $aset->nama_aset }}"
                         class="w-16 h-16 object-cover rounded-xl border border-slate-200 shadow-xs">
                    <div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-700">Foto Saat Ini</span>
                        <p class="text-xs font-bold text-slate-800 mt-1">Foto aktif yang sedang digunakan</p>
                        <p class="text-[11px] text-slate-400">Pilih file baru di bawah jika ingin mengganti foto ini</p>
                    </div>
                </div>
            @endif

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

            <!-- Live Photo Preview Box (Jika memilih foto baru) -->
            <div x-show="photoPreview && !isCompressingPhoto" x-cloak class="p-4 bg-emerald-50/50 rounded-2xl border border-emerald-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <img :src="photoPreview" alt="Pratinjau Foto Baru" class="w-20 h-20 object-cover rounded-xl border border-emerald-200 shadow-xs bg-white shrink-0">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1">
                                <i class="ti ti-check text-xs"></i> Foto Baru (WebP)
                            </span>
                            <template x-if="photoOriginalSize && photoCompressedSize">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-cyan-100 text-cyan-800 border border-cyan-200">
                                    <span x-text="photoOriginalSize"></span> → <span class="font-extrabold" x-text="photoCompressedSize"></span>
                                    (<span x-text="photoSavings"></span>)
                                </span>
                            </template>
                        </div>
                        <p class="text-xs font-semibold text-slate-800">Foto ini akan menggantikan foto lama saat Anda menyimpan perubahan.</p>
                        <button type="button" @click="clearPhoto()" class="text-xs text-rose-600 hover:text-rose-700 font-bold inline-flex items-center gap-1 pt-0.5">
                            <i class="ti ti-trash text-sm"></i> Batalkan / Gunakan Foto Lama
                        </button>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Ganti Foto Utama <span class="text-slate-400 font-normal">(Opsional)</span></label>
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
                <span class="text-xs text-slate-400 font-medium">Catatan Tambahan</span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan <span class="text-slate-400 font-normal">(Opsional)</span></label>
                <textarea name="keterangan_tambahan" rows="3" placeholder="Tuliskan catatan khusus atau informasi tambahan terkait aset..."
                          class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">{{ old('keterangan_tambahan', $aset->keterangan_tambahan) }}</textarea>
            </div>
        </div>

        <!-- Form Submit Bar -->
        <div class="flex items-center justify-end space-x-3 pt-2">
            <a href="{{ route('aset.show', $aset->id) }}" class="px-5 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition-colors">
                Batal
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white text-xs font-extrabold uppercase tracking-wider shadow-md shadow-emerald-600/20 transition-all">
                Simpan Perubahan Aset
            </button>
        </div>

    </form>

    <!-- 6. LAMPIRAN DOKUMEN (Form terpisah dari form edit utama) -->
    <div class="p-6 sm:p-7 bg-white rounded-3xl border border-slate-200/80 shadow-sm space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-xs">6</span>
                LAMPIRAN DOKUMEN
            </h3>
            <span class="text-xs text-slate-400 font-medium">Maks. 5 file • PDF, Gambar, Word, Excel (≤ 5 MB)</span>
        </div>

        <!-- Daftar Lampiran yang Sudah Ada -->
        @if($aset->lampiran && $aset->lampiran->count() > 0)
            <div class="space-y-2">
                @foreach($aset->lampiran as $lampiran)
                    <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-2xl border border-slate-200/70 group hover:border-emerald-200 transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center border shrink-0
                                {{ $lampiran->is_pdf ? 'bg-rose-50 text-rose-600 border-rose-100' : ($lampiran->is_image ? 'bg-cyan-50 text-cyan-600 border-cyan-100' : 'bg-amber-50 text-amber-600 border-amber-100') }}">
                                <i class="ti {{ $lampiran->icon_class }} text-base"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-800 truncate" title="{{ $lampiran->file_name }}">
                                    {{ $lampiran->label ?: $lampiran->file_name }}
                                </p>
                                <p class="text-[10px] text-slate-400">
                                    {{ $lampiran->file_name }} • {{ $lampiran->formatted_size }} • {{ $lampiran->created_at->translatedFormat('d M Y') }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <a href="{{ route('aset.lampiran.download', $lampiran->id) }}"
                               class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 transition-colors"
                               title="Download">
                                <i class="ti ti-download text-sm"></i>
                            </a>
                            @if(auth()->check() && auth()->user()->role === 'super_admin')
                                <form method="POST" action="{{ route('aset.lampiran.destroy', $lampiran->id) }}"
                                      onsubmit="return confirm('Hapus lampiran \'{{ $lampiran->file_name }}\'?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-1.5 rounded-lg text-rose-400 hover:bg-rose-50 hover:text-rose-600 transition-colors"
                                            title="Hapus Lampiran">
                                        <i class="ti ti-trash text-sm"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/70 text-center">
                <i class="ti ti-file-off text-2xl text-slate-300"></i>
                <p class="text-xs text-slate-400 mt-1">Belum ada lampiran dokumen untuk aset ini.</p>
            </div>
        @endif

        <!-- Form Upload Lampiran Baru -->
        @php
            $lampiranCount = $aset->lampiran ? $aset->lampiran->count() : 0;
            $sisaSlot = 5 - $lampiranCount;
        @endphp

        @if($sisaSlot > 0)
            <form method="POST" action="{{ route('aset.lampiran.store', $aset->id) }}" enctype="multipart/form-data"
                  class="p-4 rounded-2xl bg-emerald-50/50 border border-emerald-200/60 space-y-3">
                @csrf
                <div class="flex items-center gap-2 mb-1">
                    <i class="ti ti-upload text-emerald-600"></i>
                    <span class="text-xs font-bold text-emerald-800">Unggah Lampiran Baru</span>
                    <span class="text-[10px] text-emerald-600 bg-emerald-100 px-1.5 py-0.5 rounded-full font-bold border border-emerald-200">{{ $sisaSlot }} slot tersisa</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">File Dokumen <span class="text-rose-500">*</span></label>
                        <input type="file" name="lampiran_file" required
                               accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"
                               class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-700 hover:file:bg-emerald-200 cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Label / Keterangan <span class="text-slate-400 font-normal">(Opsional)</span></label>
                        <input type="text" name="lampiran_label" maxlength="255" placeholder="Contoh: Invoice pembelian, Nota toko..."
                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                    </div>
                </div>

                @if($errors->has('lampiran_file'))
                    <p class="text-[11px] font-bold text-rose-600">{{ $errors->first('lampiran_file') }}</p>
                @endif

                <div class="flex justify-end">
                    <button type="submit"
                            class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold inline-flex items-center gap-1.5 shadow-sm transition-colors">
                        <i class="ti ti-upload text-sm"></i>
                        Unggah Lampiran
                    </button>
                </div>
            </form>
        @else
            <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-800 font-medium flex items-center gap-2">
                <i class="ti ti-alert-triangle text-sm"></i>
                Batas maksimal 5 lampiran telah tercapai. Hapus lampiran lama untuk mengunggah yang baru.
            </div>
        @endif
    </div>

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
    Alpine.data('asetEditForm', () => ({
        kategoriId: @js((string) old('kategori_id', $aset->kategori_id)),
        allKategori: @js($kategoriList),
        divisiId: @js((string) old('divisi_id', $aset->divisi_id ?? ($divisiList->first()->id ?? ''))),
        allDivisi: @js($divisiList),
        jumlahUnit: @js((int) old('jumlah_unit', $aset->jumlah_unit)),
        hargaSatuan: @js((int) old('harga_satuan', (int) $aset->harga_satuan)),
        hargaSatuanDisplay: @js((int) old('harga_satuan', (int) $aset->harga_satuan) > 0 ? number_format((float) str_replace('.', '', (string) old('harga_satuan', (int) $aset->harga_satuan)), 0, ',', '.') : ''),
        umurTahun: @js((int) old('umur_ekonomis_tahun', $aset->umur_ekonomis_tahun)),
        nilaiResidu: @js((int) old('nilai_residu', (int) $aset->nilai_residu)),
        nilaiResiduDisplay: @js((int) old('nilai_residu', (int) $aset->nilai_residu) > 0 ? number_format((float) str_replace('.', '', (string) old('nilai_residu', (int) $aset->nilai_residu)), 0, ',', '.') : ''),
        merkId: @js((string) old('merk_id', $aset->merk_id)),
        merkItemsDynamic: @js($merkItems),
        merkModalOpen: false,
        merkSaving: false,
        merkError: '',
        merkNamaBaru: '',
        photoPreview: null,
        photoPreviewUrl: null,
        isCompressingPhoto: false,
        photoOriginalSize: '',
        photoCompressedSize: '',
        photoSavings: '',

        init() {
            if (this.isTanah) {
                this.umurTahun = 0;
            }
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

        onKategoriChange() {
            if (this.isTanah) {
                this.umurTahun = 0;
            }
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
