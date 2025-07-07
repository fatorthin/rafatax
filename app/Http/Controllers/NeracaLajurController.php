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
use App\Models\JournalBookReport;

class NeracaLajurController extends Controller
{
    public function export(Request $request)
    {
        $month = $request->get('month', 1);
        $year = $request->get('year', now()->year);
        
        return $this->generateExcel($month, $year);
    }

    public function saveCutOff(Request $request)
    {
        try {
            $month = $request->get('month', 1);
            $year = $request->get('year', now()->year);

            DB::beginTransaction();

            // Get data for the current month
            $data = $this->getDataForExport($month, $year);
            // dd($data);
            
            // Get the last day of the current month for transaction date
            $transactionDate = Carbon::create($year, $month, 1)->endOfMonth();
            
            // Delete existing data for this month if any
            JournalBookReport::where('journal_book_id', 3)
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month)
                ->delete();

            // Insert new data
            foreach ($data as $item) {
                // Only process items that should appear in Neraca (AO-101 to AO-305, including AO-101.1 to AO-101.5 and AO-102.1 to AO-102.5)
                if (preg_match('/^AO-(([1-2][0-9]{2}|30[0-5])(\.[1-5])?|(10[1-2])\.[1-5])$/', $item->code)) {
                    // Calculate Neraca Setelah AJE first
                    $totalDebit = $item->neraca_awal_debit + $item->kas_besar_debit + 
                                $item->kas_kecil_debit + $item->bank_debit + 
                                $item->jurnal_umum_debit + $item->aje_debit;
                    
                    $totalKredit = $item->neraca_awal_kredit + $item->kas_besar_kredit + 
                                 $item->kas_kecil_kredit + $item->bank_kredit + 
                                 $item->jurnal_umum_kredit + $item->aje_kredit;
                    
                    $selisih = $totalDebit - $totalKredit;
                    
                    // Only save if there's a balance
                    if ($selisih != 0) {
                        JournalBookReport::create([
                            'description' => $item->code . ' - ' . $item->name,
                            'journal_book_id' => 3,
                            'debit_amount' => max(0, $selisih),
                            'credit_amount' => max(0, -$selisih),
                            'coa_id' => $item->id,
                            'transaction_date' => $transactionDate,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Data Neraca berhasil disimpan');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
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
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $title = 'Neraca Lajur Bulanan (KKP) - ' . $monthNames[$month] . ' ' . $year;
        
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:U1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Headers
        $sheet->setCellValue('A3', 'Kode Akun');
        $sheet->setCellValue('B3', 'Neraca Awal');
        $sheet->setCellValue('D3', 'Kas Besar');
        $sheet->setCellValue('F3', 'Kas Kecil');
        $sheet->setCellValue('H3', 'Bank');
        $sheet->setCellValue('J3', 'Jurnal Umum');
        $sheet->setCellValue('L3', 'Neraca Sebelum AJE');
        $sheet->setCellValue('N3', 'AJE');
        $sheet->setCellValue('P3', 'Neraca Setelah AJE');
        $sheet->setCellValue('R3', 'Neraca');
        $sheet->setCellValue('T3', 'Laba Rugi');

        // Sub headers
        $subHeaders = ['Debit', 'Kredit'];
        $cols = ['B', 'D', 'F', 'H', 'J', 'L', 'N', 'P', 'R', 'T'];
        foreach ($cols as $col) {
            $sheet->setCellValue($col.'4', $subHeaders[0]);
            $sheet->setCellValue(chr(ord($col)+1).'4', $subHeaders[1]);
        }

        // Merge cells
        $sheet->mergeCells('A3:A4');
        foreach ($cols as $col) {
            $sheet->mergeCells($col.'3:'.chr(ord($col)+1).'3');
        }

        // Apply header styling
        $headerStyle = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C3C1C1']],
        ];
        $sheet->getStyle('A3:U4')->applyFromArray($headerStyle);

        // Get data
        $data = $this->getDataForExport($month, $year);
        
        if ($data->isEmpty()) {
            $sheet->setCellValue('A5', 'Tidak ada data untuk periode ini');
            $sheet->mergeCells('A5:U5');
            $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        } else {
            $row = 5;
            foreach ($data as $item) {
                // Calculate values
                $totalDebit = $item->neraca_awal_debit + $item->kas_besar_debit + $item->kas_kecil_debit + $item->bank_debit + $item->jurnal_umum_debit;
                $totalKredit = $item->neraca_awal_kredit + $item->kas_besar_kredit + $item->kas_kecil_kredit + $item->bank_kredit + $item->jurnal_umum_kredit;
                
                $selisihSebelumAJE = $totalDebit - $totalKredit;
                $neracaSebelumAJEDebit = max(0, $selisihSebelumAJE);
                $neracaSebelumAJEKredit = max(0, -$selisihSebelumAJE);

                $selisihSetelahAJE = $selisihSebelumAJE + ($item->aje_debit - $item->aje_kredit);
                $neracaSetelahAJEDebit = max(0, $selisihSetelahAJE);
                $neracaSetelahAJEKredit = max(0, -$selisihSetelahAJE);

                // Neraca (AO-101 to AO-305)
                $showInNeraca = preg_match('/^AO-(([1-2][0-9]{2}|30[0-5])(\.[1-5])?|(10[1-2])\.[1-5])$/', $item->code);
                $neracaDebit = $showInNeraca ? $neracaSetelahAJEDebit : 0;
                $neracaKredit = $showInNeraca ? $neracaSetelahAJEKredit : 0;

                // Laba Rugi (AO-401 to AO-702)
                $showInLabaRugi = preg_match('/^AO-(4[0-9]{2}(\.[1-6])?|501(\.[1-4])?|50[0-9]|5[1-9][0-9]|6[0-9]{2}|70[0-2])$/', $item->code);
                $labaRugiDebit = $showInLabaRugi ? $neracaSetelahAJEDebit : 0;
                $labaRugiKredit = $showInLabaRugi ? $neracaSetelahAJEKredit : 0;

                // Set data in cells
                $sheet->setCellValue('A'.$row, $item->code.' '.$item->name);
                $sheet->setCellValue('B'.$row, $item->neraca_awal_debit);
                $sheet->setCellValue('C'.$row, $item->neraca_awal_kredit);
                $sheet->setCellValue('D'.$row, $item->kas_besar_debit);
                $sheet->setCellValue('E'.$row, $item->kas_besar_kredit);
                $sheet->setCellValue('F'.$row, $item->kas_kecil_debit);
                $sheet->setCellValue('G'.$row, $item->kas_kecil_kredit);
                $sheet->setCellValue('H'.$row, $item->bank_debit);
                $sheet->setCellValue('I'.$row, $item->bank_kredit);
                $sheet->setCellValue('J'.$row, $item->jurnal_umum_debit);
                $sheet->setCellValue('K'.$row, $item->jurnal_umum_kredit);
                $sheet->setCellValue('L'.$row, $neracaSebelumAJEDebit);
                $sheet->setCellValue('M'.$row, $neracaSebelumAJEKredit);
                $sheet->setCellValue('N'.$row, $item->aje_debit);
                $sheet->setCellValue('O'.$row, $item->aje_kredit);
                $sheet->setCellValue('P'.$row, $neracaSetelahAJEDebit);
                $sheet->setCellValue('Q'.$row, $neracaSetelahAJEKredit);
                $sheet->setCellValue('R'.$row, $neracaDebit);
                $sheet->setCellValue('S'.$row, $neracaKredit);
                $sheet->setCellValue('T'.$row, $labaRugiDebit);
                $sheet->setCellValue('U'.$row, $labaRugiKredit);

                $row++;
            }

            // Add totals
            $totalRow = $row;
            $sheet->setCellValue('A'.$totalRow, 'Total');
            $sheet->getStyle('A'.$totalRow.':U'.$totalRow)->applyFromArray($headerStyle);
            
            // Calculate totals
            $columns = ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U'];
            foreach ($columns as $col) {
                $sheet->setCellValue($col.$totalRow, '=SUM('.$col.'5:'.$col.($totalRow-1).')');
            }

            // Apply borders and number format
            $sheet->getStyle('A5:U'.($totalRow-1))->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ]);
            $sheet->getStyle('B5:U'.$totalRow)->getNumberFormat()->setFormatCode('#,##0');
        }

        // Auto-size columns
        foreach (range('A', 'U') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Prepare download
        $filename = 'neraca-lajur-bulanan-' . strtolower(Carbon::create($year, $month, 1)->format('F-Y')) . '.xlsx';
        
        // Clear output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set headers
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        
        // Save and output
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    
    private function getDataForExport($month, $year)
    {
        // Start date of previous month
        $startOfPreviousMonth = Carbon::create($year, $month, 1)->subMonth()->startOfMonth();
        $endOfPreviousMonth = Carbon::create($year, $month, 1)->subMonth()->endOfMonth();
        
        // Current month date range
        $startOfCurrentMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfCurrentMonth = Carbon::create($year, $month, 1)->endOfMonth();

        $query = Coa::query()
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
                    WHERE transaction_date BETWEEN '{$startOfPreviousMonth}' AND '{$endOfPreviousMonth}'
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
                        debit_amount as kas_besar_kredit,
                        credit_amount as kas_besar_debit
                    FROM (
                        -- Regular case for all COA except AO-101
                        SELECT 
                            coa_id,
                            SUM(debit_amount) as debit_amount,
                            SUM(credit_amount) as credit_amount
                        FROM cash_reports
                        WHERE cash_reference_id = 6
                        AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                        AND deleted_at IS NULL
                        AND coa_id != 75
                        GROUP BY coa_id
                        
                        UNION ALL
                        
                        -- Special case for AO-101
                        SELECT 
                            75 as coa_id,
                            SUM(credit_amount) as debit_amount,
                            SUM(debit_amount) as credit_amount
                        FROM cash_reports
                        WHERE cash_reference_id = 6
                        AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                        AND deleted_at IS NULL
                    ) as combined_data
                ) as kas_besar_data"),
                'coa.id',
                '=',
                'kas_besar_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT 
                        coa_id,
                        debit_amount as kas_kecil_debit,
                        credit_amount as kas_kecil_kredit
                    FROM (
                        -- Regular case for all COA except AO-101.1
                        SELECT 
                            coa_id,
                            SUM(credit_amount) as debit_amount,
                            SUM(debit_amount) as credit_amount
                        FROM cash_reports
                        WHERE cash_reference_id = 7
                        AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                        AND deleted_at IS NULL
                        AND coa_id != 76
                        GROUP BY coa_id
                        
                        UNION ALL
                        
                        -- Special case for AO-101.1
                        SELECT 
                            76 as coa_id,
                            SUM(debit_amount) as debit_amount,
                            SUM(credit_amount) as credit_amount
                        FROM cash_reports
                        WHERE cash_reference_id = 7
                        AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                        AND deleted_at IS NULL
                    ) as combined_data
                ) as kas_kecil_data"),
                'coa.id',
                '=',
                'kas_kecil_data.coa_id'
            )
            ->leftJoin(
                DB::raw("(
                    SELECT 
                        c.id as coa_id,
                        CASE 
                            WHEN c.code = 'AO-1010' THEN (
                                SELECT COALESCE(SUM(debit_amount), 0)
                                FROM cash_reports cr
                                INNER JOIN cash_references cref ON cr.cash_reference_id = cref.id
                                WHERE cref.id IN (1, 2, 3, 4, 5)
                                AND cr.deleted_at IS NULL
                                AND cref.deleted_at IS NULL
                                AND cr.coa_id = 94
                                AND cr.transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                            )
                            WHEN c.code = 'AO-101.2' THEN (
                                SELECT COALESCE(SUM(debit_amount), 0)
                                FROM cash_reports cr
                                INNER JOIN cash_references cref ON cr.cash_reference_id = cref.id
                                WHERE cref.id IN (1, 2, 3, 4)
                                AND cr.deleted_at IS NULL
                                AND cref.deleted_at IS NULL
                                AND cr.coa_id IN (77, 82)
                                AND cr.transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                            )
                            WHEN c.code = 'AO-102.1' THEN (
                                SELECT COALESCE(SUM(credit_amount), 0)
                                FROM cash_reports
                                WHERE cash_reference_id = 1
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                            WHEN c.code = 'AO-102.2' THEN (
                                SELECT COALESCE(SUM(credit_amount), 0)
                                FROM cash_reports
                                WHERE cash_reference_id = 3
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                            WHEN c.code = 'AO-102.3' THEN (
                                SELECT COALESCE(SUM(credit_amount), 0)
                                FROM cash_reports
                                WHERE cash_reference_id = 2
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                            WHEN c.code = 'AO-102.4' THEN (
                                SELECT COALESCE(SUM(credit_amount), 0)
                                FROM cash_reports
                                WHERE cash_reference_id = 4
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                            WHEN c.code = 'AO-102.5' THEN (
                                SELECT COALESCE(SUM(credit_amount), 0)
                                FROM cash_reports
                                WHERE cash_reference_id = 5
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                            ELSE (
                                SELECT COALESCE(SUM(debit_amount), 0)
                                FROM cash_reports
                                WHERE coa_id = c.id
                                AND cash_reference_id IN (1,2,3,4,5)
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                        END as bank_kredit,
                        CASE 
                            WHEN c.code = 'AO-1010' THEN (
                                SELECT COALESCE(SUM(credit_amount), 0)
                                FROM cash_reports cr
                                INNER JOIN cash_references cref ON cr.cash_reference_id = cref.id
                                WHERE cref.id IN (1, 2, 3, 4, 5)
                                AND cr.deleted_at IS NULL
                                AND cref.deleted_at IS NULL
                                AND cr.coa_id = 94
                                AND cr.transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                            )
                            WHEN c.code = 'AO-101.2' THEN (
                                SELECT COALESCE(SUM(credit_amount), 0)
                                FROM cash_reports cr
                                INNER JOIN cash_references cref ON cr.cash_reference_id = cref.id
                                WHERE cref.id IN (1, 2, 3, 4)
                                AND cr.deleted_at IS NULL
                                AND cref.deleted_at IS NULL
                                AND cr.coa_id IN (77, 82)
                                AND cr.transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                            )
                            WHEN c.code = 'AO-102.1' THEN (
                                SELECT COALESCE(SUM(debit_amount), 0)
                                FROM cash_reports
                                WHERE cash_reference_id = 1
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                            WHEN c.code = 'AO-102.2' THEN (
                                SELECT COALESCE(SUM(debit_amount), 0)
                                FROM cash_reports
                                WHERE cash_reference_id = 3
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                            WHEN c.code = 'AO-102.3' THEN (
                                SELECT COALESCE(SUM(debit_amount), 0)
                                FROM cash_reports
                                WHERE cash_reference_id = 2
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                            WHEN c.code = 'AO-102.4' THEN (
                                SELECT COALESCE(SUM(debit_amount), 0)
                                FROM cash_reports
                                WHERE cash_reference_id = 4
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                            WHEN c.code = 'AO-102.5' THEN (
                                SELECT COALESCE(SUM(debit_amount), 0)
                                FROM cash_reports
                                WHERE cash_reference_id = 5
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                            ELSE (
                                SELECT COALESCE(SUM(credit_amount), 0)
                                FROM cash_reports
                                WHERE coa_id = c.id
                                AND cash_reference_id IN (1,2,3,4,5)
                                AND transaction_date BETWEEN '{$startOfCurrentMonth}' AND '{$endOfCurrentMonth}'
                                AND deleted_at IS NULL
                            )
                        END as bank_debit
                    FROM coa c
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
            ->orderBy('coa.id');

        return $query->get();
    }
} 