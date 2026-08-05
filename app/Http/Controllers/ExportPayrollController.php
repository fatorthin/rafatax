<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\PayrollDetail;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportPayrollController extends Controller
{
    public function exportDetailExcel(Payroll $payroll): StreamedResponse
    {
        $details = PayrollDetail::with('staff')
            ->where('payroll_id', $payroll->id)
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Payroll '.$payroll->name);

        $hasVisitSolo = $details->sum('visit_solo_count') > 0;
        $hasVisitLuar = $details->sum('visit_luar_solo_count') > 0;

        // Header columns
        $headers = [
            'No',
            'Nama',
            'Gaji Pokok',
            'TUNJAB',
            'TUNKOMP',
            'Sakit',
            'Tengah Hari',
            'Ijin',
            'Lembur',
        ];

        if ($hasVisitSolo) {
            $headers[] = 'T. Solo';
        }
        if ($hasVisitLuar) {
            $headers[] = 'T. Luar Solo';
        }

        $headers[] = 'T. Transport';
        $headers[] = 'Bonus Lembur';

        if ($hasVisitSolo) {
            $headers[] = 'Bonus Visit Solo';
        }
        if ($hasVisitLuar) {
            $headers[] = 'Bonus Visit Luar';
        }

        $headers[] = 'Bonus Lain';
        $headers[] = 'Pot. BPJS Kes';
        $headers[] = 'Pot. BPJS TK';
        $headers[] = 'Pot. Sakit';
        $headers[] = 'Pot. Tengah Hari';
        $headers[] = 'Pot. Ijin';
        $headers[] = 'Pot. Lain';
        $headers[] = 'Pot. Hutang';
        $headers[] = 'Total Bonus';
        $headers[] = 'Total Pot.';
        $headers[] = 'Total Gaji';

        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col).'1', $header);
            $col++;
        }

        // Rows
        $row = 2;
        foreach ($details as $idx => $d) {
            $bonusLembur = $d->overtime_count * $d->overtime_multiplier;
            $bonusVisitSolo = $d->visit_solo_count * 10000;
            $bonusVisitLuar = $d->visit_luar_solo_count * 15000;
            $cutSakit = $d->sick_leave_count * 0.5 * $d->salary / 25;
            $cutHalfday = $d->halfday_count * 0.5 * $d->salary / 25;
            $cutIjin = $d->leave_count * $d->salary / 25;
            $totalBonus = $bonusLembur + $bonusVisitSolo + $bonusVisitLuar + $d->bonus_lain;
            $totalPot = $d->cut_bpjs_kesehatan + $d->cut_bpjs_ketenagakerjaan + $d->cut_lain + $d->cut_hutang + $cutSakit + $cutHalfday + $cutIjin;
            $totalGaji = $d->salary + $d->bonus_position + $d->bonus_competency + $d->bonus_transport + $totalBonus - $totalPot;

            $values = [
                $idx + 1,
                $d->staff_id ? optional($d->staff)->name : $d->nama_non_staff,
                $d->salary,
                $d->bonus_position,
                $d->bonus_competency,
                $d->sick_leave_count,
                $d->halfday_count,
                $d->leave_count,
                $d->overtime_count,
            ];

            if ($hasVisitSolo) {
                $values[] = $d->visit_solo_count;
            }
            if ($hasVisitLuar) {
                $values[] = $d->visit_luar_solo_count;
            }

            $values[] = $d->bonus_transport;
            $values[] = $bonusLembur;

            if ($hasVisitSolo) {
                $values[] = $bonusVisitSolo;
            }
            if ($hasVisitLuar) {
                $values[] = $bonusVisitLuar;
            }

            $values[] = $d->bonus_lain;
            $values[] = $d->cut_bpjs_kesehatan;
            $values[] = $d->cut_bpjs_ketenagakerjaan;
            $values[] = $cutSakit;
            $values[] = $cutHalfday;
            $values[] = $cutIjin;
            $values[] = $d->cut_lain;
            $values[] = $d->cut_hutang;
            $values[] = $totalBonus;
            $values[] = $totalPot;
            $values[] = $totalGaji;

            $col = 1;
            foreach ($values as $val) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col).$row, $val);
                $col++;
            }

            $row++;
        }

        // Autosize
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $fileName = 'payroll_'.str_replace([' ', '/'], '_', $payroll->name).'.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function payslipPdf(PayrollDetail $detail)
    {
        $detail->load(['staff', 'payroll']);

        $bonusLembur = $detail->overtime_count * $detail->overtime_multiplier;
        $bonusVisitSolo = $detail->visit_solo_count * 10000;
        $bonusVisitLuar = $detail->visit_luar_solo_count * 15000;
        $cutSakit = $detail->sick_leave_count * 0.5 * $detail->salary / 25;
        $cutHalfday = $detail->halfday_count * 0.5 * $detail->salary / 25;
        $cutIjin = $detail->leave_count * $detail->salary / 25;
        $totalBonus = $bonusLembur + $bonusVisitSolo + $bonusVisitLuar + $detail->bonus_lain;
        $totalPot = $detail->cut_bpjs_kesehatan + $detail->cut_bpjs_ketenagakerjaan + $detail->cut_lain + $detail->cut_hutang + $cutSakit + $cutHalfday + $cutIjin;
        $totalGaji = $detail->salary + $detail->bonus_position + $detail->bonus_competency + $detail->bonus_transport + $totalBonus - $totalPot;

        $pdf = PDF::loadView('pdf.payslip', [
            'detail' => $detail,
            'bonusLembur' => $bonusLembur,
            'bonusVisitSolo' => $bonusVisitSolo,
            'bonusVisitLuar' => $bonusVisitLuar,
            'cutSakit' => $cutSakit,
            'cutHalfday' => $cutHalfday,
            'cutIjin' => $cutIjin,
            'totalBonus' => $totalBonus,
            'totalPot' => $totalPot,
            'totalGaji' => $totalGaji,
        ])->setPaper('a5', 'portrait');

        return $pdf->download('slip_gaji_'.str_replace(' ', '_', $detail->staff_id ? optional($detail->staff)->name : $detail->nama_non_staff).'_'.str_replace([' ', '/'], '_', optional($detail->payroll)->name).'.pdf');
    }
}
