@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
<div class="py-8 px-4">
    <div class="max-w-7xl mx-auto">
        {{-- Pesan Selamat Datang --}}
        <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
            <h2 class="text-2xl font-bold text-gray-800">Selamat Datang, {{ session('nama_pengguna', 'Admin') }}!</h2>
            <p class="text-gray-600 mt-1">Berikut adalah ringkasan leaderboard saat ini.</p>
        </div>

        {{-- Grid untuk Leaderboard --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {{-- Kolom Leaderboard Mahasiswa --}}
            <div>
                <x-leaderboard-mahasiswa :top5Mahasiswa="$top5Mahasiswa" />
            </div>

            {{-- Kolom Leaderboard Dosen --}}
            <div>
                <x-leaderboard-dosen :top5Dosen="$top5Dosen" />
            </div>
        </div>
    </div>
</div>
@endsection