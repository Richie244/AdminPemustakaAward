@extends('layouts.app')

@section('title', 'Setting Periode')

@section('content')
<div class="bg-white p-6 shadow-lg rounded-lg w-3/4 mx-auto">
    <h2 class="text-2xl font-bold mb-4">Setting Periode</h2>

    <form action="#" method="POST">
        @csrf

        <label class="block mb-2">Nama Periode</label>
        <input type="text" class="w-full border p-2 rounded mb-3">

        <label class="block mb-2">Tanggal Periode</label>
        <div class="flex space-x-2 mb-3">
            <input type="date" class="border p-2 w-1/2 rounded">
            <input type="date" class="border p-2 w-1/2 rounded">
        </div>

        <!-- Skor Kunjungan -->
        <label class="block mb-2">Skor Kunjungan</label>
        <div id="skor-kunjungan"></div>
        <button type="button" id="btn-kunjungan" onclick="tambahRange('skor-kunjungan', 'btn-kunjungan')" class="bg-gray-400 text-white px-3 py-2 rounded mb-3">
            + Tambah Range
        </button>

        <!-- Skor Pinjaman -->
        <label class="block mb-2">Skor Pinjaman</label>
        <div id="skor-pinjaman"></div>
        <button type="button" id="btn-pinjaman" onclick="tambahRange('skor-pinjaman', 'btn-pinjaman')" class="bg-gray-400 text-white px-3 py-2 rounded mb-3">
            + Tambah Range
        </button>

        <label class="block mb-2">Skor Aksara Dinamika</label>
        <input type="text" class="w-full border p-2 rounded mb-3">

        <div class="grid grid-cols-3 gap-3 mb-3">
            @for ($i = 1; $i <= 3; $i++)
                <div>
                    <label class="block mb-1">Skor Level {{ $i }}</label>
                    <input type="text" class="border p-2 w-full rounded">
                </div>
                <div>
                    <label class="block mb-1">Reward Level {{ $i }}</label>
                    <input type="text" class="border p-2 w-full rounded">
                </div>
                <div>
                    <label class="block mb-1">Slot Reward Level {{ $i }}</label>
                    <input type="text" class="border p-2 w-full rounded">
                </div>
            @endfor
        </div>

        <div class="grid grid-cols-2 gap-3 mb-3">
            @foreach(['Kunjungan', 'Aksara Dinamika', 'Pinjaman', 'Kegiatan'] as $item)
                <div>
                    <label class="block mb-1">Nilai Maks {{ $item }}</label>
                    <input type="text" class="border p-2 w-full rounded">
                </div>
            @endforeach
        </div>

        <div class="flex space-x-2">
            <button type="submit" class="bg-blue-500 text-white px-5 py-2 rounded">Simpan</button>
            <button type="button" onclick="gunakanSettingSebelumnya()" class="bg-green-500 text-white px-5 py-2 rounded">Gunakan Settingan Periode Sebelumnya</button>
        </div>
    </form>
</div>

<script>
    function tambahRange(id, btnId) {
        let container = document.getElementById(id);
        let button = document.getElementById(btnId);
        let count = container.getElementsByClassName('range-item').length;

        if (count >= 10) {
            alert("Maksimal 10 range!");
            return;
        }

        let div = document.createElement('div');
        div.className = "flex items-center space-x-2 mb-2 range-item";

        div.innerHTML = `
            <input type="number" placeholder="Start Range" class="border p-2 w-1/4 rounded">
            <input type="number" placeholder="End Range" class="border p-2 w-1/4 rounded">
            <input type="number" placeholder="Jumlah Skor" class="border p-2 w-1/4 rounded">
            <button type="button" onclick="hapusRange(this, '${btnId}')" class="bg-red-500 text-white px-2 py-1 rounded">X</button>
        `;

        container.appendChild(div);

        // Sembunyikan tombol jika sudah mencapai 10 range
        if (count + 1 >= 10) {
            button.style.display = "none";
        }
    }

    function hapusRange(button, btnId) {
        let container = button.parentElement.parentElement;
        button.parentElement.remove();

        let buttonTambah = document.getElementById(btnId);
        let count = container.getElementsByClassName('range-item').length;

        // Tampilkan kembali tombol tambah jika jumlah item kurang dari 10
        if (count < 10) {
            buttonTambah.style.display = "inline-block";
        }
    }

    function gunakanSettingSebelumnya() {
        alert("Settingan periode sebelumnya telah digunakan!");
        // Simulasi pemuatan settingan sebelumnya (bisa disesuaikan dengan fetch data dari database)
    }
</script>

@endsection
