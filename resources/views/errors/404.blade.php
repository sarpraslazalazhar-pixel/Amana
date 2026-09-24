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

    <!-- Header: Logo AMANA di Pojok Kiri Atas & Animasi di Pojok Kanan Atas -->
    <header class="w-full px-6 sm:px-12 py-4 sm:py-6 relative z-10 flex items-center justify-between">
        <a href="{{ url('/') }}" class="inline-block focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 rounded-lg">
            <img src="{{ asset('logo-amana.png') }}"
                 alt="AMANA · Al Azhar Peduli Management Asset"
                 class="h-34 sm:h-34 w-auto object-contain">
        </a>
        <div class="flex items-center gap-2.5 sm:gap-3">
            <div class="text-right">
                <div class="inline-block bg-slate-100/90 border border-slate-200/80 rounded-2xl rounded-tr-none px-3 py-1.5 shadow-2xs">
                    <p class="text-xs sm:text-sm font-bold text-slate-800">
                        &ldquo;Hayo nyasar ya:v&rdquo;
                    </p>
                </div>
                <p class="text-[10px] sm:text-[11px] text-slate-400 font-medium mt-1 mr-1">
                    by <a href="https://instagram.com/gbrnmewing" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-700 font-bold hover:underline">IT dev</a>
                </p>
            </div>
            <img src="{{ asset('catgip.webp') }}"
                 alt="Animasi Kucing"
                 onclick="spawnCatEmojis(event)"
                 class="h-16 sm:h-20 md:h-24 w-auto object-contain select-none cursor-pointer active:scale-90 hover:scale-105 transition-transform"
                 title="Klik meong! 🐾">
        </div>
    </header>

    <!-- Konten Utama: 2 Kolom Sisi Kiri Kartun & Sisi Kanan Pesan Kesalahan (Optical Center Diangkat ke Atas) -->
    <main class="w-full max-w-6xl mx-auto px-6 sm:px-12 py-4 flex-1 flex flex-col md:flex-row items-center justify-center gap-10 md:gap-14 lg:gap-20 relative z-10 -mt-6 sm:-mt-10 lg:-mt-14">

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
    <footer class="w-full px-6 sm:px-12 py-4 sm:py-5 flex items-center justify-center md:justify-end relative z-10">
        <span class="text-xs sm:text-[13px] text-slate-400 font-normal tracking-wide">
            &mdash; Aset Tertata, Kinerja Meningkat &mdash;
        </span>
    </footer>

    <script>
        function spawnCatEmojis(event) {
            const emojis = ['🐱', '😸', '😹', '😻', '😽', '🐾', '🐈', '✨', '🐟', '🧶', '💖'];
            const rect = event.currentTarget.getBoundingClientRect();
            const originX = rect.left + rect.width / 2;
            const originY = rect.top + rect.height / 2;

            for (let i = 0; i < 24; i++) {
                const el = document.createElement('span');
                el.textContent = emojis[Math.floor(Math.random() * emojis.length)];
                el.style.position = 'fixed';
                el.style.left = `${originX}px`;
                el.style.top = `${originY}px`;
                el.style.fontSize = `${Math.floor(Math.random() * 18 + 20)}px`;
                el.style.pointerEvents = 'none';
                el.style.userSelect = 'none';
                el.style.zIndex = '9999';
                document.body.appendChild(el);

                const angle = Math.random() * Math.PI * 2;
                const dist = Math.random() * 180 + 60;
                const destX = Math.cos(angle) * dist;
                const destY = Math.sin(angle) * dist - 30;
                const rot = (Math.random() - 0.5) * 360;

                el.animate([
                    { transform: 'translate(-50%, -50%) scale(0.3)', opacity: 1 },
                    { transform: `translate(calc(-50% + ${destX * 0.7}px), calc(-50% + ${destY * 0.7}px)) scale(1.4) rotate(${rot * 0.5}deg)`, opacity: 1, offset: 0.6 },
                    { transform: `translate(calc(-50% + ${destX}px), calc(-50% + ${destY + 40}px)) scale(0.9) rotate(${rot}deg)`, opacity: 0 }
                ], {
                    duration: Math.random() * 400 + 800,
                    easing: 'cubic-bezier(0.2, 0.8, 0.3, 1)',
                    fill: 'forwards'
                }).onfinish = () => el.remove();
            }
        }
    </script>

</body>
</html>
