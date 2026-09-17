<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - AMANA · General Affair</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            background-color: #f1f5f9;
        }
        .bg-daylight {
            background-color: #f1f5f9;
            background-image:
                radial-gradient(at 0% 0%, rgba(0, 163, 255, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(0, 229, 163, 0.12) 0px, transparent 50%),
                radial-gradient(at 50% 35%, rgba(248, 250, 252, 0.8) 0px, transparent 100%);
        }

        /* ===== Animasi Scene Inventory & Asset Management ===== */

        /* Latar Blob Mengambang Halus */
        .blob-drift {
            animation: blobMotion 8s ease-in-out infinite alternate;
            transform-origin: center;
        }
        @keyframes blobMotion {
            0%   { transform: translate(0, 0) scale(1); }
            100% { transform: translate(8px, -5px) scale(1.02); }
        }

        /* Gear Roda Gigi */
        .gear-rotate-cw {
            animation: spinCw 14s linear infinite;
            transform-box: view-box;
        }
        .gear-rotate-ccw {
            animation: spinCcw 9s linear infinite;
            transform-box: view-box;
        }
        .gear-fast {
            animation-duration: 2.2s !important;
        }
        @keyframes spinCw {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
        @keyframes spinCcw {
            from { transform: rotate(0deg); }
            to   { transform: rotate(-360deg); }
        }

        /* Sparkles / Bintang Berkilau */
        .sparkle-gleam {
            animation: gleam 2.8s ease-in-out infinite;
        }
        .sparkle-gleam-2 {
            animation: gleam 2.8s ease-in-out infinite 1.2s;
        }
        @keyframes gleam {
            0%, 100% { opacity: 0.35; transform: scale(0.85); }
            50%      { opacity: 1; transform: scale(1.2); }
        }

        /* Kaca Pembesar */
        .mag-hover {
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            transform-box: view-box;
            transform-origin: 368px 202px;
        }
        .mag-scanning {
            animation: magScan 1.2s ease-in-out infinite alternate;
        }
        @keyframes magScan {
            0%   { transform: scale(1) translate(0, 0) rotate(0deg); }
            100% { transform: scale(1.12) translate(-6px, -4px) rotate(-4deg); }
        }

        /* Checklist Transitions */
        .check-pop {
            transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        /* Badge Workflow */
        .badge-breathe {
            animation: badgeBreathe 3.5s ease-in-out infinite;
            transform-box: view-box;
            transform-origin: 545px 125px;
        }
        @keyframes badgeBreathe {
            0%, 100% { transform: scale(1); }
            50%      { transform: scale(1.05); }
        }
        .badge-spinning {
            animation: badgeRotate 1s cubic-bezier(0.34, 1.56, 0.64, 1) infinite;
            transform-box: view-box;
            transform-origin: 545px 125px;
        }
        @keyframes badgeRotate {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }

        /* Worker Idle Sway */
        .worker-sway {
            animation: workerBreathe 4s ease-in-out infinite alternate;
            transform-origin: 110px 425px;
        }
        @keyframes workerBreathe {
            0%   { transform: translateY(0); }
            100% { transform: translateY(-2px); }
        }

        /* Pointing Arm Gesture */
        .pointing-arm {
            transition: transform 0.3s ease;
            transform-origin: 130px 202px;
        }
        .arm-active {
            transform: translateY(-2px) rotate(-2deg);
        }

        /* Box Subtle Float */
        .box-stack {
            transition: transform 0.4s ease;
        }
        .box-active {
            transform: translateY(-3px);
        }

        /* Fallback Structural Layout Rules (Memastikan tata letak selalu rapi & kokoh) */
        .login-card {
            width: 100%;
            max-width: 980px;
            background: #ffffff;
            border-radius: 32px;
            overflow: hidden;
            box-shadow: 0 25px 70px -15px rgba(15, 23, 42, 0.12);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        .login-grid {
            display: grid;
            grid-template-columns: 1fr;
            min-height: 590px;
        }
        @media (min-width: 768px) {
            .login-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        .scene-container {
            position: relative;
            min-height: 380px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .scene-svg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-daylight flex items-center justify-center p-4 sm:p-6 md:p-10 relative overflow-x-hidden select-none"
      x-data="inventoryScene()">

    <!-- Kontur latar luar (siluet garis arsitektural modern) -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden opacity-30">
        <svg class="w-full h-full" viewBox="0 0 1440 900" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
            <path d="M-80 0 C130 160 170 380 140 900" stroke="#94a3b8" stroke-width="2" stroke-linecap="round"/>
            <path d="M-20 0 C180 190 220 420 190 900" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round"/>
            <path d="M1360 0 C1290 270 1280 570 1320 900" stroke="#94a3b8" stroke-width="2" stroke-linecap="round"/>
            <path d="M1410 0 C1350 250 1340 550 1380 900" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
    </div>

    <main class="login-card relative z-10 w-full max-w-[980px] bg-white rounded-[32px] shadow-[0_25px_70px_-15px_rgba(15,23,42,0.12)] overflow-hidden border border-slate-200/80">
        <div class="login-grid grid grid-cols-1 md:grid-cols-2 min-h-[590px]">

            <!-- ===================== PANEL KIRI : SCENE ASSET & INVENTORY ===================== -->
            <div class="scene-container relative bg-gradient-to-br from-sky-50/90 via-slate-50 to-amber-50/30 min-h-[380px] md:min-h-full overflow-hidden border-b md:border-b-0 md:border-r border-slate-200/80 flex flex-col justify-between">

                <!-- Brand Pill Glassmorphic (Top-Left) -->
                <div class="absolute top-5 left-5 z-20 flex items-center space-x-2.5 bg-white/90 backdrop-blur-md px-4 py-2 rounded-2xl text-slate-800 text-xs border border-slate-200/80 shadow-sm transition-transform hover:scale-[1.02]">
                    <img src="{{ asset('logo-icon.png') }}" class="w-8 h-8 object-contain drop-shadow-xs" alt="AMANA Icon">
                    <div>
                        <span class="font-extrabold tracking-wider text-slate-900 block leading-tight">AMANA</span>
                        <span class="text-[10px] font-semibold text-emerald-600 tracking-tight block">Aset & Inventaris GA</span>
                    </div>
                </div>

                <!-- SVG Canvas Ilustrasi Vektor Presisi -->
                <svg class="scene-svg absolute inset-0 w-full h-full pointer-events-none" viewBox="0 0 680 480" fill="none"
                     xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid meet" aria-hidden="true">
                    <defs>
                        <!-- Gradien Latar Belakang Kotak -->
                        <linearGradient id="boxFront" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#f8a765"/>
                            <stop offset="100%" stop-color="#ea8a3c"/>
                        </linearGradient>
                        <linearGradient id="boxSide" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#f49b51"/>
                            <stop offset="100%" stop-color="#df7f33"/>
                        </linearGradient>

                        <!-- Gradien Badge Workflow Oranye -->
                        <linearGradient id="badgeG" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#ff7b2b"/>
                            <stop offset="100%" stop-color="#ea580c"/>
                        </linearGradient>

                        <!-- Bayangan Lembut Kotak -->
                        <filter id="softGlow" x="-10%" y="-10%" width="120%" height="120%">
                            <feDropShadow dx="0" dy="8" stdDeviation="6" flood-color="#0f172a" flood-opacity="0.08"/>
                        </filter>
                        <filter id="badgeShadow" x="-20%" y="-20%" width="140%" height="140%">
                            <feDropShadow dx="0" dy="6" stdDeviation="8" flood-color="#ea580c" flood-opacity="0.35"/>
                        </filter>
                    </defs>

                    <!-- ===== 1. Fluid Sky-Blue Backdrop (Bentuk Organik Biru Muda) ===== -->
                    <g class="blob-drift">
                        <!-- Lapisan Luar Lembut -->
                        <path d="M 60,230 C 40,140 120,70 230,75 C 330,80 370,110 470,85 C 570,60 630,120 625,240 C 620,330 560,420 480,425 C 380,430 200,430 110,410 C 50,380 75,290 60,230 Z"
                              fill="#d8eefc" opacity="0.6"/>
                        <!-- Lapisan Inti -->
                        <path d="M 80,240 C 65,160 135,95 240,100 C 330,105 380,130 480,110 C 560,95 610,150 605,250 C 600,325 550,405 470,410 C 380,415 210,415 130,395 C 80,370 90,300 80,240 Z"
                              fill="#cbe6f9" opacity="0.8"/>
                    </g>

                    <!-- ===== 2. Interlocking Gears (Roda Gigi di Balik Box) ===== -->
                    <g>
                        <!-- Gear 1: Roda Gigi Abu-Biru Tua (Besar) -->
                        <g class="gear-rotate-cw" :class="typing ? 'gear-fast' : ''" style="transform-origin: 275px 115px;">
                            <circle cx="275" cy="115" r="30" fill="#44617d"/>
                            <!-- Gigi Gear Besar (8 Gigi) -->
                            <rect x="269" y="80" width="12" height="10" rx="2" fill="#44617d"/>
                            <rect x="269" y="140" width="12" height="10" rx="2" fill="#44617d"/>
                            <rect x="240" y="109" width="10" height="12" rx="2" fill="#44617d"/>
                            <rect x="300" y="109" width="10" height="12" rx="2" fill="#44617d"/>
                            <rect x="247" y="87" width="10" height="12" rx="2" fill="#44617d" transform="rotate(45, 252, 93)"/>
                            <rect x="293" y="87" width="10" height="12" rx="2" fill="#44617d" transform="rotate(-45, 298, 93)"/>
                            <rect x="247" y="131" width="10" height="12" rx="2" fill="#44617d" transform="rotate(-45, 252, 137)"/>
                            <rect x="293" y="131" width="10" height="12" rx="2" fill="#44617d" transform="rotate(45, 298, 137)"/>
                            <!-- Lubang Tengah Gear -->
                            <circle cx="275" cy="115" r="11" fill="#cbe6f9"/>
                        </g>

                        <!-- Gear 2: Roda Gigi Biru Muda (Kecil) -->
                        <g class="gear-rotate-ccw" :class="typing ? 'gear-fast' : ''" style="transform-origin: 322px 148px;">
                            <circle cx="322" cy="148" r="19" fill="#7cb3e4"/>
                            <!-- Gigi Gear Kecil (8 Gigi) -->
                            <rect x="318" y="125" width="8" height="7" rx="1.5" fill="#7cb3e4"/>
                            <rect x="318" y="164" width="8" height="7" rx="1.5" fill="#7cb3e4"/>
                            <rect x="299" y="144" width="7" height="8" rx="1.5" fill="#7cb3e4"/>
                            <rect x="338" y="144" width="7" height="8" rx="1.5" fill="#7cb3e4"/>
                            <rect x="304" y="130" width="7" height="8" rx="1.5" fill="#7cb3e4" transform="rotate(45, 307.5, 134)"/>
                            <rect x="333" y="130" width="7" height="8" rx="1.5" fill="#7cb3e4" transform="rotate(-45, 336.5, 134)"/>
                            <rect x="304" y="158" width="7" height="8" rx="1.5" fill="#7cb3e4" transform="rotate(-45, 307.5, 162)"/>
                            <rect x="333" y="158" width="7" height="8" rx="1.5" fill="#7cb3e4" transform="rotate(45, 336.5, 162)"/>
                            <!-- Lubang Tengah -->
                            <circle cx="322" cy="148" r="7" fill="#cbe6f9"/>
                        </g>
                    </g>

                    <!-- ===== 3. Clipboard Raksasa (Kanan) ===== -->
                    <g filter="url(#softGlow)">
                        <!-- Papan Punggung Dark Slate -->
                        <rect x="355" y="112" width="205" height="313" rx="18" fill="#203342" stroke="#162531" stroke-width="2"/>

                        <!-- Klip Kayu / Logam Coklat di Atas -->
                        <g>
                            <!-- Badan Klip -->
                            <rect x="400" y="78" width="115" height="42" rx="8" fill="#8e532f"/>
                            <path d="M 436,78 C 436,58 479,58 479,78 Z" fill="#8e532f"/>
                            <!-- Lubang Gantung -->
                            <circle cx="457.5" cy="72" r="8" fill="#203342"/>
                            <!-- Bracket Logam Coklat Tua Penjepit Kertas -->
                            <rect x="412" y="106" width="91" height="12" rx="3" fill="#66391d"/>
                        </g>

                        <!-- Lembaran Kertas Putih Bersih -->
                        <rect x="370" y="128" width="175" height="297" rx="6" fill="#ffffff"/>

                        <!-- Header Dokumen (Garis Abu-abu Elegan) -->
                        <rect x="386" y="158" width="95" height="16" rx="4" fill="#e2e8f0"/>
                        <rect x="386" y="184" width="70" height="4" rx="2" fill="#f1f5f9"/>

                        <!-- Item Checklist 1 (Baris Atas) -->
                        <g class="check-pop">
                            <!-- Garis Teks Dokumen -->
                            <rect x="386" y="222" width="92" height="6" rx="3" fill="#cbd5e1"/>
                            <rect x="386" y="234" width="65" height="4" rx="2" fill="#e2e8f0"/>
                            <!-- Kotak Checklist Emas-Kuning Bulat -->
                            <rect x="488" y="210" width="38" height="38" rx="8" fill="#fab005"/>
                            <!-- Centang Biru Navy Tebal -->
                            <path d="M 496 229 L 503 236 L 518 220" stroke="#1e3a5f" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        </g>

                        <!-- Item Checklist 2 (Baris Tengah) -->
                        <g class="check-pop">
                            <!-- Garis Teks Dokumen -->
                            <rect x="386" y="282" width="88" height="6" rx="3" fill="#cbd5e1"/>
                            <rect x="386" y="294" width="72" height="4" rx="2" fill="#e2e8f0"/>
                            <!-- Kotak Checklist Emas-Kuning Bulat -->
                            <rect x="488" y="270" width="38" height="38" rx="8" fill="#fab005"/>
                            <!-- Centang Biru Navy Tebal -->
                            <path d="M 496 289 L 503 296 L 518 280" stroke="#1e3a5f" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        </g>

                        <!-- Item Checklist 3 (Baris Bawah - Dinamis Interaktif) -->
                        <g class="check-pop">
                            <!-- Garis Teks Dokumen -->
                            <rect x="386" y="342" width="90" height="6" rx="3" fill="#cbd5e1"/>
                            <rect x="386" y="354" width="58" height="4" rx="2" fill="#e2e8f0"/>
                            <!-- Kotak Checklist Emas-Kuning Bulat -->
                            <rect x="488" y="330" width="38" height="38" rx="8" fill="#fab005"/>

                            <!-- Silang Merah (Kondisi Awal persis referensi gambar) -->
                            <g x-show="!verified && !submitted" x-transition>
                                <path d="M 498 340 L 516 358 M 516 340 L 498 358" stroke="#ef4444" stroke-width="4.5" stroke-linecap="round" fill="none"/>
                            </g>
                            <!-- Berubah Menjadi Centang Hijau saat Form Terverifikasi / Diisi -->
                            <g x-show="verified || submitted" x-cloak x-transition>
                                <path d="M 496 349 L 503 356 L 518 340" stroke="#15803d" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                            </g>
                        </g>
                    </g>

                    <!-- ===== 4. Badge Workflow Lingkaran Oranye (Pojok Kanan Atas Clipboard) ===== -->
                    <g filter="url(#badgeShadow)" :class="submitted ? 'badge-spinning' : 'badge-breathe'">
                        <!-- Lingkaran Dasar Oranye -->
                        <circle cx="552" cy="122" r="44" fill="url(#badgeG)"/>

                        <!-- Ikon Workflow / Siklus Aset (Dua Kotak Bersudut Halus + 2 Panah Sirkuler) -->
                        <g>
                            <!-- Kotak Putih Kiri Atas -->
                            <rect x="530" y="102" width="18" height="18" rx="4.5" fill="#ffffff"/>
                            <!-- Kotak Putih Kanan Bawah -->
                            <rect x="556" y="124" width="18" height="18" rx="4.5" fill="#ffffff"/>

                            <!-- Panah Melengkung Atas (Kiri Atas ke Kanan Bawah) -->
                            <path d="M 552 105 C 568 105 572 113 572 120" stroke="#ffffff" stroke-width="3" stroke-linecap="round" fill="none"/>
                            <path d="M 569 116 L 572 122 L 577 118" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>

                            <!-- Panah Melengkung Bawah (Kanan Bawah ke Kiri Atas) -->
                            <path d="M 552 139 C 536 139 532 131 532 124" stroke="#ffffff" stroke-width="3" stroke-linecap="round" fill="none"/>
                            <path d="M 535 128 L 532 122 L 527 126" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        </g>
                    </g>

                    <!-- ===== 5. Tumpukan Kotak Kardus Inventaris (Tengah) ===== -->
                    <g class="box-stack" :class="focused ? 'box-active' : ''" filter="url(#softGlow)">
                        <!-- Kotak A (Bawah Kiri - Kotak Lebar) -->
                        <g>
                            <rect x="175" y="318" width="120" height="107" rx="3" fill="url(#boxFront)"/>
                            <!-- Lakban Coklat Tua Vertikal di Tutup Kotak -->
                            <rect x="220" y="318" width="22" height="38" rx="1.5" fill="#693717"/>
                            <!-- Label Pengiriman Barcode Putih -->
                            <rect x="242" y="392" width="38" height="18" rx="2" fill="#ffffff"/>
                            <line x1="248" y1="398" x2="274" y2="398" stroke="#94a3b8" stroke-width="2" stroke-linecap="round"/>
                            <line x1="248" y1="404" x2="268" y2="404" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round"/>
                        </g>

                        <!-- Kotak B (Bawah Kanan - Kotak Tinggi Besar) -->
                        <g>
                            <rect x="282" y="252" width="168" height="173" rx="3" fill="url(#boxSide)"/>
                            <!-- Lakban Coklat Tua Vertikal -->
                            <rect x="320" y="252" width="36" height="66" rx="2" fill="#693717"/>
                            <!-- Label Pengiriman Barcode Putih -->
                            <rect x="368" y="388" width="48" height="22" rx="2" fill="#ffffff"/>
                            <line x1="376" y1="395" x2="408" y2="395" stroke="#94a3b8" stroke-width="2.5" stroke-linecap="round"/>
                            <line x1="376" y1="403" x2="400" y2="403" stroke="#cbd5e1" stroke-width="2" stroke-linecap="round"/>
                        </g>

                        <!-- Kotak C (Tengah Kiri - Duduk di atas Kotak A) -->
                        <g>
                            <rect x="192" y="230" width="120" height="88" rx="3" fill="url(#boxFront)"/>
                            <!-- Lakban Coklat Tua -->
                            <rect x="236" y="230" width="22" height="40" rx="1.5" fill="#693717"/>
                            <!-- Label Barcode Putih -->
                            <rect x="260" y="286" width="34" height="16" rx="2" fill="#ffffff"/>
                            <line x1="265" y1="292" x2="288" y2="292" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round"/>
                            <line x1="265" y1="297" x2="282" y2="297" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round"/>
                        </g>

                        <!-- Kotak D (Paling Atas - Duduk di atas Kotak C) -->
                        <g>
                            <rect x="242" y="154" width="120" height="76" rx="3" fill="url(#boxFront)"/>
                            <!-- Lakban Coklat Tua -->
                            <rect x="286" y="154" width="24" height="38" rx="1.5" fill="#693717"/>
                            <!-- Label Barcode Putih -->
                            <rect x="314" y="198" width="28" height="14" rx="2" fill="#ffffff"/>
                            <line x1="319" y1="203" x2="336" y2="203" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round"/>
                            <line x1="319" y1="207" x2="332" y2="207" stroke="#cbd5e1" stroke-width="1.2" stroke-linecap="round"/>
                        </g>
                    </g>

                    <!-- ===== 6. Kaca Pembesar (Bersandar di Samping Box Atas & Clipboard) ===== -->
                    <g class="mag-hover" :class="focused ? 'mag-scanning' : ''">
                        <!-- Gagang Kaca Pembesar Oranye-Coklat Miring 45 Derajat -->
                        <line x1="392" y1="226" x2="424" y2="258" stroke="#e06924" stroke-width="9" stroke-linecap="round"/>
                        <circle cx="394" cy="228" r="4.5" fill="#243746"/>

                        <!-- Bingkai Lensa Kaca Pembesar (Dark Slate) -->
                        <circle cx="374" cy="208" r="23" fill="#dcf0ff" opacity="0.65"/>
                        <circle cx="374" cy="208" r="23" stroke="#243746" stroke-width="5" fill="none"/>

                        <!-- Pantulan Kilau Kaca Putih -->
                        <path d="M 362 196 C 367 190 376 190 382 193" stroke="#ffffff" stroke-width="3" stroke-linecap="round" fill="none" opacity="0.85"/>
                    </g>

                    <!-- ===== 7. Sparkles / Kilauan Bintang (✦) ===== -->
                    <g>
                        <!-- Sparkle Besar (Putih Berlian) -->
                        <path class="sparkle-gleam"
                              d="M 234,140 Q 234,162 212,162 Q 234,162 234,184 Q 234,162 256,162 Q 234,162 234,140 Z"
                              fill="#ffffff" opacity="0.95" filter="drop-shadow(0 0 6px #fde047)"/>
                        <!-- Sparkle Kecil -->
                        <path class="sparkle-gleam-2"
                              d="M 212,126 Q 212,137 201,137 Q 212,137 212,148 Q 212,137 223,137 Q 212,137 212,126 Z"
                              fill="#ffffff" opacity="0.85"/>
                    </g>

                    <!-- ===== 8. Petugas Inspeksi GA / Inventory Worker (Kiri) ===== -->
                    <g class="worker-sway">
                        <!-- Sepatu Kerja Hitam Formal Berdiri Tegak -->
                        <!-- Kaki Kiri -->
                        <path d="M 88,425 L 105,425 C 107.5,425 109,423 109,420 C 109,417 107,414 100,414 L 94,414 L 88,425 Z" fill="#182533"/>
                        <!-- Kaki Kanan -->
                        <path d="M 132,425 L 152,425 C 155,425 157,423 156,419 C 155,415 151,414 143,414 L 137,414 L 132,425 Z" fill="#182533"/>

                        <!-- Celana Panjang Kerja Dark Charcoal Slate -->
                        <!-- Kaki Kiri -->
                        <path d="M 91,295 L 107,295 L 105,417 L 91,417 Z" fill="#243444"/>
                        <!-- Kaki Kanan -->
                        <path d="M 131,295 L 147,295 L 149,417 L 134,417 Z" fill="#243444"/>
                        <!-- Pinggul & Pinggang -->
                        <path d="M 89,286 L 148,286 L 148,300 C 139,303 100,303 89,300 Z" fill="#243444"/>
                        <!-- Ikat Pinggang -->
                        <rect x="90" y="284" width="58" height="5" fill="#182533"/>
                        <rect x="114" y="283" width="10" height="7" rx="1.5" fill="#94a3b8"/>

                        <!-- Kemeja Putih Lengan Panjang di Bawah Rompi -->
                        <path d="M 92,204 L 146,204 L 148,286 L 90,286 Z" fill="#ffffff"/>

                        <!-- Rompi Safety Oranye Terang (High-Visibility Vest) -->
                        <path d="M 96,205 L 110,205 L 115,248 L 123,205 L 142,205 L 147,286 L 91,286 Z" fill="#f97316"/>

                        <!-- Garis Reflektif Rompi Neon Kuning -->
                        <!-- Garis Bahu Kiri -->
                        <rect x="100" y="205" width="6.5" height="79" fill="#fde047"/>
                        <!-- Garis Bahu Kanan -->
                        <rect x="131" y="205" width="6.5" height="79" fill="#fde047"/>
                        <!-- Garis Reflektif Horizontal di Pinggang -->
                        <rect x="91" y="266" width="56" height="6.5" fill="#fde047"/>

                        <!-- Kerah Kemeja Putih & Dasi Hitam Tipis -->
                        <polygon points="112,204 119,218 126,204" fill="#ffffff"/>
                        <polygon points="117,208 121,208 120,224 118,224" fill="#1e293b"/>

                        <!-- Lengan Kanan Menjepit Map Berkas Hitam -->
                        <!-- Lengan Atas Kemeja Putih -->
                        <path d="M 92,205 L 80,240 L 92,246 L 100,214 Z" fill="#ffffff"/>
                        <!-- Map Berkas Hitam Dijepit di Bawah Ketiak -->
                        <rect x="76" y="235" width="46" height="56" rx="4" transform="rotate(-12, 76, 235)" fill="#182533"/>
                        <rect x="80" y="238" width="4" height="50" rx="1" transform="rotate(-12, 80, 238)" fill="#ffffff" opacity="0.8"/>
                        <!-- Tangan Peach Memegang Map -->
                        <circle cx="94" cy="274" r="7" fill="#fed7aa"/>

                        <!-- Leher Petugas -->
                        <rect x="113" y="186" width="13" height="19" rx="3" fill="#fed7aa"/>

                        <!-- Kepala & Wajah Samping (Profil Hadap Kanan) -->
                        <g>
                            <!-- Kepala Dasar -->
                            <path d="M 108,160 C 108,148 118,142 130,144 C 142,146 148,156 148,168 C 148,178 138,187 127,187 C 117,187 108,175 108,160 Z" fill="#fed7aa"/>
                            <!-- Hidung Mancung Mengarah ke Kanan -->
                            <path d="M 144,166 L 152,172 L 143,176 Z" fill="#fed7aa"/>
                            <!-- Telinga -->
                            <circle cx="118" cy="172" r="5" fill="#fcd34d" opacity="0.6"/>
                            <circle cx="118" cy="172" r="3.5" fill="#fed7aa"/>
                            <!-- Rambut Coklat Tua Rapi di Bawah Helm -->
                            <path d="M 107,162 C 105,172 110,183 116,183 L 115,168 Z" fill="#3d2112"/>
                            <!-- Alis & Mata Minimalis Elegan -->
                            <path d="M 137,163 L 143,164" stroke="#1e293b" stroke-width="1.8" stroke-linecap="round"/>
                            <circle cx="140" cy="168" r="1.6" fill="#1e293b"/>
                        </g>

                        <!-- Helm Proyek Kuning (Safety Hard Hat) -->
                        <g>
                            <!-- Kubah Helm -->
                            <path d="M 104,158 C 104,136 120,132 136,134 C 147,136 153,146 153,158 Z" fill="#facc15"/>
                            <!-- Garis Lekuk Mahkota Atas Helm -->
                            <path d="M 116,134 C 122,130 134,130 142,134" stroke="#fef08a" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                            <!-- Lis / Pinggiran Helm -->
                            <path d="M 100,158 L 159,158 C 163,158 164,162 160,163 L 102,163 C 98,163 97,158 100,158 Z" fill="#eab308"/>
                        </g>

                        <!-- Lengan Kiri (Menunjuk Tepat ke Kotak & Checklist 👉) -->
                        <g class="pointing-arm" :class="focused ? 'arm-active' : ''">
                            <!-- Bahu & Lengan Kemeja Putih Lurus Terentang -->
                            <path d="M 136,207 L 194,201 L 195,216 L 137,222 Z" fill="#ffffff"/>
                            <!-- Manset Lengan Kemeja -->
                            <rect x="193" y="200" width="4" height="17" rx="1" fill="#e2e8f0"/>

                            <!-- Tangan Peach dengan Jari Telunjuk Menunjuk -->
                            <g>
                                <!-- Telapak Tangan -->
                                <circle cx="202" cy="208" r="6.5" fill="#fed7aa"/>
                                <!-- Jari Telunjuk Menunjuk Lurus ke Arah Kotak -->
                                <rect x="202" y="203" width="16" height="5" rx="2.5" fill="#fed7aa"/>
                                <!-- Jari Lain Terlipat Rapi -->
                                <rect x="200" y="208" width="8" height="3" rx="1.5" fill="#fcd34d" opacity="0.6"/>
                                <rect x="200" y="211" width="7" height="3" rx="1.5" fill="#fcd34d" opacity="0.6"/>
                            </g>
                        </g>
                    </g>

                    <!-- ===== 9. Garis Lantai / Base Ground Line ===== -->
                    <line x1="45" y1="425" x2="635" y2="425" stroke="#334e68" stroke-width="4.5" stroke-linecap="round"/>
                </svg>

            </div>

            <!-- ===================== PANEL KANAN : FORM LOGIN ===================== -->
            <div class="bg-white px-8 py-10 sm:px-12 sm:py-12 flex flex-col justify-center">
                <div class="w-full max-w-[340px] mx-auto">

                    <!-- Header & Subtitle -->
                    <div class="text-center mb-6">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200/80 text-emerald-700 text-[10.5px] font-bold tracking-wider uppercase mb-2.5 shadow-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            General Affair Portal
                        </div>
                        <h1 class="text-2xl sm:text-[28px] font-extrabold text-slate-900 tracking-tight leading-tight">
                            Selamat Datang <span class="inline-block hover:rotate-12 transition-transform duration-300">👋</span>
                        </h1>
                        <p class="text-xs sm:text-[13px] text-slate-500 mt-1.5 font-normal">
                            Masuk ke portal aset <span class="font-bold text-emerald-600">AMANA</span> Al Azhar Peduli
                        </p>
                    </div>

                    @if($errors->any())
                        <div class="mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center space-x-2.5">
                            <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="font-medium leading-relaxed">{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-4" @submit="doLogin($event)">
                        @csrf

                        <!-- Field Email -->
                        <div class="space-y-1.5">
                            <label for="email" class="block text-xs font-semibold text-slate-700 pl-0.5">
                                Email atau Username
                            </label>
                            <div class="relative">
                                <input id="email" type="text" name="email" value="{{ old('email') }}" required autofocus
                                       x-model="emailVal"
                                       autocomplete="username"
                                       @focus="onFocus('email')"
                                       @blur="onBlur()"
                                       @input="onInput()"
                                       class="w-full px-4 py-3 text-xs sm:text-sm text-slate-800 bg-slate-50/70 border border-slate-200 rounded-2xl focus:outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 transition-all placeholder:text-slate-400"
                                       placeholder="cth: admin@alazhar.or.id">
                            </div>
                        </div>

                        <!-- Field Password -->
                        <div class="space-y-1.5" x-data="{ show: false }">
                            <label for="password" class="block text-xs font-semibold text-slate-700 pl-0.5">
                                Kata Sandi
                            </label>
                            <div class="relative">
                                <input id="password" :type="show ? 'text' : 'password'" name="password" required
                                       x-model="passwordVal"
                                       autocomplete="current-password"
                                       @focus="onFocus('password')"
                                       @blur="onBlur()"
                                       @input="onInput()"
                                       @keydown="checkCaps($event)"
                                       class="w-full pl-4 pr-11 py-3 text-xs sm:text-sm text-slate-800 bg-slate-50/70 border border-slate-200 rounded-2xl focus:outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 transition-all placeholder:text-slate-400"
                                       placeholder="••••••••">
                                <button type="button" @click="show = !show"
                                        class="absolute right-3.5 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 focus:outline-none transition-colors"
                                        title="Tampilkan sandi">
                                    <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="show" x-cloak class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                    </svg>
                                </button>
                            </div>
                            <p x-show="caps" x-cloak x-transition class="text-[11px] text-amber-600 pl-1 font-medium">
                                ⚠️ Caps Lock aktif
                            </p>
                        </div>

                        <!-- Remember & Forgot Password -->
                        <div class="flex items-center justify-between pt-0.5 text-xs">
                            <label class="flex items-center gap-2 text-slate-600 cursor-pointer select-none">
                                <input type="checkbox" name="remember"
                                       class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500/20 focus:ring-offset-0">
                                <span>Ingat saya</span>
                            </label>
                            <a href="#" onclick="alert('Silakan hubungi Administrator atau Tim IT AMANA Al Azhar Peduli untuk pemulihan akun atau reset kata sandi.'); return false;"
                               class="text-emerald-600 hover:text-emerald-700 font-semibold hover:underline transition-colors">
                                Lupa sandi?
                            </a>
                        </div>

                        <!-- Tombol Submit Utama -->
                        <div class="pt-1">
                            <button type="submit" id="login-btn" :disabled="submitted"
                                    class="w-full py-3.5 px-6 rounded-2xl bg-gradient-to-r from-emerald-600 via-emerald-700 to-teal-700 hover:from-emerald-500 hover:via-emerald-600 hover:to-teal-600 active:scale-[0.99] text-white font-bold text-sm tracking-wide shadow-lg shadow-emerald-600/25 transition-all duration-200 cursor-pointer text-center disabled:opacity-75 disabled:cursor-wait flex items-center justify-center gap-2">
                                <span x-show="!submitted">Masuk ke Dashboard</span>
                                <span x-show="submitted" x-cloak class="inline-flex items-center gap-2">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Memvalidasi Akun&hellip;
                                </span>
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </main>

    <!-- Alpine.js Interactivity Controller -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('inventoryScene', () => ({
                emailVal: '',
                passwordVal: '',
                focused: false,
                typing: false,
                typeTimer: null,
                submitted: false,
                caps: false,

                get verified() {
                    return this.emailVal.trim().length > 3 && this.passwordVal.trim().length > 3;
                },


                onFocus(field) {
                    this.focused = true;
                },

                onBlur() {
                    this.focused = false;
                },

                onInput() {
                    this.typing = true;
                    clearTimeout(this.typeTimer);
                    this.typeTimer = setTimeout(() => {
                        this.typing = false;
                    }, 800);
                },

                checkCaps(e) {
                    this.caps = typeof e.getModifierState === 'function' && e.getModifierState('CapsLock');
                },

                doLogin(e) {
                    e.preventDefault();
                    if (this.submitted) return;

                    this.submitted = true;
                    this.focused = true;

                    const form = e.target;
                    setTimeout(() => form.submit(), 1100);
                },
            }));
        });
    </script>
</body>
</html>
