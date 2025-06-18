<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KegiatanController;
use App\Http\Controllers\SertifikatTemplateController;
use App\Http\Controllers\SertifikatGeneratorController;
use App\Http\Controllers\PemateriController;
use App\Http\Controllers\AksaraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaderboardReportController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PeriodeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PerusahaanController; // Tambahkan ini

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Halaman utama aplikasi
Route::get('/', function () {
    if (session()->has('authenticated_civitas')) {
        return redirect()->route('periode.index');
    }
    return view('welcome');
})->name('home');

// Route untuk Login & Logout
Route::get('/login', [LoginController::class, 'viewLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate'])->name('login.authenticate');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// --- Route Sertifikat Template ---
Route::resource('sertifikat-templates', SertifikatTemplateController::class)->only([
    'index',
    'store',
    'destroy'
]);

// --- Route Kegiatan ---
Route::resource('kegiatan', KegiatanController::class)->parameters([
    'kegiatan' => 'id'
]);

Route::get('/kegiatan/{id}/daftar-hadir', [KegiatanController::class, 'daftarHadir'])->name('kegiatan.daftar-hadir');
Route::get('/report/kegiatan/pdf', [ReportController::class, 'generateKegiatanReportPdf'])->name('report.kegiatan.pdf');
Route::get('/report/kegiatan/{idKegiatan}/daftar-hadir/pdf', [ReportController::class, 'generateDaftarHadirReportPdf'])->name('report.kegiatan.daftar-hadir.pdf');

// --- Route Master Pemateri ---
Route::get('/pemateri', [PemateriController::class, 'index'])->name('master-pemateri.index');
Route::get('/pemateri/create', [PemateriController::class, 'create'])->name('master-pemateri.create');
Route::post('/pemateri', [PemateriController::class, 'store'])->name('master-pemateri.store');
Route::delete('/pemateri/{pemateri}', [PemateriController::class, 'destroy'])->name('master-pemateri.destroy');

// --- Route Master Perusahaan ---
Route::get('/perusahaan', [PerusahaanController::class, 'index'])->name('master-perusahaan.index');
Route::get('/perusahaan/create', [PerusahaanController::class, 'create'])->name('master-perusahaan.create');
Route::post('/perusahaan', [PerusahaanController::class, 'store'])->name('master-perusahaan.store');
Route::delete('/perusahaan/{perusahaan}', [PerusahaanController::class, 'destroy'])->name('master-perusahaan.destroy');


// --- Route Periode ---
Route::get('/periode', [App\Http\Controllers\PeriodeController::class, 'index'])->name('periode.index');
Route::get('/periode/create', [App\Http\Controllers\PeriodeController::class, 'create'])->name('periode.create');
Route::post('/periode', [App\Http\Controllers\PeriodeController::class, 'store'])->name('periode.store');
Route::get('/periode/dropdown', [PeriodeController::class, 'dropdown'])->name('periode.dropdown');
Route::get('/periode/{id}', [App\Http\Controllers\PeriodeController::class, 'show'])->name('periode.show');


// --- Route Aksara (Validasi) ---
Route::prefix('validasi-aksara')->name('validasi.aksara.')->group(function () {
    Route::get('/', [AksaraController::class, 'index'])->name('index');
    Route::get('/{id}/detail', [AksaraController::class, 'show'])->name('detail');
    Route::post('/{id}/setuju', [AksaraController::class, 'setuju'])->name('setuju');
    Route::post('/{id}/tolak', [AksaraController::class, 'tolak'])->name('tolak');
    Route::get('/report/pdf', [ReportController::class, 'generateAksaraReportPdf'])->name('report.pdf');
});

// --- Route Generate Sertifikat ---
Route::get('/report/leaderboard/pdf', [ReportController::class, 'generatePdfLeaderboard'])->name('report.leaderboard.pdf');
Route::get('/report/leaderboard', [LeaderboardReportController::class, 'index'])->name('report.leaderboard');
Route::get('/report/penerima-reward/pdf', [ReportController::class, 'generatePdfPenerimaReward'])->name('report.penerima-reward.pdf');
Route::post('/report/leaderboard/claim/{rekapPoinId}', [LeaderboardReportController::class, 'markAsClaimed'])->name('report.leaderboard.claim');

Route::get('/sertifikat/generate/kegiatan/{idKegiatan}/peserta/{nim}/{peran?}', [SertifikatGeneratorController::class, 'generateUntukKegiatanSatu'])
    ->name('sertifikat.generate.peserta');
