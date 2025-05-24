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
        'kunjungan' => 1, // Asumsi ID 1 untuk Kunjungan Harian di tabel JENISRANGE_AWARD
        'pinjaman' => 2,  // Asumsi ID 2 untuk Peminjaman Buku di tabel JENISRANGE_AWARD
    ];

    // Kunci di sini harus konsisten dengan yang digunakan di form dan untuk validasi
    protected array $jenisBobotMapping = [
        'bobot_level_satu'       => 1, // ID_JENIS_BOBOT = 1 (Untuk Skor Minimal Reward Level 1)
        'bobot_level_dua'        => 2, // ID_JENIS_BOBOT = 2 (Untuk Skor Minimal Reward Level 2)
        'bobot_level_tiga'       => 3, // ID_JENIS_BOBOT = 3 (Untuk Skor Minimal Reward Level 3)
        'maks_kunjungan'         => 4, // ID_JENIS_BOBOT = 4
        'maks_pinjaman'          => 5, // ID_JENIS_BOBOT = 5
        'maks_aksara_dinamika'   => 6, // ID_JENIS_BOBOT = 6
        'maks_kegiatan'          => 7, // ID_JENIS_BOBOT = 7
        'bobot_aksara_dinamika'  => 8, // ID_JENIS_BOBOT = 8
    ];

    // Ini adalah array yang akan kita kirim ke view
    protected array $namaJenisBobot = [
        1 => 'Skor Minimal Reward Level 1',
        2 => 'Skor Minimal Reward Level 2',
        3 => 'Skor Minimal Reward Level 3',
        4 => 'Poin Maksimum Kunjungan Harian',
        5 => 'Poin Maksimum Peminjaman Buku',
        6 => 'Poin Maksimum Aksara Dinamika (Review Buku)',
        7 => 'Poin Maksimum Partisipasi Kegiatan',
        8 => 'Bobot Poin Aksara Dinamika (Per Review)',
    ];


    public function __construct(MyApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Helper function to check API call success.
     */
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
        $paginatedPeriodes = new LengthAwarePaginator(new Collection(), 0, $perPage, 1, [
            'path' => Paginator::resolveCurrentPath(), 'query' => $request->query(),
        ]);
        $searchTerm = $request->input('search');
        $sortBy = $request->input('sort_by', 'tgl_mulai_desc'); 
        try {
            $apiResponse = $this->apiService->getPeriodeList(); 
            $allPeriodes = new Collection(); 
            if ($apiResponse && !isset($apiResponse['_error'])) {
                $dataFromApi = isset($apiResponse['data']) && is_array($apiResponse['data']) ? $apiResponse['data'] : null;
                if ($dataFromApi === null && is_array($apiResponse)) { $dataFromApi = $apiResponse; }
                if (is_array($dataFromApi)) {
                    $allPeriodes = collect($dataFromApi)->map(function ($item) {
                        $periodeObj = (object) $item; 
                        $periodeObj->ID_PERIODE = $periodeObj->ID_PERIODE ?? $periodeObj->id_periode ?? $periodeObj->id ?? null;
                        $periodeObj->NAMA_PERIODE = $periodeObj->NAMA_PERIODE ?? $periodeObj->nama_periode ?? $periodeObj->nama ?? 'Nama Tidak Tersedia';
                        $periodeObj->TGL_MULAI = $periodeObj->TGL_MULAI ?? $periodeObj->tgl_mulai ?? null;
                        $periodeObj->TGL_SELESAI = $periodeObj->TGL_SELESAI ?? $periodeObj->tgl_selesai ?? null;
                        return $periodeObj; 
                    })->filter(fn ($periode) => $periode->ID_PERIODE !== null);
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

            if ($searchTerm && $allPeriodes instanceof Collection) {
                $allPeriodes = $allPeriodes->filter(fn ($periode) => isset($periode->NAMA_PERIODE) && stripos($periode->NAMA_PERIODE, $searchTerm) !== false);
            }

            if ($sortBy && $allPeriodes instanceof Collection && $allPeriodes->isNotEmpty()) {
                [$sortField, $sortDirection] = explode('_', $sortBy, 2); 
                $isDescending = ($sortDirection === 'desc'); 

                $allPeriodes = $allPeriodes->sortBy(function ($periode) use ($sortField) {
                    if ($sortField === 'tgl_mulai') { try { return isset($periode->TGL_MULAI) ? Carbon::parse($periode->TGL_MULAI) : null; } catch (\Exception $e) { Log::warning("[PERIODE_INDEX] Gagal parse tanggal untuk sorting: " . ($periode->TGL_MULAI ?? 'NULL'), ['id' => ($periode->ID_PERIODE ?? 'N/A')]); return null; }}
                    if ($sortField === 'nama') { return strtolower($periode->NAMA_PERIODE ?? ''); }
                    return $periode->ID_PERIODE ?? 0; 
                }, SORT_REGULAR, $isDescending)->values(); 
            }
             Log::info('[PERIODE_INDEX] Data periode setelah filter dan sort:', $allPeriodes->toArray());

            $currentPage = Paginator::resolveCurrentPage() ?: 1;
            $itemsForCurrentPage = ($allPeriodes instanceof Collection) ? $allPeriodes->slice(($currentPage - 1) * $perPage, $perPage) : new Collection();
            $paginatedPeriodes = new LengthAwarePaginator( $itemsForCurrentPage->all(), ($allPeriodes instanceof Collection) ? $allPeriodes->count() : 0, $perPage, $currentPage,
                ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()]
            );
        } catch (\Exception $e) {
            Log::error('[PERIODE_INDEX] Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $error = 'Terjadi kesalahan saat memuat data periode: ' . $e->getMessage();
        }
        return view('periode', ['periodes' => $paginatedPeriodes, 'error' => $error]);
    }

    public function create(Request $request)
    {
        $labelPoinKomponen = [];
        $komponenBobotIdsUntukForm = [4, 5, 6, 7, 8];
        foreach ($this->jenisBobotMapping as $key => $idBobot) {
            if (in_array($idBobot, $komponenBobotIdsUntukForm)) {
                $labelPoinKomponen[$key] = $this->namaJenisBobot[$idBobot] ?? "Jenis Bobot ID {$idBobot}";
            }
        }
        $previousSettings = [];
        if ($request->query('use_previous') === 'true') { 
            Log::info('[PERIODE_CREATE] Mencoba mengambil setting dari periode sebelumnya.');
            try {
                $apiResponse = $this->apiService->getPeriodeList();
                $allPeriodesCollection = new Collection();
                if ($apiResponse && !isset($apiResponse['_error'])) {
                    $dataFromApi = isset($apiResponse['data']) && is_array($apiResponse['data']) ? $apiResponse['data'] : $apiResponse;
                    if(is_array($dataFromApi)) {
                        $allPeriodesCollection = collect($dataFromApi)->map(fn($item) => (object) $item);
                    }
                }
                if ($allPeriodesCollection->isNotEmpty()) {
                    $latestPeriode = $allPeriodesCollection->sortByDesc(function($periode) {
                        return (int)($periode->ID_PERIODE ?? $periode->id_periode ?? $periode->id ?? 0);
                    })->first();
                    if ($latestPeriode) {
                        $latestPeriodeId = $latestPeriode->ID_PERIODE ?? $latestPeriode->id_periode ?? $latestPeriode->id;
                        Log::info("[PERIODE_CREATE] Menggunakan periode ID: {$latestPeriodeId} sebagai referensi (ID terbaru).");
                        $periodeDetails = $this->getPeriodeDetailsForForm((string)$latestPeriodeId); 
                        if (!empty($periodeDetails)) { $previousSettings = $this->formatDataForPreviousSettingsForm($periodeDetails); }
                    } else { Log::warning('[PERIODE_CREATE] Tidak ada periode sebelumnya yang ditemukan.'); }
                }
            } catch (\Exception $e) { Log::error('[PERIODE_CREATE] Exception saat mengambil setting sebelumnya: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]); }
        }
        return view('tambah-periode', ['labelPoinKomponen' => $labelPoinKomponen, 'previousSettings' => $previousSettings ]);
    }
    
    private function getPeriodeDetailsForForm(string $periodeIdToFetch): array
    {
        $details = ['periode' => null, 'rangesKunjungan' => [], 'rangesPinjaman' => [], 'rewards' => [], 'pembobotans' => new Collection()];
        
        $apiResponsePeriode = $this->apiService->getPeriodeList();
        if ($apiResponsePeriode && !isset($apiResponsePeriode['_error'])) {
            $allPeriodes = isset($apiResponsePeriode['data']) && is_array($apiResponsePeriode['data']) ? $apiResponsePeriode['data'] : $apiResponsePeriode;
            $foundPeriode = collect($allPeriodes)->first(function ($item) use ($periodeIdToFetch) {
                $item = (object) $item; 
                $currentId = $item->ID_PERIODE ?? $item->id_periode ?? $item->id ?? null;
                return (string)$currentId === $periodeIdToFetch;
            });
            if ($foundPeriode) $details['periode'] = (object) $foundPeriode;
        }
        if (!$details['periode']) {
            Log::warning("[GET_DETAILS_FORM] Periode utama dengan ID {$periodeIdToFetch} tidak ditemukan.");
            return []; 
        }

        $apiResponsePembobotans = $this->apiService->getPembobotanList(['id_periode' => $periodeIdToFetch]);
        if ($apiResponsePembobotans && !isset($apiResponsePembobotans['_error'])) {
            $pembobotansData = isset($apiResponsePembobotans['data']) && is_array($apiResponsePembobotans['data']) ? $apiResponsePembobotans['data'] : $apiResponsePembobotans;
            $details['pembobotans'] = collect($pembobotansData)->map(function($item){
                $obj = (object) $item;
                $obj->ID_JENIS_BOBOT = isset($obj->ID_JENIS_BOBOT) ? (int)($obj->ID_JENIS_BOBOT) : (isset($obj->id_jenis_bobot) ? (int)($obj->id_jenis_bobot) : null);
                $obj->NILAI = $obj->NILAI ?? $obj->nilai ?? null;
                return $obj;
            })->filter(fn($pb) => $pb->ID_JENIS_BOBOT !== null)->keyBy('ID_JENIS_BOBOT');
        }
        
        $apiResponseRewards = $this->apiService->getRewardList(['id_periode' => $periodeIdToFetch]); 
        if ($apiResponseRewards && !isset($apiResponseRewards['_error'])) {
            $rewardsData = isset($apiResponseRewards['data']) && is_array($apiResponseRewards['data']) ? $apiResponseRewards['data'] : $apiResponseRewards;
            $details['rewards'] = collect($rewardsData)->map(fn($item) => (object)$item)->all();
        }

        $apiResponseRanges = $this->apiService->getRangeKunjunganList(['id_periode' => $periodeIdToFetch]); 
        if ($apiResponseRanges && !isset($apiResponseRanges['_error'])) {
            $rangesData = isset($apiResponseRanges['data']) && is_array($apiResponseRanges['data']) ? $apiResponseRanges['data'] : $apiResponseRanges;
            $filteredRanges = collect($rangesData)->filter(function($rg) use ($periodeIdToFetch){
                $rgObj = (object) $rg;
                $rangePeriodeId = $rgObj->ID_PERIODE ?? $rgObj->id_periode ?? null;
                return (string)$rangePeriodeId === $periodeIdToFetch;
            });
            foreach ($filteredRanges->map(fn($item) => (object)$item) as $range) {
                $idJenisRange = $range->ID_JENIS_RANGE ?? $range->id_jenis_range ?? null;
                if ($idJenisRange == $this->jenisRangeMapping['kunjungan']) $details['rangesKunjungan'][] = $range;
                elseif ($idJenisRange == $this->jenisRangeMapping['pinjaman']) $details['rangesPinjaman'][] = $range;
            }
        }
        Log::info("[GET_DETAILS_FORM] Details for periode {$periodeIdToFetch} (Ranges Kunjungan):", $details['rangesKunjungan']);
        Log::info("[GET_DETAILS_FORM] Details for periode {$periodeIdToFetch} (Ranges Pinjaman):", $details['rangesPinjaman']);
        return $details;
    }

    private function formatDataForPreviousSettingsForm(array $details): array
    {
        $settings = []; $periode = $details['periode']; $pembobotans = $details['pembobotans']; 
        Log::info('[PREVIOUS_SETTINGS_FORMAT] Data Pembobotan diterima:', $pembobotans->toArray());
        if ($periode) {
            $settings['nama_periode'] = $periode->NAMA_PERIODE ?? $periode->nama_periode ?? $periode->nama ?? '';
            $settings['start_date'] = isset($periode->TGL_MULAI) ? Carbon::parse($periode->TGL_MULAI)->format('Y-m-d') : (isset($periode->tgl_mulai) ? Carbon::parse($periode->tgl_mulai)->format('Y-m-d') : '');
            $settings['end_date'] = isset($periode->TGL_SELESAI) ? Carbon::parse($periode->TGL_SELESAI)->format('Y-m-d') : (isset($periode->tgl_selesai) ? Carbon::parse($periode->tgl_selesai)->format('Y-m-d') : '');
        }
        foreach (['kunjungan', 'pinjaman'] as $type) {
            $keyStart = "{$type}_start"; $keyEnd = "{$type}_end"; $keySkor = "{$type}_skor";
            $settings[$keyStart] = []; $settings[$keyEnd] = []; $settings[$keySkor] = [];
            $ranges = ($type === 'kunjungan') ? $details['rangesKunjungan'] : $details['rangesPinjaman'];
            foreach ($ranges as $range) {
                $settings[$keyStart][] = $range->RANGE_AWAL ?? $range->range_awal ?? '';
                $settings[$keyEnd][] = $range->RANGE_AKHIR ?? $range->range_akhir ?? '';
                $settings[$keySkor][] = $range->BOBOT ?? $range->bobot ?? '';
            }
        }
        $settings['rewards'] = [];
        Log::info('[PREVIOUS_SETTINGS_FORMAT] Data Rewards diterima:', $details['rewards']);
        foreach ($details['rewards'] as $reward) {
            $level = $reward->level_reward ?? $reward->LEVEL_REWARD ?? $reward->level ?? null;
            if ($level && in_array($level, [1, 2, 3])) {
                $idJenisBobotSkorMinimal = null;
                if ($level == 1) $idJenisBobotSkorMinimal = $this->jenisBobotMapping['bobot_level_satu'];
                elseif ($level == 2) $idJenisBobotSkorMinimal = $this->jenisBobotMapping['bobot_level_dua'];
                elseif ($level == 3) $idJenisBobotSkorMinimal = $this->jenisBobotMapping['bobot_level_tiga'];
                $skorMinimalEntry = $pembobotans->get($idJenisBobotSkorMinimal);
                Log::info("[PREVIOUS_SETTINGS_FORMAT] Level {$level}, ID Jenis Bobot Skor: {$idJenisBobotSkorMinimal}, Skor Minimal Entry:", $skorMinimalEntry ? (array)$skorMinimalEntry : null);
                $settings['rewards'][(int)$level] = [
                    'nama_reward' => $reward->bentuk_reward ?? $reward->BENTUK_REWARD ?? $reward->bentuk ?? '',
                    'slot_tersedia' => $reward->slot_reward ?? $reward->SLOT_REWARD ?? $reward->slot ?? '',
                    'skor_minimal' => $skorMinimalEntry ? ($skorMinimalEntry->NILAI ?? $skorMinimalEntry->nilai ?? '') : ''
                ];
            } else { Log::warning('[PREVIOUS_SETTINGS_FORMAT] Reward item tidak memiliki level yang valid atau level tidak di 1,2,3:', (array)$reward); }
        }
        for ($i=1; $i<=3; $i++) { if (!isset($settings['rewards'][$i])) { $settings['rewards'][$i] = ['nama_reward' => '', 'slot_tersedia' => '', 'skor_minimal' => '']; } }
        $settings['poin_komponen'] = [];
        foreach ($this->jenisBobotMapping as $formKey => $idBobot) {
            if ($idBobot >= 4) { 
                $pembobotanEntry = $pembobotans->get($idBobot);
                $settings['poin_komponen'][$formKey] = $pembobotanEntry ? ($pembobotanEntry->NILAI ?? $pembobotanEntry->nilai ?? '') : '';
            }
        }
        Log::info('[PERIODE_CREATE] Previous settings formatted:', $settings);
        return $settings;
    }

    public function store(Request $request)
    {
        Log::info('[PERIODE_STORE_START]', $request->all());
        $poinKomponenValidationRules = [];
        $komponenBobotKeysUntukValidasi = array_keys(array_filter($this->jenisBobotMapping, fn($id) => $id >= 4)); 
        foreach ($komponenBobotKeysUntukValidasi as $key) { $poinKomponenValidationRules['poin_komponen.' . $key] = 'required|numeric|min:0'; }
        $validator = WebValidator::make($request->all(), array_merge([
            'nama_periode' => 'required|string|max:100', 'start_date' => 'required|date', 'end_date' => 'required|date|after_or_equal:start_date',
            'kunjungan_start.*' => 'required_with:kunjungan_end.*,kunjungan_skor.*|nullable|numeric|min:0',
            'kunjungan_end.*' => 'required_with:kunjungan_start.*,kunjungan_skor.*|nullable|numeric|gte:kunjungan_start.*',
            'kunjungan_skor.*' => 'required_with:kunjungan_start.*,kunjungan_end.*|nullable|numeric|min:0',
            'pinjaman_start.*' => 'required_with:pinjaman_end.*,pinjaman_skor.*|nullable|numeric|min:0',
            'pinjaman_end.*' => 'required_with:pinjaman_start.*,pinjaman_skor.*|nullable|numeric|gte:pinjaman_start.*',
            'pinjaman_skor.*' => 'required_with:pinjaman_start.*,pinjaman_end.*|nullable|numeric|min:0',
            'rewards.1.skor_minimal' => 'required|numeric|min:0', 'rewards.2.skor_minimal' => 'required|numeric|min:0', 'rewards.3.skor_minimal' => 'required|numeric|min:0',
            'rewards.*.nama_reward' => 'required|string|max:50', 'rewards.*.slot_tersedia' => 'required|numeric|min:0',
        ], $poinKomponenValidationRules));
        if ($validator->fails()) { return redirect()->route('periode.create')->withErrors($validator)->withInput(); }
        $errors = new MessageBag(); $createdPeriodeId = null; 
        $nextPeriodeId = $this->apiService->getNextId('periode', $this->apiService->getPrimaryKeyName('periode_award'));
        if ($nextPeriodeId === null) { return redirect()->route('periode.create')->with('error', 'Gagal ID Periode.')->withInput(); }
        $dataPeriode = ['id' => $nextPeriodeId, 'nama' => $request->input('nama_periode'), 'tgl_mulai' => $request->input('start_date'), 'tgl_selesai' => $request->input('end_date')];
        $resultPeriode = $this->apiService->createPeriode($dataPeriode);
        if (!$this->isApiCallSuccessful($resultPeriode, $this->apiService->getPrimaryKeyName('periode_award'), 'createPeriode')) {
            return redirect()->route('periode.create')->with('error', 'Gagal simpan periode: ' . ($resultPeriode['_json_error_data']['message'] ?? ($resultPeriode['message'] ?? ($resultPeriode['_body'] ?? 'Error API'))))->withInput();
        }
        $createdPeriodeId = $nextPeriodeId; 
        Log::info('[PERIODE_STORE_API_SUCCESS] Periode ID: ' . $createdPeriodeId);
        foreach (['kunjungan', 'pinjaman'] as $type) {
            if ($request->input("{$type}_start")) {
                foreach ($request->input("{$type}_start") as $index => $start) {
                    if (!is_numeric($start) || !isset($request->input("{$type}_end")[$index]) || !isset($request->input("{$type}_skor")[$index])) continue;
                    $nextRangeId = $this->apiService->getNextId('range-kunjungan', $this->apiService->getPrimaryKeyName('rangekunjungan_award'));
                    if ($nextRangeId === null) { $errors->add("range_{$type}", "Gagal ID range {$type} ".($index+1)."."); continue; }
                    $dataRange = ['id' => $nextRangeId, 'id_jenis_range' => $this->jenisRangeMapping[$type], 'id_periode' => $createdPeriodeId, 'range_awal' => $start, 'range_akhir' => $request->input("{$type}_end")[$index], 'bobot' => $request->input("{$type}_skor")[$index]];
                    $resultRange = $this->apiService->createRangeKunjungan($dataRange);
                    if (!$this->isApiCallSuccessful($resultRange, $this->apiService->getPrimaryKeyName('rangekunjungan_award'), 'createRangeKunjungan')) {
                        $errors->add("range_{$type}", "Gagal simpan range {$type} ".($index+1).": " . ($resultRange['_json_error_data']['message'] ?? ($resultRange['message'] ?? ($resultRange['_body'] ?? 'Error API'))));
                    }
                }
            }
        }
        if ($request->input('rewards')) {
            foreach ($request->input('rewards') as $level => $rewardData) {
                $nextRewardId = $this->apiService->getNextId('reward', $this->apiService->getPrimaryKeyName('reward_award'));
                if ($nextRewardId === null) { $errors->add('reward', "Gagal ID reward level {$level}."); continue; }
                $dataReward = ['id' => $nextRewardId, 'idperiode' => $createdPeriodeId, 'level' => $level, 'bentuk' => $rewardData['nama_reward'], 'slot' => $rewardData['slot_tersedia']];
                $resultReward = $this->apiService->createReward($dataReward);
                if (!$this->isApiCallSuccessful($resultReward, $this->apiService->getPrimaryKeyName('reward_award'), 'createReward')) { $errors->add('reward', "Gagal simpan reward level {$level}."); continue; }
                $idJenisBobotForLevel = $this->jenisBobotMapping['bobot_level_satu'] ?? null;
                if ($level == 2) $idJenisBobotForLevel = $this->jenisBobotMapping['bobot_level_dua'] ?? null;
                elseif ($level == 3) $idJenisBobotForLevel = $this->jenisBobotMapping['bobot_level_tiga'] ?? null;
                if ($idJenisBobotForLevel && isset($rewardData['skor_minimal'])) {
                    $nextPembobotanId = $this->apiService->getNextId('pembobotan', $this->apiService->getPrimaryKeyName('pembobotan_award'));
                    if ($nextPembobotanId === null) { $errors->add('pembobotan_level_'.$level, "Gagal ID pembobotan skor minimal Level {$level}."); continue; }
                    $dataPembobotanSkorMinimal = ['id' => $nextPembobotanId, 'id_periode' => $createdPeriodeId, 'id_jenis_bobot' => $idJenisBobotForLevel, 'nilai' => $rewardData['skor_minimal']];
                    $resultPembobotanSkorMinimal = $this->apiService->createPembobotan($dataPembobotanSkorMinimal);
                    if (!$this->isApiCallSuccessful($resultPembobotanSkorMinimal, 'id', 'createPembobotanSkorMinimal')) { $errors->add('pembobotan_level_'.$level, "Gagal simpan skor minimal Level {$level}.");}
                }
            }
        }
        $inputPoinKomponen = $request->input('poin_komponen'); 
        if ($inputPoinKomponen) { 
            foreach ($inputPoinKomponen as $key => $nilai) {
                if (!isset($this->jenisBobotMapping[$key])) { Log::warning("[PERIODE_STORE_WARNING] Kunci jenis bobot tidak dikenal: {$key}"); continue; }
                $idJenisBobot = $this->jenisBobotMapping[$key];
                if ($idJenisBobot < 4) continue; 
                $nextPembobotanId = $this->apiService->getNextId('pembobotan', $this->apiService->getPrimaryKeyName('pembobotan_award'));
                 if ($nextPembobotanId === null) { $errors->add('pembobotan_komponen', "Gagal ID pembobotan '{$this->namaJenisBobot[$idJenisBobot]}'."); continue; }
                $dataPembobotan = ['id' => $nextPembobotanId, 'id_periode' => $createdPeriodeId, 'id_jenis_bobot' => $idJenisBobot, 'nilai' => $nilai];
                $resultPembobotan = $this->apiService->createPembobotan($dataPembobotan);
                if (!$this->isApiCallSuccessful($resultPembobotan, 'id', 'createPembobotanKomponen')) { $errors->add('pembobotan_komponen', "Gagal simpan pembobotan '{$this->namaJenisBobot[$idJenisBobot]}'.");}
            }
        }
        if ($errors->isNotEmpty()) { return redirect()->route('periode.create')->with('error', 'Beberapa kesalahan terjadi.')->withErrors($errors)->withInput(); }
        return redirect()->route('periode.index')->with('success', 'Pengaturan periode baru berhasil disimpan.');
    }

    public function show(Request $request, $id) 
    {
        Log::info("[PERIODE_SHOW] Memuat detail untuk periode ID: {$id}");
        $periode = null; $rangesKunjungan = []; $rangesPinjaman = []; $rewards = []; $allPembobotansForView = []; $error = null;

        try {
            $apiResponsePeriode = $this->apiService->getPeriodeList(); 
            if ($apiResponsePeriode && !isset($apiResponsePeriode['_error'])) {
                $allPeriodes = isset($apiResponsePeriode['data']) && is_array($apiResponsePeriode['data']) ? $apiResponsePeriode['data'] : $apiResponsePeriode;
                $foundPeriode = collect($allPeriodes)->first(function ($item) use ($id) {
                    $item = (object) $item;
                    $periodeIdApi = $item->ID_PERIODE ?? $item->id_periode ?? $item->id ?? null;
                    return (string)$periodeIdApi === (string)$id;
                });
                if ($foundPeriode) { $periode = (object) $foundPeriode; } 
                else { $error = "Periode ID {$id} tidak ditemukan."; }
            } else { $error = $apiResponsePeriode['_json_error_data']['message'] ?? ($apiResponsePeriode['_body'] ?? 'Gagal memuat data periode.');}

            if ($periode) {
                $pembobotansFromApi = new Collection();
                $apiResponsePembobotans = $this->apiService->getPembobotanList(['id_periode' => $id]); 
                if ($apiResponsePembobotans && !isset($apiResponsePembobotans['_error'])) {
                    $pembobotansDataFromApi = isset($apiResponsePembobotans['data']) && is_array($apiResponsePembobotans['data']) ? $apiResponsePembobotans['data'] : $apiResponsePembobotans;
                    $pembobotansFromApi = collect($pembobotansDataFromApi)->map(function($item){
                        $obj = (object) $item;
                        $obj->ID_JENIS_BOBOT = isset($obj->ID_JENIS_BOBOT) ? (int)($obj->ID_JENIS_BOBOT) : (isset($obj->id_jenis_bobot) ? (int)($obj->id_jenis_bobot) : null);
                        $obj->NILAI = $obj->NILAI ?? $obj->nilai ?? null;
                        return $obj;
                    })->filter(fn($pb) => $pb->ID_JENIS_BOBOT !== null)->keyBy('ID_JENIS_BOBOT'); 
                } else { Log::warning("[PERIODE_SHOW] Gagal memuat data pembobotan untuk periode ID {$id}.", $apiResponsePembobotans ?? []); }
                
                foreach ($this->namaJenisBobot as $idBobot => $namaDeskriptif) {
                    $pembobotanEntry = $pembobotansFromApi->get($idBobot); 
                    $allPembobotansForView[$idBobot] = (object)[
                        'id_jenis_bobot' => $idBobot,
                        'nama_jenis_bobot' => $namaDeskriptif,
                        'nilai' => $pembobotanEntry ? $pembobotanEntry->NILAI : 'N/A'
                    ];
                }
                Log::info("[PERIODE_SHOW] Data Pembobotan (untuk view) periode ID {$id}:", $allPembobotansForView);

                $allRewardsFromApi = [];
                $apiResponseAllRewards = $this->apiService->getRewardList(); 
                if ($apiResponseAllRewards && !isset($apiResponseAllRewards['_error'])) {
                     $allRewardsFromApi = isset($apiResponseAllRewards['data']) && is_array($apiResponseAllRewards['data']) ? $apiResponseAllRewards['data'] : $apiResponseAllRewards;
                } else { Log::warning("[PERIODE_SHOW] Gagal memuat daftar semua reward.", $apiResponseAllRewards ?? []); }
                
                $rewardsForCurrentPeriode = collect($allRewardsFromApi)->filter(function($r) use ($id) {
                    $rewardItem = (object) $r;
                    $rewardPeriodeId = $rewardItem->ID_PERIODE ?? $rewardItem->id_periode ?? $rewardItem->idperiode ?? null; 
                    return (string)$rewardPeriodeId === (string)$id;
                });

                foreach($rewardsForCurrentPeriode as $r) {
                     $rewardObj = (object) $r;
                     $rewardObj->processed_level = $rewardObj->level_reward ?? $rewardObj->LEVEL_REWARD ?? $rewardObj->level ?? null;
                     $rewardObj->processed_bentuk = $rewardObj->bentuk_reward ?? $rewardObj->BENTUK_REWARD ?? $rewardObj->bentuk ?? 'N/A';
                     $rewardObj->processed_slot = $rewardObj->slot_reward ?? $rewardObj->SLOT_REWARD ?? $rewardObj->slot ?? 'N/A';
                     $idJenisBobotSkorMinimal = null;
                     if ($rewardObj->processed_level == 1) $idJenisBobotSkorMinimal = $this->jenisBobotMapping['bobot_level_satu'];
                     elseif ($rewardObj->processed_level == 2) $idJenisBobotSkorMinimal = $this->jenisBobotMapping['bobot_level_dua'];
                     elseif ($rewardObj->processed_level == 3) $idJenisBobotSkorMinimal = $this->jenisBobotMapping['bobot_level_tiga'];
                     
                     $skorMinimalEntry = $pembobotansFromApi->get($idJenisBobotSkorMinimal); 
                     $rewardObj->skor_minimal = $skorMinimalEntry ? $skorMinimalEntry->NILAI : 'N/A';
                     $rewards[] = $rewardObj;
                 }
                
                $allRangesFromApi = [];
                $apiResponseAllRanges = $this->apiService->getRangeKunjunganList(); 
                if ($apiResponseAllRanges && !isset($apiResponseAllRanges['_error'])) {
                    $allRangesFromApi = isset($apiResponseAllRanges['data']) && is_array($apiResponseAllRanges['data']) ? $apiResponseAllRanges['data'] : $apiResponseAllRanges;
                } else { Log::warning("[PERIODE_SHOW] Gagal memuat daftar semua range kunjungan/pinjaman.", $apiResponseAllRanges ?? []); }
                
                $filteredRanges = collect($allRangesFromApi)->filter(function($range) use ($id) {
                    $rangeObj = (object) $range;
                    $rangePeriodeId = $rangeObj->ID_PERIODE ?? $rangeObj->id_periode ?? null;
                    return (string)$rangePeriodeId === (string)$id;
                });
                foreach ($filteredRanges as $range) {
                    $rangeObj = (object) $range;
                    $idJenisRange = $rangeObj->ID_JENIS_RANGE ?? $rangeObj->id_jenis_range ?? null;
                    if ($idJenisRange == $this->jenisRangeMapping['kunjungan']) $rangesKunjungan[] = $rangeObj;
                    elseif ($idJenisRange == $this->jenisRangeMapping['pinjaman']) $rangesPinjaman[] = $rangeObj;
                }
            }
        } catch (\Exception $e) {
            Log::error("[PERIODE_SHOW] Exception untuk periode ID {$id}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $error = 'Terjadi kesalahan sistem saat memuat detail periode.';
            if (!$periode && $error) abort(500, $error);
        }

        if (!$periode && !$error) { abort(404, 'Periode tidak ditemukan.'); }
        
        return view('detailperiode', [ 
            'periode' => $periode, 'rangesKunjungan' => $rangesKunjungan, 'rangesPinjaman' => $rangesPinjaman,
            'rewards' => $rewards, 
            'allPembobotansForView' => $allPembobotansForView, 
            'namaJenisBobotFromController' => $this->namaJenisBobot, 
            'error' => $error,
        ]);
    }
}
