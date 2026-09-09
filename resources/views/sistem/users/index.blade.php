@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('header-title', 'Pengaturan: Manajemen Pengguna & Hak Akses')

@section('content')
<div class="space-y-6" x-data="userManager">

    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Manajemen Pengguna</h2>
            <p class="text-xs text-slate-500 mt-0.5">Kelola akun pengguna, hak akses peran (Super Admin & Viewer), penautan Amil, dan keamanan sistem</p>
        </div>

        <button type="button"
                @click="openCreateModal()"
                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-extrabold text-xs uppercase tracking-wider shadow-md shadow-emerald-600/20 transition-all inline-flex items-center gap-2">
            <i class="ti ti-user-plus text-base"></i>
            Tambah Pengguna Baru
        </button>
    </div>

    <!-- Stat Tiles Ringkasan Pengguna -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 sm:gap-4">
        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Akun</span>
                <span class="p-2 rounded-xl bg-slate-100 text-slate-600 border border-slate-200"><i class="ti ti-users text-base"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">{{ $summary['total_user'] }}</p>
            <span class="text-[10px] text-slate-400 mt-0.5 block">Akun terdaftar di sistem</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Super Admin</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100"><i class="ti ti-shield-check text-base"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-emerald-600 mt-2">{{ $summary['total_super_admin'] }}</p>
            <span class="text-[10px] text-slate-400 mt-0.5 block">Akses CRUD & konfigurasi penuh</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Viewer</span>
                <span class="p-2 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100"><i class="ti ti-eye text-base"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-indigo-600 mt-2">{{ $summary['total_viewer'] }}</p>
            <span class="text-[10px] text-slate-400 mt-0.5 block">Akses pemantauan (Read-only)</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Akun Aktif</span>
                <span class="p-2 rounded-xl bg-cyan-50 text-cyan-600 border border-cyan-100"><i class="ti ti-user-check text-base"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-cyan-600 mt-2">{{ $summary['total_aktif'] }}</p>
            <span class="text-[10px] text-slate-400 mt-0.5 block">Bisa login ke aplikasi</span>
        </div>

        <div class="p-4 sm:p-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs col-span-2 sm:col-span-1">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Non-Aktif</span>
                <span class="p-2 rounded-xl bg-rose-50 text-rose-500 border border-rose-100"><i class="ti ti-user-off text-base"></i></span>
            </div>
            <p class="text-2xl sm:text-3xl font-extrabold text-slate-600 mt-2">{{ $summary['total_non_aktif'] }}</p>
            <span class="text-[10px] text-slate-400 mt-0.5 block">Akses login ditangguhkan</span>
        </div>
    </div>

    <!-- TABEL DATA PENGGUNA -->
    <div class="p-6 bg-white rounded-3xl border border-slate-200/80 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-bold text-xs">
                    <i class="ti ti-users text-sm"></i>
                </span>
                <h3 class="text-sm font-extrabold text-slate-900">Daftar Pengguna Sistem</h3>
            </div>

            <!-- Filter & Pencarian Form -->
            <form method="GET" action="{{ route('sistem.users.index') }}" class="flex flex-wrap items-center gap-2.5">
                <!-- Filter Role -->
                <select name="role" onchange="this.form.submit()"
                        class="px-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-semibold text-slate-700">
                    <option value="">-- Semua Peran --</option>
                    <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    <option value="viewer" {{ request('role') === 'viewer' ? 'selected' : '' }}>Viewer</option>
                </select>

                <!-- Filter Status -->
                <select name="status" onchange="this.form.submit()"
                        class="px-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-semibold text-slate-700">
                    <option value="">-- Semua Status --</option>
                    <option value="aktif" {{ request('status') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="non_aktif" {{ request('status') === 'non_aktif' ? 'selected' : '' }}>Non-Aktif</option>
                </select>

                <!-- Search Input -->
                <div class="relative min-w-[220px]">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..."
                           class="w-full pl-9 pr-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <i class="ti ti-search absolute left-3 top-2.5 text-slate-400 text-sm"></i>
                </div>

                @if(request()->hasAny(['role', 'status', 'search']))
                    <a href="{{ route('sistem.users.index') }}"
                       class="px-3 py-2 text-xs rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold transition-colors flex items-center gap-1"
                       title="Reset Filter">
                        <i class="ti ti-x text-sm"></i>
                        <span>Reset</span>
                    </a>
                @endif
            </form>
        </div>

        <!-- Tabel Responsif -->
        <div class="overflow-x-auto rounded-2xl border border-slate-100">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50/80 text-slate-700 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200/80">
                    <tr>
                        <th class="py-3 px-4">Pengguna</th>
                        <th class="py-3 px-4">Peran (Role)</th>
                        <th class="py-3 px-4">Tautan Amil / Penanggung Jawab</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4">Terdaftar Sejak</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/70 transition-colors {{ !$user->is_active ? 'bg-slate-50/40 opacity-75' : '' }}">
                            <!-- Info Pengguna (Avatar, Nama, Email) -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-2xl flex items-center justify-center font-extrabold text-xs flex-shrink-0 shadow-xs
                                        {{ $user->role === 'super_admin' ? 'bg-gradient-to-tr from-cyan-600 to-emerald-600 text-white' : 'bg-gradient-to-tr from-slate-600 to-indigo-600 text-white' }}">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="font-extrabold text-slate-900 text-xs truncate">{{ $user->name }}</p>
                                            @if($user->id === auth()->id())
                                                <span class="px-1.5 py-0.5 rounded-md bg-amber-100 text-amber-800 text-[9px] font-extrabold border border-amber-200">
                                                    Akun Anda
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] text-slate-400 truncate mt-0.5">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Peran (Role) -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($user->role === 'super_admin')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-xs">
                                        <i class="ti ti-shield-check text-xs text-emerald-600"></i>
                                        SUPER ADMIN
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-xs">
                                        <i class="ti ti-eye text-xs text-indigo-600"></i>
                                        VIEWER
                                    </span>
                                @endif
                            </td>

                            <!-- Tautan Amil / Penanggung Jawab -->
                            <td class="py-3.5 px-4">
                                @if($user->penanggungJawab)
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-[10px] px-1.5 py-0.5 rounded-md bg-cyan-50 text-cyan-800 border border-cyan-200">
                                            NIA {{ $user->penanggungJawab->kode_pic }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-800 text-[11px] truncate">{{ $user->penanggungJawab->nama }}</p>
                                            <p class="text-[10px] text-slate-400 truncate">{{ $user->penanggungJawab->divisi->nama_divisi ?? $user->penanggungJawab->jabatan ?? '-' }}</p>
                                        </div>
                                    </div>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-semibold text-slate-400 bg-slate-100 border border-slate-200/80">
                                        <i class="ti ti-link-off text-xs"></i>
                                        Belum Ditautkan
                                    </span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($user->is_active)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100/80 text-emerald-800 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100/80 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Non-Aktif
                                    </span>
                                @endif
                            </td>

                            <!-- Terdaftar Sejak -->
                            <td class="py-3.5 px-4 whitespace-nowrap text-slate-500 text-[11px]">
                                {{ $user->created_at ? $user->created_at->translatedFormat('d M Y') : '-' }}
                            </td>

                            <!-- Aksi Menu / Tombol -->
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Edit User -->
                                    <button type="button"
                                            @click="openEditModal(@js([
                                                'id' => $user->id,
                                                'name' => $user->name,
                                                'email' => $user->email,
                                                'role' => $user->role,
                                                'is_active' => (bool)$user->is_active,
                                                'penanggung_jawab_id' => $user->penanggungJawab?->id,
                                                'is_self' => ($user->id === auth()->id())
                                            ]))"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 border border-transparent hover:border-emerald-200 transition-colors"
                                            title="Edit Data Pengguna">
                                        <i class="ti ti-pencil text-base"></i>
                                    </button>

                                    <!-- Reset Password -->
                                    <button type="button"
                                            @click="openResetModal(@js([
                                                'id' => $user->id,
                                                'name' => $user->name,
                                                'email' => $user->email
                                            ]))"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-cyan-700 hover:bg-cyan-50 border border-transparent hover:border-cyan-200 transition-colors"
                                            title="Reset Kata Sandi">
                                        <i class="ti ti-key text-base"></i>
                                    </button>

                                    <!-- Toggle Status Aktif/Non-Aktif -->
                                    @if($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('sistem.users.toggle-status', $user->id) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="p-1.5 rounded-lg {{ $user->is_active ? 'text-slate-500 hover:text-amber-700 hover:bg-amber-50' : 'text-slate-500 hover:text-emerald-700 hover:bg-emerald-50' }} border border-transparent transition-colors"
                                                    title="{{ $user->is_active ? 'Non-aktifkan Akun' : 'Aktifkan Akun' }}">
                                                <i class="ti {{ $user->is_active ? 'ti-user-off' : 'ti-user-check' }} text-base"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Hapus User -->
                                    @if($user->id !== auth()->id())
                                        <button type="button"
                                                @click="openDeleteModal(@js([
                                                    'id' => $user->id,
                                                    'name' => $user->name,
                                                    'email' => $user->email,
                                                    'role' => $user->role
                                                ]))"
                                                class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-200 transition-colors"
                                                title="Hapus Pengguna">
                                            <i class="ti ti-trash text-base"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-200/80 text-slate-300 flex items-center justify-center mx-auto mb-2 text-xl">
                                    <i class="ti ti-users-off"></i>
                                </div>
                                <p class="text-xs font-bold text-slate-600">Tidak ada data pengguna yang ditemukan</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">Coba sesuaikan kata kunci pencarian atau filter Anda</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginasi -->
        <div class="pt-2">
            {{ $users->links() }}
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 1: TAMBAH PENGGUNA BARU                                            -->
    <!-- ========================================================================= -->
    <div x-show="modalCreateOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="modalCreateOpen = false">
        
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 space-y-5 overflow-hidden transform transition-all"
             @click.outside="modalCreateOpen = false">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-gradient-to-tr from-cyan-600 to-emerald-600 text-white flex items-center justify-center shadow-xs">
                        <i class="ti ti-user-plus text-base"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Tambah Pengguna Baru</h3>
                        <p class="text-[11px] text-slate-400">Daftarkan akun login baru ke dalam sistem AMANA</p>
                    </div>
                </div>
                <button type="button" @click="modalCreateOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('sistem.users.store') }}" class="space-y-4">
                @csrf

                <!-- Nama Lengkap -->
                <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nama Lengkap <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" required placeholder="Contoh: Ahmad Rizki Pratama"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-medium">
                </div>

                <!-- Email Login -->
                <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                        Email Login <span class="text-rose-500">*</span>
                    </label>
                    <input type="email" name="email" required placeholder="nama@alazhar.or.id"
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-medium">
                </div>

                <!-- Peran (Role) -->
                <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                        Peran (Role) <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-start gap-2.5 p-3 rounded-2xl border cursor-pointer transition-all"
                               :class="createRole === 'super_admin' ? 'border-emerald-500 bg-emerald-50/50 shadow-xs' : 'border-slate-200 bg-white hover:bg-slate-50'">
                            <input type="radio" name="role" value="super_admin" x-model="createRole" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <p class="text-xs font-extrabold text-slate-900">Super Admin</p>
                                <p class="text-[10px] text-slate-500 mt-0.5">CRUD penuh, master, & sistem</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-2.5 p-3 rounded-2xl border cursor-pointer transition-all"
                               :class="createRole === 'viewer' ? 'border-indigo-500 bg-indigo-50/50 shadow-xs' : 'border-slate-200 bg-white hover:bg-slate-50'">
                            <input type="radio" name="role" value="viewer" x-model="createRole" class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <p class="text-xs font-extrabold text-slate-900">Viewer</p>
                                <p class="text-[10px] text-slate-500 mt-0.5">Read-only & ekspor PDF</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Kata Sandi & Konfirmasi -->
                <div class="space-y-3 p-3.5 bg-slate-50 rounded-2xl border border-slate-200/80">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-extrabold text-slate-700 uppercase tracking-wider">Kata Sandi Akun</span>
                        <button type="button" @click="generateRandomPassword('create')"
                                class="text-[11px] font-bold text-cyan-700 hover:text-cyan-800 flex items-center gap-1">
                            <i class="ti ti-refresh text-xs"></i>
                            Generate Acak
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div class="relative">
                            <input :type="showCreatePassword ? 'text' : 'password'"
                                   name="password"
                                   x-model="createPassword"
                                   required
                                   minlength="6"
                                   placeholder="Kata sandi baru (min 6)"
                                   class="w-full pl-3 pr-8 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                            <button type="button" @click="showCreatePassword = !showCreatePassword"
                                    class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600">
                                <i class="ti" :class="showCreatePassword ? 'ti-eye-off' : 'ti-eye'"></i>
                            </button>
                        </div>

                        <div class="relative">
                            <input :type="showCreatePassword ? 'text' : 'password'"
                                   name="password_confirmation"
                                   x-model="createPasswordConfirm"
                                   required
                                   minlength="6"
                                   placeholder="Ulangi kata sandi"
                                   class="w-full pl-3 pr-8 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        </div>
                    </div>
                </div>

                <!-- Tautan Penanggung Jawab / Amil (Opsional) -->
                <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                        Tautkan ke Profil Amil / PIC <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <select name="penanggung_jawab_id"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-medium">
                        <option value="">-- Tanpa Tautan Amil --</option>
                        @foreach($picList as $pic)
                            <option value="{{ $pic->id }}">
                                [NIA {{ $pic->kode_pic }}] {{ $pic->nama }} {{ $pic->divisi ? '('.$pic->divisi->nama_divisi.')' : '' }} {{ $pic->user_id ? '— (Sudah terhubung akun lain)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1">Menghubungkan akun login ini dengan identitas pemegang aset resmi di data master.</p>
                </div>

                <!-- Status Aktif -->
                <div class="flex items-center gap-2.5 pt-1">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="create_is_active" value="1" checked
                           class="w-4 h-4 text-emerald-600 rounded-md border-slate-300 focus:ring-emerald-500">
                    <label for="create_is_active" class="text-xs font-bold text-slate-700 cursor-pointer">
                        Status akun langsung aktif (dapat langsung login)
                    </label>
                </div>

                <!-- Actions Button -->
                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                    <button type="button" @click="modalCreateOpen = false"
                            class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-extrabold text-xs uppercase tracking-wider shadow-md shadow-emerald-600/20 transition-all">
                        Simpan Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2: EDIT PENGGUNA                                                    -->
    <!-- ========================================================================= -->
    <div x-show="modalEditOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="modalEditOpen = false">
        
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 space-y-5 overflow-hidden transform transition-all"
             @click.outside="modalEditOpen = false">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center shadow-xs">
                        <i class="ti ti-user-edit text-base"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Edit Data Pengguna</h3>
                        <p class="text-[11px] text-slate-400">Perbarui informasi profil, peran, dan status keaktifan akun</p>
                    </div>
                </div>
                <button type="button" @click="modalEditOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <form method="POST" :action="'/sistem/users/' + editUser.id" class="space-y-4">
                @csrf
                @method('PUT')

                <!-- Nama Lengkap -->
                <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nama Lengkap <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" x-model="editUser.name" required
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-medium">
                </div>

                <!-- Email Login -->
                <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                        Email Login <span class="text-rose-500">*</span>
                    </label>
                    <input type="email" name="email" x-model="editUser.email" required
                           class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-medium">
                </div>

                <!-- Peran (Role) -->
                <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                        Peran (Role) <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-start gap-2.5 p-3 rounded-2xl border cursor-pointer transition-all"
                               :class="editUser.role === 'super_admin' ? 'border-emerald-500 bg-emerald-50/50 shadow-xs' : 'border-slate-200 bg-white hover:bg-slate-50'">
                            <input type="radio" name="role" value="super_admin" x-model="editUser.role" :disabled="editUser.is_self" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <p class="text-xs font-extrabold text-slate-900">Super Admin</p>
                                <p class="text-[10px] text-slate-500 mt-0.5">CRUD penuh & sistem</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-2.5 p-3 rounded-2xl border cursor-pointer transition-all"
                               :class="editUser.role === 'viewer' ? 'border-indigo-500 bg-indigo-50/50 shadow-xs' : 'border-slate-200 bg-white hover:bg-slate-50'">
                            <input type="radio" name="role" value="viewer" x-model="editUser.role" :disabled="editUser.is_self" class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <p class="text-xs font-extrabold text-slate-900">Viewer</p>
                                <p class="text-[10px] text-slate-500 mt-0.5">Read-only</p>
                            </div>
                        </label>
                    </div>
                    <template x-if="editUser.is_self">
                        <p class="text-[10px] text-amber-600 font-semibold mt-1.5 flex items-center gap-1">
                            <i class="ti ti-info-circle"></i>
                            Anda tidak dapat menurunkan peran akun Anda sendiri.
                        </p>
                    </template>
                </div>

                <!-- Tautan Penanggung Jawab / Amil (Opsional) -->
                <div>
                    <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                        Tautkan ke Profil Amil / PIC <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <select name="penanggung_jawab_id" x-model="editUser.penanggung_jawab_id"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-medium">
                        <option value="">-- Tanpa Tautan Amil --</option>
                        @foreach($picList as $pic)
                            <option value="{{ $pic->id }}">
                                [NIA {{ $pic->kode_pic }}] {{ $pic->nama }} {{ $pic->divisi ? '('.$pic->divisi->nama_divisi.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Aktif -->
                <div class="flex items-center gap-2.5 pt-1">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="edit_is_active" value="1" x-model="editUser.is_active" :disabled="editUser.is_self"
                           class="w-4 h-4 text-emerald-600 rounded-md border-slate-300 focus:ring-emerald-500">
                    <label for="edit_is_active" class="text-xs font-bold text-slate-700 cursor-pointer">
                        Status akun aktif (bisa login)
                    </label>
                </div>
                <template x-if="editUser.is_self">
                    <p class="text-[10px] text-amber-600 font-semibold flex items-center gap-1">
                        <i class="ti ti-info-circle"></i>
                        Anda tidak dapat menonaktifkan akun Anda sendiri.
                    </p>
                </template>

                <!-- Actions Button -->
                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                    <button type="button" @click="modalEditOpen = false"
                            class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-extrabold text-xs uppercase tracking-wider shadow-md shadow-emerald-600/20 transition-all">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 3: RESET PASSWORD                                                   -->
    <!-- ========================================================================= -->
    <div x-show="modalResetOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="modalResetOpen = false">
        
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-6 space-y-5 overflow-hidden transform transition-all"
             @click.outside="modalResetOpen = false">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-cyan-50 text-cyan-700 border border-cyan-200 flex items-center justify-center shadow-xs">
                        <i class="ti ti-key text-base"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Reset Kata Sandi</h3>
                        <p class="text-[11px] text-slate-400">Atur ulang kata sandi pengguna terpilih</p>
                    </div>
                </div>
                <button type="button" @click="modalResetOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <i class="ti ti-x text-lg"></i>
                </button>
            </div>

            <!-- Target User Info Box -->
            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200/80 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                    <i class="ti ti-user"></i>
                </div>
                <div class="min-w-0">
                    <p class="font-extrabold text-slate-900 text-xs truncate" x-text="resetUser.name"></p>
                    <p class="text-[11px] text-slate-500 truncate" x-text="resetUser.email"></p>
                </div>
            </div>

            <form method="POST" :action="'/sistem/users/' + resetUser.id + '/reset-password'" class="space-y-4">
                @csrf

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">
                            Kata Sandi Baru <span class="text-rose-500">*</span>
                        </label>
                        <button type="button" @click="generateRandomPassword('reset')"
                                class="text-[11px] font-bold text-cyan-700 hover:text-cyan-800 flex items-center gap-1">
                            <i class="ti ti-refresh text-xs"></i>
                            Generate Acak
                        </button>
                    </div>

                    <div class="relative">
                        <input :type="showResetPassword ? 'text' : 'password'"
                               name="password"
                               x-model="resetPasswordVal"
                               required
                               minlength="6"
                               placeholder="Masukkan kata sandi baru (min 6 karakter)"
                               class="w-full pl-3.5 pr-8 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono">
                        <button type="button" @click="showResetPassword = !showResetPassword"
                                class="absolute right-2.5 top-3 text-slate-400 hover:text-slate-600">
                            <i class="ti" :class="showResetPassword ? 'ti-eye-off' : 'ti-eye'"></i>
                        </button>
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1">
                            Konfirmasi Kata Sandi <span class="text-rose-500">*</span>
                        </label>
                        <input :type="showResetPassword ? 'text' : 'password'"
                               name="password_confirmation"
                               x-model="resetPasswordConfirmVal"
                               required
                               minlength="6"
                               placeholder="Ulangi kata sandi baru"
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-mono">
                    </div>
                </div>

                <!-- Actions Button -->
                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                    <button type="button" @click="modalResetOpen = false"
                            class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-extrabold text-xs uppercase tracking-wider shadow-md shadow-emerald-600/20 transition-all">
                        Reset Kata Sandi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 4: HAPUS PENGGUNA                                                   -->
    <!-- ========================================================================= -->
    <div x-show="modalDeleteOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="modalDeleteOpen = false">
        
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-6 space-y-5 overflow-hidden transform transition-all"
             @click.outside="modalDeleteOpen = false">
            
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center flex-shrink-0 text-xl shadow-xs">
                    <i class="ti ti-alert-triangle"></i>
                </span>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Konfirmasi Hapus Pengguna</h3>
                    <p class="text-[11px] text-slate-400">Tindakan ini permanen dan tidak dapat dibatalkan</p>
                </div>
            </div>

            <div class="p-3.5 bg-rose-50/50 rounded-2xl border border-rose-200/80 text-xs text-rose-900 space-y-1.5">
                <p class="font-bold">Apakah Anda yakin ingin menghapus akun berikut?</p>
                <div class="p-2.5 bg-white rounded-xl border border-rose-100 font-medium">
                    <p class="font-extrabold text-slate-900" x-text="deleteUser.name"></p>
                    <p class="text-slate-500 text-[11px]" x-text="deleteUser.email"></p>
                </div>
                <p class="text-[11px] text-rose-700">Tautan profil Amil (jika ada) akan otomatis dilepaskan dari akun ini.</p>
            </div>

            <form method="POST" :action="'/sistem/users/' + deleteUser.id" class="flex items-center justify-end gap-2.5 pt-2">
                @csrf
                @method('DELETE')
                <button type="button" @click="modalDeleteOpen = false"
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    Batal
                </button>
                <button type="submit"
                        class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs uppercase tracking-wider shadow-md shadow-rose-600/20 transition-all">
                    Hapus Pengguna
                </button>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('userManager', () => ({
        modalCreateOpen: false,
        modalEditOpen: false,
        modalResetOpen: false,
        modalDeleteOpen: false,

        createRole: 'super_admin',
        createPassword: '',
        createPasswordConfirm: '',
        showCreatePassword: false,

        editUser: {
            id: null,
            name: '',
            email: '',
            role: 'viewer',
            is_active: true,
            penanggung_jawab_id: '',
            is_self: false
        },

        resetUser: {
            id: null,
            name: '',
            email: ''
        },
        resetPasswordVal: '',
        resetPasswordConfirmVal: '',
        showResetPassword: false,

        deleteUser: {
            id: null,
            name: '',
            email: '',
            role: ''
        },

        openCreateModal() {
            this.createRole = 'super_admin';
            this.createPassword = '';
            this.createPasswordConfirm = '';
            this.showCreatePassword = false;
            this.modalCreateOpen = true;
        },

        openEditModal(user) {
            this.editUser = {
                id: user.id,
                name: user.name,
                email: user.email,
                role: user.role,
                is_active: user.is_active,
                penanggung_jawab_id: user.penanggung_jawab_id || '',
                is_self: user.is_self
            };
            this.modalEditOpen = true;
        },

        openResetModal(user) {
            this.resetUser = {
                id: user.id,
                name: user.name,
                email: user.email
            };
            this.resetPasswordVal = '';
            this.resetPasswordConfirmVal = '';
            this.showResetPassword = false;
            this.modalResetOpen = true;
        },

        openDeleteModal(user) {
            this.deleteUser = {
                id: user.id,
                name: user.name,
                email: user.email,
                role: user.role
            };
            this.modalDeleteOpen = true;
        },

        generateRandomPassword(target) {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
            let pwd = '';
            for (let i = 0; i < 10; i++) {
                pwd += chars.charAt(Math.floor(Math.random() * chars.length));
            }

            if (target === 'create') {
                this.createPassword = pwd;
                this.createPasswordConfirm = pwd;
                this.showCreatePassword = true;
            } else if (target === 'reset') {
                this.resetPasswordVal = pwd;
                this.resetPasswordConfirmVal = pwd;
                this.showResetPassword = true;
            }
        }
    }));
});
</script>
@endpush
