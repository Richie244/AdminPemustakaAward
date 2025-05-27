<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MyApiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SertifikatGeneratorController extends Controller
{
    protected MyApiService $apiService;
    protected string $dummyKegiatanIdForTemplate;
    protected string $globalTemplateNimIdentifier;

    public function __construct(MyApiService $apiService)
    {
        $this->apiService = $apiService;
        $this->dummyKegiatanIdForTemplate = config('app.dummy_kegiatan_id_for_template', '0'); //
        $this->globalTemplateNimIdentifier = config('app.global_template_nim_identifier', 'TPLGLB'); //
    }

    public function generateUntukKegiatanSatu(Request $request, $idKegiatan, $nim, $peran = null)
    {
        // $idKegiatan sudah menjadi parameter dinamis
        $peranPeserta = $peran ?? $request->input('peran', 'PESERTA'); // Ambil peran dari URL atau request, default 'PESERTA'
        $namaViewBlade = 'sertifikat.template_global_dinamis'; // View baru

        Log::info("[GENERATE_SERTIFIKAT_GLOBAL] Memulai untuk NIM: {$nim}, ID Kegiatan: {$idKegiatan}, Peran: {$peranPeserta}");

        try {
            // 1. Ambil Data Template Global dari tabel SERTIFIKAT_PUST
            $namaFileBackground = null;
            $pathBackgroundAbsolut = null;
            $responseSertifikatGlobal = $this->apiService->getSertifikatList([
                // Filter untuk mendapatkan template global
                // Asumsi API bisa memfilter berdasarkan id_kegiatan dan nim
                'id_kegiatan' => $this->dummyKegiatanIdForTemplate,
                'nim' => $this->globalTemplateNimIdentifier
            ]);
            
            if ($responseSertifikatGlobal && !isset($responseSertifikatGlobal['_error']) && is_array($responseSertifikatGlobal) && !empty($responseSertifikatGlobal)) {
                // API mungkin mengembalikan array, ambil yang pertama jika ada
                $templateData = (object) (isset($responseSertifikatGlobal[0]) ? $responseSertifikatGlobal[0] : $responseSertifikatGlobal);
                 if (isset($templateData->nama_file)) {
                    $namaFileBackground = $templateData->nama_file;
                    // Penting: Pastikan path ini benar dan bisa diakses oleh DomPDF
                    // DomPDF biasanya butuh path absolut di server.
                    $pathBackgroundAbsolut = storage_path('app/public/templates_sertifikat/' . $namaFileBackground); //
                    Log::info("[GENERATE_SERTIFIKAT_GLOBAL] Template background ditemukan: {$namaFileBackground}, path: {$pathBackgroundAbsolut}");
                 } else {
                    Log::warning("[GENERATE_SERTIFIKAT_GLOBAL] Template background global ditemukan di API namun tidak ada nama_file.");
                 }
            } else {
                // Fallback jika filter di atas tidak mengembalikan hasil atau error
                $allSertifikatResult = $this->apiService->getSertifikatList();
                if($allSertifikatResult && !isset($allSertifikatResult['_error']) && is_array($allSertifikatResult)){
                    $globalTemplateData = collect($allSertifikatResult)->first(function($sert) {
                        $sert = (object) $sert;
                        return (string)($sert->id_kegiatan ?? null) === (string)$this->dummyKegiatanIdForTemplate &&
                               strtoupper($sert->nim ?? '') === strtoupper($this->globalTemplateNimIdentifier);
                    });
                    if($globalTemplateData) {
                        $templateData = (object) $globalTemplateData;
                        if (isset($templateData->nama_file)) {
                            $namaFileBackground = $templateData->nama_file;
                            $pathBackgroundAbsolut = storage_path('app/public/templates_sertifikat/' . $namaFileBackground); //
                            Log::info("[GENERATE_SERTIFIKAT_GLOBAL] Template background ditemukan (fallback): {$namaFileBackground}, path: {$pathBackgroundAbsolut}");
                        }
                    }
                }
            }


            if (!$namaFileBackground || !file_exists($pathBackgroundAbsolut)) {
                Log::error("[GENERATE_SERTIFIKAT_GLOBAL] File template background global '{$namaFileBackground}' tidak ditemukan di storage atau tidak terdefinisi.");
                return response("Template sertifikat global tidak ditemukan.", 404);
            }

            // 2. Ambil Data Kegiatan berdasarkan $idKegiatan
            $kegiatanData = null;
            $responseKegiatan = $this->apiService->getKegiatanList(); // Ambil semua, lalu filter
            if ($responseKegiatan && !isset($responseKegiatan['_error']) && is_array($responseKegiatan)) {
                $found = collect($responseKegiatan)->first(function ($item) use ($idKegiatan) {
                    $k = (object) $item;
                    return ($k->id_kegiatan ?? $k->ID_KEGIATAN ?? null) == $idKegiatan;
                });
                if ($found) $kegiatanData = (object) $found;
            }

            if (!$kegiatanData) {
                Log::error("[GENERATE_SERTIFIKAT_GLOBAL] Data kegiatan ID {$idKegiatan} tidak ditemukan.");
                return response("Data kegiatan tidak ditemukan", 404);
            }
            // Ambil JUDUL_KEGIATAN untuk ditampilkan di sertifikat
            $judulKegiatanFormat = strtoupper($kegiatanData->judul_kegiatan ?? $kegiatanData->JUDUL_KEGIATAN ?? 'NAMA KEGIATAN');

            // 3. Ambil Data Peserta (Civitas) berdasarkan NIM
            $pesertaData = null;
            $responseCivitas = $this->apiService->getCivitasList(); // Ambil semua, lalu filter
            if ($responseCivitas && !isset($responseCivitas['_error']) && is_array($responseCivitas)) {
                $foundCivitas = collect($responseCivitas)->first(function($item) use ($nim){
                    $c = (object) $item;
                    return ($c->id_civitas ?? $c->ID_CIVITAS ?? null) == $nim;
                });
                if ($foundCivitas) $pesertaData = (object) $foundCivitas;
            }

            if (!$pesertaData) {
                Log::error("[GENERATE_SERTIFIKAT_GLOBAL] Data peserta tidak ditemukan untuk NIM: {$nim}");
                return response("Data peserta tidak ditemukan", 404);
            }
            $namaPeserta = strtoupper($pesertaData->nama ?? $pesertaData->NAMA ?? $nim);


            // Data Tanda Tangan (bisa statis atau dinamis jika perlu)
            // Ambil contoh dari template_kegiatan_1.blade.php atau sesuaikan
            $jabatanPejabatSatu = 'Ketua Panitia Pelaksana,'; // Contoh dari image_44feb4.png
            $namaPejabatSatu = '( Dr. Murad Naser, M.Pd. )';   // Contoh dari image_44feb4.png
            // Jika ada pemateri yang relevan dengan kegiatan ini dan ingin ditampilkan
            // Anda bisa mengambilnya dari $kegiatanData->pemateri atau $kegiatanData->jadwal
            // Misalnya, jika pemateri pertama dari sesi pertama adalah ketua pelaksana
            if (isset($kegiatanData->jadwal) && $kegiatanData->jadwal->isNotEmpty()) {
                $sesiPertama = $kegiatanData->jadwal->first();
                if ($sesiPertama && isset($sesiPertama->id_pemateri)) {
                    $pemateriDataList = $this->apiService->getPemateriKegiatanList();
                    if ($pemateriDataList && !isset($pemateriDataList['_error'])) {
                        $foundMasterPemateri = collect($pemateriDataList)->firstWhere('id_pemateri', $sesiPertama->id_pemateri);
                        if($foundMasterPemateri){
                            // $jabatanPejabatSatu = "Pemateri"; // Sesuaikan jika perlu
                            // $namaPejabatSatu = $foundMasterPemateri->nama_pemateri ?? $namaPejabatSatu;
                            // $nipPejabatSatu = null; // Jika NIP tidak ada untuk pemateri
                        }
                    }
                }
            }


            // 4. Siapkan data untuk view
            $dataUntukView = [
                'namaPeserta'           => $namaPeserta,
                'judulKegiatanFormat'   => $judulKegiatanFormat, // Ini yang akan menggantikan "Peserta"
                // 'peranText'             => strtoupper($peranPeserta), // Tidak lagi digunakan jika judul kegiatan menggantikan peran
                'pathBackgroundAbsolut' => $pathBackgroundAbsolut, // Path absolut ke gambar background
                'nomorSertifikat'       => '12345678', // Contoh nomor dari image_44feb4.png, buat dinamis jika perlu
                // Data tanda tangan
                'jabatanPejabatSatu'    => $jabatanPejabatSatu,
                'namaPejabatSatu'       => $namaPejabatSatu,
                // 'nipPejabatSatu'        => $nipPejabatSatu ?? null,
                // Anda bisa tambahkan pejabat kedua jika ada
            ];

            // 5. Generate PDF
            $pdf = PDF::loadView($namaViewBlade, $dataUntukView)->setPaper('a4', 'landscape');

            // 6. Simpan record sertifikat ke API (jika belum ada untuk kombinasi nim & id_kegiatan ini)
            $recordSertifikatExists = false;
            $sertifikatRecords = $this->apiService->getSertifikatList(['nim' => $nim, 'id_kegiatan' => $idKegiatan]);
            if ($sertifikatRecords && !isset($sertifikatRecords['_error']) && !empty($sertifikatRecords)) {
                $recordSertifikatExists = true;
            }

            if (!$recordSertifikatExists) {
                $nextIdSertifikat = $this->apiService->getNextId('sertifikat', 'id_sertifikat');
                $apiRequiresId = env('API_REQUIRES_ID_ON_CREATE', true);

                if ($apiRequiresId && $nextIdSertifikat === null) {
                    Log::error("[GENERATE_SERTIFIKAT_GLOBAL] Gagal generate ID untuk record sertifikat. PDF tetap dibuat.");
                } else {
                    // Nama file untuk disimpan di DB SERTIFIKAT_PUST bisa jadi referensi ke template global
                    // atau bisa juga nama PDF yang di-generate jika ingin menyimpan PDF per peserta.
                    // Untuk saat ini, kita anggap NAMA_FILE di SERTIFIKAT_PUST adalah nama file PDF unik per peserta.
                    $namaFilePdfPeserta = 'sertifikat_peserta_' . Str::slug($namaPeserta) . '_kegiatan_' . $idKegiatan . '_' . time() . '.pdf';

                    $dataSertifikatApi = [
                        'nim' => $nim,
                        'id_kegiatan' => $idKegiatan,
                        'nama_file' => $namaFilePdfPeserta, 
                    ];
                    if ($apiRequiresId && $nextIdSertifikat !== null) {
                        $dataSertifikatApi['id'] = $nextIdSertifikat;
                    }

                    Log::info("[GENERATE_SERTIFIKAT_GLOBAL] Data yang akan dikirim ke createSertifikat API:", $dataSertifikatApi);
                    $resultApi = $this->apiService->createSertifikat($dataSertifikatApi);

                    if ($resultApi && !isset($resultApi['_error']) && (isset($resultApi['id']) || isset($resultApi['id_sertifikat']) || isset($resultApi['_success_no_content']) || ($resultApi['success'] ?? false) === true)) {
                         Log::info("[GENERATE_SERTIFIKAT_GLOBAL] Record sertifikat untuk NIM {$nim}, Kegiatan {$idKegiatan} berhasil disimpan ke API.");
                    } else {
                        Log::error("[GENERATE_SERTIFIKAT_GLOBAL] Gagal menyimpan record sertifikat ke API.", ['request' => $dataSertifikatApi, 'response' => $resultApi ?? []]);
                    }
                }
            } else {
                Log::info("[GENERATE_SERTIFIKAT_GLOBAL] Record sertifikat untuk NIM {$nim}, Kegiatan {$idKegiatan} sudah ada. Tidak membuat record baru.");
            }

            // 7. Kembalikan PDF untuk di-download
            $namaFileDownload = 'Sertifikat_' . Str::slug($namaPeserta) . '_' . Str::slug($judulKegiatanFormat) . '.pdf';
            return $pdf->download($namaFileDownload);

        } catch (\Exception $e) {
            Log::error("[GENERATE_SERTIFIKAT_GLOBAL] Exception: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response("Terjadi kesalahan saat generate sertifikat PDF: " . $e->getMessage(), 500);
        }
    }
}