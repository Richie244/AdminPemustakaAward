<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Klaim Reward Pemenang</title>
    <style>
        @page { margin: 15px; }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            color: #333;
            background-color: #e9ecef;
        }
        .container {
            border: 2px solid #4A90E2;
            border-radius: 12px;
            background-color: #ffffff;
            padding: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding: 15px;
            background-color: #4A90E2;
            color: white;
            border-radius: 8px;
        }
        .header h1 { margin: 0; font-size: 22px; text-transform: uppercase; font-weight: bold; }
        .header p { margin: 5px 0 0 0; font-size: 13px; }
        
        .level-header {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 18px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 15px;
            border-radius: 6px;
        }

        .winner-section-wrapper {
            display: table;
            width: 100%;
            border-spacing: 10px;
            margin-bottom: 15px;
        }

        .winner-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .table-wrapper {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
            background-color: #fff;
        }
        
        .table-header {
            padding: 10px;
            background-color: #f7f7f7;
            border-bottom: 1px solid #dee2e6;
        }

        .table-header h3 {
            margin: 0;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
        }
        
        .table-header p {
            margin: 3px 0 0 0;
            font-size: 11px;
            text-align: center;
            color: #6c757d;
        }

        table.winner-table {
            width: 100%;
            border-collapse: collapse;
        }
        .winner-table th, .winner-table td {
            border-bottom: 1px solid #e9ecef;
            padding: 8px 10px;
            text-align: left;
            font-size: 10px;
            vertical-align: middle;
        }
        .winner-table th {
            background-color: #e9ecef;
            color: #495057;
            font-weight: bold;
        }
        .winner-table tr:last-child td {
            border-bottom: none;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $namaPeriode }}</h1>
            <p>{{ $rentangTanggalPeriode }}</p>
        </div>

        @if($groupedWinners->isEmpty())
            <p style="text-align:center; padding: 50px; font-size: 14px;">Belum ada yang mengklaim hadiah pada periode ini.</p>
        @else
            @foreach($groupedWinners as $level => $winners)
                <div class="level-header">LEVEL {{ $level }}</div>
                
                @php
                    // [FIX] Menggunakan panjang ID Civitas untuk pemisahan yang lebih andal
                    $mahasiswaWinners = $winners->filter(fn($w) => strlen((string) ($w['id_civitas'] ?? '')) === 11);
                    $dosenWinners = $winners->filter(fn($w) => strlen((string) ($w['id_civitas'] ?? '')) === 6);
                    
                    $total_slot_level = $winners->first()['slot_reward'] ?? 0;
                    $total_claimed_level = $winners->count();
                @endphp

                <div class="winner-section-wrapper">
                    {{-- Kolom Mahasiswa --}}
                    <div class="winner-column">
                         <div class="table-wrapper">
                             <div class="table-header">
                                 <h3>MAHASISWA</h3>
                                 <p>Total Slot Level Ini: {{ $total_claimed_level }} / {{ $total_slot_level }}</p>
                             </div>
                            <table class="winner-table">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th style="width: 35%;">Tanggal Klaim</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($mahasiswaWinners->sortBy('tgl_terima') as $winner)
                                        <tr>
                                            <td>
                                                <b>{{ $winner['nama_civitas'] ?? 'Nama Tidak Ada' }}</b><br>
                                                <small style="color: #6c757d;">{{ $winner['id_civitas'] }}</small>
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($winner['tgl_terima'])->translatedFormat('d M Y, H:i') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2" class="text-center" style="padding: 20px;">- Belum Ada Klaim -</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Kolom Dosen --}}
                    <div class="winner-column">
                        <div class="table-wrapper">
                            <div class="table-header">
                                <h3>DOSEN</h3>
                                <p>Total Slot Level Ini: {{ $total_claimed_level }} / {{ $total_slot_level }}</p>
                             </div>
                            <table class="winner-table">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th style="width: 35%;">Tanggal Klaim</th>
                                    </tr>
                                </thead>
                                <tbody>
                                     @forelse($dosenWinners->sortBy('tgl_terima') as $winner)
                                        <tr>
                                            <td>
                                                <b>{{ $winner['nama_civitas'] ?? 'Nama Tidak Ada' }}</b><br>
                                                <small style="color: #6c757d;">{{ $winner['id_civitas'] }}</small>
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($winner['tgl_terima'])->translatedFormat('d M Y, H:i') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2" class="text-center" style="padding: 20px;">- Belum Ada Klaim -</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</body>
</html>
