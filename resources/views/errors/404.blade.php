<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan · AMANA</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #ffffff;
        }

        .blend-multiply {
            mix-blend-mode: multiply;
        }
    </style>
</head>
<body class="min-h-screen min-h-dvh bg-white flex flex-col justify-between relative overflow-x-hidden text-slate-800 antialiased selection:bg-blue-100 selection:text-blue-900">

    <!-- Kontur Halus Background Sudut Kanan Bawah Sesuai Desain Referensi -->
    <div class="absolute bottom-0 right-0 w-[300px] sm:w-[450px] lg:w-[560px] h-[260px] sm:h-[380px] pointer-events-none select-none overflow-hidden z-0">
        <svg viewBox="0 0 600 400" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full opacity-70">
            <path d="M120 400C180 280 320 220 600 240V400H120Z" fill="url(#waveGrad404)"/>
            <defs>
                <linearGradient id="waveGrad404" x1="120" y1="300" x2="600" y2="400" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#bae6fd" stop-opacity="0.35"/>
                    <stop offset="0.6" stop-color="#a7f3d0" stop-opacity="0.45"/>
                    <stop offset="1" stop-color="#6ee7b7" stop-opacity="0.2"/>
                </linearGradient>
            </defs>
        </svg>
    </div>

    <!-- Header: Logo AMANA di Pojok Kiri Atas -->
    <header class="w-full px-6 sm:px-12 py-6 sm:py-8 relative z-10">
        <a href="{{ url('/') }}" class="inline-block focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 rounded-lg">
            <img src="{{ asset('logo-amana.png') }}"
                 alt="AMANA · Al Azhar Peduli Management Asset"
                 class="h-34 sm:h-34 w-auto object-contain">
        </a>
    </header>

    <!-- Konten Utama: 2 Kolom Sisi Kiri Kartun & Sisi Kanan Pesan Kesalahan -->
    <main class="w-full max-w-6xl mx-auto px-6 sm:px-12 py-6 flex-1 flex flex-col md:flex-row items-center justify-center gap-10 md:gap-14 lg:gap-20 relative z-10">

        <!-- Kolom Kiri: Ilustrasi Kartun (image.png) Tanpa Animasi & Menyatu Sempurna -->
        <div class="flex-1 flex justify-center md:justify-end w-full">
            <div class="w-full max-w-[340px] sm:max-w-[420px] lg:max-w-[490px]">
                <img src="{{ asset('image.png') }}"
                     alt="Ilustrasi Terjadi Kesalahan"
                     class="w-full h-auto object-contain select-none pointer-events-none blend-multiply">
            </div>
        </div>

        <!-- Kolom Kanan: Teks & Tombol Aksi -->
        <div class="flex-1 text-center md:text-left flex flex-col items-center md:items-start max-w-lg">
            <h1 class="text-3xl sm:text-4xl lg:text-[50px] font-extrabold text-[#0f172a] tracking-tight leading-[1.18] mb-4">
                Oops...<br>Terjadi Kesalahan
            </h1>

            <p class="text-slate-500 text-sm sm:text-base leading-relaxed mb-8 max-w-md font-normal">
                Halaman yang Anda cari tidak dapat ditemukan atau telah dipindahkan. Silakan periksa kembali alamat URL atau kembali ke beranda.
            </p>

            <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 sm:gap-4 w-full">
                <!-- Tombol Utama: Kembali ke Beranda -->
                <a href="{{ url('/') }}"
                   class="inline-flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-xl bg-[#1d63ed] hover:bg-[#1554d1] text-white font-semibold text-sm sm:text-base shadow-sm hover:shadow-md hover:shadow-blue-500/20 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                    </svg>
                    <span>Kembali ke Beranda</span>
                </a>

                <!-- Tombol Sekunder: Coba Lagi -->
                <button type="button"
                        onclick="window.location.reload()"
                        class="inline-flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 font-semibold text-sm sm:text-base border border-slate-200 hover:border-slate-300 shadow-sm transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2">
                    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20 11A8.1 8.1 0 0 0 4.5 9M4 5v4h4" />
                        <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" />
                    </svg>
                    <span>Coba Lagi</span>
                </button>
            </div>
        </div>

    </main>

    <!-- Footer: Tagline di Pojok Kanan Bawah Sesuai Desain Referensi -->
    <footer class="w-full px-6 sm:px-12 py-6 flex items-center justify-center md:justify-end relative z-10">
        <span class="text-xs sm:text-[13px] text-slate-400 font-normal tracking-wide">
            &mdash; Aset Tertata, Kinerja Meningkat &mdash;
        </span>
    </footer>

</body>
</html>
