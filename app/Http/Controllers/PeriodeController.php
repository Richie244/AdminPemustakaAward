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
        'bobot_aksara_dinamika'  => 8,
    ];

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
            } elseif ($apiResponse && isset($apiResponse['_error'])) {
                $error = $apiResponse['_json_error_data']['message'] ?? ($apiResponse['_body'] ?? 'Gagal memuat data periode dari API.');
            } else {
                if(!(is_array($apiResponse) && empty($apiResponse) && !isset($apiResponse['_error']))){ $error = 'Tidak ada data periode atau respons API tidak valid.'; }
            }
            if ($searchTerm && $allPeriodes instanceof Collection) {
                $allPeriodes = $allPeriodes->filter(fn ($periode) => isset($periode->NAMA_PERIODE) && stripos($periode->NAMA_PERIODE, $searchTerm) !== false);
            }
            if ($sortBy && $allPeriodes instanceof Collection && $allPeriodes->isNotEmpty()) {
                [$sortField, $sortDirection] = explode('_', $sortBy, 2); 
                $isDescending = ($sortDirection === 'desc'); 
                $allPeriodes = $allPeriodes->sortBy(function ($periode) use ($sortField) {
                    if ($sortField === 'tgl_mulai') { try { return isset($periode->TGL_MULAI) ? Carbon::parse($periode->TGL_MULAI) : null; } catch (\Exception $e) { return null; }}
                    if ($sortField === 'nama') { return strtolower($periode->NAMA_PERIODE ?? ''); }
                    return $periode->ID_PERIODE ?? 0; 
                }, SORT_REGULAR, $isDescending)->values(); 
            }
            $currentPage = Paginator::resolveCurrentPage() ?: 1;
            $itemsForCurrentPage = ($allPeriodes instanceof Collection) ? $allPeriodes->slice(($currentPage - 1) * $perPage, $perPage) : new Collection();
            $paginatedPeriodes = new LengthAwarePaginator( $itemsForCurrentPage->all(), ($allPeriodes instanceof Collection) ? $allPeriodes->count() : 0, $perPage, $currentPage,
                ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()]);
        } catch (\Exception $e) { $error = 'Terjadi kesalahan: ' . $e->getMessage(); }
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
        // Mengambil data dengan filter ID Periode
        $apiResponsePembobotans = $this->apiService->getPembobotanList(['id_periode' => $periodeIdToFetch]);
        if ($apiResponsePembobotans && !isset($apiResponsePembobotans['_error'])) {
            $pembobotansData = isset($apiResponsePembobotans['data']) && is_array($apiResponsePembobotans['data']) ? $apiResponsePembobotans['data'] : $apiResponsePembobotans;
            $details['pembobotans'] = collect($pembobotansData)->map(function($item){ // Tidak perlu filter manual lagi jika API sudah filter
                $obj = (object) $item;
                $obj->ID_JENIS_BOBOT = isset($obj->ID_JENIS_BOBOT) ? (int)($obj->ID_JENIS_BOBOT) : (isset($obj->id_jenis_bobot) ? (int)($obj->id_jenis_bobot) : null);
                $obj->NILAI = $obj->NILAI ?? $obj->nilai ?? null;
                return $obj;
            })->filter(fn($pb) => $pb->ID_JENIS_BOBOT !== null)->keyBy('ID_JENIS_BOBOT');
        }
        $apiResponseRewards = $this->apiService->getRewardList(['id_periode' => $periodeIdToFetch]); 
        if ($apiResponseRewards && !isset($apiResponseRewards['_error'])) {
            $rewardsData = isset($apiResponseRewards['data']) && is_array($apiResponseRewards['data']) ? $apiResponseRewards['data'] : $apiResponseRewards;
            $details['rewards'] = collect($rewardsData)->map(fn($item) => (object)$item)->all(); // Tidak perlu filter manual lagi
        }
        $apiResponseRanges = $this->apiService->getRangeKunjunganList(['id_periode' => $periodeIdToFetch]); 
        if ($apiResponseRanges && !isset($apiResponseRanges['_error'])) {
            $rangesData = isset($apiResponseRanges['data']) && is_array($apiResponseRanges['data']) ? $apiResponseRanges['data'] : $apiResponseRanges;
            foreach (collect($rangesData)->map(fn($item) => (object)$item) as $range) { // Tidak perlu filter manual lagi
                $idJenisRange = $range->ID_JENIS_RANGE ?? $range->id_jenis_range ?? null;
                if ($idJenisRange == $this->jenisRangeMapping['kunjungan']) $details['rangesKunjungan'][] = $range;
                elseif ($idJenisRange == $this->jenisRangeMapping['pinjaman']) $details['rangesPinjaman'][] = $range;
            }
        }
        Log::info("[GET_DETAILS_FORM] Details for periode {$periodeIdToFetch}:", $details);
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
        // ... (Metode store tetap sama seperti sebelumnya) ...
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
                // PENTING: Memastikan API dipanggil dengan filter id_periode
                $apiResponsePembobotans = $this->apiService->getPembobotanList(['id_periode' => $id]); 
                if ($apiResponsePembobotans && !isset($apiResponsePembobotans['_error'])) {
                    $pembobotansDataFromApi = isset($apiResponsePembobotans['data']) && is_array($apiResponsePembobotans['data']) ? $apiResponsePembobotans['data'] : $apiResponsePembobotans;
                    // Jika API tidak memfilter berdasarkan id_periode, lakukan filter manual di sini
                    $pembobotansFromApi = collect($pembobotansDataFromApi)->filter(function($pb) use ($id){
                        $pbObj = (object) $pb;
                        return (string)($pbObj->ID_PERIODE ?? $pbObj->id_periode ?? null) === (string)$id;
                    })->map(function($item){
                        $obj = (object) $item;
                        $obj->ID_JENIS_BOBOT = isset($obj->ID_JENIS_BOBOT) ? (int)($obj->ID_JENIS_BOBOT) : (isset($obj->id_jenis_bobot) ? (int)($obj->id_jenis_bobot) : null);
                        $obj->NILAI = $obj->NILAI ?? $obj->nilai ?? null;
                        return $obj;
                    })->filter(fn($pb) => $pb->ID_JENIS_BOBOT !== null)->keyBy('ID_JENIS_BOBOT'); 
                    Log::info("[PERIODE_SHOW] Data Pembobotan (setelah filter manual jika perlu, keyed by ID_JENIS_BOBOT) untuk periode ID {$id}:", $pembobotansFromApi->toArray());
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

                // Mengambil dan memfilter REWARD
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
                
                // Mengambil dan memfilter RANGE KUNJUNGAN/PINJAMAN
                $allRangesFromApi = [];
                $apiResponseAllRanges = $this->apiService->getRangeKunjunganList(); 
                if ($apiResponseAllRanges && !isset($apiResponseAllRanges['_error'])) {
                    $allRangesFromApi = isset($apiResponseAllRanges['data']) && is_array($apiResponseAllRanges['data']) ? $apiResponseAllRanges['data'] : $apiResponseAllRanges;
                } else { Log::warning("[PERIODE_SHOW] Gagal memuat daftar semua range kunjungan/pinjaman.", $apiResponseAllRanges ?? []); }
                
                $filteredRanges = collect($allRangesFromApi)->filter(function($range) use ($id) {
                    $rangeObj = (object) $range;
                    $rangePeriodeId = $rangeObj->ID_PERIODE ?? $rangeObj->id_periode ?? null;
                    // Log::debug("[PERIODE_SHOW_RANGE_FILTER] Comparing rangePeriodeId: {$rangePeriodeId} (type: " . gettype($rangePeriodeId) . ") with periodeId: {$id} (type: " . gettype($id) . ")");
                    return (string)$rangePeriodeId === (string)$id;
                });
                Log::info("[PERIODE_SHOW] Data Ranges mentah untuk periode ID {$id} (setelah filter manual):", $filteredRanges->toArray());

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
