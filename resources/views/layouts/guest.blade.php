<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', config('app.name', 'Laravel'))</title>

    {{-- Memuat Tailwind CSS --}}
    {{-- Jika Anda menggunakan Vite atau Mix, ganti dengan @vite atau mix() --}}
    <script src="https://cdn.tailwindcss.com"></script>
    
    {{-- Memuat Font Inter (atau font lain yang Anda gunakan) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
    @stack('styles') {{-- Untuk style tambahan per halaman jika perlu --}}
</head>
<body class="bg-slate-100 text-slate-800 antialiased">
    {{-- Konten utama untuk halaman guest (login, register, forgot password, dll.) --}}
    <div class="flex items-center justify-center min-h-screen अंत-sm:px-4">
        @yield('content')
    </div>

    @stack('scripts') {{-- Untuk script tambahan per halaman jika perlu --}}
</body>
</html>