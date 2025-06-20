<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MyApiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class ReportController extends Controller
{
    protected MyApiService $apiService;

    public function __construct(MyApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function generateKegiatanReportPdf(Request $request)
    {
        $startDate = $request->input('start_date_kegiatan');
        $endDate = $request->input('end_date_kegiatan');
        $searchTerm = $request->input('search');

        Log::info('[REPORT_KEGIATAN_PDF] Generate PDF. Filter Tanggal:', ['start' => $startDate, 'end' => $endDate, 'search' => $searchTerm]);

        $kegiatanList = new Collection();
        try {
            $allJadwalResult = $this->apiService->getJadwalKegiatanList();
            $allJadwal = ($allJadwalResult && !isset($allJadwalResult['_error']) && is_array($allJadwalResult)) ? collect($allJadwalResult)->map(fn($item) => (object) $item) : new Collection();

            $allMasterPemateriResult = $this->apiService->getPemateriKegiatanList();
            $allMasterPemateri = ($allMasterPemateriResult && !isset($allMasterPemateriResult['_error']) && is_array($allMasterPemateriResult)) ? collect($allMasterPemateriResult)->map(fn($item) => (object) $item) : new Collection();
            
            // Ambil semua data kehadiran sekali saja
            $allHadirResult = $this->apiService->getHadirKegiatanList();
            $allHadirKegiatan = ($allHadirResult && !isset($allHadirResult['_error']) && is_array($allHadirResult)) ? collect($allHadirResult)->map(fn($item) => (object) $item) : new Collection();

            $responseKegiatanFromApi = $this->apiService->getKegiatanList($searchTerm ? ['search' => $searchTerm] : []);

            if ($responseKegiatanFromApi && !isset($responseKegiatanFromApi['_error']) && is_array($responseKegiatanFromApi)) {
                $allRawKegiatan = collect($responseKegiatanFromApi)->map(fn($itemArray) => (object) $itemArray);

                foreach ($allRawKegiatan as $rawKegiatanObject) {
                    $k = clone $rawKegiatanObject;
                    $idKegiatanUtama = $k->id_kegiatan ?? $k->ID_KEGIATAN ?? null;

                    if (!$idKegiatanUtama) continue;

                    $k->jadwal = $allJadwal->filter(fn($jadwal) => ($jadwal->id_kegiatan ?? $jadwal->ID_KEGIATAN ?? null) == $idKegiatanUtama)
                        ->sortBy(function($jadwal) {
                            try { return Carbon::parse($jadwal->tgl_kegiatan . ' ' . $jadwal->waktu_mulai)->timestamp; } catch (\Exception $e) { return 0;}
                        })->values();
                    
                    if ($startDate && $endDate) {
                        $k->jadwal = $k->jadwal->filter(function ($jadwalSesi) use ($startDate, $endDate) {
                            if (!isset($jadwalSesi->tgl_kegiatan)) return false;
                            try {
                                $tglSesi = Carbon::parse($jadwalSesi->tgl_kegiatan);
                                return $tglSesi->betweenIncluded(Carbon::parse($startDate), Carbon::parse($endDate));
                            } catch (\Exception $e) {
                                return false;
                            }
                        });
                    }
                    
                    if ($k->jadwal->isEmpty() && ($startDate || $endDate)) {
                        continue; 
                    }

                    // Hitung total peserta hadir untuk kegiatan ini
                    $totalPesertaHadirKegiatanIni = 0;
                    $nimPesertaSudahDihitung = new Collection(); // Untuk memastikan NIM unik per kegiatan

                    foreach ($k->jadwal as $jadwalItem) {
                        $idJadwalIni = $jadwalItem->id_jadwal ?? $jadwalItem->id ?? null;
                        if ($idJadwalIni) {
                            $kehadiranUntukJadwalIni = $allHadirKegiatan->where('id_jadwal', (string) $idJadwalIni);
                            foreach($kehadiranUntukJadwalIni as $hadir) {
                                if (isset($hadir->nim) && !$nimPesertaSudahDihitung->contains($hadir->nim)) {
                                    $nimPesertaSudahDihitung->push($hadir->nim);
                                    $totalPesertaHadirKegiatanIni++;
                                }
                            }
                        }
                    }
                    $k->total_peserta_hadir = $totalPesertaHadirKegiatanIni; //

                    $k->pemateri = new Collection();
                    if ($k->jadwal->isNotEmpty()) {
                        foreach($k->jadwal as $jadwalItem) {
                            $idPemateriDiJadwal = $jadwalItem->id_pemateri ?? null;
                            if ($idPemateriDiJadwal) {
                                $foundMasterPemateri = $allMasterPemateri->firstWhere('id_pemateri', $idPemateriDiJadwal);
                                if($foundMasterPemateri){
                                    $namaPemateri = $foundMasterPemateri->nama_pemateri ?? 'Nama Pemateri Tidak Ditemukan';
                                    if (!$k->pemateri->contains('id_pemateri', $idPemateriDiJadwal)) {
                                        $k->pemateri->push((object)['id_pemateri' => $idPemateriDiJadwal, 'nama_pemateri' => $namaPemateri]);
                                    }
                                }
                            }
                        }
                    }
                    $kegiatanList->push($k);
                }
            } else {
                Log::error('[REPORT_KEGIATAN_PDF] Gagal mengambil data kegiatan dari API.', $responseKegiatanFromApi ?? []);
            }
        } catch (\Exception $e) {
            Log::error('[REPORT_KEGIATAN_PDF] Exception: ' . $e->getMessage());
            return response("Error generating PDF: " . $e->getMessage(), 500);
        }

        $data = [
            'title' => 'Laporan Daftar Kegiatan',
            'date' => date('d M Y'),
            'kegiatanList' => $kegiatanList,
            'filterStartDate' => $startDate ? Carbon::parse($startDate)->translatedFormat('d M Y') : null,
            'filterEndDate' => $endDate ? Carbon::parse($endDate)->translatedFormat('d M Y') : null,
            'searchTerm' => $searchTerm
        ];

        $pdf = Pdf::loadView('reports.kegiatan_pdf', $data)->setPaper('a4', 'landscape');
        return $pdf->download('laporan_kegiatan_'.date('YmdHis').'.pdf');
    }

    public function generateAksaraReportPdf(Request $request)
    {
        $startDate = $request->input('start_date_validasi');
        $endDate = $request->input('end_date_validasi');
        $statusFilter = $request->input('status_validasi');
        $searchTerm = $request->input('search'); 

        Log::info('[REPORT_AKSARA_PDF] Generate PDF. Filter Tanggal:', ['start' => $startDate, 'end' => $endDate, 'status' => $statusFilter, 'search' => $searchTerm]);

        $submissionsCollection = new Collection();
        try {
            $responseSubmissions = $this->apiService->getAksaraDinamikaList($searchTerm ? ['search' => $searchTerm] : []);
            if (!$responseSubmissions || isset($responseSubmissions['_error'])) {
                throw new \Exception('Gagal mengambil data Aksara Dinamika.');
            }

            $responseHistoriStatus = $this->apiService->readHistoriStatus();
            $latestHistories = new Collection();
            if ($responseHistoriStatus && !isset($responseHistoriStatus['_error']) && is_array($responseHistoriStatus)) {
                 $allHistoriStatus = collect($responseHistoriStatus)->map(function($historiItem){
                    $h = (object) $historiItem;
                    $h->id_aksara_dinamika_histori = (string) ($h->id_aksara_dinamika ?? $h->ID_AKSARA_DINAMIKA ?? null);
                    $h->tgl_status_parsed = (isset($h->tgl_status) && !empty(trim((string)$h->tgl_status))) ? Carbon::parse($h->tgl_status) : null;
                    $h->status_histori = strtolower($h->status ?? 'pending');
                    return $h;
                });
                $latestHistories = $allHistoriStatus
                    ->filter(fn($h) => $h->id_aksara_dinamika_histori !== null && $h->tgl_status_parsed !== null)
                    ->groupBy('id_aksara_dinamika_histori')
                    ->map(fn(Collection $g) => $g->sortByDesc('tgl_status_parsed')->first());
            }

            $submissionsCollection = collect($responseSubmissions)->map(function($itemArray) use ($latestHistories) {
                $item = (object) $itemArray;
                $newItem = new \stdClass();
                $currentAksaraId = (string) ($item->id_aksara_dinamika ?? $item->ID_AKSARA_DINAMIKA ?? null);
                
                $newItem->id = $currentAksaraId;
                $newItem->JUDUL = $item->judul ?? $item->JUDUL ?? ('ID Buku: ' . ($item->id_buku ?? $item->ID_BUKU ?? 'N/A'));
                $newItem->NAMA = $item->nama ?? $item->NAMA ?? ('NIM: ' . ($item->nim ?? $item->NIM ?? 'N/A'));
                $newItem->NIM = $item->nim ?? $item->NIM ?? null;
                $newItem->PENGARANG = $item->pengarang1 ?? $item->PENGARANG1 ?? null;
                
                $latestHistoryEntry = $latestHistories->get($currentAksaraId);
                $newItem->TGL_VALIDASI_CARBON = null;

                if ($latestHistoryEntry) {
                    $newItem->STATUS = $latestHistoryEntry->status_histori;
                    $newItem->TGL_VALIDASI_CARBON = $latestHistoryEntry->tgl_status_parsed;
                } else {
                    $newItem->STATUS = strtolower($item->status_validasi ?? $item->STATUS_VALIDASI ?? 'pending');
                    if (isset($item->tgl_submit)) {
                        try { $newItem->TGL_VALIDASI_CARBON = Carbon::parse($item->tgl_submit); } catch (\Exception $e) {}
                    }
                }
                $newItem->TGL_VALIDASI_DISPLAY = $newItem->TGL_VALIDASI_CARBON ? $newItem->TGL_VALIDASI_CARBON->translatedFormat('d M Y') : 'Belum Divalidasi';
                return $newItem;
            });

            if ($statusFilter && in_array($statusFilter, ['pending', 'diterima', 'ditolak'])) {
                $submissionsCollection = $submissionsCollection->filter(fn($item) => ($item->STATUS ?? 'pending') === $statusFilter);
            }

            if ($startDate && $endDate) {
                $start = Carbon::parse($startDate)->startOfDay();
                $end = Carbon::parse($endDate)->endOfDay();
                $submissionsCollection = $submissionsCollection->filter(function ($item) use ($start, $end) {
                    return $item->TGL_VALIDASI_CARBON && $item->TGL_VALIDASI_CARBON->betweenIncluded($start, $end);
                });
            }
            
        } catch (\Exception $e) {
            Log::error('[REPORT_AKSARA_PDF] Exception: ' . $e->getMessage());
            return response("Error generating PDF: " . $e->getMessage(), 500);
        }

        $data = [
            'title' => 'Laporan Validasi Aksara Dinamika',
            'date' => date('d M Y'),
            'submissions' => $submissionsCollection,
            'filterStartDate' => $startDate ? Carbon::parse($startDate)->translatedFormat('d M Y') : null,
            'filterEndDate' => $endDate ? Carbon::parse($endDate)->translatedFormat('d M Y') : null,
            'filterStatus' => $statusFilter ? ucfirst($statusFilter) : 'Semua',
            'searchTerm' => $searchTerm
        ];

        $pdf = Pdf::loadView('reports.aksara_pdf', $data)->setPaper('a4', 'landscape');
        return $pdf->download('laporan_aksara_dinamika_'.date('YmdHis').'.pdf');
    }

        public function generateDaftarHadirReportPdf(Request $request, $idKegiatan)
    {
        Log::info("[REPORT_DAFTAR_HADIR_PDF] Generate PDF untuk ID Kegiatan: {$idKegiatan}");

        try {
            // 1. Ambil data kegiatan
            $kegiatanListResult = $this->apiService->getKegiatanList();
            if (!$kegiatanListResult || isset($kegiatanListResult['_error'])) {
                throw new \Exception("Gagal mengambil data kegiatan.");
            }
            $kegiatan = collect($kegiatanListResult)->first(function ($item) use ($idKegiatan) {
                $k = (object) $item;
                return ($k->id_kegiatan ?? $k->ID_KEGIATAN ?? null) == $idKegiatan;
            });

            if (!$kegiatan) {
                return response("Kegiatan dengan ID {$idKegiatan} tidak ditemukan.", 404);
            }
            $kegiatan = (object) $kegiatan;

            // 2. Ambil semua jadwal, kehadiran, dan data civitas (untuk nama)
            $allJadwalResult = $this->apiService->getJadwalKegiatanList();
            $allJadwal = ($allJadwalResult && !isset($allJadwalResult['_error'])) ? collect($allJadwalResult)->map(fn($j) => (object)$j) : collect();

            $allHadirResult = $this->apiService->getHadirKegiatanList();
            $allHadirKegiatan = ($allHadirResult && !isset($allHadirResult['_error'])) ? collect($allHadirResult)->map(fn($h) => (object)$h) : collect();

            $allCivitasResult = $this->apiService->getCivitasList();
            $allCivitas = ($allCivitasResult && !isset($allCivitasResult['_error'])) ? collect($allCivitasResult)->keyBy('id_civitas') : collect();

            // 3. Proses dan gabungkan data
            $jadwalUntukKegiatanIni = $allJadwal->filter(fn($j) => ($j->id_kegiatan ?? $j->ID_KEGIATAN ?? null) == $idKegiatan)
                ->sortBy(function($j) {
                    try { return Carbon::parse($j->tgl_kegiatan . ' ' . $j->waktu_mulai)->timestamp; } catch (\Exception $e) { return 0; }
                })
                ->values();

            $jadwalDenganKehadiranDanNama = $jadwalUntukKegiatanIni->map(function ($jadwal) use ($allHadirKegiatan, $allCivitas) {
                $idJadwalIni = $jadwal->id_jadwal ?? $jadwal->id ?? null;
                $kehadiran = $allHadirKegiatan->where('id_jadwal', (string)$idJadwalIni);
                
                $jadwal->peserta = $kehadiran->map(function ($hadir) use ($allCivitas) {
                    $nim = $hadir->nim ?? null;
                    $nama = $allCivitas->get($nim)['nama'] ?? 'Nama tidak ditemukan';
                    return (object)['nim' => $nim, 'nama' => $nama];
                })->filter(fn($p) => $p->nim)->values();

                return $jadwal;
            });
            
            // 4. Siapkan data untuk view PDF
            $data = [
                'title' => 'Laporan Daftar Hadir',
                'date' => date('d M Y'),
                'kegiatan' => $kegiatan,
                'jadwalDenganKehadiran' => $jadwalDenganKehadiranDanNama,
            ];

            // 5. Generate PDF
            $pdf = Pdf::loadView('reports.daftar_hadir_kegiatan_pdf', $data)->setPaper('a4', 'portrait');
            $namaFile = 'daftar_hadir_' . \Illuminate\Support\Str::slug($kegiatan->judul_kegiatan ?? 'kegiatan') . '_' . date('YmdHis') . '.pdf';
            
            return $pdf->download($namaFile);

        } catch (\Exception $e) {
            Log::error("[REPORT_DAFTAR_HADIR_PDF] Exception: " . $e->getMessage());
            // Mengembalikan pesan error yang lebih informatif ke browser
            return response("Error saat generate PDF: " . $e->getMessage(), 500);
        }
    }

    public function generatePdfLeaderboard(Request $request)
    {
        $periodeId = $request->query('periode');
        $queryParams = $periodeId ? ['periode' => $periodeId] : [];

        // Panggil API untuk mendapatkan data leaderboard lengkap
        // Kita asumsikan response dari API ini sudah berisi info klaim (misal: 'tgl_terima')
        $dataMhs = $this->apiService->getMahasiswaLeaderboard($queryParams);
        $dataDosen = $this->apiService->getDosenLeaderboard($queryParams);
        $periodes = $this->apiService->getPeriodeList();

        $leaderboardMhs = $dataMhs['leaderboard'] ?? [];
        $leaderboardDosen = $dataDosen['leaderboard'] ?? [];
        
        // Logika untuk menentukan nama periode
        $selectedPeriodeName = 'Periode Saat Ini';
        if ($periodeId && isset($periodes['data'])) {
            foreach ($periodes['data'] as $periode) {
                if ($periode['id_periode'] == $periodeId) {
                    $selectedPeriodeName = $periode['nama_periode'];
                    break;
                }
            }
        } elseif (isset($dataMhs['periode_aktif'])) {
            $selectedPeriodeName = $dataMhs['periode_aktif'];
        }

        $data = [
            'leaderboardMahasiswa' => $leaderboardMhs,
            'leaderboardDosen' => $leaderboardDosen,
            'namaPeriode' => $selectedPeriodeName,
            'tanggalCetak' => now()->translatedFormat('d F Y')
        ];

        $pdf = PDF::loadView('reports.leaderboard_pdf', $data);
        return $pdf->stream('laporan-leaderboard-'.$selectedPeriodeName.'.pdf');
    }

    public function generatePdfPenerimaReward(Request $request)
    {
        try {
            // 1. Ambil periodeId dari request
            $periodeId = $request->query('periode');

            // 2. Teruskan periodeId ke service untuk memfilter data di API
            $penerimaData = $this->apiService->getPenerimaReward($periodeId);
            $allWinners = $penerimaData['data'] ?? [];
            
            $groupedByLevel = (new Collection($allWinners))->groupBy('level_reward')->sortKeys();

            // 3. Logika untuk menampilkan nama periode di judul PDF
            $periodes = $this->apiService->getPeriodeList();
            $namaPeriode = 'Periode Berjalan';
            $rentangTanggalPeriode = '';

            if ($periodeId && !empty($periodes['data'])) {
                $selectedPeriode = collect($periodes['data'])->firstWhere('id_periode', $periodeId);
                if ($selectedPeriode) {
                    $namaPeriode = $selectedPeriode['nama_periode'];
                    $rentangTanggalPeriode = \Carbon\Carbon::parse($selectedPeriode['tgl_mulai'])->translatedFormat('d M Y') . ' - ' . \Carbon\Carbon::parse($selectedPeriode['tgl_selesai'])->translatedFormat('d M Y');
                }
            } else if (!empty($periodes['data'])) {
                $currentPeriode = collect($periodes['data'])->firstWhere('status', 'aktif');
                if ($currentPeriode) {
                     $namaPeriode = $currentPeriode['nama_periode'];
                     $rentangTanggalPeriode = \Carbon\Carbon::parse($currentPeriode['tgl_mulai'])->translatedFormat('d M Y') . ' - ' . \Carbon\Carbon::parse($currentPeriode['tgl_selesai'])->translatedFormat('d M Y');
                }
            }

            $data = [
                'groupedWinners' => $groupedByLevel,
                'namaPeriode' => $namaPeriode,
                'rentangTanggalPeriode' => $rentangTanggalPeriode
            ];

            $pdf = PDF::loadView('reports.penerima_reward_visual_pdf', $data);
            return $pdf->setPaper('a4', 'portrait')->stream('laporan-klaim-hadiah.pdf');

        } catch (\Exception $e) {
            Log::error('Gagal membuat PDF Laporan Reward: ' . $e->getMessage());
            return response("Terjadi kesalahan saat membuat laporan PDF.", 500);
        }
    }
}
