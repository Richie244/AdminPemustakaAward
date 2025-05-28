<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Sertifikat {{ $namaPeserta ?? 'Peserta' }} - {{ $judulKegiatanFormat ?? 'Kegiatan' }}</title>
    <style>
        @page {
            margin: 0;
            size: A4 landscape; /* Sesuaikan dengan ukuran template Anda */
        }
        body {
            font-family: 'Times New Roman', Times, serif; /* Ganti font jika perlu */
            margin: 0;
            padding: 0;
        }
        .sertifikat-container {
            width: 100%;
            height: 100%;
            position: relative;
        }
        .background-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        /* --- SESUAIKAN NILAI POSISI DAN FONT DI BAWAH INI --- */

        .nama-peserta-sertifikat { /* Mengganti nama kelas agar lebih spesifik */
            position: absolute;
            top: 285px;  /* Perkiraan: Atur posisi vertikal nama peserta */
            left: 0;
            width: 100%;
            font-size: 36px; /* Perkiraan: Atur ukuran font nama peserta */
            font-weight: bold;
            color: #000000; /* Hitam atau warna lain */
            text-align: center;
            text-transform: uppercase;
            z-index: 1;
            letter-spacing: 1px;
        }

        .judul-kegiatan-sertifikat {
            position: absolute;
            top: 490px; /* Perkiraan: Atur posisi vertikal judul kegiatan */
            left: 0;
            width: 100%;
            font-size: 28px; /* Perkiraan: Atur ukuran font judul kegiatan */
            font-weight: bold; /* Atau normal jika tidak tebal */ 
            color: #000000; /* Hitam atau warna lain */
            text-align: center;
            text-transform: uppercase; /* Atau biarkan normal */
            z-index: 1;
            padding: 0 0mm; /* Beri padding agar teks panjang tidak terlalu mepet ke tepi */
            box-sizing: border-box;
            line-height: 1.3;
            letter-spacing: 1px; /* Atur jarak antar baris jika judul bisa lebih dari 1 baris */
        }

    </style>
</head>
<body>
    <div class="sertifikat-container">
        @if(isset($pathBackgroundAbsolut) && file_exists($pathBackgroundAbsolut))
            <img src="{{ $pathBackgroundAbsolut }}" class="background-image" alt="Template Sertifikat">
        @else
            <p style="color:red; text-align:center; padding-top: 50px; position:absolute; width:100%; z-index:10;">
                Background template tidak ditemukan. Path: {{ $pathBackgroundAbsolut ?? 'Tidak diset' }}
            </p>
        @endif
        
        {{-- Hanya menampilkan Nama Peserta --}}
        <div class="nama-peserta-sertifikat">{{ $namaPeserta ?? '[NAMA PESERTA]' }}</div>
        
        {{-- Hanya menampilkan Judul Kegiatan --}}
        <div class="judul-kegiatan-sertifikat">{{ $judulKegiatanFormat ?? '[JUDUL KEGIATAN]' }}</div>

    </div>
</body>
</html>