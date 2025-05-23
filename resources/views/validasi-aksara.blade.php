@extends('layouts.app')

@section('title', 'Validasi Aksara Dinamika')
@section('page_title', 'Validasi Aksara Dinamika')

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
                        {{-- PERUBAHAN: Judul kolom diubah menjadi Pengarang --}}
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
                        {{-- PERUBAHAN: Menampilkan data Pengarang --}}
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
</div>

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
