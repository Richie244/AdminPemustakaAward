<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use App\Models\Periode; // Jika Anda menggunakan Model

class PeriodeController extends Controller
{
    // Method untuk menampilkan daftar periode
    public function index()
    {
        // Logika untuk mengambil data periode
        // $periodeList = Periode::orderBy('start_date', 'desc')->get(); 
        $periodeList = [ /* ... data dummy atau dari DB ... */ ]; // Ganti dengan data asli
         $currentDate = now();
         $filteredPeriodeList = collect($periodeList)->filter(function ($periode) use ($currentDate) {
            return $periode['status'] === 'Aktif' || 
                ($periode['status'] === 'Non-Aktif' && (!isset($periode['end_date']) || \Carbon\Carbon::parse($periode['end_date'] ?? '1970-01-01')->endOfDay()->gte($currentDate)));
         })->all();
        return view('periode', ['periodeList' => $filteredPeriodeList]); // Pastikan nama view benar
    }

    // Method untuk menampilkan form tambah periode
    public function create()
    {
        return view('settingperiode'); // Pastikan nama view benar
    }

    // Method untuk menyimpan periode baru
    public function store(Request $request)
    {
        // 1. Validasi data dari $request
        $validatedData = $request->validate([
            'nama_periode' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'kunjungan_start.*' => 'nullable|integer|min:0', // Validasi untuk array
            'kunjungan_end.*' => 'nullable|integer|min:0|gte:kunjungan_start.*',
            'kunjungan_skor.*' => 'nullable|integer|min:0',
            'pinjaman_start.*' => 'nullable|integer|min:0',
            'pinjaman_end.*' => 'nullable|integer|min:0|gte:pinjaman_start.*',
            'pinjaman_skor.*' => 'nullable|integer|min:0',
            'rewards.*.skor_minimal' => 'required|integer|min:0', // Validasi untuk array multidimensi
            'rewards.*.nama_reward' => 'required|string|max:255',
            'rewards.*.slot_tersedia' => 'required|integer|min:0',
            'nilai_maks.kunjungan' => 'required|integer|min:0',
            'nilai_maks.aksara_dinamika' => 'required|integer|min:0',
            'nilai_maks.pinjaman' => 'required|integer|min:0',
            'nilai_maks.kegiatan' => 'required|integer|min:0',
        ]);

        // 2. Proses data (gabungkan range, format, dll.)
        //    Contoh: Menggabungkan range skor kunjungan
        $skorKunjunganRanges = [];
        if ($request->has('kunjungan_start')) {
            for ($i = 0; $i < count($request->kunjungan_start); $i++) {
                if (isset($request->kunjungan_start[$i]) && isset($request->kunjungan_end[$i]) && isset($request->kunjungan_skor[$i])) {
                     $skorKunjunganRanges[] = [
                        'start' => $request->kunjungan_start[$i],
                        'end' => $request->kunjungan_end[$i],
                        'skor' => $request->kunjungan_skor[$i],
                    ];
                }
            }
        }
        // Lakukan hal serupa untuk skor pinjaman
        // Proses data rewards dan nilai_maks

        // 3. Simpan data ke database (jika menggunakan database)
        //    Contoh:
        //    $periode = new PeriodeModel();
        //    $periode->nama = $validatedData['nama_periode'];
        //    $periode->start_date = $validatedData['start_date'];
        //    $periode->end_date = $validatedData['end_date'];
        //    // Simpan data JSON atau relasi untuk skor, reward, nilai maks
        //    $periode->skor_kunjungan_config = json_encode($skorKunjunganRanges); 
        //    // ... simpan data lainnya ...
        //    $periode->save();

        // 4. Redirect ke halaman daftar periode dengan pesan sukses
        return redirect()->route('periode.index')->with('success', 'Periode baru berhasil ditambahkan!');
    }

    // Method untuk menampilkan detail periode
    public function show($id)
    {
         // Logika untuk mengambil data $periodeData berdasarkan $id
         $allPeriodeData = [ /* ... data dummy lengkap ... */ ]; 
         $periodeData = $allPeriodeData[$id] ?? null; 

         if (!$periodeData) {
             abort(404); // Tampilkan 404 jika ID tidak ditemukan
         }

         return view('detailperiode', compact('periodeData', 'id')); // Kirim data ke view
    }

    // Method lain jika perlu (edit, update, destroy)
}