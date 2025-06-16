<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    {{-- TAMBAHKAN SCRIPT & STYLE DI BAWAH INI --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Rubik:wght@300..900&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Russo+One&display=swap">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    {{-- JQuery (diperlukan oleh Select2) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    {{-- Select2 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    {{-- Select2 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-track { background: #2d3748; }
        .sidebar::-webkit-scrollbar-thumb { background: #4a5568; border-radius: 3px; }
        .sidebar::-webkit-scrollbar-thumb:hover { background: #718096; }
        .sidebar { height: 100vh; overflow-y: auto; }
        .nav-item { transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out, padding-left 0.2s ease-in-out; }
        .nav-item:hover:not(.bg-blue-600) { background-color: #2D3748; }
        [x-cloak] { display: none !important; }
        
        /* Tambahan style agar Select2 cocok dengan Tailwind */
        .select2-container--default .select2-selection--single {
            border: 1px solid #d1d5db; /* border-gray-300 */
            border-radius: 0.375rem; /* rounded-md */
            height: 2.625rem; /* Sesuaikan tinggi agar sama dengan input lain */
            padding: 0.5rem 0.75rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 2.5rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.625rem;
        }
    </style>
    @stack('styles')
</head>
{{-- sisa file body tidak berubah --}}
<body class="bg-gray-100">
    <div class="flex h-screen">
        {{-- Sidebar --}}
        <aside class="fixed top-0 left-0 w-64 h-full bg-slate-800 text-slate-100 p-5 sidebar shadow-lg z-20">
            <div class="mb-8 text-center">
                <a href="{{ url('/') }}" class="text-2xl font-bold text-white hover:text-blue-400 transition-colors">
                    Pemustaka Award
                </a>
            </div>  
            <nav>
                <ul>
                    <li class="mb-3">
                        <a href="{{ route('dashboard') }}" 
                           class="nav-item flex items-center p-3 rounded-lg group
                                  {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white font-semibold shadow-md' : 'hover:bg-slate-700 hover:text-white' }}">
                            <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white {{ request()->routeIs('dashboard') ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                            Dashboard
                        </a>
                    </li>
                    <li class="mb-3">
                        <a href="{{ route('periode.index') }}" 
                           class="nav-item flex items-center p-3 rounded-lg group
                                  {{ request()->is('periode*') || request()->is('detailperiode/*') || request()->is('settingperiode') ? 'bg-blue-600 text-white font-semibold shadow-md' : 'hover:bg-slate-700 hover:text-white' }}">
                            <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white {{ request()->is('periode*') || request()->is('detailperiode/*') || request()->is('settingperiode') ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                            Periode
                        </a>
                    </li>
                    <li class="mb-3">
                        <a href="{{ route('kegiatan.index') }}" 
                           class="nav-item flex items-center p-3 rounded-lg group
                                  {{ request()->is('kegiatan*') ? 'bg-blue-600 text-white font-semibold shadow-md' : 'hover:bg-slate-700 hover:text-white' }}">
                            <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white {{ request()->is('kegiatan*') ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Kegiatan
                        </a>
                    </li>
                    <li class="mb-3">
                        <a href="{{ route('validasi.aksara.index') }}" 
                           class="nav-item flex items-center p-3 rounded-lg group
                                  {{ request()->is('aksara*') || request()->is('validasi-aksara*') ? 'bg-blue-600 text-white font-semibold shadow-md' : 'hover:bg-slate-700 hover:text-white' }}">
                            <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white {{ request()->is('aksara*') || request()->is('validasi-aksara*') ? 'text-white' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Validasi Aksara
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="mt-auto pt-5 border-t border-slate-700">
                <p class="text-xs text-slate-500 text-center">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}.
                </p>
            </div>
        </aside>

        <main class="ml-64 flex-1 h-full flex flex-col">
            <header class="bg-white shadow-md sticky top-0 z-10">
                <div class="max-w-full mx-auto px-6 py-3">
                    <div class="flex items-center justify-between">
                        <h1 class="text-xl font-semibold text-gray-700">
                            @yield('page_title', 'Dashboard')
                        </h1>
                        @if(session()->has('nama_pengguna'))
                        <div class="flex items-center space-x-3">
                            <span class="text-sm text-gray-600">
                                Halo, <span class="font-medium">{{ session('nama_pengguna') }}</span>
                                @if(session()->has('status_pengguna'))
                                    <span class="text-xs text-gray-500">({{ session('status_pengguna') }})</span>
                                @endif
                            </span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="text-sm text-blue-600 hover:text-blue-800 hover:underline focus:outline-none">
                                    Logout
                                </button>
                            </form>
                        </div>
                        @else
                           <a href="{{ route('login') }}" class="text-sm text-blue-600 hover:text-blue-800 hover:underline">Login</a>
                        @endif
                    </div>
                </div>
            </header>
            <div class="flex-1 overflow-y-auto bg-gray-100">
                <div class="py-8 px-6"> 
                    @yield('content')
                </div>
            </div>
        </main>
    </div>

    <script src="https://kit.fontawesome.com/a2411311d5.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <script src="{{ asset('js/sidebar.js') }}" defer></script>
    @stack('scripts')
    {{-- <script src="https://unpkg.com/preline@latest/dist/preline.js"></script> --}} {{-- Komentar Preline yang benar --}}
</body>
</html>