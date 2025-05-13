@extends('layouts.app')

@section('title', 'Edit Kegiatan - ' . ($kegiatan->judul_kegiatan ?? ($kegiatan->JUDUL_KEGIATAN ?? '')))

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
            <h2 class="text-3xl font-bold mb-8 text-gray-800 border-b border-gray-200 pb-4">Edit Kegiatan</h2>

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

            @php $kegiatanId = $kegiatan->id_kegiatan ?? $kegiatan->ID_KEGIATAN ?? null; @endphp

            @if(!$kegiatanId)
                <div class="text-center py-10 text-red-500">
                    <p>Data kegiatan tidak ditemukan atau ID kegiatan tidak valid.</p>
                </div>
            @else
                <form action="{{ route('kegiatan.update', $kegiatanId) }}" method="POST" class="space-y-6" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    {{-- Judul Kegiatan --}}
                    <div>
                        <label for="judul" class="block text-sm font-medium text-gray-700 mb-1">Judul Kegiatan</label>
                        <input type="text" id="judul" name="judul" value="{{ old('judul', $kegiatan->judul_kegiatan ?? ($kegiatan->JUDUL_KEGIATAN ?? '')) }}" required maxlength="50"
                               placeholder="Masukkan judul kegiatan..."
                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                    </div>

                    {{-- Media dan Lokasi (Global untuk Kegiatan) --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-1">
                            <label for="media" class="block text-sm font-medium text-gray-700 mb-1">Media Pelaksanaan</label>
                            <select id="media" name="media" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                                <option value="Online" {{ old('media', $kegiatan->media ?? ($kegiatan->MEDIA ?? '')) == 'Online' ? 'selected' : '' }}>Online</option>
                                <option value="Onsite" {{ old('media', $kegiatan->media ?? ($kegiatan->MEDIA ?? '')) == 'Onsite' ? 'selected' : '' }}>Onsite</option>
                                <option value="Hybrid" {{ old('media', $kegiatan->media ?? ($kegiatan->MEDIA ?? '')) == 'Hybrid' ? 'selected' : '' }}>Hybrid</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label for="lokasi" class="block text-sm font-medium text-gray-700 mb-1">Lokasi / Link</label>
                            <input type="text" id="lokasi" name="lokasi" value="{{ old('lokasi', $kegiatan->lokasi ?? ($kegiatan->LOKASI ?? '')) }}" maxlength="50"
                                   placeholder="Contoh: Aula Gedung A / Link Zoom" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                        </div>
                    </div>

                    {{-- Sesi Kegiatan Dinamis --}}
                    <div class="p-5 border border-gray-200 rounded-xl bg-gray-50/50">
                        <div class="flex justify-between items-center mb-4">
                            <label class="block text-md font-semibold text-gray-700">Sesi Kegiatan</label>
                            <button type="button" title="Tambah Sesi Kegiatan" class="add-sesi flex-shrink-0 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                                <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Tambah Sesi
                            </button>
                        </div>
                        <div id="sesi-container" class="space-y-6">
                            @php
                                $oldSesi = old('sesi');
                                $jadwalKegiatan = $kegiatan->jadwal ?? collect();
                            @endphp

                            @if($oldSesi)
                                {{-- Populate with old input if validation failed --}}
                                @foreach($oldSesi as $sesiIndex => $sesiData)
                                <div class="p-4 bg-white border border-gray-300 rounded-lg shadow-sm sesi-item space-y-4" data-sesi-index="{{ $sesiIndex }}">
                                    <div class="flex justify-between items-center">
                                        <h3 class="text-lg font-semibold text-gray-800">Sesi {{ $loop->iteration }}</h3>
                                        <button type="button" title="Hapus Sesi Ini" class="remove-sesi flex-shrink-0 bg-red-500 hover:bg-red-600 text-white p-2 rounded-md transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Tanggal Sesi</label>
                                            <input type="date" name="sesi[{{ $sesiIndex }}][tanggal]" value="{{ $sesiData['tanggal'] ?? '' }}" class="block w-full rounded-md border-gray-300 shadow-sm py-2 px-3 text-sm" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Jam Mulai</label>
                                            <input type="time" name="sesi[{{ $sesiIndex }}][jam_mulai]" value="{{ $sesiData['jam_mulai'] ?? '' }}" class="block w-full rounded-md border-gray-300 shadow-sm py-2 px-3 text-sm" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Jam Selesai</label>
                                            <input type="time" name="sesi[{{ $sesiIndex }}][jam_selesai]" value="{{ $sesiData['jam_selesai'] ?? '' }}" class="block w-full rounded-md border-gray-300 shadow-sm py-2 px-3 text-sm">
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Pemateri Sesi {{ $loop->iteration }}</label>
                                        @php
                                            $oldPemateriId = $sesiData['id_pemateri'] ?? (isset($sesiData['pemateri_ids']) && is_array($sesiData['pemateri_ids']) && count($sesiData['pemateri_ids']) > 0 ? $sesiData['pemateri_ids'][0] : null);
                                        @endphp
                                        <select name="sesi[{{ $sesiIndex }}][id_pemateri]" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                                            <option value="">-- Pilih Pemateri --</option>
                                            @if(isset($masterPemateri) && $masterPemateri->isNotEmpty())
                                                @foreach($masterPemateri as $pemateriMaster)
                                                    @php $pemateriMasterId = $pemateriMaster->id_pemateri ?? $pemateriMaster->ID_PEMATERI ?? ''; @endphp
                                                    <option value="{{ $pemateriMasterId }}" {{ (string)$oldPemateriId == (string)$pemateriMasterId ? 'selected' : '' }}>
                                                        {{ $pemateriMaster->nama_pemateri ?? $pemateriMaster->NAMA_PEMATERI ?? 'Nama Tidak Tersedia' }}
                                                    </option>
                                                @endforeach
                                            @else
                                                <option value="" disabled>Tidak ada data master pemateri</option>
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                @endforeach
                            @elseif($jadwalKegiatan->isNotEmpty())
                                {{-- Populate with existing data from database --}}
                                @foreach($jadwalKegiatan as $sesiIndex => $jadwal)
                                <div class="p-4 bg-white border border-gray-300 rounded-lg shadow-sm sesi-item space-y-4" data-sesi-index="{{ $sesiIndex }}">
                                    <div class="flex justify-between items-center">
                                        <h3 class="text-lg font-semibold text-gray-800">Sesi {{ $loop->iteration }}</h3>
                                         <button type="button" title="Hapus Sesi Ini" class="remove-sesi flex-shrink-0 bg-red-500 hover:bg-red-600 text-white p-2 rounded-md transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Tanggal Sesi</label>
                                            <input type="date" name="sesi[{{ $sesiIndex }}][tanggal]" value="{{ $jadwal->tgl_kegiatan ? \Carbon\Carbon::parse($jadwal->tgl_kegiatan)->format('Y-m-d') : '' }}" class="block w-full rounded-md border-gray-300 shadow-sm py-2 px-3 text-sm" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Jam Mulai</label>
                                            <input type="time" name="sesi[{{ $sesiIndex }}][jam_mulai]" value="{{ $jadwal->waktu_mulai ? \Carbon\Carbon::parse($jadwal->waktu_mulai)->format('H:i') : '' }}" class="block w-full rounded-md border-gray-300 shadow-sm py-2 px-3 text-sm" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Jam Selesai</label>
                                            <input type="time" name="sesi[{{ $sesiIndex }}][jam_selesai]" value="{{ $jadwal->waktu_selesai ? \Carbon\Carbon::parse($jadwal->waktu_selesai)->format('H:i') : '' }}" class="block w-full rounded-md border-gray-300 shadow-sm py-2 px-3 text-sm">
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Pemateri Sesi {{ $loop->iteration }}</label>
                                        @php
                                            $currentJadwalPemateriId = $jadwal->id_pemateri ?? null;
                                        @endphp
                                        <select name="sesi[{{ $sesiIndex }}][id_pemateri]" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                                            <option value="">-- Pilih Pemateri --</option>
                                            @if(isset($masterPemateri) && $masterPemateri->isNotEmpty())
                                                @foreach($masterPemateri as $pemateriMaster)
                                                    @php $pemateriMasterId = $pemateriMaster->id_pemateri ?? $pemateriMaster->ID_PEMATERI ?? ''; @endphp
                                                    <option value="{{ $pemateriMasterId }}" {{ (string)$currentJadwalPemateriId == (string)$pemateriMasterId ? 'selected' : '' }}>
                                                        {{ $pemateriMaster->nama_pemateri ?? $pemateriMaster->NAMA_PEMATERI ?? 'Nama Tidak Tersedia' }}
                                                    </option>
                                                @endforeach
                                            @else
                                                <option value="" disabled>Tidak ada data master pemateri</option>
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                @endforeach
                            @else
                                {{-- Fallback: JS will add one empty session if no old input and no DB data --}}
                            @endif
                        </div>
                    </div>

                    {{-- Input untuk Template Sertifikat --}}
                    <div>
                        <label for="template_sertifikat" class="block text-sm font-medium text-gray-700 mb-1">Template Sertifikat (Opsional)</label>
                        <input type="file" id="template_sertifikat" name="template_sertifikat"
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 border border-gray-300 rounded-lg shadow-sm cursor-pointer">
                        @if($kegiatan->template_sertifikat_file && (property_exists($kegiatan->template_sertifikat_file, 'nama_file') || (is_array($kegiatan->template_sertifikat_file) && isset($kegiatan->template_sertifikat_file['nama_file'])) ) )
                            @php
                                $namaFileSertifikat = '';
                                $pathFileSertifikat = '';
                                if (is_object($kegiatan->template_sertifikat_file) && property_exists($kegiatan->template_sertifikat_file, 'nama_file')) {
                                    $namaFileSertifikat = $kegiatan->template_sertifikat_file->nama_file;
                                    $pathFileSertifikat = asset('storage/sertifikat_templates_kegiatan/' . $namaFileSertifikat);
                                } elseif (is_array($kegiatan->template_sertifikat_file) && isset($kegiatan->template_sertifikat_file['nama_file'])) {
                                    $namaFileSertifikat = $kegiatan->template_sertifikat_file['nama_file'];
                                    $pathFileSertifikat = asset('storage/sertifikat_templates_kegiatan/' . $namaFileSertifikat);
                                }
                            @endphp
                            @if($namaFileSertifikat)
                            <p class="mt-1 text-xs text-gray-500">File saat ini:
                                <a href="{{ $pathFileSertifikat }}" target="_blank" class="text-blue-500 hover:underline">
                                    {{ $namaFileSertifikat }}
                                </a>
                                (Biarkan kosong jika tidak ingin mengubah)
                            </p>
                            @else
                             <p class="mt-1 text-xs text-gray-500">Belum ada template sertifikat. Format yang didukung: PDF, DOC, DOCX, JPG, PNG. Maksimal 2MB.</p>
                            @endif
                        @else
                            <p class="mt-1 text-xs text-gray-500">Belum ada template sertifikat. Format yang didukung: PDF, DOC, DOCX, JPG, PNG. Maksimal 2MB.</p>
                        @endif
                    </div>

                    {{-- Keterangan Umum Kegiatan --}}
                    <div>
                        <label for="keterangan_kegiatan" class="block text-sm font-medium text-gray-700 mb-1">Keterangan Umum Kegiatan</label>
                        <textarea id="keterangan_kegiatan" name="keterangan_kegiatan" rows="3"
                                  placeholder="Deskripsi umum kegiatan, target peserta keseluruhan, dll."
                                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2 px-3.5 text-sm">{{ old('keterangan_kegiatan', $kegiatan->keterangan ?? ($kegiatan->KETERANGAN ?? '')) }}</textarea>
                    </div>

                    {{-- Bobot Nilai Keseluruhan Kegiatan --}}
                    <div>
                        <label for="bobot_kegiatan" class="block text-sm font-medium text-gray-700 mb-1">Bobot Poin Keseluruhan Kegiatan</label>
                        <input type="number" id="bobot_kegiatan" name="bobot_kegiatan"
                               value="{{ old('bobot_kegiatan', $kegiatan->bobot_kegiatan ?? ($kegiatan->jadwal->first()->bobot_kegiatan ?? ($kegiatan->jadwal->first()->BOBOT_KEGIATAN ?? ($kegiatan->jadwal->first()->bobot ?? '') ) )) }}" required min="0"
                               placeholder="Masukkan bobot poin total" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
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
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const masterPemateriData = @json($masterPemateri ?? collect());
    const sesiContainer = document.getElementById('sesi-container');

    function getNextSesiIndex() {
        let maxIndex = -1;
        sesiContainer.querySelectorAll('.sesi-item').forEach(item => {
            const currentIndex = parseInt(item.dataset.sesiIndex, 10);
            if (!isNaN(currentIndex) && currentIndex > maxIndex) {
                maxIndex = currentIndex;
            }
        });
        return maxIndex + 1;
    }

    function createPemateriSelectHtml(sesiIdx, selectedPemateriId = null) {
        let optionsHtml = '<option value="">-- Pilih Pemateri --</option>';
        const pemateriArray = Array.isArray(masterPemateriData) ?
            masterPemateriData :
            Object.values(masterPemateriData || {});

        pemateriArray.forEach((pemateri) => {
            const pemateriId = pemateri.id_pemateri || pemateri.ID_PEMATERI;
            const pemateriNama = pemateri.nama_pemateri || pemateri.NAMA_PEMATERI || 'Nama Tidak Tersedia';
            if (pemateriId && pemateriNama) {
                const isSelected = selectedPemateriId && (String(selectedPemateriId) === String(pemateriId));
                optionsHtml += `<option value="${pemateriId}" ${isSelected ? 'selected' : ''}>${pemateriNama}</option>`;
            }
        });

        // Name of select changed to sesi[${sesiIdx}][id_pemateri] (no more array for pemateri_ids)
        // Removed the "Hapus Pemateri Ini" button from here
        return `
            <select name="sesi[${sesiIdx}][id_pemateri]" required
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                ${optionsHtml}
            </select>
        `;
    }
    
    function updateSesiNumbers() {
        const sesiItems = sesiContainer.querySelectorAll('.sesi-item');
        sesiItems.forEach((sesi, index) => {
            const sesiNumber = index + 1;
            const header = sesi.querySelector('h3');
            if (header) {
                header.textContent = `Sesi ${sesiNumber}`;
            }
            const pemateriLabel = sesi.querySelector('label[class*="text-gray-700 mb-2"]');
            if (pemateriLabel && pemateriLabel.textContent.includes('Pemateri Sesi')) {
                 pemateriLabel.textContent = `Pemateri Sesi ${sesiNumber}`;
            }
            // No "Tambah Pemateri Lagi" button to update per session
        });
    }

    function addSesiField() {
        const currentIndex = getNextSesiIndex();
        const sesiNumber = sesiContainer.querySelectorAll('.sesi-item').length + 1;

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
                    {{-- Removed "Tambah Pemateri Lagi untuk Sesi Ini" button --}}
                </div>
            </div>
        `;
        sesiContainer.insertAdjacentHTML('beforeend', sesiHTML);
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('button');
        if (!btn) return;

        if (btn.classList.contains('add-sesi')) {
            addSesiField();
        } else if (btn.classList.contains('remove-sesi')) {
            if (sesiContainer.querySelectorAll('.sesi-item').length > 1 || 
                (sesiContainer.querySelectorAll('.sesi-item').length === 1 && !sesiContainer.querySelector('.sesi-item').hasAttribute('data-loaded-from-db'))) {
                btn.closest('.sesi-item').remove();
                updateSesiNumbers();
            } else {
                alert('Minimal harus ada satu sesi kegiatan.');
            }
        }
        // Removed event listeners for 'add-pemateri-sesi' and 'remove-pemateri-item'
    });

    if (sesiContainer.children.length === 0) {
        addSesiField();
    } else {
        sesiContainer.querySelectorAll('.sesi-item').forEach(item => item.setAttribute('data-loaded-from-db', 'true'));
    }
    updateSesiNumbers(); 
});
</script>
@endsection