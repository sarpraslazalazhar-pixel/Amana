@extends('layouts.app')

@section('title', 'Data Master Lokasi')
@section('header-title', 'Master Data: Lokasi Penempatan Aset')

@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    .leaflet-popup-content-wrapper {
        border-radius: 1rem;
        padding: 0.25rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    .leaflet-popup-content {
        margin: 0.75rem 1rem;
        font-family: inherit;
    }
    .custom-emerald-pin {
        background-color: #059669;
        border: 2px solid #ffffff;
        border-radius: 50%;
        color: white;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
    }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="lokasiManager">

    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Data Master Lokasi</h2>
            <p class="text-xs text-slate-500 mt-0.5">Pemetaan geografis gedung, kantor perwakilan, dan ruangan aset Al Azhar</p>
        </div>

        @if(auth()->check() && auth()->user()->role === 'super_admin')
            <button type="button"
                    @click="openCreateModal()"
                    class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-extrabold text-xs uppercase tracking-wider shadow-md shadow-emerald-600/20 transition-all inline-flex items-center gap-2">
                <i class="ti ti-plus text-base"></i>
                Tambah Lokasi Baru
            </button>
        @endif
    </div>

    <!-- Stat Tiles Ringkasan Lokasi -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Titik Lokasi</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100"><i class="ti ti-map-pin text-lg"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">{{ $summary['total_lokasi'] }}</p>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Seluruh unit ruangan & pos</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Gedung & Wilayah</span>
                <span class="p-2 rounded-xl bg-cyan-50 text-cyan-600 border border-cyan-100"><i class="ti ti-building text-lg"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">{{ $summary['total_gedung'] }}</p>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Pusat, KL, & KPW se-Indonesia</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Terpetakan GPS</span>
                <span class="p-2 rounded-xl bg-amber-50 text-amber-600 border border-amber-100"><i class="ti ti-crosshair text-lg"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-emerald-600 mt-2">{{ $summary['total_berkoordinat'] }}</p>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Memiliki koordinat presisi</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Aset di Lokasi</span>
                <span class="p-2 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100"><i class="ti ti-box text-lg"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">{{ $summary['total_aset_terpetakan'] }}</p>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Unit aset terdaftar</span>
        </div>
    </div>

    <!-- CARD PETA INTERAKTIF OVERVIEW LEAFLET -->
    <div class="p-6 bg-white rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2.5">
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200">
                    <i class="ti ti-map-2 text-lg"></i>
                </span>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wide">Peta Sebaran Lokasi & Gedung Al Azhar</h3>
                    <p class="text-xs text-slate-400">Klik penanda pin untuk melihat info ruangan & jumlah aset</p>
                </div>
            </div>

            <!-- Quick Building Navigation Dropdown / Pills -->
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-xs font-bold text-slate-400 mr-1">Fokus Gedung:</span>
                <button type="button" @click="flyToLocation(-6.309315, 106.772520, 16)"
                        class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 transition-colors">
                    🏢 Cirendeu
                </button>
                <button type="button" @click="flyToLocation(-6.238210, 106.801530, 16)"
                        class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 transition-colors">
                    🕌 Kebayoran
                </button>
                <button type="button" @click="flyToLocation(-6.398540, 106.764510, 16)"
                        class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 transition-colors">
                    🎓 RGI Sawangan
                </button>
                <button type="button" @click="flyToLocation(-7.331200, 112.721500, 13)"
                        class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 transition-colors">
                    📍 Surabaya
                </button>
                <button type="button" @click="resetIndonesiaView()"
                        class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    🇮🇩 Seluruh Indonesia
                </button>
            </div>
        </div>

        <!-- Peta Container -->
        <div id="overview-map" class="w-full h-[400px] rounded-2xl border border-slate-200 shadow-inner z-10 relative overflow-hidden"></div>
    </div>

    <!-- TABEL DATA MASTER LOKASI -->
    <div class="p-6 bg-white rounded-3xl border border-slate-200/80 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-xs">
                    <i class="ti ti-list text-sm"></i>
                </span>
                <h3 class="text-sm font-extrabold text-slate-900">Daftar Master Lokasi</h3>
            </div>

            <!-- Filter & Pencarian Form -->
            <form method="GET" action="{{ route('data.lokasi.index') }}" class="flex flex-wrap items-center gap-2.5">
                <select name="gedung" onchange="this.form.submit()"
                        class="px-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-semibold text-slate-700">
                    <option value="">-- Semua Gedung / Wilayah --</option>
                    @foreach($gedungList as $g)
                        <option value="{{ $g }}" {{ request('gedung') === $g ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select>

                <div class="relative min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / kode / alamat..."
                           class="w-full pl-9 pr-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <i class="ti ti-search absolute left-3 top-2.5 text-slate-400 text-sm"></i>
                </div>

                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                    Filter
                </button>
                @if(request()->hasAny(['gedung', 'search']))
                    <a href="{{ route('data.lokasi.index') }}" class="px-3 py-2 text-xs font-bold text-rose-600 hover:text-rose-700">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <!-- Tabel Data -->
        <div class="overflow-x-auto rounded-2xl border border-slate-200/80">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-500 uppercase tracking-wider font-extrabold text-[11px]">
                    <tr>
                        <th class="py-3.5 px-4">Kode</th>
                        <th class="py-3.5 px-4">Nama Lokasi / Ruangan</th>
                        <th class="py-3.5 px-4">Gedung / Wilayah</th>
                        <th class="py-3.5 px-4">Koordinat GPS</th>
                        <th class="py-3.5 px-4">Alamat Lengkap</th>
                        <th class="py-3.5 px-4 text-center">Jml Aset</th>
                        @if(auth()->check() && auth()->user()->role === 'super_admin')
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($lokasiList as $item)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <!-- Kode Lokasi -->
                            <td class="py-3 px-4">
                                <span class="font-mono font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md text-xs">
                                    {{ $item->kode_lokasi }}
                                </span>
                            </td>

                            <!-- Nama Lokasi -->
                            <td class="py-3 px-4 font-bold text-slate-900">
                                {{ $item->nama_lokasi }}
                            </td>

                            <!-- Gedung -->
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $item->gedung ?? 'Lainnya' }}
                                </span>
                            </td>

                            <!-- Koordinat GPS -->
                            <td class="py-3 px-4">
                                @if($item->latitude && $item->longitude)
                                    <button type="button"
                                            @click="flyToLocation({{ $item->latitude }}, {{ $item->longitude }}, 17)"
                                            class="inline-flex items-center gap-1 font-mono text-[11px] text-cyan-700 hover:text-cyan-800 font-semibold hover:underline bg-cyan-50 px-2 py-0.5 rounded border border-cyan-200">
                                        <i class="ti ti-map-pin text-xs"></i>
                                        {{ number_format($item->latitude, 4) }}, {{ number_format($item->longitude, 4) }}
                                    </button>
                                @else
                                    <span class="text-slate-400 text-[11px] italic">Belum diatur</span>
                                @endif
                            </td>

                            <!-- Alamat -->
                            <td class="py-3 px-4 text-slate-600 max-w-xs truncate text-[11px]" title="{{ $item->alamat_lengkap }}">
                                {{ $item->alamat_lengkap ?: '-' }}
                            </td>

                            <!-- Jumlah Aset -->
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold {{ $item->aset_count > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $item->aset_count }} Unit
                                </span>
                            </td>

                            <!-- Aksi -->
                            @if(auth()->check() && auth()->user()->role === 'super_admin')
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button"
                                                @click="openEditModal({{ Js::from($item) }})"
                                                class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 border border-slate-200 inline-flex items-center justify-center transition-colors"
                                                title="Edit Lokasi">
                                            <i class="ti ti-pencil text-sm"></i>
                                        </button>

                                        <form method="POST" action="{{ route('data.lokasi.destroy', $item->id) }}" class="inline"
                                              onsubmit="return confirm('Hapus lokasi [{{ $item->kode_lokasi }}] {{ $item->nama_lokasi }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 border border-slate-200 inline-flex items-center justify-center transition-colors"
                                                    title="Hapus Lokasi">
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
                                <i class="ti ti-map-pin-off text-3xl block mb-1 text-slate-300"></i>
                                Tidak ada data lokasi yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginasi -->
        <div class="pt-2">
            {{ $lokasiList->links() }}
        </div>
    </div>

    <!-- MODAL FORM: TAMBAH / EDIT LOKASI DENGAN PINPOINT PICKER -->
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
         @keydown.escape.window="modalOpen = false">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-2xl w-full p-6 sm:p-7 space-y-5 my-8"
             @click.outside="modalOpen = false">

            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <i class="ti text-lg" :class="isEdit ? 'ti-pencil' : 'ti-plus'"></i>
                    </span>
                    <h3 class="text-base font-extrabold text-slate-900" x-text="isEdit ? 'Edit Data Lokasi' : 'Tambah Lokasi Baru'"></h3>
                </div>
                <button type="button" @click="modalOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form :action="formAction" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="_method" value="PUT" :disabled="!isEdit">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Kode Lokasi -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Lokasi (3 Digit) <span class="text-rose-500">*</span></label>
                        <input type="text" name="kode_lokasi" required x-model="formData.kode_lokasi" placeholder="Contoh: 111"
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono font-bold">
                    </div>

                    <!-- Gedung / Kategori Wilayah -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Gedung / Wilayah <span class="text-slate-400">(Opsional)</span></label>
                        <input type="text" name="gedung" x-model="formData.gedung" placeholder="Contoh: Kantor Cirendeu / RGI Sawangan"
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    </div>

                    <!-- Nama Lokasi -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lokasi / Ruangan <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama_lokasi" required x-model="formData.nama_lokasi" placeholder="Contoh: Lobi Utama Lantai 1"
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-semibold text-slate-800">
                    </div>

                    <!-- Alamat Lengkap (Auto Synchronized with Map Coordinates) -->
                    <div class="sm:col-span-2 space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-semibold text-slate-700">Alamat Lengkap <span class="text-slate-400">(Otomatis tersinkronisasi dengan titik peta)</span></label>
                            <div class="flex items-center gap-2">
                                <span x-show="isGeocoding" x-cloak class="text-[11px] font-bold text-emerald-600 inline-flex items-center gap-1 animate-pulse">
                                    <i class="ti ti-loader-2 animate-spin"></i> Menyesuaikan alamat...
                                </span>
                                <button type="button" @click="fetchReverseGeocode(formData.latitude, formData.longitude)"
                                        class="text-[10px] font-bold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 px-2 py-0.5 rounded border border-emerald-200 inline-flex items-center gap-1 transition-colors">
                                    <i class="ti ti-refresh text-xs"></i> Ambil dari Titik Peta
                                </button>
                            </div>
                        </div>
                        <textarea name="alamat_lengkap" rows="2" x-model="formData.alamat_lengkap" placeholder="Alamat akan terisi otomatis saat pin peta digeser..."
                                  class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-slate-800"></textarea>
                    </div>
                </div>

                <!-- INTERACTIVE PINPOINT MAP PICKER -->
                <div class="space-y-3 pt-2 border-t border-slate-100">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-extrabold text-slate-800 flex items-center gap-1.5">
                            <i class="ti ti-map-pin text-emerald-600 text-sm"></i>
                            Tentukan Titik Koordinat GPS pada Peta
                        </label>
                        <button type="button" @click="useCurrentLocation()"
                                class="text-[11px] font-bold text-emerald-700 hover:text-emerald-800 inline-flex items-center gap-1 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200 transition-colors">
                            <i class="ti ti-current-location text-xs"></i>
                            Lokasi Saya Saat Ini
                        </button>
                    </div>

                    <!-- SEARCH ADDRESS / PLACE ON MAP -->
                    <div class="relative" @click.outside="searchResults = []">
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <input type="text" x-model="searchAddressQuery"
                                       @keydown.enter.prevent="searchLocationByAddress()"
                                       placeholder="Ketik nama jalan / gedung / kota untuk cari di peta (misal: Masjid Agung Al Azhar)..."
                                       class="w-full pl-9 pr-8 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-slate-800">
                                <i class="ti ti-search absolute left-3 top-2.5 text-slate-400 text-sm"></i>
                                <button type="button" x-show="searchAddressQuery" @click="searchAddressQuery = ''; searchResults = []"
                                        class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600">
                                    <i class="ti ti-x text-xs"></i>
                                </button>
                            </div>
                            <button type="button" @click="searchLocationByAddress()"
                                    :disabled="isSearchingAddress"
                                    class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-bold transition-all inline-flex items-center gap-1.5 shadow-xs flex-shrink-0">
                                <i class="ti" :class="isSearchingAddress ? 'ti-loader-2 animate-spin' : 'ti-map-search'"></i>
                                <span>Cari di Peta</span>
                            </button>
                        </div>

                        <!-- Dropdown List Hasil Pencarian Alamat -->
                        <div x-show="searchResults.length > 0" x-cloak
                             class="absolute z-20 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-2xl shadow-xl max-h-48 overflow-y-auto divide-y divide-slate-100">
                            <template x-for="(result, idx) in searchResults" :key="idx">
                                <button type="button" @click="selectSearchResult(result)"
                                        class="w-full text-left p-2.5 hover:bg-emerald-50 transition-colors flex items-start gap-2.5">
                                    <i class="ti ti-map-pin text-emerald-600 mt-0.5 flex-shrink-0 text-sm"></i>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-800 truncate" x-text="result.name || result.display_name.split(',')[0]"></p>
                                        <p class="text-[10px] text-slate-500 line-clamp-1" x-text="result.display_name"></p>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-400">Klik di peta atau geser penanda pin hijau untuk memilih koordinat presisi. Alamat lengkap akan otomatis disesuaikan.</p>

                    <!-- Leaflet Picker Container -->
                    <div id="picker-map" class="w-full h-56 rounded-2xl border border-slate-200 shadow-inner z-10 relative overflow-hidden"></div>

                    <!-- Latitude & Longitude Numeric Inputs -->
                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-0.5">Latitude</label>
                            <input type="number" step="any" name="latitude" x-model.number="formData.latitude"
                                   @change="syncManualCoordinates()"
                                   class="w-full px-3 py-1.5 text-xs font-mono rounded-lg border border-slate-200 bg-slate-50 focus:bg-white">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-0.5">Longitude</label>
                            <input type="number" step="any" name="longitude" x-model.number="formData.longitude"
                                   @change="syncManualCoordinates()"
                                   class="w-full px-3 py-1.5 text-xs font-mono rounded-lg border border-slate-200 bg-slate-50 focus:bg-white">
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="modalOpen = false"
                            class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white text-xs font-extrabold uppercase tracking-wider shadow-md shadow-emerald-600/20 transition-all">
                        Simpan Lokasi
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function lokasiManager() {
    return {
        modalOpen: false,
        isEdit: false,
        editId: null,
        isGeocoding: false,
        searchAddressQuery: '',
        isSearchingAddress: false,
        searchResults: [],
        formAction: '{{ route('data.lokasi.store') }}',
        formData: {
            kode_lokasi: '',
            nama_lokasi: '',
            gedung: '',
            alamat_lengkap: '',
            latitude: -6.309315,
            longitude: 106.772520
        },
        pickerMap: null,
        pickerMarker: null,
        overviewMap: null,
        markers: @js($markersData),

        init() {
            this.$nextTick(() => {
                this.initOverviewMap();
            });

            // Cleanup when leaving page via SPA Navigator
            document.addEventListener('amana:before-page-unload', () => {
                this.destroyMaps();
            }, { once: true });
        },

        destroy() {
            this.destroyMaps();
        },

        destroyMaps() {
            if (this.overviewMap) {
                try { this.overviewMap.remove(); } catch (e) {}
                this.overviewMap = null;
            }
            if (this.pickerMap) {
                try { this.pickerMap.remove(); } catch (e) {}
                this.pickerMap = null;
            }
            const overviewContainer = document.getElementById('overview-map');
            if (overviewContainer && overviewContainer._leaflet_id) {
                delete overviewContainer._leaflet_id;
                overviewContainer.innerHTML = '';
            }
            const pickerContainer = document.getElementById('picker-map');
            if (pickerContainer && pickerContainer._leaflet_id) {
                delete pickerContainer._leaflet_id;
                pickerContainer.innerHTML = '';
            }
        },

        initOverviewMap() {
            const overviewContainer = document.getElementById('overview-map');
            if (!overviewContainer) return;

            // Clean up any stale map instance or leaflet ID
            if (this.overviewMap) {
                try { this.overviewMap.remove(); } catch (e) {}
                this.overviewMap = null;
            }
            if (overviewContainer._leaflet_id) {
                delete overviewContainer._leaflet_id;
                overviewContainer.innerHTML = '';
            }

            if (typeof L === 'undefined') {
                console.warn('Leaflet is not available.');
                return;
            }

            this.overviewMap = L.map('overview-map').setView([-6.309315, 106.772520], 11);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap contributors'
            }).addTo(this.overviewMap);

            const customIcon = L.divIcon({
                className: 'custom-emerald-pin',
                html: '<i class="ti ti-map-pin text-sm"></i>',
                iconSize: [28, 28],
                iconAnchor: [14, 28],
                popupAnchor: [0, -28]
            });

            const markerGroup = L.featureGroup();

            this.markers.forEach(item => {
                if (item.lat && item.lng) {
                    const marker = L.marker([item.lat, item.lng], { icon: customIcon });

                    const popupContent = `
                        <div class="space-y-1 text-xs">
                            <div class="flex items-center gap-1.5">
                                <span class="font-mono font-bold text-emerald-800 bg-emerald-100 px-1.5 py-0.2 rounded text-[10px]">${item.kode_lokasi}</span>
                                <span class="font-extrabold text-slate-900">${item.nama_lokasi}</span>
                            </div>
                            <p class="text-[11px] font-semibold text-slate-600">🏢 Gedung: ${item.gedung}</p>
                            <p class="text-[10px] text-slate-500 leading-tight">${item.alamat_lengkap}</p>
                            <div class="pt-1 flex items-center justify-between border-t border-slate-100 mt-1">
                                <span class="text-[10px] text-slate-400">Total Aset:</span>
                                <span class="px-1.5 py-0.5 rounded font-extrabold text-[10px] ${item.aset_count > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'}">${item.aset_count} Unit</span>
                            </div>
                        </div>
                    `;

                    marker.bindPopup(popupContent);
                    marker.addTo(markerGroup);
                }
            });

            markerGroup.addTo(this.overviewMap);

            // Staggered resize invalidation to ensure proper rendering after SPA transitions
            [50, 150, 300, 500, 800].forEach(delay => {
                setTimeout(() => {
                    if (this.overviewMap) {
                        this.overviewMap.invalidateSize();
                    }
                }, delay);
            });
        },

        openCreateModal() {
            this.isEdit = false;
            this.editId = null;
            this.isGeocoding = false;
            this.searchAddressQuery = '';
            this.searchResults = [];
            this.formAction = '{{ route('data.lokasi.store') }}';
            this.formData = {
                kode_lokasi: '',
                nama_lokasi: '',
                gedung: '',
                alamat_lengkap: '',
                latitude: -6.309315,
                longitude: 106.772520
            };
            this.modalOpen = true;
            this.$nextTick(() => {
                this.initPickerMap(-6.309315, 106.772520);
            });
        },

        openEditModal(item) {
            this.isEdit = true;
            this.editId = item.id;
            this.isGeocoding = false;
            this.searchAddressQuery = '';
            this.searchResults = [];
            this.formAction = '/data/lokasi/' + item.id;
            let lat = item.latitude ? parseFloat(item.latitude) : -6.309315;
            let lng = item.longitude ? parseFloat(item.longitude) : 106.772520;
            this.formData = {
                kode_lokasi: item.kode_lokasi,
                nama_lokasi: item.nama_lokasi,
                gedung: item.gedung || '',
                alamat_lengkap: item.alamat_lengkap || '',
                latitude: lat,
                longitude: lng
            };
            this.modalOpen = true;
            this.$nextTick(() => {
                this.initPickerMap(lat, lng);
            });
        },

        async searchLocationByAddress() {
            const q = (this.searchAddressQuery || '').trim();
            if (!q) return;
            this.isSearchingAddress = true;
            try {
                const res = await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&q=${encodeURIComponent(q)}&countrycodes=id&limit=5`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.searchResults = data || [];
                    if (this.searchResults.length === 1) {
                        this.selectSearchResult(this.searchResults[0]);
                    } else if (this.searchResults.length === 0) {
                        alert('Tidak ditemukan hasil pencarian di peta untuk: ' + q);
                    }
                }
            } catch (err) {
                console.warn('Gagal mencari alamat:', err);
            } finally {
                this.isSearchingAddress = false;
            }
        },

        selectSearchResult(result) {
            const lat = parseFloat(result.lat);
            const lng = parseFloat(result.lon);
            this.formData.latitude = parseFloat(lat.toFixed(7));
            this.formData.longitude = parseFloat(lng.toFixed(7));
            this.formData.alamat_lengkap = result.display_name;
            this.searchResults = [];

            if (this.pickerMap && this.pickerMarker) {
                this.pickerMap.setView([lat, lng], 17);
                this.pickerMarker.setLatLng([lat, lng]);
            }
        },

        async fetchReverseGeocode(lat, lng) {
            if (!lat || !lng) return;
            this.isGeocoding = true;
            try {
                const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data && data.display_name) {
                        this.formData.alamat_lengkap = data.display_name;
                    }
                }
            } catch (err) {
                console.warn('Gagal reverse geocode:', err);
            } finally {
                this.isGeocoding = false;
            }
        },

        syncManualCoordinates() {
            const lat = parseFloat(this.formData.latitude);
            const lng = parseFloat(this.formData.longitude);
            if (!isNaN(lat) && !isNaN(lng) && this.pickerMap && this.pickerMarker) {
                this.pickerMarker.setLatLng([lat, lng]);
                this.pickerMap.setView([lat, lng]);
                this.fetchReverseGeocode(lat, lng);
            }
        },

        initPickerMap(lat, lng) {
            const container = document.getElementById('picker-map');
            if (!container) return;

            if (this.pickerMap) {
                try { this.pickerMap.remove(); } catch (e) {}
                this.pickerMap = null;
            }
            if (container._leaflet_id) {
                delete container._leaflet_id;
                container.innerHTML = '';
            }

            if (typeof L === 'undefined') {
                console.warn('Leaflet is not available.');
                return;
            }

            const customPickerIcon = L.divIcon({
                className: 'custom-emerald-pin',
                html: '<i class="ti ti-map-pin text-base"></i>',
                iconSize: [32, 32],
                iconAnchor: [16, 32],
                popupAnchor: [0, -32]
            });

            this.pickerMap = L.map('picker-map').setView([lat, lng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap contributors'
            }).addTo(this.pickerMap);

            this.pickerMarker = L.marker([lat, lng], { draggable: true, icon: customPickerIcon }).addTo(this.pickerMap);

            this.pickerMarker.on('dragend', (e) => {
                const position = e.target.getLatLng();
                this.formData.latitude = parseFloat(position.lat.toFixed(7));
                this.formData.longitude = parseFloat(position.lng.toFixed(7));
                this.fetchReverseGeocode(this.formData.latitude, this.formData.longitude);
            });

            this.pickerMap.on('click', (e) => {
                const latlng = e.latlng;
                this.pickerMarker.setLatLng(latlng);
                this.formData.latitude = parseFloat(latlng.lat.toFixed(7));
                this.formData.longitude = parseFloat(latlng.lng.toFixed(7));
                this.fetchReverseGeocode(this.formData.latitude, this.formData.longitude);
            });

            [50, 150, 300, 500].forEach(delay => {
                setTimeout(() => {
                    if (this.pickerMap) {
                        this.pickerMap.invalidateSize();
                    }
                }, delay);
            });
        },

        useCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition((pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    this.formData.latitude = parseFloat(lat.toFixed(7));
                    this.formData.longitude = parseFloat(lng.toFixed(7));
                    if (this.pickerMap && this.pickerMarker) {
                        this.pickerMap.setView([lat, lng], 16);
                        this.pickerMarker.setLatLng([lat, lng]);
                    }
                    this.fetchReverseGeocode(this.formData.latitude, this.formData.longitude);
                }, (err) => {
                    alert('Gagal mendeteksi lokasi: ' + err.message);
                });
            } else {
                alert('Browser Anda tidak mendukung geolokasi.');
            }
        },

        flyToLocation(lat, lng, zoom = 16) {
            if (this.overviewMap) {
                this.overviewMap.flyTo([lat, lng], zoom, {
                    duration: 1.5
                });
                const el = document.getElementById('overview-map');
                if (el) {
                    window.scrollTo({ top: el.offsetTop - 100, behavior: 'smooth' });
                }
            }
        },

        resetIndonesiaView() {
            if (this.overviewMap) {
                this.overviewMap.setView([-2.5, 118.0], 5);
            }
        }
    };
}
window.lokasiManager = lokasiManager;

if (window.Alpine) {
    Alpine.data('lokasiManager', lokasiManager);
} else {
    document.addEventListener('alpine:init', () => {
        Alpine.data('lokasiManager', lokasiManager);
    });
}
</script>
@endpush
