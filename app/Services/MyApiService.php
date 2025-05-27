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
            'perusahaan_pemateri_pust'  => 'ID_PERUSAHAAN',
            'perusahaan-pemateri'       => 'ID_PERUSAHAAN',
            'perusahaan'                => 'ID_PERUSAHAAN',
            'jadwal-kegiatan'           => 'ID_JADWAL',
            'hadir-kegiatan'            => 'ID_HADIR',
            'aksara-dinamika'           => 'ID_AKSARA_DINAMIKA',
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
        $mappedKey = $this->primaryKeyMap[$cleanedEndpointName] ?? $this->primaryKeyMap['default'];
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
        
        if ($response->successful() && empty(trim($response->body()))) {
            Log::info("{$errorMessagePrefix}: Response sukses namun body kosong.", ['url' => $response->effectiveUri()->__toString(), 'status' => $response->status()]);
            return [
                '_success_no_content' => true,
                '_status' => $response->status()
            ];
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
        
        if (is_array($jsonData) && (empty($jsonData) || isset($jsonData[0])) && !collect($jsonData)->has('data')) {
            Log::debug("{$errorMessagePrefix}: API mengembalikan array langsung (tidak dibungkus 'data'). Menggunakan array tersebut sebagai data.", ['url' => $response->effectiveUri()->__toString()]);
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
            } elseif (is_array($apiResponse)) {
                $itemsToIterate = $apiResponse;
                 Log::debug("[SERVICE_GET_NEXT_ID] Using direct API response as items for '{$endpoint}'. Count: " . count($itemsToIterate));
            } else {
                Log::warning("[SERVICE_GET_NEXT_ID] Response for '{$endpoint}' is not an array and not 'data' wrapped. Assuming empty.", ['response_preview' => Str::limit(json_encode($apiResponse), 200)]);
            }

            if (empty($itemsToIterate)) {
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
                    $idColumnName,
                    strtoupper($idColumnName),
                    strtolower($idColumnName),
                    'id', 
                    'ID'
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

    public function getPeriodeList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('periode', $params), 'Gagal mengambil daftar periode');
    }
    public function createPeriode(array $data): ?array {
        return $this->handleResponse($this->httpClient->asJson()->post('periode', $data), 'Gagal membuat periode');
    }
    public function getRangeKunjunganList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('range-kunjungan', $params), 'Gagal mengambil daftar range kunjungan');
    }
    public function createRangeKunjungan(array $data): ?array {
        return $this->handleResponse($this->httpClient->asJson()->post('range-kunjungan', $data), 'Gagal membuat range kunjungan');
    }
    public function getRewardList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('reward', $params), 'Gagal mengambil daftar reward');
    }
    public function createReward(array $data): ?array {
        return $this->handleResponse($this->httpClient->asJson()->post('reward', $data), 'Gagal membuat reward');
    }
    public function getPembobotanList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('pembobotan', $params), 'Gagal mengambil daftar pembobotan');
    }
    public function createPembobotan(array $data): ?array {
        return $this->handleResponse($this->httpClient->asJson()->post('pembobotan', $data), 'Gagal membuat pembobotan');
    }
    public function getPemateriList(array $params = []): ?array { 
        return $this->handleResponse($this->httpClient->get('pemateri-kegiatan', $params), 'Gagal mengambil daftar master pemateri (endpoint /pemateri)');
    }
    public function createPemateri(array $data): ?array {
        Log::info('[MyApiService] createPemateri data:', $data);
        return $this->handleResponse($this->httpClient->asJson()->post('pemateri-kegiatan', $data), 'Gagal membuat pemateri');
    }

    public function deletePemateri(string $id): ?array {
        Log::info("[MyApiService] Menghapus pemateri dengan ID: {$id}");
        return $this->handleResponse($this->httpClient->delete("pemateri-kegiatan/{$id}"), "Gagal menghapus pemateri ID: {$id}");
    }

    public function getPerusahaanPemateriList(array $params = []): ?array { 
        Log::info('[MyApiService] Mengambil daftar perusahaan pemateri dari endpoint /perusahaan');
        $response = $this->httpClient->get('perusahaan', $params);
        return $this->handleResponse($response, 'Gagal mengambil daftar perusahaan pemateri');
    }

    public function createPerusahaanPemateri(array $data): ?array {
        Log::info('[MyApiService] createPerusahaanPemateri data:', $data);
        return $this->handleResponse($this->httpClient->asJson()->post('perusahaan', $data), 'Gagal membuat perusahaan pemateri');
    }

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
    public function getJadwalKegiatanList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('jadwal-kegiatan', $params), 'Gagal mengambil daftar jadwal kegiatan');
    }
    public function createJadwalKegiatan(array $data): ?array {
        return $this->handleResponse($this->httpClient->asJson()->post('jadwal-kegiatan', $data), 'Gagal membuat jadwal kegiatan');
    }
    public function deleteJadwalKegiatan(string $id): ?array {
        return $this->handleResponse($this->httpClient->delete("jadwal-kegiatan/{$id}"), "Gagal menghapus jadwal kegiatan ID: {$id}");
    }
    public function getPemateriKegiatanList(array $params = []): ?array { 
        return $this->handleResponse($this->httpClient->get('pemateri-kegiatan', $params), 'Gagal mengambil daftar pemateri (endpoint pemateri-kegiatan)');
    }
    public function getSertifikatList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('sertifikat', $params), 'Gagal mengambil daftar sertifikat');
    }
    public function createSertifikat(array $data): ?array {
        return $this->handleResponse($this->httpClient->asJson()->post('sertifikat', $data), 'Gagal membuat sertifikat');
    }
    public function deleteSertifikat(string $id): ?array {
        return $this->handleResponse($this->httpClient->delete("sertifikat/{$id}"), "Gagal menghapus sertifikat ID: {$id}");
    }
    public function getHadirKegiatanList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('hadir-kegiatan', $params), 'Gagal mengambil daftar hadir kegiatan');
    }
    public function deleteHadirKegiatan(string $id): ?array {
        return $this->handleResponse($this->httpClient->delete("hadir-kegiatan/{$id}"), "Gagal menghapus hadir kegiatan ID: {$id}");
    }
    public function getAksaraDinamikaList(array $queryParams = []): ?array {
        return $this->handleResponse($this->httpClient->get('aksara-dinamika', $queryParams),'Gagal mengambil daftar Aksara Dinamika');
    }
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
    public function getCivitasList(array $queryParams = []): ?array {
        return $this->handleResponse($this->httpClient->get('civitas', $queryParams),'Gagal mengambil daftar Civitas');
    }
}