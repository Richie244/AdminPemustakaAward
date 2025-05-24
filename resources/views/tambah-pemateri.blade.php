@extends('layouts.app')

@section('title', 'Tambah Master Pemateri Eksternal') {{-- Judul disesuaikan --}}
@section('page_title', 'Tambah Pemateri Eksternal') {{-- Judul disesuaikan --}}

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="min-h-screen py-8 px-4">
    <div class="max-w-4xl mx-auto"> 
        <div class="mb-6">
            <a href="{{ route('master-pemateri.index') }}" 
               class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Daftar Pemateri
            </a>
        </div>

        <div class="bg-white p-6 sm:p-8 shadow-xl rounded-2xl">
            <h2 class="text-3xl font-bold mb-8 text-gray-800 border-b border-gray-200 pb-4">Tambah Pemateri Eksternal Baru</h2>

            @if ($errors->any())
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Oops! Terjadi kesalahan:</strong>
                    <ul class="mt-2 list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
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

            <form action="{{ route('master-pemateri.store') }}" method="POST" class="space-y-6">
                @csrf
                {{-- Input tersembunyi untuk menandakan jenis pemateri adalah eksternal secara default --}}
                <input type="hidden" name="jenis_pemateri" value="eksternal">

                {{-- Informasi Dasar Pemateri --}}
                <div class="p-6 border border-gray-200 rounded-xl bg-gray-50/50">
                    <h3 class="text-lg font-semibold text-gray-700 mb-5">Informasi Pemateri</h3>
                    <div class="space-y-4">
                        <div>
                            <label for="nama_pemateri" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap Pemateri</label>
                            <input type="text" id="nama_pemateri" name="nama_pemateri" value="{{ old('nama_pemateri') }}" required maxlength="100"
                                   placeholder="Masukkan nama lengkap pemateri..."
                                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="email_pemateri" class="block text-sm font-medium text-gray-700 mb-1">Email Pemateri</label>
                                <input type="email" id="email_pemateri" name="email_pemateri" value="{{ old('email_pemateri') }}" maxlength="100"
                                       placeholder="Contoh: email@example.com"
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                            </div>
                            <div>
                                <label for="no_hp_pemateri" class="block text-sm font-medium text-gray-700 mb-1">No. HP Pemateri</label>
                                <input type="tel" id="no_hp_pemateri" name="no_hp_pemateri" value="{{ old('no_hp_pemateri') }}" maxlength="20"
                                       placeholder="Contoh: 081234567890"
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                            </div>
                        </div>
                        {{-- Pilihan Jenis Pemateri Dihilangkan dari Tampilan --}}
                    </div>
                </div>

                {{-- Detail Perusahaan/Instansi (Selalu Tampil) --}}
                <div class="p-6 border border-gray-200 rounded-xl bg-gray-50/50">
                    <h3 class="text-lg font-semibold text-gray-700 mb-5">Detail Perusahaan/Instansi</h3>
                    <p class="text-xs text-gray-500 mb-4">Isi jika pemateri berasal dari perusahaan atau instansi eksternal. Kosongkan jika tidak terafiliasi.</p>
                    <div class="space-y-4">
                        <div>
                            <label for="nama_perusahaan" class="block text-sm font-medium text-gray-700 mb-1">Nama Perusahaan/Instansi <span class="text-gray-500 text-xs">(Opsional)</span></label>
                            <input type="text" id="nama_perusahaan" name="nama_perusahaan" value="{{ old('nama_perusahaan') }}" maxlength="100"
                                   placeholder="Masukkan nama perusahaan atau instansi..."
                                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                        </div>
                        <div>
                            <label for="alamat_perusahaan" class="block text-sm font-medium text-gray-700 mb-1">Alamat Perusahaan/Instansi <span class="text-gray-500 text-xs">(Opsional)</span></label>
                            <textarea id="alamat_perusahaan" name="alamat_perusahaan" rows="3" maxlength="200"
                                      placeholder="Masukkan alamat lengkap..."
                                      class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2 px-3.5 text-sm">{{ old('alamat_perusahaan') }}</textarea>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="kota_perusahaan" class="block text-sm font-medium text-gray-700 mb-1">Kota <span class="text-gray-500 text-xs">(Opsional)</span></label>
                                <input type="text" id="kota_perusahaan" name="kota_perusahaan" value="{{ old('kota_perusahaan') }}" maxlength="50"
                                       placeholder="Kota asal perusahaan..."
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                            </div>
                            <div>
                                <label for="email_perusahaan" class="block text-sm font-medium text-gray-700 mb-1">Email Perusahaan/Instansi <span class="text-gray-500 text-xs">(Opsional)</span></label>
                                <input type="email" id="email_perusahaan" name="email_perusahaan" value="{{ old('email_perusahaan') }}" maxlength="100"
                                       placeholder="Contoh: info@perusahaan.com"
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="telp_perusahaan" class="block text-sm font-medium text-gray-700 mb-1">Telepon Perusahaan/Instansi <span class="text-gray-500 text-xs">(Opsional)</span></label>
                                <input type="tel" id="telp_perusahaan" name="telp_perusahaan" value="{{ old('telp_perusahaan') }}" maxlength="20"
                                       placeholder="Contoh: 031-123456"
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                            </div>
                            <div>
                                <label for="contact_person_perusahaan" class="block text-sm font-medium text-gray-700 mb-1">Contact Person <span class="text-gray-500 text-xs">(Opsional)</span></label>
                                <input type="text" id="contact_person_perusahaan" name="contact_person_perusahaan" value="{{ old('contact_person_perusahaan') }}" maxlength="100"
                                       placeholder="Nama contact person di perusahaan..."
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-end gap-4 pt-5 border-t border-gray-200">
                    <a href="{{ route('master-pemateri.index') }}"
                       class="w-full sm:w-auto order-2 sm:order-1 text-center bg-gray-100 text-gray-700 border border-gray-300 px-8 py-3 rounded-lg hover:bg-gray-200 font-semibold transition-colors duration-150 text-sm">
                        Batal
                    </a>
                    <button type="submit" 
                            class="w-full sm:w-auto order-1 sm:order-2 bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white px-10 py-3 rounded-lg shadow-md hover:shadow-lg font-semibold transition-all duration-150 ease-in-out transform hover:scale-105 text-sm">
                        Simpan Pemateri
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Alpine.js tidak lagi dibutuhkan untuk show/hide jenis pemateri di form ini --}}
{{-- <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.12.3/dist/cdn.min.js" defer></script> --}}
@endpush
