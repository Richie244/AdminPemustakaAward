<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

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
            ->retry(3, 200, function ($exception, $request) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException;
            });

        if ($apiKey) {
            $this->httpClient->withToken($apiKey);
        }

        $this->primaryKeyMap = [
            'sertifikat' => 'id', // Asumsi dari controller sebelumnya, bisa juga 'id_sertifikat'
            'kegiatan' => 'id_kegiatan',
            'civitas' => 'id_civitas',
            'histori-status' => 'id_histori_status',
            'periode_award' => 'id_periode',
            'periode' => 'id_periode', // Endpoint API untuk periode
            'rangekunjungan_award' => 'id_range_kunjungan',
            'range-kunjungan' => 'id_range_kunjungan', // Endpoint API untuk range kunjungan
            'reward_award' => 'id_reward',
            'reward' => 'id_reward', // Endpoint API untuk reward
            'pembobotan_award' => 'id_pembobotan',
            'pembobotan' => 'id_pembobotan', // Endpoint API untuk pembobotan
            'pematerikegiatan_pust' => 'id_pemateri', // Untuk getNextId dari PemateriController
            'pemateri' => 'id_pemateri',           // Endpoint API untuk pemateri
            'perusahaan_pemateri_pust' => 'id_perusahaan', // Untuk getNextId dari PemateriController
            'perusahaan-pemateri' => 'id_perusahaan', // Endpoint API untuk perusahaan pemateri
            'default' => 'id',
        ];
    }

    public static function make(string $configKey = 'services.api'): self
    {
        return new self($configKey);
    }

    public function getPrimaryKeyName(string $endpointName): string
    {
        $cleanedEndpointName = last(explode('/', strtolower($endpointName)));
        return $this->primaryKeyMap[$cleanedEndpointName] ?? $this->primaryKeyMap['default'];
    }

    protected function handleResponse(Response $response, string $errorMessagePrefix = 'Operasi API gagal'): ?array
    {
        if ($response->failed()) {
            Log::error($errorMessagePrefix, [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $response->effectiveUri()->__toString(),
                'config_key' => $this->serviceConfigKeyUsed,
            ]);
            return [
                '_error' => true,
                '_status' => $response->status(),
                '_body' => $response->body(),
                '_json_error_data' => $response->json()
            ];
        }
        
        if ($response->successful() && empty($response->body())) {
            return [
                '_success_no_content' => true,
                '_status' => $response->status()
            ];
        }

        $jsonData = $response->json();
        if ($jsonData === null && !empty($response->body())) {
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
        return $jsonData;
    }

    public function getNextId(string $endpoint, string $idColumnName = null, int $defaultId = 1): ?int
    {
        if ($idColumnName === null) {
            $idColumnName = $this->getPrimaryKeyName($endpoint);
        }
        try {
            Log::info("[SERVICE_GET_NEXT_ID] Fetching next ID for endpoint: {$endpoint}, column: {$idColumnName}");
            $rawHttpResponse = $this->httpClient->get($endpoint); 
            $apiResponse = $this->handleResponse($rawHttpResponse, "[SERVICE_GET_NEXT_ID] Gagal mengambil data dari API {$endpoint} untuk getNextId");

            if (!$apiResponse || isset($apiResponse['_error'])) {
                $statusErrorCode = $apiResponse['_status'] ?? ($rawHttpResponse ? $rawHttpResponse->status() : null);
                if ($statusErrorCode == 404) {
                    Log::info("[SERVICE_GET_NEXT_ID] Endpoint {$endpoint} mengembalikan 404 (Not Found). Memulai ID dari {$defaultId}.");
                    return $defaultId;
                }
                Log::error("[SERVICE_GET_NEXT_ID] Error saat mengambil data dari API {$endpoint} (bukan 404).", $apiResponse ?? ['raw_status' => $statusErrorCode]);
                return null;
            }
            $itemsToIterate = $apiResponse['data'] ?? $apiResponse;
            if (is_array($itemsToIterate) && count($itemsToIterate) > 0 && !is_array(current($itemsToIterate)) && !isset($itemsToIterate[0])) {
                 Log::warning("[SERVICE_GET_NEXT_ID] Respons dari {$endpoint} bukan array dari item. Memulai ID dari {$defaultId}.");
                 return $defaultId;
            }
            if (isset($apiResponse['_success_no_content']) || (is_array($itemsToIterate) && empty($itemsToIterate))) {
                Log::info("[SERVICE_GET_NEXT_ID] Endpoint {$endpoint} mengembalikan data kosong. Memulai ID dari {$defaultId}.");
                return $defaultId;
            }
            if (is_array($itemsToIterate)) {
                $maxId = 0;
                foreach ($itemsToIterate as $item) {
                    if (!is_array($item) && !is_object($item)) continue;
                    $itemObject = (object) $item;
                    $currentId = null;
                    $possibleIdKeys = array_unique([strtolower($idColumnName), $idColumnName, strtoupper($idColumnName), 'id', 'ID']);
                    foreach($possibleIdKeys as $key) {
                        if (property_exists($itemObject, $key) && is_numeric($itemObject->{$key})) {
                            $currentId = (int) $itemObject->{$key};
                            break;
                        }
                    }
                    if ($currentId !== null && $currentId > $maxId) { $maxId = $currentId; }
                }
                $nextId = $maxId + 1;
                Log::info("[SERVICE_GET_NEXT_ID] Max ID: {$maxId}, Next ID: {$nextId} untuk {$endpoint}");
                return $nextId;
            } else {
                Log::error("[SERVICE_GET_NEXT_ID] Data dari API {$endpoint} bukan array.", ['response_body' => $apiResponse]);
            }
        } catch (\Exception $e) {
            Log::error("[SERVICE_GET_NEXT_ID] Exception di API {$endpoint}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
        Log::warning("[SERVICE_GET_NEXT_ID] Gagal mendapatkan ID berikutnya dari API {$endpoint}. Fallback ke null.");
        return null;
    }

    // --- Metode untuk endpoint PERIODE_AWARD ---
    public function getPeriodeList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('periode', $params), 'Gagal mengambil daftar periode');
    }
    public function createPeriode(array $data): ?array {
        return $this->handleResponse($this->httpClient->asJson()->post('periode', $data), 'Gagal membuat periode');
    }

    // --- Metode untuk endpoint RANGEKUNJUNGAN_AWARD ---
    public function getRangeKunjunganList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('range-kunjungan', $params), 'Gagal mengambil daftar range kunjungan');
    }
    public function createRangeKunjungan(array $data): ?array {
        return $this->handleResponse($this->httpClient->asJson()->post('range-kunjungan', $data), 'Gagal membuat range kunjungan');
    }

    // --- Metode untuk endpoint REWARD_AWARD ---
    public function getRewardList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('reward', $params), 'Gagal mengambil daftar reward');
    }
    public function createReward(array $data): ?array {
        return $this->handleResponse($this->httpClient->asJson()->post('reward', $data), 'Gagal membuat reward');
    }

    // --- Metode untuk endpoint PEMBOBOTAN_AWARD ---
    public function getPembobotanList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('pembobotan', $params), 'Gagal mengambil daftar pembobotan');
    }
    public function createPembobotan(array $data): ?array {
        return $this->handleResponse($this->httpClient->asJson()->post('pembobotan', $data), 'Gagal membuat pembobotan');
    }

    // --- Metode untuk endpoint PEMATERI ---
    public function getPemateriList(array $params = []): ?array {
        // Pastikan endpoint 'pemateri' sudah ada di routes/api.php Anda
        return $this->handleResponse($this->httpClient->get('pemateri', $params), 'Gagal mengambil daftar pemateri');
    }

    public function createPemateri(array $data): ?array {
        // Pastikan endpoint 'pemateri' (POST) sudah ada di routes/api.php Anda
        // dan Controller API yang sesuai menangani penyimpanan ke PEMATERIKEGIATAN_PUST
        Log::info('[MyApiService] createPemateri data:', $data);
        return $this->handleResponse($this->httpClient->asJson()->post('pemateri', $data), 'Gagal membuat pemateri');
    }

    // --- Metode untuk endpoint PERUSAHAAN PEMATERI ---
    public function getPerusahaanPemateriList(array $params = []): ?array { // Jika Anda perlu mengambil list perusahaan
        return $this->handleResponse($this->httpClient->get('perusahaan-pemateri', $params), 'Gagal mengambil daftar perusahaan pemateri');
    }

    public function createPerusahaanPemateri(array $data): ?array {
        // Pastikan endpoint 'perusahaan-pemateri' (POST) sudah ada di routes/api.php Anda
        // dan Controller API yang sesuai menangani penyimpanan ke PERUSAHAAN_PEMATERI_PUST
        Log::info('[MyApiService] createPerusahaanPemateri data:', $data);
        return $this->handleResponse($this->httpClient->asJson()->post('perusahaan-pemateri', $data), 'Gagal membuat perusahaan pemateri');
    }


    // --- Metode lainnya yang sudah ada ---
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

    // getPemateriKegiatanList sebelumnya mungkin merujuk ke tabel relasi, 
    // sedangkan getPemateriList yang baru adalah untuk master pemateri.
    // Saya akan membiarkan yang lama jika masih digunakan di tempat lain.
    public function getPemateriKegiatanList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('pemateri-kegiatan', $params), 'Gagal mengambil daftar pemateri kegiatan (relasi)');
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
