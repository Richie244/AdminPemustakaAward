@extends('layouts.app')

@section('title', 'Validasi Aksara Dinamika')
@section('page_title', 'Validasi Aksara Dinamika')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
{{-- Initialize Alpine.js data context on a parent element --}}
<div class="bg-white p-6 shadow-lg rounded-lg max-w-7xl mx-auto" x-data="{ showAksaraReportModal: false }">
    <div class="flex flex-col md:flex-row justify-between md:items-center mb-6 space-y-4 md:space-y-0">
        <h2 class="text-2xl font-bold text-gray-800">Validasi Aksara Dinamika</h2>
        
        <div class="flex flex-col md:flex-row md:items-center md:space-x-4 space-y-4 md:space-y-0">
            {{-- Formulir Pencarian --}}
            <form method="GET" action="{{ route('validasi.aksara.index') }}" class="flex items-center">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Cari Nama Buku / Pengirim / NIM..." 
                    value="{{ rawurldecode(request('search', '')) }}" 
                    class="border rounded-l-lg px-4 py-2 w-64 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-300"
                >
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-r-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300">
                    Cari
                </button>
                 @if(request('search'))
                    <a href="{{ route('validasi.aksara.index', ['status' => request('status')]) }}" class="ml-2 text-sm text-gray-500 hover:text-gray-700" title="Hapus Pencarian">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                @endif
            </form>

            {{-- Tombol Report PDF Aksara Dinamika --}}
            <button @click="showAksaraReportModal = true"
               class="bg-red-500 hover:bg-red-600 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg flex items-center gap-2 transition-all duration-150 ease-in-out transform hover:scale-105 text-sm sm:text-base w-full md:w-auto justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3v-6m-1.5-9H5.625a1.875 1.875 0 00-1.875 1.875v17.25a1.875 1.875 0 001.875 1.875h12.75a1.875 1.875 0 001.875-1.875V11.25a9 9 0 00-9-9z"></path></svg>
                Report PDF
            </button>

            {{-- Dropdown Filter Status --}}
            <select 
                id="statusFilterSelect"
                onchange="handleStatusFilterChange(this)"
                class="border rounded-lg px-4 py-2 w-full md:w-auto bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-300"
            >
                <option value="">Semua Status</option> 
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu Validasi</option>
                <option value="diterima" {{ request('status') === 'diterima' ? 'selected' : '' }}>Diterima</option>
                <option value="ditolak" {{ request('status') === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
            </select>
        </div>
    </div>

    {{-- (Notifikasi dan tabel tetap sama) --}}
    @if(session('success'))
        <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md" role="alert">
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

    @if($submissions->isEmpty())
        <div class="text-center py-8 text-gray-500">
            Tidak ada data yang tersedia @if(request('search')) untuk pencarian "{{ rawurldecode(request('search','')) }}"@endif @if(request('status')) dengan status "{{ request('status') }}"@endif.
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">No</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Nama Buku</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Pengarang</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Pengirim</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($submissions as $key => $item) 
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $submissions->firstItem() + $key }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $item->JUDUL ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $item->PENGARANG ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $item->NAMA ?? ($item->NIM ?? '-') }}</td>
                        <td class="px-6 py-4">
                            @php
                                $statusValue = strtolower($item->STATUS ?? 'pending');
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                {{ $statusValue === 'pending' ? 'bg-yellow-100 text-yellow-700' : 
                                  ($statusValue === 'diterima' ? 'bg-green-100 text-green-700' : 
                                  ($statusValue === 'ditolak' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700')) }}">
                                {{ $statusValue === 'pending' ? 'Menunggu' : 
                                  ($statusValue === 'diterima' ? 'Diterima' : 
                                  ($statusValue === 'ditolak' ? 'Ditolak' : 'N/A')) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('validasi.aksara.detail', ['id' => $item->id]) }}" 
                               class="bg-blue-100 text-blue-600 px-3 py-1.5 rounded-md text-sm hover:bg-blue-200 transition duration-150">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="mt-6"> 
            {{ $submissions->links('vendor.pagination.tailwind') }}
        </div>
    @endif

    {{-- Modal Report PDF Aksara --}}
    <div x-show="showAksaraReportModal" 
        x-cloak 
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/60 flex items-center justify-center p-4 z-50">
        <div @click.outside="showAksaraReportModal = false" 
             x-transition
             class="bg-white rounded-xl p-6 w-full max-w-lg shadow-xl max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-xl font-semibold text-gray-800">Filter Laporan Aksara Dinamika</h3>
                <button @click="showAksaraReportModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form action="{{ route('validasi.aksara.report.pdf') }}" method="GET" target="_blank">
                 <input type="hidden" name="search" value="{{ rawurldecode(request('search', '')) }}">
                <div class="space-y-4">
                    <div>
                        <label for="start_date_validasi_report" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai Validasi/Submit</label> {{-- ID diubah untuk unik --}}
                        <input type="date" name="start_date_validasi" id="start_date_validasi_report"
                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-200 py-2 px-3 text-sm">
                    </div>
                    <div>
                        <label for="end_date_validasi_report" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai Validasi/Submit</label> {{-- ID diubah untuk unik --}}
                        <input type="date" name="end_date_validasi" id="end_date_validasi_report"
                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-200 py-2 px-3 text-sm">
                    </div>
                    <div>
                        <label for="status_validasi_report" class="block text-sm font-medium text-gray-700 mb-1">Status Validasi</label> {{-- ID diubah untuk unik --}}
                        <select name="status_validasi" id="status_validasi_report"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-200 py-2 px-3 text-sm">
                            <option value="">Semua Status</option>
                            <option value="pending">Menunggu Validasi</option>
                            <option value="diterima">Diterima</option>
                            <option value="ditolak">Ditolak</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="showAksaraReportModal = false" 
                            class="px-5 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-5 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none transition-colors">
                        Cetak PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
{{-- Script untuk handleStatusFilterChange sudah ada di bawah --}}
{{-- AlpineJS sudah di-include di layout utama, jika belum, tambahkan: --}}
{{-- <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script> --}}
@endpush

<script>
    function handleStatusFilterChange(selectElement) {
        const currentUrl = new URL(window.location.href);
        const searchInput = document.querySelector('input[name="search"]');
        const searchTerm = searchInput ? searchInput.value : (currentUrl.searchParams.get('search') || ''); 
        
        let params = new URLSearchParams();
        if (selectElement.value) { 
            params.set('status', selectElement.value);
        }
        if (searchTerm) {
            params.set('search', searchTerm); 
        }
        
        let baseUrl = '{{ route("validasi.aksara.index") }}'; 
        const queryString = params.toString();
        const newUrl = baseUrl + (queryString ? '?' + queryString.replace(/%20/g, '+') : '');
        
        window.location.href = newUrl; 
    }
</script>
@endsection