@extends('layouts.app') 

@section('title', 'Daftar Kegiatan')
@section('page_title', 'Manajemen Kegiatan') {{-- Untuk judul di App Bar --}}

@section('content') 
<div class="min-h-screen pt-2 pb-8 px-2">  
    <div>  
        <div class="bg-white p-6 shadow-lg rounded-xl overflow-hidden"> 
            <div class="flex flex-col sm:flex-row justify-between items-center mb-6 pb-4 border-b border-gray-200 gap-4"> 
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Daftar Kegiatan</h1> 
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                    {{-- Formulir Pencarian --}}
                    <form action="{{ route('kegiatan.index') }}" method="GET" class="flex w-full sm:w-auto">
                        <input type="text" name="search" placeholder="Cari judul, lokasi, pemateri..." 
                               value="{{ $searchTerm ?? '' }}" 
                               class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-l-md shadow-sm text-sm py-2 px-3 w-full sm:min-w-[250px]">
                        <button type="submit" 
                                class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-r-md text-sm inline-flex items-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </button>
                        @if($searchTerm)
                        <a href="{{ route('kegiatan.index') }}" class="ml-2 text-sm text-blue-600 hover:text-blue-800 flex items-center" title="Hapus Filter">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                        @endif
                    </form>

                    <a href="{{ route('kegiatan.create') }}"  
                       class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg flex items-center gap-2 transition-all duration-150 ease-in-out transform hover:scale-105 text-sm sm:text-base w-full sm:w-auto justify-center"> 
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"> 
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path> 
                        </svg> 
                        Tambah Kegiatan 
                    </a> 
                </div>
            </div> 

            @if(session('success'))
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md" role="alert">
                    <p class="font-bold">Sukses!</p>
                    <p>{{ session('success') }}</p>
                </div>
            @endif
            @if(session('info'))
                <div class="mb-4 bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded-md" role="alert">
                    <p>{{ session('info') }}</p>
                </div>
            @endif
            @if($errors->has('api_error'))
                <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
                    <p class="font-bold">Error API!</p>
                    <p>{{ $errors->first('api_error') }}</p>
                </div>
            @endif


            @if($kegiatan->isEmpty()) 
                <div class="text-center py-10 text-gray-500"> 
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"> 
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path> 
                    </svg> 
                    <h3 class="mt-2 text-lg font-medium text-gray-900">
                        @if($searchTerm)
                            Tidak Ada Kegiatan Ditemukan
                        @else
                            Belum Ada Kegiatan
                        @endif
                    </h3> 
                    <p class="mt-1 text-sm">
                        @if($searchTerm)
                            Coba kata kunci lain atau <a href="{{ route('kegiatan.index') }}" class="text-blue-600 hover:underline">lihat semua kegiatan</a>.
                        @else
                            Mulai dengan menambahkan kegiatan baru.
                        @endif
                    </p> 
                </div> 
            @else 
                <div class="overflow-x-auto rounded-lg border">  
                    <table class="min-w-full w-full">  
                        <thead class="bg-gray-50"> 
                            <tr> 
                                <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-gray-600">No</th> 
                                <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Judul Kegiatan</th> 
                                <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Tanggal</th> 
                                <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Waktu</th> 
                                <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Pemateri</th> 
                                <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Media/Lokasi</th> 
                                <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Keterangan</th> 
                                <th scope="col" class="px-6 py-3 text-center text-sm font-semibold text-gray-600">Bobot</th> 
                                <th scope="col" class="px-6 py-3 text-center text-sm font-semibold text-gray-600">Aksi</th> 
                            </tr> 
                        </thead> 
                        <tbody class="divide-y divide-gray-200">  
                            @foreach ($kegiatan as $index => $k)  
                                <tr class="hover:bg-gray-50 transition-colors duration-150"> 
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $kegiatan->firstItem() + $index }}</td> 
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800">{{ $k->judul_kegiatan ?? ($k->JUDUL_KEGIATAN ?? '-') }}</td> 
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"> 
                                        {{ $k->jadwal->first() && property_exists($k->jadwal->first(), 'tgl_kegiatan') ? \Carbon\Carbon::parse($k->jadwal->first()->tgl_kegiatan)->translatedFormat('d M Y') : '-' }} 
                                    </td> 
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"> 
                                        {{ $k->jadwal->first() && property_exists($k->jadwal->first(), 'waktu_mulai') ? \Carbon\Carbon::parse($k->jadwal->first()->waktu_mulai)->format('H:i') : '-' }} 
                                        @if($k->jadwal->first() && property_exists($k->jadwal->first(), 'waktu_selesai') && $k->jadwal->first()->waktu_selesai) 
                                            - {{ \Carbon\Carbon::parse($k->jadwal->first()->waktu_selesai)->format('H:i') }} 
                                        @endif 
                                    </td> 
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"> 
                                        {{ $k->pemateri->isNotEmpty() ? $k->pemateri->pluck('nama_pemateri')->filter()->join(', ') : '-' }} 
                                    </td> 
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $k->lokasi ?? ($k->LOKASI ?? '-') }}</td> 
                                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate" title="{{ $k->keterangan ?? ($k->KETERANGAN ?? '') }}">{{ \Illuminate\Support\Str::limit($k->keterangan ?? ($k->KETERANGAN ?? '-'), 50) }}</td> 
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-center"> 
                                        {{ $k->jadwal->first() && property_exists($k->jadwal->first(), 'bobot') ? $k->jadwal->first()->bobot : '-' }} 
                                    </td> 
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center"> 
                                        <div class="flex items-center justify-center space-x-1"> 
                                            @php $kegiatanId = $k->id_kegiatan ?? $k->ID_KEGIATAN ?? null; @endphp 
                                            @if($kegiatanId) 
                                                {{-- Tombol Detail/Edit (mengarah ke kegiatan.show) dengan ikon pensil --}} 
                                                <a href="{{ route('kegiatan.show', $kegiatanId) }}" class="bg-yellow-100 text-yellow-700 hover:bg-yellow-200 px-2.5 py-1 rounded-md text-xs" title="Lihat Detail/Edit"> 
                                                    <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg> 
                                                </a> 
                                                
                                                {{-- Tombol Daftar Hadir (tetap) --}} 
                                                <a href="{{ route('kegiatan.daftar-hadir', $kegiatanId) }}" class="bg-green-100 text-green-600 hover:bg-green-200 px-2.5 py-1 rounded-md text-xs" title="Daftar Hadir"> 
                                                     <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M12 7a4 4 0 110 8 4 4 0 010-8z"></path></svg> 
                                                </a> 
                                                
                                                {{-- Tombol Salin Link (tetap) --}} 
                                                @if(Route::has('kegiatan.show')) 
                                                <button id="copyBtn-{{$kegiatanId}}" onclick="salinLink('{{ route('kegiatan.show', $kegiatanId) }}', 'copyBtn-{{$kegiatanId}}')" class="bg-indigo-100 text-indigo-600 hover:bg-indigo-200 px-2.5 py-1 rounded-md text-xs" title="Salin Link"> 
                                                    <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg> 
                                                </button> 
                                                @endif 
                                            @else 
                                                <span class="text-xs text-gray-400">N/A</span> 
                                            @endif 
                                        </div> 
                                    </td> 
                                </tr> 
                            @endforeach 
                        </tbody>  
                    </table> 
                </div> 
                {{-- Pagination Links --}} 
                <div class="mt-6"> 
                    {{-- Pastikan $kegiatan adalah instance Paginator --}}
                    @if ($kegiatan instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        {{ $kegiatan->links('vendor.pagination.tailwind') }} 
                    @endif
                </div>  
            @endif 
        </div>  
    </div> 
</div> 

<script> 
    function salinLink(url, buttonId) { 
        navigator.clipboard.writeText(url).then(function() { 
            const button = document.getElementById(buttonId); 
            if (!button) return;  
            const originalContent = button.innerHTML;  
            button.innerHTML = ` 
                <svg class="w-4 h-4 text-green-500 inline-block" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg> 
            `;  
            button.disabled = true;  

            setTimeout(() => { 
                button.innerHTML = originalContent; 
                button.disabled = false; 
            }, 1500);  

        }).catch(function(err) { 
            console.error("Gagal menyalin link: ", err); 
            alert("Gagal menyalin link. Pastikan Anda menggunakan koneksi aman (HTTPS) jika ada."); 
        }); 
    } 
</script> 
@endsection
