@props(['status'])

@if($status === 'aktif')
    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-emerald-500"></span> Aktif
    </span>
@else
    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
        <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-slate-400"></span> Non-Aktif
    </span>
@endif
