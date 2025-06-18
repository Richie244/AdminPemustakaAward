{{-- resources/views/reports/leaderboard-report.blade.php --}}
@extends('layouts.app')

@section('title', 'Laporan Leaderboard & Klaim Hadiah')
@section('page_title', 'Laporan Leaderboard & Klaim Hadiah')

@section('content')
<div class="bg-white p-6 shadow-lg rounded-xl">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 pb-4 border-b border-gray-200">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Laporan Leaderboard Lengkap</h1>
            <p class="text-sm text-gray-500">Periode: {{ $selectedPeriodeName }}</p>
        </div>
        {{-- Dropdown Periode (opsional, mirip dashboard) --}}
    </div>

    @if (session('success'))
        <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md" role="alert">
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
            <p>{{ session('error') }}</p>
        </div>
    @endif

    {{-- Leaderboard Mahasiswa --}}
    <div class="mb-12">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Peringkat Mahasiswa</h2>
        @if (!empty($leaderboardMahasiswa))
            <div class="overflow-x-auto rounded-lg border">
                <table class="min-w-full w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Peringkat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIM</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Poin</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status Klaim</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($leaderboardMahasiswa as $index => $user)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">{{ $index + 1 }}</td>
                                <td class="px-6 py-4 font-medium">{{ $user['nama'] ?? '-' }}</td>
                                <td class="px-6 py-4">{{ $user['nim'] ?? '-' }}</td>
                                <td class="px-6 py-4 font-semibold">{{ $user['total_rekap_poin'] ?? '0' }}</td>
                                <td class="px-6 py-4">
                                    @php
                                        $statusKlaim = $user['status_klaim'] ?? 'Belum Diklaim';
                                        $statusClass = $statusKlaim == 'Sudah Diklaim' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusClass }}">
                                        {{ $statusKlaim }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if ($statusKlaim != 'Sudah Diklaim')
                                        <form action="{{ route('report.leaderboard.claim', $user['id_rekap_poin']) }}" method="POST">
                                            @csrf
                                            @if($periodeId)
                                            <input type="hidden" name="periode" value="{{ $periodeId }}">
                                            @endif
                                            <button type="submit" class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                                Tandai Klaim
                                            </button>
                                        </form>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 text-center py-4">Data leaderboard mahasiswa tidak tersedia.</p>
        @endif
    </div>

    {{-- Leaderboard Dosen --}}
    <div>
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Peringkat Dosen</h2>
        @if (!empty($leaderboardDosen))
            <div class="overflow-x-auto rounded-lg border">
                 <table class="min-w-full w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Peringkat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIK</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Poin</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status Klaim</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($leaderboardDosen as $index => $user)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">{{ $index + 1 }}</td>
                                <td class="px-6 py-4 font-medium">{{ $user['nama'] ?? '-' }}</td>
                                <td class="px-6 py-4">{{ $user['nim'] ?? '-' }}</td>
                                <td class="px-6 py-4 font-semibold">{{ $user['total_rekap_poin'] ?? '0' }}</td>
                                <td class="px-6 py-4">
                                     @php
                                        $statusKlaim = $user['status_klaim'] ?? 'Belum Diklaim';
                                        $statusClass = $statusKlaim == 'Sudah Diklaim' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusClass }}">
                                        {{ $statusKlaim }}
                                    </span>
                                </td>
                               <td class="px-6 py-4 text-center">
                                    @if (($user['status_klaim'] ?? 'Belum Diklaim') != 'Sudah Diklaim')
                                        <form action="{{ route('report.leaderboard.claim', $user['id_rekap_poin']) }}" method="POST">
                                            @csrf
                                            @if($periodeId)
                                            <input type="hidden" name="periode" value="{{ $periodeId }}">
                                            @endif
                                            <button type="submit" class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                                Tandai Klaim
                                            </button>
                                        </form>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 text-center py-4">Data leaderboard dosen tidak tersedia.</p>
        @endif
    </div>

</div>
@endsection