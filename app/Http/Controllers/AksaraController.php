<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http; // Import Http facade
use Illuminate\Support\Facades\Log;   // Import Log facade

class AksaraController extends Controller
{
    protected $apiBaseUrl;

    public function __construct()
    {
        // Set base URL API dari environment variable atau config, atau hardcode jika perlu
        $this->apiBaseUrl = config('services.api.base_url', 'https://7f61-202-51-113-148.ngrok-free.app/api');
    }

    /** 
     * Membuat instance LengthAwarePaginator dari sebuah collection.
     */
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
        $status = $request->input('status'); // Tetap ambil, tapi tidak akan digunakan untuk filter API
        $searchTerm = $request->input('search');
        $submissionsCollection = new Collection();

        try {
            $response = Http::get("{$this->apiBaseUrl}/aksara-dinamika");

            if ($response->successful() && is_array($response->json())) {
                $apiData = $response->json();
                // Log::info('[AKSARA_INDEX] Data dari API /aksara-dinamika:', $apiData);

                // API mengembalikan objek dengan field seperti 'id_aksara_dinamika', 'nim', 'id_buku', dll.
                // Kita perlu menyesuaikan filter dan tampilan dengan field ini.
                $submissionsCollection = collect($apiData)->map(function($item){
                    // Tambahkan properti 'id' agar kompatibel dengan route('aksara.detail', ['id' => $item->id])
                    // dan properti lain yang mungkin masih digunakan di view (meskipun datanya mungkin tidak ada)
                    $newItem = (object) $item;
                    $newItem->id = $item['id_aksara_dinamika'] ?? null; 
                    // Untuk field yang tidak ada di API, kita set default atau biarkan null
                    $newItem->judul = $item['judul_buku'] ?? ('ID Buku: ' . ($item['id_buku'] ?? 'N/A')); // Asumsi API bisa dimodif untuk kirim judul_buku
                    $newItem->tanggal = $item['tanggal_submit'] ?? null; // Asumsi API bisa dimodif untuk kirim tanggal_submit
                    $newItem->nama = $item['nama_mahasiswa'] ?? ('NIM: ' . ($item['nim'] ?? 'N/A')); // Asumsi API bisa dimodif untuk kirim nama_mahasiswa
                    $newItem->status = $item['status_validasi'] ?? 'pending'; // Asumsi API bisa dimodif untuk kirim status_validasi
                    $newItem->alasan_penolakan = $item['alasan_ditolak'] ?? null; // Asumsi API bisa dimodif
                    return $newItem;
                });

                // 1. Terapkan Filter Pencarian (berdasarkan field yang ADA dari API)
                if ($searchTerm) {
                    $submissionsCollection = $submissionsCollection->filter(function ($item) use ($searchTerm) {
                        // Sesuaikan pencarian dengan field yang benar-benar ada dan relevan
                        return stripos($item->nim, $searchTerm) !== false ||
                               stripos($item->id_buku, $searchTerm) !== false ||
                               stripos($item->induk_buku ?? '', $searchTerm) !== false ||
                               stripos($item->judul, $searchTerm) !== false; // Jika judul sudah dimapping
                    });
                }

                // 2. Filter Status (HANYA BISA DILAKUKAN JIKA API MENGEMBALIKAN FIELD STATUS)
                // Karena API saat ini tidak mengembalikan status, filter ini tidak akan efektif.
                // Saya akan membiarkannya di sini jika API di masa depan diperbarui.
                if ($status && in_array($status, ['pending', 'accepted', 'rejected'])) {
                     $submissionsCollection = $submissionsCollection->where('status', $status);
                     Log::info("[AKSARA_INDEX] Filtering by status: {$status}. Count after filter: " . $submissionsCollection->count());
                }


                // Urutkan hasil (misalnya berdasarkan ID Aksara Dinamika, karena tanggal tidak ada)
                $submissionsCollection = $submissionsCollection->sortByDesc('id_aksara_dinamika');

            } else {
                Log::error('Gagal mengambil data Aksara Dinamika dari API: ' . $response->status() . ' - ' . $response->body());
                // Tampilkan pesan error ke pengguna atau redirect dengan error
                return back()->withErrors(['api_error' => 'Gagal memuat data dari server.']);
            }
        } catch (\Exception $e) {
            Log::error('Exception saat mengambil data Aksara Dinamika dari API: ' . $e->getMessage());
            return back()->withErrors(['api_error' => 'Terjadi kesalahan saat menghubungi server.']);
        }

        // 3. Buat pagination
        $submissions = $this->paginate(
            $submissionsCollection,
            10, 
            null, 
            ['path' => route('validasi.aksara.index')] 
        );

        return view('validasi-aksara', [
            'submissions' => $submissions,
        ]);
    }

    public function show($id) // $id adalah id_aksara_dinamika
    {
        $peserta = null;
        try {
            $response = Http::get("{$this->apiBaseUrl}/aksara-dinamika");
            if ($response->successful() && is_array($response->json())) {
                $apiData = $response->json();
                $foundItem = collect($apiData)->firstWhere('id_aksara_dinamika', (int)$id);

                if($foundItem) {
                    $peserta = (object) $foundItem;
                    // Tambahkan properti 'id' agar kompatibel dengan view jika view menggunakan $peserta->id
                    $peserta->id = $peserta->id_aksara_dinamika;
                    // Mapping field lain agar view detailaksara.blade.php bisa menampilkan sesuatu
                    // Ini adalah asumsi, idealnya API readAksaraDinamika by ID akan mengembalikan data lebih lengkap
                    $peserta->judul = $peserta->judul_buku ?? ('ID Buku: ' . ($peserta->id_buku ?? 'N/A'));
                    $peserta->nama = $peserta->nama_mahasiswa ?? ('NIM: ' . ($peserta->nim ?? 'N/A'));
                    $peserta->dosen = $peserta->dosen_usulan ?? '-';
                    $peserta->link = $peserta->link_upload ?? '#';
                    $peserta->status = $peserta->status_validasi ?? 'pending'; // Asumsi
                    $peserta->alasan_penolakan = $peserta->alasan_ditolak ?? null; // Asumsi
                    $peserta->tanggal = $peserta->tanggal_submit ?? null; // Asumsi

                } else {
                    Log::warning("[AKSARA_SHOW] Submission dengan ID {$id} tidak ditemukan di API.");
                    abort(404, 'Submission tidak ditemukan.');
                }
            } else {
                Log::error("[AKSARA_SHOW] Gagal mengambil data dari API /aksara-dinamika: " . $response->status() . " - " . $response->body());
                abort(500, 'Gagal mengambil data submission.');
            }
        } catch (\Exception $e) {
            Log::error("[AKSARA_SHOW] Exception saat mengambil detail submission ID {$id}: " . $e->getMessage());
            abort(500, 'Terjadi kesalahan server.');
        }

        return view('detailaksara', compact('peserta'));
    }

    public function tolak(Request $request, $id) // $id adalah id_aksara_dinamika
    {
        // API updAksaraDinamika tidak mendukung update status atau alasan penolakan.
        // Fungsi ini tidak dapat diimplementasikan sepenuhnya sesuai keinginan tanpa perubahan API.
        Log::warning("[AKSARA_TOLAK] Aksi tolak untuk ID {$id} dipanggil, tetapi API backend tidak mendukung update status/alasan penolakan secara langsung via endpoint update yang ada.");
        
        // Redirect kembali dengan pesan bahwa aksi tidak didukung oleh API saat ini.
        return redirect(route('validasi.aksara.index', $request->query()))
                       ->with('info', "Fungsi 'tolak' tidak didukung oleh API backend saat ini untuk mengubah status. Perlu endpoint API khusus untuk update status.");
    }

    public function setuju($id) // $id adalah id_aksara_dinamika
    {
        // API updAksaraDinamika tidak mendukung update status.
        // Fungsi ini tidak dapat diimplementasikan sepenuhnya sesuai keinginan tanpa perubahan API.
        Log::warning("[AKSARA_SETUJU] Aksi setuju untuk ID {$id} dipanggil, tetapi API backend tidak mendukung update status secara langsung via endpoint update yang ada.");

        // Redirect kembali dengan pesan bahwa aksi tidak didukung oleh API saat ini.
        return redirect(route('validasi.aksara.index', request()->query()))
                       ->with('info', "Fungsi 'setuju' tidak didukung oleh API backend saat ini untuk mengubah status. Perlu endpoint API khusus untuk update status.");
    }
}
