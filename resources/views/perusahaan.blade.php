@extends('layouts.app')

@section('title', 'Master Perusahaan')
@section('page_title', 'Manajemen Master Perusahaan')

@push('styles')
{{-- Tambahkan style khusus jika diperlukan --}}
@endpush

@section('content')
<div class="min-h-screen pt-2 pb-8 px-2">
    <div>
        <div class="bg-white p-6 shadow-lg rounded-xl overflow-hidden">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-6 pb-4 border-b border-gray-200 gap-4">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Daftar Master Perusahaan</h1>
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                    {{-- Formulir Pencarian --}}
                    <form action="{{ route('master-perusahaan.index') }}" method="GET" class="flex w-full sm:w-auto">
                        <input type="text" name="search" placeholder="Cari nama, alamat, kontak..."
                               value="{{ $searchTerm ?? '' }}"
                               class="border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-l-md shadow-sm text-sm py-2 px-3 w-full sm:min-w-[200px] md:min-w-[250px]">
                        <button type="submit"
                                aria-label="Cari"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-r-md text-sm inline-flex items-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </button>
                        @if($searchTerm ?? null)
                        <a href="{{ route('master-perusahaan.index') }}" class="ml-2 text-sm text-blue-600 hover:text-blue-800 flex items-center" title="Hapus Filter Pencarian">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                        @endif
                    </form>

                    <a href="{{ route('master-perusahaan.create') }}"
                       class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg flex items-center gap-2 transition-all duration-150 ease-in-out transform hover:scale-105 text-sm sm:text-base w-full sm:w-auto justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Tambah Perusahaan
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
            @if($error_message ?? null)
                 <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
                    <p class="font-bold">Error!</p>
                    <p>{{ $error_message }}</p>
                </div>
            @endif


            @if(!isset($perusahaanList) || $perusahaanList->isEmpty())
                <div class="text-center py-10 text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    <h3 class="mt-2 text-lg font-medium text-gray-900">
                        @if($searchTerm ?? null)
                            Tidak Ada Perusahaan Ditemukan
                        @else
                            Belum Ada Master Perusahaan
                        @endif
                    </h3>
                    <p class="mt-1 text-sm">
                        @if($searchTerm ?? null)
                            Coba kata kunci lain atau <a href="{{ route('master-perusahaan.index') }}" class="text-blue-600 hover:underline">lihat semua perusahaan</a>.
                        @else
                            Mulai dengan menambahkan master perusahaan baru.
                        @endif
                    </p>
                </div>
            @else
                <div class="overflow-x-auto rounded-lg border">
                    <table class="min-w-full w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Perusahaan</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alamat</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kota</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telepon</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Person</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($perusahaanList as $index => $perusahaan)
                                @php
                                    $perusahaan = (object) $perusahaan;
                                    $perusahaanId = $perusahaan->id_perusahaan ?? $perusahaan->ID_PERUSAHAAN ?? $perusahaan->id ?? null;
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $perusahaanList->firstItem() + $index }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800">{{ $perusahaan->nama_perusahaan ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate" title="{{ $perusahaan->alamat_perusahaan ?? '' }}">{{ \Illuminate\Support\Str::limit($perusahaan->alamat_perusahaan ?? '-', 40) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $perusahaan->kota_perusahaan ?? '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $perusahaan->email_perusahaan ?? '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $perusahaan->telp_perusahaan ?? '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $perusahaan->contact_person_perusahaan ?? '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                        <div class="flex items-center justify-center space-x-1">
                                            @if($perusahaanId && $perusahaanId != 1) {{-- Tombol delete tidak muncul untuk ID 1 --}}
                                                <form action="{{ route('master-perusahaan.destroy', $perusahaanId) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus perusahaan \'{{ addslashes($perusahaan->nama_perusahaan ?? 'ini') }}\'? Ini juga dapat mempengaruhi data pemateri yang terkait.');" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800 p-1 rounded-md hover:bg-red-100" title="Hapus Perusahaan">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </form>
                                            @elseif ($perusahaanId == 1)
                                                <span class="text-xs text-gray-400 italic">(Default)</span>
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
                    @if ($perusahaanList instanceof \Illuminate\Pagination\LengthAwarePaginator && $perusahaanList->hasPages())
                        {{ $perusahaanList->appends(request()->query())->links('vendor.pagination.tailwind') }}
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Script tambahan jika ada --}}
@endpush