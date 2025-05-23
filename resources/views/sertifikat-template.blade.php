@extends('layouts.app')

@section('title', 'Kelola Template Sertifikat Global')
@section('page_title', 'Template Sertifikat Global')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    {{-- Tombol Kembali dan Judul Halaman --}}
    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-200">
        <a href="{{ route('kegiatan.index') }}" class="text-blue-600 hover:text-blue-800 inline-flex items-center p-2 rounded-full hover:bg-blue-50 transition-colors duration-150" title="Kembali ke Daftar Kegiatan">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Template Sertifikat</h1>
    </div>

    {{-- Notifikasi Sukses/Error --}}
    @if(session('success'))
        <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md shadow" role="alert">
            <p class="font-bold">Sukses!</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md shadow" role="alert">
            <p class="font-bold">Error!</p>
            <p>{{ session('error') }}</p>
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md shadow" role="alert">
            <p class="font-bold">Oops! Ada yang salah:</p>
            <ul class="mt-1 list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Card untuk Kelola Template Global --}}
    <div class="bg-white p-6 shadow-xl rounded-xl overflow-hidden">
        
        @if($currentTemplate && isset($currentTemplate->nama_file))
            {{-- Tampilkan Template Saat Ini --}}
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Template Saat Ini:</h2>
            <div class="mb-6 p-4 border rounded-lg bg-gray-50">
                <p class="text-sm text-gray-600">Nama File: 
                    <a href="{{ Storage::url('templates_sertifikat/' . $currentTemplate->nama_file) }}" 
                       target="_blank" 
                       class="text-blue-600 hover:underline font-medium">
                        {{ $currentTemplate->nama_file }}
                    </a>
                </p>
                {{-- Anda bisa menambahkan preview gambar jika file adalah gambar --}}
                @if(Str::endsWith(strtolower($currentTemplate->nama_file), ['.jpg', '.jpeg', '.png', '.gif']))
                    <div class="mt-4">
                        <img src="{{ Storage::url('templates_sertifikat/' . $currentTemplate->nama_file) }}" alt="Preview Template" class="max-w-xs max-h-48 border rounded">
                    </div>
                @endif
                 <p class="text-xs text-gray-500 mt-2">
                    Diunggah pada: {{ $currentTemplate->display_upload_date ?? '-' }}
                </p>
            </div>
            <hr class="my-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Ganti Template Sertifikat:</h3>
            <form action="{{ route('sertifikat-templates.store') }}" method="POST" enctype="multipart/form-data"> {{-- Atau route('sertifikat-templates.update', $currentTemplate->id_sertifikat) jika Anda punya metode update --}}
                @csrf
                {{-- Jika menggunakan metode UPDATE, tambahkan @method('PUT') --}}
                {{-- @method('PUT') --}}
                
                <div class="mb-4">
                    <label for="file_template_update" class="block text-sm font-medium text-gray-700 mb-1">Pilih File Template Baru (JPG/PNG)</label>
                    <input type="file" name="file_template" id="file_template_update" 
                           required
                           accept=".jpg,.jpeg,.png"
                           class="block w-full text-sm text-gray-500
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded-md file:border-0
                                  file:text-sm file:font-semibold
                                  file:bg-blue-50 file:text-blue-700
                                  hover:file:bg-blue-100
                                  border border-gray-300 rounded-md p-1 cursor-pointer">
                    @error('file_template')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                 {{-- Nama Template mungkin tidak diperlukan jika hanya ada satu template global --}}
                 {{-- Kecuali jika Anda ingin tetap memberi nama pada versi template ini --}}
                <div class="mb-4">
                    <label for="nama_template_update" class="block text-sm font-medium text-gray-700 mb-1">Nama Template (Opsional, untuk referensi)</label>
                    <input type="text" name="nama_template" id="nama_template_update" 
                           value="{{ old('nama_template', $currentTemplate->nama_template_display ?? 'Template Sertifikat') }}"
                           class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm w-full py-2 px-3"
                           placeholder="Misal: Template Global v2">
                    @error('nama_template')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6 text-right">
                    <button type="submit"
                            class="bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white px-6 py-2.5 rounded-lg shadow-md hover:shadow-lg flex items-center gap-2 transition-all duration-150 ease-in-out transform hover:scale-105 text-sm sm:text-base inline-flex">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Ganti & Unggah Template
                    </button>
                </div>
            </form>

        @else
            {{-- Form untuk Unggah Template Global Pertama Kali --}}
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Unggah Template Sertifikat</h2> 
            <p class="text-sm text-gray-600 mb-6">Belum ada template sertifikatyang diunggah. Silakan unggah template pertama Anda.</p>
            
            <form action="{{ route('sertifikat-templates.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label for="nama_template_new" class="block text-sm font-medium text-gray-700 mb-1">Nama Template (untuk referensi Anda)</label>
                    <input type="text" name="nama_template" id="nama_template_new" 
                           value="{{ old('nama_template', 'Template Sertifikat') }}"
                           required
                           class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm w-full py-2 px-3"
                           placeholder="Misal: Template Sertifikat">
                    @error('nama_template')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="file_template_new" class="block text-sm font-medium text-gray-700 mb-1">Pilih File Template (JPG/PNG)</label>
                    <input type="file" name="file_template" id="file_template_new" 
                           required
                           accept=".jpg,.jpeg,.png"
                           class="block w-full text-sm text-gray-500
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded-md file:border-0
                                  file:text-sm file:font-semibold
                                  file:bg-blue-50 file:text-blue-700
                                  hover:file:bg-blue-100
                                  border border-gray-300 rounded-md p-1 cursor-pointer">
                    @error('file_template')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6 text-right">
                    <button type="submit"
                            class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-6 py-2.5 rounded-lg shadow-md hover:shadow-lg flex items-center gap-2 transition-all duration-150 ease-in-out transform hover:scale-105 text-sm sm:text-base inline-flex">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Unggah Template Sertifikat
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
