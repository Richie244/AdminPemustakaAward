<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MyApiService
{
    protected PendingRequest $httpClient;
    protected string $serviceConfigKeyUsed;
    protected array $primaryKeyMap;

    public function __construct(string $configKey = 'services.api')
    {
        $this->serviceConfigKeyUsed = $configKey;
        $baseUrl = config("{$configKey}.base_url");
        $apiKey = config("{$configKey}.key");

        if (!$baseUrl) {
            throw new \Exception("API base URL untuk konfigurasi '{$configKey}' tidak ditemukan. Harap periksa file .env dan config/services.php Anda.");
        }

        $this->httpClient = Http::baseUrl($baseUrl)
            ->timeout(30)
            ->retry(3, 100, function ($exception, $request) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException ||
                       ($exception instanceof \Illuminate\Http\Client\RequestException && $exception->response && $exception->response->status() >= 500);
            });

        if ($apiKey) {
            $this->httpClient->withToken($apiKey);
        }

        $this->primaryKeyMap = [
            'sertifikat'                => 'ID_SERTIFIKAT',
            'kegiatan'                  => 'ID_KEGIATAN',
            'civitas'                   => 'ID_CIVITAS',
            'histori-status'            => 'ID_HISTORI_STATUS',
            'periode_award'             => 'ID_PERIODE',
            'periode'                   => 'ID_PERIODE',
            'rangekunjungan_award'      => 'ID_RANGE_KUNJUNGAN',
            'range-kunjungan'           => 'ID_RANGE_KUNJUNGAN',
            'reward_award'              => 'ID_REWARD',
            'reward'                    => 'ID_REWARD',
            'pembobotan_award'          => 'ID_PEMBOBOTAN',
            'pembobotan'                => 'ID_PEMBOBOTAN',
            'pematerikegiatan_pust'     => 'ID_PEMATERI',
            'pemateri'                  => 'ID_PEMATERI',
            'pemateri-kegiatan'         => 'ID_PEMATERI',
            'perusahaan_pemateri_pust'  => 'ID_PERUSAHAAN', // Alias untuk endpoint perusahaan di PemateriController
            'perusahaan'                => 'ID_PERUSAHAAN', // Untuk CRUD Perusahaan murni
            'jadwal-kegiatan'           => 'ID_JADWAL',
            'hadir-kegiatan'            => 'ID_HADIR',
            'aksara-dinamika'           => 'ID_AKSARA_DINAMIKA',
            'kota'                      => 'id',
            'default'                   => 'id',
        ];
    }

    public static function make(string $configKey = 'services.api'): self
    {
        return new self($configKey);
    }

    public function getPrimaryKeyName(string $endpointName): string
    {
        $cleanedEndpointName = last(explode('/', strtolower(trim($endpointName))));
        // Prioritaskan mapping yang lebih spesifik jika ada
        $mappedKey = $this->primaryKeyMap[$cleanedEndpointName] ??
                     $this->primaryKeyMap[str_replace('-', '_', $cleanedEndpointName)] ?? // coba dengan underscore
                     $this->primaryKeyMap['default'];
        Log::debug("[MyApiService::getPrimaryKeyName] Endpoint: '{$endpointName}' -> Cleaned: '{$cleanedEndpointName}' -> Mapped PK: '{$mappedKey}'");
        return $mappedKey;
    }

    protected function handleResponse(Response $response, string $errorMessagePrefix = 'Operasi API gagal'): ?array
    {
        if ($response->failed()) {
            $errorData = [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $response->effectiveUri()->__toString(),
                'config_key' => $this->serviceConfigKeyUsed,
            ];
            Log::error($errorMessagePrefix, $errorData);
            return [
                '_error' => true,
                '_status' => $response->status(),
                '_body' => $response->body(),
                '_json_error_data' => $response->json() ?? ['message' => 'API request failed with status ' . $response->status() . '. No JSON body.', 'details' => Str::limit($response->body(), 200)]
            ];
        }

        if ($response->successful() && empty(trim($response->body())) && $response->status() !== 204) { // Allow 204 No Content
            Log::info("{$errorMessagePrefix}: Response sukses namun body kosong (status: {$response->status()}).", ['url' => $response->effectiveUri()->__toString()]);
            // Untuk POST/PUT/DELETE yang sukses tanpa content, ini bisa jadi normal
            if (in_array(strtoupper($response->transferStats->getRequest()->getMethod()), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
                 return ['_success_no_content' => true, '_status' => $response->status()];
            }
            // Untuk GET, body kosong mungkin berarti tidak ada data, tapi bukan error
            // Kembalikan array kosong agar bisa diiterasi
            return [];
        }
         if ($response->status() === 204) { // Handle 204 No Content specifically
            Log::info("{$errorMessagePrefix}: Response sukses dengan status 204 No Content.", ['url' => $response->effectiveUri()->__toString()]);
            return ['_success_no_content' => true, '_status' => $response->status()];
        }


        $jsonData = $response->json();
        if ($jsonData === null && !empty(trim($response->body()))) {
            Log::warning("{$errorMessagePrefix}: Response body bukan JSON valid meskipun request sukses.", [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $response->effectiveUri()->__toString(),
            ]);
            return [
                '_error' => true,
                '_status' => $response->status(),
                '_body' => $response->body(),
                '_message' => 'Response body bukan JSON valid.'
            ];
        }

        // Jika API mengembalikan array langsung (tidak dibungkus 'data' atau key lain)
        // dan array tersebut tidak kosong ATAU merupakan array kosong (yang valid)
        if (is_array($jsonData) && (isset($jsonData[0]) || empty($jsonData)) && !collect($jsonData)->has(['data', 'success', 'message'])) {
            Log::debug("{$errorMessagePrefix}: API mengembalikan array langsung. Menggunakan array tersebut sebagai data.", ['url' => $response->effectiveUri()->__toString(), 'count' => count($jsonData)]);
            return $jsonData;
        }

        return $jsonData;
    }

    public function getNextId(string $endpoint, string $idColumnName = null, int $defaultId = 1): ?int
    {
        if ($idColumnName === null) {
            $idColumnName = $this->getPrimaryKeyName($endpoint);
        }
        Log::info("[SERVICE_GET_NEXT_ID] Attempting for endpoint: '{$endpoint}', expecting PK column: '{$idColumnName}'");

        try {
            $rawHttpResponse = $this->httpClient->get($endpoint);
            $apiResponse = $this->handleResponse($rawHttpResponse, "[SERVICE_GET_NEXT_ID] API call to '{$endpoint}' failed for getNextId");

            Log::debug("[SERVICE_GET_NEXT_ID] Processed API Response for '{$endpoint}': ", is_array($apiResponse) ? $apiResponse : ['response_type' => gettype($apiResponse), 'content_preview' => Str::limit(json_encode($apiResponse), 200)]);

            if ($apiResponse === null) {
                Log::error("[SERVICE_GET_NEXT_ID] Null response after handleResponse for '{$endpoint}'. Cannot determine next ID.");
                return null;
            }
            if (isset($apiResponse['_error'])) {
                Log::error("[SERVICE_GET_NEXT_ID] Error flag present in response for '{$endpoint}'.", $apiResponse);
                if (($apiResponse['_status'] ?? 0) == 404) {
                    Log::info("[SERVICE_GET_NEXT_ID] Endpoint '{$endpoint}' returned 404. Assuming empty, starting ID from {$defaultId}.");
                    return $defaultId;
                }
                return null;
            }
               if (isset($apiResponse['_success_no_content'])) {
                Log::info("[SERVICE_GET_NEXT_ID] Endpoint '{$endpoint}' returned success with no content. Assuming empty, starting ID from {$defaultId}.");
                return $defaultId;
            }

            $itemsToIterate = [];
            if (isset($apiResponse['data']) && is_array($apiResponse['data'])) {
                $itemsToIterate = $apiResponse['data'];
                Log::debug("[SERVICE_GET_NEXT_ID] Extracted items from 'data' key for '{$endpoint}'. Count: " . count($itemsToIterate));
            } elseif (is_array($apiResponse)) { // Jika API langsung mengembalikan array
                $itemsToIterate = $apiResponse;
                 Log::debug("[SERVICE_GET_NEXT_ID] Using direct API response as items for '{$endpoint}'. Count: " . count($itemsToIterate));
            } else {
                Log::warning("[SERVICE_GET_NEXT_ID] Response for '{$endpoint}' is not an array and not 'data' wrapped. Assuming empty.", ['response_preview' => Str::limit(json_encode($apiResponse), 200)]);
            }

            if (empty($itemsToIterate) && is_array($itemsToIterate)) { // Pastikan $itemsToIterate adalah array sebelum dihitung
                Log::info("[SERVICE_GET_NEXT_ID] No items found for '{$endpoint}' after extracting. Starting ID from {$defaultId}.");
                return $defaultId;
            }


            $maxId = 0;
            foreach ($itemsToIterate as $keyItem => $item) {
                if (!is_array($item) && !is_object($item)) {
                    Log::debug("[SERVICE_GET_NEXT_ID] Skipping non-array/object item in '{$endpoint}' iteration at index {$keyItem}.", ['item_type' => gettype($item)]);
                    continue;
                }
                $itemObject = (object) $item;

                $currentId = null;
                $possibleKeys = array_unique([
                    $idColumnName, // Kunci dari mapping
                    strtoupper($idColumnName),
                    strtolower($idColumnName),
                    'id', // Fallback umum
                    'ID'  // Fallback umum
                ]);
                Log::debug("[SERVICE_GET_NEXT_ID] Item " . ($keyItem+1) . " for '{$endpoint}'. Possible PK keys to check: " . implode(', ', $possibleKeys), (array)$itemObject);

                foreach ($possibleKeys as $key) {
                    if (property_exists($itemObject, $key)) {
                        $value = $itemObject->{$key};
                        if (is_numeric($value)) {
                            $currentId = (int) $value;
                            Log::debug("[SERVICE_GET_NEXT_ID] Found ID {$currentId} using key '{$key}' in item " . ($keyItem+1) . " for '{$endpoint}'.");
                            break;
                        } else {
                              Log::debug("[SERVICE_GET_NEXT_ID] Found key '{$key}' but value '{$value}' is not numeric in item " . ($keyItem+1) . " for '{$endpoint}'.");
                        }
                    }
                }

                if ($currentId === null) {
                    Log::warning("[SERVICE_GET_NEXT_ID] Could not find a valid numeric ID in item " . ($keyItem+1) . " for '{$endpoint}'. Item dump:", (array)$itemObject);
                }

                if ($currentId !== null && $currentId > $maxId) {
                    $maxId = $currentId;
                }
            }
            $nextId = $maxId + 1;
            Log::info("[SERVICE_GET_NEXT_ID] Calculated Max ID: {$maxId}, Next ID: {$nextId} for '{$endpoint}'.");
            return $nextId;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("[SERVICE_GET_NEXT_ID] ConnectionException for '{$endpoint}': " . $e->getMessage());
        } catch (\Exception $e) {
            Log::error("[SERVICE_GET_NEXT_ID] General Exception for '{$endpoint}': " . $e->getMessage(), ['trace' => Str::limit($e->getTraceAsString(), 1000)]);
        }

        Log::warning("[SERVICE_GET_NEXT_ID] Failed to determine next ID for '{$endpoint}'. Returning null.");
        return null;
    }

    // Periode
    public function getPeriodeList(array $params = []): array {
        return $this->handleResponse($this->httpClient->get('periode', $params), 'Gagal mengambil daftar periode');
    }
    public function createPeriode(array $data): array {
        return $this->handleResponse($this->httpClient->asJson()->post('periode', $data), 'Gagal membuat periode');
    }
    public function getPeriodeDetail(string $id): array {
        return $this->handleResponse($this->httpClient->get("periode/{$id}"), "Gagal mengambil detail periode ID: {$id}");
    }
    public function getLatestPeriodeDetails(): array {
        return $this->handleResponse($this->httpClient->get('periode', ['_sort' => 'start_date:DESC', '_limit' => 1]), 'Gagal mengambil periode terbaru');
    }
    public function deletePeriode(string $id): array {
        return $this->handleResponse($this->httpClient->delete("periode/{$id}"), "Gagal menghapus periode ID: {$id}");
    }

    public function createRangeSkor(array $data): array {
        return $this->handleResponse($this->httpClient->asJson()->post('range-skor', $data), 'Gagal membuat range skor');
    }


    // Range Kunjungan
    public function getRangeKunjunganList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('range-kunjungan', $params), 'Gagal mengambil daftar range kunjungan');
    }
    public function createRangeKunjungan(array $data): ?array {
        return $this->handleResponse($this->httpClient->asJson()->post('range-kunjungan', $data), 'Gagal membuat range kunjungan');
    }

    // Reward
    public function getRewardList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('reward', $params), 'Gagal mengambil daftar reward');
    }
    public function createReward(array $data): ?array {
        return $this->handleResponse($this->httpClient->asJson()->post('reward', $data), 'Gagal membuat reward');
    }

    // Pembobotan
    public function getPembobotanList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('pembobotan', $params), 'Gagal mengambil daftar pembobotan');
    }
    public function createPembobotan(array $data): ?array {
        return $this->handleResponse($this->httpClient->asJson()->post('pembobotan', $data), 'Gagal membuat pembobotan');
    }

    // Pemateri (Master)
    public function getPemateriList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('pemateri-kegiatan', $params), 'Gagal mengambil daftar master pemateri (endpoint /pemateri-kegiatan)');
    }
    public function createPemateri(array $data): ?array {
        Log::info('[MyApiService] createPemateri data (to /pemateri-kegiatan):', $data);
        return $this->handleResponse($this->httpClient->asJson()->post('pemateri-kegiatan', $data), 'Gagal membuat pemateri');
    }
    public function deletePemateri(string $id): ?array {
        Log::info("[MyApiService] Menghapus pemateri dengan ID: {$id} (from /pemateri-kegiatan)");
        return $this->handleResponse($this->httpClient->delete("pemateri-kegiatan/{$id}"), "Gagal menghapus pemateri ID: {$id}");
    }

    // Perusahaan Pemateri (digunakan oleh PemateriController dan PerusahaanController)
    public function getPerusahaanPemateriList(array $params = []): ?array {
        Log::info('[MyApiService] Mengambil daftar perusahaan dari endpoint /perusahaan');
        $response = $this->httpClient->get('perusahaan', $params);
        return $this->handleResponse($response, 'Gagal mengambil daftar perusahaan');
    }
    public function createPerusahaanPemateri(array $data): ?array {
        Log::info('[MyApiService] createPerusahaanPemateri data (to /perusahaan):', $data);
        return $this->handleResponse($this->httpClient->asJson()->post('perusahaan', $data), 'Gagal membuat perusahaan');
    }
    public function deletePerusahaanPemateri(string $id): ?array { // Method baru untuk delete perusahaan
        Log::info("[MyApiService] Menghapus perusahaan dengan ID: {$id} (from /perusahaan)");
        return $this->handleResponse($this->httpClient->delete("perusahaan/{$id}"), "Gagal menghapus perusahaan ID: {$id}");
    }


    // Kegiatan
    public function getKegiatanList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('kegiatan', $params), 'Gagal mengambil daftar kegiatan');
    }
    public function createKegiatan(array $data): ?array {
        return $this->handleResponse($this->httpClient->asJson()->post('kegiatan', $data), 'Gagal membuat kegiatan');
    }
    public function updateKegiatan(string $id, array $data): ?array {
        return $this->handleResponse($this->httpClient->asJson()->put("kegiatan/{$id}", $data), "Gagal mengupdate kegiatan ID: {$id}");
    }
    public function deleteKegiatan(string $id): ?array {
        return $this->handleResponse($this->httpClient->delete("kegiatan/{$id}"), "Gagal menghapus kegiatan ID: {$id}");
    }

    // Jadwal Kegiatan
    public function getJadwalKegiatanList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('jadwal-kegiatan', $params), 'Gagal mengambil daftar jadwal kegiatan');
    }
    public function createJadwalKegiatan(array $data): ?array {
        return $this->handleResponse($this->httpClient->asJson()->post('jadwal-kegiatan', $data), 'Gagal membuat jadwal kegiatan');
    }
    public function deleteJadwalKegiatan(string $id): ?array {
        return $this->handleResponse($this->httpClient->delete("jadwal-kegiatan/{$id}"), "Gagal menghapus jadwal kegiatan ID: {$id}");
    }

    // Pemateri Kegiatan (List untuk KegiatanController, mungkin sama dengan getPemateriList)
    public function getPemateriKegiatanList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('pemateri-kegiatan', $params), 'Gagal mengambil daftar pemateri untuk kegiatan (endpoint /pemateri-kegiatan)');
    }

    // Sertifikat
    public function getSertifikatList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('sertifikat', $params), 'Gagal mengambil daftar sertifikat');
    }
    public function createSertifikat(array $data): ?array {
        return $this->handleResponse($this->httpClient->asJson()->post('sertifikat', $data), 'Gagal membuat sertifikat');
    }
    public function deleteSertifikat(string $id): ?array {
        return $this->handleResponse($this->httpClient->delete("sertifikat/{$id}"), "Gagal menghapus sertifikat ID: {$id}");
    }

    // Hadir Kegiatan
    public function getHadirKegiatanList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('hadir-kegiatan', $params), 'Gagal mengambil daftar hadir kegiatan');
    }
    public function deleteHadirKegiatan(string $id): ?array {
        return $this->handleResponse($this->httpClient->delete("hadir-kegiatan/{$id}"), "Gagal menghapus hadir kegiatan ID: {$id}");
    }

    // Aksara Dinamika
    public function getAksaraDinamikaList(array $queryParams = []): ?array {
        return $this->handleResponse($this->httpClient->get('aksara-dinamika', $queryParams),'Gagal mengambil daftar Aksara Dinamika');
    }

    // Histori Status Aksara
    public function createAksaraHistoriStatus(array $data): ?array {
        Log::info('[SERVICE_CREATE_HISTORI_STATUS] Mengirim data ke API /histori-status:', $data);
        return $this->handleResponse($this->httpClient->asJson()->post('histori-status', $data), 'Gagal membuat histori status Aksara Dinamika');
    }
    public function readHistoriStatus(array $queryParams = []): ?array {
        $rawHttpResponse = $this->httpClient->get('histori-status', $queryParams);
        $handledResponse = $this->handleResponse($rawHttpResponse, 'Gagal membaca data Histori Status');
        if (isset($handledResponse['_error']) && isset($handledResponse['_status']) && $handledResponse['_status'] == 404) {
            Log::info("[SERVICE_READ_HISTORI_STATUS] Endpoint histori-status mengembalikan 404. Mengembalikan array kosong.");
            return [];
        }
        return $handledResponse;
    }

    // Civitas
    public function getCivitasList(array $queryParams = []): ?array {
        return $this->handleResponse($this->httpClient->get('civitas', $queryParams),'Gagal mengambil daftar Civitas');
    }

    public function getKotaList(): ?array
    {
        Log::info('[MyApiService] Mengambil daftar kota dari endpoint /kota');
        $response = $this->httpClient->get('kota'); // Memanggil endpoint /kota
        return $this->handleResponse($response, 'Gagal mengambil daftar kota');
    }

    // ========== START: FUNGSI BARU UNTUK LEADERBOARD ==========

    /**
     * Mengambil data leaderboard untuk mahasiswa.
     *
     * @param array $params Query parameters opsional.
     * @return array|null
     */
    public function getMahasiswaLeaderboard(array $params = []): ?array
    {
        Log::info('[MyApiService] Mengambil leaderboard mahasiswa dari endpoint /rekap-poin/leaderboard/mhs');
        $response = $this->httpClient->get('rekap-poin/leaderboard/mhs', $params);
        return $this->handleResponse($response, 'Gagal mengambil leaderboard mahasiswa');
    }

    /**
     * Mengambil data leaderboard untuk dosen.
     *
     * @param array $params Query parameters opsional.
     * @return array|null
     */
    public function getDosenLeaderboard(array $params = []): ?array
    {
        Log::info('[MyApiService] Mengambil leaderboard dosen dari endpoint /rekap-poin/leaderboard/dosen');
        $response = $this->httpClient->get('rekap-poin/leaderboard/dosen', $params);
        return $this->handleResponse($response, 'Gagal mengambil leaderboard dosen');
    }

    // ========== END: FUNGSI BARU UNTUK LEADERBOARD ==========

    public function markRewardAsClaimed(string $rekapPoinId): ?array
    {
        // Sesuaikan nama endpoint jika berbeda
        $endpoint = "rekap-poin/klaim/{$rekapPoinId}";
        Log::info("[MyApiService] Menandai klaim hadiah untuk ID: {$rekapPoinId} melalui endpoint: {$endpoint}");
        
        // Menggunakan metode POST sesuai rencana
        $response = $this->httpClient->post($endpoint);
        
        return $this->handleResponse($response, "Gagal menandai klaim hadiah untuk ID: {$rekapPoinId}");
    }

    public function getPenerimaReward(?string $periodeId = null): ?array
    {
        $endpoint = 'penerima-reward';
        $queryParams = [];

        // Jika ada periodeId, tambahkan sebagai parameter query
        if ($periodeId) {
            $queryParams['id_periode'] = $periodeId;
        }

        Log::info('[MyApiService] Mengambil data dari endpoint: ' . $endpoint, $queryParams);
        $response = $this->httpClient->get($endpoint, $queryParams);
        
        return $this->handleResponse($response, 'Gagal mengambil data penerima reward.');
    }

    public function getCivitasDetail(string $id_civitas): ?array
    {
        // ASUMSI NAMA ENDPOINT. Anda harus mengganti 'civitas' dengan endpoint yang benar.
        $endpoint = 'civitas/' . $id_civitas;
        Log::info('[MyApiService] Mengambil detail dari endpoint: ' . $endpoint);
        $response = $this->httpClient->get($endpoint);
        return $this->handleResponse($response, 'Gagal mengambil detail civitas ' . $id_civitas);
    }

    public function getRewardDetail(string $id_reward): ?array
    {
        // ASUMSI NAMA ENDPOINT. Anda harus mengganti 'reward-award' dengan endpoint yang benar.
        $endpoint = 'reward-award/' . $id_reward;
        Log::info('[MyApiService] Mengambil detail dari endpoint: ' . $endpoint);
        $response = $this->httpClient->get($endpoint);
        return $this->handleResponse($response, 'Gagal mengambil detail reward ' . $id_reward);
    }
}