@extends('layouts.app')

@section('title', 'Dashboard - AMANA')
@section('header-title', 'Dashboard AMANA')

@php
    $formatRupiah = fn ($nilai) => 'Rp ' . number_format($nilai, 0, ',', '.');
@endphp

@section('content')
<div class="space-y-6" x-data="{
    chartTab: 'unit', // 'unit' atau 'nilai'
}">

    <!-- ========================================================================= -->
    <!-- HEADER BAR: SALAM, TANGGAL & PINTASAN CEPAT                               -->
    <!-- ========================================================================= -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs">
        <div class="flex items-center space-x-3.5">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white flex items-center justify-center shadow-md shadow-emerald-500/20 flex-shrink-0">
                <i class="ti ti-layout-dashboard text-2xl"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                    Assalamu'alaikum, {{ auth()->user()->name ?? 'Amil LAZ AL AZHAR' }}
                </h1>
                <p class="text-xs text-slate-500">
                    Ringkasan inventaris aset, valuasi buku, penyusutan berjalan, dan linimasa aktivitas terkini.
                </p>
            </div>
        </div>

        <div class="flex items-center flex-wrap gap-2">
            <a href="{{ route('kalender.index') }}"
               class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs inline-flex items-center gap-1.5 transition-colors shadow-2xs">
                <i class="ti ti-calendar-event text-sm text-emerald-600"></i>
                <span>Buka Kalender</span>
            </a>
            @if(auth()->check() && auth()->user()->role === 'super_admin')
                <a href="{{ route('aset.create') }}"
                   class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs inline-flex items-center gap-1.5 transition-colors shadow-xs">
                    <i class="ti ti-plus text-sm"></i>
                    <span>Tambah Aset</span>
                </a>
            @endif
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 5 KARTU RANGKUMAN UTAMA (SUMMARY KPI)                                     -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        
        <!-- 1. Jumlah Aset Aktif -->
        <div class="relative overflow-hidden p-4 rounded-3xl bg-white border border-slate-200/80 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">Aset Aktif</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="ti ti-box text-base"></i>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ number_format($totalAktif) }} <span class="text-xs font-semibold text-slate-400">Unit</span></h3>
                <div class="mt-2 flex items-center gap-1.5 text-[10.5px] font-semibold">
                    <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200/60">{{ $totalTetap }} Tetap</span>
                    <span class="px-1.5 py-0.5 rounded bg-sky-50 text-sky-700 border border-sky-200/60">{{ $totalKelolaan }} Kelolaan</span>
                </div>
            </div>
        </div>

        <!-- 2. Total Nilai Awal (Perolehan) -->
        <div class="relative overflow-hidden p-4 rounded-3xl bg-white border border-slate-200/80 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">Nilai Awal (Perolehan)</span>
                <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i class="ti ti-coins text-base"></i>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">{{ $formatRupiah($totalNilaiAwal) }}</h3>
                <p class="mt-1 text-[10.5px] text-slate-500 font-medium truncate">
                    Tetap: {{ $formatRupiah($nilaiAwalTetap) }}
                </p>
            </div>
        </div>

        <!-- 3. Total Akumulasi Penyusutan -->
        <div class="relative overflow-hidden p-4 rounded-3xl bg-white border border-slate-200/80 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">Total Penyusutan</span>
                <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i class="ti ti-trending-down text-base"></i>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="text-lg sm:text-xl font-black text-amber-600 tracking-tight">{{ $formatRupiah($totalAkumulasiPenyusutan) }}</h3>
                <div class="mt-1 flex items-center gap-1 text-[10.5px] font-semibold text-amber-700">
                    <span>{{ number_format($persentasePenyusutan, 1) }}% terdepresiasi</span>
                </div>
            </div>
        </div>

        <!-- 4. Total Nilai Sekarang (Nilai Buku) -->
        <div class="relative overflow-hidden p-4 rounded-3xl bg-white border border-emerald-200/80 bg-gradient-to-b from-white to-emerald-50/20 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10.5px] font-bold text-emerald-800 uppercase tracking-wider">Nilai Sekarang (Buku)</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-xs">
                    <i class="ti ti-award text-base"></i>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="text-lg sm:text-xl font-black text-emerald-900 tracking-tight">{{ $formatRupiah($totalNilaiSekarang) }}</h3>
                <div class="mt-1 flex items-center gap-1 text-[10.5px] font-bold text-emerald-700">
                    <span>{{ number_format($persentaseNilaiBuku, 1) }}% nilai tersisa</span>
                </div>
            </div>
        </div>

        <!-- 5. Penyusutan Akhir Bulan -->
        <div class="relative overflow-hidden p-4 rounded-3xl bg-white border border-slate-200/80 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">Penyusutan / Bulan</span>
                <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <i class="ti ti-calendar-minus text-base"></i>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="text-lg sm:text-xl font-black text-rose-600 tracking-tight">{{ $formatRupiah($totalPenyusutanBulan) }}</h3>
                <p class="mt-1 text-[10.5px] text-slate-500 font-medium truncate">
                    Beban depresiasi akhir bulan
                </p>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- SECTION GRAFIK APEXCHARTS (DUAL PANEL)                                    -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- ===================================================================== -->
        <!-- PANEL GRAFIK 1: ASET TETAP & KELOLAAN PER KATEGORI + MoM GROWTH       -->
        <!-- ===================================================================== -->
        <div class="lg:col-span-7 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs flex flex-col justify-between space-y-4">
            
            <!-- Header Grafik 1 + Toggle Tab -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Grafik Aset per Kategori</h3>
                    <p class="text-xs text-slate-500">Perbandingan Aset Tetap vs Aset Kelolaan per kategori barang.</p>
                </div>

                <!-- Toggle Tab Switcher (Unit vs Nilai Rupiah) -->
                <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs">
                    <button @click="chartTab = 'unit'; toggleChartKategori('unit')"
                            :class="chartTab === 'unit' ? 'bg-white text-emerald-700 font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                            class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1">
                        <i class="ti ti-hash text-sm"></i>
                        <span>Jumlah Unit</span>
                    </button>
                    <button @click="chartTab = 'nilai'; toggleChartKategori('nilai')"
                            :class="chartTab === 'nilai' ? 'bg-white text-emerald-700 font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                            class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1">
                        <i class="ti ti-currency-dollar text-sm"></i>
                        <span>Nilai Rupiah</span>
                    </button>
                </div>
            </div>

            <!-- ApexCharts Container 1 -->
            <div class="w-full min-h-[300px]" id="apexChartKategori"></div>

            <!-- Kartu Persentase Perubahan Per Bulan (MoM Growth) di Bawah Grafik -->
            <div class="pt-4 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-3">
                
                <!-- MoM Unit Growth -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pertumbuhan Unit (MoM)</p>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-sm font-extrabold text-slate-800">{{ $momGrowth['unit_this_month'] }} unit baru</span>
                            <span class="inline-flex items-center gap-0.5 text-xs font-black px-1.5 py-0.5 rounded {{ $momGrowth['unit_change_pct'] >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                <i class="ti {{ $momGrowth['unit_change_pct'] >= 0 ? 'ti-trending-up' : 'ti-trending-down' }}"></i>
                                {{ $momGrowth['unit_change_pct'] > 0 ? '+' : '' }}{{ $momGrowth['unit_change_pct'] }}%
                            </span>
                        </div>
                    </div>
                    <span class="text-[10px] text-slate-400 font-medium text-right">vs bln lalu<br>({{ $momGrowth['unit_last_month'] }} unit)</span>
                </div>

                <!-- MoM Nilai Investasi Growth -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nilai Pengadaan (MoM)</p>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-sm font-extrabold text-slate-800">{{ $formatRupiah($momGrowth['nilai_this_month']) }}</span>
                            <span class="inline-flex items-center gap-0.5 text-xs font-black px-1.5 py-0.5 rounded {{ $momGrowth['nilai_change_pct'] >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                <i class="ti {{ $momGrowth['nilai_change_pct'] >= 0 ? 'ti-trending-up' : 'ti-trending-down' }}"></i>
                                {{ $momGrowth['nilai_change_pct'] > 0 ? '+' : '' }}{{ $momGrowth['nilai_change_pct'] }}%
                            </span>
                        </div>
                    </div>
                    <span class="text-[10px] text-slate-400 font-medium text-right">vs bln lalu<br>({{ $formatRupiah($momGrowth['nilai_last_month']) }})</span>
                </div>

            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- PANEL GRAFIK 2: TOTAL NILAI ASET TETAP VS KELOLAAN (DENGAN PENYUSUTAN)-->
        <!-- ===================================================================== -->
        <div class="lg:col-span-5 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs flex flex-col justify-between space-y-4">
            
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Valuasi & Penyusutan</h3>
                <p class="text-xs text-slate-500">Komparasi Nilai Awal, Total Penyusutan, dan Nilai Buku.</p>
            </div>

            <!-- ApexCharts Container 2 -->
            <div class="w-full min-h-[300px]" id="apexChartPenyusutan"></div>

            <!-- Ringkasan Valuasi di Bawah Grafik -->
            <div class="pt-4 border-t border-slate-100 grid grid-cols-2 gap-3 text-xs">
                <div class="p-3 rounded-2xl bg-emerald-50/70 border border-emerald-200/70">
                    <p class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Aset Tetap (Buku)</p>
                    <p class="mt-1 text-sm font-extrabold text-emerald-900">{{ $formatRupiah($nilaiSekarangTetap) }}</p>
                    <p class="text-[10px] text-emerald-600 mt-0.5">Depresiasi: {{ $formatRupiah($akumulasiTetap) }}</p>
                </div>
                <div class="p-3 rounded-2xl bg-sky-50/70 border border-sky-200/70">
                    <p class="text-[10px] font-bold text-sky-700 uppercase tracking-wider">Aset Kelolaan (Buku)</p>
                    <p class="mt-1 text-sm font-extrabold text-sky-900">{{ $formatRupiah($nilaiSekarangKelolaan) }}</p>
                    <p class="text-[10px] text-sky-600 mt-0.5">Depresiasi: {{ $formatRupiah($akumulasiKelolaan) }}</p>
                </div>
            </div>

        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- BANNER PENGINGAT AGENDA JATUH TEMPO & TERLAMBAT                           -->
    <!-- ========================================================================= -->
    @if(isset($agendaReminder) && $agendaReminder['total_count'] > 0)
        <div x-data="{ bannerOpen: true }" x-show="bannerOpen" x-transition class="relative overflow-hidden rounded-3xl border {{ $agendaReminder['has_critical'] ? 'bg-gradient-to-r from-rose-50/90 via-amber-50/70 to-white border-rose-200 shadow-xs' : 'bg-gradient-to-r from-sky-50/90 via-indigo-50/50 to-white border-sky-200 shadow-xs' }} p-5 sm:p-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 shadow-xs {{ $agendaReminder['has_critical'] ? 'bg-rose-600 text-white' : 'bg-sky-600 text-white' }}">
                        @if($agendaReminder['has_critical'])
                            <i class="ti ti-alert-triangle text-xl"></i>
                        @else
                            <i class="ti ti-calendar-event text-xl"></i>
                        @endif
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">
                                {{ $agendaReminder['has_critical'] ? 'Perhatian: Ada Jadwal Agenda Perawatan Mendesak' : 'Jadwal Agenda Perawatan Mendatang' }}
                            </h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold {{ $agendaReminder['has_critical'] ? 'bg-rose-100 text-rose-800 border border-rose-200' : 'bg-sky-100 text-sky-800 border border-sky-200' }}">
                                {{ $agendaReminder['total_count'] }} Agenda
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                            @if($agendaReminder['overdue_count'] > 0 && $agendaReminder['today_count'] > 0)
                                Terdapat <strong class="text-rose-700 font-bold">{{ $agendaReminder['overdue_count'] }} agenda terlambat</strong> dan <strong class="text-amber-700 font-bold">{{ $agendaReminder['today_count'] }} agenda jatuh tempo hari ini</strong>.
                            @elseif($agendaReminder['overdue_count'] > 0)
                                Terdapat <strong class="text-rose-700 font-bold">{{ $agendaReminder['overdue_count'] }} agenda terlambat</strong> yang belum diselesaikan.
                            @elseif($agendaReminder['today_count'] > 0)
                                Terdapat <strong class="text-amber-700 font-bold">{{ $agendaReminder['today_count'] }} agenda</strong> yang jatuh tempo hari ini.
                            @else
                                Terdapat <strong class="text-sky-700 font-bold">{{ $agendaReminder['upcoming_count'] }} agenda</strong> yang akan jatuh tempo dalam 7 hari ke depan.
                            @endif
                            Pastikan pemeliharaan aset tercatat dengan baik.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-end md:self-center shrink-0">
                    <a href="{{ route('kalender.index') }}" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 transition-all flex items-center gap-1.5 shadow-xs">
                        <i class="ti ti-calendar text-sm text-emerald-600"></i>
                        Lihat Kalender
                    </a>
                    <button @click="bannerOpen = false" type="button" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-white/80 transition-colors" title="Tutup pemberitahuan" aria-label="Tutup pemberitahuan">
                        <i class="ti ti-x text-base"></i>
                    </button>
                </div>
            </div>

            <!-- Preview 3 Agenda Paling Kritis -->
            <div class="mt-4 pt-4 border-t border-slate-200/70 grid grid-cols-1 md:grid-cols-3 gap-3">
                @foreach($agendaReminder['items']->take(3) as $item)
                    <div class="bg-white/90 backdrop-blur-xs p-3 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between hover:border-slate-300 transition-all group">
                        <div>
                            <div class="flex items-center justify-between gap-1 mb-1">
                                <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded-md border {{ $item['category'] === 'overdue' ? 'bg-rose-50 text-rose-700 border-rose-200' : ($item['category'] === 'today' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-sky-50 text-sky-700 border-sky-200') }}">
                                    {{ $item['human_diff'] }}
                                </span>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $item['due_date_formatted'] }}</span>
                            </div>
                            <h4 class="text-xs font-bold text-slate-900 group-hover:text-emerald-700 transition-colors truncate">
                                {{ $item['nama_agenda'] }}
                            </h4>
                            <p class="text-[11px] text-slate-500 truncate mt-0.5">
                                {{ $item['nama_aset'] }} <span class="font-mono text-[10px]">({{ $item['kode_aset'] }})</span>
                            </p>
                        </div>
                        <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-[10px] text-slate-400 truncate">
                                <i class="ti ti-map-pin"></i> {{ $item['lokasi_nama'] }}
                            </span>
                            <a href="{{ route('aset.show', $item['aset_id']) }}?tab=agenda" class="text-[11px] font-bold text-emerald-600 hover:text-emerald-800 inline-flex items-center gap-0.5">
                                Buka <i class="ti ti-chevron-right text-xs"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- SECTION 5 WIDGET AKTIVITAS TERBARU (2-COLUMN BALANCED GRID)               -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- ===================================================================== -->
        <!-- KOLOM KIRI: WIDGET 1 (ASET TERBARU), WIDGET 2 (RIWAYAT), WIDGET 3 (JURNAL) -->
        <!-- ===================================================================== -->
        <div class="space-y-6">

            <!-- WIDGET 1: 5 ASET TERBARU -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i class="ti ti-sparkles text-base"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-extrabold text-slate-900">Aset Terbaru</h4>
                            <p class="text-[11px] text-slate-400">5 aset yang baru ditambahkan ke sistem</p>
                        </div>
                    </div>
                    <a href="{{ route('aset.tetap') }}" class="text-xs font-bold text-emerald-600 hover:text-emerald-700">
                        Lihat Semua →
                    </a>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($asetTerbaru as $ast)
                        <div class="py-3 flex items-center justify-between gap-3 hover:bg-slate-50/60 p-2 rounded-2xl transition-colors">
                            <div class="flex items-center space-x-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex-shrink-0 flex items-center justify-center">
                                    @if($ast->foto_utama)
                                        <img src="{{ Storage::disk('public')->url($ast->foto_utama) }}" alt="{{ $ast->nama_aset }}" class="w-full h-full object-cover">
                                    @else
                                        <i class="ti ti-box text-slate-400 text-lg"></i>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('aset.show', $ast->id) }}" class="font-extrabold text-xs text-slate-900 hover:text-emerald-600 truncate block">
                                        {{ $ast->nama_aset }}
                                    </a>
                                    <div class="mt-0.5 flex items-center gap-1.5 text-[10px] text-slate-400">
                                        <span class="font-mono text-emerald-700 font-bold">{{ $ast->kode_aset }}</span>
                                        <span>• {{ $ast->kategori->nama_kategori ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="text-xs font-extrabold text-slate-900 font-mono">{{ $formatRupiah($ast->harga_total) }}</div>
                                <div class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($ast->tanggal_pembelian)->translatedFormat('d M Y') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-slate-400 text-xs italic">Belum ada aset yang terdaftar.</div>
                    @endforelse
                </div>
            </div>

            <!-- WIDGET 2: 5 RIWAYAT MUTASI TERBARU (PINDAH PENANGGUNG JAWAB) -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                            <i class="ti ti-history text-base"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-extrabold text-slate-900">Riwayat Mutasi & Pemegang</h4>
                            <p class="text-[11px] text-slate-400">Perpindahan penanggung jawab & lokasi terkini</p>
                        </div>
                    </div>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($riwayatTerbaru as $rw)
                        <div class="py-3 flex items-start justify-between gap-3 hover:bg-slate-50/60 p-2 rounded-2xl transition-colors">
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-mono text-[10px] font-bold text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200/60">
                                        {{ $rw->aset->kode_aset ?? '-' }}
                                    </span>
                                    <span class="text-xs font-bold text-slate-900 truncate">
                                        {{ $rw->aset->nama_aset ?? 'Aset' }}
                                    </span>
                                </div>
                                <div class="mt-1 text-xs text-slate-600 font-medium">
                                    PIC: <strong class="text-slate-800">{{ $rw->penanggungJawab->nama ?? '-' }}</strong> • Lokasi: {{ $rw->lokasi->nama_lokasi ?? '-' }}
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    {{ $rw->keterangan ?: 'Perubahan status riwayat penempatan.' }}
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                    {{ $rw->kondisi_persen }}% kondisi
                                </span>
                                <div class="text-[10px] text-slate-400 mt-1 font-mono">
                                    {{ \Carbon\Carbon::parse($rw->sejak_tanggal)->translatedFormat('d M Y') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-slate-400 text-xs italic">Belum ada riwayat mutasi.</div>
                    @endforelse
                </div>
            </div>

            <!-- WIDGET 3: 5 JURNAL TERAKHIR (DITAMBAH / DIUBAH) -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                            <i class="ti ti-notes text-base"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-extrabold text-slate-900">Jurnal Peristiwa Terakhir</h4>
                            <p class="text-[11px] text-slate-400">Insiden, servis, dan perbaikan aset</p>
                        </div>
                    </div>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($jurnalTerakhir as $jnl)
                        <div class="py-3 flex items-start justify-between gap-3 hover:bg-slate-50/60 p-2 rounded-2xl transition-colors">
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-mono text-[10px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200/60">
                                        {{ $jnl->aset->kode_aset ?? '-' }}
                                    </span>
                                    <span class="text-xs font-bold text-slate-900 truncate">
                                        {{ $jnl->aset->nama_aset ?? 'Aset' }}
                                    </span>
                                </div>
                                <div class="mt-1 text-xs text-slate-700 font-medium line-clamp-2">
                                    {{ $jnl->kejadian }}
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="px-2 py-0.5 rounded-full text-[9.5px] font-bold uppercase {{ ($jnl->status_penanganan === 'selesai') ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $jnl->status_penanganan ?: 'tercatat' }}
                                </span>
                                <div class="text-[10px] text-slate-400 mt-1 font-mono">
                                    {{ \Carbon\Carbon::parse($jnl->tanggal)->translatedFormat('d M Y') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-slate-400 text-xs italic">Belum ada catatan jurnal insiden.</div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- ===================================================================== -->
        <!-- KOLOM KANAN: WIDGET 4 (TRANSAKSI KEUANGAN), WIDGET 5 (JEJAK AUDIT)    -->
        <!-- ===================================================================== -->
        <div class="space-y-6">

            <!-- WIDGET 4: 5 TRANSAKSI KEUANGAN / PENGELUARAN BIAYA TERBARU -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <i class="ti ti-receipt text-base"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-extrabold text-slate-900">Transaksi Biaya Terbaru</h4>
                            <p class="text-[11px] text-slate-400">Arus pengeluaran & biaya pemeliharaan aset</p>
                        </div>
                    </div>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($keuanganTerbaru as $keu)
                        <div class="py-3 flex items-center justify-between gap-3 hover:bg-slate-50/60 p-2 rounded-2xl transition-colors">
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-1.5 py-0.5 rounded text-[9.5px] font-bold uppercase bg-rose-100 text-rose-800">
                                        Pengeluaran
                                    </span>
                                    <span class="font-bold text-xs text-slate-900 truncate">
                                        {{ $keu->keterangan ?: ($keu->jenis_transaksi ?: 'Biaya Perawatan') }}
                                    </span>
                                </div>
                                <div class="mt-0.5 text-[10px] text-slate-400 truncate">
                                    {{ $keu->aset->nama_aset ?? '-' }} ({{ $keu->aset->kode_aset ?? '-' }})
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="text-xs font-extrabold font-mono text-rose-600">
                                    -{{ $formatRupiah($keu->nominal) }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-mono">
                                    {{ \Carbon\Carbon::parse($keu->tanggal)->translatedFormat('d M Y') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-slate-400 text-xs italic">Belum ada pengeluaran biaya aset tercatat.</div>
                    @endforelse
                </div>
            </div>

            <!-- WIDGET 5: 5 JEJAK AUDIT SISTEM TERBARU -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
                            <i class="ti ti-shield-check text-base"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-extrabold text-slate-900">Jejak Audit Aktivitas Sistem</h4>
                            <p class="text-[11px] text-slate-400">Log perubahan data dan aktivitas pengguna</p>
                        </div>
                    </div>
                    @if(auth()->check() && auth()->user()->role === 'super_admin')
                        <a href="{{ route('audit-log.index') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900">
                            Log Lengkap →
                        </a>
                    @endif
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($auditLogTerbaru as $aud)
                        <div class="py-3 flex items-start space-x-3 hover:bg-slate-50/60 p-2 rounded-2xl transition-colors">
                            <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center flex-shrink-0 mt-0.5">
                                {{ strtoupper(substr($aud->user->name ?? 'A', 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-1">
                                    <span class="font-extrabold text-xs text-slate-900">
                                        {{ $aud->user->name ?? 'Sistem' }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-mono">
                                        {{ $aud->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <div class="mt-0.5 text-xs text-slate-600 line-clamp-1">
                                    {{ $aud->deskripsi }}
                                </div>
                                @if($aud->kode_aset)
                                    <span class="mt-1 inline-block font-mono text-[9.5px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.2 rounded border border-emerald-200/60">
                                        {{ $aud->kode_aset }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-slate-400 text-xs italic">Belum ada jejak audit tercatat.</div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>

<!-- ========================================================================= -->
<!-- APEXCHARTS JAVASCRIPT INITIALIZATION (apexcharts)                          -->
<!-- ========================================================================= -->
<script>
    (function () {
        // ---------------------------------------------------------------------
        // DATA PAYLOAD FROM CONTROLLER
        // ---------------------------------------------------------------------
        const kategoriLabels = @json($chartKategoriLabels);
        const jumlahTetapData = @json($chartKategoriJumlahTetap);
        const jumlahKelolaanData = @json($chartKategoriJumlahKelolaan);
        const nilaiTetapData = @json($chartKategoriNilaiTetap);
        const nilaiKelolaanData = @json($chartKategoriNilaiKelolaan);
        const nilaiPenyusutanData = @json($chartNilaiPenyusutan);

        // Helper format rupiah
        const formatRupiahJs = (val) => 'Rp ' + Number(val || 0).toLocaleString('id-ID');

        // Function toggle unit vs nilai rupiah
        window.toggleChartKategori = function(mode) {
            if (!window.chartKategori) return;

            if (mode === 'unit') {
                window.chartKategori.updateOptions({
                    series: [
                        { name: 'Aset Tetap', data: jumlahTetapData },
                        { name: 'Aset Kelolaan', data: jumlahKelolaanData }
                    ],
                    yaxis: {
                        labels: {
                            formatter: function (val) {
                                return Number(val).toLocaleString('id-ID');
                            }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return Number(val).toLocaleString('id-ID') + ' Unit';
                            }
                        }
                    }
                });
            } else {
                window.chartKategori.updateOptions({
                    series: [
                        { name: 'Aset Tetap', data: nilaiTetapData },
                        { name: 'Aset Kelolaan', data: nilaiKelolaanData }
                    ],
                    yaxis: {
                        labels: {
                            formatter: function (val) {
                                if (val >= 1000000000) return (val / 1000000000).toFixed(1) + ' M';
                                if (val >= 1000000) return (val / 1000000).toFixed(1) + ' Jt';
                                return Number(val).toLocaleString('id-ID');
                            }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return formatRupiahJs(val);
                            }
                        }
                    }
                });
            }
        };

        function destroyExistingCharts() {
            if (window.chartKategori && typeof window.chartKategori.destroy === 'function') {
                window.chartKategori.destroy();
                window.chartKategori = null;
            }
            if (window.chartPenyusutan && typeof window.chartPenyusutan.destroy === 'function') {
                window.chartPenyusutan.destroy();
                window.chartPenyusutan = null;
            }
        }

        function initDashboardCharts() {
            // 1. Destroy any existing instances to avoid memory leaks & duplicate charts
            destroyExistingCharts();

            const containerKategori = document.querySelector("#apexChartKategori");
            const containerPenyusutan = document.querySelector("#apexChartPenyusutan");
            if (!containerKategori || !containerPenyusutan) return;

            // 2. Ensure containers are completely cleared
            containerKategori.innerHTML = '';
            containerPenyusutan.innerHTML = '';

            if (typeof ApexCharts === 'undefined') {
                console.warn('ApexCharts is not available.');
                return;
            }

            // ---------------------------------------------------------------------
            // 1. APEXCHART KATEGORI (GROUPED BAR / COLUMN)
            // ---------------------------------------------------------------------
            const chartKategoriOptions = {
                chart: {
                    type: 'bar',
                    height: 320,
                    toolbar: { show: false },
                    fontFamily: 'inherit',
                },
                colors: ['#059669', '#0284c7'],
                series: [
                    { name: 'Aset Tetap', data: jumlahTetapData },
                    { name: 'Aset Kelolaan', data: jumlahKelolaanData }
                ],
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '50%',
                        borderRadius: 6,
                    },
                },
                dataLabels: { enabled: false },
                stroke: { show: true, width: 2, colors: ['transparent'] },
                xaxis: {
                    categories: kategoriLabels,
                    labels: {
                        style: { fontSize: '11px', fontWeight: 600, colors: '#64748b' }
                    }
                },
                yaxis: {
                    labels: {
                        style: { fontSize: '11px', fontWeight: 600, colors: '#64748b' },
                        formatter: function (val) {
                            return Number(val).toLocaleString('id-ID');
                        }
                    }
                },
                fill: { opacity: 1 },
                tooltip: {
                    theme: 'light',
                    y: {
                        formatter: function (val) {
                            return Number(val).toLocaleString('id-ID') + ' Unit';
                        }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    fontWeight: 600,
                    fontSize: '12px',
                    labels: { colors: '#334155' }
                },
                grid: {
                    borderColor: '#f1f5f9',
                    strokeDashArray: 3,
                }
            };

            window.chartKategori = new ApexCharts(containerKategori, chartKategoriOptions);
            window.chartKategori.render();

            // ---------------------------------------------------------------------
            // 2. APEXCHART VALUASI & PENYUSUTAN (GROUPED COLUMN)
            // ---------------------------------------------------------------------
            const chartPenyusutanOptions = {
                chart: {
                    type: 'bar',
                    height: 320,
                    toolbar: { show: false },
                    fontFamily: 'inherit',
                },
                colors: ['#3b82f6', '#f59e0b', '#10b981'],
                series: [
                    { name: 'Nilai Awal', data: nilaiPenyusutanData.nilai_awal },
                    { name: 'Total Penyusutan', data: nilaiPenyusutanData.penyusutan },
                    { name: 'Nilai Buku Sekarang', data: nilaiPenyusutanData.nilai_sekarang }
                ],
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        borderRadius: 6,
                    },
                },
                dataLabels: { enabled: false },
                stroke: { show: true, width: 2, colors: ['transparent'] },
                xaxis: {
                    categories: nilaiPenyusutanData.labels,
                    labels: {
                        style: { fontSize: '12px', fontWeight: 700, colors: '#334155' }
                    }
                },
                yaxis: {
                    labels: {
                        style: { fontSize: '10.5px', fontWeight: 600, colors: '#64748b' },
                        formatter: function (val) {
                            if (val >= 1000000000) return (val / 1000000000).toFixed(1) + ' M';
                            if (val >= 1000000) return (val / 1000000).toFixed(1) + ' Jt';
                            return Number(val).toLocaleString('id-ID');
                        }
                    }
                },
                fill: { opacity: 1 },
                tooltip: {
                    theme: 'light',
                    y: {
                        formatter: function (val) {
                            return formatRupiahJs(val);
                        }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    fontWeight: 600,
                    fontSize: '11px',
                    labels: { colors: '#334155' }
                },
                grid: {
                    borderColor: '#f1f5f9',
                    strokeDashArray: 3,
                }
            };

            window.chartPenyusutan = new ApexCharts(containerPenyusutan, chartPenyusutanOptions);
            window.chartPenyusutan.render();
        }

        // Clean up charts before SPA navigator replaces page content
        document.addEventListener('amana:before-page-unload', destroyExistingCharts, { once: true });

        // Safe idempotent execution
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDashboardCharts, { once: true });
        } else {
            initDashboardCharts();
        }
    })();
</script>
@endsection
