<?php

use App\Http\Controllers\AksaraController;
use App\Http\Controllers\KegiatanController; // Web Controller
use App\Http\Controllers\PeriodeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome'); // Atau halaman dashboard Anda
});

// --- Route Kegiatan ---
// Menggunakan resource controller untuk standar CRUD.
// Parameter {kegiatan} diubah menjadi {id} untuk konsistensi.
Route::resource('kegiatan', KegiatanController::class)->parameters([
    'kegiatan' => 'id' // Ini akan membuat URL menjadi /kegiatan/{id}, /kegiatan/{id}/edit, dll.
]); 

// Route tambahan untuk kegiatan jika tidak tercakup oleh resource controller
// Parameter {id} di sini sudah konsisten dengan perubahan di atas.
Route::get('/kegiatan/{id}/daftar-hadir', [KegiatanController::class, 'daftarHadir'])->name('kegiatan.daftar-hadir');


// --- Route Periode ---
Route::get('/periode', [PeriodeController::class, 'index'])->name('periode.index'); 
Route::get('/periode/create', [PeriodeController::class, 'create'])->name('periode.create');
Route::post('/periode', [PeriodeController::class, 'store'])->name('periode.store'); 
Route::get('/detailperiode/{id}', [PeriodeController::class, 'show'])->name('periode.show'); 
// Route::get('/settingperiode', [PeriodeController::class, 'create']); // Sudah dicakup oleh periode.create


// --- Route Aksara ---
Route::get('/aksara', [AksaraController::class, 'index'])->name('aksara.index');
// Pastikan parameter route konsisten dengan yang diharapkan Controller
Route::get('/aksara/{id}/detail', [AksaraController::class, 'show'])->name('aksara.detail'); 
Route::get('/aksara/{id}/setuju', [AksaraController::class, 'setuju'])->name('aksara.setuju'); 
Route::get('/aksara/{id}/tolak', [AksaraController::class, 'tolak'])->name('aksara.tolak'); 
Route::get('/validasi-aksara', [AksaraController::class, 'index'])->name('validasi.aksara.index');

