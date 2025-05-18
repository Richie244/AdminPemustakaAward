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

    public function __construct(string $configKey = 'services.api')
    {
        $this->serviceConfigKeyUsed = $configKey;
        // Mengambil base URL dari konfigurasi. Pastikan ini adalah base URL yang benar
        // untuk SEMUA endpoint yang diakses service ini, termasuk /api/civitas jika endpointnya 'civitas'.
        // Jika /api/civitas ada di domain/port yang berbeda, Anda perlu service terpisah atau cara konfigurasi yang lebih canggih.
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
    }

    public static function make(string $configKey = 'services.api'): self
    {
        return new self($configKey);
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

        return $response->json(); 
    }

    public function getNextId(string $endpoint, string $idColumnName, int $defaultId = 1): ?int
    {
        try {
            Log::info("[SERVICE_GET_NEXT_ID] Fetching next ID for endpoint: {$endpoint}, column: {$idColumnName}");
            $apiResponse = $this->httpClient->get($endpoint); 
            $responseData = $this->handleResponse($apiResponse, "[SERVICE_GET_NEXT_ID] Gagal mengambil data dari API {$endpoint}");

            if (isset($responseData['_error'])) { 
                 Log::error("[SERVICE_GET_NEXT_ID] Error dari handleResponse untuk {$endpoint}.");
                 return null;
            }

            if (isset($responseData['_success_no_content']) || empty($responseData)) {
                Log::info("[SERVICE_GET_NEXT_ID] Endpoint {$endpoint} returned empty data or no content. Starting ID from {$defaultId}.");
                return $defaultId;
            }

            if (is_array($responseData)) {
                $maxId = 0;
                foreach ($responseData as $item) {
                    $itemObject = (object) $item;
                    $currentId = null;
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
                Log::info("[SERVICE_GET_NEXT_ID] Max ID found: {$maxId}, Next ID: {$nextId} for {$endpoint}");
                return $nextId;
            } else {
                Log::error("[SERVICE_GET_NEXT_ID] Data dari API {$endpoint} bukan array atau format tidak dikenal setelah pengecekan.", ['response_body' => $responseData]);
            }
        } catch (\Exception $e) {
            Log::error("[SERVICE_GET_NEXT_ID] Exception saat mengambil data dari API {$endpoint}: " . $e->getMessage());
        }
        Log::warning("[SERVICE_GET_NEXT_ID] Gagal mendapatkan ID berikutnya dari API untuk {$endpoint}. Menggunakan fallback null.");
        return null;
    }

    // --- Metode untuk endpoint KEGIATAN ---
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

    // --- Metode untuk endpoint JADWAL KEGIATAN ---
    public function getJadwalKegiatanList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('jadwal-kegiatan', $params), 'Gagal mengambil daftar jadwal kegiatan');
    }
    public function createJadwalKegiatan(array $data): ?array {
        return $this->handleResponse($this->httpClient->asJson()->post('jadwal-kegiatan', $data), 'Gagal membuat jadwal kegiatan');
    }
    public function deleteJadwalKegiatan(string $id): ?array {
        return $this->handleResponse($this->httpClient->delete("jadwal-kegiatan/{$id}"), "Gagal menghapus jadwal kegiatan ID: {$id}");
    }

    // --- Metode untuk endpoint PEMATERI KEGIATAN ---
    public function getPemateriKegiatanList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('pemateri-kegiatan', $params), 'Gagal mengambil daftar pemateri kegiatan');
    }

    // --- Metode untuk endpoint SERTIFIKAT ---
    public function getSertifikatList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('sertifikat', $params), 'Gagal mengambil daftar sertifikat');
    }
    public function createSertifikat(array $data): ?array {
        return $this->handleResponse($this->httpClient->asJson()->post('sertifikat', $data), 'Gagal membuat sertifikat');
    }
    public function deleteSertifikat(string $id): ?array {
        return $this->handleResponse($this->httpClient->delete("sertifikat/{$id}"), "Gagal menghapus sertifikat ID: {$id}");
    }

    // --- Metode untuk endpoint HADIR KEGIATAN ---
    public function getHadirKegiatanList(array $params = []): ?array {
        return $this->handleResponse($this->httpClient->get('hadir-kegiatan', $params), 'Gagal mengambil daftar hadir kegiatan');
    }
    public function deleteHadirKegiatan(string $id): ?array {
        return $this->handleResponse($this->httpClient->delete("hadir-kegiatan/{$id}"), "Gagal menghapus hadir kegiatan ID: {$id}");
    }

    // --- Metode untuk Aksara Dinamika ---
    public function getAksaraDinamikaList(array $queryParams = []): ?array
    {
        return $this->handleResponse(
            $this->httpClient->get('aksara-dinamika', $queryParams), 
            'Gagal mengambil daftar Aksara Dinamika'
        );
    }

    public function createAksaraHistoriStatus(array $data): ?array
    {
        Log::info('[SERVICE_CREATE_HISTORI_STATUS] Mengirim data ke API /histori-status:', $data);
        return $this->handleResponse(
            $this->httpClient->asJson()->post('histori-status', $data), 
            'Gagal membuat histori status Aksara Dinamika'
        );
    }

    public function readHistoriStatus(array $queryParams = []): ?array
    {
        return $this->handleResponse(
            $this->httpClient->get('histori-status', $queryParams),
            'Gagal membaca data Histori Status'
        );
    }

    // --- Metode BARU untuk mengambil daftar Civitas ---
    /**
     * Mengambil daftar semua civitas.
     * @return array|null Daftar civitas atau null jika error.
     */
    public function getCivitasList(array $queryParams = []): ?array
    {
        // Asumsi endpoint adalah 'civitas'. Sesuaikan jika berbeda.
        // Pastikan base URL yang dikonfigurasi untuk service ini juga mencakup endpoint civitas.
        return $this->handleResponse(
            $this->httpClient->get('civitas', $queryParams),
            'Gagal mengambil daftar Civitas'
        );
    }
}
