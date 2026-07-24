<?php

namespace App\Http\Controllers;

use App\Models\Client;
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

        foreach ($transactions as $tx) {
            if ($tx['type'] === 'Saldo Awal') {
                $saldoAwal = $tx['debit'];
            } elseif ($tx['type'] === 'Sales Invoice') {
                $totalInvoice += $tx['debit'];
            } elseif ($tx['type'] === 'Sales Receipt') {
                $totalPembayaran += $tx['kredit'];
            }
        }

        $sisaPiutang = $saldoAwal + $totalInvoice - $totalPembayaran;

        return view('filament.pages.piutang-detail-standalone', compact(
            'client',
            'transactions',
            'saldoAwal',
            'totalInvoice',
            'totalPembayaran',
            'sisaPiutang'
        ));
    }
}
