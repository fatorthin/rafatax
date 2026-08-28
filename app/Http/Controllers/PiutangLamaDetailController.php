<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\SaldoAwalPiutang;
use App\Filament\Pages\PiutangLamaPerClient;
use Illuminate\Http\Request;

class PiutangLamaDetailController extends Controller
{
    public function show($id, Request $request)
    {
        $client = Client::findOrFail($id);
        $page = new PiutangLamaPerClient();
        $transactions = $page->getClientTransactions($client);

        // Calculate totals for summary cards
        $saldoAwal = 0;
        $totalInvoice = 0;
        $totalPembayaran = 0;
        $totalPotongan = 0;

        foreach ($transactions as $tx) {
            if ($tx['type'] === 'Saldo Awal') {
                $saldoAwal += $tx['amount'];
            } elseif ($tx['type'] === 'Sales Invoice') {
                $totalInvoice += $tx['debit'];
            } elseif (str_starts_with($tx['type'], 'Sales Receipt') || str_starts_with($tx['type'], 'Pembayaran')) {
                $totalPembayaran += $tx['kredit'];
            } elseif ($tx['type'] === 'Discount MoU' || $tx['type'] === 'Cancel MoU') {
                $totalPotongan += $tx['kredit'];
            }
        }

        $sisaPiutang = $saldoAwal + $totalInvoice - $totalPembayaran - $totalPotongan;

        $mous = $client->mous()
            ->where(function ($q) {
                $q->where('start_date', '<', '2026-01-01')
                    ->orWhere(function ($sub) {
                        $sub->whereNull('start_date')->where('created_at', '<', '2026-01-01');
                    });
            })
            ->with(['cost_lists', 'categoryMou', 'invoices.costListInvoices'])
            ->get();

        $saldoAwalRecords = SaldoAwalPiutang::where('client_id', $client->id)->orderBy('year', 'asc')->get();
        $saldoAwalRecord = SaldoAwalPiutang::where('client_id', $client->id)->where('year', 2025)->first()
            ?? $saldoAwalRecords->first();

        return view('filament.pages.piutang-lama-detail-standalone', compact(
            'client',
            'transactions',
            'saldoAwal',
            'saldoAwalRecord',
            'saldoAwalRecords',
            'totalInvoice',
            'totalPembayaran',
            'totalPotongan',
            'sisaPiutang',
            'mous'
        ));
    }

    public function updateSaldoAwal($id, Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'year' => 'nullable|integer',
            'notes' => 'nullable|string|max:255',
        ]);

        $client = Client::findOrFail($id);

        SaldoAwalPiutang::updateOrCreate(
            [
                'client_id' => $client->id,
                'year' => (int) $request->input('year', 2025),
            ],
            [
                'amount' => (float) $request->input('amount'),
                'notes' => $request->input('notes'),
            ]
        );

        return redirect()->back()->with('success', 'Saldo awal piutang lama berhasil diperbarui');
    }
}
