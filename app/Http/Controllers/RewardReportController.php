<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MyApiService;

class RewardReportController extends Controller
{
    protected $apiService;

    public function __construct(MyApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Menampilkan halaman laporan penerima reward.
     */
    public function index()
    {
        // Panggil method di MyApiService untuk mengambil data penerima reward
        // Kita perlu membuat method getPenerimaReward() ini di langkah berikutnya.
        $penerimaData = $this->apiService->getPenerimaReward(); 

        return view('reports.penerima-reward', [
            'penerimaList' => $penerimaData['data'] ?? [],
        ]);
    }

    /**
     * Membuat laporan PDF untuk penerima reward.
     * (Akan kita implementasikan nanti setelah halaman web-nya jadi)
     */
    public function generatePdf()
    {
        $penerimaData = $this->apiService->getPenerimaReward();
        $data = ['penerimaList' => $penerimaData['data'] ?? []];
        
        // $pdf = PDF::loadView('reports.penerima_reward_pdf', $data);
        // return $pdf->stream('laporan-penerima-reward.pdf');

        // Untuk sementara, kembalikan pesan
        return "Fungsi PDF untuk Laporan Penerima Reward akan dibuat di sini.";
    }
}
