@props(['name', 'label', 'model', 'desc' => null])

<div class="flex items-start justify-between p-3 rounded-xl border border-slate-100 bg-slate-50/50 hover:bg-slate-50 hover:border-slate-200 transition-colors">
    <div class="pr-3 select-none">
        <label for="{{ $name }}" class="text-xs font-bold text-slate-800 cursor-pointer block">
            {{ $label }}
        </label>
        @if($desc)
            <p class="text-[10px] text-slate-500 mt-0.5 leading-tight">{{ $desc }}</p>
        @endif
    </div>

    <!-- Toggle Switch Button -->
    <div class="flex-shrink-0 pt-0.5">
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox"
                   id="{{ $name }}"
                   name="{{ $name }}"
                   value="1"
                   x-model="{{ $model }}"
                   class="sr-only peer">
            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
        </label>
    </div>
</div>
