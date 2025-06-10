@extends('layouts.app')

@section('title', 'Tambah Master Perusahaan')
@section('page_title', 'Tambah Master Perusahaan Baru')

@push('styles')
{{-- Style tambahan jika ada --}}
@endpush

@section('content')
<div class="min-h-screen pt-2 pb-8 px-2">
    <div class="bg-white p-6 shadow-lg rounded-xl max-w-2xl mx-auto">
        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
            <h1 class="text-2xl font-bold text-gray-800">Form Tambah Perusahaan</h1>
            <a href="{{ route('master-perusahaan.index') }}" class="text-green-600 hover:text-green-800 transition-colors">
                &larr; Kembali ke Daftar Perusahaan
            </a>
        </div>

        @if(session('error'))
            <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
                <p class="font-bold">Gagal!</p>
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <form action="{{ route('master-perusahaan.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Nama Perusahaan --}}
                <div class="md:col-span-2">
                    <label for="nama" class="block text-sm font-medium text-gray-700 mb-1">Nama Perusahaan <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" id="nama" value="{{ old('nama') }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm @error('nama') border-red-500 @enderror"
                           required>
                    @error('nama')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Alamat Perusahaan --}}
                <div class="md:col-span-2">
                    <label for="alamat" class="block text-sm font-medium text-gray-700 mb-1">Alamat Perusahaan</label>
                    <textarea name="alamat" id="alamat" rows="3"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm @error('alamat') border-red-500 @enderror"
                              >{{ old('alamat') }}</textarea>
                    @error('alamat')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Kota Perusahaan (Dropdown) --}}
                <div>
                    <label for="kota" class="block text-sm font-medium text-gray-700 mb-1">Kota Perusahaan</label>
                    <select name="kota" id="kota"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm @error('kota') border-red-500 @enderror">
                        <option value="">-- Pilih Kota --</option>
                        
                        @if(isset($kotaList) && $kotaList->isNotEmpty())
                            @foreach($kotaList as $kota)
                                <option value="{{ $kota->id }}" {{ old('kota') == $kota->id ? 'selected' : '' }}>
                                    {{ $kota->nama }}
                                </option>
                            @endforeach
                        @else
                            <option value="" disabled>Gagal memuat daftar kota</option>
                        @endif
                    </select>
                    @if($error_message_kota ?? null)
                        <p class="mt-1 text-xs text-red-600">{{ $error_message_kota }}</p>
                    @endif
                    @error('kota')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email Perusahaan --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Perusahaan</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm @error('email') border-red-500 @enderror"
                           placeholder="Contoh: info@perusahaan.com">
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Telepon Perusahaan --}}
                <div>
                    <label for="telp" class="block text-sm font-medium text-gray-700 mb-1">Telepon Perusahaan</label>
                    <input type="text" name="telp" id="telp" value="{{ old('telp') }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm @error('telp') border-red-500 @enderror"
                           placeholder="Contoh: 031-xxxxxxx">
                    @error('telp')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Contact Person --}}
                <div>
                    <label for="kontak" class="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
                    <input type="text" name="kontak" id="kontak" value="{{ old('kontak') }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm @error('kontak') border-red-500 @enderror"
                           placeholder="Contoh: Bpk. Budi">
                    @error('kontak')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-8 pt-5 border-t border-gray-200">
                <div class="flex justify-end gap-3">
                    <a href="{{ route('master-perusahaan.index') }}"
                       class="bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-4 rounded-md shadow-sm text-sm font-medium transition-colors">
                        Batal
                    </a>
                    <button type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-md shadow-sm text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Simpan Perusahaan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
{{-- Script tambahan jika ada, misalnya untuk select2 jika daftar kota banyak --}}
@endpush