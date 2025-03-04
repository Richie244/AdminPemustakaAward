@extends('layouts.app')

@section('title', 'Kegiatan')

@section('content')
<h2 class="text-2xl font-bold mb-4">Kegiatan</h2>
<div class="bg-white p-5 shadow-lg rounded-lg">
    <a href="{{ route('kegiatan.tambah') }}" class="bg-blue-500 text-white px-4 py-2 rounded mb-4 inline-block">Tambah</a>
    
    <table class="w-full border-collapse border border-gray-300">
        <thead>
            <tr class="bg-gray-100">
                <th class="border p-2">Judul Kegiatan</th>
                <th class="border p-2">Tanggal Kegiatan</th>
                <th class="border p-2">Jam Kegiatan</th>
                <th class="border p-2">Pemateri</th>
                <th class="border p-2">Media/Lokasi Kegiatan</th>
                <th class="border p-2">Keterangan</th>
                <th class="border p-2">Bobot Nilai</th>
                <th class="border p-2 text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($kegiatan as $kegiatan)
                <tr class="text-center">
                    <td class="border p-2">{{ $kegiatan['judul'] }}</td>
                    <td class="border p-2">{{ $kegiatan['tanggal'] }}</td>
                    <td class="border p-2">{{ $kegiatan['jam'] }}</td>
                    <td class="border p-2">{{ $kegiatan['pemateri'] }}</td>
                    <td class="border p-2">{{ $kegiatan['lokasi'] }}</td>
                    <td class="border p-2">{{ $kegiatan['keterangan'] }}</td>
                    <td class="border p-2">{{ $kegiatan['bobot'] }}</td>
                    <td class="border p-2">
                        <div class="flex border border-gray-500 rounded-lg overflow-hidden w-48 mx-auto">
                            <!-- Edit -->
                            <a href="{{ url('/kegiatan/edit/'.$kegiatan['id']) }}" class="w-12 h-12 flex items-center justify-center border-r border-gray-500 hover:bg-gray-200">
                                <img src="{{ asset('assets/icons/Edit.png') }}" alt="Edit" class="w-6 h-6">
                            </a>
                            <!-- Hapus -->
                            <a href="{{ url('/kegiatan/hapus/'.$kegiatan['id']) }}" class="w-12 h-12 flex items-center justify-center border-r border-gray-500 hover:bg-gray-200">
                                <img src="{{ asset('assets/icons/Trash.png') }}" alt="Hapus" class="w-6 h-6">
                            </a>
                            <!-- Daftar Hadir -->
                            <a href="{{ url('/kegiatan/daftar-hadir/'.$kegiatan['id']) }}" class="w-12 h-12 flex items-center justify-center border-r border-gray-500 hover:bg-gray-200">
                                <img src="{{ asset('assets/icons/hadir.png') }}" alt="Daftar Hadir" class="w-6 h-6">
                            </a>
                            <!-- Salin'id -->
                            <button onclick="copyToClipboard('{{ $kegiatan['id'] }}')" class="w-12 h-12 flex items-center justify-center hover:bg-gray-200">
                                <img src="{{ asset('assets/icons/Copy.png') }}" alt="Salin'id" class="w-6 h-6">
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
    function copyToClipboard'id) {
        navigator.clipboard.writeText'id);
        alert('id Kegiatan ' +'id + ' telah disalin!');
    }
</script>

@endsection
