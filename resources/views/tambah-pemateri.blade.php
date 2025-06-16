@extends('layouts.app')

@section('title', 'Tambah Master Pemateri')
@section('page_title', 'Tambah Master Pemateri Baru')

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
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

        {{-- Tampilkan Notifikasi Error --}}
        @if (session('error'))
            <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
                <p class="font-bold">Gagal!</p>
                <p>{{ session('error') }}</p>
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
                <p class="font-bold">Terjadi Kesalahan:</p>
                <ul class="mt-1 list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if ($error_message_perusahaan ?? null)
            <div class="mb-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-md" role="alert">
                <p class="font-bold">Peringatan:</p>
                <p>{{ $error_message_perusahaan }}</p>
            </div>
        @endif
        @if ($error_message_civitas ?? null)
             <div class="mb-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-md" role="alert">
                <p class="font-bold">Peringatan:</p>
                <p>{{ $error_message_civitas }}</p>
            </div>
        @endif

        {{-- FORM DENGAN ALPINE.JS YANG TELAH DIPERBAIKI --}}
        <form action="{{ route('master-pemateri.store') }}" method="POST"
              x-data="pemateriForm()">
            @csrf

            <div class="grid grid-cols-1 gap-6">
                {{-- Pilihan Perusahaan (Paling Atas) --}}
                <div>
                    <label for="id_perusahaan" class="block text-sm font-medium text-gray-700 mb-1">Asal Perusahaan/Instansi <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-2 mt-1">
                        <select name="id_perusahaan" id="id_perusahaan" style="width: 100%;" required>
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
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        </a>
                    </div>
                </div>

                {{-- FORM UNTUK PEMATERI INTERNAL (Kondisional) --}}
                <div x-show="pemateriType === 'internal'" x-cloak x-transition>
                    <div class="space-y-6">
                        <div>
                            <label for="id_civitas_select2" class="block text-sm font-medium text-gray-700 mb-1">Nama Pemateri (Internal) <span class="text-red-500">*</span></label>
                            <select name="id_civitas" id="id_civitas_select2" style="width: 100%;" x-model="selectedCivitasId">
                                <option value="">-- Cari Nama Dosen/Staf --</option>
                                @foreach($civitasList as $civitas)
                                    <option value="{{ $civitas->id_civitas }}" {{ old('id_civitas') == $civitas->id_civitas ? 'selected' : '' }}>
                                        {{ $civitas->nama }} ({{ $civitas->status ?? 'Civitas' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- FORM UNTUK PEMATERI EKSTERNAL (Kondisional) --}}
                <div x-show="pemateriType === 'eksternal'" x-cloak x-transition>
                    <div class="space-y-6">
                        <div>
                            <label for="nama_pemateri_eksternal" class="block text-sm font-medium text-gray-700 mb-1">Nama Pemateri (Eksternal) <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_pemateri_eksternal" id="nama_pemateri_eksternal" value="{{ old('nama_pemateri_eksternal') }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 sm:text-sm">
                        </div>
                    </div>
                </div>

                {{-- Email Pemateri (bersama, readonly jika internal) --}}
                <div>
                    <label for="email_pemateri" class="block text-sm font-medium text-gray-700 mb-1">Email Pemateri</label>
                    <input type="email" name="email_pemateri" id="email_pemateri"
                           :value="email"
                           :readonly="pemateriType === 'internal'"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 sm:text-sm"
                           :class="{ 'bg-gray-100 cursor-not-allowed': pemateriType === 'internal', 'bg-white': pemateriType !== 'internal' }">
                </div>

                {{-- No. HP Pemateri (bersama, readonly jika internal) --}}
                <div>
                    <label for="no_hp_pemateri" class="block text-sm font-medium text-gray-700 mb-1">No. HP Pemateri</label>
                    <input type="text" name="no_hp_pemateri" id="no_hp_pemateri"
                           :value="no_hp"
                           :readonly="pemateriType === 'internal'"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 sm:text-sm"
                           :class="{ 'bg-gray-100 cursor-not-allowed': pemateriType === 'internal', 'bg-white': pemateriType !== 'internal' }">
                </div>
            </div>

            {{-- Tombol Submit --}}
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
{{-- REVISI SCRIPT KEDUA: Logika Alpine.js yang lebih stabil --}}
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('pemateriForm', () => ({
        // 1. Tentukan nilai awal berdasarkan old input dari Laravel
        pemateriType: '{{ old('id_perusahaan') == 1 ? 'internal' : 'eksternal' }}',
        civitasList: {!! $civitasList->keyBy('id_civitas')->toJson() !!},
        selectedCivitasId: '{{ old('id_civitas') }}',
        email: '{{ old('email_pemateri') }}',
        no_hp: '{{ old('no_hp_pemateri') }}',

        init() {
            let self = this;

            // 2. Inisialisasi Select2 untuk dropdown perusahaan
            $('#id_perusahaan').select2({
                placeholder: "-- Cari dan Pilih Perusahaan --",
                allowClear: true
            }).on('select2:select', function(e) {
                // Saat pengguna memilih perusahaan BARU, update tipe dan reset field
                self.updatePemateriType(e.params.data.id);
            }).on('select2:unselect', function(e) {
                // Saat pengguna menghapus pilihan, kembali ke 'eksternal' dan reset field
                self.updatePemateriType(''); // ID kosong akan dianggap eksternal
            });

            // 3. Inisialisasi Select2 untuk dropdown staf internal
            $('#id_civitas_select2').select2({
                placeholder: "-- Cari Nama Dosen/Staf --",
                allowClear: true
            }).on('select2:select', function(e) {
                // Saat nama dipilih, hanya perbarui data kontak
                self.handleCivitasChange(e.params.data.id);
            }).on('select2:unselect', function(e) {
                 // Saat nama dihapus, hanya reset data kontak
                 self.email = '';
                 self.no_hp = '';
                 self.selectedCivitasId = '';
            });
            
            // 4. Perbarui data kontak jika ada data lama (saat validasi gagal)
            if(self.selectedCivitasId && self.pemateriType === 'internal') {
                self.handleCivitasChange(self.selectedCivitasId);
            }
        },

        // Fungsi untuk mengubah tipe pemateri (dan reset field)
        updatePemateriType(companyId) {
            this.pemateriType = (companyId == '1') ? 'internal' : 'eksternal';
            // Panggil reset HANYA saat tipe berubah secara aktif oleh pengguna
            this.resetFieldsForNewSelection();
        },

        // Fungsi untuk memperbarui email & no.hp berdasarkan pilihan civitas
        handleCivitasChange(civitasId) {
            this.selectedCivitasId = civitasId;
            if (civitasId && this.civitasList[civitasId]) {
                const civitas = this.civitasList[civitasId];
                this.email = civitas.email || '';
                this.no_hp = civitas.hp || '';
            } else {
                this.email = '';
                this.no_hp = '';
            }
        },

        // Fungsi untuk mereset field saat pilihan PERUSAHAAN diubah
        resetFieldsForNewSelection() {
            // Reset pilihan nama civitas
            $('#id_civitas_select2').val(null).trigger('change');
            this.selectedCivitasId = '';
            
            // Reset nama pemateri eksternal
            const namaEksternalInput = document.getElementById('nama_pemateri_eksternal');
            if (namaEksternalInput) {
                namaEksternalInput.value = '';
            }
            
            // Reset kontak
            this.email = '';
            this.no_hp = '';
        }
    }));
});
</script>
@endpush