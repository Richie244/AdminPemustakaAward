@extends('layouts.guest') {{-- INI PERUBAHAN UTAMA: Menggunakan layout guest --}}

@section('title', 'Login Aplikasi')

@section('content')
{{-- Card login yang di-style dengan Tailwind CSS --}}
{{-- Struktur HTML card login di sini SAMA SEPERTI yang sudah kita buat sebelumnya --}}
{{-- yang sudah di-style dengan Tailwind --}}
<div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-md">
    <h4 class="text-3xl font-bold text-center text-slate-800 mb-8">Log In!</h4>

    {{-- Menampilkan error validasi umum (jika ada) --}}
    @if ($errors->any() && !$errors->has('nocivitas') && !$errors->has('password') && !$errors->has('loginError'))
        <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
            <p class="font-bold">Terjadi Kesalahan</p>
            <ul class="mt-1 list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    @if ($error !== $errors->first('nocivitas') && $error !== $errors->first('password') && $error !== $errors->first('loginError'))
                        <li>{{ $error }}</li>
                    @endif
                @endforeach
            </ul>
        </div>
    @endif
    
    @error('loginError')
        <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
            <p>{{ $message }}</p>
        </div>
    @enderror

    <form action="{{ route('login.authenticate') }}" method="POST">
        @csrf
        
        <div class="mb-5">
            <label for="nocivitas" class="sr-only">NIK/NIM</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="input-icon h-5 w-5 text-slate-400" viewBox="0 0 500 500" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                         <path d="M207.8 20.73c-93.45 18.32-168.7 93.66-187 187.1c-27.64 140.9 68.65 266.2 199.1 285.1c19.01 2.888 36.17-12.26 36.17-31.49l.0001-.6631c0-15.74-11.44-28.88-26.84-31.24c-84.35-12.98-149.2-86.13-149.2-174.2c0-102.9 88.61-185.5 193.4-175.4c91.54 8.869 158.6 91.25 158.6 183.2l0 16.16c0 22.09-17.94 40.05-40 40.05s-40.01-17.96-40.01-40.05v-120.1c0-8.847-7.161-16.02-16.01-16.02l-31.98 .0036c-7.299 0-13.2 4.992-15.12 11.68c-24.85-12.15-54.24-16.38-86.06-5.106c-38.75 13.73-68.12 48.91-73.72 89.64c-9.483 69.01 43.81 128 110.9 128c26.44 0 50.43-9.544 69.59-24.88c24 31.3 65.23 48.69 109.4 37.49C465.2 369.3 496 324.1 495.1 277.2V256.3C495.1 107.1 361.2-9.332 207.8 20.73zM239.1 304.3c-26.47 0-48-21.56-48-48.05s21.53-48.05 48-48.05s48 21.56 48 48.05S266.5 304.3 239.1 304.3z"></path>
                    </svg>
                </div>
                <input 
                    autocomplete="off" 
                    id="nocivitas" 
                    placeholder="NIK/NIM" 
                    class="w-full pl-10 pr-3 py-2.5 border @error('nocivitas') border-red-500 @else border-slate-300 @enderror rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-slate-700 placeholder-slate-400" 
                    name="nocivitas" 
                    type="text" 
                    value="{{ old('nocivitas') }}" 
                    required 
                    autofocus>
            </div>
            @error('nocivitas') 
                <p class="text-red-500 text-xs mt-1.5 -translate-y-3">{{ $message }}</p> {{-- Sedikit penyesuaian posisi error --}}
            @enderror
        
            <div class="mb-6 @error('nocivitas') mt-1 @else mt-0 @enderror"> {{-- Atur margin top jika ada error nocivitas --}}
                <label for="password" class="sr-only">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="input-icon h-5 w-5 text-slate-400" viewBox="0 0 500 500" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                            <path d="M80 192V144C80 64.47 144.5 0 224 0C303.5 0 368 64.47 368 144V192H384C419.3 192 448 220.7 448 256V448C448 483.3 419.3 512 384 512H64C28.65 512 0 483.3 0 448V256C0 220.7 28.65 192 64 192H80zM144 192H304V144C304 99.82 268.2 64 224 64C179.8 64 144 99.82 144 144V192z"></path>
                        </svg>
                    </div>
                    <input 
                        autocomplete="current-password" 
                        id="password" 
                        placeholder="Password" 
                        class="w-full pl-10 pr-3 py-2.5 border @error('password') border-red-500 @else border-slate-300 @enderror rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors text-slate-700 placeholder-slate-400" 
                        name="password" 
                        type="password" 
                        required>
                </div>
                @error('password') 
                    <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
                @enderror
            </div>
            
            <button 
                class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold py-2.5 px-4 rounded-lg transition duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-75 shadow-md hover:shadow-lg @error('password') mt-1 @else mt-0 @enderror" 
                type="submit">
                Login
            </button>
            
            @if (Route::has('password.request')) 
                <div class="text-center mt-5">
                    <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-800 hover:underline transition-colors">
                        Forgot your password?
                    </a>
                </div>
            @endif
        </form>
</div>
@endsection