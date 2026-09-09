<!-- Header Bar -->
<header class="h-20 w-full bg-white/80 backdrop-blur-md border-b border-slate-200 px-6 sm:px-10 lg:px-12 flex items-center justify-between sticky top-0 z-10">
    <div class="flex items-center gap-3.5">
        <!-- Mobile Drawer Toggle Button -->
        <button @click="sidebarOpen = !sidebarOpen" class="md:hidden p-2 text-slate-500 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition-colors" aria-label="Buka menu">
            <i class="ti ti-menu-2 text-xl"></i>
        </button>

        <!-- Desktop Sidebar Resize Button (Toggle Collapse/Expand) -->
        <button @click="toggleSidebar()" 
                class="hidden md:flex items-center justify-center p-2 text-slate-500 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition-all duration-200 border border-transparent hover:border-slate-200" 
                :title="sidebarCollapsed ? 'Perbesar Sidebar' : 'Perkecil Sidebar'" 
                aria-label="Toggle sidebar">
            <i class="ti text-xl transition-transform duration-200" :class="sidebarCollapsed ? 'ti-layout-sidebar-left-expand text-emerald-600' : 'ti-layout-sidebar-left-collapse'"></i>
        </button>
        <div>
            <h1 id="header-page-title" class="text-lg font-bold text-slate-900 tracking-tight">@yield('header-title', 'Dashboard')</h1>
        </div>
    </div>
    <div class="flex items-center space-x-3 text-xs">
        <!-- Notification Bell Dropdown -->
        <div class="relative" 
             x-data="{ 
                 notifOpen: false, 
                 filterTab: 'all',
                 loading: false,
                 loaded: false,
                 reminders: null,
                 totalCount: {{ $agendaReminder['total_count'] ?? 0 }},
                 hasCritical: {{ ($agendaReminder['has_critical'] ?? false) ? 'true' : 'false' }},
                 async toggleDropdown() {
                     this.notifOpen = !this.notifOpen;
                     if (this.notifOpen && !this.loaded) {
                         this.loading = true;
                         try {
                             const res = await fetch('{{ route('agenda.reminders') }}');
                             if (res.ok) {
                                 this.reminders = await res.json();
                                 this.totalCount = this.reminders.total_count;
                                 this.hasCritical = this.reminders.has_critical;
                                 this.loaded = true;
                             }
                         } catch (e) {
                             console.error(e);
                         } finally {
                             this.loading = false;
                         }
                     }
                 }
             }" 
             @click.outside="notifOpen = false" 
             @keydown.escape.window="notifOpen = false">
            <button @click="toggleDropdown()" 
                    type="button"
                    class="relative p-2.5 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-all duration-200 border border-slate-200/80 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                    :class="{ 'bg-slate-100 text-slate-900 shadow-xs': notifOpen }"
                    title="Pengingat Jadwal Agenda"
                    aria-label="Pengingat Jadwal Agenda">
                <i class="ti ti-bell text-lg"></i>
                
                <template x-if="totalCount > 0">
                    <!-- Badge Counter -->
                    <span class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-5 h-5 px-1.5 text-[10px] font-extrabold text-white rounded-full shadow-xs"
                          :class="hasCritical ? 'bg-rose-600' : 'bg-blue-600'"
                          x-text="totalCount > 99 ? '99+' : totalCount">
                    </span>
                </template>
                <template x-if="hasCritical">
                    <span class="absolute -top-0.5 -right-0.5 flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                    </span>
                </template>
            </button>

            <!-- Dropdown Panel -->
            <div x-show="notifOpen" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                 x-cloak
                 class="absolute right-0 mt-3 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-200/90 z-50 overflow-hidden">
                 
                 <!-- Header Dropdown -->
                 <div class="px-4 py-3 bg-gradient-to-r from-slate-900 to-slate-800 text-white flex items-center justify-between">
                     <div class="flex items-center gap-2">
                         <div class="w-7 h-7 rounded-lg bg-white/10 flex items-center justify-center text-emerald-400">
                             <i class="ti ti-calendar-event text-base"></i>
                         </div>
                         <div>
                             <h4 class="font-bold text-xs tracking-wide">Pengingat Agenda</h4>
                             <p class="text-[10px] text-slate-300">Jadwal perawatan & servis aset</p>
                         </div>
                     </div>
                     <template x-if="totalCount > 0">
                         <span class="px-2 py-0.5 text-[10px] font-bold rounded-md"
                               :class="hasCritical ? 'bg-rose-500/20 text-rose-300 border border-rose-500/40' : 'bg-blue-500/20 text-blue-300 border border-blue-500/40'"
                               x-text="totalCount + ' Perlu Tindakan'">
                         </span>
                     </template>
                     <template x-if="totalCount === 0">
                         <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                             Aman
                         </span>
                     </template>
                 </div>

                 <!-- Loading State -->
                 <div x-show="loading && !loaded" class="p-6 text-center text-slate-400 text-xs">
                     <i class="ti ti-loader-2 animate-spin text-2xl text-emerald-600 mb-2 inline-block"></i>
                     <p>Memuat jadwal agenda...</p>
                 </div>

                 <!-- Content when Loaded -->
                 <div x-show="!loading || loaded">
                     <template x-if="totalCount > 0">
                         <div>
                             <!-- Filter Tabs -->
                             <div class="flex items-center gap-1 p-2 bg-slate-50 border-b border-slate-200/80 text-[11px] overflow-x-auto">
                                 <button type="button" 
                                         @click="filterTab = 'all'"
                                         class="px-2.5 py-1 rounded-lg font-semibold transition-all shrink-0"
                                         :class="filterTab === 'all' ? 'bg-white text-slate-900 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-800'">
                                     Semua (<span x-text="reminders?.total_count || totalCount"></span>)
                                 </button>
                                 <template x-if="(reminders?.overdue_count || 0) > 0">
                                     <button type="button" 
                                             @click="filterTab = 'overdue'"
                                             class="px-2.5 py-1 rounded-lg font-semibold transition-all shrink-0 flex items-center gap-1"
                                             :class="filterTab === 'overdue' ? 'bg-rose-600 text-white shadow-xs' : 'text-rose-600 hover:bg-rose-50'">
                                         <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                         Terlambat (<span x-text="reminders?.overdue_count"></span>)
                                     </button>
                                 </template>
                                 <template x-if="(reminders?.today_count || 0) > 0">
                                     <button type="button" 
                                             @click="filterTab = 'today'"
                                             class="px-2.5 py-1 rounded-lg font-semibold transition-all shrink-0 flex items-center gap-1"
                                             :class="filterTab === 'today' ? 'bg-amber-500 text-white shadow-xs' : 'text-amber-600 hover:bg-amber-50'">
                                         <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                         Hari Ini (<span x-text="reminders?.today_count"></span>)
                                     </button>
                                 </template>
                                 <template x-if="(reminders?.upcoming_count || 0) > 0">
                                     <button type="button" 
                                             @click="filterTab = 'upcoming'"
                                             class="px-2.5 py-1 rounded-lg font-semibold transition-all shrink-0 flex items-center gap-1"
                                             :class="filterTab === 'upcoming' ? 'bg-blue-600 text-white shadow-xs' : 'text-blue-600 hover:bg-blue-50'">
                                         <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                         Mendatang (<span x-text="reminders?.upcoming_count"></span>)
                                     </button>
                                 </template>
                             </div>

                             <!-- Items List -->
                             <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                                 <template x-for="item in (reminders?.items || [])" :key="item.id">
                                     <a :href="'/aset/' + item.aset_id + '?tab=agenda'"
                                        x-show="filterTab === 'all' || filterTab === item.category"
                                        class="p-3 block hover:bg-slate-50/80 transition-colors group">
                                         <div class="flex items-start gap-2.5">
                                             <!-- Category Icon Pill -->
                                             <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 mt-0.5 border"
                                                  :class="item.category === 'overdue' ? 'bg-rose-50 text-rose-600 border-rose-200' : (item.category === 'today' ? 'bg-amber-50 text-amber-600 border-amber-200' : 'bg-blue-50 text-blue-600 border-blue-200')">
                                                 <i class="ti text-sm"
                                                    :class="item.category === 'overdue' ? 'ti-alert-triangle' : (item.category === 'today' ? 'ti-bell-ringing' : 'ti-calendar')"></i>
                                             </div>

                                             <div class="min-w-0 flex-1">
                                                 <div class="flex items-center justify-between gap-1 mb-0.5">
                                                     <h5 class="text-xs font-bold text-slate-800 group-hover:text-emerald-700 transition-colors truncate"
                                                         x-text="item.nama_agenda">
                                                     </h5>
                                                     <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded-md border shrink-0"
                                                           :class="item.category === 'overdue' ? 'bg-rose-50 text-rose-700 border-rose-200' : (item.category === 'today' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-blue-50 text-blue-700 border-blue-200')"
                                                           x-text="item.human_diff">
                                                     </span>
                                                 </div>

                                                 <p class="text-[11px] text-slate-600 truncate flex items-center gap-1">
                                                     <span class="font-medium text-slate-800" x-text="item.nama_aset"></span>
                                                     <span class="text-slate-300">•</span>
                                                     <span class="text-slate-500 font-mono text-[10px]" x-text="item.kode_aset"></span>
                                                 </p>

                                                 <div class="flex items-center gap-2 mt-1 text-[10px] text-slate-400">
                                                     <span class="flex items-center gap-1">
                                                         <i class="ti ti-calendar-event text-slate-400"></i>
                                                         <span x-text="item.due_date_formatted"></span>
                                                     </span>
                                                     <template x-if="item.lokasi_nama && item.lokasi_nama !== '-'">
                                                         <span class="flex items-center gap-1">
                                                             <span class="text-slate-300">•</span>
                                                             <span class="truncate flex items-center gap-0.5">
                                                                 <i class="ti ti-map-pin text-slate-400"></i>
                                                                 <span x-text="item.lokasi_nama"></span>
                                                             </span>
                                                         </span>
                                                     </template>
                                                 </div>
                                             </div>
                                         </div>
                                     </a>
                                 </template>
                             </div>
                         </div>
                     </template>

                     <template x-if="totalCount === 0">
                         <!-- Empty State -->
                         <div class="p-6 text-center">
                             <div class="w-12 h-12 mx-auto mb-2.5 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 shadow-xs">
                                 <i class="ti ti-circle-check-filled text-2xl"></i>
                             </div>
                             <h5 class="text-xs font-bold text-slate-800">Semua Terjadwal Rapi</h5>
                             <p class="text-[11px] text-slate-500 mt-0.5">Tidak ada agenda perawatan yang terlambat atau mendesak dalam 7 hari ke depan.</p>
                         </div>
                     </template>
                 </div>

                 <!-- Dropdown Footer -->
                 <div class="p-2.5 bg-slate-50 border-t border-slate-200/80 flex items-center justify-between text-[11px]">
                     <a href="{{ route('kalender.index') }}" class="inline-flex items-center gap-1.5 font-semibold text-emerald-600 hover:text-emerald-700 hover:underline">
                         <i class="ti ti-calendar"></i>
                         Buka Kalender Aset
                     </a>
                     <span class="text-[10px] text-slate-400">AMANA Reminder</span>
                 </div>
            </div>
        </div>

        <span class="px-3.5 py-1.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 font-medium flex items-center gap-1.5">
            <i class="ti ti-building text-sm text-slate-500"></i>
            Al Azhar Peduli
        </span>
    </div>
</header>
