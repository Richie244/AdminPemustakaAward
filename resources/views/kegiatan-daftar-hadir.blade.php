@extends('layouts.app')

@section('title', 'Daftar Hadir - ' . ($kegiatan->judul_kegiatan ?? 'Kegiatan Tidak Ditemukan'))

@section('content')
<div class="min-h-screen py-8 px-4">
    <div class="max-w-5xl mx-auto">
        {{-- Tombol Kembali --}}
        <div class="mb-6">
            <a href="{{ route('kegiatan.index') }}" 
               class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Daftar Kegiatan
            </a>
        </div>

        {{-- Card Utama --}}
        <div class="bg-white p-6 sm:p-8 shadow-xl rounded-2xl">
            <div class="mb-6 pb-4 border-b border-gray-200">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-1">
                    Daftar Hadir: {{ $kegiatan->judul_kegiatan ?? ($kegiatan->JUDUL_KEGIATAN ?? 'Kegiatan Tidak Diketahui') }}
                </h1>
                <p class="text-sm text-gray-500">ID Kegiatan: {{ $kegiatan->id_kegiatan ?? ($kegiatan->ID_KEGIATAN ?? 'N/A') }}</p>
            </div>

            @if($jadwalDenganKehadiran->isEmpty())
                <div class="text-center py-10 text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <h3 class="mt-2 text-lg font-medium text-gray-900">Belum Ada Jadwal</h3>
                    <p class="mt-1 text-sm">Tidak ada jadwal yang ditemukan untuk kegiatan ini.</p>
                </div>
            @else
                <div class="space-y-6">
                    @foreach($jadwalDenganKehadiran as $index => $jadwal)
                        <div class="p-5 border border-gray-200 rounded-xl bg-gray-50/50">
                            <h3 class="text-lg font-semibold text-gray-700 mb-1">
                                Sesi {{ $index + 1 }}: 
                                {{ $jadwal->tgl_kegiatan ? \Carbon\Carbon::parse($jadwal->tgl_kegiatan)->translatedFormat('d F Y') : 'Tanggal Tidak Ada' }}
                                ({{ $jadwal->waktu_mulai ? \Carbon\Carbon::parse($jadwal->waktu_mulai)->format('H:i') : '' }}
                                @if($jadwal->waktu_selesai)
                                - {{ \Carbon\Carbon::parse($jadwal->waktu_selesai)->format('H:i') }}
                                @endif)
                            </h3>
                            @if(property_exists($jadwal, 'kode_random') && $jadwal->kode_random)
                                <p class="text-sm text-gray-500 mb-3">Kode Presensi: <span class="font-mono bg-gray-200 px-2 py-0.5 rounded text-gray-700">{{ $jadwal->kode_random }}</span></p>
                            @endif

                            @if($jadwal->kehadiran->isNotEmpty())
                                <div class="overflow-x-auto rounded-lg border border-gray-200 mt-3">
                                    <table class="min-w-full w-full">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIM Peserta</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-100">
                                            @foreach($jadwal->kehadiran as $kehadiranIndex => $nim)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-600">{{ $kehadiranIndex + 1 }}</td>
                                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">{{ $nim }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-sm text-gray-500 mt-3">Belum ada peserta yang hadir untuk sesi ini.</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
