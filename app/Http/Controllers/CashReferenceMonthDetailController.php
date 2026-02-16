<?php

namespace App\Http\Controllers;

use App\Models\Coa;
use App\Models\CashReport;
use App\Models\CashReference;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashReferenceMonthDetailController extends Controller
{
    public function show($id, Request $request)
    {
        $year = (int) $request->query('year');
        $month = (int) $request->query('month');

        if (!$year || !$month || $month < 1 || $month > 12) {
            return redirect()->back()->with('error', 'Invalid year or month parameter');
        }

        $data = $this->getTransactionData($id, $year, $month);

        // Get COA list for form
        $coaList = Coa::all()->mapWithKeys(function ($coa) {
            return [$coa->id => $coa->code . ' - ' . $coa->name];
        });

        return view('cash-reference.month-detail', array_merge($data, ['coaList' => $coaList]));
    }

    public function export($id, Request $request)
    {
        $year = (int) $request->query('year');
        $month = (int) $request->query('month');

        if (!$year || !$month || $month < 1 || $month > 12) {
            return redirect()->back()->with('error', 'Invalid year or month parameter');
        }

        $data = $this->getTransactionData($id, $year, $month);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Title
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', $data['cashReference']->name . ' - ' . $data['monthName'] . ' ' . $data['year']);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Summary
        $sheet->setCellValue('A3', 'Previous Balance');
        $sheet->setCellValue('B3', $data['prevBalance']);
        $sheet->getStyle('B3')->getNumberFormat()->setFormatCode('#,##0.00');

        $sheet->setCellValue('A4', 'Total Debit');
        $sheet->setCellValue('B4', $data['totalDebit']);
        $sheet->getStyle('B4')->getNumberFormat()->setFormatCode('#,##0.00');

        $sheet->setCellValue('A5', 'Total Credit');
        $sheet->setCellValue('B5', $data['totalCredit']);
        $sheet->getStyle('B5')->getNumberFormat()->setFormatCode('#,##0.00');

        $sheet->setCellValue('A6', 'Ending Balance');
        $sheet->setCellValue('B6', $data['endingBalance']);
        $sheet->getStyle('B6')->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('B6')->getFont()->setBold(true);

        // Headers
        $row = 8;
        $headers = ['Date', 'CoA', 'Description', 'Debit', 'Credit', 'Balance'];
        foreach ($headers as $col => $header) {
            $columnUtils = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
            $sheet->setCellValue($columnUtils . $row, $header);
            $sheet->getStyle($columnUtils . $row)->getFont()->setBold(true);
            $sheet->getStyle($columnUtils . $row)->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        }

        // Data
        $row++;
        foreach ($data['transactions'] as $transaction) {
            $sheet->setCellValue('A' . $row, Carbon::parse($transaction->transaction_date)->format('d-M-Y'));
            $sheet->setCellValue('B' . $row, $transaction->coa->code ?? '-');
            $sheet->setCellValue('C' . $row, $transaction->description);

            $sheet->setCellValue('D' . $row, $transaction->debit_amount);
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

            $sheet->setCellValue('E' . $row, $transaction->credit_amount);
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

            $sheet->setCellValue('F' . $row, $transaction->running_balance);
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

            $row++;
        }

        // Auto size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        // Sanitize name components
        $sanitizedName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $data['cashReference']->name);
        $filename = 'Cash_Ref_' . $sanitizedName . '_' . $data['monthName'] . '_' . $data['year'] . '.xlsx';

        // Clear output buffer to avoid corrupt file
        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . urlencode($filename) . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    private function getTransactionData($id, $year, $month)
    {
        $cashReference = CashReference::findOrFail($id);

        // Get previous month balance
        $prevBalance = $this->getPreviousMonthBalance($id, $year, $month);

        // Get transactions for the month
        $transactions = CashReport::where('cash_reference_id', $id)
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        // Calculate running balance for each transaction
        $balance = $prevBalance;
        foreach ($transactions as $transaction) {
            $balance += $transaction->debit_amount - $transaction->credit_amount;
            $transaction->running_balance = $balance;
        }

        $monthName = Carbon::create($year, $month, 1)->format('F');

        // Calculate totals
        $totalDebit = $transactions->sum('debit_amount');
        $totalCredit = $transactions->sum('credit_amount');
        $endingBalance = $prevBalance + ($totalDebit - $totalCredit);

        return compact(
            'cashReference',
            'transactions',
            'year',
            'month',
            'monthName',
            'prevBalance',
            'totalDebit',
            'totalCredit',
            'endingBalance'
        );
    }

    private function getPreviousMonthBalance($cashReferenceId, $year, $month): float
    {
        $prevMonth = $month - 1;
        $prevYear = $year;

        if ($prevMonth === 0) {
            $prevMonth = 12;
            $prevYear = $year - 1;
        }

        $lastDayPrevMonth = Carbon::create($prevYear, $prevMonth, 1)->endOfMonth()->format('Y-m-d');

        return CashReport::where('cash_reference_id', $cashReferenceId)
            ->where('transaction_date', '<=', $lastDayPrevMonth)
            ->sum(DB::raw('debit_amount - credit_amount'));
    }

    public function store($id, Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'coa_id' => 'required|exists:coa,id',
            'transaction_date' => 'required|date',
            'debit_amount' => 'required|numeric|min:0',
            'credit_amount' => 'required|numeric|min:0',
        ]);

        CashReport::create([
            'cash_reference_id' => $id,
            'description' => $validated['description'],
            'coa_id' => $validated['coa_id'],
            'transaction_date' => $validated['transaction_date'],
            'debit_amount' => $validated['debit_amount'],
            'credit_amount' => $validated['credit_amount'],
            'invoice_id' => 0,
            'mou_id' => 0,
            'cost_list_invoice_id' => 0,
        ]);

        $year = Carbon::parse($validated['transaction_date'])->year;
        $month = Carbon::parse($validated['transaction_date'])->month;

        return redirect()
            ->route('cash-reference.month-detail', ['id' => $id, 'year' => $year, 'month' => $month])
            ->with('success', 'Transaction added successfully');
    }

    public function edit($id, $transactionId)
    {
        $cashReference = CashReference::findOrFail($id);
        $transaction = CashReport::findOrFail($transactionId);

        $coaList = Coa::all()->mapWithKeys(function ($coa) {
            return [$coa->id => $coa->code . ' - ' . $coa->name];
        });

        return view('cash-reference.edit-transaction', compact('cashReference', 'transaction', 'coaList'));
    }

    public function update($transactionId, Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'coa_id' => 'required|exists:coa,id',
            'transaction_date' => 'required|date',
            'debit_amount' => 'required|numeric|min:0',
            'credit_amount' => 'required|numeric|min:0',
        ]);

        $transaction = CashReport::findOrFail($transactionId);
        $transaction->update($validated);

        $year = Carbon::parse($validated['transaction_date'])->year;
        $month = Carbon::parse($validated['transaction_date'])->month;

        return redirect()
            ->route('cash-reference.month-detail', [
                'id' => $transaction->cash_reference_id,
                'year' => $year,
                'month' => $month,
            ])
            ->with('success', 'Transaction updated successfully');
    }

    public function destroy($id, $transactionId, Request $request)
    {
        $transaction = CashReport::findOrFail($transactionId);
        $year = Carbon::parse($transaction->transaction_date)->year;
        $month = Carbon::parse($transaction->transaction_date)->month;

        $transaction->delete();

        return redirect()
            ->route('cash-reference.month-detail', ['id' => $id, 'year' => $year, 'month' => $month])
            ->with('success', 'Transaction deleted successfully');
    }
}
