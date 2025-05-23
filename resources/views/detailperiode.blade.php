@extends('layouts.app')

@section('title', 'Detail Periode Award')

@section('content')

@php
    // Helper function for date formatting
    if (!function_exists('formatTanggalIndo')) {
        function formatTanggalIndo($tanggal) {
            if (empty($tanggal)) return 'N/A';
            try {
                return \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y');
            } catch (\Exception $e) {
                return 'Tanggal Tidak Valid';
            }
        }
    }

    // Determine periode status
    $statusPeriodeText = 'Non-Aktif';
    $statusPeriodeClass = 'bg-red-100 text-red-700 border border-red-200';
    $tglMulaiDariPeriode = null;
    $tglSelesaiDariPeriode = null;

    if (isset($periode) && is_object($periode)) {
        $tglMulaiDariPeriode = $periode->TGL_MULAI ?? $periode->tgl_mulai ?? null;
        $tglSelesaiDariPeriode = $periode->TGL_SELESAI ?? $periode->tgl_selesai ?? null;
    }

    if ($tglMulaiDariPeriode && $tglSelesaiDariPeriode) {
        try {
            $now = \Carbon\Carbon::now();
            $tglMulaiCarbon = \Carbon\Carbon::parse($tglMulaiDariPeriode);
            $tglSelesaiCarbon = \Carbon\Carbon::parse($tglSelesaiDariPeriode)->endOfDay();
            
            if ($now->between($tglMulaiCarbon, $tglSelesaiCarbon)) {
                $statusPeriodeText = 'Aktif';
                $statusPeriodeClass = 'bg-green-100 text-green-700 border border-green-200';
            } elseif ($now->lt($tglMulaiCarbon)) {
                $statusPeriodeText = 'Akan Datang';
                $statusPeriodeClass = 'bg-blue-100 text-blue-700 border border-blue-200';
            } elseif ($now->gt($tglSelesaiCarbon)) {
                $statusPeriodeText = 'Selesai';
            }
        } catch (\Exception $e) {
            // Log::error('Error parsing date in detailperiode.blade.php for status: ' . $e->getMessage());
        }
    }

    // $allPembobotansForView adalah array [id_jenis_bobot => object{id_jenis_bobot, nama_jenis_bobot, nilai}]
    // yang dikirim dari controller.
    
    $skorAksaraDinamikaDefault = 'N/A';
    if (isset($allPembobotansForView[8]) && is_object($allPembobotansForView[8])) {
        $skorAksaraDinamikaDefault = $allPembobotansForView[8]->nilai ?? 'N/A';
    }

    // Data untuk "Poin Maksimum per Komponen" (ID Jenis Bobot 4, 5, 6, 7)
    $poinKomponenUntukTampilan = [];
    $komponenIdsToShow = [4, 5, 6, 7]; 
    $iconKeys = [ 
        4 => 'kunjungan', 5 => 'pinjaman', 6 => 'aksara_dinamika', 7 => 'kegiatan',
    ];

    if (isset($allPembobotansForView) && is_array($allPembobotansForView)) {
        foreach ($komponenIdsToShow as $idBobot) {
            $namaKomponen = $namaJenisBobotFromController[$idBobot] ?? 'Komponen Tidak Dikenal'; // Ambil nama dari mapping controller
            $nilaiKomponen = 'N/A';
            $iconKeyKomponen = $iconKeys[$idBobot] ?? 'default';

            if (isset($allPembobotansForView[$idBobot]) && is_object($allPembobotansForView[$idBobot])) {
                $nilaiKomponen = $allPembobotansForView[$idBobot]->nilai ?? 'N/A';
                // Jika nama dari $allPembobotansForView lebih akurat (misal dari DB langsung), gunakan itu
                // $namaKomponen = $allPembobotansForView[$idBobot]->nama_jenis_bobot ?? $namaKomponen;
            }
            $poinKomponenUntukTampilan[$idBobot] = [
                'nama' => $namaKomponen,
                'nilai' => $nilaiKomponen,
                'icon_key' => $iconKeyKomponen
            ];
        }
    }

@endphp

<div class="bg-gray-100 min-h-screen py-8 px-4">
    <div class="max-w-7xl mx-auto"> 
        
        <div class="mb-6">
            <a href="{{ route('periode.index') }}" 
               class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Daftar Periode
            </a>
        </div>

        @if($error)
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">{{ $error }}</span>
            </div>
        @endif

        @if($periode && !$error)
            <div class="bg-white p-6 sm:p-8 rounded-xl shadow-lg mb-8">
                <div class="flex flex-col sm:flex-row justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ $periode->NAMA_PERIODE ?? $periode->nama_periode ?? $periode->nama ?? 'Nama Periode Tidak Tersedia' }}</h1>
                        <div class="flex items-center text-gray-500 text-sm space-x-4">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-1.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span>Mulai: {{ formatTanggalIndo($periode->TGL_MULAI ?? $periode->tgl_mulai ?? null) }}</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-1.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span>Selesai: {{ formatTanggalIndo($periode->TGL_SELESAI ?? $periode->tgl_selesai ?? null) }}</span>
                            </div>
                        </div>
                    </div>
                    <span class="mt-3 sm:mt-0 text-sm font-semibold px-4 py-1.5 rounded-full {{ $statusPeriodeClass }}">
                        Status: {{ $statusPeriodeText }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-1 space-y-8">
                    {{-- Skor Default Aksara Dinamika --}}
                    <div class="bg-white p-6 rounded-xl shadow-lg">
                        <div class="flex items-center text-gray-700 mb-3">
                            <svg class="w-6 h-6 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.964 14.964L9 9M15 9l-6 6"></path></svg>
                            <h2 class="text-xl font-semibold">Skor Default</h2>
                        </div>
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <p class="text-sm text-blue-700">{{ $namaJenisBobotFromController[8] ?? 'Aksara Dinamika (Review Buku)' }}</p>
                            <p class="text-3xl font-bold text-blue-600">{{ $skorAksaraDinamikaDefault }} <span class="text-lg font-normal">poin</span></p>
                        </div>
                    </div>

                    {{-- Detail Skor Range Kunjungan Harian --}}
                    <div class="bg-white p-6 rounded-xl shadow-lg">
                        <div class="flex items-center text-gray-700 mb-4">
                             <svg class="w-6 h-6 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7.014A8.003 8.003 0 0122 12c0 3.771-2.502 6.94-6.014 7.753A8.003 8.003 0 0012 22a8.003 8.003 0 00-5.986-2.247C3.502 18.94 1 15.771 1 12c0-1.604.468-3.112 1.258-4.427m2.828 11.084A8 8 0 0117.657 5.343M6.343 18.657A8 8 0 015.343 6.343"></path></svg>
                            <h2 class="text-xl font-semibold">Detail Skor Kunjungan Harian</h2>
                        </div>
                        <ul class="space-y-2">
                            @forelse ($rangesKunjungan as $range)
                            @php $rangeItem = (object) $range; @endphp
                            <li class="flex justify-between items-center bg-green-50 p-3 rounded-md hover:bg-green-100 transition-colors">
                                <div class="flex items-center flex-grow"> 
                                    <span class="mr-2 text-green-600 flex-shrink-0"> 
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </span>
                                    <span class="text-sm text-gray-700">Range {{ $rangeItem->RANGE_AWAL ?? $rangeItem->range_awal ?? 'N/A' }} - {{ $rangeItem->RANGE_AKHIR ?? $rangeItem->range_akhir ?? 'N/A' }}</span>
                                </div>
                                <span class="text-sm font-semibold text-green-700 ml-2 flex-shrink-0">{{ $rangeItem->BOBOT ?? $rangeItem->bobot ?? 'N/A' }} poin</span>
                            </li>
                            @empty
                            <li class="text-sm text-gray-500">Belum ada pengaturan skor untuk kunjungan.</li>
                            @endforelse
                        </ul>
                    </div>

                    {{-- Detail Skor Peminjaman Buku --}}
                    <div class="bg-white p-6 rounded-xl shadow-lg">
                        <div class="flex items-center text-gray-700 mb-4">
                            <svg class="w-6 h-6 mr-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v11.494m0 0A7.5 7.5 0 0019.5 12H4.5a7.5 7.5 0 007.5 5.747z"></path></svg>
                            <h2 class="text-xl font-semibold">Detail Skor Peminjaman Buku</h2>
                        </div>
                        <ul class="space-y-2">
                            @forelse ($rangesPinjaman as $range)
                            @php $rangeItem = (object) $range; @endphp
                            <li class="flex justify-between items-center bg-indigo-50 p-3 rounded-md hover:bg-indigo-100 transition-colors">
                                 <div class="flex items-center flex-grow">
                                    <span class="mr-2 text-indigo-600 flex-shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                    </span>
                                    <span class="text-sm text-gray-700">Range {{ $rangeItem->RANGE_AWAL ?? $rangeItem->range_awal ?? 'N/A' }} - {{ $rangeItem->RANGE_AKHIR ?? $rangeItem->range_akhir ?? 'N/A' }}</span>
                                </div>
                                <span class="text-sm font-semibold text-indigo-700 ml-2 flex-shrink-0">{{ $rangeItem->BOBOT ?? $rangeItem->bobot ?? 'N/A' }} poin</span>
                            </li>
                            @empty
                            <li class="text-sm text-gray-500">Belum ada pengaturan skor untuk peminjaman.</li>
                            @endforelse
                        </ul>
                    </div>

                    {{-- Poin Maksimum per Komponen --}}
                    <div class="bg-white p-6 rounded-xl shadow-lg">
                        <div class="flex items-center text-gray-700 mb-4">
                            <svg class="w-6 h-6 mr-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            <h2 class="text-xl font-semibold">Poin Maksimum per Komponen</h2>
                        </div>
                        <ul class="space-y-3">
                            @forelse ($poinKomponenUntukTampilan as $idBobot => $komponen)
                            <li class="flex justify-between items-center bg-purple-50 p-3 rounded-md hover:bg-purple-100 transition-colors">
                                <div class="flex items-center flex-grow min-w-0"> 
                                    <span class="mr-2 text-purple-600 flex-shrink-0">
                                        @if($komponen['icon_key'] === 'kunjungan') <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        @elseif($komponen['icon_key'] === 'aksara_dinamika') <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v11.494m0 0A7.5 7.5 0 0019.5 12H4.5a7.5 7.5 0 007.5 5.747z"></path></svg>
                                        @elseif($komponen['icon_key'] === 'pinjaman') <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                        @elseif($komponen['icon_key'] === 'kegiatan') <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                        @else <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10m0 0v10m0-10L7 17m10-10H7"></path></svg>
                                        @endif
                                    </span>
                                    <span class="text-sm text-gray-700 truncate">
                                        {{ $komponen['nama'] ?? 'N/A' }}
                                    </span>
                                </div>
                                <span class="text-sm font-semibold text-purple-700 ml-2 flex-shrink-0">{{ $komponen['nilai'] ?? 'N/A' }} poin</span>
                            </li>
                            @empty
                            <li class="text-sm text-gray-500">Belum ada komponen poin maksimum yang ditentukan.</li>
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
                            @forelse ($rewards as $reward)
                            @php $rewardItem = (object) $reward; @endphp
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-200">
                                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between">
                                    <div class="flex items-center mb-2 sm:mb-0 flex-grow min-w-0"> 
                                        <span class="mr-2 p-2 rounded-full flex-shrink-0 
                                            @if(($rewardItem->processed_level ?? 0) == 1) bg-yellow-100 text-yellow-600 
                                            @elseif(($rewardItem->processed_level ?? 0) == 2) bg-gray-200 text-gray-600
                                            @else bg-amber-100 text-amber-600 @endif">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                            </svg>
                                        </span>
                                        <div class="truncate"> 
                                            <h4 class="font-semibold text-gray-800 text-sm truncate">{{ $rewardItem->processed_bentuk ?? 'N/A' }} 
                                                <span class="text-xs text-gray-500">(Level {{ $rewardItem->processed_level ?? 'N/A' }})</span></h4>
                                            <p class="text-xs text-gray-500">Min. {{ $rewardItem->skor_minimal ?? 'N/A' }} Poin</p>
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-600 text-left sm:text-right ml-2 flex-shrink-0">
                                        <span class="font-medium">{{ $rewardItem->processed_slot ?? 'N/A' }}</span> Slot Tersedia
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
        @elseif(!$error)
            <div class="text-center bg-white p-10 rounded-xl shadow-lg">
                 <h3 class="mt-2 text-lg font-medium text-gray-900">Periode Tidak Ditemukan</h3>
                 <p class="mt-1 text-sm text-gray-500">Data untuk periode ini tidak dapat ditemukan.</p>
            </div>
        @endif
    </div>
</div>
@endsection
