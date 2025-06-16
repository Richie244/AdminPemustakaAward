@props(['top5Mahasiswa'])

@once
<style>
    .font-rubik {
        font-family: 'Rubik', sans-serif;
    }
    .font-russo {
        font-family: 'Russo One', sans-serif;
    }
</style>
@endonce

<div class="w-full">
    @if (!empty($top5Mahasiswa))
        @php
            // Menggunakan variabel yang dikirim dari props
            $topUser = $top5Mahasiswa[0] ?? null;
            $topUserPhoto = asset('assets/images/profile.png');

            if ($topUser && isset($topUser['jkel'])) {
                $gender = strtolower(trim($topUser['jkel']));
                if ($gender == 'pria' || $gender == 'l') {
                    $topUserPhoto = asset('assets/images/Cylo.png');
                } elseif ($gender == 'wanita' || $gender == 'p') {
                    $topUserPhoto = asset('assets/images/Cyla.png');
                }
            }
        @endphp

        {{-- Bagian Header dengan Judul --}}
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-3xl font-bold font-rubik">Leaderboard Mahasiswa</h2>
        </div>

        {{-- Kartu Peringkat 1 --}}
        <div class="relative bg-[rgba(31,76,109,1)] text-white pt-32 px-10 pb-28 rounded-t-[25px] min-h-[350px]">
            <div class="flex justify-center items-end">
                @if ($topUser)
                    <div class="text-center">
                        <div class="relative w-36 h-36 mx-auto">
                            <img src="{{ $topUserPhoto }}" alt="User Profile Peringkat 1"
                                class="w-full h-full object-cover rounded-full border-4 border-[rgba(251,195,77,1)]">
                            <div class="absolute top-0 right-0 bg-[rgba(251,195,77,1)] text-[rgba(31,76,109,1)] font-bold text-sm px-3 py-1 rounded-full shadow-md font-rubik">
                                1st
                            </div>
                        </div>
                        <p class="mt-2 font-bold font-rubik text-lg text-[rgba(251,195,77,1)] truncate" title="{{ $topUser['nama'] }}">{{ $topUser['nama'] }}</p>
                        <p class="mt-2 font-bold font-rubik text-lg text-[rgba(251,195,77,1)]">{{ $topUser['nim'] ?? '-' }}</p>
                        <div class="flex justify-center items-center space-x-2 mt-2">
                            <img src="{{ asset('assets/images/Poin.png') }}" alt="Poin Icon" class="w-8 h-8">
                            <p class="text-lg leading-none font-russo">{{ $topUser['total_rekap_poin'] ?? '0' }}</p>
                        </div>
                    </div>
                @else
                    <div class="text-center">
                        <p class="text-lg font-bold font-rubik text-[rgba(251,195,77,1)]">Data Peringkat 1 Tidak Tersedia</p>
                    </div>
                @endif
            </div>

            <div class="absolute bottom-[-2px] left-0 w-full z-10">
                <div class="bg-white p-4 rounded-t-[25px] w-full flex items-center justify-center">
                    <h3 class="text-2xl font-bold font-rubik text-[rgba(31,76,109,1)] text-center">Points Leaderboard</h3>
                </div>
            </div>
        </div>

        {{-- Tabel Peringkat --}}
        <div class="overflow-hidden rounded-b-[25px] w-full bg-white shadow-lg">
            <table class="w-full text-gray-700 border-collapse table-fixed">
                <thead class="p-0">
                    <tr class="bg-white border-t border-gray-300">
                        {{-- SOLUSI: Mengatur ulang lebar kolom untuk memberi ruang pada Points --}}
                        <th class="px-2 py-3 font-rubik text-left border-t border-gray-300 w-[15%] text-sm">Place</th>
                        <th class="px-2 py-3 font-rubik text-left border-t border-gray-300 w-[15%] text-sm">Profile</th>
                        <th class="px-2 py-3 font-rubik text-left border-t border-gray-300 w-[25%] text-sm">Nama</th>
                        <th class="px-2 py-3 font-rubik text-left border-t border-gray-300 w-[20%] text-sm">NIM</th>
                        <th class="px-2 py-3 font-rubik text-left border-t border-gray-300 w-[10%] text-sm">Status</th>
                        <th class="px-2 py-3 font-rubik text-center border-t border-gray-300 w-[15%] text-sm">Points</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($top5Mahasiswa as $index => $user)
                        @php
                            $userPhoto = asset('assets/images/profile.png');
                            if (isset($user['jkel'])) {
                                $gender = strtolower(trim($user['jkel']));
                                if ($gender == 'pria' || $gender == 'l') {
                                    $userPhoto = asset('assets/images/Cylo.png');
                                } elseif ($gender == 'wanita' || $gender == 'p') {
                                    $userPhoto = asset('assets/images/Cyla.png');
                                }
                            }
                        @endphp
                        <tr class="{{ (int)$index % 2 == 0 ? 'bg-gray-100' : 'bg-white' }}">
                            <td class="px-2 py-3">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-[rgba(251,195,77,1)] text-[rgba(31,76,109,1)] font-medium border border-gray-300 text-sm font-rubik">
                                    {{ (int)$index + 1 }}
                                </span>
                            </td>
                            <td class="px-2 py-3">
                                <img src="{{ $userPhoto }}" alt="Profile {{ $user['nama'] ?? '' }}" class="w-6 h-6 object-cover rounded-full">
                            </td>
                            <td class="px-2 py-3 font-rubik font-bold text-[rgba(31,76,109,1)] truncate text-sm" title="{{ $user['nama'] ?? '-' }}">{{ $user['nama'] ?? '-' }}</td>
                            <td class="px-2 py-3 font-rubik font-bold text-[rgba(31,76,109,1)] text-xs">{{ $user['nim'] ?? '-' }}</td>
                            <td class="px-2 py-3 font-rubik font-bold text-[rgba(31,76,109,1)] text-xs">{{ $user['status'] ?? '-' }}</td>
                            <td class="px-2 py-3 text-center text-[rgba(31,76,109,1)]">
                                <div class="flex items-center justify-center space-x-2">
                                    <img src="{{ asset('assets/images/Poin.png') }}" alt="Poin Icon" class="w-5 h-5">
                                    <span class="text-sm font-russo">{{ $user['total_rekap_poin'] ?? 0 }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="bg-white p-6 rounded-xl shadow-lg text-center text-gray-500">
            <p class="font-rubik">Data leaderboard mahasiswa tidak tersedia.</p>
        </div>
    @endif
</div>