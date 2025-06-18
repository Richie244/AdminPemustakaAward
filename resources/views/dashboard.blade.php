@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')

    {{-- Baris Header: Judul, Tombol-tombol, dan Dropdown Periode --}}
    <div class="flex flex-col sm:flex-row justify-between items-center mb-8 gap-4">
        <h2 class="text-2xl font-bold text-gray-800 font-rubik">Ringkasan Pemustaka Award</h2>
        
        <div class="flex items-center gap-x-3 w-full sm:w-auto">
            {{-- Tombol 1: Laporan Leaderboard Poin --}}
            <a href="{{ route('report.leaderboard.pdf', ['periode' => $periodeId ?? '']) }}" target="_blank"
               class="flex-1 sm:flex-none inline-flex justify-center items-center px-4 py-2.5 bg-blue-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:ring ring-blue-300 transition"
               title="Cetak Laporan Peringkat Poin">
                Leaderboard PDF
            </a>

            {{-- [DIUBAH] Tombol 2: Laporan Klaim Hadiah kini menyertakan parameter periode --}}
            <a href="{{ route('report.penerima-reward.pdf', ['periode' => $periodeId ?? '']) }}" target="_blank"
               class="flex-1 sm:flex-none inline-flex justify-center items-center px-4 py-2.5 bg-red-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:ring ring-red-300 transition"
               title="Cetak Laporan Siapa Saja yang Sudah Klaim Hadiah">
                Laporan Klaim
            </a>

            {{-- Dropdown Periode --}}
            <div class="relative flex-1 sm:flex-none">
                <button id="dropdownDefaultButton" data-dropdown-toggle="dropdown"
                    class="w-full text-slate-800 bg-white border border-slate-300 hover:bg-slate-50 focus:ring-4 focus:outline-none focus:ring-slate-200 font-semibold rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center shadow-sm"
                    type="button">
                    {{ $selectedPeriodeName ?? 'Pilih Periode' }} 
                    <svg class="w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                    </svg>
                </button>

                <div id="dropdown"
                    class="z-10 hidden bg-amber-50 rounded-lg shadow-lg w-48 absolute right-0 mt-2 border border-amber-50 p-2">
                    <ul id="periodeList" class="space-y-1" aria-labelledby="dropdownDefaultButton">
                        {{-- Konten diisi oleh JavaScript --}}
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Grid untuk Leaderboard --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div>
            <x-leaderboard-mahasiswa :top5Mahasiswa="$top5Mahasiswa" />
        </div>
        <div>
            <x-leaderboard-dosen :top5Dosen="$top5Dosen" />
        </div>
    </div>

@push('scripts')
<script>
// ... (JavaScript tetap sama) ...
document.addEventListener("DOMContentLoaded", function () {
    const dropdownButton = document.getElementById("dropdownDefaultButton");
    const periodeList = document.getElementById("periodeList");
    const currentPath = "{{ route('dashboard') }}"; 

    if (!dropdownButton || !periodeList) {
        return;
    }
    
    fetch("{{ route('periode.dropdown') }}")
        .then((response) => response.json())
        .then((data) => {
            let periodes = data.data || data; 
            periodeList.innerHTML = "";

            // Link untuk kembali ke periode aktif/default
            const homeLink = document.createElement("li");
            homeLink.innerHTML = `
                <a href="${currentPath}" class="flex items-center gap-x-3 rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-amber-100 transition-colors duration-200">
                    <i class="fa-solid fa-star w-5 text-center text-amber-500"></i>
                    <span>Periode Saat Ini</span>
                </a>
            `;
            periodeList.appendChild(homeLink);

            // Tambahkan semua periode lain dari API
            if (Array.isArray(periodes)) {
                periodes.forEach((item) => {
                    const li = document.createElement("li");
                    const filterUrl = `${currentPath}?periode=${item.id_periode}`;
                    li.innerHTML = `
                        <a href="${filterUrl}" class="flex items-center gap-x-3 rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-amber-100 transition-colors duration-200">
                            <i class="fa-solid fa-trophy w-5 text-center text-gray-400"></i>
                            <span>${item.nama_periode}</span>
                        </a>
                    `;
                    periodeList.appendChild(li);
                });
            }
        })
        .catch((error) => {
            console.error("Error fetching periode:", error);
            periodeList.innerHTML = '<li><span class="block px-4 py-2 text-red-500">Gagal memuat</span></li>';
        });
});
</script>
@endpush
@endsection
