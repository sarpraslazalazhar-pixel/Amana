@props(['label', 'value', 'sublabel' => null, 'color' => 'emerald'])

@php
    $accentGradient = [
        'emerald' => 'from-emerald-500 to-teal-600',
        'blue' => 'from-blue-500 to-cyan-600',
        'indigo' => 'from-indigo-500 to-blue-600',
        'amber' => 'from-amber-500 to-orange-600',
        'rose' => 'from-rose-500 to-pink-600',
    ][$color] ?? 'from-cyan-500 to-emerald-600';
@endphp

<div class="relative overflow-hidden p-6 bg-white rounded-3xl border border-slate-200/80 shadow-sm transition-all duration-300 hover:shadow-md hover:border-slate-300 hover:-translate-y-0.5 group">
    <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl {{ $accentGradient }} opacity-10 blur-xl group-hover:opacity-20 transition-opacity"></div>
    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">{{ $label }}</p>
    <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ $value }}</h3>
    @if($sublabel)
        <span class="inline-block mt-3 px-2.5 py-1 text-[10px] font-bold rounded-lg bg-slate-100 text-slate-600 border border-slate-200/80">
            {{ $sublabel }}
        </span>
    @endif
</div>
