<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        /* Sidebar agar tetap scrollable jika kontennya panjang */
        .sidebar {
            height: 100vh;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-gray-200">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="fixed top-0 left-0 w-64 h-screen bg-gray-900 text-white p-5 sidebar">
            <h1 class="text-xl font-bold mb-5">Menu</h1>
            <ul>
                <li class="mb-3">
                    <a href="{{ route('kegiatan.index') }}" 
                        class="block p-2 rounded shadow-md hover:bg-gray-300 
                        {{ request()->is('kegiatan*') ? 'bg-white text-black' : 'bg-gray-900 text-white' }}">
                        Kegiatan
                    </a>
                </li>
                <li class="mb-3">
                    <a href="/periode" 
                        class="block p-2 rounded hover:bg-gray-700 
                        {{ request()->is('periode') || request()->is('detailperiode/*') || request()->is('settingperiode') ? 'bg-white text-black' : 'bg-gray-900 text-white' }}">
                        Periode
                    </a>
                </li>
                <li class="mb-3">
                    <a href="{{ route('aksara.index') }}" 
                        class="block p-2 rounded hover:bg-gray-700 
                        {{ request()->is('aksara*') ? 'bg-white text-black' : 'bg-gray-900 text-white' }}">
                        Validasi Aksara Dinamika
                    </a>
                </li>
                <li class="mb-3">
                    <a href="/report" 
                        class="block p-2 rounded hover:bg-gray-700 
                        {{ request()->is('report') ? 'bg-white text-black' : 'bg-gray-900 text-white' }}">
                        Report
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Content -->
        <main class="ml-64 p-5 flex-1 h-screen overflow-auto">
            @yield('content')
        </main>
    </div>
</body>
</html>
