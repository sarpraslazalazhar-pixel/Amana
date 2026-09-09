@extends('layouts.app')

@section('title', 'Detail Aset - ' . $aset->kode_aset)
@section('header-title', 'Detail Aset: ' . $aset->nama_aset)

@php
    $backRoute = $aset->status === 'non_aktif'
        ? 'aset.nonAktif'
        : ($aset->jenis === 'kelolaan' ? 'aset.kelolaan' : 'aset.tetap');
    $qrUrl = route('public.qr', $aset->kode_aset);
    $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($qrUrl);

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

    $divisiItems = ($divisiList ?? collect())->map(fn($d) => [
        'id' => $d->id,
        'code' => $d->kode_divisi,
        'title' => $d->nama_divisi,
        'subtitle' => 'Kode ' . $d->kode_divisi
    ])->values()->all();
@endphp

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{
    activeTab: '{{ request('tab', 'riwayat') }}',
    modalOpen: false,
    modalType: 'riwayat',
    imageModalOpen: false,
    previewImageUrl: '',
    previewImageTitle: '',
    agendaSelesaiModalOpen: false,
    selectedAgenda: null,
    agendaBiayaInput: '0',
    formatRibuan(val) {
        let clean = String(val).replace(/[^0-9]/g, '');
        return clean ? Number(clean).toLocaleString('id-ID') : '0';
    },
    openAgendaSelesaiModal(agenda) {
        this.selectedAgenda = agenda;
        let est = Number(agenda.biaya_estimasi || 0);
        this.agendaBiayaInput = est > 0 ? est.toLocaleString('id-ID') : '0';
        this.agendaSelesaiModalOpen = true;
    },
    editModalOpen: false,
    editType: 'riwayat',
    editItem: null,
    editNominalInput: '0',
    openEditModal(type, item) {
        this.editType = type;
        this.editItem = item;
        if (type === 'keuangan') {
            this.editNominalInput = item.nominal ? Number(item.nominal).toLocaleString('id-ID') : '0';
        } else if (type === 'agenda') {
            this.editNominalInput = item.biaya_estimasi ? Number(item.biaya_estimasi).toLocaleString('id-ID') : '0';
        }
        this.editModalOpen = true;
    },
    openImageModal(url, title) {
        this.previewImageUrl = url;
        this.previewImageTitle = title || 'Foto Aset';
        this.imageModalOpen = true;
    }
} " @keydown.escape.window="imageModalOpen = false; agendaSelesaiModalOpen = false; editModalOpen = false">

    <!-- Top Breadcrumb & Main Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ $aset->nama_aset }}</h2>
                <x-badge-status :status="$aset->status" />
            </div>
            <div class="mt-1.5 flex items-center gap-2 text-xs">
                <span class="inline-block px-2.5 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 font-mono font-bold border border-emerald-200">
                    {{ $aset->kode_aset }}
                </span>
                <span class="text-slate-500">• Aset {{ ucfirst($aset->jenis) }}</span>
                <span class="text-slate-400">• ID: #AST-{{ str_pad($aset->id, 5, '0', STR_PAD_LEFT) }}</span>
            </div>
        </div>

        <!-- Action Buttons (Responsive Top Right) -->
        <div class="flex items-center flex-wrap gap-2">
            <!-- Tombol Kembali -->
            <a href="{{ route($backRoute) }}"
               class="px-4 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-extrabold text-xs uppercase tracking-wider border border-emerald-200 transition-colors inline-flex items-center gap-1 shadow-xs">
                « KEMBALI
            </a>

            <!-- Tombol Edit Aset -->
            @if(auth()->check() && auth()->user()->role === 'super_admin')
                <a href="{{ route('aset.edit', $aset->id) }}"
                   class="w-9 h-9 rounded-xl bg-white hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 border border-slate-200 hover:border-emerald-200 shadow-xs inline-flex items-center justify-center transition-colors"
                   title="Edit Aset">
                    <i class="ti ti-pencil text-base"></i>
                </a>
            @endif

            <!-- Tombol Non-Aktifkan / Aktifkan -->
            @if(auth()->check() && auth()->user()->role === 'super_admin')
                <form method="POST" action="{{ route('aset.destroy', $aset->id) }}" class="inline" onsubmit="return confirm('{{ $aset->status === 'aktif' ? 'Non-aktifkan' : 'Aktifkan' }} aset ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-9 h-9 rounded-xl bg-white hover:bg-rose-50 text-slate-600 hover:text-rose-600 border border-slate-200 hover:border-rose-200 shadow-xs inline-flex items-center justify-center transition-colors"
                            title="{{ $aset->status === 'aktif' ? 'Non-aktifkan Aset' : 'Aktifkan Aset' }}">
                        <i class="ti ti-box-off text-base"></i>
                    </button>
                </form>
            @endif

            <!-- Tombol Ekspor PDF / Cetak Kartu Aset -->
            <a href="{{ route('aset.pdf', $aset->id) }}" target="_blank"
               class="w-9 h-9 rounded-xl bg-white hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 border border-slate-200 hover:border-emerald-200 shadow-xs inline-flex items-center justify-center transition-colors"
               title="Ekspor PDF Kartu Aset (Cetak)">
                <i class="ti ti-file-type-pdf text-base"></i>
            </a>

            <!-- Public QR Link -->
            <a href="{{ $qrUrl }}" target="_blank"
               class="w-9 h-9 rounded-xl bg-white hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 border border-slate-200 hover:border-emerald-200 shadow-xs inline-flex items-center justify-center transition-colors"
               title="Lihat Public QR Scan">
                <i class="ti ti-qrcode text-base"></i>
            </a>

            @if(auth()->check() && auth()->user()->role === 'super_admin')
                <!-- Log Audit Shortcut -->
                <a href="{{ route('audit-log.index', ['aset_id' => $aset->id]) }}"
                   class="w-9 h-9 rounded-xl bg-white hover:bg-amber-50 text-slate-600 hover:text-amber-700 border border-slate-200 hover:border-amber-200 shadow-xs inline-flex items-center justify-center transition-colors"
                   title="Lihat Log Audit Aset Ini">
                    <i class="ti ti-history text-base"></i>
                </a>
            @endif
        </div>
    </div>

    <!-- 2-Column Responsive Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- ========================================== -->
        <!-- KOLOM KIRI: 6 BAGIAN SPESIFIKASI ASET      -->
        <!-- ========================================== -->
        <div class="lg:col-span-7 space-y-5">

            <!-- 1. DATA ASET -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-xs">1</span>
                        DATA ASET
                    </h3>
                    <span class="text-xs text-slate-400 font-medium">Informasi Pokok</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-y-4 gap-x-6 text-xs">
                    <div class="sm:col-span-2">
                        <span class="text-slate-400 block mb-1 font-medium">Nama Aset</span>
                        <span class="font-extrabold text-slate-900 text-sm">{{ $aset->nama_aset }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Kode Aset</span>
                        <span class="font-mono font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg text-xs inline-block tracking-wider">
                            {{ $aset->kode_aset }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Kategori Barang</span>
                        <span class="font-bold text-slate-800 text-[13px]">
                            {{ $aset->kategori->nama_kategori ?? '-' }}
                            @if(isset($aset->kategori->kode_kategori))
                                <span class="text-emerald-700 font-mono font-bold text-xs bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">{{ $aset->kategori->kode_kategori }}</span>
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Jenis Barang (Master)</span>
                        <span class="font-bold text-slate-800 text-[13px]">
                            {{ $aset->barang->nama_barang ?? ($aset->tipe_model ?? '-') }}
                            @if(isset($aset->barang->kode_barang))
                                <span class="text-cyan-700 font-mono font-bold text-xs bg-cyan-50 px-1.5 py-0.5 rounded border border-cyan-200">{{ $aset->barang->kode_barang }}</span>
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Sifat Barang</span>
                        <span class="inline-flex items-center gap-1 font-bold text-[12px] px-2 py-0.5 rounded-md {{ $aset->sifat_barang === 'D' ? 'bg-cyan-50 text-cyan-800 border border-cyan-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                            {{ $aset->sifat_barang === 'D' ? 'D — Dinamis (Amil)' : 'S — Statis (Lokasi)' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Divisi Pengelola</span>
                        <span class="font-bold text-slate-800 text-[13px]">
                            {{ $aset->divisi->nama_divisi ?? '-' }}
                            @if(isset($aset->divisi->kode_divisi))
                                <span class="text-slate-400 font-normal">(Kode {{ $aset->divisi->kode_divisi }})</span>
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Cara & Status Perolehan</span>
                        <span class="font-semibold text-slate-700 text-xs">
                            {{ $aset->cara_perolehan === '2' ? 'Hibah / Donasi' : 'Pembelian' }} • {{ $aset->status_barang === '2' ? 'Second' : 'Baru' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Jenis & Status</span>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="px-2 py-0.5 rounded-md bg-cyan-50 text-cyan-700 font-bold border border-cyan-200 text-[11px]">
                                Aset {{ ucfirst($aset->jenis) }}
                            </span>
                            <x-badge-status :status="$aset->status" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. FOTO & QR CODE -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-xs">2</span>
                        FOTO & QR CODE
                    </h3>
                    <span class="text-xs text-slate-400 font-medium">Dokumentasi Visual</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Foto Utama Aset -->
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/70 flex flex-col items-center justify-center text-center min-h-[210px]">
                        @if($aset->foto_utama)
                            <div class="relative group cursor-pointer overflow-hidden rounded-xl border border-slate-200 shadow-xs w-full h-44"
                                 @click="openImageModal('{{ asset('storage/' . $aset->foto_utama) }}', '{{ $aset->nama_aset }} ({{ $aset->kode_aset }})')">
                                <img src="{{ asset('storage/' . $aset->foto_utama) }}" alt="{{ $aset->nama_aset }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                    <span class="px-3 py-1.5 rounded-xl bg-white/20 backdrop-blur-md border border-white/30 text-xs font-bold flex items-center gap-1.5 shadow-sm">
                                        <i class="ti ti-zoom-in text-base"></i>
                                        Lihat Foto Penuh
                                    </span>
                                </div>
                            </div>
                        @else
                            <div class="w-14 h-14 rounded-2xl bg-slate-200/70 text-slate-400 flex items-center justify-center mb-2">
                                <i class="ti ti-photo text-2xl"></i>
                            </div>
                            <p class="text-xs font-semibold text-slate-600">Belum Ada Foto Aset</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">Foto dapat ditambahkan via tombol Edit</p>
                        @endif
                    </div>

                    <!-- QR Code AMANA -->
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/70 flex flex-col items-center justify-between text-center min-h-[210px]">
                        <div class="p-2 bg-white rounded-xl shadow-xs border border-slate-200 group cursor-pointer hover:border-emerald-300 transition-colors"
                             @click="openImageModal('{{ $qrApiUrl }}', 'QR Code {{ $aset->kode_aset }}')"
                             title="Klik untuk memperbesar QR Code">
                            <img src="{{ $qrApiUrl }}" alt="QR {{ $aset->kode_aset }}" class="w-28 h-28 object-contain">
                        </div>
                        <div class="mt-2 space-y-1">
                            <p class="text-xs font-bold text-slate-800">Scan QR Code Publik</p>
                            <a href="{{ $qrUrl }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 hover:text-emerald-700">
                                <i class="ti ti-external-link text-xs"></i> Buka Portal Verifikasi
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. DETAIL ASET (Spesifikasi Teknis & Lokasi) -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-xs">3</span>
                        DETAIL ASET
                    </h3>
                    <span class="text-xs text-slate-400 font-medium">Spesifikasi & Lokasi</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-y-4 gap-x-4 text-xs">
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Merk</span>
                        <span class="font-bold text-slate-800 text-[13px]">{{ $aset->merk->nama_merk ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Tipe / Model</span>
                        <span class="font-bold text-slate-800 text-[13px]">{{ $aset->tipe_model ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Produsen</span>
                        <span class="font-bold text-slate-800 text-[13px]">{{ $aset->produsen ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">No. Seri / Produksi</span>
                        <span class="font-mono font-bold text-slate-700 text-xs">{{ $aset->no_seri ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Tahun Produksi</span>
                        <span class="font-bold text-slate-800 text-[13px]">{{ $aset->tahun_produksi ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Lokasi Penempatan</span>
                        <span class="font-bold text-slate-800 text-[13px]">{{ $aset->lokasi->nama_lokasi ?? '-' }}</span>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="text-slate-400 block mb-1 font-medium">Penanggung Jawab / Pemegang</span>
                        <span class="font-bold text-slate-800 text-[13px]">
                            {{ $aset->penanggungJawab->nama ?? '-' }}
                            @if(isset($aset->penanggungJawab->jabatan))
                                <span class="text-slate-400 font-normal">({{ $aset->penanggungJawab->jabatan }})</span>
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Deskripsi Kondisi</span>
                        <span class="font-semibold text-slate-700 text-xs">{{ $aset->deskripsi ?? 'Aset Baru' }}</span>
                    </div>
                </div>
            </div>

            <!-- 4. PEMBELIAN & NILAI BUKU (Keuangan & Penyusutan) -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-xs">4</span>
                        PEMBELIAN & NILAI BUKU
                    </h3>
                    <span class="text-xs text-slate-400 font-medium">Penyusutan Garis Lurus</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-y-4 gap-x-4 text-xs">
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Tanggal Pembelian</span>
                        <span class="font-bold text-slate-800 text-[13px]">{{ $aset->tanggal_pembelian ? \Carbon\Carbon::parse($aset->tanggal_pembelian)->format('d M Y') : '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Toko / Distributor</span>
                        <span class="font-bold text-slate-800 text-[13px]">{{ $aset->toko_distributor ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">No. Invoice</span>
                        <span class="font-mono font-bold text-slate-700 text-xs">{{ $aset->no_invoice ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Jumlah Unit</span>
                        <span class="font-bold text-slate-800 text-[13px]">{{ $aset->jumlah_unit }} unit</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Harga Satuan</span>
                        <span class="font-bold text-slate-800 text-[13px]">Rp {{ number_format($aset->harga_satuan, 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Harga Pembelian Total</span>
                        <span class="font-extrabold text-slate-900 text-sm">Rp {{ number_format($aset->harga_total, 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Umur Ekonomis</span>
                        <span class="font-bold text-slate-800 text-[13px]">{{ $aset->umur_ekonomis_tahun }} Tahun</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Nilai Residu / Sisa</span>
                        <span class="font-bold text-slate-800 text-[13px]">Rp {{ number_format($aset->nilai_residu, 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Penyusutan / Bulan</span>
                        <span class="font-bold text-rose-600 text-[13px]">Rp {{ number_format($aset->penyusutan_per_bulan, 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block mb-1 font-medium">Estimasi Nilai Buku Saat Ini</span>
                        <span class="font-extrabold text-emerald-600 text-sm">Rp {{ number_format($nilaiBuku, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- 5. KETERANGAN -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-xs">5</span>
                        KETERANGAN
                    </h3>
                </div>
                <p class="text-xs sm:text-[13px] text-slate-600 leading-relaxed">
                    {{ $aset->keterangan_tambahan ?: 'Tidak ada keterangan tambahan khusus untuk aset ini.' }}
                </p>
            </div>

            <!-- 6. LAMPIRAN -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-xs">6</span>
                        LAMPIRAN
                    </h3>
                    <span class="text-xs text-slate-400 font-medium">Dokumen Pendukung</span>
                </div>
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/70 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                            <i class="ti ti-file-text text-lg"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-800">Invoice & Dokumen Pembelian</p>
                            <p class="text-[11px] text-slate-400">{{ $aset->no_invoice ? 'No Invoice: ' . $aset->no_invoice : 'Belum ada dokumen fisik dilampirkan' }}</p>
                        </div>
                    </div>
                    @if(auth()->check() && auth()->user()->role === 'super_admin')
                        <a href="{{ route('aset.edit', $aset->id) }}" class="text-xs font-bold text-emerald-600 hover:text-emerald-700">
                            + Unggah
                        </a>
                    @endif
                </div>
            </div>

        </div>

        <!-- ======================================================= -->
        <!-- KOLOM KANAN: SUB-MODUL RIWAYAT, AGENDA, KEUANGAN, JURNAL -->
        <!-- ======================================================= -->
        <div class="lg:col-span-5 space-y-5 lg:sticky lg:top-6">

            <!-- Sub-Modul Card (Sesuai Warna Aplikasi AMANA) -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-7 space-y-5">
                
                <!-- Tabs Header Bar -->
                <div class="border-b border-slate-200/80 pb-0">
                    <div class="flex items-center space-x-1 overflow-x-auto">
                        <button @click="activeTab = 'riwayat'" type="button"
                                class="px-4 sm:px-5 py-2.5 rounded-t-xl text-xs uppercase font-extrabold tracking-wider transition-all"
                                :class="activeTab === 'riwayat'
                                    ? 'bg-emerald-50 text-emerald-700 border-t border-x border-emerald-200 shadow-xs'
                                    : 'bg-slate-200 hover:bg-slate-300 text-slate-600 font-bold'">
                            RIWAYAT ({{ $aset->riwayat->count() }})
                        </button>

                        <button @click="activeTab = 'agenda'" type="button"
                                class="px-4 sm:px-5 py-2.5 rounded-t-xl text-xs uppercase font-extrabold tracking-wider transition-all"
                                :class="activeTab === 'agenda'
                                    ? 'bg-emerald-50 text-emerald-700 border-t border-x border-emerald-200 shadow-xs'
                                    : 'bg-slate-200 hover:bg-slate-300 text-slate-600 font-bold'">
                            AGENDA ({{ $aset->agenda->count() }})
                        </button>

                        <button @click="activeTab = 'keuangan'" type="button"
                                class="px-4 sm:px-5 py-2.5 rounded-t-xl text-xs uppercase font-extrabold tracking-wider transition-all"
                                :class="activeTab === 'keuangan'
                                    ? 'bg-emerald-50 text-emerald-700 border-t border-x border-emerald-200 shadow-xs'
                                    : 'bg-slate-200 hover:bg-slate-300 text-slate-600 font-bold'">
                            KEUANGAN ({{ $aset->keuangan->count() }})
                        </button>

                        <button @click="activeTab = 'jurnal'" type="button"
                                class="px-4 sm:px-5 py-2.5 rounded-t-xl text-xs uppercase font-extrabold tracking-wider transition-all"
                                :class="activeTab === 'jurnal'
                                    ? 'bg-emerald-50 text-emerald-700 border-t border-x border-emerald-200 shadow-xs'
                                    : 'bg-slate-200 hover:bg-slate-300 text-slate-600 font-bold'">
                            JURNAL ({{ $aset->jurnal->count() }})
                        </button>
                    </div>
                </div>

                <!-- Banner Bar with Action Button (AMANA Emerald / Cyan Theme) -->
                <div class="h-14 px-4 bg-emerald-50/70 border border-emerald-200/80 rounded-xl flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-2 text-xs font-bold text-emerald-900 truncate mr-2">
                        <i class="ti text-base text-emerald-600 flex-shrink-0"
                           :class="{
                                'ti-history': activeTab === 'riwayat',
                                'ti-calendar-event': activeTab === 'agenda',
                                'ti-cash': activeTab === 'keuangan',
                                'ti-notebook': activeTab === 'jurnal'
                           }"></i>
                        <span class="truncate" x-text="
                            activeTab === 'riwayat' ? 'Catatan Riwayat & Perpindahan Aset' :
                            (activeTab === 'agenda' ? 'Jadwal Agenda & Perawatan Aset' :
                            (activeTab === 'keuangan' ? 'Catatan Pengeluaran & Biaya Perawatan' : 'Buku Harian & Jurnal Kejadian Aset'))
                        "></span>
                    </div>

                    @if(auth()->check() && auth()->user()->role === 'super_admin')
                        <div class="flex-shrink-0">
                            <button x-show="activeTab === 'riwayat'" type="button"
                                    @click="modalType = 'riwayat'; modalOpen = true"
                                    class="px-3.5 py-1.5 rounded-lg bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-extrabold text-xs uppercase tracking-wider transition-all shadow-sm shadow-emerald-600/20">
                                + RIWAYAT
                            </button>
                            <button x-show="activeTab === 'agenda'" type="button" x-cloak
                                    @click="modalType = 'agenda'; modalOpen = true"
                                    class="px-3.5 py-1.5 rounded-lg bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-extrabold text-xs uppercase tracking-wider transition-all shadow-sm shadow-emerald-600/20">
                                + AGENDA
                            </button>
                            <button x-show="activeTab === 'keuangan'" type="button" x-cloak
                                    @click="modalType = 'keuangan'; modalOpen = true"
                                    class="px-3.5 py-1.5 rounded-lg bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-extrabold text-xs uppercase tracking-wider transition-all shadow-sm shadow-emerald-600/20">
                                + CATAT BIAYA
                            </button>
                            <button x-show="activeTab === 'jurnal'" type="button" x-cloak
                                    @click="modalType = 'jurnal'; modalOpen = true"
                                    class="px-3.5 py-1.5 rounded-lg bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-extrabold text-xs uppercase tracking-wider transition-all shadow-sm shadow-emerald-600/20">
                                + JURNAL
                            </button>
                        </div>
                    @endif
                </div>

                <!-- ================= TAB 1: RIWAYAT (Sesuai Amana.md) ================= -->
                <div x-show="activeTab === 'riwayat'" class="space-y-4 pt-1">
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Mencatat perjalanan riwayat (history) aset: perubahan lokasi, penanggung jawab, hingga kondisi dan kelengkapannya.
                    </p>

                    <div class="relative pl-6 space-y-4 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                        @forelse($aset->riwayat as $item)
                            <div class="relative group">
                                <span class="absolute -left-[27px] top-1 w-3 h-3 rounded-full border-2 border-white shadow-xs
                                    {{ $item->jenis_aksi === 'mutasi' ? 'bg-indigo-600 ring-4 ring-indigo-50' :
                                       ($item->jenis_aksi === 'pembuatan' ? 'bg-emerald-600 ring-4 ring-emerald-50' :
                                       ($item->jenis_aksi === 'ubah_status' ? 'bg-rose-500 ring-4 ring-rose-50' : 'bg-cyan-500 ring-4 ring-cyan-50')) }}">
                                </span>
                                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70 hover:border-emerald-200 transition-colors space-y-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wide
                                                {{ $item->jenis_aksi === 'mutasi' ? 'bg-indigo-100 text-indigo-800' :
                                                   ($item->jenis_aksi === 'pembuatan' ? 'bg-emerald-100 text-emerald-800' :
                                                   ($item->jenis_aksi === 'ubah_status' ? 'bg-rose-100 text-rose-800' : 'bg-slate-200 text-slate-700')) }}">
                                                {{ str_replace('_', ' ', $item->jenis_aksi) }}
                                            </span>
                                            <span class="text-[11px] font-bold text-slate-500">
                                                Sejak: {{ $item->sejak_tanggal ? $item->sejak_tanggal->format('d M Y') : $item->created_at->format('d M Y') }}
                                            </span>
                                        </div>

                                        @if(auth()->check() && auth()->user()->role === 'super_admin')
                                            <div class="flex items-center gap-1">
                                                <button type="button" @click="openEditModal('riwayat', @js($item))"
                                                        class="p-1 rounded-lg text-slate-400 hover:text-cyan-600 hover:bg-cyan-50 transition-colors"
                                                        title="Edit Riwayat">
                                                    <i class="ti ti-pencil text-sm"></i>
                                                </button>
                                                @if($item->jenis_aksi !== 'pembuatan')
                                                    <form method="POST" action="{{ route('aset.riwayat.destroy', $item->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan riwayat ini?');" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                                                title="Hapus Riwayat">
                                                            <i class="ti ti-trash text-sm"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Detail Grid Sesuai Amana.md -->
                                    <div class="grid grid-cols-2 gap-2 text-xs pt-1 border-t border-slate-200/50">
                                        <div>
                                            <span class="text-[11px] text-slate-400 block font-medium">Penanggung Jawab:</span>
                                            <span class="font-bold text-slate-800">{{ $item->penanggungJawab->nama ?? '-' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-[11px] text-slate-400 block font-medium">Lokasi Penempatan:</span>
                                            <span class="font-bold text-slate-800">{{ $item->lokasi->nama_lokasi ?? '-' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-[11px] text-slate-400 block font-medium">Divisi Pengampu:</span>
                                            <span class="font-bold text-slate-800">{{ $item->divisi->nama_divisi ?? ($item->penanggungJawab->divisi->nama_divisi ?? ($aset->divisi->nama_divisi ?? '-')) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-[11px] text-slate-400 block font-medium">Jumlah Unit:</span>
                                            <span class="font-bold text-slate-800">{{ $item->jumlah ?? $aset->jumlah_unit }} Unit</span>
                                        </div>
                                        <div class="flex items-center gap-2 col-span-2">
                                            <div>
                                                <span class="text-[11px] text-slate-400 block font-medium">Kondisi:</span>
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 text-emerald-800">
                                                    {{ $item->kondisi_persen ?? 100 }}%
                                                </span>
                                            </div>
                                            <div>
                                                <span class="text-[11px] text-slate-400 block font-medium">Kelengkapan:</span>
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-extrabold bg-cyan-100 text-cyan-800">
                                                    {{ $item->kelengkapan_persen ?? 100 }}%
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    @if($item->kode_aset_sebelumnya && $item->kode_aset_baru && $item->kode_aset_sebelumnya !== $item->kode_aset_baru)
                                        <div class="mt-2 p-2 rounded-xl bg-emerald-50/80 border border-emerald-200 flex flex-wrap items-center justify-between gap-1 text-[11px]">
                                            <span class="text-emerald-900 font-bold flex items-center gap-1">
                                                <i class="ti ti-refresh text-xs text-emerald-600"></i> Mutasi Kode Aset:
                                            </span>
                                            <div class="flex items-center gap-1 font-mono font-bold">
                                                <span class="text-slate-400 line-through text-[10px]">{{ $item->kode_aset_sebelumnya }}</span>
                                                <i class="ti ti-arrow-right text-emerald-600 text-xs"></i>
                                                <span class="text-emerald-800 bg-white px-1.5 py-0.5 rounded border border-emerald-300 text-[10px]">{{ $item->kode_aset_baru }}</span>
                                            </div>
                                        </div>
                                    @elseif($item->kode_aset_baru)
                                        <div class="mt-1 flex items-center gap-1 text-[10px] text-slate-400 font-mono">
                                            <span>Kode Aset:</span>
                                            <span class="font-bold text-slate-600">{{ $item->kode_aset_baru }}</span>
                                        </div>
                                    @endif

                                    @if($item->keterangan)
                                        <p class="text-xs text-slate-600 bg-white p-2 rounded-xl border border-slate-100">
                                            {{ $item->keterangan }}
                                        </p>
                                    @endif

                                    <!-- Audit Info Pembuat & Pengedit -->
                                    <div class="pt-2 mt-2 border-t border-slate-200/60 flex flex-wrap items-center justify-between gap-2 text-[10.5px] text-slate-400">
                                        <div class="flex items-center gap-1">
                                            <i class="ti ti-plus text-emerald-600"></i>
                                            <span>Ditambahkan: <strong class="text-slate-600 font-semibold">{{ $item->user->name ?? 'Sistem' }}</strong> ({{ $item->created_at ? $item->created_at->translatedFormat('d M Y H:i') : '-' }})</span>
                                        </div>
                                        @if($item->updated_by || ($item->updated_at && $item->created_at && $item->updated_at->diffInSeconds($item->created_at) > 60))
                                            <div class="flex items-center gap-1">
                                                <i class="ti ti-pencil text-cyan-600"></i>
                                                <span>Terakhir Diedit: <strong class="text-slate-600 font-semibold">{{ $item->updater->name ?? ($item->user->name ?? 'Admin') }}</strong> ({{ $item->updated_at->translatedFormat('d M Y H:i') }})</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-6 text-center text-slate-400 text-xs">
                                <i class="ti ti-history-off text-2xl block mb-1"></i>
                                Belum ada riwayat aktivitas untuk aset ini.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- ================= TAB 2: AGENDA (Sesuai Amana.md) ================= -->
                <div x-show="activeTab === 'agenda'" x-cloak class="space-y-4 pt-1">
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Mencatat jadwal servis, perawatan berkala, ganti sparepart, atau pembayaran pajak sesuai siklus kalender.
                    </p>

                    @php
                        $agendaAktif = $aset->agenda->filter(function ($ag) {
                            return $ag->tipe_agenda !== 'tanggal_tertentu' || $ag->status !== 'selesai';
                        });
                        $agendaSelesaiTertentu = $aset->agenda->filter(function ($ag) {
                            return $ag->tipe_agenda === 'tanggal_tertentu' && $ag->status === 'selesai';
                        });
                    @endphp

                    <div class="space-y-3">
                        @forelse($agendaAktif as $agenda)
                            <div class="p-3.5 rounded-2xl border transition-all {{ $agenda->status === 'selesai' ? 'bg-emerald-50/40 border-emerald-200' : 'bg-slate-50 border-slate-200/80' }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="space-y-1 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-900">{{ $agenda->nama_agenda }}</span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase
                                                {{ $agenda->status === 'selesai' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                {{ $agenda->status }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs font-medium text-emerald-700">
                                            <i class="ti ti-calendar-time text-sm"></i>
                                            <span>{{ $agenda->jadwal_teks }}</span>
                                        </div>
                                        @if($agenda->keterangan)
                                            <p class="text-xs text-slate-500">{{ $agenda->keterangan }}</p>
                                        @endif
                                        @if($agenda->biaya_estimasi > 0)
                                            <div class="text-[11px] font-semibold text-slate-600 pt-0.5">
                                                Estimasi Biaya: Rp {{ number_format($agenda->biaya_estimasi, 0, ',', '.') }}
                                            </div>
                                        @endif

                                        @if($agenda->status === 'selesai')
                                            <!-- Detail Penyelesaian & Bukti Aksi -->
                                            <div class="mt-2 p-2.5 rounded-xl bg-white/80 border border-emerald-200 space-y-1.5 text-xs">
                                                <div class="flex flex-wrap items-center justify-between gap-1">
                                                    <span class="text-[11px] font-bold text-emerald-900 flex items-center gap-1">
                                                        <i class="ti ti-circle-check-filled text-emerald-600"></i>
                                                        Selesai Dilaksanakan: {{ $agenda->tanggal_selesai ? \Carbon\Carbon::parse($agenda->tanggal_selesai)->translatedFormat('d M Y') : '-' }}
                                                    </span>
                                                    @if($agenda->biaya_riil > 0)
                                                        <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200 font-mono font-bold text-[10.5px]">
                                                            Realisasi: Rp {{ number_format($agenda->biaya_riil, 0, ',', '.') }}
                                                        </span>
                                                    @endif
                                                </div>
                                                @if($agenda->catatan_penyelesaian)
                                                    <p class="text-slate-600 text-[11px] italic">
                                                        "{{ $agenda->catatan_penyelesaian }}"
                                                    </p>
                                                @endif
                                                @if($agenda->lampiran_penyelesaian_url)
                                                    <div class="pt-0.5">
                                                        <a href="{{ $agenda->lampiran_penyelesaian_url }}" target="_blank"
                                                           class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-bold hover:bg-emerald-100 transition-colors">
                                                            <i class="ti ti-paperclip text-emerald-600"></i>
                                                            Lihat Dokumen / Lampiran Bukti
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    @if(auth()->check() && auth()->user()->role === 'super_admin')
                                        <div class="flex-shrink-0 flex items-center gap-1.5">
                                            <button type="button" @click="openEditModal('agenda', @js($agenda))"
                                                    class="p-1.5 rounded-xl border border-slate-200 text-slate-500 hover:text-cyan-600 hover:bg-cyan-50 text-xs font-bold transition-all shadow-2xs"
                                                    title="Edit Agenda">
                                                <i class="ti ti-pencil text-sm"></i>
                                            </button>
                                            <form method="POST" action="{{ route('aset.agenda.destroy', $agenda->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus agenda ini?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="p-1.5 rounded-xl border border-slate-200 text-slate-500 hover:text-rose-600 hover:bg-rose-50 text-xs font-bold transition-all shadow-2xs"
                                                        title="Hapus Agenda">
                                                    <i class="ti ti-trash text-sm"></i>
                                                </button>
                                            </form>
                                            @if($agenda->status === 'selesai')
                                                <form method="POST" action="{{ route('aset.agenda.toggle', $agenda->id) }}" onsubmit="return confirm('Kembalikan status agenda ini ke Belum Selesai (Pending)? Catatan jurnal dan riwayat keuangan yang telah dibuat tetap disimpan.');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                            class="p-1.5 rounded-xl border text-xs font-bold transition-all bg-emerald-600 text-white border-emerald-600 hover:bg-emerald-700 shadow-2xs"
                                                            title="Selesai (Klik untuk kembalikan ke Pending)">
                                                        <i class="ti ti-check text-sm"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <button type="button"
                                                        @click="openAgendaSelesaiModal(@js($agenda))"
                                                        class="px-2.5 py-1.5 rounded-xl border text-xs font-bold transition-all bg-white text-emerald-700 border-emerald-300 hover:bg-emerald-50 flex items-center gap-1 shadow-2xs cursor-pointer"
                                                        title="Selesaikan Agenda (Isi Bukti Dokumen & Biaya)">
                                                    <i class="ti ti-check text-sm text-emerald-600 font-bold"></i>
                                                    <span class="font-extrabold text-[11px]">Selesaikan</span>
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <!-- Audit Info Pembuat & Pengedit -->
                                <div class="pt-2 mt-2 border-t border-slate-200/60 flex flex-wrap items-center justify-between gap-2 text-[10.5px] text-slate-400">
                                    <div class="flex items-center gap-1">
                                        <i class="ti ti-plus text-emerald-600"></i>
                                        <span>Dijadwalkan: <strong class="text-slate-600 font-semibold">{{ $agenda->user->name ?? 'Sistem' }}</strong> ({{ $agenda->created_at ? $agenda->created_at->translatedFormat('d M Y H:i') : '-' }})</span>
                                    </div>
                                    @if($agenda->updated_by || ($agenda->updated_at && $agenda->created_at && $agenda->updated_at->diffInSeconds($agenda->created_at) > 60))
                                        <div class="flex items-center gap-1">
                                            <i class="ti ti-pencil text-cyan-600"></i>
                                            <span>{{ $agenda->status === 'selesai' ? 'Diselesaikan/Diedit' : 'Terakhir Diedit' }}: <strong class="text-slate-600 font-semibold">{{ $agenda->updater->name ?? ($agenda->user->name ?? 'Admin') }}</strong> ({{ $agenda->updated_at->translatedFormat('d M Y H:i') }})</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-6 text-center text-slate-400 text-xs">
                                <i class="ti ti-calendar-off text-2xl block mb-1"></i>
                                Belum ada agenda kegiatan aktif yang dijadwalkan.
                            </div>
                        @endforelse
                    </div>

                    @if($agendaSelesaiTertentu->isNotEmpty())
                        <!-- Riwayat Agenda Tanggal Tertentu Selesai (Disembunyikan dari Daftar Aktif) -->
                        <div x-data="{ showSelesai: false }" class="pt-3 border-t border-slate-200/80">
                            <button type="button" @click="showSelesai = !showSelesai"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors cursor-pointer">
                                <i class="ti" :class="showSelesai ? 'ti-chevron-down' : 'ti-chevron-right'"></i>
                                <span>Lihat Riwayat Agenda Selesai ({{ $agendaSelesaiTertentu->count() }})</span>
                            </button>

                            <div x-show="showSelesai" x-cloak class="mt-3 space-y-3">
                                @foreach($agendaSelesaiTertentu as $agenda)
                                    <div class="p-3.5 rounded-2xl border bg-emerald-50/40 border-emerald-200">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="space-y-1 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-bold text-slate-900">{{ $agenda->nama_agenda }}</span>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800">
                                                        Selesai
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2 text-xs font-medium text-emerald-700">
                                                    <i class="ti ti-calendar-time text-sm"></i>
                                                    <span>{{ $agenda->jadwal_teks }}</span>
                                                </div>
                                                @if($agenda->keterangan)
                                                    <p class="text-xs text-slate-500">{{ $agenda->keterangan }}</p>
                                                @endif

                                                <!-- Detail Penyelesaian & Bukti Aksi -->
                                                <div class="mt-2 p-2.5 rounded-xl bg-white/80 border border-emerald-200 space-y-1.5 text-xs">
                                                    <div class="flex flex-wrap items-center justify-between gap-1">
                                                        <span class="text-[11px] font-bold text-emerald-900 flex items-center gap-1">
                                                            <i class="ti ti-circle-check-filled text-emerald-600"></i>
                                                            Selesai Dilaksanakan: {{ $agenda->tanggal_selesai ? \Carbon\Carbon::parse($agenda->tanggal_selesai)->translatedFormat('d M Y') : '-' }}
                                                        </span>
                                                        @if($agenda->biaya_riil > 0)
                                                            <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200 font-mono font-bold text-[10.5px]">
                                                                Realisasi: Rp {{ number_format($agenda->biaya_riil, 0, ',', '.') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    @if($agenda->catatan_penyelesaian)
                                                        <p class="text-slate-600 text-[11px] italic">
                                                            "{{ $agenda->catatan_penyelesaian }}"
                                                        </p>
                                                    @endif
                                                    @if($agenda->lampiran_penyelesaian_url)
                                                        <div class="pt-0.5">
                                                            <a href="{{ $agenda->lampiran_penyelesaian_url }}" target="_blank"
                                                               class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-bold hover:bg-emerald-100 transition-colors">
                                                                <i class="ti ti-paperclip text-emerald-600"></i>
                                                                Lihat Dokumen / Lampiran Bukti
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            @if(auth()->check() && auth()->user()->role === 'super_admin')
                                                <div class="flex-shrink-0 flex items-center gap-1.5">
                                                    <button type="button" @click="openEditModal('agenda', @js($agenda))"
                                                            class="p-1.5 rounded-xl border border-slate-200 text-slate-500 hover:text-cyan-600 hover:bg-cyan-50 text-xs font-bold transition-all shadow-2xs"
                                                            title="Edit Agenda">
                                                        <i class="ti ti-pencil text-sm"></i>
                                                    </button>
                                                    <form method="POST" action="{{ route('aset.agenda.destroy', $agenda->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus agenda ini?');" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="p-1.5 rounded-xl border border-slate-200 text-slate-500 hover:text-rose-600 hover:bg-rose-50 text-xs font-bold transition-all shadow-2xs"
                                                                title="Hapus Agenda">
                                                            <i class="ti ti-trash text-sm"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="{{ route('aset.agenda.toggle', $agenda->id) }}" onsubmit="return confirm('Kembalikan status agenda ini ke Belum Selesai (Pending)? Catatan jurnal dan riwayat keuangan yang telah dibuat tetap disimpan.');">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit"
                                                                class="p-1.5 rounded-xl border text-xs font-bold transition-all bg-emerald-600 text-white border-emerald-600 hover:bg-emerald-700 shadow-2xs"
                                                                title="Selesai (Klik untuk kembalikan ke Pending)">
                                                            <i class="ti ti-check text-sm"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Audit Info Pembuat & Pengedit -->
                                        <div class="pt-2 mt-2 border-t border-slate-200/60 flex flex-wrap items-center justify-between gap-2 text-[10.5px] text-slate-400">
                                            <div class="flex items-center gap-1">
                                                <i class="ti ti-plus text-emerald-600"></i>
                                                <span>Dijadwalkan: <strong class="text-slate-600 font-semibold">{{ $agenda->user->name ?? 'Sistem' }}</strong> ({{ $agenda->created_at ? $agenda->created_at->translatedFormat('d M Y H:i') : '-' }})</span>
                                            </div>
                                            @if($agenda->updated_by || ($agenda->updated_at && $agenda->created_at && $agenda->updated_at->diffInSeconds($agenda->created_at) > 60))
                                                <div class="flex items-center gap-1">
                                                    <i class="ti ti-pencil text-cyan-600"></i>
                                                    <span>Diselesaikan/Diedit: <strong class="text-slate-600 font-semibold">{{ $agenda->updater->name ?? ($agenda->user->name ?? 'Admin') }}</strong> ({{ $agenda->updated_at->translatedFormat('d M Y H:i') }})</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- ================= TAB 3: KEUANGAN / BIAYA ASET ================= -->
                <div x-show="activeTab === 'keuangan'" x-cloak class="space-y-4 pt-1">
                    @php
                        $totPengeluaran = $aset->keuangan->sum('nominal');
                    @endphp

                    <!-- Widget Ringkasan Total Pengeluaran & Biaya -->
                    <div class="p-4 rounded-2xl bg-rose-50/80 border border-rose-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-2xs">
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-rose-700 block">Total Pengeluaran & Biaya Aset</span>
                            <span class="text-[11px] text-slate-500">Akumulasi seluruh biaya perbaikan, servis, suku cadang, dan pemeliharaan aset</span>
                        </div>
                        <span class="text-base sm:text-lg font-extrabold text-rose-900 font-mono">
                            Rp {{ number_format($totPengeluaran, 0, ',', '.') }}
                        </span>
                    </div>

                    <div class="space-y-2.5">
                        @forelse($aset->keuangan as $keu)
                            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 hover:bg-white transition-colors space-y-2">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-rose-100 text-rose-800">
                                                Pengeluaran
                                            </span>
                                            <span class="text-xs font-bold text-slate-900">{{ $keu->jenis_transaksi ?: 'Biaya Perawatan' }}</span>
                                            @if($keu->is_dari_agenda)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200 flex items-center gap-1">
                                                    <i class="ti ti-link text-[10px]"></i> Otomatis dari Agenda
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-400 mt-1">
                                            {{ \Carbon\Carbon::parse($keu->tanggal)->translatedFormat('d M Y') }}
                                            @if($keu->keterangan)
                                                • {{ $keu->keterangan }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2.5">
                                        <span class="text-xs font-extrabold flex-shrink-0 text-rose-600 font-mono">
                                            - Rp {{ number_format($keu->nominal, 0, ',', '.') }}
                                        </span>
                                        @if(auth()->check() && auth()->user()->role === 'super_admin')
                                            <div class="flex items-center gap-1">
                                                <button type="button" @click="openEditModal('keuangan', @js($keu))"
                                                        class="p-1 rounded-lg text-slate-400 hover:text-cyan-600 hover:bg-cyan-50 transition-colors"
                                                        title="Edit Catatan Keuangan">
                                                    <i class="ti ti-pencil text-sm"></i>
                                                </button>
                                                @if(! $keu->is_dari_agenda)
                                                    <form method="POST" action="{{ route('aset.keuangan.destroy', $keu->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan biaya ini?');" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                                                title="Hapus Catatan Keuangan">
                                                            <i class="ti ti-trash text-sm"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Audit Info Pembuat & Pengedit -->
                                <div class="pt-2 border-t border-slate-200/60 flex flex-wrap items-center justify-between gap-2 text-[10.5px] text-slate-400">
                                    <div class="flex items-center gap-1">
                                        <i class="ti ti-plus text-emerald-600"></i>
                                        <span>Dicatat: <strong class="text-slate-600 font-semibold">{{ $keu->user->name ?? 'Sistem' }}</strong> ({{ $keu->created_at ? $keu->created_at->translatedFormat('d M Y H:i') : '-' }})</span>
                                    </div>
                                    @if($keu->updated_by || ($keu->updated_at && $keu->created_at && $keu->updated_at->diffInSeconds($keu->created_at) > 60))
                                        <div class="flex items-center gap-1">
                                            <i class="ti ti-pencil text-cyan-600"></i>
                                            <span>Terakhir Diedit: <strong class="text-slate-600 font-semibold">{{ $keu->updater->name ?? ($keu->user->name ?? 'Admin') }}</strong> ({{ $keu->updated_at->translatedFormat('d M Y H:i') }})</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-6 text-center text-slate-400 text-xs">
                                <i class="ti ti-receipt-off text-2xl block mb-1"></i>
                                Belum ada catatan biaya atau pengeluaran untuk aset ini.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- ================= TAB 4: JURNAL (Sesuai Amana.md) ================= -->
                <div x-show="activeTab === 'jurnal'" x-cloak class="space-y-4 pt-1">
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Buku harian untuk mencatat berbagai kejadian yang menyertai aset (perbaikan, kecelakaan, dll) beserta bukti lampirannya.
                    </p>

                    <div class="space-y-3">
                        @forelse($aset->jurnal as $jurnal)
                            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2.5">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                            <i class="ti ti-calendar text-emerald-600"></i>
                                            {{ \Carbon\Carbon::parse($jurnal->tanggal)->format('d M Y') }}
                                        </span>
                                        @if($jurnal->is_dari_agenda)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200 flex items-center gap-1">
                                                <i class="ti ti-link text-[10px]"></i> Otomatis dari Agenda
                                            </span>
                                        @endif
                                    </div>

                                    @if(auth()->check() && auth()->user()->role === 'super_admin' && ! $jurnal->is_dari_agenda)
                                        <div class="flex items-center gap-1">
                                            <button type="button" @click="openEditModal('jurnal', @js($jurnal))"
                                                    class="p-1 rounded-lg text-slate-400 hover:text-cyan-600 hover:bg-cyan-50 transition-colors"
                                                    title="Edit Catatan Jurnal">
                                                <i class="ti ti-pencil text-sm"></i>
                                            </button>
                                            <form method="POST" action="{{ route('aset.jurnal.destroy', $jurnal->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan jurnal ini?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                                        title="Hapus Catatan Jurnal">
                                                    <i class="ti ti-trash text-sm"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>

                                <p class="text-xs font-medium text-slate-800 whitespace-pre-line">{{ $jurnal->kejadian }}</p>

                                @if($jurnal->lampiran_url)
                                    <div class="pt-1">
                                        <a href="{{ $jurnal->lampiran_url }}" target="_blank"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 border border-slate-200 hover:border-emerald-300 font-bold text-xs transition-colors shadow-2xs">
                                            <i class="ti ti-paperclip text-emerald-600"></i>
                                            Lihat / Unduh Lampiran File
                                        </a>
                                    </div>
                                @endif

                                <!-- Audit Info Pembuat & Pengedit -->
                                <div class="pt-2 border-t border-slate-200/60 flex flex-wrap items-center justify-between gap-2 text-[10.5px] text-slate-400">
                                    <div class="flex items-center gap-1">
                                        <i class="ti ti-plus text-emerald-600"></i>
                                        <span>Dilaporkan: <strong class="text-slate-600 font-semibold">{{ $jurnal->user->name ?? 'Sistem' }}</strong> ({{ $jurnal->created_at ? $jurnal->created_at->translatedFormat('d M Y H:i') : '-' }})</span>
                                    </div>
                                    @if($jurnal->updated_by || ($jurnal->updated_at && $jurnal->created_at && $jurnal->updated_at->diffInSeconds($jurnal->created_at) > 60))
                                        <div class="flex items-center gap-1">
                                            <i class="ti ti-pencil text-cyan-600"></i>
                                            <span>Terakhir Diperbarui: <strong class="text-slate-600 font-semibold">{{ $jurnal->updater->name ?? ($jurnal->user->name ?? 'Admin') }}</strong> ({{ $jurnal->updated_at->translatedFormat('d M Y H:i') }})</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-6 text-center text-slate-400 text-xs">
                                <i class="ti ti-notebook-off text-2xl block mb-1"></i>
                                Belum ada catatan jurnal kejadian untuk aset ini.
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- ======================================================= -->
    <!-- MODAL FORM: EDIT SUB-MODUL ASET                         -->
    <!-- ======================================================= -->
    <div x-show="editModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div @click.away="editModalOpen = false"
             class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-200 space-y-5 animate-in fade-in zoom-in duration-200 max-h-[90vh] overflow-y-auto">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-cyan-100 text-cyan-700 flex items-center justify-center font-bold">
                        <i class="ti ti-pencil text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900" x-text="
                            editType === 'riwayat' ? 'Edit Catatan Riwayat Aset' :
                            (editType === 'agenda' ? 'Edit Agenda Kegiatan' :
                            (editType === 'keuangan' ? 'Edit Transaksi Keuangan' : 'Edit Jurnal Kejadian Aset'))
                        "></h3>
                        <p class="text-[11px] text-slate-400">Perbarui rincian data sub-modul</p>
                    </div>
                </div>
                <button @click="editModalOpen = false" class="text-slate-400 hover:text-slate-700 p-1 rounded-lg hover:bg-slate-100">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>

            <form :action="'/' + editType + '/' + editItem?.id" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <!-- 1. Form Edit Riwayat -->
                <template x-if="editType === 'riwayat'">
                    <div class="space-y-4">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Berlaku Sejak Tanggal <span class="text-rose-500">*</span></label>
                            <input type="date" name="sejak_tanggal" :value="editItem?.sejak_tanggal ? editItem.sejak_tanggal.substring(0, 10) : ''" required
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-500/20 focus:border-cyan-500 font-medium">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Kondisi (%)</label>
                                <input type="number" name="kondisi_persen" :value="editItem?.kondisi_persen ?? 100" min="0" max="100" required
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none font-bold">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Kelengkapan (%)</label>
                                <input type="number" name="kelengkapan_persen" :value="editItem?.kelengkapan_persen ?? 100" min="0" max="100" required
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none font-bold">
                            </div>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Catatan / Keterangan</label>
                            <textarea name="keterangan" rows="3" :value="editItem?.keterangan || ''"
                                      class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-500/20 focus:border-cyan-500 font-medium"></textarea>
                        </div>
                    </div>
                </template>

                <!-- 2. Form Edit Agenda -->
                <template x-if="editType === 'agenda'">
                    <div class="space-y-4" x-data="{ agendaType: editItem?.tipe_agenda || 'mingguan' }">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Agenda / Kegiatan <span class="text-rose-500">*</span></label>
                            <input type="text" name="nama_agenda" :value="editItem?.nama_agenda" required
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-500/20 focus:border-cyan-500 font-bold">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Siklus / Tipe Pengulangan</label>
                            <select name="tipe_agenda" x-model="agendaType"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white font-medium">
                                <option value="mingguan">Mingguan (Hari Tertentu)</option>
                                <option value="bulanan">Bulanan (Tanggal Tertentu Tiap Bulan)</option>
                                <option value="tahunan">Tahunan (Tanggal & Bulan Tertentu)</option>
                                <option value="tanggal_tertentu">Tanggal Tertentu (Sekali Jalan)</option>
                            </select>
                        </div>

                        <!-- Hari (Mingguan) -->
                        <div x-show="agendaType === 'mingguan'">
                            <label class="block font-bold text-slate-700 mb-1">Pilih Hari</label>
                            <select name="hari" :value="editItem?.hari || 'senin'" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                <option value="senin">Senin</option>
                                <option value="selasa">Selasa</option>
                                <option value="rabu">Rabu</option>
                                <option value="kamis">Kamis</option>
                                <option value="jumat">Jumat</option>
                                <option value="sabtu">Sabtu</option>
                                <option value="minggu">Minggu</option>
                            </select>
                        </div>

                        <!-- Tanggal Hari (Bulanan / Tahunan) -->
                        <div x-show="agendaType === 'bulanan' || agendaType === 'tahunan'">
                            <label class="block font-bold text-slate-700 mb-1">Tanggal (1 - 31)</label>
                            <input type="number" name="tanggal_hari" :value="editItem?.tanggal_hari || 1" min="1" max="31"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50">
                        </div>

                        <!-- Bulan (Tahunan) -->
                        <div x-show="agendaType === 'tahunan'">
                            <label class="block font-bold text-slate-700 mb-1">Bulan</label>
                            <select name="bulan" :value="editItem?.bulan || 1" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                <option value="1">Januari</option>
                                <option value="2">Februari</option>
                                <option value="3">Maret</option>
                                <option value="4">April</option>
                                <option value="5">Mei</option>
                                <option value="6">Juni</option>
                                <option value="7">Juli</option>
                                <option value="8">Agustus</option>
                                <option value="9">September</option>
                                <option value="10">Oktober</option>
                                <option value="11">November</option>
                                <option value="12">Desember</option>
                            </select>
                        </div>

                        <!-- Tanggal Spesifik -->
                        <div x-show="agendaType === 'tanggal_tertentu'">
                            <label class="block font-bold text-slate-700 mb-1">Tanggal Kegiatan</label>
                            <input type="date" name="tanggal" :value="editItem?.tanggal ? editItem.tanggal.substring(0, 10) : ''"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50">
                        </div>

                        <!-- Estimasi Biaya -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Estimasi Biaya (Rp)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold">Rp</span>
                                <input type="text" name="biaya_estimasi" x-model="editNominalInput"
                                       @input="editNominalInput = formatRibuan($event.target.value)"
                                       class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 font-mono font-bold">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Keterangan / SOP</label>
                            <textarea name="keterangan" rows="2" :value="editItem?.keterangan || ''"
                                      class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50"></textarea>
                        </div>
                    </div>
                </template>

                <!-- 3. Form Edit Keuangan -->
                <template x-if="editType === 'keuangan'">
                    <div class="space-y-4">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tanggal Transaksi <span class="text-rose-500">*</span></label>
                            <input type="date" name="tanggal" :value="editItem?.tanggal ? editItem.tanggal.substring(0, 10) : ''" required
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 font-medium">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Jenis / Keperluan Transaksi <span class="text-rose-500">*</span></label>
                            <input type="text" name="jenis_transaksi" :value="editItem?.jenis_transaksi" required
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 font-bold">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nominal Biaya (Rp) <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold">Rp</span>
                                <input type="text" name="nominal" x-model="editNominalInput"
                                       @input="editNominalInput = formatRibuan($event.target.value)" required
                                       class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 font-mono font-bold text-rose-600">
                            </div>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Keterangan Tambahan</label>
                            <textarea name="keterangan" rows="2" :value="editItem?.keterangan || ''"
                                      class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50"></textarea>
                        </div>
                    </div>
                </template>

                <!-- 4. Form Edit Jurnal (Hanya Manual) -->
                <template x-if="editType === 'jurnal'">
                    <div class="space-y-4">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tanggal Kejadian <span class="text-rose-500">*</span></label>
                            <input type="date" name="tanggal" :value="editItem?.tanggal ? editItem.tanggal.substring(0, 10) : ''" required
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 font-medium">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Uraian Kejadian / Kronologi <span class="text-rose-500">*</span></label>
                            <textarea name="kejadian" rows="3" required :value="editItem?.kejadian"
                                      class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 font-medium"></textarea>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Ganti Dokumen / Foto Bukti (Opsional)</label>
                            <input type="file" name="lampiran" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx"
                                   class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100 cursor-pointer">
                            <span class="text-[10.5px] text-slate-400 mt-0.5 block">Biarkan kosong jika tidak ingin mengubah dokumen lampiran.</span>
                        </div>
                    </div>
                </template>

                <!-- Tombol Aksi Modal -->
                <div class="pt-3 flex items-center justify-end gap-2.5 border-t border-slate-100">
                    <button type="button" @click="editModalOpen = false"
                            class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-extrabold shadow-sm shadow-cyan-600/20 transition-all flex items-center gap-1.5 cursor-pointer">
                        <i class="ti ti-device-floppy"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================= -->
    <!-- MODAL FORM: SELESAIKAN AGENDA ASET                     -->
    <!-- ======================================================= -->
    <div x-show="agendaSelesaiModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div @click.away="agendaSelesaiModalOpen = false"
             class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-200 space-y-5 animate-in fade-in zoom-in duration-200 max-h-[90vh] overflow-y-auto">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                        <i class="ti ti-check text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Selesaikan Agenda Kegiatan</h3>
                        <p class="text-[11px] text-slate-400">Catat bukti pelaksanaan, dokumen lampiran & biaya</p>
                    </div>
                </div>
                <button @click="agendaSelesaiModalOpen = false" class="text-slate-400 hover:text-slate-700 p-1 rounded-lg hover:bg-slate-100">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>

            <!-- Ringkasan Agenda Yang Dipilih -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1.5 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 font-medium">Nama Agenda:</span>
                    <strong class="text-slate-900 font-bold text-sm" x-text="selectedAgenda?.nama_agenda"></strong>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 font-medium">Jadwal / Siklus:</span>
                    <span class="font-semibold text-emerald-700" x-text="selectedAgenda?.jadwal_teks"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 font-medium">Estimasi Biaya:</span>
                    <span class="font-mono font-bold text-slate-700" x-text="'Rp ' + Number(selectedAgenda?.biaya_estimasi || 0).toLocaleString('id-ID')"></span>
                </div>
            </div>

            <form :action="'/agenda/' + selectedAgenda?.id + '/selesai'" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf

                <!-- Tanggal Selesai -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tanggal Pelaksanaan / Selesai <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal_selesai" value="{{ date('Y-m-d') }}" required
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-medium">
                </div>

                <!-- Catatan / Hasil Pengerjaan -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Catatan / Laporan Hasil Pengerjaan</label>
                    <textarea name="catatan_penyelesaian" rows="3"
                              placeholder="Misal: Perawatan rutin selesai dilakukan, komponen yang aus telah diperbaiki..."
                              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-medium"></textarea>
                    <span class="text-[10.5px] text-slate-400 mt-0.5 block">Disimpan sebagai riwayat hasil penyelesaian agenda.</span>
                </div>

                <!-- Dokumen Bukti / Lampiran -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Upload Dokumen / Foto Bukti (Opsional)</label>
                    <input type="file" name="lampiran" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx"
                           class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                    <span class="text-[10.5px] text-slate-400 mt-0.5 block">Mendukung file JPG, PNG, PDF maks 10MB. <strong>Agenda akan dicatat ke Jurnal hanya jika melampirkan foto/dokumen ini.</strong></span>
                </div>

                <!-- Realisasi Biaya Aktual -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Realisasi Biaya Aktual (Rp)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold">Rp</span>
                        <input type="text" name="biaya_riil"
                               x-model="agendaBiayaInput"
                               @input="agendaBiayaInput = formatRibuan($event.target.value)"
                               placeholder="0"
                               class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono font-bold text-slate-800">
                    </div>
                    <span class="text-[10.5px] text-slate-400 mt-0.5 block">Ketik nominal tanpa titik, otomatis terformat Rupiah. Jika biaya > 0, otomatis tercatat di tab Keuangan.</span>
                </div>

                <!-- Info Otomatisasi -->
                <div class="p-3 rounded-xl bg-emerald-50/80 border border-emerald-200 text-emerald-900 text-[11px] flex items-start gap-2">
                    <i class="ti ti-info-circle text-base text-emerald-600 flex-shrink-0 mt-0.5"></i>
                    <div>
                        <strong class="font-bold block">Integrasi Otomatis:</strong>
                        Aksi ini otomatis dicatat ke tab <strong>Jurnal</strong>, dan jika terdapat biaya akan langsung dicatat ke tab <strong>Keuangan</strong>.
                    </div>
                </div>

                <!-- Tombol Submit & Batal -->
                <div class="pt-2 flex items-center justify-end gap-2.5 border-t border-slate-100">
                    <button type="button" @click="agendaSelesaiModalOpen = false"
                            class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold shadow-sm shadow-emerald-600/20 transition-all flex items-center gap-1.5 cursor-pointer">
                        <i class="ti ti-check"></i>
                        <span>Simpan & Selesaikan</span>
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- ======================================================= -->
    <!-- MODAL FORM POPUPS (SESUAI SPESIFIKASI AMANA.MD)          -->
    <!-- ======================================================= -->
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div @click.away="modalOpen = false"
             class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-200 space-y-5 animate-in fade-in zoom-in duration-200 max-h-[90vh] overflow-y-auto">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span x-text="
                        modalType === 'riwayat' ? 'Catat Riwayat & Mutasi Aset' :
                        (modalType === 'agenda' ? 'Jadwalkan Agenda / Kalender Aset' :
                        (modalType === 'keuangan' ? 'Catat Transaksi Keuangan' : 'Tambah Jurnal Kejadian Aset'))
                    "></span>
                </h3>
                <button @click="modalOpen = false" class="text-slate-400 hover:text-slate-700 p-1 rounded-lg hover:bg-slate-100">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>

            <!-- 1. Form Riwayat (Sesuai Amana.md) -->
            <form x-show="modalType === 'riwayat'"
                  x-data="{
                      pjId: '{{ $aset->penanggung_jawab_id }}',
                      lokasiId: '{{ $aset->lokasi_id }}',
                      divisiId: '{{ $aset->divisi_id }}',
                      previewCode: '{{ $aset->kode_aset }}',
                      isChanged: false,
                      loading: false,
                      pjMap: {{ json_encode($pjList->pluck('divisi_id', 'id')) }},
                      async fetchPreview() {
                          this.loading = true;
                          try {
                              const res = await fetch('{{ route('aset.preview-mutasi', $aset->id) }}', {
                                  method: 'POST',
                                  headers: {
                                      'Content-Type': 'application/json',
                                      'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                  },
                                  body: JSON.stringify({
                                      penanggung_jawab_id: this.pjId,
                                      lokasi_id: this.lokasiId,
                                      divisi_id: this.divisiId
                                  })
                              });
                              const data = await res.json();
                              if (data && data.new_code) {
                                  this.previewCode = data.new_code;
                                  this.isChanged = data.changed;
                              }
                          } catch (e) {
                              console.error(e);
                          } finally {
                              this.loading = false;
                          }
                      },
                      onPjChanged(val) {
                          this.pjId = val;
                          if (this.pjMap[val] && '{{ $aset->sifat_barang }}' === 'D') {
                              this.divisiId = this.pjMap[val];
                          }
                          this.fetchPreview();
                      },
                      onLokasiChanged(val) {
                          this.lokasiId = val;
                          this.fetchPreview();
                      },
                      onDivisiChanged(val) {
                          this.divisiId = val;
                          this.fetchPreview();
                      }
                  }"
                  method="POST" action="{{ route('aset.mutasi', $aset->id) }}" class="space-y-3.5 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Sejak Tanggal <span class="text-rose-500">*</span></label>
                    <input type="date" name="sejak_tanggal" required value="{{ date('Y-m-d') }}"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Penanggung Jawab / Pemegang Baru <span class="text-rose-500">*</span></label>
                    <x-searchable-select name="penanggung_jawab_id"
                                         :items="$pjItems"
                                         model="pjId"
                                         change="onPjChanged(selectedId)"
                                         value="{{ $aset->penanggung_jawab_id }}"
                                         :required="true"
                                         placeholder="-- Cari / Pilih Penanggung Jawab --" />
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Lokasi Penempatan Baru <span class="text-rose-500">*</span></label>
                    <x-searchable-select name="lokasi_id"
                                         :items="$lokasiItems"
                                         model="lokasiId"
                                         change="onLokasiChanged(selectedId)"
                                         value="{{ $aset->lokasi_id }}"
                                         :required="true"
                                         placeholder="-- Cari / Pilih Lokasi --" />
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Divisi Pengampu Baru <span class="text-rose-500">*</span></label>
                    <x-searchable-select name="divisi_id"
                                         :items="$divisiItems"
                                         model="divisiId"
                                         change="onDivisiChanged(selectedId)"
                                         value="{{ $aset->divisi_id }}"
                                         :required="true"
                                         placeholder="-- Pilih Divisi Pengampu --" />
                </div>

                <!-- Live Preview Kode Aset -->
                <div class="p-3.5 rounded-2xl border transition-all"
                     :class="isChanged ? 'bg-emerald-50/80 border-emerald-300' : 'bg-slate-50 border-slate-200'">
                    <div class="flex items-center justify-between gap-2 mb-1.5">
                        <span class="text-[11px] font-bold text-slate-700 flex items-center gap-1.5">
                            <i class="ti ti-qrcode text-base text-emerald-600"></i>
                            Live Preview Kode Aset:
                        </span>
                        <span x-show="isChanged" class="px-2 py-0.5 rounded-md bg-emerald-600 text-white font-extrabold text-[10px] tracking-wider uppercase shadow-xs">
                            KODE BERUBAH
                        </span>
                        <span x-show="!isChanged" class="px-2 py-0.5 rounded-md bg-slate-200 text-slate-600 font-bold text-[10px] uppercase">
                            KODE TETAP
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <div class="font-mono text-sm font-extrabold tracking-wider"
                             :class="isChanged ? 'text-emerald-800' : 'text-slate-800'"
                             x-text="previewCode"></div>
                        <span x-show="loading" class="text-[10px] text-slate-400 italic">Memperbarui...</span>
                    </div>
                    <p x-show="isChanged" class="text-[10.5px] text-emerald-700 mt-1 leading-normal">
                        * Nomor urut asli (<span class="font-bold">#{{ str_pad($aset->nomor_urut ?: substr($aset->kode_aset, -2) ?: 1, 2, '0', STR_PAD_LEFT) }}</span>) tetap dipertahankan. Label QR fisik lama tetap dapat di-scan.
                    </p>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Jumlah Unit</label>
                    <input type="number" name="jumlah" min="1" value="{{ $aset->jumlah_unit }}"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Kondisi (%)</label>
                        <input type="number" name="kondisi_persen" min="0" max="100" value="100" placeholder="100"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Kelengkapan (%)</label>
                        <input type="number" name="kelengkapan_persen" min="0" max="100" value="100" placeholder="100"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Keterangan <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <textarea name="keterangan" rows="2" placeholder="Catatan perpindahan atau serah terima..."
                              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="modalOpen = false" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs hover:bg-slate-200">Batal</button>
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 text-white font-extrabold text-xs uppercase tracking-wider hover:from-cyan-500 hover:to-emerald-500 shadow-md shadow-emerald-600/20">
                        Simpan Riwayat
                    </button>
                </div>
            </form>

            <!-- 2. Form Agenda (Sesuai Amana.md - Dynamic Dropdown Waktu) -->
            <form x-show="modalType === 'agenda'" x-cloak x-data="{ tipeAgenda: 'tanggal_tertentu' }" method="POST" action="{{ route('aset.agenda.store', $aset->id) }}" class="space-y-3.5 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Tipe Agenda <span class="text-rose-500">*</span></label>
                    <select name="tipe_agenda" x-model="tipeAgenda" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        <option value="mingguan">Mingguan (Setiap Hari Tertentu)</option>
                        <option value="bulanan">Bulanan (Setiap Tanggal Tertentu)</option>
                        <option value="tahunan">Tahunan (Setiap Bulan & Tanggal Tertentu)</option>
                        <option value="tanggal_tertentu">Tanggal Tertentu (Sekali Tanggal Pasti)</option>
                    </select>
                </div>

                <!-- Opsi Mingguan: Pilih Hari -->
                <div x-show="tipeAgenda === 'mingguan'" class="space-y-1">
                    <label class="block font-semibold text-slate-700 mb-1">Pilih Hari <span class="text-rose-500">*</span></label>
                    <select name="hari" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        <option value="senin">Setiap Senin</option>
                        <option value="selasa">Setiap Selasa</option>
                        <option value="rabu">Setiap Rabu</option>
                        <option value="kamis">Setiap Kamis</option>
                        <option value="jumat">Setiap Jumat</option>
                        <option value="sabtu">Setiap Sabtu</option>
                        <option value="minggu">Setiap Minggu</option>
                    </select>
                </div>

                <!-- Opsi Bulanan: Pilih Tanggal 1-28 -->
                <div x-show="tipeAgenda === 'bulanan'" x-cloak class="space-y-1">
                    <label class="block font-semibold text-slate-700 mb-1">Setiap Tanggal (1 - 28) <span class="text-rose-500">*</span></label>
                    <input type="number" name="tanggal_hari" min="1" max="28" value="1"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <p class="text-[11px] text-slate-400">Pilih tanggal 1 s/d 28 agar jadwal berlaku seragam di setiap bulan.</p>
                </div>

                <!-- Opsi Tahunan: Tanggal & Bulan (DD/MM) dalam satu baris tanpa tahun -->
                <div x-show="tipeAgenda === 'tahunan'" x-cloak class="space-y-1.5">
                    <label class="block font-semibold text-slate-700 mb-1">Tanggal & Bulan Tahunan (Tanpa Tahun) <span class="text-rose-500">*</span></label>
                    <div class="grid grid-cols-12 gap-2">
                        <div class="col-span-4">
                            <select name="tanggal_hari" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-xs font-semibold">
                                @for($d = 1; $d <= 28; $d++)
                                    <option value="{{ $d }}" {{ $d === 1 ? 'selected' : '' }}>Tgl {{ $d }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-span-8">
                            <select name="bulan" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-xs font-semibold">
                                <option value="1">Januari</option>
                                <option value="2">Februari</option>
                                <option value="3">Maret</option>
                                <option value="4">April</option>
                                <option value="5">Mei</option>
                                <option value="6">Juni</option>
                                <option value="7">Juli</option>
                                <option value="8">Agustus</option>
                                <option value="9">September</option>
                                <option value="10">Oktober</option>
                                <option value="11">November</option>
                                <option value="12">Desember</option>
                            </select>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-400">Contoh: Tgl 17 Agustus (otomatis berulang di setiap tahun tanpa tahun tetap).</p>
                </div>

                <!-- Opsi Tanggal Tertentu: Date Picker -->
                <div x-show="tipeAgenda === 'tanggal_tertentu'" x-cloak class="space-y-1">
                    <label class="block font-semibold text-slate-700 mb-1">Tanggal Tertentu <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal" value="{{ date('Y-m-d') }}"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Nama Agenda / Aktivitas <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama_agenda" required placeholder="Contoh: Servis Rutin / Ganti Oli / Perpanjangan STNK"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Estimasi Biaya <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <div class="flex rounded-xl border border-slate-200 bg-slate-50 focus-within:bg-white focus-within:ring-2 focus-within:ring-emerald-500/20 focus-within:border-emerald-500 transition-all overflow-hidden"
                         x-data="{
                            valDisplay: '',
                            rawVal: 0,
                            format(e) {
                                let clean = e.target.value.replace(/\D/g, '');
                                this.rawVal = clean ? parseInt(clean, 10) : 0;
                                this.valDisplay = this.rawVal ? this.rawVal.toLocaleString('id-ID') : '';
                                e.target.value = this.valDisplay;
                            }
                         }">
                        <span class="inline-flex items-center px-3.5 text-xs font-bold text-slate-500 bg-slate-100/90 border-r border-slate-200 select-none">Rp</span>
                        <input type="text" inputmode="numeric" placeholder="500.000"
                               :value="valDisplay" @input="format($event)"
                               class="w-full px-3.5 py-2.5 text-xs bg-transparent border-0 focus:outline-none focus:ring-0 font-bold text-slate-800">
                        <input type="hidden" name="biaya_estimasi" :value="rawVal">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Keterangan Tambahan <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <textarea name="keterangan" rows="2" placeholder="Catatan kegiatan..."
                              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="modalOpen = false" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs hover:bg-slate-200">Batal</button>
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 text-white font-extrabold text-xs uppercase tracking-wider hover:from-cyan-500 hover:to-emerald-500 shadow-md shadow-emerald-600/20">
                        Jadwalkan Agenda
                    </button>
                </div>
            </form>

            <!-- 3. Form Keuangan / Pengeluaran Biaya -->
            <form x-show="modalType === 'keuangan'" x-cloak method="POST" action="{{ route('aset.keuangan.store', $aset->id) }}" class="space-y-3.5 text-xs">
                @csrf
                <input type="hidden" name="tipe" value="pengeluaran">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Tanggal Pengeluaran <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal" required value="{{ date('Y-m-d') }}"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Nominal Biaya <span class="text-rose-500">*</span></label>
                    <div class="flex rounded-xl border border-slate-200 bg-slate-50 focus-within:bg-white focus-within:ring-2 focus-within:ring-emerald-500/20 focus-within:border-emerald-500 transition-all overflow-hidden"
                         x-data="{
                            valDisplay: '',
                            rawVal: 0,
                            format(e) {
                                let clean = e.target.value.replace(/\D/g, '');
                                this.rawVal = clean ? parseInt(clean, 10) : 0;
                                this.valDisplay = this.rawVal ? this.rawVal.toLocaleString('id-ID') : '';
                                e.target.value = this.valDisplay;
                            }
                         }">
                        <span class="inline-flex items-center px-3.5 text-xs font-bold text-slate-500 bg-slate-100/90 border-r border-slate-200 select-none">Rp</span>
                        <input type="text" inputmode="numeric" required placeholder="250.000"
                               :value="valDisplay" @input="format($event)"
                               class="w-full px-3.5 py-2.5 text-xs bg-transparent border-0 focus:outline-none focus:ring-0 font-bold text-slate-800">
                        <input type="hidden" name="nominal" :value="rawVal">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Jenis / Kategori Biaya <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <input type="text" name="jenis_transaksi" placeholder="Contoh: Servis Rutin / Ganti Ban / Sparepart / Pajak"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Keterangan Biaya <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <textarea name="keterangan" rows="2" placeholder="Rincian nota pembayaran, perbaikan, atau keterangan pembelian..."
                              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="modalOpen = false" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs hover:bg-slate-200">Batal</button>
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 text-white font-extrabold text-xs uppercase tracking-wider hover:from-cyan-500 hover:to-emerald-500 shadow-md shadow-emerald-600/20">
                        Simpan Pengeluaran
                    </button>
                </div>
            </form>

            <!-- 4. Form Jurnal (Sesuai Amana.md) -->
            <form x-show="modalType === 'jurnal'" x-cloak method="POST" action="{{ route('aset.jurnal.store', $aset->id) }}" enctype="multipart/form-data" class="space-y-3.5 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Tanggal Kejadian <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal" required value="{{ date('Y-m-d') }}"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Kejadian / Catatan Harian <span class="text-rose-500">*</span></label>
                    <textarea name="kejadian" rows="3" required placeholder="Deskripsikan kejadian, perbaikan, atau kondisi khusus aset..."
                              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"></textarea>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Lampiran File <span class="text-slate-400 font-normal">(Opsional - Foto / Dokumen PDF)</span></label>
                    <input type="file" name="lampiran" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx"
                           class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                </div>
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="modalOpen = false" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs hover:bg-slate-200">Batal</button>
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 text-white font-extrabold text-xs uppercase tracking-wider hover:from-cyan-500 hover:to-emerald-500 shadow-md shadow-emerald-600/20">
                        Simpan Jurnal
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- ======================================================= -->
    <!-- LIGHTBOX MODAL POPUP KHUSUS GAMBAR                      -->
    <!-- ======================================================= -->
    <div x-show="imageModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="image-modal-title" role="dialog" aria-modal="true">
        
        <!-- Backdrop with Dark Blur -->
        <div x-show="imageModalOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="imageModalOpen = false"
             class="fixed inset-0 bg-slate-950/85 backdrop-blur-md transition-opacity"></div>

        <!-- Modal Container -->
        <div class="flex min-h-full items-center justify-center p-4 sm:p-6 text-center">
            <div x-show="imageModalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative transform overflow-hidden rounded-3xl bg-slate-900/95 border border-slate-800 text-left shadow-2xl transition-all max-w-4xl w-full flex flex-col p-2.5">
                
                <!-- Modal Top Header & Actions -->
                <div class="px-4 py-3 flex items-center justify-between border-b border-slate-800/80 bg-slate-900/60 rounded-2xl mb-2">
                    <div class="flex items-center gap-2 truncate mr-3">
                        <i class="ti ti-photo text-emerald-400 text-lg flex-shrink-0"></i>
                        <span class="text-xs sm:text-sm font-extrabold text-white truncate" x-text="previewImageTitle"></span>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <!-- Buka di Tab Baru -->
                        <a :href="previewImageUrl" target="_blank"
                           class="px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold inline-flex items-center gap-1.5 transition-colors border border-slate-700"
                           title="Buka Gambar Asli">
                            <i class="ti ti-external-link text-sm"></i>
                            <span class="hidden sm:inline">Tab Baru</span>
                        </a>
                        <!-- Unduh Foto -->
                        <a :href="previewImageUrl" download
                           class="px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold inline-flex items-center gap-1.5 transition-colors border border-slate-700"
                           title="Unduh Gambar">
                            <i class="ti ti-download text-sm"></i>
                            <span class="hidden sm:inline">Unduh</span>
                        </a>
                        <!-- Tutup X -->
                        <button type="button" @click="imageModalOpen = false"
                                class="p-1.5 rounded-xl bg-slate-800 hover:bg-rose-500/20 hover:text-rose-400 text-slate-400 border border-slate-700 transition-colors"
                                title="Tutup (Esc)">
                            <i class="ti ti-x text-base"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Image Display -->
                <div class="relative flex items-center justify-center p-2 sm:p-4 min-h-[250px] max-h-[78vh] overflow-hidden">
                    <img :src="previewImageUrl" :alt="previewImageTitle"
                         class="max-h-[74vh] max-w-full object-contain rounded-2xl shadow-2xl border border-slate-800">
                </div>

                <!-- Footer Info -->
                <div class="px-4 py-2 text-center text-[11px] text-slate-500 border-t border-slate-800/80 bg-slate-900/60 rounded-2xl mt-2 flex items-center justify-between">
                    <span>Tekan <kbd class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 font-mono text-[10px] border border-slate-700">ESC</kbd> atau klik di luar untuk menutup</span>
                    <span class="text-emerald-400 font-medium font-mono text-[10px]">AMANA Visual Portal</span>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
