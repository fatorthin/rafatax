<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\PayrollDetail;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportPayrollController extends Controller
{
    public function exportDetailExcel(Payroll $payroll): StreamedResponse
    {
        $details = PayrollDetail::with('staff')
            ->where('payroll_id', $payroll->id)
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Payroll ' . $payroll->name);

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
            'T. Solo',
            'T. Luar Solo',
            'Bonus Lembur',
            'Bonus Visit Solo',
            'Bonus Visit Luar',
            'Bonus Lain',
            'Pot. BPJS Kes',
            'Pot. BPJS TK',
            'Pot. Lain',
            'Pot. Hutang',
            'Total Bonus',
            'Total Pot.',
            'Total Gaji'
        ];
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . '1', $header);
            $col++;
        }

        // Rows
        $row = 2;
        foreach ($details as $idx => $d) {
            $bonusLembur = $d->overtime_count * 10000;
            $bonusVisitSolo = $d->visit_solo_count * 10000;
            $bonusVisitLuar = $d->visit_luar_solo_count * 15000;
            $cutSakit = $d->sick_leave_count * 0.5 * $d->salary / 25;
            $cutHalfday = $d->halfday_count * 0.5 * $d->salary / 25;
            $cutIjin = $d->leave_count * $d->salary / 25;
            $totalBonus = $bonusLembur + $bonusVisitSolo + $bonusVisitLuar + $d->bonus_lain;
            $totalPot = $d->cut_bpjs_kesehatan + $d->cut_bpjs_ketenagakerjaan + $d->cut_lain + $d->cut_hutang + $cutSakit + $cutHalfday + $cutIjin;
            $totalGaji = $d->salary + $d->bonus_position + $d->bonus_competency + $totalBonus - $totalPot;

            $values = [
                $idx + 1,
                optional($d->staff)->name,
                $d->salary,
                $d->bonus_position,
                $d->bonus_competency,
                $d->sick_leave_count,
                $d->halfday_count,
                $d->leave_count,
                $d->overtime_count,
                $d->visit_solo_count,
                $d->visit_luar_solo_count,
                $bonusLembur,
                $bonusVisitSolo,
                $bonusVisitLuar,
                $d->bonus_lain,
                $d->cut_bpjs_kesehatan,
                $d->cut_bpjs_ketenagakerjaan,
                $d->cut_lain,
                $d->cut_hutang,
                $totalBonus,
                $totalPot,
                $totalGaji,
            ];

            $col = 1;
            foreach ($values as $val) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $row, $val);
                $col++;
            }

            $row++;
        }

        // Autosize
        $highestColumn = $sheet->getHighestColumn();
        foreach (range('A', $highestColumn) as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $fileName = 'payroll_' . str_replace([' ', '/'], '_', $payroll->name) . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function payslipPdf(PayrollDetail $detail)
    {
        $detail->load(['staff', 'payroll']);

        $bonusLembur = $detail->overtime_count * 10000;
        $bonusVisitSolo = $detail->visit_solo_count * 10000;
        $bonusVisitLuar = $detail->visit_luar_solo_count * 15000;
        $cutSakit = $detail->sick_leave_count * 0.5 * $detail->salary / 25;
        $cutHalfday = $detail->halfday_count * 0.5 * $detail->salary / 25;
        $cutIjin = $detail->leave_count * $detail->salary / 25;
        $totalBonus = $bonusLembur + $bonusVisitSolo + $bonusVisitLuar + $detail->bonus_lain;
        $totalPot = $detail->cut_bpjs_kesehatan + $detail->cut_bpjs_ketenagakerjaan + $detail->cut_lain + $detail->cut_hutang + $cutSakit + $cutHalfday + $cutIjin;
        $totalGaji = $detail->salary + $detail->bonus_position + $detail->bonus_competency + $totalBonus - $totalPot;


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

        return $pdf->download('slip_gaji_' . optional($detail->staff)->name . '_' . str_replace([' ', '/'], '_', optional($detail->payroll)->name) . '.pdf');
    }
}
