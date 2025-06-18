<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Penerima Reward</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 4px 0; font-size: 11px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 7px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .note { font-size: 9px; color: #777; margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Klaim Hadiah</h1>
        <p><strong>Tanggal Cetak:</strong> {{ $tanggalCetak }}</p>
    </div>

    <h2>Daftar Civitas Penerima Reward</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">ID Civitas (NIM/NIK)</th>
                <th style="width: 20%;">ID Reward</th>
                <th style="width: 25%;">Tanggal Klaim</th>
                <th style="width: 30%;">Paraf</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($penerimaList as $penerima)
                <tr>
                    <td>{{ $penerima['id_civitas'] ?? '-' }}</td>
                    <td>{{ $penerima['id_reward'] ?? '-' }}</td>
                    <td>
                        @if (!empty($penerima['tgl_terima']))
                           {{ \Carbon\Carbon::parse($penerima['tgl_terima'])->translatedFormat('d F Y, H:i') }}
                        @else
                           -
                        @endif
                    </td>
                    <td></td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align: center;">Belum ada data penerima reward.</td></tr>
            @endforelse
        </tbody>
    </table>
    
    <p class="note">
        Catatan: Untuk menampilkan Nama Civitas dan Nama Reward, API perlu diperbarui untuk menyertakan informasi tersebut.
    </p>
</body>
</html>
