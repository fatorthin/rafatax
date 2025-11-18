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

        // Get COA list for form
        $coaList = Coa::all()->mapWithKeys(function ($coa) {
            return [$coa->id => $coa->code . ' - ' . $coa->name];
        });

        $monthName = Carbon::create($year, $month, 1)->format('F');

        // Calculate totals
        $totalDebit = $transactions->sum('debit_amount');
        $totalCredit = $transactions->sum('credit_amount');
        $endingBalance = $prevBalance + ($totalDebit - $totalCredit);

        return view('cash-reference.month-detail', compact(
            'cashReference',
            'transactions',
            'year',
            'month',
            'monthName',
            'prevBalance',
            'totalDebit',
            'totalCredit',
            'endingBalance',
            'coaList'
        ));
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
