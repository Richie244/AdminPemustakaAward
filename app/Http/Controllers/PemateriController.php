<?php

namespace App\Http\Controllers;

use App\Services\MyApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\RequestException;


class PemateriController extends Controller
{
    protected MyApiService $apiService;

    public function __construct(MyApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    // Method index() tidak berubah

    // ... (method index() yang sudah ada) ...
    public function index(Request $request)
    {
        Log::info('[PEMATERI_INDEX] Memuat daftar pemateri.', $request->all());
        $searchTerm = $request->input('search');
        $perPage = 15;
        $currentPage = Paginator::resolveCurrentPage('page') ?: 1;

        $pemateriListPaginator = new LengthAwarePaginator(
            new Collection(), 0, $perPage, $currentPage,
            ['path' => route('master-pemateri.index'), 'query' => $request->query()]
        );
        $error_message = null;

        try {
            // 1. Ambil semua data master perusahaan sekali saja
            $responsePerusahaan = $this->apiService->getPerusahaanPemateriList();
            Log::info('[PEMATERI_INDEX] Respons dari getPerusahaanPemateriList:', is_array($responsePerusahaan) ? $responsePerusahaan : ['response_type' => gettype($responsePerusahaan)]);

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
                Log::info('[PEMATERI_INDEX] Data Perusahaan Pemateri (keyed):', $allPerusahaan->toArray());
            } else {
                Log::warning('[PEMATERI_INDEX] Gagal mengambil daftar perusahaan atau API mengembalikan error.', $responsePerusahaan ?? []);
                if(isset($responsePerusahaan['_error']) && ($responsePerusahaan['_status'] ?? null) == 404) {
                    Log::info('[PEMATERI_INDEX] Endpoint perusahaan pemateri tidak ditemukan (404), melanjutkan tanpa data perusahaan.');
                } else if (isset($responsePerusahaan['_error'])) {
                     $error_message = "Warning: Data perusahaan tidak dapat dimuat. ";
                }
            }

            // 2. Ambil data pemateri
            $apiParams = $searchTerm ? ['search' => $searchTerm] : [];
            $responsePemateri = $this->apiService->getPemateriList($apiParams);

            Log::info('[PEMATERI_INDEX] Respons dari getPemateriList (endpoint pemateri-kegiatan):', is_array($responsePemateri) ? $responsePemateri : ['response_type' => gettype($responsePemateri)]);

            $pemateriCollection = new Collection();

            if ($responsePemateri && !isset($responsePemateri['_error']) && !isset($responsePemateri['_success_no_content'])) {
                $dataFromApi = isset($responsePemateri['data']) && is_array($responsePemateri['data']) ? $responsePemateri['data'] : (is_array($responsePemateri) ? $responsePemateri : []);

                if (!empty($dataFromApi)) {
                    $pemateriCollection = collect($dataFromApi)->map(function($item) use ($allPerusahaan) {
                        $pemateriObj = (object) $item;
                        $pemateriObj->id_pemateri = $pemateriObj->id_pemateri ?? $pemateriObj->ID_PEMATERI ?? null;
                        $pemateriObj->nama_pemateri = $pemateriObj->nama_pemateri ?? $pemateriObj->NAMA_PEMATERI ?? 'Nama Tidak Ada';
                        $pemateriObj->email = $pemateriObj->email ?? $pemateriObj->EMAIL ?? null;
                        $pemateriObj->no_hp = $pemateriObj->no_hp ?? $pemateriObj->NO_HP ?? null;
                        $idPerusahaanPemateri = $pemateriObj->id_perusahaan ?? $pemateriObj->ID_PERUSAHAAN ?? null;
                        $pemateriObj->id_perusahaan_numeric = is_numeric($idPerusahaanPemateri) ? (int)$idPerusahaanPemateri : null;

                        if ($pemateriObj->id_perusahaan_numeric !== null && $allPerusahaan->has($pemateriObj->id_perusahaan_numeric)) {
                            $perusahaan = $allPerusahaan->get($pemateriObj->id_perusahaan_numeric);
                            $pemateriObj->nama_perusahaan_display = $perusahaan->nama_perusahaan ?? 'Tidak diketahui';
                        } else if ($pemateriObj->id_perusahaan_numeric == 1) {
                             $pemateriObj->nama_perusahaan_display = 'Universitas Dinamika';
                        }
                         else {
                            $pemateriObj->nama_perusahaan_display = '-';
                        }
                        $pemateriObj->tipe_pemateri = ($pemateriObj->id_perusahaan_numeric == 1) ? 'Internal' : 'Eksternal';
                        if ($pemateriObj->id_perusahaan_numeric === null) {
                             $pemateriObj->tipe_pemateri = 'Eksternal (Individu)';
                             $pemateriObj->nama_perusahaan_display = '-';
                        }
                        return $pemateriObj;
                    });
                }
                 Log::info('[PEMATERI_INDEX] Koleksi pemateri setelah map:', $pemateriCollection->toArray());

            } elseif ($responsePemateri && isset($responsePemateri['_error'])) {
                Log::error('[PEMATERI_INDEX] API Error saat mengambil daftar pemateri.', $responsePemateri);
                $error_message = ($error_message ? $error_message . '; ' : '') . ($responsePemateri['_json_error_data']['message'] ?? ($responsePemateri['_body'] ?? 'Gagal memuat data pemateri dari API.'));
            } elseif (isset($responsePemateri['_success_no_content'])) {
                Log::info('[PEMATERI_INDEX] API pemateri mengembalikan success_no_content.');
                $error_message = ($error_message ? $error_message . '; ' : '') . 'Tidak ada data pemateri yang ditemukan.';
            } else {
                Log::warning('[PEMATERI_INDEX] Respons tidak valid dari API getPemateriList.', (array)$responsePemateri);
                if (empty($error_message)) {
                    $error_message = 'Tidak ada data pemateri atau respons API tidak valid.';
                }
            }

            if ($searchTerm && $pemateriCollection->isNotEmpty()) {
                 $pemateriCollection = $pemateriCollection->filter(function ($pemateri) use ($searchTerm) {
                    $namaMatch = isset($pemateri->nama_pemateri) && stripos($pemateri->nama_pemateri, $searchTerm) !== false;
                    $emailMatch = isset($pemateri->email) && stripos($pemateri->email, $searchTerm) !== false;
                    $perusahaanMatch = isset($pemateri->nama_perusahaan_display) && $pemateri->nama_perusahaan_display !== '-' && stripos($pemateri->nama_perusahaan_display, $searchTerm) !== false;
                    return $namaMatch || $emailMatch || $perusahaanMatch;
                });
            }

            $currentPage = Paginator::resolveCurrentPage('page') ?: 1;
            $currentPageItems = $pemateriCollection->slice(($currentPage - 1) * $perPage, $perPage)->values()->all();

            $pemateriListPaginator = new LengthAwarePaginator(
                $currentPageItems,
                $pemateriCollection->count(),
                $perPage,
                $currentPage,
                ['path' => route('master-pemateri.index'), 'query' => $request->query()]
            );

        } catch (RequestException $re) {
            Log::error('[PEMATERI_INDEX] HTTP Request Exception: ' . $re->getMessage(), [
                'status' => $re->response ? $re->response->status() : 'N/A',
                'url' => $re->response ? $re->response->effectiveUri() : 'N/A',
            ]);
            $status = $re->response ? $re->response->status() : 'Unknown';
            if ($status == 404) {
                $error_message = "Endpoint API tidak ditemukan (404). Harap periksa konfigurasi URL API dan pastikan endpoint yang relevan ada di backend. URL yang diakses: " . ($re->response ? $re->response->effectiveUri() : 'Tidak diketahui');
            } else {
                $error_message = "Gagal mengambil data dari API (Status HTTP: {$status}). URL: " . ($re->response ? $re->response->effectiveUri() : 'Tidak diketahui');
            }
        } catch (\Exception $e) {
            Log::error('[PEMATERI_INDEX] General Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $error_message = 'Terjadi kesalahan sistem umum saat memuat data pemateri.';
        }

        return view('pemateri', [
            'pemateriList' => $pemateriListPaginator,
            'searchTerm' => $searchTerm,
            'error_message' => $error_message
        ]);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        Log::info('[PEMATERI_CREATE] Menampilkan form tambah pemateri.');
        $perusahaanList = new Collection();
        $error_message_perusahaan = null;

        try {
            $responsePerusahaan = $this->apiService->getPerusahaanPemateriList();
            if ($responsePerusahaan && !isset($responsePerusahaan['_error'])) {
                $perusahaanDataFromApi = isset($responsePerusahaan['data']) && is_array($responsePerusahaan['data']) ? $responsePerusahaan['data'] : (is_array($responsePerusahaan) ? $responsePerusahaan : []);
                if (!empty($perusahaanDataFromApi)) {
                    $perusahaanList = collect($perusahaanDataFromApi)->map(function($item) {
                        return (object) $item;
                    })->filter(fn($p) => isset($p->id_perusahaan) || isset($p->ID_PERUSAHAAN));
                }
            } else {
                Log::warning('[PEMATERI_CREATE] Gagal mengambil daftar perusahaan untuk form.', $responsePerusahaan ?? []);
                $error_message_perusahaan = 'Gagal memuat daftar perusahaan. Silakan coba lagi nanti.';
                 if (isset($responsePerusahaan['_status']) && $responsePerusahaan['_status'] == 404) {
                    $error_message_perusahaan = 'Endpoint untuk daftar perusahaan tidak ditemukan (404).';
                }
            }
        } catch (\Exception $e) {
            Log::error('[PEMATERI_CREATE] Exception saat mengambil daftar perusahaan: ' . $e->getMessage());
            $error_message_perusahaan = 'Terjadi kesalahan sistem saat memuat daftar perusahaan.';
        }

        // <<< AWAL PERUBAHAN: Tambah pengambilan data Civitas >>>
        $civitasList = new Collection();
        $error_message_civitas = null;
        try {
            $responseCivitas = $this->apiService->getCivitasList();
            if ($responseCivitas && !isset($responseCivitas['_error'])) {
                $civitasDataFromApi = is_array($responseCivitas) ? $responseCivitas : [];
                $civitasList = collect($civitasDataFromApi)->map(fn($item) => (object) $item)
                                ->filter(fn($c) => isset($c->id_civitas) && isset($c->nama)); // Pastikan data valid
            } else {
                $error_message_civitas = 'Gagal memuat daftar staf internal dari API.';
                Log::error('[PEMATERI_CREATE] Gagal mengambil daftar civitas.', $responseCivitas ?? []);
            }
        } catch (\Exception $e) {
            $error_message_civitas = 'Terjadi kesalahan sistem saat memuat daftar staf internal.';
            Log::error('[PEMATERI_CREATE] Exception saat mengambil daftar civitas: ' . $e->getMessage());
        }
        // <<< AKHIR PERUBAHAN >>>

        return view('tambah-pemateri', [
            'perusahaanList' => $perusahaanList,
            'error_message_perusahaan' => $error_message_perusahaan,
            'civitasList' => $civitasList, // Kirim data civitas ke view
            'error_message_civitas' => $error_message_civitas, // Kirim pesan error civitas
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Log::info('[PEMATERI_STORE_START] Menerima request untuk menyimpan pemateri baru.', $request->all());
    
        $idPerusahaanYangDipilih = $request->input('id_perusahaan');
    
        // Validasi input awal
        $rules = [
            'id_perusahaan' => 'required|numeric',
            'email_pemateri' => 'nullable|email|max:100',
            'no_hp_pemateri' => 'nullable|string|max:20',
        ];
    
        if ($idPerusahaanYangDipilih == 1) { // Asumsi ID 1 adalah "Universitas Dinamika"
            $rules['id_civitas'] = 'required|string|max:100';
        } else {
            $rules['nama_pemateri_eksternal'] = 'required|string|max:100';
        }
    
        $validator = Validator::make($request->all(), $rules);
    
        if ($validator->fails()) {
            Log::warning('[PEMATERI_STORE_VALIDATION_FAIL]', $validator->errors()->toArray());
            return redirect()->route('master-pemateri.create')
                ->withErrors($validator)
                ->withInput();
        }

        // <<< AWAL PERUBAHAN: Validasi Duplikat Nama + Perusahaan >>>

        // 1. Tentukan nama dan ID perusahaan dari request
        $namaPemateriBaru = '';
        $idPerusahaanBaru = (int)$idPerusahaanYangDipilih;

        if ($idPerusahaanBaru == 1) { // Pemateri Internal
            $idCivitas = $request->input('id_civitas');
            $responseCivitas = $this->apiService->getCivitasList();
            $civitasData = collect($responseCivitas)->firstWhere('id_civitas', $idCivitas);

            if ($civitasData) {
                $namaPemateriBaru = ((object)$civitasData)->nama;
            } else {
                return redirect()->route('master-pemateri.create')->with('error', 'Data civitas internal tidak valid untuk validasi.')->withInput();
            }
        } else { // Pemateri Eksternal
            $namaPemateriBaru = $request->input('nama_pemateri_eksternal');
        }

        // 2. Ambil semua data pemateri yang sudah ada dari API
        try {
            $responsePemateri = $this->apiService->getPemateriList();
            $existingPemateriList = new Collection();
            if ($responsePemateri && !isset($responsePemateri['_error'])) {
                 $dataFromApi = isset($responsePemateri['data']) && is_array($responsePemateri['data']) ? $responsePemateri['data'] : (is_array($responsePemateri) ? $responsePemateri : []);
                $existingPemateriList = collect($dataFromApi)->map(fn($item) => (object)$item);
            } else {
                 Log::error('[PEMATERI_STORE_VALIDATION] Gagal mengambil daftar pemateri untuk validasi duplikat.');
                return redirect()->route('master-pemateri.create')->with('error', 'Tidak dapat memvalidasi data duplikat karena gagal memuat daftar pemateri yang ada.')->withInput();
            }
        } catch (\Exception $e) {
            Log::error('[PEMATERI_STORE_VALIDATION] Exception saat mengambil daftar pemateri: ' . $e->getMessage());
            return redirect()->route('master-pemateri.create')->with('error', 'Terjadi kesalahan sistem saat validasi data.')->withInput();
        }

        // 3. Cari duplikat (Nama sama DAN Perusahaan sama). Perbandingan nama bersifat case-insensitive.
        $isDuplicate = $existingPemateriList->contains(function ($pemateri) use ($namaPemateriBaru, $idPerusahaanBaru) {
            $namaSama = isset($pemateri->nama_pemateri) && strcasecmp(trim($pemateri->nama_pemateri), trim($namaPemateriBaru)) == 0;
            $perusahaanSama = isset($pemateri->id_perusahaan) && (int)$pemateri->id_perusahaan == $idPerusahaanBaru;
            return $namaSama && $perusahaanSama;
        });

        // 4. Jika duplikat ditemukan, kembalikan dengan pesan error
        if ($isDuplicate) {
            Log::warning('[PEMATERI_STORE_DUPLICATE] Ditemukan pemateri duplikat.', ['nama' => $namaPemateriBaru, 'id_perusahaan' => $idPerusahaanBaru]);
            
            // Ambil nama perusahaan untuk pesan error yang lebih jelas
            $namaPerusahaan = 'instansi tersebut';
            $responsePerusahaan = $this->apiService->getPerusahaanPemateriList();
            if ($responsePerusahaan && !isset($responsePerusahaan['_error'])) {
                $dataPerusahaan = isset($responsePerusahaan['data']) && is_array($responsePerusahaan['data']) ? $responsePerusahaan['data'] : $responsePerusahaan;
                $perusahaan = collect($dataPerusahaan)->firstWhere('id_perusahaan', $idPerusahaanBaru);
                if ($perusahaan) {
                    $namaPerusahaan = ((object)$perusahaan)->nama_perusahaan;
                }
            }
            
            $errorMessage = "Gagal menyimpan. Pemateri dengan nama '{$namaPemateriBaru}' sudah terdaftar di '{$namaPerusahaan}'.";
            return redirect()->route('master-pemateri.create')->with('error', $errorMessage)->withInput();
        }

        // <<< AKHIR PERUBAHAN >>>

        // Lanjutkan proses penyimpanan jika tidak ada duplikat
        $nextPemateriId = $this->apiService->getNextId('pemateri-kegiatan', 'id_pemateri');
        if ($nextPemateriId === null) {
            return redirect()->route('master-pemateri.create')->with('error', 'Gagal generate ID Pemateri.')->withInput();
        }
    
        $dataPemateri = [];
    
        if ($idPerusahaanYangDipilih == 1) {
            // Logika untuk Pemateri Internal (sudah mengambil data $civitasData di atas)
            $civitasData = (object) collect($this->apiService->getCivitasList())->firstWhere('id_civitas', $request->input('id_civitas'));
            $dataPemateri = [
                'id'            => $nextPemateriId,
                'nama_pemateri' => $civitasData->nama,
                'email'         => $civitasData->email ?? $request->input('email_pemateri'),
                'no_hp'         => $civitasData->hp ?? $request->input('no_hp_pemateri'),
                'id_perusahaan' => (int)$idPerusahaanYangDipilih,
                'id_civitas'    => $request->input('id_civitas'),
            ];
        } else {
            // Logika untuk Pemateri Eksternal
            $dataPemateri = [
                'id'            => $nextPemateriId,
                'nama_pemateri' => $request->input('nama_pemateri_eksternal'),
                'email'         => $request->input('email_pemateri'),
                'no_hp'         => $request->input('no_hp_pemateri'),
                'id_perusahaan' => (int)$idPerusahaanYangDipilih,
            ];
        }
    
        $resultPemateri = $this->apiService->createPemateri($dataPemateri);
    
        if ($resultPemateri && !isset($resultPemateri['_error']) && (!isset($resultPemateri['success']) || $resultPemateri['success'] === true || isset($resultPemateri['_success_no_content']))) {
            Log::info('[PEMATERI_STORE_SUCCESS] Pemateri berhasil disimpan.', ['request_data' => $dataPemateri, 'api_response' => $resultPemateri]);
            return redirect()->route('master-pemateri.index')->with('success', 'Pemateri baru berhasil ditambahkan.');
        } else {
            $apiErrorMsg = $resultPemateri['_json_error_data']['message'] ?? ($resultPemateri['message'] ?? ($resultPemateri['_body'] ?? 'Error API saat membuat pemateri.'));
            Log::error('[PEMATERI_STORE_API_FAIL] Gagal menyimpan pemateri.', ['request_data' => $dataPemateri, 'response' => $resultPemateri]);
            return redirect()->route('master-pemateri.create')->with('error', 'Gagal menyimpan pemateri: ' . $apiErrorMsg)->withInput();
        }
    }

    // Method destroy() tidak berubah
    // ... (method destroy() yang sudah ada) ...
    public function destroy(string $id)
    {
        Log::info("[PEMATERI_DESTROY] Memulai penghapusan untuk ID Pemateri: {$id}");
        try {
            $response = $this->apiService->deletePemateri($id);

            if ($response && !isset($response['_error']) && (isset($response['success']) && $response['success'] === true || isset($response['_success_no_content']))) {
                Log::info("[PEMATERI_DESTROY] Pemateri ID: {$id} berhasil dihapus via API.");
                return redirect()->route('master-pemateri.index')->with('success', 'Pemateri berhasil dihapus.');
            } else {
                $errorMessage = $response['_json_error_data']['message'] ?? ($response['message'] ?? ($response['_body'] ?? 'Gagal menghapus pemateri. Error tidak diketahui dari API.'));
                Log::error("[PEMATERI_DESTROY] Gagal menghapus pemateri ID: {$id} via API.", ['response' => $response]);
                return redirect()->route('master-pemateri.index')->with('error', $errorMessage);
            }
        } catch (RequestException $re) {
            Log::error('[PEMATERI_DESTROY] HTTP Request Exception: ' . $re->getMessage(), [
                'status' => $re->response ? $re->response->status() : 'N/A',
                'url' => $re->response ? $re->response->effectiveUri() : 'N/A',
            ]);
            $status = $re->response ? $re->response->status() : 'Unknown';
            $errorMessage = "Gagal menghapus pemateri. Error koneksi ke API (Status: {$status}).";
            if ($status == 404) {
                $errorMessage = "Endpoint API untuk hapus pemateri tidak ditemukan (404).";
            }
            return redirect()->route('master-pemateri.index')->with('error', $errorMessage);
        }
        catch (\Exception $e) {
            Log::error("[PEMATERI_DESTROY] Exception saat menghapus pemateri ID: {$id}", ['exception' => $e]);
            return redirect()->route('master-pemateri.index')->with('error', 'Terjadi kesalahan sistem saat menghapus pemateri.');
        }
    }
}