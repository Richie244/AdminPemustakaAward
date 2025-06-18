<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MyApiService;

class DashboardController extends Controller
{
    protected $apiService;

    public function __construct(MyApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function index(Request $request)
    {
        // 1. Ambil ID periode dari query URL.
        $periodeId = $request->query('periode');
        $queryParams = $periodeId ? ['periode' => $periodeId] : [];

        // Panggil API dengan parameter periode
        $dataMhs = $this->apiService->getMahasiswaLeaderboard($queryParams);
        $dataDosen = $this->apiService->getDosenLeaderboard($queryParams);
        $periodes = $this->apiService->getPeriodeList();

        // Ambil top 5 untuk ditampilkan di dashboard
        $top5Mahasiswa = isset($dataMhs['leaderboard']) ? array_slice($dataMhs['leaderboard'], 0, 5) : [];
        $top5Dosen = isset($dataDosen['leaderboard']) ? array_slice($dataDosen['leaderboard'], 0, 5) : [];

        // Logika untuk menentukan nama periode yang ditampilkan di tombol dropdown
        $selectedPeriodeName = 'Periode Saat Ini'; // Default
        if ($periodeId && isset($periodes['data'])) {
            foreach ($periodes['data'] as $periode) {
                if ($periode['id_periode'] == $periodeId) {
                    $selectedPeriodeName = $periode['nama_periode'];
                    break;
                }
            }
        } elseif (isset($dataMhs['periode_aktif'])) {
            $selectedPeriodeName = $dataMhs['periode_aktif'];
        }
        
        // Cek jika ada error dari API
        if (isset($dataMhs['_error']) || isset($dataDosen['_error'])) {
            session()->flash('error', 'Gagal mengambil data dari API. Silakan coba lagi nanti.');
        }

        // 2. [PENTING] Pastikan $periodeId dikirim ke view.
        return view('dashboard', [
            'top5Mahasiswa' => $top5Mahasiswa,
            'top5Dosen' => $top5Dosen,
            'periodes' => $periodes['data'] ?? [],
            'selectedPeriodeName' => $selectedPeriodeName,
            'periodeId' => $periodeId // Baris ini memastikan ID terkirim ke view
        ]);
    }
}
