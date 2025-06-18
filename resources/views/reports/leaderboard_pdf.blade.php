<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Pemenang & Status Klaim</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 4px 0; font-size: 11px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 7px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        h2 { font-size: 14px; margin-top: 0; margin-bottom: 10px; }
        .status-claimed { color: green; font-weight: bold; }
        .status-unclaimed { color: #d9534f; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Pemenang & Status Klaim Hadiah</h1>
        <p><strong>Periode:</strong> {{ $namaPeriode }}</p>
        <p><strong>Tanggal Cetak:</strong> {{ $tanggalCetak }}</p>
    </div>

    <h2>Leaderboard Mahasiswa</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 8%;">Peringkat</th>
                <th>Nama</th>
                <th style="width: 15%;">NIM</th>
                <th style="width: 12%;">Total Poin</th>
                <th style="width: 25%;">Status Klaim</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($leaderboardMahasiswa as $index => $user)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ $user['nama'] ?? '-' }}</td>
                    <td>{{ $user['nim'] ?? '-' }}</td>
                    <td>{{ number_format($user['total_rekap_poin'] ?? 0) }}</td>
                    <td>
                        {{-- Logika berdasarkan skema DB Anda: TGL_TERIMA --}}
                        @if (!empty($user['tgl_terima']))
                            <span class="status-claimed">Sudah Diklaim ({{ \Carbon\Carbon::parse($user['tgl_terima'])->format('d/m/Y') }})</span>
                        @else
                            <span class="status-unclaimed">Belum Diklaim</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align: center;">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Leaderboard Dosen</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 8%;">Peringkat</th>
                <th>Nama</th>
                <th style="width: 15%;">NIK</th>
                <th style="width: 12%;">Total Poin</th>
                <th style="width: 25%;">Status Klaim</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($leaderboardDosen as $index => $user)
                 <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ $user['nama'] ?? '-' }}</td>
                    <td>{{ $user['nim'] ?? '-' }}</td>
                    <td>{{ number_format($user['total_rekap_poin'] ?? 0) }}</td>
                    <td>
                        {{-- Logika berdasarkan skema DB Anda: TGL_TERIMA --}}
                        @if (!empty($user['tgl_terima']))
                            <span class="status-claimed">Sudah Diklaim ({{ \Carbon\Carbon::parse($user['tgl_terima'])->format('d/m/Y') }})</span>
                        @else
                            <span class="status-unclaimed">Belum Diklaim</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align: center;">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
