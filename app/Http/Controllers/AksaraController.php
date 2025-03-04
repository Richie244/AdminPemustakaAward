<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class AksaraController extends Controller
{
    public function index()
    {
        $menungguValidasi = [
            (object) [
                'nim' => 22410100016,
                'judul' => 'Panduan Java Dasar Bagian 1',
                'tanggal' => '2025-02-10',
                'nama' => 'User 1',
                'email' => '22410100016@dinamika.ac.id'
            ],
        ];

        $sudahValidasi = [
            (object) [
                'nim' => 22410100016,
                'judul' => 'Manusia Ulang-Alik : Biografi Umar Kayam',
                'tanggal' => '2025-01-15',
                'nama' => 'User 2',
                'email' => '22410100016@dinamika.ac.id',
                'status' => 'valid'
            ],
            (object) [
                'nim' => 22410100016,
                'judul' => 'Panduan Belajar Desain Grafis Dengan Adobe Photoshop CS',
                'tanggal' => '2024-12-26',
                'nama' => 'User 3',
                'email' => '22410100016@dinamika.ac.id',
                'status' => 'tidak valid'
            ],
        ];

        return view('validasi-aksara', compact('menungguValidasi', 'sudahValidasi'));
    }

    public function show($nim)
    {
        // Data dummy, bisa diganti dengan query ke database
        $peserta = (object) [
            'nim' => 22410100016,
            'nama' => 'User 1',
            'email' => '22410100016@dinamika.ac.id',
            'judul' => 'Panduan Java Dasar Bagian 1',
            'pengarang' => 'John Doe',
            'review' => 'Buku ini sangat cocok untuk pemula...',
        ];
    
        return view('detailaksara', compact('peserta'));
    }
    
    public function tolak(Request $request, $nim)
    {
        $alasan = $request->query('alasan');
    
        // Simpan alasan penolakan ke database jika diperlukan
        return redirect()->route('validasi.aksara.index')->with('error', 'Review ditolak dengan alasan: ' . $alasan);
    }
    
    public function setuju($nim)
    {
        // Logika validasi
        return redirect()->route('validasi.aksara.index')->with('success', 'Aksara berhasil divalidasi.');
    }
    
}
