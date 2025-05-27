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

    public function __construct(MyApiService $apiService)
    {
        $this->apiService = $apiService;
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

    public function create()
    {
        $masterPemateriResult = $this->apiService->getPemateriKegiatanList();
        $masterPemateri = new Collection();
        if ($masterPemateriResult && !isset($masterPemateriResult['_error']) && is_array($masterPemateriResult)) {
            $masterPemateri = collect($masterPemateriResult)->map(fn($item) => (object) $item);
        } else {
            Log::error('Gagal mengambil data master pemateri dari API untuk form create.', $masterPemateriResult ?? []);
        }
        return view('tambah-kegiatan', compact('masterPemateri')); 
    }

    public function store(Request $request)
    {
        Log::info('[STORE_KEGIATAN] Menerima request data:', $request->all());

        $validatedData = $request->validate([
             'judul' => 'required|string|max:50',
             'media' => 'required|string|max:20', 
             'lokasi' => 'nullable|string|max:50', 
             'template_sertifikat' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
             'keterangan_kegiatan' => 'nullable|string', 
             'bobot_kegiatan' => 'required|integer|min:0',    
             'sesi' => 'required|array|min:1', 
             'sesi.*.tanggal' => 'required|date_format:Y-m-d',
             'sesi.*.jam_mulai' => 'required|date_format:H:i',
             'sesi.*.jam_selesai' => 'nullable|date_format:H:i|after_or_equal:sesi.*.jam_mulai',
             'sesi.*.pemateri_ids' => 'required|array|min:1', 
             'sesi.*.pemateri_ids.*' => 'required|numeric' 
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

        if ($request->hasFile('template_sertifikat') && $request->file('template_sertifikat')->isValid()) {
            try {
                $file = $request->file('template_sertifikat');
                $namaFileSertifikat = 'tpl_sert_keg_' . $createdKegiatanId . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/sertifikat_templates_kegiatan', $namaFileSertifikat); 
                Log::info('[STORE_KEGIATAN] Template sertifikat berhasil diunggah: ' . $namaFileSertifikat);
                
                $nextSertifikatId = $this->apiService->getNextId('sertifikat', 'id_sertifikat');
                if ($nextSertifikatId === null) {
                    Log::error("[STORE_KEGIATAN] Gagal men-generate ID Sertifikat.");
                } else {
                    $apiSertifikatData = [
                        'id' => $nextSertifikatId, // atau 'id_sertifikat' tergantung API Anda
                        'id_kegiatan' => $createdKegiatanId,
                        'nama_file' => $namaFileSertifikat,
                        'nim' => 'TEMPLATE_KEGIATAN', 
                    ];
                    Log::info('[STORE_KEGIATAN] Mengirim data sertifikat template ke API:', $apiSertifikatData);
                    $responseSertifikat = $this->apiService->createSertifikat($apiSertifikatData); 
                    if (!$responseSertifikat || isset($responseSertifikat['_error']) || ($responseSertifikat['success'] ?? false) !== true) {
                        Log::error('[STORE_KEGIATAN] Gagal menyimpan info sertifikat template via API.', $responseSertifikat ?? []);
                    } else {
                        Log::info('[STORE_KEGIATAN] Respon API sertifikat template:', $responseSertifikat);
                    }
                }
            } catch (\Exception $e) {
                Log::error('[STORE_KEGIATAN] Gagal mengunggah atau menyimpan info file sertifikat: ' . $e->getMessage());
            }
        }
        
        if (isset($validatedData['sesi']) && is_array($validatedData['sesi'])) {
            foreach ($validatedData['sesi'] as $indexSesi => $dataSesi) {
                $nextJadwalId = $this->apiService->getNextId('jadwal-kegiatan', 'id_jadwal'); 
                if ($nextJadwalId === null) {
                    Log::error("[STORE_KEGIATAN] Gagal men-generate ID Jadwal untuk sesi index {$indexSesi}.");
                    continue; 
                }

                $waktuMulaiString = $dataSesi['tanggal'] . ' ' . $dataSesi['jam_mulai'] . ':00';
                $waktuSelesaiString = (isset($dataSesi['jam_selesai']) && !empty($dataSesi['jam_selesai'])) ? $dataSesi['tanggal'] . ' ' . $dataSesi['jam_selesai'] . ':00' : null;
                $idPemateriUntukSesiIni = $dataSesi['pemateri_ids'][0] ?? 0; 
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
                if(isset($dataSesi['pemateri_ids']) && is_array($dataSesi['pemateri_ids'])){
                    foreach ($dataSesi['pemateri_ids'] as $idPemateriDariForm) {
                        if (!empty($idPemateriDariForm)) {
                            Log::warning("[STORE_KEGIATAN] Memproses ID_PEMATERI: {$idPemateriDariForm} untuk kegiatan '{$createdKegiatanId}' (Sesi ".($indexSesi+1)."). Endpoint API untuk relasi ini perlu dibuat jika belum ada.");
                        }
                    }
                }
            }
        }
        return redirect()->route('kegiatan.index')->with('success', 'Kegiatan berhasil disimpan.');
    }

    public function show(string $id)
    {
        $kegiatan = null;
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

                    $allMasterPemateriResult = $this->apiService->getPemateriKegiatanList();
                    $allMasterPemateri = ($allMasterPemateriResult && !isset($allMasterPemateriResult['_error'])) ? collect($allMasterPemateriResult)->map(fn($item) => (object) $item) : new Collection();
                    $kegiatan->pemateri = new Collection();
                    if ($kegiatan->jadwal->isNotEmpty()) {
                        foreach($kegiatan->jadwal as $jadwalItem) {
                            $idPemateriDiJadwal = $jadwalItem->id_pemateri ?? null;
                            if ($idPemateriDiJadwal) {
                                $foundMasterPemateri = $allMasterPemateri->firstWhere('id_pemateri', $idPemateriDiJadwal);
                                if($foundMasterPemateri){
                                    $namaPemateri = $foundMasterPemateri->nama_pemateri ?? 'Nama Pemateri Tidak Ditemukan';
                                    if (!$kegiatan->pemateri->contains('id_pemateri', $idPemateriDiJadwal)) {
                                        $kegiatan->pemateri->push((object)['id_pemateri' => $idPemateriDiJadwal, 'nama_pemateri' => $namaPemateri]);
                                    }
                                }
                            }
                        }
                    }
                    
                    $kegiatan->template_sertifikat_file = null;
                    $sertifikatResult = $this->apiService->getSertifikatList();
                    if($sertifikatResult && !isset($sertifikatResult['_error']) && is_array($sertifikatResult)){
                        $sertifikatTerkait = collect($sertifikatResult)->first(function($sert) use ($idKegiatanUtama){
                            $sert = (object) $sert;
                            return ($sert->id_kegiatan ?? null) == $idKegiatanUtama && 
                                (is_null($sert->nim ?? null) || ($sert->nim ?? null) == 'TEMPLATE_KEGIATAN');
                        });
                        if($sertifikatTerkait) $kegiatan->template_sertifikat_file = (object) $sertifikatTerkait;
                    }

                } else { abort(404, 'Kegiatan tidak ditemukan.'); }
            } else { abort(500, 'Gagal mengambil data kegiatan.'); }
        } catch (\Exception $e) {
            Log::error("[SHOW_KEGIATAN] Exception saat memproses detail kegiatan ID {$id}: " . $e->getMessage());
            abort(500, 'Terjadi kesalahan server.');
        }
        if (!$kegiatan) { abort(404, 'Kegiatan tidak ditemukan.'); } 
        
        return view('kegiatan-detail', compact('kegiatan'));
    }

    public function edit(string $id)
    {
        $kegiatan = null;
        $masterPemateri = new Collection();
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
                    
                    $masterPemateriResult = $this->apiService->getPemateriKegiatanList();
                    if($masterPemateriResult && !isset($masterPemateriResult['_error']) && is_array($masterPemateriResult)){
                        $masterPemateri = collect($masterPemateriResult)->map(fn($item) => (object) $item);
                    }
                    
                    $kegiatan->selected_pemateri_ids = new Collection(); 
                    if ($kegiatan->jadwal->isNotEmpty()) {
                        foreach($kegiatan->jadwal as $jadwalItem) {
                            $idPemateriDiJadwal = $jadwalItem->id_pemateri ?? null;
                            if ($idPemateriDiJadwal && !$kegiatan->selected_pemateri_ids->contains($idPemateriDiJadwal)) {
                                $kegiatan->selected_pemateri_ids->push($idPemateriDiJadwal);
                            }
                        }
                    }

                    $kegiatan->template_sertifikat_file = null; 
                    $sertifikatResult = $this->apiService->getSertifikatList();
                    if($sertifikatResult && !isset($sertifikatResult['_error']) && is_array($sertifikatResult)){
                        $sertifikatTerkait = collect($sertifikatResult)->first(function($sert) use ($idKegiatanUtama){
                            $sert = (object) $sert;
                            return ($sert->id_kegiatan ?? null) == $idKegiatanUtama && 
                                (is_null($sert->nim ?? null) || ($sert->nim ?? null) == 'TEMPLATE_KEGIATAN');
                        });
                        if($sertifikatTerkait) { $kegiatan->template_sertifikat_file = (object) $sertifikatTerkait; }
                    }
                } else { abort(404, 'Kegiatan tidak ditemukan untuk diedit.'); }
            } else { abort(500, 'Gagal mengambil data kegiatan untuk diedit.'); }
        } catch (\Exception $e) {
            Log::error("[EDIT_KEGIATAN] Exception saat memproses data edit kegiatan ID {$id}: " . $e->getMessage());
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
            'judul' => 'required|string|max:50',
            'media' => 'required|string|max:20',
            'lokasi' => 'nullable|string|max:50',
            'template_sertifikat' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'keterangan_kegiatan' => 'nullable|string',
            'bobot_kegiatan' => 'required|integer|min:0',
            'sesi' => 'required|array|min:1',
            'sesi.*.tanggal' => 'required|date_format:Y-m-d',
            'sesi.*.jam_mulai' => 'required|date_format:H:i',
            'sesi.*.jam_selesai' => 'nullable|date_format:H:i|after_or_equal:sesi.*.jam_mulai',
            'sesi.*.id_pemateri' => 'required|numeric' // Form edit mengirim id_pemateri per sesi
        ]);

        $idKegiatanToUpdate = $id;

        // 1. Update Data Kegiatan Utama
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

        // 2. Handle Update/Upload File Sertifikat
        if ($request->hasFile('template_sertifikat') && $request->file('template_sertifikat')->isValid()) {
            Log::info("[UPDATE_KEGIATAN] File sertifikat baru diunggah untuk kegiatan ID {$idKegiatanToUpdate}.");
            
            // Cari dan hapus sertifikat lama jika ada
            $sertifikatListResult = $this->apiService->getSertifikatList(); // Ambil semua, lalu filter
            if ($sertifikatListResult && !isset($sertifikatListResult['_error']) && is_array($sertifikatListResult)) {
                $oldSertifikatData = collect($sertifikatListResult)->first(function($sertArray) use ($idKegiatanToUpdate){
                    $sert = (object) $sertArray;
                    return ($sert->id_kegiatan ?? null) == $idKegiatanToUpdate && ($sert->nim ?? null) == 'TEMPLATE_KEGIATAN';
                });

                if($oldSertifikatData){
                    $oldSertifikatData = (object) $oldSertifikatData; // Pastikan objek
                    $oldSertifikatId = $oldSertifikatData->id_sertifikat ?? $oldSertifikatData->id ?? null;
                    $oldNamaFile = $oldSertifikatData->nama_file ?? null;
                    if($oldSertifikatId){
                        Log::info("[UPDATE_KEGIATAN] Menghapus template sertifikat lama ID record: {$oldSertifikatId}");
                        $this->apiService->deleteSertifikat($oldSertifikatId);
                    }
                    if ($oldNamaFile) {
                        Log::info("[UPDATE_KEGIATAN] Menghapus file sertifikat lama dari storage: {$oldNamaFile}");
                        Storage::delete('public/sertifikat_templates_kegiatan/' . $oldNamaFile);
                    }
                }
            }

            // Simpan sertifikat baru
            try {
                $file = $request->file('template_sertifikat');
                $namaFileSertifikat = 'tpl_sert_keg_' . $idKegiatanToUpdate . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/sertifikat_templates_kegiatan', $namaFileSertifikat);
                Log::info("[UPDATE_KEGIATAN] File sertifikat baru berhasil diunggah: " . $namaFileSertifikat);

                $nextSertifikatId = $this->apiService->getNextId('sertifikat', 'id_sertifikat');
                if ($nextSertifikatId !== null) {
                    $apiSertifikatData = [
                        'id' => $nextSertifikatId,
                        'id_kegiatan' => $idKegiatanToUpdate,
                        'nama_file' => $namaFileSertifikat,
                        'nim' => 'TEMPLATE_KEGIATAN',
                    ];
                    Log::info('[UPDATE_KEGIATAN] Mengirim data sertifikat template baru ke API:', $apiSertifikatData);
                    $resSertPost = $this->apiService->createSertifikat($apiSertifikatData);
                    if (!$resSertPost || isset($resSertPost['_error']) || ($resSertPost['success'] ?? false) !== true) {
                        Log::error('[UPDATE_KEGIATAN] Gagal menyimpan record sertifikat template baru via API.', $resSertPost ?? []);
                    } else {
                        Log::info('[UPDATE_KEGIATAN] Sukses menyimpan record sertifikat template baru.');
                    }
                } else {
                    Log::error("[UPDATE_KEGIATAN] Gagal men-generate ID untuk sertifikat baru.");
                }
            } catch (\Exception $e) {
                Log::error('[UPDATE_KEGIATAN] Gagal mengunggah atau menyimpan info file sertifikat baru: ' . $e->getMessage());
            }
        }

        // 3. Handle Update Jadwal Kegiatan (Strategi: Hapus semua jadwal lama, lalu insert yang baru dari form)
        Log::info("[UPDATE_KEGIATAN] Memulai proses update jadwal untuk kegiatan ID {$idKegiatanToUpdate}.");
        
        // Ambil dan Hapus Jadwal Lama beserta Kehadiran Terkait
        $jadwalLamaResult = $this->apiService->getJadwalKegiatanList(); // Ambil semua, lalu filter
        if ($jadwalLamaResult && !isset($jadwalLamaResult['_error']) && is_array($jadwalLamaResult)) {
            $jadwalLamaUntukKegiatanIni = collect($jadwalLamaResult)->filter(fn($jArray) => (((object)$jArray)->id_kegiatan ?? null) == $idKegiatanToUpdate);

            if ($jadwalLamaUntukKegiatanIni->isNotEmpty()) {
                $allHadirKegiatanList = $this->apiService->getHadirKegiatanList(); // Ambil semua data hadir sekali
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


        // Buat ulang jadwal dari data sesi yang ada di form
        if (isset($validatedData['sesi']) && is_array($validatedData['sesi'])) {
            foreach ($validatedData['sesi'] as $indexSesi => $dataSesi) {
                $nextJadwalId = $this->apiService->getNextId('jadwal-kegiatan', 'id_jadwal');
                if ($nextJadwalId === null) {
                    Log::error("[UPDATE_KEGIATAN] Gagal men-generate ID Jadwal baru untuk sesi index {$indexSesi}, skip pembuatan jadwal ini.");
                    continue;
                }
                $waktuMulaiString = $dataSesi['tanggal'] . ' ' . $dataSesi['jam_mulai'] . ':00';
                $waktuSelesaiString = (isset($dataSesi['jam_selesai']) && !empty($dataSesi['jam_selesai'])) ? $dataSesi['tanggal'] . ' ' . $dataSesi['jam_selesai'] . ':00' : null;
                
                // Di form edit, 'id_pemateri' dikirim langsung per sesi, bukan array 'pemateri_ids'
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
            // Hapus sertifikat terkait
            $sertifikatResult = $this->apiService->getSertifikatList(); 
            if ($sertifikatResult && !isset($sertifikatResult['_error']) && is_array($sertifikatResult)) {
                foreach ($sertifikatResult as $sertifikatArray) {
                    $s = (object) $sertifikatArray;
                    if (($s->id_kegiatan ?? null) == $idKegiatan) { 
                        $idSertifikatToDelete = $s->id_sertifikat ?? $s->id ?? null;
                        if ($idSertifikatToDelete) {
                            $delResponse = $this->apiService->deleteSertifikat($idSertifikatToDelete);
                            if (!$delResponse || isset($delResponse['_error']) || ($delResponse['success'] ?? false) !== true) {
                                $errors[] = "Gagal hapus sertifikat ID {$idSertifikatToDelete}";
                            } else {
                                // Hapus file fisik jika record API berhasil dihapus
                                if (isset($s->nama_file) && !empty($s->nama_file)) {
                                    Storage::delete('public/sertifikat_templates_kegiatan/' . $s->nama_file);
                                    Log::info("[DESTROY_KEGIATAN] Berhasil hapus file sertifikat fisik: {$s->nama_file}");
                                }
                            }
                        }
                    }
                }
            }

            // Hapus kehadiran terkait jadwal, lalu hapus jadwal
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

    public function daftarHadir(string $idKegiatan) 
    {
        Log::info("[DAFTAR_HADIR] Memulai pengambilan data untuk ID Kegiatan: {$idKegiatan}");
        $kegiatan = null;
        $jadwalDenganKehadiran = new Collection();
        try {
            $kegiatanListResult = $this->apiService->getKegiatanList();
            if ($kegiatanListResult && !isset($kegiatanListResult['_error']) && is_array($kegiatanListResult)) {
                $rawKegiatanArray = collect($kegiatanListResult)->first(fn($itemArray) => ((object)$itemArray)->id_kegiatan == $idKegiatan);
                
                if (!$rawKegiatanArray) { abort(404, 'Kegiatan tidak ditemukan.');}
                $kegiatan = (object) $rawKegiatanArray;

                $allJadwalResult = $this->apiService->getJadwalKegiatanList();
                if (!$allJadwalResult || isset($allJadwalResult['_error']) || !is_array($allJadwalResult)) { throw new \Exception("Gagal mengambil data jadwal.");}
                $allJadwal = collect($allJadwalResult)->map(fn($item) => (object) $item);
                
                $jadwalUntukKegiatanIni = $allJadwal->filter(fn($jadwal) => ($jadwal->id_kegiatan ?? null) == $idKegiatan)
                    ->sortBy(function($jadwal) {
                        $tgl = $jadwal->tgl_kegiatan ?? '1970-01-01 00:00:00';
                        $waktu = $jadwal->waktu_mulai ?? $tgl;
                        try { return \Carbon\Carbon::parse($waktu)->timestamp; } catch (\Exception $e) { try { return \Carbon\Carbon::parse($tgl)->timestamp; } catch (\Exception $ex) { return 0;}}
                    })->values();

                $allHadirResult = $this->apiService->getHadirKegiatanList();
                if (!$allHadirResult || isset($allHadirResult['_error']) || !is_array($allHadirResult)) { throw new \Exception("Gagal mengambil data kehadiran.");}
                $allHadirKegiatan = collect($allHadirResult)->map(fn($item) => (object) $item);

                $jadwalDenganKehadiran = $jadwalUntukKegiatanIni->map(function ($jadwal) use ($allHadirKegiatan) {
                    $idJadwalIni = $jadwal->id_jadwal ?? null;
                    $jadwal->kehadiran = $idJadwalIni ? $allHadirKegiatan->where('id_jadwal', $idJadwalIni)->pluck('nim')->filter()->values() : new Collection();
                    return $jadwal;
                });

            } else { abort(500, 'Gagal mengambil data kegiatan.');}
        } catch (\Exception $e) {
            Log::error("[DAFTAR_HADIR] Exception saat memproses daftar hadir untuk kegiatan ID {$idKegiatan}: " . $e->getMessage());
            abort(500, 'Terjadi kesalahan server.');
        }
        return view('kegiatan-daftar-hadir', compact('kegiatan', 'jadwalDenganKehadiran'));
    }
}
