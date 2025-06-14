<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeaderboardController extends Controller
{
    /**
     * URL dasar untuk API backend.
     * Diambil dari config/services.php yang membaca file .env
     *
     * @var string
     */
    public $baseUrl;

    /**
     * Constructor untuk menginisialisasi base URL.
     */
    public function __construct()
    {
        // Mengambil base URL dari file konfigurasi sekali saja.
        $this->baseUrl = config('services.backend.base_url');
    }

    public function viewLeaderboard1()
    {
        $response = Http::get($this->baseUrl .'/rekap-poin/leaderboard/mhs');
        $data = $response->json();

        $top5 = array_slice($data, 0, 5); // hanya ambil 5 teratas

        return view('Mahasiswa.leaderboard', compact('top5'));
    }
    
    public function viewLeaderboard2()
    {
        $response = Http::get($this->baseUrl .'/rekap-poin/leaderboard/dosen');
        $data = $response->json();

        $top5 = array_slice($data, 0, 5); // hanya ambil 5 teratas

        return view('Dosen/leaderboard', compact('top5'));
    }

    public function viewdropdownperiode()
    {
        $response = Http::get($this->baseUrl .'/periode');
        $data = $response->json();
        return response()->json($data); // return JSON, bukan view
    }
}