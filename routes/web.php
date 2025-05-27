<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KegiatanController;
use App\Http\Controllers\SertifikatTemplateController;
use App\Http\Controllers\PemateriController;
use App\Http\Controllers\AksaraController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SertifikatGeneratorController;
use App\Http\Controllers\PeriodeController;

 // Pastikan ini di-import

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

// --- Grup Route yang Memerlukan Autentikasi ---
// Route::middleware(['auth.custom'])->group(function () { // Sesuaikan dengan middleware Anda

    // --- Route Sertifikat Template ---
    Route::resource('sertifikat-templates', SertifikatTemplateController::class)->only([
        'index', 'store', 'destroy'
    ]);

    // --- Route Kegiatan ---
    Route::resource('kegiatan', KegiatanController::class)->parameters([
        'kegiatan' => 'id' 
    ]); 
    Route::get('/kegiatan/{id}/daftar-hadir', [KegiatanController::class, 'daftarHadir'])->name('kegiatan.daftar-hadir');

    // Contoh routes untuk Master Pemateri
    Route::get('/pemateri', [PemateriController::class, 'index'])->name('master-pemateri.index');
    Route::get('/pemateri/create', [PemateriController::class, 'create'])->name('master-pemateri.create');
    Route::post('/pemateri', [PemateriController::class, 'store'])->name('master-pemateri.store');
    // Tambahkan route untuk edit, update, delete jika perlu
    Route::delete('/pemateri/{pemateri}', [PemateriController::class, 'destroy'])->name('master-pemateri.destroy');

    // --- Route Periode ---
    Route::get('/periode', [App\Http\Controllers\PeriodeController::class, 'index'])->name('periode.index'); 
    Route::get('/periode/create', [App\Http\Controllers\PeriodeController::class, 'create'])->name('periode.create');
    Route::post('/periode', [App\Http\Controllers\PeriodeController::class, 'store'])->name('periode.store'); 
    Route::get('/periode/{id}', [App\Http\Controllers\PeriodeController::class, 'show'])->name('periode.show');    

    // --- Route Aksara (Validasi) ---
    Route::prefix('validasi-aksara')->name('validasi.aksara.')->group(function () {
        Route::get('/', [AksaraController::class, 'index'])->name('index');
        Route::get('/{id}/detail', [AksaraController::class, 'show'])->name('detail');
        Route::post('/{id}/setuju', [AksaraController::class, 'setuju'])->name('setuju');
        Route::post('/{id}/tolak', [AksaraController::class, 'tolak'])->name('tolak');  
    });

    // --- Route BARU untuk Generate Sertifikat ---
    // Parameter {idKegiatan} dan {nim} akan dinamis
    // Parameter {peran?} adalah opsional, defaultnya akan dihandle di controller
    Route::get('/sertifikat/generate/kegiatan/{idKegiatan}/peserta/{nim}/{peran?}', [SertifikatGeneratorController::class, 'generateUntukKegiatanSatu'])
        ->name('sertifikat.generate.peserta');
    Route::get('/generate-sertifikat/kegiatan-1/{nim}', [SertifikatGeneratorController::class, 'generateUntukKegiatanSatu'])
        ->name('sertifikat.generate.kegiatan1');
    // Catatan: Metode 'generateUntukKegiatanSatu' mungkin perlu diubah namanya menjadi lebih generik
    // jika tidak lagi spesifik untuk "kegiatan 1" saja.
    // Atau, Anda bisa membuat metode baru di controller untuk menangani ID kegiatan dinamis.
    // Untuk saat ini, kita akan tetap menggunakan 'generateUntukKegiatanSatu' tapi idKegiatan dari URL akan menggantikan '1' yang di-hardcode.

// }); // Akhir grup middleware
