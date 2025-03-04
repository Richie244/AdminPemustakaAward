@extends('layouts.app')

@section('title', 'Detail Periode')

@section('content')

@php
    // Data Dummy
    $periodeData = [
        'periode' => [
            'nama' => 'Periode Januari - Maret 2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-03-31',
        ],
        'skor' => [
            'aksara_dinamika' => 85,
        ],
        'rewards' => [
            ['level' => 1, 'skor' => 100, 'reward' => 'Medali Perunggu', 'slot' => 10],
            ['level' => 2, 'skor' => 200, 'reward' => 'Medali Perak', 'slot' => 5],
            ['level' => 3, 'skor' => 300, 'reward' => 'Medali Emas', 'slot' => 3]
        ],
        'nilai_maks' => [
            'kunjungan' => 50,
            'aksara_dinamika' => 100,
            'pinjaman' => 75,
            'kegiatan' => 150
        ]
    ];
@endphp

<div class="bg-white p-6 shadow-lg rounded-lg w-3/4 mx-auto">
    <h2 class="text-2xl font-bold mb-4">{{ $periodeData['periode']['nama'] }}</h2>
    <p><strong>Start Date:</strong> {{ $periodeData['periode']['start_date'] }}</p>
    <p><strong>End Date:</strong> {{ $periodeData['periode']['end_date'] }}</p>

    <h3 class="text-xl font-semibold mt-4">Skor Aksara Dinamika</h3>
    <p>{{ $periodeData['skor']['aksara_dinamika'] }}</p>

    <h3 class="text-xl font-semibold mt-4">Reward Levels</h3>
    <ul>
        @foreach ($periodeData['rewards'] as $reward)
            <li>Level {{ $reward['level'] }}: {{ $reward['reward'] }} ({{ $reward['skor'] }} Skor, Slot: {{ $reward['slot'] }})</li>
        @endforeach
    </ul>

    <h3 class="text-xl font-semibold mt-4">Nilai Maksimum</h3>
    <ul>
        <li><strong>Kunjungan:</strong> {{ $periodeData['nilai_maks']['kunjungan'] }}</li>
        <li><strong>Aksara Dinamika:</strong> {{ $periodeData['nilai_maks']['aksara_dinamika'] }}</li>
        <li><strong>Pinjaman:</strong> {{ $periodeData['nilai_maks']['pinjaman'] }}</li>
        <li><strong>Kegiatan:</strong> {{ $periodeData['nilai_maks']['kegiatan'] }}</li>
    </ul>
</div>

@endsection
