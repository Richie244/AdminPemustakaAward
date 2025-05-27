<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Laporan Aksara Dinamika' }}</title>
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
        .status-pending { background-color: #fffbe6; color: #f59e0b; }
        .status-diterima { background-color: #f0fdf4; color: #16a34a; }
        .status-ditolak { background-color: #fef2f2; color: #dc2626; }
        @page { margin: 20mm 15mm; }
         footer { position: fixed; bottom: -10mm; left: 0px; right: 0px; height: 50px; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    <footer>
        Dicetak pada: {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i:s') }} - Halaman <span class="page-number"></span>
    </footer>

    <div class="header">
        <h1>{{ $title ?? 'Laporan Validasi Aksara Dinamika' }}</h1>
        <p>Tanggal Cetak: {{ $date }}</p>
    </div>

    @if($filterStartDate || $filterEndDate || $filterStatus !== 'Semua' || $searchTerm)
    <div class="filter-info">
        <strong>Filter Aktif:</strong><br>
        @if($filterStartDate && $filterEndDate)
            Periode Validasi: {{ $filterStartDate }} - {{ $filterEndDate }} <br>
        @elseif($filterStartDate)
            Mulai Tanggal Validasi: {{ $filterStartDate }} <br>
        @elseif($filterEndDate)
            Sampai Tanggal Validasi: {{ $filterEndDate }} <br>
        @endif
        @if($filterStatus !== 'Semua')
            Status: {{ $filterStatus }} <br>
        @endif
        @if($searchTerm)
            Pencarian: {{ $searchTerm }}
        @endif
    </div>
    @endif

    @if($submissions->isEmpty())
        <div class="no-data">Tidak ada data submisi Aksara Dinamika yang sesuai dengan filter yang dipilih.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Judul Karya</th>
                    <th>Pengarang</th>
                    <th>Pengirim (NIM)</th>
                    <th>Status</th>
                    <th>Tgl Validasi/Submit</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($submissions as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->JUDUL ?? '-' }}</td>
                        <td>{{ $item->PENGARANG ?? '-' }}</td>
                        <td>{{ $item->NAMA ?? '-' }} ({{ $item->NIM ?? '-' }})</td>
                        <td class="status-{{ strtolower($item->STATUS ?? 'pending') }}">
                            {{ ucfirst(strtolower($item->STATUS ?? 'pending') === 'pending' ? 'Menunggu' : (strtolower($item->STATUS ?? 'pending') === 'diterima' ? 'Diterima' : 'Ditolak')) }}
                        </td>
                        <td>{{ $item->TGL_VALIDASI_DISPLAY ?? '-' }}</td>
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