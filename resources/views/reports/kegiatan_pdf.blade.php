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
                    <th>Tanggal Sesi Awal</th>
                    <th>Waktu Sesi Awal</th>
                    <th>Total Sesi</th>
                    <th>Pemateri Utama</th>
                    <th>Media/Lokasi</th>
                    <th>Bobot Poin</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($kegiatanList as $index => $k)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $k->judul_kegiatan ?? ($k->JUDUL_KEGIATAN ?? '-') }}</td>
                        <td>
                            {{ $k->jadwal->first() && property_exists($k->jadwal->first(), 'tgl_kegiatan') ? \Carbon\Carbon::parse($k->jadwal->first()->tgl_kegiatan)->translatedFormat('d M Y') : '-' }}
                        </td>
                        <td>
                            {{ $k->jadwal->first() && property_exists($k->jadwal->first(), 'waktu_mulai') ? \Carbon\Carbon::parse($k->jadwal->first()->waktu_mulai)->format('H:i') : '-' }}
                            @if($k->jadwal->first() && property_exists($k->jadwal->first(), 'waktu_selesai') && $k->jadwal->first()->waktu_selesai)
                                - {{ \Carbon\Carbon::parse($k->jadwal->first()->waktu_selesai)->format('H:i') }}
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
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Halaman {PAGE_NUM} dari {PAGE_COUNT}";
            $size = 9;
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 35;
            // $pdf->page_text($x, $y, $text, $font, $size); // Tidak perlu jika sudah pakai CSS counter
        }
    </script>
</body>
</html>