<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Sertifikat {{ $namaPeserta ?? 'Peserta' }} - {{ $judulKegiatanFormat ?? 'Kegiatan' }}</title>
    <style>
        @page {
            margin: 0;
            size: A4 landscape; /* Atau ukuran lain yang sesuai dengan template Anda */
        }
        body {
            font-family: 'Helvetica', Arial, sans-serif; /* Pilih font yang umum atau embed font kustom */
            margin: 0;
            padding: 0;
            text-align: center;
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

        /* Styling untuk teks dinamis - SESUAIKAN POSISI (top, left) DAN FONT */
        .nomor-sertifikat {
            position: absolute;
            top: 110px; /* Contoh, sesuaikan dengan template Anda */
            left: 0;
            width: 100%;
            font-size: 12px;
            color: #333;
            text-align: center;
        }

        .diberikan-kepada-label {
            position: absolute;
            top: 150px; /* Contoh */
            left: 0;
            width: 100%;
            font-size: 14px;
            color: #333;
            text-align: center;
        }

        .nama-peserta {
            position: absolute;
            top: 175px; /* Contoh, di bawah "Diberikan kepada" */
            left: 0;
            width: 100%;
            font-size: 28px;
            font-weight: bold;
            color: #000;
            text-align: center;
            text-transform: uppercase;
        }

        .sebagai-label {
            position: absolute;
            top: 230px; /* Contoh */
            left: 0;
            width: 100%;
            font-size: 14px;
            color: #333;
            text-align: center;
        }

        .judul-kegiatan-sertifikat { /* Menggantikan peran "Peserta" */
            position: absolute;
            top: 255px; /* Contoh, di bawah "Sebagai" */
            left: 0;
            width: 100%;
            font-size: 24px; /* Mungkin perlu lebih kecil dari nama peserta */
            font-weight: bold;
            color: #000;
            text-align: center;
            padding: 0 30px; /* Agar tidak terlalu mepet jika judul panjang */
            line-height: 1.2;
            text-transform: uppercase;
        }
        
        .area-tandatangan {
            position: absolute;
            bottom: 100px; /* Sesuaikan jarak dari bawah */
            width: 100%;
            left: 0;
        }

        .tandatangan {
            display: inline-block; /* Atau gunakan flex/grid jika didukung baik oleh DomPDF versi Anda */
            width: 45%; /* Bagi dua untuk dua tanda tangan */
            text-align: center;
            font-size: 12px;
            color: #333;
            vertical-align: top; /* Jaga alignment jika tinggi berbeda */
        }
         .tandatangan.kiri {
            /* Bisa ditambahkan margin jika perlu penyesuaian posisi */
            /* margin-left: 5%; */
        }
        .tandatangan.kanan {
            /* margin-right: 5%; */
        }
        .jabatan {
            font-size: 12px;
            margin-bottom: 40px; /* Ruang untuk ttd basah */
        }
        .nama-pejabat {
            font-weight: bold;
            text-decoration: underline; /* Jika diperlukan */
            font-size: 12px;
        }
        .nip-pejabat {
            font-size: 11px;
        }

    </style>
</head>
<body>
    <div class="sertifikat-container">
        @if(isset($pathBackgroundAbsolut) && file_exists($pathBackgroundAbsolut))
            <img src="{{ $pathBackgroundAbsolut }}" class="background-image" alt="Template Sertifikat">
        @else
            <p style="color:red; text-align:center; padding-top: 50px;">Background template tidak ditemukan.</p>
        @endif
        
        @if(!empty($nomorSertifikat))
            <div class="nomor-sertifikat">No : {{ $nomorSertifikat }}</div>
        @endif

        <div class="diberikan-kepada-label">Diberikan kepada :</div>
        <div class="nama-peserta">{{ $namaPeserta ?? '[NAMA PESERTA]' }}</div>
        
        <div class="sebagai-label">Sebagai :</div>
        <div class="judul-kegiatan-sertifikat">{{ $judulKegiatanFormat ?? '[JUDUL KEGIATAN]' }}</div>

        {{-- Contoh Area Tanda Tangan (Sesuaikan dengan kebutuhan Anda) --}}
        <div class="area-tandatangan">
            <div class="tandatangan kiri">
                {{-- Jika ada jabatan/nama dinamis dari data kegiatan/pemateri --}}
                <span class="jabatan">{{ $jabatanPejabatSatu ?? 'Ketua Panitia Pelaksana,' }}</span>
                <br><br><br> {{-- Ruang untuk tanda tangan --}}
                <span class="nama-pejabat">{{ $namaPejabatSatu ?? '( Dr. Murad Naser, M.Pd. )' }}</span>
                @if(isset($nipPejabatSatu) && $nipPejabatSatu)
                <br><span class="nip-pejabat">{{ $nipPejabatSatu }}</span>
                @endif
            </div>

            <div class="tandatangan kanan">
                {{-- Kosongkan jika hanya satu tanda tangan seperti di contoh, atau isi --}}
                {{-- <span class="jabatan">{{ $jabatanPejabatDua ?? 'Kepala Perpustakaan' }}</span>
                <br><br><br>
                <span class="nama-pejabat">{{ $namaPejabatDua ?? 'Nama Pejabat Dua' }}</span><br>
                <span class="nip-pejabat">{{ $nipPejabatDua ?? 'NIP. Pejabat Dua' }}</span> --}}
            </div>
        </div>

    </div>
</body>
</html>