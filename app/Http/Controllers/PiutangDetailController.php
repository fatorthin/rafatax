<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\SaldoAwalPiutang;
use App\Filament\Pages\PiutangPerClient;
use Illuminate\Http\Request;

class PiutangDetailController extends Controller
{
    public function show($id)
    {
        $client = Client::findOrFail($id);
        $page = new PiutangPerClient();
        $transactions = $page->getClientTransactions($client);

        // Calculate totals for summary cards
        $saldoAwal = 0;
        $totalInvoice = 0;
        $totalPembayaran = 0;
        $totalPotongan = 0;

        foreach ($transactions as $tx) {
            if ($tx['type'] === 'Saldo Awal') {
                $saldoAwal += $tx['debit'];
            } elseif ($tx['type'] === 'Sales Invoice') {
                $totalInvoice += $tx['debit'];
            } elseif ($tx['type'] === 'Sales Receipt') {
                $totalPembayaran += $tx['kredit'];
            } elseif ($tx['type'] === 'Discount MoU' || $tx['type'] === 'Cancel MoU') {
                $totalPotongan += $tx['kredit'];
            }
        }

        $sisaPiutang = $saldoAwal + $totalInvoice - $totalPembayaran - $totalPotongan;

        $mous = $client->mous()->with(['cost_lists', 'categoryMou', 'invoices.costListInvoices'])->get();

        $saldoAwalRecords = SaldoAwalPiutang::where('client_id', $client->id)->orderBy('year', 'asc')->get();
        $saldoAwalRecord = $saldoAwalRecords->first();

        return view('filament.pages.piutang-detail-standalone', compact(
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
            'amount' => 'required|numeric|min:0',
            'year' => 'required|integer',
            'notes' => 'nullable|string|max:255',
        ]);

        $client = Client::findOrFail($id);

        SaldoAwalPiutang::updateOrCreate(
            [
                'client_id' => $client->id,
                'year' => $request->input('year', 2024),
            ],
            [
                'amount' => $request->input('amount'),
                'notes' => $request->input('notes'),
            ]
        );

        return redirect()->back()->with('success', 'Saldo awal piutang berhasil diperbarui');
    }
}
