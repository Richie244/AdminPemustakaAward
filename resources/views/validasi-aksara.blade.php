@extends('layouts.app')

@section('content')
<div class="bg-white p-6 shadow-lg rounded-lg max-w-7xl mx-auto">
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
                    placeholder="Cari Nama Buku / Pengirim..." 
                    value="{{ rawurldecode(request('search', '')) }}" 
                    class="border rounded-l-lg px-4 py-2 w-64 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-300"
                >
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-r-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300">
                    Cari
                </button>
            </form>

            {{-- Dropdown Filter Status --}}
            <select 
                id="statusFilterSelect"
                onchange="handleStatusFilterChange(this)"
                class="border rounded-lg px-4 py-2 w-full md:w-64 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-300"
            >
                <option value="">Semua Status</option> 
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu Validasi</option>
                <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>Diterima</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
            </select>
        </div>
    </div>

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
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Tanggal</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Pengirim</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($submissions as $key => $item) 
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $submissions->firstItem() + $key }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $item->judul }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $item->nama }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                {{ $item->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 
                                    ($item->status === 'accepted' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700') }}">
                                {{ $item->status === 'pending' ? 'Menunggu' : 
                                    ($item->status === 'accepted' ? 'Diterima' : 'Ditolak') }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('aksara.detail', ['id' => $item->id]) }}" 
                               class="bg-blue-100 text-blue-600 px-3 py-1.5 rounded-md text-sm hover:bg-blue-200 transition duration-150">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        {{-- Pagination --}}
        <div class="mt-6"> 
            {{ $submissions->links('vendor.pagination.tailwind') }}
            
            {{-- PERUBAHAN DI SINI: Baris di bawah ini dihapus/dikomentari --}}
            {{-- 
            <div class="text-sm text-gray-600 mt-2">
                Showing {{ $submissions->firstItem() }} 
                to {{ $submissions->lastItem() }} 
                of {{ $submissions->total() }} results
            </div>
            --}}
        </div>
    @endif
</div>

{{-- Script ditempatkan di akhir section content sebagai workaround --}}
<script>
    console.log('Script block in section content is executing.'); 

    function handleStatusFilterChange(selectElement) {
        console.log('Status dropdown changed. Selected value:', selectElement.value);
        const currentUrl = new URL(window.location.href);
        const searchTerm = currentUrl.searchParams.get('search') || ''; 
        console.log('Current search term from URL (should be decoded):', searchTerm);

        let params = new URLSearchParams();
        if (selectElement.value) { 
            params.set('status', selectElement.value);
        }
        // if (searchTerm) { // Biarkan URLSearchParams menangani encoding awal
        //     params.set('search', searchTerm); 
        // }
        
        let baseUrl = '';
        try {
            baseUrl = JSON.parse('{!! json_encode(route("validasi.aksara.index")) !!}');
            if (!baseUrl || typeof baseUrl !== 'string') {
                console.error('Base URL from route is invalid or not a string. Fallback used. Route output was:', {!! json_encode(route("validasi.aksara.index")) !!});
                baseUrl = '/validasi-aksara'; 
            }
        } catch (e) {
            console.error('Error parsing base URL from route:', e, 'Raw route output:', {!! json_encode(route("validasi.aksara.index")) !!});
            baseUrl = '/validasi-aksara'; 
        }
        
        let queryString = '';
        let finalQueryParts = [];

        if (selectElement.value) {
            finalQueryParts.push('status=' + encodeURIComponent(selectElement.value));
        }
        if (searchTerm) {
            // Pastikan spasi di search term di-encode sebagai '+'
            const encodedSearchTermForPlus = encodeURIComponent(searchTerm).replace(/%20/g, '+');
            finalQueryParts.push('search=' + encodedSearchTermForPlus);
        }
        queryString = finalQueryParts.join('&');
        
        const newUrl = baseUrl + (queryString ? '?' + queryString : '');
        
        console.log('Attempting to redirect to URL (forcing + for spaces in search):', newUrl);

        window.location.href = newUrl; 
    }
</script>
@endsection