@extends('layouts.app')

@section('title', 'Master Pemateri')
@section('page_title', 'Manajemen Master Pemateri')

@push('styles')
<style>
    /* Tambahkan style khusus jika diperlukan */
</style>
@endpush

@section('content')
<div class="min-h-screen pt-2 pb-8 px-2"> 
    <div>  
        <div class="bg-white p-6 shadow-lg rounded-xl overflow-hidden"> 
            <div class="flex flex-col sm:flex-row justify-between items-center mb-6 pb-4 border-b border-gray-200 gap-4"> 
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Daftar Master Pemateri</h1> 
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                    {{-- Formulir Pencarian --}}
                    <form action="{{ route('master-pemateri.index') }}" method="GET" class="flex w-full sm:w-auto">
                        <input type="text" name="search" placeholder="Cari nama, email, perusahaan..." 
                               value="{{ $searchTerm ?? '' }}" 
                               class="border-gray-300 focus:border-purple-500 focus:ring-purple-500 rounded-l-md shadow-sm text-sm py-2 px-3 w-full sm:min-w-[200px] md:min-w-[250px]">
                        <button type="submit" 
                                aria-label="Cari"
                                class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-r-md text-sm inline-flex items-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </button>
                        @if($searchTerm ?? null)
                        <a href="{{ route('master-pemateri.index') }}" class="ml-2 text-sm text-blue-600 hover:text-blue-800 flex items-center" title="Hapus Filter Pencarian">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                        @endif
                    </form>

                    <a href="{{ route('master-pemateri.create') }}"  
                       class="bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg flex items-center gap-2 transition-all duration-150 ease-in-out transform hover:scale-105 text-sm sm:text-base w-full sm:w-auto justify-center"> 
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"> 
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path> 
                        </svg> 
                        Tambah Pemateri 
                    </a> 
                </div>
            </div> 

            @if(session('success'))
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md" role="alert">
                    <p class="font-bold">Sukses!</p>
                    <p>{{ session('success') }}</p>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
                    <p class="font-bold">Gagal!</p>
                    <p>{{ session('error') }}</p>
                </div>
            @endif
            @if($error_message ?? null) {{-- Pesan error dari controller jika ada masalah saat fetch data --}}
                 <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
                    <p class="font-bold">Error!</p>
                    <p>{{ $error_message }}</p>
                </div>
            @endif


            @if(!isset($pemateriList) || $pemateriList->isEmpty()) 
                <div class="text-center py-10 text-gray-500"> 
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"> 
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M12 7a4 4 0 110 8 4 4 0 010-8z"></path>
                    </svg> 
                    <h3 class="mt-2 text-lg font-medium text-gray-900">
                        @if($searchTerm ?? null)
                            Tidak Ada Pemateri Ditemukan
                        @else
                            Belum Ada Master Pemateri
                        @endif
                    </h3> 
                    <p class="mt-1 text-sm">
                        @if($searchTerm ?? null)
                            Coba kata kunci lain atau <a href="{{ route('master-pemateri.index') }}" class="text-blue-600 hover:underline">lihat semua pemateri</a>.
                        @else
                            Mulai dengan menambahkan master pemateri baru.
                        @endif
                    </p> 
                </div> 
            @else 
                <div class="overflow-x-auto rounded-lg border">  
                    <table class="min-w-full w-full">  
                        <thead class="bg-gray-50"> 
                            <tr> 
                                <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-gray-600">No</th> 
                                <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Nama Pemateri</th> 
                                <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Email</th> 
                                <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-gray-600">No. HP</th> 
                                <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Tipe</th> 
                                <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Perusahaan/Instansi</th> 
                                <th scope="col" class="px-6 py-3 text-center text-sm font-semibold text-gray-600">Aksi</th> 
                            </tr> 
                        </thead> 
                        <tbody class="divide-y divide-gray-200">  
                            @foreach ($pemateriList as $index => $pemateri)  
                                @php 
                                    $pemateri = (object) $pemateri; 
                                    $pemateriId = $pemateri->id_pemateri ?? $pemateri->ID_PEMATERI ?? null;
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors duration-150"> 
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $pemateriList->firstItem() + $index }}</td> 
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800">{{ $pemateri->nama_pemateri ?? '-' }}</td> 
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $pemateri->email ?? '-' }}</td> 
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $pemateri->no_hp ?? '-' }}</td> 
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            {{ ($pemateri->tipe_pemateri ?? 'Eksternal') === 'Internal' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                            {{ $pemateri->tipe_pemateri ?? 'Eksternal' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $pemateri->nama_perusahaan_display ?? '-' }}</td> 
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center"> 
                                        <div class="flex items-center justify-center space-x-1"> 
                                            @if($pemateriId) 
                                                {{-- Tombol Edit (Contoh, bisa diaktifkan jika ada route dan controller method) --}}
                                                {{-- <a href="{{ route('master-pemateri.edit', $pemateriId) }}" class="text-yellow-600 hover:text-yellow-800 p-1 rounded-md hover:bg-yellow-100" title="Edit Pemateri"> 
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg> 
                                                </a> --}}
                                                
                                                {{-- Tombol Delete AKTIF --}}
                                                <form action="{{ route('master-pemateri.destroy', $pemateriId) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pemateri \'{{ addslashes($pemateri->nama_pemateri ?? 'ini') }}\'? Aksi ini tidak dapat dibatalkan.');" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800 p-1 rounded-md hover:bg-red-100" title="Hapus Pemateri">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </form>
                                            @else 
                                                <span class="text-xs text-red-400">N/A (ID Error)</span> 
                                            @endif 
                                        </div> 
                                    </td> 
                                </tr> 
                            @endforeach 
                        </tbody>  
                    </table> 
                </div> 
                <div class="mt-6"> 
                    @if ($pemateriList instanceof \Illuminate\Pagination\LengthAwarePaginator && $pemateriList->hasPages())
                        {{ $pemateriList->appends(request()->query())->links('vendor.pagination.tailwind') }}
                    @endif
                </div>  
            @endif 
        </div>  
    </div> 
</div> 
@endsection

@push('scripts')
{{-- <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script> --}}
@endpush
