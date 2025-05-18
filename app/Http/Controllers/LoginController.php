<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
// Auth facade tidak terpakai di logika Anda saat ini, bisa dihapus jika tidak ada rencana Auth Laravel standar
// use Illuminate\Support\Facades\Auth; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // Tambahkan Log facade untuk error logging
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    protected string $civitasApiUrl;

    public function __construct()
    {
        // Mengambil URL API dari file konfigurasi
        // 'services.civitas_api.url' adalah path ke konfigurasi Anda
        $this->civitasApiUrl = config('services.civitas_api.url');

        if (!$this->civitasApiUrl) {
            // Log error atau lempar exception jika URL API tidak terkonfigurasi
            // Ini penting untuk debugging jika konfigurasi salah
            Log::critical('URL API Civitas tidak terkonfigurasi di config/services.php atau .env');
            // Anda bisa throw new \Exception('Konfigurasi URL API Civitas tidak ditemukan.');
            // atau set ke URL default yang sangat jelas bahwa ini fallback
            // $this->civitasApiUrl = 'https://api.error.not.configured/api/civitas';
        }
    }

    public function viewLoginForm()
    {
        // Pastikan view 'formlogin.blade.php' atau 'auth/login.blade.php' ada
        // Jika Anda mengikuti saran sebelumnya untuk halaman login standalone:
        // return view('auth.login'); 
        return view('formlogin'); // Sesuai kode Anda
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'nocivitas' => ['required', 'string'],
            // 'password' => ['required'], // Jika Anda memiliki field password di masa depan
        ]);

        $allowedCivitasIds = [
            "050530", // Deasy Kumalawati
            "120778", // Maria Widya Nugrahayu
            "000286", // Agung Prasetyo Wibowo
        ];

        $inputCivitasId = $credentials['nocivitas'];

        // 1. Cek apakah nocivitas yang diinput ada dalam daftar yang diizinkan
        if (!in_array($inputCivitasId, $allowedCivitasIds)) {
            return back()->withErrors([
                'loginError' => 'Akses ditolak. Akun Anda tidak memiliki izin untuk login.',
            ])->withInput($request->only('nocivitas'));
        }

        // 2. Jika diizinkan, lanjutkan untuk mengambil data dari API
        // Gunakan properti $this->civitasApiUrl yang sudah diinisialisasi di constructor
        if (empty($this->civitasApiUrl) || $this->civitasApiUrl === config('services.civitas_api.url', 'https://default-civitas-api.com/api/civitas') && str_contains($this->civitasApiUrl, 'default-civitas-api.com')) {
             Log::error('Login Gagal: URL API Civitas tidak valid atau menggunakan fallback default.', ['configured_url' => $this->civitasApiUrl]);
             return back()->withErrors([
                 'loginError' => 'Konfigurasi server API tidak valid. Hubungi administrator.',
             ])->withInput($request->only('nocivitas'));
        }
        
        try {
            // Menggunakan $this->civitasApiUrl
            $response = Http::get($this->civitasApiUrl);

            if (!$response->successful()) {
                Log::error('Login Gagal: Permintaan API ke ' . $this->civitasApiUrl . ' gagal.', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return back()->withErrors([
                    'loginError' => 'Sistem sedang mengalami gangguan saat menghubungi server otentikasi. Silakan coba lagi nanti.',
                ])->withInput($request->only('nocivitas'));
            }

            $apiData = $response->json();
            if (!is_array($apiData)) {
                Log::error('Login Gagal: Respons API dari ' . $this->civitasApiUrl . ' bukan array JSON.', ['body' => $response->body()]);
                return back()->withErrors([
                    'loginError' => 'Format data dari server otentikasi tidak sesuai. Hubungi administrator.',
                ])->withInput($request->only('nocivitas'));
            }
            
            $foundUserFromApi = collect($apiData)->firstWhere('id_civitas', $inputCivitasId);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Login Gagal: Kesalahan koneksi API ke ' . $this->civitasApiUrl, ['error' => $e->getMessage()]);
            return back()->withErrors([
                'loginError' => 'Tidak dapat terhubung ke server otentikasi. Periksa koneksi Anda dan konfigurasi server.',
            ])->withInput($request->only('nocivitas'));
        } catch (\Exception $e) { // Menangkap error umum lainnya
            Log::error('Login Gagal: Terjadi error saat proses otentikasi via API dari ' . $this->civitasApiUrl, ['error' => $e->getMessage()]);
            return back()->withErrors([
                'loginError' => 'Terjadi kesalahan yang tidak diketahui pada sistem otentikasi.',
            ])->withInput($request->only('nocivitas'));
        }

        // 3. Proses data pengguna jika ditemukan di API
        if ($foundUserFromApi) {
            Session::put('authenticated_civitas', $foundUserFromApi);
            Session::put('nama_pengguna', $foundUserFromApi['nama']);
            Session::put('status_pengguna', $foundUserFromApi['status']);

            $request->session()->regenerate();

            if ($foundUserFromApi['status'] == 'TENDIK') {
                // Pastikan route 'periode.index' ada dan sesuai
                // Sebelumnya 'kegiatan.index', diubah sesuai kode terakhir Anda
                return redirect()->intended(route('periode.index')); 
            } else {
                // Pastikan route 'leaderboard2' ada dan sesuai
                return redirect()->intended(route('leaderboard2'));
            }
        }

        Log::warning('Pengguna dengan ID Civitas ' . $inputCivitasId . ' ada di daftar izin tapi tidak ditemukan di API (' . $this->civitasApiUrl . ').');
        return back()->withErrors([
            'loginError' => 'Data pengguna tidak dapat diverifikasi sepenuhnya. Hubungi administrator.',
        ])->withInput($request->only('nocivitas'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Session::flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('status', 'Anda telah berhasil logout.');
    }
}
