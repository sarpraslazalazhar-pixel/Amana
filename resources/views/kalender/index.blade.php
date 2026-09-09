@extends('layouts.app')

@section('title', 'Kalender Aset Terpadu - ' . $month_name)
@section('header-title', 'Kalender Aset Terpadu')

@section('content')
<div class="space-y-6" x-data="{
    viewMode: 'table', // 'table' atau 'grid'
    hideEmptyDays: false,
    selectedEvent: null,
    selectedDay: null,
    eventModalOpen: false,
    dayModalOpen: false,
    openEventModal(event) {
        this.selectedEvent = event;
        this.eventModalOpen = true;
    },
    openDayModal(day) {
        this.selectedDay = day;
        this.dayModalOpen = true;
    }
}" @keydown.escape.window="eventModalOpen = false; dayModalOpen = false">

    <!-- ========================================================================= -->
    <!-- TOP BAR: JUDUL, NAVIGATOR BULAN, & VIEW SWITCHER                          -->
    <!-- ========================================================================= -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <div class="flex items-center space-x-2.5">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white flex items-center justify-center shadow-md shadow-emerald-500/20">
                    <i class="ti ti-calendar-event text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Kalender Aset Terpadu</h2>
                    <p class="text-xs text-slate-500">Pantau Agenda, Jurnal, Transaksi Keuangan, dan Mutasi Riwayat Aset dalam satu linimasa.</p>
                </div>
            </div>
        </div>

        <!-- Month Navigator & Switcher -->
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Navigasi Bulan & Dropdown Picker -->
            <div class="flex items-center bg-slate-50 border border-slate-200/90 rounded-xl p-1 shadow-2xs">
                <!-- Tombol Bulan Sebelumnya -->
                <a href="{{ route('kalender.index', array_merge(request()->query(), ['month' => $prev_month, 'year' => $prev_year])) }}"
                   class="p-2 rounded-lg hover:bg-white text-slate-600 hover:text-emerald-700 transition-colors shadow-2xs"
                   title="Bulan Sebelumnya">
                    <i class="ti ti-chevron-left text-base"></i>
                </a>

                <!-- Form Pilih Bulan & Tahun Cepat -->
                <form method="GET" action="{{ route('kalender.index') }}" class="flex items-center gap-1 px-1">
                    @if(!empty($filters['kategori_id']))
                        <input type="hidden" name="kategori_id" value="{{ $filters['kategori_id'] }}">
                    @endif
                    @if(!empty($filters['lokasi_id']))
                        <input type="hidden" name="lokasi_id" value="{{ $filters['lokasi_id'] }}">
                    @endif
                    @if(!empty($filters['penanggung_jawab_id']))
                        <input type="hidden" name="penanggung_jawab_id" value="{{ $filters['penanggung_jawab_id'] }}">
                    @endif
                    @if(!empty($filters['q']))
                        <input type="hidden" name="q" value="{{ $filters['q'] }}">
                    @endif
                    @foreach($selectedTypes as $t)
                        <input type="hidden" name="types[]" value="{{ $t }}">
                    @endforeach

                    <!-- Select Bulan -->
                    @php
                        $bulanList = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                        ];
                    @endphp
                    <select name="month" onchange="this.form.submit()"
                            class="rounded-lg border-0 bg-transparent text-xs sm:text-sm font-extrabold text-slate-800 focus:ring-2 focus:ring-emerald-500 py-1 pl-2 pr-6 cursor-pointer hover:bg-white/80 transition-colors"
                            title="Pilih Bulan">
                        @foreach($bulanList as $mNum => $mNama)
                            <option value="{{ $mNum }}" {{ $month == $mNum ? 'selected' : '' }}>
                                {{ $mNama }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Select Tahun -->
                    @php
                        $currYear = (int) now()->year;
                        $startYear = $currYear - 5;
                        $endYear = $currYear + 5;
                    @endphp
                    <select name="year" onchange="this.form.submit()"
                            class="rounded-lg border-0 bg-transparent text-xs sm:text-sm font-extrabold text-slate-800 focus:ring-2 focus:ring-emerald-500 py-1 pl-2 pr-6 cursor-pointer hover:bg-white/80 transition-colors"
                            title="Pilih Tahun">
                        @for($y = $startYear; $y <= $endYear; $y++)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </form>

                <!-- Tombol Bulan Berikutnya -->
                <a href="{{ route('kalender.index', array_merge(request()->query(), ['month' => $next_month, 'year' => $next_year])) }}"
                   class="p-2 rounded-lg hover:bg-white text-slate-600 hover:text-emerald-700 transition-colors shadow-2xs"
                   title="Bulan Berikutnya">
                    <i class="ti ti-chevron-right text-base"></i>
                </a>
            </div>

            <!-- Tombol Hari Ini -->
            @if($month != now()->month || $year != now()->year)
                <a href="{{ route('kalender.index', array_merge(request()->except(['month', 'year']), ['month' => now()->month, 'year' => now()->year])) }}"
                   class="px-3 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-extrabold text-xs border border-emerald-200 transition-colors flex items-center gap-1">
                    <i class="ti ti-calendar-time text-sm"></i>
                    <span>Bulan Ini</span>
                </a>
            @endif

            <!-- View Switcher (Tabel Rekap vs Grid Kalender) -->
            <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200">
                <button @click="viewMode = 'table'"
                        :class="viewMode === 'table' ? 'bg-white text-emerald-700 font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                        class="px-3 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5">
                    <i class="ti ti-table text-sm"></i>
                    <span class="hidden sm:inline">Tabel Rekap</span>
                </button>
                <button @click="viewMode = 'grid'"
                        :class="viewMode === 'grid' ? 'bg-white text-emerald-700 font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                        class="px-3 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5">
                    <i class="ti ti-calendar text-sm"></i>
                    <span class="hidden sm:inline">Grid Kalender</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 5 STATISTIC KPI TILES                                                     -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
        <!-- Total Aktivitas -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center space-x-3.5">
            <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center flex-shrink-0">
                <i class="ti ti-activity text-xl"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Aktivitas</p>
                <h3 class="text-xl font-extrabold text-slate-900">{{ number_format($stats['total_aktivitas']) }}</h3>
                <p class="text-[10px] text-slate-500 font-medium">{{ $stats['hari_dengan_aktivitas'] }} hari aktif</p>
            </div>
        </div>

        <!-- Agenda Perawatan -->
        <div class="bg-white p-4 rounded-2xl border border-blue-100 shadow-xs flex items-center space-x-3.5">
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="ti ti-calendar-check text-xl"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-blue-500 uppercase tracking-wider">Agenda Servis</p>
                <h3 class="text-xl font-extrabold text-blue-900">{{ number_format($stats['total_agenda']) }}</h3>
                <p class="text-[10px] text-blue-600 font-medium">{{ $stats['agenda_selesai'] }} selesai • {{ $stats['agenda_pending'] }} pending</p>
            </div>
        </div>

        <!-- Jurnal Insiden -->
        <div class="bg-white p-4 rounded-2xl border border-amber-100 shadow-xs flex items-center space-x-3.5">
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="ti ti-notes text-xl"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-amber-500 uppercase tracking-wider">Jurnal Peristiwa</p>
                <h3 class="text-xl font-extrabold text-amber-900">{{ number_format($stats['total_jurnal']) }}</h3>
                <p class="text-[10px] text-amber-600 font-medium">Log insiden & servis</p>
            </div>
        </div>

        <!-- Transaksi Keuangan -->
        <div class="bg-white p-4 rounded-2xl border border-emerald-100 shadow-xs flex items-center space-x-3.5">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="ti ti-wallet text-xl"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-emerald-500 uppercase tracking-wider">Biaya & Transaksi</p>
                <h3 class="text-xl font-extrabold text-emerald-900">{{ number_format($stats['total_keuangan']) }}</h3>
                <p class="text-[10px] text-rose-600 font-medium">Rp {{ number_format($stats['keuangan_pengeluaran'], 0, ',', '.') }} pengeluaran</p>
            </div>
        </div>

        <!-- Riwayat Mutasi -->
        <div class="bg-white p-4 rounded-2xl border border-purple-100 shadow-xs flex items-center space-x-3.5 col-span-2 sm:col-span-1">
            <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="ti ti-history text-xl"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-purple-500 uppercase tracking-wider">Mutasi Riwayat</p>
                <h3 class="text-xl font-extrabold text-purple-900">{{ number_format($stats['total_riwayat']) }}</h3>
                <p class="text-[10px] text-purple-600 font-medium">Perubahan PIC/Lokasi</p>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- FILTER TOOLBAR                                                            -->
    <!-- ========================================================================= -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
        <form method="GET" action="{{ route('kalender.index') }}" class="space-y-3">
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">

            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <!-- Tipe Aktivitas Pills Toggle -->
                <div class="flex items-center flex-wrap gap-2 text-xs">
                    <span class="font-bold text-slate-500 mr-1 text-[11px] uppercase tracking-wider">Tampilkan:</span>
                    
                    <!-- Agenda -->
                    <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border transition-all {{ in_array('agenda', $selectedTypes) ? 'bg-blue-50 border-blue-300 text-blue-800 font-bold' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                        <input type="checkbox" name="types[]" value="agenda" {{ in_array('agenda', $selectedTypes) ? 'checked' : '' }} onchange="this.form.submit()" class="rounded text-blue-600 focus:ring-blue-500">
                        <span>Agenda ({{ $stats['total_agenda'] }})</span>
                    </label>

                    <!-- Jurnal -->
                    <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border transition-all {{ in_array('jurnal', $selectedTypes) ? 'bg-amber-50 border-amber-300 text-amber-800 font-bold' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                        <input type="checkbox" name="types[]" value="jurnal" {{ in_array('jurnal', $selectedTypes) ? 'checked' : '' }} onchange="this.form.submit()" class="rounded text-amber-600 focus:ring-amber-500">
                        <span>Jurnal ({{ $stats['total_jurnal'] }})</span>
                    </label>

                    <!-- Keuangan -->
                    <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border transition-all {{ in_array('keuangan', $selectedTypes) ? 'bg-emerald-50 border-emerald-300 text-emerald-800 font-bold' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                        <input type="checkbox" name="types[]" value="keuangan" {{ in_array('keuangan', $selectedTypes) ? 'checked' : '' }} onchange="this.form.submit()" class="rounded text-emerald-600 focus:ring-emerald-500">
                        <span>Transaksi ({{ $stats['total_keuangan'] }})</span>
                    </label>

                    <!-- Riwayat -->
                    <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border transition-all {{ in_array('riwayat', $selectedTypes) ? 'bg-purple-50 border-purple-300 text-purple-800 font-bold' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                        <input type="checkbox" name="types[]" value="riwayat" {{ in_array('riwayat', $selectedTypes) ? 'checked' : '' }} onchange="this.form.submit()" class="rounded text-purple-600 focus:ring-purple-500">
                        <span>Riwayat ({{ $stats['total_riwayat'] }})</span>
                    </label>
                </div>

                <!-- Toggle Sembunyikan Hari Kosong -->
                <div class="flex items-center gap-2 text-xs" x-show="viewMode === 'table'">
                    <label class="cursor-pointer inline-flex items-center gap-2 text-slate-600 font-medium">
                        <input type="checkbox" x-model="hideEmptyDays" class="rounded text-emerald-600 focus:ring-emerald-500">
                        <span>Sembunyikan Hari Kosong</span>
                    </label>
                </div>
            </div>

            <!-- Row 2 Filters: Kategori, Lokasi, PJ, Search -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 pt-2 border-t border-slate-100 text-xs">
                <!-- Filter Kategori -->
                <div>
                    <select name="kategori_id" onchange="this.form.submit()" class="w-full rounded-xl border-slate-200 text-xs focus:ring-emerald-500 focus:border-emerald-500 bg-slate-50 py-2">
                        <option value="">Semua Kategori</option>
                        @foreach($kategoriList as $kat)
                            <option value="{{ $kat->id }}" {{ ($filters['kategori_id'] ?? '') == $kat->id ? 'selected' : '' }}>
                                {{ $kat->nama_kategori }} ({{ $kat->kode_kategori }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Lokasi -->
                <div>
                    <select name="lokasi_id" onchange="this.form.submit()" class="w-full rounded-xl border-slate-200 text-xs focus:ring-emerald-500 focus:border-emerald-500 bg-slate-50 py-2">
                        <option value="">Semua Lokasi</option>
                        @foreach($lokasiList as $lok)
                            <option value="{{ $lok->id }}" {{ ($filters['lokasi_id'] ?? '') == $lok->id ? 'selected' : '' }}>
                                {{ $lok->nama_lokasi }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Penanggung Jawab -->
                <div>
                    <select name="penanggung_jawab_id" onchange="this.form.submit()" class="w-full rounded-xl border-slate-200 text-xs focus:ring-emerald-500 focus:border-emerald-500 bg-slate-50 py-2">
                        <option value="">Semua Penanggung Jawab</option>
                        @foreach($pjList as $pj)
                            <option value="{{ $pj->id }}" {{ ($filters['penanggung_jawab_id'] ?? '') == $pj->id ? 'selected' : '' }}>
                                {{ $pj->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Search Input & Reset -->
                <div class="flex items-center gap-1.5">
                    <div class="relative flex-1">
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama / kode aset..."
                               class="w-full pl-8 pr-3 py-2 rounded-xl border-slate-200 text-xs focus:ring-emerald-500 focus:border-emerald-500 bg-slate-50">
                        <i class="ti ti-search absolute left-2.5 top-2.5 text-slate-400 text-sm"></i>
                    </div>
                    <button type="submit" class="p-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white transition-colors" title="Terapkan">
                        <i class="ti ti-filter text-sm"></i>
                    </button>
                    @if(!empty($filters['kategori_id']) || !empty($filters['lokasi_id']) || !empty($filters['penanggung_jawab_id']) || !empty($filters['q']))
                        <a href="{{ route('kalender.index', ['month' => $month, 'year' => $year]) }}" class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 transition-colors" title="Reset Filter">
                            <i class="ti ti-x text-sm"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW MODE 1: TABEL REKAP HARIAN (Hari, Tgl, Agenda, Jurnal, Transaksi, Riwayat) -->
    <!-- ========================================================================= -->
    <div x-show="viewMode === 'table'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-800 text-white font-extrabold uppercase text-[10.5px] tracking-wider divide-x divide-slate-700">
                        <th class="py-3.5 px-3 w-28 text-center">Hari</th>
                        <th class="py-3.5 px-3 w-32 text-center">Tgl</th>
                        <th class="py-3.5 px-3 min-w-[200px] text-blue-300">
                            <span class="inline-flex items-center gap-1"><i class="ti ti-calendar-check text-sm"></i> Agenda</span>
                        </th>
                        <th class="py-3.5 px-3 min-w-[200px] text-amber-300">
                            <span class="inline-flex items-center gap-1"><i class="ti ti-notes text-sm"></i> Jurnal</span>
                        </th>
                        <th class="py-3.5 px-3 min-w-[200px] text-emerald-300">
                            <span class="inline-flex items-center gap-1"><i class="ti ti-wallet text-sm"></i> Transaksi</span>
                        </th>
                        <th class="py-3.5 px-3 min-w-[200px] text-purple-300">
                            <span class="inline-flex items-center gap-1"><i class="ti ti-history text-sm"></i> Riwayat</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80">
                    @foreach($days_data as $day)
                        <tr class="transition-colors hover:bg-slate-50/70 {{ $day['is_today'] ? 'bg-emerald-50/40 font-semibold' : ($day['is_weekend'] ? 'bg-slate-50/30' : '') }}"
                            x-show="!hideEmptyDays || {{ $day['total_aktivitas'] > 0 ? 'true' : 'false' }}">
                            
                            <!-- 1. Hari -->
                            <td class="py-3.5 px-3 text-center align-top border-r border-slate-200/80">
                                <div class="font-extrabold {{ $day['is_today'] ? 'text-emerald-700 font-black' : ($day['is_weekend'] ? 'text-rose-600' : 'text-slate-800') }}">
                                    {{ $day['day_name'] }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $day['day_short'] }}</div>
                            </td>

                            <!-- 2. Tgl -->
                            <td class="py-3.5 px-3 text-center align-top border-r border-slate-200/80">
                                <div class="inline-flex items-center justify-center">
                                    <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-mono font-extrabold {{ $day['is_today'] ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $day['day_number'] }} {{ \Carbon\Carbon::parse($day['date'])->translatedFormat('M') }}
                                    </span>
                                </div>
                                @if($day['is_today'])
                                    <span class="block mt-1 text-[9px] font-black uppercase text-emerald-700 tracking-wider">HARI INI</span>
                                @endif
                            </td>

                            <!-- 3. Agenda -->
                            <td class="py-3 px-3 align-top border-r border-slate-200/80 space-y-1.5">
                                @forelse($day['agenda'] as $ag)
                                    <div @click="openEventModal(@js($ag))"
                                         class="p-2.5 rounded-xl bg-blue-50/80 hover:bg-blue-100/90 border border-blue-200/90 cursor-pointer transition-all shadow-2xs hover:shadow-xs group">
                                        <div class="flex items-center justify-between gap-1">
                                            <span class="font-mono text-[10px] font-bold text-blue-700 bg-white/80 px-1.5 py-0.5 rounded border border-blue-200">
                                                {{ $ag['kode_aset'] }}
                                            </span>
                                            <span class="text-[9.5px] font-bold uppercase px-1.5 py-0.5 rounded {{ ($ag['status'] ?? '') === 'selesai' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-200/70 text-blue-800' }}">
                                                {{ $ag['status'] ?? 'pending' }}
                                            </span>
                                        </div>
                                        <div class="mt-1 font-bold text-slate-800 group-hover:text-blue-900 leading-tight">
                                            {{ $ag['title'] }}
                                        </div>
                                        <div class="mt-1 text-[10px] text-slate-500 flex items-center justify-between">
                                            <span class="truncate">{{ $ag['nama_aset'] }}</span>
                                            @if($ag['biaya_estimasi'] > 0)
                                                <span class="font-mono font-bold text-slate-700">Rp {{ number_format($ag['biaya_estimasi'], 0, ',', '.') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <span class="text-slate-300 select-none block py-1">-</span>
                                @endforelse
                            </td>

                            <!-- 4. Jurnal -->
                            <td class="py-3 px-3 align-top border-r border-slate-200/80 space-y-1.5">
                                @forelse($day['jurnal'] as $j)
                                    <div @click="openEventModal(@js($j))"
                                         class="p-2.5 rounded-xl bg-amber-50/80 hover:bg-amber-100/90 border border-amber-200/90 cursor-pointer transition-all shadow-2xs hover:shadow-xs group">
                                        <div class="flex items-center justify-between gap-1">
                                            <span class="font-mono text-[10px] font-bold text-amber-700 bg-white/80 px-1.5 py-0.5 rounded border border-amber-200">
                                                {{ $j['kode_aset'] }}
                                            </span>
                                            @if($j['tingkat_kerusakan'])
                                                <span class="text-[9.5px] font-bold uppercase px-1.5 py-0.5 rounded bg-amber-200/70 text-amber-900">
                                                    {{ $j['tingkat_kerusakan'] }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="mt-1 font-bold text-slate-800 group-hover:text-amber-900 leading-tight line-clamp-2">
                                            {{ $j['title'] }}
                                        </div>
                                        <div class="mt-1 text-[10px] text-slate-500 truncate">
                                            {{ $j['nama_aset'] }}
                                        </div>
                                    </div>
                                @empty
                                    <span class="text-slate-300 select-none block py-1">-</span>
                                @endforelse
                            </td>

                            <!-- 5. Transaksi -->
                            <td class="py-3 px-3 align-top border-r border-slate-200/80 space-y-1.5">
                                @forelse($day['keuangan'] as $keu)
                                    <div @click="openEventModal(@js($keu))"
                                         class="p-2.5 rounded-xl border cursor-pointer transition-all shadow-2xs hover:shadow-xs group bg-rose-50/80 hover:bg-rose-100/90 border-rose-200/90">
                                        <div class="flex items-center justify-between gap-1">
                                            <span class="font-mono text-[10px] font-bold bg-white/80 px-1.5 py-0.5 rounded border text-rose-700 border-rose-200">
                                                {{ $keu['kode_aset'] }}
                                            </span>
                                            <span class="text-[9.5px] font-bold uppercase px-1.5 py-0.5 rounded bg-rose-200/70 text-rose-900">
                                                Pengeluaran
                                            </span>
                                        </div>
                                        <div class="mt-1 font-bold text-slate-800 leading-tight">
                                            {{ $keu['title'] }}
                                        </div>
                                        <div class="mt-1 flex items-center justify-between text-[10px]">
                                            <span class="text-slate-500 truncate">{{ $keu['nama_aset'] }}</span>
                                            <span class="font-mono font-extrabold text-rose-700">
                                                -{{ $keu['nominal_formatted'] }}
                                            </span>
                                        </div>
                                    </div>
                                @empty
                                    <span class="text-slate-300 select-none block py-1">-</span>
                                @endforelse
                            </td>

                            <!-- 6. Riwayat -->
                            <td class="py-3 px-3 align-top space-y-1.5">
                                @forelse($day['riwayat'] as $r)
                                    <div @click="openEventModal(@js($r))"
                                         class="p-2.5 rounded-xl bg-purple-50/80 hover:bg-purple-100/90 border border-purple-200/90 cursor-pointer transition-all shadow-2xs hover:shadow-xs group">
                                        <div class="flex items-center justify-between gap-1">
                                            <span class="font-mono text-[10px] font-bold text-purple-700 bg-white/80 px-1.5 py-0.5 rounded border border-purple-200">
                                                {{ $r['kode_aset'] }}
                                            </span>
                                            <span class="text-[9.5px] font-bold uppercase px-1.5 py-0.5 rounded bg-purple-200/70 text-purple-900">
                                                {{ $r['jenis_aksi'] ?? 'mutasi' }}
                                            </span>
                                        </div>
                                        <div class="mt-1 font-bold text-slate-800 group-hover:text-purple-900 leading-tight">
                                            {{ $r['title'] }}
                                        </div>
                                        <div class="mt-1 text-[10px] text-slate-500 flex items-center justify-between">
                                            <span class="truncate">PIC: {{ $r['pj_nama'] }}</span>
                                            <span class="text-slate-400">{{ $r['lokasi_nama'] }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <span class="text-slate-300 select-none block py-1">-</span>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW MODE 2: VISUAL GRID KALENDER BULANAN                                 -->
    <!-- ========================================================================= -->
    <div x-show="viewMode === 'grid'" x-cloak class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <!-- 7 Column Day Header -->
        <div class="grid grid-cols-7 bg-slate-800 text-white font-extrabold text-xs uppercase tracking-wider text-center divide-x divide-slate-700">
            <div class="py-3">Senin</div>
            <div class="py-3">Selasa</div>
            <div class="py-3">Rabu</div>
            <div class="py-3">Kamis</div>
            <div class="py-3">Jumat</div>
            <div class="py-3 text-emerald-300">Sabtu</div>
            <div class="py-3 text-rose-300">Minggu</div>
        </div>

        <!-- Days Grid Cells -->
        <div class="grid grid-cols-7 border-t border-slate-200 divide-x divide-y divide-slate-200">
            <!-- Padding Before Start of Month -->
            @for($i = 0; $i < $padding_before; $i++)
                <div class="min-h-[110px] bg-slate-50/50 p-2 text-slate-300 select-none"></div>
            @endfor

            <!-- Day Cells -->
            @foreach($days_data as $day)
                <div class="min-h-[115px] p-2 transition-colors hover:bg-slate-50 flex flex-col justify-between {{ $day['is_today'] ? 'bg-emerald-50/30' : ($day['is_weekend'] ? 'bg-slate-50/40' : '') }}">
                    <!-- Header Cell (Date Number & Badge) -->
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="w-6 h-6 rounded-lg text-xs font-mono font-extrabold flex items-center justify-center {{ $day['is_today'] ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-800' }}">
                            {{ $day['day_number'] }}
                        </span>
                        @if($day['total_aktivitas'] > 0)
                            <button @click="openDayModal(@js($day))"
                                    class="px-1.5 py-0.5 rounded-md bg-slate-200 hover:bg-emerald-600 hover:text-white text-[9.5px] font-bold text-slate-700 transition-colors"
                                    title="Lihat semua kegiatan hari ini">
                                {{ $day['total_aktivitas'] }} item
                            </button>
                        @endif
                    </div>

                    <!-- Event Pills (Max 3 visible) -->
                    <div class="space-y-1 flex-1">
                        @php
                            $allEvents = array_merge($day['agenda'], $day['jurnal'], $day['keuangan'], $day['riwayat']);
                            $visibleEvents = array_slice($allEvents, 0, 3);
                            $extraCount = count($allEvents) - 3;
                        @endphp

                        @foreach($visibleEvents as $ev)
                            <div @click="openEventModal(@js($ev))"
                                 class="px-1.5 py-1 rounded text-[10px] font-semibold truncate cursor-pointer transition-all hover:scale-[1.02] {{ $ev['type'] === 'agenda' ? 'bg-blue-100 text-blue-900 border border-blue-200' : ($ev['type'] === 'jurnal' ? 'bg-amber-100 text-amber-900 border border-amber-200' : ($ev['type'] === 'keuangan' ? 'bg-emerald-100 text-emerald-900 border border-emerald-200' : 'bg-purple-100 text-purple-900 border border-purple-200')) }}"
                                 title="{{ $ev['title'] }} ({{ $ev['kode_aset'] }})">
                                <span class="font-bold">[{{ strtoupper(substr($ev['type'], 0, 1)) }}]</span>
                                <span>{{ $ev['title'] }}</span>
                            </div>
                        @endforeach

                        @if($extraCount > 0)
                            <button @click="openDayModal(@js($day))"
                                    class="w-full text-center py-0.5 text-[9.5px] font-bold text-emerald-700 hover:underline">
                                +{{ $extraCount }} lainnya...
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach

            <!-- Padding After End of Month -->
            @for($i = 0; $i < $padding_after; $i++)
                <div class="min-h-[110px] bg-slate-50/50 p-2 text-slate-300 select-none"></div>
            @endfor
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 1: DETAIL EVENT SPESIFIK                                            -->
    <!-- ========================================================================= -->
    <div x-show="eventModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-4"
             @click.away="eventModalOpen = false">
            
            <!-- Modal Header -->
            <div class="flex items-start justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                         :class="{
                             'bg-blue-100 text-blue-700': selectedEvent?.type === 'agenda',
                             'bg-amber-100 text-amber-700': selectedEvent?.type === 'jurnal',
                             'bg-emerald-100 text-emerald-700': selectedEvent?.type === 'keuangan',
                             'bg-purple-100 text-purple-700': selectedEvent?.type === 'riwayat'
                         }">
                        <i class="ti text-xl"
                           :class="{
                               'ti-calendar-check': selectedEvent?.type === 'agenda',
                               'ti-notes': selectedEvent?.type === 'jurnal',
                               'ti-wallet': selectedEvent?.type === 'keuangan',
                               'ti-history': selectedEvent?.type === 'riwayat'
                           }"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded"
                              :class="{
                                  'bg-blue-100 text-blue-800': selectedEvent?.type === 'agenda',
                                  'bg-amber-100 text-amber-800': selectedEvent?.type === 'jurnal',
                                  'bg-emerald-100 text-emerald-800': selectedEvent?.type === 'keuangan',
                                  'bg-purple-100 text-purple-800': selectedEvent?.type === 'riwayat'
                              }"
                              x-text="selectedEvent?.type"></span>
                        <h4 class="text-base font-extrabold text-slate-900 mt-1" x-text="selectedEvent?.title"></h4>
                    </div>
                </div>
                <button @click="eventModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <!-- Modal Content Details -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-2.5 text-xs">
                <!-- Aset Info -->
                <div class="flex justify-between border-b border-slate-200/80 pb-2">
                    <span class="text-slate-500 font-medium">Aset Terkait:</span>
                    <span class="font-bold text-slate-900" x-text="selectedEvent?.nama_aset"></span>
                </div>
                <div class="flex justify-between border-b border-slate-200/80 pb-2">
                    <span class="text-slate-500 font-medium">Kode Aset:</span>
                    <span class="font-mono font-bold text-emerald-700" x-text="selectedEvent?.kode_aset"></span>
                </div>
                <div class="flex justify-between border-b border-slate-200/80 pb-2">
                    <span class="text-slate-500 font-medium">Lokasi:</span>
                    <span class="font-semibold text-slate-800" x-text="selectedEvent?.lokasi_nama"></span>
                </div>
                <div class="flex justify-between border-b border-slate-200/80 pb-2">
                    <span class="text-slate-500 font-medium">Penanggung Jawab:</span>
                    <span class="font-semibold text-slate-800" x-text="selectedEvent?.pj_nama"></span>
                </div>

                <!-- Type Specific Fields -->
                <template x-if="selectedEvent?.type === 'agenda'">
                    <div class="space-y-2">
                        <div class="flex justify-between border-b border-slate-200/80 pb-2">
                            <span class="text-slate-500 font-medium">Jadwal / Frekuensi:</span>
                            <span class="font-bold text-blue-700" x-text="selectedEvent?.jadwal_teks"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500 font-medium">Biaya Estimasi:</span>
                            <span class="font-mono font-bold text-slate-900" x-text="'Rp ' + Number(selectedEvent?.biaya_estimasi || 0).toLocaleString('id-ID')"></span>
                        </div>
                    </div>
                </template>

                <template x-if="selectedEvent?.type === 'keuangan'">
                    <div class="flex justify-between">
                        <span class="text-slate-500 font-medium">Nominal Biaya:</span>
                        <span class="font-mono font-bold text-rose-700 text-sm" x-text="'- ' + selectedEvent?.nominal_formatted"></span>
                    </div>
                </template>

                <template x-if="selectedEvent?.type === 'jurnal'">
                    <div class="flex justify-between">
                        <span class="text-slate-500 font-medium">Tingkat Kerusakan:</span>
                        <span class="font-bold text-amber-700 uppercase" x-text="selectedEvent?.tingkat_kerusakan || '-'"></span>
                    </div>
                </template>

                <template x-if="selectedEvent?.type === 'riwayat'">
                    <div class="flex justify-between">
                        <span class="text-slate-500 font-medium">Kondisi & Kelengkapan:</span>
                        <span class="font-bold text-purple-700" x-text="selectedEvent?.kondisi_persen + '% kondisi • ' + selectedEvent?.kelengkapan_persen + '% lengkap'"></span>
                    </div>
                </template>
            </div>

            <!-- Action Button -->
            <div class="flex items-center justify-end gap-2 pt-2">
                <button @click="eventModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs">
                    Tutup
                </button>
                <a :href="'/aset/' + selectedEvent?.aset_id + (selectedEvent?.type === 'agenda' ? '?tab=agenda' : '')"
                   class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs inline-flex items-center gap-1.5 shadow-xs">
                    <span>Lihat Detail Aset</span>
                    <i class="ti ti-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2: DETAIL SELURUH KEGIATAN PADA SATU TANGGAL                        -->
    <!-- ========================================================================= -->
    <div x-show="dayModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 space-y-4 max-h-[85vh] flex flex-col"
             @click.away="dayModalOpen = false">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-black">
                        <span x-text="selectedDay?.day_number"></span>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900" x-text="selectedDay?.day_name + ', ' + selectedDay?.formatted_date"></h3>
                        <p class="text-xs text-slate-500" x-text="selectedDay?.total_aktivitas + ' total kegiatan pada tanggal ini'"></p>
                    </div>
                </div>
                <button @click="dayModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <!-- List of all events in this day -->
            <div class="space-y-3 overflow-y-auto flex-1 pr-1 text-xs">
                <!-- Agenda Items -->
                <template x-for="ag in (selectedDay?.agenda || [])" :key="'ag-'+ag.id">
                    <div class="p-3 rounded-2xl bg-blue-50/90 border border-blue-200/90 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded bg-blue-600 text-white font-bold text-[9px] uppercase">AGENDA</span>
                            <span class="font-mono font-bold text-blue-800" x-text="ag.kode_aset"></span>
                        </div>
                        <div class="font-bold text-slate-900" x-text="ag.title"></div>
                        <div class="text-slate-500 flex justify-between">
                            <span x-text="ag.nama_aset"></span>
                            <a :href="'/aset/' + ag.aset_id" class="text-blue-700 font-bold hover:underline">Buka Aset →</a>
                        </div>
                    </div>
                </template>

                <!-- Jurnal Items -->
                <template x-for="j in (selectedDay?.jurnal || [])" :key="'j-'+j.id">
                    <div class="p-3 rounded-2xl bg-amber-50/90 border border-amber-200/90 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded bg-amber-600 text-white font-bold text-[9px] uppercase">JURNAL</span>
                            <span class="font-mono font-bold text-amber-800" x-text="j.kode_aset"></span>
                        </div>
                        <div class="font-bold text-slate-900" x-text="j.title"></div>
                        <div class="text-slate-500 flex justify-between">
                            <span x-text="j.nama_aset"></span>
                            <a :href="'/aset/' + j.aset_id" class="text-amber-700 font-bold hover:underline">Buka Aset →</a>
                        </div>
                    </div>
                </template>

                <!-- Keuangan Items -->
                <template x-for="k in (selectedDay?.keuangan || [])" :key="'k-'+k.id">
                    <div class="p-3 rounded-2xl bg-emerald-50/90 border border-emerald-200/90 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded bg-emerald-600 text-white font-bold text-[9px] uppercase">TRANSAKSI</span>
                            <span class="font-mono font-bold text-emerald-800" x-text="k.nominal_formatted"></span>
                        </div>
                        <div class="font-bold text-slate-900" x-text="k.title"></div>
                        <div class="text-slate-500 flex justify-between">
                            <span x-text="k.nama_aset + ' (' + k.kode_aset + ')'"></span>
                            <a :href="'/aset/' + k.aset_id" class="text-emerald-700 font-bold hover:underline">Buka Aset →</a>
                        </div>
                    </div>
                </template>

                <!-- Riwayat Items -->
                <template x-for="r in (selectedDay?.riwayat || [])" :key="'r-'+r.id">
                    <div class="p-3 rounded-2xl bg-purple-50/90 border border-purple-200/90 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded bg-purple-600 text-white font-bold text-[9px] uppercase">RIWAYAT MUTASI</span>
                            <span class="font-mono font-bold text-purple-800" x-text="r.kode_aset"></span>
                        </div>
                        <div class="font-bold text-slate-900" x-text="r.title"></div>
                        <div class="text-slate-500 flex justify-between">
                            <span x-text="r.nama_aset + ' • PIC: ' + r.pj_nama"></span>
                            <a :href="'/aset/' + r.aset_id" class="text-purple-700 font-bold hover:underline">Buka Aset →</a>
                        </div>
                    </div>
                </template>
            </div>

            <div class="pt-2 border-t border-slate-100 flex justify-end">
                <button @click="dayModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
