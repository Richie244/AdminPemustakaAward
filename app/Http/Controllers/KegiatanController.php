<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str; 
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class KegiatanController extends Controller
{
    protected $apiBaseUrl = 'https://7f61-202-51-113-148.ngrok-free.app/api'; 

    // Fungsi helper untuk mendapatkan ID berikutnya secara manual dari API
    private function getNextIdFromApi($endpoint, $idColumnName, $defaultId = 1)
    {
        try {
            Log::info("[GET_NEXT_ID] Fetching next ID for endpoint: {$endpoint}, column: {$idColumnName}");
            $response = Http::get("{$this->apiBaseUrl}/{$endpoint}");
            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data) && !empty($data)) {
                    $maxId = 0;
                    foreach ($data as $item) {
                        $itemObject = (object) $item; 
                        $currentId = null;
                        
                        // Mencoba berbagai variasi nama kolom ID (case-insensitive dan umum)
                        // Berdasarkan JSON Anda, API mengembalikan 'id_kegiatan', 'id_jadwal', 'id_sertifikat', 'id_pemateri' (lowercase)
                        $possibleIdKeys = [strtolower($idColumnName), $idColumnName, strtoupper($idColumnName), 'id', 'ID'];
                        foreach($possibleIdKeys as $key) {
                            if (property_exists($itemObject, $key) && is_numeric($itemObject->{$key})) {
                                $currentId = (int) $itemObject->{$key};
                                break; 
                            }
                        }
                        
                        if ($currentId !== null && $currentId > $maxId) {
                            $maxId = $currentId;
                        }
                    }
                    $nextId = $maxId + 1;
                    Log::info("[GET_NEXT_ID] Max ID found: {$maxId}, Next ID: {$nextId} for {$endpoint}");
                    return $nextId;
                } elseif (is_array($data) && empty($data)) {
                    Log::info("[GET_NEXT_ID] Endpoint {$endpoint} returned empty data. Starting ID from {$defaultId}.");
                    return $defaultId;
                } else {
                    Log::error("[GET_NEXT_ID] Data dari API {$endpoint} bukan array atau format tidak dikenal.", ['response_body' => $data]);
                }
            } else {
                Log::error("[GET_NEXT_ID] Gagal mengambil data dari API {$endpoint}: " . $response->status() . " - " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("[GET_NEXT_ID] Exception saat mengambil data dari API {$endpoint}: " . $e->getMessage());
        }
        Log::warning("[GET_NEXT_ID] Gagal mendapatkan ID berikutnya dari API untuk {$endpoint}. Menggunakan fallback ID random (TIDAK IDEAL!).");
        return null; 
    }

    public function index(Request $request)
    {
        $perPage = 10; 
        $currentPage = Paginator::resolveCurrentPage('page');
        $kegiatanDataFinal = new Collection();
        $totalKegiatan = 0;

        try {
            $allJadwal = new Collection();
            try {
                $responseJadwal = Http::get("{$this->apiBaseUrl}/jadwal-kegiatan");
                if ($responseJadwal->successful() && is_array($responseJadwal->json())) {
                    $allJadwal = collect($responseJadwal->json())->map(fn($item) => (object) $item);
                } else { Log::error('[INDEX_KEGIATAN] Gagal mengambil semua data jadwal dari API: ' . $responseJadwal->status() . ' - ' . $responseJadwal->body());}
            } catch (\Exception $e) { Log::error('[INDEX_KEGIATAN] Exception saat mengambil semua data jadwal: ' . $e->getMessage()); }
            
            $allMasterPemateri = new Collection();
             try {
                $responseMasterPemateri = Http::get("{$this->apiBaseUrl}/pemateri-kegiatan");
                if($responseMasterPemateri->successful() && is_array($responseMasterPemateri->json())){
                    $allMasterPemateri = collect($responseMasterPemateri->json())->map(fn($item) => (object) $item);
                } else { Log::error('[INDEX_KEGIATAN] Gagal mengambil semua data master pemateri dari API: ' . $responseMasterPemateri->status() . ' - ' . $responseMasterPemateri->body());}
            } catch (\Exception $e) { Log::error('[INDEX_KEGIATAN] Exception saat mengambil master pemateri: ' . $e->getMessage());}

            $responseKegiatan = Http::get("{$this->apiBaseUrl}/kegiatan"); 
            if ($responseKegiatan->successful()) {
                $apiResultKegiatan = $responseKegiatan->json();

                if (is_array($apiResultKegiatan)) {
                    $allKegiatan = collect($apiResultKegiatan)->map(fn($item) => (object) $item);
                    $totalKegiatan = $allKegiatan->count();
                    $currentKegiatanItems = $allKegiatan->slice(($currentPage - 1) * $perPage, $perPage);

                    foreach ($currentKegiatanItems as $k) {
                        $idKegiatanUtama = $k->id_kegiatan ?? null; 

                        if (!$idKegiatanUtama) {
                            $k->jadwal = new Collection(); 
                            $k->pemateri = new Collection(); 
                            $kegiatanDataFinal->push($k);
                            continue; 
                        }

                        $k->jadwal = $allJadwal->filter(function ($jadwal) use ($idKegiatanUtama) {
                            $idKegiatanDiJadwal = $jadwal->id_kegiatan ?? null;
                            return $idKegiatanDiJadwal == $idKegiatanUtama;
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
                                    $foundMasterPemateri = $allMasterPemateri->first(function ($master) use ($idPemateriDiJadwal) {
                                        $idMaster = $master->id_pemateri ?? null;
                                        return $idMaster == $idPemateriDiJadwal;
                                    });
                                    if($foundMasterPemateri){
                                        $namaPemateri = $foundMasterPemateri->nama_pemateri ?? 'Nama Pemateri Tidak Ditemukan';
                                        if (!$k->pemateri->contains('nama_pemateri', $namaPemateri)) {
                                            $k->pemateri->push((object)['nama_pemateri' => $namaPemateri]);
                                        }
                                    }
                                }
                            }
                        }
                        $kegiatanDataFinal->push($k);
                    }
                } else { Log::error('Data dari API kegiatan tidak dalam format array yang diharapkan.'); }
            } else { Log::error('Gagal mengambil data kegiatan dari API: ' . $responseKegiatan->status() . ' - ' . $responseKegiatan->body()); }
        } catch (\Exception $e) { Log::error('[INDEX_KEGIATAN] Exception utama saat memproses data kegiatan: ' . $e->getMessage()); }
        $kegiatan = new LengthAwarePaginator($kegiatanDataFinal->all(), $totalKegiatan, $perPage, $currentPage, ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page']);
        return view('kegiatan', compact('kegiatan'));
    }

 public function create()
    {
        $masterPemateri = [];
        try {
            $response = Http::get("{$this->apiBaseUrl}/pemateri-kegiatan");
            if ($response->successful() && is_array($response->json())) {
                $masterPemateri = collect($response->json())->map(function($item) { return (object) $item; });
            } else { Log::error('Gagal mengambil data master pemateri dari API: ' . $response->status() . ' - ' . $response->body()); }
        } catch (\Exception $e) { Log::error('Exception saat mengambil data master pemateri: ' . $e->getMessage()); }
        return view('tambah-kegiatan', compact('masterPemateri')); 
    }

    public function store(Request $request)
    {
        Log::info('[STORE_KEGIATAN_V6_FIX_VALIDATION] Menerima request data:', $request->all());

        // PERUBAHAN VALIDASI: Menyesuaikan dengan input 'pemateri_ids' sebagai array
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
            'sesi.*.pemateri_ids' => 'required|array|min:1', // Setiap sesi harus punya array pemateri_ids
            'sesi.*.pemateri_ids.*' => 'required|numeric' // Setiap ID di dalam array pemateri_ids harus numerik
        ]);

        // LANGKAH 1: Simpan Data Kegiatan Utama
        $nextKegiatanId = $this->getNextIdFromApi('kegiatan', 'id_kegiatan');
        if ($nextKegiatanId === null) { 
             return back()->withInput()->withErrors(['api_error_kegiatan' => 'Gagal men-generate ID Kegiatan. Periksa koneksi atau log API.']);
        }

        $apiKegiatanData = [
            'id_kegiatan' => $nextKegiatanId, 
            'judul_kegiatan' => $validatedData['judul'],
            'media' => $validatedData['media'], 
            'lokasi' => $validatedData['lokasi'] ?? '', 
            'keterangan' => $validatedData['keterangan_kegiatan'] ?? '', 
        ];

        Log::info('[STORE_KEGIATAN_V6_FIX_VALIDATION] Mengirim data kegiatan utama ke API:', $apiKegiatanData);
        $responseKegiatan = Http::post("{$this->apiBaseUrl}/kegiatan", $apiKegiatanData);

        if (!$responseKegiatan->successful() || $responseKegiatan->json('success') !== true) {
            Log::error('[STORE_KEGIATAN_V6_FIX_VALIDATION] Gagal menyimpan kegiatan utama via API: ' . $responseKegiatan->status() . ' - ' . $responseKegiatan->body(), $responseKegiatan->json() ?? []);
            return back()->withInput()->withErrors(['api_error_kegiatan' => 'Gagal menyimpan data kegiatan utama: ' . ($responseKegiatan->json('message') ?? 'Error tidak diketahui dari API.') . ($responseKegiatan->json('errors') ? ' Details: '.json_encode($responseKegiatan->json('errors')) : '')]);
        }
        Log::info('[STORE_KEGIATAN_V6_FIX_VALIDATION] Respon API kegiatan utama:', $responseKegiatan->json());
        $createdKegiatanId = $nextKegiatanId; 

        // LANGKAH 2: Handle Upload File Sertifikat (Template)
        if ($request->hasFile('template_sertifikat') && $request->file('template_sertifikat')->isValid()) {
            try {
                $file = $request->file('template_sertifikat');
                $namaFileSertifikat = 'tpl_sert_keg_' . $createdKegiatanId . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/sertifikat_templates_kegiatan', $namaFileSertifikat); 
                Log::info('[STORE_KEGIATAN_V6_FIX_VALIDATION] Template sertifikat berhasil diunggah: ' . $namaFileSertifikat);
                
                $nextSertifikatId = $this->getNextIdFromApi('sertifikat', 'id_sertifikat');
                 if ($nextSertifikatId === null) {
                    Log::error("[STORE_KEGIATAN_V6_FIX_VALIDATION] Gagal men-generate ID Sertifikat, proses sertifikat dihentikan untuk file: {$namaFileSertifikat}");
                } else {
                    $apiSertifikatData = [
                        'id' => $nextSertifikatId,
                        'id_kegiatan' => $createdKegiatanId,
                        'nama_file' => $namaFileSertifikat,
                        'nim' => 'TEMPLATE_KEGIATAN', 
                    ];
                    Log::info('[STORE_KEGIATAN_V6_FIX_VALIDATION] Mengirim data sertifikat template ke API:', $apiSertifikatData);
                    $responseSertifikat = Http::post("{$this->apiBaseUrl}/sertifikat", $apiSertifikatData); 
                    if (!$responseSertifikat->successful() || $responseSertifikat->json('success') !== true) {
                        Log::error('[STORE_KEGIATAN_V6_FIX_VALIDATION] Gagal menyimpan info sertifikat template via API: ' . $responseSertifikat->status() . ' - ' . $responseSertifikat->body(), $responseSertifikat->json() ?? []);
                    } else {
                        Log::info('[STORE_KEGIATAN_V6_FIX_VALIDATION] Respon API sertifikat template:', $responseSertifikat->json());
                    }
                }
            } catch (\Exception $e) {
                Log::error('[STORE_KEGIATAN_V6_FIX_VALIDATION] Gagal mengunggah atau menyimpan info file sertifikat: ' . $e->getMessage());
            }
        }
        
        // LANGKAH 3: Simpan Setiap Sesi Kegiatan (Jadwal) via API
        if (isset($validatedData['sesi']) && is_array($validatedData['sesi'])) {
            foreach ($validatedData['sesi'] as $indexSesi => $dataSesi) {
                $nextJadwalId = $this->getNextIdFromApi('jadwal-kegiatan', 'id_jadwal'); 
                if ($nextJadwalId === null) {
                    Log::error("[STORE_KEGIATAN_V6_FIX_VALIDATION] Gagal men-generate ID Jadwal untuk sesi index {$indexSesi}, proses jadwal ini dihentikan.");
                    continue; 
                }

                $waktuMulaiString = $dataSesi['tanggal'] . ' ' . $dataSesi['jam_mulai'] . ':00';
                $waktuSelesaiString = null;
                if (isset($dataSesi['jam_selesai']) && !empty($dataSesi['jam_selesai'])) {
                    $waktuSelesaiString = $dataSesi['tanggal'] . ' ' . $dataSesi['jam_selesai'] . ':00';
                }

                // Ambil ID Pemateri pertama dari array pemateri_ids untuk sesi ini
                // karena API jadwal hanya menerima satu id_pemateri.
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
                
                Log::info("[STORE_KEGIATAN_V6_FIX_VALIDATION] Mengirim data jadwal (Sesi ".($indexSesi+1).") ke API:", $apiJadwalData);
                $responseJadwal = Http::post("{$this->apiBaseUrl}/jadwal-kegiatan", $apiJadwalData); 
                if (!$responseJadwal->successful() || $responseJadwal->json('success') !== true) {
                    Log::error('[STORE_KEGIATAN_V6_FIX_VALIDATION] Gagal menyimpan jadwal kegiatan (Sesi '.($indexSesi+1).') via API: ' . $responseJadwal->status() . ' - ' . $responseJadwal->body(), $responseJadwal->json() ?? []);
                } else {
                     Log::info('[STORE_KEGIATAN_V6_FIX_VALIDATION] Respon API jadwal (Sesi '.($indexSesi+1).'):', $responseJadwal->json());
                }

                // LANGKAH 4 (per sesi): Simpan Relasi Pemateri dengan Kegiatan (PEMATERIKEGIATAN_PUST)
                // Ini masih memerlukan endpoint API baru untuk tabel pivot.
                if(isset($dataSesi['pemateri_ids']) && is_array($dataSesi['pemateri_ids'])){
                    foreach ($dataSesi['pemateri_ids'] as $idPemateriDariForm) {
                        if (!empty($idPemateriDariForm)) {
                            Log::warning("[STORE_KEGIATAN_V6_FIX_VALIDATION] Memproses ID_PEMATERI: {$idPemateriDariForm} untuk kegiatan '{$createdKegiatanId}' (Sesi ".($indexSesi+1)."). Namun, TIDAK ADA ENDPOINT API untuk menyimpan relasi ini ke tabel pivot PEMATERIKEGIATAN_PUST. Data ini TIDAK AKAN TERSIMPAN.");
                        }
                    }
                }
            }
        }

        return redirect()->route('kegiatan.index')->with('success', 'Data kegiatan utama berhasil disimpan. Penyimpanan detail sesi (jadwal, sertifikat) telah dicoba (periksa log). Penyimpanan relasi pemateri per sesi memerlukan API tambahan.');
    }
    
    public function show(string $id)
    {
        // ... (Method show tetap sama seperti versi sebelumnya) ...
        $kegiatan = null;
        Log::info("[SHOW_KEGIATAN] Memulai pengambilan data untuk ID Kegiatan: {$id}");
        try {
            $responseKegiatan = Http::get("{$this->apiBaseUrl}/kegiatan");
            if ($responseKegiatan->successful() && is_array($responseKegiatan->json())) {
                $kegiatan = collect($responseKegiatan->json())->first(function ($item) use ($id) {
                    $item = (object) $item; return ($item->id_kegiatan ?? null) == $id; 
                });
                if ($kegiatan) {
                    $kegiatan = (object) $kegiatan;
                    $idKegiatanUtama = $kegiatan->id_kegiatan;
                    Log::info("[SHOW_KEGIATAN] Kegiatan ID {$idKegiatanUtama} ditemukan.", (array)$kegiatan);
                    $responseJadwal = Http::get("{$this->apiBaseUrl}/jadwal-kegiatan");
                    if ($responseJadwal->successful() && is_array($responseJadwal->json())) {
                        $allJadwal = collect($responseJadwal->json())->map(fn($item) => (object) $item);
                        $kegiatan->jadwal = $allJadwal->filter(fn($jadwal) => ($jadwal->id_kegiatan ?? null) == $idKegiatanUtama)
                                                    ->sortBy(function($jadwal) {
                                                        $tgl = $jadwal->tgl_kegiatan ?? '1970-01-01 00:00:00';
                                                        $waktu = $jadwal->waktu_mulai ?? $tgl;
                                                        try { return \Carbon\Carbon::parse($waktu)->timestamp; } catch (\Exception $e) { try { return \Carbon\Carbon::parse($tgl)->timestamp; } catch (\Exception $ex) { return 0;}}
                                                    })->values();
                    } else { $kegiatan->jadwal = new Collection(); Log::error("[SHOW_KEGIATAN] Gagal mengambil jadwal. API Response: " . $responseJadwal->body());}
                    $responseMasterPemateri = Http::get("{$this->apiBaseUrl}/pemateri-kegiatan");
                    $allMasterPemateri = new Collection();
                    if($responseMasterPemateri->successful() && is_array($responseMasterPemateri->json())){
                        $allMasterPemateri = collect($responseMasterPemateri->json())->map(fn($item) => (object) $item);
                    }
                    $kegiatan->pemateri = new Collection();
                    if ($kegiatan->jadwal->isNotEmpty()) {
                        foreach($kegiatan->jadwal as $jadwalItem) {
                            $idPemateriDiJadwal = $jadwalItem->id_pemateri ?? null;
                            if ($idPemateriDiJadwal) {
                                $foundMasterPemateri = $allMasterPemateri->first(fn($master) => ($master->id_pemateri ?? null) == $idPemateriDiJadwal);
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
                    $responseSertifikat = Http::get("{$this->apiBaseUrl}/sertifikat");
                    if($responseSertifikat->successful() && is_array($responseSertifikat->json())){
                        $sertifikatTerkait = collect($responseSertifikat->json())->first(function($sert) use ($idKegiatanUtama){
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
        // ... (Method edit tetap sama seperti versi sebelumnya) ...
        $kegiatan = null;
        $masterPemateri = new Collection();
        Log::info("[EDIT_KEGIATAN] Memulai pengambilan data untuk edit ID Kegiatan: {$id}");
        try {
            $responseKegiatan = Http::get("{$this->apiBaseUrl}/kegiatan");
            if ($responseKegiatan->successful() && is_array($responseKegiatan->json())) {
                $kegiatan = collect($responseKegiatan->json())->first(function ($item) use ($id) {
                    $item = (object) $item; return ($item->id_kegiatan ?? null) == $id;
                });
                if ($kegiatan) {
                    $kegiatan = (object) $kegiatan;
                    $idKegiatanUtama = $kegiatan->id_kegiatan;
                    $responseJadwal = Http::get("{$this->apiBaseUrl}/jadwal-kegiatan");
                    if ($responseJadwal->successful() && is_array($responseJadwal->json())) {
                        $allJadwal = collect($responseJadwal->json())->map(fn($item) => (object) $item);
                        $kegiatan->jadwal = $allJadwal->filter(fn($jadwal) => ($jadwal->id_kegiatan ?? null) == $idKegiatanUtama)
                                                    ->sortBy(function($jadwal) {
                                                        $tgl = $jadwal->tgl_kegiatan ?? '1970-01-01 00:00:00';
                                                        $waktu = $jadwal->waktu_mulai ?? $tgl;
                                                        try { return \Carbon\Carbon::parse($waktu)->timestamp; } catch (\Exception $e) { return \Carbon\Carbon::parse($tgl)->timestamp;}
                                                    })->values();
                    } else { $kegiatan->jadwal = new Collection(); }
                    $responseMasterPemateri = Http::get("{$this->apiBaseUrl}/pemateri-kegiatan");
                    if($responseMasterPemateri->successful() && is_array($responseMasterPemateri->json())){
                        $masterPemateri = collect($responseMasterPemateri->json())->map(fn($item) => (object) $item);
                    }
                    $kegiatan->selected_pemateri_ids = new Collection(); // Ini akan diisi dengan ID pemateri per sesi
                    if ($kegiatan->jadwal->isNotEmpty()) {
                        foreach($kegiatan->jadwal as $jadwalItem) {
                            // Untuk form edit, kita perlu menyimpan ID pemateri yang sudah terpilih untuk setiap sesi
                            // Jika API jadwal mengembalikan id_pemateri, kita bisa langsung gunakan itu
                            $idPemateriDiJadwal = $jadwalItem->id_pemateri ?? null;
                            if ($idPemateriDiJadwal) {
                                // Kita bisa membuat array asosiatif atau struktur lain jika perlu
                                // Untuk kesederhanaan, kita asumsikan setiap jadwal hanya punya satu pemateri di form edit ini
                                // $kegiatan->selected_pemateri_ids->put($jadwalItem->id_jadwal, $idPemateriDiJadwal); // Contoh jika ingin map by id_jadwal
                                if(!$kegiatan->selected_pemateri_ids->contains($idPemateriDiJadwal)){ // Hanya untuk contoh jika ingin daftar unik
                                     $kegiatan->selected_pemateri_ids->push($idPemateriDiJadwal);
                                }
                            }
                        }
                    }
                    $kegiatan->template_sertifikat_file = null; 
                    $responseSertifikat = Http::get("{$this->apiBaseUrl}/sertifikat");
                    if($responseSertifikat->successful() && is_array($responseSertifikat->json())){
                        $sertifikatTerkait = collect($responseSertifikat->json())->first(function($sert) use ($idKegiatanUtama){
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

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Log::info("[UPDATE_KEGIATAN_V3_SESSION_FORM] Menerima request data untuk ID {$id}:", $request->all());

        // --- MODIFIKASI: Normalisasi input jam_selesai ---
        $sesiInput = $request->input('sesi', []);
        if (is_array($sesiInput)) {
            foreach ($sesiInput as $key => &$sesiItem) { // Gunakan reference (&) untuk modifikasi langsung
                if (isset($sesiItem['jam_selesai']) && $sesiItem['jam_selesai'] === '') {
                    $sesiItem['jam_selesai'] = null;
                }
            }
            unset($sesiItem); // Hapus reference setelah loop
            $request->merge(['sesi' => $sesiInput]); // Ganti input 'sesi' dengan versi yang sudah dinormalisasi
        }
        Log::info("[UPDATE_KEGIATAN_V3_SESSION_FORM] Data 'sesi' setelah normalisasi jam_selesai:", $request->input('sesi'));
        // --- AKHIR MODIFIKASI ---

        $validatedData = $request->validate([
            'judul' => 'required|string|max:50',
            'media' => 'required|string|max:20',
            'lokasi' => 'nullable|string|max:50',
            'template_sertifikat' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
            'keterangan_kegiatan' => 'nullable|string',
            'bobot_kegiatan' => 'required|integer|min:0',
            'sesi' => 'required|array|min:1',
            // 'sesi.*.id_jadwal' => 'nullable|numeric', // Baris ini ada jika Anda menggunakan strategi update jadwal per-ID
            'sesi.*.tanggal' => 'required|date_format:Y-m-d',
            'sesi.*.jam_mulai' => 'required|date_format:H:i',
            'sesi.*.jam_selesai' => 'nullable|date_format:H:i|after_or_equal:sesi.*.jam_mulai',
            'sesi.*.id_pemateri' => 'required|numeric'
        ]);

        $idKegiatanToUpdate = $id;

        // 1. Update Data Kegiatan Utama
        $apiKegiatanData = [
            'judul_kegiatan' => $validatedData['judul'],
            'media' => $validatedData['media'],
            'lokasi' => $validatedData['lokasi'] ?? '',
            'keterangan' => $validatedData['keterangan_kegiatan'] ?? '',
        ];
        Log::info("[UPDATE_KEGIATAN_V3_SESSION_FORM] Mengirim data update kegiatan utama ke API untuk ID {$idKegiatanToUpdate}:", $apiKegiatanData);
        $responseKegiatan = Http::put("{$this->apiBaseUrl}/kegiatan/{$idKegiatanToUpdate}", $apiKegiatanData);

        if (!$responseKegiatan->successful() || ($responseKegiatan->json('success') !== true && !in_array($responseKegiatan->status(), [200, 204]))) {
            Log::error("[UPDATE_KEGIATAN_V3_SESSION_FORM] Gagal mengupdate kegiatan utama via API: " . $responseKegiatan->status() . " - " . $responseKegiatan->body(), $responseKegiatan->json() ?? []);
            return back()->withInput()->withErrors(['api_error_kegiatan' => 'Gagal mengupdate data kegiatan utama: ' . ($responseKegiatan->json('message') ?? 'Error tidak diketahui dari API.') . ($responseKegiatan->json('errors') ? ' Details: '.json_encode($responseKegiatan->json('errors')) : '') ]);
        }
        Log::info('[UPDATE_KEGIATAN_V3_SESSION_FORM] Respon API update kegiatan utama:', $responseKegiatan->json() ?? '[No JSON Response Body]');

        // 2. Handle Update/Upload File Sertifikat
        if ($request->hasFile('template_sertifikat') && $request->file('template_sertifikat')->isValid()) {
            Log::info("[UPDATE_KEGIATAN_V3_SESSION_FORM] File sertifikat baru diunggah untuk kegiatan ID {$idKegiatanToUpdate}.");
            $oldSertifikatData = null; // Ganti nama variabel agar lebih jelas
            try {
                $responseOldSert = Http::get("{$this->apiBaseUrl}/sertifikat");
                if($responseOldSert->successful() && is_array($responseOldSert->json())){
                    $foundSert = collect($responseOldSert->json())->first(function($sert) use ($idKegiatanToUpdate){
                        $sert = (object) $sert;
                        return ($sert->id_kegiatan ?? null) == $idKegiatanToUpdate && ($sert->nim ?? null) == 'TEMPLATE_KEGIATAN';
                    });
                    if($foundSert) $oldSertifikatData = (object)$foundSert;
                }
            } catch (\Exception $e) { Log::error("[UPDATE_KEGIATAN_V3_SESSION_FORM] Exception saat mencari sertifikat lama: " . $e->getMessage()); }

            if($oldSertifikatData){
                $oldSertifikatId = $oldSertifikatData->id_sertifikat ?? $oldSertifikatData->id ?? null;
                $oldNamaFile = $oldSertifikatData->nama_file ?? null;
                if($oldSertifikatId){
                    Log::info("[UPDATE_KEGIATAN_V3_SESSION_FORM] Menghapus template sertifikat lama ID record: {$oldSertifikatId}");
                    Http::delete("{$this->apiBaseUrl}/sertifikat/{$oldSertifikatId}");
                }
                if ($oldNamaFile) {
                    Log::info("[UPDATE_KEGIATAN_V3_SESSION_FORM] Menghapus file sertifikat lama dari storage: {$oldNamaFile}");
                    Storage::delete('public/sertifikat_templates_kegiatan/' . $oldNamaFile);
                }
            }

            try {
                $file = $request->file('template_sertifikat');
                $namaFileSertifikat = 'tpl_sert_keg_' . $idKegiatanToUpdate . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/sertifikat_templates_kegiatan', $namaFileSertifikat);
                Log::info("[UPDATE_KEGIATAN_V3_SESSION_FORM] File sertifikat baru berhasil diunggah: " . $namaFileSertifikat);

                $nextSertifikatId = $this->getNextIdFromApi('sertifikat', 'id_sertifikat');
                if ($nextSertifikatId !== null) {
                    $apiSertifikatData = [
                        'id' => $nextSertifikatId,
                        'id_kegiatan' => $idKegiatanToUpdate,
                        'nama_file' => $namaFileSertifikat,
                        'nim' => 'TEMPLATE_KEGIATAN',
                    ];
                    Log::info('[UPDATE_KEGIATAN_V3_SESSION_FORM] Mengirim data sertifikat template baru ke API:', $apiSertifikatData);
                    $resSertPost = Http::post("{$this->apiBaseUrl}/sertifikat", $apiSertifikatData);
                    if (!$resSertPost->successful() || $resSertPost->json('success') !== true) {
                        Log::error('[UPDATE_KEGIATAN_V3_SESSION_FORM] Gagal menyimpan record sertifikat template baru via API: ' . $resSertPost->status() . ' - ' . $resSertPost->body());
                    } else {
                        Log::info('[UPDATE_KEGIATAN_V3_SESSION_FORM] Sukses menyimpan record sertifikat template baru.');
                    }
                } else {
                     Log::error("[UPDATE_KEGIATAN_V3_SESSION_FORM] Gagal men-generate ID untuk sertifikat baru.");
                }
            } catch (\Exception $e) {
                Log::error('[UPDATE_KEGIATAN_V3_SESSION_FORM] Gagal mengunggah atau menyimpan info file sertifikat baru: ' . $e->getMessage());
            }
        }

        // 3. Handle Update Jadwal Kegiatan (Strategi: Hapus semua jadwal lama, lalu insert yang baru dari form)
        Log::info("[UPDATE_KEGIATAN_V3_SESSION_FORM] Memulai proses update jadwal untuk kegiatan ID {$idKegiatanToUpdate} (Strategi: Hapus & Buat Ulang).");
        $jadwalLamaIds = new Collection();
        try {
            $responseJadwalLama = Http::get("{$this->apiBaseUrl}/jadwal-kegiatan");
            if($responseJadwalLama->successful() && is_array($responseJadwalLama->json())){
                $jadwalLamaIds = collect($responseJadwalLama->json())
                                ->filter(fn($j) => ((object)$j)->id_kegiatan == $idKegiatanToUpdate)
                                ->pluck('id_jadwal') // Asumsikan 'id_jadwal' adalah primary key dari tabel jadwal
                                ->filter()
                                ->values(); // Ambil hanya value ID nya
            }
        } catch (\Exception $e) { Log::error("[UPDATE_KEGIATAN_V3_SESSION_FORM] Exception saat mengambil jadwal lama: " . $e->getMessage()); }

        Log::info("[UPDATE_KEGIATAN_V3_SESSION_FORM] Daftar ID Jadwal Lama yang akan dihapus: ", $jadwalLamaIds->all());

        if ($jadwalLamaIds->isNotEmpty()) {
            $allHadirKegiatan = new Collection();
            try {
                $responseHadir = Http::get("{$this->apiBaseUrl}/hadir-kegiatan");
                if ($responseHadir->successful() && is_array($responseHadir->json())) {
                    $allHadirKegiatan = collect($responseHadir->json())->map(fn($item) => (object) $item);
                }
            } catch (\Exception $e) {Log::error("[UPDATE_KEGIATAN_V3_SESSION_FORM] Exception saat mengambil daftar hadir untuk penghapusan terkait: " . $e->getMessage());}

            foreach($jadwalLamaIds as $idJadwalLama){
                if ($idJadwalLama === null) continue; // Skip jika ID null

                // Hapus kehadiran terkait jadwal lama
                $hadirTerkaitJadwal = $allHadirKegiatan->filter(fn($h) => ($h->id_jadwal ?? null) == $idJadwalLama);
                foreach($hadirTerkaitJadwal as $hadir) {
                    $idHadirToDelete = $hadir->id_hadir ?? $hadir->id ?? null; // Cek juga 'id' jika 'id_hadir' tidak ada
                    if($idHadirToDelete){
                        Log::info("[UPDATE_KEGIATAN_V3_SESSION_FORM] Menghapus kehadiran ID: {$idHadirToDelete} terkait jadwal lama ID: {$idJadwalLama}");
                        Http::delete("{$this->apiBaseUrl}/hadir-kegiatan/{$idHadirToDelete}");
                    }
                }
                // Hapus jadwal lama
                Log::info("[UPDATE_KEGIATAN_V3_SESSION_FORM] Menghapus jadwal lama ID: {$idJadwalLama}");
                $delJadwalRes = Http::delete("{$this->apiBaseUrl}/jadwal-kegiatan/{$idJadwalLama}");
                 if (!$delJadwalRes->successful()){
                     Log::error("[UPDATE_KEGIATAN_V3_SESSION_FORM] Gagal hapus jadwal lama ID {$idJadwalLama}. Response: " . $delJadwalRes->body());
                 }
            }
        }

        // Buat ulang jadwal dari data sesi yang ada di form
        if (isset($validatedData['sesi']) && is_array($validatedData['sesi'])) {
            foreach ($validatedData['sesi'] as $indexSesi => $dataSesi) {
                $nextJadwalId = $this->getNextIdFromApi('jadwal-kegiatan', 'id_jadwal');
                if ($nextJadwalId === null) {
                    Log::error("[UPDATE_KEGIATAN_V3_SESSION_FORM] Gagal men-generate ID Jadwal baru untuk sesi index {$indexSesi}, skip pembuatan jadwal ini.");
                    continue;
                }
                $waktuMulaiString = $dataSesi['tanggal'] . ' ' . $dataSesi['jam_mulai'] . ':00';
                $waktuSelesaiString = (isset($dataSesi['jam_selesai']) && !empty($dataSesi['jam_selesai'])) ? $dataSesi['tanggal'] . ' ' . $dataSesi['jam_selesai'] . ':00' : null;

                $idPemateriUntukSesiIni = $dataSesi['id_pemateri'] ?? 0;
                if (empty($idPemateriUntukSesiIni) && $idPemateriUntukSesiIni !== 0) $idPemateriUntukSesiIni = 0;

                $apiJadwalData = [
                    'id' => $nextJadwalId,
                    'id_kegiatan' => (int) $idKegiatanToUpdate, // Pastikan integer
                    'tgl_kegiatan' => $dataSesi['tanggal'],
                    'waktu_mulai' => $waktuMulaiString,
                    'waktu_selesai' => $waktuSelesaiString,
                    'bobot' => $validatedData['bobot_kegiatan'],
                    'keterangan' => $validatedData['keterangan_kegiatan'] ?? ('Sesi ke-' . ($indexSesi + 1) . ' untuk ' . $validatedData['judul']),
                    'id_pemateri' => (int) $idPemateriUntukSesiIni, // Pastikan integer
                    'kode_random' => Str::upper(Str::random(10))
                ];
                Log::info('[UPDATE_KEGIATAN_V3_SESSION_FORM] Mengirim data jadwal baru (Sesi '.($indexSesi+1).') ke API (setelah hapus lama):', $apiJadwalData);
                $resJadwalPost = Http::post("{$this->apiBaseUrl}/jadwal-kegiatan", $apiJadwalData);
                if (!$resJadwalPost->successful() || $resJadwalPost->json('success') !== true) {
                    Log::error('[UPDATE_KEGIATAN_V3_SESSION_FORM] Gagal menyimpan jadwal baru (Sesi '.($indexSesi+1).') via API: ' . $resJadwalPost->status() . ' - ' . $resJadwalPost->body());
                } else {
                    Log::info('[UPDATE_KEGIATAN_V3_SESSION_FORM] Sukses menyimpan jadwal baru (Sesi '.($indexSesi+1).').');
                }
            }
        }

        return redirect()->route('kegiatan.index')->with('success', 'Kegiatan berhasil diupdate. Periksa log untuk detail.');
    }


    public function destroy(string $id) 
    {
        // ... (Method destroy tetap sama seperti versi sebelumnya) ...
        $idKegiatan = $id; 
        Log::info("[DESTROY_KEGIATAN] Memulai proses penghapusan untuk ID Kegiatan: {$idKegiatan}");
        $errors = [];
        try {
            $responseSertifikat = Http::get("{$this->apiBaseUrl}/sertifikat");
            if ($responseSertifikat->successful() && is_array($responseSertifikat->json())) {
                $allSertifikat = collect($responseSertifikat->json());
                $sertifikatTerkait = $allSertifikat->filter(fn($s) => ((object)$s)->id_kegiatan == $idKegiatan);
                foreach ($sertifikatTerkait as $sertifikat) {
                    $idSertifikatToDelete = ((object)$sertifikat)->id_sertifikat ?? null;
                    if ($idSertifikatToDelete) {
                        Log::info("[DESTROY_KEGIATAN] Menghapus sertifikat ID: {$idSertifikatToDelete}");
                        $delResponse = Http::delete("{$this->apiBaseUrl}/sertifikat/{$idSertifikatToDelete}");
                        if (!$delResponse->successful() || $delResponse->json('success') !== true) $errors[] = "Gagal hapus sertifikat ID {$idSertifikatToDelete}";
                    }
                }
            }
        } catch (\Exception $e) { Log::error("[DESTROY_KEGIATAN] Exception sertifikat: " . $e->getMessage()); $errors[] = "Error sertifikat.";}
        $allHadirKegiatan = new Collection();
        try {
            $responseHadir = Http::get("{$this->apiBaseUrl}/hadir-kegiatan");
            if ($responseHadir->successful() && is_array($responseHadir->json())) {
                $allHadirKegiatan = collect($responseHadir->json())->map(fn($item) => (object) $item);
            }
        } catch (\Exception $e) { Log::error("[DESTROY_KEGIATAN] Exception hadir: " . $e->getMessage());}
        try {
            $responseJadwal = Http::get("{$this->apiBaseUrl}/jadwal-kegiatan");
            if ($responseJadwal->successful() && is_array($responseJadwal->json())) {
                $allJadwal = collect($responseJadwal->json());
                $jadwalTerkait = $allJadwal->filter(fn($j) => ((object)$j)->id_kegiatan == $idKegiatan);
                foreach ($jadwalTerkait as $jadwal) {
                    $idJadwalToDelete = ((object)$jadwal)->id_jadwal ?? null;
                    if ($idJadwalToDelete) {
                        $hadirTerkaitJadwal = $allHadirKegiatan->filter(fn($h) => ((object)$h)->id_jadwal == $idJadwalToDelete);
                        foreach($hadirTerkaitJadwal as $hadir) {
                            $idHadirToDelete = ((object)$hadir)->id_hadir ?? null;
                            if($idHadirToDelete){
                                $delHadirResponse = Http::delete("{$this->apiBaseUrl}/hadir-kegiatan/{$idHadirToDelete}");
                                if (!$delHadirResponse->successful() || $delHadirResponse->json('success') !== true) $errors[] = "Gagal hapus hadir ID {$idHadirToDelete}";
                            }
                        }
                        $delJadwalResponse = Http::delete("{$this->apiBaseUrl}/jadwal-kegiatan/{$idJadwalToDelete}");
                        if (!$delJadwalResponse->successful() || $delJadwalResponse->json('success') !== true) $errors[] = "Gagal hapus jadwal ID {$idJadwalToDelete}";
                    }
                }
            }
        } catch (\Exception $e) { Log::error("[DESTROY_KEGIATAN] Exception jadwal: " . $e->getMessage()); $errors[] = "Error jadwal.";}
        Log::warning("[DESTROY_KEGIATAN] Penghapusan relasi pemateri dari PEMATERIKEGIATAN_PUST tidak dapat dilakukan karena tidak ada endpoint API yang sesuai.");
        if (empty($errors)) { 
            $response = Http::delete("{$this->apiBaseUrl}/kegiatan/{$idKegiatan}");
            if ($response->successful() && $response->json('success') === true) {
                return redirect()->route('kegiatan.index')->with('success', 'Kegiatan dan data terkait berhasil dihapus via API!');
            } else {
                $errorMessage = $response->json('message') ?? 'Gagal menghapus data kegiatan utama.';
                $errors[] = $errorMessage;
            }
        }
        $combinedErrorMessages = implode('; ', $errors);
        return redirect()->route('kegiatan.index')->withErrors(['api_error' => 'Gagal menghapus kegiatan atau beberapa data terkait. ' . $combinedErrorMessages]);
    }

    public function daftarHadir(string $idKegiatan) 
    {
        // ... (Method daftarHadir tetap sama seperti versi sebelumnya) ...
        Log::info("[DAFTAR_HADIR] Memulai pengambilan data untuk ID Kegiatan: {$idKegiatan}");
        $kegiatan = null;
        $jadwalDenganKehadiran = new Collection();
        try {
            $responseKegiatan = Http::get("{$this->apiBaseUrl}/kegiatan");
            if ($responseKegiatan->successful() && is_array($responseKegiatan->json())) {
                $kegiatan = collect($responseKegiatan->json())->first(function ($item) use ($idKegiatan) {
                    $item = (object) $item; return ($item->id_kegiatan ?? null) == $idKegiatan;
                });
                if (!$kegiatan) { abort(404, 'Kegiatan tidak ditemukan.');}
                $kegiatan = (object) $kegiatan;
                $responseAllJadwal = Http::get("{$this->apiBaseUrl}/jadwal-kegiatan");
                if (!$responseAllJadwal->successful() || !is_array($responseAllJadwal->json())) { throw new \Exception("Gagal mengambil data jadwal.");}
                $allJadwal = collect($responseAllJadwal->json())->map(fn($item) => (object) $item);
                $jadwalUntukKegiatanIni = $allJadwal->filter(fn($jadwal) => ($jadwal->id_kegiatan ?? null) == $idKegiatan)
                                                    ->sortBy(function($jadwal) {
                                                        $tgl = $jadwal->tgl_kegiatan ?? '1970-01-01 00:00:00';
                                                        $waktu = $jadwal->waktu_mulai ?? $tgl;
                                                        try { return \Carbon\Carbon::parse($waktu)->timestamp; } catch (\Exception $e) { try { return \Carbon\Carbon::parse($tgl)->timestamp; } catch (\Exception $ex) { return 0;}}
                                                    })->values();
                $responseAllHadir = Http::get("{$this->apiBaseUrl}/hadir-kegiatan");
                if (!$responseAllHadir->successful() || !is_array($responseAllHadir->json())) { throw new \Exception("Gagal mengambil data kehadiran.");}
                $allHadirKegiatan = collect($responseAllHadir->json())->map(fn($item) => (object) $item);
                $jadwalDenganKehadiran = $jadwalUntukKegiatanIni->map(function ($jadwal) use ($allHadirKegiatan) {
                    $idJadwalIni = $jadwal->id_jadwal ?? null;
                    if ($idJadwalIni) {
                        $jadwal->kehadiran = $allHadirKegiatan->filter(fn($hadir) => ($hadir->id_jadwal ?? null) == $idJadwalIni)->pluck('nim')->filter()->values();
                    } else { $jadwal->kehadiran = new Collection(); }
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
