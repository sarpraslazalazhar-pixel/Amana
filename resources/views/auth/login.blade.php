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

               <img src="{{ asset('amana.webp') }}" alt="AMANA" class="w-full h-full object-cover">

            </div>

            <!-- ===================== PANEL KANAN : FORM LOGIN ===================== -->
            <div class="bg-white px-8 py-10 sm:px-12 sm:py-12 flex flex-col justify-center">
                <div class="w-full max-w-[340px] mx-auto">

                    <!-- Brand / Logo -->
                    <div class="text-center mb-6">
                        <a href="{{ url('/') }}" class="inline-block transition-transform duration-200 hover:scale-105">
                            <img src="{{ asset('logo-amana.png') }}" 
                                 alt="AMANA" 
                                 class="h-26 sm:h-26 w-auto mx-auto object-contain">
                        </a>
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
