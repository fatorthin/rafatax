<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NeracaLajurPiutangController extends Controller
{
    public function exportDetailJP(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year  = (int) $request->get('year', now()->year);

        ini_set('memory_limit', '1024M');
        set_time_limit(600);

        // Delegate to the existing Livewire page class export method for detail JP
        $page = new \App\Filament\Resources\CashReportResource\Pages\NeracaLajurPiutang();
        $page->month = $month;
        $page->year  = $year;
        $page->exportDetailJurnalPendapatan();
    }

    public function exportNeraca(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year  = (int) $request->get('year', now()->year);

        ini_set('memory_limit', '1024M');
        set_time_limit(600);

        // Delegate to the existing Livewire page class export method for Neraca Lajur
        $page = new \App\Filament\Resources\CashReportResource\Pages\NeracaLajurPiutang();
        $page->month = $month;
        $page->year  = $year;
        $page->exportToExcel();
    }

    /**
     * Halaman Detail Transaksi Kas Bank (Opsi 2).
     */
    public function detailBank(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year  = (int) $request->get('year', now()->year);
        $coaId = $request->get('coa_id');
        $type  = strtolower($request->get('type', 'kredit'));
        $from  = $request->get('from', 'admin');

        $data = $this->getBankDetailData($coaId, $month, $year, $type);
        $data['from'] = $from;

        return view('neraca-lajur.detail-bank', $data);
    }

    /**
     * Export Excel Detail Transaksi Kas Bank.
     */
    public function exportDetailBank(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year  = (int) $request->get('year', now()->year);
        $coaId = $request->get('coa_id');
        $type  = strtolower($request->get('type', 'kredit'));

        $data = $this->getBankDetailData($coaId, $month, $year, $type);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle('Detail Transaksi Bank');

        // Header Dokumen
        $sheet->mergeCells('A1:G1');
        $titleText = 'DETAIL TRANSAKSI KAS BANK - ' . ($data['coa'] ? $data['coa']->code . ' ' . $data['coa']->name : 'SEMUA AKUN');
        $sheet->setCellValue('A1', $titleText);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:G2');
        $periodeText = 'Periode: ' . $data['monthName'] . ' ' . $year . ' | Kolom: ' . strtoupper($type) . ' | Total: Rp ' . number_format($data['totalAmount'], 0, ',', '.');
        $sheet->setCellValue('A2', $periodeText);
        $sheet->getStyle('A2')->getFont()->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Rekapitulasi Per Bank
        $row = 4;
        $sheet->setCellValue("A{$row}", 'REKAPITULASI PER KAS BANK');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue("A{$row}", 'No');
        $sheet->setCellValue("B{$row}", 'Kas / Bank');
        $sheet->setCellValue("C{$row}", 'Kepemilikan');
        $sheet->setCellValue("D{$row}", 'Jumlah Transaksi');
        $sheet->setCellValue("E{$row}", 'Total Nominal (Rp)');
        $sheet->getStyle("A{$row}:E{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:E{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('E2E8F0');
        $sheet->getStyle("A{$row}:E{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $rekapStartRow = $row;
        $row++;

        $noBank = 1;
        foreach ($data['summaryByBank'] as $bankSummary) {
            $sheet->setCellValue("A{$row}", $noBank++);
            $sheet->setCellValue("B{$row}", $bankSummary['name']);
            $sheet->setCellValue("C{$row}", $bankSummary['status_kepemilikan'] ?? '-');
            $sheet->setCellValue("D{$row}", $bankSummary['count']);
            $sheet->setCellValue("E{$row}", $bankSummary['total_amount']);
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("A{$row}:D{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        // Total Rekapitulasi
        $sheet->setCellValue("A{$row}", 'TOTAL');
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("D{$row}", $data['totalCount']);
        $sheet->setCellValue("E{$row}", $data['totalAmount']);
        $sheet->getStyle("A{$row}:E{$row}")->getFont()->setBold(true);
        $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$row}:E{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F1F5F9');

        $rekapBorderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'CBD5E1'],
                ],
            ],
        ];
        $sheet->getStyle("A{$rekapStartRow}:E{$row}")->applyFromArray($rekapBorderStyle);

        // Tabel Detail Transaksi
        $row += 3;
        $sheet->setCellValue("A{$row}", 'RINCIAN TRANSAKSI');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        $detailStartRow = $row;
        $sheet->setCellValue("A{$row}", 'No');
        $sheet->setCellValue("B{$row}", 'Tanggal');
        $sheet->setCellValue("C{$row}", 'Kas / Bank');
        $sheet->setCellValue("D{$row}", 'No. Invoice');
        $sheet->setCellValue("E{$row}", 'Klien / Perusahaan');
        $sheet->setCellValue("F{$row}", 'Keterangan');
        $sheet->setCellValue("G{$row}", 'Nominal (Rp)');
        $sheet->getStyle("A{$row}:G{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:G{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('DBEAFE');
        $sheet->getStyle("A{$row}:G{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $row++;

        $noTrx = 1;
        foreach ($data['transactions'] as $trx) {
            $sheet->setCellValue("A{$row}", $noTrx++);
            $sheet->setCellValue("B{$row}", \Carbon\Carbon::parse($trx['transaction_date'])->format('d/m/Y'));
            $sheet->setCellValue("C{$row}", $trx['bank_name']);
            $sheet->setCellValue("D{$row}", $trx['invoice_number'] ?: '-');
            $sheet->setCellValue("E{$row}", $trx['client_name'] ?: '-');
            $sheet->setCellValue("F{$row}", $trx['description']);
            $sheet->setCellValue("G{$row}", $trx['amount']);
            $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("A{$row}:B{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        // Total Detail
        $sheet->setCellValue("A{$row}", 'TOTAL');
        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->setCellValue("G{$row}", $data['totalAmount']);
        $sheet->getStyle("A{$row}:G{$row}")->getFont()->setBold(true);
        $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$row}:G{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EFF6FF');

        $sheet->getStyle("A{$detailStartRow}:G{$row}")->applyFromArray($rekapBorderStyle);

        // Auto width
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $coaCodeSanitized = $data['coa'] ? preg_replace('/[^A-Za-z0-9\-]/', '_', $data['coa']->code) : 'Semua';
        $filename = "Detail_Bank_{$coaCodeSanitized}_{$month}_{$year}.xlsx";

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename);
    }

    /**
     * Helper untuk mengambil data detail transaksi bank dan ringkasannya.
     */
    private function getBankDetailData($coaId, int $month, int $year, string $type): array
    {
        $bankCashReferenceIds = [1, 2, 3, 4, 5];
        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth()->format('Y-m-d');
        $endDate   = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');

        $coa = null;
        if ($coaId && $coaId !== 'all') {
            $coa = \App\Models\Coa::find($coaId);
        }

        $query = \App\Models\CashReport::query()
            ->with([
                'cashReference',
                'coa',
                'client',
                'invoice.client',
                'invoice.mou.client',
                'invoice.memo',
            ])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereNull('deleted_at');

        $isBankDirectAccount = false;
        $directCashReferenceId = null;

        if ($coa) {
            if ($coa->code === 'AO-1010') {
                $query->where('coa_id', 162)->whereIn('cash_reference_id', $bankCashReferenceIds);
                if ($type === 'debit') {
                    $query->where('credit_amount', '>', 0);
                } else {
                    $query->where('debit_amount', '>', 0);
                }
            } elseif ($coa->code === 'AO-101.2') {
                $query->where('coa_id', 78)->whereIn('cash_reference_id', $bankCashReferenceIds);
                if ($type === 'debit') {
                    $query->where('credit_amount', '>', 0);
                } else {
                    $query->where('debit_amount', '>', 0);
                }
            } elseif ($coa->code === 'AO-102.1') {
                $isBankDirectAccount = true;
                $directCashReferenceId = 1;
                $query->where('cash_reference_id', 1);
                if ($type === 'debit') {
                    $query->where('debit_amount', '>', 0);
                } else {
                    $query->where('credit_amount', '>', 0);
                }
            } elseif ($coa->code === 'AO-102.2') {
                $isBankDirectAccount = true;
                $directCashReferenceId = 3;
                $query->where('cash_reference_id', 3);
                if ($type === 'debit') {
                    $query->where('debit_amount', '>', 0);
                } else {
                    $query->where('credit_amount', '>', 0);
                }
            } elseif ($coa->code === 'AO-102.3') {
                $isBankDirectAccount = true;
                $directCashReferenceId = 2;
                $query->where('cash_reference_id', 2);
                if ($type === 'debit') {
                    $query->where('debit_amount', '>', 0);
                } else {
                    $query->where('credit_amount', '>', 0);
                }
            } elseif ($coa->code === 'AO-102.4') {
                $isBankDirectAccount = true;
                $directCashReferenceId = 4;
                $query->where('cash_reference_id', 4);
                if ($type === 'debit') {
                    $query->where('debit_amount', '>', 0);
                } else {
                    $query->where('credit_amount', '>', 0);
                }
            } elseif ($coa->code === 'AO-102.5') {
                $isBankDirectAccount = true;
                $directCashReferenceId = 5;
                $query->where('cash_reference_id', 5);
                if ($type === 'debit') {
                    $query->where('debit_amount', '>', 0);
                } else {
                    $query->where('credit_amount', '>', 0);
                }
            } else {
                // Akun umum (misal AO-103.6 s/d AO-103.12, pendapatan, biaya)
                $query->where('coa_id', $coa->id)->whereIn('cash_reference_id', $bankCashReferenceIds);
                if ($type === 'debit') {
                    $query->where('credit_amount', '>', 0);
                } else {
                    $query->where('debit_amount', '>', 0);
                }
            }
        } else {
            // Semua Akun
            $query->whereIn('cash_reference_id', $bankCashReferenceIds);
            if ($type === 'debit') {
                $query->where('credit_amount', '>', 0);
            } else {
                $query->where('debit_amount', '>', 0);
            }
        }

        $reports = $query->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $transactions = $reports->map(function ($r) use ($isBankDirectAccount, $type) {
            $clientName = '';
            if ($r->invoice) {
                $clientName = $r->invoice->client_name;
            } elseif ($r->client) {
                $clientName = $r->client->company_name;
            }

            // Hitung nominal yang sesuai dengan jenis kolom
            if ($isBankDirectAccount) {
                $amount = $type === 'debit' ? (float) $r->debit_amount : (float) $r->credit_amount;
            } else {
                $amount = $type === 'debit' ? (float) $r->credit_amount : (float) $r->debit_amount;
            }

            return [
                'id'                => $r->id,
                'transaction_date'  => $r->transaction_date,
                'cash_reference_id' => $r->cash_reference_id,
                'bank_name'         => trim($r->cashReference->name ?? 'Bank'),
                'status_kepemilikan'=> $r->cashReference->status_kepemilikan ?? '-',
                'coa_code'          => $r->coa->code ?? '-',
                'coa_name'          => $r->coa->name ?? '-',
                'description'       => $r->description,
                'invoice_id'        => $r->invoice_id,
                'invoice_number'    => $r->invoice->invoice_number ?? null,
                'client_name'       => $clientName,
                'debit_amount'      => (float) $r->debit_amount,
                'credit_amount'     => (float) $r->credit_amount,
                'amount'            => $amount,
            ];
        });

        // Rekapitulasi per Bank
        $summaryByBank = [];
        $allBanks = \App\Models\CashReference::whereIn('id', $bankCashReferenceIds)->orderBy('sort_order')->get();

        foreach ($allBanks as $bankRef) {
            $bankTrx = $transactions->where('cash_reference_id', $bankRef->id);
            $total = $bankTrx->sum('amount');
            $count = $bankTrx->count();

            // Jika filter CoA langsung ke satu bank spesifik, hanya masukkan bank tersebut
            if ($isBankDirectAccount && $directCashReferenceId !== $bankRef->id) {
                continue;
            }

            if ($count > 0 || ($isBankDirectAccount && $directCashReferenceId === $bankRef->id)) {
                $summaryByBank[$bankRef->id] = [
                    'id'                => $bankRef->id,
                    'name'              => trim($bankRef->name),
                    'status_kepemilikan'=> $bankRef->status_kepemilikan,
                    'total_amount'      => $total,
                    'count'             => $count,
                ];
            }
        }

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return [
            'coa'           => $coa,
            'coaId'         => $coaId,
            'month'         => $month,
            'year'          => $year,
            'monthName'     => $monthNames[$month] ?? "Bulan {$month}",
            'type'          => $type,
            'transactions'  => $transactions,
            'summaryByBank' => $summaryByBank,
            'totalAmount'   => $transactions->sum('amount'),
            'totalCount'    => $transactions->count(),
        ];
    }
}
