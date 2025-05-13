<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

class AksaraController extends Controller
{
    private $allSubmissions;

    public function __construct()
    {
        // Data dummy Anda (pastikan ada cukup data untuk menguji pagination)
        $this->allSubmissions = collect([
            (object) ['id' => 1, 'nim' => 22410100016, 'judul' => 'Panduan Java Dasar Bagian 1', 'tanggal' => '2025-02-10', 'nama' => 'User 1', 'email' => '22410100016@dinamika.ac.id', 'status' => 'pending', 'pengarang' => 'John Doe', 'review' => 'Buku ini sangat cocok untuk pemula...', 'dosen' => 'Dosen 1', 'link' => '#', 'alasan_penolakan' => null],
            (object) ['id' => 2, 'nim' => 22410100016, 'judul' => 'Manusia Ulang-Alik : Biografi Umar Kayam', 'tanggal' => '2025-01-15', 'nama' => 'User 2', 'email' => '22410100016@dinamika.ac.id', 'status' => 'accepted', 'pengarang' => 'Jane Smith', 'review' => 'Biografi yang sangat inspiratif...', 'dosen' => 'Dosen 2', 'link' => '#', 'alasan_penolakan' => null],
            (object) ['id' => 3, 'nim' => 22410100016, 'judul' => 'Panduan Belajar Desain Grafis Dengan Adobe Photoshop CS', 'tanggal' => '2024-12-26', 'nama' => 'User 3', 'email' => '22410100016@dinamika.ac.id', 'status' => 'rejected', 'pengarang' => 'Michael Brown', 'review' => 'Materi terlalu dasar...', 'dosen' => 'Dosen 3', 'link' => '#', 'alasan_penolakan' => 'Format penulisan tidak sesuai standar'],
            (object) ['id' => 4, 'nim' => 22410100017, 'judul' => 'Buku Tentang Python', 'tanggal' => '2025-03-01', 'nama' => 'User 4', 'email' => '22410100017@dinamika.ac.id', 'status' => 'pending', 'pengarang' => 'Guido van Rossum', 'review' => 'Review buku Python', 'dosen' => 'Dosen 1', 'link' => '#', 'alasan_penolakan' => null],
            (object) ['id' => 5, 'nim' => 22410100018, 'judul' => 'Laravel untuk Pemula', 'tanggal' => '2025-03-05', 'nama' => 'User 5', 'email' => '22410100018@dinamika.ac.id', 'status' => 'accepted', 'pengarang' => 'Taylor Otwell', 'review' => 'Review buku Laravel', 'dosen' => 'Dosen 2', 'link' => '#', 'alasan_penolakan' => null],
            (object) ['id' => 6, 'nim' => 22410100019, 'judul' => 'Algoritma dan Struktur Data', 'tanggal' => '2025-03-10', 'nama' => 'User 6', 'email' => '22410100019@dinamika.ac.id', 'status' => 'pending', 'pengarang' => 'Thomas H. Cormen', 'review' => 'Review buku CLRS', 'dosen' => 'Dosen 3', 'link' => '#', 'alasan_penolakan' => null],
            (object) ['id' => 7, 'nim' => 22410100020, 'judul' => 'Pemrograman Web Lanjut', 'tanggal' => '2025-03-12', 'nama' => 'User 1', 'email' => '22410100020@dinamika.ac.id', 'status' => 'rejected', 'pengarang' => 'Web Dev', 'review' => 'Review buku Web Lanjut', 'dosen' => 'Dosen 1', 'link' => '#', 'alasan_penolakan' => 'Kurang lengkap'],
            (object) ['id' => 8, 'nim' => 22410100021, 'judul' => 'Dasar Jaringan Komputer', 'tanggal' => '2025-03-15', 'nama' => 'User 7', 'email' => '22410100021@dinamika.ac.id', 'status' => 'pending', 'pengarang' => 'Andrew S. Tanenbaum', 'review' => 'Review buku Jaringan', 'dosen' => 'Dosen 2', 'link' => '#', 'alasan_penolakan' => null],
            (object) ['id' => 9, 'nim' => 22410100022, 'judul' => 'Kecerdasan Buatan Modern', 'tanggal' => '2025-03-18', 'nama' => 'User 8', 'email' => '22410100022@dinamika.ac.id', 'status' => 'accepted', 'pengarang' => 'Stuart Russell', 'review' => 'Review buku AI', 'dosen' => 'Dosen 3', 'link' => '#', 'alasan_penolakan' => null],
            (object) ['id' => 10, 'nim' => 22410100023, 'judul' => 'Pengantar Basis Data', 'tanggal' => '2025-03-20', 'nama' => 'User 9', 'email' => '22410100023@dinamika.ac.id', 'status' => 'pending', 'pengarang' => 'Database Guru', 'review' => 'Review buku Basis Data', 'dosen' => 'Dosen 1', 'link' => '#', 'alasan_penolakan' => null],
            (object) ['id' => 11, 'nim' => 22410100024, 'judul' => 'Sistem Operasi Konsep', 'tanggal' => '2025-03-22', 'nama' => 'User 10', 'email' => '22410100024@dinamika.ac.id', 'status' => 'accepted', 'pengarang' => 'Abraham Silberschatz', 'review' => 'Review buku Sistem Operasi', 'dosen' => 'Dosen 2', 'link' => '#', 'alasan_penolakan' => null],
        ]);
    }

    /**
     * Membuat instance LengthAwarePaginator dari sebuah collection.
     *
     * @param  \Illuminate\Support\Collection  $items Collection yang akan dipaginasi.
     * @param  int  $perPage Jumlah item per halaman.
     * @param  int|null  $page Nomor halaman saat ini (null untuk otomatis).
     * @param  array  $options Opsi untuk paginator (misalnya, 'path').
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    protected function paginate(Collection $items, $perPage = 10, $page = null, $options = [])
    {
        // Tentukan nomor halaman saat ini, default ke 1 jika tidak ada.
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);

        // --- PERUBAHAN KUNCI ADA DI SINI ---
        // Siapkan opsi untuk konstruktor LengthAwarePaginator.
        // 'path' digunakan sebagai URL dasar untuk link pagination.
        // 'query' akan digunakan oleh method appends() secara internal oleh Paginator.
        $paginatorOptions = [
            'path' => $options['path'] ?? Paginator::resolveCurrentPath(), // Gunakan path dari opsi atau resolve otomatis
            'query' => request()->except('page'), // Sertakan SEMUA parameter query KECUALI 'page'
        ];
        // ------------------------------------

        // Buat instance LengthAwarePaginator.
        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(), // Ambil item untuk halaman ini.
            $items->count(),                            // Jumlah total item.
            $perPage,                                   // Item per halaman.
            $page,                                      // Halaman saat ini.
            $paginatorOptions                           // Opsi yang sudah disiapkan.
        );
    }

    public function index(Request $request)
    {
        $status = $request->input('status');
        $searchTerm = $request->input('search');

        $filteredSubmissions = $this->allSubmissions;

        // 1. Terapkan Filter Pencarian
        if ($searchTerm) {
            $filteredSubmissions = $filteredSubmissions->filter(function ($item) use ($searchTerm) {
                return stripos($item->judul, $searchTerm) !== false ||
                       stripos($item->nama, $searchTerm) !== false;
            });
        }

        // 2. Terapkan Filter Status
        if ($status && in_array($status, ['pending', 'accepted', 'rejected'])) {
             $filteredSubmissions = $filteredSubmissions->where('status', $status);
        }

        // Urutkan hasil (opsional)
        $filteredSubmissions = $filteredSubmissions->sortByDesc('tanggal');

        // 3. Buat pagination
        $submissions = $this->paginate(
            $filteredSubmissions,
            10,   // Item per halaman
            null, // Biarkan Paginator mengambil 'page' dari request
            ['path' => route('validasi.aksara.index')] // Kirim path dasar untuk URL pagination
        );

        return view('validasi-aksara', [
            'submissions' => $submissions,
        ]);
    }

    // Method show, tolak, setuju (tidak ada perubahan signifikan dari versi sebelumnya)
    public function show($id)
    {
        $peserta = $this->allSubmissions->firstWhere('id', (int)$id);
        if(!$peserta) {
            abort(404, 'Submission tidak ditemukan.');
        }
        return view('detailaksara', compact('peserta'));
    }

    public function tolak(Request $request, $id)
    {
        $request->validate(['alasan' => 'required|string|max:255']);
        $alasan = $request->input('alasan');
        $updated = false;

        $this->allSubmissions = $this->allSubmissions->map(function ($item) use ($id, $alasan, &$updated) {
            if ($item->id == (int)$id) {
                $item->status = 'rejected';
                $item->alasan_penolakan = $alasan;
                $updated = true;
            }
            return $item;
        });
        // Untuk data dummy, perubahan ini hanya sementara. Pertimbangkan Session untuk persistensi antar request.

        $redirectRoute = route('validasi.aksara.index', request()->query());

        if ($updated) {
            return redirect($redirectRoute)->with('error', 'Submission ID '.$id.' ditolak. Alasan: '.$alasan);
        } else {
            return redirect($redirectRoute)->with('error', 'Submission ID '.$id.' tidak ditemukan.');
        }
    }

    public function setuju($id)
    {
        $updated = false;
        $this->allSubmissions = $this->allSubmissions->map(function ($item) use ($id, &$updated) {
            if ($item->id == (int)$id) {
                $item->status = 'accepted';
                $item->alasan_penolakan = null;
                $updated = true;
            }
            return $item;
        });

        $redirectRoute = route('validasi.aksara.index', request()->query());

         if ($updated) {
            return redirect($redirectRoute)->with('success', 'Submission ID '.$id.' berhasil divalidasi!');
         } else {
            return redirect($redirectRoute)->with('error', 'Submission ID '.$id.' tidak ditemukan.');
         }
    }
}
