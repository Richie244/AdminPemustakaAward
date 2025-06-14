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
                <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md shadow" role="alert">
                    <p class="font-bold mb-2">Terjadi kesalahan validasi:</p>
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('kegiatan.update', ['id' => $kegiatan->id_kegiatan ?? ($kegiatan->ID_KEGIATAN ?? '')]) }}" method="POST" class="space-y-6" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                @php
                    $currentSesiData = old('sesi', $kegiatan->jadwal->map(function($jadwal) {
                        return [
                            'tanggal' => \Carbon\Carbon::parse($jadwal->tgl_kegiatan)->format('Y-m-d'),
                            'jam_mulai' => \Carbon\Carbon::parse($jadwal->waktu_mulai)->format('H:i'),
                            'jam_selesai' => $jadwal->waktu_selesai ? \Carbon\Carbon::parse($jadwal->waktu_selesai)->format('H:i') : '',
                            'id_pemateri' => $jadwal->id_pemateri
                        ];
                    }));
                @endphp
                
                <div>
                    <label for="judul" class="block text-sm font-medium text-gray-700 mb-1">Judul Kegiatan <span class="text-red-500">*</span></label>
                    <input type="text" name="judul" id="judul" value="{{ old('judul', $kegiatan->judul_kegiatan ?? ($kegiatan->JUDUL_KEGIATAN ?? '')) }}"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm"
                            required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="media" class="block text-sm font-medium text-gray-700 mb-1">Media <span class="text-red-500">*</span></label>
                        <select name="media" id="media"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm"
                                required>
                            <option value="">-- Pilih Media --</option>
                            <option value="Online" @if(old('media', $kegiatan->media ?? ($kegiatan->MEDIA ?? '')) == 'Online') selected @endif>Online</option>
                            <option value="Offline" @if(old('media', $kegiatan->media ?? ($kegiatan->MEDIA ?? '')) == 'Offline') selected @endif>Offline</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="lokasi" class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                        <input type="text" name="lokasi" id="lokasi" value="{{ old('lokasi', $kegiatan->lokasi ?? ($kegiatan->LOKASI ?? '')) }}"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm"
                                placeholder="Contoh: Ruang Seminar, Gedung A, Link Zoom/GMeet">
                    </div>
                </div>

                <div class="p-5 border border-gray-200 rounded-xl bg-gray-50/50">
                    <div class="flex justify-between items-center mb-4">
                        <label class="block text-md font-semibold text-gray-700">Sesi Kegiatan</label>
                        <button type="button" title="Tambah Sesi Kegiatan" class="add-sesi flex-shrink-0 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                            <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Tambah Sesi
                        </button>
                    </div>
                    <div id="sesi-container" class="space-y-6">
                        {{-- Sesi dari data yang ada --}}
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="keterangan_kegiatan" class="block text-sm font-medium text-gray-700 mb-1">Keterangan Kegiatan</label>
                            <textarea name="keterangan_kegiatan" id="keterangan_kegiatan" rows="3"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">{{ old('keterangan_kegiatan', $kegiatan->keterangan ?? ($kegiatan->KETERANGAN ?? '')) }}</textarea>
                        </div>
                        <div>
                            <label for="bobot_kegiatan" class="block text-sm font-medium text-gray-700 mb-1">Poin Kegiatan Per Sesi <span class="text-red-500">*</span></label>
                            <input type="number" name="bobot_kegiatan" id="bobot_kegiatan" value="{{ old('bobot_kegiatan', $kegiatan->jadwal->first()->bobot ?? 0) }}" min="0"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm"
                                    required>
                        </div>
                </div>

                <div class="pt-6 border-t border-gray-200">
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('kegiatan.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg transition-colors">
                            Batal
                        </a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const masterPemateriData = @json($masterPemateri ?? collect());
    const sesiContainer = document.getElementById('sesi-container');
    const addSesiBtn = document.querySelector('.add-sesi');
    let sesiCounter = 0; // Reset counter to 0 and let it increment in addSesiField
    const currentSesiData = @json($currentSesiData);

    function initSelect2(selector) {
        $(selector).select2({
            placeholder: "-- Cari dan Pilih Pemateri --",
            allowClear: true
        });
    }

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
            optionsHtml += '<option value="" disabled>Tidak ada data master pemateri. Harap tambah terlebih dahulu.</option>';
        }

        return `
            <select name="sesi[${sesiIdx}][id_pemateri]" required style="width: 100%;"
                    class="pemateri-select block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                ${optionsHtml}
            </select>
        `;
    }

    function updateSesiNumbers() {
        const sesiItems = sesiContainer.querySelectorAll('.sesi-item');
        sesiItems.forEach((item, index) => {
            const sesiHeader = item.querySelector('.sesi-header-text');
            if (sesiHeader) {
                sesiHeader.textContent = `Detail Sesi ${index + 1}`;
            }
            const pemateriLabel = item.querySelector('.pemateri-label');
            if(pemateriLabel) {
                pemateriLabel.textContent = `Pemateri Sesi ${index + 1}`;
            }
        });
    }

    function addSesiField(populateData = null) {
        const currentIndex = sesiCounter++;
        const sesiDiv = document.createElement('div');
        sesiDiv.className = 'p-4 bg-white border border-gray-300 rounded-lg shadow-sm sesi-item space-y-4';
        sesiDiv.setAttribute('data-sesi-index', currentIndex);

        const oldTanggal = populateData ? populateData.tanggal : '';
        const oldJamMulai = populateData ? populateData.jam_mulai : '';
        const oldJamSelesai = populateData ? populateData.jam_selesai : '';
        const oldSelectedPemateriId = populateData ? populateData.id_pemateri : null;
        
        sesiDiv.innerHTML = `
            <div class="flex justify-between items-center">
                <h4 class="font-semibold text-gray-700 sesi-header-text">Detail Sesi</h4>
                <button type="button" title="Hapus Sesi" class="remove-sesi text-red-500 hover:text-red-700 font-semibold text-sm">Hapus</button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="sesi_${currentIndex}_tanggal" class="block text-sm font-medium text-gray-700 mb-1">Tanggal <span class="text-red-500">*</span></label>
                    <input type="date" name="sesi[${currentIndex}][tanggal]" id="sesi_${currentIndex}_tanggal" value="${oldTanggal}"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm" required>
                </div>
                <div>
                    <label for="sesi_${currentIndex}_jam_mulai" class="block text-sm font-medium text-gray-700 mb-1">Jam Mulai <span class="text-red-500">*</span></label>
                    <input type="time" name="sesi[${currentIndex}][jam_mulai]" id="sesi_${currentIndex}_jam_mulai" value="${oldJamMulai}"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm" required>
                </div>
                <div>
                    <label for="sesi_${currentIndex}_jam_selesai" class="block text-sm font-medium text-gray-700 mb-1">Jam Selesai</label>
                    <input type="time" name="sesi[${currentIndex}][jam_selesai]" id="sesi_${currentIndex}_jam_selesai" value="${oldJamSelesai}"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 py-2.5 px-3.5 text-sm">
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2 pemateri-label">Pemateri Sesi</label>
                <div id="pemateri-container-${currentIndex}" class="space-y-2">
                    ${createPemateriSelectHtml(currentIndex, oldSelectedPemateriId)}
                </div>
            </div>
        `;
        
        sesiContainer.appendChild(sesiDiv);
        initSelect2(`.sesi-item[data-sesi-index="${currentIndex}"] .pemateri-select`);
        updateSesiNumbers();
    }

    sesiContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-sesi')) {
            e.target.closest('.sesi-item').remove();
            updateSesiNumbers();
        }
    });

    addSesiBtn.addEventListener('click', function() {
        addSesiField();
    });

    if (currentSesiData && currentSesiData.length > 0) {
        currentSesiData.forEach(sesiData => addSesiField(sesiData));
    } else {
        addSesiField(); // Tambah satu jika tidak ada data sama sekali
    }
    updateSesiNumbers();
});
</script>
@endsection