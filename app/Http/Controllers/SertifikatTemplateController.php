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

    // Di dalam __construct()
    public function __construct(MyApiService $apiService)
    {
        $this->apiService = $apiService;
        $this->dummyKegiatanIdForTemplate = config('app.dummy_kegiatan_id_for_template', '0');
        // $this->globalTemplateNimIdentifier = 'TPLGLB'; // Baris lama
        $this->globalTemplateNimIdentifier = config('app.global_template_nim_identifier', 'TPLGLB'); // Baru
    }

    public function index()
    {
        $currentTemplate = null;
        Log::info('[SERTIFIKAT_TEMPLATE_INDEX] Memulai pengambilan template global. Dummy Kegiatan ID: ' . $this->dummyKegiatanIdForTemplate . ', NIM Identifier: ' . $this->globalTemplateNimIdentifier);

        try {
            $response = $this->apiService->getSertifikatList(); 
            
            if ($response && !isset($response['_error']) && is_array($response)) {
                $foundTemplate = collect($response)->first(function ($sertifikat) {
                    $s = (object) $sertifikat;
                    return (isset($s->nim) && strtoupper($s->nim) === strtoupper($this->globalTemplateNimIdentifier)) &&
                           (isset($s->id_kegiatan) && (string)$s->id_kegiatan === $this->dummyKegiatanIdForTemplate);
                });

                if ($foundTemplate) {
                    $currentTemplate = (object) $foundTemplate;
                    Log::info('[SERTIFIKAT_TEMPLATE_INDEX] Template global ditemukan:', (array)$currentTemplate);

                    $uploadDate = $currentTemplate->tgl_upload ?? $currentTemplate->TGL_UPLOAD ?? $currentTemplate->created_at ?? $currentTemplate->CREATED_AT ?? null;
                    $currentTemplate->display_upload_date = $uploadDate ? Carbon::parse($uploadDate)->translatedFormat('d M Y H:i') : 'Tidak diketahui';
                    
                    $currentTemplate->nama_template_display = $currentTemplate->nama_template_deskriptif ?? 'Template Global Utama';
                    $currentTemplate->id_sertifikat_for_route = $currentTemplate->id_sertifikat ?? $currentTemplate->id ?? null;

                } else {
                    Log::info('[SERTIFIKAT_TEMPLATE_INDEX] Tidak ada template global yang cocok ditemukan.');
                }
            } else {
                Log::error('[SERTIFIKAT_TEMPLATE_INDEX] Gagal mengambil daftar sertifikat dari API.', $response ?? ['reason' => 'Response null atau error flag']);
                session()->flash('error', 'Gagal memuat data template dari server.');
            }
        } catch (\Exception $e) {
            Log::error('[SERTIFIKAT_TEMPLATE_INDEX] Exception saat mengambil template sertifikat: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan sistem saat memuat template.');
        }
        return view('sertifikat-template', compact('currentTemplate')); 
    }

    public function create()
    {
        return redirect()->route('sertifikat-templates.index'); 
    }

    public function store(Request $request)
    {
        if (!isset($this->apiService)) {
            Log::critical('MyApiService tidak terinisialisasi di SertifikatTemplateController@store');
            return back()->with('error', 'Kesalahan konfigurasi layanan API.')->withInput();
        }

        $request->validate([
            'nama_template' => 'required|string|max:100|regex:/^[a-zA-Z0-9\s\-_]+$/', 
            'file_template' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048', 
        ]);

        $file = $request->file('file_template');
        
        // WORKAROUND: Buat nama file yang sangat pendek untuk pengujian
        $fileExtension = $file->getClientOriginalExtension();
        $fileName = 'tpl_' . time() . '.' . $fileExtension; // Contoh: tpl_1621392000.jpg (sekitar 18-20 char)
        // Jika NAMA_FILE di Oracle sangat pendek (misal 7 char), ini masih bisa terlalu panjang.
        // Coba nama yang lebih pendek lagi jika perlu:
        // $fileName = Str::random(3) . '.' . $fileExtension; // Contoh: abc.jpg (7 char)

        Log::info('[SERTIFIKAT_TEMPLATE_STORE] Nama file yang di-generate (workaround): ' . $fileName);
        
        // Hapus template global lama (record API dan file fisik) jika ada
        $responseOldTemplates = $this->apiService->getSertifikatList();
        if ($responseOldTemplates && !isset($responseOldTemplates['_error']) && is_array($responseOldTemplates)) {
            $oldGlobalTemplate = collect($responseOldTemplates)->first(function ($sertifikat) {
                $s = (object) $sertifikat;
                return isset($s->nim) && strtoupper($s->nim) === strtoupper($this->globalTemplateNimIdentifier) &&
                       isset($s->id_kegiatan) && (string)$s->id_kegiatan === $this->dummyKegiatanIdForTemplate;
            });

            if ($oldGlobalTemplate) {
                $oldTemplateId = $oldGlobalTemplate->id_sertifikat ?? $oldGlobalTemplate->id ?? null;
                if ($oldTemplateId) {
                    Log::info('[SERTIFIKAT_TEMPLATE_STORE] Menghapus template global lama ID: ' . $oldTemplateId);
                    if (isset($oldGlobalTemplate->nama_file) && !empty($oldGlobalTemplate->nama_file)) {
                        Storage::disk('public')->delete('templates_sertifikat/' . $oldGlobalTemplate->nama_file);
                    }
                    $this->apiService->deleteSertifikat($oldTemplateId); 
                }
            }
        }

        $path = $file->storeAs('templates_sertifikat', $fileName, 'public'); 

        if ($path) {
            $nextIdSertifikat = $this->apiService->getNextId('sertifikat', 'id_sertifikat'); 
            
            if ($nextIdSertifikat === null) { 
                Storage::disk('public')->delete($path); 
                return back()->with('error', 'Gagal men-generate ID unik untuk template sertifikat. Pastikan API endpoint untuk getNextId (/sertifikat) berfungsi.')->withInput();
            }

            // Di dalam metode store()
            // ...
            $dataApi = [
                'id' => $nextIdSertifikat,
                'nim' => $this->globalTemplateNimIdentifier,
                'id_kegiatan' => $this->dummyKegiatanIdForTemplate, // Gunakan nilai dari properti
                'nama_file' => $fileName,
            ];
            // ...

            Log::info('[SERTIFIKAT_TEMPLATE_STORE] Data yang akan dikirim ke API createSertifikat:', $dataApi);
            $result = $this->apiService->createSertifikat($dataApi); 

            if ($result && !isset($result['_error']) && (($result['success'] ?? false) === true || isset($result['id']) || isset($result['id_sertifikat']) || isset($result['_success_no_content']))) {
                return redirect()->route('sertifikat-templates.index')->with('success', 'Template sertifikat global berhasil diunggah/diperbarui.');
            } else {
                Storage::disk('public')->delete($path); 
                Log::error('Gagal menyimpan data template sertifikat ke API', ['payload_sent' => $dataApi, 'api_response' => $result ?? []]);
                $apiMessage = $result['_json_error_data']['message'] ?? ($result['_body'] ?? 'Error API tidak diketahui');
                 if (isset($result['_json_error_data']['errors'])) {
                    $apiMessage .= " Details: " . json_encode($result['_json_error_data']['errors']);
                }
                return back()->with('error', 'Gagal menyimpan template sertifikat ke API: ' . $apiMessage)->withInput();
            }
        }
        return back()->with('error', 'Gagal mengunggah file template.')->withInput();
    }

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
            $found = collect($sertifikatList)->first(function ($sertifikat) use ($id_template) {
                $s = (object) $sertifikat;
                return ($s->id_sertifikat ?? $s->id ?? null) == $id_template && 
                       (isset($s->nim) && strtoupper($s->nim) === strtoupper($this->globalTemplateNimIdentifier)) &&
                       (isset($s->id_kegiatan) && (string)$s->id_kegiatan === $this->dummyKegiatanIdForTemplate);
            });
            if ($found) {
                $templateData = (object) $found;
            }
        }

        if (!$templateData || !isset($templateData->nama_file)) {
            Log::error("[SERTIFIKAT_TEMPLATE_DESTROY] Template global ID: {$id_template} tidak ditemukan atau tidak memiliki nama file.");
            return redirect()->route('sertifikat-templates.index')->with('error', 'Template global tidak ditemukan atau data tidak lengkap.');
        }

        $idToDeleteApi = $templateData->id_sertifikat ?? $templateData->id ?? $id_template;
        $result = $this->apiService->deleteSertifikat($idToDeleteApi); 

        if ($result && !isset($result['_error']) && (($result['success'] ?? false) === true || isset($result['_success_no_content']))) {
            Storage::disk('public')->delete('templates_sertifikat/' . $templateData->nama_file);
            Log::info('[SERTIFIKAT_TEMPLATE_DESTROY] Template sertifikat global dan file berhasil dihapus: ' . $templateData->nama_file);
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
