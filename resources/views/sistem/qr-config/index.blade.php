@extends('layouts.app')

@section('title', 'Konfigurasi QR & Portal Scan Publik')

@section('content')
<div x-data="qrConfigApp()" class="space-y-6 pb-12">
    
    <!-- Top Header & Actions -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-600 inline-flex items-center gap-1">
                    <i class="ti ti-home"></i> Beranda
                </a>
                <span>/</span>
                <span class="text-slate-500 font-medium">Pengaturan</span>
                <span>/</span>
                <span class="text-emerald-700 font-bold">Konfigurasi QR</span>
            </nav>
            <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                <i class="ti ti-settings-2 text-emerald-600 text-2xl"></i>
                Konfigurasi QR & Portal Scan Publik
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Atur informasi yang tampil saat QR Code discan melalui smartphone serta keterangan teks label cetak fisik.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <form action="{{ route('pengaturan.qr-config.reset') }}" method="POST"
                  onsubmit="return confirm('Apakah Anda yakin ingin mereset seluruh konfigurasi QR ke setelan bawaan standar sistem?')">
                @csrf
                <button type="submit"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold inline-flex items-center gap-1.5 transition-colors shadow-xs">
                    <i class="ti ti-rotate-clockwise text-slate-500"></i>
                    Reset Bawaan
                </button>
            </form>

            <button type="button" @click="submitForm()"
                    class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-700 hover:to-emerald-700 text-white text-xs font-bold inline-flex items-center gap-2 shadow-md shadow-emerald-600/20 transition-all">
                <i class="ti ti-device-floppy text-base"></i>
                Simpan Konfigurasi
            </button>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <i class="ti ti-circle-check text-emerald-600 text-lg"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900">
                <i class="ti ti-x"></i>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium space-y-1 shadow-xs">
            <div class="flex items-center gap-2 font-bold mb-1">
                <i class="ti ti-alert-triangle text-rose-600 text-base"></i>
                <span>Terdapat kesalahan pada input:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-rose-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Main Navigation Tabs -->
    <div class="flex border-b border-slate-200 bg-white px-6 pt-3 rounded-2xl border shadow-xs gap-4">
        <button type="button"
                @click="activeTab = 'scan'"
                :class="activeTab === 'scan' ? 'border-emerald-600 text-emerald-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 font-medium'"
                class="pb-3 border-b-2 text-xs flex items-center gap-2 transition-all">
            <i class="ti ti-device-mobile text-base"></i>
            <span>1. Tampilan Scan Publik (Smartphone)</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-50 text-emerald-700 font-bold border border-emerald-200">
                Portal /p/{kode}
            </span>
        </button>

        <button type="button"
                @click="activeTab = 'label'"
                :class="activeTab === 'label' ? 'border-emerald-600 text-emerald-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 font-medium'"
                class="pb-3 border-b-2 text-xs flex items-center gap-2 transition-all">
            <i class="ti ti-tag text-base"></i>
            <span>2. Keterangan Label Cetak QR</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] bg-cyan-50 text-cyan-700 font-bold border border-cyan-200">
                Stiker Fisik
            </span>
        </button>
    </div>

    <!-- Main Form & Realtime Interactive Preview -->
    <form id="qrConfigForm" action="{{ route('pengaturan.qr-config.update') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- LEFT COLUMN: Settings Controls (7 Cols) -->
            <div class="lg:col-span-7 space-y-6">

                <!-- ================= TAB 1: SCAN SMARTPHONE ================= -->
                <div x-show="activeTab === 'scan'" class="space-y-6">
                    
                    <!-- 1. Informasi Umum -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                        <div class="p-4 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 text-xs font-black flex items-center justify-center">1</span>
                                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Informasi Umum</h3>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="setGroup('umum', true)" class="text-[11px] text-emerald-600 hover:underline font-semibold">Pilih Semua</button>
                                <span class="text-slate-300">|</span>
                                <button type="button" @click="setGroup('umum', false)" class="text-[11px] text-slate-400 hover:text-slate-600 font-semibold">Kosongkan</button>
                            </div>
                        </div>
                        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-3.5">
                            <x-qr-toggle-item name="show_nama_aset" label="Nama Aset" model="form.show_nama_aset" desc="Nama lengkap unit aset" />
                            <x-qr-toggle-item name="show_kategori" label="Kategori" model="form.show_kategori" desc="Kategori barang (Elektronik, dll)" />
                            <x-qr-toggle-item name="show_kode_aset" label="Kode Aset" model="form.show_kode_aset" desc="Kode format resmi 17 digit" />
                            <x-qr-toggle-item name="show_kode_sistem" label="Kode Sistem" model="form.show_kode_sistem" desc="ID nomor urut register database" />
                            <x-qr-toggle-item name="show_foto" label="Foto Aset" model="form.show_foto" desc="Foto dokumentasi aset fisik" />
                            <x-qr-toggle-item name="show_keterangan_tambahan" label="Keterangan Tambahan" model="form.show_keterangan_tambahan" desc="Catatan khusus terkait aset" />
                            <x-qr-toggle-item name="show_lampiran" label="Lampiran" model="form.show_lampiran" desc="Daftar file/invoice terlampir" />
                            <x-qr-toggle-item name="show_status_aset" label="Status Aset" model="form.show_status_aset" desc="Badge status Aktif / Non-Aktif" />
                        </div>
                    </div>

                    <!-- 2. Kondisi Khusus Status Non-Aktif -->
                    <div class="bg-white rounded-2xl border border-amber-200 bg-amber-50/20 shadow-xs overflow-hidden">
                        <div class="p-4 bg-amber-50/80 border-b border-amber-200 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-lg bg-amber-200 text-amber-900 text-xs font-black flex items-center justify-center">2</span>
                                <div>
                                    <h3 class="text-xs font-bold text-amber-950 uppercase tracking-wide">Jika Status Aset adalah Non-Aktif</h3>
                                    <p class="text-[11px] text-amber-700">Item yang ditampilkan saat status aset berstatus Non-Aktif/Purna Pakai</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-3.5">
                            <x-qr-toggle-item name="show_nonaktif_tanggal" label="Tanggal Non-Aktif" model="form.show_nonaktif_tanggal" desc="Tanggal diputuskan non-aktif" />
                            <x-qr-toggle-item name="show_nonaktif_sebab" label="Sebab" model="form.show_nonaktif_sebab" desc="Alasan rusak/hilang/dijual" />
                            <x-qr-toggle-item name="show_nonaktif_keterangan" label="Keterangan" model="form.show_nonaktif_keterangan" desc="Catatan detail purna pakai" />
                        </div>
                    </div>

                    <!-- 3. Detil Aset -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                        <div class="p-4 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 text-xs font-black flex items-center justify-center">3</span>
                                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Detil Spesifikasi Aset</h3>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="setGroup('detil', true)" class="text-[11px] text-emerald-600 hover:underline font-semibold">Pilih Semua</button>
                                <span class="text-slate-300">|</span>
                                <button type="button" @click="setGroup('detil', false)" class="text-[11px] text-slate-400 hover:text-slate-600 font-semibold">Kosongkan</button>
                            </div>
                        </div>
                        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-3.5">
                            <x-qr-toggle-item name="show_merk" label="Merk" model="form.show_merk" desc="Merk pabrikan perangkat" />
                            <x-qr-toggle-item name="show_tipe" label="Tipe / Model" model="form.show_tipe" desc="Tipe spesifik perangkat" />
                            <x-qr-toggle-item name="show_produsen" label="Produsen" model="form.show_produsen" desc="Nama perusahaan perakit" />
                            <x-qr-toggle-item name="show_no_seri" label="No. Seri / Kode Produksi" model="form.show_no_seri" desc="Serial number resmi" />
                            <x-qr-toggle-item name="show_tahun_produksi" label="Tahun Produksi" model="form.show_tahun_produksi" desc="Tahun pembuatan unit" />
                            <x-qr-toggle-item name="show_deskripsi" label="Deskripsi Fisik" model="form.show_deskripsi" desc="Deskripsi kondisi saat perolehan" />
                        </div>
                    </div>

                    <!-- 4. Pembelian & Keuangan -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                        <div class="p-4 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 text-xs font-black flex items-center justify-center">4</span>
                                <div>
                                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Data Pembelian & Invoice</h3>
                                    <p class="text-[11px] text-slate-500">Informasi nilai beli awal aset (Sensitif / Opsional)</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="setGroup('pembelian', true)" class="text-[11px] text-emerald-600 hover:underline font-semibold">Pilih Semua</button>
                                <span class="text-slate-300">|</span>
                                <button type="button" @click="setGroup('pembelian', false)" class="text-[11px] text-slate-400 hover:text-slate-600 font-semibold">Kosongkan</button>
                            </div>
                        </div>
                        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-3.5">
                            <x-qr-toggle-item name="show_tanggal_pembelian" label="Tanggal Pembelian" model="form.show_tanggal_pembelian" desc="Tanggal transaksi pembelian" />
                            <x-qr-toggle-item name="show_toko_distributor" label="Toko / Distributor" model="form.show_toko_distributor" desc="Vendor tempat pengadaan" />
                            <x-qr-toggle-item name="show_no_invoice" label="No. Invoice" model="form.show_no_invoice" desc="Nomor bukti kwitansi pembelian" />
                            <x-qr-toggle-item name="show_jumlah_unit" label="Jumlah Unit" model="form.show_jumlah_unit" desc="Kuantitas unit dalam aset" />
                            <x-qr-toggle-item name="show_harga_satuan" label="Harga Satuan" model="form.show_harga_satuan" desc="Nominal per unit" />
                            <x-qr-toggle-item name="show_harga_total" label="Harga Total" model="form.show_harga_total" desc="Akumulasi total nilai beli" />
                        </div>
                    </div>

                    <!-- 5. Umur & Penyusutan -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                        <div class="p-4 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 text-xs font-black flex items-center justify-center">5</span>
                                <div>
                                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Umur & Nilai Penyusutan</h3>
                                    <p class="text-[11px] text-slate-500">Kalkulasi nilai buku dan depresiasi garis lurus</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="setGroup('penyusutan', true)" class="text-[11px] text-emerald-600 hover:underline font-semibold">Pilih Semua</button>
                                <span class="text-slate-300">|</span>
                                <button type="button" @click="setGroup('penyusutan', false)" class="text-[11px] text-slate-400 hover:text-slate-600 font-semibold">Kosongkan</button>
                            </div>
                        </div>
                        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-3.5">
                            <x-qr-toggle-item name="show_umur_ekonomi" label="Umur Ekonomi" model="form.show_umur_ekonomi" desc="Masa manfaat dalam tahun" />
                            <x-qr-toggle-item name="show_penyusutan_per_bulan" label="Nilai Penyusutan / Bulan (Rp)" model="form.show_penyusutan_per_bulan" desc="Beban depresiasi bulanan" />
                            <x-qr-toggle-item name="show_usia_aset" label="Usia Aset Berjalan" model="form.show_usia_aset" desc="Durasi sejak dibeli s/d hari ini" />
                            <x-qr-toggle-item name="show_nilai_sekarang" label="Nilai Sekarang (Rp)" model="form.show_nilai_sekarang" desc="Estimasi nilai buku aset saat ini" />
                        </div>
                    </div>

                    <!-- 6. Keuangan, Agenda, dan Jurnal -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                        <div class="p-4 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 text-xs font-black flex items-center justify-center">6</span>
                                <div>
                                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Keuangan, Agenda & Jurnal</h3>
                                    <p class="text-[11px] text-slate-500">Data operasional yang tampil dalam bentuk kartu Accordion</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-3.5">
                            <x-qr-toggle-item name="show_keuangan" label="Transaksi Keuangan" model="form.show_keuangan" desc="Catatan pengeluaran & biaya perawatan aset" />
                            <x-qr-toggle-item name="show_agenda" label="Agenda Aset" model="form.show_agenda" desc="Jadwal servis & pengingat berkala" />
                            <x-qr-toggle-item name="show_jurnal" label="Jurnal Aset" model="form.show_jurnal" desc="Catatan insiden & histori kejadian" />
                        </div>
                    </div>

                    <!-- 7. Riwayat Aset -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                        <div class="p-4 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 text-xs font-black flex items-center justify-center">7</span>
                                <div>
                                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Riwayat Perpindahan & Status</h3>
                                    <p class="text-[11px] text-slate-500">Histori pemegang penanggung jawab dan lokasi</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 space-y-4">
                            <!-- Radio Mode Tampilan -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-2">Mode Tampilan Riwayat di Portal Scan:</label>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                    <label class="relative flex items-center p-3 rounded-xl border cursor-pointer transition-all"
                                           :class="form.riwayat_mode === 'terakhir' ? 'bg-emerald-50/60 border-emerald-500 text-emerald-950 font-bold' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                        <input type="radio" name="riwayat_mode" value="terakhir" x-model="form.riwayat_mode" class="text-emerald-600 focus:ring-emerald-500 mr-2.5">
                                        <div class="text-xs">
                                            <span class="block">Riwayat Terakhir</span>
                                            <span class="text-[10px] font-normal text-slate-500">Hanya posisi saat ini</span>
                                        </div>
                                    </label>

                                    <label class="relative flex items-center p-3 rounded-xl border cursor-pointer transition-all"
                                           :class="form.riwayat_mode === 'semua' ? 'bg-emerald-50/60 border-emerald-500 text-emerald-950 font-bold' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                        <input type="radio" name="riwayat_mode" value="semua" x-model="form.riwayat_mode" class="text-emerald-600 focus:ring-emerald-500 mr-2.5">
                                        <div class="text-xs">
                                            <span class="block">Semua Riwayat</span>
                                            <span class="text-[10px] font-normal text-slate-500">Seluruh linimasa mutasi</span>
                                        </div>
                                    </label>

                                    <label class="relative flex items-center p-3 rounded-xl border cursor-pointer transition-all"
                                           :class="form.riwayat_mode === 'tidak_tampil' ? 'bg-slate-100 border-slate-400 text-slate-900 font-bold' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                        <input type="radio" name="riwayat_mode" value="tidak_tampil" x-model="form.riwayat_mode" class="text-emerald-600 focus:ring-emerald-500 mr-2.5">
                                        <div class="text-xs">
                                            <span class="block">Tidak Ditampilkan</span>
                                            <span class="text-[10px] font-normal text-slate-500">Sembunyikan modul</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Sub Item Riwayat yang Muncul -->
                            <div x-show="form.riwayat_mode !== 'tidak_tampil'" x-transition class="pt-3 border-t border-slate-100">
                                <p class="text-xs font-bold text-slate-700 mb-2">Jika Riwayat Aset Ditampilkan, apa saja item yang muncul?</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                    <x-qr-toggle-item name="show_riwayat_tanggal" label="Tanggal" model="form.show_riwayat_tanggal" desc="Tanggal mulai efektif" />
                                    <x-qr-toggle-item name="show_riwayat_penanggung_jawab" label="Penanggung Jawab" model="form.show_riwayat_penanggung_jawab" desc="Nama PIC / Amil pemegang" />
                                    <x-qr-toggle-item name="show_riwayat_lokasi" label="Lokasi" model="form.show_riwayat_lokasi" desc="Gedung / Ruangan penempatan" />
                                    <x-qr-toggle-item name="show_riwayat_jumlah" label="Jumlah Unit" model="form.show_riwayat_jumlah" desc="Kuantitas unit fisik" />
                                    <x-qr-toggle-item name="show_riwayat_kondisi" label="Kondisi (%)" model="form.show_riwayat_kondisi" desc="Persentase kelayakan fisik" />
                                    <x-qr-toggle-item name="show_riwayat_kelengkapan" label="Kelengkapan (%)" model="form.show_riwayat_kelengkapan" desc="Persentase kelengkapan unit" />
                                    <x-qr-toggle-item name="show_riwayat_keterangan" label="Keterangan" model="form.show_riwayat_keterangan" desc="Catatan mutasi riwayat" />
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ================= TAB 2: LABEL CETAK QR ================= -->
                <div x-show="activeTab === 'label'" class="space-y-6" x-cloak>
                    
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-6">
                        <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                            <span class="w-8 h-8 rounded-xl bg-cyan-100 text-cyan-800 text-sm font-black flex items-center justify-center">
                                <i class="ti ti-printer"></i>
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Pengaturan Keterangan Cetak & Unduh Label QR-Code</h3>
                                <p class="text-xs text-slate-500">Tiga baris teks yang dicetak pada stiker fisik QR-Code.</p>
                            </div>
                        </div>

                        <!-- 1. Header Judul -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-800">
                                1. Judul Atas Stiker (Maks 25 Karakter)
                            </label>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label class="flex items-center p-3 rounded-xl border cursor-pointer transition-all"
                                       :class="form.label_judul_pilihan === 'preset' ? 'bg-cyan-50/60 border-cyan-500 text-cyan-950 font-bold' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                    <input type="radio" name="label_judul_pilihan" value="preset" x-model="form.label_judul_pilihan" class="text-cyan-600 focus:ring-cyan-500 mr-2.5">
                                    <div class="text-xs">
                                        <span class="block">Preset Standar:</span>
                                        <span class="text-[11px] font-mono text-cyan-800 font-extrabold">MILIK LAZ AL AZHAR</span>
                                    </div>
                                </label>

                                <label class="flex items-center p-3 rounded-xl border cursor-pointer transition-all"
                                       :class="form.label_judul_pilihan === 'custom' ? 'bg-cyan-50/60 border-cyan-500 text-cyan-950 font-bold' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                    <input type="radio" name="label_judul_pilihan" value="custom" x-model="form.label_judul_pilihan" class="text-cyan-600 focus:ring-cyan-500 mr-2.5">
                                    <div class="text-xs">
                                        <span class="block">Lainnya... (Kustom):</span>
                                        <span class="text-[10px] font-normal text-slate-500">Ketik judul instansi sendiri</span>
                                    </div>
                                </label>
                            </div>

                            <div x-show="form.label_judul_pilihan === 'custom'" x-transition class="pt-2">
                                <div class="relative">
                                    <input type="text" name="label_judul_custom" x-model="form.label_judul_custom" maxlength="25"
                                           placeholder="Contoh: YPI AL AZHAR PUSAT"
                                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 uppercase">
                                    <span class="absolute right-3 top-2.5 text-[10px] font-bold text-slate-400"
                                          x-text="(form.label_judul_custom ? form.label_judul_custom.length : 0) + '/25'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Baris Pertama -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-800">
                                2. Keterangan Baris Pertama (Tengah)
                            </label>
                            <select name="label_baris_1" x-model="form.label_baris_1"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 bg-white">
                                <option value="nama_aset">Nama Aset (Rekomendasi)</option>
                                <option value="kode_aset">Kode Aset</option>
                                <option value="kategori">Kategori Barang</option>
                                <option value="merk">Merk Perangkat</option>
                                <option value="lokasi">Lokasi Penempatan</option>
                                <option value="penanggung_jawab">Nama Penanggung Jawab</option>
                            </select>
                        </div>

                        <!-- 3. Baris Kedua -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-800">
                                3. Keterangan Baris Kedua (Bawah)
                            </label>
                            <select name="label_baris_2" x-model="form.label_baris_2"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 bg-white">
                                <option value="kode_aset">Kode Aset (Rekomendasi)</option>
                                <option value="nama_aset">Nama Aset</option>
                                <option value="lokasi">Lokasi Penempatan</option>
                                <option value="penanggung_jawab">Nama Penanggung Jawab</option>
                                <option value="kategori">Kategori Barang</option>
                                <option value="merk">Merk Perangkat</option>
                            </select>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600 flex items-start gap-2.5">
                            <i class="ti ti-info-circle text-cyan-600 text-base flex-shrink-0 mt-0.5"></i>
                            <p class="leading-relaxed">
                                <strong>Catatan:</strong> Seluruh teks keterangan pada stiker QR fisik akan dipotong secara otomatis jika panjang karakter melebihi <strong>25 karakter</strong> agar proporsi cetak tetap rapi dan mudah terbaca scanner.
                            </p>
                        </div>
                    </div>

                </div>

            </div>

            <!-- RIGHT COLUMN: Sticky Live Interactive Preview Simulator (5 Cols) -->
            <div class="lg:col-span-5 sticky top-6 space-y-4">
                
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-xs font-extrabold text-slate-800 uppercase tracking-wider">Live Simulator Preview</span>
                        </div>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-slate-100 text-slate-600"
                              x-text="activeTab === 'scan' ? 'Mockup Smartphone' : 'Mockup Stiker Label'"></span>
                    </div>

                    <!-- PREVIEW 1: MOCKUP SMARTPHONE FOR SCAN TAB -->
                    <div x-show="activeTab === 'scan'" class="flex justify-center">
                        <div class="w-full max-w-[320px] bg-slate-900 rounded-[36px] p-2.5 shadow-2xl border-4 border-slate-800">
                            <!-- Notch & Camera -->
                            <div class="w-28 h-4 bg-slate-800 rounded-full mx-auto mb-2 flex items-center justify-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-slate-950"></span>
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-900"></span>
                            </div>

                            <!-- Screen Content (Scrollable) -->
                            <div class="bg-slate-50 rounded-[28px] overflow-hidden text-slate-800 max-h-[580px] overflow-y-auto custom-scroll shadow-inner">
                                <!-- Header Portal -->
                                <div class="bg-gradient-to-r from-cyan-600 to-emerald-600 p-4 text-white text-center">
                                    <div class="w-12 h-12 mx-auto mb-1.5 bg-white p-1.5 rounded-2xl shadow-sm flex items-center justify-center">
                                        <img src="{{ asset('logo-icon.png') }}" alt="AMANA" class="w-full h-full object-contain">
                                    </div>
                                    <p class="text-[10px] text-white/90 font-bold">Aset Manajemen Al Azhar</p>
                                    <p class="text-[8px] text-white/70">Verification Portal</p>
                                </div>

                                <div class="p-3.5 space-y-3">
                                    <!-- Kode & Nama Aset -->
                                    <div class="text-center pb-2 border-b border-slate-100">
                                        <template x-if="form.show_kode_aset">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 mb-1">
                                                {{ $sampleAset->kode_aset ?? 'EL14D05021202601' }}
                                            </span>
                                        </template>

                                        <template x-if="form.show_nama_aset">
                                            <h4 class="text-xs font-bold text-slate-900">{{ $sampleAset->nama_aset ?? 'Laptop Asus ROG Strix' }}</h4>
                                        </template>

                                        <template x-if="form.show_status_aset">
                                            <div class="mt-1">
                                                <span class="px-2 py-0.5 rounded-md text-[9px] font-extrabold uppercase bg-emerald-100 text-emerald-800">
                                                    {{ strtoupper($sampleAset->status ?? 'AKTIF') }}
                                                </span>
                                            </div>
                                        </template>

                                        <!-- Foto Aset -->
                                        <template x-if="form.show_foto">
                                            <div class="mt-2 rounded-xl bg-slate-200 h-28 flex items-center justify-center text-slate-400 text-xs overflow-hidden border border-slate-200">
                                                @if($sampleAset->foto_utama)
                                                    <img src="{{ asset('storage/' . $sampleAset->foto_utama) }}" class="w-full h-full object-cover">
                                                @else
                                                    <div class="text-center p-2">
                                                        <i class="ti ti-photo text-2xl"></i>
                                                        <p class="text-[9px] mt-0.5">Foto Utama Aset</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Key Details List -->
                                    <div class="space-y-1.5 text-[10px]">
                                        <template x-if="form.show_kode_sistem">
                                            <div class="flex justify-between py-1 border-b border-slate-100">
                                                <span class="text-slate-400">Kode Sistem:</span>
                                                <span class="font-bold text-slate-800 font-mono">#{{ $sampleAset->id ?? '1' }}</span>
                                            </div>
                                        </template>

                                        <template x-if="form.show_kategori">
                                            <div class="flex justify-between py-1 border-b border-slate-100">
                                                <span class="text-slate-400">Kategori:</span>
                                                <span class="font-bold text-slate-800">{{ $sampleAset->kategori->nama_kategori ?? 'Elektronik' }}</span>
                                            </div>
                                        </template>

                                        <template x-if="form.show_merk">
                                            <div class="flex justify-between py-1 border-b border-slate-100">
                                                <span class="text-slate-400">Merk:</span>
                                                <span class="font-bold text-slate-800">{{ $sampleAset->merk->nama_merk ?? 'Asus' }}</span>
                                            </div>
                                        </template>

                                        <template x-if="form.show_tipe">
                                            <div class="flex justify-between py-1 border-b border-slate-100">
                                                <span class="text-slate-400">Tipe / Model:</span>
                                                <span class="font-bold text-slate-800">{{ $sampleAset->tipe_model ?? 'ROG Strix G15' }}</span>
                                            </div>
                                        </template>

                                        <template x-if="form.show_produsen">
                                            <div class="flex justify-between py-1 border-b border-slate-100">
                                                <span class="text-slate-400">Produsen:</span>
                                                <span class="font-bold text-slate-800">{{ $sampleAset->produsen ?? 'ASUS Inc.' }}</span>
                                            </div>
                                        </template>

                                        <template x-if="form.show_no_seri">
                                            <div class="flex justify-between py-1 border-b border-slate-100">
                                                <span class="text-slate-400">No Seri / SN:</span>
                                                <span class="font-bold text-slate-800 font-mono">{{ $sampleAset->no_seri ?? 'SN-2026-9988' }}</span>
                                            </div>
                                        </template>

                                        <template x-if="form.show_tahun_produksi">
                                            <div class="flex justify-between py-1 border-b border-slate-100">
                                                <span class="text-slate-400">Tahun Produksi:</span>
                                                <span class="font-bold text-slate-800">{{ $sampleAset->tahun_produksi ?? '2025' }}</span>
                                            </div>
                                        </template>

                                        <template x-if="form.show_deskripsi">
                                            <div class="py-1 border-b border-slate-100">
                                                <span class="text-slate-400 block mb-0.5">Deskripsi Fisik:</span>
                                                <span class="font-medium text-slate-800">{{ $sampleAset->deskripsi ?? 'Kondisi mulus dan normal.' }}</span>
                                            </div>
                                        </template>

                                        <template x-if="form.show_keterangan_tambahan">
                                            <div class="py-1 border-b border-slate-100">
                                                <span class="text-slate-400 block mb-0.5">Keterangan:</span>
                                                <span class="font-medium text-slate-800">{{ $sampleAset->keterangan_tambahan ?? 'Garansi resmi aktif.' }}</span>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Pembelian Card (jika aktif) -->
                                    <template x-if="form.show_tanggal_pembelian || form.show_toko_distributor || form.show_no_invoice || form.show_jumlah_unit || form.show_harga_satuan || form.show_harga_total">
                                        <div class="p-2.5 rounded-xl bg-slate-100/80 border border-slate-200 text-[10px] space-y-1">
                                            <p class="font-bold text-slate-900 text-[10px] flex items-center gap-1">
                                                <i class="ti ti-receipt text-cyan-600"></i> Data Pembelian
                                            </p>
                                            <template x-if="form.show_tanggal_pembelian">
                                                <div class="flex justify-between text-slate-600">
                                                    <span>Tgl Beli:</span>
                                                    <span class="font-semibold">{{ $sampleAset->tanggal_pembelian ? \Carbon\Carbon::parse($sampleAset->tanggal_pembelian)->format('d/m/Y') : '15/01/2026' }}</span>
                                                </div>
                                            </template>
                                            <template x-if="form.show_toko_distributor">
                                                <div class="flex justify-between text-slate-600">
                                                    <span>Toko:</span>
                                                    <span class="font-semibold">{{ $sampleAset->toko_distributor ?? 'PT Asus Indo' }}</span>
                                                </div>
                                            </template>
                                            <template x-if="form.show_no_invoice">
                                                <div class="flex justify-between text-slate-600">
                                                    <span>Invoice:</span>
                                                    <span class="font-semibold font-mono">{{ $sampleAset->no_invoice ?? 'INV/2026/01' }}</span>
                                                </div>
                                            </template>
                                            <template x-if="form.show_harga_total">
                                                <div class="flex justify-between text-slate-900 font-bold pt-1 border-t border-slate-200">
                                                    <span>Harga Total:</span>
                                                    <span class="text-emerald-700">Rp {{ number_format($sampleAset->harga_total ?? 18500000, 0, ',', '.') }}</span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Penyusutan Card (jika aktif) -->
                                    <template x-if="form.show_umur_ekonomi || form.show_penyusutan_per_bulan || form.show_usia_aset || form.show_nilai_sekarang">
                                        <div class="p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-200 text-[10px] space-y-1">
                                            <p class="font-bold text-emerald-950 flex items-center gap-1">
                                                <i class="ti ti-chart-line text-emerald-600"></i> Umur & Penyusutan
                                            </p>
                                            <template x-if="form.show_umur_ekonomi">
                                                <div class="flex justify-between text-slate-600">
                                                    <span>Umur Manfaat:</span>
                                                    <span class="font-semibold">{{ $sampleAset->umur_ekonomis_tahun ?? 4 }} Tahun</span>
                                                </div>
                                            </template>
                                            <template x-if="form.show_penyusutan_per_bulan">
                                                <div class="flex justify-between text-slate-600">
                                                    <span>Penyusutan/bln:</span>
                                                    <span class="font-semibold">Rp {{ number_format($sampleAset->penyusutan_per_bulan ?? 385416, 0, ',', '.') }}</span>
                                                </div>
                                            </template>
                                            <template x-if="form.show_nilai_sekarang">
                                                <div class="flex justify-between text-emerald-950 font-bold pt-1 border-t border-emerald-200">
                                                    <span>Nilai Sekarang:</span>
                                                    <span class="text-emerald-700">Rp 15.416.000</span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Accordion Sub-Modules Preview -->
                                    <div class="space-y-1.5 pt-1">
                                        <!-- Riwayat Accordion -->
                                        <template x-if="form.riwayat_mode !== 'tidak_tampil'">
                                            <div class="p-2 rounded-xl bg-white border border-slate-200 text-[10px] shadow-xs">
                                                <div class="flex items-center justify-between font-bold text-slate-800">
                                                    <span class="flex items-center gap-1.5">
                                                        <i class="ti ti-history text-cyan-600"></i> Riwayat Perpindahan
                                                    </span>
                                                    <span class="text-[8px] px-1.5 py-0.2 rounded bg-slate-100 text-slate-500 font-normal uppercase" x-text="form.riwayat_mode"></span>
                                                </div>
                                                <div class="mt-1.5 pt-1.5 border-t border-slate-100 text-[9px] text-slate-600 space-y-0.5">
                                                    <template x-if="form.show_riwayat_penanggung_jawab">
                                                        <p>• <strong>PJ:</strong> {{ $sampleAset->penanggungJawab->nama ?? 'Budi Santoso' }}</p>
                                                    </template>
                                                    <template x-if="form.show_riwayat_lokasi">
                                                        <p>• <strong>Lokasi:</strong> {{ $sampleAset->lokasi->nama_lokasi ?? 'Lab Komputer 1' }}</p>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Agenda Accordion -->
                                        <template x-if="form.show_agenda">
                                            <div class="p-2 rounded-xl bg-white border border-slate-200 text-[10px] shadow-xs flex items-center justify-between font-bold text-slate-800">
                                                <span class="flex items-center gap-1.5">
                                                    <i class="ti ti-calendar text-emerald-600"></i> Agenda & Servis
                                                </span>
                                                <i class="ti ti-chevron-down text-slate-400"></i>
                                            </div>
                                        </template>

                                        <!-- Keuangan Accordion -->
                                        <template x-if="form.show_keuangan">
                                            <div class="p-2 rounded-xl bg-white border border-slate-200 text-[10px] shadow-xs flex items-center justify-between font-bold text-slate-800">
                                                <span class="flex items-center gap-1.5">
                                                    <i class="ti ti-cash text-amber-600"></i> Transaksi Keuangan
                                                </span>
                                                <i class="ti ti-chevron-down text-slate-400"></i>
                                            </div>
                                        </template>

                                        <!-- Jurnal Accordion -->
                                        <template x-if="form.show_jurnal">
                                            <div class="p-2 rounded-xl bg-white border border-slate-200 text-[10px] shadow-xs flex items-center justify-between font-bold text-slate-800">
                                                <span class="flex items-center gap-1.5">
                                                    <i class="ti ti-notes text-indigo-600"></i> Jurnal Catatan & Insiden
                                                </span>
                                                <i class="ti ti-chevron-down text-slate-400"></i>
                                            </div>
                                        </template>

                                        <!-- Lampiran Accordion -->
                                        <template x-if="form.show_lampiran">
                                            <div class="p-2 rounded-xl bg-white border border-slate-200 text-[10px] shadow-xs flex items-center justify-between font-bold text-slate-800">
                                                <span class="flex items-center gap-1.5">
                                                    <i class="ti ti-paperclip text-rose-600"></i> File & Lampiran
                                                </span>
                                                <i class="ti ti-chevron-down text-slate-400"></i>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Footer Note -->
                                    <div class="pt-2 text-center border-t border-slate-100 text-[8px] text-slate-400">
                                        Terverifikasi Resmi oleh Al Azhar Peduli
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PREVIEW 2: MOCKUP STIKER LABEL FOR LABEL TAB -->
                    <div x-show="activeTab === 'label'" class="p-4 bg-slate-100 rounded-2xl flex flex-col items-center justify-center">
                        <p class="text-[11px] font-bold text-slate-500 mb-3">Simulasi Stiker Label QR Fisik (Ukuran 5 × 6.5 cm)</p>
                        
                        <!-- Printable Card Mockup -->
                        <div class="w-64 bg-white rounded-2xl p-4 shadow-xl border-2 border-slate-300 text-center flex flex-col items-center space-y-3">
                            
                            <!-- Header Judul -->
                            <div class="w-full bg-gradient-to-r from-cyan-700 to-emerald-700 text-white px-2 py-1.5 rounded-lg shadow-xs">
                                <span class="block text-[11px] font-black tracking-wider uppercase truncate"
                                      x-text="getEffectiveTitle()"></span>
                            </div>

                            <!-- QR Code Center Sample -->
                            <div class="p-2 bg-white rounded-xl border border-slate-200 shadow-inner flex items-center justify-center">
                                <div class="w-32 h-32 bg-slate-900 rounded-lg p-2 flex flex-col items-center justify-center relative overflow-hidden">
                                    <!-- QR Visual Graphic -->
                                    <div class="w-full h-full bg-white p-1 rounded flex items-center justify-center relative">
                                        <div class="grid grid-cols-5 gap-1 w-full h-full p-1 opacity-90">
                                            <div class="bg-black rounded-xs"></div><div class="bg-black rounded-xs"></div><div class="bg-black rounded-xs"></div><div class="bg-white"></div><div class="bg-black rounded-xs"></div>
                                            <div class="bg-black rounded-xs"></div><div class="bg-white"></div><div class="bg-black rounded-xs"></div><div class="bg-black rounded-xs"></div><div class="bg-black rounded-xs"></div>
                                            <div class="bg-black rounded-xs"></div><div class="bg-black rounded-xs"></div><div class="bg-white"></div><div class="bg-black rounded-xs"></div><div class="bg-white"></div>
                                            <div class="bg-white"></div><div class="bg-black rounded-xs"></div><div class="bg-black rounded-xs"></div><div class="bg-white"></div><div class="bg-black rounded-xs"></div>
                                            <div class="bg-black rounded-xs"></div><div class="bg-white"></div><div class="bg-black rounded-xs"></div><div class="bg-black rounded-xs"></div><div class="bg-black rounded-xs"></div>
                                        </div>
                                        <div class="absolute inset-0 m-auto w-6 h-6 bg-white rounded-full border border-emerald-600 flex items-center justify-center shadow-xs">
                                            <span class="text-[8px] font-black text-emerald-800">AM</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Text Lines -->
                            <div class="w-full space-y-1">
                                <div class="px-2 py-1 rounded bg-slate-50 border border-slate-200 text-slate-900">
                                    <span class="block text-[10px] font-bold truncate"
                                          x-text="getLineValue(form.label_baris_1)"></span>
                                </div>
                                <div class="px-2 py-1 rounded bg-emerald-50/60 border border-emerald-200 text-emerald-900">
                                    <span class="block text-[10px] font-mono font-black truncate"
                                          x-text="getLineValue(form.label_baris_2)"></span>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

            </div>

        </div>
    </form>

</div>

@push('scripts')
<script>
    function qrConfigApp() {
        return {
            activeTab: 'scan',
            form: {
                // 1. Informasi Umum
                show_nama_aset: {{ $config['show_nama_aset'] ? 'true' : 'false' }},
                show_kategori: {{ $config['show_kategori'] ? 'true' : 'false' }},
                show_kode_aset: {{ $config['show_kode_aset'] ? 'true' : 'false' }},
                show_kode_sistem: {{ $config['show_kode_sistem'] ? 'true' : 'false' }},
                show_keterangan_tambahan: {{ $config['show_keterangan_tambahan'] ? 'true' : 'false' }},
                show_foto: {{ $config['show_foto'] ? 'true' : 'false' }},
                show_lampiran: {{ $config['show_lampiran'] ? 'true' : 'false' }},
                show_status_aset: {{ $config['show_status_aset'] ? 'true' : 'false' }},

                // 2. Non-Aktif
                show_nonaktif_tanggal: {{ $config['show_nonaktif_tanggal'] ? 'true' : 'false' }},
                show_nonaktif_sebab: {{ $config['show_nonaktif_sebab'] ? 'true' : 'false' }},
                show_nonaktif_keterangan: {{ $config['show_nonaktif_keterangan'] ? 'true' : 'false' }},

                // 3. Detil
                show_merk: {{ $config['show_merk'] ? 'true' : 'false' }},
                show_tipe: {{ $config['show_tipe'] ? 'true' : 'false' }},
                show_produsen: {{ $config['show_produsen'] ? 'true' : 'false' }},
                show_no_seri: {{ $config['show_no_seri'] ? 'true' : 'false' }},
                show_tahun_produksi: {{ $config['show_tahun_produksi'] ? 'true' : 'false' }},
                show_deskripsi: {{ $config['show_deskripsi'] ? 'true' : 'false' }},

                // 4. Pembelian
                show_tanggal_pembelian: {{ $config['show_tanggal_pembelian'] ? 'true' : 'false' }},
                show_toko_distributor: {{ $config['show_toko_distributor'] ? 'true' : 'false' }},
                show_no_invoice: {{ $config['show_no_invoice'] ? 'true' : 'false' }},
                show_jumlah_unit: {{ $config['show_jumlah_unit'] ? 'true' : 'false' }},
                show_harga_satuan: {{ $config['show_harga_satuan'] ? 'true' : 'false' }},
                show_harga_total: {{ $config['show_harga_total'] ? 'true' : 'false' }},

                // 5. Umur & Penyusutan
                show_umur_ekonomi: {{ $config['show_umur_ekonomi'] ? 'true' : 'false' }},
                show_penyusutan_per_bulan: {{ $config['show_penyusutan_per_bulan'] ? 'true' : 'false' }},
                show_usia_aset: {{ $config['show_usia_aset'] ? 'true' : 'false' }},
                show_nilai_sekarang: {{ $config['show_nilai_sekarang'] ? 'true' : 'false' }},

                // 6. Keuangan, Agenda & Jurnal
                show_keuangan: {{ $config['show_keuangan'] ? 'true' : 'false' }},
                show_agenda: {{ $config['show_agenda'] ? 'true' : 'false' }},
                show_jurnal: {{ $config['show_jurnal'] ? 'true' : 'false' }},

                // 7. Riwayat
                riwayat_mode: '{{ $config['riwayat_mode'] ?? 'terakhir' }}',
                show_riwayat_tanggal: {{ $config['show_riwayat_tanggal'] ? 'true' : 'false' }},
                show_riwayat_penanggung_jawab: {{ $config['show_riwayat_penanggung_jawab'] ? 'true' : 'false' }},
                show_riwayat_lokasi: {{ $config['show_riwayat_lokasi'] ? 'true' : 'false' }},
                show_riwayat_jumlah: {{ $config['show_riwayat_jumlah'] ? 'true' : 'false' }},
                show_riwayat_kondisi: {{ $config['show_riwayat_kondisi'] ? 'true' : 'false' }},
                show_riwayat_kelengkapan: {{ $config['show_riwayat_kelengkapan'] ? 'true' : 'false' }},
                show_riwayat_keterangan: {{ $config['show_riwayat_keterangan'] ? 'true' : 'false' }},

                // 8. Label
                label_judul_pilihan: '{{ $config['label_judul_pilihan'] ?? 'preset' }}',
                label_judul_preset: '{{ $config['label_judul_preset'] ?? 'MILIK LAZ AL AZHAR' }}',
                label_judul_custom: '{{ $config['label_judul_custom'] ?? '' }}',
                label_baris_1: '{{ $config['label_baris_1'] ?? 'nama_aset' }}',
                label_baris_2: '{{ $config['label_baris_2'] ?? 'kode_aset' }}'
            },

            // Sample data dictionary
            sample: {
                nama_aset: '{{ $sampleAset->nama_aset ?? "Laptop Asus ROG Strix" }}',
                kode_aset: '{{ $sampleAset->kode_aset ?? "EL14D05021202601" }}',
                kategori: '{{ $sampleAset->kategori->nama_kategori ?? "Elektronik" }}',
                merk: '{{ $sampleAset->merk->nama_merk ?? "Asus" }}',
                lokasi: '{{ $sampleAset->lokasi->nama_lokasi ?? "Lab Komputer 1" }}',
                penanggung_jawab: '{{ $sampleAset->penanggungJawab->nama ?? "Budi Santoso" }}'
            },

            getEffectiveTitle() {
                if (this.form.label_judul_pilihan === 'custom' && this.form.label_judul_custom) {
                    return this.form.label_judul_custom.substring(0, 25);
                }
                return (this.form.label_judul_preset || 'MILIK LAZ AL AZHAR').substring(0, 25);
            },

            getLineValue(field) {
                const val = this.sample[field] || this.sample.nama_aset;
                return val.substring(0, 25);
            },

            setGroup(group, state) {
                if (group === 'umum') {
                    this.form.show_nama_aset = state;
                    this.form.show_kategori = state;
                    this.form.show_kode_aset = state;
                    this.form.show_kode_sistem = state;
                    this.form.show_keterangan_tambahan = state;
                    this.form.show_foto = state;
                    this.form.show_lampiran = state;
                    this.form.show_status_aset = state;
                } else if (group === 'detil') {
                    this.form.show_merk = state;
                    this.form.show_tipe = state;
                    this.form.show_produsen = state;
                    this.form.show_no_seri = state;
                    this.form.show_tahun_produksi = state;
                    this.form.show_deskripsi = state;
                } else if (group === 'pembelian') {
                    this.form.show_tanggal_pembelian = state;
                    this.form.show_toko_distributor = state;
                    this.form.show_no_invoice = state;
                    this.form.show_jumlah_unit = state;
                    this.form.show_harga_satuan = state;
                    this.form.show_harga_total = state;
                } else if (group === 'penyusutan') {
                    this.form.show_umur_ekonomi = state;
                    this.form.show_penyusutan_per_bulan = state;
                    this.form.show_usia_aset = state;
                    this.form.show_nilai_sekarang = state;
                }
            },

            submitForm() {
                document.getElementById('qrConfigForm').submit();
            }
        };
    }
</script>
@endpush
@endsection
