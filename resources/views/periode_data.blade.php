<?php
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

return $periodeData;
