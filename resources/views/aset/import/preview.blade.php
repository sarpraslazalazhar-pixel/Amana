@extends('layouts.app')

@section('title', 'Resolusi Komponen Impor — ' . $batch->nama_file)
@section('header-title', 'Resolusi Komponen Kode Aset')

@section('content')
<div class="space-y-6" x-data="importPreview({
    batchId: {{ $batch->id }},
    total: {{ $batch->total_baris }},
    ready: {{ $batch->baris_siap }},
    incomplete: {{ $batch->baris_belum_lengkap }},
    duplicate: {{ $batch->baris_duplikat }},
    failed: {{ $failedCount ?? 0 }}
})">

    <!-- Header Actions & Title -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('aset.import.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1">
                    <i class="ti ti-arrow-left"></i> Kembali ke Riwayat
                </a>
                <span class="text-slate-300">•</span>
                <span class="text-xs font-bold text-slate-600 font-mono">Batch #{{ $batch->id }}</span>
            </div>
            <h2 class="text-xl font-bold text-slate-900 tracking-tight mt-1 flex items-center gap-2">
                <i class="ti ti-file-spreadsheet text-emerald-600"></i> {{ $batch->nama_file }}
            </h2>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('aset.import.summary', $batch->id) }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs border border-slate-200 shadow-sm transition-all">
                <i class="ti ti-download mr-1.5 text-slate-500 text-sm"></i> Unduh Laporan Status
            </a>

            @if($batch->status !== 'completed')
                <button type="button" @click="openCommitModal = true"
                        :disabled="ready === 0"
                        class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-semibold text-xs shadow-md shadow-emerald-600/15 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="ti ti-check mr-1.5 text-sm"></i> Generate & Simpan Aset (<span x-text="ready"></span>)
                </button>
            @else
                <span class="inline-flex items-center px-4 py-2.5 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200">
                    <i class="ti ti-check-double mr-1.5 text-sm"></i> Batch Selesai Diimpor
                </span>
            @endif
        </div>
    </div>

    <!-- Alert Flash Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-3">
            <i class="ti ti-circle-check text-lg text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('warning'))
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs space-y-2">
            <div class="flex items-center gap-2 font-bold">
                <i class="ti ti-alert-triangle text-base text-amber-600"></i>
                <span>{{ session('warning') }}</span>
            </div>
            @if(session('import_errors') && is_array(session('import_errors')))
                <div class="mt-2 pl-6 space-y-1">
                    <p class="font-semibold text-amber-900">Rincian baris gagal:</p>
                    <ul class="list-disc list-inside space-y-1 text-amber-800 font-mono text-[11px]">
                        @foreach(session('import_errors') as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-3">
            <i class="ti ti-alert-circle text-lg text-rose-600"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <!-- Top Summary Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Baris</p>
                <h3 class="text-xl font-extrabold text-slate-900 mt-1" x-text="total"></h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
                <i class="ti ti-list text-xl"></i>
            </div>
        </div>

        <div class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Siap Generate</p>
                <h3 class="text-xl font-extrabold text-emerald-700 mt-1" x-text="ready"></h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i class="ti ti-circle-check text-xl"></i>
            </div>
        </div>

        <div class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Belum Lengkap</p>
                <h3 class="text-xl font-extrabold text-amber-700 mt-1" x-text="incomplete"></h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <i class="ti ti-alert-triangle text-xl"></i>
            </div>
        </div>

        <div class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-cyan-600 uppercase tracking-wider">Duplikat Terdeteksi</p>
                <h3 class="text-xl font-extrabold text-cyan-700 mt-1" x-text="duplicate"></h3>
            </div>
            <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center">
                <i class="ti ti-copy text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Bulk-Assign Toolbar (Sticky Top) -->
    @if($batch->status !== 'completed')
        <div class="p-4 bg-gradient-to-r from-slate-900 to-slate-800 text-white rounded-2xl shadow-md border border-slate-800 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-white/10 pb-2.5">
                <div class="flex items-center gap-2">
                    <i class="ti ti-wand text-emerald-400 text-base"></i>
                    <h4 class="text-xs font-bold uppercase tracking-wider">Alat Bulk-Assign (Terapkan Massal Komponen)</h4>
                </div>
                <div class="text-[11px] text-slate-300">
                    Terpilih: <span class="font-bold text-emerald-400" x-text="selectedItems.length"></span> baris di halaman ini
                </div>
            </div>

            <form action="{{ route('aset.import.bulk-assign', $batch->id) }}" method="POST" class="space-y-3">
                @csrf
                <template x-for="id in selectedItems" :key="id">
                    <input type="hidden" name="item_ids[]" :value="id">
                </template>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2.5 text-xs text-slate-800">
                    <!-- Divisi -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-300 mb-1">Divisi (Auto-Klasifikasi)</label>
                        <select name="divisi_id" class="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-700 bg-slate-800 text-white focus:bg-slate-900 focus:outline-none">
                            <option value="">-- Tetap / Tidak Diubah --</option>
                            @foreach($divisiList as $div)
                                <option value="{{ $div->id }}">{{ $div->kode_divisi }} - {{ $div->nama_divisi }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Sifat Barang -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-300 mb-1">Sifat Barang</label>
                        <select name="sifat_barang" class="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-700 bg-slate-800 text-white focus:bg-slate-900 focus:outline-none">
                            <option value="">-- Tetap --</option>
                            <option value="D">Dinamis (Amil / PIC)</option>
                            <option value="S">Statis (Lokasi Bersama)</option>
                        </select>
                    </div>

                    <!-- Cara Perolehan -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-300 mb-1">Cara Perolehan</label>
                        <select name="cara_perolehan" class="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-700 bg-slate-800 text-white focus:bg-slate-900 focus:outline-none">
                            <option value="">-- Tetap --</option>
                            <option value="1">1 - Beli</option>
                            <option value="2">2 - Hibah / Donasi</option>
                        </select>
                    </div>

                    <!-- Status Barang -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-300 mb-1">Status Barang</label>
                        <select name="status_barang" class="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-700 bg-slate-800 text-white focus:bg-slate-900 focus:outline-none">
                            <option value="">-- Tetap --</option>
                            <option value="1">1 - Baru</option>
                            <option value="2">2 - Second</option>
                        </select>
                    </div>

                    <!-- Lokasi Default -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-300 mb-1">Set Lokasi</label>
                        <select name="lokasi_id" class="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-700 bg-slate-800 text-white focus:bg-slate-900 focus:outline-none">
                            <option value="">-- Tetap --</option>
                            @foreach($lokasiList as $lok)
                                <option value="{{ $lok->id }}">{{ $lok->kode_lokasi }} - {{ $lok->nama_lokasi }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Scope Assignment & Submit -->
                    <div class="flex items-end gap-1.5">
                        <select name="selection_mode" class="w-full px-2 py-1.5 text-xs rounded-xl border border-slate-700 bg-slate-800 text-slate-200 focus:outline-none">
                            <option value="selected">Baris Terpilih</option>
                            <option value="all_incomplete">Semua Belum Lengkap</option>
                            <option value="all_batch">Seluruh File (Semua)</option>
                        </select>

                        <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs transition-all whitespace-nowrap shadow-sm">
                            Terapkan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <!-- Filter & Search Card -->
    <div class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <!-- Status Tabs -->
        <div class="flex items-center gap-1.5 flex-wrap">
            <a href="{{ request()->fullUrlWithQuery(['status' => 'all', 'page' => 1]) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ $filterStatus === 'all' ? 'bg-slate-800 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Semua Baris
            </a>
            <a href="{{ request()->fullUrlWithQuery(['status' => 'incomplete', 'page' => 1]) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ $filterStatus === 'incomplete' ? 'bg-amber-600 text-white shadow-sm' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200' }}">
                ⚠️ Belum Lengkap (<span x-text="incomplete"></span>)
            </a>
            <a href="{{ request()->fullUrlWithQuery(['status' => 'ready', 'page' => 1]) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ $filterStatus === 'ready' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' }}">
                ✓ Siap Generate (<span x-text="ready"></span>)
            </a>
            <a href="{{ request()->fullUrlWithQuery(['status' => 'duplicate', 'page' => 1]) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ $filterStatus === 'duplicate' ? 'bg-cyan-600 text-white shadow-sm' : 'bg-cyan-50 text-cyan-700 hover:bg-cyan-100 border border-cyan-200' }}">
                Duplikat (<span x-text="duplicate"></span>)
            </a>
            @if(($failedCount ?? 0) > 0)
                <a href="{{ request()->fullUrlWithQuery(['status' => 'failed', 'page' => 1]) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ $filterStatus === 'failed' ? 'bg-rose-600 text-white shadow-sm' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200' }}">
                    ❌ Gagal Impor (<span x-text="failed"></span>)
                </a>
            @endif
        </div>

        <!-- Search & Filter Form -->
        <form method="GET" action="{{ route('aset.import.preview', $batch->id) }}" class="flex items-center gap-2">
            <input type="hidden" name="status" value="{{ $filterStatus }}">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari aset, kode, PIC, lokasi..."
                   class="px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 w-48 sm:w-60">

            <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white rounded-xl text-xs font-semibold hover:bg-slate-900">
                <i class="ti ti-search"></i>
            </button>
            @if(request()->anyFilled(['search', 'kategori_id']))
                <a href="{{ route('aset.import.preview', ['batch' => $batch->id, 'status' => $filterStatus]) }}" class="px-2 py-1.5 bg-slate-100 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-200" title="Reset">
                    <i class="ti ti-rotate-clockwise"></i>
                </a>
            @endif
        </form>
    </div>

    <!-- Data Staging Interactive Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-3 text-center w-10">
                            <input type="checkbox" @change="toggleSelectAll($event)" class="rounded text-emerald-600 focus:ring-emerald-500">
                        </th>
                        <th class="py-3 px-3 text-center w-12">#</th>
                        <th class="py-3 px-4 w-44">Status & Kelengkapan</th>
                        <th class="py-3 px-4 min-w-[200px]">Data Mentah Excel</th>
                        <th class="py-3 px-3 min-w-[140px]">Kategori</th>
                        <th class="py-3 px-3 min-w-[150px]">Nama Barang</th>
                        <th class="py-3 px-3 min-w-[110px]">Sifat</th>
                        <th class="py-3 px-3 min-w-[160px]">PIC (NIA) / Lokasi</th>
                        <th class="py-3 px-3 min-w-[140px]">Divisi</th>
                        <th class="py-3 px-3 min-w-[100px]">Perolehan</th>
                        <th class="py-3 px-3 min-w-[100px]">Kondisi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50/70 transition-colors"
                            x-data="rowItem({
                                id: {{ $item->id }},
                                is_ready: {{ $item->is_ready ? 'true' : 'false' }},
                                is_duplicate: {{ $item->is_duplicate ? 'true' : 'false' }},
                                missing: {{ json_encode($item->missing_components ?? []) }},
                                sifat: '{{ $item->sifat_barang }}',
                                kategori_id: '{{ $item->kategori_id }}',
                                barang_id: '{{ $item->barang_id }}',
                                pj_id: '{{ $item->penanggung_jawab_id }}',
                                lokasi_id: '{{ $item->lokasi_id }}',
                                divisi_id: '{{ $item->divisi_id }}',
                                cara: '{{ $item->cara_perolehan }}',
                                status_brg: '{{ $item->status_barang }}'
                            })"
                            :class="'{{ $item->import_status }}' === 'failed' ? 'bg-rose-50/40 border-l-4 border-l-rose-500' : (!is_ready ? 'bg-amber-50/20' : (is_duplicate ? 'bg-cyan-50/20' : ''))">

                            <!-- Checkbox -->
                            <td class="py-2.5 px-3 text-center">
                                <input type="checkbox" :value="{{ $item->id }}" x-model="selectedItems" class="rounded text-emerald-600 focus:ring-emerald-500">
                            </td>

                            <!-- Baris # -->
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-500">
                                {{ $item->baris_ke }}
                            </td>

                            <!-- Status Badge & Feedback -->
                            <td class="py-2.5 px-4">
                                @if($item->import_status === 'failed')
                                    <div class="space-y-1">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-100 text-rose-800">
                                            <i class="ti ti-x text-xs"></i> Gagal Impor
                                        </span>
                                        <p class="text-[10px] text-rose-600 font-medium leading-tight">
                                            {{ $item->error_message ?: 'Gagal saat generate/simpan aset' }}
                                        </p>
                                    </div>
                                @elseif($item->import_status === 'success')
                                    <div class="space-y-0.5">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            <i class="ti ti-circle-check text-xs"></i> Berhasil Diimpor
                                        </span>
                                        <div class="font-mono text-[11px] font-bold text-emerald-700">{{ $item->generated_kode_aset }}</div>
                                    </div>
                                @elseif($item->import_status === 'updated')
                                    <div class="space-y-0.5">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-cyan-100 text-cyan-800">
                                            <i class="ti ti-refresh text-xs"></i> Diperbarui
                                        </span>
                                        <div class="font-mono text-[11px] font-bold text-cyan-700">{{ $item->generated_kode_aset }}</div>
                                    </div>
                                @elseif($item->import_status === 'skipped')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600">
                                        <i class="ti ti-player-skip-forward text-xs"></i> Dilewati
                                    </span>
                                @else
                                    <template x-if="is_ready">
                                        <div class="space-y-0.5">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                <i class="ti ti-check text-xs"></i> Siap Generate
                                            </span>
                                            @if($item->is_duplicate)
                                                <div class="text-[10px] text-cyan-700 font-medium">Duplikat Terdeteksi</div>
                                            @endif
                                            @if($item->generated_kode_aset)
                                                <div class="font-mono text-[11px] font-bold text-emerald-700">{{ $item->generated_kode_aset }}</div>
                                            @endif
                                        </div>
                                    </template>

                                    <template x-if="!is_ready">
                                        <div class="space-y-1">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800">
                                                <i class="ti ti-alert-triangle text-xs"></i> Belum Lengkap
                                            </span>
                                            <p class="text-[10px] text-rose-600 leading-tight">
                                                Kurang: <span x-text="missing.join(', ')"></span>
                                            </p>
                                        </div>
                                    </template>
                                @endif
                            </td>

                            <!-- Data Mentah Excel -->
                            <td class="py-2.5 px-4">
                                <p class="font-semibold text-slate-900 leading-snug">{{ $item->nama_aset_mentah }}</p>
                                <div class="text-[10px] text-slate-500 flex flex-wrap gap-x-2 gap-y-0.5 mt-0.5">
                                    @if($item->kode_aset_lama)
                                        <span class="font-mono text-slate-700">Kode: {{ $item->kode_aset_lama }}</span>
                                    @endif
                                    @if($item->kode_sistem_lama)
                                        <span class="font-mono text-slate-500">Sistem: {{ $item->kode_sistem_lama }}</span>
                                    @endif
                                    @if($item->merk_mentah)
                                        <span>Merk: {{ $item->merk_mentah }}</span>
                                    @endif
                                    @if($item->pj_mentah)
                                        <span>PJ: {{ $item->pj_mentah }}</span>
                                    @endif
                                    @if($item->lokasi_mentah)
                                        <span>Lok: {{ $item->lokasi_mentah }}</span>
                                    @endif
                                </div>
                                <div class="text-[10px] flex flex-wrap gap-x-2 gap-y-0.5 mt-1">
                                    <span class="font-bold {{ $item->harga_satuan > 0 ? 'text-emerald-700' : 'text-amber-600' }}">
                                        Harga: Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}
                                    </span>
                                    @if($item->jumlah_unit > 1)
                                        <span class="text-slate-500">Qty: {{ $item->jumlah_unit }}</span>
                                    @endif
                                    @if($item->tanggal_pembelian)
                                        <span class="text-slate-500">Tgl: {{ $item->tanggal_pembelian->format('d/m/Y') }}</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Dropdown Kategori -->
                            <td class="py-2.5 px-3">
                                <select x-model="kategori_id" @change="saveField('kategori_id', $event.target.value)"
                                        class="w-full px-2 py-1 text-xs rounded-lg border border-slate-200 bg-white focus:ring-1 focus:ring-emerald-500 focus:outline-none">
                                    <option value="">-- Pilih --</option>
                                    @foreach($kategoriList as $kat)
                                        <option value="{{ $kat->id }}">{{ $kat->kode_kategori }} - {{ $kat->nama_kategori }}</option>
                                    @endforeach
                                </select>
                            </td>

                            <!-- Dropdown Barang -->
                            <td class="py-2.5 px-3">
                                <select x-model="barang_id" @change="saveField('barang_id', $event.target.value)"
                                        class="w-full px-2 py-1 text-xs rounded-lg border border-slate-200 bg-white focus:ring-1 focus:ring-emerald-500 focus:outline-none">
                                    <option value="">-- Pilih Barang --</option>
                                    @foreach($barangList as $brg)
                                        <option value="{{ $brg->id }}">{{ $brg->kode_barang }} - {{ $brg->nama_barang }}</option>
                                    @endforeach
                                </select>
                            </td>

                            <!-- Dropdown Sifat (D/S) -->
                            <td class="py-2.5 px-3">
                                <select x-model="sifat" @change="saveField('sifat_barang', $event.target.value)"
                                        class="w-full px-2 py-1 text-xs rounded-lg border border-slate-200 bg-white focus:ring-1 focus:ring-emerald-500 focus:outline-none">
                                    <option value="D">D (Amil/PIC)</option>
                                    <option value="S">S (Lokasi)</option>
                                </select>
                            </td>

                            <!-- Dropdown PIC / Lokasi -->
                            <td class="py-2.5 px-3">
                                <template x-if="sifat === 'D'">
                                    <select x-model="pj_id" @change="saveField('penanggung_jawab_id', $event.target.value)"
                                            class="w-full px-2 py-1 text-xs rounded-lg border border-slate-200 bg-white focus:ring-1 focus:ring-emerald-500 focus:outline-none">
                                        <option value="">-- Pilih PIC (NIA) --</option>
                                        @foreach($pjList as $pj)
                                            <option value="{{ $pj->id }}">{{ $pj->kode_pic ?: '---' }} - {{ $pj->nama }}</option>
                                        @endforeach
                                    </select>
                                </template>

                                <template x-if="sifat === 'S'">
                                    <select x-model="lokasi_id" @change="saveField('lokasi_id', $event.target.value)"
                                            class="w-full px-2 py-1 text-xs rounded-lg border border-slate-200 bg-white focus:ring-1 focus:ring-emerald-500 focus:outline-none">
                                        <option value="">-- Pilih Lokasi --</option>
                                        @foreach($lokasiList as $lok)
                                            <option value="{{ $lok->id }}">{{ $lok->kode_lokasi }} - {{ $lok->nama_lokasi }}</option>
                                        @endforeach
                                    </select>
                                </template>
                            </td>

                            <!-- Dropdown Divisi -->
                            <td class="py-2.5 px-3">
                                <select x-model="divisi_id" @change="saveField('divisi_id', $event.target.value)"
                                        class="w-full px-2 py-1 text-xs rounded-lg border border-slate-200 bg-white focus:ring-1 focus:ring-emerald-500 focus:outline-none">
                                    <option value="">-- Pilih Divisi --</option>
                                    @foreach($divisiList as $div)
                                        <option value="{{ $div->id }}">{{ $div->kode_divisi }} - {{ $div->nama_divisi }}</option>
                                    @endforeach
                                </select>
                            </td>

                            <!-- Cara Perolehan -->
                            <td class="py-2.5 px-3">
                                <select x-model="cara" @change="saveField('cara_perolehan', $event.target.value)"
                                        class="w-full px-1.5 py-1 text-xs rounded-lg border border-slate-200 bg-white focus:ring-1 focus:ring-emerald-500 focus:outline-none">
                                    <option value="1">1 - Beli</option>
                                    <option value="2">2 - Hibah</option>
                                </select>
                            </td>

                            <!-- Status Barang -->
                            <td class="py-2.5 px-3">
                                <select x-model="status_brg" @change="saveField('status_barang', $event.target.value)"
                                        class="w-full px-1.5 py-1 text-xs rounded-lg border border-slate-200 bg-white focus:ring-1 focus:ring-emerald-500 focus:outline-none">
                                    <option value="1">1 - Baru</option>
                                    <option value="2">2 - Second</option>
                                </select>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="py-10 text-center text-slate-400">
                                <i class="ti ti-inbox text-2xl mb-1 block"></i>
                                Tidak ada data baris yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-slate-500">
                Menampilkan {{ $items->firstItem() ?? 0 }} - {{ $items->lastItem() ?? 0 }} dari {{ $items->total() }} baris
            </span>
            {{ $items->links() }}
        </div>
    </div>

    <!-- Modal Konfirmasi Commit Import -->
    <div x-show="openCommitModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-5" @click.away="openCommitModal = false">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl">
                    <i class="ti ti-database-import"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Konfirmasi Eksekusi Impor Aset</h3>
                    <p class="text-xs text-slate-500">Generate kode aset resmi & simpan ke sistem AMANA</p>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-600">Baris Siap Di-generate:</span>
                    <span class="font-bold text-emerald-600" x-text="ready + ' Aset'"></span>
                </div>
                <div class="flex justify-between" x-show="incomplete > 0">
                    <span class="text-slate-600">Baris Belum Lengkap (akan dilewati):</span>
                    <span class="font-bold text-amber-600" x-text="incomplete + ' Aset'"></span>
                </div>
                <div class="flex justify-between" x-show="duplicate > 0">
                    <span class="text-slate-600">Baris Duplikat Terdeteksi:</span>
                    <span class="font-bold text-cyan-600" x-text="duplicate + ' Aset'"></span>
                </div>
            </div>

            <form action="{{ route('aset.import.commit', $batch->id) }}" method="POST" class="space-y-4">
                @csrf

                <!-- Opsi Duplikat -->
                <div class="space-y-2" x-show="duplicate > 0">
                    <label class="block text-xs font-bold text-slate-800">Tindakan untuk Baris Duplikat (Kode Lama Sama):</label>
                    <div class="space-y-1.5 text-xs">
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50">
                            <input type="radio" name="duplicate_action" value="update" checked class="text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <p class="font-semibold text-slate-800">Perbarui Data Aset yang Ada (Update)</p>
                                <p class="text-[11px] text-slate-500">Memperbarui harga & rincian aset yang sudah terdaftar tanpa mengubah kode aset.</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50">
                            <input type="radio" name="duplicate_action" value="skip" class="text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <p class="font-semibold text-slate-800">Lewati Baris Duplikat (Skip)</p>
                                <p class="text-[11px] text-slate-500">Hanya impor aset yang benar-benar baru.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button type="button" @click="openCommitModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-md shadow-emerald-600/20 transition-all">
                        <i class="ti ti-check mr-1"></i> Mulai Generate & Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('importPreview', (config) => ({
        batchId: config.batchId,
        total: config.total,
        ready: config.ready,
        incomplete: config.incomplete,
        duplicate: config.duplicate,
        failed: config.failed || 0,
        selectedItems: [],
        openCommitModal: false,

        toggleSelectAll(e) {
            if (e.target.checked) {
                const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
                this.selectedItems = Array.from(checkboxes).map(cb => parseInt(cb.value)).filter(v => !isNaN(v));
            } else {
                this.selectedItems = [];
            }
        },

        updateMetrics(summary) {
            if (summary) {
                this.total = summary.total;
                this.ready = summary.ready;
                this.incomplete = summary.incomplete;
                this.duplicate = summary.duplicate;
            }
        }
    }));

    Alpine.data('rowItem', (row) => ({
        id: row.id,
        is_ready: row.is_ready,
        is_duplicate: row.is_duplicate,
        missing: row.missing,
        sifat: row.sifat,
        kategori_id: row.kategori_id,
        barang_id: row.barang_id,
        pj_id: row.pj_id,
        lokasi_id: row.lokasi_id,
        divisi_id: row.divisi_id,
        cara: row.cara,
        status_brg: row.status_brg,

        saveField(field, value) {
            const payload = {};
            payload[field] = value;

            fetch(`/aset/import/item/${this.id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.is_ready = data.is_ready;
                    this.missing = data.missing_components || [];
                    // Update parent metrics
                    const root = Alpine.$data(document.querySelector('[x-data^="importPreview"]'));
                    if (root && root.updateMetrics) {
                        root.updateMetrics(data.batch_summary);
                    }
                }
            })
            .catch(err => console.error('Auto-save error:', err));
        }
    }));
});
</script>
@endsection
