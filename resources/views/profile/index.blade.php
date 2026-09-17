@extends('layouts.app')

@section('title', 'Profil & Keamanan Akun')
@section('header-title', 'Pengaturan: Profil & Keamanan Akun')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <!-- Flash Notification -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                    <i class="ti ti-check text-base"></i>
                </div>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 p-1">
                <i class="ti ti-x text-sm"></i>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm shadow-xs space-y-1">
            <div class="flex items-center gap-2 font-bold text-rose-700">
                <i class="ti ti-alert-circle text-base"></i>
                <span>Terdapat kesalahan pada input Anda:</span>
            </div>
            <ul class="list-disc list-inside pl-2 space-y-0.5 text-xs text-rose-600">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 1. Header & Identity Card -->
    <div class="p-6 sm:p-8 bg-white rounded-3xl border border-slate-200/80 shadow-xs relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-44 h-44 rounded-full bg-emerald-50/60 pointer-events-none -z-0"></div>
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
            <div class="flex items-center gap-4 sm:gap-5">
                <!-- Avatar Lingkaran Besar -->
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-600 text-white font-extrabold text-2xl sm:text-3xl flex items-center justify-center shadow-lg shadow-emerald-600/20 ring-4 ring-emerald-50">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">{{ $user->name }}</h2>
                        <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-extrabold tracking-wider uppercase {{ $user->role === 'super_admin' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-indigo-50 text-indigo-700 border border-indigo-200' }}">
                            {{ strtoupper(str_replace('_', ' ', $user->role)) }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-cyan-50 text-cyan-700 border border-cyan-200">
                            {{ $user->is_active ? 'Akun Aktif' : 'Non-Aktif' }}
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 flex items-center gap-1.5 font-medium">
                        <i class="ti ti-mail text-slate-400"></i>
                        {{ $user->email }}
                    </p>
                    <div class="flex flex-wrap items-center gap-4 mt-2 text-[11px] text-slate-400">
                        <span class="flex items-center gap-1">
                            <i class="ti ti-calendar"></i>
                            Bergabung {{ $user->created_at ? $user->created_at->translatedFormat('d F Y') : '-' }}
                        </span>
                        @if($user->penanggungJawab)
                            <span class="flex items-center gap-1 text-emerald-600 font-semibold">
                                <i class="ti ti-id-badge-2"></i>
                                Amil: {{ $user->penanggungJawab->nama }} {{ $user->penanggungJawab->divisi ? '('.$user->penanggungJawab->divisi->nama_divisi.')' : '' }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="sm:text-right">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                    <i class="ti ti-arrow-left"></i>
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- 2. Grid Dua Kolom Form: Edit Profil & Ganti Password -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Form 1: Ubah Data Profil -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 sm:p-7 flex flex-col justify-between">
            <div class="space-y-5">
                <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-700 border border-emerald-200/80 flex items-center justify-center shadow-xs">
                        <i class="ti ti-user-edit text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Informasi Akun</h3>
                        <p class="text-[11px] text-slate-400">Perbarui nama pengguna dan alamat email login</p>
                    </div>
                </div>

                <form id="formUpdateProfile" method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <!-- Nama Lengkap -->
                    <div>
                        <label for="name" class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nama Lengkap <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                                <i class="ti ti-user text-base"></i>
                            </span>
                            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                                   class="w-full pl-10 pr-4 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-medium text-slate-800 transition-all">
                        </div>
                    </div>

                    <!-- Email Login -->
                    <div>
                        <label for="email" class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                            Alamat Email Login <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                                <i class="ti ti-mail text-base"></i>
                            </span>
                            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                                   class="w-full pl-10 pr-4 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-medium text-slate-800 transition-all">
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Gunakan format email resmi (cth: admin@alazharpeduli.or.id).</p>
                    </div>

                    <!-- Hak Akses Peran (Readonly Info) -->
                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                            Peran Akses Sistem
                        </label>
                        <div class="px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-100 text-xs font-semibold text-slate-600 flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <i class="ti ti-shield text-slate-500"></i>
                                {{ $user->role === 'super_admin' ? 'Super Administrator (Akses Penuh)' : 'Viewer (Akses Baca Saja)' }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-normal">Dikelola Pengaturan</span>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                                class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-extrabold text-xs uppercase tracking-wider shadow-md shadow-emerald-600/20 transition-all inline-flex items-center justify-center gap-2">
                            <i class="ti ti-device-floppy text-base"></i>
                            Simpan Perubahan Profil
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Form 2: Ganti Kata Sandi -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 sm:p-7 flex flex-col justify-between"
             x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
            <div class="space-y-5">
                <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                    <div class="w-10 h-10 rounded-2xl bg-cyan-50 text-cyan-700 border border-cyan-200/80 flex items-center justify-center shadow-xs">
                        <i class="ti ti-lock text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Perbarui Kata Sandi</h3>
                        <p class="text-[11px] text-slate-400">Jaga keamanan akun dengan kata sandi yang kuat</p>
                    </div>
                </div>

                <form id="formUpdatePassword" method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <!-- Kata Sandi Saat Ini -->
                    <div>
                        <label for="current_password" class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                            Kata Sandi Saat Ini <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                                <i class="ti ti-key text-base"></i>
                            </span>
                            <input :type="showCurrent ? 'text' : 'password'" id="current_password" name="current_password" required
                                   class="w-full pl-10 pr-11 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-medium text-slate-800 transition-all"
                                   placeholder="Masukkan kata sandi lama">
                            <button type="button" @click="showCurrent = !showCurrent"
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1">
                                <i class="ti" :class="showCurrent ? 'ti-eye-off' : 'ti-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Kata Sandi Baru -->
                    <div>
                        <label for="new_password" class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                            Kata Sandi Baru <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                                <i class="ti ti-shield-lock text-base"></i>
                            </span>
                            <input :type="showNew ? 'text' : 'password'" id="new_password" name="password" required minlength="6"
                                   class="w-full pl-10 pr-11 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-medium text-slate-800 transition-all"
                                   placeholder="Minimal 6 karakter">
                            <button type="button" @click="showNew = !showNew"
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1">
                                <i class="ti" :class="showNew ? 'ti-eye-off' : 'ti-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Konfirmasi Kata Sandi Baru -->
                    <div>
                        <label for="password_confirmation" class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">
                            Ulangi Kata Sandi Baru <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                                <i class="ti ti-check-check text-base"></i>
                            </span>
                            <input :type="showConfirm ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required minlength="6"
                                   class="w-full pl-10 pr-11 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-medium text-slate-800 transition-all"
                                   placeholder="Ketik ulang kata sandi baru">
                            <button type="button" @click="showConfirm = !showConfirm"
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1">
                                <i class="ti" :class="showConfirm ? 'ti-eye-off' : 'ti-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                                class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 text-white font-extrabold text-xs uppercase tracking-wider shadow-md shadow-emerald-600/20 transition-all inline-flex items-center justify-center gap-2">
                            <i class="ti ti-key text-base"></i>
                            Perbarui Kata Sandi
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

</div>
@endsection
