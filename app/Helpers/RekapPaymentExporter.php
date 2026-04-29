<?php

namespace App\Helpers;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RekapPaymentExporter
{
    public static function export(array $data)
    {
        $cutOffDate = $data['cut_off_date'];

        $query = \App\Models\MoU::with(['client', 'categoryMou', 'cost_lists'])
            ->whereHas('client');

        if (!empty($data['type'])) {
            $query->where('type', $data['type']);
        }
        if (!empty($data['category_mou_id'])) {
            $query->whereIn('category_mou_id', (array) $data['category_mou_id']);
        }
        if (!empty($data['status'])) {
            $query->where('status', $data['status']);
        }

        $mous = $query->orderBy('created_at', 'desc')->get();

        $mouIds = $mous->pluck('id')->toArray();
        $invoices = \App\Models\Invoice::with('costListInvoices')
            ->whereIn('mou_id', $mouIds)
            ->where('invoice_date', '<=', $cutOffDate)
            ->orderBy('invoice_date', 'asc')
            ->get()
            ->groupBy('mou_id');

        $cutOffFormatted = Carbon::parse($cutOffDate)->locale('id')->translatedFormat('d F Y');
        $lastCol = 'T'; // 20 columns A-T

        return response()->streamDownload(function () use ($mous, $invoices, $cutOffFormatted, $lastCol) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Rekap Payment & Piutang');

            // ─── TITLE ───
            $sheet->setCellValue('A1', 'REKAP PAYMENT & PIUTANG KLIEN');
            $sheet->mergeCells("A1:{$lastCol}1");
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A2', 'Per Tanggal: ' . $cutOffFormatted);
            $sheet->mergeCells("A2:{$lastCol}2");
            $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(11);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // ─── HEADERS ───
            $hr = 4;
            $headers = [
                'A' => 'No',
                'B' => 'Nama Perusahaan Klien',
                'C' => 'Tipe Klien',
                'D' => 'Tipe MoU',
                'E' => 'Case / Kategori MoU',
                'F' => 'No. MoU',
                'G' => 'Deskripsi MoU',
                'H' => 'Status MoU',
                'I' => 'Nominal Bulanan',
                'J' => 'Nominal Tahunan',
                'K' => 'Nominal Case',
                'L' => 'Total Nominal MoU',
                'M' => 'No. Invoice',
                'N' => 'Deskripsi Invoice',
                'O' => 'Tgl Invoice',
                'P' => 'Due Date',
                'Q' => 'Status Invoice',
                'R' => 'Tanggal Transfer',
                'S' => 'Nominal Invoice',
                'T' => 'Piutang (Sisa)',
            ];

            foreach ($headers as $col => $label) {
                $sheet->setCellValue($col . $hr, $label);
            }

            $headerRange = "A{$hr}:{$lastCol}{$hr}";
            $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new Color('FFFFFF'));
            $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2D3748');
            $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

            $cf = '#,##0';
            $row = $hr + 1;
            $no = 1;
            $gtMou = 0;
            $gtBulanan = 0;
            $gtTahunan = 0;
            $gtCase = 0;
            $gtInvoice = 0;
            $gtPaid = 0;
            $gtPiutang = 0;

            $excelData = [];
            $mouMergeRanges = [];
            $mouRows = [];
            $piutangRows = [];
            $statusStyles = [];
            $mouCols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'T'];

            // Fast sum helper to avoid repeated sum calls on relationships
            // But cost_lists and costListInvoices are collections in memory so sum() is relatively fast.
            // Still, doing it once is better.

            foreach ($mous as $mou) {
                $mouInvoices = $invoices->get($mou->id, collect());
                $totalMou = $mou->cost_lists->sum('total_amount');

                // Split nominal by CoA
                $nomBulanan = $mou->cost_lists->where('coa_id', 119)->sum('total_amount');
                $nomTahunan = $mou->cost_lists->where('coa_id', 120)->sum('total_amount');
                $nomCase = $mou->cost_lists->whereNotIn('coa_id', [119, 120])->sum('total_amount');

                $totalInvPaid = 0;
                $totalInvAll = 0;
                foreach ($mouInvoices as $inv) {
                    $amt = $inv->costListInvoices->sum('amount');
                    $inv->cached_amount = $amt; // Cache to avoid recalculating
                    $totalInvAll += $amt;
                    if ($inv->invoice_status === 'paid') {
                        $totalInvPaid += $amt;
                    }
                }

                $piutang = max($totalMou - $totalInvPaid, 0);

                $gtMou += $totalMou;
                $gtBulanan += $nomBulanan;
                $gtTahunan += $nomTahunan;
                $gtCase += $nomCase;
                $gtInvoice += $totalInvAll;
                $gtPaid += $totalInvPaid;
                $gtPiutang += $piutang;

                $startRow = $row;
                $invCount = $mouInvoices->count();

                $baseData = [
                    $no,
                    $mou->client->company_name ?? '-',
                    $mou->client->jenis_wp ? ucfirst($mou->client->jenis_wp) : ($mou->client->type ?? '-'),
                    strtoupper($mou->type ?? '-'),
                    $mou->categoryMou->name ?? '-',
                    $mou->mou_number ?? '-',
                    $mou->description ?? '-',
                    ucfirst($mou->status ?? '-'),
                    $nomBulanan,
                    $nomTahunan,
                    $nomCase,
                    $totalMou
                ];

                if ($invCount === 0) {
                    $excelData[] = array_merge($baseData, [
                        '-', '-', '-', '-', 'Belum ada invoice', '-', 0, $piutang
                    ]);
                    $mouRows[] = "A{$row}:L{$row}";
                    $piutangRows[] = "T{$row}:T{$row}";
                    $row++;
                } else {
                    $first = true;
                    foreach ($mouInvoices->values() as $inv) {
                        $invData = [
                            $inv->invoice_number ?? '-',
                            $inv->description ?? '-',
                            $inv->invoice_date ? Carbon::parse($inv->invoice_date)->format('d/m/Y') : '-',
                            $inv->due_date ? Carbon::parse($inv->due_date)->format('d/m/Y') : '-',
                            ucfirst($inv->invoice_status ?? '-'),
                            $inv->tgl_transfer ? Carbon::parse($inv->tgl_transfer)->format('d/m/Y') : '-',
                            $inv->cached_amount
                        ];

                        if ($first) {
                            $excelData[] = array_merge($baseData, $invData, [$piutang]);
                            $first = false;
                        } else {
                            $excelData[] = array_merge(array_fill(0, 12, null), $invData, [null]);
                        }

                        $sc = match ($inv->invoice_status) {
                            'paid' => '27AE60',
                            'unpaid' => 'E67E22',
                            'overdue' => 'E74C3C',
                            default => '7F8C8D',
                        };
                        $statusStyles[$sc][] = "Q{$row}";
                        $row++;
                    }

                    $endRow = $row - 1;
                    if ($invCount > 1) {
                        foreach ($mouCols as $c) {
                            $mouMergeRanges[] = "{$c}{$startRow}:{$c}{$endRow}";
                        }
                    }

                    $mouRows[] = "A{$startRow}:L{$endRow}";
                    $piutangRows[] = "T{$startRow}:T{$endRow}";
                }

                $no++;
            }

            // Write all data at once using bulk insertion
            if (!empty($excelData)) {
                $sheet->fromArray($excelData, null, 'A5');
            }

            // Apply batch styling
            $dataEndRow = $row - 1;
            if ($dataEndRow >= 5) {
                // Set alignment for all rows
                $sheet->getStyle("A5:T{$dataEndRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                
                // Number formatting for entire columns
                $sheet->getStyle("I5:L{$dataEndRow}")->getNumberFormat()->setFormatCode($cf);
                $sheet->getStyle("S5:T{$dataEndRow}")->getNumberFormat()->setFormatCode($cf);

                // Merges
                foreach ($mouMergeRanges as $mr) {
                    $sheet->mergeCells($mr);
                }

                // MoU Block Styles
                $mouStyleArray = [
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF5FB']],
                    'font' => ['bold' => true],
                ];
                foreach ($mouRows as $mr) {
                    $sheet->getStyle($mr)->applyFromArray($mouStyleArray);
                }

                // Piutang Block Styles
                $piutangStyleArray = [
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FDEDEC']],
                    'font' => ['bold' => true],
                ];
                foreach ($piutangRows as $pr) {
                    $sheet->getStyle($pr)->applyFromArray($piutangStyleArray);
                }

                // Status colors
                foreach ($statusStyles as $color => $cells) {
                    foreach (array_chunk($cells, 50) as $chunk) {
                        $range = implode(',', $chunk);
                        $sheet->getStyle($range)->getFont()->getColor()->setRGB($color);
                        $sheet->getStyle($range)->getFont()->setBold(true);
                    }
                }
            }

            // ─── GRAND TOTAL ───
            $tr = $row;
            $sheet->setCellValue("G{$tr}", 'GRAND TOTAL');
            $sheet->getStyle("G{$tr}")->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle("G{$tr}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->setCellValue("I{$tr}", $gtBulanan);
            $sheet->setCellValue("J{$tr}", $gtTahunan);
            $sheet->setCellValue("K{$tr}", $gtCase);
            $sheet->setCellValue("L{$tr}", $gtMou);
            $sheet->setCellValue("S{$tr}", $gtInvoice);
            $sheet->setCellValue("T{$tr}", $gtPiutang);

            foreach (['I', 'J', 'K', 'L', 'S', 'T'] as $c) {
                $sheet->getStyle("{$c}{$tr}")->getNumberFormat()->setFormatCode($cf);
            }

            $totalRange = "A{$tr}:{$lastCol}{$tr}";
            $sheet->getStyle($totalRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F7DC6F');
            $sheet->getStyle($totalRange)->getFont()->setBold(true);
            $sheet->getStyle($totalRange)->getBorders()->getTop()->setBorderStyle(Border::BORDER_DOUBLE);

            // ─── SUMMARY ───
            $sr = $tr + 2;
            $sheet->setCellValue("A{$sr}", 'Keterangan:');
            $sheet->getStyle("A{$sr}")->getFont()->setBold(true);
            $sheet->setCellValue('A' . ($sr + 1), 'Total Nominal MoU');
            $sheet->setCellValue('C' . ($sr + 1), $gtMou);
            $sheet->getStyle('C' . ($sr + 1))->getNumberFormat()->setFormatCode($cf);
            $sheet->setCellValue('A' . ($sr + 2), 'Total Invoice Terbayar');
            $sheet->setCellValue('C' . ($sr + 2), $gtPaid);
            $sheet->getStyle('C' . ($sr + 2))->getNumberFormat()->setFormatCode($cf);
            $sheet->setCellValue('A' . ($sr + 3), 'Total Piutang');
            $sheet->setCellValue('C' . ($sr + 3), $gtPiutang);
            $sheet->getStyle('C' . ($sr + 3))->getNumberFormat()->setFormatCode($cf);
            $sheet->getStyle('A' . ($sr + 3) . ':C' . ($sr + 3))->getFont()->setBold(true)->getColor()->setRGB('E74C3C');

            // ─── BORDERS ───
            $sheet->getStyle("A{$hr}:{$lastCol}{$tr}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            // ─── COLUMN WIDTHS ───
            foreach (range('A', $lastCol) as $c) {
                $sheet->getColumnDimension($c)->setAutoSize(true);
            }
            $sheet->getColumnDimension('B')->setAutoSize(false)->setWidth(30);
            $sheet->getColumnDimension('F')->setAutoSize(false)->setWidth(25);
            $sheet->getColumnDimension('G')->setAutoSize(false)->setWidth(25);
            $sheet->getColumnDimension('M')->setAutoSize(false)->setWidth(25);
            $sheet->getColumnDimension('N')->setAutoSize(false)->setWidth(25);

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'Rekap_Payment_Piutang_' . Carbon::parse($data['cut_off_date'])->format('Y-m-d_H-i-s') . '.xlsx');
    }
}
