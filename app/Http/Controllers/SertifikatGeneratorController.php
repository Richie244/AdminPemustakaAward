<?php

namespace App\Http\Controllers; // Sesuaikan namespace jika berbeda

use Illuminate\Http\Request;
use App\Services\MyApiService; // Asumsi Anda menggunakan ini untuk ambil data
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SertifikatGeneratorController extends Controller
{
    protected MyApiService $apiService;

    public function __construct(MyApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Generate sertifikat untuk peserta NIM tertentu pada kegiatan.
     * ID Kegiatan sekarang diambil dari variabel $idKegiatan.
     *
     * @param string $nim
     * @return \Illuminate\Http\Response
     */
    public function generateUntukKegiatanSatu(Request $request, $nim)
    {
        $idKegiatan = '0'; // ID Kegiatan spesifik
        $peranPeserta = $request->input('peran', 'PESERTA');
        $namaFileTemplateGambar = 'Sertifikat.jpg';
        $namaViewBlade = 'template_kegiatan_1'; // Pastikan path ini benar (misal, 'sertifikat.template_kegiatan_1' jika di subfolder)

        Log::info("[GENERATE_SERTIFIKAT_KEGIATAN] Memulai untuk NIM: {$nim}, ID Kegiatan: {$idKegiatan}, Peran: {$peranPeserta}");

        try {
            // 1. Ambil Data Kegiatan
            $kegiatanData = null;
            $responseKegiatan = $this->apiService->getKegiatanList();
            if ($responseKegiatan && !isset($responseKegiatan['_error']) && is_array($responseKegiatan)) {
                $found = collect($responseKegiatan)->firstWhere('id_kegiatan', (string)$idKegiatan);
                if ($found) $kegiatanData = (object) $found;
            }

            if (!$kegiatanData) {
                Log::error("[GENERATE_SERTIFIKAT_KEGIATAN] Data kegiatan ID {$idKegiatan} tidak ditemukan.");
                return response("Data kegiatan tidak ditemukan", 404);
            }

            $judulParts = explode(" - ", $kegiatanData->judul_kegiatan ?? '');
            $judulUtamaKegiatan = strtoupper($judulParts[0] ?? $kegiatanData->judul_kegiatan ?? 'JUDUL KEGIATAN');
            $subJudulKegiatan = $judulParts[1] ?? '';
            if (empty($subJudulKegiatan) && isset($kegiatanData->keterangan) && strlen($kegiatanData->keterangan) < 100) {
                $subJudulKegiatan = '"'.$kegiatanData->keterangan.'"';
            }

            $tanggalKegiatanDisplay = Carbon::now()->translatedFormat('j F Y');
            if (isset($kegiatanData->jadwal) && is_array($kegiatanData->jadwal) && count($kegiatanData->jadwal) > 0 && isset($kegiatanData->jadwal[0]->tgl_kegiatan)) {
                try {
                    $tanggalKegiatanDisplay = Carbon::parse($kegiatanData->jadwal[0]->tgl_kegiatan)->translatedFormat('j F Y');
                } catch (\Exception $e) {
                    Log::warning("[GENERATE_SERTIFIKAT_KEGIATAN] Gagal parse tanggal dari jadwal[0]->tgl_kegiatan: " . $kegiatanData->jadwal[0]->tgl_kegiatan);
                }
            } elseif (isset($kegiatanData->TGL_KEGIATAN_DARI_API)) {
                 try {
                    $tanggalKegiatanDisplay = Carbon::parse($kegiatanData->TGL_KEGIATAN_DARI_API)->translatedFormat('j F Y');
                } catch (\Exception $e) {
                    Log::warning("[GENERATE_SERTIFIKAT_KEGIATAN] Gagal parse tanggal dari TGL_KEGIATAN_DARI_API: " . $kegiatanData->TGL_KEGIATAN_DARI_API);
                }
            }

            // 2. Ambil Data Peserta (Civitas) berdasarkan NIM
            $pesertaData = null;
            $responseCivitas = $this->apiService->getCivitasList();
            if ($responseCivitas && !isset($responseCivitas['_error']) && is_array($responseCivitas)) {
                $foundCivitas = collect($responseCivitas)->firstWhere('id_civitas', (string)$nim);
                if ($foundCivitas) $pesertaData = (object) $foundCivitas;
            }

            if (!$pesertaData) {
                Log::error("[GENERATE_SERTIFIKAT_KEGIATAN] Data peserta tidak ditemukan untuk NIM: {$nim}");
                return response("Data peserta tidak ditemukan", 404);
            }

            // 3. Siapkan data untuk view
            $dataUntukView = [
                'namaPeserta'           => $pesertaData->nama ?? $nim,
                'peranText'             => strtoupper($peranPeserta),
                'judulUtamaKegiatan'    => $judulUtamaKegiatan,
                'subJudulKegiatan'      => $subJudulKegiatan,
                'tanggalKegiatanDisplay'=> strtoupper($tanggalKegiatanDisplay),
                'namaFileTemplateGambar'=> $namaFileTemplateGambar,
                'nomorSertifikat'       => 'SERT/' . date('Y/m') . '/' . strtoupper(Str::random(5)),
                'pathQrCode'            => null,
                'jabatanPejabatSatu'    => 'Ketua Pelaksana',
                'namaPejabatSatu'       => 'Nama Ketua Pelaksana, S.Kom.',
                'nipPejabatSatu'        => 'NIP. 123456789012345',
                'jabatanPejabatDua'     => 'Kepala Perpustakaan',
                'namaPejabatDua'        => 'Deasy Kumalawati, S.Pd., M.A.',
                'nipPejabatDua'         => 'NIP. XX YY ZZ',
            ];

            // 4. Generate PDF
            $pdf = PDF::loadView($namaViewBlade, $dataUntukView)->setPaper('a4', 'landscape');

            // 5. Simpan PDF ke server
            // Nama file yang deskriptif untuk disimpan di storage dan diunduh pengguna
            $namaPesertaSlug = Str::slug($pesertaData->nama ?? $nim);
            $namaFileUntukStorage = 'sertifikat_' . $nim . '_kegiatan_' . $idKegiatan . '_' . $namaPesertaSlug . '.pdf';
            
            // PERUBAHAN: Nama file yang SANGAT PENDEK untuk dikirim ke API
            // Ini untuk menghindari ORA-12899 jika kolom NAMA_FILE di Oracle sangat pendek
            // Contoh: menghasilkan nama file acak 8 karakter + .pdf (total 12 karakter)
            // Sesuaikan panjang Str::random() jika diperlukan, misal 5 atau 6 jika batas lebih ketat.
            $namaFileUntukApi = Str::lower(Str::random(8)) . '.pdf'; 

            $pathStorage = 'public/sertifikat_generated/';
            Storage::put($pathStorage . $namaFileUntukStorage, $pdf->output()); // Simpan dengan nama deskriptif
            Log::info("[GENERATE_SERTIFIKAT_KEGIATAN] Sertifikat PDF berhasil digenerate: " . $pathStorage . $namaFileUntukStorage);

            // 6. Catat ke tabel SERTIFIKAT_PUST (melalui API)
            $nextIdSertifikat = $this->apiService->getNextId('sertifikat', 'id_sertifikat');
            $apiRequiresId = env('API_REQUIRES_ID_ON_CREATE', true);

            if ($apiRequiresId && $nextIdSertifikat === null) {
                Log::error("[GENERATE_SERTIFIKAT_KEGIATAN] Gagal generate ID untuk record sertifikat. PDF tetap dibuat.");
                // Tidak langsung return error, biarkan PDF di-download.
            }

            $dataSertifikatApi = [
                'nim' => $nim,
                'id_kegiatan' => $idKegiatan,
                'nama_file' => $namaFileUntukApi, // KIRIM NAMA FILE PENDEK KE API
            ];

            if ($apiRequiresId && $nextIdSertifikat !== null) {
                $dataSertifikatApi['id'] = $nextIdSertifikat;
            }

            Log::info("[GENERATE_SERTIFIKAT_KEGIATAN] Data yang akan dikirim ke createSertifikat API:", $dataSertifikatApi);
            $resultApi = $this->apiService->createSertifikat($dataSertifikatApi);

            $apiSuccess = false;
            if ($resultApi) {
                if (isset($resultApi['success']) && $resultApi['success'] === true) {
                    $apiSuccess = true;
                } elseif (!isset($resultApi['_error']) && (isset($resultApi['id']) || (isset($this->apiService) && method_exists($this->apiService, 'getPrimaryKeyName') && isset($resultApi[$this->apiService->getPrimaryKeyName('sertifikat')])) )) {
                    $apiSuccess = true;
                }
            }

            if (!$apiSuccess) {
                Log::error("[GENERATE_SERTIFIKAT_KEGIATAN] Gagal menyimpan record sertifikat ke API.", ['request' => $dataSertifikatApi, 'response' => $resultApi ?? []]);
                // Pertimbangkan untuk memberi tahu pengguna bahwa penyimpanan DB gagal, misal via flash message jika ini bukan direct download.
            } else {
                Log::info("[GENERATE_SERTIFIKAT_KEGIATAN] Record sertifikat berhasil disimpan ke API.");
            }

            // 7. Kembalikan PDF untuk di-download (gunakan nama deskriptif untuk pengguna)
            return $pdf->download($namaFileUntukStorage);

        } catch (\Exception $e) {
            Log::error("[GENERATE_SERTIFIKAT_KEGIATAN] Exception: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response("Terjadi kesalahan saat generate sertifikat PDF: " . $e->getMessage(), 500);
        }
    }
}
