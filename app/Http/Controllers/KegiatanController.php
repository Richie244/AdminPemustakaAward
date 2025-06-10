<?php

namespace App\Http\Controllers;

use App\Services\MyApiService; // Pastikan service API Anda di-import
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage; // Untuk menghapus file
use Illuminate\Support\Str; // Untuk Str::random

class KegiatanController extends Controller
{
    protected MyApiService $apiService;
    protected string $dummyKegiatanIdForTemplate;
    protected string $globalTemplateNimIdentifier;

    public function __construct(MyApiService $apiService)
    {
        $this->apiService = $apiService;
        $this->dummyKegiatanIdForTemplate = config('app.dummy_kegiatan_id_for_template', '0');
        $this->globalTemplateNimIdentifier = config('app.global_template_nim_identifier', 'TPLGLB');
    }

    protected function paginate(Collection $items, $perPage = 10, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $paginatorOptions = [
            'path' => $options['path'] ?? Paginator::resolveCurrentPath(),
            'query' => request()->except('page'),
        ];
        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            $paginatorOptions
        );
    }

    public function index(Request $request)
    {
        $searchTerm = $request->input('search');
        $perPage = 10;
        $currentPage = Paginator::resolveCurrentPage('page');
        
        $processedKegiatanList = new Collection();

        try {
            $allJadwalResult = $this->apiService->getJadwalKegiatanList();
            $allJadwal = ($allJadwalResult && !isset($allJadwalResult['_error']) && is_array($allJadwalResult)) ? collect($allJadwalResult)->map(fn($item) => (object) $item) : new Collection();

            $allMasterPemateriResult = $this->apiService->getPemateriKegiatanList();
            $allMasterPemateri = ($allMasterPemateriResult && !isset($allMasterPemateriResult['_error']) && is_array($allMasterPemateriResult)) ? collect($allMasterPemateriResult)->map(fn($item) => (object) $item) : new Collection();
            
            $responseKegiatanFromApi = $this->apiService->getKegiatanList();

            if ($responseKegiatanFromApi && !isset($responseKegiatanFromApi['_error']) && is_array($responseKegiatanFromApi)) {
                $allRawKegiatan = collect($responseKegiatanFromApi)->map(fn($itemArray) => (object) $itemArray);

                foreach ($allRawKegiatan as $rawKegiatanObject) {
                    $k = clone $rawKegiatanObject;
                    $idKegiatanUtama = $k->id_kegiatan ?? $k->ID_KEGIATAN ?? null;

                    if (!$idKegiatanUtama) {
                        $k->jadwal = new Collection();
                        $k->pemateri = new Collection();
                        $processedKegiatanList->push($k);
                        continue;
                    }

                    $k->jadwal = $allJadwal->filter(function ($jadwal) use ($idKegiatanUtama) {
                        return ($jadwal->id_kegiatan ?? $jadwal->ID_KEGIATAN ?? null) == $idKegiatanUtama;
                    })->sortBy(function($jadwal) {
                        $tgl = $jadwal->tgl_kegiatan ?? '1970-01-01 00:00:00';
                        $waktu = $jadwal->waktu_mulai ?? $tgl;
                        try { return \Carbon\Carbon::parse($waktu)->timestamp; } catch (\Exception $e) { try { return \Carbon\Carbon::parse($tgl)->timestamp; } catch (\Exception $ex) { return 0;}}
                    })->values();

                    $k->pemateri = new Collection();
                    if ($k->jadwal->isNotEmpty()) {
                        foreach($k->jadwal as $jadwalItem) {
                            $idPemateriDiJadwal = $jadwalItem->id_pemateri ?? null;
                            if ($idPemateriDiJadwal) {
                                $foundMasterPemateri = $allMasterPemateri->firstWhere('id_pemateri', $idPemateriDiJadwal);
                                if($foundMasterPemateri){
                                    $namaPemateri = $foundMasterPemateri->nama_pemateri ?? 'Nama Pemateri Tidak Ditemukan';
                                    if (!$k->pemateri->contains('id_pemateri', $idPemateriDiJadwal)) {
                                        $k->pemateri->push((object)['id_pemateri' => $idPemateriDiJadwal, 'nama_pemateri' => $namaPemateri]);
                                    }
                                }
                            }
                        }
                    }
                    $processedKegiatanList->push($k);
                }
            } else {
                Log::error('Gagal mengambil data kegiatan dari API atau format tidak sesuai.', $responseKegiatanFromApi ?? []);
                $processedKegiatanList = new Collection();
            }
        } catch (\Exception $e) {
            Log::error('[INDEX_KEGIATAN] Exception utama saat memproses data kegiatan: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $processedKegiatanList = new Collection();
        }

        $kegiatanToPaginate = $processedKegiatanList;
        if ($searchTerm) {
            $kegiatanToPaginate = $processedKegiatanList->filter(function ($k) use ($searchTerm) {
                $judulMatch = stripos($k->judul_kegiatan ?? ($k->JUDUL_KEGIATAN ?? ''), $searchTerm) !== false;
                $lokasiMatch = stripos($k->lokasi ?? ($k->LOKASI ?? ''), $searchTerm) !== false;
                
                $pemateriMatch = false;
                if (isset($k->pemateri) && $k->pemateri->isNotEmpty()) {
                    foreach ($k->pemateri as $p) {
                        if (isset($p->nama_pemateri) && stripos($p->nama_pemateri, $searchTerm) !== false) {
                            $pemateriMatch = true;
                            break;
                        }
                    }
                }
                return $judulMatch || $lokasiMatch || $pemateriMatch;
            });
        }
        
        $totalKegiatan = $kegiatanToPaginate->count();
        $currentItemsForPage = $kegiatanToPaginate->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $kegiatanPaginator = new LengthAwarePaginator(
            $currentItemsForPage,
            $totalKegiatan,
            $perPage,
            $currentPage,
            ['path' => route('kegiatan.index')]
        );

        $kegiatanPaginator->appends($request->query());

        return view('kegiatan', [
            'kegiatan' => $kegiatanPaginator,
            'searchTerm' => $searchTerm
        ]);
    }

    private function getProcessedMasterPemateri()
    {
        $masterPemateri = new Collection();
        $error_message_pemateri = null;

        try {
            $responsePerusahaan = $this->apiService->getPerusahaanPemateriList();
            $allPerusahaan = new Collection();
            if ($responsePerusahaan && !isset($responsePerusahaan['_error'])) {
                $perusahaanDataFromApi = isset($responsePerusahaan['data']) && is_array($responsePerusahaan['data']) ? $responsePerusahaan['data'] : (is_array($responsePerusahaan) ? $responsePerusahaan : []);
                if (!empty($perusahaanDataFromApi)) {
                    $allPerusahaan = collect($perusahaanDataFromApi)
                                    ->map(fn($item) => (object) $item)
                                    ->filter(fn($p) => isset($p->id_perusahaan) || isset($p->ID_PERUSAHAAN))
                                    ->keyBy(function($p) {
                                        return $p->id_perusahaan ?? $p->ID_PERUSAHAAN ?? null;
                                    });
                }
            } else {
                Log::warning('[GET_MASTER_PEMATERI] Gagal mengambil daftar perusahaan.', $responsePerusahaan ?? []);
            }

            $responsePemateri = $this->apiService->getPemateriKegiatanList();
            
            if ($responsePemateri && !isset($responsePemateri['_error']) && !isset($responsePemateri['_success_no_content'])) {
                $dataFromApi = isset($responsePemateri['data']) && is_array($responsePemateri['data']) ? $responsePemateri['data'] : (is_array($responsePemateri) ? $responsePemateri : []);
                
                if (!empty($dataFromApi)) {
                    $masterPemateri = collect($dataFromApi)->map(function($item) use ($allPerusahaan) {
                        $pemateriObj = (object) $item;
                        $pemateriObj->id_pemateri = $pemateriObj->id_pemateri ?? $pemateriObj->ID_PEMATERI ?? null;
                        $pemateriObj->nama_pemateri = $pemateriObj->nama_pemateri ?? $pemateriObj->NAMA_PEMATERI ?? 'Nama Tidak Ada';
                        
                        $idPerusahaanPemateri = $pemateriObj->id_perusahaan ?? $pemateriObj->ID_PERUSAHAAN ?? null;
                        $pemateriObj->id_perusahaan_numeric = is_numeric($idPerusahaanPemateri) ? (int)$idPerusahaanPemateri : null;

                        if ($pemateriObj->id_perusahaan_numeric === 1) { 
                            $pemateriObj->tipe_pemateri = 'Internal';
                            $pemateriObj->nama_perusahaan_display = 'Universitas Dinamika';
                        } else if ($pemateriObj->id_perusahaan_numeric !== null && $allPerusahaan->has($pemateriObj->id_perusahaan_numeric)) {
                            $perusahaan = $allPerusahaan->get($pemateriObj->id_perusahaan_numeric);
                            $pemateriObj->tipe_pemateri = 'Eksternal';
                            $pemateriObj->nama_perusahaan_display = $perusahaan->nama_perusahaan ?? 'Perusahaan Tidak Diketahui';
                        } else {
                            $pemateriObj->tipe_pemateri = 'Eksternal (Individu)';
                            $pemateriObj->nama_perusahaan_display = '-';
                        }
                        return $pemateriObj;
                    });
                }
            } elseif ($responsePemateri && isset($responsePemateri['_error'])) {
                $error_message_pemateri = $responsePemateri['_json_error_data']['message'] ?? ($responsePemateri['_body'] ?? 'Gagal memuat data pemateri dari API.');
                Log::error('[GET_MASTER_PEMATERI] API Error: ' . $error_message_pemateri, $responsePemateri);
            }

        } catch (\Exception $e) {
            Log::error('[GET_MASTER_PEMATERI] Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $error_message_pemateri = 'Terjadi kesalahan sistem saat memuat data pemateri.';
        }

        if ($error_message_pemateri) {
            Log::error("Error saat memproses master pemateri: " . $error_message_pemateri);
        }
        return $masterPemateri;
    }

    public function create()
    {
        $masterPemateri = $this->getProcessedMasterPemateri();
        
        if ($masterPemateri->isEmpty()) {
             Log::warning('[KEGIATAN_CREATE] Tidak ada data master pemateri yang dapat dimuat untuk form tambah kegiatan.');
        }
        return view('tambah-kegiatan', compact('masterPemateri'));
    }

    public function store(Request $request)
    {
        Log::info('[STORE_KEGIATAN] Menerima request data:', $request->all());

        $validatedData = $request->validate([
             'judul' => 'required|string|max:100',
             'media' => 'required|string|max:20',
             'lokasi' => 'nullable|string|max:50',
             'keterangan_kegiatan' => 'nullable|string',
             'bobot_kegiatan' => 'required|integer|min:0',
             'sesi' => 'required|array|min:1',
             'sesi.*.tanggal' => 'required|date_format:Y-m-d',
             'sesi.*.jam_mulai' => 'required|date_format:H:i',
             'sesi.*.jam_selesai' => 'nullable|date_format:H:i|after_or_equal:sesi.*.jam_mulai',
             'sesi.*.id_pemateri' => 'required|numeric'
        ]);

        $nextKegiatanId = $this->apiService->getNextId('kegiatan', 'id_kegiatan');

        if ($nextKegiatanId === null) {
            Log::error('[STORE_KEGIATAN] Gagal men-generate ID Kegiatan.');
            return back()->withInput()->withErrors(['api_error_kegiatan' => 'Gagal men-generate ID Kegiatan. Periksa koneksi atau log API.']);
        }
        
        $apiKegiatanData = [
            'id_kegiatan' => $nextKegiatanId,
            'judul_kegiatan' => $validatedData['judul'],
            'media' => $validatedData['media'],
            'lokasi' => $validatedData['lokasi'] ?? '',
            'keterangan' => $validatedData['keterangan_kegiatan'] ?? '',
        ];
        Log::info('[STORE_KEGIATAN] Mengirim data kegiatan utama ke API:', $apiKegiatanData);
        $responseKegiatan = $this->apiService->createKegiatan($apiKegiatanData);
        
        if (!$responseKegiatan || isset($responseKegiatan['_error']) || ($responseKegiatan['success'] ?? false) !== true) {
            Log::error('[STORE_KEGIATAN] Gagal menyimpan kegiatan utama via API.', $responseKegiatan ?? []);
            $apiMessage = $responseKegiatan['_json_error_data']['message'] ?? ($responseKegiatan['_body'] ?? 'Error tidak diketahui dari API.');
            $apiErrors = isset($responseKegiatan['_json_error_data']['errors']) ? ' Details: '.json_encode($responseKegiatan['_json_error_data']['errors']) : '';
            return back()->withInput()->withErrors(['api_error_kegiatan' => 'Gagal menyimpan data kegiatan utama: ' . $apiMessage . $apiErrors]);
        }
        Log::info('[STORE_KEGIATAN] Respon API kegiatan utama:', $responseKegiatan);
        $createdKegiatanId = $nextKegiatanId;
        
        if (isset($validatedData['sesi']) && is_array($validatedData['sesi'])) {
            foreach ($validatedData['sesi'] as $indexSesi => $dataSesi) {
                $nextJadwalId = $this->apiService->getNextId('jadwal-kegiatan', 'id_jadwal');
                if ($nextJadwalId === null) {
                    Log::error("[STORE_KEGIATAN] Gagal men-generate ID Jadwal untuk sesi index {$indexSesi}.");
                    continue;
                }

                $waktuMulaiString = $dataSesi['tanggal'] . ' ' . $dataSesi['jam_mulai'] . ':00';
                $waktuSelesaiString = (isset($dataSesi['jam_selesai']) && !empty($dataSesi['jam_selesai'])) ? $dataSesi['tanggal'] . ' ' . $dataSesi['jam_selesai'] . ':00' : null;
                
                $idPemateriUntukSesiIni = $dataSesi['id_pemateri'] ?? 0;
                if (empty($idPemateriUntukSesiIni) && $idPemateriUntukSesiIni !== 0) {
                     $idPemateriUntukSesiIni = 0;
                }

                $apiJadwalData = [
                    'id' => $nextJadwalId,
                    'id_kegiatan' => $createdKegiatanId,
                    'tgl_kegiatan' => $dataSesi['tanggal'],
                    'waktu_mulai' => $waktuMulaiString,
                    'waktu_selesai' => $waktuSelesaiString,
                    'bobot' => $validatedData['bobot_kegiatan'],
                    'keterangan' => $validatedData['keterangan_kegiatan'] ?? ('Sesi ke-' . ($indexSesi + 1) . ' untuk ' . $validatedData['judul']),
                    'id_pemateri' => (int) $idPemateriUntukSesiIni,
                    'kode_random' => Str::upper(Str::random(10))
                ];
                
                Log::info("[STORE_KEGIATAN] Mengirim data jadwal (Sesi ".($indexSesi+1).") ke API:", $apiJadwalData);
                $responseJadwal = $this->apiService->createJadwalKegiatan($apiJadwalData);
                if (!$responseJadwal || isset($responseJadwal['_error']) || ($responseJadwal['success'] ?? false) !== true) {
                    Log::error('[STORE_KEGIATAN] Gagal menyimpan jadwal kegiatan (Sesi '.($indexSesi+1).') via API.', $responseJadwal ?? []);
                } else {
                    Log::info('[STORE_KEGIATAN] Respon API jadwal (Sesi '.($indexSesi+1).'):', $responseJadwal);
                }
            }
        }
        return redirect()->route('kegiatan.index')->with('success', 'Kegiatan berhasil disimpan.');
    }

    public function show(string $id)
    {
        $kegiatan = null;
        $templateSertifikatGlobal = null; // Untuk menyimpan info template global
        Log::info("[SHOW_KEGIATAN] Memulai pengambilan data untuk ID Kegiatan: {$id}");
        try {
            $kegiatanListResult = $this->apiService->getKegiatanList();
            if ($kegiatanListResult && !isset($kegiatanListResult['_error']) && is_array($kegiatanListResult)) {
                
                $foundItemArray = collect($kegiatanListResult)->first(function ($itemArray) use ($id) {
                    $itemAsObject = (object) $itemArray;
                    return ($itemAsObject->id_kegiatan ?? $itemAsObject->ID_KEGIATAN ?? null) == $id;
                });

                if ($foundItemArray) {
                    $rawKegiatanObject = (object) $foundItemArray;
                    $kegiatan = clone $rawKegiatanObject;
                    $idKegiatanUtama = $kegiatan->id_kegiatan ?? $kegiatan->ID_KEGIATAN ?? null;

                    $allJadwalResult = $this->apiService->getJadwalKegiatanList();
                    $allJadwal = ($allJadwalResult && !isset($allJadwalResult['_error'])) ? collect($allJadwalResult)->map(fn($item) => (object) $item) : new Collection();
                    $kegiatan->jadwal = $allJadwal->filter(fn($jadwal) => ($jadwal->id_kegiatan ?? $jadwal->ID_KEGIATAN ?? null) == $idKegiatanUtama)
                        ->sortBy(function($jadwal) {
                            $tgl = $jadwal->tgl_kegiatan ?? '1970-01-01 00:00:00';
                            $waktu = $jadwal->waktu_mulai ?? $tgl;
                            try { return \Carbon\Carbon::parse($waktu)->timestamp; } catch (\Exception $e) { try { return \Carbon\Carbon::parse($tgl)->timestamp; } catch (\Exception $ex) { return 0;}}
                        })->values();

                    $allMasterPemateriProcessed = $this->getProcessedMasterPemateri();
                    $kegiatan->pemateri = new Collection();

                    if ($kegiatan->jadwal->isNotEmpty()) {
                        foreach($kegiatan->jadwal as $jadwalItem) {
                            $idPemateriDiJadwal = $jadwalItem->id_pemateri ?? null;
                            if ($idPemateriDiJadwal) {
                                $foundMasterPemateri = $allMasterPemateriProcessed->firstWhere('id_pemateri', $idPemateriDiJadwal);
                                if($foundMasterPemateri){
                                    if (!$kegiatan->pemateri->contains('id_pemateri', $idPemateriDiJadwal)) {
                                         $kegiatan->pemateri->push($foundMasterPemateri);
                                    }
                                }
                            }
                        }
                    }
                    
                    // Ambil template sertifikat global
                    $sertifikatGlobalResult = $this->apiService->getSertifikatList([
                        'id_kegiatan' => $this->dummyKegiatanIdForTemplate, // Dari config('app.dummy_kegiatan_id_for_template')
                        'nim' => $this->globalTemplateNimIdentifier // Dari config('app.global_template_nim_identifier')
                    ]);

                    if($sertifikatGlobalResult && !isset($sertifikatGlobalResult['_error']) && is_array($sertifikatGlobalResult)){
                        // API mungkin mengembalikan array dari item, jadi kita ambil yang pertama
                        $globalTemplateData = collect($sertifikatGlobalResult)->first();
                        if($globalTemplateData) {
                            $templateSertifikatGlobal = (object) $globalTemplateData;
                        }
                    }
                    if(!$templateSertifikatGlobal) { // Fallback jika filter di atas tidak berhasil
                        $allSertifikatResult = $this->apiService->getSertifikatList();
                        if($allSertifikatResult && !isset($allSertifikatResult['_error']) && is_array($allSertifikatResult)){
                            $globalTemplateData = collect($allSertifikatResult)->first(function($sert) {
                                $sert = (object) $sert;
                                return (string)($sert->id_kegiatan ?? null) === (string)$this->dummyKegiatanIdForTemplate &&
                                       strtoupper($sert->nim ?? '') === strtoupper($this->globalTemplateNimIdentifier);
                            });
                            if($globalTemplateData) {
                                $templateSertifikatGlobal = (object) $globalTemplateData;
                            }
                        }
                    }


                } else { abort(404, 'Kegiatan tidak ditemukan.'); }
            } else { abort(500, 'Gagal mengambil data kegiatan.'); }
        } catch (\Exception $e) {
            Log::error("[SHOW_KEGIATAN] Exception saat memproses detail kegiatan ID {$id}: " . $e->getMessage());
            abort(500, 'Terjadi kesalahan server.');
        }
        if (!$kegiatan) { abort(404, 'Kegiatan tidak ditemukan.'); }
        
        return view('kegiatan-detail', compact('kegiatan', 'templateSertifikatGlobal'));
    }

    public function edit(string $id)
    {
        $kegiatan = null;
        $masterPemateri = $this->getProcessedMasterPemateri();
        Log::info("[EDIT_KEGIATAN] Memulai pengambilan data untuk edit ID Kegiatan: {$id}");

        try {
            $kegiatanListResult = $this->apiService->getKegiatanList();
            if ($kegiatanListResult && !isset($kegiatanListResult['_error']) && is_array($kegiatanListResult)) {
                
                $foundItemArray = collect($kegiatanListResult)->first(function ($itemArray) use ($id) {
                    $itemAsObject = (object) $itemArray;
                    return ($itemAsObject->id_kegiatan ?? $itemAsObject->ID_KEGIATAN ?? null) == $id;
                });

                if ($foundItemArray) {
                    $rawKegiatanObject = (object) $foundItemArray;
                    $kegiatan = clone $rawKegiatanObject;
                    
                    $idKegiatanUtama = $kegiatan->id_kegiatan ?? $kegiatan->ID_KEGIATAN ?? null;

                    $jadwalResult = $this->apiService->getJadwalKegiatanList();
                    $allJadwal = ($jadwalResult && !isset($jadwalResult['_error'])) ? collect($jadwalResult)->map(fn($item) => (object) $item) : new Collection();
                    $kegiatan->jadwal = $allJadwal->filter(fn($jadwal) => ($jadwal->id_kegiatan ?? $jadwal->ID_KEGIATAN ?? null) == $idKegiatanUtama)
                        ->sortBy(function($jadwal) {
                        $tgl = $jadwal->tgl_kegiatan ?? '1970-01-01 00:00:00';
                        $waktu = $jadwal->waktu_mulai ?? $tgl;
                        try { return \Carbon\Carbon::parse($waktu)->timestamp; } catch (\Exception $e) { return \Carbon\Carbon::parse($tgl)->timestamp;}
                        })->values();
                    
                } else { abort(404, 'Kegiatan tidak ditemukan untuk diedit.'); }
            } else { abort(500, 'Gagal mengambil data kegiatan untuk diedit.'); }
        } catch (\Exception $e) {
            Log::error("[EDIT_KEGIATAN] Exception saat memproses data edit kegiatan ID {$id}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            abort(500, 'Terjadi kesalahan server.');
        }
        if (!$kegiatan) { abort(404, 'Kegiatan tidak ditemukan.'); }
        
        return view('kegiatan-edit', compact('kegiatan', 'masterPemateri'));
    }

    public function update(Request $request, string $id)
    {
        Log::info("[UPDATE_KEGIATAN] Menerima request data untuk ID {$id}:", $request->all());
        
        $sesiInput = $request->input('sesi', []);
        if (is_array($sesiInput)) {
            foreach ($sesiInput as $key => &$sesiItem) {
                if (isset($sesiItem['jam_selesai']) && $sesiItem['jam_selesai'] === '') {
                    $sesiItem['jam_selesai'] = null;
                }
            }
            unset($sesiItem);
            $request->merge(['sesi' => $sesiInput]);
        }

        $validatedData = $request->validate([
            'judul' => 'required|string|max:100',
            'media' => 'required|string|max:20',
            'lokasi' => 'nullable|string|max:50',
            'keterangan_kegiatan' => 'nullable|string',
            'bobot_kegiatan' => 'required|integer|min:0',
            'sesi' => 'required|array|min:1',
            'sesi.*.tanggal' => 'required|date_format:Y-m-d',
            'sesi.*.jam_mulai' => 'required|date_format:H:i',
            'sesi.*.jam_selesai' => 'nullable|date_format:H:i|after_or_equal:sesi.*.jam_mulai',
            'sesi.*.id_pemateri' => 'required|numeric'
        ]);

        $idKegiatanToUpdate = $id;

        $apiKegiatanData = [
            'judul_kegiatan' => $validatedData['judul'],
            'media' => $validatedData['media'],
            'lokasi' => $validatedData['lokasi'] ?? '',
            'keterangan' => $validatedData['keterangan_kegiatan'] ?? '',
        ];
        Log::info("[UPDATE_KEGIATAN] Mengirim data update kegiatan utama ke API untuk ID {$idKegiatanToUpdate}:", $apiKegiatanData);
        $responseKegiatan = $this->apiService->updateKegiatan($idKegiatanToUpdate, $apiKegiatanData);

        if (!$responseKegiatan || isset($responseKegiatan['_error']) || (($responseKegiatan['success'] ?? false) !== true && !in_array($responseKegiatan['_status'] ?? 0, [200, 204])) ) {
            Log::error("[UPDATE_KEGIATAN] Gagal mengupdate kegiatan utama via API.", $responseKegiatan ?? []);
            $apiMessage = $responseKegiatan['_json_error_data']['message'] ?? ($responseKegiatan['_body'] ?? 'Error tidak diketahui dari API.');
            $apiErrors = isset($responseKegiatan['_json_error_data']['errors']) ? ' Details: '.json_encode($responseKegiatan['_json_error_data']['errors']) : '';
            return back()->withInput()->withErrors(['api_error_kegiatan' => 'Gagal mengupdate data kegiatan utama: ' . $apiMessage . $apiErrors]);
        }
        Log::info('[UPDATE_KEGIATAN] Respon API update kegiatan utama:', $responseKegiatan ?? '[No JSON Response Body]');

        // Logika upload template sertifikat dihapus

        Log::info("[UPDATE_KEGIATAN] Memulai proses update jadwal untuk kegiatan ID {$idKegiatanToUpdate}.");
        
        $jadwalLamaResult = $this->apiService->getJadwalKegiatanList();
        if ($jadwalLamaResult && !isset($jadwalLamaResult['_error']) && is_array($jadwalLamaResult)) {
            $jadwalLamaUntukKegiatanIni = collect($jadwalLamaResult)->filter(fn($jArray) => (((object)$jArray)->id_kegiatan ?? null) == $idKegiatanToUpdate);

            if ($jadwalLamaUntukKegiatanIni->isNotEmpty()) {
                $allHadirKegiatanList = $this->apiService->getHadirKegiatanList();
                $allHadirKegiatan = ($allHadirKegiatanList && !isset($allHadirKegiatanList['_error'])) ? collect($allHadirKegiatanList)->map(fn($h) => (object)$h) : new Collection();

                foreach($jadwalLamaUntukKegiatanIni as $jadwalLamaArray){
                    $jadwalLama = (object) $jadwalLamaArray;
                    $idJadwalLama = $jadwalLama->id_jadwal ?? $jadwalLama->id ?? null;
                    if ($idJadwalLama === null) continue;

                    $hadirTerkaitJadwal = $allHadirKegiatan->where('id_jadwal', $idJadwalLama);
                    foreach($hadirTerkaitJadwal as $hadir) {
                        $idHadirToDelete = $hadir->id_hadir ?? $hadir->id ?? null;
                        if($idHadirToDelete){
                            Log::info("[UPDATE_KEGIATAN] Menghapus kehadiran ID: {$idHadirToDelete} terkait jadwal lama ID: {$idJadwalLama}");
                            $this->apiService->deleteHadirKegiatan($idHadirToDelete);
                        }
                    }
                    Log::info("[UPDATE_KEGIATAN] Menghapus jadwal lama ID: {$idJadwalLama}");
                    $delJadwalRes = $this->apiService->deleteJadwalKegiatan($idJadwalLama);
                    if (!$delJadwalRes || isset($delJadwalRes['_error'])){
                        Log::error("[UPDATE_KEGIATAN] Gagal hapus jadwal lama ID {$idJadwalLama}.", $delJadwalRes ?? []);
                    }
                }
            }
        }

        if (isset($validatedData['sesi']) && is_array($validatedData['sesi'])) {
            foreach ($validatedData['sesi'] as $indexSesi => $dataSesi) {
                $nextJadwalId = $this->apiService->getNextId('jadwal-kegiatan', 'id_jadwal');
                if ($nextJadwalId === null) {
                    Log::error("[UPDATE_KEGIATAN] Gagal men-generate ID Jadwal baru untuk sesi index {$indexSesi}, skip pembuatan jadwal ini.");
                    continue;
                }
                $waktuMulaiString = $dataSesi['tanggal'] . ' ' . $dataSesi['jam_mulai'] . ':00';
                $waktuSelesaiString = (isset($dataSesi['jam_selesai']) && !empty($dataSesi['jam_selesai'])) ? $dataSesi['tanggal'] . ' ' . $dataSesi['jam_selesai'] . ':00' : null;
                
                $idPemateriUntukSesiIni = $dataSesi['id_pemateri'] ?? 0;
                if (empty($idPemateriUntukSesiIni) && $idPemateriUntukSesiIni !== 0) $idPemateriUntukSesiIni = 0;

                $apiJadwalData = [
                    'id' => $nextJadwalId,
                    'id_kegiatan' => (int) $idKegiatanToUpdate,
                    'tgl_kegiatan' => $dataSesi['tanggal'],
                    'waktu_mulai' => $waktuMulaiString,
                    'waktu_selesai' => $waktuSelesaiString,
                    'bobot' => $validatedData['bobot_kegiatan'],
                    'keterangan' => $validatedData['keterangan_kegiatan'] ?? ('Sesi ke-' . ($indexSesi + 1) . ' untuk ' . $validatedData['judul']),
                    'id_pemateri' => (int) $idPemateriUntukSesiIni,
                    'kode_random' => Str::upper(Str::random(10))
                ];
                Log::info('[UPDATE_KEGIATAN] Mengirim data jadwal baru (Sesi '.($indexSesi+1).') ke API:', $apiJadwalData);
                $resJadwalPost = $this->apiService->createJadwalKegiatan($apiJadwalData);
                if (!$resJadwalPost || isset($resJadwalPost['_error']) || ($resJadwalPost['success'] ?? false) !== true) {
                    Log::error('[UPDATE_KEGIATAN] Gagal menyimpan jadwal baru (Sesi '.($indexSesi+1).') via API.', $resJadwalPost ?? []);
                } else {
                    Log::info('[UPDATE_KEGIATAN] Sukses menyimpan jadwal baru (Sesi '.($indexSesi+1).').');
                }
            }
        }

        return redirect()->route('kegiatan.index')->with('success', 'Kegiatan berhasil diupdate.');
    }

    public function destroy(string $id)
    {
        $idKegiatan = $id;
        Log::info("[DESTROY_KEGIATAN] Memulai proses penghapusan untuk ID Kegiatan: {$idKegiatan}");
        $errors = [];
        try {
            // Logika penghapusan sertifikat terkait kegiatan dihilangkan
            $jadwalResult = $this->apiService->getJadwalKegiatanList();
            if ($jadwalResult && !isset($jadwalResult['_error']) && is_array($jadwalResult)) {
                $allHadirKegiatanList = $this->apiService->getHadirKegiatanList();
                $allHadirKegiatan = ($allHadirKegiatanList && !isset($allHadirKegiatanList['_error'])) ? collect($allHadirKegiatanList)->map(fn($h) => (object)$h) : new Collection();

                foreach ($jadwalResult as $jadwalArray) {
                    $j = (object) $jadwalArray;
                    if (($j->id_kegiatan ?? null) == $idKegiatan) {
                        $idJadwalToDelete = $j->id_jadwal ?? $j->id ?? null;
                        if ($idJadwalToDelete) {
                            $hadirTerkaitJadwal = $allHadirKegiatan->where('id_jadwal', $idJadwalToDelete);
                            foreach($hadirTerkaitJadwal as $hadir) {
                                $idHadirToDelete = $hadir->id_hadir ?? $hadir->id ?? null;
                                if($idHadirToDelete){
                                    $this->apiService->deleteHadirKegiatan($idHadirToDelete);
                                }
                            }
                            $this->apiService->deleteJadwalKegiatan($idJadwalToDelete);
                        }
                    }
                }
            }
            
            if (empty($errors)) {
                $response = $this->apiService->deleteKegiatan($idKegiatan);
                if ($response && !isset($response['_error']) && ($response['success'] ?? false) === true) {
                    return redirect()->route('kegiatan.index')->with('success', 'Kegiatan dan data terkait berhasil dihapus!');
                } else {
                    $apiMessage = $response['message'] ?? ($response['_body'] ?? 'Gagal menghapus data kegiatan utama.');
                    $errors[] = $apiMessage;
                    Log::error("[DESTROY_KEGIATAN] Gagal hapus kegiatan utama.", $response ?? []);
                }
            }
        } catch (\Exception $e) {
            Log::error("[DESTROY_KEGIATAN] Exception: " . $e->getMessage());
            $errors[] = "Terjadi kesalahan server saat menghapus.";
        }
        
        return redirect()->route('kegiatan.index')->withErrors(['api_error' => 'Gagal menghapus kegiatan atau beberapa data terkait. ' . implode('; ', $errors)]);
    }

    // app/Http/Controllers/KegiatanController.php

public function daftarHadir(string $idKegiatan)
{
    Log::info("[DAFTAR_HADIR] Memulai pengambilan data untuk ID Kegiatan: {$idKegiatan}");
    $kegiatan = null;
    $jadwalDenganKehadiran = new Collection();

    try {
        // 1. Ambil data kegiatan utama
        $kegiatanListResult = $this->apiService->getKegiatanList();
        if (!$kegiatanListResult || isset($kegiatanListResult['_error'])) {
            // Jika daftar kegiatan utama gagal diambil, ini adalah error fatal.
            Log::error('[DAFTAR_HADIR] Gagal mengambil daftar kegiatan dari API.', $kegiatanListResult ?? []);
            abort(500, 'Gagal mengambil data kegiatan dari API.');
        }

        $rawKegiatanArray = collect($kegiatanListResult)->first(function ($itemArray) use ($idKegiatan) {
            $itemObject = (object)$itemArray;
            $kegiatanIdApi = $itemObject->id_kegiatan ?? $itemObject->ID_KEGIATAN ?? null;
            return (string)$kegiatanIdApi === (string)$idKegiatan;
        });

        if (!$rawKegiatanArray) {
            abort(404, 'Kegiatan tidak ditemukan.');
        }
        $kegiatan = (object) $rawKegiatanArray;

        // 2. Ambil semua jadwal
        $allJadwalResult = $this->apiService->getJadwalKegiatanList();
        // **PERBAIKAN:** Cek error secara spesifik, jika tidak ada error, anggap data lain (null, [], dll) sebagai koleksi kosong.
        if (isset($allJadwalResult['_error'])) {
            Log::error('[DAFTAR_HADIR] API Error saat mengambil jadwal.', $allJadwalResult);
            $allJadwal = new Collection(); // Lanjutkan dengan koleksi kosong
        } else {
            // Jika isinya bukan array (misal null atau string kosong), buat jadi koleksi kosong.
            $allJadwal = is_array($allJadwalResult) ? collect($allJadwalResult)->map(fn($item) => (object) $item) : new Collection();
        }

        // 3. Ambil semua data kehadiran
        $allHadirResult = $this->apiService->getHadirKegiatanList();
        // **PERBAIKAN:** Terapkan logika yang sama untuk data kehadiran.
        if (isset($allHadirResult['_error'])) {
            Log::error('[DAFTAR_HADIR] API Error saat mengambil data kehadiran.', $allHadirResult);
            $allHadirKegiatan = new Collection(); // Lanjutkan dengan koleksi kosong
        } else {
            $allHadirKegiatan = is_array($allHadirResult) ? collect($allHadirResult)->map(fn($item) => (object) $item) : new Collection();
        }

        // Filter jadwal untuk kegiatan ini
        $jadwalUntukKegiatanIni = $allJadwal->filter(function($jadwal) use ($idKegiatan) {
            $jadwalKegiatanId = $jadwal->id_kegiatan ?? $jadwal->ID_KEGIATAN ?? null;
            return (string)$jadwalKegiatanId === (string)$idKegiatan;
        })->sortBy(function($jadwal) {
            try {
                return \Carbon\Carbon::parse(($jadwal->tgl_kegiatan ?? '') . ' ' . ($jadwal->waktu_mulai ?? ''))->timestamp;
            } catch (\Exception $e) {
                return 0; // Fallback untuk sorting jika tanggal tidak valid
            }
        })->values();

        // Gabungkan jadwal dengan data kehadiran
        $jadwalDenganKehadiran = $jadwalUntukKegiatanIni->map(function ($jadwal) use ($allHadirKegiatan) {
            $idJadwalIni = $jadwal->id_jadwal ?? $jadwal->ID_JADWAL ?? $jadwal->id ?? null;
            $jadwal->kehadiran = $idJadwalIni ? $allHadirKegiatan->where('id_jadwal', (string)$idJadwalIni)->pluck('nim')->filter()->values() : new Collection();
            return $jadwal;
        });

    } catch (\Exception $e) {
        Log::error("[DAFTAR_HADIR] Exception saat memproses daftar hadir untuk kegiatan ID {$idKegiatan}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        abort(500, 'Terjadi kesalahan server saat memuat data.');
    }

    return view('kegiatan-daftar-hadir', compact('kegiatan', 'jadwalDenganKehadiran'));
}
    
}