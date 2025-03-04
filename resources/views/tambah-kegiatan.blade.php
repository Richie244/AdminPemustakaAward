@extends('layouts.app')

@section('title', 'Unggah Kegiatan')

@section('content')
    <div class="bg-white p-5 shadow-lg rounded-lg w-2/3 mx-auto">
        <h2 class="text-2xl font-bold mb-4">Unggah Kegiatan</h2>

        <form action="{{ route('kegiatan.store') }}" method="POST">
            @csrf
            <label class="block mb-2">Judul Kegiatan</label>
            <input type="text" name="judul" class="w-full border p-2 rounded mb-3" required>

            <!-- Tanggal dan Jam Kegiatan -->
            <label class="block mb-2">Tanggal dan Jam Kegiatan</label>
            <div id="tanggal-container">
                <div class="flex gap-2 mb-3">
                    <input type="date" name="tanggal[]" class="border p-2 w-2/4" required>
                    <input type="time" name="jam_mulai[]" class="border p-2 w-1/4" required>
                    <input type="time" name="jam_selesai[]" class="border p-2 w-1/4">
                    <button type="button" class="bg-blue-500 text-white px-3 w-10 rounded add-tanggal">+</button>
                </div>
            </div>

            <!-- Pemateri -->
            <label class="block mb-2">Pemateri</label>
            <div id="pemateri-container">
                <div class="flex gap-2 mb-3">
                    <input type="text" name="pemateri[]" class="w-full border p-2 rounded" required>
                    <button type="button" class="bg-blue-500 text-white px-3 w-10 rounded add-pemateri">+</button>
                </div>
            </div>

            <!-- Media/Lokasi Kegiatan -->
            <label class="block mb-2">Media/Lokasi Kegiatan</label>
            <div class="flex space-x-2 mb-3">
                <select name="media" class="w-1/3 border p-2 rounded">
                    <option value="Onsite">Onsite</option>
                    <option value="Online">Online</option>
                </select>
                <input type="text" name="lokasi" class="w-2/3 border p-2 rounded" placeholder="Masukkan lokasi jika Onsite">
            </div>
            
            <!-- Keterangan -->
            <label class="block mb-2">Keterangan</label>
            <textarea name="keterangan" class="w-full border p-2 rounded mb-3"></textarea>

            <!-- Bobot Nilai -->
            <label class="block mb-2">Bobot Nilai</label>
            <input type="number" name="bobot" class="w-full border p-2 rounded mb-3" required>

            <!-- Tombol Simpan & Batal -->
            <div class="flex space-x-2">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Simpan</button>
                <a href="{{ route('kegiatan.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded">Batal</a>
            </div>
        </form>
    </div>

    <!-- JavaScript untuk menambah input field dinamis -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.add-tanggal').addEventListener('click', function() {
                let container = document.getElementById('tanggal-container');
                let newField = document.createElement('div');
                newField.classList.add('flex', 'gap-2', 'mb-3');
                newField.innerHTML = `
                    <input type="date" name="tanggal[]" class="border p-2 w-2/4">
                    <input type="time" name="jam_mulai[]" class="border p-2 w-1/4">
                    <input type="time" name="jam_selesai[]" class="border p-2 w-1/4">
                    <button type="button" class="bg-red-500 text-white px-3 w-10 rounded remove-tanggal">-</button>
                `;
                container.appendChild(newField);
            });

            document.querySelector('.add-pemateri').addEventListener('click', function() {
                let container = document.getElementById('pemateri-container');
                let newField = document.createElement('div');
                newField.classList.add('flex', 'gap-2', 'mb-3');
                newField.innerHTML = `
                    <input type="text" name="pemateri[]" class="w-full border p-2 rounded">
                    <button type="button" class="bg-red-500 text-white px-3 w-10 rounded remove-pemateri">-</button>
                `;
                container.appendChild(newField);
            });

            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-tanggal')) {
                    e.target.parentElement.remove();
                }
                if (e.target.classList.contains('remove-pemateri')) {
                    e.target.parentElement.remove();
                }
            });
        });
    </script>
@endsection
