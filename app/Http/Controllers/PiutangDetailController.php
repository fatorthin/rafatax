<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\SaldoAwalPiutang;
use App\Filament\Pages\PiutangPerClient;
use Illuminate\Http\Request;

class PiutangDetailController extends Controller
{
    public function show($id, Request $request)
    {
        $client = Client::findOrFail($id);
        $periode = $request->query('periode', 'all');
        $page = new PiutangPerClient();
        $transactions = $page->getClientTransactions($client, $periode);

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

        $mous = $client->mous()->with(['cost_lists', 'categoryMou', 'invoices.costListInvoices'])->get();

        $saldoAwalRecords = SaldoAwalPiutang::where('client_id', $client->id)->orderBy('year', 'asc')->get();
        $saldoAwalRecord = SaldoAwalPiutang::where('client_id', $client->id)->where('year', 2026)->first()
            ?? $saldoAwalRecords->first();

        return view('filament.pages.piutang-detail-standalone', compact(
            'client',
            'periode',
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
                'year' => (int) $request->input('year', 2026),
            ],
            [
                'amount' => (float) $request->input('amount'),
                'notes' => $request->input('notes'),
            ]
        );

        return redirect()->back()->with('success', 'Saldo awal piutang berhasil diperbarui');
    }
}
