@extends('layouts.app')

@section('title', $config['title'])
@section('header-title', $config['title'] . ' AMANA')

@section('content')
<div class="space-y-6 pb-16"
     x-data="{
         filterOpen: @js(request()->anyFilled(['search', 'kategori_id', 'lokasi_id', 'penanggung_jawab_id', 'tgl_dari', 'tgl_sampai'])),
         selectedIds: [],
         pageIds: @js($asetList->pluck('id')->map(fn($id) => (int)$id)->toArray()),

         get isAllSelected() {
             if (this.pageIds.length === 0) return false;
             return this.pageIds.every(id => this.selectedIds.includes(id));
         },

         toggleAll() {
             if (this.isAllSelected) {
                 this.selectedIds = this.selectedIds.filter(id => !this.pageIds.includes(id));
             } else {
                 const newIds = this.pageIds.filter(id => !this.selectedIds.includes(id));
                 this.selectedIds = [...this.selectedIds, ...newIds];
             }
         },

         toggleItem(id) {
             const num = parseInt(id);
             if (this.selectedIds.includes(num)) {
                 this.selectedIds = this.selectedIds.filter(i => i !== num);
             } else {
                 this.selectedIds.push(num);
             }
         },

         goToBulkPrint() {
             if (this.selectedIds.length === 0) return;
             window.location.href = '{{ route('aset.qr.print') }}?selected_ids=' + this.selectedIds.join(',');
         }
     }">

    <!-- Header Actions & Title -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">{{ $config['title'] }}</h2>
            <p class="text-xs text-slate-500">{{ $config['subtitle'] }}</p>
        </div>

        <div class="flex items-center flex-wrap gap-2.5">
            <!-- Tombol Toggle Hide/Show Filter -->
            <button @click="filterOpen = !filterOpen"
                    type="button"
                    class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs font-semibold border transition-all"
                    :class="filterOpen ? 'bg-slate-800 text-white border-slate-800 shadow-sm' : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200 shadow-sm hover:border-slate-300'">
                <i class="ti ti-adjustments-horizontal mr-1.5 text-sm" :class="filterOpen ? 'text-white' : 'text-slate-500'"></i>
                <span x-text="filterOpen ? 'Tutup Filter' : 'Filter & Pencarian'">Filter & Pencarian</span>
                @if(request()->anyFilled(['search', 'kategori_id', 'lokasi_id', 'penanggung_jawab_id', 'tgl_dari', 'tgl_sampai']))
                    <span class="ml-2 px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-emerald-500 text-white">Aktif</span>
                @endif
            </button>

            <a href="{{ route('aset.import.index') }}"
               class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs border border-slate-200 shadow-sm transition-all hover:border-slate-300">
                <i class="ti ti-file-import mr-1.5 text-cyan-600 text-sm"></i> Impor Excel
            </a>

            <a href="{{ route($config['export_route'], request()->query()) }}"
               class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs border border-slate-200 shadow-sm transition-all hover:border-slate-300">
                <i class="ti ti-file-spreadsheet mr-1.5 text-emerald-600 text-sm"></i> Ekspor Excel
            </a>

            @if(auth()->check() && auth()->user()->role === 'super_admin')
                <a href="{{ route('aset.create') }}"
                   class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-semibold text-xs shadow-md shadow-emerald-600/15 transition-all">
                    <i class="ti ti-plus mr-1.5 text-sm"></i> Tambah Aset Baru
                </a>
            @endif
        </div>
    </div>

    <!-- Ringkasan Statistik Cepat (Filtered Summary) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Aset Terfilter</p>
                <h3 class="text-xl font-extrabold text-slate-900 mt-1">{{ number_format($summary['total_aset'], 0, ',', '.') }} <span class="text-xs font-semibold text-slate-500">Aset</span></h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i class="ti ti-box text-xl"></i>
            </div>
        </div>

        <div class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Unit Fisik</p>
                <h3 class="text-xl font-extrabold text-slate-900 mt-1">{{ number_format($summary['total_unit'], 0, ',', '.') }} <span class="text-xs font-semibold text-slate-500">Unit</span></h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <i class="ti ti-packages text-xl"></i>
            </div>
        </div>

        <div class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Akumulasi Nilai</p>
                <h3 class="text-xl font-extrabold text-slate-900 mt-1">Rp {{ number_format($summary['total_nilai'], 0, ',', '.') }}</h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <i class="ti ti-coin text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div x-show="filterOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         x-cloak
         class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm">
        <form method="GET" action="{{ route($config['route']) }}" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                <!-- Search -->
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Pencarian</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i class="ti ti-search text-sm"></i>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Cari nama, kode aset, merk, no seri..."
                               class="w-full pl-9 pr-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                    </div>
                </div>

                <!-- Kategori -->
                @php
                    $kategoriFilterItems = $kategoriList->map(fn($kat) => [
                        'id' => (string) $kat->id,
                        'title' => $kat->nama_kategori,
                        'code' => $kat->kode_kategori,
                    ])->toArray();
                @endphp
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Kategori</label>
                    <x-searchable-select
                        name="kategori_id"
                        :items="$kategoriFilterItems"
                        :value="request('kategori_id', '')"
                        placeholder="-- Semua Kategori --"
                    />
                </div>

                <!-- Lokasi -->
                @php
                    $lokasiFilterItems = $lokasiList->map(fn($lok) => [
                        'id' => (string) $lok->id,
                        'title' => $lok->nama_lokasi,
                        'code' => $lok->kode_lokasi,
                        'subtitle' => $lok->gedung ?? null,
                    ])->toArray();
                @endphp
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Lokasi</label>
                    <x-searchable-select
                        name="lokasi_id"
                        :items="$lokasiFilterItems"
                        :value="request('lokasi_id', '')"
                        placeholder="-- Semua Lokasi --"
                    />
                </div>

                <!-- Penanggung Jawab -->
                @php
                    $pjFilterItems = $penanggungJawabList->map(fn($pj) => [
                        'id' => (string) $pj->id,
                        'title' => $pj->nama,
                        'code' => $pj->kode_pic ? str_pad($pj->kode_pic, 3, '0', STR_PAD_LEFT) : null,
                        'subtitle' => $pj->jabatan ?? ($pj->divisi?->nama_divisi ?? null),
                    ])->toArray();
                @endphp
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Penanggung Jawab</label>
                    <x-searchable-select
                        name="penanggung_jawab_id"
                        :items="$pjFilterItems"
                        :value="request('penanggung_jawab_id', '')"
                        placeholder="-- Semua Penanggung Jawab --"
                    />
                </div>

                <!-- Tanggal Beli Dari -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Tanggal Beli Dari</label>
                    <input type="date" name="tgl_dari" value="{{ request('tgl_dari') }}"
                           class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                </div>

                <!-- Tanggal Beli Sampai -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Tanggal Beli Sampai</label>
                    <input type="date" name="tgl_sampai" value="{{ request('tgl_sampai') }}"
                           class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                </div>

                <!-- Tombol Aksi Filter -->
                <div class="flex items-end space-x-2">
                    <button type="submit" class="flex-1 py-2 px-3 rounded-xl bg-slate-800 text-white font-semibold text-xs hover:bg-slate-900 transition-colors flex items-center justify-center">
                        <i class="ti ti-filter mr-1.5 text-xs"></i> Terapkan
                    </button>
                    <a href="{{ route($config['route']) }}" class="py-2 px-3 rounded-xl bg-slate-100 text-slate-700 font-semibold text-xs hover:bg-slate-200 transition-colors flex items-center justify-center" title="Reset Filter">
                        <i class="ti ti-rotate-clockwise mr-1 text-xs"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-3 w-10 text-center">
                            <input type="checkbox"
                                   :checked="isAllSelected"
                                   @change="toggleAll()"
                                   class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 cursor-pointer">
                        </th>
                        <th class="py-3.5 px-4">Kode Aset</th>
                        <th class="py-3.5 px-4">Nama Aset & Merk</th>
                        <th class="py-3.5 px-4">Kategori</th>
                        <th class="py-3.5 px-4">Lokasi</th>
                        <th class="py-3.5 px-4">Penanggung Jawab</th>
                        <th class="py-3.5 px-4">Tgl Pembelian</th>
                        @if($config['show_status_column'])
                            <th class="py-3.5 px-4">Status</th>
                        @else
                            <th class="py-3.5 px-4">Jenis</th>
                        @endif
                        <th class="py-3.5 px-4 text-right">Harga Total</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($asetList as $aset)
                        <tr class="hover:bg-slate-50/80 transition-colors"
                            :class="selectedIds.includes({{ $aset->id }}) ? 'bg-emerald-50/40' : ''">
                            <td class="py-3 px-3 text-center" @click.stop>
                                <input type="checkbox"
                                       :checked="selectedIds.includes({{ $aset->id }})"
                                       @change="toggleItem({{ $aset->id }})"
                                       class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 cursor-pointer">
                            </td>
                            <td class="py-3 px-4 font-mono text-xs font-bold text-emerald-600">
                                <a href="{{ route('aset.show', $aset->id) }}" class="hover:underline">{{ $aset->kode_aset }}</a>
                            </td>
                            <td class="py-3 px-4">
                                <a href="{{ route('aset.show', $aset->id) }}" class="font-semibold text-slate-900 hover:text-emerald-600 block transition-colors">
                                    {{ $aset->nama_aset }}
                                </a>
                                <div class="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                                    <span>{{ $aset->merk->nama_merk ?? '-' }}</span>
                                    @if($aset->tipe_model)
                                        <span>•</span>
                                        <span>{{ $aset->tipe_model }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-700">
                                    {{ $aset->kategori->nama_kategori ?? '-' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-600 text-xs">{{ $aset->lokasi->nama_lokasi ?? '-' }}</td>
                            <td class="py-3 px-4 text-slate-600 text-xs">{{ $aset->penanggungJawab->nama ?? '-' }}</td>
                            <td class="py-3 px-4 text-slate-600 text-xs">
                                {{ $aset->tanggal_pembelian ? \Carbon\Carbon::parse($aset->tanggal_pembelian)->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-3 px-4">
                                @if($config['show_status_column'])
                                    <x-badge-status :status="$aset->status" />
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $aset->jenis === 'kelolaan' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-cyan-50 text-cyan-700 border-cyan-200' }}">
                                        {{ ucfirst($aset->jenis) }}
                                    </span>
                                @endif
                            <td class="py-3 px-4 text-right font-medium text-slate-900 text-xs whitespace-nowrap">
                                @if($aset->harga_total > 0)
                                    Rp {{ number_format($aset->harga_total, 0, ',', '.') }}
                                @else
                                    <span class="text-slate-400 font-normal italic">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                <a href="{{ route('aset.show', $aset->id) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 transition-colors"
                                   title="Lihat Detail Aset">
                                    <i class="ti ti-eye text-sm"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-12 text-center text-slate-400">
                                <i class="ti ti-box-search mb-2 block text-3xl"></i>
                                <p class="text-sm font-medium">{{ $config['empty'] }}</p>
                                @if(request()->anyFilled(['search', 'kategori_id', 'lokasi_id', 'penanggung_jawab_id', 'tgl_dari', 'tgl_sampai']))
                                    <p class="text-xs text-slate-400 mt-1">Coba sesuaikan atau <a href="{{ route($config['route']) }}" class="text-emerald-600 underline font-medium">reset filter</a> pencarian Anda.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($asetList->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-xs text-slate-500">
                    Menampilkan <span class="font-medium text-slate-700">{{ $asetList->firstItem() ?? 0 }}</span> sampai <span class="font-medium text-slate-700">{{ $asetList->lastItem() ?? 0 }}</span> dari <span class="font-medium text-slate-700">{{ $asetList->total() }}</span> entri
                </p>
                <div>
                    {{ $asetList->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- Floating Action Bar saat aset dicentang -->
    <div class="fixed bottom-4 inset-x-0 z-40 max-w-xl mx-auto px-4"
         x-show="selectedIds.length > 0"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-8"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-8"
         x-cloak>
        <div class="p-3 bg-slate-900/95 text-white rounded-2xl shadow-2xl backdrop-blur-md border border-slate-800 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 pl-2">
                <span class="w-7 h-7 rounded-xl bg-emerald-500/20 text-emerald-400 font-bold text-xs flex items-center justify-center" x-text="selectedIds.length"></span>
                <span class="text-xs font-bold text-slate-200"><span x-text="selectedIds.length"></span> Aset Dipilih</span>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" @click="selectedIds = []"
                        class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                    Batal
                </button>
                <button type="button" @click="goToBulkPrint()"
                        class="px-4 py-2 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-bold text-xs shadow-md shadow-emerald-600/30 transition-all inline-flex items-center gap-1.5">
                    <i class="ti ti-printer text-sm"></i>
                    <span>Cetak Label QR (<span x-text="selectedIds.length"></span>)</span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
