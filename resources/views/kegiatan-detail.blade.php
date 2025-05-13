@extends('layouts.app')

@section('title', 'Detail Kegiatan - ' . ($kegiatan->judul_kegiatan ?? ($kegiatan->JUDUL_KEGIATAN ?? 'Tidak Diketahui')))

@section('content')
<div class="min-h-screen py-8 px-4">
    <div class="max-w-4xl mx-auto">
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

        {{-- Card Detail Kegiatan --}}
        <div class="bg-white p-6 sm:p-8 shadow-xl rounded-2xl">
            <div class="flex flex-col sm:flex-row justify-between items-start mb-6 pb-4 border-b border-gray-200">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-1">{{ $kegiatan->judul_kegiatan ?? ($kegiatan->JUDUL_KEGIATAN ?? 'Judul Tidak Tersedia') }}</h1>
                    <p class="text-sm text-gray-500">ID Kegiatan: {{ $kegiatan->id_kegiatan ?? ($kegiatan->ID_KEGIATAN ?? 'N/A') }}</p>
                </div>
                {{-- Anda bisa menambahkan status kegiatan di sini jika ada fieldnya --}}
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 mb-8">
                <div>
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Media</h3>
                    <p class="text-gray-700">{{ $kegiatan->media ?? ($kegiatan->MEDIA ?? '-') }}</p>
                </div>
                <div>
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Lokasi / Link</h3>
                    <p class="text-gray-700">{{ $kegiatan->lokasi ?? ($kegiatan->LOKASI ?? '-') }}</p>
                </div>
                <div class="md:col-span-2">
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Keterangan</h3>
                    <p class="text-gray-700 whitespace-pre-wrap">{{ $kegiatan->keterangan ?? ($kegiatan->KETERANGAN ?? '-') }}</p>
                </div>
                 {{-- PERUBAHAN: Menggunakan $kegiatan->template_sertifikat_file --}}
                @if(isset($kegiatan->template_sertifikat_file) && $kegiatan->template_sertifikat_file && property_exists($kegiatan->template_sertifikat_file, 'nama_file'))
                <div class="md:col-span-2">
                    <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Template Sertifikat</h3>
                    {{-- Asumsi file disimpan di storage/app/public/sertifikat_templates_kegiatan --}}
                    <a href="{{ asset('storage/sertifikat_templates_kegiatan/' . $kegiatan->template_sertifikat_file->nama_file) }}" target="_blank" class="text-blue-600 hover:underline">
                        {{ $kegiatan->template_sertifikat_file->nama_file }}
                    </a>
                </div>
                @endif
            </div>

            {{-- Detail Jadwal --}}
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-700 mb-4 border-t pt-4">Jadwal Kegiatan</h2>
                @if($kegiatan->jadwal->isNotEmpty())
                    <div class="space-y-4">
                        @foreach($kegiatan->jadwal as $index => $jadwal)
                        <div class="p-4 border rounded-lg bg-gray-50">
                            <p class="font-medium text-gray-800">Sesi {{ $index + 1 }}</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mt-2 text-sm">
                                <div>
                                    <span class="text-gray-500">Tanggal:</span>
                                    <span class="text-gray-700">{{ property_exists($jadwal, 'tgl_kegiatan') && $jadwal->tgl_kegiatan ? \Carbon\Carbon::parse($jadwal->tgl_kegiatan)->translatedFormat('d F Y') : '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Waktu:</span>
                                    <span class="text-gray-700">
                                        {{ property_exists($jadwal, 'waktu_mulai') && $jadwal->waktu_mulai ? \Carbon\Carbon::parse($jadwal->waktu_mulai)->format('H:i') : '-' }}
                                        @if(property_exists($jadwal, 'waktu_selesai') && $jadwal->waktu_selesai)
                                        - {{ \Carbon\Carbon::parse($jadwal->waktu_selesai)->format('H:i') }}
                                        @endif
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Bobot:</span>
                                    <span class="text-gray-700">{{ property_exists($jadwal, 'bobot') ? ($jadwal->bobot ?? '-') : '-' }} Poin</span>
                                </div>
                                @if(property_exists($jadwal, 'id_pemateri') && $jadwal->id_pemateri != 0)
                                    @php
                                        $namaPemateriJadwal = $kegiatan->pemateri->firstWhere('id_pemateri', $jadwal->id_pemateri)->nama_pemateri ?? 'Pemateri Tidak Ditemukan';
                                    @endphp
                                     @if($namaPemateriJadwal !== 'Pemateri Tidak Ditemukan')
                                    <div>
                                        <span class="text-gray-500">Pemateri Sesi:</span>
                                        <span class="text-gray-700">{{ $namaPemateriJadwal }}</span>
                                    </div>
                                    @endif
                                @endif
                                 @if(property_exists($jadwal, 'kode_random') && $jadwal->kode_random)
                                <div>
                                    <span class="text-gray-500">Kode Presensi:</span>
                                    <span class="text-gray-700 font-mono bg-gray-200 px-2 py-0.5 rounded">{{ $jadwal->kode_random }}</span>
                                </div>
                                @endif
                                @if(property_exists($jadwal, 'keterangan') && $jadwal->keterangan && $jadwal->keterangan !== ($kegiatan->keterangan ?? ($kegiatan->KETERANGAN ?? '-')))
                                <div class="sm:col-span-2">
                                    <span class="text-gray-500">Ket. Jadwal:</span>
                                    <span class="text-gray-700">{{ $jadwal->keterangan }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500">Tidak ada detail jadwal untuk kegiatan ini.</p>
                @endif
            </div>

            {{-- Daftar Pemateri Utama (jika ada, dan jika tidak ditampilkan per sesi jadwal) --}}
            @if($kegiatan->pemateri->isNotEmpty() && !($kegiatan->jadwal->isNotEmpty() && property_exists($kegiatan->jadwal->first(), 'id_pemateri')))
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-700 mb-4 border-t pt-4">Pemateri Kegiatan</h2>
                <ul class="list-disc list-inside text-gray-700 space-y-1">
                    @foreach($kegiatan->pemateri as $p)
                        <li>{{ $p->nama_pemateri ?? 'Nama Tidak Ada' }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Tombol Aksi Tambahan --}}
            <div class="mt-8 pt-6 border-t border-gray-200 flex flex-col sm:flex-row justify-end gap-3">
                <a href="{{ route('kegiatan.edit', ($kegiatan->id_kegiatan ?? $kegiatan->ID_KEGIATAN)) }}" 
                   class="w-full sm:w-auto text-center bg-blue-500 hover:bg-blue-600 text-white px-6 py-2.5 rounded-lg shadow-md transition-colors duration-150">
                    Edit Kegiatan
                </a>
                <form action="{{ route('kegiatan.destroy', ($kegiatan->id_kegiatan ?? $kegiatan->ID_KEGIATAN)) }}" method="POST" class="w-full sm:w-auto" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kegiatan ini beserta semua data terkait?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="w-full bg-red-500 hover:bg-red-600 text-white px-6 py-2.5 rounded-lg shadow-md transition-colors duration-150">
                        Hapus Kegiatan
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
@endsection
