@extends('layouts.app')

@section('title', 'Tambah Periode Award Baru')

@section('content')
    {{-- Latar belakang halaman --}}
    <div class="bg-gray-100 min-h-screen py-8 px-4">
        {{-- Kontainer utama --}}
        <div class="max-w-7xl mx-auto">
            {{-- Tombol Kembali --}}
            <div class="mb-6">
                <a href="{{ route('periode.index') }}"
                    class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors duration-150">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali ke Daftar Periode
                </a>
            </div>

            {{-- Card Formulir Utama --}}
            <div class="bg-white p-6 sm:p-8 shadow-xl rounded-2xl">
                <h2 class="text-3xl font-bold mb-8 text-gray-800 border-b border-gray-200 pb-4">Tambah Periode Baru</h2>

                @if ($errors->any())
                    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"
                        role="alert">
                        <strong class="font-bold">Oops! Terjadi kesalahan:</strong>
                        <ul class="mt-2 list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error_message)
                                <li>{{ $error_message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('periode.store') }}" method="POST" class="space-y-8">
                    @csrf

                    <div class="p-6 border border-gray-200 rounded-xl bg-gray-50/50">
                        <h3 class="text-lg font-semibold text-gray-700 mb-5">Informasi Dasar</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="nama_periode" class="block text-sm font-medium text-gray-600 mb-1">Nama
                                    Periode</label>
                                <input type="text" id="nama_periode" name="nama_periode" required
                                    placeholder="Contoh: Periode Januari - Maret 2026"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm @error('nama_periode') border-red-500 @enderror"
                                    value="{{ old('nama_periode', $previousSettings['nama_periode'] ?? '') }}">
                                @error('nama_periode')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Tanggal Periode</label>
                                <div class="flex items-center gap-3">
                                    <input type="date" name="start_date" required title="Tanggal Mulai"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm @error('start_date') border-red-500 @enderror"
                                        value="{{ old('start_date', $previousSettings['start_date'] ?? '') }}">
                                    <span class="text-gray-500">-</span>
                                    <input type="date" name="end_date" required title="Tanggal Selesai"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm @error('end_date') border-red-500 @enderror"
                                        value="{{ old('end_date', $previousSettings['end_date'] ?? '') }}">
                                </div>
                                @error('start_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                @error('end_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {{-- Skor Kunjungan --}}
                        <div class="p-6 border border-gray-200 rounded-xl bg-gray-50/50">
                            <label class="block text-lg font-semibold text-gray-700 mb-4">Skor Kunjungan Harian</label>
                            <div id="skor-kunjungan-container" class="space-y-3 mb-4">
                                {{-- Rows will be added by JS --}}
                            </div>
                            <button type="button" id="btn-tambah-kunjungan" onclick="tambahRange('skor-kunjungan-container', 'kunjungan')"
                                class="bg-blue-100 text-blue-700 hover:bg-blue-200 font-medium px-4 py-2 rounded-lg text-sm flex items-center gap-1.5 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                Tambah Range Kunjungan
                            </button>
                        </div>

                        {{-- Skor Pinjaman --}}
                        <div class="p-6 border border-gray-200 rounded-xl bg-gray-50/50">
                            <label class="block text-lg font-semibold text-gray-700 mb-4">Skor Peminjaman Buku</label>
                            <div id="skor-pinjaman-container" class="space-y-3 mb-4">
                                {{-- Rows will be added by JS --}}
                            </div>
                            <button type="button" id="btn-tambah-pinjaman" onclick="tambahRange('skor-pinjaman-container', 'pinjaman')"
                                class="bg-blue-100 text-blue-700 hover:bg-blue-200 font-medium px-4 py-2 rounded-lg text-sm flex items-center gap-1.5 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                Tambah Range Pinjaman
                            </button>
                        </div>
                    </div>

                    <div class="p-6 border border-gray-200 rounded-xl bg-gray-50/50">
                        <h3 class="text-lg font-semibold text-gray-700 mb-5">Pengaturan Level Reward</h3>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            @for ($i = 1; $i <= 3; $i++)
                                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm space-y-4">
                                    <h4 class="text-md font-semibold text-gray-600 mb-1">Level Reward {{ $i }}</h4>
                                    <div>
                                        <label for="reward_skor_{{ $i }}" class="block text-xs font-medium text-gray-500 mb-1">Skor Minimal</label>
                                        <input type="number" id="reward_skor_{{ $i }}" name="rewards[{{ $i }}][skor_minimal]" required class="block w-full rounded-md border-gray-300 shadow-sm py-2 px-3 text-sm" value="{{ old('rewards.' . $i . '.skor_minimal', $previousSettings['rewards'][$i]['skor_minimal'] ?? '') }}">
                                    </div>
                                    <div>
                                        <label for="reward_nama_{{ $i }}" class="block text-xs font-medium text-gray-500 mb-1">Nama Reward</label>
                                        <input type="text" id="reward_nama_{{ $i }}" name="rewards[{{ $i }}][nama_reward]" required class="block w-full rounded-md border-gray-300 shadow-sm py-2 px-3 text-sm" value="{{ old('rewards.' . $i . '.nama_reward', $previousSettings['rewards'][$i]['nama_reward'] ?? '') }}">
                                    </div>
                                    <div>
                                        <label for="reward_slot_{{ $i }}" class="block text-xs font-medium text-gray-500 mb-1">Jumlah Slot</label>
                                        <input type="number" id="reward_slot_{{ $i }}" name="rewards[{{ $i }}][slot_tersedia]" required class="block w-full rounded-md border-gray-300 shadow-sm py-2 px-3 text-sm" value="{{ old('rewards.' . $i . '.slot_tersedia', $previousSettings['rewards'][$i]['slot_tersedia'] ?? '') }}">
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>
                    
                    {{-- ### BAGIAN YANG DIINTEGRASIKAN ### --}}
                    <div class="p-6 border border-gray-200 rounded-xl bg-gray-50/50">
                        <h3 class="text-lg font-semibold text-gray-700 mb-5">Pengaturan Poin/Bobot Komponen</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4">
                            @foreach ($labelPoinKomponen as $key => $label)
                                <div>
                                    <label for="poin_komponen_{{ $key }}" class="block text-sm font-medium text-gray-600 mb-1">{{ $label }}</label>
                                    <input type="number" id="poin_komponen_{{ $key }}" name="poin_komponen[{{ $key }}]" placeholder="Poin/Bobot" required class="block w-full rounded-lg border-gray-300 shadow-sm py-2.5 px-3.5 text-sm" value="{{ old('poin_komponen.' . $key, $previousSettings['poin_komponen'][$key] ?? '') }}">
                                </div>
                            @endforeach
                        </div>
                        
                        <h4 class="text-md font-semibold text-gray-600 mt-8 mb-4 pt-4 border-t">Bobot Prioritas Komponen</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-4">
                            @php($prioritasComponents = ['bobot_kunjungan' => 'Prioritas Kunjungan', 'bobot_pinjaman' => 'Prioritas Pinjaman', 'bobot_kegiatan' => 'Prioritas Kegiatan', 'bobot_aksara_dinamika' => 'Prioritas Aksara Dinamika'])
                            @foreach($prioritasComponents as $key => $label)
                            <div>
                                <label for="poin_komponen_{{ $key }}" class="block text-sm font-medium text-gray-600 mb-1">{{ $label }}</label>
                                <select name="poin_komponen[{{$key}}]" required class="block w-full rounded-lg border-gray-300 shadow-sm py-2.5 px-3.5 text-sm priority-select">
                                    <option value="">-- Pilih Prioritas --</option>
                                    @foreach($prioritasOptions as $optionValue => $optionName)
                                        {{-- PERBAIKAN: Gunakan (string) untuk memastikan perbandingan tipe data yang konsisten --}}
                                        <option value="{{ $optionValue }}" {{ (string)(old('poin_komponen.' . $key, $previousSettings['poin_komponen'][$key] ?? '')) === (string)$optionValue ? 'selected' : '' }}>
                                            {{ $optionName }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-4 mt-10 pt-6 border-t border-gray-200">
                        <a href="{{ route('periode.create', ['use_previous' => 'true']) }}" class="w-full sm:w-auto bg-gray-100 text-gray-700 border border-gray-300 px-8 py-3 rounded-lg hover:bg-gray-200 font-semibold transition-colors duration-150 text-sm text-center">
                            Gunakan Setting Sebelumnya
                        </a>
                        <button type="submit"
                            class="w-full sm:w-auto bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-10 py-3 rounded-lg shadow-md hover:shadow-lg font-semibold transition-all duration-150 ease-in-out transform hover:scale-105 text-sm">
                            Simpan Pengaturan Periode
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function tambahRange(containerId, prefix, data = {start: '', end: '', skor: ''}) {
            const container = document.getElementById(containerId);

            // --- TAMBAHKAN KODE INI ---
            if (container.childElementCount >= 10) {
                alert('Maksimal 10 range skor yang dapat ditambahkan.');
                return;
            }
            // --- BATAS KODE TAMBAHAN ---

            const div = document.createElement('div');
            div.className = "flex items-center gap-3 range-item";

            div.innerHTML = `
                <input type="number" name="${prefix}_start[]" placeholder="Min" required class="block w-full rounded-lg border-gray-300 shadow-sm py-2 px-3 text-sm" value="${data.start}">
                <input type="number" name="${prefix}_end[]" placeholder="Maks" required class="block w-full rounded-lg border-gray-300 shadow-sm py-2 px-3 text-sm" value="${data.end}">
                <input type="number" name="${prefix}_skor[]" placeholder="Skor" required class="block w-full rounded-lg border-gray-300 shadow-sm py-2 px-3 text-sm" value="${data.skor}">
                <button type="button" onclick="this.closest('.range-item').remove()" class="p-1.5 text-red-500 hover:bg-red-100 rounded-full transition-colors flex-shrink-0">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                </button>
            `;
            container.appendChild(div);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const oldKunjungan = @json(old('kunjungan_start'));
            const oldPinjaman = @json(old('pinjaman_start'));
            const previousSettings = @json($previousSettings);

            // Populate Kunjungan
            let kunjunganData = [];
            if (oldKunjungan) {
                oldKunjungan.forEach((start, index) => {
                    kunjunganData.push({ start: start, end: @json(old('kunjungan_end'))[index], skor: @json(old('kunjungan_skor'))[index] });
                });
            } else if (previousSettings.kunjungan_start) {
                previousSettings.kunjungan_start.forEach((start, index) => {
                    kunjunganData.push({ start: start, end: previousSettings.kunjungan_end[index], skor: previousSettings.kunjungan_skor[index] });
                });
            }
            if (kunjunganData.length > 0) {
                kunjunganData.forEach(data => tambahRange('skor-kunjungan-container', 'kunjungan', data));
            } else {
                tambahRange('skor-kunjungan-container', 'kunjungan');
            }

            // Populate Pinjaman
            let pinjamanData = [];
            if (oldPinjaman) {
                oldPinjaman.forEach((start, index) => {
                    pinjamanData.push({ start: start, end: @json(old('pinjaman_end'))[index], skor: @json(old('pinjaman_skor'))[index] });
                });
            } else if (previousSettings.pinjaman_start) {
                previousSettings.pinjaman_start.forEach((start, index) => {
                    pinjamanData.push({ start: start, end: previousSettings.pinjaman_end[index], skor: previousSettings.pinjaman_skor[index] });
                });
            }
             if (pinjamanData.length > 0) {
                pinjamanData.forEach(data => tambahRange('skor-pinjaman-container', 'pinjaman', data));
            } else {
                tambahRange('skor-pinjaman-container', 'pinjaman');
            }
            
            // 1. Temukan semua dropdown prioritas
        const allPrioritySelects = document.querySelectorAll('.priority-select');

        // 2. Buat fungsi untuk memperbarui status opsi
        function updatePriorityOptions() {
            // Ambil semua nilai yang sedang dipilih (kecuali yang kosong)
            const selectedValues = Array.from(allPrioritySelects)
                .map(select => select.value)
                .filter(value => value !== ''); // Filter nilai kosong

            // Loop ke setiap dropdown prioritas
            allPrioritySelects.forEach(select => {
                const ownSelectedValue = select.value;

                // Loop ke setiap opsi di dalam dropdown
                select.querySelectorAll('option').forEach(option => {
                    // Jangan nonaktifkan opsi placeholder "-- Pilih Prioritas --"
                    if (option.value === '') {
                        return;
                    }

                    // Cek apakah nilai opsi ini sudah dipilih di dropdown LAIN
                    const isSelectedElsewhere = selectedValues.includes(option.value);
                    
                    // Nonaktifkan opsi jika nilainya sudah dipilih di dropdown lain,
                    // KECUALI jika itu adalah nilai yang sedang dipilih di dropdown ini.
                    // Ini agar opsi yang sedang aktif tidak ikut nonaktif.
                    if (isSelectedElsewhere && option.value !== ownSelectedValue) {
                        option.disabled = true;
                    } else {
                        option.disabled = false;
                    }
                });
            });
        }

        // 3. Tambahkan event listener ke setiap dropdown
        //    Setiap kali pengguna mengubah pilihan, panggil fungsi update.
        allPrioritySelects.forEach(select => {
            select.addEventListener('change', updatePriorityOptions);
        });

        // 4. Panggil fungsi ini sekali saat halaman dimuat
        //    Ini penting agar statusnya benar saat menggunakan "Gunakan Setting Sebelumnya".
        updatePriorityOptions();
    });
    </script>
@endsection