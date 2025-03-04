@extends('layouts.app')

@section('content')
    <div class="bg-white p-6 shadow-lg rounded-lg w-3/4 mx-auto">
        <h2 class="text-2xl font-bold mb-4">Setting Validasi Aksara Dinamika</h2>

        <!-- Menunggu Validasi -->
        <h3 class="text-lg font-semibold mb-2">Menunggu Validasi</h3>
        <div class="space-y-3">
            @foreach ($menungguValidasi as $item)
                <div class="bg-white shadow-md p-4 rounded-md flex justify-between items-center relative border-b-4 border-gray-500 cursor-pointer"
                    onclick="window.location.href='{{ route('aksara.detail', $item->nim) }}'">
                    
                    <div>
                        <p class="font-bold">{{ $item->judul }}</p>
                        <p class="text-gray-600 text-sm">{{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y') }}</p>
                    </div>

                    <div class="text-right">
                        <p class="text-sm font-semibold">{{ $item->nama }}</p>
                        <p class="text-xs text-gray-600">{{ $item->nim }}</p>
                        <p class="text-xs text-gray-600">{{ $item->email }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Sudah Divalidasi -->
        <h3 class="text-lg font-semibold mt-6 mb-2">Sudah Divalidasi</h3>
        <div class="space-y-3">
            @foreach ($sudahValidasi as $item)
                <div class="bg-white shadow-md p-4 rounded-md flex justify-between items-center relative 
                    {{ $item->status == 'valid' ? 'border-b-4 border-green-500' : 'border-b-4 border-red-500' }}">
                    
                    <div>
                        <p class="font-bold">{{ $item->judul }}</p>
                        <p class="text-gray-600 text-sm">{{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y') }}</p>
                    </div>

                    <div class="text-right">
                        <p class="text-sm font-semibold">{{ $item->nama }}</p>
                        <p class="text-xs text-gray-600">{{ $item->nim }}</p>
                        <p class="text-xs text-gray-600">{{ $item->email }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
