<?php

namespace App\Http\Controllers;

use App\Services\MyApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PerusahaanController extends Controller
{
    protected MyApiService $apiService;

    public function __construct(MyApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    protected function paginate(Collection $items, $perPage = 15, $page = null, $options = [])
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
        Log::info('[PERUSAHAAN_INDEX] Memuat daftar perusahaan.', $request->all());
        $searchTerm = $request->input('search');
        $perPage = 15;
        $currentPage = Paginator::resolveCurrentPage('page') ?: 1;

        $perusahaanListPaginator = new LengthAwarePaginator(
            new Collection(), 0, $perPage, $currentPage,
            ['path' => route('master-perusahaan.index'), 'query' => $request->query()]
        );
        $error_message = null;

        try {
            $apiParams = $searchTerm ? ['search' => $searchTerm] : [];
            $responsePerusahaan = $this->apiService->getPerusahaanPemateriList($apiParams);
            Log::info('[PERUSAHAAN_INDEX] Respons dari getPerusahaanPemateriList:', is_array($responsePerusahaan) ? $responsePerusahaan : ['response_type' => gettype($responsePerusahaan)]);

            $perusahaanCollection = new Collection();

            if ($responsePerusahaan && !isset($responsePerusahaan['_error']) && !isset($responsePerusahaan['_success_no_content'])) {
                $dataFromApi = isset($responsePerusahaan['data']) && is_array($responsePerusahaan['data']) ? $responsePerusahaan['data'] : (is_array($responsePerusahaan) ? $responsePerusahaan : []);

                if (!empty($dataFromApi)) {
                    $perusahaanCollection = collect($dataFromApi)->map(function ($item) {
                        $perusahaanObj = (object) $item;
                        $perusahaanObj->id_perusahaan = $perusahaanObj->id_perusahaan ?? $perusahaanObj->ID_PERUSAHAAN ?? $perusahaanObj->id ?? null;
                        $perusahaanObj->nama_perusahaan = $perusahaanObj->nama_perusahaan ?? $perusahaanObj->NAMA_PERUSAHAAN ?? $perusahaanObj->nama ?? 'Nama Tidak Ada';
                        $perusahaanObj->alamat_perusahaan = $perusahaanObj->alamat_perusahaan ?? $perusahaanObj->ALAMAT_PERUSAHAAN ?? $perusahaanObj->alamat ?? null;
                        $perusahaanObj->kota_perusahaan = $perusahaanObj->kota_perusahaan ?? $perusahaanObj->KOTA_PERUSAHAAN ?? null;
                        $perusahaanObj->email_perusahaan = $perusahaanObj->email_perusahaan ?? $perusahaanObj->EMAIL_PERUSAHAAN ?? null;
                        $perusahaanObj->telp_perusahaan = $perusahaanObj->telp_perusahaan ?? $perusahaanObj->TELP_PERUSAHAAN ?? null;
                        $perusahaanObj->contact_person_perusahaan = $perusahaanObj->contact_person_perusahaan ?? $perusahaanObj->CONTACT_PERSON_PERUSAHAAN ?? null;
                        return $perusahaanObj;
                    })->filter(fn($p) => $p->id_perusahaan !== null);
                }
                Log::info('[PERUSAHAAN_INDEX] Koleksi perusahaan setelah map:', $perusahaanCollection->toArray());
            } elseif ($responsePerusahaan && isset($responsePerusahaan['_error'])) {
                Log::error('[PERUSAHAAN_INDEX] API Error saat mengambil daftar perusahaan.', $responsePerusahaan);
                $error_message = $responsePerusahaan['_json_error_data']['message'] ?? ($responsePerusahaan['_body'] ?? 'Gagal memuat data perusahaan dari API.');
            } elseif (isset($responsePerusahaan['_success_no_content'])) {
                Log::info('[PERUSAHAAN_INDEX] API perusahaan mengembalikan success_no_content.');
                 $error_message = 'Tidak ada data perusahaan yang ditemukan.';
            } else {
                 Log::warning('[PERUSAHAAN_INDEX] Respons tidak valid dari API perusahaan.', (array)$responsePerusahaan);
                 if (empty($error_message)) {
                    $error_message = 'Tidak ada data perusahaan atau respons API tidak valid.';
                 }
            }

            if ($searchTerm && $perusahaanCollection->isNotEmpty()) {
                $perusahaanCollection = $perusahaanCollection->filter(function ($perusahaan) use ($searchTerm) {
                    $namaMatch = isset($perusahaan->nama_perusahaan) && stripos($perusahaan->nama_perusahaan, $searchTerm) !== false;
                    $alamatMatch = isset($perusahaan->alamat_perusahaan) && stripos($perusahaan->alamat_perusahaan, $searchTerm) !== false;
                    $emailMatch = isset($perusahaan->email_perusahaan) && stripos($perusahaan->email_perusahaan, $searchTerm) !== false;
                    $kontakMatch = isset($perusahaan->kontak_perusahaan) && stripos($perusahaan->kontak_perusahaan, $searchTerm) !== false;
                    $cpMatch = isset($perusahaan->contact_person_perusahaan) && stripos($perusahaan->contact_person_perusahaan, $searchTerm) !== false;
                    return $namaMatch || $alamatMatch || $emailMatch || $kontakMatch || $cpMatch;
                });
            }

            $perusahaanCollection = $perusahaanCollection->sortBy('nama_perusahaan')->values();
            $currentPageItems = $perusahaanCollection->slice(($currentPage - 1) * $perPage, $perPage)->values()->all();

            $perusahaanListPaginator = new LengthAwarePaginator(
                $currentPageItems,
                $perusahaanCollection->count(),
                $perPage,
                $currentPage,
                ['path' => route('master-perusahaan.index'), 'query' => $request->query()]
            );

        } catch (\Exception $e) {
            Log::error('[PERUSAHAAN_INDEX] General Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $error_message = 'Terjadi kesalahan sistem saat memuat data perusahaan.';
        }

        return view('perusahaan', [
            'perusahaanList' => $perusahaanListPaginator,
            'searchTerm' => $searchTerm,
            'error_message' => $error_message
        ]);
    }

    public function create()
    {
        Log::info('[PERUSAHAAN_CREATE] Menampilkan form tambah perusahaan.');
        
        $kotaList = [];
        $error_message_kota = null;

        try {
            $responseKota = $this->apiService->getKotaList();

            if ($responseKota && !isset($responseKota['_error']) && isset($responseKota['data'])) {
                // Konversi data kota menjadi collection object
                $kotaList = collect($responseKota['data'])->map(fn($item) => (object)$item);
            } else {
                $error_message_kota = 'Gagal memuat daftar kota dari API.';
                Log::error('[PERUSAHAAN_CREATE] Gagal mengambil daftar kota.', $responseKota ?? []);
            }

        } catch (\Exception $e) {
            $error_message_kota = 'Terjadi kesalahan sistem saat memuat daftar kota.';
            Log::error('[PERUSAHAAN_CREATE] Exception saat mengambil daftar kota: ' . $e->getMessage());
        }

        // Kirimkan $kotaList dan pesan error (jika ada) ke view
        return view('tambah-perusahaan', [
            'kotaList' => $kotaList,
            'error_message_kota' => $error_message_kota
        ]);
    }


    // app/Http/Controllers/PerusahaanController.php

    public function store(Request $request)
    {
        Log::info('[PERUSAHAAN_STORE_START] Menerima request untuk menyimpan perusahaan baru.', $request->all());

        // --- AWAL PERBAIKAN ---
        // Sesuaikan aturan validasi ini agar cocok dengan nama input di form Anda
        // dan juga apa yang diharapkan oleh API Controller.
        $validator = Validator::make($request->all(), [
            'nama'   => 'required|string|max:100',
            'alamat' => 'nullable|string|max:255',
            'kota'   => 'required|numeric', // API mengharapkan ID kota, jadi validasinya numeric
            'email'  => 'nullable|email|max:100',
            'telp'   => 'nullable|string|max:50',
            'kontak' => 'nullable|string|max:100',
        ]);
        // --- AKHIR PERBAIKAN ---

        if ($validator->fails()) {
            Log::warning('[PERUSAHAAN_STORE_VALIDATION_FAIL]', $validator->errors()->toArray());
            return redirect()->route('master-perusahaan.create')
                ->withErrors($validator)
                ->withInput();
        }

        $nextPerusahaanId = $this->apiService->getNextId('perusahaan', 'ID_PERUSAHAAN');
        if ($nextPerusahaanId === null) {
            Log::error('[PERUSAHAAN_STORE] Gagal men-generate ID Perusahaan.');
            return redirect()->route('master-perusahaan.create')->with('error', 'Gagal generate ID Perusahaan. Periksa koneksi atau log API.')->withInput();
        }

        // Array data yang dikirim ke API sudah benar dari jawaban sebelumnya
        $dataPerusahaan = [
            'id'     => $nextPerusahaanId,
            'nama'   => $request->input('nama'),
            'alamat' => $request->input('alamat'),
            'kota'   => $request->input('kota'),
            'email'  => $request->input('email'),
            'telp'   => $request->input('telp'),
            'kontak' => $request->input('kontak'),
        ];

        $resultPerusahaan = $this->apiService->createPerusahaanPemateri($dataPerusahaan);

        if ($resultPerusahaan && !isset($resultPerusahaan['_error']) && ($resultPerusahaan['success'] ?? false) === true) {
            Log::info('[PERUSAHAAN_STORE_SUCCESS] Perusahaan berhasil disimpan.', ['request_data' => $dataPerusahaan, 'api_response' => $resultPerusahaan]);
            return redirect()->route('master-perusahaan.index')->with('success', 'Perusahaan baru berhasil ditambahkan.');
        } else {
            $apiErrorMsgArr = $resultPerusahaan['errors'] ?? ($resultPerusahaan['message'] ?? ($resultPerusahaan['_body'] ?? 'Error API saat membuat perusahaan.'));
            $apiErrorMsg = is_array($apiErrorMsgArr) ? json_encode($apiErrorMsgArr) : $apiErrorMsgArr;
            Log::error('[PERUSAHAAN_STORE_API_FAIL] Gagal menyimpan perusahaan.', ['request_data' => $dataPerusahaan, 'response' => $resultPerusahaan]);
            return redirect()->route('master-perusahaan.create')->with('error', 'Gagal menyimpan perusahaan: ' . $apiErrorMsg)->withInput();
        }
    }

    public function destroy(string $id)
    {
        Log::info("[PERUSAHAAN_DESTROY] Memulai penghapusan untuk ID Perusahaan: {$id}");
        try {
            $response = $this->apiService->deletePerusahaanPemateri($id); // deletePerusahaanPemateri mengarah ke endpoint /perusahaan/{id}

            if ($response && !isset($response['_error']) && (isset($response['success']) && $response['success'] === true || isset($response['_success_no_content']))) {
                Log::info("[PERUSAHAAN_DESTROY] Perusahaan ID: {$id} berhasil dihapus via API.");
                return redirect()->route('master-perusahaan.index')->with('success', 'Perusahaan berhasil dihapus.');
            } else {
                $errorMessage = $response['_json_error_data']['message'] ?? ($response['message'] ?? ($response['_body'] ?? 'Gagal menghapus perusahaan. Error tidak diketahui dari API.'));
                Log::error("[PERUSAHAAN_DESTROY] Gagal menghapus perusahaan ID: {$id} via API.", ['response' => $response]);
                return redirect()->route('master-perusahaan.index')->with('error', $errorMessage);
            }
        } catch (\Exception $e) {
            Log::error("[PERUSAHAAN_DESTROY] Exception saat menghapus perusahaan ID: {$id}", ['exception' => $e]);
            return redirect()->route('master-perusahaan.index')->with('error', 'Terjadi kesalahan sistem saat menghapus perusahaan.');
        }
    }
}