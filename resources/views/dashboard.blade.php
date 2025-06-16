@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')

    {{-- Baris Header: Judul dan Dropdown Periode --}}
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-2xl font-bold text-gray-800 font-rubik">Ringkasan Leaderboard</h2>
        
        <div class="relative">
            <button id="dropdownDefaultButton" data-dropdown-toggle="dropdown"
                class="text-slate-800 bg-white border border-slate-300 hover:bg-slate-50 focus:ring-4 focus:outline-none focus:ring-slate-200 font-semibold rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center shadow-sm transition-all duration-200"
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
document.addEventListener("DOMContentLoaded", function () {
    const dropdownButton = document.getElementById("dropdownDefaultButton");
    const periodeList = document.getElementById("periodeList");
    const currentPath = "{{ route('dashboard') }}"; 

    if (!dropdownButton || !periodeList) {
        return;
    }

    const activePeriodeName = dropdownButton.textContent.trim();
    
    // PERBAIKAN DI SINI: Fetch ke route internal, bukan ke API langsung
    fetch("{{ route('periode.dropdown') }}")
        .then((response) => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then((data) => {
            let periodes = data.data || data; 
            periodeList.innerHTML = "";

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has("periode")) {
                const homeLink = document.createElement("li");
                homeLink.innerHTML = `
                    <a href="${currentPath}" class="flex items-center gap-x-3 rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-amber-100 transition-colors duration-200">
                        <i class="fa-solid fa-star w-5 text-center text-amber-500"></i>
                        <span>Periode Saat Ini</span>
                    </a>
                `;
                periodeList.appendChild(homeLink);
            }

            if (Array.isArray(periodes)) {
                periodes.forEach((item) => {
                    if (item.nama_periode !== activePeriodeName) {
                        const li = document.createElement("li");
                        const filterUrl = `${currentPath}?periode=${item.id_periode}`;
                        li.innerHTML = `
                            <a href="${filterUrl}" class="flex items-center gap-x-3 rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-amber-100 transition-colors duration-200">
                                <i class="fa-solid fa-trophy w-5 text-center text-gray-400"></i>
                                <span>${item.nama_periode}</span>
                            </a>
                        `;
                        periodeList.appendChild(li);
                    }
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
