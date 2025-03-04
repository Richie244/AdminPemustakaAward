<?php

use App\Http\Controllers\AksaraController;
use App\Http\Controllers\KegiatanController;
use Illuminate\Support\Facades\Route;

Route::get('/kegiatan', [KegiatanController::class, 'index'])->name('kegiatan.index');

Route::get('/kegiatan/tambah', [KegiatanController::class, 'create'])->name('kegiatan.tambah');
Route::post('/kegiatan/store', [KegiatanController::class, 'store'])->name('kegiatan.store');

Route::get('/kegiatan/edit/{id}', [KegiatanController::class, 'edit'])->name('kegiatan.edit');
Route::put('/kegiatan/update/{id}', [KegiatanController::class, 'update'])->name('kegiatan.update');
Route::get('/kegiatan/daftar-hadir/{id}', [KegiatanController::class, 'daftar-hadir'])->name('kegiatan.edit');

Route::get('/periode', function () {
    return view('periode');
});

Route::get('/detailperiode/{id}', function ($id) {
    return view('detailperiode')->with('id', $id);
});

Route::get('/settingperiode', function () {
    return view('settingperiode');
});



Route::get('/aksara', [AksaraController::class, 'index'])->name('aksara.index');
Route::get('/aksara/{nim}', [AksaraController::class, 'show'])->name('aksara.detail');
Route::get('/aksara/setuju/{nim}', [AksaraController::class, 'setuju'])->name('aksara.setuju');
Route::get('/aksara/tolak/{nim}', [AksaraController::class, 'tolak'])->name('aksara.tolak');


