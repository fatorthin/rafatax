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
            $query->where('category_mou_id', $data['category_mou_id']);
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

            // MoU columns that get merged when there are multiple invoices
            $mouCols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'T'];

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
                    $totalInvAll += $amt;
                    if ($inv->invoice_status === 'paid') $totalInvPaid += $amt;
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

                // MoU data
                $sheet->setCellValue("A{$row}", $no);
                $sheet->setCellValue("B{$row}", $mou->client->company_name ?? '-');
                $sheet->setCellValue("C{$row}", $mou->client->jenis_wp ? ucfirst($mou->client->jenis_wp) : ($mou->client->type ?? '-'));
                $sheet->setCellValue("D{$row}", strtoupper($mou->type ?? '-'));
                $sheet->setCellValue("E{$row}", $mou->categoryMou->name ?? '-');
                $sheet->setCellValue("F{$row}", $mou->mou_number ?? '-');
                $sheet->setCellValue("G{$row}", $mou->description ?? '-');
                $sheet->setCellValue("H{$row}", ucfirst($mou->status ?? '-'));
                $sheet->setCellValue("I{$row}", $nomBulanan);
                $sheet->setCellValue("J{$row}", $nomTahunan);
                $sheet->setCellValue("K{$row}", $nomCase);
                $sheet->setCellValue("L{$row}", $totalMou);

                foreach (['I', 'J', 'K', 'L'] as $c) {
                    $sheet->getStyle("{$c}{$row}")->getNumberFormat()->setFormatCode($cf);
                }

                if ($invCount === 0) {
                    $sheet->setCellValue("M{$row}", '-');
                    $sheet->setCellValue("N{$row}", '-');
                    $sheet->setCellValue("O{$row}", '-');
                    $sheet->setCellValue("P{$row}", '-');
                    $sheet->setCellValue("Q{$row}", 'Belum ada invoice');
                    $sheet->setCellValue("R{$row}", '-');
                    $sheet->setCellValue("S{$row}", 0);
                    $sheet->getStyle("S{$row}")->getNumberFormat()->setFormatCode($cf);
                    $sheet->setCellValue("T{$row}", $piutang);
                    $sheet->getStyle("T{$row}")->getNumberFormat()->setFormatCode($cf);

                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EBF5FB');
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setBold(true);
                    $row++;
                } else {
                    // Write invoice rows
                    foreach ($mouInvoices->values() as $inv) {
                        $invAmt = $inv->costListInvoices->sum('amount');
                        $sheet->setCellValue("M{$row}", $inv->invoice_number ?? '-');
                        $sheet->setCellValue("N{$row}", $inv->description ?? '-');
                        $sheet->setCellValue("O{$row}", $inv->invoice_date ? Carbon::parse($inv->invoice_date)->format('d/m/Y') : '-');
                        $sheet->setCellValue("P{$row}", $inv->due_date ? Carbon::parse($inv->due_date)->format('d/m/Y') : '-');
                        $sheet->setCellValue("Q{$row}", ucfirst($inv->invoice_status ?? '-'));
                        $sheet->setCellValue("R{$row}", $inv->tgl_transfer ? Carbon::parse($inv->tgl_transfer)->format('d/m/Y') : '-');
                        $sheet->setCellValue("S{$row}", $invAmt);
                        $sheet->getStyle("S{$row}")->getNumberFormat()->setFormatCode($cf);

                        $sc = match ($inv->invoice_status) {
                            'paid' => '27AE60',
                            'unpaid' => 'E67E22',
                            'overdue' => 'E74C3C',
                            default => '7F8C8D',
                        };
                        $sheet->getStyle("Q{$row}")->getFont()->getColor()->setRGB($sc);
                        $sheet->getStyle("Q{$row}")->getFont()->setBold(true);
                        $row++;
                    }

                    $endRow = $row - 1;

                    // Piutang
                    $sheet->setCellValue("T{$startRow}", $piutang);
                    $sheet->getStyle("T{$startRow}")->getNumberFormat()->setFormatCode($cf);

                    // Merge MoU columns
                    if ($invCount > 1) {
                        foreach ($mouCols as $c) {
                            $sheet->mergeCells("{$c}{$startRow}:{$c}{$endRow}");
                        }
                    }

                    // Style MoU columns
                    $sheet->getStyle("A{$startRow}:L{$endRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EBF5FB');
                    $sheet->getStyle("A{$startRow}:L{$endRow}")->getFont()->setBold(true);

                    // Piutang style
                    $sheet->getStyle("T{$startRow}:T{$endRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FDEDEC');
                    $sheet->getStyle("T{$startRow}:T{$endRow}")->getFont()->setBold(true);
                }

                $sheet->getStyle("A{$startRow}:{$lastCol}" . ($row - 1))->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $no++;
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
