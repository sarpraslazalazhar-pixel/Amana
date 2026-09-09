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

        /* ===== Animasi Scene GA ===== */
        .cloud-float { animation: cloudDrift 8s ease-in-out infinite alternate; }
        @keyframes cloudDrift { 0% { transform: translateX(-4px); } 100% { transform: translateX(8px); } }

        .sun-glow { animation: sunPulse 4s ease-in-out infinite alternate; }
        @keyframes sunPulse { 0% { opacity: .35; transform: scale(0.96); } 100% { opacity: .65; transform: scale(1.05); } }

        .wlamp { transition: opacity .6s ease; }

        /* Lampu PJU */
        .glow-lamp { opacity: 0; transition: opacity .6s ease; }
        .glow-lamp.on { opacity: 1; }
        .glow-lamp.flick { animation: lampflick .65s steps(3) 1; }
        @keyframes lampflick {
            0%,100% { opacity: 0 }
            20% { opacity:.9 } 40% { opacity:.1 }
            60% { opacity:.8 } 80% { opacity:.15 }
        }

        /* Lampu depan mobil */
        .glow-beam { opacity: 0; transition: opacity .5s ease; }
        .glow-beam.on { opacity: .7; }
        .glow-beam.on.bp { animation: beampulse .45s ease 1; }
        @keyframes beampulse { 0%,100% { opacity:.7 } 45% { opacity:1 } }

        /* Palang parkir */
        #gate-arm { transform-origin: 433px 396px; transform-box: view-box;
                    transition: transform .9s cubic-bezier(.34,1.56,.64,1); }
        .gate.open #gate-arm { transform: rotate(-76deg); }
        .gate-red    { transition: opacity .4s ease; }
        .gate-green  { transition: opacity .4s ease; }
        .gate.open .gate-red   { opacity: 0; }
        .gate.open .gate-green { opacity: 1; }

        /* Mobil GA */
        #car.shk { animation: carshake .55s ease 2; }
        @keyframes carshake {
            0%,100% { transform: translateX(0) }
            20% { transform: translateX(-6px) } 40% { transform: translateX(5px) }
            60% { transform: translateX(-4px) } 80% { transform: translateX(3px) }
        }
        #car.go { animation: driveoff 1.25s cubic-bezier(.45,0,.9,.4) forwards; }
        @keyframes driveoff {
            0%   { transform: translateX(0) }
            12%  { transform: translateX(4px) }
            100% { transform: translateX(380px) }
        }
        #car.go .wheel { animation: spin .32s linear infinite;
                         transform-box: fill-box; transform-origin: center; }
        @keyframes spin { to { transform: rotate(360deg) } }

        .puff { opacity: 0; }
        #car.go .puff { animation: puff .9s ease-out forwards; }
        #car.go .puff.p2 { animation-delay: .15s; }
        #car.go .puff.p3 { animation-delay: .3s; }
        @keyframes puff {
            0%   { opacity:.8; transform: translate(0,0) scale(.6) }
            100% { opacity:0;  transform: translate(-34px,-10px) scale(1.7) }
        }

        /* Lampu beacon patroli */
        .beacon-dome { opacity:.3; transition: opacity .3s ease; }
        #car.go .beacon-dome { animation: amberblink .55s ease-in-out infinite; }
        @keyframes amberblink { 0%,100% { opacity:.3 } 50% { opacity:1 } }

        /* Hazard saat error */
        #car.haz .hzl { animation: hazblink .45s steps(2) 4; }
        @keyframes hazblink { 0%,100% { opacity:0 } 50% { opacity:1 } }

        /* Wiper kaca */
        #wiper { transform-origin: 372px 412px; transform-box: view-box;
                 transform: rotate(0deg); }
        #wiper.wp { animation: wipersweep .9s ease 1; }
        @keyframes wipersweep { 0%,100% { transform: rotate(0) } 45% { transform: rotate(-42deg) } }

        #lyr-sky, #lyr-mid { will-change: transform; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-daylight flex items-center justify-center p-4 sm:p-6 md:p-10 relative overflow-x-hidden select-none"
      x-data="gaScene()" @mousemove="mx = $event.clientX / window.innerWidth; my = $event.clientY / window.innerHeight">

    <!-- Kontur latar luar (siluet garis pepohonan modern terang) -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden opacity-30">
        <svg class="w-full h-full" viewBox="0 0 1440 900" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
            <path d="M-80 0 C130 160 170 380 140 900" stroke="#94a3b8" stroke-width="2" stroke-linecap="round"/>
            <path d="M-20 0 C180 190 220 420 190 900" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round"/>
            <path d="M1360 0 C1290 270 1280 570 1320 900" stroke="#94a3b8" stroke-width="2" stroke-linecap="round"/>
            <path d="M1410 0 C1350 250 1340 550 1380 900" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
    </div>

    <main class="relative z-10 w-full max-w-[960px] bg-white rounded-[32px] shadow-[0_20px_60px_-15px_rgba(15,23,42,0.12)] overflow-hidden border border-slate-200/80">
        <div class="grid grid-cols-1 md:grid-cols-2 min-h-[580px]">

            <!-- ===================== PANEL KIRI : SCENE TERANG GA ===================== -->
            <div class="relative bg-gradient-to-b from-sky-100 via-sky-50 to-emerald-50 min-h-[320px] md:min-h-full overflow-hidden border-b md:border-b-0 md:border-r border-slate-200/80">
                <!-- Brand pill (top-left) -->
                <div class="absolute top-4 left-4 z-20 flex items-center space-x-2 bg-white/85 backdrop-blur-md px-3.5 py-1.5 rounded-full text-slate-800 text-xs border border-slate-200/80 shadow-sm">
                    <img src="{{ asset('logo-icon.png') }}" class="w-10 h-10 object-contain" alt="AMANA Icon">
                    <span class="font-bold tracking-wider text-slate-800">AMANA</span>
                </div>

                <svg class="absolute inset-0 w-full h-full" viewBox="0 0 500 620" fill="none"
                     xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMax slice" aria-hidden="true">
                    <defs>
                        <!-- Sky Gradient (Terang / Fresh Morning) -->
                        <linearGradient id="skyG" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#bae6fd"/>
                            <stop offset="55%" stop-color="#e0f2fe"/>
                            <stop offset="100%" stop-color="#ecfdf5"/>
                        </linearGradient>
                        <!-- Sorot Lampu Depan Mobil -->
                        <linearGradient id="beamG" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0%" stop-color="#fef08a" stop-opacity=".95"/>
                            <stop offset="100%" stop-color="#fef08a" stop-opacity="0"/>
                        </linearGradient>
                        <!-- Sorot Lampu PJU -->
                        <linearGradient id="coneG" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#fef08a" stop-opacity=".75"/>
                            <stop offset="100%" stop-color="#fef08a" stop-opacity="0"/>
                        </linearGradient>
                        <clipPath id="armClip"><rect x="433" y="392" width="62" height="9" rx="4.5"/></clipPath>
                    </defs>

                    <rect width="500" height="620" fill="url(#skyG)"/>

                    <!-- ===== Layer langit (Matahari & Awan Pagi) ===== -->
                    <g id="lyr-sky" :style="`transform:translate(${(mx-.5)*-10}px, ${(my-.5)*-6}px)`">
                        <!-- Matahari Terang -->
                        <circle cx="420" cy="80" r="48" fill="#fef08a" class="sun-glow"/>
                        <circle cx="420" cy="80" r="26" fill="#fde047" opacity=".95"/>
                        <circle cx="414" cy="74" r="7" fill="#ffffff" opacity=".4"/>

                        <!-- Awan Pagi Halus -->
                        <g fill="#ffffff" opacity=".85" class="cloud-float">
                            <!-- Cloud 1 -->
                            <path d="M 40 100 Q 55 85 75 90 Q 95 80 115 95 Q 130 95 135 108 Q 138 120 120 120 L 45 120 Q 30 115 40 100 Z"/>
                            <!-- Cloud 2 -->
                            <path d="M 220 60 Q 235 48 250 52 Q 268 45 285 58 Q 298 58 302 68 Q 305 78 290 78 L 225 78 Q 212 75 220 60 Z" opacity=".7"/>
                            <!-- Cloud 3 -->
                            <path d="M 320 130 Q 332 118 348 122 Q 362 115 378 126 Q 388 126 392 135 L 325 135 Q 312 132 320 130 Z" opacity=".6"/>
                        </g>
                    </g>

                    <!-- ===== Layer tengah: Gedung GA + PJU ===== -->
                    <g id="lyr-mid" :style="`transform:translate(${(mx-.5)*-18}px, ${(my-.5)*-10}px)`">

                        <!-- Gedung kantor General Affair (Modern Clean) -->
                        <rect x="8" y="340" width="90" height="166" fill="#e2e8f0" stroke="#cbd5e1" stroke-width="1.5"/>
                        <rect x="86" y="300" width="150" height="206" fill="#ffffff" stroke="#cbd5e1" stroke-width="1.5"/>
                        <rect x="82" y="293" width="158" height="9" rx="3" fill="#0284c7"/>
                        <line x1="160" y1="293" x2="160" y2="256" stroke="#64748b" stroke-width="3"/>
                        <circle cx="160" cy="253" r="3.5" fill="#ef4444">
                            <animate attributeName="opacity" values="1;.2;1" dur="1.5s" repeatCount="indefinite"/>
                        </circle>

                        <!-- Papan nama GENERAL AFFAIR (Hijau Emerald & Putih) -->
                        <rect x="94" y="310" width="102" height="24" rx="4" fill="#ffffff" stroke="#059669" stroke-width="1.5"/>
                        <text x="145" y="326" text-anchor="middle" font-family="'Plus Jakarta Sans',sans-serif"
                              font-size="9.5" font-weight="800" fill="#047857" letter-spacing="1">GENERAL AFFAIR</text>

                        <!-- Jendela statis (Kaca Biru Lembut) -->
                        <g fill="#e0f2fe" stroke="#93c5fd" stroke-width="1">
                            <rect x="146" y="352" width="32" height="28" rx="3"/>
                            <rect x="192" y="392" width="32" height="28" rx="3"/>
                            <rect x="100" y="392" width="32" height="28" rx="3"/>
                            <rect x="146" y="432" width="32" height="28" rx="3"/>
                            <rect x="24" y="412" width="26" height="22" rx="3"/>
                        </g>

                        <!-- Jendela interaktif: Menyala Terang Bertahap saat User Berinteraksi -->
                        <g>
                            <rect x="100" y="352" width="32" height="28" rx="3" fill="#e0f2fe"/>
                            <rect x="100" y="352" width="32" height="28" rx="3" fill="#fef08a" class="wlamp" :style="{ opacity: lit >= 1 ? .5 : 0 }"/>
                            <rect x="100" y="352" width="32" height="28" rx="3" fill="#fde047" class="wlamp" :style="{ opacity: lit >= 1 ? 1 : 0 }"/>

                            <rect x="192" y="352" width="32" height="28" rx="3" fill="#e0f2fe"/>
                            <rect x="192" y="352" width="32" height="28" rx="3" fill="#fef08a" class="wlamp" :style="{ opacity: lit >= 2 ? .5 : 0 }"/>
                            <rect x="192" y="352" width="32" height="28" rx="3" fill="#fde047" class="wlamp" :style="{ opacity: lit >= 2 ? 1 : 0 }"/>

                            <rect x="146" y="392" width="32" height="28" rx="3" fill="#e0f2fe"/>
                            <rect x="146" y="392" width="32" height="28" rx="3" fill="#fef08a" class="wlamp" :style="{ opacity: lit >= 3 ? .5 : 0 }"/>
                            <rect x="146" y="392" width="32" height="28" rx="3" fill="#fde047" class="wlamp" :style="{ opacity: lit >= 3 ? 1 : 0 }"/>

                            <rect x="100" y="432" width="32" height="28" rx="3" fill="#e0f2fe"/>
                            <rect x="100" y="432" width="32" height="28" rx="3" fill="#fef08a" class="wlamp" :style="{ opacity: lit >= 4 ? .5 : 0 }"/>
                            <rect x="100" y="432" width="32" height="28" rx="3" fill="#fde047" class="wlamp" :style="{ opacity: lit >= 4 ? 1 : 0 }"/>

                            <rect x="192" y="432" width="32" height="28" rx="3" fill="#e0f2fe"/>
                            <rect x="192" y="432" width="32" height="28" rx="3" fill="#fef08a" class="wlamp" :style="{ opacity: lit >= 5 ? .5 : 0 }"/>
                            <rect x="192" y="432" width="32" height="28" rx="3" fill="#fde047" class="wlamp" :style="{ opacity: lit >= 5 ? 1 : 0 }"/>

                            <rect x="24" y="372" width="26" height="22" rx="3" fill="#e0f2fe"/>
                            <rect x="24" y="372" width="26" height="22" rx="3" fill="#fef08a" class="wlamp" :style="{ opacity: lit >= 6 ? .5 : 0 }"/>
                            <rect x="24" y="372" width="26" height="22" rx="3" fill="#fde047" class="wlamp" :style="{ opacity: lit >= 6 ? 1 : 0 }"/>
                        </g>

                        <!-- Pintu Masuk Gedung -->
                        <rect x="138" y="462" width="34" height="44" rx="2" fill="#f1f5f9" stroke="#94a3b8" stroke-width="1.5"/>
                        <rect x="141" y="465" width="13" height="38" fill="#e0f2fe" opacity=".9"/>
                        <rect x="156" y="465" width="13" height="38" fill="#e0f2fe" opacity=".9"/>
                        <circle cx="153" cy="484" r="1.8" fill="#059669"/>

                        <!-- Lampu PJU Taman/Kampus -->
                        <rect x="252" y="497" width="12" height="9" rx="2" fill="#64748b"/>
                        <rect x="256" y="388" width="4" height="112" fill="#64748b"/>
                        <path d="M258,392 C258,377 274,373 286,375" stroke="#64748b" stroke-width="4" stroke-linecap="round" fill="none"/>
                        <rect x="282" y="372" width="19" height="8" rx="3" fill="#475569"/>
                        <ellipse cx="291" cy="381" rx="6" ry="2.6" fill="#fef08a" opacity=".9"/>

                        <!-- Cahaya PJU -->
                        <g class="glow-lamp" :class="{ on: lamp, flick: flicking }">
                            <polygon points="283,383 299,383 332,505 250,505" fill="url(#coneG)"/>
                            <ellipse cx="291" cy="506" rx="46" ry="7" fill="#fef08a" opacity=".4"/>
                            <circle cx="291" cy="379" r="14" fill="#fef08a" opacity=".5"/>
                        </g>
                    </g>

                    <!-- Palang Parkir / Barrier Gate -->
                    <g class="gate" :class="{ open: gate }">
                        <ellipse cx="433" cy="506" rx="14" ry="4" fill="#0f172a" opacity=".15"/>
                        <rect x="429" y="396" width="8" height="108" fill="#475569"/>
                        <rect x="429" y="396" width="2.5" height="108" fill="#64748b"/>
                        <rect x="424" y="500" width="18" height="7" rx="2" fill="#334155"/>
                        <circle cx="433" cy="390" r="9" fill="#1e293b"/>
                        <circle class="gate-red" cx="433" cy="390" r="5" fill="#ef4444"/>
                        <circle class="gate-green" cx="433" cy="390" r="5" fill="#22c55e" style="opacity:0"/>
                        <circle cx="433" cy="390" r="11" fill="#22c55e" class="wlamp gate-green" :style="{ opacity: gate ? .35 : 0 }"/>
                        <g id="gate-arm">
                            <rect x="433" y="392" width="62" height="9" rx="4.5" fill="#ffffff" stroke="#94a3b8" stroke-width=".8"/>
                            <g clip-path="url(#armClip)" fill="#dc2626">
                                <rect x="441" y="392" width="10" height="9"/>
                                <rect x="461" y="392" width="10" height="9"/>
                                <rect x="481" y="392" width="10" height="9"/>
                            </g>
                        </g>
                    </g>

                    <!-- ===== Layer Depan: Jalan Aspal + Mobil Dinas GA ===== -->
                    <rect x="0" y="505" width="500" height="115" fill="#334155"/>
                    <rect x="0" y="505" width="500" height="3" fill="#475569"/>
                    <!-- Garis Marka Jalan Putih Bersih -->
                    <g fill="#f8fafc" opacity=".9">
                        <rect x="12"  y="566" width="34" height="5" rx="2"/>
                        <rect x="82"  y="566" width="34" height="5" rx="2"/>
                        <rect x="152" y="566" width="34" height="5" rx="2"/>
                        <rect x="222" y="566" width="34" height="5" rx="2"/>
                        <rect x="292" y="566" width="34" height="5" rx="2"/>
                        <rect x="362" y="566" width="34" height="5" rx="2"/>
                        <rect x="432" y="566" width="34" height="5" rx="2"/>
                    </g>

                    <g id="car" :class="{ shk: shake, go: driving, haz: hazard }" x-ref="car">
                        <!-- Bayangan Bawah Mobil -->
                        <ellipse cx="340" cy="506" rx="74" ry="8" fill="#0f172a" opacity=".25"/>

                        <!-- Berkas Lampu Depan Mobil -->
                        <g class="glow-beam" :class="{ on: beams, bp: beamPulse }">
                            <polygon points="410,446 500,422 500,476 410,458" fill="url(#beamG)"/>
                            <ellipse cx="468" cy="506" rx="42" ry="6" fill="#fef08a" opacity=".35"/>
                            <circle cx="405" cy="449" r="7" fill="#ffffff" opacity=".7"/>
                        </g>

                        <!-- Asap Knalpot -->
                        <g fill="#cbd5e1">
                            <circle class="puff"    cx="264" cy="486" r="4"/>
                            <circle class="puff p2" cx="254" cy="488" r="5.5"/>
                            <circle class="puff p3" cx="242" cy="487" r="7"/>
                        </g>

                        <!-- Bodi Mobil Dinas GA (Hijau Emerald Al Azhar) -->
                        <path d="M 272,492 L 272,452 Q 272,442 283,441 L 305,439 L 320,412
                                 Q 323,405 332,405 L 366,405 Q 375,405 378,412 L 391,438
                                 L 401,441 Q 410,443 410,453 L 410,492 Z" fill="#059669"/>
                        <rect x="272" y="468" width="138" height="24" rx="6" fill="#047857"/>
                        <path d="M 322,408 L 372,408" stroke="#34d399" stroke-width="2" stroke-linecap="round" opacity=".8"/>

                        <!-- Kaca Mobil -->
                        <path d="M322,411 L337,411 L346,436 L309,436 Z" fill="#e0f2fe" opacity=".95"/>
                        <path d="M347,411 L364,411 L377,436 L343,436 Z" fill="#e0f2fe" opacity=".95"/>

                        <!-- Wiper Mobil -->
                        <g id="wiper" :class="{ wp: wiping }">
                            <line x1="372" y1="412" x2="352" y2="436" stroke="#0f172a" stroke-width="2.5" stroke-linecap="round"/>
                        </g>

                        <!-- Garis Pintu + Handle -->
                        <path d="M 347,440 L 346,470" stroke="#065f46" stroke-width="1.5"/>
                        <rect x="350" y="446" width="9" height="2.5" rx="1" fill="#065f46"/>

                        <!-- Beacon Patroli Amber di Atap -->
                        <rect x="341" y="399" width="14" height="4" rx="1.5" fill="#334155"/>
                        <path class="beacon-dome" d="M 344,399 Q 348,390 352,399 Z" fill="#f59e0b"/>

                        <!-- Lampu Belakang + Hazard -->
                        <rect x="271" y="445" width="5" height="10" rx="2" fill="#b91c1c"/>
                        <rect class="hzl" x="271" y="445" width="5" height="10" rx="2" fill="#f87171" style="opacity:0"/>
                        <rect class="hzl" x="403" y="444" width="7" height="11" rx="2.5" fill="#f87171" style="opacity:0"/>

                        <!-- Lampu Depan -->
                        <rect x="403" y="444" width="7" height="11" rx="2.5" fill="#ffffff" stroke="#93c5fd" stroke-width=".5"/>
                        <rect x="403" y="444" width="7" height="11" rx="2.5" fill="#fef08a" class="wlamp" :style="{ opacity: beams ? 1 : 0 }"/>

                        <!-- Knalpot -->
                        <rect x="268" y="486" width="7" height="3.5" rx="1.5" fill="#64748b"/>

                        <!-- Roda Mobil -->
                        <g class="wheel">
                            <circle cx="300" cy="487" r="16" fill="#1e293b" stroke="#334155" stroke-width="3"/>
                            <circle cx="300" cy="487" r="7.5" fill="#94a3b8"/>
                            <path d="M300,480 v14 M293,487 h14" stroke="#f1f5f9" stroke-width="2" stroke-linecap="round"/>
                        </g>
                        <g class="wheel">
                            <circle cx="384" cy="487" r="16" fill="#1e293b" stroke="#334155" stroke-width="3"/>
                            <circle cx="384" cy="487" r="7.5" fill="#94a3b8"/>
                            <path d="M384,480 v14 M377,487 h14" stroke="#f1f5f9" stroke-width="2" stroke-linecap="round"/>
                        </g>
                    </g>
                </svg>

                <!-- Keterangan Interaksi (Bottom Pill) -->
                <div class="absolute bottom-3 left-0 right-0 z-20 flex justify-center px-4">
                    <div class="flex items-center gap-2.5 text-[10px] font-medium text-slate-600 bg-white/85 backdrop-blur-md px-3.5 py-1.5 rounded-full border border-slate-200/80 shadow-sm whitespace-nowrap">
                        <span>💡 Fokus = PJU</span>
                        <span class="text-slate-300">•</span>
                        <span>🚗 Ketik = Lampu Mobil</span>
                        <span class="text-slate-300">•</span>
                        <span>🅿️ Masuk = Buka Gerbang</span>
                    </div>
                </div>
            </div>

            <!-- ===================== PANEL KANAN : FORM LOGIN ===================== -->
            <div class="bg-white px-8 py-10 sm:px-12 sm:py-12 flex flex-col justify-center">
                <div class="w-full max-w-[340px] mx-auto">

                    <!-- Logo & Header -->
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

                        <div class="space-y-1.5">
                            <label for="email" class="block text-xs font-semibold text-slate-700 pl-0.5">
                                Email atau Username
                            </label>
                            <input id="email" type="text" name="email" value="{{ old('email') }}" required autofocus
                                   autocomplete="username"
                                   @focus="lampOn(); lightUp()"
                                   @input="beams = true; beamPulseNow(); lightUp()"
                                   class="w-full px-4 py-3 text-xs sm:text-sm text-slate-800 bg-slate-50/70 border border-slate-200 rounded-2xl focus:outline-none focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-500/10 transition-all placeholder:text-slate-400"
                                   placeholder="cth: admin@alazhar.or.id">
                        </div>

                        <div class="space-y-1.5" x-data="{ show: false }">
                            <label for="password" class="block text-xs font-semibold text-slate-700 pl-0.5">
                                Kata Sandi
                            </label>
                            <div class="relative">
                                <input id="password" :type="show ? 'text' : 'password'" name="password" required
                                       autocomplete="current-password"
                                       @focus="beams = true; lightUp()"
                                       @input="beamPulseNow(); lightUp()"
                                       @keydown="checkCaps($event)"
                                       class="w-full pl-4 pr-11 py-3 text-xs sm:text-sm text-slate-800 bg-slate-50/70 border border-slate-200 rounded-2xl focus:outline-none focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-500/10 transition-all placeholder:text-slate-400"
                                       placeholder="••••••••">
                                <button type="button" @click="show = !show; wipe()"
                                        class="absolute right-3.5 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 focus:outline-none transition-colors"
                                        title="Tampilkan sandi">
                                    <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="show" x-cloak class="w-4 h-4 text-cyan-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                    </svg>
                                </button>
                            </div>
                            <p x-show="caps" x-cloak x-transition class="text-[11px] text-amber-600 pl-1 font-medium">
                                ⚠️ Caps Lock aktif
                            </p>
                        </div>

                        <div class="flex items-center justify-between pt-0.5 text-xs">
                            <label class="flex items-center gap-2 text-slate-600 cursor-pointer select-none">
                                <input type="checkbox" name="remember"
                                       class="w-4 h-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500/20 focus:ring-offset-0">
                                <span>Ingat saya</span>
                            </label>
                            <a href="#" onclick="alert('Demo Passwords:\n• Super Admin: superadmin@alazhar.or.id / password123\n• Viewer: viewer@alazhar.or.id / password123'); return false;"
                               class="text-cyan-600 hover:text-cyan-700 font-semibold hover:underline transition-colors">
                                Lupa sandi?
                            </a>
                        </div>

                        <div class="pt-1">
                            <button type="submit" id="login-btn" :disabled="driving"
                                    class="w-full py-3.5 px-6 rounded-2xl bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-500 hover:to-emerald-500 active:scale-[0.99] text-white font-bold text-sm tracking-wide shadow-md shadow-emerald-600/20 transition-all duration-200 cursor-pointer text-center disabled:opacity-75 disabled:cursor-wait flex items-center justify-center gap-2">
                                <span x-show="!driving">Masuk ke Dashboard</span>
                                <span x-show="driving" x-cloak>🚧 Membuka gerbang&hellip;</span>
                            </button>
                        </div>
                    </form>

                    <!-- Divider -->
                    <div class="relative my-5 flex items-center justify-center">
                        <div class="border-t border-slate-200 flex-grow"></div>
                        <span class="px-3 text-[11px] text-slate-400 font-medium uppercase tracking-wider select-none">Akses Cepat Demo</span>
                        <div class="border-t border-slate-200 flex-grow"></div>
                    </div>

                    <!-- Login Cepat Demo -->
                    <div class="grid grid-cols-2 gap-2.5">
                        <a href="{{ route('login.admin') }}"
                           class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 hover:border-slate-300 transition-all text-xs font-semibold text-slate-700">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Super Admin
                        </a>
                        <a href="{{ route('login.viewer') }}"
                           class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 hover:border-slate-300 transition-all text-xs font-semibold text-slate-700">
                            <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                            Viewer Demo
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('gaScene', () => ({
                mx: .5, my: .5,
                lit: 0,
                lamp: false,
                beams: false,
                beamPulse: false,
                gate: false,
                driving: false,
                shake: false,
                hazard: false,
                wiping: false,
                flicking: false,
                caps: false,

                init() {
                    setTimeout(() => { if (this.lit < 1) this.lit = 1; }, 900);

                    // Lampu PJU sesekali berkedip saat belum aktif
                    setInterval(() => {
                        if (!this.lamp && !this.driving) {
                            this.flicking = true;
                            setTimeout(() => this.flicking = false, 700);
                        }
                    }, 7000);
                },

                lampOn() {
                    this.lamp = true;
                },

                lightUp() {
                    if (this.lit < 6) this.lit++;
                },

                beamPulseNow() {
                    this.beamPulse = false;
                    requestAnimationFrame(() => {
                        this.beamPulse = true;
                        setTimeout(() => this.beamPulse = false, 480);
                    });
                },

                wipe() {
                    this.wiping = false;
                    requestAnimationFrame(() => {
                        this.wiping = true;
                        setTimeout(() => this.wiping = false, 950);
                    });
                },

                checkCaps(e) {
                    this.caps = typeof e.getModifierState === 'function' && e.getModifierState('CapsLock');
                },

                doLogin(e) {
                    e.preventDefault();
                    if (this.driving) return;

                    this.driving = true;
                    this.gate = true;
                    this.lamp = true;
                    this.beams = true;
                    this.lit = 6;

                    const form = e.target;
                    setTimeout(() => form.submit(), 1400);
                },
            }));
        });
    </script>
</body>
</html>
