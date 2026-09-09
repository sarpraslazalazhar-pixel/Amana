<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'AMANA') - Aset Manajemen Al Azhar</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
    @stack('styles')
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased overflow-hidden" 
      x-data="{ 
          sidebarOpen: false, 
          sidebarCollapsed: JSON.parse(localStorage.getItem('amana_sidebar_collapsed') || 'false'),
          toggleSidebar() {
              this.sidebarCollapsed = !this.sidebarCollapsed;
              localStorage.setItem('amana_sidebar_collapsed', JSON.stringify(this.sidebarCollapsed));
          }
      }">

    <div class="h-screen flex overflow-hidden">
        <!-- Sidebar Navigation Partial -->
        @include('layouts.sidebar')

        <!-- Main Content Area (Independent Scroll) -->
        <div id="main-scroll-container" class="flex-1 flex flex-col min-w-0 bg-slate-50 h-full overflow-y-auto">
            <!-- Header Bar Partial -->
            @include('layouts.header')

            <!-- Page Content -->
            <main id="page-main" class="p-6 sm:p-8 flex-1">
                @if(session('success'))
                    <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center justify-between shadow-sm">
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center justify-between shadow-sm">
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
