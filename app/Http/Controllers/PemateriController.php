<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MyApiService; // Pastikan namespace ini benar
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator; // Menggunakan Validator standar Laravel
use Illuminate\Support\MessageBag;

class PemateriController extends Controller
{
    protected MyApiService $apiService;

    public function __construct(MyApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Log::info('[PEMATERI_INDEX] Memuat daftar pemateri.', $request->all());
        $pemateriList = [];
        $error = null;
        $searchTerm = $request->input('search');
        $perPage = 15; // Jumlah item per halaman

        try {
            // Asumsi MyApiService memiliki metode getPemateriList()
            // Jika API mendukung pencarian, kirim searchTerm
            $apiParams = $searchTerm ? ['search' => $searchTerm] : [];
            $response = $this->apiService->getPemateriList($apiParams); // Anda perlu membuat metode ini di MyApiService

            if ($response && !isset($response['_error'])) {
                $dataFromApi = isset($response['data']) && is_array($response['data']) ? $response['data'] : $response;
                if (is_array($dataFromApi)) {
                    $pemateriCollection = collect($dataFromApi)->map(fn($item) => (object) $item);
                    
                    // Jika API tidak melakukan search, filter di sini
                    // if ($searchTerm && !$apiParams) { // Jika search dilakukan di sini
                    //     $pemateriCollection = $pemateriCollection->filter(function ($pemateri) use ($searchTerm) {
                    //         return stripos($pemateri->NAMA_PEMATERI ?? $pemateri->nama_pemateri ?? '', $searchTerm) !== false ||
                    //                stripos($pemateri->EMAIL ?? $pemateri->email ?? '', $searchTerm) !== false;
                    //     });
                    // }

                    // Pagination manual
                    $currentPage = Paginator::resolveCurrentPage() ?: 1;
                    $currentPageItems = $pemateriCollection->slice(($currentPage - 1) * $perPage, $perPage)->all();
                    $pemateriList = new \Illuminate\Pagination\LengthAwarePaginator(
                        $currentPageItems,
                        $pemateriCollection->count(),
                        $perPage,
                        $currentPage,
                        ['path' => $request->url(), 'query' => $request->query()]
                    );

                } else {
                    Log::warning('[PEMATERI_INDEX] Data pemateri dari API bukan array.', (array)$response);
                    $error = 'Format data pemateri dari API tidak sesuai.';
                }
            } elseif ($response && isset($response['_error'])) {
                Log::error('[PEMATERI_INDEX] API Error saat mengambil daftar pemateri.', $response);
                $error = $response['_json_error_data']['message'] ?? ($response['_body'] ?? 'Gagal memuat data pemateri dari API.');
            } else {
                Log::warning('[PEMATERI_INDEX] Respons tidak valid atau kosong dari API getPemateriList.', (array)$response);
                $error = 'Tidak ada data pemateri atau respons API tidak valid.';
            }
        } catch (\Exception $e) {
            Log::error('[PEMATERI_INDEX] Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $error = 'Terjadi kesalahan sistem saat memuat data pemateri.';
        }

        // Pastikan view 'master-pemateri.index' ada
        return view('master-pemateri.index', [
            'pemateriList' => $pemateriList,
            'searchTerm' => $searchTerm,
            'error' => $error
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Anda mungkin perlu mengirim data master perusahaan jika pemateri eksternal bisa memilih dari daftar perusahaan yang ada
        // $perusahaanList = $this->apiService->getPerusahaanList(); // Buat metode ini jika perlu
        // return view('tambah-pemateri', compact('perusahaanList'));
        return view('tambah-pemateri'); // Nama file Blade Anda
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info('[PEMATERI_STORE_START] Menerima request untuk menyimpan pemateri baru.', $request->all());

        $validator = Validator::make($request->all(), [
            'nama_pemateri' => 'required|string|max:100',
            'email_pemateri' => 'nullable|email|max:100',
            'no_hp_pemateri' => 'nullable|string|max:20',
            'jenis_pemateri' => 'required|in:internal,eksternal',
            // Validasi untuk pemateri eksternal
            'nama_perusahaan' => 'required_if:jenis_pemateri,eksternal|nullable|string|max:100',
            'alamat_perusahaan' => 'nullable|string|max:200',
            'kota_perusahaan' => 'nullable|string|max:50',
            'email_perusahaan' => 'nullable|email|max:100',
            'telp_perusahaan' => 'nullable|string|max:20',
            'contact_person_perusahaan' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            Log::warning('[PEMATERI_STORE_VALIDATION_FAIL]', $validator->errors()->toArray());
            return redirect()->route('master-pemateri.create')
                ->withErrors($validator)
                ->withInput();
        }

        $idPerusahaan = null;
        if ($request->input('jenis_pemateri') === 'eksternal' && $request->filled('nama_perusahaan')) {
            // Logika untuk mendapatkan atau membuat ID Perusahaan
            // Ini adalah contoh, Anda perlu menyesuaikannya dengan API Anda
            $nextPerusahaanId = $this->apiService->getNextId('perusahaan_pemateri_pust', 'id_perusahaan'); // Sesuaikan endpoint & kolom
            if ($nextPerusahaanId === null) {
                return redirect()->route('master-pemateri.create')->with('error', 'Gagal generate ID Perusahaan.')->withInput();
            }
            $dataPerusahaan = [
                'id_perusahaan' => $nextPerusahaanId, // Atau 'id' jika API mengharapkan itu
                'nama_perusahaan' => $request->input('nama_perusahaan'),
                'alamat_perusahaan' => $request->input('alamat_perusahaan'),
                'kota_perusahaan' => $request->input('kota_perusahaan'),
                'email_perusahaan' => $request->input('email_perusahaan'),
                'telp_perusahaan' => $request->input('telp_perusahaan'),
                'contact_person_perusahaan' => $request->input('contact_person_perusahaan'),
            ];
            // Anda perlu metode createPerusahaan di MyApiService yang memanggil API untuk insert perusahaan
            $resultPerusahaan = $this->apiService->createPerusahaanPemateri($dataPerusahaan); // Buat metode ini
            
            if (!$this->isApiCallSuccessful($resultPerusahaan, $this->apiService->getPrimaryKeyName('perusahaan_pemateri_pust'), 'createPerusahaanPemateri')) {
                 $apiErrorMsg = $resultPerusahaan['_json_error_data']['message'] ?? ($resultPerusahaan['message'] ?? ($resultPerusahaan['_body'] ?? 'Error API saat membuat perusahaan.'));
                return redirect()->route('master-pemateri.create')->with('error', 'Gagal menyimpan data perusahaan: ' . $apiErrorMsg)->withInput();
            }
            $idPerusahaan = $nextPerusahaanId; // atau $resultPerusahaan['id_perusahaan'] jika API mengembalikan
        }

        $nextPemateriId = $this->apiService->getNextId('pematerikegiatan_pust', 'id_pemateri'); // Sesuaikan endpoint & kolom
        if ($nextPemateriId === null) {
            return redirect()->route('master-pemateri.create')->with('error', 'Gagal generate ID Pemateri.')->withInput();
        }

        $dataPemateri = [
            'id_pemateri' => $nextPemateriId, // Atau 'id' jika API mengharapkan itu
            'nama_pemateri' => $request->input('nama_pemateri'),
            'email' => $request->input('email_pemateri'),
            'no_hp' => $request->input('no_hp_pemateri'),
            'id_perusahaan' => $idPerusahaan, // Akan null jika internal
            // Tambahkan field lain yang mungkin dibutuhkan oleh tabel PEMATERIKEGIATAN_PUST
        ];

        // Anda perlu metode createPemateri di MyApiService
        $resultPemateri = $this->apiService->createPemateri($dataPemateri); // Buat metode ini

        if ($this->isApiCallSuccessful($resultPemateri, $this->apiService->getPrimaryKeyName('pematerikegiatan_pust'), 'createPemateri')) {
            Log::info('[PEMATERI_STORE_SUCCESS] Pemateri berhasil disimpan.', $dataPemateri);
            return redirect()->route('master-pemateri.index')->with('success', 'Pemateri baru berhasil ditambahkan.');
        } else {
            Log::error('[PEMATERI_STORE_API_FAIL] Gagal menyimpan pemateri.', $resultPemateri ?? []);
            $apiErrorMsg = $resultPemateri['_json_error_data']['message'] ?? ($resultPemateri['message'] ?? ($resultPemateri['_body'] ?? 'Error API saat membuat pemateri.'));
            return redirect()->route('master-pemateri.create')->with('error', 'Gagal menyimpan pemateri: ' . $apiErrorMsg)->withInput();
        }
    }

    // Metode show, edit, update, destroy bisa ditambahkan di sini jika diperlukan
    // public function show($id) {}
    // public function edit($id) {}
    // public function update(Request $request, $id) {}
    // public function destroy($id) {}
}
