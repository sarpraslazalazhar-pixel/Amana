@extends('layouts.app')

@section('title', 'Data Master Penanggung Jawab')
@section('header-title', 'Master Data: Penanggung Jawab (PIC / Amil)')

@section('content')
<div class="space-y-6" x-data="picManager">

    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Penanggung Jawab (PIC / Amil)</h2>
            <p class="text-xs text-slate-500 mt-0.5">Kelola data pemegang aset, divisi pengampu, kontak, dan daftar aset yang ditugaskan</p>
        </div>

        @if(auth()->check() && auth()->user()->role === 'super_admin')
            <button type="button"
                    @click="openCreateModal()"
                    class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-extrabold text-xs uppercase tracking-wider shadow-md shadow-emerald-600/20 transition-all inline-flex items-center gap-2">
                <i class="ti ti-user-plus text-base"></i>
                Tambah PIC Baru
            </button>
        @endif
    </div>

    <!-- Stat Tiles Ringkasan PIC -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Amil / PIC</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100"><i class="ti ti-users text-lg"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">{{ $summary['total_pic'] }}</p>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Terdaftar dalam master sistem</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Amil Aktif</span>
                <span class="p-2 rounded-xl bg-cyan-50 text-cyan-600 border border-cyan-100"><i class="ti ti-user-check text-lg"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-emerald-600 mt-2">{{ $summary['total_aktif'] }}</p>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Status operasional aktif</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Amil Non-Aktif</span>
                <span class="p-2 rounded-xl bg-slate-50 text-slate-500 border border-slate-200"><i class="ti ti-user-off text-lg"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-700 mt-2">{{ $summary['total_non_aktif'] }}</p>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Purna tugas / mutasi keluar</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Aset Terdistribusi</span>
                <span class="p-2 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100"><i class="ti ti-box text-lg"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">{{ $summary['total_aset_terdistribusi'] }}</p>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Aset di bawah tanggung jawab PIC</span>
        </div>
    </div>

    <!-- TABEL DATA MASTER PENANGGUNG JAWAB -->
    <div class="p-6 bg-white rounded-3xl border border-slate-200/80 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-xs">
                    <i class="ti ti-user text-sm"></i>
                </span>
                <h3 class="text-sm font-extrabold text-slate-900">Daftar Penanggung Jawab (PIC)</h3>
            </div>

            <!-- Filter & Pencarian Form -->
            <form method="GET" action="{{ route('data.penanggung-jawab.index') }}" class="flex flex-wrap items-center gap-2.5">
                <!-- Filter Divisi -->
                <select name="divisi_id" onchange="this.form.submit()"
                        class="px-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-semibold text-slate-700">
                    <option value="">-- Semua Divisi --</option>
                    @foreach($divisiList as $div)
                        <option value="{{ $div->id }}" {{ request('divisi_id') == $div->id ? 'selected' : '' }}>
                            {{ $div->nama_divisi }} (Kode {{ $div->kode_divisi }})
                        </option>
                    @endforeach
                </select>

                <!-- Filter Status -->
                <select name="status" onchange="this.form.submit()"
                        class="px-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-semibold text-slate-700">
                    <option value="">-- Semua Status --</option>
                    <option value="aktif" {{ request('status') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="non_aktif" {{ request('status') === 'non_aktif' ? 'selected' : '' }}>Non-Aktif</option>
                </select>

                <!-- Search Input -->
                <div class="relative min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / NIA / jabatan..."
                           class="w-full pl-9 pr-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <i class="ti ti-search absolute left-3 top-2.5 text-slate-400 text-sm"></i>
                </div>

                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                    Filter
                </button>
                @if(request()->hasAny(['divisi_id', 'status', 'search']))
                    <a href="{{ route('data.penanggung-jawab.index') }}" class="px-3 py-2 text-xs font-bold text-rose-600 hover:text-rose-700">
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
                        <th class="py-3.5 px-4">NIA / Kode</th>
                        <th class="py-3.5 px-4">Nama Lengkap</th>
                        <th class="py-3.5 px-4">Divisi</th>
                        <th class="py-3.5 px-4">Jabatan</th>
                        <th class="py-3.5 px-4">Kontak (WA & Email)</th>
                        <th class="py-3.5 px-4 text-center">Aset Dipegang</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        @if(auth()->check() && auth()->user()->role === 'super_admin')
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($picList as $item)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <!-- Kode PIC / NIA -->
                            <td class="py-3.5 px-4">
                                <span class="font-mono font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md text-xs">
                                    {{ $item->kode_pic }}
                                </span>
                            </td>

                            <!-- Nama Lengkap -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-emerald-500 to-cyan-600 text-white font-extrabold text-xs flex items-center justify-center shadow-xs flex-shrink-0">
                                        {{ strtoupper(substr($item->nama, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 text-[13px]">{{ $item->nama }}</p>
                                        @if($item->alamat)
                                            <p class="text-[10px] text-slate-400 line-clamp-1">{{ $item->alamat }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Divisi -->
                            <td class="py-3.5 px-4">
                                @if($item->divisi)
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-cyan-50 text-cyan-800 border border-cyan-200">
                                        {{ $item->divisi->nama_divisi }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">-</span>
                                @endif
                            </td>

                            <!-- Jabatan -->
                            <td class="py-3.5 px-4 text-slate-700 font-medium">
                                {{ $item->jabatan ?: '-' }}
                            </td>

                            <!-- Kontak -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    @if($item->telepon)
                                        @php
                                            $cleanPhone = preg_replace('/[^0-9]/', '', $item->telepon);
                                            if (str_starts_with($cleanPhone, '0')) {
                                                $cleanPhone = '62' . substr($cleanPhone, 1);
                                            }
                                        @endphp
                                        <a href="https://wa.me/{{ $cleanPhone }}" target="_blank"
                                           class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-2 py-0.5 rounded-lg border border-emerald-200 transition-colors"
                                           title="Chat WhatsApp {{ $item->telepon }}">
                                            <i class="ti ti-brand-whatsapp text-sm text-emerald-600"></i>
                                            <span>{{ $item->telepon }}</span>
                                        </a>
                                    @endif

                                    @if($item->email)
                                        <a href="mailto:{{ $item->email }}"
                                           class="w-6 h-6 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors"
                                           title="{{ $item->email }}">
                                            <i class="ti ti-mail text-xs"></i>
                                        </a>
                                    @endif

                                    @if(!$item->telepon && !$item->email)
                                        <span class="text-slate-400 italic text-[11px]">-</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Jumlah Aset Dipegang (Klik Buka Modal) -->
                            <td class="py-3.5 px-4 text-center">
                                <button type="button"
                                        @click="openAssetModal({{ $item->id }})"
                                        class="px-2.5 py-1 rounded-lg text-xs font-extrabold transition-all inline-flex items-center gap-1.5 shadow-2xs {{ $item->aset_count > 0 ? 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-100 hover:bg-slate-200 text-slate-600' }}">
                                    <i class="ti ti-box text-xs"></i>
                                    <span>{{ $item->aset_count }} Unit</span>
                                </button>
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide {{ $item->status === 'aktif' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                    {{ $item->status === 'aktif' ? 'Aktif' : 'Non-Aktif' }}
                                </span>
                            </td>

                            <!-- Aksi -->
                            @if(auth()->check() && auth()->user()->role === 'super_admin')
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button"
                                                @click="openAssetModal({{ $item->id }})"
                                                class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-cyan-50 text-slate-600 hover:text-cyan-700 border border-slate-200 inline-flex items-center justify-center transition-colors"
                                                title="Lihat Daftar Aset">
                                            <i class="ti ti-eye text-sm"></i>
                                        </button>

                                        <button type="button"
                                                @click="openEditModal({{ Js::from($item) }})"
                                                class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 border border-slate-200 inline-flex items-center justify-center transition-colors"
                                                title="Edit Data PIC">
                                            <i class="ti ti-pencil text-sm"></i>
                                        </button>

                                        <form method="POST" action="{{ route('data.penanggung-jawab.destroy', $item->id) }}" class="inline"
                                              onsubmit="return confirm('Hapus Penanggung Jawab [{{ $item->kode_pic }}] {{ $item->nama }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 border border-slate-200 inline-flex items-center justify-center transition-colors"
                                                    title="Hapus PIC">
                                                <i class="ti ti-trash text-sm"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400 text-xs">
                                <i class="ti ti-user-x text-3xl block mb-1 text-slate-300"></i>
                                Tidak ada data Penanggung Jawab yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginasi -->
        <div class="pt-2">
            {{ $picList->links() }}
        </div>
    </div>

    <!-- MODAL FORM: TAMBAH / EDIT PENANGGUNG JAWAB -->
    <div x-show="modalFormOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
         @keydown.escape.window="modalFormOpen = false">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-xl w-full p-6 sm:p-7 space-y-5 my-8"
             @click.outside="modalFormOpen = false">

            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <i class="ti text-lg" :class="isEdit ? 'ti-pencil' : 'ti-user-plus'"></i>
                    </span>
                    <h3 class="text-base font-extrabold text-slate-900" x-text="isEdit ? 'Edit Penanggung Jawab' : 'Tambah PIC / Amil Baru'"></h3>
                </div>
                <button type="button" @click="modalFormOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form :action="formAction" method="POST" class="space-y-4">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Kode PIC (NIA 3 Digit) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kode PIC / 3 Digit NIA <span class="text-rose-500">*</span></label>
                        <input type="text" name="kode_pic" required x-model="formData.kode_pic" placeholder="Contoh: 050"
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono font-bold">
                    </div>

                    <!-- Divisi -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Divisi Penugasan <span class="text-slate-400">(Opsional)</span></label>
                        <select name="divisi_id" x-model="formData.divisi_id"
                                class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-semibold text-slate-700">
                            <option value="">-- Pilih Divisi --</option>
                            @foreach($divisiList as $div)
                                <option value="{{ $div->id }}">{{ $div->nama_divisi }} (Kode {{ $div->kode_divisi }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Nama Lengkap -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Amil <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama" required x-model="formData.nama" placeholder="Contoh: Suryamin"
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-bold text-slate-800">
                    </div>

                    <!-- Jabatan -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jabatan / Posisi Kerja</label>
                        <input type="text" name="jabatan" x-model="formData.jabatan" placeholder="Contoh: Staf CRM & Aset"
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Status Keaktifan <span class="text-rose-500">*</span></label>
                        <select name="status" required x-model="formData.status"
                                class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-bold text-slate-700">
                            <option value="aktif">Aktif</option>
                            <option value="non_aktif">Non-Aktif</option>
                        </select>
                    </div>

                    <!-- Nomor Telepon / WA -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">No. Telepon / WhatsApp</label>
                        <input type="text" name="telepon" x-model="formData.telepon" placeholder="Contoh: 081289050050"
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono">
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Email</label>
                        <input type="email" name="email" x-model="formData.email" placeholder="Contoh: suryamin@alazhar.org"
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    </div>

                    <!-- Alamat / Domisili -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat / Domisili <span class="text-slate-400">(Opsional)</span></label>
                        <textarea name="alamat" rows="2" x-model="formData.alamat" placeholder="Alamat domisili staf..."
                                  class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"></textarea>
                    </div>

                    <!-- Keterangan Tambahan -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan <span class="text-slate-400">(Opsional)</span></label>
                        <textarea name="keterangan" rows="2" x-model="formData.keterangan" placeholder="Catatan khusus PIC..."
                                  class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"></textarea>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="modalFormOpen = false"
                            class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white text-xs font-extrabold uppercase tracking-wider shadow-md shadow-emerald-600/20 transition-all">
                        Simpan Data PIC
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL DETAIL: DAFTAR ASET YANG DIPEGANG PIC -->
    <div x-show="modalAssetOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="modalAssetOpen = false">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-5xl w-full p-5 sm:p-6 flex flex-col max-h-[92vh] my-auto animate-in fade-in zoom-in duration-150"
             @click.outside="modalAssetOpen = false">

            <!-- Modal Header -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-500 to-cyan-600 text-white font-extrabold text-sm flex items-center justify-center shadow-xs flex-shrink-0">
                        <i class="ti ti-box text-xl"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-base font-extrabold text-slate-900" x-text="currentPic.nama"></h3>
                            <span class="font-mono font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded text-xs" x-text="'NIA: ' + currentPic.kode_pic"></span>
                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-extrabold flex items-center gap-1">
                                <i class="ti ti-boxes text-xs"></i>
                                <span x-text="(picAssets ? picAssets.length : 0) + ' Unit'"></span>
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <span x-text="currentPic.jabatan || 'Amil'"></span> • <span x-text="currentPic.divisi || 'Tanpa Divisi'"></span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <template x-if="currentPic.telepon">
                        <a :href="'https://wa.me/' + cleanPhone(currentPic.telepon)" target="_blank"
                           class="px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-bold text-xs inline-flex items-center gap-1.5 transition-colors">
                            <i class="ti ti-brand-whatsapp text-base text-emerald-600"></i>
                            <span class="hidden sm:inline">Hubungi WA</span>
                        </a>
                    </template>
                    <button type="button" @click="modalAssetOpen = false" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Loader State -->
            <div x-show="loadingAssets" class="py-16 text-center text-slate-400 flex-1 flex flex-col items-center justify-center">
                <i class="ti ti-loader-2 animate-spin text-3xl text-emerald-600 block mb-2"></i>
                <p class="text-xs font-semibold text-slate-600">Memuat daftar aset yang dipegang...</p>
            </div>

            <!-- Asset Content Container -->
            <div x-show="!loadingAssets" class="flex-1 flex flex-col min-h-0 space-y-3 pt-3">
                
                <!-- Toolbar Pencarian & Filter -->
                <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200/80 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 flex-shrink-0">
                    <div class="flex flex-1 flex-wrap items-center gap-2">
                        <!-- Input Search -->
                        <div class="relative flex-1 min-w-[200px]">
                            <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input type="text" x-model="searchAsset" placeholder="Cari kode, nama, lokasi, kategori..."
                                   class="w-full pl-8 pr-7 py-1.5 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-medium placeholder:text-slate-400">
                            <button type="button" x-show="searchAsset" @click="searchAsset = ''"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <i class="ti ti-x text-xs"></i>
                            </button>
                        </div>

                        <!-- Filter Kategori -->
                        <select x-model="selectedKategori" class="px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 bg-white text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                            <option value="">Semua Kategori</option>
                            <template x-for="kat in kategoriList" :key="kat">
                                <option :value="kat" x-text="kat"></option>
                            </template>
                        </select>

                        <!-- Filter Status -->
                        <select x-model="selectedStatus" class="px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 bg-white text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                            <option value="">Semua Status</option>
                            <template x-for="st in statusList" :key="st">
                                <option :value="st" x-text="st.toUpperCase()"></option>
                            </template>
                        </select>

                        <!-- Tombol Reset -->
                        <button type="button" x-show="searchAsset || selectedKategori || selectedStatus"
                                @click="resetAssetFilters()"
                                class="px-2 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50 rounded-lg transition-colors inline-flex items-center gap-1"
                                title="Reset Filter">
                            <i class="ti ti-rotate-clockwise"></i>
                            <span>Reset</span>
                        </button>
                    </div>

                    <!-- Per Page Selector -->
                    <div class="flex items-center gap-1.5 text-xs text-slate-500 flex-shrink-0 self-end sm:self-auto">
                        <span>Tampil:</span>
                        <select x-model="perPage" class="px-2 py-1 text-xs rounded-lg border border-slate-200 bg-white font-bold text-slate-700 focus:outline-none">
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="all">Semua</option>
                        </select>
                    </div>
                </div>

                <!-- Table Area dengan Scrollable Container & Sticky Header -->
                <div class="flex-1 overflow-y-auto overflow-x-auto min-h-[250px] max-h-[50vh] rounded-2xl border border-slate-200 bg-white relative shadow-2xs">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-100/95 backdrop-blur-xs text-slate-600 uppercase tracking-wider font-extrabold text-[10.5px] sticky top-0 z-10 border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-3.5 text-center w-12">No</th>
                                <th class="py-2.5 px-3.5">Kode Aset</th>
                                <th class="py-2.5 px-3.5">Nama Aset</th>
                                <th class="py-2.5 px-3.5">Kategori</th>
                                <th class="py-2.5 px-3.5">Lokasi Penempatan</th>
                                <th class="py-2.5 px-3.5 text-center w-24">Status</th>
                                <th class="py-2.5 px-3.5 text-right w-20">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(aset, idx) in paginatedAssets" :key="aset.id">
                                <tr class="hover:bg-emerald-50/40 transition-colors">
                                    <td class="py-2.5 px-3.5 text-center font-mono text-slate-400 text-[11px]"
                                        x-text="(perPage === 'all' ? 0 : (currentPage - 1) * parseInt(perPage, 10)) + idx + 1"></td>
                                    <td class="py-2.5 px-3.5">
                                        <span class="font-mono font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded text-[11px] select-all"
                                              x-text="aset.kode_aset"></span>
                                    </td>
                                    <td class="py-2.5 px-3.5 font-bold text-slate-900" x-text="aset.nama_aset"></td>
                                    <td class="py-2.5 px-3.5 text-slate-600">
                                        <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[10.5px] font-medium" x-text="aset.kategori"></span>
                                    </td>
                                    <td class="py-2.5 px-3.5 text-slate-600">
                                        <div class="flex items-center gap-1.5">
                                            <i class="ti ti-map-pin text-slate-400 text-xs flex-shrink-0"></i>
                                            <span x-text="aset.lokasi" class="line-clamp-1"></span>
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-3.5 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
                                              :class="aset.status === 'aktif' ? 'bg-emerald-100 text-emerald-800' : (aset.status === 'rusak' ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-600')"
                                              x-text="aset.status"></span>
                                    </td>
                                    <td class="py-2.5 px-3.5 text-right">
                                        <a :href="aset.detail_url" target="_blank"
                                           class="px-2.5 py-1 rounded-lg bg-white hover:bg-emerald-50 text-emerald-700 font-bold text-[11px] inline-flex items-center gap-1 border border-slate-200 hover:border-emerald-300 transition-all shadow-2xs">
                                            <span>Buka</span>
                                            <i class="ti ti-external-link text-xs"></i>
                                        </a>
                                    </td>
                                </tr>
                            </template>

                            <!-- Empty Search / Filter Result -->
                            <template x-if="filteredAssets.length === 0 && picAssets.length > 0">
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-400 text-xs">
                                        <i class="ti ti-search-off text-3xl block mb-1.5 text-slate-300"></i>
                                        <p class="font-semibold text-slate-700">Tidak ada unit yang cocok dengan kriteria pencarian.</p>
                                        <button type="button" @click="resetAssetFilters()" class="mt-2 px-3 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl hover:bg-emerald-100">
                                            Reset Filter & Pencarian
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            <!-- Zero Assets in PIC -->
                            <template x-if="picAssets.length === 0">
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-400 text-xs">
                                        <i class="ti ti-box-off text-3xl block mb-1 text-slate-300"></i>
                                        Saat ini tidak ada aset yang dipegang oleh Penanggung Jawab ini.
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Informasi & Paginasi -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 flex-shrink-0 border-t border-slate-100 text-xs text-slate-500">
                    <div>
                        Menampilkan <strong class="text-slate-800" x-text="paginationStart"></strong> - <strong class="text-slate-800" x-text="paginationEnd"></strong> dari <strong class="text-slate-800" x-text="filteredAssets.length"></strong> unit
                        <span x-show="filteredAssets.length !== picAssets.length" class="text-[11px] text-slate-400">
                            (difilter dari <span x-text="picAssets.length"></span> total unit)
                        </span>
                    </div>

                    <!-- Pagination Controls -->
                    <div class="flex items-center gap-1" x-show="totalPages > 1 && perPage !== 'all'">
                        <button type="button" @click="goToPage(currentPage - 1)" :disabled="currentPage === 1"
                                class="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600 disabled:opacity-30 disabled:pointer-events-none transition-colors"
                                title="Halaman Sebelumnya">
                            <i class="ti ti-chevron-left"></i>
                        </button>

                        <template x-for="p in pagesToShow" :key="p">
                            <div>
                                <template x-if="p === '...'">
                                    <span class="px-2 text-slate-400 font-bold select-none">...</span>
                                </template>
                                <template x-if="p !== '...'">
                                    <button type="button" @click="goToPage(p)"
                                            class="min-w-[28px] h-7 px-1.5 rounded-lg font-bold text-xs transition-colors"
                                            :class="currentPage === p ? 'bg-gradient-to-r from-cyan-600 to-emerald-600 text-white shadow-2xs' : 'border border-slate-200 hover:bg-slate-50 text-slate-700'"
                                            x-text="p"></button>
                                </template>
                            </div>
                        </template>

                        <button type="button" @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages"
                                class="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600 disabled:opacity-30 disabled:pointer-events-none transition-colors"
                                title="Halaman Berikutnya">
                            <i class="ti ti-chevron-right"></i>
                        </button>
                    </div>

                    <!-- Tombol Tutup -->
                    <div>
                        <button type="button" @click="modalAssetOpen = false"
                                class="px-5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                            Tutup
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('picManager', () => ({
        modalFormOpen: false,
        modalAssetOpen: false,
        isEdit: false,
        editId: null,
        loadingAssets: false,
        formAction: '{{ route('data.penanggung-jawab.store') }}',
        formData: {
            kode_pic: '',
            nama: '',
            divisi_id: '',
            jabatan: '',
            telepon: '',
            email: '',
            status: 'aktif',
            alamat: '',
            keterangan: ''
        },
        currentPic: {},
        picAssets: [],

        // State filter & paginasi modal aset
        searchAsset: '',
        selectedKategori: '',
        selectedStatus: '',
        perPage: 15,
        currentPage: 1,

        init() {
            this.$watch('searchAsset', () => { this.currentPage = 1; });
            this.$watch('selectedKategori', () => { this.currentPage = 1; });
            this.$watch('selectedStatus', () => { this.currentPage = 1; });
            this.$watch('perPage', () => { this.currentPage = 1; });
        },

        get kategoriList() {
            const list = new Set();
            (this.picAssets || []).forEach(a => {
                if (a.kategori && a.kategori !== '-') list.add(a.kategori);
            });
            return Array.from(list).sort();
        },

        get statusList() {
            const list = new Set();
            (this.picAssets || []).forEach(a => {
                if (a.status) list.add(a.status);
            });
            return Array.from(list).sort();
        },

        get filteredAssets() {
            let list = this.picAssets || [];
            const q = (this.searchAsset || '').toLowerCase().trim();
            if (q) {
                list = list.filter(a =>
                    (a.kode_aset && a.kode_aset.toLowerCase().includes(q)) ||
                    (a.nama_aset && a.nama_aset.toLowerCase().includes(q)) ||
                    (a.lokasi && a.lokasi.toLowerCase().includes(q)) ||
                    (a.kategori && a.kategori.toLowerCase().includes(q))
                );
            }
            if (this.selectedKategori) {
                list = list.filter(a => a.kategori === this.selectedKategori);
            }
            if (this.selectedStatus) {
                list = list.filter(a => a.status === this.selectedStatus);
            }
            return list;
        },

        get totalPages() {
            if (this.perPage === 'all') return 1;
            const p = parseInt(this.perPage, 10);
            return Math.max(1, Math.ceil(this.filteredAssets.length / p));
        },

        get paginatedAssets() {
            if (this.perPage === 'all') return this.filteredAssets;
            const p = parseInt(this.perPage, 10);
            const start = (this.currentPage - 1) * p;
            return this.filteredAssets.slice(start, start + p);
        },

        get paginationStart() {
            if (this.filteredAssets.length === 0) return 0;
            if (this.perPage === 'all') return 1;
            const p = parseInt(this.perPage, 10);
            return (this.currentPage - 1) * p + 1;
        },

        get paginationEnd() {
            if (this.perPage === 'all') return this.filteredAssets.length;
            const p = parseInt(this.perPage, 10);
            return Math.min(this.currentPage * p, this.filteredAssets.length);
        },

        get pagesToShow() {
            const total = this.totalPages;
            const current = this.currentPage;
            if (total <= 7) {
                return Array.from({ length: total }, (_, i) => i + 1);
            }
            const pages = [];
            if (current <= 4) {
                for (let i = 1; i <= 5; i++) pages.push(i);
                pages.push('...');
                pages.push(total);
            } else if (current >= total - 3) {
                pages.push(1);
                pages.push('...');
                for (let i = total - 4; i <= total; i++) pages.push(i);
            } else {
                pages.push(1);
                pages.push('...');
                pages.push(current - 1);
                pages.push(current);
                pages.push(current + 1);
                pages.push('...');
                pages.push(total);
            }
            return pages;
        },

        resetAssetFilters() {
            this.searchAsset = '';
            this.selectedKategori = '';
            this.selectedStatus = '';
            this.currentPage = 1;
        },

        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
            }
        },

        openCreateModal() {
            this.isEdit = false;
            this.editId = null;
            this.formAction = '{{ route('data.penanggung-jawab.store') }}';
            this.formData = {
                kode_pic: '',
                nama: '',
                divisi_id: '',
                jabatan: '',
                telepon: '',
                email: '',
                status: 'aktif',
                alamat: '',
                keterangan: ''
            };
            this.modalFormOpen = true;
        },

        openEditModal(item) {
            this.isEdit = true;
            this.editId = item.id;
            this.formAction = '/data/penanggung-jawab/' + item.id;
            this.formData = {
                kode_pic: item.kode_pic,
                nama: item.nama,
                divisi_id: item.divisi_id || '',
                jabatan: item.jabatan || '',
                telepon: item.telepon || '',
                email: item.email || '',
                status: item.status || 'aktif',
                alamat: item.alamat || '',
                keterangan: item.keterangan || ''
            };
            this.modalFormOpen = true;
        },

        async openAssetModal(id) {
            this.loadingAssets = true;
            this.modalAssetOpen = true;
            this.currentPic = {};
            this.picAssets = [];
            this.resetAssetFilters();
            this.perPage = 15;

            try {
                const response = await fetch('/data/penanggung-jawab/' + id, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (response.ok) {
                    const data = await response.json();
                    this.currentPic = data.pic || {};
                    this.picAssets = data.aset || [];
                }
            } catch (err) {
                console.error('Gagal mengambil data aset PIC:', err);
            } finally {
                this.loadingAssets = false;
            }
        },

        cleanPhone(phone) {
            if (!phone) return '';
            let cleaned = phone.replace(/[^0-9]/g, '');
            if (cleaned.startsWith('0')) {
                cleaned = '62' + cleaned.substring(1);
            }
            return cleaned;
        }
    }));
});
</script>
@endpush
