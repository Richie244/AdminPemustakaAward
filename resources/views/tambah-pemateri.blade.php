{{-- resources/views/tambah-pemateri.blade.php --}}

@extends('layouts.app')

@section('title', 'Tambah Master Pemateri')
@section('page_title', 'Tambah Master Pemateri Baru')

@push('styles')
{{-- Style tambahan jika ada --}}
@endpush

@section('content')
<div class="min-h-screen pt-2 pb-8 px-2">
    <div class="bg-white p-6 shadow-lg rounded-xl max-w-2xl mx-auto">
        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
            <h1 class="text-2xl font-bold text-gray-800">Form Tambah Pemateri</h1>
            <a href="{{ route('master-pemateri.index') }}" class="text-purple-600 hover:text-purple-800 transition-colors">
                &larr; Kembali ke Daftar Pemateri
            </a>
        </div>

        @if(session('error'))
            <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
                <p class="font-bold">Gagal!</p>
                <p>{{ session('error') }}</p>
            </div>
        @endif

        @if($error_message_perusahaan ?? null)
            <div class="mb-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-md" role="alert">
                <p class="font-bold">Peringatan:</p>
                <p>{{ $error_message_perusahaan }}</p>
            </div>
        @endif

        <form action="{{ route('master-pemateri.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 gap-6">
                {{-- Nama Pemateri --}}
                <div>
                    <label for="nama_pemateri" class="block text-sm font-medium text-gray-700 mb-1">Nama Pemateri <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_pemateri" id="nama_pemateri" value="{{ old('nama_pemateri') }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 sm:text-sm @error('nama_pemateri') border-red-500 @enderror"
                           required>
                    @error('nama_pemateri')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email Pemateri --}}
                <div>
                    <label for="email_pemateri" class="block text-sm font-medium text-gray-700 mb-1">Email Pemateri</label>
                    <input type="email" name="email_pemateri" id="email_pemateri" value="{{ old('email_pemateri') }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 sm:text-sm @error('email_pemateri') border-red-500 @enderror">
                    @error('email_pemateri')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- No. HP Pemateri --}}
                <div>
                    <label for="no_hp_pemateri" class="block text-sm font-medium text-gray-700 mb-1">No. HP Pemateri</label>
                    <input type="text" name="no_hp_pemateri" id="no_hp_pemateri" value="{{ old('no_hp_pemateri') }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 sm:text-sm @error('no_hp_pemateri') border-red-500 @enderror">
                    @error('no_hp_pemateri')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Pilihan Perusahaan --}}
                <div>
                    <label for="id_perusahaan" class="block text-sm font-medium text-gray-700 mb-1">Asal Perusahaan/Instansi <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-2 mt-1">
                        {{-- Atribut style="width: 100%;" ditambahkan untuk kompatibilitas Select2 --}}
                        <select name="id_perusahaan" id="id_perusahaan" style="width: 100%;"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 sm:text-sm @error('id_perusahaan') border-red-500 @enderror"
                                required {{ ($perusahaanList && $perusahaanList->isEmpty() && $error_message_perusahaan) || ($error_message_perusahaan && !$perusahaanList) ? 'disabled' : '' }}>
                            <option value="">-- Pilih Perusahaan --</option>
                            @if ($perusahaanList && $perusahaanList->isNotEmpty())
                                @foreach ($perusahaanList as $perusahaan)
                                    <option value="{{ $perusahaan->id_perusahaan }}" {{ old('id_perusahaan') == $perusahaan->id_perusahaan ? 'selected' : '' }}>
                                        {{ $perusahaan->nama_perusahaan }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <a href="{{ route('master-perusahaan.create') }}" target="_blank"
                           class="flex-shrink-0 bg-green-500 hover:bg-green-600 text-white p-2.5 rounded-md shadow-sm transition-colors"
                           title="Tambah Perusahaan Baru">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </a>
                    </div>
                    @if (($perusahaanList && $perusahaanList->isEmpty() && $error_message_perusahaan) || ($error_message_perusahaan && !$perusahaanList))
                         <p class="mt-1 text-xs text-yellow-700">Tidak dapat memuat daftar perusahaan. Pilihan dinonaktifkan.</p>
                    @elseif ($perusahaanList && $perusahaanList->isEmpty() && !$error_message_perusahaan)
                         <p class="mt-1 text-xs text-gray-500">Tidak ada data perusahaan tersedia. Gunakan tombol `+` untuk menambah.</p>
                    @endif
                    @error('id_perusahaan')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            <div class="mt-8 pt-5 border-t border-gray-200">
                <div class="flex justify-end gap-3">
                    <a href="{{ route('master-pemateri.index') }}"
                       class="bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-4 rounded-md shadow-sm text-sm font-medium transition-colors">
                        Batal
                    </a>
                    <button type="submit"
                            class="bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-md shadow-sm text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                        Simpan Pemateri
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
{{-- Script untuk mengaktifkan Select2 --}}
<script>
    $(document).ready(function() {
        $('#id_perusahaan').select2({
            placeholder: "-- Cari dan Pilih Perusahaan --",
            allowClear: true
        });
    });
</script>
@endpush