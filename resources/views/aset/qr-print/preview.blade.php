@extends('layouts.app')

@section('title', 'Preview Lembar Cetak QR Code')

@section('content')
<div class="space-y-6 pb-20"
     x-data="{
         currentPage: 1,
         totalPages: {{ count($pages) }},
         zoomLevel: 100,

         submitAction(routeUrl, targetBlank = false) {
             const form = this.$refs.actionForm;
             form.action = routeUrl;
             form.target = targetBlank ? '_blank' : '_self';
             form.submit();
         }
     }">

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-3xl border border-slate-200/80 shadow-sm sticky top-0 z-30">
        <div class="flex items-center gap-3">
            <a href="{{ route('aset.qr.print', ['selected_ids' => implode(',', array_map(fn($l) => $l['aset']->id, $labels))]) }}"
               class="p-2.5 rounded-2xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition-colors"
               title="Kembali ke Pemilihan Aset">
                <i class="ti ti-arrow-left text-base"></i>
            </a>
            <div>
                <h1 class="text-base font-extrabold text-slate-900 leading-snug">Preview Lembar Cetak Label QR</h1>
                <p class="text-xs text-slate-500">
                    Template: <strong class="text-emerald-700">{{ $template['name'] }}</strong> &bull;
                    Total: <strong class="text-slate-900">{{ count($labels) }} Stiker</strong> ({{ count($pages) }} Lembar)
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Hidden Form untuk Download / Print -->
            <form x-ref="actionForm" method="POST" action="" class="hidden">
                @csrf
                <input type="hidden" name="template_key" value="{{ $template['key'] }}">
                @foreach($labels as $item)
                    <input type="hidden" name="aset_ids[]" value="{{ $item['aset']->id }}">
                @endforeach
            </form>

            <!-- Tombol Unduh PDF -->
            <button type="button" @click="submitAction('{{ route('aset.qr.download-pdf') }}', false)"
                    class="px-4 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition-all inline-flex items-center gap-1.5">
                <i class="ti ti-download text-sm"></i>
                <span>Unduh PDF</span>
            </button>

            <!-- Tombol Cetak Langsung -->
            <button type="button" @click="submitAction('{{ route('aset.qr.print-direct') }}', true)"
                    class="px-4 py-2.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-md shadow-slate-900/20 transition-all inline-flex items-center gap-1.5">
                <i class="ti ti-printer text-sm text-cyan-400"></i>
                <span>Cetak Langsung</span>
            </button>
        </div>
    </div>

    <!-- Sheet Canvas Area -->
    <div class="space-y-8 flex flex-col items-center">
        @foreach($pages as $pageIndex => $pageLabels)
            <div class="w-full max-w-4xl space-y-2">
                <!-- Page Label Info -->
                <div class="flex items-center justify-between text-xs text-slate-500 px-2 font-medium">
                    <span class="flex items-center gap-1.5">
                        <i class="ti ti-file-description text-emerald-600"></i>
                        Lembar Ke-{{ $pageIndex + 1 }} dari {{ count($pages) }}
                    </span>
                    <span>{{ count($pageLabels) }} Label pada lembar ini</span>
                </div>

                <!-- Lembaran Kertas Virtual -->
                <div class="bg-white rounded-3xl shadow-xl border border-slate-200/90 p-6 sm:p-8 min-h-[500px]">
                    
                    @if($template['key'] === 'thermal_single')
                        <!-- Layout Thermal Single 1 Stiker -->
                        <div class="flex items-center justify-center p-4">
                            @foreach($pageLabels as $lbl)
                                <div class="w-[260px] p-5 bg-white rounded-3xl border-2 border-dashed border-slate-300 text-center space-y-3 shadow-md">
                                    <div class="text-[11px] font-extrabold tracking-wider text-slate-900 uppercase border-b border-slate-100 pb-2">
                                        {{ $lbl['title'] }}
                                    </div>
                                    <div class="w-36 h-36 mx-auto flex items-center justify-center bg-white p-1">
                                        <img src="data:image/svg+xml;base64,{{ $lbl['qr_base64'] }}" alt="QR Code" class="w-full h-full object-contain">
                                    </div>
                                    <div class="space-y-1 pt-2 border-t border-slate-100">
                                        <p class="font-extrabold text-sm text-slate-900 truncate">{{ $lbl['row1_text'] }}</p>
                                        <p class="font-mono font-bold text-xs text-emerald-700 truncate">{{ $lbl['row2_text'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                    @elseif($template['key'] === 'a4_grid_12')
                        <!-- Layout A4 3 Kolom x 4 Baris (12 Kotak Besar) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach($pageLabels as $lbl)
                                <div class="p-4 bg-white rounded-2xl border-2 border-dashed border-slate-300 text-center flex flex-col items-center justify-between shadow-2xs hover:border-emerald-400 transition-colors">
                                    <div class="w-full text-[10px] font-extrabold tracking-wide uppercase text-slate-900 border-b border-slate-100 pb-1.5 truncate">
                                        {{ $lbl['title'] }}
                                    </div>
                                    <div class="w-28 h-28 my-2 flex items-center justify-center">
                                        <img src="data:image/svg+xml;base64,{{ $lbl['qr_base64'] }}" alt="QR Code" class="w-full h-full object-contain">
                                    </div>
                                    <div class="w-full pt-1.5 border-t border-slate-100 space-y-0.5">
                                        <p class="font-extrabold text-xs text-slate-900 truncate" title="{{ $lbl['row1_text'] }}">
                                            {{ $lbl['row1_text'] }}
                                        </p>
                                        <p class="font-mono font-bold text-[10px] text-emerald-700 truncate">
                                            {{ $lbl['row2_text'] }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                    @else
                        <!-- Layout Grid 4 Kolom (A4 20 Stiker, A4 24 Stiker, atau TJ 103) -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                            @foreach($pageLabels as $lbl)
                                <div class="p-3 bg-white rounded-2xl border-2 border-dashed border-slate-300 text-center flex flex-col items-center justify-between shadow-2xs hover:border-emerald-400 transition-colors">
                                    <div class="w-full text-[9px] font-extrabold tracking-wide uppercase text-slate-900 border-b border-slate-100 pb-1 truncate">
                                        {{ $lbl['title'] }}
                                    </div>
                                    <div class="w-20 h-20 my-1.5 flex items-center justify-center">
                                        <img src="data:image/svg+xml;base64,{{ $lbl['qr_base64'] }}" alt="QR Code" class="w-full h-full object-contain">
                                    </div>
                                    <div class="w-full pt-1 border-t border-slate-100 space-y-0.5">
                                        <p class="font-extrabold text-[11px] text-slate-900 truncate" title="{{ $lbl['row1_text'] }}">
                                            {{ $lbl['row1_text'] }}
                                        </p>
                                        <p class="font-mono font-bold text-[9px] text-emerald-700 truncate">
                                            {{ $lbl['row2_text'] }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection

