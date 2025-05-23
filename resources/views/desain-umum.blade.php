<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Sertifikat</title>
    <style>
        @page {
            margin: 0; /* Menghilangkan margin default halaman PDF */
            size: A4 landscape; /* Contoh ukuran kertas, sesuaikan */
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif; /* Gunakan font yang didukung DomPDF atau konfigurasikan font kustom */
            margin: 0;
            padding: 0;
            text-align: center; /* Default text align */
        }
        .sertifikat-container {
            width: 100%;
            height: 100%;
            position: relative; /* Untuk menempatkan teks di atas background */
            /* Ukuran A4 Landscape dalam pixel (kurang lebih, tergantung DPI) */
            /* Anda mungkin perlu menyesuaikan ini atau menggunakan persentase */
            /* width: 1123px; */
            /* height: 794px; */
        }
        .background-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1; /* Agar gambar ada di belakang teks */
        }

        /* Styling untuk teks dinamis */
        /* KOORDINAT (top, left) dan UKURAN FONT PERLU DISESUAIKAN SECARA MANUAL */
        /* agar pas dengan desain template Sertifikat.jpg Anda */

        .nama-peserta {
            position: absolute;
            top: 265px; /* Perkiraan dari gambar Sertifikat.jpg */
            left: 0;
            width: 100%;
            font-size: 28px; /* Sesuaikan */
            font-weight: bold;
            color: #000000; /* Sesuaikan */
            text-align: center;
        }
        .sebagai-peran {
            position: absolute;
            top: 310px; /* Perkiraan */
            left: 0;
            width: 100%;
            text-align: center;
        }
        .sebagai-teks {
             font-size: 12px; /* Sesuaikan */
             color: #000;
             display: inline-block; /* Untuk mengontrol lebar jika perlu */
             margin-right: 5px;
        }
        .peran-peserta {
            font-size: 20px; /* Sesuaikan */
            font-weight: bold;
            color: #000000; /* Sesuaikan */
            display: inline-block;
        }

        .tanggal-kegiatan {
            position: absolute;
            top: 368px; /* Perkiraan */
            left: 180px; /* Perkiraan, dari kiri */
            font-size: 11px; /* Sesuaikan */
            color: #333333; /* Sesuaikan */
            text-align: left;
        }
        .judul-utama-kegiatan { /* Untuk "LIBRARY FRIEND ZONE" */
            position: absolute;
            top: 395px; /* Perkiraan */
            left: 0;
            width: 100%;
            font-size: 18px; /* Sesuaikan */
            font-weight: bold;
            color: #000000; /* Sesuaikan */
            text-align: center;
            text-transform: uppercase;
        }
        .sub-judul-kegiatan { /* Untuk "Bedah Buku : ..." */
            position: absolute;
            top: 420px; /* Perkiraan */
            left: 0;
            width: 100%;
            font-size: 14px; /* Sesuaikan */
            color: #333333; /* Sesuaikan */
            text-align: center;
        }

        /* Anda mungkin perlu menambahkan path ke font kustom jika Helvetica/Arial tidak memuaskan */
        /* @font-face {
            font-family: 'NamaFontKustom';
            src: url({{ storage_path('fonts/NamaFontKustom.ttf') }}) format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        body { font-family: 'NamaFontKustom', sans-serif; } */

    </style>
</head>
<body>
    <div class="sertifikat-container">
        {{-- Path ke gambar template Anda. Pastikan gambar ada di public/images/templates/ atau sesuaikan path --}}
        {{-- Menggunakan public_path() agar DomPDF bisa mengakses file sistem --}}
        <img src="{{ public_path('storage/templates_sertifikat/' . $namaFileTemplate) }}" class="background-image" alt="Template Sertifikat">
        
        <div class="nama-peserta">{{ $namaPeserta ?? '[Nama Peserta]' }}</div>
        
        <div class="sebagai-peran">
            <span class="sebagai-teks">SEBAGAI</span>
            <span class="peran-peserta">{{ $peranText ?? '[PERAN]' }}</span>
        </div>

        <div class="tanggal-kegiatan">TANGGAL : {{ $tanggalKegiatanDisplay ?? '[Tanggal Kegiatan]' }}</div>
        <div class="judul-utama-kegiatan">{{ $judulUtamaKegiatan ?? '[JUDUL UTAMA KEGIATAN]' }}</div>
        <div class="sub-judul-kegiatan">"{{ $subJudulKegiatan ?? '[Sub Judul Kegiatan]' }}"</div>

        {{-- Tambahkan elemen lain seperti nama pejabat dan tanda tangan jika diperlukan, --}}
        {{-- dengan positioning absolut yang sesuai --}}
        {{-- Contoh:
        <div style="position: absolute; top: 480px; left: 550px; text-align: center; font-size: 11px;">
            Kepala Perpustakaan Universitas Dinamika
            <br><br><br><br>
            <b>{{ $namaKepalaPerpustakaan ?? '[Nama Kepala]' }}</b>
            <hr style="border-top: 1px solid black; width: 150px; margin: 2px auto;">
            {{ $nipKepalaPerpustakaan ?? '[NIP Kepala]' }}
        </div>
        --}}
    </div>
</body>
</html>
