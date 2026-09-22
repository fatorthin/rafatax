<?php

namespace App\Helpers;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PiutangPerClientExporter
{
    /**
     * Export collection of clients to Excel .xlsx
     *
     * @param  \Illuminate\Support\Collection  $records
     * @param  string|null  $subtitle
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public static function export(Collection $records, ?string $subtitle = null): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Piutang');

        // Page setup
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);

        // 1. Title & Subtitle
        $sheet->setCellValue('A1', 'REKAP PIUTANG PER CLIENT');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F172A'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $downloadInfo = 'Periode: Saldo Awal (2025) & Transaksi (>= 2026) | Diunduh: ' . now()->format('d/m/Y H:i');
        if (!empty($subtitle)) {
            $downloadInfo .= ' (' . $subtitle . ')';
        }
        $sheet->setCellValue('A2', $downloadInfo);
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // 2. Table Headers (Row 4)
        $headerRow = 4;
        $headers = [
            'A' => 'No',
            'B' => 'Kode Client',
            'C' => 'Nama Client',
            'D' => 'Jenis',
            'E' => 'Saldo Awal (2025)',
            'F' => 'Total Invoice (2026)',
            'G' => 'Total Pembayaran (2026)',
            'H' => 'Sisa Piutang',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $headerRow, $label);
        }

        $headerRange = "A{$headerRow}:H{$headerRow}";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10.5,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F172A'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '334155'],
                ],
            ],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(28);

        // 3. Data Rows
        $startRow = 5;
        $currentRow = $startRow;
        $index = 1;

        $currencyFormat = '#,##0;(#,##0);"-"';

        foreach ($records as $client) {
            $saldoAwal = (float) ($client->saldo_awal ?? 0);
            $totalInvoice = (float) ($client->total_invoice ?? 0);
            $totalPembayaran = (float) ($client->total_pembayaran ?? 0);
            $totalPiutang = (float) ($client->total_piutang ?? ($saldoAwal + $totalInvoice - $totalPembayaran));

            $sheet->setCellValue('A' . $currentRow, $index);
            $sheet->setCellValue('B' . $currentRow, $client->code ?? '-');
            $sheet->setCellValue('C' . $currentRow, $client->company_name ?? '-');
            $sheet->setCellValue('D' . $currentRow, strtoupper($client->type ?? '-'));
            $sheet->setCellValue('E' . $currentRow, $saldoAwal);
            $sheet->setCellValue('F' . $currentRow, $totalInvoice);
            $sheet->setCellValue('G' . $currentRow, $totalPembayaran);
            $sheet->setCellValue('H' . $currentRow, $totalPiutang);

            // Alignments
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('D' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $currentRow . ':H' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Numbers format
            $sheet->getStyle('E' . $currentRow . ':H' . $currentRow)->getNumberFormat()->setFormatCode($currencyFormat);

            // Highlight sisa piutang
            $sheet->getStyle('H' . $currentRow)->getFont()->setBold(true);
            if ($totalPiutang > 0) {
                $sheet->getStyle('H' . $currentRow)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('B45309')); // Amber 700
            } else {
                $sheet->getStyle('H' . $currentRow)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('047857')); // Emerald 700
            }

            // Alternating zebra striping
            if ($index % 2 === 0) {
                $sheet->getStyle("A{$currentRow}:H{$currentRow}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            // Cell border
            $sheet->getStyle("A{$currentRow}:H{$currentRow}")->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setRGB('E2E8F0');

            $sheet->getRowDimension($currentRow)->setRowHeight(20);

            $currentRow++;
            $index++;
        }

        $lastDataRow = $currentRow - 1;

        // 4. Summary / Total Row
        if ($lastDataRow >= $startRow) {
            $sheet->setCellValue('A' . $currentRow, 'TOTAL');
            $sheet->mergeCells("A{$currentRow}:D{$currentRow}");
            $sheet->getStyle("A{$currentRow}:D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('E' . $currentRow, "=SUM(E{$startRow}:E{$lastDataRow})");
            $sheet->setCellValue('F' . $currentRow, "=SUM(F{$startRow}:F{$lastDataRow})");
            $sheet->setCellValue('G' . $currentRow, "=SUM(G{$startRow}:G{$lastDataRow})");
            $sheet->setCellValue('H' . $currentRow, "=SUM(H{$startRow}:H{$lastDataRow})");

            $sheet->getStyle("E{$currentRow}:H{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("E{$currentRow}:H{$currentRow}")->getNumberFormat()->setFormatCode($currencyFormat);

            $totalRange = "A{$currentRow}:H{$currentRow}";
            $sheet->getStyle($totalRange)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 10.5,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
                'borders' => [
                    'top' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '94A3B8'],
                    ],
                    'bottom' => [
                        'borderStyle' => Border::BORDER_DOUBLE,
                        'color' => ['rgb' => '475569'],
                    ],
                    'left' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1'],
                    ],
                    'right' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1'],
                    ],
                    'vertical' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1'],
                    ],
                ],
            ]);
            $sheet->getRowDimension($currentRow)->setRowHeight(24);
        }

        // 5. Auto-size columns with minimum widths
        $minWidths = [
            'A' => 8,
            'B' => 16,
            'C' => 38,
            'D' => 12,
            'E' => 22,
            'F' => 22,
            'G' => 24,
            'H' => 24,
        ];

        foreach ($minWidths as $col => $minWidth) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Apply a small width check after auto-size
        $spreadsheet->getActiveSheet()->calculateColumnWidths();
        foreach ($minWidths as $col => $minWidth) {
            $calculatedWidth = $sheet->getColumnDimension($col)->getWidth();
            if ($calculatedWidth < $minWidth) {
                $sheet->getColumnDimension($col)->setAutoSize(false);
                $sheet->getColumnDimension($col)->setWidth($minWidth);
            }
        }

        $fileName = 'Rekap_Piutang_Per_Client_' . date('Y-m-d_H-i-s') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
