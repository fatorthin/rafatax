<?php

namespace App\Http\Controllers;

use App\Models\CashReference;
use App\Models\CashReport;
use App\Models\Coa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CashReferenceMonthController extends Controller
{
    public function show(Request $request, $id)
    {
        // Validate query parameters
        $year = $request->query('year');
        $month = $request->query('month');

        // Redirect back if invalid parameters
        if (!$year || !$month || $month < 1 || $month > 12) {
            return redirect()->back()->with('error', 'Invalid year or month parameter');
        }

        $cashReference = CashReference::findOrFail($id);

        // Calculate previous month's balance
        $prevMonth = $month - 1;
        $prevYear = $year;

        if ($prevMonth === 0) {
            $prevMonth = 12;
            $prevYear = $year - 1;
        }

        $lastDayPrevMonth = Carbon::create($prevYear, $prevMonth)->endOfMonth()->format('Y-m-d');

        $prevBalance = CashReport::where('cash_reference_id', $id)
            ->where('transaction_date', '<=', $lastDayPrevMonth)
            ->sum(DB::raw('debit_amount - credit_amount'));

        // Get transactions for this month
        $transactions = CashReport::with('coa')
            ->where('cash_reference_id', $id)
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        // Calculate running balance for each transaction
        $runningBalance = $prevBalance;
        $transactionsWithBalance = $transactions->map(function ($transaction) use (&$runningBalance) {
            $runningBalance += $transaction->debit_amount - $transaction->credit_amount;
            $transaction->running_balance = $runningBalance;
            return $transaction;
        });

        // Calculate totals
        $totalDebit = $transactions->sum('debit_amount');
        $totalCredit = $transactions->sum('credit_amount');
        $endingBalance = $prevBalance + ($totalDebit - $totalCredit);

        $monthName = Carbon::create($year, $month)->format('F');

        // Get all CoA for dropdown
        $coaList = Coa::orderBy('code')->get();

        return view('cash-reference-month-detail', compact(
            'cashReference',
            'transactions',
            'transactionsWithBalance',
            'prevBalance',
            'totalDebit',
            'totalCredit',
            'endingBalance',
            'year',
            'month',
            'monthName',
            'coaList'
        ));
    }

    public function delete(Request $request, $transactionId)
    {
        try {
            $transaction = CashReport::findOrFail($transactionId);
            $cashReferenceId = $transaction->cash_reference_id;
            $year = $request->query('year');
            $month = $request->query('month');

            $transaction->delete();

            return redirect()->route('cash-reference.month-detail', [
                'id' => $cashReferenceId,
                'year' => $year,
                'month' => $month
            ])->with('success', 'Transaction deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete transaction: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'transaction_date' => 'required|date',
            'coa_id' => 'required|exists:coa,id',
            'description' => 'required|string|max:500',
            'debit_amount' => 'required|numeric|min:0',
            'credit_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cashReport = CashReport::create([
                'cash_reference_id' => $id,
                'transaction_date' => $request->transaction_date,
                'coa_id' => $request->coa_id,
                'description' => $request->description,
                'debit_amount' => $request->debit_amount,
                'credit_amount' => $request->credit_amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully',
                'transaction' => $cashReport->load('coa')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit($transactionId)
    {
        try {
            $transaction = CashReport::with('coa')->findOrFail($transactionId);

            return response()->json([
                'success' => true,
                'transaction' => [
                    'id' => $transaction->id,
                    'transaction_date' => $transaction->transaction_date,
                    'coa_id' => $transaction->coa_id,
                    'description' => $transaction->description,
                    'debit_amount' => $transaction->debit_amount,
                    'credit_amount' => $transaction->credit_amount,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found: ' . $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $transactionId)
    {
        $validator = Validator::make($request->all(), [
            'transaction_date' => 'required|date',
            'coa_id' => 'required|exists:coa,id',
            'description' => 'required|string|max:500',
            'debit_amount' => 'required|numeric|min:0',
            'credit_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transaction = CashReport::findOrFail($transactionId);

            $transaction->update([
                'transaction_date' => $request->transaction_date,
                'coa_id' => $request->coa_id,
                'description' => $request->description,
                'debit_amount' => $request->debit_amount,
                'credit_amount' => $request->credit_amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transaction updated successfully',
                'transaction' => $transaction->load('coa')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update transaction: ' . $e->getMessage()
            ], 500);
        }
    }
}
