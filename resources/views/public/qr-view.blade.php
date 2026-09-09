<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Aset - {{ $aset->nama_aset }} ({{ $aset->kode_aset }})</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="min-h-full bg-slate-100 flex items-center justify-center p-3 sm:p-6"
      x-data="{
          imageModalOpen: false,
          previewImageUrl: '{{ $aset->foto_utama ? asset('storage/' . $aset->foto_utama) : '' }}',
          previewImageTitle: '{{ $aset->nama_aset }} ({{ $aset->kode_aset }})',
          activeAccordion: null,
          toggleAccordion(name) {
              this.activeAccordion = this.activeAccordion === name ? null : name;
          }
      }"
      @keydown.escape.window="imageModalOpen = false">

    <!-- Container Card (Mobile First) -->
    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl border border-slate-200/90 overflow-hidden my-auto">
        
        <!-- Header Branding Banner -->
        <div class="bg-gradient-to-r from-cyan-600 via-emerald-600 to-emerald-700 p-5 text-white text-center relative overflow-hidden">
            <div class="absolute -right-8 -top-8 w-28 h-28 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
            <div class="absolute -left-8 -bottom-8 w-28 h-28 bg-white/10 rounded-full blur-xl pointer-events-none"></div>

            <div class="relative z-10">
                <div class="w-14 h-14 mx-auto mb-2.5 bg-white p-2 rounded-2xl shadow-md flex items-center justify-center">
                    <img src="{{ asset('logo-icon.png') }}" alt="AMANA" class="w-full h-full object-contain">
                </div>
                <h1 class="text-sm font-extrabold tracking-wide uppercase">Aset Manajemen Al Azhar</h1>
                <p class="text-[11px] text-emerald-100 font-medium flex items-center justify-center gap-1 mt-0.5">
                    <i class="ti ti-shield-check text-xs"></i> Portal Verifikasi QR Resmi
                </p>
            </div>
        </div>

        @if(!empty($isMutasiRedirect))
            <div class="mx-5 mt-4 p-3 rounded-2xl bg-amber-50 border border-amber-200 text-amber-950 text-xs shadow-xs">
                <div class="flex items-start gap-2.5">
                    <i class="ti ti-alert-circle text-amber-600 text-base flex-shrink-0 mt-0.5"></i>
                    <div class="space-y-0.5">
                        <p class="font-extrabold text-[11px] text-amber-950">Aset Telah Mengalami Mutasi</p>
                        <p class="text-[10.5px] text-amber-800 leading-relaxed">
                            Label QR fisik yang dipindai menggunakan kode lama (<span class="font-mono font-bold">{{ $oldScannedCode }}</span>). Aset ini kini resmi menggunakan kode <span class="font-mono font-bold text-emerald-800 bg-emerald-100/80 px-1 py-0.2 rounded border border-emerald-200">{{ $aset->kode_aset }}</span>.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Main Body Details -->
        <div class="p-5 space-y-4">

            <!-- 1. Header Identitas Aset -->
            <div class="text-center pb-4 border-b border-slate-100">
                @if($config['show_kode_aset'])
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-mono font-black bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-xs mb-1.5">
                        {{ $aset->kode_aset }}
                    </span>
                @endif

                @if($config['show_nama_aset'])
                    <h2 class="text-base font-extrabold text-slate-900 leading-snug">
                        {{ $aset->nama_aset }}
                    </h2>
                @endif

                @if($config['show_status_aset'])
                    <div class="mt-2 flex items-center justify-center gap-2">
                        @if($aset->status === 'aktif')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                ASET AKTIF ({{ strtoupper($aset->jenis) }})
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                <i class="ti ti-ban text-xs"></i>
                                NON-AKTIF / PURNA PAKAI
                            </span>
                        @endif
                    </div>
                @endif

                <!-- Alert Khusus Jika Non-Aktif -->
                @if($aset->status === 'non_aktif' && ($config['show_nonaktif_tanggal'] || $config['show_nonaktif_sebab'] || $config['show_nonaktif_keterangan']))
                    <div class="mt-3 p-3 rounded-2xl bg-rose-50 border border-rose-200 text-left text-xs space-y-1">
                        <p class="font-bold text-rose-900 flex items-center gap-1.5 text-[11px]">
                            <i class="ti ti-alert-triangle text-rose-600"></i> Informasi Purna Pakai:
                        </p>
                        @if($config['show_nonaktif_tanggal'])
                            <div class="flex justify-between text-[11px] text-rose-800">
                                <span>Tanggal Non-Aktif:</span>
                                <span class="font-semibold">{{ $aset->updated_at ? $aset->updated_at->format('d/m/Y') : '-' }}</span>
                            </div>
                        @endif
                        @if($config['show_nonaktif_sebab'])
                            <div class="flex justify-between text-[11px] text-rose-800">
                                <span>Sebab:</span>
                                <span class="font-semibold">{{ $aset->nonaktif_sebab ?? 'Purna Manfaat / Rusak' }}</span>
                            </div>
                        @endif
                        @if($config['show_nonaktif_keterangan'] && $aset->nonaktif_keterangan)
                            <div class="pt-1 text-[10px] text-rose-700 border-t border-rose-200/60">
                                <span class="block font-medium">Catatan: {{ $aset->nonaktif_keterangan }}</span>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Foto Utama Aset -->
                @if($config['show_foto'] && $aset->foto_utama)
                    <div class="mt-3 relative group cursor-pointer overflow-hidden rounded-2xl border border-slate-200 shadow-xs max-h-48"
                         @click="imageModalOpen = true">
                        <img src="{{ asset('storage/' . $aset->foto_utama) }}" alt="{{ $aset->nama_aset }}"
                             class="w-full h-44 object-cover group-hover:scale-105 transition-transform duration-300">
                        <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                            <span class="px-3 py-1.5 rounded-xl bg-white/20 backdrop-blur-md border border-white/30 text-xs font-bold flex items-center gap-1.5 shadow-sm">
                                <i class="ti ti-zoom-in text-base"></i> Klik untuk Perbesar
                            </span>
                        </div>
                    </div>
                @endif
            </div>

            <!-- 2. Tabel Rincian Data Utama -->
            <div class="space-y-2 text-xs">
                @if($config['show_kode_sistem'])
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400">Kode Sistem</span>
                        <span class="font-bold text-slate-800 font-mono">#{{ $aset->id }}</span>
                    </div>
                @endif

                @if($config['show_kategori'])
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400">Kategori</span>
                        <span class="font-semibold text-slate-800">{{ $aset->kategori->nama_kategori ?? '-' }}</span>
                    </div>
                @endif

                @if($config['show_merk'])
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400">Merk</span>
                        <span class="font-semibold text-slate-800">{{ $aset->merk->nama_merk ?? '-' }}</span>
                    </div>
                @endif

                @if($config['show_tipe'] && $aset->tipe_model)
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400">Tipe / Model</span>
                        <span class="font-semibold text-slate-800">{{ $aset->tipe_model }}</span>
                    </div>
                @endif

                @if($config['show_produsen'] && $aset->produsen)
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400">Produsen</span>
                        <span class="font-semibold text-slate-800">{{ $aset->produsen }}</span>
                    </div>
                @endif

                @if($config['show_no_seri'] && $aset->no_seri)
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400">No. Seri / SN</span>
                        <span class="font-semibold text-slate-800 font-mono">{{ $aset->no_seri }}</span>
                    </div>
                @endif

                @if($config['show_tahun_produksi'] && $aset->tahun_produksi)
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400">Tahun Produksi</span>
                        <span class="font-semibold text-slate-800">{{ $aset->tahun_produksi }}</span>
                    </div>
                @endif

                <!-- Penanggung Jawab & Lokasi Terakhir -->
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-400">Lokasi Penempatan</span>
                    <span class="font-semibold text-slate-800 text-right">{{ $aset->lokasi->nama_lokasi ?? '-' }}</span>
                </div>

                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-400">Penanggung Jawab</span>
                    <span class="font-semibold text-slate-800 text-right">{{ $aset->penanggungJawab->nama ?? '-' }}</span>
                </div>

                @if($config['show_deskripsi'] && $aset->deskripsi)
                    <div class="py-1.5 border-b border-slate-50">
                        <span class="text-slate-400 block mb-0.5">Deskripsi Fisik</span>
                        <p class="font-medium text-slate-700 leading-relaxed bg-slate-50 p-2 rounded-xl">{{ $aset->deskripsi }}</p>
                    </div>
                @endif

                @if($config['show_keterangan_tambahan'] && $aset->keterangan_tambahan)
                    <div class="py-1.5 border-b border-slate-50">
                        <span class="text-slate-400 block mb-0.5">Keterangan Tambahan</span>
                        <p class="font-medium text-slate-700 leading-relaxed bg-slate-50 p-2 rounded-xl">{{ $aset->keterangan_tambahan }}</p>
                    </div>
                @endif
            </div>

            <!-- 3. Card Data Pembelian -->
            @if($config['show_tanggal_pembelian'] || $config['show_toko_distributor'] || $config['show_no_invoice'] || $config['show_jumlah_unit'] || $config['show_harga_satuan'] || $config['show_harga_total'])
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                    <p class="font-bold text-slate-900 flex items-center gap-1.5">
                        <i class="ti ti-receipt text-cyan-600"></i> Informasi Pembelian & Nilai
                    </p>
                    <div class="space-y-1.5 text-slate-600 pt-1">
                        @if($config['show_tanggal_pembelian'] && $aset->tanggal_pembelian)
                            <div class="flex justify-between">
                                <span class="text-slate-400">Tanggal Beli:</span>
                                <span class="font-semibold text-slate-800">{{ \Carbon\Carbon::parse($aset->tanggal_pembelian)->format('d/m/Y') }}</span>
                            </div>
                        @endif
                        @if($config['show_toko_distributor'] && $aset->toko_distributor)
                            <div class="flex justify-between">
                                <span class="text-slate-400">Toko / Distributor:</span>
                                <span class="font-semibold text-slate-800">{{ $aset->toko_distributor }}</span>
                            </div>
                        @endif
                        @if($config['show_no_invoice'] && $aset->no_invoice)
                            <div class="flex justify-between">
                                <span class="text-slate-400">No. Invoice:</span>
                                <span class="font-semibold text-slate-800 font-mono">{{ $aset->no_invoice }}</span>
                            </div>
                        @endif
                        @if($config['show_jumlah_unit'])
                            <div class="flex justify-between">
                                <span class="text-slate-400">Jumlah Unit:</span>
                                <span class="font-semibold text-slate-800">{{ $aset->jumlah_unit }} Unit</span>
                            </div>
                        @endif
                        @if($config['show_harga_satuan'])
                            <div class="flex justify-between">
                                <span class="text-slate-400">Harga Satuan:</span>
                                <span class="font-semibold text-slate-800">Rp {{ number_format($aset->harga_satuan, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        @if($config['show_harga_total'])
                            <div class="flex justify-between pt-1.5 border-t border-slate-200 font-bold text-slate-900">
                                <span>Harga Total Perolehan:</span>
                                <span class="text-emerald-700">Rp {{ number_format($aset->harga_total, 0, ',', '.') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- 4. Card Umur & Penyusutan -->
            @if($config['show_umur_ekonomi'] || $config['show_penyusutan_per_bulan'] || $config['show_usia_aset'] || $config['show_nilai_sekarang'])
                <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200 text-xs space-y-2">
                    <p class="font-bold text-emerald-950 flex items-center gap-1.5">
                        <i class="ti ti-chart-line text-emerald-600"></i> Umur & Estimasi Penyusutan
                    </p>
                    <div class="space-y-1.5 text-slate-600 pt-1">
                        @if($config['show_umur_ekonomi'])
                            <div class="flex justify-between">
                                <span class="text-slate-500">Umur Ekonomis:</span>
                                <span class="font-semibold text-slate-900">{{ $aset->umur_ekonomis_tahun }} Tahun</span>
                            </div>
                        @endif
                        @if($config['show_usia_aset'] && $usiaAset)
                            <div class="flex justify-between">
                                <span class="text-slate-500">Usia Aset Berjalan:</span>
                                <span class="font-semibold text-slate-900">{{ $usiaAset }}</span>
                            </div>
                        @endif
                        @if($config['show_penyusutan_per_bulan'])
                            <div class="flex justify-between">
                                <span class="text-slate-500">Penyusutan / Bulan:</span>
                                <span class="font-semibold text-slate-900">Rp {{ number_format($aset->penyusutan_per_bulan, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        @if($config['show_nilai_sekarang'] && $nilaiBuku !== null)
                            <div class="flex justify-between pt-1.5 border-t border-emerald-200 font-bold text-emerald-950">
                                <span>Nilai Sekarang (Buku):</span>
                                <span class="text-emerald-700">Rp {{ number_format($nilaiBuku, 0, ',', '.') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- 5. Sub-Modul Accordions -->
            <div class="space-y-2 pt-2">
                
                <!-- A. Riwayat Perpindahan Accordion -->
                @if($config['riwayat_mode'] !== 'tidak_tampil')
                    @php
                        $riwayatItems = $config['riwayat_mode'] === 'terakhir'
                            ? ($aset->riwayat->first() ? collect([$aset->riwayat->first()]) : collect())
                            : $aset->riwayat;
                    @endphp

                    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-xs">
                        <button type="button" @click="toggleAccordion('riwayat')"
                                class="w-full p-3.5 flex items-center justify-between text-left hover:bg-slate-50 transition-colors">
                            <span class="text-xs font-bold text-slate-800 flex items-center gap-2">
                                <i class="ti ti-history text-cyan-600 text-sm"></i>
                                Riwayat Perpindahan Aset
                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 font-normal">
                                    {{ $config['riwayat_mode'] === 'terakhir' ? 'Posisi Terakhir' : $riwayatItems->count() . ' Catatan' }}
                                </span>
                            </span>
                            <i class="ti ti-chevron-down text-slate-400 transition-transform duration-200"
                               :class="activeAccordion === 'riwayat' ? 'rotate-180 text-emerald-600' : ''"></i>
                        </button>

                        <div x-show="activeAccordion === 'riwayat'" x-collapse class="p-3.5 pt-0 border-t border-slate-100 text-xs space-y-3">
                            @forelse($riwayatItems as $rw)
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1 text-[11px]">
                                    @if($config['show_riwayat_tanggal'])
                                        <div class="flex items-center justify-between text-slate-400 font-semibold text-[10px]">
                                            <span>Mulai Berlaku:</span>
                                            <span class="text-slate-700">{{ \Carbon\Carbon::parse($rw->sejak_tanggal)->format('d/m/Y') }}</span>
                                        </div>
                                    @endif
                                    @if($config['show_riwayat_penanggung_jawab'])
                                        <div class="flex justify-between">
                                            <span class="text-slate-400">Penanggung Jawab:</span>
                                            <span class="font-bold text-slate-800">{{ $rw->penanggungJawab->nama ?? '-' }}</span>
                                        </div>
                                    @endif
                                    @if($config['show_riwayat_lokasi'])
                                        <div class="flex justify-between">
                                            <span class="text-slate-400">Lokasi:</span>
                                            <span class="font-semibold text-slate-800">{{ $rw->lokasi->nama_lokasi ?? '-' }}</span>
                                        </div>
                                    @endif
                                    @if($rw->divisi)
                                        <div class="flex justify-between">
                                            <span class="text-slate-400">Divisi:</span>
                                            <span class="font-semibold text-slate-800">{{ $rw->divisi->nama_divisi }}</span>
                                        </div>
                                    @endif
                                    @if($rw->kode_aset_sebelumnya && $rw->kode_aset_baru && $rw->kode_aset_sebelumnya !== $rw->kode_aset_baru)
                                        <div class="mt-1 p-1.5 rounded-lg bg-emerald-50 border border-emerald-200 flex items-center justify-between font-mono text-[10px]">
                                            <span class="text-slate-400 line-through">{{ $rw->kode_aset_sebelumnya }}</span>
                                            <i class="ti ti-arrow-right text-emerald-600 text-xs"></i>
                                            <span class="text-emerald-800 font-bold">{{ $rw->kode_aset_baru }}</span>
                                        </div>
                                    @endif
                                    <div class="flex items-center gap-3 pt-1 text-[10px]">
                                        @if($config['show_riwayat_kondisi'])
                                            <span class="text-slate-500">Kondisi: <strong class="text-emerald-700">{{ $rw->kondisi_persen ?? 100 }}%</strong></span>
                                        @endif
                                        @if($config['show_riwayat_kelengkapan'])
                                            <span class="text-slate-500">Kelengkapan: <strong class="text-cyan-700">{{ $rw->kelengkapan_persen ?? 100 }}%</strong></span>
                                        @endif
                                    </div>
                                    @if($config['show_riwayat_keterangan'] && $rw->keterangan)
                                        <p class="text-[10px] text-slate-500 pt-1 border-t border-slate-200/50 italic">{{ $rw->keterangan }}</p>
                                    @endif
                                </div>
                            @empty
                                <p class="text-center py-2 text-slate-400 text-[11px]">Belum ada riwayat perpindahan tercatat.</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                <!-- B. Agenda Accordion -->
                @if($config['show_agenda'])
                    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-xs">
                        <button type="button" @click="toggleAccordion('agenda')"
                                class="w-full p-3.5 flex items-center justify-between text-left hover:bg-slate-50 transition-colors">
                            <span class="text-xs font-bold text-slate-800 flex items-center gap-2">
                                <i class="ti ti-calendar text-emerald-600 text-sm"></i>
                                Agenda & Jadwal Perawatan
                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 font-normal">
                                    {{ $aset->agenda->count() }}
                                </span>
                            </span>
                            <i class="ti ti-chevron-down text-slate-400 transition-transform duration-200"
                               :class="activeAccordion === 'agenda' ? 'rotate-180 text-emerald-600' : ''"></i>
                        </button>

                        <div x-show="activeAccordion === 'agenda'" x-collapse class="p-3.5 pt-0 border-t border-slate-100 text-xs space-y-2">
                            @forelse($aset->agenda as $ag)
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 text-[11px] space-y-0.5">
                                    <div class="flex items-center justify-between">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase bg-emerald-100 text-emerald-800">
                                            {{ $ag->tipe_agenda ?? 'Jadwal' }}
                                        </span>
                                        <span class="text-[10px] text-slate-500 font-medium">{{ $ag->jadwal_teks ?? '' }}</span>
                                    </div>
                                    <p class="font-bold text-slate-800 pt-1">{{ $ag->nama_agenda }}</p>
                                    @if($ag->keterangan)
                                        <p class="text-[10px] text-slate-500">{{ $ag->keterangan }}</p>
                                    @endif
                                </div>
                            @empty
                                <p class="text-center py-2 text-slate-400 text-[11px]">Tidak ada agenda aktif saat ini.</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                <!-- C. Keuangan Accordion -->
                @if($config['show_keuangan'])
                    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-xs">
                        <button type="button" @click="toggleAccordion('keuangan')"
                                class="w-full p-3.5 flex items-center justify-between text-left hover:bg-slate-50 transition-colors">
                            <span class="text-xs font-bold text-slate-800 flex items-center gap-2">
                                <i class="ti ti-cash text-amber-600 text-sm"></i>
                                Riwayat Pengeluaran & Biaya Aset
                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 font-normal">
                                    {{ $aset->keuangan->count() }}
                                </span>
                            </span>
                            <i class="ti ti-chevron-down text-slate-400 transition-transform duration-200"
                               :class="activeAccordion === 'keuangan' ? 'rotate-180 text-emerald-600' : ''"></i>
                        </button>

                        <div x-show="activeAccordion === 'keuangan'" x-collapse class="p-3.5 pt-0 border-t border-slate-100 text-xs space-y-2">
                            @forelse($aset->keuangan as $keu)
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between text-[11px]">
                                    <div>
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-rose-100 text-rose-800">
                                            Pengeluaran
                                        </span>
                                        <p class="font-medium text-slate-800 mt-1">{{ $keu->keterangan ?? $keu->jenis_transaksi ?? 'Biaya perawatan aset' }}</p>
                                        <span class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($keu->tanggal)->format('d/m/Y') }}</span>
                                    </div>
                                    <span class="font-bold text-rose-700">
                                        - Rp {{ number_format($keu->nominal, 0, ',', '.') }}
                                    </span>
                                </div>
                            @empty
                                <p class="text-center py-2 text-slate-400 text-[11px]">Belum ada catatan pengeluaran biaya aset.</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                <!-- D. Jurnal Accordion -->
                @if($config['show_jurnal'])
                    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-xs">
                        <button type="button" @click="toggleAccordion('jurnal')"
                                class="w-full p-3.5 flex items-center justify-between text-left hover:bg-slate-50 transition-colors">
                            <span class="text-xs font-bold text-slate-800 flex items-center gap-2">
                                <i class="ti ti-notes text-indigo-600 text-sm"></i>
                                Jurnal Catatan & Insiden
                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 font-normal">
                                    {{ $aset->jurnal->count() }}
                                </span>
                            </span>
                            <i class="ti ti-chevron-down text-slate-400 transition-transform duration-200"
                               :class="activeAccordion === 'jurnal' ? 'rotate-180 text-emerald-600' : ''"></i>
                        </button>

                        <div x-show="activeAccordion === 'jurnal'" x-collapse class="p-3.5 pt-0 border-t border-slate-100 text-xs space-y-2">
                            @forelse($aset->jurnal as $jur)
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 text-[11px] space-y-1">
                                    <div class="flex items-center justify-between text-[10px] text-slate-400 font-semibold">
                                        <span>Tanggal Kejadian:</span>
                                        <span>{{ \Carbon\Carbon::parse($jur->tanggal)->format('d/m/Y') }}</span>
                                    </div>
                                    <p class="font-medium text-slate-800">{{ $jur->kejadian }}</p>
                                    @if($jur->lampiran)
                                        <a href="{{ Storage::disk('public')->url($jur->lampiran) }}" target="_blank"
                                           class="inline-flex items-center gap-1 text-[10px] text-indigo-600 hover:underline pt-0.5">
                                            <i class="ti ti-paperclip"></i> Lihat Lampiran Jurnal
                                        </a>
                                    @endif
                                </div>
                            @empty
                                <p class="text-center py-2 text-slate-400 text-[11px]">Belum ada catatan jurnal.</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                <!-- E. Lampiran File Accordion -->
                @if($config['show_lampiran'] && $aset->lampiran && $aset->lampiran->count() > 0)
                    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-xs">
                        <button type="button" @click="toggleAccordion('lampiran')"
                                class="w-full p-3.5 flex items-center justify-between text-left hover:bg-slate-50 transition-colors">
                            <span class="text-xs font-bold text-slate-800 flex items-center gap-2">
                                <i class="ti ti-paperclip text-rose-600 text-sm"></i>
                                Dokumen & File Lampiran
                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 font-normal">
                                    {{ $aset->lampiran->count() }} File
                                </span>
                            </span>
                            <i class="ti ti-chevron-down text-slate-400 transition-transform duration-200"
                               :class="activeAccordion === 'lampiran' ? 'rotate-180 text-emerald-600' : ''"></i>
                        </button>

                        <div x-show="activeAccordion === 'lampiran'" x-collapse class="p-3.5 pt-0 border-t border-slate-100 text-xs space-y-2">
                            @foreach($aset->lampiran as $lamp)
                                <a href="{{ asset('storage/' . $lamp->file_path) }}" target="_blank"
                                   class="p-2.5 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 flex items-center justify-between text-[11px] text-slate-700 transition-colors">
                                    <div class="flex items-center gap-2 truncate">
                                        <i class="ti ti-file-description text-rose-500 text-base flex-shrink-0"></i>
                                        <span class="font-medium truncate">{{ $lamp->file_name ?? 'Dokumen Pendukung' }}</span>
                                    </div>
                                    <i class="ti ti-download text-slate-400"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>

            <!-- Footer Verification Stamp -->
            <div class="pt-4 text-center border-t border-slate-100 space-y-1">
                <p class="text-[11px] font-bold text-slate-500 flex items-center justify-center gap-1">
                    <i class="ti ti-badge-check text-emerald-600"></i> Terverifikasi Resmi
                </p>
                <p class="text-[9px] text-slate-400">
                    Sistem Manajemen Aset & Inventaris © {{ date('Y') }} Lembaga Amil Zakat Al Azhar
                </p>
            </div>

        </div>
    </div>

    <!-- Lightbox Modal Portal Publik -->
    <div x-show="imageModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        
        <div x-show="imageModalOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="imageModalOpen = false"
             class="fixed inset-0 bg-slate-950/85 backdrop-blur-md transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center">
            <div x-show="imageModalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative transform overflow-hidden rounded-3xl bg-slate-900/95 border border-slate-800 text-left shadow-2xl transition-all max-w-2xl w-full p-2.5">
                
                <div class="px-4 py-3 flex items-center justify-between border-b border-slate-800 bg-slate-900/60 rounded-2xl mb-2">
                    <span class="text-xs font-extrabold text-white truncate" x-text="previewImageTitle"></span>
                    <div class="flex items-center gap-2">
                        <a :href="previewImageUrl" target="_blank"
                           class="px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold inline-flex items-center gap-1.5 transition-colors border border-slate-700">
                            <i class="ti ti-external-link text-sm"></i>
                            <span>Tab Baru</span>
                        </a>
                        <button type="button" @click="imageModalOpen = false"
                                class="p-1.5 rounded-xl bg-slate-800 hover:bg-rose-500/20 hover:text-rose-400 text-slate-400 border border-slate-700 transition-colors">
                            <i class="ti ti-x text-base"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-center p-2 min-h-[200px] max-h-[75vh] overflow-hidden">
                    <img :src="previewImageUrl" :alt="previewImageTitle"
                         class="max-h-[70vh] max-w-full object-contain rounded-2xl shadow-2xl border border-slate-800">
                </div>
            </div>
        </div>
    </div>

</body>
</html>
