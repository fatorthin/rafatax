<?php

namespace App\Http\Controllers;

use App\Models\CaseProject;
use App\Models\CaseProjectPowerOfAttorney;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CaseProjectDocumentController extends Controller
{
    /**
     * Preview Surat Kuasa Khusus & Surat Pernyataan (1 file PDF gabungan)
     */
    public function previewDokumenKuasa($caseProjectId)
    {
        try {
            list($pdf, $filename) = $this->generatePdf($caseProjectId);
            return $pdf->stream($filename);
        } catch (\Throwable $e) {
            return response('Gagal memproses dokumen PDF: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Download Surat Kuasa Khusus & Surat Pernyataan (1 file PDF gabungan)
     */
    public function downloadDokumenKuasa($caseProjectId)
    {
        try {
            list($pdf, $filename) = $this->generatePdf($caseProjectId);
            return $pdf->download($filename);
        } catch (\Throwable $e) {
            return response('Gagal mengunduh dokumen PDF: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Helper to prepare PDF instance & filename
     */
    private function generatePdf($caseProjectId)
    {
        $caseProject = CaseProject::with(['client', 'mou', 'powerOfAttorney'])->findOrFail($caseProjectId);
        $poa = $caseProject->powerOfAttorney;

        // If no POA record saved yet, construct a temporary one from defaults
        if (!$poa) {
            $client = $caseProject->client;
            $poa = new CaseProjectPowerOfAttorney([
                'case_project_id' => $caseProject->id,
                'nomor_surat_kuasa' => $caseProject->power_of_attorney_number ?? '',
                'tanggal_surat_kuasa' => $caseProject->power_of_attorney_date ?? now(),
                'jenis_wp' => ($client?->jenis_wp === 'badan') ? 'BADAN' : 'ORANG PRIBADI',
                'pemberi_nama' => $client?->owner_name ?? ($client?->company_name ?? ''),
                'pemberi_npwp' => $client?->npwp ?? '',
                'pemberi_jabatan' => $client?->owner_role ?? 'Direktur',
                'bertindak_selaku' => ($client?->jenis_wp === 'badan') ? 'wakil_wajib_pajak' : 'wajib_pajak',
                'wp_badan_nama' => $client?->company_name ?? '',
                'wp_badan_npwp' => $client?->npwp ?? '',
                'kuasa_kategori' => 'konsultan_pajak',
                'penerima_nama' => 'ANTIN OKFITASARI, S.E., S.H., M.Si., Ak., CA.AB., BKP., CATr., ACPA',
                'penerima_npwp' => '',
                'penerima_izin_no' => '',
                'penerima_status_keluarga' => '-',
                'kewajiban_terkait' => $caseProject->case_type ?? 'Pemeriksaan Perpajakan',
                'jenis_pajak' => 'Pajak Penghasilan Badan / PPN',
                'masa_pajak_pilihan' => 'Tahun Pajak',
                'masa_tahun_pajak' => $caseProject->mou?->tahun_pajak ?? (string) now()->year,
                'tanggal_mulai' => now(),
                'tanggal_selesai' => now()->addMonths(6),
                'rincian_kuasa' => CaseProjectPowerOfAttorney::getDefaultRincianKuasa(),
                'pernyataan_nomor' => $caseProject->power_of_attorney_number ?? '',
                'pernyataan_tanggal' => $caseProject->power_of_attorney_date ?? now(),
                'pernyataan_pemberi_alamat' => $client?->address ?? '',
                'pernyataan_penerima_alamat' => 'Dk. Nampan RT 02 RW 01, Madegondo, Grogol, Sukoharjo',
                'pernyataan_status_hubungan' => 'Konsultan Pajak yang memenuhi persyaratan sesuai ketentuan peraturan perundang-undangan perpajakan',
            ]);
        }

        $pdf = Pdf::loadView('pdf.dokumen-kuasa-gabungan', [
            'caseProject' => $caseProject,
            'poa' => $poa,
        ])->setPaper('a4', 'portrait')
          ->setOption(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        $safeClientName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $caseProject->client?->company_name ?? 'Client');
        $safeNumber = preg_replace('/[^A-Za-z0-9_\-]/', '-', $poa->nomor_surat_kuasa ?: 'Surat-Kuasa');
        $filename = "Dokumen-Kuasa-{$safeNumber}-{$safeClientName}.pdf";

        return [$pdf, $filename];
    }
}
