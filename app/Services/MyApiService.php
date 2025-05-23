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
            'sertifikat' => 'id',
            'kegiatan' => 'id_kegiatan',
            'civitas' => 'id_civitas',
            'histori-status' => 'id_histori_status',
            'periode_award' => 'id_periode', // Primary key untuk tabel periode
            'rangekunjungan_award' => 'id_range_kunjungan', // Primary key untuk tabel range kunjungan
            'reward_award' => 'id_reward', // Primary key untuk tabel reward
            'pembobotan_award' => 'id_pembobotan', // Primary key untuk tabel pembobotan
            'default' => 'id',
        ];
    }

    public static function make(string $configKey = 'services.api'): self
    {
        return new self($configKey);
    }

    public function getPrimaryKeyName(string $endpointName): string
    {
        // Bersihkan nama endpoint jika mengandung path, misal 'api/periode' menjadi 'periode'
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
        // Jika idColumnName tidak disediakan, coba ambil dari primaryKeyMap
        if ($idColumnName === null) {
            $idColumnName = $this->getPrimaryKeyName($endpoint);
        }

        try {
            Log::info("[SERVICE_GET_NEXT_ID] Fetching next ID for endpoint: {$endpoint}, column: {$idColumnName}");
            
            $apiResponse = null;
            $rawHttpResponse = null; 

            // Panggil GET langsung ke endpoint untuk mendapatkan semua data
            // Asumsi endpoint ini mengembalikan list semua item dari tabel terkait
            $rawHttpResponse = $this->httpClient->get($endpoint); 
            $apiResponse = $this->handleResponse($rawHttpResponse, "[SERVICE_GET_NEXT_ID] Gagal mengambil data dari API {$endpoint} untuk getNextId");

            if (!$apiResponse || isset($apiResponse['_error'])) {
                $statusErrorCode = $apiResponse['_status'] ?? ($rawHttpResponse ? $rawHttpResponse->status() : null);
                if ($statusErrorCode == 404) {
                    Log::info("[SERVICE_GET_NEXT_ID] Endpoint {$endpoint} mengembalikan 404 (Not Found). Ini dianggap sebagai 'belum ada data'. Memulai ID dari {$defaultId}.");
                    return $defaultId;
                }
                Log::error("[SERVICE_GET_NEXT_ID] Error saat mengambil data dari API {$endpoint} untuk kalkulasi next ID (bukan 404).", $apiResponse ?? ['raw_status' => $statusErrorCode]);
                return null;
            }

            // Jika API mengembalikan data dalam key 'data'
            $itemsToIterate = $apiResponse;
            if (isset($apiResponse['data']) && is_array($apiResponse['data'])) {
                $itemsToIterate = $apiResponse['data'];
            } elseif (is_array($itemsToIterate) && count($itemsToIterate) > 0 && !is_array(current($itemsToIterate)) && !isset($itemsToIterate[0])) {
                 // Kondisi ini mencoba mendeteksi jika $itemsToIterate adalah array asosiatif tunggal (objek JSON tunggal)
                 // bukan array dari objek. Jika ya, ini bukan list untuk diiterasi.
                 Log::warning("[SERVICE_GET_NEXT_ID] Respons dari {$endpoint} bukan array dari item (mungkin objek tunggal). Menganggap belum ada data, memulai ID dari {$defaultId}.");
                 return $defaultId;
            }


            if (isset($apiResponse['_success_no_content']) || (is_array($itemsToIterate) && empty($itemsToIterate))) {
                Log::info("[SERVICE_GET_NEXT_ID] Endpoint {$endpoint} mengembalikan data kosong atau no content. Memulai ID dari {$defaultId}.");
                return $defaultId;
            }

            if (is_array($itemsToIterate)) {
                $maxId = 0;
                foreach ($itemsToIterate as $item) {
                    if (!is_array($item) && !is_object($item)) continue; // Lewati jika item bukan array atau objek
                    $itemObject = (object) $item;
                    $currentId = null;
                    // Memastikan $idColumnName selalu ada di $possibleIdKeys
                    $possibleIdKeys = array_unique([strtolower($idColumnName), $idColumnName, strtoupper($idColumnName), 'id', 'ID']);

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
                Log::info("[SERVICE_GET_NEXT_ID] Max ID ditemukan: {$maxId}, Next ID: {$nextId} untuk {$endpoint}");
                return $nextId;
            } else {
                Log::error("[SERVICE_GET_NEXT_ID] Data dari API {$endpoint} bukan array atau format tidak dikenal setelah pengecekan.", ['response_body' => $apiResponse]);
            }
        } catch (\Exception $e) {
            Log::error("[SERVICE_GET_NEXT_ID] Exception saat mengambil data dari API {$endpoint}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
        Log::warning("[SERVICE_GET_NEXT_ID] Gagal mendapatkan ID berikutnya dari API untuk {$endpoint}. Menggunakan fallback null.");
        return null;
    }

    // --- Metode untuk endpoint PERIODE_AWARD ---
    public function getPeriodeList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('periode', $params), 'Gagal mengambil daftar periode');
    }
    public function createPeriode(array $data): ?array {
        return $this->handleResponse($this->httpClient->asJson()->post('periode', $data), 'Gagal membuat periode');
    }
    // Metode update dan delete periode bisa ditambahkan jika perlu, mengikuti pola ControllerPeriode

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

    public function getPemateriKegiatanList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('pemateri-kegiatan', $params), 'Gagal mengambil daftar pemateri kegiatan');
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
