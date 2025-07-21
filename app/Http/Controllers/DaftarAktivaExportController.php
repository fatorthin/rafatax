<?php

namespace App\Http\Controllers;

use App\Models\DaftarAktivaTetap;
use App\Models\DepresiasiAktivaTetap;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DaftarAktivaExportController extends Controller
{
    public function export($bulan, $tahun)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $sheet->setCellValue('A1', 'DAFTAR AKTIVA TETAP');
        $sheet->setCellValue('A2', 'Periode: ' . Carbon::create($tahun, $bulan, 1)->format('F Y'));
        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A1:A2')->getFont()->setBold(true);
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal('center');

        // Set headers
        $headers = [
            'Nama Aktiva',
            'Tahun Perolehan',
            'Harga Perolehan',
            'Tarif (%)',
            'Akumulasi Penyusutan Lalu',
            'Nilai Buku Lalu',
            'Penyusutan Bulan Ini',
            'Akumulasi s/d Bulan Ini',
            'Nilai Buku'
        ];

        foreach (array_values($headers) as $key => $header) {
            $sheet->setCellValue(chr(65 + $key) . '4', $header);
        }
        $sheet->getStyle('A4:I4')->getFont()->setBold(true);

        // Get data
        $data = DaftarAktivaTetap::all();
        $row = 5;

        foreach ($data as $item) {
            $tanggal = Carbon::create($tahun, $bulan, 1)->startOfMonth();
            $tanggalAkhir = $tanggal->copy()->endOfMonth();
            
            $akumulasiLalu = DepresiasiAktivaTetap::where('daftar_aktiva_tetap_id', $item->id)
                ->where('tanggal_penyusutan', '<', $tanggal->format('Y-m-d'))
                ->sum('jumlah_penyusutan');

            $penyusutanBulanIni = DepresiasiAktivaTetap::where('daftar_aktiva_tetap_id', $item->id)
                ->whereBetween('tanggal_penyusutan', [$tanggal, $tanggalAkhir])
                ->sum('jumlah_penyusutan');

            $akumulasiSdBulanIni = DepresiasiAktivaTetap::where('daftar_aktiva_tetap_id', $item->id)
                ->where('tanggal_penyusutan', '<=', $tanggalAkhir)
                ->sum('jumlah_penyusutan');

            $sheet->setCellValue('A' . $row, $item->deskripsi);
            $sheet->setCellValue('B' . $row, Carbon::parse($item->tahun_perolehan)->format('M Y'));
            $sheet->setCellValue('C' . $row, $item->harga_perolehan);
            $sheet->setCellValue('D' . $row, $item->tarif_penyusutan . '%');
            $sheet->setCellValue('E' . $row, $akumulasiLalu);
            
            // Check if asset was acquired before the selected month
            if ($item->tahun_perolehan < $tanggal) {
                $nilaiBukuLalu = $item->harga_perolehan - $akumulasiLalu;
            } else {
                $nilaiBukuLalu = 0;
            }
            
            $sheet->setCellValue('F' . $row, $nilaiBukuLalu);
            $sheet->setCellValue('G' . $row, $penyusutanBulanIni);
            $sheet->setCellValue('H' . $row, $akumulasiSdBulanIni);
            $sheet->setCellValue('I' . $row, $item->harga_perolehan - $akumulasiSdBulanIni);

            $row++;
        }

        // Add totals
        $lastRow = $row - 1;
        $sheet->setCellValue('A' . $row, 'Total');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        
        foreach (['C', 'E', 'F', 'G', 'H', 'I'] as $col) {
            $sheet->setCellValue($col . $row, "=SUM({$col}5:{$col}{$lastRow})");
        }

        // Auto-size columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Format numbers
        foreach (['C', 'E', 'F', 'G', 'H', 'I'] as $col) {
            $sheet->getStyle($col . '5:' . $col . $row)->getNumberFormat()
                ->setFormatCode('#,##0');
        }

        // Create response
        $writer = new Xlsx($spreadsheet);
        $filename = 'daftar-aktiva-tetap-' . Carbon::create($tahun, $bulan, 1)->format('F-Y') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
} 