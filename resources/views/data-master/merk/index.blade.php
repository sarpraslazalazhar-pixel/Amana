@extends('layouts.app')

@section('title', 'Data Master Merk')
@section('header-title', 'Master Data: Merk Aset')

@section('content')
<div class="space-y-6" x-data="merkManager">

    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Data Master Merk</h2>
            <p class="text-xs text-slate-500 mt-0.5">Daftar merk / brand aset yang digunakan pada seluruh unit Al Azhar</p>
        </div>

        <button type="button"
                @click="openCreateModal()"
                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-extrabold text-xs uppercase tracking-wider shadow-md shadow-emerald-600/20 transition-all inline-flex items-center gap-2">
            <i class="ti ti-plus text-base"></i>
            Tambah Merk Baru
        </button>
    </div>

    <!-- Stat Tiles Ringkasan Merk -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Merk</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100"><i class="ti ti-tag text-lg"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">{{ $summary['total_merk'] }}</p>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Seluruh merk terdaftar</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Merk Dipakai</span>
                <span class="p-2 rounded-xl bg-cyan-50 text-cyan-600 border border-cyan-100"><i class="ti ti-link text-lg"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">{{ $summary['merk_terpakai'] }}</p>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Terhubung dengan aset</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Belum Terpakai</span>
                <span class="p-2 rounded-xl bg-amber-50 text-amber-600 border border-amber-100"><i class="ti ti-tag-off text-lg"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-emerald-600 mt-2">{{ $summary['merk_tak_terpakai'] }}</p>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Dapat dihapus dengan aman</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Unit Aset</span>
                <span class="p-2 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100"><i class="ti ti-box text-lg"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">{{ $summary['total_aset_bermerk'] }}</p>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Aset berlabel merk</span>
        </div>
    </div>

    <!-- TABEL DATA MASTER MERK -->
    <div class="p-6 bg-white rounded-3xl border border-slate-200/80 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-xs">
                    <i class="ti ti-list text-sm"></i>
                </span>
                <h3 class="text-sm font-extrabold text-slate-900">Daftar Master Merk</h3>
            </div>

            <!-- Pencarian Form -->
            <form method="GET" action="{{ route('data.merk.index') }}" class="flex flex-wrap items-center gap-2.5">
                <div class="relative min-w-[220px]">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama merk..."
                           class="w-full pl-9 pr-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <i class="ti ti-search absolute left-3 top-2.5 text-slate-400 text-sm"></i>
                </div>

                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                    Filter
                </button>
                @if(request()->filled('search'))
                    <a href="{{ route('data.merk.index') }}" class="px-3 py-2 text-xs font-bold text-rose-600 hover:text-rose-700">
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
                        <th class="py-3.5 px-4">Nama Merk</th>
                        <th class="py-3.5 px-4 text-center">Jml Aset</th>
                        <th class="py-3.5 px-4">Status Pakai</th>
                        <th class="py-3.5 px-4">Dibuat</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($merkList as $item)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <!-- Nama Merk -->
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center gap-1.5 font-bold text-slate-900">
                                    <span class="p-1 rounded-md bg-slate-100 text-slate-500 border border-slate-200"><i class="ti ti-tag text-xs"></i></span>
                                    {{ $item->nama_merk }}
                                </span>
                            </td>

                            <!-- Jumlah Aset -->
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold {{ $item->aset_count > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $item->aset_count }} Unit
                                </span>
                            </td>

                            <!-- Status Pakai -->
                            <td class="py-3 px-4">
                                @if($item->aset_count > 0)
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-cyan-50 text-cyan-700 border border-cyan-200">Aktif Dipakai</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-500 border border-slate-200">Belum Terpakai</span>
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
                                            @click='openEditModal(@js(["id" => $item->id, "nama_merk" => $item->nama_merk]))'
                                            class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 border border-slate-200 inline-flex items-center justify-center transition-colors"
                                            title="Edit Merk">
                                        <i class="ti ti-pencil text-sm"></i>
                                    </button>

                                    <form method="POST" action="{{ route('data.merk.destroy', $item->id) }}" class="inline"
                                          onsubmit="return confirm('Hapus merk [{{ $item->nama_merk }}]?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 border border-slate-200 inline-flex items-center justify-center transition-colors"
                                                title="Hapus Merk">
                                            <i class="ti ti-trash text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400 text-xs">
                                <i class="ti ti-tag-off text-3xl block mb-1 text-slate-300"></i>
                                Tidak ada data merk yang sesuai dengan kriteria pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginasi -->
        <div class="pt-2">
            {{ $merkList->links() }}
        </div>
    </div>

    <!-- MODAL FORM: TAMBAH / EDIT MERK -->
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
         @keydown.escape.window="modalOpen = false">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-6 sm:p-7 space-y-5 my-8"
             @click.outside="modalOpen = false">

            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <i class="ti text-lg" :class="isEdit ? 'ti-pencil' : 'ti-plus'"></i>
                    </span>
                    <h3 class="text-base font-extrabold text-slate-900" x-text="isEdit ? 'Edit Data Merk' : 'Tambah Merk Baru'"></h3>
                </div>
                <button type="button" @click="modalOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form :action="formAction" method="POST" class="space-y-4">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Merk <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama_merk" required maxlength="150" x-model="formData.nama_merk" placeholder="Contoh: Lenovo"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-bold text-slate-800 uppercase">
                    <p class="text-[10px] text-slate-400 mt-1">Maksimal 150 karakter, tidak boleh sama dengan merk lain.</p>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-3 pt-2 border-t border-slate-100">
                    <button type="button" @click="modalOpen = false"
                            class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white text-xs font-extrabold uppercase tracking-wider shadow-md shadow-emerald-600/20 transition-all"
                            x-text="isEdit ? 'Simpan Perubahan' : 'Simpan Merk'">
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('merkManager', () => ({
        modalOpen: false,
        isEdit: false,
        editId: null,
        formAction: '{{ route('data.merk.store') }}',
        formData: {
            nama_merk: ''
        },

        openCreateModal() {
            this.isEdit = false;
            this.editId = null;
            this.formAction = '{{ route('data.merk.store') }}';
            this.formData = { nama_merk: '' };
            this.modalOpen = true;
        },

        openEditModal(item) {
            this.isEdit = true;
            this.editId = item.id;
            this.formAction = '/data/merk/' + item.id;
            this.formData = { nama_merk: item.nama_merk };
            this.modalOpen = true;
        }
    }));
});
</script>
@endpush
