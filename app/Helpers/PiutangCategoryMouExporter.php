<?php

namespace App\Helpers;

use App\Models\CategoryMou;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PiutangCategoryMouExporter
{
    /**
     * Export rekapitulasi piutang per kategori MoU ke Excel.
     */
    public static function exportSummary(Collection $records, ?string $subtitle = null): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Piutang Kategori');

        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);

        // Header Dokumen
        $sheet->setCellValue('A1', 'REKAPITULASI PIUTANG PER KATEGORI MOU');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $downloadInfo = 'Diunduh pada: ' . now()->format('d/m/Y H:i');
        if (!empty($subtitle)) {
            $downloadInfo = $subtitle . ' | ' . $downloadInfo;
        }
        $sheet->setCellValue('A2', $downloadInfo);
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Table Header
        $headerRow = 4;
        $headers = [
            'A' => 'No',
            'B' => 'Kategori MoU',
            'C' => 'Jumlah MoU',
            'D' => 'Total Invoice (Tagihan)',
            'E' => 'Total Pembayaran',
            'F' => 'Sisa Piutang',
            'G' => 'Rasio Pelunasan',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $headerRow, $label);
        }

        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'], // Slate 800
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ];
        $sheet->getStyle("A{$headerRow}:G{$headerRow}")->applyFromArray($headerStyle);
        $sheet->getRowDimension($headerRow)->setRowHeight(26);

        // Data Rows
        $currentRow = 5;
        $no = 1;
        $sumMou = 0;
        $sumInvoice = 0;
        $sumPaid = 0;
        $sumPiutang = 0;

        foreach ($records as $item) {
            $totalInv = (float) ($item->total_invoice ?? 0);
            $totalPay = (float) ($item->total_pembayaran ?? 0);
            $sisa = (float) ($item->total_piutang ?? 0);
            $jmlMou = (int) ($item->jumlah_mou ?? 0);
            $ratio = $totalInv > 0 ? round(($totalPay / $totalInv) * 100, 1) : 100;

            $sumMou += $jmlMou;
            $sumInvoice += $totalInv;
            $sumPaid += $totalPay;
            $sumPiutang += $sisa;

            $sheet->setCellValue("A{$currentRow}", $no++);
            $sheet->setCellValue("B{$currentRow}", $item->name);
            $sheet->setCellValue("C{$currentRow}", $jmlMou);
            $sheet->setCellValue("D{$currentRow}", $totalInv);
            $sheet->setCellValue("E{$currentRow}", $totalPay);
            $sheet->setCellValue("F{$currentRow}", $sisa);
            $sheet->setCellValue("G{$currentRow}", $ratio . '%');

            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$currentRow}:F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle("D{$currentRow}:F{$currentRow}")->getNumberFormat()->setFormatCode('#,##0');

            if ($currentRow % 2 == 0) {
                $sheet->getStyle("A{$currentRow}:G{$currentRow}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            $currentRow++;
        }

        // Summary Row
        $sheet->setCellValue("A{$currentRow}", 'TOTAL');
        $sheet->mergeCells("A{$currentRow}:B{$currentRow}");
        $sheet->setCellValue("C{$currentRow}", $sumMou);
        $sheet->setCellValue("D{$currentRow}", $sumInvoice);
        $sheet->setCellValue("E{$currentRow}", $sumPaid);
        $sheet->setCellValue("F{$currentRow}", $sumPiutang);
        $overallRatio = $sumInvoice > 0 ? round(($sumPaid / $sumInvoice) * 100, 1) : 100;
        $sheet->setCellValue("G{$currentRow}", $overallRatio . '%');

        $totalStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '0F172A']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '0F172A']],
            ],
        ];
        $sheet->getStyle("A{$currentRow}:G{$currentRow}")->applyFromArray($totalStyle);
        $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$currentRow}:F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("G{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$currentRow}:F{$currentRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getRowDimension($currentRow)->setRowHeight(22);

        // Border seluruh tabel
        $dataBorderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ];
        $sheet->getStyle("A5:G{$currentRow}")->applyFromArray($dataBorderStyle);

        // Auto width
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Rekap_Piutang_Kategori_MoU_' . now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export detail piutang 1 kategori MoU ke Excel (3 sheet: Invoices, Pembayaran, Breakdown MoU).
     */
    public static function exportDetail(CategoryMou $category, array $data, ?string $subtitle = null): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);

        // ==========================================
        // SHEET 1: DAFTAR INVOICE
        // ==========================================
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Daftar Invoice');

        $sheet1->setCellValue('A1', 'RINCIAN INVOICE: ' . strtoupper($category->name));
        $sheet1->mergeCells('A1:I1');
        $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(13)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));
        $sheet1->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $subText = 'Periode: ' . ($subtitle ?? 'Tahun Berjalan') . ' | Diunduh: ' . now()->format('d/m/Y H:i');
        $sheet1->setCellValue('A2', $subText);
        $sheet1->mergeCells('A2:I2');
        $sheet1->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));
        $sheet1->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers1 = [
            'A' => 'No',
            'B' => 'Tanggal Invoice',
            'C' => 'No. Invoice',
            'D' => 'Klien / Perusahaan',
            'E' => 'No. MoU',
            'F' => 'Tipe',
            'G' => 'Nominal Tagihan (Rp)',
            'H' => 'Status Pembayaran',
            'I' => 'Status Kirim',
        ];
        $hRow = 4;
        foreach ($headers1 as $col => $lbl) {
            $sheet1->setCellValue($col . $hRow, $lbl);
        }
        $sheet1->getStyle("A{$hRow}:I{$hRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ]);
        $sheet1->getRowDimension($hRow)->setRowHeight(24);

        $row = 5;
        $no = 1;
        $totalInvAmount = 0;
        foreach ($data['invoices'] as $inv) {
            $amt = (float) $inv['amount'];
            $totalInvAmount += $amt;

            $sheet1->setCellValue("A{$row}", $no++);
            $sheet1->setCellValue("B{$row}", $inv['invoice_date'] ? \Carbon\Carbon::parse($inv['invoice_date'])->format('d/m/Y') : '-');
            $sheet1->setCellValue("C{$row}", $inv['invoice_number']);
            $sheet1->setCellValue("D{$row}", $inv['client_name'] ?? '-');
            $sheet1->setCellValue("E{$row}", $inv['mou_number'] ?? '-');
            $sheet1->setCellValue("F{$row}", strtoupper($inv['invoice_type'] ?? '-'));
            $sheet1->setCellValue("G{$row}", $amt);
            $sheet1->setCellValue("H{$row}", ucfirst($inv['invoice_status'] ?? '-'));
            $sheet1->setCellValue("I{$row}", $inv['is_send_invoice'] ? 'Sudah' : 'Belum');

            $sheet1->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet1->getStyle("D{$row}:E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet1->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet1->getStyle("H{$row}:I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0');

            if ($row % 2 == 0) {
                $sheet1->getStyle("A{$row}:I{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }
            $row++;
        }

        // Total Row Sheet 1
        $sheet1->setCellValue("A{$row}", 'TOTAL');
        $sheet1->mergeCells("A{$row}:F{$row}");
        $sheet1->setCellValue("G{$row}", $totalInvAmount);
        $sheet1->getStyle("A{$row}:I{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
        ]);
        $sheet1->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet1->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet1->getStyle("A5:I{$row}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ]);
        foreach (range('A', 'I') as $col) {
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }

        // ==========================================
        // SHEET 2: DAFTAR PEMBAYARAN
        // ==========================================
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Riwayat Pembayaran');

        $sheet2->setCellValue('A1', 'RIWAYAT PEMBAYARAN: ' . strtoupper($category->name));
        $sheet2->mergeCells('A1:G1');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(13)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));
        $sheet2->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->setCellValue('A2', $subText);
        $sheet2->mergeCells('A2:G2');
        $sheet2->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));
        $sheet2->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers2 = [
            'A' => 'No',
            'B' => 'Tanggal Pembayaran',
            'C' => 'No. Invoice',
            'D' => 'Klien / Perusahaan',
            'E' => 'No. MoU',
            'F' => 'Rekening Penerima',
            'G' => 'Nominal Bayar (Rp)',
        ];
        $hRow = 4;
        foreach ($headers2 as $col => $lbl) {
            $sheet2->setCellValue($col . $hRow, $lbl);
        }
        $sheet2->getStyle("A{$hRow}:G{$hRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '047857']], // Emerald 700
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ]);
        $sheet2->getRowDimension($hRow)->setRowHeight(24);

        $row = 5;
        $no = 1;
        $totalPaidAmount = 0;
        foreach ($data['payments'] as $pay) {
            $amt = (float) $pay['amount'];
            $totalPaidAmount += $amt;

            $sheet2->setCellValue("A{$row}", $no++);
            $sheet2->setCellValue("B{$row}", $pay['date'] ? \Carbon\Carbon::parse($pay['date'])->format('d/m/Y') : '-');
            $sheet2->setCellValue("C{$row}", $pay['invoice_number']);
            $sheet2->setCellValue("D{$row}", $pay['client_name'] ?? '-');
            $sheet2->setCellValue("E{$row}", $pay['mou_number'] ?? '-');
            $sheet2->setCellValue("F{$row}", $pay['rek_transfer'] ?? '-');
            $sheet2->setCellValue("G{$row}", $amt);

            $sheet2->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle("C{$row}:E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet2->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet2->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet2->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0');

            if ($row % 2 == 0) {
                $sheet2->getStyle("A{$row}:G{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }
            $row++;
        }

        // Total Row Sheet 2
        $sheet2->setCellValue("A{$row}", 'TOTAL');
        $sheet2->mergeCells("A{$row}:F{$row}");
        $sheet2->setCellValue("G{$row}", $totalPaidAmount);
        $sheet2->getStyle("A{$row}:G{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D1FAE5']],
        ]);
        $sheet2->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet2->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet2->getStyle("A5:G{$row}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ]);
        foreach (range('A', 'G') as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        // ==========================================
        // SHEET 3: BREAKDOWN PER MOU
        // ==========================================
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Rincian per MoU');

        $sheet3->setCellValue('A1', 'BREAKDOWN PER MOU: ' . strtoupper($category->name));
        $sheet3->mergeCells('A1:H1');
        $sheet3->getStyle('A1')->getFont()->setBold(true)->setSize(13)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));
        $sheet3->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet3->setCellValue('A2', $subText);
        $sheet3->mergeCells('A2:H2');
        $sheet3->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));
        $sheet3->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers3 = [
            'A' => 'No',
            'B' => 'No. MoU',
            'C' => 'Klien / Perusahaan',
            'D' => 'Tipe',
            'E' => 'Nilai Biaya MoU (Rp)',
            'F' => 'Total Invoice (Rp)',
            'G' => 'Terbayar (Rp)',
            'H' => 'Sisa Piutang (Rp)',
        ];
        $hRow = 4;
        foreach ($headers3 as $col => $lbl) {
            $sheet3->setCellValue($col . $hRow, $lbl);
        }
        $sheet3->getStyle("A{$hRow}:H{$hRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0369A1']], // Sky 700
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ]);
        $sheet3->getRowDimension($hRow)->setRowHeight(24);

        $row = 5;
        $no = 1;
        $totalCostMou = 0;
        $totalInvMou = 0;
        $totalPayMou = 0;
        $totalPiutangMou = 0;

        foreach ($data['mou_breakdown'] as $mou) {
            $cost = (float) $mou['cost_mou'];
            $inv = (float) $mou['total_invoice'];
            $pay = (float) $mou['total_paid'];
            $piutang = (float) $mou['sisa_piutang'];

            $totalCostMou += $cost;
            $totalInvMou += $inv;
            $totalPayMou += $pay;
            $totalPiutangMou += $piutang;

            $sheet3->setCellValue("A{$row}", $no++);
            $sheet3->setCellValue("B{$row}", $mou['mou_number']);
            $sheet3->setCellValue("C{$row}", $mou['client_name'] ?? '-');
            $sheet3->setCellValue("D{$row}", strtoupper($mou['type'] ?? '-'));
            $sheet3->setCellValue("E{$row}", $cost);
            $sheet3->setCellValue("F{$row}", $inv);
            $sheet3->setCellValue("G{$row}", $pay);
            $sheet3->setCellValue("H{$row}", $piutang);

            $sheet3->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet3->getStyle("B{$row}:C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet3->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet3->getStyle("E{$row}:H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet3->getStyle("E{$row}:H{$row}")->getNumberFormat()->setFormatCode('#,##0');

            if ($row % 2 == 0) {
                $sheet3->getStyle("A{$row}:H{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }
            $row++;
        }

        // Total Row Sheet 3
        $sheet3->setCellValue("A{$row}", 'TOTAL');
        $sheet3->mergeCells("A{$row}:D{$row}");
        $sheet3->setCellValue("E{$row}", $totalCostMou);
        $sheet3->setCellValue("F{$row}", $totalInvMou);
        $sheet3->setCellValue("G{$row}", $totalPayMou);
        $sheet3->setCellValue("H{$row}", $totalPiutangMou);
        $sheet3->getStyle("A{$row}:H{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0F2FE']],
        ]);
        $sheet3->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet3->getStyle("E{$row}:H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet3->getStyle("E{$row}:H{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet3->getStyle("A5:H{$row}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ]);
        foreach (range('A', 'H') as $col) {
            $sheet3->getColumnDimension($col)->setAutoSize(true);
        }

        // Set active sheet ke sheet 1 saat pertama kali dibuka
        $spreadsheet->setActiveSheetIndex(0);

        $catNameClean = preg_replace('/[^A-Za-z0-9_\-]/', '_', $category->name);
        $filename = "Detail_Piutang_Kategori_{$catNameClean}_" . now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }
}

