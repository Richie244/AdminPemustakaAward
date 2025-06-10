{{-- resources/views/reports/daftar_hadir_kegiatan_pdf.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Laporan Daftar Hadir' }} - {{ $kegiatan->judul_kegiatan ?? 'Kegiatan' }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; margin: 0.5cm; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 16px; }
        .header p { margin: 2px 0; font-size: 12px; }

        .sesi-section { margin-bottom: 25px; page-break-inside: avoid; }
        .sesi-header { background-color: #e9e9e9; padding: 8px; font-weight: bold; font-size: 12px; border-radius: 4px; margin-bottom: 10px; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cccccc; text-align: left; padding: 6px; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .no-data { text-align: center; padding: 15px; font-style: italic; color: #777; }
        
        footer { position: fixed; bottom: -15mm; left: 0px; right: 0px; height: 50px; font-size: 9px; text-align: center; }
        .page-number:before { content: "Halaman " counter(page); }
    </style>
</head>
<body>
    <footer>
        Dicetak pada: {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i:s') }} - <span class="page-number"></span>
    </footer>

    <div class="header">
        <h1>DAFTAR HADIR KEGIATAN</h1>
        <p>{{ strtoupper($kegiatan->judul_kegiatan ?? ($kegiatan->JUDUL_KEGIATAN ?? 'Judul Tidak Tersedia')) }}</p>
    </div>

    @if($jadwalDenganKehadiran->isEmpty())
        <div class="no-data">Tidak ada jadwal sesi yang tercatat untuk kegiatan ini.</div>
    @else
        @foreach($jadwalDenganKehadiran as $index => $jadwal)
            <div class="sesi-section">
                <div class="sesi-header">
                    Sesi {{ $index + 1 }}:
                    {{ $jadwal->tgl_kegiatan ? \Carbon\Carbon::parse($jadwal->tgl_kegiatan)->translatedFormat('l, d F Y') : 'Tanggal Tidak Ada' }}
                    ({{ $jadwal->waktu_mulai ? \Carbon\Carbon::parse($jadwal->waktu_mulai)->format('H:i') : '' }}
                    @if($jadwal->waktu_selesai)
                    - {{ \Carbon\Carbon::parse($jadwal->waktu_selesai)->format('H:i') }}
                    @endif)
                </div>

                @if($jadwal->peserta->isNotEmpty())
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 10%;">No</th>
                                <th style="width: 30%;">NIM</th>
                                <th style="width: 60%;">Nama Peserta</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jadwal->peserta as $pesertaIndex => $peserta)
                            <tr>
                                <td>{{ $pesertaIndex + 1 }}</td>
                                <td>{{ $peserta->nim ?? '-' }}</td>
                                <td>{{ $peserta->nama ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="no-data">Tidak ada peserta yang hadir untuk sesi ini.</div>
                @endif
            </div>
        @endforeach
    @endif
</body>
</html>