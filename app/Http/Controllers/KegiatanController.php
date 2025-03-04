<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class KegiatanController extends Controller
{
    public function index()
    {
        // Dummy data sementara
        $kegiatan = [
            ['id' => 1, 'judul' => 'Workshop Laravel', 'tanggal' => '2025-02-28', 'jam' => '10:00', 'pemateri' => 'John Doe', 'lokasi' => 'Zoom', 'keterangan' => 'Pelatihan Laravel', 'bobot' => 10],
            ['id' => 2, 'judul' => 'Seminar AI', 'tanggal' => '2025-03-05', 'jam' => '14:00', 'pemateri' => 'Jane Smith', 'lokasi' => 'Auditorium', 'keterangan' => 'Pembahasan AI', 'bobot' => 15],
        ];
        return view('kegiatan', compact('kegiatan'));
    }

    public function create()
    {
        return view('tambah-kegiatan');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'judul' => 'required|string|max:255',
            'tanggal' => 'required|array',
            'tanggal.*' => 'date',
            'jam_mulai' => 'required|array',
            'jam_mulai.*' => 'date_format:H:i',
            'jam_selesai' => 'nullable|array',
            'jam_selesai.*' => 'date_format:H:i',
            'pemateri' => 'required|array',
            'pemateri.*' => 'string|max:255',
            'media' => 'required|string',
            'lokasi' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string',
            'bobot' => 'required|integer|min:1',
        ]);

        // Simpan ke database
        Kegiatan::create([
            'judul' => $validatedData['judul'],
            'tanggal' => json_encode($validatedData['tanggal']),
            'jam_mulai' => json_encode($validatedData['jam_mulai']),
            'jam_selesai' => json_encode($validatedData['jam_selesai']),
            'pemateri' => json_encode($validatedData['pemateri']),
            'media' => $validatedData['media'],
            'lokasi' => $validatedData['lokasi'],
            'keterangan' => $validatedData['keterangan'],
            'bobot' => $validatedData['bobot'],
        ]);

        return redirect()->route('kegiatan.index')->with('success', 'Kegiatan berhasil ditambahkan!');
    }

    public function edit($id)
    {
        // Dummy data
        $kegiatan = [
            'id' => $id,
            'judul' => 'Workshop Laravel',
            'tanggal' => ['2025-02-28'],
            'jam_mulai' => ['10:00'],
            'jam_selesai' => ['12:00'],
            'pemateri' => ['John Doe'],
            'media' => ['Online'],
            'lokasi' => 'Zoom',
            'keterangan' => 'Pelatihan Laravel',
            'bobot' => 10
        ];
        

        return view('editkegiatan', compact('kegiatan'));
    }

    public function update(Request $request, $id)
    {
        // Validasi data (jika nanti pakai database)
        $request->validate([
            'judul' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'jam' => 'required',
            'pemateri' => 'required|string|max:255',
            'lokasi' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'bobot' => 'required|numeric|min:0',
        ]);

        // Simulasi update (nanti bisa diganti dengan database)
        return redirect()->route('kegiatan.index')->with('success', 'Kegiatan berhasil diperbarui.');
    }

    public function daftarKegiatan()
    {
        $kegiatan = Kegiatan::all(); // Ambil semua data kegiatan dari database
        return view('daftar-kegiatan', compact('kegiatan'));
    }
}
