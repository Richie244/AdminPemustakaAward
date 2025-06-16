<?php

namespace App\Http\Controllers;

use App\Services\MyApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * @var \App\Services\MyApiService
     */
    protected $apiService;

    public function __construct(MyApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Menampilkan halaman dashboard dengan data leaderboard yang bisa difilter.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Ambil periode_id dari query string
        $periodeId = $request->query('periode');
        $queryParams = $periodeId ? ['periode' => $periodeId] : [];

        // Panggil API dengan parameter periode
        $dataMhs = $this->apiService->getMahasiswaLeaderboard($queryParams);
        $dataDosen = $this->apiService->getDosenLeaderboard($queryParams);

        // PERBAIKAN: Mem-parsing struktur response API yang baru
        $top5Mhs = [];
        if (isset($dataMhs['leaderboard']) && is_array($dataMhs['leaderboard'])) {
            $top5Mhs = array_slice($dataMhs['leaderboard'], 0, 5);
        }

        $top5Dosen = [];
        if (isset($dataDosen['leaderboard']) && is_array($dataDosen['leaderboard'])) {
            $top5Dosen = array_slice($dataDosen['leaderboard'], 0, 5);
        }

        // Ambil nama periode aktif dari salah satu response
        $selectedPeriodeName = $dataMhs['periode_aktif'] ?? 'Periode Saat Ini';

        // Catat error jika ada
        if (isset($dataMhs['_error'])) {
            Log::error('Gagal mengambil leaderboard mahasiswa', $dataMhs);
            session()->flash('error', 'Gagal mengambil data Leaderboard Mahasiswa dari API.');
        }
        if (isset($dataDosen['_error'])) {
            Log::error('Gagal mengambil leaderboard dosen', $dataDosen);
            session()->flash('error', 'Gagal mengambil data Leaderboard Dosen dari API.');
        }
        
        // Kirim semua data yang diperlukan ke view
        return view('dashboard', [
            'top5Mahasiswa' => $top5Mhs,
            'top5Dosen' => $top5Dosen,
            'selectedPeriodeName' => $selectedPeriodeName,
        ]);
    }
}
