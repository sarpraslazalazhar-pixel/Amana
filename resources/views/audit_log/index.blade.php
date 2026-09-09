@extends('layouts.app')

@section('title', 'Log Aktivitas & Audit - AMANA')
@section('header-title', 'Log Aktivitas & Audit')

@section('content')
<div class="space-y-6" x-data="{
    modalOpen: false,
    selectedLog: null,
    openDiff(log) {
        this.selectedLog = log;
        this.modalOpen = true;
    }
}">

    <!-- Page Header & Title -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-cyan-600 to-emerald-600 text-white flex items-center justify-center shadow-md shadow-emerald-600/20">
                    <i class="ti ti-history text-xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Log Aktivitas & Audit</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Catatan audit jejak aktivitas sistem, histori pembaruan data, dan perubahan aset</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-600 text-xs font-semibold flex items-center gap-1.5 shadow-xs">
                <i class="ti ti-shield-check text-emerald-600 text-sm"></i>
                Khusus Super Admin
            </span>
        </div>
    </div>

    <!-- 4 Stats Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 rounded-3xl bg-white border border-slate-200/80 shadow-sm space-y-1">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Log Tercatat</span>
            <span class="text-2xl font-extrabold text-slate-900">{{ number_format($summary['total_log']) }}</span>
            <p class="text-[11px] text-slate-500">Semua aktivitas sistem</p>
        </div>

        <div class="p-5 rounded-3xl bg-white border border-slate-200/80 shadow-sm space-y-1">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Aktivitas Hari Ini</span>
            <span class="text-2xl font-extrabold text-emerald-600">{{ number_format($summary['log_hari_ini']) }}</span>
            <p class="text-[11px] text-slate-500">Aktivitas per hari ini</p>
        </div>

        <div class="p-5 rounded-3xl bg-white border border-slate-200/80 shadow-sm space-y-1">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Aktivitas Minggu Ini</span>
            <span class="text-2xl font-extrabold text-cyan-600">{{ number_format($summary['log_minggu_ini']) }}</span>
            <p class="text-[11px] text-slate-500">7 hari terakhir</p>
        </div>

        <div class="p-5 rounded-3xl bg-white border border-slate-200/80 shadow-sm space-y-1">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Pembaruan Data</span>
            <span class="text-2xl font-extrabold text-amber-600">{{ number_format($summary['total_update']) }}</span>
            <p class="text-[11px] text-slate-500">Revisi field data aset</p>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="p-5 bg-white rounded-3xl border border-slate-200/80 shadow-sm">
        <form method="GET" action="{{ route('audit-log.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
                <!-- Search Box -->
                <div class="lg:col-span-4">
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Cari Aktivitas / Aset / User</label>
                    <div class="relative">
                        <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Nama aset, kode, deskripsi, atau admin..."
                               class="w-full pl-9 pr-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                    </div>
                </div>

                <!-- Tipe Aksi -->
                <div class="lg:col-span-3">
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Tipe Aksi</label>
                    <select name="aksi" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                        <option value="">Semua Aksi</option>
                        <option value="buat_aset" {{ request('aksi') === 'buat_aset' ? 'selected' : '' }}>Pendaftaran Aset</option>
                        <option value="update_data" {{ request('aksi') === 'update_data' ? 'selected' : '' }}>Pembaruan Data Aset</option>
                        <option value="mutasi" {{ request('aksi') === 'mutasi' ? 'selected' : '' }}>Mutasi & Riwayat Fisik</option>
                        <option value="keuangan" {{ request('aksi') === 'keuangan' ? 'selected' : '' }}>Transaksi Keuangan</option>
                        <option value="agenda" {{ request('aksi') === 'agenda' ? 'selected' : '' }}>Agenda Kegiatan</option>
                        <option value="jurnal" {{ request('aksi') === 'jurnal' ? 'selected' : '' }}>Jurnal Kejadian</option>
                        <option value="ubah_status" {{ request('aksi') === 'ubah_status' ? 'selected' : '' }}>Ubah Status Aset</option>
                    </select>
                </div>

                <!-- Tanggal Dari -->
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Dari Tanggal</label>
                    <input type="date" name="tgl_dari" value="{{ request('tgl_dari') }}"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                </div>

                <!-- Tanggal Sampai -->
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Sampai Tanggal</label>
                    <input type="date" name="tgl_sampai" value="{{ request('tgl_sampai') }}"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-colors">
                </div>

                <!-- Tombol Submit -->
                <div class="lg:col-span-1 flex items-end gap-1.5">
                    <button type="submit" class="w-full py-2 px-3 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white text-xs font-bold shadow-xs transition-all flex items-center justify-center" title="Terapkan Filter">
                        <i class="ti ti-filter text-sm"></i>
                    </button>
                    @if(request()->anyFilled(['search', 'aksi', 'tgl_dari', 'tgl_sampai', 'aset_id']))
                        <a href="{{ route('audit-log.index') }}" class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition-colors flex items-center justify-center" title="Reset Filter">
                            <i class="ti ti-rotate-clockwise text-sm"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Table of Audit Logs -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50/80 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3.5">Waktu</th>
                        <th class="px-4 py-3.5">Pengguna</th>
                        <th class="px-4 py-3.5">Aset Terkait</th>
                        <th class="px-4 py-3.5">Aksi</th>
                        <th class="px-5 py-3.5">Deskripsi Aktivitas</th>
                        <th class="px-4 py-3.5 text-right">Rincian Perubahan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        @php
                            $badgeStyles = match($log->aksi) {
                                'buat_aset' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'update_data' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'mutasi' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'keuangan' => 'bg-purple-50 text-purple-700 border-purple-200',
                                'agenda' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                                'jurnal' => 'bg-rose-50 text-rose-700 border-rose-200',
                                'ubah_status' => 'bg-slate-100 text-slate-700 border-slate-300',
                                default => 'bg-slate-50 text-slate-600 border-slate-200',
                            };
                            $aksiLabel = match($log->aksi) {
                                'buat_aset' => 'Pendaftaran Aset',
                                'update_data' => 'Pembaruan Data',
                                'mutasi' => 'Mutasi / Riwayat',
                                'keuangan' => 'Keuangan',
                                'agenda' => 'Agenda',
                                'jurnal' => 'Jurnal Kejadian',
                                'ubah_status' => 'Ubah Status',
                                default => ucfirst($log->aksi),
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <!-- Waktu -->
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="font-bold text-slate-800 block">{{ $log->created_at->format('d M Y') }}</span>
                                <span class="text-[11px] text-slate-400 font-mono">{{ $log->created_at->format('H:i:s') }} WIB</span>
                            </td>

                            <!-- Pengguna -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-slate-100 border border-slate-200 text-slate-600 flex items-center justify-center font-bold text-[10px]">
                                        {{ strtoupper(substr($log->user->name ?? 'S', 0, 1)) }}
                                    </div>
                                    <div>
                                        <span class="font-bold text-slate-800 block leading-tight">{{ $log->user->name ?? 'Sistem' }}</span>
                                        <span class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">{{ $log->user->role ?? 'admin' }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Aset Terkait -->
                            <td class="px-4 py-3.5">
                                @if($log->aset_id && $log->kode_aset)
                                    <a href="{{ route('aset.show', $log->aset_id) }}" class="group block">
                                        <span class="font-bold text-slate-800 group-hover:text-emerald-700 transition-colors block line-clamp-1">
                                            {{ $log->nama_aset ?? $log->aset->nama_aset ?? '-' }}
                                        </span>
                                        <span class="font-mono text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.2 rounded inline-block mt-0.5">
                                            {{ $log->kode_aset }}
                                        </span>
                                    </a>
                                @elseif($log->kode_aset)
                                    <span class="font-bold text-slate-800 block line-clamp-1">{{ $log->nama_aset ?? '-' }}</span>
                                    <span class="font-mono text-[11px] text-slate-500 bg-slate-100 border border-slate-200 px-1.5 py-0.2 rounded inline-block mt-0.5">
                                        {{ $log->kode_aset }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic">-</span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider border {{ $badgeStyles }}">
                                    {{ $aksiLabel }}
                                </span>
                            </td>

                            <!-- Deskripsi -->
                            <td class="px-5 py-3.5">
                                <p class="text-xs text-slate-700 leading-relaxed font-medium">{{ $log->deskripsi }}</p>
                                @if($log->ip_address)
                                    <span class="text-[10px] text-slate-400 font-mono">IP: {{ $log->ip_address }}</span>
                                @endif
                            </td>

                            <!-- Rincian Diff -->
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                @if(!empty($log->perubahan_data) && is_array($log->perubahan_data))
                                    <button type="button"
                                            @click="openDiff(@js($log))"
                                            class="px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 font-bold text-xs inline-flex items-center gap-1.5 transition-colors shadow-xs">
                                        <i class="ti ti-file-diff text-sm"></i>
                                        Rincian ({{ count($log->perubahan_data) }})
                                    </button>
                                @else
                                    <span class="text-slate-300 text-[11px] font-medium">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-xs">
                                <i class="ti ti-history-off text-3xl block mb-2 text-slate-300"></i>
                                Tidak ada log aktivitas yang sesuai dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Diff Detail (Alpine.js) -->
    <div x-show="modalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        
        <!-- Backdrop -->
        <div x-show="modalOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="modalOpen = false"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div x-show="modalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-slate-200">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                            <i class="ti ti-file-diff text-amber-600 text-lg"></i>
                            Rincian Perubahan Data (Before vs After)
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5" x-text="selectedLog?.deskripsi || ''"></p>
                    </div>
                    <button @click="modalOpen = false" class="p-1.5 rounded-xl text-slate-400 hover:bg-slate-200 hover:text-slate-700 transition-colors">
                        <i class="ti ti-x text-base"></i>
                    </button>
                </div>

                <!-- Modal Body: Table Comparison -->
                <div class="p-6 max-h-[60vh] overflow-y-auto">
                    <template x-if="selectedLog && selectedLog.perubahan_data">
                        <table class="w-full text-xs text-left">
                            <thead class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider border-b border-slate-200 bg-slate-50">
                                <tr>
                                    <th class="px-3.5 py-2.5 rounded-l-xl">Field yang Diubah</th>
                                    <th class="px-3.5 py-2.5 text-rose-700">Nilai Sebelum (Lama)</th>
                                    <th class="px-3.5 py-2.5 text-emerald-700 rounded-r-xl">Nilai Sesudah (Baru)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(diff, index) in selectedLog.perubahan_data" :key="index">
                                    <tr>
                                        <td class="px-3.5 py-3 font-bold text-slate-800 align-top" x-text="diff.field || diff.key"></td>
                                        <td class="px-3.5 py-3 text-rose-700 bg-rose-50/40 rounded-lg font-mono text-[11px] align-top" x-text="diff.sebelum || '-'"></td>
                                        <td class="px-3.5 py-3 text-emerald-700 bg-emerald-50/40 rounded-lg font-mono text-[11px] align-top font-bold" x-text="diff.sesudah || '-'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex justify-end">
                    <button type="button" @click="modalOpen = false" class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
