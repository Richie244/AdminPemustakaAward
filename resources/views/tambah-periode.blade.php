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

                @if (session('error'))
                    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"
                        role="alert">
                        <strong class="font-bold">Error!</strong>
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

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
                                @error('start_date')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                                @error('end_date')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {{-- Skor Kunjungan --}}
                        <div class="p-6 border border-gray-200 rounded-xl bg-gray-50/50">
                            <label class="block text-lg font-semibold text-gray-700 mb-4">Skor Kunjungan Harian</label>
                            <div id="skor-kunjungan-container" class="space-y-3 mb-4">
                                @php
                                    $kunjunganStarts = old(
                                        'kunjungan_start',
                                        $previousSettings['kunjungan_start'] ?? [],
                                    );
                                    $kunjunganEnds = old('kunjungan_end', $previousSettings['kunjungan_end'] ?? []);
                                    $kunjunganSkors = old('kunjungan_skor', $previousSettings['kunjungan_skor'] ?? []);
                                @endphp
                                @if (!empty($kunjunganStarts))
                                    @foreach ($kunjunganStarts as $index => $startValue)
                                        <div class="flex items-center gap-3 range-item">
                                            <input type="number" name="kunjungan_start[]" placeholder="Min Kunjungan"
                                                required
                                                class="block w-full rounded-lg border-gray-300 shadow-sm py-2 px-3 text-sm @error('kunjungan_start.' . $index) border-red-500 @enderror"
                                                value="{{ $startValue }}">
                                            <input type="number" name="kunjungan_end[]" placeholder="Maks Kunjungan"
                                                required
                                                class="block w-full rounded-lg border-gray-300 shadow-sm py-2 px-3 text-sm @error('kunjungan_end.' . $index) border-red-500 @enderror"
                                                value="{{ $kunjunganEnds[$index] ?? '' }}">
                                            <input type="number" name="kunjungan_skor[]" placeholder="Skor" required
                                                class="block w-full rounded-lg border-gray-300 shadow-sm py-2 px-3 text-sm @error('kunjungan_skor.' . $index) border-red-500 @enderror"
                                                value="{{ $kunjunganSkors[$index] ?? '' }}">
                                            <button type="button" onclick="hapusRange(this, 'btn-tambah-kunjungan')"
                                                class="p-1.5 text-red-500 hover:bg-red-100 rounded-full transition-colors flex-shrink-0">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <button type="button" id="btn-tambah-kunjungan"
                                onclick="tambahRange('skor-kunjungan-container', 'btn-tambah-kunjungan', 'kunjungan')"
                                class="bg-blue-100 text-blue-700 hover:bg-blue-200 font-medium px-4 py-2 rounded-lg text-sm flex items-center gap-1.5 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Tambah Range Kunjungan
                            </button>
                            @error('kunjungan_start.*')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                            @error('kunjungan_end.*')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                            @error('kunjungan_skor.*')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Skor Pinjaman --}}
                        <div class="p-6 border border-gray-200 rounded-xl bg-gray-50/50">
                            <label class="block text-lg font-semibold text-gray-700 mb-4">Skor Peminjaman Buku</label>
                            <div id="skor-pinjaman-container" class="space-y-3 mb-4">
                                @php
                                    $pinjamanStarts = old('pinjaman_start', $previousSettings['pinjaman_start'] ?? []);
                                    $pinjamanEnds = old('pinjaman_end', $previousSettings['pinjaman_end'] ?? []);
                                    $pinjamanSkors = old('pinjaman_skor', $previousSettings['pinjaman_skor'] ?? []);
                                @endphp
                                @if (!empty($pinjamanStarts))
                                    @foreach ($pinjamanStarts as $index => $startValue)
                                        <div class="flex items-center gap-3 range-item">
                                            <input type="number" name="pinjaman_start[]" placeholder="Min Pinjaman"
                                                required
                                                class="block w-full rounded-lg border-gray-300 shadow-sm py-2 px-3 text-sm @error('pinjaman_start.' . $index) border-red-500 @enderror"
                                                value="{{ $startValue }}">
                                            <input type="number" name="pinjaman_end[]" placeholder="Maks Pinjaman"
                                                required
                                                class="block w-full rounded-lg border-gray-300 shadow-sm py-2 px-3 text-sm @error('pinjaman_end.' . $index) border-red-500 @enderror"
                                                value="{{ $pinjamanEnds[$index] ?? '' }}">
                                            <input type="number" name="pinjaman_skor[]" placeholder="Skor" required
                                                class="block w-full rounded-lg border-gray-300 shadow-sm py-2 px-3 text-sm @error('pinjaman_skor.' . $index) border-red-500 @enderror"
                                                value="{{ $pinjamanSkors[$index] ?? '' }}">
                                            <button type="button" onclick="hapusRange(this, 'btn-tambah-pinjaman')"
                                                class="p-1.5 text-red-500 hover:bg-red-100 rounded-full transition-colors flex-shrink-0">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <button type="button" id="btn-tambah-pinjaman"
                                onclick="tambahRange('skor-pinjaman-container', 'btn-tambah-pinjaman', 'pinjaman')"
                                class="bg-blue-100 text-blue-700 hover:bg-blue-200 font-medium px-4 py-2 rounded-lg text-sm flex items-center gap-1.5 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Tambah Range Pinjaman
                            </button>
                            @error('pinjaman_start.*')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                            @error('pinjaman_end.*')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                            @error('pinjaman_skor.*')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="p-6 border border-gray-200 rounded-xl bg-gray-50/50">
                        <h3 class="text-lg font-semibold text-gray-700 mb-5">Pengaturan Level Reward</h3>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            @for ($i = 1; $i <= 3; $i++)
                                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm space-y-4">
                                    <h4 class="text-md font-semibold text-gray-600 mb-1">Level Reward {{ $i }}
                                    </h4>

                                    <div>
                                        <label for="reward_skor_{{ $i }}"
                                            class="block text-xs font-medium text-gray-500 mb-1">Skor Minimal Total untuk
                                            Level Ini</label>
                                        <input type="number" id="reward_skor_{{ $i }}"
                                            name="rewards[{{ $i }}][skor_minimal]" placeholder="Contoh: 100"
                                            required
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2 px-3 text-sm @error('rewards.' . $i . '.skor_minimal') border-red-500 @enderror"
                                            value="{{ old('rewards.' . $i . '.skor_minimal', $previousSettings['rewards'][$i]['skor_minimal'] ?? '') }}">
                                        @error('rewards.' . $i . '.skor_minimal')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="reward_nama_{{ $i }}"
                                            class="block text-xs font-medium text-gray-500 mb-1">Nama Reward</label>
                                        <input type="text" id="reward_nama_{{ $i }}"
                                            name="rewards[{{ $i }}][nama_reward]"
                                            placeholder="Contoh: Medali Perunggu" required
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2 px-3 text-sm @error('rewards.' . $i . '.nama_reward') border-red-500 @enderror"
                                            value="{{ old('rewards.' . $i . '.nama_reward', $previousSettings['rewards'][$i]['nama_reward'] ?? '') }}">
                                        @error('rewards.' . $i . '.nama_reward')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="reward_slot_{{ $i }}"
                                            class="block text-xs font-medium text-gray-500 mb-1">Jumlah Slot</label>
                                        <input type="number" id="reward_slot_{{ $i }}"
                                            name="rewards[{{ $i }}][slot_tersedia]" placeholder="Contoh: 10"
                                            required
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2 px-3 text-sm @error('rewards.' . $i . '.slot_tersedia') border-red-500 @enderror"
                                            value="{{ old('rewards.' . $i . '.slot_tersedia', $previousSettings['rewards'][$i]['slot_tersedia'] ?? '') }}">
                                        @error('rewards.' . $i . '.slot_tersedia')
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>

                    <div class="p-6 border border-gray-200 rounded-xl bg-gray-50/50">
                        <h3 class="text-lg font-semibold text-gray-700 mb-5">Pengaturan Poin/Bobot Komponen</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4">
                            @if (isset($labelPoinKomponen) && is_array($labelPoinKomponen) && count($labelPoinKomponen) > 0)
                                @foreach ($labelPoinKomponen as $key => $label)
                                    <div>
                                        <label for="poin_komponen_{{ $key }}"
                                            class="block text-sm font-medium text-gray-600 mb-1">{{ $label }}</label>
                                        <input type="number" id="poin_komponen_{{ $key }}"
                                            name="poin_komponen[{{ $key }}]" placeholder="Poin/Bobot" required
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm @error('poin_komponen.' . $key) border-red-500 @enderror"
                                            value="{{ old('poin_komponen.' . $key, $previousSettings['poin_komponen'][$key] ?? '') }}">
                                        @error('poin_komponen.' . $key)
                                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            @else
                                <p class="text-sm text-gray-500 col-span-full">Data jenis bobot untuk poin komponen tidak
                                    tersedia.</p>
                            @endif
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
                if (button) button.style.display = "none";
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
            // Redirect ke halaman create dengan parameter use_previous
            window.location.href = "{{ route('periode.create', ['use_previous' => 'true']) }}";
        }

        document.addEventListener('DOMContentLoaded', function() {
            const previousSettings = @json($previousSettings ?? []);

            // Hanya tambahkan range default jika tidak ada data old DAN tidak ada data dari previousSettings
            if ({{ old('kunjungan_start') ? count(old('kunjungan_start')) : 0 }} === 0 && (!previousSettings
                    .kunjungan_start || previousSettings.kunjungan_start.length === 0)) {
                tambahRange('skor-kunjungan-container', 'btn-tambah-kunjungan', 'kunjungan');
            }
            if ({{ old('pinjaman_start') ? count(old('pinjaman_start')) : 0 }} === 0 && (!previousSettings
                    .pinjaman_start || previousSettings.pinjaman_start.length === 0)) {
                tambahRange('skor-pinjaman-container', 'btn-tambah-pinjaman', 'pinjaman');
            }
        });
    </script>
@endsection
