{{-- File: resources/views/sertifikat/template_kegiatan_1.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Sertifikat</title>
    <style>
        /*
            PENTING: Styling untuk PDF dengan DOMPDF memerlukan CSS yang cermat.
            - Gunakan inline CSS atau blok <style> ini.
            - Hindari CSS eksternal jika tidak dikonfigurasi khusus untuk DOMPDF.
            - Fitur CSS modern mungkin terbatas. Uji tampilan secara berkala.
            - Path gambar: Pastikan path ke gambar background dan QR code benar.
                         `public_path()` digunakan untuk mendapatkan path absolut di server.
        */
        @page {
            margin: 0; /* Menghilangkan margin halaman default */
            size: A4 landscape; /* Ukuran kertas A4 dengan orientasi landscape */
        }
        body {
            font-family: 'Times New Roman', Times, serif; /* Font default, ganti jika perlu & pastikan font tersedia */
            margin: 0;
            padding: 0;
            background-color: #ffffff; /* Warna fallback jika gambar tidak termuat */
            /* Pastikan $namaFileTemplateGambar adalah nama file yang ada di public/images/ */
            background-image: url("{{ asset('templates_sertifikat/tpl_1747612092.png') }}");
            background-size: 100% 100%; /* Memastikan gambar mengisi seluruh halaman */
            background-repeat: no-repeat;
            background-position: center center;
            width: 297mm; /* Lebar A4 landscape */
            height: 210mm; /* Tinggi A4 landscape */
            position: relative; /* Diperlukan untuk positioning absolut elemen di dalamnya */
            box-sizing: border-box;
        }

        .container {
            width: 100%;
            height: 100%;
            position: relative;
            /* Sesuaikan padding ini agar konten tidak terlalu mepet ke tepi gambar template Anda */
            padding: 20mm 25mm;
            box-sizing: border-box;
        }

        /* Styling untuk elemen-elemen sertifikat */
        /* PENTING: Nilai 'top', 'left', 'right', 'bottom', 'font-size', dll., adalah CONTOH. */
        /* Anda HARUS menyesuaikannya berdasarkan desain template gambar Anda! */

        .nomor-sertifikat {
            position: absolute;
            top: 25mm; /* Contoh: Sesuaikan jarak dari atas */
            left: 25mm; /* Contoh: Sesuaikan jarak dari kiri */
            font-size: 11px;
            color: #444444;
        }

        .qr-code {
            position: absolute;
            top: 25mm; /* Contoh: Sesuaikan jarak dari atas */
            right: 25mm; /* Contoh: Sesuaikan jarak dari kanan */
            width: 30mm; /* Ukuran QR Code */
            height: 30mm;
        }

        .diberikan-kepada {
            position: absolute;
            top: 70mm; /* Contoh */
            left: 0;
            right: 0;
            text-align: center;
            font-size: 16px;
            color: #333333;
        }

        .nama-peserta {
            position: absolute;
            top: 80mm; /* Contoh */
            left: 0;
            right: 0;
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            color: #000000; /* Warna nama peserta, sesuaikan */
            text-transform: uppercase;
        }

        .sebagai {
            position: absolute;
            top: 95mm; /* Contoh */
            left: 0;
            right: 0;
            text-align: center;
            font-size: 14px;
            color: #333333;
        }

        .peran {
            position: absolute;
            top: 102mm; /* Contoh */
            left: 0;
            right: 0;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            color: #111111;
            text-transform: uppercase;
        }

        .dalam-kegiatan {
            position: absolute;
            top: 115mm; /* Contoh */
            left: 0;
            right: 0;
            text-align: center;
            font-size: 14px;
            color: #333333;
        }

        .judul-kegiatan-utama {
            position: absolute;
            top: 122mm; /* Contoh */
            left: 0;
            right: 0;
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            color: #000000;
            padding: 0 20mm; /* Padding agar judul panjang tidak keluar batas */
            line-height: 1.2;
             text-transform: uppercase;
        }
        .sub-judul-kegiatan {
            position: absolute;
            top: 135mm; /* Contoh, sesuaikan jika judul utama lebih dari 1 baris */
            left: 0;
            right: 0;
            text-align: center;
            font-size: 16px;
            color: #222222;
            padding: 0 20mm;
            line-height: 1.2;
        }

        /* Area Tanda Tangan */
        .area-tandatangan {
            position: absolute;
            bottom: 20mm; /* Jarak dari bawah halaman */
            width: 100%;
            left: 0;
        }

        .tandatangan {
            /* Menggunakan display: inline-block agar bisa diatur lebarnya dan berdampingan */
            display: inline-block;
            width: 48%; /* Bagi dua untuk dua tanda tangan, sisakan sedikit margin jika perlu */
            text-align: center;
            font-size: 12px;
            color: #333333;
            vertical-align: top; /* Agar align dari atas jika tingginya beda */
        }

        .tandatangan.kiri {
            /* Jika ingin sedikit geser ke kiri dari tengah mutlak */
             margin-left: 2%; /* Sesuaikan jika perlu */
        }

        .tandatangan.kanan {
            /* Jika ingin sedikit geser ke kanan dari tengah mutlak */
            /* margin-right: 2%; */ /* Sesuaikan jika perlu */
        }

        .jabatan {
            font-size: 13px;
            margin-bottom: 15mm; /* Jarak untuk area tanda tangan (cap & ttd basah) */
        }
        .nama-pejabat {
            font-weight: bold;
            text-decoration: underline;
            font-size: 13px;
        }
        .nip-pejabat {
            font-size: 11px;
        }

    </style>
</head>
<body>
    <div class="container">

        @if(!empty($nomorSertifikat))
        <div class="nomor-sertifikat">
            Nomor: {{ $nomorSertifikat }}
        </div>
        @endif

        @if(!empty($pathQrCode))
            {{-- Pastikan $pathQrCode adalah path absolut ke file QR di server --}}
            {{-- Atau jika berupa data URI: <img src="{{ $pathQrCode }}" alt="QR Code"> --}}
            <img src="{{ $pathQrCode }}" alt="QR Code" class="qr-code">
        @endif

        <div class="diberikan-kepada">
            Diberikan kepada:
        </div>

        <div class="nama-peserta">
            {{ $namaPeserta ?? 'Nama Peserta Tidak Ditemukan' }}
        </div>

        <div class="sebagai">
            atas partisipasinya sebagai
        </div>
        
        <div class="peran">
            {{ $peranText ?? 'Peserta' }}
        </div>

        <div class="dalam-kegiatan">
            dalam kegiatan
        </div>

        <div class="judul-kegiatan-utama">
            {{ $judulUtamaKegiatan ?? 'Judul Kegiatan Utama' }}
        </div>

        @if(!empty($subJudulKegiatan))
        <div class="sub-judul-kegiatan">
            {{ $subJudulKegiatan }}
        </div>
        @endif

        <div class="area-tandatangan">
            <div class="tandatangan kiri">
                <span class="jabatan">{{ $jabatanPejabatSatu ?? 'Jabatan Pejabat Satu' }}</span>
                <br><br><br> {/* Ruang untuk tanda tangan dan cap */}
                <span class="nama-pejabat">{{ $namaPejabatSatu ?? 'Nama Pejabat Satu' }}</span><br>
                <span class="nip-pejabat">{{ $nipPejabatSatu ?? 'NIP/NIK. Pejabat Satu' }}</span>
            </div>

            <div class="tandatangan kanan">
                Surabaya, {{ $tanggalKegiatanDisplay ?? \Carbon\Carbon::now()->translatedFormat('j F Y') }}<br>
                <span class="jabatan">{{ $jabatanPejabatDua ?? 'Kepala Perpustakaan' }}</span>
                 <br><br><br> {/* Ruang untuk tanda tangan dan cap */}
                <span class="nama-pejabat">{{ $namaPejabatDua ?? 'Nama Pejabat Dua' }}</span><br>
                <span class="nip-pejabat">{{ $nipPejabatDua ?? 'NIP/NIK. Pejabat Dua' }}</span>
            </div>
        </div>

    </div>
</body>
</html>
