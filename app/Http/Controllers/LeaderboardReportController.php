<?php
// app/Http/Controllers/LeaderboardReportController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MyApiService;
use Illuminate\Support\Facades\Log;

class LeaderboardReportController extends Controller
{
    protected $apiService;

    public function __construct(MyApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Menampilkan halaman laporan leaderboard lengkap.
     */
    public function index(Request $request)
    {
        $periodeId = $request->query('periode');
        $queryParams = $periodeId ? ['periode' => $periodeId] : [];

        // Panggil API untuk mendapatkan data lengkap (bukan hanya top 5)
        $dataMhs = $this->apiService->getMahasiswaLeaderboard($queryParams);
        $dataDosen = $this->apiService->getDosenLeaderboard($queryParams);

        $leaderboardMhs = [];
        if (isset($dataMhs['leaderboard']) && is_array($dataMhs['leaderboard'])) {
            $leaderboardMhs = $dataMhs['leaderboard'];
        }

        $leaderboardDosen = [];
        if (isset($dataDosen['leaderboard']) && is_array($dataDosen['leaderboard'])) {
            $leaderboardDosen = $dataDosen['leaderboard'];
        }

        $selectedPeriodeName = $dataMhs['periode_aktif'] ?? 'Periode Saat Ini';

        if (isset($dataMhs['_error'])) {
            session()->flash('error', 'Gagal mengambil data Leaderboard Mahasiswa dari API.');
        }
        if (isset($dataDosen['_error'])) {
            session()->flash('error', 'Gagal mengambil data Leaderboard Dosen dari API.');
        }
        
        return view('reports.leaderboard-report', [
            'leaderboardMahasiswa' => $leaderboardMhs,
            'leaderboardDosen' => $leaderboardDosen,
            'selectedPeriodeName' => $selectedPeriodeName,
            'periodeId' => $periodeId
        ]);
    }

    /**
     * Menangani permintaan untuk menandai hadiah sebagai sudah diklaim.
     */
    public function markAsClaimed(Request $request, $rekapPoinId)
    {
        $periodeId = $request->input('periode');
        $queryParams = $periodeId ? ['periode' => $periodeId] : [];

        try {
            $result = $this->apiService->markRewardAsClaimed($rekapPoinId);

            if ($result && !isset($result['_error'])) {
                return redirect()->route('report.leaderboard', $queryParams)
                                 ->with('success', 'Hadiah berhasil ditandai sebagai sudah diklaim.');
            } else {
                $errorMessage = $result['_json_error_data']['message'] ?? 'Gagal memperbarui status klaim hadiah.';
                return redirect()->route('report.leaderboard', $queryParams)
                                 ->with('error', $errorMessage);
            }
        } catch (\Exception $e) {
            Log::error('Exception saat markAsClaimed: ' . $e->getMessage());
            return redirect()->route('report.leaderboard', $queryParams)
                             ->with('error', 'Terjadi kesalahan sistem saat memproses permintaan.');
        }
    }
}