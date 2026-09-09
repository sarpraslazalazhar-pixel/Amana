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
                    <select name="kategori_id" required class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
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
                        <label class="block text-xs font-semibold text-slate-700">Merk Aset <span class="text-rose-500">*</span></label>
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
                                         :required="true"
                                         placeholder="-- Pilih Merk --" />
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
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Harga Satuan <span class="text-rose-500">*</span></label>
                    <div class="flex rounded-xl border border-slate-200 bg-slate-50 focus-within:bg-white focus-within:ring-2 focus-within:ring-emerald-500/20 focus-within:border-emerald-500 transition-all overflow-hidden">
                        <span class="inline-flex items-center px-3.5 text-xs font-bold text-slate-500 bg-slate-100/90 border-r border-slate-200 select-none">Rp</span>
                        <input type="text" inputmode="numeric" required
                               x-model="hargaSatuanDisplay"
                               @input="formatCurrency('hargaSatuan')"
                               placeholder="10.000.000"
                               class="w-full px-3.5 py-2.5 text-xs bg-transparent border-0 focus:outline-none focus:ring-0 font-bold text-slate-800">
                        <input type="hidden" name="harga_satuan" :value="hargaSatuan">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Umur Ekonomis (Tahun) <span class="text-rose-500">*</span></label>
                    <input type="number" name="umur_ekonomis_tahun" required min="1" max="50" x-model.number="umurTahun" value="{{ old('umur_ekonomis_tahun', $aset->umur_ekonomis_tahun) }}"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors font-medium">
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
                    <span class="text-base font-extrabold text-slate-900" x-text="formatRupiah(hargaTotal)">Rp 0</span>
                </div>
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Estimasi Beban Penyusutan / Bulan</span>
                    <span class="text-base font-extrabold text-rose-600" x-text="formatRupiah(penyusutanBulan)">Rp 0</span>
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

            <!-- Live Photo Preview Box (Jika memilih foto baru) -->
            <div x-show="photoPreview" x-cloak class="p-4 bg-emerald-50/50 rounded-2xl border border-emerald-200 flex items-center gap-4">
                <img :src="photoPreview" alt="Pratinjau Foto Baru" class="w-20 h-20 object-cover rounded-xl border border-emerald-200 shadow-xs">
                <div class="space-y-1">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        Foto Baru Dipilih
                    </span>
                    <p class="text-xs font-semibold text-slate-800">Foto ini akan menggantikan foto lama saat Anda menyimpan perubahan.</p>
                    <button type="button" @click="clearPhoto()" class="text-xs text-rose-600 hover:text-rose-700 font-bold inline-flex items-center gap-1">
                        <i class="ti ti-trash text-sm"></i> Batalkan / Gunakan Foto Lama
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Ganti Foto Utama <span class="text-slate-400 font-normal">(Opsional)</span></label>
                <input type="file" name="foto_utama" accept="image/*" x-ref="photoInput" @change="handlePhotoChange($event)"
                       class="w-full text-xs text-slate-500 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                <p class="text-[11px] text-slate-400 mt-1.5">Mendukung format gambar resolusi tinggi kamera (JPG, PNG, WEBP, GIF).</p>
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
        divisiId: @js((string) old('divisi_id', $aset->divisi_id ?? ($divisiList->first()->id ?? ''))),
        allDivisi: @js($divisiList),
        jumlahUnit: @js((int) old('jumlah_unit', $aset->jumlah_unit)),
        hargaSatuan: @js((int) old('harga_satuan', (int) $aset->harga_satuan)),
        hargaSatuanDisplay: @js(number_format((float) str_replace('.', '', (string) old('harga_satuan', (int) $aset->harga_satuan)), 0, ',', '.')),
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
            let thn = Number(this.umurTahun) || 1;
            let residu = Number(this.nilaiResidu) || 0;
            let disusutkan = Math.max(0, this.hargaTotal - residu);
            return Math.round(disusutkan / (thn * 12));
        },

        formatRupiah(val) {
            return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
        },

        handlePhotoChange(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.photoPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        clearPhoto() {
            this.photoPreview = null;
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
