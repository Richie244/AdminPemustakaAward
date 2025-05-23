@extends('layouts.app')

@section('title', 'Manajemen Periode Award')

@section('content')
{{-- Background halaman utama --}}
<div class="bg-gray-100 min-h-screen py-8 px-4">
    {{-- Container utama --}}
    <div class="max-w-7xl mx-auto"> {{-- Lebar kontainer diperbesar untuk mengakomodasi filter --}}
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-4 sm:mb-0">Manajemen Periode Award</h1>
            <a href="{{ route('periode.create') }}"
               class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-6 py-3 rounded-lg shadow-md hover:shadow-lg flex items-center gap-2 transition-all duration-150 ease-in-out transform hover:scale-105">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Tambah Periode Baru
            </a>
        </div>

        {{-- Form Pencarian dan Filter --}}
        <div class="mb-8 p-6 bg-white rounded-xl shadow-md">
            <form action="{{ route('periode.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Cari Periode</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nama periode..."
                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                </div>
                <div>
                    <label for="sort_by" class="block text-sm font-medium text-gray-700 mb-1">Urutkan Berdasarkan</label>
                    <select name="sort_by" id="sort_by"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                        <option value="tgl_mulai_desc" {{ request('sort_by') == 'tgl_mulai_desc' ? 'selected' : '' }}>Tanggal Mulai (Terbaru)</option>
                        <option value="tgl_mulai_asc" {{ request('sort_by') == 'tgl_mulai_asc' ? 'selected' : '' }}>Tanggal Mulai (Terlama)</option>
                        <option value="nama_asc" {{ request('sort_by') == 'nama_asc' ? 'selected' : '' }}>Nama Periode (A-Z)</option>
                        <option value="nama_desc" {{ request('sort_by') == 'nama_desc' ? 'selected' : '' }}>Nama Periode (Z-A)</option>
                    </select>
                </div>
                <div class="flex items-end space-x-2">
                    <button type="submit"
                            class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg shadow-sm font-medium text-sm transition-colors duration-150 flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        Cari
                    </button>
                    <a href="{{ route('periode.index') }}"
                       class="w-full md:w-auto bg-gray-200 hover:bg-gray-300 text-gray-700 px-5 py-2.5 rounded-lg shadow-sm font-medium text-sm transition-colors duration-150 flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m-15.357-2a8.001 8.001 0 0015.357 2M9 15h4.581V9"></path></svg>
                        Reset
                    </a>
                </div>
            </form>
        </div>


        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Berhasil!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if($error ?? null) {{-- Menggunakan null coalescing operator untuk variabel $error --}}
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">{{ $error }}</span>
            </div>
        @endif

        @if (empty($periodes) && !($error ?? null))
            <div class="text-center bg-white p-10 rounded-xl shadow-lg">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">
                    @if(request('search'))
                        Tidak ada periode yang cocok dengan pencarian "{{ request('search') }}".
                    @else
                        Belum Ada Periode
                    @endif
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    @if(request('search'))
                        Coba kata kunci lain atau reset pencarian.
                    @else
                        Mulai dengan menambahkan periode kegiatan baru.
                    @endif
                </p>
            </div>
        @elseif(!empty($periodes))
            <div class="bg-white shadow-xl rounded-2xl overflow-x-auto"> {{-- Tambah overflow-x-auto untuk tabel responsif --}}
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Periode</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Mulai</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Selesai</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($periodes as $periodeItem)
                            @php 
                                $periode = is_array($periodeItem) ? (object)$periodeItem : $periodeItem; 
                                $periodeId = $periode->ID_PERIODE ?? $periode->id_periode ?? $periode->id ?? null;
                            @endphp
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $periodeId ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $periode->NAMA_PERIODE ?? $periode->nama_periode ?? $periode->nama ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ isset($periode->TGL_MULAI) ? \Carbon\Carbon::parse($periode->TGL_MULAI)->translatedFormat('d M Y') : (isset($periode->tgl_mulai) ? \Carbon\Carbon::parse($periode->tgl_mulai)->translatedFormat('d M Y') : 'N/A') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ isset($periode->TGL_SELESAI) ? \Carbon\Carbon::parse($periode->TGL_SELESAI)->translatedFormat('d M Y') : (isset($periode->tgl_selesai) ? \Carbon\Carbon::parse($periode->tgl_selesai)->translatedFormat('d M Y') : 'N/A') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    @if($periodeId)
                                    <a href="{{ route('periode.show', ['id' => $periodeId]) }}" 
                                       class="text-indigo-600 hover:text-indigo-900 transition-colors duration-150">
                                       Detail
                                    </a>
                                    @else
                                    <span class="text-gray-400">Detail (ID Error)</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            {{-- Pagination Links --}}
            @if ($periodes instanceof \Illuminate\Pagination\LengthAwarePaginator && $periodes->hasPages())
                <div class="mt-8">
                    {{ $periodes->appends(request()->query())->links() }} {{-- Menambahkan query string saat paginasi --}}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
