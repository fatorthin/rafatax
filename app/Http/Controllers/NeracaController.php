<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coa;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

class NeracaController extends Controller
{
    public function export(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        return $this->generateExcel($month, $year);
    }

    private function generateExcel($month, $year)
    {
        // Disable memory limit and increase execution time
        ini_set('memory_limit', '1024M');
        set_time_limit(600);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];
        $title = 'LAPORAN NERACA - ' . strtoupper($monthNames[$month]) . ' ' . $year;

        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Get Neraca Data
        $neracaData = $this->getNeracaData($month, $year);

        // Headers - Aktiva (Left) and Pasiva (Right)
        $sheet->setCellValue('A3', 'AKTIVA');
        $sheet->mergeCells('A3:C3');
        $sheet->getStyle('A3')->getFont()->setBold(true);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A3')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E0E0E0');

        $sheet->setCellValue('D3', 'PASIVA');
        $sheet->mergeCells('D3:F3');
        $sheet->getStyle('D3')->getFont()->setBold(true);
        $sheet->getStyle('D3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D3')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E0E0E0');

        // Process data side by side
        $aktivaRow = 4;
        $pasivaRow = 4;

        // Write Aktiva data
        foreach ($neracaData['aktiva'] as $item) {
            if ($item['is_group_header']) {
                $sheet->setCellValue('A' . $aktivaRow, $item['name']);
                $sheet->mergeCells('A' . $aktivaRow . ':C' . $aktivaRow);
                $sheet->getStyle('A' . $aktivaRow)->getFont()->setBold(true);
                $sheet->getStyle('A' . $aktivaRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F5F5F5');
            } elseif ($item['is_group_total']) {
                $sheet->setCellValue('B' . $aktivaRow, $item['name']);
                $sheet->setCellValue('C' . $aktivaRow, $item['amount']);
                $sheet->getStyle('B' . $aktivaRow)->getFont()->setBold(true);
                $sheet->getStyle('C' . $aktivaRow)->getFont()->setBold(true);
                $sheet->getStyle('C' . $aktivaRow)->getNumberFormat()->setFormatCode('#,##0');
            } else {
                $sheet->setCellValue('A' . $aktivaRow, $item['code']);
                $sheet->setCellValue('B' . $aktivaRow, $item['name']);
                $sheet->setCellValue('C' . $aktivaRow, $item['amount']);
                $sheet->getStyle('C' . $aktivaRow)->getNumberFormat()->setFormatCode('#,##0');
                if ($item['is_negative']) {
                    $sheet->getStyle('C' . $aktivaRow)->getFont()->getColor()->setRGB('FF0000');
                }
            }
            $aktivaRow++;
        }

        // Total Aktiva
        $sheet->setCellValue('B' . $aktivaRow, 'TOTAL AKTIVA');
        $sheet->setCellValue('C' . $aktivaRow, $neracaData['totalAktiva']);
        $sheet->getStyle('B' . $aktivaRow . ':C' . $aktivaRow)->getFont()->setBold(true);
        $sheet->getStyle('B' . $aktivaRow . ':C' . $aktivaRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D0D0D0');
        $sheet->getStyle('C' . $aktivaRow)->getNumberFormat()->setFormatCode('#,##0');

        // Write Pasiva data
        foreach ($neracaData['pasiva'] as $item) {
            if ($item['is_group_header']) {
                $sheet->setCellValue('D' . $pasivaRow, $item['name']);
                $sheet->mergeCells('D' . $pasivaRow . ':F' . $pasivaRow);
                $sheet->getStyle('D' . $pasivaRow)->getFont()->setBold(true);
                $sheet->getStyle('D' . $pasivaRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F5F5F5');
            } elseif ($item['is_group_total']) {
                $sheet->setCellValue('E' . $pasivaRow, $item['name']);
                $sheet->setCellValue('F' . $pasivaRow, $item['amount']);
                $sheet->getStyle('E' . $pasivaRow)->getFont()->setBold(true);
                $sheet->getStyle('F' . $pasivaRow)->getFont()->setBold(true);
                $sheet->getStyle('F' . $pasivaRow)->getNumberFormat()->setFormatCode('#,##0');
            } elseif ($item['is_sisa_dana']) {
                $sheet->setCellValue('E' . $pasivaRow, $item['name']);
                $sheet->setCellValue('F' . $pasivaRow, $item['amount']);
                $sheet->getStyle('E' . $pasivaRow)->getFont()->setBold(true);
                $sheet->getStyle('F' . $pasivaRow)->getFont()->setBold(true);
                $sheet->getStyle('F' . $pasivaRow)->getNumberFormat()->setFormatCode('#,##0');
                if ($item['is_negative']) {
                    $sheet->getStyle('F' . $pasivaRow)->getFont()->getColor()->setRGB('FF0000');
                }
            } else {
                $sheet->setCellValue('D' . $pasivaRow, $item['code']);
                $sheet->setCellValue('E' . $pasivaRow, $item['name']);
                $sheet->setCellValue('F' . $pasivaRow, $item['amount']);
                $sheet->getStyle('F' . $pasivaRow)->getNumberFormat()->setFormatCode('#,##0');
                if ($item['is_negative']) {
                    $sheet->getStyle('F' . $pasivaRow)->getFont()->getColor()->setRGB('FF0000');
                }
            }
            $pasivaRow++;
        }

        // Total Pasiva
        $sheet->setCellValue('E' . $pasivaRow, 'TOTAL PASIVA');
        $sheet->setCellValue('F' . $pasivaRow, $neracaData['totalPasiva']);
        $sheet->getStyle('E' . $pasivaRow . ':F' . $pasivaRow)->getFont()->setBold(true);
        $sheet->getStyle('E' . $pasivaRow . ':F' . $pasivaRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D0D0D0');
        $sheet->getStyle('F' . $pasivaRow)->getNumberFormat()->setFormatCode('#,##0');

        // Apply borders to both sections
        $sheet->getStyle('A3:C' . $aktivaRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        $sheet->getStyle('D3:F' . $pasivaRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Prepare download
        $filename = 'neraca-' . strtolower($monthNames[$month]) . '-' . $year . '.xlsx';

        // Clear output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Set headers
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // Save and output
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function getNeracaData($month, $year)
    {
        // Start date of current month
        $startOfCurrentMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfCurrentMonth = Carbon::create($year, $month, 1)->endOfMonth();

        // Get Laba Rugi data first
        $labaRugiData = $this->getLabaRugiCalculation($startOfCurrentMonth, $endOfCurrentMonth);
        $sisaDanaTahunBerjalan = $labaRugiData['labaRugiBersih'];

        $data = Coa::query()
            ->select([
                'coa.id',
                'coa.code',
                'coa.name',
                'coa.type',
                'coa.group_coa_id',
                'group_coas.name as group_name',
                DB::raw('COALESCE(neraca_data.debit, 0) as debit'),
                DB::raw('COALESCE(neraca_data.credit, 0) as credit')
            ])
            ->leftJoin('group_coas', 'coa.group_coa_id', '=', 'group_coas.id')
            ->leftJoin(
                DB::raw("(
                    SELECT 
                        coa_id,
                        SUM(debit_amount) as debit,
                        SUM(credit_amount) as credit
                    FROM journal_book_reports 
                    WHERE transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                    AND deleted_at IS NULL
                    AND journal_book_id = 3
                    GROUP BY coa_id
                ) as neraca_data"),
                'coa.id',
                '=',
                'neraca_data.coa_id'
            )
            ->where('coa.deleted_at', null)
            ->where('coa.type', 'kkp')
            ->whereNotIn('coa.id', [78, 118]) // Exclude 'Tidak Terklasifikasi'
            ->whereRaw("coa.code REGEXP '^AO-(([1-2][0-9]{2}|30[0-5])(\\.[1-5])?|(10[1-2])\\.[1-5]|1010|1011)$'")
            ->orderBy('group_coas.id')
            ->orderBy('coa.id')
            ->get();

        $neracaData = [
            'aktiva' => [],
            'pasiva' => [],
            'totalAktiva' => 0,
            'totalPasiva' => 0
        ];

        $currentGroup = null;
        $currentGroupName = '';
        $currentGroupTotal = 0;
        $currentGroupSide = null;

        $defaultItemStructure = [
            'code' => '',
            'name' => '',
            'amount' => 0,
            'is_negative' => false,
            'is_group_header' => false,
            'is_group_total' => false,
            'is_sisa_dana' => false
        ];

        foreach ($data as $row) {
            $amount = preg_match('/^AO-1/', $row->code) ?
                $row->debit - $row->credit :
                $row->credit - $row->debit;

            $isAktiva = preg_match('/^AO-1/', $row->code);
            $target = $isAktiva ? 'aktiva' : 'pasiva';

            if ($currentGroup !== $row->group_coa_id) {
                if ($currentGroup !== null) {
                    $previousTarget = $currentGroupSide ?? $target;
                    $neracaData[$previousTarget][] = array_merge($defaultItemStructure, [
                        'name' => 'Total ' . $currentGroupName,
                        'amount' => abs($currentGroupTotal),
                        'is_negative' => $currentGroupTotal < 0,
                        'is_group_total' => true
                    ]);
                }

                $currentGroup = $row->group_coa_id;
                $currentGroupName = $row->group_name ?? 'Lainnya';
                $currentGroupTotal = 0;
                $currentGroupSide = $target;

                $neracaData[$target][] = array_merge($defaultItemStructure, [
                    'name' => $currentGroupName,
                    'is_group_header' => true
                ]);
            }

            $neracaData[$target][] = array_merge($defaultItemStructure, [
                'code' => $row->code,
                'name' => $row->name,
                'amount' => abs($amount),
                'is_negative' => $amount < 0
            ]);

            $currentGroupTotal += $amount;
            if ($isAktiva) {
                $neracaData['totalAktiva'] += $amount;
            } else {
                $neracaData['totalPasiva'] += $amount;
            }
        }

        if ($currentGroup !== null && $currentGroupSide !== null) {
            $neracaData[$currentGroupSide][] = array_merge($defaultItemStructure, [
                'name' => 'Total ' . $currentGroupName,
                'amount' => abs($currentGroupTotal),
                'is_negative' => $currentGroupTotal < 0,
                'is_group_total' => true
            ]);
        }

        $neracaData['pasiva'][] = array_merge($defaultItemStructure, [
            'name' => 'Sisa (Lebih) Dana Tahun Berjalan',
            'amount' => abs($sisaDanaTahunBerjalan),
            'is_negative' => $sisaDanaTahunBerjalan < 0,
            'is_sisa_dana' => true
        ]);
        $neracaData['totalPasiva'] += $sisaDanaTahunBerjalan;

        return $neracaData;
    }

    private function getLabaRugiCalculation($startOfCurrentMonth, $endOfCurrentMonth)
    {
        $data = Coa::query()
            ->select([
                'coa.id',
                'coa.code',
                'coa.name',
                'coa.type',
                DB::raw('COALESCE(journal_data.neraca_awal_debit, 0) as neraca_awal_debit'),
                DB::raw('COALESCE(journal_data.neraca_awal_kredit, 0) as neraca_awal_kredit'),
                DB::raw('COALESCE(kas_besar_data.kas_besar_debit, 0) as kas_besar_debit'),
                DB::raw('COALESCE(kas_besar_data.kas_besar_kredit, 0) as kas_besar_kredit'),
                DB::raw('COALESCE(kas_kecil_data.kas_kecil_debit, 0) as kas_kecil_debit'),
                DB::raw('COALESCE(kas_kecil_data.kas_kecil_kredit, 0) as kas_kecil_kredit'),
                DB::raw('COALESCE(bank_data.bank_debit, 0) as bank_debit'),
                DB::raw('COALESCE(bank_data.bank_kredit, 0) as bank_kredit'),
                DB::raw('COALESCE(jurnal_umum_data.jurnal_umum_debit, 0) as jurnal_umum_debit'),
                DB::raw('COALESCE(jurnal_umum_data.jurnal_umum_kredit, 0) as jurnal_umum_kredit'),
                DB::raw('COALESCE(aje_data.aje_debit, 0) as aje_debit'),
                DB::raw('COALESCE(aje_data.aje_kredit, 0) as aje_kredit')
            ])
            ->leftJoin(
                DB::raw("(
                    SELECT 
                        coa_id,
                        SUM(debit_amount) as neraca_awal_debit,
                        SUM(credit_amount) as neraca_awal_kredit
                    FROM journal_book_reports 
                    WHERE transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                    AND deleted_at IS NULL
                    AND journal_book_id = 3
                    GROUP BY coa_id
                ) as journal_data"),
                'coa.id',
                '=',
                'journal_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT 
                        coa_id,
                        SUM(credit_amount) as kas_besar_debit,
                        SUM(debit_amount) as kas_besar_kredit
                    FROM cash_reports
                    WHERE cash_reference_id = 6
                    AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                    AND deleted_at IS NULL
                    GROUP BY coa_id
                ) as kas_besar_data"),
                'coa.id',
                '=',
                'kas_besar_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT 
                        coa_id,
                        SUM(credit_amount) as kas_kecil_debit,
                        SUM(debit_amount) as kas_kecil_kredit
                    FROM cash_reports
                    WHERE cash_reference_id = 7
                    AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                    AND deleted_at IS NULL
                    GROUP BY coa_id
                ) as kas_kecil_data"),
                'coa.id',
                '=',
                'kas_kecil_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT 
                        coa_id,
                        SUM(credit_amount) as bank_debit,
                        SUM(debit_amount) as bank_kredit
                    FROM cash_reports
                    WHERE cash_reference_id IN (1,2,3,4,5)
                    AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                    AND deleted_at IS NULL
                    GROUP BY coa_id
                ) as bank_data"),
                'coa.id',
                '=',
                'bank_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT 
                        coa_id,
                        SUM(debit_amount) as jurnal_umum_debit,
                        SUM(credit_amount) as jurnal_umum_kredit
                    FROM journal_book_reports 
                    WHERE journal_book_id = 1
                    AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                    AND deleted_at IS NULL
                    GROUP BY coa_id
                ) as jurnal_umum_data"),
                'coa.id',
                '=',
                'jurnal_umum_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT 
                        coa_id,
                        SUM(debit_amount) as aje_debit,
                        SUM(credit_amount) as aje_kredit
                    FROM journal_book_reports 
                    WHERE journal_book_id = 2
                    AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                    AND deleted_at IS NULL
                    GROUP BY coa_id
                ) as aje_data"),
                'coa.id',
                '=',
                'aje_data.coa_id'
            )
            ->where('coa.deleted_at', null)
            ->where('coa.type', 'kkp')
            ->whereRaw("coa.code REGEXP '^AO-(4[0-9]{2}(\\.[1-6])?|501(\\.[1-4])?|50[0-9]|5[1-9][0-9]|6[0-9]{2}|70[0-2])$'")
            ->orderBy('coa.code')
            ->get();

        $totalPendapatan = 0;
        $totalBeban = 0;

        foreach ($data as $row) {
            $totalDebit = $row->neraca_awal_debit + $row->kas_besar_debit +
                $row->kas_kecil_debit + $row->bank_debit +
                $row->jurnal_umum_debit + $row->aje_debit;

            $totalKredit = $row->neraca_awal_kredit + $row->kas_besar_kredit +
                $row->kas_kecil_kredit + $row->bank_kredit +
                $row->jurnal_umum_kredit + $row->aje_kredit;

            if (preg_match('/^AO-4/', $row->code)) {
                $amount = $totalKredit - $totalDebit;
                $totalPendapatan += $amount;
            } else {
                $amount = $totalDebit - $totalKredit;
                $totalBeban += $amount;
            }
        }

        return [
            'totalPendapatan' => $totalPendapatan,
            'totalBeban' => $totalBeban,
            'labaRugiBersih' => $totalPendapatan - $totalBeban
        ];
    }
}
