@extends('layouts.app')

@section('title', 'Data Master Kategori & Jenis Barang')
@section('header-title', 'Master Data: Kategori & Jenis Barang')

@section('content')
<div class="space-y-6" x-data="kategoriManager">

    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Kategori & Jenis Barang</h2>
            <p class="text-xs text-slate-500 mt-0.5">Kelola klasifikasi kode aset 2-huruf (EL, FN, KD) dan sub-item jenis barang 2-digit (01-99)</p>
        </div>

        @if(auth()->check() && auth()->user()->role === 'super_admin')
            <div class="flex items-center gap-2.5">
                <button type="button"
                        @click="openCreateKategoriModal()"
                        class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold text-xs uppercase tracking-wider transition-all inline-flex items-center gap-1.5">
                    <i class="ti ti-folder-plus text-base text-slate-600"></i>
                    + Kategori Baru
                </button>
                <button type="button"
                        @click="openCreateBarangModal({{ $selectedKategori ? $selectedKategori->id : ($kategoriList->first()->id ?? 'null') }})"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-extrabold text-xs uppercase tracking-wider shadow-md shadow-emerald-600/20 transition-all inline-flex items-center gap-2">
                    <i class="ti ti-plus text-base"></i>
                    + Tambah Jenis Barang
                </button>
            </div>
        @endif
    </div>

    <!-- Stat Tiles Ringkasan Kategori -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Kategori</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100"><i class="ti ti-category text-lg"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">{{ $summary['total_kategori'] }}</p>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Klasifikasi 2-huruf utama</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Jenis Barang</span>
                <span class="p-2 rounded-xl bg-cyan-50 text-cyan-600 border border-cyan-100"><i class="ti ti-tags text-lg"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-cyan-600 mt-2">{{ $summary['total_barang'] }}</p>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Sub-item barang (01-99)</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Aset Terklasifikasi</span>
                <span class="p-2 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100"><i class="ti ti-box text-lg"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">{{ $summary['total_aset_terkategori'] }}</p>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Unit aset dalam inventaris</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kategori Terbanyak</span>
                <span class="p-2 rounded-xl bg-amber-50 text-amber-600 border border-amber-100"><i class="ti ti-trophy text-lg"></i></span>
            </div>
            <p class="text-xl sm:text-2xl font-extrabold text-amber-600 mt-2 truncate">
                {{ $summary['kategori_terbanyak']->nama_kategori ?? '-' }}
            </p>
            <span class="text-[11px] text-slate-400 mt-0.5 block">
                {{ $summary['kategori_terbanyak']->aset_count ?? 0 }} unit aset terdaftar
            </span>
        </div>
    </div>

    <!-- KARTU DAFTAR KATEGORI UTAMA (CATEGORY SWITCHER) -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <label class="text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                <i class="ti ti-layout-grid text-emerald-600 text-sm"></i>
                Pilih Kategori Klasifikasi
            </label>
            <span class="text-[11px] text-slate-400">Klik kartu untuk menyaring daftar jenis barang di bawah</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($kategoriList as $kat)
                @php
                    $isSelected = ($selectedKategori && $selectedKategori->id === $kat->id);
                    [$iconName, $colorClass] = match($kat->kode_kategori) {
                        'EL' => ['ti-device-laptop', 'text-cyan-600 bg-cyan-50 border-cyan-100'],
                        'FN' => ['ti-armchair', 'text-amber-600 bg-amber-50 border-amber-100'],
                        'KD' => ['ti-car', 'text-emerald-600 bg-emerald-50 border-emerald-100'],
                        'GD' => ['ti-building-skyscraper', 'text-blue-600 bg-blue-50 border-blue-100'],
                        'TN' => ['ti-map-2', 'text-emerald-700 bg-emerald-50 border-emerald-100'],
                        default => ['ti-category', 'text-indigo-600 bg-indigo-50 border-indigo-100'],
                    };
                @endphp
                <div class="p-5 rounded-3xl border transition-all duration-200 relative group flex flex-col justify-between {{ $isSelected ? 'bg-white border-emerald-500 shadow-md ring-2 ring-emerald-500/20' : 'bg-white border-slate-200/80 hover:border-slate-300 shadow-xs' }}">
                    <a href="{{ route('data.kategori.index', ['kategori_id' => $kat->id]) }}" class="block space-y-3">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <span class="p-3 rounded-2xl border flex items-center justify-center {{ $colorClass }}">
                                    <i class="ti {{ $iconName }} text-2xl"></i>
                                </span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-extrabold text-sm px-2 py-0.5 rounded-lg {{ $isSelected ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-800' }}">
                                            {{ $kat->kode_kategori }}
                                        </span>
                                        <h3 class="font-extrabold text-slate-900 text-base">{{ $kat->nama_kategori }}</h3>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-1 line-clamp-1">{{ $kat->keterangan ?: 'Tidak ada keterangan khusus' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-3 border-t border-slate-100 text-xs">
                            <span class="text-slate-500 font-semibold flex items-center gap-1">
                                <i class="ti ti-tags text-slate-400"></i>
                                {{ $kat->barang_count }} Jenis Barang
                            </span>
                            <span class="px-2 py-0.5 rounded-md font-extrabold text-[11px] {{ $kat->aset_count > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                {{ $kat->aset_count }} Unit Aset
                            </span>
                        </div>
                    </a>

                    @if(auth()->check() && auth()->user()->role === 'super_admin')
                        <div class="flex items-center justify-end gap-1.5 pt-3 mt-2 border-t border-slate-100">
                            <button type="button"
                                    @click="openEditKategoriModal({{ Js::from($kat) }})"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-700 hover:bg-emerald-50 transition-colors"
                                    title="Edit Kategori">
                                <i class="ti ti-pencil text-sm"></i>
                            </button>
                            <form method="POST" action="{{ route('data.kategori.destroy', $kat->id) }}" class="inline"
                                  onsubmit="return confirm('Hapus Kategori [{{ $kat->kode_kategori }}] {{ $kat->nama_kategori }} beserta sub-barangnya?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                        title="Hapus Kategori">
                                    <i class="ti ti-trash text-sm"></i>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- TABEL DAFTAR JENIS BARANG (SUB-ITEMS) -->
    <div class="p-6 bg-white rounded-3xl border border-slate-200/80 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-xs">
                    <i class="ti ti-tags text-sm"></i>
                </span>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                        <span>Daftar Jenis Barang</span>
                        @if($selectedKategori)
                            <span class="font-mono font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-md text-xs">
                                Kategori: {{ $selectedKategori->nama_kategori }} ({{ $selectedKategori->kode_kategori }})
                            </span>
                        @endif
                    </h3>
                    <p class="text-[11px] text-slate-400">Kode 2-digit pembentuk komponen ke-2 nomor aset (contoh: {{ $selectedKategori ? $selectedKategori->kode_kategori : 'EL' }}01)</p>
                </div>
            </div>

            <!-- Filter & Pencarian Form -->
            <form method="GET" action="{{ route('data.kategori.index') }}" class="flex flex-wrap items-center gap-2.5">
                @if($selectedKategori)
                    <input type="hidden" name="kategori_id" value="{{ $selectedKategori->id }}">
                @endif

                <div class="relative min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / kode barang..."
                           class="w-full pl-9 pr-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <i class="ti ti-search absolute left-3 top-2.5 text-slate-400 text-sm"></i>
                </div>

                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                    Cari
                </button>
                @if(request()->has('search'))
                    <a href="{{ route('data.kategori.index', ['kategori_id' => $selectedKategori ? $selectedKategori->id : null]) }}" class="px-3 py-2 text-xs font-bold text-rose-600 hover:text-rose-700">
                        Reset
                    </a>
                @endif

                @if(auth()->check() && auth()->user()->role === 'super_admin' && $selectedKategori)
                    <button type="button"
                            @click="openCreateBarangModal({{ $selectedKategori->id }})"
                            class="px-3.5 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 font-extrabold text-xs inline-flex items-center gap-1.5 transition-colors">
                        <i class="ti ti-plus text-xs"></i>
                        <span>Tambah di {{ $selectedKategori->kode_kategori }}</span>
                    </button>
                @endif
            </form>
        </div>

        <!-- Tabel Data Barang -->
        <div class="overflow-x-auto rounded-2xl border border-slate-200/80">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 uppercase tracking-wider font-extrabold text-[11px]">
                    <tr>
                        <th class="py-3.5 px-4">Kode Gabungan</th>
                        <th class="py-3.5 px-4">Kode Digit</th>
                        <th class="py-3.5 px-4">Kategori Induk</th>
                        <th class="py-3.5 px-4">Nama Jenis Barang</th>
                        <th class="py-3.5 px-4">Keterangan</th>
                        <th class="py-3.5 px-4 text-center">Unit Aset</th>
                        @if(auth()->check() && auth()->user()->role === 'super_admin')
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($barangList as $item)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <!-- Kode Gabungan (EL01, etc) -->
                            <td class="py-3.5 px-4">
                                <span class="font-mono font-extrabold text-emerald-800 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg text-xs">
                                    {{ $item->kategori->kode_kategori }}{{ $item->kode_barang }}
                                </span>
                            </td>

                            <!-- Kode Digit -->
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-700">
                                {{ $item->kode_barang }}
                            </td>

                            <!-- Kategori Induk -->
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-cyan-50 text-cyan-800 border border-cyan-200">
                                    {{ $item->kategori->nama_kategori }} ({{ $item->kategori->kode_kategori }})
                                </span>
                            </td>

                            <!-- Nama Barang -->
                            <td class="py-3.5 px-4">
                                <p class="font-bold text-slate-900 text-[13px]">{{ $item->nama_barang }}</p>
                            </td>

                            <!-- Keterangan -->
                            <td class="py-3.5 px-4 text-slate-500">
                                {{ $item->keterangan ?: '-' }}
                            </td>

                            <!-- Jumlah Aset -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-1 rounded-lg font-extrabold text-xs {{ $item->aset_count > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $item->aset_count }} Unit
                                </span>
                            </td>

                            <!-- Aksi -->
                            @if(auth()->check() && auth()->user()->role === 'super_admin')
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button"
                                                @click="openEditBarangModal({{ Js::from($item) }})"
                                                class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 border border-slate-200 inline-flex items-center justify-center transition-colors"
                                                title="Edit Barang">
                                            <i class="ti ti-pencil text-sm"></i>
                                        </button>

                                        <form method="POST" action="{{ route('data.kategori.barang.destroy', $item->id) }}" class="inline"
                                              onsubmit="return confirm('Hapus Jenis Barang [{{ $item->kategori->kode_kategori }}{{ $item->kode_barang }}] {{ $item->nama_barang }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 border border-slate-200 inline-flex items-center justify-center transition-colors"
                                                    title="Hapus Barang">
                                                <i class="ti ti-trash text-sm"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                <i class="ti ti-tags-off text-3xl block mb-1 text-slate-300"></i>
                                Belum ada jenis barang pada kategori ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginasi -->
        <div class="pt-2">
            {{ $barangList->links() }}
        </div>
    </div>

    <!-- MODAL FORM: TAMBAH / EDIT KATEGORI -->
    <div x-show="modalKategoriOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
         @keydown.escape.window="modalKategoriOpen = false">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 sm:p-7 space-y-5 my-8"
             @click.outside="modalKategoriOpen = false">

            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <i class="ti text-lg" :class="isEditKategori ? 'ti-pencil' : 'ti-folder-plus'"></i>
                    </span>
                    <h3 class="text-base font-extrabold text-slate-900" x-text="isEditKategori ? 'Edit Kategori' : 'Tambah Kategori Baru'"></h3>
                </div>
                <button type="button" @click="modalKategoriOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form :action="formKategoriAction" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="_method" value="PUT" :disabled="!isEditKategori">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Kategori (2 Huruf Kapital) <span class="text-rose-500">*</span></label>
                    <input type="text" name="kode_kategori" maxlength="2" required x-model="formKategoriData.kode_kategori"
                           placeholder="Contoh: EL, FN, KD, PR"
                           @input="formKategoriData.kode_kategori = formKategoriData.kode_kategori.toUpperCase()"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono font-extrabold uppercase">
                    <p class="text-[11px] text-slate-400 mt-1">Harus tepat 2 karakter huruf (misal EL untuk Elektronik, FN untuk Furniture).</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kategori <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama_kategori" required x-model="formKategoriData.nama_kategori"
                           placeholder="Contoh: Elektronik & IT"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-bold text-slate-800">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan <span class="text-slate-400">(Opsional)</span></label>
                    <textarea name="keterangan" rows="2" x-model="formKategoriData.keterangan"
                              placeholder="Deskripsi ruang lingkup kategori..."
                              class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"></textarea>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="modalKategoriOpen = false"
                            class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white text-xs font-extrabold uppercase tracking-wider shadow-md shadow-emerald-600/20 transition-all">
                        Simpan Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL FORM: TAMBAH / EDIT JENIS BARANG -->
    <div x-show="modalBarangOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
         @keydown.escape.window="modalBarangOpen = false">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 sm:p-7 space-y-5 my-8"
             @click.outside="modalBarangOpen = false">

            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <i class="ti text-lg" :class="isEditBarang ? 'ti-pencil' : 'ti-plus'"></i>
                    </span>
                    <h3 class="text-base font-extrabold text-slate-900" x-text="isEditBarang ? 'Edit Jenis Barang' : 'Tambah Jenis Barang Baru'"></h3>
                </div>
                <button type="button" @click="modalBarangOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form :action="formBarangAction" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="_method" value="PUT" :disabled="!isEditBarang">

                <!-- Kategori Induk Dropdown -->
                <div x-show="!isEditBarang">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Induk <span class="text-rose-500">*</span></label>
                    <select name="kategori_id" required x-model="formBarangData.kategori_id"
                            @change="updateNextCodeForKategori($event.target.value)"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-bold text-slate-700">
                        @foreach($kategoriList as $k)
                            <option value="{{ $k->id }}" data-kode="{{ $k->kode_kategori }}">{{ $k->nama_kategori }} ({{ $k->kode_kategori }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Kode Digit Barang (2 digit) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Barang (2 Digit) <span class="text-rose-500">*</span></label>
                        <input type="text" name="kode_barang" maxlength="2" required x-model="formBarangData.kode_barang"
                               placeholder="Contoh: 01, 14, 56"
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono font-bold">
                    </div>

                    <!-- Live Preview Kode Aset Gabungan -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Preview Kode Gabungan</label>
                        <div class="px-3.5 py-2 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-between">
                            <span class="font-mono font-extrabold text-sm text-emerald-800"
                                  x-text="getCategoryCode(formBarangData.kategori_id) + (formBarangData.kode_barang ? String(formBarangData.kode_barang).padStart(2, '0') : '01')"></span>
                            <span class="text-[10px] font-bold text-emerald-600 bg-emerald-100/70 px-1.5 py-0.5 rounded">Otomatis</span>
                        </div>
                    </div>
                </div>

                <!-- Nama Barang -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Jenis Barang <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama_barang" required x-model="formBarangData.nama_barang"
                           placeholder="Contoh: Drone Kamera, Kursi Kerja Ergonomis..."
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-bold text-slate-800">
                </div>

                <!-- Keterangan -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan <span class="text-slate-400">(Opsional)</span></label>
                    <textarea name="keterangan" rows="2" x-model="formBarangData.keterangan"
                              placeholder="Deskripsi jenis barang..."
                              class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"></textarea>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="modalBarangOpen = false"
                            class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white text-xs font-extrabold uppercase tracking-wider shadow-md shadow-emerald-600/20 transition-all">
                        Simpan Jenis Barang
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function kategoriManager() {
    return {
        modalKategoriOpen: false,
        modalBarangOpen: false,
        isEditKategori: false,
        isEditBarang: false,
        formKategoriAction: '{{ route('data.kategori.store') }}',
        formBarangAction: '',
        kategoriList: @js($kategoriList),
        formKategoriData: {
            kode_kategori: '',
            nama_kategori: '',
            keterangan: ''
        },
        formBarangData: {
            kategori_id: '{{ $selectedKategori ? $selectedKategori->id : ($kategoriList->first()->id ?? "") }}',
            kode_barang: '{{ $nextCode }}',
            nama_barang: '',
            keterangan: ''
        },

        openCreateKategoriModal() {
            this.isEditKategori = false;
            this.formKategoriAction = '{{ route('data.kategori.store') }}';
            this.formKategoriData = {
                kode_kategori: '',
                nama_kategori: '',
                keterangan: ''
            };
            this.modalKategoriOpen = true;
        },

        openEditKategoriModal(kat) {
            this.isEditKategori = true;
            this.formKategoriAction = '{{ url('data/kategori') }}/' + kat.id;
            this.formKategoriData = {
                kode_kategori: kat.kode_kategori,
                nama_kategori: kat.nama_kategori,
                keterangan: kat.keterangan || ''
            };
            this.modalKategoriOpen = true;
        },

        async openCreateBarangModal(kategoriId) {
            this.isEditBarang = false;
            const katId = kategoriId || (this.kategoriList[0] ? this.kategoriList[0].id : '');
            this.formBarangAction = '/data/kategori/' + katId + '/barang';
            this.formBarangData = {
                kategori_id: katId,
                kode_barang: '01',
                nama_barang: '',
                keterangan: ''
            };

            if (katId) {
                await this.updateNextCodeForKategori(katId);
            }
            this.modalBarangOpen = true;
        },

        openEditBarangModal(item) {
            this.isEditBarang = true;
            this.formBarangAction = '{{ url('data/kategori/barang') }}/' + item.id;
            this.formBarangData = {
                kategori_id: item.kategori_id,
                kode_barang: item.kode_barang,
                nama_barang: item.nama_barang,
                keterangan: item.keterangan || ''
            };
            this.modalBarangOpen = true;
        },

        async updateNextCodeForKategori(kategoriId) {
            this.formBarangAction = '/data/kategori/' + kategoriId + '/barang';
            try {
                const res = await fetch('/data/kategori/' + kategoriId, {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data.next_kode_barang) {
                        this.formBarangData.kode_barang = data.next_kode_barang;
                    }
                }
            } catch (err) {
                console.error('Gagal mengambil next code barang:', err);
            }
        },

        getCategoryCode(kategoriId) {
            const kat = this.kategoriList.find(k => k.id == kategoriId);
            return kat ? kat.kode_kategori : 'EL';
        }
    };
}
window.kategoriManager = kategoriManager;

if (window.Alpine) {
    Alpine.data('kategoriManager', kategoriManager);
} else {
    document.addEventListener('alpine:init', () => {
        Alpine.data('kategoriManager', kategoriManager);
    });
}
</script>
@endpush
