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
             @if(session('warning'))
                <div class="mb-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-md" role="alert">
                    <p class="font-bold">Perhatian:</p>
                    <p>{{ session('warning') }}</p>
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
                        <input type="text" id="judul" name="judul" value="{{ old('judul', $kegiatan->judul_kegiatan ?? ($kegiatan->JUDUL_KEGIATAN ?? '')) }}" required maxlength="100"
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
                    
                    {{-- Template Sertifikat Dihapus --}}

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
                                // Prioritaskan old input, lalu data dari database
                                $currentSesiData = $oldSesi ?: $jadwalKegiatan->map(function ($jadwal) {
                                    return [
                                        'tanggal' => $jadwal->tgl_kegiatan ? \Carbon\Carbon::parse($jadwal->tgl_kegiatan)->format('Y-m-d') : '',
                                        'jam_mulai' => $jadwal->waktu_mulai ? \Carbon\Carbon::parse($jadwal->waktu_mulai)->format('H:i') : '',
                                        'jam_selesai' => $jadwal->waktu_selesai ? \Carbon\Carbon::parse($jadwal->waktu_selesai)->format('H:i') : '',
                                        'id_pemateri' => $jadwal->id_pemateri ?? null // Ambil id_pemateri dari data jadwal
                                    ];
                                })->all();
                            @endphp

                            @if(!empty($currentSesiData))
                                @foreach($currentSesiData as $sesiIndex => $sesiData)
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
                                            $currentPemateriIdSesiIni = $sesiData['id_pemateri'] ?? null;
                                        @endphp
                                        <select name="sesi[{{ $sesiIndex }}][id_pemateri]" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                                            <option value="">-- Pilih Pemateri --</option>
                                             @if(isset($masterPemateri) && $masterPemateri->where('tipe_pemateri', 'Internal')->count() > 0)
                                                <optgroup label="Pemateri Internal">
                                                    @foreach($masterPemateri->where('tipe_pemateri', 'Internal') as $pemateri)
                                                        @php $pemateriMasterId = $pemateri->id_pemateri ?? $pemateri->ID_PEMATERI ?? ''; @endphp
                                                        <option value="{{ $pemateriMasterId }}" {{ (string)$currentPemateriIdSesiIni == (string)$pemateriMasterId ? 'selected' : '' }}>
                                                            {{ $pemateri->nama_pemateri ?? $pemateri->NAMA_PEMATERI ?? 'Internal Tidak Bernama' }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                            @if(isset($masterPemateri) && $masterPemateri->where('tipe_pemateri', '!=', 'Internal')->count() > 0)
                                                <optgroup label="Pemateri Eksternal">
                                                    @foreach($masterPemateri->where('tipe_pemateri', '!=', 'Internal') as $pemateri)
                                                         @php $pemateriMasterId = $pemateri->id_pemateri ?? $pemateri->ID_PEMATERI ?? ''; @endphp
                                                        <option value="{{ $pemateriMasterId }}" {{ (string)$currentPemateriIdSesiIni == (string)$pemateriMasterId ? 'selected' : '' }}>
                                                            {{ $pemateri->nama_pemateri ?? $pemateri->NAMA_PEMATERI ?? 'Eksternal Tidak Bernama' }}
                                                            @if(isset($pemateri->nama_perusahaan_display) && $pemateri->nama_perusahaan_display !== '-' && $pemateri->nama_perusahaan_display !== 'Universitas Dinamika')
                                                                ({{ $pemateri->nama_perusahaan_display }})
                                                            @endif
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                            @if(!isset($masterPemateri) || $masterPemateri->isEmpty())
                                                <option value="" disabled>Tidak ada data master pemateri</option>
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                @endforeach
                            @endif
                        </div>
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
                               value="{{ old('bobot_kegiatan', $kegiatan->bobot_kegiatan ?? ($kegiatan->jadwal->first()->bobot ?? ($kegiatan->jadwal->first()->BOBOT ?? '') ) ) }}" required min="0"
                               placeholder="Masukkan bobot poin total" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                    </div>

                    {{-- Tombol Aksi --}}
                    <div class="flex flex-col sm:flex-row justify-end gap-4 pt-5 border-t border-gray-200">
                        <a href="{{ route('kegiatan.show', $kegiatanId) }}"
                           class="w-full sm:w-auto order-2 sm:order-1 text-center bg-gray-100 text-gray-700 border border-gray-300 px-8 py-3 rounded-lg hover:bg-gray-200 font-semibold transition-colors duration-150 text-sm">
                            Batal
                        </a>
                        <button type="submit"
                                class="w-full sm:w-auto order-1 sm:order-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-10 py-3 rounded-lg shadow-md hover:shadow-lg font-semibold transition-all duration-150 ease-in-out transform hover:scale-105 text-sm">
                            Simpan Perubahan
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
    let sesiCounter = {{ count(old('sesi', $kegiatan->jadwal ?? [])) }};

    function createPemateriSelectHtml(sesiIdx, selectedPemateriId = null) {
        let optionsHtml = '<option value="">-- Pilih Pemateri --</option>';
        const pemateriInternal = masterPemateriData.filter(p => p.tipe_pemateri === 'Internal');
        const pemateriEksternal = masterPemateriData.filter(p => p.tipe_pemateri !== 'Internal');

        if (pemateriInternal.length > 0) {
            optionsHtml += '<optgroup label="Pemateri Internal">';
            pemateriInternal.forEach((pemateri) => {
                const pemateriId = pemateri.id_pemateri || pemateri.ID_PEMATERI;
                const pemateriNama = pemateri.nama_pemateri || pemateri.NAMA_PEMATERI || 'Internal Tidak Bernama';
                if (pemateriId && pemateriNama) {
                    const isSelected = selectedPemateriId && (String(selectedPemateriId) === String(pemateriId));
                    optionsHtml += `<option value="${pemateriId}" ${isSelected ? 'selected' : ''}>${pemateriNama}</option>`;
                }
            });
            optionsHtml += '</optgroup>';
        }

        if (pemateriEksternal.length > 0) {
            optionsHtml += '<optgroup label="Pemateri Eksternal">';
            pemateriEksternal.forEach((pemateri) => {
                const pemateriId = pemateri.id_pemateri || pemateri.ID_PEMATERI;
                const pemateriNama = pemateri.nama_pemateri || pemateri.NAMA_PEMATERI || 'Eksternal Tidak Bernama';
                const namaPerusahaan = pemateri.nama_perusahaan_display;
                if (pemateriId && pemateriNama) {
                    const isSelected = selectedPemateriId && (String(selectedPemateriId) === String(pemateriId));
                    let displayText = pemateriNama;
                    if (namaPerusahaan && namaPerusahaan !== '-' && namaPerusahaan !== 'Universitas Dinamika') {
                        displayText += ` (${namaPerusahaan})`;
                    }
                    optionsHtml += `<option value="${pemateriId}" ${isSelected ? 'selected' : ''}>${displayText}</option>`;
                }
            });
            optionsHtml += '</optgroup>';
        }
         if (masterPemateriData.length === 0) {
             optionsHtml += '<option value="" disabled>Tidak ada data master pemateri</option>';
        }

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
        });
    }

    function addSesiField(populateData = null) {
        const currentIndex = sesiCounter++; // Gunakan global counter
        const sesiNumber = sesiContainer.querySelectorAll('.sesi-item').length + 1;
        
        let selectedPemateriIdForNewSesi = null;
        if(populateData && populateData.id_pemateri){ // Jika ini data dari DB atau old() yang sudah benar
            selectedPemateriIdForNewSesi = populateData.id_pemateri;
        }

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
                        <input type="date" name="sesi[${currentIndex}][tanggal]" value="${populateData && populateData.tanggal ? populateData.tanggal : ''}" class="block w-full rounded-md border-gray-300 shadow-sm py-2 px-3 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Jam Mulai</label>
                        <input type="time" name="sesi[${currentIndex}][jam_mulai]" value="${populateData && populateData.jam_mulai ? populateData.jam_mulai : ''}" class="block w-full rounded-md border-gray-300 shadow-sm py-2 px-3 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Jam Selesai</label>
                        <input type="time" name="sesi[${currentIndex}][jam_selesai]" value="${populateData && populateData.jam_selesai ? populateData.jam_selesai : ''}" class="block w-full rounded-md border-gray-300 shadow-sm py-2 px-3 text-sm">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pemateri Sesi ${sesiNumber}</label>
                    <div id="pemateri-container-${currentIndex}" class="space-y-2">
                         ${createPemateriSelectHtml(currentIndex, selectedPemateriIdForNewSesi)}
                    </div>
                    {{-- Tombol tambah pemateri per sesi dihapus --}}
                </div>
            </div>
        `;
        sesiContainer.insertAdjacentHTML('beforeend', sesiHTML);
        updateSesiNumbers();
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
    });

    // Jika tidak ada sesi (baik dari old input maupun dari DB), tambahkan satu default
    if (sesiContainer.children.length === 0) {
        addSesiField();
    } else {
        // Jika ada, pastikan yang dari DB ditandai, dan nomor sesi diupdate
        sesiContainer.querySelectorAll('.sesi-item').forEach(item => {
             // Cek apakah item ini hasil render dari server (data DB atau old input)
             // Jika ya, kita bisa tambahkan atribut untuk menandainya jika perlu,
             // atau cukup pastikan sesiCounter sudah benar saat inisialisasi.
            item.setAttribute('data-loaded-from-db', 'true');
        });
        updateSesiNumbers();
    }
});
</script>
@endsection