<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MyApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator as WebValidator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;

class PeriodeController extends Controller
{
    protected MyApiService $apiService;
    protected array $jenisRangeMapping = [
        'kunjungan' => 1,
        'pinjaman' => 2,
    ];

    protected array $jenisBobotMapping = [
        'bobot_level_satu'       => 1,
        'bobot_level_dua'        => 2,
        'bobot_level_tiga'       => 3,
        'maks_kunjungan'         => 4,
        'maks_pinjaman'          => 5,
        'maks_aksara_dinamika'   => 6,
        'maks_kegiatan'          => 7,
        'poin_aksara_dinamika'  => 8,
        'bobot_aksara_dinamika' => 9,
        'bobot_kegiatan'        => 10,
        'bobot_kunjungan'       => 11,
        'bobot_pinjaman'        => 12,
    ];

    protected array $namaJenisBobot = [
        1 => 'Skor Minimal Reward Level 1',
        2 => 'Skor Minimal Reward Level 2',
        3 => 'Skor Minimal Reward Level 3',
        4 => 'Poin Maksimum Kunjungan Harian',
        5 => 'Poin Maksimum Peminjaman Buku',
        6 => 'Poin Maksimum Aksara Dinamika (Review Buku)',
        7 => 'Poin Maksimum Partisipasi Kegiatan',
        8 => 'Poin Aksara Dinamika (Per Review)',
        9 => 'Bobot Aksara Dinamika',
        10 => 'Bobot Partisipasi Kegiatan',
        11 => 'Bobot Kunjungan Harian',
        12 => 'Bobot Peminjaman Buku',
    ];


    public function __construct(MyApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    private function isApiCallSuccessful($result, string $idKeyToCheck = 'id', string $endpointNameForLog = ''): bool
    {
        if (!$result || isset($result['_error'])) {
            Log::warning("[API_CALL_FAIL_OR_ERROR_FLAG] Endpoint: {$endpointNameForLog}", ['result' => $result]);
            return false;
        }
        if ((isset($result['success']) && $result['success'] === true) || isset($result['_success_no_content'])) {
            return true;
        }
        $dataToCheck = $result['data'] ?? $result;
        if (is_array($dataToCheck) && (isset($dataToCheck[$idKeyToCheck]) || isset($dataToCheck['id']))) {
             return true;
        }
        Log::warning("[API_CALL_NO_CLEAR_SUCCESS_INDICATOR] Endpoint: {$endpointNameForLog}", ['result' => $result]);
        return false; 
    }


    public function index(Request $request)
    {
        Log::info('[PERIODE_INDEX] Memuat daftar periode.', $request->all());
        $error = null;
        $perPage = 10; 
        
        $searchTerm = $request->input('search');
        $sortBy = $request->input('sort_by', 'tgl_mulai_desc'); // Default sorting

        try {
            // Jika API Anda mendukung parameter search dan sort_by, kirimkan di sini
            // Contoh: $apiParams = ['search' => $searchTerm, 'sort_by' => $sortBy];
            // $apiResponse = $this->apiService->getPeriodeList($apiParams); 
            // Jika tidak, ambil semua data dan filter/sort di sisi PHP
            $apiResponse = $this->apiService->getPeriodeList(); 
            
            $allPeriodes = new Collection(); 

            if ($apiResponse && !isset($apiResponse['_error'])) {
                $dataFromApi = isset($apiResponse['data']) && is_array($apiResponse['data']) ? $apiResponse['data'] : null;
                // Jika API langsung mengembalikan array item tanpa dibungkus 'data'
                if ($dataFromApi === null && is_array($apiResponse) && (empty($apiResponse) || isset($apiResponse[0]))) {
                    $dataFromApi = $apiResponse;
                }

                if (is_array($dataFromApi)) {
                    $allPeriodes = collect($dataFromApi)->map(function ($item) {
                        $periodeObj = (object) $item; 
                        // Normalisasi nama kolom (utamakan versi API, lalu fallback ke versi umum)
                        $periodeObj->ID_PERIODE = $periodeObj->ID_PERIODE ?? $periodeObj->id_periode ?? $periodeObj->id ?? null;
                        $periodeObj->NAMA_PERIODE = $periodeObj->NAMA_PERIODE ?? $periodeObj->nama_periode ?? $periodeObj->nama ?? 'Nama Tidak Tersedia';
                        $periodeObj->TGL_MULAI_ORI = $periodeObj->TGL_MULAI ?? $periodeObj->tgl_mulai ?? null; // Simpan tanggal original untuk sorting
                        $periodeObj->TGL_SELESAI_ORI = $periodeObj->TGL_SELESAI ?? $periodeObj->tgl_selesai ?? null; // Simpan tanggal original
                        
                        // Untuk tampilan, format tanggal
                        try {
                            $periodeObj->TGL_MULAI = $periodeObj->TGL_MULAI_ORI ? Carbon::parse($periodeObj->TGL_MULAI_ORI)->translatedFormat('d M Y') : 'N/A';
                            $periodeObj->TGL_SELESAI = $periodeObj->TGL_SELESAI_ORI ? Carbon::parse($periodeObj->TGL_SELESAI_ORI)->translatedFormat('d M Y') : 'N/A';
                        } catch (\Exception $e) {
                            Log::warning("[PERIODE_INDEX] Gagal parse tanggal untuk periode ID: " . $periodeObj->ID_PERIODE, ['tgl_mulai' => $periodeObj->TGL_MULAI_ORI, 'tgl_selesai' => $periodeObj->TGL_SELESAI_ORI]);
                            $periodeObj->TGL_MULAI = 'Format Salah';
                            $periodeObj->TGL_SELESAI = 'Format Salah';
                        }
                        return $periodeObj; 
                    })->filter(fn ($periode) => $periode->ID_PERIODE !== null); // Pastikan ID ada
                }
                 Log::info('[PERIODE_INDEX] Data periode mentah dari API (setelah map dan filter null ID):', $allPeriodes->toArray());
            } elseif ($apiResponse && isset($apiResponse['_error'])) {
                Log::error('[PERIODE_INDEX] API Error saat mengambil daftar periode.', $apiResponse);
                $error = $apiResponse['_json_error_data']['message'] ?? ($apiResponse['_body'] ?? 'Gagal memuat data periode dari API.');
            } else {
                Log::warning('[PERIODE_INDEX] Respons tidak valid atau kosong dari API getPeriodeList.', (array)$apiResponse);
                if(!(is_array($apiResponse) && empty($apiResponse) && !isset($apiResponse['_error']))){
                     $error = 'Tidak ada data periode atau respons API tidak valid.';
                }
            }

            // Lakukan filtering berdasarkan searchTerm jika ada
            if ($searchTerm && $allPeriodes instanceof Collection) {
                $allPeriodes = $allPeriodes->filter(function ($periode) use ($searchTerm) {
                    // Pastikan NAMA_PERIODE ada sebelum melakukan stripos
                    return isset($periode->NAMA_PERIODE) && stripos($periode->NAMA_PERIODE, $searchTerm) !== false;
                });
            }

            // Lakukan sorting jika ada data dan sortBy didefinisikan
            if ($sortBy && $allPeriodes instanceof Collection && $allPeriodes->isNotEmpty()) {
                [$sortField, $sortDirection] = explode('_', $sortBy, 2); 
                $isDescending = ($sortDirection === 'desc'); 

                $allPeriodes = $allPeriodes->sortBy(function ($periode) use ($sortField) {
                    if ($sortField === 'tgl_mulai') {
                        // Gunakan tanggal original untuk sorting yang akurat
                        return isset($periode->TGL_MULAI_ORI) ? Carbon::parse($periode->TGL_MULAI_ORI)->timestamp : null;
                    }
                    if ($sortField === 'nama') {
                        return strtolower($periode->NAMA_PERIODE ?? '');
                    }
                    // Default sort by ID jika field tidak dikenali
                    return $periode->ID_PERIODE ?? 0; 
                }, SORT_REGULAR, $isDescending)->values(); // values() untuk reset keys setelah sort
            }
             Log::info('[PERIODE_INDEX] Data periode setelah filter dan sort:', $allPeriodes->toArray());

            // Paginasi manual dari collection
            $currentPage = Paginator::resolveCurrentPage() ?: 1;
            $itemsForCurrentPage = ($allPeriodes instanceof Collection) ? $allPeriodes->slice(($currentPage - 1) * $perPage, $perPage) : new Collection();
            
            $paginatedPeriodes = new LengthAwarePaginator(
                $itemsForCurrentPage->all(), // Hanya item untuk halaman saat ini
                ($allPeriodes instanceof Collection) ? $allPeriodes->count() : 0, // Total item sebelum paginasi
                $perPage,
                $currentPage,
                ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()] // Opsi paginator
            );

        } catch (\Exception $e) {
            Log::error('[PERIODE_INDEX] Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $error = 'Terjadi kesalahan saat memuat data periode: ' . $e->getMessage();
        }
        return view('periode', ['periodes' => $paginatedPeriodes, 'error' => $error, 'searchTerm' => $searchTerm, 'sortBy' => $sortBy]);
    }

    // ... (method create, store, show tetap sama seperti sebelumnya) ...
    public function create(Request $request)
    {
        // 1. Definisikan opsi untuk dropdown prioritas sesuai permintaan
        // Kunci (key) akan menjadi nilai yang disimpan (value), dan nilai (value) akan menjadi teks yang ditampilkan.
        $prioritasOptions = [
            1 => 'Prioritas',
            2 => 'Penting',
            3 => 'Menengah',
            4 => 'Tambahan',
        ];

        // 2. Siapkan array untuk menampung label form yang akan ditampilkan
        $labelPoinKomponen = [];   // Untuk input tipe angka (e.g., Poin Maksimum)
        $labelBobotPrioritas = []; // Untuk input tipe dropdown (e.g., Bobot Prioritas)

        // Tentukan ID mana saja yang termasuk dalam kategori prioritas
        $prioritasIds = [
            $this->jenisBobotMapping['bobot_aksara_dinamika'], // ID 9
            $this->jenisBobotMapping['bobot_kegiatan'],        // ID 10
            $this->jenisBobotMapping['bobot_kunjungan'],       // ID 11
            $this->jenisBobotMapping['bobot_pinjaman'],        // ID 12
        ];

        // 3. Kelompokkan setiap jenis bobot ke dalam kategori yang sesuai
        foreach ($this->jenisBobotMapping as $key => $idBobot) {
            // Jika ID termasuk dalam daftar prioritas, masukkan ke array prioritas
            if (in_array($idBobot, $prioritasIds)) {
                $labelBobotPrioritas[$key] = $this->namaJenisBobot[$idBobot] ?? "Bobot ID {$idBobot}";
            }
            // Jika ID untuk skor minimal reward (level 1, 2, 3), lewati
            elseif ($idBobot < 4) {
                continue;
            }
            // Sisanya adalah untuk Poin Komponen (yang menggunakan input angka)
            else {
                $labelPoinKomponen[$key] = $this->namaJenisBobot[$idBobot] ?? "Jenis Bobot ID {$idBobot}";
            }
        }

        // 4. Handle fungsionalitas "Gunakan Setting Sebelumnya"
        $previousSettings = [];
        if ($request->query('use_previous') === 'true') {
            Log::info('[PERIODE_CREATE] Mencoba mengambil setting dari periode sebelumnya.');
            try {
                $apiResponse = $this->apiService->getPeriodeList();
                $allPeriodesCollection = new \Illuminate\Support\Collection();

                if ($apiResponse && !isset($apiResponse['_error'])) {
                    $dataFromApi = isset($apiResponse['data']) && is_array($apiResponse['data']) ? $apiResponse['data'] : (is_array($apiResponse) ? $apiResponse : []);
                    if (!empty($dataFromApi)) {
                        $allPeriodesCollection = collect($dataFromApi)->map(fn($item) => (object) $item);
                    }
                }

                if ($allPeriodesCollection->isNotEmpty()) {
                    $latestPeriode = $allPeriodesCollection->sortByDesc(function ($periode) {
                        $id = $periode->ID_PERIODE ?? $periode->id_periode ?? $periode->id ?? 0;
                        return is_numeric($id) ? (int)$id : 0;
                    })->first();

                    if ($latestPeriode) {
                        $latestPeriodeId = $latestPeriode->ID_PERIODE ?? $latestPeriode->id_periode ?? $latestPeriode->id;
                        Log::info("[PERIODE_CREATE] Menggunakan periode ID: {$latestPeriodeId} sebagai referensi.");
                        
                        $periodeDetails = $this->getPeriodeDetailsForForm((string)$latestPeriodeId);
                        
                        if (!empty($periodeDetails)) {
                            $previousSettings = $this->formatDataForPreviousSettingsForm($periodeDetails);
                        } else {
                            Log::warning("[PERIODE_CREATE] Tidak dapat mengambil detail untuk periode ID: {$latestPeriodeId}.");
                        }
                    } else {
                        Log::warning('[PERIODE_CREATE] Tidak ada periode sebelumnya yang ditemukan.');
                    }
                } else {
                    Log::info('[PERIODE_CREATE] Tidak ada data periode tersedia untuk dijadikan referensi.');
                }
            } catch (\Exception $e) {
                Log::error('[PERIODE_CREATE] Exception saat mengambil setting sebelumnya: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }
        }

        // 5. Kembalikan view dan kirim semua data yang diperlukan
        return view('tambah-periode', [
            'labelPoinKomponen'   => $labelPoinKomponen,
            'labelBobotPrioritas' => $labelBobotPrioritas,
            'prioritasOptions'    => $prioritasOptions,
            'previousSettings'    => $previousSettings
        ]);
    }
    
    private function getPeriodeDetailsForForm(string $periodeIdToFetch): array
    {
        $details = [
            'periode' => null, 
            'rangesKunjungan' => [], 
            'rangesPinjaman' => [], 
            'rewards' => [], 
            'pembobotans' => new Collection() // Inisialisasi sebagai Collection
        ];
        
        // 1. Ambil data periode utama
        $apiResponsePeriode = $this->apiService->getPeriodeList(); // Ambil semua, lalu filter
        if ($apiResponsePeriode && !isset($apiResponsePeriode['_error'])) {
            $allPeriodesData = isset($apiResponsePeriode['data']) && is_array($apiResponsePeriode['data']) ? $apiResponsePeriode['data'] : (is_array($apiResponsePeriode) ? $apiResponsePeriode : []);
            $foundPeriode = collect($allPeriodesData)->first(function ($item) use ($periodeIdToFetch) {
                $item = (object) $item; // Pastikan objek
                $currentId = $item->ID_PERIODE ?? $item->id_periode ?? $item->id ?? null;
                return (string)$currentId === $periodeIdToFetch;
            });
            if ($foundPeriode) {
                $details['periode'] = (object) $foundPeriode;
            }
        }

        if (!$details['periode']) {
            Log::warning("[GET_DETAILS_FORM] Periode utama dengan ID {$periodeIdToFetch} tidak ditemukan.");
            return []; // Kembalikan array kosong jika periode utama tidak ditemukan
        }

        // 2. Ambil data pembobotan terkait periode ini
        $apiResponsePembobotans = $this->apiService->getPembobotanList(['id_periode' => $periodeIdToFetch]); // Asumsi API bisa filter by id_periode
        if ($apiResponsePembobotans && !isset($apiResponsePembobotans['_error'])) {
            $pembobotansData = isset($apiResponsePembobotans['data']) && is_array($apiResponsePembobotans['data']) ? $apiResponsePembobotans['data'] : (is_array($apiResponsePembobotans) ? $apiResponsePembobotans : []);
            $details['pembobotans'] = collect($pembobotansData)->map(function($item){
                $obj = (object) $item;
                $obj->ID_JENIS_BOBOT = isset($obj->ID_JENIS_BOBOT) ? (int)($obj->ID_JENIS_BOBOT) : (isset($obj->id_jenis_bobot) ? (int)($obj->id_jenis_bobot) : null);
                $obj->NILAI = $obj->NILAI ?? $obj->nilai ?? null;
                return $obj;
            })->filter(fn($pb) => $pb->ID_JENIS_BOBOT !== null)->keyBy('ID_JENIS_BOBOT');
        } else {
            Log::warning("[GET_DETAILS_FORM] Gagal memuat data pembobotan untuk periode ID {$periodeIdToFetch}.", $apiResponsePembobotans ?? []);
        }
        
        // 3. Ambil data reward terkait periode ini
        $apiResponseRewards = $this->apiService->getRewardList(['id_periode' => $periodeIdToFetch]); 
        if ($apiResponseRewards && !isset($apiResponseRewards['_error'])) {
            $rewardsData = isset($apiResponseRewards['data']) && is_array($apiResponseRewards['data']) ? $apiResponseRewards['data'] : (is_array($apiResponseRewards) ? $apiResponseRewards : []);
            $details['rewards'] = collect($rewardsData)->map(fn($item) => (object)$item)->all();
        } else {
            Log::warning("[GET_DETAILS_FORM] Gagal memuat data reward untuk periode ID {$periodeIdToFetch}.", $apiResponseRewards ?? []);
        }

        // 4. Ambil data range (kunjungan & pinjaman) terkait periode ini
        $apiResponseRanges = $this->apiService->getRangeKunjunganList(['id_periode' => $periodeIdToFetch]); 
        if ($apiResponseRanges && !isset($apiResponseRanges['_error'])) {
            $rangesData = isset($apiResponseRanges['data']) && is_array($apiResponseRanges['data']) ? $apiResponseRanges['data'] : (is_array($apiResponseRanges) ? $apiResponseRanges : []);
            
            // Filter lagi di sini jika API tidak memfilter dengan benar
            $filteredRanges = collect($rangesData)->filter(function($rg) use ($periodeIdToFetch){
                $rgObj = (object) $rg;
                $rangePeriodeId = $rgObj->ID_PERIODE ?? $rgObj->id_periode ?? null;
                return (string)$rangePeriodeId === $periodeIdToFetch;
            });

            foreach ($filteredRanges->map(fn($item) => (object)$item) as $range) {
                $idJenisRange = $range->ID_JENIS_RANGE ?? $range->id_jenis_range ?? null;
                if ($idJenisRange == $this->jenisRangeMapping['kunjungan']) {
                    $details['rangesKunjungan'][] = $range;
                } elseif ($idJenisRange == $this->jenisRangeMapping['pinjaman']) {
                    $details['rangesPinjaman'][] = $range;
                }
            }
        } else {
             Log::warning("[GET_DETAILS_FORM] Gagal memuat data range untuk periode ID {$periodeIdToFetch}.", $apiResponseRanges ?? []);
        }
        Log::info("[GET_DETAILS_FORM] Details for periode {$periodeIdToFetch} (Ranges Kunjungan):", $details['rangesKunjungan']);
        Log::info("[GET_DETAILS_FORM] Details for periode {$periodeIdToFetch} (Ranges Pinjaman):", $details['rangesPinjaman']);
        return $details;
    }

    private function formatDataForPreviousSettingsForm(array $details): array
    {
        $settings = [];
        $periode = $details['periode'] ?? null;
        $pembobotans = $details['pembobotans'] instanceof \Illuminate\Support\Collection ? $details['pembobotans'] : new \Illuminate\Support\Collection();

        // Log data pembobotan yang diterima untuk debugging
        Log::info('[PREVIOUS_SETTINGS_FORMAT] Data Pembobotan mentah diterima:', $pembobotans->toArray());

        if ($periode) {
            $settings['nama_periode'] = $periode->NAMA_PERIODE ?? $periode->nama_periode ?? $periode->nama ?? '';
            $tglMulaiOri = $periode->TGL_MULAI ?? $periode->tgl_mulai ?? null;
            $tglSelesaiOri = $periode->TGL_SELESAI ?? $periode->tgl_selesai ?? null;
            try {
                $settings['start_date'] = $tglMulaiOri ? \Carbon\Carbon::parse($tglMulaiOri)->format('Y-m-d') : '';
                $settings['end_date'] = $tglSelesaiOri ? \Carbon\Carbon::parse($tglSelesaiOri)->format('Y-m-d') : '';
            } catch (\Exception $e) {
                $settings['start_date'] = ''; $settings['end_date'] = '';
                Log::error("Error parsing date in formatDataForPreviousSettingsForm: " . $e->getMessage());
            }
        }

        // --- (Ini tidak perlu diubah) ---
        foreach (['kunjungan', 'pinjaman'] as $type) {
            $keyStart = "{$type}_start"; $keyEnd = "{$type}_end"; $keySkor = "{$type}_skor";
            $settings[$keyStart] = []; $settings[$keyEnd] = []; $settings[$keySkor] = [];
            $ranges = ($type === 'kunjungan') ? ($details['rangesKunjungan'] ?? []) : ($details['rangesPinjaman'] ?? []);
            foreach ($ranges as $range) {
                $settings[$keyStart][] = $range->RANGE_AWAL ?? $range->range_awal ?? '';
                $settings[$keyEnd][] = $range->RANGE_AKHIR ?? $range->range_akhir ?? '';
                $settings[$keySkor][] = $range->BOBOT ?? $range->bobot ?? '';
            }
        }
        
        // --- (Ini tidak perlu diubah) ---
        $settings['rewards'] = [];
        foreach (($details['rewards'] ?? []) as $reward) {
            $reward = (object) $reward;
            $level = $reward->level_reward ?? $reward->LEVEL_REWARD ?? $reward->level ?? null;
            
            if ($level && in_array((int)$level, [1, 2, 3])) {
                $levelInt = (int)$level;
                $idJenisBobotSkorMinimal = null;
                if ($levelInt == 1) $idJenisBobotSkorMinimal = $this->jenisBobotMapping['bobot_level_satu'];
                elseif ($levelInt == 2) $idJenisBobotSkorMinimal = $this->jenisBobotMapping['bobot_level_dua'];
                elseif ($levelInt == 3) $idJenisBobotSkorMinimal = $this->jenisBobotMapping['bobot_level_tiga'];
                
                $skorMinimalEntry = $pembobotans->get($idJenisBobotSkorMinimal);
                
                $settings['rewards'][$levelInt] = [
                    'nama_reward' => $reward->bentuk_reward ?? $reward->BENTUK_REWARD ?? $reward->bentuk ?? '',
                    'slot_tersedia' => $reward->slot_reward ?? $reward->SLOT_REWARD ?? $reward->slot ?? '',
                    // Memastikan nilai skor minimal adalah numerik
                    'skor_minimal' => $skorMinimalEntry ? ($skorMinimalEntry->NILAI ?? $skorMinimalEntry->nilai ?? '') : ''
                ];
            }
        }
        for ($i=1; $i<=3; $i++) { 
            if (!isset($settings['rewards'][$i])) { 
                $settings['rewards'][$i] = ['nama_reward' => '', 'slot_tersedia' => '', 'skor_minimal' => '']; 
            } 
        }
        
        // ### BAGIAN YANG DIPERBAIKI ###
        $settings['poin_komponen'] = [];
        foreach ($this->jenisBobotMapping as $formKey => $idBobot) {
            // Hanya proses bobot untuk komponen (ID >= 4)
            if ($idBobot >= 4) {
                $pembobotanEntry = $pembobotans->get($idBobot);
                
                // Ambil nilai dan pastikan itu numerik jika ada.
                // Jika tidak ada entry, defaultnya adalah string kosong ''.
                $nilai = $pembobotanEntry ? ($pembobotanEntry->NILAI ?? $pembobotanEntry->nilai ?? '') : '';

                // Simpan nilai yang sudah diekstrak.
                $settings['poin_komponen'][$formKey] = $nilai;

                // Log untuk setiap komponen agar mudah dilacak
                Log::info("[PREVIOUS_SETTINGS_FORMAT] Processing '{$formKey}' (ID: {$idBobot}). Found value: '{$nilai}'");
            }
        }

        Log::info('[PERIODE_CREATE] Final previous settings formatted for form:', $settings);
        return $settings;
    }


    public function store(Request $request)
    {
        Log::info('[PERIODE_STORE_START]', $request->all());
        $poinKomponenValidationRules = [];
        // Ambil kunci dari $jenisBobotMapping yang ID-nya >= 4
        $komponenBobotKeysUntukValidasi = array_keys(array_filter($this->jenisBobotMapping, fn($id) => $id >= 4)); 
        foreach ($komponenBobotKeysUntukValidasi as $key) {
            $poinKomponenValidationRules['poin_komponen.' . $key] = 'required|numeric|min:0';
        }

        $validator = WebValidator::make($request->all(), array_merge([
            'nama_periode' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'kunjungan_start' => 'nullable|array|max:10',
            'pinjaman_start' => 'nullable|array|max:10',
            'kunjungan_start.*' => 'required_with:kunjungan_end.*,kunjungan_skor.*|nullable|numeric|min:0',
            'kunjungan_end.*' => 'required_with:kunjungan_start.*,kunjungan_skor.*|nullable|numeric|gte:kunjungan_start.*',
            'kunjungan_skor.*' => 'required_with:kunjungan_start.*,kunjungan_end.*|nullable|numeric|min:0',
            'pinjaman_start.*' => 'required_with:pinjaman_end.*,pinjaman_skor.*|nullable|numeric|min:0',
            'pinjaman_end.*' => 'required_with:pinjaman_start.*,pinjaman_skor.*|nullable|numeric|gte:pinjaman_start.*',
            'pinjaman_skor.*' => 'required_with:pinjaman_start.*,pinjaman_end.*|nullable|numeric|min:0',
            'rewards.1.skor_minimal' => 'required|numeric|min:0', // Validasi skor minimal untuk setiap level reward
            'rewards.2.skor_minimal' => 'required|numeric|min:0',
            'rewards.3.skor_minimal' => 'required|numeric|min:0',
            'rewards.*.nama_reward' => 'required|string|max:50',
            'rewards.*.slot_tersedia' => 'required|numeric|min:0',
        ], $poinKomponenValidationRules));

        if ($validator->fails()) {
            return redirect()->route('periode.create')->withErrors($validator)->withInput();
        }

        $errors = new MessageBag();
        $createdPeriodeId = null; 

        // 1. Simpan Periode Utama
        $nextPeriodeId = $this->apiService->getNextId('periode', $this->apiService->getPrimaryKeyName('periode_award'));
        if ($nextPeriodeId === null) {
            return redirect()->route('periode.create')->with('error', 'Gagal men-generate ID untuk Periode baru. Coba lagi.')->withInput();
        }
        $dataPeriode = [
            'id' => $nextPeriodeId, // Atau 'ID_PERIODE' sesuai API
            'nama' => $request->input('nama_periode'), // Atau 'NAMA_PERIODE'
            'tgl_mulai' => $request->input('start_date'), // Atau 'TGL_MULAI'
            'tgl_selesai' => $request->input('end_date') // Atau 'TGL_SELESAI'
        ];
        $resultPeriode = $this->apiService->createPeriode($dataPeriode);
        if (!$this->isApiCallSuccessful($resultPeriode, $this->apiService->getPrimaryKeyName('periode_award'), 'createPeriode')) {
            return redirect()->route('periode.create')->with('error', 'Gagal menyimpan data periode utama: ' . ($resultPeriode['_json_error_data']['message'] ?? ($resultPeriode['message'] ?? ($resultPeriode['_body'] ?? 'Error API tidak diketahui'))))->withInput();
        }
        $createdPeriodeId = $nextPeriodeId; // Gunakan ID yang di-generate
        Log::info('[PERIODE_STORE_API_SUCCESS] Periode utama berhasil disimpan dengan ID: ' . $createdPeriodeId);

        // 2. Simpan Range Kunjungan dan Pinjaman
        foreach (['kunjungan', 'pinjaman'] as $type) {
            if ($request->input("{$type}_start")) {
                foreach ($request->input("{$type}_start") as $index => $start) {
                    // Hanya proses jika semua field untuk range ini ada dan start adalah numerik
                    if (!is_numeric($start) || !isset($request->input("{$type}_end")[$index]) || !isset($request->input("{$type}_skor")[$index])) {
                        continue;
                    }
                    $nextRangeId = $this->apiService->getNextId('range-kunjungan', $this->apiService->getPrimaryKeyName('rangekunjungan_award'));
                    if ($nextRangeId === null) { $errors->add("range_{$type}", "Gagal men-generate ID untuk range {$type} ".($index+1)."."); continue; }
                    
                    $dataRange = [
                        'id' => $nextRangeId, // Atau 'ID_RANGE_KUNJUNGAN'
                        'id_jenis_range' => $this->jenisRangeMapping[$type],
                        'id_periode' => $createdPeriodeId,
                        'range_awal' => $start,
                        'range_akhir' => $request->input("{$type}_end")[$index],
                        'bobot' => $request->input("{$type}_skor")[$index]
                    ];
                    $resultRange = $this->apiService->createRangeKunjungan($dataRange);
                    if (!$this->isApiCallSuccessful($resultRange, $this->apiService->getPrimaryKeyName('rangekunjungan_award'), 'createRangeKunjungan')) {
                        $errors->add("range_{$type}", "Gagal menyimpan range {$type} ".($index+1).": " . ($resultRange['_json_error_data']['message'] ?? ($resultRange['message'] ?? ($resultRange['_body'] ?? 'Error API tidak diketahui'))));
                    }
                }
            }
        }

        // 3. Simpan Reward dan Skor Minimal Reward (sebagai Pembobotan)
        if ($request->input('rewards')) {
            foreach ($request->input('rewards') as $level => $rewardData) {
                $nextRewardId = $this->apiService->getNextId('reward', $this->apiService->getPrimaryKeyName('reward_award'));
                if ($nextRewardId === null) { $errors->add('reward', "Gagal men-generate ID untuk reward level {$level}."); continue; }

                $dataReward = [
                    'id' => $nextRewardId, // Atau 'ID_REWARD'
                    'idperiode' => $createdPeriodeId, // Atau 'ID_PERIODE'
                    'level' => $level, // Atau 'LEVEL_REWARD'
                    'bentuk' => $rewardData['nama_reward'], // Atau 'BENTUK_REWARD'
                    'slot' => $rewardData['slot_tersedia'] // Atau 'SLOT_REWARD'
                ];
                $resultReward = $this->apiService->createReward($dataReward);
                if (!$this->isApiCallSuccessful($resultReward, $this->apiService->getPrimaryKeyName('reward_award'), 'createReward')) {
                    $errors->add('reward', "Gagal menyimpan reward untuk level {$level}.");
                    continue; // Lanjut ke level berikutnya jika reward gagal disimpan
                }

                // Simpan skor minimal sebagai pembobotan
                $idJenisBobotForLevel = null;
                if ($level == 1) $idJenisBobotForLevel = $this->jenisBobotMapping['bobot_level_satu'];
                elseif ($level == 2) $idJenisBobotForLevel = $this->jenisBobotMapping['bobot_level_dua'];
                elseif ($level == 3) $idJenisBobotForLevel = $this->jenisBobotMapping['bobot_level_tiga'];

                if ($idJenisBobotForLevel && isset($rewardData['skor_minimal'])) {
                    $nextPembobotanIdSkor = $this->apiService->getNextId('pembobotan', $this->apiService->getPrimaryKeyName('pembobotan_award'));
                    if ($nextPembobotanIdSkor === null) { $errors->add('pembobotan_level_'.$level, "Gagal men-generate ID untuk pembobotan skor minimal Level {$level}."); continue; }

                    $dataPembobotanSkorMinimal = [
                        'id' => $nextPembobotanIdSkor, // Atau 'ID_PEMBOBOTAN'
                        'id_periode' => $createdPeriodeId,
                        'id_jenis_bobot' => $idJenisBobotForLevel,
                        'nilai' => $rewardData['skor_minimal']
                    ];
                    $resultPembobotanSkorMinimal = $this->apiService->createPembobotan($dataPembobotanSkorMinimal);
                    if (!$this->isApiCallSuccessful($resultPembobotanSkorMinimal, 'id', 'createPembobotanSkorMinimal')) {
                        $errors->add('pembobotan_level_'.$level, "Gagal menyimpan skor minimal untuk Level {$level}.");
                    }
                }
            }
        }

        // 4. Simpan Poin/Bobot Komponen Lainnya
        $inputPoinKomponen = $request->input('poin_komponen'); 
        if ($inputPoinKomponen) { 
            foreach ($inputPoinKomponen as $key => $nilai) {
                // Pastikan kunci ada di mapping dan ID Bobot >= 4 (untuk komponen, bukan skor minimal reward)
                if (!isset($this->jenisBobotMapping[$key])) {
                    Log::warning("[PERIODE_STORE_WARNING] Kunci jenis bobot tidak dikenal dari form poin_komponen: {$key}");
                    continue;
                }
                $idJenisBobot = $this->jenisBobotMapping[$key];
                if ($idJenisBobot < 4) continue; // Skip jika ini ID untuk skor minimal reward (sudah dihandle di atas)

                $nextPembobotanIdKomponen = $this->apiService->getNextId('pembobotan', $this->apiService->getPrimaryKeyName('pembobotan_award'));
                 if ($nextPembobotanIdKomponen === null) { $errors->add('pembobotan_komponen', "Gagal men-generate ID untuk pembobotan '{$this->namaJenisBobot[$idJenisBobot]}'."); continue; }
                
                $dataPembobotan = [
                    'id' => $nextPembobotanIdKomponen, // Atau 'ID_PEMBOBOTAN'
                    'id_periode' => $createdPeriodeId,
                    'id_jenis_bobot' => $idJenisBobot,
                    'nilai' => $nilai
                ];
                $resultPembobotan = $this->apiService->createPembobotan($dataPembobotan);
                if (!$this->isApiCallSuccessful($resultPembobotan, 'id', 'createPembobotanKomponen')) {
                    $errors->add('pembobotan_komponen', "Gagal menyimpan pembobotan untuk '{$this->namaJenisBobot[$idJenisBobot]}'.");
                }
            }
        }

        if ($errors->isNotEmpty()) {
            // Jika ada error, kembalikan dengan pesan error dan input lama
            // Mungkin juga perlu menghapus record periode utama yang sudah terbuat jika proses selanjutnya banyak yang gagal (transaksional)
            // Untuk saat ini, kita kembalikan error saja
            return redirect()->route('periode.create')->with('error', 'Beberapa bagian gagal disimpan. Silakan periksa detail error.')->withErrors($errors)->withInput();
        }

        return redirect()->route('periode.index')->with('success', 'Pengaturan periode baru berhasil disimpan.');
    }


    public function show(Request $request, $id) 
    {
        Log::info("[PERIODE_SHOW] Memuat detail untuk periode ID: {$id}");
        $periode = null;
        $rangesKunjungan = [];
        $rangesPinjaman = [];
        $rewards = []; // Akan berisi objek reward dengan skor minimalnya
        $allPembobotansForView = []; // Untuk menampilkan semua jenis bobot dan nilainya
        $error = null;

        try {
            // 1. Ambil data periode utama
            $apiResponsePeriode = $this->apiService->getPeriodeList(); // Ambil semua, lalu filter
            if ($apiResponsePeriode && !isset($apiResponsePeriode['_error'])) {
                $allPeriodes = isset($apiResponsePeriode['data']) && is_array($apiResponsePeriode['data']) ? $apiResponsePeriode['data'] : (is_array($apiResponsePeriode) ? $apiResponsePeriode : []);
                $foundPeriode = collect($allPeriodes)->first(function ($item) use ($id) {
                    $item = (object) $item; // Pastikan objek
                    // Normalisasi pengecekan ID
                    $periodeIdApi = $item->ID_PERIODE ?? $item->id_periode ?? $item->id ?? null;
                    return (string)$periodeIdApi === (string)$id;
                });

                if ($foundPeriode) {
                    $periode = (object) $foundPeriode;
                } else {
                    $error = "Periode dengan ID {$id} tidak ditemukan.";
                }
            } else {
                $error = $apiResponsePeriode['_json_error_data']['message'] ?? ($apiResponsePeriode['_body'] ?? 'Gagal memuat data periode.');
            }

            if ($periode) { // Lanjutkan hanya jika periode ditemukan
                // 2. Ambil semua data pembobotan untuk periode ini
                $pembobotansFromApi = new Collection();
                $apiResponsePembobotans = $this->apiService->getPembobotanList(['id_periode' => $id]); // Asumsi API bisa filter by id_periode
                if ($apiResponsePembobotans && !isset($apiResponsePembobotans['_error'])) {
                    $pembobotansDataFromApi = isset($apiResponsePembobotans['data']) && is_array($apiResponsePembobotans['data']) ? $apiResponsePembobotans['data'] : (is_array($apiResponsePembobotans) ? $apiResponsePembobotans : []);
                    $pembobotansFromApi = collect($pembobotansDataFromApi)->map(function($item){
                        $obj = (object) $item;
                        // Normalisasi nama kolom
                        $obj->ID_JENIS_BOBOT = isset($obj->ID_JENIS_BOBOT) ? (int)($obj->ID_JENIS_BOBOT) : (isset($obj->id_jenis_bobot) ? (int)($obj->id_jenis_bobot) : null);
                        $obj->NILAI = $obj->NILAI ?? $obj->nilai ?? null;
                        return $obj;
                    })->filter(fn($pb) => $pb->ID_JENIS_BOBOT !== null)->keyBy('ID_JENIS_BOBOT'); // Key by ID_JENIS_BOBOT untuk lookup mudah
                } else {
                    Log::warning("[PERIODE_SHOW] Gagal memuat data pembobotan untuk periode ID {$id}.", $apiResponsePembobotans ?? []);
                }
                
                // Siapkan $allPembobotansForView dengan semua jenis bobot
                foreach ($this->namaJenisBobot as $idBobot => $namaDeskriptif) {
                    $pembobotanEntry = $pembobotansFromApi->get($idBobot); // Cari berdasarkan ID_JENIS_BOBOT
                    $allPembobotansForView[$idBobot] = (object)[
                        'id_jenis_bobot' => $idBobot,
                        'nama_jenis_bobot' => $namaDeskriptif,
                        'nilai' => $pembobotanEntry ? $pembobotanEntry->NILAI : 'N/A' // Ambil nilai jika ada
                    ];
                }
                Log::info("[PERIODE_SHOW] Data Pembobotan (untuk view) periode ID {$id}:", $allPembobotansForView);


                // 3. Ambil data reward untuk periode ini
                $allRewardsFromApi = [];
                $apiResponseAllRewards = $this->apiService->getRewardList(); // Ambil semua, lalu filter di PHP
                if ($apiResponseAllRewards && !isset($apiResponseAllRewards['_error'])) {
                     $dataRewardsFromApi = isset($apiResponseAllRewards['data']) && is_array($apiResponseAllRewards['data']) ? $apiResponseAllRewards['data'] : (is_array($apiResponseAllRewards) ? $apiResponseAllRewards : []);
                     $allRewardsFromApi = $dataRewardsFromApi;
                } else {
                    Log::warning("[PERIODE_SHOW] Gagal memuat daftar semua reward.", $apiResponseAllRewards ?? []);
                }
                
                $rewardsForCurrentPeriode = collect($allRewardsFromApi)->filter(function($r) use ($id) {
                    $rewardItem = (object) $r;
                    // Normalisasi nama kolom ID Periode di reward
                    $rewardPeriodeId = $rewardItem->ID_PERIODE ?? $rewardItem->id_periode ?? $rewardItem->idperiode ?? null; 
                    return (string)$rewardPeriodeId === (string)$id;
                });

                // Gabungkan data reward dengan skor minimalnya dari pembobotan
                foreach($rewardsForCurrentPeriode as $r) {
                     $rewardObj = (object) $r;
                     // Normalisasi nama kolom level, bentuk, slot
                     $rewardObj->processed_level = $rewardObj->level_reward ?? $rewardObj->LEVEL_REWARD ?? $rewardObj->level ?? null;
                     $rewardObj->processed_bentuk = $rewardObj->bentuk_reward ?? $rewardObj->BENTUK_REWARD ?? $rewardObj->bentuk ?? 'N/A';
                     $rewardObj->processed_slot = $rewardObj->slot_reward ?? $rewardObj->SLOT_REWARD ?? $rewardObj->slot ?? 'N/A';
                     
                     // Ambil skor minimal dari $allPembobotansForView yang sudah disiapkan
                     $idJenisBobotSkorMinimal = null;
                     if ($rewardObj->processed_level == 1) $idJenisBobotSkorMinimal = $this->jenisBobotMapping['bobot_level_satu'];
                     elseif ($rewardObj->processed_level == 2) $idJenisBobotSkorMinimal = $this->jenisBobotMapping['bobot_level_dua'];
                     elseif ($rewardObj->processed_level == 3) $idJenisBobotSkorMinimal = $this->jenisBobotMapping['bobot_level_tiga'];
                     
                     $rewardObj->skor_minimal = ($idJenisBobotSkorMinimal && isset($allPembobotansForView[$idJenisBobotSkorMinimal]))
                                                ? $allPembobotansForView[$idJenisBobotSkorMinimal]->nilai
                                                : 'N/A';
                     $rewards[] = $rewardObj;
                 }
                
                // 4. Ambil data range untuk periode ini
                $allRangesFromApi = [];
                $apiResponseAllRanges = $this->apiService->getRangeKunjunganList(); // Ambil semua, lalu filter di PHP
                if ($apiResponseAllRanges && !isset($apiResponseAllRanges['_error'])) {
                    $dataRangesFromApi = isset($apiResponseAllRanges['data']) && is_array($apiResponseAllRanges['data']) ? $apiResponseAllRanges['data'] : (is_array($apiResponseAllRanges) ? $apiResponseAllRanges : []);
                    $allRangesFromApi = $dataRangesFromApi;
                } else {
                    Log::warning("[PERIODE_SHOW] Gagal memuat daftar semua range kunjungan/pinjaman.", $apiResponseAllRanges ?? []);
                }
                
                $filteredRanges = collect($allRangesFromApi)->filter(function($range) use ($id) {
                    $rangeObj = (object) $range;
                    // Normalisasi nama kolom ID Periode di range
                    $rangePeriodeId = $rangeObj->ID_PERIODE ?? $rangeObj->id_periode ?? null;
                    return (string)$rangePeriodeId === (string)$id;
                });

                foreach ($filteredRanges as $range) {
                    $rangeObj = (object) $range;
                    // Normalisasi nama kolom ID Jenis Range
                    $idJenisRange = $rangeObj->ID_JENIS_RANGE ?? $rangeObj->id_jenis_range ?? null;
                    if ($idJenisRange == $this->jenisRangeMapping['kunjungan']) {
                        $rangesKunjungan[] = $rangeObj;
                    } elseif ($idJenisRange == $this->jenisRangeMapping['pinjaman']) {
                        $rangesPinjaman[] = $rangeObj;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("[PERIODE_SHOW] Exception untuk periode ID {$id}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $error = 'Terjadi kesalahan sistem saat memuat detail periode.';
            if (!$periode && $error) { // Jika periode tidak ditemukan dan ada error sistem
                // Anda bisa memilih untuk mengembalikan view error atau redirect
                // return redirect()->route('periode.index')->with('error', $error);
                // atau set $periode menjadi null agar view menampilkan pesan 'tidak ditemukan'
                 $periode = null;
            }
        }

        if (!$periode && !$error) { // Jika periode tidak ditemukan setelah semua proses dan tidak ada error API sebelumnya
            $error = "Periode dengan ID {$id} tidak dapat ditemukan.";
        }
        
        return view('detailperiode', [ 
            'periode' => $periode,
            'rangesKunjungan' => $rangesKunjungan,
            'rangesPinjaman' => $rangesPinjaman,
            'rewards' => $rewards, 
            'allPembobotansForView' => $allPembobotansForView, // Kirim ini ke view
            'namaJenisBobotFromController' => $this->namaJenisBobot, // Untuk referensi nama di view jika perlu
            'error' => $error,
        ]);
    }

    public function dropdown()
    {
        $response = $this->apiService->getPeriodeList();

        if (isset($response['_error'])) {
            // Jika terjadi error saat mengambil data dari API, kirim response error
            return response()->json(['error' => 'Gagal mengambil data periode dari API.'], 500);
        }

        // Jika berhasil, kirim data periode sebagai JSON
        return response()->json($response);
    }
}