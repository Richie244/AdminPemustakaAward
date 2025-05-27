<?php

namespace App\Http\Controllers;

use App\Services\MyApiService; // Pastikan service API Anda di-import
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session; // Untuk mengambil ID validator
use Carbon\Carbon; // Untuk mengambil tanggal dan waktu saat ini

class AksaraController extends Controller
{
    protected MyApiService $apiService;

    public function __construct(MyApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    protected function paginate(Collection $items, $perPage = 10, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $paginatorOptions = [
            'path' => $options['path'] ?? Paginator::resolveCurrentPath(),
            'query' => request()->except('page'),
        ];
        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            $paginatorOptions
        );
    }

    public function index(Request $request)
    {
        $statusFilter = $request->input('status'); // Akan berisi 'pending', 'diterima', atau 'ditolak'
        $searchTerm = rawurldecode($request->input('search', ''));
        $submissionsCollection = new Collection();

        Log::info('[AKSARA_INDEX_START] Memulai proses pengambilan data. Filter Status: ' . $statusFilter);

        try {
            $responseSubmissions = $this->apiService->getAksaraDinamikaList();
            if (!$responseSubmissions || isset($responseSubmissions['_error'])) {
                Log::error('[AKSARA_INDEX_ERROR] Gagal mengambil data Aksara Dinamika.', $responseSubmissions ?? []);
                return back()->withErrors(['api_error' => 'Gagal memuat data Aksara Dinamika.']);
            }
            if (empty($responseSubmissions) && !is_array($responseSubmissions)) {
                $responseSubmissions = [];
            }

            $responseHistoriStatus = $this->apiService->readHistoriStatus();
            $latestHistories = new Collection();

            if ($responseHistoriStatus && !isset($responseHistoriStatus['_error']) && is_array($responseHistoriStatus)) {
                $allHistoriStatus = collect($responseHistoriStatus)->map(function($historiItem){
                    $h = (object) $historiItem;
                    $h->id_aksara_dinamika_histori = (string) ($h->id_aksara_dinamika ?? $h->ID_AKSARA_DINAMIKA ?? null);
                    $h->tgl_status_parsed = (isset($h->tgl_status) && !empty(trim((string)$h->tgl_status))) ? Carbon::parse($h->tgl_status) : null;
                    
                    $h->status_histori = strtolower($h->status ?? 'pending');
                    $h->user_pust_status_histori = $h->user_pust_status ?? $h->USER_PUST_STATUS ?? null;
                    $h->keterangan_histori = $h->keterangan ?? null;
                    return $h;
                });

                $latestHistories = $allHistoriStatus
                    ->filter(fn($h) => $h->id_aksara_dinamika_histori !== null && $h->tgl_status_parsed !== null)
                    ->groupBy('id_aksara_dinamika_histori')
                    ->map(function (Collection $historiesForOneAksara) {
                        return $historiesForOneAksara->sortByDesc('tgl_status_parsed')->first();
                    });
            } else {
                Log::warning('[AKSARA_INDEX_WARNING] Gagal mengambil data Histori Status.', $responseHistoriStatus ?? []);
            }

            $submissionsCollection = collect($responseSubmissions)->map(function($itemArray) use ($latestHistories) {
                $item = (object) $itemArray;
                $newItem = new \stdClass();
                $currentAksaraId = (string) ($item->id_aksara_dinamika ?? $item->ID_AKSARA_DINAMIKA ?? null);
                
                $newItem->id = $currentAksaraId;
                $newItem->JUDUL = $item->judul ?? $item->JUDUL ?? ('ID Buku: ' . ($item->id_buku ?? $item->ID_BUKU ?? 'N/A'));
                $newItem->NAMA = $item->nama ?? $item->NAMA ?? ('NIM: ' . ($item->nim ?? $item->NIM ?? 'N/A'));
                
                $latestHistoryEntry = $latestHistories->get($currentAksaraId);

                if ($latestHistoryEntry) {
                    $newItem->STATUS = $latestHistoryEntry->status_histori;
                    $newItem->ALASAN_PENOLAKAN = $latestHistoryEntry->keterangan_histori ?? null;
                    $newItem->VALIDATOR_ID = $latestHistoryEntry->user_pust_status_histori ?? null;
                    $newItem->TGL_VALIDASI = $latestHistoryEntry->tgl_status_parsed ? $latestHistoryEntry->tgl_status_parsed->translatedFormat('d F Y H:i') : null;
                } else {
                    $newItem->STATUS = strtolower(
                        $item->status_validasi ??
                        $item->STATUS_VALIDASI ??
                        $item->submission_status ??
                        'pending'
                    );
                    $newItem->ALASAN_PENOLAKAN = $item->alasan_penolakan ??
                                               $item->ALASAN_PENOLAKAN ??
                                               null;
                    $newItem->VALIDATOR_ID = null;
                    $newItem->TGL_VALIDASI = null;
                }
                
                $newItem->NIM = $item->nim ?? $item->NIM ?? null;
                $newItem->ID_BUKU = $item->id_buku ?? $item->ID_BUKU ?? null;
                $newItem->INDUK_BUKU = $item->induk_buku ?? $item->INDUK_BUKU ?? null;
                $newItem->REVIEW = $item->review ?? $item->REVIEW ?? null;
                $newItem->DOSEN_USULAN = $item->dosen_usulan ?? $item->DOSEN_USULAN ?? null;
                $newItem->LINK_UPLOAD = $item->link_upload ?? $item->LINK_UPLOAD ?? null;
                $newItem->PENGARANG = $item->pengarang1 ?? $item->PENGARANG1 ?? null;
                $newItem->EMAIL = $item->email ?? null;
 
                return $newItem;
            });

            if ($searchTerm) {
                $submissionsCollection = $submissionsCollection->filter(function ($item) use ($searchTerm) {
                    return stripos($item->JUDUL ?? '', $searchTerm) !== false ||
                           stripos($item->NAMA ?? '', $searchTerm) !== false ||
                           stripos($item->NIM ?? '', $searchTerm) !== false ||
                           stripos($item->PENGARANG ?? '', $searchTerm) !== false;
                });
            }

            if ($statusFilter && in_array($statusFilter, ['pending', 'diterima', 'ditolak'])) {
                $submissionsCollection = $submissionsCollection->filter(function($item) use ($statusFilter){
                    return ($item->STATUS ?? 'pending') === $statusFilter;
                });
            }

            $submissionsCollection = $submissionsCollection->sortByDesc('id');

        } catch (\Exception $e) {
            Log::error('[AKSARA_INDEX_EXCEPTION] Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
             return back()->withErrors(['api_error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }

        $submissions = $this->paginate(
            $submissionsCollection, 10, null,
            ['path' => route('validasi.aksara.index')]
        );
        
        $submissions->appends($request->query());

        return view('validasi-aksara', [
            'submissions' => $submissions,
            'searchTerm' => $searchTerm
        ]);
    }

    public function show($id)
    {
        Log::info("[AKSARA_SHOW] Attempting to show submission for ID: {$id}");
        $peserta = null;
        try {
            $responseApi = $this->apiService->getAksaraDinamikaList();
            $responseHistoriStatus = $this->apiService->readHistoriStatus();
            $responseCivitas = $this->apiService->getCivitasList();

            if (!$responseApi || isset($responseApi['_error']) || !is_array($responseApi)) {
                Log::error("[AKSARA_SHOW] Gagal mengambil data Aksara Dinamika atau format salah.", $responseApi ?? []);
                abort(500, 'Gagal mengambil data submission dari API Service.');
            }
            $foundItemData = collect($responseApi)->firstWhere('id_aksara_dinamika', (string)$id);

            if (!$foundItemData) {
                Log::warning("[AKSARA_SHOW] Submission not found for ID: {$id}.");
                abort(404, 'Submission tidak ditemukan.');
            }
            $item = (object) $foundItemData;
            $peserta = new \stdClass();
            $currentAksaraId = (string) ($item->id_aksara_dinamika ?? $item->ID_AKSARA_DINAMIKA ?? null);

            $peserta->id = $currentAksaraId;
            $peserta->NAMA = $item->nama ?? 'Tidak tersedia';
            $peserta->nim = $item->nim ?? $item->NIM ?? 'Tidak tersedia';
            $peserta->email = $item->email ?? null;
            $peserta->JUDUL = $item->judul ?? 'Tidak tersedia';
            $peserta->pengarang = $item->pengarang1 ?? $item->PENGARANG1 ?? null;
            $peserta->review = $item->review ?? $item->REVIEW ?? '-';
            $peserta->dosen_usulan = $item->dosen_usulan ?? $item->DOSEN_USULAN ?? '-';
            $peserta->link_upload = $item->link_upload ?? $item->LINK_UPLOAD ?? '#';
            $peserta->id_buku = $item->id_buku ?? $item->ID_BUKU ?? null;
            $peserta->induk_buku = $item->induk_buku ?? $item->INDUK_BUKU ?? null;
            
            $peserta->VALIDATOR_ID = null;
            $peserta->TGL_VALIDASI = null;
            $peserta->NAMA_VALIDATOR = null;

            $latestHistori = null;
            if ($responseHistoriStatus && !isset($responseHistoriStatus['_error']) && is_array($responseHistoriStatus)) {
                $latestHistori = collect($responseHistoriStatus)
                    ->map(function($historiItem){
                        $h = (object) $historiItem;
                        $h->id_aksara_dinamika_histori = (string) ($h->id_aksara_dinamika ?? $h->ID_AKSARA_DINAMIKA ?? null);
                        $h->tgl_status_parsed = (isset($h->tgl_status) && !empty(trim((string)$h->tgl_status))) ? Carbon::parse($h->tgl_status) : null;
                        $h->status_histori = strtolower($h->status ?? 'pending'); 
                        $h->user_pust_status_histori = $h->user_pust_status ?? $h->USER_PUST_STATUS ?? null;
                        $h->keterangan_histori = $h->keterangan ?? null;
                        return $h;
                    })
                    ->where('id_aksara_dinamika_histori', $currentAksaraId)
                    ->filter(fn($h) => $h->tgl_status_parsed !== null)
                    ->sortByDesc('tgl_status_parsed')
                    ->first();
            }

            if ($latestHistori) {
                $peserta->STATUS = $latestHistori->status_histori;
                $peserta->ALASAN_PENOLAKAN = $latestHistori->keterangan_histori ?? null;
                $peserta->VALIDATOR_ID = $latestHistori->user_pust_status_histori ?? null;
                $peserta->TGL_VALIDASI = $latestHistori->tgl_status_parsed ? $latestHistori->tgl_status_parsed->translatedFormat('d F Y \p\u\k\u\l H:i') : null;

                if ($peserta->VALIDATOR_ID && $responseCivitas && !isset($responseCivitas['_error']) && is_array($responseCivitas)) {
                    $validatorData = collect($responseCivitas)->firstWhere('id_civitas', (string)$peserta->VALIDATOR_ID);
                    if ($validatorData) {
                        $peserta->NAMA_VALIDATOR = ((object)$validatorData)->nama ?? null;
                    }
                }
            } else {
                $peserta->STATUS = strtolower($item->status_validasi ?? $item->STATUS_VALIDASI ?? $item->submission_status ?? 'pending');
                $peserta->ALASAN_PENOLAKAN = $item->alasan_penolakan ?? $item->ALASAN_PENOLAKAN ?? null;
            }
            Log::info("[AKSARA_SHOW] Processed peserta object for ID {$id}: ", (array)$peserta);

        } catch (\Exception $e) {
            Log::error("[AKSARA_SHOW] General Exception saat mengambil detail submission ID {$id}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            abort(500, 'Terjadi kesalahan server umum.');
        }
        if (is_null($peserta)) { abort(404, 'Data peserta tidak ditemukan atau tidak dapat diproses.'); }

        return view('detailaksara', compact('peserta'));
    }

    public function setuju(Request $request, $id)
    {
        $authenticatedCivitas = Session::get('authenticated_civitas');
        $validatorId = $authenticatedCivitas['id_civitas'] ?? 'SYSTEM_VALIDATOR';

        if ($validatorId === 'SYSTEM_VALIDATOR') {
            Log::warning("[AKSARA_SETUJU] Tidak dapat menemukan ID Civitas validator dari session. Menggunakan fallback.");
        }

        $nextHistoriId = $this->apiService->getNextId('histori-status', 'id_histori_status');

        if ($nextHistoriId === null) {
            Log::error("[AKSARA_SETUJU] Gagal mendapatkan ID berikutnya untuk histori_status dari API.");
            return redirect()->route('validasi.aksara.detail', ['id' => $id] + $request->query())
                             ->with('error', "Gagal memproses persetujuan: Tidak bisa generate ID histori. Pastikan API histori-status berfungsi.");
        }

        $historiData = [
            'id_histori_status' => $nextHistoriId,
            'id_aksara_dinamika' => $id,
            'status' => 'diterima',
            'keterangan' => 'Karya disetujui oleh validator.',
            'tgl_status' => Carbon::now()->toDateTimeString(),
            'user' => $validatorId
        ];

        Log::info("[AKSARA_SETUJU] Mencoba menyetujui Aksara ID {$id} dengan data:", $historiData);
        $result = $this->apiService->createAksaraHistoriStatus($historiData);

        if ($result && !isset($result['_error']) && (isset($result['_success_no_content']) || ($result['success'] ?? false) === true || (isset($result['id_histori_status'])))) {
            Log::info("[AKSARA_SETUJU] Karya ID {$id} berhasil disetujui via API.");
            return redirect()->route('validasi.aksara.detail', ['id' => $id] + $request->query())
                             ->with('success', "Karya berhasil disetujui.");
        } else {
            Log::error("[AKSARA_SETUJU] Gagal membuat histori status 'diterima' via API untuk ID Aksara {$id}", $result ?? []);
            $apiErrorMessage = $result['_json_error_data']['message'] ?? ($result['_body'] ?? 'Error tidak diketahui dari API.');
            if (isset($result['_json_error_data']['errors'])) {
                $apiErrorMessage .= " Details: " . json_encode($result['_json_error_data']['errors']);
            }
            return redirect()->route('validasi.aksara.detail', ['id' => $id] + $request->query())
                             ->with('error', "Gagal menyetujui karya: " . $apiErrorMessage);
        }
    }

    public function tolak(Request $request, $id)
    {
        $request->validate([
            'alasan' => 'required|string|max:255',
        ]);

        $authenticatedCivitas = Session::get('authenticated_civitas');
        $validatorId = $authenticatedCivitas['id_civitas'] ?? 'SYSTEM_VALIDATOR';

        if ($validatorId === 'SYSTEM_VALIDATOR') {
            Log::warning("[AKSARA_TOLAK] Tidak dapat menemukan ID Civitas validator dari session. Menggunakan fallback.");
        }

        $nextHistoriId = $this->apiService->getNextId('histori-status', 'id_histori_status');

        if ($nextHistoriId === null) {
            Log::error("[AKSARA_TOLAK] Gagal mendapatkan ID berikutnya untuk histori_status dari API.");
            return redirect()->route('validasi.aksara.detail', ['id' => $id] + $request->query())
                             ->with('error', "Gagal memproses penolakan: Tidak bisa generate ID histori. Pastikan API histori-status berfungsi.");
        }

        $historiData = [
            'id_histori_status' => $nextHistoriId,
            'id_aksara_dinamika' => $id,
            'status' => 'ditolak',
            'keterangan' => $request->input('alasan'),
            'tgl_status' => Carbon::now()->toDateTimeString(),
            'user' => $validatorId
        ];

        Log::info("[AKSARA_TOLAK] Mencoba menolak Aksara ID {$id} dengan data:", $historiData);
        $result = $this->apiService->createAksaraHistoriStatus($historiData);

        if ($result && !isset($result['_error']) && (isset($result['_success_no_content']) || ($result['success'] ?? false) === true || (isset($result['id_histori_status'])))) {
            Log::info("[AKSARA_TOLAK] Karya ID {$id} berhasil ditolak via API.");
            return redirect()->route('validasi.aksara.detail', ['id' => $id] + $request->query())
                             ->with('success', "Karya berhasil ditolak dengan alasan tercatat.");
        } else {
            Log::error("[AKSARA_TOLAK] Gagal membuat histori status 'ditolak' via API untuk ID Aksara {$id}", $result ?? []);
            $apiErrorMessage = $result['_json_error_data']['message'] ?? ($result['_body'] ?? 'Error tidak diketahui dari API.');
            if (isset($result['_json_error_data']['errors'])) {
                $apiErrorMessage .= " Details: " . json_encode($result['_json_error_data']['errors']);
            }
            return redirect()->route('validasi.aksara.detail', ['id' => $id] + $request->query())
                             ->with('error', "Gagal menolak karya: " . $apiErrorMessage);
        }
    }
}