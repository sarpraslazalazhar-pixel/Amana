@props([
    'name',
    'items' => [],
    'value' => '',
    'placeholder' => '-- Pilih --',
    'required' => false,
    'dynamicItems' => null,
    'model' => null,
    'change' => null,
])

<div class="relative"
     x-data="{
        open: false,
        search: '',
        highlightIndex: 0,
        selectedId: @if($model) {{ $model }} @else '{{ $value }}' @endif,
        staticItems: @js($items),

        get items() {
            @if($dynamicItems)
                try {
                    return (typeof {{ $dynamicItems }} !== 'undefined' && {{ $dynamicItems }}) ? {{ $dynamicItems }} : (this.{{ $dynamicItems }} || this.staticItems || []);
                } catch (e) {
                    return this.{{ $dynamicItems }} || this.staticItems || [];
                }
            @else
                return this.staticItems || [];
            @endif
        },

        get filteredItems() {
            const list = this.items || [];
            const q = (this.search || '').trim().toLowerCase();
            if (!q) return list;
            return list.filter(item => {
                const code = String(item.code || '').toLowerCase();
                const title = String(item.title || item.nama || '').toLowerCase();
                const sub = String(item.subtitle || '').toLowerCase();
                return code.includes(q) || title.includes(q) || sub.includes(q);
            });
        },

        get selectedItem() {
            const list = this.items || [];
            return list.find(i => String(i.id) === String(this.selectedId)) || null;
        },

        init() {
            @if($model)
                this.$watch('{{ $model }}', (val) => {
                    this.selectedId = val;
                });
            @endif
        },

        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.search = '';
                this.highlightIndex = 0;
                this.$nextTick(() => {
                    if (this.$refs.searchInput) {
                        this.$refs.searchInput.focus();
                    }
                });
            }
        },

        select(item) {
            this.selectedId = item ? item.id : '';
            @if($model)
                {{ $model }} = this.selectedId;
            @endif
            this.open = false;
            this.search = '';
            @if($change)
                {{ $change }};
            @endif
        },

        highlightNext() {
            if (this.filteredItems.length === 0) return;
            this.highlightIndex = (this.highlightIndex + 1) % this.filteredItems.length;
            this.scrollToHighlighted();
        },

        highlightPrev() {
            if (this.filteredItems.length === 0) return;
            this.highlightIndex = (this.highlightIndex - 1 + this.filteredItems.length) % this.filteredItems.length;
            this.scrollToHighlighted();
        },

        selectHighlighted() {
            if (this.filteredItems.length > 0 && this.filteredItems[this.highlightIndex]) {
                this.select(this.filteredItems[this.highlightIndex]);
            }
        },

        scrollToHighlighted() {
            this.$nextTick(() => {
                const el = this.$refs.listContainer?.children[this.highlightIndex];
                if (el) {
                    el.scrollIntoView({ block: 'nearest' });
                }
            });
        }
     }"
     @click.outside="open = false"
     @keydown.escape="open = false">

    <!-- Hidden Input for Form Submission -->
    <input type="hidden" name="{{ $name }}" :value="selectedId">

    <!-- Trigger Button -->
    <button type="button"
            @click="toggle()"
            class="w-full px-3.5 py-2 min-h-[38px] text-xs rounded-xl border border-slate-200 bg-slate-50 hover:bg-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all flex items-center justify-between text-left">
        <div class="flex items-center gap-2 min-w-0 pr-2">
            <template x-if="selectedItem">
                <div class="flex items-center gap-2 truncate">
                    <template x-if="selectedItem.code">
                        <span class="font-mono font-bold text-[11px] px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-200 flex-shrink-0"
                              x-text="selectedItem.code"></span>
                    </template>
                    <span class="font-bold text-slate-800 text-xs truncate" x-text="selectedItem.title || selectedItem.nama"></span>
                    <template x-if="selectedItem.subtitle">
                        <span class="text-[11px] text-slate-400 truncate hidden sm:inline" x-text="'(' + selectedItem.subtitle + ')'"></span>
                    </template>
                </div>
            </template>
            <template x-if="!selectedItem">
                <span class="text-slate-400">{{ $placeholder }}</span>
            </template>
        </div>

        <div class="flex items-center gap-1 flex-shrink-0 text-slate-400">
            <template x-if="selectedItem && !{{ $required ? 'true' : 'false' }}">
                <span @click.stop="select(null)" class="hover:text-rose-500 p-0.5" title="Hapus Pilihan">
                    <i class="ti ti-x text-xs"></i>
                </span>
            </template>
            <i class="ti ti-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180 text-emerald-600' : ''"></i>
        </div>
    </button>

    <!-- Dropdown Floating Menu -->
    <div x-show="open" x-cloak
         class="absolute z-50 left-0 right-0 mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden">

        <!-- Search Input Box inside Dropdown -->
        <div class="p-2 border-b border-slate-100 bg-slate-50/70">
            <div class="relative">
                <i class="ti ti-search absolute left-2.5 top-2 text-slate-400 text-xs"></i>
                <input type="text"
                       x-ref="searchInput"
                       x-model="search"
                       @keydown.arrow-down.prevent="highlightNext()"
                       @keydown.arrow-up.prevent="highlightPrev()"
                       @keydown.enter.prevent="selectHighlighted()"
                       placeholder="Ketik untuk mencari (kode / nama)..."
                       class="w-full pl-7 pr-7 py-1.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                <button type="button" x-show="search" @click="search = ''; $refs.searchInput.focus()"
                        class="absolute right-2 top-2 text-slate-400 hover:text-slate-600">
                    <i class="ti ti-x text-xs"></i>
                </button>
            </div>
        </div>

        <!-- Options List -->
        <div x-ref="listContainer" class="max-h-60 overflow-y-auto divide-y divide-slate-50 p-1">
            @if(!$required)
                <div @click="select(null)"
                     class="p-2 rounded-xl cursor-pointer transition-colors flex items-center gap-2 text-slate-400 hover:text-slate-600 hover:bg-slate-50 border-b border-slate-100 mb-0.5">
                    <i class="ti ti-ban text-xs"></i>
                    <span class="text-[11px] italic font-medium">-- Tanpa Pilihan / Kosongkan --</span>
                </div>
            @endif
            <template x-for="(item, idx) in filteredItems" :key="item.id">
                <div @click="select(item)"
                     @mouseenter="highlightIndex = idx"
                     class="p-2.5 rounded-xl cursor-pointer transition-colors flex items-center justify-between gap-2"
                     :class="highlightIndex === idx ? 'bg-emerald-50/80 text-emerald-950' : (String(item.id) === String(selectedId) ? 'bg-slate-50' : 'hover:bg-slate-50')">

                    <div class="flex items-center gap-2 min-w-0">
                        <template x-if="item.code">
                            <span class="font-mono font-bold text-[10px] px-1.5 py-0.5 rounded border flex-shrink-0"
                                  :class="highlightIndex === idx ? 'bg-emerald-200 text-emerald-900 border-emerald-300' : 'bg-slate-100 text-slate-700 border-slate-200'"
                                  x-text="item.code"></span>
                        </template>
                        <div class="min-w-0">
                            <p class="font-bold text-xs truncate"
                               :class="highlightIndex === idx ? 'text-emerald-900' : 'text-slate-800'"
                               x-text="item.title || item.nama"></p>
                            <template x-if="item.subtitle">
                                <p class="text-[10px] text-slate-400 truncate" x-text="item.subtitle"></p>
                            </template>
                        </div>
                    </div>

                    <template x-if="String(item.id) === String(selectedId)">
                        <span class="text-emerald-600 font-bold text-xs flex-shrink-0">
                            <i class="ti ti-check"></i>
                        </span>
                    </template>
                </div>
            </template>

            <template x-if="filteredItems.length === 0">
                <div class="py-6 text-center text-slate-400 text-xs">
                    <i class="ti ti-search-off text-xl block mb-1"></i>
                    Tidak ada hasil pencarian.
                </div>
            </template>
        </div>
    </div>
</div>
