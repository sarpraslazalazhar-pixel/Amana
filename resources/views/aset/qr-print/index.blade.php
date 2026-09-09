@extends('layouts.app')

@section('title', 'Cetak Label QR Code Massal')

@section('content')
<div class="space-y-6 pb-20"
     x-data="{
         selectedIds: @js(array_map('intval', $preSelectedIds)),
         templateKey: '{{ request('template_key', 'a4_grid_20') }}',
         filterOpen: {{ request()->anyFilled(['search', 'kategori_id', 'lokasi_id', 'penanggung_jawab_id', 'jenis', 'status']) ? 'true' : 'false' }},
         currentPageIds: @js($asetList->pluck('id')->map(fn($id) => (int)$id)->toArray()),

         get isAllCurrentPageSelected() {
             if (this.currentPageIds.length === 0) return false;
             return this.currentPageIds.every(id => this.selectedIds.includes(id));
         },

         toggleSelectCurrentPage() {
             if (this.isAllCurrentPageSelected) {
                 this.selectedIds = this.selectedIds.filter(id => !this.currentPageIds.includes(id));
             } else {
                 const newIds = this.currentPageIds.filter(id => !this.selectedIds.includes(id));
                 this.selectedIds = [...this.selectedIds, ...newIds];
             }
         },

         toggleAsset(id) {
             const numId = parseInt(id);
             if (this.selectedIds.includes(numId)) {
                 this.selectedIds = this.selectedIds.filter(i => i !== numId);
             } else {
                 this.selectedIds.push(numId);
             }
         },

         clearSelection() {
             this.selectedIds = [];
         },

         submitAction(routeUrl, targetBlank = false) {
             if (this.selectedIds.length === 0) {
                 alert('Silakan pilih minimal satu aset untuk dicetak!');
                 return;
             }
             const form = this.$refs.printForm;
             form.action = routeUrl;
             form.target = targetBlank ? '_blank' : '_self';
             form.submit();
         }
     }">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-cyan-600 to-emerald-600 flex items-center justify-center text-white shadow-md shadow-emerald-600/20">
                    <i class="ti ti-printer text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Cetak Label QR Code Massal</h1>
                    <p class="text-xs text-slate-500">Pilih aset, tentukan template ukuran kertas / grid stiker, dan cetak dokumen PDF.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="filterOpen = !filterOpen"
                    class="px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors inline-flex items-center gap-2 shadow-xs">
                <i class="ti ti-filter text-slate-500"></i>
                <span>Filter Aset</span>
                @if(request()->anyFilled(['search', 'kategori_id', 'lokasi_id', 'penanggung_jawab_id', 'jenis', 'status']))
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                @endif
            </button>

            <a href="{{ route('pengaturan.qr-config.index') }}"
               class="px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors inline-flex items-center gap-1.5 shadow-xs"
               title="Buka Pengaturan Format QR">
                <i class="ti ti-settings text-slate-500"></i>
                <span>Format Label</span>
            </a>
        </div>
    </div>

    <!-- Banner Info Konfigurasi Format Label Aktif -->
    <div class="p-3.5 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-xs">
        <div class="flex items-center gap-2 text-emerald-950">
            <i class="ti ti-info-circle text-emerald-600 text-base flex-shrink-0"></i>
            <div>
                <span class="font-bold">Format Label Aktif:</span>
                <span class="text-slate-700 ml-1">
                    Judul: <strong>"{{ \App\Models\QrConfig::getEffectiveLabelTitle() }}"</strong> &bull;
                    Baris 1: <code>{{ $config['qr_label_row1_field'] ?? 'nama_aset' }}</code> &bull;
                    Baris 2: <code>{{ $config['qr_label_row2_field'] ?? 'kode_aset' }}</code>
                </span>
            </div>
        </div>
        <a href="{{ route('pengaturan.qr-config.index') }}#tab-label" class="text-[11px] font-bold text-emerald-700 hover:underline flex-shrink-0">
            Ubah Tata Letak Label &rarr;
        </a>
    </div>

    <!-- 1. Pilihan Template Kertas & Grid Stiker -->
    <div class="p-5 bg-white rounded-3xl border border-slate-200/80 shadow-sm space-y-3.5">
        <div>
            <h2 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                <i class="ti ti-layout-grid text-emerald-600"></i> 1. Pilih Template Ukuran Kertas & Grid Cetak
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Pilih layout yang sesuai dengan jenis printer atau kertas stiker berperekat yang Anda gunakan.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 pt-1">
            @foreach($templates as $tpl)
                <label @click="templateKey = '{{ $tpl['key'] }}'"
                       class="relative p-4 rounded-2xl border-2 cursor-pointer transition-all duration-200 flex flex-col justify-between"
                       :class="templateKey === '{{ $tpl['key'] }}' ? 'border-emerald-500 bg-emerald-50/40 ring-2 ring-emerald-500/20 shadow-sm' : 'border-slate-200 hover:border-slate-300 bg-white'">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold"
                                  :class="templateKey === '{{ $tpl['key'] }}' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600'">
                                {{ $tpl['badge'] }}
                            </span>
                            <div class="w-4 h-4 rounded-full border flex items-center justify-center"
                                 :class="templateKey === '{{ $tpl['key'] }}' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-300'">
                                <i class="ti ti-check text-[10px]" x-show="templateKey === '{{ $tpl['key'] }}'"></i>
                            </div>
                        </div>

                        <p class="font-extrabold text-xs text-slate-900">{{ $tpl['name'] }}</p>
                        <p class="text-[11px] text-slate-500 leading-relaxed">{{ $tpl['description'] }}</p>
                    </div>

                    <div class="pt-3 mt-3 border-t border-slate-100/80 flex items-center justify-between text-[10px] text-slate-400 font-mono">
                        <span>Dimensi: {{ $tpl['label_width'] }} × {{ $tpl['label_height'] }}</span>
                        <span>{{ $tpl['per_page'] }} / lembar</span>
                    </div>
                </label>
            @endforeach
        </div>
    </div>

    <!-- 2. Panel Filter Aset -->
    <div x-show="filterOpen" x-collapse x-cloak class="p-5 bg-white rounded-3xl border border-slate-200/80 shadow-sm space-y-3">
        <form method="GET" action="{{ route('aset.qr.print') }}" class="space-y-3">
            <!-- Simpan parameter selected_ids jika ada -->
            <input type="hidden" name="selected_ids" :value="selectedIds.join(',')">

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <!-- Search -->
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Pencarian Aset</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i class="ti ti-search text-xs"></i>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Cari nama, kode, no seri..."
                               class="w-full pl-8 pr-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    </div>
                </div>

                <!-- Kategori -->
                @php
                    $katItems = $kategoriList->map(fn($k) => ['id' => (string)$k->id, 'title' => $k->nama_kategori, 'code' => $k->kode_kategori])->toArray();
                @endphp
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Kategori</label>
                    <x-searchable-select name="kategori_id" :items="$katItems" :value="request('kategori_id', '')" placeholder="-- Semua Kategori --" />
                </div>

                <!-- Lokasi -->
                @php
                    $lokItems = $lokasiList->map(fn($l) => ['id' => (string)$l->id, 'title' => $l->nama_lokasi, 'code' => $l->kode_lokasi, 'subtitle' => $l->gedung])->toArray();
                @endphp
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Lokasi</label>
                    <x-searchable-select name="lokasi_id" :items="$lokItems" :value="request('lokasi_id', '')" placeholder="-- Semua Lokasi --" />
                </div>

                <!-- Penanggung Jawab -->
                @php
                    $pjItems = $penanggungJawabList->map(fn($p) => ['id' => (string)$p->id, 'title' => $p->nama, 'code' => $p->kode_pic ? str_pad($p->kode_pic, 3, '0', STR_PAD_LEFT) : null, 'subtitle' => $p->jabatan ?? ($p->divisi?->nama_divisi)])->toArray();
                @endphp
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Penanggung Jawab</label>
                    <x-searchable-select name="penanggung_jawab_id" :items="$pjItems" :value="request('penanggung_jawab_id', '')" placeholder="-- Semua PJ --" />
                </div>

                <!-- Tombol Submit Filter -->
                <div class="flex items-end gap-1.5">
                    <button type="submit" class="flex-1 py-2 px-3 rounded-xl bg-slate-800 text-white font-semibold text-xs hover:bg-slate-900 transition-colors flex items-center justify-center">
                        <i class="ti ti-filter mr-1 text-xs"></i> Filter
                    </button>
                    <a href="{{ route('aset.qr.print') }}" class="py-2 px-2.5 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 text-xs font-semibold" title="Reset Filter">
                        <i class="ti ti-rotate-clockwise"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- 3. Tabel Pemilihan Aset Massal -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden space-y-0">
        <!-- Header Tabel & Aksi Pilihan -->
        <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
            <div class="flex items-center gap-3">
                <button type="button" @click="toggleSelectCurrentPage()"
                        class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:bg-slate-50 transition-colors inline-flex items-center gap-2">
                    <div class="w-3.5 h-3.5 rounded border flex items-center justify-center"
                         :class="isAllCurrentPageSelected ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-300'">
                        <i class="ti ti-check text-[9px]" x-show="isAllCurrentPageSelected"></i>
                    </div>
                    <span x-text="isAllCurrentPageSelected ? 'Batal Pilih Halaman Ini' : 'Pilih Semua di Halaman Ini'"></span>
                </button>

                <template x-if="selectedIds.length > 0">
                    <button type="button" @click="clearSelection()"
                            class="text-xs font-semibold text-rose-600 hover:underline">
                        Kosongkan (<span x-text="selectedIds.length"></span>)
                    </button>
                </template>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-500">
                    Total Ditemukan: <strong class="text-slate-800">{{ number_format($totalFiltered, 0, ',', '.') }}</strong> Aset
                </span>
                <span class="inline-block w-1 h-1 rounded-full bg-slate-300"></span>
                <span class="text-xs font-bold text-emerald-700 bg-emerald-100/80 px-2.5 py-0.5 rounded-full">
                    <span x-text="selectedIds.length"></span> Aset Terpilih
                </span>
            </div>
        </div>

        <!-- Tabel List -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/30 text-slate-400 font-semibold text-[11px] uppercase tracking-wider">
                        <th class="py-3 px-4 w-12 text-center">Pilih</th>
                        <th class="py-3 px-4">Identitas Aset</th>
                        <th class="py-3 px-4">Kategori & Merk</th>
                        <th class="py-3 px-4">Lokasi</th>
                        <th class="py-3 px-4">Penanggung Jawab</th>
                        <th class="py-3 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($asetList as $aset)
                        <tr class="hover:bg-slate-50/80 transition-colors cursor-pointer"
                            @click="toggleAsset({{ $aset->id }})"
                            :class="selectedIds.includes({{ $aset->id }}) ? 'bg-emerald-50/30' : ''">
                            <td class="py-3 px-4 text-center" @click.stop>
                                <input type="checkbox"
                                       :checked="selectedIds.includes({{ $aset->id }})"
                                       @change="toggleAsset({{ $aset->id }})"
                                       class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 cursor-pointer">
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-extrabold text-slate-900">{{ $aset->nama_aset }}</div>
                                <span class="font-mono font-bold text-[10px] text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 inline-block mt-0.5">
                                    {{ $aset->kode_aset }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-800">{{ $aset->kategori->nama_kategori ?? '-' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $aset->merk->nama_merk ?? '-' }} {{ $aset->tipe_model }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-medium text-slate-800">{{ $aset->lokasi->nama_lokasi ?? '-' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $aset->lokasi->gedung ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-medium text-slate-800">{{ $aset->penanggungJawab->nama ?? '-' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $aset->penanggungJawab->jabatan ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $aset->status === 'aktif' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                    {{ strtoupper(str_replace('_', '-', $aset->status)) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                <i class="ti ti-box text-3xl mb-2 block"></i>
                                Tidak ada aset yang ditemukan dengan kriteria filter saat ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($asetList->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $asetList->links() }}
            </div>
        @endif
    </div>

    <!-- Hidden Form untuk Submit Aksi Cetak / Preview / Download -->
    <form x-ref="printForm" method="POST" action="" class="hidden">
        @csrf
        <input type="hidden" name="template_key" :value="templateKey">
        <template x-for="id in selectedIds" :key="id">
            <input type="hidden" name="aset_ids[]" :value="id">
        </template>
    </form>

    <!-- Floating Action Bar (Sticky di Bawah Layar) -->
    <div class="fixed bottom-4 inset-x-0 z-40 max-w-4xl mx-auto px-4"
         x-show="selectedIds.length > 0"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-8"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-8"
         x-cloak>
        <div class="p-3.5 bg-slate-900/95 text-white rounded-3xl shadow-2xl backdrop-blur-md border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3 pl-2">
                <div class="w-9 h-9 rounded-2xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-sm">
                    <span x-text="selectedIds.length"></span>
                </div>
                <div>
                    <p class="text-xs font-bold leading-tight">
                        <span x-text="selectedIds.length"></span> Aset Terpilih untuk Dicetak
                    </p>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        Format: <span class="text-emerald-400 font-semibold" x-text="templateKey"></span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Tombol 1: Live Interactive Preview -->
                <button type="button" @click="submitAction('{{ route('aset.qr.preview') }}', false)"
                        class="px-4 py-2.5 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white font-bold text-xs transition-all border border-slate-700 inline-flex items-center gap-1.5">
                    <i class="ti ti-eye text-sm text-cyan-400"></i>
                    <span>Preview Lembaran</span>
                </button>

                <!-- Tombol 2: Unduh PDF -->
                <button type="button" @click="submitAction('{{ route('aset.qr.download-pdf') }}', false)"
                        class="px-4 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 transition-all inline-flex items-center gap-1.5">
                    <i class="ti ti-download text-sm"></i>
                    <span>Unduh PDF</span>
                </button>

                <!-- Tombol 3: Cetak Langsung Browser -->
                <button type="button" @click="submitAction('{{ route('aset.qr.print-direct') }}', true)"
                        class="px-4 py-2.5 rounded-2xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-bold text-xs shadow-lg shadow-cyan-600/30 transition-all inline-flex items-center gap-1.5">
                    <i class="ti ti-printer text-sm"></i>
                    <span>Cetak Langsung</span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

