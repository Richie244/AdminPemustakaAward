<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MyApiService;
use Illuminate\Support\MessageBag;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;

class PeriodeController extends Controller
{
    protected MyApiService $apiService;

    protected array $jenisRangeMapping = ['kunjungan' => 1, 'pinjaman' => 2];
    protected array $jenisBobotMapping = [
        'bobot_level_satu' => 1, 'bobot_level_dua' => 2, 'bobot_level_tiga' => 3, 'maks_kunjungan' => 4,
        'maks_pinjaman' => 5, 'maks_aksara_dinamika' => 6, 'maks_kegiatan' => 7, 'bobot_aksara_dinamika' => 8,
        'bobot_kunjungan' => 9, 'bobot_pinjaman' => 10, 'bobot_kegiatan' => 11,
    ];
    protected array $namaJenisBobot = [
        1 => 'Skor Minimal Reward Level 1', 2 => 'Skor Minimal Reward Level 2', 3 => 'Skor Minimal Reward Level 3',
        4 => 'Poin Maksimum Kunjungan Harian', 5 => 'Poin Maksimum Peminjaman Buku', 6 => 'Poin Maksimum Aksara Dinamika',
        7 => 'Poin Maksimum Partisipasi Kegiatan', 8 => 'Bobot Prioritas Aksara Dinamika', 9 => 'Bobot Prioritas Kunjungan',
        10 => 'Bobot Prioritas Pinjaman', 11 => 'Bobot Prioritas Kegiatan',
    ];
    protected array $prioritasOptions = ['Prioritas' => 1, 'Penting' => 2, 'Menengah' => 3, 'Tambahan' => 4];

    public function __construct(MyApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $search = $request->input('search', '');
        $apiResponse = $this->apiService->getPeriodeList();

        if ($apiResponse['status'] !== 'success') {
            return view('periode', ['error' => $apiResponse['message'] ?? 'Gagal mengambil data.', 'paginator' => null, 'search' => $search]);
        }
        
        $allData = collect($apiResponse['data'])->map(function ($item) {
            // ### PERBAIKAN DI SINI ###
            // Memeriksa apakah kunci 'start_date' dan 'end_date' ada sebelum digunakan.
            $startDate = $item['start_date'] ?? null;
            $endDate = $item['end_date'] ?? null;

            $item['start_date_formatted'] = $startDate ? Carbon::parse($startDate)->isoFormat('D MMMM YYYY') : 'Tidak diatur';
            $item['end_date_formatted'] = $endDate ? Carbon::parse($endDate)->isoFormat('D MMMM YYYY') : 'Tidak diatur';
            $item['is_active'] = ($startDate && $endDate) ? Carbon::now()->between($startDate, $endDate) : false;
            
            return $item;
        })->sortByDesc(function ($item) {
            // Memastikan sorting juga aman jika tanggal tidak ada
            return $item['start_date'] ?? '1970-01-01';
        })->values();
        
        if (!empty($search)) {
            $allData = $allData->filter(fn($item) => stripos($item['nama_periode'], $search) !== false);
        }

        $paginator = new LengthAwarePaginator($allData->forPage($page, 10), $allData->count(), 10, $page, ['path' => $request->url(), 'query' => $request->query()]);
        return view('periode', ['paginator' => $paginator, 'search' => $search]);
    }

    public function create(Request $request)
    {
        $labelPoinKomponen = [];
        $komponenBobotIdsUntukForm = [4, 5, 6, 7];
        foreach ($this->jenisBobotMapping as $key => $idBobot) {
            if (in_array($idBobot, $komponenBobotIdsUntukForm)) {
                $labelPoinKomponen[$key] = $this->namaJenisBobot[$idBobot];
            }
        }

        $previousSettings = [];
        if ($request->query('use_previous') === 'true') {
            $latestPeriodeResponse = $this->apiService->getLatestPeriodeDetails();
            if ($latestPeriodeResponse['status'] === 'success' && !empty($latestPeriodeResponse['data'])) {
                $latestPeriodeId = $latestPeriodeResponse['data'][0]['id'];
                $settingsResponse = $this->apiService->getPeriodeDetail($latestPeriodeId);
                if ($settingsResponse['status'] === 'success') {
                    $previousSettings = $this->formatSettingsForForm($settingsResponse['data']);
                }
            }
        }
        
        return view('tambah-periode', [
            'labelPoinKomponen' => $labelPoinKomponen,
            'previousSettings' => $previousSettings,
            'prioritasOptions' => $this->prioritasOptions,
        ]);
    }

    public function store(Request $request)
    {
        $poinKomponen = $request->input('poin_komponen', []);
        $prioritasKeys = ['bobot_kunjungan', 'bobot_pinjaman', 'bobot_kegiatan', 'bobot_aksara_dinamika'];
        $prioritasValues = [];
        foreach ($prioritasKeys as $key) {
            if (isset($poinKomponen[$key])) $prioritasValues[] = $poinKomponen[$key];
        }

        if (count($prioritasValues) !== count(array_unique($prioritasValues))) {
            return redirect()->back()->withErrors(['bobot_prioritas' => 'Nilai bobot prioritas tidak boleh sama.'])->withInput();
        }

        $errors = new MessageBag();
        $nextPeriodeId = $this->apiService->getNextId('periode', 'id');
        $dataPeriode = ['id' => $nextPeriodeId, 'nama_periode' => $request->input('nama_periode'), 'start_date' => $request->input('start_date'), 'end_date' => $request->input('end_date')];
        $resultPeriode = $this->apiService->createPeriode($dataPeriode);

        if ($resultPeriode['status'] !== 'success') {
            return redirect()->back()->withErrors(['api_error' => 'Gagal menyimpan data periode utama.'])->withInput();
        }
        $createdPeriodeId = $resultPeriode['data']['id'];

        foreach ($this->jenisRangeMapping as $jenis => $idJenis) {
            $starts = $request->input("{$jenis}_start", []);
            foreach ($starts as $i => $start) {
                if(!is_null($start)) {
                    $dataRange = ['id' => $this->apiService->getNextId('range_skor_award', 'id'), 'id_periode' => $createdPeriodeId, 'id_jenis_range' => $idJenis, 'min_range' => $start, 'max_range' => $request->input("{$jenis}_end")[$i], 'skor' => $request->input("{$jenis}_skor")[$i]];
                    if ($this->apiService->createRangeSkor($dataRange)['status'] !== 'success') $errors->add("range_{$jenis}", "Gagal menyimpan range {$jenis}.");
                }
            }
        }

        foreach ($request->input('rewards', []) as $level => $rewardData) {
            $idJenisBobot = $this->jenisBobotMapping['bobot_level_' . ['satu', 'dua', 'tiga'][$level-1]];
            $dataBobot = ['id' => $this->apiService->getNextId('pembobotan_award', 'id'), 'id_periode' => $createdPeriodeId, 'id_jenis_bobot' => $idJenisBobot, 'nilai' => $rewardData['skor_minimal']];
            if ($this->apiService->createPembobotan($dataBobot)['status'] !== 'success') $errors->add("bobot_level_{$level}", "Gagal menyimpan skor minimal Level {$level}.");
            $dataReward = array_merge($rewardData, ['id' => $this->apiService->getNextId('reward_award', 'id'), 'id_periode' => $createdPeriodeId, 'id_jenis_bobot' => $idJenisBobot]);
            if ($this->apiService->createReward($dataReward)['status'] !== 'success') $errors->add("reward_{$level}", "Gagal menyimpan reward Level {$level}.");
        }
        
        foreach ($poinKomponen as $key => $nilai) {
            if (isset($this->jenisBobotMapping[$key])) {
                $idJenisBobot = $this->jenisBobotMapping[$key];
                $nilaiUntukApi = in_array($key, $prioritasKeys) ? ($this->prioritasOptions[$nilai] ?? null) : $nilai;
                $dataBobot = ['id' => $this->apiService->getNextId('pembobotan_award', 'id'), 'id_periode' => $createdPeriodeId, 'id_jenis_bobot' => $idJenisBobot, 'nilai' => $nilaiUntukApi];
                if ($this->apiService->createPembobotan($dataBobot)['status'] !== 'success') $errors->add("bobot_{$key}", "Gagal menyimpan pembobotan {$key}.");
            }
        }

        if ($errors->any()) {
            $this->apiService->deletePeriode($createdPeriodeId);
            return redirect()->back()->withErrors($errors)->withInput();
        }
        return redirect()->route('periode.index')->with('success', 'Periode baru berhasil ditambahkan.');
    }

    public function destroy($id)
    {
        $result = $this->apiService->deletePeriode($id);
        if ($result['status'] === 'success') {
            return redirect()->route('periode.index')->with('success', 'Periode berhasil dihapus.');
        }
        return redirect()->route('periode.index')->with('error', $result['message'] ?? 'Gagal menghapus periode.');
    }
    
    private function formatSettingsForForm(array $details): array
    {
        $settings = [
            'nama_periode' => $details['nama_periode'] ?? '',
            'start_date' => isset($details['start_date']) ? Carbon::parse($details['start_date'])->format('Y-m-d') : '',
            'end_date' => isset($details['end_date']) ? Carbon::parse($details['end_date'])->format('Y-m-d') : '',
            'rewards' => [], 'poin_komponen' => [],
            'kunjungan_start' => [], 'kunjungan_end' => [], 'kunjungan_skor' => [],
            'pinjaman_start' => [], 'pinjaman_end' => [], 'pinjaman_skor' => [],
        ];

        foreach ($details['pembobotan'] ?? [] as $bobot) {
            $key = array_search($bobot['id_jenis_bobot'], $this->jenisBobotMapping);
            if ($key !== false) {
                if (in_array($key, ['bobot_kunjungan', 'bobot_pinjaman', 'bobot_kegiatan', 'bobot_aksara_dinamika'])) {
                    $settings['poin_komponen'][$key] = array_search($bobot['nilai'], $this->prioritasOptions) ?: '';
                } else {
                    $settings['poin_komponen'][$key] = $bobot['nilai'];
                }
            }
        }
        foreach ($details['range_skor'] ?? [] as $range) {
            $key = array_search($range['id_jenis_range'], $this->jenisRangeMapping);
            if ($key !== false) {
                $settings[$key.'_start'][] = $range['min_range'];
                $settings[$key.'_end'][] = $range['max_range'];
                $settings[$key.'_skor'][] = $range['skor'];
            }
        }
        foreach ($details['reward'] ?? [] as $reward) {
            $level = $reward['id_jenis_bobot'];
            if ($level >= 1 && $level <= 3) {
                $settings['rewards'][$level] = ['skor_minimal' => $reward['skor_minimal'], 'nama_reward' => $reward['nama_reward'], 'slot_tersedia' => $reward['slot_tersedia']];
            }
        }
        return $settings;
    }
}