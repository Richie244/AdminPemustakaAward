<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MyApiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
}