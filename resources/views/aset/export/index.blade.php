@extends('layouts.app')

@section('title', 'Pusat Ekspor Data Aset')
@section('header-title', 'Pusat Ekspor Data Aset')
@section('content')
@php
    $divisiFilterItems = $divisiList->map(fn($d) => [
        'id' => (string) $d->id,
        'title' => $d->nama_divisi,
        'code' => 'Div ' . $d->kode_divisi,
        'subtitle' => $d->keterangan ?: '',
    ])->toArray();

    $kategoriFilterItems = $kategoriList->map(fn($k) => [
        'id' => (string) $k->id,
        'title' => $k->nama_kategori,
        'code' => $k->kode_kategori,
        'subtitle' => '',
    ])->toArray();

    $merkFilterItems = $merkList->map(fn($m) => [
        'id' => (string) $m->id,
        'title' => $m->nama_merk,
        'code' => '',
        'subtitle' => '',
    ])->toArray();

    $wilayahFilterItems = collect($wilayahList ?? \App\Models\Lokasi::getWilayahOptions())->map(fn($w, $key) => [
        'id' => (string) $key,
        'title' => $w['nama'],
        'code' => $key,
        'subtitle' => 'Rentang: ' . $key,
    ])->values()->toArray();

    $lokasiFilterItems = $lokasiList->map(fn($l) => [
        'id' => (string) $l->id,
        'title' => $l->nama_lokasi,
        'code' => $l->kode_lokasi,
        'subtitle' => $l->gedung ?: 'Gedung',
    ])->toArray();

    $pjFilterItems = $pjList->map(fn($p) => [
        'id' => (string) $p->id,
        'title' => $p->nama,
        'code' => $p->kode_pic ? str_pad($p->kode_pic, 3, '0', STR_PAD_LEFT) : null,
        'subtitle' => $p->jabatan ?? ($p->divisi?->nama_divisi ?? null),
    ])->toArray();
@endphp
<div class="space-y-6">

    <!-- Header Actions & Title -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">Pusat Ekspor Data Aset</h2>
            <p class="text-xs text-slate-500">Unduh data aset dalam format Excel (.xlsx) resmi dengan header dua tingkat, nilai penyusutan, dan filter klasifikasi.</p>
        </div>

        <div class="flex items-center gap-2">
            @if(auth()->check() && auth()->user()->role === 'super_admin')
                <a href="{{ route('aset.import.index') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs border border-slate-200 shadow-sm transition-all">
                    <i class="ti ti-file-import mr-1.5 text-emerald-600 text-sm"></i> Impor Data Aset
                </a>
            @endif
            <a href="{{ route('aset.tetap') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition-all">
                <i class="ti ti-arrow-left mr-1.5 text-sm"></i> Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Form Filter & Download (2 Cols) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="p-6 bg-white rounded-2xl border border-slate-200/80 shadow-sm space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <i class="ti ti-adjustments-horizontal text-emerald-600 text-base"></i> Parameter & Filter Ekspor
                    </h3>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ number_format($filteredCount, 0, ',', '.') }} dari {{ number_format($totalCount, 0, ',', '.') }} Aset
                    </span>
                </div>

                <form action="{{ route('aset.export.download') }}" method="GET" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <!-- Klasifikasi Aset -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Klasifikasi Aset (Turunan Divisi)</label>
                            <select name="klasifikasi" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                                <option value="semua" {{ request('klasifikasi') === 'semua' ? 'selected' : '' }}>-- Semua Klasifikasi --</option>
                                <option value="tetap" {{ request('klasifikasi') === 'tetap' ? 'selected' : '' }}>Aset Tetap (Divisi 1 - 4)</option>
                                <option value="kelolaan" {{ request('klasifikasi') === 'kelolaan' ? 'selected' : '' }}>Aset dalam Kelolaan (Divisi 5 - 6)</option>
                            </select>
                        </div>

                        <!-- Divisi -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Divisi Spesifik</label>
                            <x-searchable-select
                                name="divisi_id"
                                :items="$divisiFilterItems"
                                :value="request('divisi_id', '')"
                                placeholder="-- Semua Divisi --"
                            />
                        </div>

                        <!-- Kategori -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Barang</label>
                            <x-searchable-select
                                name="kategori_id"
                                :items="$kategoriFilterItems"
                                :value="request('kategori_id', '')"
                                placeholder="-- Semua Kategori --"
                            />
                        </div>

                        <!-- Merk -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Merk / Brand</label>
                            <x-searchable-select
                                name="merk_id"
                                :items="$merkFilterItems"
                                :value="request('merk_id', '')"
                                placeholder="-- Semua Merk --"
                            />
                        </div>

                        <!-- Area / Wilayah Lokasi (Ratusan) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Area / Wilayah Lokasi (1xx - 6xx)</label>
                            <x-searchable-select
                                name="area_lokasi"
                                :items="$wilayahFilterItems"
                                :value="request('area_lokasi', '')"
                                placeholder="-- Semua Area / Wilayah --"
                            />
                        </div>

                        <!-- Lokasi -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Lokasi Penempatan</label>
                            <x-searchable-select
                                name="lokasi_id"
                                :items="$lokasiFilterItems"
                                :value="request('lokasi_id', '')"
                                placeholder="-- Semua Lokasi --"
                            />
                        </div>

                        <!-- Penanggung Jawab -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Penanggung Jawab (Amil / PIC)</label>
                            <x-searchable-select
                                name="penanggung_jawab_id"
                                :items="$pjFilterItems"
                                :value="request('penanggung_jawab_id', '')"
                                placeholder="-- Semua Penanggung Jawab --"
                            />
                        </div>

                        <!-- Status Aset -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Status Aset</label>
                            <select name="status" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                                <option value="semua" {{ request('status') === 'semua' ? 'selected' : '' }}>-- Semua Status --</option>
                                <option value="aktif" {{ request('status', 'aktif') === 'aktif' ? 'selected' : '' }}>Aktif Digunakan</option>
                                <option value="non_aktif" {{ request('status') === 'non_aktif' ? 'selected' : '' }}>Non-Aktif / Diarsipkan</option>
                            </select>
                        </div>

                        <!-- Pencarian Keyword -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Kunci (Nama, Kode, No Seri)</label>
                            <input type="text" name="search" value="{{ request('search') }}"
                                   placeholder="Contoh: Laptop, EL14D..., AC..."
                                   class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        </div>

                        <!-- Rentang Tanggal Beli -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Beli Dari</label>
                            <input type="date" name="tgl_dari" value="{{ request('tgl_dari') }}"
                                   class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Beli Sampai</label>
                            <input type="date" name="tgl_sampai" value="{{ request('tgl_sampai') }}"
                                   class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                        <button type="submit" formaction="{{ route('aset.export.index') }}"
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition-colors">
                            <i class="ti ti-refresh text-sm"></i> Perbarui Hitungan Filter
                        </button>

                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition-all">
                            <i class="ti ti-file-spreadsheet text-base"></i> Download File Excel (.xlsx)
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Side: Format Info & Structure -->
        <div class="space-y-6">
            <div class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
                <div class="flex items-center gap-2 text-emerald-700 font-bold text-xs">
                    <i class="ti ti-table text-lg"></i> Struktur Laporan Excel (.xlsx)
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    File hasil ekspor disusun dengan standar laporan aset resmi LAZ Al Azhar Peduli:
                </p>

                <div class="space-y-2 text-xs">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60 space-y-1">
                        <p class="font-bold text-slate-800">1. Header Identitas Lembaga</p>
                        <p class="text-slate-500 text-[11px]">Memuat judul laporan, nama lembaga, tanggal cetak, dan total aset terfilter.</p>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60 space-y-1">
                        <p class="font-bold text-slate-800">2. Header Dua Tingkat (Multi-Level)</p>
                        <ul class="text-[11px] text-slate-500 list-disc list-inside space-y-0.5">
                            <li><strong>Identitas Aset:</strong> Kode Baru, Kode Lama, Nama, Sifat, Kategori, Barang, Merk, Model</li>
                            <li><strong>Penempatan & PIC:</strong> Lokasi, Divisi, Klasifikasi, Penanggung Jawab</li>
                            <li><strong>Pembelian:</strong> Tanggal, Toko, Invoice, Qty, Harga Satuan, Harga Total</li>
                            <li><strong>Penyusutan:</strong> Umur, Residu, Penyusutan/Bln, Nilai Buku Terkini</li>
                            <li><strong>Status:</strong> Cara Perolehan, Status Barang, Status Aset</li>
                        </ul>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60 space-y-1">
                        <p class="font-bold text-slate-800">3. Format Angka & Mata Uang</p>
                        <p class="text-slate-500 text-[11px]">Semua nilai moneter diformat rapi dengan format ribuan standar Excel (Accounting/Number).</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
