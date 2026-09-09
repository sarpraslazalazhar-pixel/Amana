<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Aset AMANA - Al Azhar Peduli</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col justify-between selection:bg-cyan-500 selection:text-white antialiased">

    <!-- Header / Navbar -->
    <header class="border-b border-slate-200/80 bg-white/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <img src="{{ asset('logo-amana.png') }}" alt="AMANA Logo" class="h-10 w-auto object-contain">
            <div>
                @auth
                    <a href="{{ route('dashboard') }}" class="py-2.5 px-5 rounded-2xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition-all">
                        Buka Dashboard &rarr;
                    </a>
                @else
                    <a href="{{ route('login') }}" class="py-2.5 px-5 rounded-2xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition-all">
                        Masuk Portal
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="max-w-5xl mx-auto px-6 py-20 text-center flex-1 flex flex-col items-center justify-center">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white border border-slate-200/80 text-xs font-semibold text-emerald-700 shadow-sm mb-6">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            General Affair · Al Azhar Peduli
        </div>
        <h1 class="text-4xl sm:text-6xl font-extrabold text-slate-900 tracking-tight max-w-3xl leading-tight">
            Sistem Manajemen &amp; Inventaris Aset Terpadu
        </h1>
        <p class="mt-6 text-base sm:text-lg text-slate-600 max-w-2xl font-normal leading-relaxed">
            Kelola pencatatan aset fisik, estimasi penyusutan nilai buku otomatis, serta verifikasi lokasi via QR Code secara transparan dan efisien.
        </p>

        <div class="mt-10 flex flex-col sm:flex-row items-center gap-4">
            <a href="{{ route('login') }}" class="w-full sm:w-auto py-3.5 px-8 rounded-2xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 text-white font-bold text-sm shadow-md shadow-emerald-600/20 transition-all">
                Masuk ke System Portal
            </a>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200/80 py-6 text-center text-xs text-slate-500 bg-white">
        &copy; {{ date('Y') }} Al Azhar Peduli. All rights reserved.
    </footer>

</body>
</html>
