@extends('layouts.app')

@section('content')
<div x-data="{ showRejectModal: false, rejectReason: '' }">
    <div class="bg-white p-6 shadow-lg rounded-lg w-3/4 mx-auto">
        <h2 class="text-2xl font-bold">Setting Validasi Aksara Dinamika</h2>

        <!-- Identitas Peserta -->
        <div class="mt-4 border p-4 rounded">
            <h3 class="font-semibold">Identitas Peserta</h3>
            <p>Nama : {{ $peserta->nama }}</p>
            <p>NIM : {{ $peserta->nim }}</p>
            <p>Email : {{ $peserta->email }}</p>
        </div>

        <!-- Hasil Review -->
        <div class="mt-4 border p-4 rounded">
            <h3 class="font-semibold">Hasil Review Peserta</h3>
            <p>Judul : {{ $peserta->judul }}</p>
            <p>Pengarang : {{ $peserta->pengarang }}</p>
            <p>Review : {{ $peserta->review }}</p>
        </div>

        <!-- Tombol Aksi -->
        <div class="mt-4 flex justify-end space-x-4">
            <a href="{{ route('aksara.setuju', $peserta->nim) }}" 
                class="bg-blue-500 text-white px-4 py-2 rounded">Diterima</a>
            <button class="bg-red-500 text-white px-4 py-2 rounded" @click="showRejectModal = true">
                Ditolak
            </button>
        </div>

        <!-- Modal Alasan Penolakan -->
        <div x-show="showRejectModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center">
            <div class="bg-white p-6 rounded-lg w-1/3">
                <h2 class="text-xl font-bold mb-2">Alasan Penolakan</h2>
                <textarea x-model="rejectReason" class="w-full border rounded p-2" placeholder="Masukkan alasan..."></textarea>

                <div class="mt-4 flex justify-end space-x-4">
                    <button class="bg-gray-400 text-white px-4 py-2 rounded" @click="showRejectModal = false">
                        Batal
                    </button>
                    <a :href="'{{ route('aksara.tolak', $peserta->nim) }}?alasan=' + encodeURIComponent(rejectReason)"
                       class="bg-red-500 text-white px-4 py-2 rounded">
                        Kirim
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pastikan Alpine.js dimuat -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

@endsection
