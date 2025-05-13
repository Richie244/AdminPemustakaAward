@extends('layouts.app')

@section('title', 'Periode')

@section('content')
@php
    $periodeList = [
        ['id' => 1, 'nama' => 'Januari - Maret 2025', 'status' => 'Aktif', 'deskripsi' => 'Periode pendaftaran dan pelaksanaan kegiatan awal tahun.', 'kegiatan_count' => 5],
        ['id' => 2, 'nama' => 'April - Juni 2025', 'status' => 'Non-Aktif', 'deskripsi' => 'Periode kegiatan pertengahan tahun, saat ini belum aktif.', 'kegiatan_count' => 0],
        ['id' => 3, 'nama' => 'Juli - September 2025', 'status' => 'Non-Aktif', 'deskripsi' => 'Periode kegiatan setelah pertengahan tahun.', 'kegiatan_count' => 2],
        ['id' => 4, 'nama' => 'Oktober - Desember 2025', 'status' => 'Non-Aktif', 'deskripsi' => 'Periode kegiatan akhir tahun.', 'kegiatan_count' => 0],
    ];
@endphp

{{-- Background halaman utama --}}
<div class="bg-gray-100 min-h-screen py-8 px-4">
    {{-- Container utama, max-w-4xl dan mx-auto untuk menengahkan blok --}}
    <div class="max-w-4xl mx-auto">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-10">
            <h1 class="text-3xl font-bold text-gray-800 mb-4 sm:mb-0">Manajemen Periode Kegiatan</h1>
            <a href="{{ url('/settingperiode') }}" 
               class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-6 py-3 rounded-lg shadow-md hover:shadow-lg flex items-center gap-2 transition-all duration-150 ease-in-out transform hover:scale-105">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Tambah Periode Baru
            </a>
        </div>

        @if (empty($periodeList))
            {{-- Tampilan jika tidak ada periode --}}
            <div class="text-center bg-white p-10 rounded-xl shadow-lg">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">Belum Ada Periode</h3>
                <p class="mt-1 text-sm text-gray-500">Mulai dengan menambahkan periode kegiatan baru.</p>
            </div>
        @else
            {{-- PERUBAHAN: md:grid-cols-2 diubah menjadi md:grid-cols-1 --}}
            <div class="grid grid-cols-1 md:grid-cols-1 gap-6"> 
                @foreach ($periodeList as $periode)
                <div class="group relative bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 ease-in-out overflow-hidden"> {{-- Sedikit mengurangi shadow hover --}}
                    <div class="p-6 md:p-8"> {{-- Menambah padding pada layar medium ke atas --}}
                        <div class="flex flex-col sm:flex-row justify-between items-start mb-3">
                            <h3 class="text-xl md:text-2xl font-semibold text-gray-800 group-hover:text-blue-600 transition-colors duration-300 mb-2 sm:mb-0">{{ $periode['nama'] }}</h3>
                            <span class="text-xs font-semibold px-3 py-1 rounded-full whitespace-nowrap {{-- Mencegah status wrap --}}
                                {{ $periode['status'] === 'Aktif' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200' }}">
                                {{ $periode['status'] }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 mb-4 h-auto md:h-10 overflow-hidden"> {{-- Mengatur tinggi deskripsi lagi --}}
                            {{ $periode['deskripsi'] ?? 'Tidak ada deskripsi untuk periode ini.' }}
                        </p>
                        
                        <div class="flex items-center text-sm text-gray-500 mb-5">
                            <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span>{{ $periode['kegiatan_count'] }} Kegiatan Terjadwal</span>
                        </div>

                        <div class="border-t border-gray-200 pt-4">
                             <a href="{{ url('/detailperiode/' . $periode['id']) }}" 
                               class="w-full bg-blue-50 hover:bg-blue-100 text-blue-600 font-medium px-4 py-2.5 rounded-lg flex items-center justify-center gap-2 transition-all duration-150 ease-in-out text-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Detail
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
