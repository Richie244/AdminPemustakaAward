<?php

namespace App\Http\Controllers;

use App\Services\MyApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class SertifikatTemplateController extends Controller
{
    protected MyApiService $apiService;
    protected string $dummyKegiatanIdForTemplate;
    protected string $globalTemplateNimIdentifier;

    public function __construct(MyApiService $apiService)
    {
        $this->apiService = $apiService;
        $this->dummyKegiatanIdForTemplate = config('app.dummy_kegiatan_id_for_template', '0'); //
        $this->globalTemplateNimIdentifier = config('app.global_template_nim_identifier', 'TPLGLB'); //
    }

    public function index()
    {
        $currentTemplate = null;
        $errorMessage = null; // Variabel untuk menyimpan pesan error spesifik

        Log::info('[SERTIFIKAT_TEMPLATE_INDEX] Memulai pengambilan template global. Dummy Kegiatan ID: ' . $this->dummyKegiatanIdForTemplate . ', NIM Identifier: ' . $this->globalTemplateNimIdentifier);

        try {
            // Coba filter di sisi API jika memungkinkan, jika tidak, filter setelahnya.
            // Jika API Anda tidak mendukung filter ini, Anda harus menghapusnya dari parameter.
            $apiParams = [
                'id_kegiatan' => $this->dummyKegiatanIdForTemplate,
                'nim' => $this->globalTemplateNimIdentifier,
                // Tambahkan parameter lain jika API Anda bisa memfilter lebih spesifik
                // 'exact_match[id_kegiatan]' => $this->dummyKegiatanIdForTemplate,
                // 'exact_match[nim]' => $this->globalTemplateNimIdentifier,
            ];
            
            // Untuk sementara, kita akan tetap filter di PHP karena tidak tahu kemampuan API
            $response = $this->apiService->getSertifikatList(); // Ambil semua dulu
            
            if ($response === null) {
                Log::error('[SERTIFIKAT_TEMPLATE_INDEX] API getSertifikatList mengembalikan null.');
                $errorMessage = 'Tidak ada respons dari server API template.';
            } elseif (isset($response['_error'])) {
                Log::error('[SERTIFIKAT_TEMPLATE_INDEX] API Error saat mengambil daftar sertifikat.', $response);
                $apiErrorBody = $response['_body'] ?? json_encode($response); // Ambil body error jika ada
                $errorMessage = 'Gagal memuat data template dari API: ' . ($response['_json_error_data']['message'] ?? Str::limit($apiErrorBody, 100));

            } elseif (is_array($response)) {
                $dataToFilter = isset($response['data']) && is_array($response['data']) ? $response['data'] : $response;

                $foundTemplateData = collect($dataToFilter)->first(function ($sertifikat) {
                    $s = (object) $sertifikat;
                    $isNimMatch = isset($s->nim) && strtoupper(trim((string)($s->nim))) === strtoupper($this->globalTemplateNimIdentifier);
                    $isKegiatanMatch = isset($s->id_kegiatan) && trim((string)($s->id_kegiatan)) === trim((string)$this->dummyKegiatanIdForTemplate);
                    return $isNimMatch && $isKegiatanMatch;
                });

                if ($foundTemplateData) {
                    $currentTemplate = (object) $foundTemplateData;
                    Log::info('[SERTIFIKAT_TEMPLATE_INDEX] Template global ditemukan:', (array)$currentTemplate);

                    $uploadDateSource = $currentTemplate->tgl_upload ?? $currentTemplate->TGL_UPLOAD ?? $currentTemplate->created_at ?? $currentTemplate->CREATED_AT ?? null;
                    try {
                        $currentTemplate->display_upload_date = $uploadDateSource ? Carbon::parse($uploadDateSource)->translatedFormat('d M Y H:i') : 'Tidak diketahui';
                    } catch (\Exception $e) {
                        $currentTemplate->display_upload_date = 'Format tanggal tidak valid';
                         Log::warning("[SERTIFIKAT_TEMPLATE_INDEX] Gagal parse tanggal upload: " . $uploadDateSource);
                    }
                    
                    $currentTemplate->nama_template_display = $currentTemplate->nama_template_deskriptif ?? $currentTemplate->NAMA_TEMPLATE_DESKRIPTIF ?? $currentTemplate->nama_template ?? 'Template Global Utama';
                    $currentTemplate->id_sertifikat_for_route = $currentTemplate->id_sertifikat ?? $currentTemplate->id ?? $currentTemplate->ID_SERTIFIKAT ?? null;

                } else {
                    Log::info('[SERTIFIKAT_TEMPLATE_INDEX] Tidak ada template global yang cocok ditemukan dalam respons API.');
                    // Tidak set error message di sini, karena ini kondisi valid jika belum ada template
                }
            } else {
                Log::error('[SERTIFIKAT_TEMPLATE_INDEX] Respons API tidak terduga (bukan array dan bukan error yang dikenal).', ['response' => $response]);
                $errorMessage = 'Format respons API template tidak valid.';
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('[SERTIFIKAT_TEMPLATE_INDEX] ConnectionException saat mengambil template sertifikat: ' . $e->getMessage());
            $errorMessage = 'Tidak dapat terhubung ke server API template.';
        } catch (\Exception $e) {
            Log::error('[SERTIFIKAT_TEMPLATE_INDEX] Exception umum saat mengambil template sertifikat: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $errorMessage = 'Terjadi kesalahan sistem saat memuat template.';
        }

        if ($errorMessage) {
            session()->flash('error', $errorMessage);
        }

        return view('sertifikat-template', compact('currentTemplate'));
    }

    public function store(Request $request)
    {
        if (!isset($this->apiService)) {
            Log::critical('MyApiService tidak terinisialisasi di SertifikatTemplateController@store');
            return back()->with('error', 'Kesalahan konfigurasi layanan API.')->withInput();
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'nama_template' => 'required|string|max:100|regex:/^[a-zA-Z0-9\s\-_.]+$/', // Izinkan titik juga
            'file_template' => 'required|file|mimes:jpg,jpeg,png|max:2048', // Hanya JPG, JPEG, PNG
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $file = $request->file('file_template');
        $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $fileExtension = $file->getClientOriginalExtension();
        
        // Buat nama file yang lebih unik dan pendek jika perlu, tapi usahakan deskriptif
        $safeOriginalName = Str::slug($originalFileName);
        $timestamp = time();
        // Maksimal panjang NAMA_FILE di Oracle bisa jadi kendala. Misal 30 char.
        // "tpl-global-<timestamp>.<ext>" -> "tpl-global-1621392000.jpg" (sekitar 25 char)
        $maxLength = 25 - (strlen($fileExtension) + 1); // sisa untuk nama dasar
        $baseName = "tpl-glbl-" . substr($safeOriginalName, 0, $maxLength - 10) . "-" . $timestamp;
        $fileName = Str::limit($baseName, $maxLength, '') . '.' . $fileExtension;


        Log::info('[SERTIFIKAT_TEMPLATE_STORE] Nama file yang di-generate: ' . $fileName);
        
        // Hapus template global lama (record API dan file fisik) jika ada
        // Gunakan logika yang sama seperti di index() untuk menemukan template lama
        $responseOldTemplates = $this->apiService->getSertifikatList(); // Bisa juga difilter jika API support
        if ($responseOldTemplates && !isset($responseOldTemplates['_error']) && is_array($responseOldTemplates)) {
            $dataToFilter = isset($responseOldTemplates['data']) && is_array($responseOldTemplates['data']) ? $responseOldTemplates['data'] : $responseOldTemplates;
            $oldGlobalTemplateData = collect($dataToFilter)->first(function ($sertifikat) {
                $s = (object) $sertifikat;
                return (isset($s->nim) && strtoupper(trim((string)($s->nim))) === strtoupper($this->globalTemplateNimIdentifier)) &&
                       (isset($s->id_kegiatan) && trim((string)($s->id_kegiatan)) === trim((string)$this->dummyKegiatanIdForTemplate));
            });

            if ($oldGlobalTemplateData) {
                $oldGlobalTemplate = (object) $oldGlobalTemplateData;
                $oldTemplateId = $oldGlobalTemplate->id_sertifikat ?? $oldGlobalTemplate->id ?? $oldGlobalTemplate->ID_SERTIFIKAT ?? null;
                if ($oldTemplateId) {
                    Log::info('[SERTIFIKAT_TEMPLATE_STORE] Menghapus template global lama ID: ' . $oldTemplateId);
                    $oldNamaFile = $oldGlobalTemplate->nama_file ?? $oldGlobalTemplate->NAMA_FILE ?? null;
                    if ($oldNamaFile) {
                        Storage::disk('public')->delete('templates_sertifikat/' . $oldNamaFile);
                        Log::info('[SERTIFIKAT_TEMPLATE_STORE] File fisik lama dihapus: ' . $oldNamaFile);
                    }
                    $deleteResult = $this->apiService->deleteSertifikat($oldTemplateId);
                    if (!$deleteResult || isset($deleteResult['_error'])) {
                        Log::warning('[SERTIFIKAT_TEMPLATE_STORE] Gagal menghapus record template lama dari API.', $deleteResult ?? []);
                        // Pertimbangkan apakah akan melanjutkan atau mengembalikan error
                    }
                }
            }
        }

        $path = $file->storeAs('templates_sertifikat', $fileName, 'public');

        if ($path) {
            $nextIdSertifikat = $this->apiService->getNextId('sertifikat', 'id_sertifikat');
            
            if ($nextIdSertifikat === null) {
                Storage::disk('public')->delete($path);
                Log::error('[SERTIFIKAT_TEMPLATE_STORE] Gagal men-generate ID unik untuk template sertifikat.');
                return back()->with('error', 'Gagal men-generate ID unik untuk template sertifikat. Pastikan API endpoint untuk getNextId (/sertifikat) berfungsi.')->withInput();
            }

            $dataApi = [
                'id' => $nextIdSertifikat,
                'nim' => $this->globalTemplateNimIdentifier,
                'id_kegiatan' => $this->dummyKegiatanIdForTemplate,
                'nama_file' => $fileName,
                'nama_template_deskriptif' => $request->input('nama_template'), // Tambahkan nama deskriptif
                'tgl_upload' => Carbon::now()->toDateTimeString(), // Tambahkan tanggal upload
            ];

            Log::info('[SERTIFIKAT_TEMPLATE_STORE] Data yang akan dikirim ke API createSertifikat:', $dataApi);
            $result = $this->apiService->createSertifikat($dataApi);

            if ($result && !isset($result['_error']) && (($result['success'] ?? false) === true || isset($result['id']) || isset($result['id_sertifikat']) || isset($result['_success_no_content']))) {
                return redirect()->route('sertifikat-templates.index')->with('success', 'Template sertifikat global berhasil diunggah/diperbarui.');
            } else {
                Storage::disk('public')->delete($path);
                Log::error('[SERTIFIKAT_TEMPLATE_STORE] Gagal menyimpan data template sertifikat ke API', ['payload_sent' => $dataApi, 'api_response' => $result ?? []]);
                $apiMessage = $result['_json_error_data']['message'] ?? ($result['_body'] ?? 'Error API tidak diketahui');
                 if (isset($result['_json_error_data']['errors'])) {
                    $apiMessage .= " Details: " . json_encode($result['_json_error_data']['errors']);
                }
                return back()->with('error', 'Gagal menyimpan template sertifikat ke API: ' . $apiMessage)->withInput();
            }
        }
        return back()->with('error', 'Gagal mengunggah file template.')->withInput();
    }

    // ... (destroy, show, edit, update methods)
    public function destroy($id_template) 
    {
        if (!isset($this->apiService)) {
            Log::critical('MyApiService tidak terinisialisasi di SertifikatTemplateController@destroy');
            return redirect()->route('sertifikat-templates.index')->with('error', 'Kesalahan konfigurasi layanan API.');
        }

        Log::info("[SERTIFIKAT_TEMPLATE_DESTROY] Mencoba menghapus template global ID (sertifikat): {$id_template}");
        
        $templateData = null;
        $sertifikatList = $this->apiService->getSertifikatList(); 
        if ($sertifikatList && !isset($sertifikatList['_error']) && is_array($sertifikatList)) {
            $dataToFilter = isset($sertifikatList['data']) && is_array($sertifikatList['data']) ? $sertifikatList['data'] : $sertifikatList;
            $found = collect($dataToFilter)->first(function ($sertifikat) use ($id_template) {
                $s = (object) $sertifikat;
                $apiId = $s->id_sertifikat ?? $s->id ?? $s->ID_SERTIFIKAT ?? null;
                return (string)$apiId == (string)$id_template && 
                       (isset($s->nim) && strtoupper(trim((string)($s->nim))) === strtoupper($this->globalTemplateNimIdentifier)) &&
                       (isset($s->id_kegiatan) && trim((string)($s->id_kegiatan)) === trim((string)$this->dummyKegiatanIdForTemplate));
            });
            if ($found) {
                $templateData = (object) $found;
            }
        }

        if (!$templateData || !(isset($templateData->nama_file) || isset($templateData->NAMA_FILE)) ) {
            Log::error("[SERTIFIKAT_TEMPLATE_DESTROY] Template global ID: {$id_template} tidak ditemukan atau tidak memiliki nama file.");
            return redirect()->route('sertifikat-templates.index')->with('error', 'Template global tidak ditemukan atau data tidak lengkap.');
        }
        
        $namaFileToDelete = $templateData->nama_file ?? $templateData->NAMA_FILE ?? null;
        $idToDeleteApi = $templateData->id_sertifikat ?? $templateData->id ?? $templateData->ID_SERTIFIKAT ?? $id_template;
        
        $result = $this->apiService->deleteSertifikat($idToDeleteApi); 

        if ($result && !isset($result['_error']) && (($result['success'] ?? false) === true || isset($result['_success_no_content']))) {
            if ($namaFileToDelete) {
                Storage::disk('public')->delete('templates_sertifikat/' . $namaFileToDelete);
                Log::info('[SERTIFIKAT_TEMPLATE_DESTROY] Template sertifikat global dan file fisik berhasil dihapus: ' . $namaFileToDelete);
            } else {
                Log::warning('[SERTIFIKAT_TEMPLATE_DESTROY] Record API template global dihapus, namun tidak ada nama file untuk dihapus dari storage.');
            }
            return redirect()->route('sertifikat-templates.index')->with('success', 'Template sertifikat global berhasil dihapus.');
        } else {
            Log::error('[SERTIFIKAT_TEMPLATE_DESTROY] Gagal menghapus template sertifikat global dari API', $result ?? []);
            $apiMessage = $result['_json_error_data']['message'] ?? ($result['_body'] ?? 'Error API tidak diketahui');
            return redirect()->route('sertifikat-templates.index')->with('error', 'Gagal menghapus template global: ' . $apiMessage);
        }
    }
    
    public function show(string $id) { return redirect()->route('sertifikat-templates.index'); }
    public function edit(string $id) { return redirect()->route('sertifikat-templates.index')->with('info', 'Gunakan form di halaman ini untuk mengganti template global.'); }
    public function update(Request $request, string $id) { return $this->store($request); }


}