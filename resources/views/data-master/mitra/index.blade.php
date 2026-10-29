@extends('layouts.app')

@section('title', 'Data Master Toko & Mitra')
@section('header-title', 'Master Data: Toko & Mitra')

@section('content')
<div class="space-y-6" x-data="mitraManager">

    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Data Master Toko & Mitra</h2>
            <p class="text-xs text-slate-500 mt-0.5">Daftar toko, distributor, dan penyedia jasa pengadaan aset Al Azhar</p>
        </div>

        <button type="button"
                @click="openCreateModal()"
                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-extrabold text-xs uppercase tracking-wider shadow-md shadow-emerald-600/20 transition-all inline-flex items-center gap-2">
            <i class="ti ti-plus text-base"></i>
            Tambah Mitra Baru
        </button>
    </div>

    <!-- Error Notification jika delete diblokir -->
    @if($errors->has('error'))
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-2">
            <i class="ti ti-alert-triangle text-base text-rose-600 flex-shrink-0"></i>
            <span>{{ $errors->first('error') }}</span>
        </div>
    @endif

    <!-- Stat Tiles Ringkasan Mitra -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Mitra</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100"><i class="ti ti-building-store text-lg"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">{{ $summary['total'] }}</p>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Seluruh mitra terdaftar</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mitra Dipakai</span>
                <span class="p-2 rounded-xl bg-cyan-50 text-cyan-600 border border-cyan-100"><i class="ti ti-link text-lg"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">{{ $summary['terpakai'] }}</p>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Terhubung dengan aset</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mitra Aktif</span>
                <span class="p-2 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100"><i class="ti ti-check text-lg"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-emerald-600 mt-2">{{ $summary['aktif'] }}</p>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Status aktif untuk pengadaan</span>
        </div>
    </div>

    <!-- TABEL DATA MASTER MITRA -->
    <div class="p-6 bg-white rounded-3xl border border-slate-200/80 shadow-sm space-y-5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-xs">
                    <i class="ti ti-list text-sm"></i>
                </span>
                <h3 class="text-sm font-extrabold text-slate-900">Daftar Master Toko & Mitra</h3>
            </div>

            <!-- Pencarian & Filter Form -->
            <form method="GET" action="{{ route('data.mitra.index') }}" class="flex flex-wrap items-center gap-2.5">
                <div class="relative min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, kontak, telepon..."
                           class="w-full pl-9 pr-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <i class="ti ti-search absolute left-3 top-2.5 text-slate-400 text-sm"></i>
                </div>

                <select name="jenis" class="px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <option value="">-- Semua Jenis --</option>
                    <option value="toko" {{ request('jenis') === 'toko' ? 'selected' : '' }}>Toko</option>
                    <option value="distributor" {{ request('jenis') === 'distributor' ? 'selected' : '' }}>Distributor</option>
                    <option value="jasa" {{ request('jenis') === 'jasa' ? 'selected' : '' }}>Jasa / Servis</option>
                </select>

                <select name="status" class="px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <option value="">-- Semua Status --</option>
                    <option value="aktif" {{ request('status') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="nonaktif" {{ request('status') === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                </select>

                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                    Filter
                </button>
                @if(request()->filled('search') || request()->filled('jenis') || request()->filled('status'))
                    <a href="{{ route('data.mitra.index') }}" class="px-3 py-2 text-xs font-bold text-rose-600 hover:text-rose-700">
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
                        <th class="py-3.5 px-4">Nama Mitra</th>
                        <th class="py-3.5 px-4">Jenis</th>
                        <th class="py-3.5 px-4">Kontak & Telepon</th>
                        <th class="py-3.5 px-4 text-center">Jml Aset</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Dibuat</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($mitraList as $item)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <!-- Nama Mitra -->
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center gap-1.5 font-bold text-slate-900">
                                    <span class="p-1 rounded-md bg-slate-100 text-slate-500 border border-slate-200"><i class="ti ti-building-store text-xs"></i></span>
                                    {{ $item->nama }}
                                </span>
                                @if($item->alamat)
                                    <span class="block text-[11px] text-slate-400 truncate max-w-xs mt-0.5" title="{{ $item->alamat }}">{{ $item->alamat }}</span>
                                @endif
                            </td>

                            <!-- Jenis -->
                            <td class="py-3 px-4">
                                @if($item->jenis === 'distributor')
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-cyan-50 text-cyan-700 border border-cyan-200">Distributor</span>
                                @elseif($item->jenis === 'jasa')
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">Jasa / Servis</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Toko</span>
                                @endif
                            </td>

                            <!-- Kontak & Telepon -->
                            <td class="py-3 px-4 text-slate-600 text-[11px]">
                                @if($item->nama_kontak || $item->telepon)
                                    <div class="font-medium text-slate-800">{{ $item->nama_kontak ?: '-' }}</div>
                                    <div class="text-slate-400">{{ $item->telepon ?: '-' }}</div>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            <!-- Jumlah Aset -->
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold {{ $item->aset_count > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $item->aset_count }} Unit
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="py-3 px-4">
                                @if($item->status === 'aktif')
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-500 border border-slate-200">Nonaktif</span>
                                @endif
                            </td>

                            <!-- Dibuat -->
                            <td class="py-3 px-4 text-slate-600 text-[11px]">
                                {{ $item->created_at?->translatedFormat('d M Y') ?? '-' }}
                            </td>

                            <!-- Aksi -->
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button"
                                            @click="openEditModal({{ Js::from($item) }})"
                                            class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 border border-slate-200 inline-flex items-center justify-center transition-colors"
                                            title="Edit Mitra">
                                        <i class="ti ti-pencil text-sm"></i>
                                    </button>

                                    <form method="POST" action="{{ route('data.mitra.destroy', $item->id) }}" class="inline"
                                          onsubmit="return confirm('Hapus mitra [{{ $item->nama }}]?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 border border-slate-200 inline-flex items-center justify-center transition-colors"
                                                title="Hapus Mitra">
                                            <i class="ti ti-trash text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                <i class="ti ti-building-store text-3xl block mb-1 text-slate-300"></i>
                                Tidak ada data mitra yang sesuai dengan kriteria pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginasi -->
        <div class="pt-2">
            {{ $mitraList->links() }}
        </div>
    </div>

    <!-- MODAL FORM: TAMBAH / EDIT MITRA -->
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
         @keydown.escape.window="modalOpen = false">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 sm:p-7 space-y-5 my-8"
             @click.outside="modalOpen = false">

            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <i class="ti text-lg" :class="isEdit ? 'ti-pencil' : 'ti-plus'"></i>
                    </span>
                    <h3 class="text-base font-extrabold text-slate-900" x-text="isEdit ? 'Edit Data Mitra' : 'Tambah Mitra Baru'"></h3>
                </div>
                <button type="button" @click="modalOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form :action="formAction" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="_method" value="PUT" :disabled="!isEdit">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Toko / Mitra <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama" required maxlength="200" x-model="formData.nama" placeholder="Contoh: PT Aneka Komputerindo"
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-bold text-slate-800">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Mitra <span class="text-rose-500">*</span></label>
                        <select name="jenis" required x-model="formData.jenis"
                                class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                            <option value="toko">Toko</option>
                            <option value="distributor">Distributor</option>
                            <option value="jasa">Jasa / Servis</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Status <span class="text-rose-500">*</span></label>
                        <select name="status" required x-model="formData.status"
                                class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kontak / PIC Mitra</label>
                        <input type="text" name="nama_kontak" maxlength="150" x-model="formData.nama_kontak" placeholder="Contoh: Pak Budi"
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">No. Telepon / WhatsApp</label>
                        <input type="text" name="telepon" maxlength="30" x-model="formData.telepon" placeholder="Contoh: 0812-3456-7890"
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" maxlength="150" x-model="formData.email" placeholder="Contoh: info@aneka.co.id"
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Lengkap</label>
                        <textarea name="alamat" rows="2" x-model="formData.alamat" placeholder="Alamat kantor / toko..."
                                  class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"></textarea>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan / Keterangan</label>
                        <textarea name="keterangan" rows="2" x-model="formData.keterangan" placeholder="Catatan tambahan mitra..."
                                  class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"></textarea>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-3 pt-2 border-t border-slate-100">
                    <button type="button" @click="modalOpen = false"
                            class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white text-xs font-extrabold uppercase tracking-wider shadow-md shadow-emerald-600/20 transition-all"
                            x-text="isEdit ? 'Simpan Perubahan' : 'Simpan Mitra'">
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function mitraManager() {
    return {
        modalOpen: false,
        isEdit: false,
        editId: null,
        formAction: '{{ route('data.mitra.store') }}',
        formData: {
            nama: '',
            jenis: 'toko',
            nama_kontak: '',
            telepon: '',
            email: '',
            alamat: '',
            keterangan: '',
            status: 'aktif'
        },

        openCreateModal() {
            this.isEdit = false;
            this.editId = null;
            this.formAction = '{{ route('data.mitra.store') }}';
            this.formData = {
                nama: '',
                jenis: 'toko',
                nama_kontak: '',
                telepon: '',
                email: '',
                alamat: '',
                keterangan: '',
                status: 'aktif'
            };
            this.modalOpen = true;
        },

        openEditModal(item) {
            this.isEdit = true;
            this.editId = item.id;
            this.formAction = '/data/mitra/' + item.id;
            this.formData = {
                nama: item.nama || '',
                jenis: item.jenis || 'toko',
                nama_kontak: item.nama_kontak || '',
                telepon: item.telepon || '',
                email: item.email || '',
                alamat: item.alamat || '',
                keterangan: item.keterangan || '',
                status: item.status || 'aktif'
            };
            this.modalOpen = true;
        }
    };
}
window.mitraManager = mitraManager;

if (window.Alpine) {
    Alpine.data('mitraManager', mitraManager);
} else {
    document.addEventListener('alpine:init', () => {
        Alpine.data('mitraManager', mitraManager);
    });
}
</script>
@endpush
