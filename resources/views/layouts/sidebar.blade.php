<div x-show="sidebarOpen" @click.away="sidebarOpen = false"
     class="fixed inset-0 z-40 flex transition-transform duration-300 lg:static lg:inset-auto lg:translate-x-0"
     :class="{'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen}">
    
    <div class="fixed inset-0 bg-black bg-opacity-50 lg:hidden" @click="sidebarOpen = false" aria-hidden="true"></div>

    <div class="relative flex flex-col w-64 bg-gray-800 text-white sidebar">
        <div class="flex items-center justify-center h-20 shadow-md bg-gray-900">
            <svg class="h-10 w-auto text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v11.494m-9-5.747h18"/>
            </svg>
            <h1 class="text-2xl font-bold ml-2">Admin Panel</h1>
        </div>
        
        <nav class="flex-1 px-4 py-6 space-y-2">
            @php
                function is_active($routeName) {
                    return request()->routeIs($routeName) ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-300 hover:bg-gray-700 hover:text-white';
                }
            @endphp
            
            <a href="{{ route('kegiatan.index') }}"
               class="nav-item flex items-center px-4 py-2.5 rounded-lg transition-colors duration-200 {{ is_active('kegiatan.*') }}">
                <svg class="h-6 w-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span>Kegiatan</span>
            </a>
            
            <a href="{{ route('validasi.aksara.index') }}"
               class="nav-item flex items-center px-4 py-2.5 rounded-lg transition-colors duration-200 {{ is_active('validasi.aksara.*') }}">
                <svg class="h-6 w-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Validasi Aksara</span>
            </a>
            
            <div class="pt-4">
                <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Master Data</h3>
                <div class="mt-2 space-y-2">
                    <a href="{{ route('master-pemateri.index') }}"
                       class="nav-item flex items-center px-4 py-2.5 rounded-lg transition-colors duration-200 {{ is_active('master-pemateri.*') }}">
                         <svg class="h-6 w-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        <span>Master Pemateri</span>
                    </a>
                     <a href="{{ route('master-perusahaan.index') }}"
                       class="nav-item flex items-center px-4 py-2.5 rounded-lg transition-colors duration-200 {{ is_active('master-perusahaan.*') }}">
                        <svg class="h-6 w-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <span>Master Perusahaan</span>
                    </a>
                    <a href="{{ route('periode.index') }}"
                        class="nav-item flex items-center px-4 py-2.5 rounded-lg transition-colors duration-200 {{ is_active('periode.*') }}">
                         <svg class="h-6 w-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span>Master Periode</span>
                    </a>
                </div>
            </div>

            <div class="pt-4">
                 <h3 class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Pengaturan</h3>
                 <div class="mt-2 space-y-2">
                    <a href="{{ route('desain.umum') }}"
                       class="nav-item flex items-center px-4 py-2.5 rounded-lg transition-colors duration-200 {{ is_active('desain.umum') }}">
                        <svg class="h-6 w-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path></svg>
                        <span>Desain Umum</span>
                    </a>
                </div>
            </div>
        </nav>
        
        <div class="mt-auto p-4">
             <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               class="nav-item flex items-center px-4 py-2.5 rounded-lg transition-colors duration-200 text-red-300 hover:bg-red-700 hover:text-white">
                <svg class="h-6 w-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                <span>Logout</span>
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </div>
    </div>
</div>