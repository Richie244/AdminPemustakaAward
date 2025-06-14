<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\MyApiService;
use Illuminate\Support\Facades\Log; // Menggunakan API Service Anda

class DashboardController extends Controller
{
    protected $apiService;

    public function __construct(MyApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Menampilkan halaman dashboard dengan data leaderboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Menggunakan method yang sudah ada di MyApiService
        $dataMhs = $this->apiService->getMahasiswaLeaderboard();
        $dataDosen = $this->apiService->getDosenLeaderboard();

        // Mengambil 5 data teratas, dengan penanganan jika data tidak ada atau error
        $top5Mhs = (!isset($dataMhs['_error']) && is_array($dataMhs)) ? array_slice($dataMhs, 0, 5) : [];
        $top5Dosen = (!isset($dataDosen['_error']) && is_array($dataDosen)) ? array_slice($dataDosen, 0, 5) : [];

        // Menampilkan pesan error jika salah satu API gagal
        if (isset($dataMhs['_error'])) {
            Log::error('Gagal mengambil leaderboard mahasiswa', $dataMhs);
            session()->flash('error', 'Gagal mengambil data Leaderboard Mahasiswa dari API.');
        }
        if (isset($dataDosen['_error'])) {
            Log::error('Gagal mengambil leaderboard dosen', $dataDosen);
            session()->flash('error', 'Gagal mengambil data Leaderboard Dosen dari API.');
        }
        
        // Kirim data ke view dashboard
        return view('dashboard', [
            'top5Mahasiswa' => $top5Mhs,
            'top5Dosen' => $top5Dosen
        ]);
    }
}