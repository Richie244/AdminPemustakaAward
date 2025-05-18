<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/preline@latest/dist/preline.js"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Custom scrollbar untuk sidebar jika kontennya panjang (opsional) */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: #2d3748; /* Warna track sedikit lebih terang dari sidebar bg */
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: #4a5568; /* Warna thumb */
            border-radius: 3px;
        }
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #718096;
        }
        .sidebar {
            height: 100vh; /* Pastikan sidebar mengisi tinggi layar */
            overflow-y: auto; /* Aktifkan scroll jika konten melebihi tinggi */
        }
        /* Efek transisi halus untuk item menu */
        .nav-item {
            transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out, padding-left 0.2s ease-in-out;
        }
        .nav-item-active { /* Kelas ini di-override oleh kelas spesifik di link */
            /* background-color: #4A5568; */
            /* color: #E2E8F0; */
        }
        .nav-item:hover:not(.bg-blue-600) { /* Efek hover hanya untuk item non-aktif (yang tidak memiliki bg-blue-600) */
            background-color: #2D3748; 
        }
    </style>
    @stack('styles') {{-- Untuk style spesifik per halaman --}}
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        {{-- Sidebar dengan warna yang lebih gelap dan modern --}}
        <aside class="fixed top-0 left-0 w-64 h-full bg-slate-800 text-slate-100 p-5 sidebar shadow-lg z-20"> {{-- Naikkan z-index sidebar --}}
            {{-- Logo atau Nama Aplikasi --}}
            <div class="mb-8 text-center">
                <a href="{{ url('/') }}" class="text-2xl font-bold text-white hover:text-blue-400 transition-colors">
                    Pemustaka Award
                </a>
            </div>  
            
            <nav>
                <ul>
                    {{-- Item Menu Periode --}}
                    <li class="mb-3">
                        <a href="{{ route('periode.index') }}" {{-- Pastikan route 'periode.index' ada --}}
                           class="nav-item flex items-center p-3 rounded-lg group
                                  {{ request()->is('periode*') || request()->is('detailperiode/*') || request()->is('settingperiode') ? 'bg-blue-600 text-white font-semibold shadow-md' : 'hover:bg-slate-700 hover:text-white' }}">
                            <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white {{ request()->is('periode*') || request()->is('detailperiode/*') || request()->is('settingperiode') ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                            Periode
                        </a>
                    </li>
                    {{-- Item Menu Kegiatan --}}
                    <li class="mb-3">
                        <a href="{{ route('kegiatan.index') }}" 
                           class="nav-item flex items-center p-3 rounded-lg group
                                  {{ request()->is('kegiatan*') ? 'bg-blue-600 text-white font-semibold shadow-md' : 'hover:bg-slate-700 hover:text-white' }}">
                            <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white {{ request()->is('kegiatan*') ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Kegiatan
                        </a>
                    </li>
                    {{-- Item Menu Validasi Aksara Dinamika --}}
                    <li class="mb-3">
                        <a href="{{ route('validasi.aksara.index') }}" 
                           class="nav-item flex items-center p-3 rounded-lg group
                                  {{ request()->is('aksara*') || request()->is('validasi-aksara*') ? 'bg-blue-600 text-white font-semibold shadow-md' : 'hover:bg-slate-700 hover:text-white' }}">
                            <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white {{ request()->is('aksara*') || request()->is('validasi-aksara*') ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Validasi Aksara
                        </a>
                    </li>
                    {{-- Item Menu Report --}}
                    <li class="mb-3">
                        <a href="{{ url('/report') }}" {{-- Menggunakan url() jika route belum ada --}}
                           class="nav-item flex items-center p-3 rounded-lg group
                                  {{ request()->is('report') ? 'bg-blue-600 text-white font-semibold shadow-md' : 'hover:bg-slate-700 hover:text-white' }}">
                            <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white {{ request()->is('report') ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Report
                        </a>
                    </li>
                </ul>
            </nav>

            {{-- Footer Sidebar (Opsional) --}}
            <div class="mt-auto pt-5 border-t border-slate-700">
                <p class="text-xs text-slate-500 text-center">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}.
                </p>
            </div>
        </aside>

        {{-- ml-64 untuk memberi ruang bagi sidebar fixed --}}
        <main class="ml-64 flex-1 h-full flex flex-col"> {{-- Tambah flex flex-col --}}
            
            {{-- AWAL: App Bar / Header Utama Konten --}}
            <header class="bg-white shadow-md sticky top-0 z-10"> {{-- Dibuat sticky --}}
                <div class="max-w-full mx-auto px-6 py-3">
                    <div class="flex items-center justify-between">
                        {{-- Judul Halaman Dinamis (Opsional, bisa diambil dari @yield('page_title') jika ada) --}}
                        <h1 class="text-xl font-semibold text-gray-700">
                            @yield('page_title', '') {{-- Default ke Dashboard jika tidak ada judul spesifik --}}
                        </h1>

                        {{-- Informasi Pengguna dan Logout --}}
                        @if(session()->has('nama_pengguna'))
                        <div class="flex items-center space-x-3">
                            <span class="text-sm text-gray-600">
                                Halo, <span class="font-medium">{{ session('nama_pengguna') }}</span>
                                @if(session()->has('status_pengguna'))
                                    <span class="text-xs text-gray-500">({{ session('status_pengguna') }})</span>
                                @endif
                            </span>
                            <form method="POST" action="{{ route('logout') }}"> {{-- Pastikan route 'logout' ada --}}
                                @csrf
                                <button type="submit" class="text-sm text-blue-600 hover:text-blue-800 hover:underline focus:outline-none">
                                    Logout
                                </button>
                            </form>
                        </div>
                        @else
                         {{-- Opsional: Tampilkan link login jika belum login (seharusnya tidak terjadi jika halaman ini diakses setelah login) --}}
                         <a href="{{ route('login') }}" class="text-sm text-blue-600 hover:text-blue-800 hover:underline">Login</a>
                        @endif
                    </div>
                </div>
            </header>
            {{-- AKHIR: App Bar / Header Utama Konten --}}

            {{-- Area Konten Utama --}}
            <div class="flex-1 overflow-y-auto bg-gray-100">
                <div class="py-8 px-6"> 
                    @yield('content')
                </div>
            </div>
        </main>
    </div>

    @stack('scripts') {{-- Untuk script spesifik per halaman --}}
    <script>
        // Script untuk Preline UI jika diperlukan di semua halaman
        // HSStaticMethods.autoInit(); // Jika Anda menggunakan komponen Preline yang butuh init JS
    </script>
</body>
</html>
