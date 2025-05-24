@extends('layouts.app')

@section('title', 'Tambah Kegiatan Baru')

@section('content')
<div class="min-h-screen py-8 px-4">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <a href="{{ route('kegiatan.index') }}"
               class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Daftar Kegiatan
            </a>
        </div>

        <div class="bg-white p-6 sm:p-8 shadow-xl rounded-2xl">
            <h2 class="text-3xl font-bold mb-8 text-gray-800 border-b border-gray-200 pb-4">Tambah Kegiatan Baru</h2>

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

            <form action="{{ route('kegiatan.store') }}" method="POST" class="space-y-6" enctype="multipart/form-data">
                @csrf

                {{-- Judul Kegiatan --}}
                <div>
                    <label for="judul" class="block text-sm font-medium text-gray-700 mb-1">Judul Kegiatan</label>
                    <input type="text" id="judul" name="judul" value="{{ old('judul') }}" required maxlength="50"
                           placeholder="Masukkan judul kegiatan..."
                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                </div>

                {{-- Media dan Lokasi (Global untuk Kegiatan) --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-1">
                        <label for="media" class="block text-sm font-medium text-gray-700 mb-1">Media Pelaksanaan</label>
                        <select id="media" name="media"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                            <option value="Online" {{ old('media') == 'Online' ? 'selected' : '' }}>Online</option>
                            <option value="Onsite" {{ old('media') == 'Onsite' ? 'selected' : '' }}>Onsite</option>
                            <option value="Hybrid" {{ old('media') == 'Hybrid' ? 'selected' : '' }}>Hybrid</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="lokasi" class="block text-sm font-medium text-gray-700 mb-1">Lokasi / Link</label>
                        <input type="text" id="lokasi" name="lokasi" value="{{ old('lokasi') }}" maxlength="50"
                               placeholder="Contoh: Aula Gedung A / Link Zoom"
                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                    </div>
                </div>

                {{-- Sesi Kegiatan Dinamis (Jadwal, Media, Lokasi, Pemateri) --}}
                <div class="p-5 border border-gray-200 rounded-xl bg-gray-50/50">
                     <div class="flex justify-between items-center mb-4">
                         <label class="block text-md font-semibold text-gray-700">Sesi Kegiatan</label>
                         <button type="button" title="Tambah Sesi Kegiatan" class="add-sesi flex-shrink-0 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                             <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                             Tambah Sesi
                         </button>
                     </div>
                     <div id="sesi-container" class="space-y-6">
                         {{-- Baris sesi akan ditambahkan di sini oleh JavaScript --}}
                     </div>
                </div>

                {{-- Keterangan Umum Kegiatan --}}
                <div>
                    <label for="keterangan_kegiatan" class="block text-sm font-medium text-gray-700 mb-1">Keterangan Umum Kegiatan</label>
                    <textarea id="keterangan_kegiatan" name="keterangan_kegiatan" rows="3"
                              placeholder="Deskripsi umum kegiatan, target peserta keseluruhan, dll."
                              class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2 px-3.5 text-sm">{{ old('keterangan_kegiatan') }}</textarea>
                </div>

                {{-- Bobot Nilai Keseluruhan Kegiatan --}}
                <div>
                    <label for="bobot_kegiatan" class="block text-sm font-medium text-gray-700 mb-1">Poin Kegiatan Per Sesi</label>
                    <input type="number" id="bobot_kegiatan" name="bobot_kegiatan" value="{{ old('bobot_kegiatan') }}" required min="0"
                           placeholder="Masukkan bobot poin total"
                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                </div>

                {{-- Tombol Aksi --}}
                <div class="flex flex-col sm:flex-row justify-end gap-4 pt-5 border-t border-gray-200">
                    <a href="{{ route('kegiatan.index') }}"
                       class="w-full sm:w-auto order-2 sm:order-1 text-center bg-gray-100 text-gray-700 border border-gray-300 px-8 py-3 rounded-lg hover:bg-gray-200 font-semibold transition-colors duration-150 text-sm">
                        Batal
                    </a>
                    <button type="submit"
                            class="w-full sm:w-auto order-1 sm:order-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-10 py-3 rounded-lg shadow-md hover:shadow-lg font-semibold transition-all duration-150 ease-in-out transform hover:scale-105 text-sm">
                        Simpan Kegiatan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const masterPemateriData = @json($masterPemateri ?? collect());
        const sesiContainer = document.getElementById('sesi-container');
        let sesiCounter = 0;

        function createPemateriSelectHtml(sesiIdx) {
            let optionsHtml = '<option value="">-- Pilih Pemateri --</option>';
            const pemateriArray = Array.isArray(masterPemateriData) ?
                masterPemateriData :
                Object.values(masterPemateriData || {});

            pemateriArray.forEach((pemateri) => {
                if (pemateri.id_pemateri && pemateri.nama_pemateri) {
                    optionsHtml += `<option value="${pemateri.id_pemateri}">${pemateri.nama_pemateri}</option>`;
                }
            });

            return `
                <div class="flex items-center gap-3 pemateri-item mb-2">
                    <select name="sesi[${sesiIdx}][pemateri_ids][]" required
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                        ${optionsHtml}
                    </select>
                    <button type="button" title="Hapus Pemateri Ini"
                            class="remove-pemateri-item flex-shrink-0 bg-red-100 hover:bg-red-200 text-red-600 p-2 rounded-md transition-colors">
                       <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                           <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                       </svg>
                    </button>
                </div>
            `;
        }

        function addPemateriToSesi(sesiIndex) {
            const container = document.getElementById(`pemateri-container-${sesiIndex}`);
            if (container) {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = createPemateriSelectHtml(sesiIndex);
                container.appendChild(wrapper.firstElementChild);
            }
        }

        function addSesiField() {
            const currentIndex = sesiCounter++;
            const sesiNumber = sesiContainer.children.length + 1;

            const sesiHTML = `
                <div class="p-4 bg-white border border-gray-300 rounded-lg shadow-sm sesi-item space-y-4" data-sesi-index="${currentIndex}">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-800">Sesi ${sesiNumber}</h3>
                        <button type="button" title="Hapus Sesi Ini"
                                class="remove-sesi flex-shrink-0 bg-red-500 hover:bg-red-600 text-white p-2 rounded-md transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Tanggal Sesi</label>
                            <input type="date" name="sesi[${currentIndex}][tanggal]"
                                   class="block w-full rounded-md border-gray-300 shadow-sm py-2 px-3 text-sm" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Jam Mulai</label>
                            <input type="time" name="sesi[${currentIndex}][jam_mulai]"
                                   class="block w-full rounded-md border-gray-300 shadow-sm py-2 px-3 text-sm" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Jam Selesai</label>
                            <input type="time" name="sesi[${currentIndex}][jam_selesai]"
                                   class="block w-full rounded-md border-gray-300 shadow-sm py-2 px-3 text-sm">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pemateri Sesi ${sesiNumber}</label>
                        <div id="pemateri-container-${currentIndex}" class="space-y-2">
                            ${createPemateriSelectHtml(currentIndex)}
                        </div>
                    </div>
                </div>
            `;

            sesiContainer.insertAdjacentHTML('beforeend', sesiHTML);
        }

        // Event delegation untuk handling dynamic elements
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('button');
            if (!btn) return;

            if (btn.classList.contains('add-sesi')) {
                addSesiField();
            } else if (btn.classList.contains('add-pemateri-sesi')) { // This class isn't used in current HTML, but kept for potential future use
                const sesiIndex = btn.dataset.sesiIndex;
                addPemateriToSesi(sesiIndex);
            } else if (btn.classList.contains('remove-sesi')) {
                if (sesiContainer.children.length > 1) {
                    btn.closest('.sesi-item').remove();
                } else {
                    alert('Minimal harus ada satu sesi kegiatan');
                }
            } else if (btn.classList.contains('remove-pemateri-item')) {
                const pemateriContainer = btn.closest('.pemateri-item').parentElement;
                if (pemateriContainer.children.length > 1) {
                    btn.closest('.pemateri-item').remove();
                } else {
                    alert('Minimal harus ada satu pemateri');
                }
            }
        });

        // Tambahkan sesi pertama saat load
        if (sesiContainer.children.length === 0) addSesiField();
    });
</script>
@endsection