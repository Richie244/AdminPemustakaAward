<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Laporan Kegiatan' }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; margin: 0.5cm; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #dddddd; text-align: left; padding: 6px; vertical-align: top;}
        th { background-color: #f2f2f2; font-weight: bold; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 0; font-size: 12px; }
        .filter-info { font-size: 11px; margin-bottom: 10px; }
        .no-data { text-align: center; padding: 20px; font-style: italic; }
        .sesi-detail { font-size: 9px; white-space: pre-line; } /* Untuk menampilkan tiap sesi di baris baru */
        @page { margin: 20mm 15mm; } /* Margin halaman PDF */
        footer { position: fixed; bottom: -10mm; left: 0px; right: 0px; height: 50px; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    <footer>
        Dicetak pada: {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i:s') }} - Halaman <span class="page-number"></span>
    </footer>

    <div class="header">
        <h1>{{ $title ?? 'Laporan Daftar Kegiatan' }}</h1>
        <p>Tanggal Cetak: {{ $date }}</p>
    </div>

    @if($filterStartDate || $filterEndDate || $searchTerm)
    <div class="filter-info">
        <strong>Filter Aktif:</strong><br>
        @if($filterStartDate && $filterEndDate)
            Periode Kegiatan: {{ $filterStartDate }} - {{ $filterEndDate }} <br>
        @elseif($filterStartDate)
            Mulai Tanggal Kegiatan: {{ $filterStartDate }} <br>
        @elseif($filterEndDate)
            Sampai Tanggal Kegiatan: {{ $filterEndDate }} <br>
        @endif
        @if($searchTerm)
            Pencarian: {{ $searchTerm }}
        @endif
    </div>
    @endif

    @if($kegiatanList->isEmpty())
        <div class="no-data">Tidak ada data kegiatan yang sesuai dengan filter yang dipilih.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Judul Kegiatan</th>
                    <th>Detail Sesi (Tanggal & Waktu)</th>
                    <th>Total Sesi</th>
                    <th>Pemateri Utama</th>
                    <th>Media/Lokasi</th>
                    <th>Bobot Poin (Per Sesi)</th>
                    <th>Total Peserta Hadir</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($kegiatanList as $index => $k)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $k->judul_kegiatan ?? ($k->JUDUL_KEGIATAN ?? '-') }}</td>
                        <td class="sesi-detail">
                            @if($k->jadwal->isNotEmpty())
                                @foreach($k->jadwal as $sesiIndex => $jadwalSesi)
                                    @php
                                        $tanggalSesi = property_exists($jadwalSesi, 'tgl_kegiatan') && $jadwalSesi->tgl_kegiatan ? \Carbon\Carbon::parse($jadwalSesi->tgl_kegiatan)->translatedFormat('d M Y') : '';
                                        $waktuMulai = property_exists($jadwalSesi, 'waktu_mulai') && $jadwalSesi->waktu_mulai ? \Carbon\Carbon::parse($jadwalSesi->waktu_mulai)->format('H:i') : '-';
                                        $waktuSelesai = property_exists($jadwalSesi, 'waktu_selesai') && $jadwalSesi->waktu_selesai ? \Carbon\Carbon::parse($jadwalSesi->waktu_selesai)->format('H:i') : '';
                                        $waktuDisplay = $waktuMulai;
                                        if ($waktuSelesai && $waktuSelesai !== '-') {
                                            $waktuDisplay .= ' - ' . $waktuSelesai;
                                        }
                                    @endphp
                                    Sesi {{ $sesiIndex + 1 }}: {{ $tanggalSesi }} ({{ $waktuDisplay }})@if(!$loop->last)<br>@endif
                                @endforeach
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $k->jadwal->count() > 0 ? $k->jadwal->count() : '0' }}</td>
                        <td>
                            {{ $k->pemateri->isNotEmpty() ? $k->pemateri->pluck('nama_pemateri')->filter()->join(', ') : '-' }}
                        </td>
                        <td>{{ $k->lokasi ?? ($k->LOKASI ?? '-') }}</td>
                        <td style="text-align:center;">
                            {{ $k->jadwal->first() && property_exists($k->jadwal->first(), 'bobot') ? $k->jadwal->first()->bobot : '-' }}
                        </td>
                        <td style="text-align:center;">
                            {{ $k->total_peserta_hadir ?? '0' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    <script type="text/php">
        if (isset($pdf)) {
            // Script untuk nomor halaman bisa dibiarkan atau disesuaikan jika diperlukan
        }
    </script>
</body>
</html>