<!-- Sidebar Navigation -->
<aside class="fixed inset-y-0 left-0 z-40 bg-white border-r border-slate-200 text-slate-700 flex flex-col justify-between h-full transition-[width,transform] duration-300 ease-in-out md:static md:translate-x-0 md:flex-shrink-0 select-none"
       :class="{
           'w-64': !sidebarCollapsed,
           'w-64 md:w-20': sidebarCollapsed,
           'translate-x-0': sidebarOpen,
           '-translate-x-full md:translate-x-0': !sidebarOpen
       }">
    <!-- Brand / Logo (Fixed Top) -->
    <div class="h-20 flex items-center justify-between border-b border-slate-100 flex-shrink-0 relative overflow-hidden transition-all duration-300"
         :class="sidebarCollapsed ? 'px-4 md:px-2 md:justify-center' : 'px-6'">
        <a href="{{ route('dashboard') }}" class="flex items-center overflow-hidden transition-all duration-200" :class="sidebarCollapsed ? 'md:justify-center md:w-full' : ''">
            <!-- Full logo when expanded -->
            <img src="{{ asset('logo-amana.png') }}" 
                 alt="AMANA" 
                 class="h-20 w-auto object-contain transition-opacity duration-200"
                 :class="sidebarCollapsed ? 'hidden' : 'block'">
            <!-- Favicon icon when collapsed -->
            <img src="{{ asset('favicon.png') }}" 
                 alt="AMANA" 
                 class="h-8 w-8 object-contain transition-transform duration-200 hover:scale-105"
                 :class="sidebarCollapsed ? 'hidden md:block' : 'hidden'"
                 title="AMANA - Aset Manajemen">
        </a>
        
        <!-- Toggle button on sidebar for desktop when expanded -->
        <button @click="toggleSidebar()" 
                x-show="!sidebarCollapsed"
                class="hidden md:flex p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors" 
                title="Perkecil menu">
            <i class="ti ti-layout-sidebar-left-collapse text-lg"></i>
        </button>

        <!-- Close button for mobile drawer -->
        <button @click="sidebarOpen = false" class="md:hidden text-slate-400 hover:text-slate-700 p-1.5 rounded-xl hover:bg-slate-100" aria-label="Tutup menu">
            <i class="ti ti-x text-xl"></i>
        </button>
    </div>

    <!-- Navigation Links (Scrollable independently) -->
    <div class="flex-1 overflow-y-auto overflow-x-hidden space-y-1.5 transition-all duration-300"
         :class="sidebarCollapsed ? 'px-3 md:px-2 py-6' : 'px-4 py-6'">
        <nav id="sidebar-nav" class="space-y-1.5">
            <!-- Dashboard -->
            <div class="relative group">
                <a href="{{ route('dashboard') }}"
                   class="flex items-center text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-cyan-600 to-emerald-600 text-white shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                   :class="sidebarCollapsed ? 'px-4 py-3 md:px-0 md:py-3 md:justify-center' : 'px-4 py-3'">
                    <i class="ti ti-layout-dashboard text-lg" :class="sidebarCollapsed ? 'mr-3 md:mr-0' : 'mr-3'"></i>
                    <span x-show="!sidebarCollapsed" class="truncate">Dashboard</span>
                </a>
                <!-- Floating Tooltip (Collapsed Desktop) -->
                <div x-show="sidebarCollapsed" 
                     x-cloak
                     class="hidden md:flex pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-slate-900/90 text-white text-xs font-semibold rounded-xl whitespace-nowrap shadow-xl z-50 opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-150 items-center">
                    Dashboard
                </div>
            </div>

            <!-- Kalender Aset -->
            <div class="relative group">
                <a href="{{ route('kalender.index') }}"
                   class="flex items-center text-sm font-semibold rounded-xl transition-all duration-200 {{ request()->routeIs('kalender.*') ? 'bg-gradient-to-r from-cyan-600 to-emerald-600 text-white shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                   :class="sidebarCollapsed ? 'px-4 py-3 md:px-0 md:py-3 md:justify-center' : 'px-4 py-3'">
                    <i class="ti ti-calendar-event text-lg {{ request()->routeIs('kalender.*') ? 'text-white' : 'text-slate-500' }}" :class="sidebarCollapsed ? 'mr-3 md:mr-0' : 'mr-3'"></i>
                    <span x-show="!sidebarCollapsed" class="truncate">Kalender Aset</span>
                </a>
                <!-- Floating Tooltip (Collapsed Desktop) -->
                <div x-show="sidebarCollapsed" 
                     x-cloak
                     class="hidden md:flex pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-slate-900/90 text-white text-xs font-semibold rounded-xl whitespace-nowrap shadow-xl z-50 opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-150 items-center">
                    Kalender Aset
                </div>
            </div>

            <!-- Daftar Aset Dropdown -->
            <div x-data="{ 
                    asetOpen: @js(request()->routeIs('aset.*')),
                    flyoutOpen: false
                 }" 
                 class="relative group"
                 @mouseenter="if (sidebarCollapsed) { flyoutOpen = true; window.animateFlyout?.($refs.flyout); }"
                 @mouseleave="if (sidebarCollapsed) flyoutOpen = false">
                
                <!-- Trigger Button -->
                <button @click="if (sidebarCollapsed) { toggleSidebar(); asetOpen = true; } else { asetOpen = !asetOpen; }"
                        class="w-full flex items-center transition-all duration-200 rounded-xl {{ request()->routeIs('aset.*') ? 'bg-slate-100 text-slate-900 font-semibold border border-slate-200/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                        :class="sidebarCollapsed ? 'px-4 py-3 md:px-0 md:py-3 md:justify-center' : 'justify-between px-4 py-3 text-sm font-semibold'">
                    <span class="flex items-center" :class="sidebarCollapsed ? 'md:justify-center' : ''">
                        <i class="ti ti-box text-lg {{ request()->routeIs('aset.*') ? 'text-emerald-600' : 'text-slate-500' }}"
                           :class="sidebarCollapsed ? 'mr-3 md:mr-0' : 'mr-3'"></i>
                        <span x-show="!sidebarCollapsed" class="truncate">Daftar Aset</span>
                    </span>
                    <i x-show="!sidebarCollapsed" class="ti ti-chevron-down text-sm transition-transform duration-200 text-slate-400" :class="{ 'rotate-180': asetOpen }"></i>
                </button>

                <!-- Accordion Submenu (Expanded Mode) -->
                <div x-show="!sidebarCollapsed && asetOpen" 
                     x-transition 
                     x-cloak 
                     class="mt-1 ml-4 pl-3 border-l-2 border-slate-200/80 space-y-1 py-1">
                    <a href="{{ route('aset.tetap') }}"
                       class="block px-3.5 py-2 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('aset.tetap') ? 'bg-gradient-to-r from-cyan-600 to-emerald-600 text-white shadow-sm font-bold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        Aset Tetap
                    </a>
                    <a href="{{ route('aset.kelolaan') }}"
                       class="block px-3.5 py-2 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('aset.kelolaan') ? 'bg-gradient-to-r from-cyan-600 to-emerald-600 text-white shadow-sm font-bold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        Aset Kelolaan
                    </a>
                    <a href="{{ route('aset.nonAktif') }}"
                       class="block px-3.5 py-2 text-xs font-semibold rounded-lg transition-all {{ request()->routeIs('aset.nonAktif') ? 'bg-gradient-to-r from-cyan-600 to-emerald-600 text-white shadow-sm font-bold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        Aset Non Aktif
                    </a>
                </div>

                <!-- Floating Flyout Submenu (Collapsed Mode Desktop) -->
                <div x-show="sidebarCollapsed && flyoutOpen" 
                     x-ref="flyout"
                     x-cloak
                     class="hidden md:block absolute left-full top-0 ml-3 w-52 bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-slate-200 p-2 z-50 space-y-1">
                    <div class="px-3 py-2 text-xs font-bold text-slate-800 border-b border-slate-100 flex items-center gap-2 mb-1">
                        <i class="ti ti-box text-emerald-600"></i>
                        Daftar Aset
                    </div>
                    <a href="{{ route('aset.tetap') }}"
                       class="flex items-center px-3 py-2 text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('aset.tetap') ? 'bg-gradient-to-r from-cyan-600 to-emerald-600 text-white shadow-sm font-bold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <i class="ti ti-circle-filled text-[6px] mr-2 {{ request()->routeIs('aset.tetap') ? 'text-white' : 'text-slate-300' }}"></i>
                        Aset Tetap
                    </a>
                    <a href="{{ route('aset.kelolaan') }}"
                       class="flex items-center px-3 py-2 text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('aset.kelolaan') ? 'bg-gradient-to-r from-cyan-600 to-emerald-600 text-white shadow-sm font-bold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <i class="ti ti-circle-filled text-[6px] mr-2 {{ request()->routeIs('aset.kelolaan') ? 'text-white' : 'text-slate-300' }}"></i>
                        Aset Kelolaan
                    </a>
                    <a href="{{ route('aset.nonAktif') }}"
                       class="flex items-center px-3 py-2 text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('aset.nonAktif') ? 'bg-gradient-to-r from-cyan-600 to-emerald-600 text-white shadow-sm font-bold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <i class="ti ti-circle-filled text-[6px] mr-2 {{ request()->routeIs('aset.nonAktif') ? 'text-white' : 'text-slate-300' }}"></i>
                        Aset Non Aktif
                    </a>
                </div>
            </div>

            <!-- Impor & Ekspor Section -->
            <div class="pt-4 pb-1.5" :class="sidebarCollapsed ? 'md:pt-3 md:pb-1' : ''">
                <p x-show="!sidebarCollapsed" class="px-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Migrasi & Laporan</p>
                <div x-show="sidebarCollapsed" class="hidden md:block mx-2 border-t border-slate-200" title="Migrasi & Laporan"></div>
            </div>

            <!-- Impor Data Aset -->
            <div class="relative group">
                <a href="{{ route('aset.import.index') }}"
                   class="flex items-center text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('aset.import.*') ? 'bg-gradient-to-r from-cyan-600 to-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                   :class="sidebarCollapsed ? 'px-4 py-2.5 md:px-0 md:py-2.5 md:justify-center' : 'px-4 py-2.5'">
                    <i class="ti ti-file-import text-base {{ request()->routeIs('aset.import.*') ? 'text-white' : 'text-slate-400' }}" :class="sidebarCollapsed ? 'mr-3 md:mr-0' : 'mr-3'"></i>
                    <span x-show="!sidebarCollapsed" class="truncate">Impor Data Aset</span>
                </a>
                <!-- Tooltip -->
                <div x-show="sidebarCollapsed" 
                     x-cloak
                     class="hidden md:flex pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-slate-900/90 text-white text-xs font-semibold rounded-xl whitespace-nowrap shadow-xl z-50 opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-150 items-center">
                    Impor Data Aset
                </div>
            </div>

            <!-- Ekspor Data Aset -->
            <div class="relative group">
                <a href="{{ route('aset.export.index') }}"
                   class="flex items-center text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('aset.export.*') ? 'bg-gradient-to-r from-cyan-600 to-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                   :class="sidebarCollapsed ? 'px-4 py-2.5 md:px-0 md:py-2.5 md:justify-center' : 'px-4 py-2.5'">
                    <i class="ti ti-file-export text-base {{ request()->routeIs('aset.export.*') ? 'text-white' : 'text-slate-400' }}" :class="sidebarCollapsed ? 'mr-3 md:mr-0' : 'mr-3'"></i>
                    <span x-show="!sidebarCollapsed" class="truncate">Ekspor Data Aset</span>
                </a>
                <!-- Tooltip -->
                <div x-show="sidebarCollapsed" 
                     x-cloak
                     class="hidden md:flex pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-slate-900/90 text-white text-xs font-semibold rounded-xl whitespace-nowrap shadow-xl z-50 opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-150 items-center">
                    Ekspor Data Aset
                </div>
            </div>

            <!-- Cetak Label QR -->
            <div class="relative group">
                <a href="{{ route('aset.qr.print') }}"
                   class="flex items-center text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('aset.qr.*') ? 'bg-gradient-to-r from-cyan-600 to-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                   :class="sidebarCollapsed ? 'px-4 py-2.5 md:px-0 md:py-2.5 md:justify-center' : 'px-4 py-2.5'">
                    <i class="ti ti-printer text-base {{ request()->routeIs('aset.qr.*') ? 'text-white' : 'text-slate-400' }}" :class="sidebarCollapsed ? 'mr-3 md:mr-0' : 'mr-3'"></i>
                    <span x-show="!sidebarCollapsed" class="truncate">Cetak Label QR</span>
                </a>
                <!-- Tooltip -->
                <div x-show="sidebarCollapsed" 
                     x-cloak
                     class="hidden md:flex pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-slate-900/90 text-white text-xs font-semibold rounded-xl whitespace-nowrap shadow-xl z-50 opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-150 items-center">
                    Cetak Label QR
                </div>
            </div>

            @if(auth()->check() && auth()->user()->role === 'super_admin')
                <!-- DATA MASTER SECTION -->
                <div class="pt-5 pb-1.5" :class="sidebarCollapsed ? 'md:pt-4 md:pb-1' : ''">
                    <p x-show="!sidebarCollapsed" class="px-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Data Master</p>
                    <div x-show="sidebarCollapsed" class="hidden md:block mx-2 border-t border-slate-200" title="Data Master"></div>
                </div>

                <!-- Penanggung Jawab -->
                <div class="relative group">
                    <a href="{{ route('data.penanggung-jawab.index') }}"
                       class="flex items-center text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('data.penanggung-jawab.*') ? 'bg-gradient-to-r from-cyan-600 to-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                       :class="sidebarCollapsed ? 'px-4 py-2.5 md:px-0 md:py-2.5 md:justify-center' : 'px-4 py-2.5'">
                        <i class="ti ti-user text-base {{ request()->routeIs('data.penanggung-jawab.*') ? 'text-white' : 'text-slate-400' }}" :class="sidebarCollapsed ? 'mr-3 md:mr-0' : 'mr-3'"></i>
                        <span x-show="!sidebarCollapsed" class="truncate">Penanggung Jawab</span>
                    </a>
                    <!-- Tooltip -->
                    <div x-show="sidebarCollapsed" 
                         x-cloak
                         class="hidden md:flex pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-slate-900/90 text-white text-xs font-semibold rounded-xl whitespace-nowrap shadow-xl z-50 opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-150 items-center">
                        Penanggung Jawab
                    </div>
                </div>

                <!-- Kategori -->
                <div class="relative group">
                    <a href="{{ route('data.kategori.index') }}"
                       class="flex items-center text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('data.kategori.*') ? 'bg-gradient-to-r from-cyan-600 to-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                       :class="sidebarCollapsed ? 'px-4 py-2.5 md:px-0 md:py-2.5 md:justify-center' : 'px-4 py-2.5'">
                        <i class="ti ti-category text-base {{ request()->routeIs('data.kategori.*') ? 'text-white' : 'text-slate-400' }}" :class="sidebarCollapsed ? 'mr-3 md:mr-0' : 'mr-3'"></i>
                        <span x-show="!sidebarCollapsed" class="truncate">Kategori</span>
                    </a>
                    <!-- Tooltip -->
                    <div x-show="sidebarCollapsed" 
                         x-cloak
                         class="hidden md:flex pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-slate-900/90 text-white text-xs font-semibold rounded-xl whitespace-nowrap shadow-xl z-50 opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-150 items-center">
                        Kategori
                    </div>
                </div>

                <!-- Lokasi -->
                <div class="relative group">
                    <a href="{{ route('data.lokasi.index') }}"
                       class="flex items-center text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('data.lokasi.*') ? 'bg-gradient-to-r from-cyan-600 to-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                       :class="sidebarCollapsed ? 'px-4 py-2.5 md:px-0 md:py-2.5 md:justify-center' : 'px-4 py-2.5'">
                        <i class="ti ti-map-pin text-base {{ request()->routeIs('data.lokasi.*') ? 'text-white' : 'text-slate-400' }}" :class="sidebarCollapsed ? 'mr-3 md:mr-0' : 'mr-3'"></i>
                        <span x-show="!sidebarCollapsed" class="truncate">Lokasi</span>
                    </a>
                    <!-- Tooltip -->
                    <div x-show="sidebarCollapsed" 
                         x-cloak
                         class="hidden md:flex pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-slate-900/90 text-white text-xs font-semibold rounded-xl whitespace-nowrap shadow-xl z-50 opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-150 items-center">
                        Lokasi
                    </div>
                </div>

                <!-- Merk -->
                <div class="relative group">
                    <a href="{{ route('data.merk.index') }}"
                       class="flex items-center text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('data.merk.*') ? 'bg-gradient-to-r from-cyan-600 to-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                       :class="sidebarCollapsed ? 'px-4 py-2.5 md:px-0 md:py-2.5 md:justify-center' : 'px-4 py-2.5'">
                        <i class="ti ti-tag text-base {{ request()->routeIs('data.merk.*') ? 'text-white' : 'text-slate-400' }}" :class="sidebarCollapsed ? 'mr-3 md:mr-0' : 'mr-3'"></i>
                        <span x-show="!sidebarCollapsed" class="truncate">Merk</span>
                    </a>
                    <!-- Tooltip -->
                    <div x-show="sidebarCollapsed" 
                         x-cloak
                         class="hidden md:flex pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-slate-900/90 text-white text-xs font-semibold rounded-xl whitespace-nowrap shadow-xl z-50 opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-150 items-center">
                        Merk
                    </div>
                </div>

                <!-- LOG AKTIVITAS -->
                <div class="pt-4 pb-1.5" :class="sidebarCollapsed ? 'md:pt-3 md:pb-1' : ''">
                    <p x-show="!sidebarCollapsed" class="px-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Aktivitas</p>
                    <div x-show="sidebarCollapsed" class="hidden md:block mx-2 border-t border-slate-200" title="Aktivitas"></div>
                </div>

                <!-- Log Aktivitas -->
                <div class="relative group">
                    <a href="{{ route('audit-log.index') }}"
                       class="flex items-center text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('audit-log.*') ? 'bg-gradient-to-r from-cyan-600 to-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                       :class="sidebarCollapsed ? 'px-4 py-2.5 md:px-0 md:py-2.5 md:justify-center' : 'px-4 py-2.5'">
                        <i class="ti ti-history text-base {{ request()->routeIs('audit-log.*') ? 'text-white' : 'text-slate-400' }}" :class="sidebarCollapsed ? 'mr-3 md:mr-0' : 'mr-3'"></i>
                        <span x-show="!sidebarCollapsed" class="truncate">Log Aktivitas</span>
                    </a>
                    <!-- Tooltip -->
                    <div x-show="sidebarCollapsed" 
                         x-cloak
                         class="hidden md:flex pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-slate-900/90 text-white text-xs font-semibold rounded-xl whitespace-nowrap shadow-xl z-50 opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-150 items-center">
                        Log Aktivitas
                    </div>
                </div>

                <!-- PENGATURAN / SISTEM -->
                <div class="pt-4 pb-1.5" :class="sidebarCollapsed ? 'md:pt-3 md:pb-1' : ''">
                    <p x-show="!sidebarCollapsed" class="px-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pengaturan</p>
                    <div x-show="sidebarCollapsed" class="hidden md:block mx-2 border-t border-slate-200" title="Pengaturan"></div>
                </div>

                <!-- Pengguna -->
                <div class="relative group">
                    <a href="{{ route('sistem.users.index') }}"
                       class="flex items-center text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('sistem.users.*') ? 'bg-gradient-to-r from-cyan-600 to-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                       :class="sidebarCollapsed ? 'px-4 py-2.5 md:px-0 md:py-2.5 md:justify-center' : 'px-4 py-2.5'">
                        <i class="ti ti-users text-base {{ request()->routeIs('sistem.users.*') ? 'text-white' : 'text-slate-400' }}" :class="sidebarCollapsed ? 'mr-3 md:mr-0' : 'mr-3'"></i>
                        <span x-show="!sidebarCollapsed" class="truncate">Pengguna</span>
                    </a>
                    <!-- Tooltip -->
                    <div x-show="sidebarCollapsed" 
                         x-cloak
                         class="hidden md:flex pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-slate-900/90 text-white text-xs font-semibold rounded-xl whitespace-nowrap shadow-xl z-50 opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-150 items-center">
                        Pengguna
                    </div>
                </div>

                <!-- Konfigurasi QR -->
                <div class="relative group">
                    <a href="{{ route('pengaturan.qr-config.index') }}"
                       class="flex items-center text-xs font-semibold rounded-xl transition-all {{ request()->routeIs('pengaturan.qr-config.*') ? 'bg-gradient-to-r from-cyan-600 to-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                       :class="sidebarCollapsed ? 'px-4 py-2.5 md:px-0 md:py-2.5 md:justify-center' : 'px-4 py-2.5'">
                        <i class="ti ti-settings-2 text-base {{ request()->routeIs('pengaturan.qr-config.*') ? 'text-white' : 'text-slate-400' }}" :class="sidebarCollapsed ? 'mr-3 md:mr-0' : 'mr-3'"></i>
                        <span x-show="!sidebarCollapsed" class="truncate">Konfigurasi QR</span>
                    </a>
                    <!-- Tooltip -->
                    <div x-show="sidebarCollapsed" 
                         x-cloak
                         class="hidden md:flex pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-slate-900/90 text-white text-xs font-semibold rounded-xl whitespace-nowrap shadow-xl z-50 opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-150 items-center">
                        Konfigurasi QR
                    </div>
                </div>
            @endif
        </nav>
    </div>

    <!-- User Info & Logout (Fixed Bottom) -->
    <div class="border-t border-slate-200 bg-slate-50/80 flex-shrink-0 transition-all duration-300"
         :class="sidebarCollapsed ? 'p-3 md:p-2' : 'p-4'">
        <!-- Expanded User Info -->
        <div x-show="!sidebarCollapsed" class="flex items-center justify-between">
            <div class="min-w-0 pr-2">
                <p class="text-xs font-semibold text-slate-900 truncate">{{ auth()->user()->name ?? 'Pengguna' }}</p>
                <span class="inline-block mt-0.5 px-2 py-0.5 text-[10px] font-bold rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200">
                    {{ strtoupper(auth()->user()->role ?? 'guest') }}
                </span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="p-2 rounded-xl text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition-colors" title="Keluar" aria-label="Keluar">
                    <i class="ti ti-logout text-lg"></i>
                </button>
            </form>
        </div>

        <!-- Collapsed User Info (Desktop) -->
        <div x-show="sidebarCollapsed" class="hidden md:flex flex-col items-center gap-2 py-1">
            <div class="relative group">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-xs flex items-center justify-center shadow-xs cursor-default">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <!-- Tooltip User Profile -->
                <div class="pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 bg-slate-900/90 text-white text-xs font-medium rounded-xl whitespace-nowrap shadow-xl z-50 opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-150">
                    <div class="font-bold">{{ auth()->user()->name ?? 'Pengguna' }}</div>
                    <div class="text-[10px] text-emerald-400">{{ strtoupper(auth()->user()->role ?? 'guest') }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="w-full flex justify-center">
                @csrf
                <div class="relative group">
                    <button type="submit" class="p-2 rounded-xl text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition-colors" title="Keluar" aria-label="Keluar">
                        <i class="ti ti-logout text-lg"></i>
                    </button>
                    <div class="pointer-events-none absolute left-full top-1/2 -translate-y-1/2 ml-3 px-2.5 py-1 bg-rose-900 text-white text-[11px] font-semibold rounded-lg whitespace-nowrap shadow-xl z-50 opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-150">
                        Keluar
                    </div>
                </div>
            </form>
        </div>
    </div>
</aside>

<!-- Backdrop Overlay for Mobile Drawer -->
<div x-show="sidebarOpen"
     @click="sidebarOpen = false"
     x-transition:enter="transition-opacity ease-linear duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     x-cloak
     class="fixed inset-0 bg-slate-900/40 z-30 md:hidden backdrop-blur-xs"></div>
