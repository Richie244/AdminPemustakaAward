@extends('layouts.app')

@section('title', 'Detail Periode')

@section('content')

@php
    // Data Dummy - Pastikan ini diganti dengan data dinamis dari Controller Anda
    $periodeId = $id ?? 1; // Mengambil ID dari route atau default ke 1 jika tidak ada
    
    $allPeriodeData = [
        1 => [
            'periode' => [
                'nama' => 'Periode Januari - Maret 2025',
                'start_date' => '2025-01-01',
                'end_date' => '2025-03-31',
                'status' => 'Aktif', // Menambahkan status untuk konsistensi
            ],
            'skor' => [ 
                'skor_aksara_dinamika_default' => 85,
            ],
            'rewards' => [
                ['level' => 1, 'nama_reward' => 'Medali Perunggu', 'skor_minimal' => 100,  'slot_tersedia' => 10, 'icon' => 'bronze-medal.svg'],
                ['level' => 2, 'nama_reward' => 'Medali Perak', 'skor_minimal' => 200, 'slot_tersedia' => 5, 'icon' => 'silver-medal.svg'],
                ['level' => 3, 'nama_reward' => 'Medali Emas', 'skor_minimal' => 300, 'slot_tersedia' => 3, 'icon' => 'gold-medal.svg']
            ],
            'nilai_maks_komponen' => [ 
                ['komponen' => 'Kunjungan Harian', 'nilai' => 50, 'icon' => 'calendar-check.svg'],
                ['komponen' => 'Aksara Dinamika (Review Buku)', 'nilai' => 100, 'icon' => 'book-open.svg'],
                ['komponen' => 'Peminjaman Buku', 'nilai' => 75, 'icon' => 'book-borrow.svg'],
                ['komponen' => 'Partisipasi Kegiatan', 'nilai' => 150, 'icon' => 'users-group.svg']
            ]
        ],
         2 => [
            'periode' => [
                'nama' => 'Periode April - Juni 2025',
                'start_date' => '2025-04-01',
                'end_date' => '2025-06-30',
                'status' => 'Non-Aktif',
            ],
            'skor' => [
                'skor_aksara_dinamika_default' => 0, 
            ],
            'rewards' => [
                ['level' => 1, 'nama_reward' => 'Voucher Diskon', 'skor_minimal' => 120,  'slot_tersedia' => 15, 'icon' => 'ticket.svg'],
            ],
            'nilai_maks_komponen' => [
                 ['komponen' => 'Kunjungan Harian', 'nilai' => 60, 'icon' => 'calendar-check.svg'],
            ]
        ],
    ];

    $periodeData = $allPeriodeData[$periodeId] ?? $allPeriodeData[1]; 

    if (!function_exists('formatTanggalIndo')) {
        function formatTanggalIndo($tanggal) {
            if (empty($tanggal)) return '-';
            return \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y');
        }
    }
@endphp

<div class="bg-gray-100 min-h-screen py-8 px-4">
    {{-- PERUBAHAN: max-w-5xl menjadi max-w-7xl untuk tampilan lebih lebar --}}
    <div class="max-w-7xl mx-auto"> 
        
        <div class="mb-6">
            <a href="{{ url('/periode') }}" 
               class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Daftar Periode
            </a>
        </div>

        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-lg mb-8">
            <div class="flex flex-col sm:flex-row justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ $periodeData['periode']['nama'] }}</h1>
                    <div class="flex items-center text-gray-500 text-sm space-x-4">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span>Mulai: {{ formatTanggalIndo($periodeData['periode']['start_date']) }}</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span>Selesai: {{ formatTanggalIndo($periodeData['periode']['end_date']) }}</span>
                        </div>
                    </div>
                </div>
                <span class="mt-3 sm:mt-0 text-sm font-semibold px-4 py-1.5 rounded-full
                    {{ ($periodeData['periode']['status'] ?? 'Non-Aktif') === 'Aktif' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200' }}">
                    Status: {{ $periodeData['periode']['status'] ?? 'Non-Aktif' }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 space-y-8">
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <div class="flex items-center text-gray-700 mb-3">
                        <svg class="w-6 h-6 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.964 14.964L9 9M15 9l-6 6"></path></svg>
                        <h2 class="text-xl font-semibold">Skor Default</h2>
                    </div>
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <p class="text-sm text-blue-700">Aksara Dinamika (Review Buku)</p>
                        <p class="text-3xl font-bold text-blue-600">{{ $periodeData['skor']['skor_aksara_dinamika_default'] ?? 0 }} <span class="text-lg font-normal">poin</span></p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <div class="flex items-center text-gray-700 mb-4">
                         <svg class="w-6 h-6 mr-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <h2 class="text-xl font-semibold">Poin Maksimum per Komponen</h2>
                    </div>
                    <ul class="space-y-3">
                        @forelse ($periodeData['nilai_maks_komponen'] as $komponen)
                        <li class="flex justify-between items-center bg-purple-50 p-3 rounded-md hover:bg-purple-100 transition-colors">
                            <div class="flex items-center">
                                <span class="mr-3 text-purple-600">
                                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        @if($komponen['komponen'] === 'Kunjungan Harian') <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        @elseif($komponen['komponen'] === 'Aksara Dinamika (Review Buku)') <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v11.494m0 0A7.5 7.5 0 0019.5 12H4.5a7.5 7.5 0 007.5 5.747z"></path>
                                        @elseif($komponen['komponen'] === 'Peminjaman Buku') <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                        @else <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        @endif
                                    </svg>
                                </span>
                                <span class="text-sm text-gray-700">{{ $komponen['komponen'] }}</span>
                            </div>
                            <span class="font-semibold text-purple-700">{{ $komponen['nilai'] }} poin</span>
                        </li>
                        @empty
                        <li class="text-sm text-gray-500">Belum ada komponen nilai maksimum.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white p-6 rounded-xl shadow-lg h-full">
                    <div class="flex items-center text-gray-700 mb-4">
                        <svg class="w-6 h-6 mr-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <h2 class="text-xl font-semibold">Level Reward</h2>
                    </div>
                    <div class="space-y-4">
                        @forelse ($periodeData['rewards'] as $index => $reward)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-200">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between">
                                <div class="flex items-center mb-2 sm:mb-0">
                                    <span class="mr-3 p-2 rounded-full 
                                        @if($reward['level'] == 1) bg-yellow-100 text-yellow-600 
                                        @elseif($reward['level'] == 2) bg-gray-200 text-gray-600
                                        @else bg-amber-100 text-amber-600 @endif">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            @if($reward['level'] == 1) <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3-3m0 0l3 3m-3-3v8m0-13a9 9 0 110 18 9 9 0 010-18z"></path>
                                            @elseif($reward['level'] == 2) <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                            @else <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                            @endif
                                        </svg>
                                    </span>
                                    <div>
                                        <h4 class="font-semibold text-gray-800">{{ $reward['nama_reward'] }} <span class="text-xs text-gray-500">(Level {{ $reward['level'] }})</span></h4>
                                        <p class="text-xs text-gray-500">Min. {{ $reward['skor_minimal'] }} Poin</p>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-600 text-left sm:text-right">
                                    <span class="font-medium">{{ $reward['slot_tersedia'] }}</span> Slot Tersedia
                                </div>
                            </div>
                        </div>
                        @empty
                        <p class="text-sm text-gray-500">Belum ada level reward yang ditentukan untuk periode ini.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
