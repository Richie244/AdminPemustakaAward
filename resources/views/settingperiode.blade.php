@extends('layouts.app')

@section('title', 'Setting Periode')

@section('content')
{{-- Latar belakang halaman --}}
<div class="bg-gray-100 min-h-screen py-8 px-4">
    {{-- Kontainer utama - DIUBAH MENJADI max-w-7xl --}}
    <div class="max-w-7xl mx-auto"> 
        {{-- Tombol Kembali (Opsional, jika diperlukan) --}}
        <div class="mb-6">
            <a href="{{ url('/periode') }}" {{-- Sesuaikan dengan route daftar periode Anda --}}
               class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Daftar Periode
            </a>
        </div>

        {{-- Card Formulir Utama --}}
        <div class="bg-white p-6 sm:p-8 shadow-xl rounded-2xl">
            <h2 class="text-3xl font-bold mb-8 text-gray-800 border-b border-gray-200 pb-4">Pengaturan Periode Baru</h2>

            {{-- Ganti action dengan route yang sesuai untuk menyimpan data --}}
            <form action="{{ route('periode.store') }}" method="POST" class="space-y-8"> 
                @csrf

                <div class="p-6 border border-gray-200 rounded-xl bg-gray-50/50">
                    <h3 class="text-lg font-semibold text-gray-700 mb-5">Informasi Dasar</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="nama_periode" class="block text-sm font-medium text-gray-600 mb-1">Nama Periode</label>
                            <input type="text" id="nama_periode" name="nama_periode" required 
                                   placeholder="Contoh: Periode Januari - Maret 2026"
                                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Tanggal Periode</label>
                            <div class="flex items-center gap-3">
                                <input type="date" name="start_date" required title="Tanggal Mulai"
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                                <span class="text-gray-500">-</span>
                                <input type="date" name="end_date" required title="Tanggal Selesai"
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    {{-- Skor Kunjungan --}}
                    <div class="p-6 border border-gray-200 rounded-xl bg-gray-50/50">
                        <label class="block text-lg font-semibold text-gray-700 mb-4">Skor Kunjungan Harian</label>
                        <div id="skor-kunjungan-container" class="space-y-3 mb-4">
                            {{-- Input range dinamis akan ditambahkan di sini oleh JS --}}
                        </div>
                        <button type="button" id="btn-tambah-kunjungan" onclick="tambahRange('skor-kunjungan-container', 'btn-tambah-kunjungan', 'kunjungan')" 
                                class="bg-blue-100 text-blue-700 hover:bg-blue-200 font-medium px-4 py-2 rounded-lg text-sm flex items-center gap-1.5 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Tambah Range Kunjungan
                        </button>
                    </div>

                    {{-- Skor Pinjaman --}}
                    <div class="p-6 border border-gray-200 rounded-xl bg-gray-50/50">
                        <label class="block text-lg font-semibold text-gray-700 mb-4">Skor Peminjaman Buku</label>
                        <div id="skor-pinjaman-container" class="space-y-3 mb-4">
                            {{-- Input range dinamis akan ditambahkan di sini oleh JS --}}
                        </div>
                        <button type="button" id="btn-tambah-pinjaman" onclick="tambahRange('skor-pinjaman-container', 'btn-tambah-pinjaman', 'pinjaman')" 
                                class="bg-blue-100 text-blue-700 hover:bg-blue-200 font-medium px-4 py-2 rounded-lg text-sm flex items-center gap-1.5 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Tambah Range Pinjaman
                        </button>
                    </div>
                </div>

                <div class="p-6 border border-gray-200 rounded-xl bg-gray-50/50">
                    <h3 class="text-lg font-semibold text-gray-700 mb-5">Pengaturan Level Reward</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @for ($i = 1; $i <= 3; $i++)
                            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                <h4 class="text-md font-semibold text-gray-600 mb-3">Level {{ $i }}</h4>
                                <div class="space-y-3">
                                    <div>
                                        <label for="reward_skor_{{ $i }}" class="block text-xs font-medium text-gray-500 mb-1">Skor Minimal</label>
                                        <input type="number" id="reward_skor_{{ $i }}" name="rewards[{{ $i }}][skor_minimal]" placeholder="Contoh: 100" required
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2 px-3 text-sm">
                                    </div>
                                     <div>
                                        <label for="reward_nama_{{ $i }}" class="block text-xs font-medium text-gray-500 mb-1">Nama Reward</label>
                                        <input type="text" id="reward_nama_{{ $i }}" name="rewards[{{ $i }}][nama_reward]" placeholder="Contoh: Medali Perunggu" required
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2 px-3 text-sm">
                                    </div>
                                     <div>
                                        <label for="reward_slot_{{ $i }}" class="block text-xs font-medium text-gray-500 mb-1">Jumlah Slot</label>
                                        <input type="number" id="reward_slot_{{ $i }}" name="rewards[{{ $i }}][slot_tersedia]" placeholder="Contoh: 10" required
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2 px-3 text-sm">
                                    </div>
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>

                <div class="p-6 border border-gray-200 rounded-xl bg-gray-50/50">
                    <h3 class="text-lg font-semibold text-gray-700 mb-5">Poin Maksimum Komponen Lain</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach(['Kunjungan Harian' => 'kunjungan', 'Aksara Dinamika (Review Buku)' => 'aksara_dinamika', 'Peminjaman Buku' => 'pinjaman', 'Partisipasi Kegiatan' => 'kegiatan'] as $label => $key)
                            <div>
                                <label for="max_{{ $key }}" class="block text-sm font-medium text-gray-600 mb-1">{{ $label }}</label>
                                <input type="number" id="max_{{ $key }}" name="nilai_maks[{{ $key }}]" placeholder="Poin Maks" required
                                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-end gap-4 mt-10 pt-6 border-t border-gray-200">
                    <button type="button" onclick="gunakanSettingSebelumnya()" 
                            class="w-full sm:w-auto bg-gray-100 text-gray-700 border border-gray-300 px-8 py-3 rounded-lg hover:bg-gray-200 font-semibold transition-colors duration-150 text-sm">
                        Gunakan Setting Sebelumnya
                    </button>
                    <button type="submit" 
                            class="w-full sm:w-auto bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-10 py-3 rounded-lg shadow-md hover:shadow-lg font-semibold transition-all duration-150 ease-in-out transform hover:scale-105 text-sm">
                        Simpan Pengaturan Periode
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Script untuk tambah/hapus range --}}
<script>
    function tambahRange(containerId, btnId, prefix) {
        let container = document.getElementById(containerId);
        let button = document.getElementById(btnId);
        let count = container.querySelectorAll('.range-item').length; 
        const maxItems = 10; 

        if (count >= maxItems) {
            alert(`Maksimal ${maxItems} range untuk bagian ini!`);
            return;
        }

        let div = document.createElement('div');
        div.className = "flex items-center gap-3 range-item"; 

        div.innerHTML = `
            <input type="number" name="${prefix}_start[]" placeholder="Min ${prefix.charAt(0).toUpperCase() + prefix.slice(1)}" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2 px-3 text-sm">
            <input type="number" name="${prefix}_end[]" placeholder="Maks ${prefix.charAt(0).toUpperCase() + prefix.slice(1)}" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2 px-3 text-sm">
            <input type="number" name="${prefix}_skor[]" placeholder="Skor" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2 px-3 text-sm">
            <button type="button" onclick="hapusRange(this, '${btnId}')" class="p-1.5 text-red-500 hover:bg-red-100 rounded-full transition-colors flex-shrink-0">
                 <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </button>
        `;

        container.appendChild(div);

        if (container.querySelectorAll('.range-item').length >= maxItems) {
             if(button) button.style.display = "none";
        }
    }

    function hapusRange(button, btnId) {
        let rangeItem = button.closest('.range-item'); 
        if (rangeItem) {
            let container = rangeItem.parentElement;
            rangeItem.remove(); 

            let count = container.querySelectorAll('.range-item').length;
            let buttonTambah = document.getElementById(btnId);
            const maxItems = 10

            if (buttonTambah && count < maxItems) {
                buttonTambah.style.display = "inline-flex"; 
            }
        }
    }

    function gunakanSettingSebelumnya() {
        alert("Fitur 'Gunakan Setting Sebelumnya' belum diimplementasikan.\nAnda perlu mengambil data dari server dan mengisi form ini.");
    }
</script>
@endsection
