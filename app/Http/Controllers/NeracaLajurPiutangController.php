<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NeracaLajurPiutangController extends Controller
{
    public function exportDetailJP(Request $request)
    {
        $month  = (int) $request->get('month', now()->month);
        $year   = (int) $request->get('year', now()->year);
        $entity = strtolower($request->get('entity', 'all'));

        ini_set('memory_limit', '1024M');
        set_time_limit(600);

        // Delegate to the existing Livewire page class export method for detail JP
        $page = new \App\Filament\Resources\CashReportResource\Pages\NeracaLajurPiutang();
        $page->month  = $month;
        $page->year   = $year;
        $page->entity = $entity;
        $page->exportDetailJurnalPendapatan();
    }

    /**
     * Halaman Detail Transaksi Kas Besar.
     */
    public function detailKasBesar(Request $request)
    {
        $month  = (int) $request->get('month', now()->month);
        $year   = (int) $request->get('year', now()->year);
        $coaId  = $request->get('coa_id');
        $type   = strtolower($request->get('type', 'debit'));
        $from   = $request->get('from', 'admin');
        $entity = strtolower($request->get('entity', 'all'));

        $data = $this->getKasBesarDetailData($coaId, $month, $year, $type, $entity);
        $data['from']   = $from;
        $data['entity'] = $entity;

        return view('neraca-lajur.detail-kas-besar', $data);
    }

    /**
     * Export Excel Detail Transaksi Kas Besar.
     */
    public function exportDetailKasBesar(Request $request)
    {
        $month  = (int) $request->get('month', now()->month);
        $year   = (int) $request->get('year', now()->year);
        $coaId  = $request->get('coa_id');
        $type   = strtolower($request->get('type', 'debit'));
        $entity = strtolower($request->get('entity', 'all'));

        $data = $this->getKasBesarDetailData($coaId, $month, $year, $type, $entity);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail Kas Besar');

        // Header Dokumen
        $sheet->mergeCells('A1:G1');
        $titleText = 'DETAIL TRANSAKSI KAS BESAR - ' . ($data['coa'] ? $data['coa']->code . ' ' . $data['coa']->name : 'SEMUA AKUN');
        $sheet->setCellValue('A1', $titleText);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:G2');
        $periodeText = 'Periode: ' . $data['monthName'] . ' ' . $year . ' | Kolom: ' . strtoupper($type) . ' | Total: Rp ' . number_format($data['totalAmount'], 0, ',', '.');
        $sheet->setCellValue('A2', $periodeText);
        $sheet->getStyle('A2')->getFont()->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Rekapitulasi Per Kas
        $row = 4;
        $sheet->setCellValue("A{$row}", 'REKAPITULASI KAS BESAR');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue("A{$row}", 'No');
        $sheet->setCellValue("B{$row}", 'Kas');
        $sheet->setCellValue("C{$row}", 'Kepemilikan');
        $sheet->setCellValue("D{$row}", 'Jumlah Transaksi');
        $sheet->setCellValue("E{$row}", 'Total Nominal (Rp)');
        $sheet->getStyle("A{$row}:E{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:E{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('E2E8F0');
        $sheet->getStyle("A{$row}:E{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $row++;

        $no = 1;
        $rekapStartRow = $row;
        foreach ($data['summaryByCash'] as $cData) {
            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $cData['name']);
            $sheet->setCellValue("C{$row}", $cData['status_kepemilikan']);
            $sheet->setCellValue("D{$row}", $cData['count']);
            $sheet->setCellValue("E{$row}", $cData['total_amount']);
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}:D{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $row++;
        }

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
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'CBD5E1']]
            ]
        ];
        $sheet->getStyle("A" . ($rekapStartRow - 1) . ":E{$row}")->applyFromArray($rekapBorderStyle);

        $row += 2;

        // Rincian Transaksi
        $sheet->setCellValue("A{$row}", 'RINCIAN TRANSAKSI');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        $detailStartRow = $row;
        $sheet->setCellValue("A{$row}", 'No');
        $sheet->setCellValue("B{$row}", 'Tanggal');
        $sheet->setCellValue("C{$row}", 'Kas');
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
            $sheet->setCellValue("C{$row}", $trx['cash_name']);
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

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $coaCodeSanitized = $data['coa'] ? preg_replace('/[^A-Za-z0-9\-]/', '_', $data['coa']->code) : 'Semua';
        $filename = "Detail_Kas_Besar_{$coaCodeSanitized}_{$month}_{$year}.xlsx";

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename);
    }

    /**
     * Helper untuk mengambil data detail transaksi Kas Besar.
     */
    private function getKasBesarDetailData($coaId, int $month, int $year, string $type, string $entity = 'all'): array
    {
        $isSeparated = ($year > 2026) || ($year == 2026 && $month >= 4);

        if ($isSeparated && $entity === 'pt') {
            $kasBesarIds = [10];
        } elseif ($isSeparated && $entity === 'kkp') {
            $kasBesarIds = [6];
        } else {
            $kasBesarIds = $isSeparated ? [6, 10] : [6];
        }

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
            ->whereIn('cash_reference_id', $kasBesarIds)
            ->whereNull('deleted_at');

        $isKasBesarDirectAccount = false;
        if ($coa) {
            if ($coa->code === 'AO-101' || $coa->id == 76) {
                $isKasBesarDirectAccount = true;
                if ($type === 'debit') {
                    $query->where('debit_amount', '>', 0);
                } else {
                    $query->where('credit_amount', '>', 0);
                }
            } else {
                $query->where('coa_id', $coa->id);
                if ($type === 'debit') {
                    $query->where('credit_amount', '>', 0);
                } else {
                    $query->where('debit_amount', '>', 0);
                }
            }
        } else {
            if ($type === 'debit') {
                $query->where('credit_amount', '>', 0);
            } else {
                $query->where('debit_amount', '>', 0);
            }
        }

        $reports = $query->orderBy('transaction_date', 'asc')->orderBy('id', 'asc')->get();

        $transactions = $reports->map(function ($r) use ($isKasBesarDirectAccount, $type) {
            $clientName = '';
            if ($r->invoice) {
                $clientName = $r->invoice->client_name;
            } elseif ($r->client) {
                $clientName = $r->client->company_name;
            }

            if ($isKasBesarDirectAccount) {
                $amount = $type === 'debit' ? (float) $r->debit_amount : (float) $r->credit_amount;
            } else {
                $amount = $type === 'debit' ? (float) $r->credit_amount : (float) $r->debit_amount;
            }

            return [
                'id'                => $r->id,
                'transaction_date'  => $r->transaction_date,
                'cash_reference_id' => $r->cash_reference_id,
                'cash_name'         => trim($r->cashReference->name ?? 'Kas Besar'),
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

        $summaryByCash = [];
        $allCashRefs = \App\Models\CashReference::whereIn('id', $kasBesarIds)->orderBy('sort_order')->get();
        foreach ($allCashRefs as $ref) {
            $refTrx = $transactions->where('cash_reference_id', $ref->id);
            $total = $refTrx->sum('amount');
            $count = $refTrx->count();
            if ($count > 0 || count($kasBesarIds) === 1) {
                $summaryByCash[$ref->id] = [
                    'id'                => $ref->id,
                    'name'              => trim($ref->name),
                    'status_kepemilikan'=> $ref->status_kepemilikan,
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
            'summaryByCash' => $summaryByCash,
            'totalAmount'   => $transactions->sum('amount'),
            'totalCount'    => $transactions->count(),
        ];
    }

    /**
     * Halaman Detail Transaksi Kas Kecil.
     */
    public function detailKasKecil(Request $request)
    {
        $month  = (int) $request->get('month', now()->month);
        $year   = (int) $request->get('year', now()->year);
        $coaId  = $request->get('coa_id');
        $type   = strtolower($request->get('type', 'debit'));
        $from   = $request->get('from', 'admin');
        $entity = strtolower($request->get('entity', 'all'));

        $data = $this->getKasKecilDetailData($coaId, $month, $year, $type, $entity);
        $data['from']   = $from;
        $data['entity'] = $entity;

        return view('neraca-lajur.detail-kas-kecil', $data);
    }

    /**
     * Export Excel Detail Transaksi Kas Kecil.
     */
    public function exportDetailKasKecil(Request $request)
    {
        $month  = (int) $request->get('month', now()->month);
        $year   = (int) $request->get('year', now()->year);
        $coaId  = $request->get('coa_id');
        $type   = strtolower($request->get('type', 'debit'));
        $entity = strtolower($request->get('entity', 'all'));

        $data = $this->getKasKecilDetailData($coaId, $month, $year, $type, $entity);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail Kas Kecil');

        // Header Dokumen
        $sheet->mergeCells('A1:G1');
        $titleText = 'DETAIL TRANSAKSI KAS KECIL - ' . ($data['coa'] ? $data['coa']->code . ' ' . $data['coa']->name : 'SEMUA AKUN');
        $sheet->setCellValue('A1', $titleText);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:G2');
        $periodeText = 'Periode: ' . $data['monthName'] . ' ' . $year . ' | Kolom: ' . strtoupper($type) . ' | Total: Rp ' . number_format($data['totalAmount'], 0, ',', '.');
        $sheet->setCellValue('A2', $periodeText);
        $sheet->getStyle('A2')->getFont()->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Rekapitulasi Per Kas
        $row = 4;
        $sheet->setCellValue("A{$row}", 'REKAPITULASI KAS KECIL');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue("A{$row}", 'No');
        $sheet->setCellValue("B{$row}", 'Kas');
        $sheet->setCellValue("C{$row}", 'Kepemilikan');
        $sheet->setCellValue("D{$row}", 'Jumlah Transaksi');
        $sheet->setCellValue("E{$row}", 'Total Nominal (Rp)');
        $sheet->getStyle("A{$row}:E{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:E{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('E2E8F0');
        $sheet->getStyle("A{$row}:E{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $row++;

        $no = 1;
        $rekapStartRow = $row;
        foreach ($data['summaryByCash'] as $cData) {
            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $cData['name']);
            $sheet->setCellValue("C{$row}", $cData['status_kepemilikan']);
            $sheet->setCellValue("D{$row}", $cData['count']);
            $sheet->setCellValue("E{$row}", $cData['total_amount']);
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}:D{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $row++;
        }

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
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'CBD5E1']]
            ]
        ];
        $sheet->getStyle("A" . ($rekapStartRow - 1) . ":E{$row}")->applyFromArray($rekapBorderStyle);

        $row += 2;

        // Rincian Transaksi
        $sheet->setCellValue("A{$row}", 'RINCIAN TRANSAKSI');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        $detailStartRow = $row;
        $sheet->setCellValue("A{$row}", 'No');
        $sheet->setCellValue("B{$row}", 'Tanggal');
        $sheet->setCellValue("C{$row}", 'Kas');
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
            $sheet->setCellValue("C{$row}", $trx['cash_name']);
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

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $coaCodeSanitized = $data['coa'] ? preg_replace('/[^A-Za-z0-9\-]/', '_', $data['coa']->code) : 'Semua';
        $filename = "Detail_Kas_Kecil_{$coaCodeSanitized}_{$month}_{$year}.xlsx";

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename);
    }

    /**
     * Helper untuk mengambil data detail transaksi Kas Kecil.
     */
    private function getKasKecilDetailData($coaId, int $month, int $year, string $type, string $entity = 'all'): array
    {
        $isSeparated = ($year > 2026) || ($year == 2026 && $month >= 4);

        if ($isSeparated && $entity === 'pt') {
            $kasKecilIds = [9];
        } elseif ($isSeparated && $entity === 'kkp') {
            $kasKecilIds = [7];
        } else {
            $kasKecilIds = [7, 9];
        }

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
            ->whereIn('cash_reference_id', $kasKecilIds)
            ->whereNull('deleted_at');

        $isKasKecilDirectAccount = false;
        if ($coa) {
            if ($coa->code === 'AO-101.1' || $coa->id == 77) {
                $isKasKecilDirectAccount = true;
                if ($type === 'debit') {
                    $query->where('debit_amount', '>', 0);
                } else {
                    $query->where('credit_amount', '>', 0);
                }
            } else {
                $query->where('coa_id', $coa->id);
                if ($type === 'debit') {
                    $query->where('credit_amount', '>', 0);
                } else {
                    $query->where('debit_amount', '>', 0);
                }
            }
        } else {
            if ($type === 'debit') {
                $query->where('debit_amount', '>', 0);
            } else {
                $query->where('credit_amount', '>', 0);
            }
        }

        $reports = $query->orderBy('transaction_date', 'asc')->orderBy('id', 'asc')->get();

        $transactions = $reports->map(function ($r) use ($isKasKecilDirectAccount, $type) {
            $clientName = '';
            if ($r->invoice) {
                $clientName = $r->invoice->client_name;
            } elseif ($r->client) {
                $clientName = $r->client->company_name;
            }

            if ($isKasKecilDirectAccount) {
                $amount = $type === 'debit' ? (float) $r->debit_amount : (float) $r->credit_amount;
            } else {
                $amount = $type === 'debit' ? (float) $r->credit_amount : (float) $r->debit_amount;
            }

            return [
                'id'                => $r->id,
                'transaction_date'  => $r->transaction_date,
                'cash_reference_id' => $r->cash_reference_id,
                'cash_name'         => trim($r->cashReference->name ?? 'Kas Kecil'),
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

        $summaryByCash = [];
        $allCashRefs = \App\Models\CashReference::whereIn('id', $kasKecilIds)->orderBy('sort_order')->get();
        foreach ($allCashRefs as $ref) {
            $refTrx = $transactions->where('cash_reference_id', $ref->id);
            $total = $refTrx->sum('amount');
            $count = $refTrx->count();
            if ($count > 0 || count($kasKecilIds) === 1) {
                $summaryByCash[$ref->id] = [
                    'id'                => $ref->id,
                    'name'              => trim($ref->name),
                    'status_kepemilikan'=> $ref->status_kepemilikan,
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
            'summaryByCash' => $summaryByCash,
            'totalAmount'   => $transactions->sum('amount'),
            'totalCount'    => $transactions->count(),
        ];
    }

    /**
     * Halaman Detail Transaksi Jurnal Pendapatan.
     */
    public function detailJurnalPendapatan(Request $request)
    {
        $month  = (int) $request->get('month', now()->month);
        $year   = (int) $request->get('year', now()->year);
        $coaId  = $request->get('coa_id');
        $type   = strtolower($request->get('type', 'debit'));
        $from   = $request->get('from', 'admin');
        $entity = strtolower($request->get('entity', 'all'));

        $data = $this->getJurnalPendapatanDetailData($coaId, $month, $year, $type, $entity);
        $data['from']   = $from;
        $data['entity'] = $entity;

        return view('neraca-lajur.detail-jurnal-pendapatan', $data);
    }

    /**
     * Export Excel Detail Transaksi Jurnal Pendapatan per CoA & Type.
     */
    public function exportDetailJurnalPendapatan(Request $request)
    {
        $month  = (int) $request->get('month', now()->month);
        $year   = (int) $request->get('year', now()->year);
        $coaId  = $request->get('coa_id');
        $type   = strtolower($request->get('type', 'debit'));
        $entity = strtolower($request->get('entity', 'all'));

        $data = $this->getJurnalPendapatanDetailData($coaId, $month, $year, $type, $entity);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail Jurnal Pendapatan');

        // Header Dokumen
        $sheet->mergeCells('A1:H1');
        $titleText = 'DETAIL JURNAL PENDAPATAN - ' . ($data['coa'] ? $data['coa']->code . ' ' . $data['coa']->name : 'SEMUA AKUN');
        $sheet->setCellValue('A1', $titleText);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:H2');
        $periodeText = 'Periode: ' . $data['monthName'] . ' ' . $year . ' | Kolom: ' . strtoupper($type) . ' | Total: Rp ' . number_format($data['totalAmount'], 0, ',', '.');
        $sheet->setCellValue('A2', $periodeText);
        $sheet->getStyle('A2')->getFont()->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Rekapitulasi per Sumber
        $row = 4;
        $sheet->setCellValue("A{$row}", 'REKAPITULASI SUMBER JURNAL PENDAPATAN');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue("A{$row}", 'No');
        $sheet->setCellValue("B{$row}", 'Sumber Transaksi');
        $sheet->setCellValue("C{$row}", 'Jumlah Transaksi');
        $sheet->setCellValue("D{$row}", 'Total Nominal (Rp)');
        $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:D{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('E2E8F0');
        $sheet->getStyle("A{$row}:D{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $row++;

        $no = 1;
        $rekapStartRow = $row;
        foreach ($data['summaryBySource'] as $sData) {
            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $sData['name']);
            $sheet->setCellValue("C{$row}", $sData['count']);
            $sheet->setCellValue("D{$row}", $sData['total_amount']);
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("A{$row}:C{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        $sheet->setCellValue("A{$row}", 'TOTAL');
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("C{$row}", $data['totalCount']);
        $sheet->setCellValue("D{$row}", $data['totalAmount']);
        $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
        $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$row}:D{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('F1F5F9');

        $rekapBorderStyle = [
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'CBD5E1']]
            ]
        ];
        $sheet->getStyle("A" . ($rekapStartRow - 1) . ":D{$row}")->applyFromArray($rekapBorderStyle);

        $row += 2;

        // Rincian Transaksi
        $sheet->setCellValue("A{$row}", 'RINCIAN TRANSAKSI');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;

        $detailStartRow = $row;
        $sheet->setCellValue("A{$row}", 'No');
        $sheet->setCellValue("B{$row}", 'Tanggal');
        $sheet->setCellValue("C{$row}", 'Sumber');
        $sheet->setCellValue("D{$row}", 'No. Ref / Invoice');
        $sheet->setCellValue("E{$row}", 'Klien / Perusahaan');
        $sheet->setCellValue("F{$row}", 'Keterangan');
        $sheet->setCellValue("G{$row}", 'Akun Debit / Kredit');
        $sheet->setCellValue("H{$row}", 'Nominal (Rp)');
        $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:H{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('DBEAFE');
        $sheet->getStyle("A{$row}:H{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $row++;

        $noTrx = 1;
        foreach ($data['transactions'] as $trx) {
            $sheet->setCellValue("A{$row}", $noTrx++);
            $sheet->setCellValue("B{$row}", \Carbon\Carbon::parse($trx['transaction_date'])->format('d/m/Y'));
            $sheet->setCellValue("C{$row}", $trx['source_label']);
            $sheet->setCellValue("D{$row}", $trx['ref_number'] ?: '-');
            $sheet->setCellValue("E{$row}", $trx['client_name'] ?: '-');
            $sheet->setCellValue("F{$row}", $trx['description']);
            $sheet->setCellValue("G{$row}", "DR: {$trx['debit_coa_code']} / CR: {$trx['credit_coa_code']}");
            $sheet->setCellValue("H{$row}", $trx['amount']);
            $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("A{$row}:D{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        // Total Detail
        $sheet->setCellValue("A{$row}", 'TOTAL');
        $sheet->mergeCells("A{$row}:G{$row}");
        $sheet->setCellValue("H{$row}", $data['totalAmount']);
        $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
        $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$row}:H{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EFF6FF');

        $sheet->getStyle("A{$detailStartRow}:H{$row}")->applyFromArray($rekapBorderStyle);

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $coaCodeSanitized = $data['coa'] ? preg_replace('/[^A-Za-z0-9\-]/', '_', $data['coa']->code) : 'Semua';
        $filename = "Detail_Jurnal_Pendapatan_{$coaCodeSanitized}_{$month}_{$year}.xlsx";

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename);
    }

    /**
     * Helper untuk mengambil data detail transaksi Jurnal Pendapatan.
     */
    private function getJurnalPendapatanDetailData($coaId, int $month, int $year, string $type, string $entity = 'all'): array
    {
        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth()->format('Y-m-d');
        $endDate   = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');
        $isSeparated = ($year > 2026) || ($year == 2026 && $month >= 4);

        $piutangMap        = \App\Services\JurnalPendapatanService::getPiutangToPendapatanMap();
        $revToPiutangMap   = \App\Services\JurnalPendapatanService::getRevenueToPiutangMap();
        $piutangToBelumMap = \App\Services\JurnalPendapatanService::getPiutangToBelumDiterimaMap();
        $piutangCoaIds     = array_keys($piutangMap);

        $coa = null;
        if ($coaId && $coaId !== 'all') {
            $coa = \App\Models\Coa::find($coaId);
        }

        $coas = \App\Models\Coa::all()->keyBy('id');
        $transactions = [];

        // 1. Invoice Dibuat
        $invQuery = \DB::table('invoices as inv')
            ->join('cost_list_invoices as cli', 'cli.invoice_id', '=', 'inv.id')
            ->leftJoin('mous as m', 'm.id', '=', 'inv.mou_id')
            ->leftJoin('clients as c_inv', 'c_inv.id', '=', 'inv.client_id')
            ->leftJoin('clients as c_mou', 'c_mou.id', '=', 'm.client_id')
            ->whereNull('inv.deleted_at')
            ->whereNull('cli.deleted_at')
            ->whereNull('inv.memo_id')
            ->whereBetween('inv.invoice_date', [$startDate, $endDate])
            ->selectRaw('
                inv.id as invoice_id,
                inv.invoice_number,
                inv.invoice_date,
                inv.invoice_type,
                cli.id as cli_id,
                cli.description,
                cli.amount,
                cli.coa_id,
                c_inv.company_name as client_name_inv,
                c_mou.company_name as client_name_mou
            ');

        if ($isSeparated && in_array($entity, ['pt', 'kkp'], true)) {
            $invQuery->where('inv.invoice_type', $entity);
        }

        foreach ($invQuery->get() as $inv) {
            $mappedPiutangCoaId = $inv->coa_id;
            if (isset($revToPiutangMap[$mappedPiutangCoaId])) {
                $mappedPiutangCoaId = $revToPiutangMap[$mappedPiutangCoaId];
            } elseif (!isset($piutangMap[$mappedPiutangCoaId])) {
                continue;
            }

            $mappedBelumCoaId = $piutangToBelumMap[$mappedPiutangCoaId] ?? 175;

            $include = false;
            if (!$coa) {
                $include = true;
            } else {
                if ($type === 'debit' && $mappedPiutangCoaId == $coa->id) {
                    $include = true;
                } elseif ($type === 'kredit' && $mappedBelumCoaId == $coa->id) {
                    $include = true;
                }
            }

            if ($include) {
                $cName = $inv->client_name_inv ?: ($inv->client_name_mou ?: '-');
                $transactions[] = [
                    'id'               => 'inv-' . $inv->cli_id,
                    'source'           => 'invoice',
                    'source_label'     => 'Invoice Dibuat',
                    'transaction_date' => $inv->invoice_date,
                    'ref_number'       => $inv->invoice_number,
                    'client_name'      => $cName,
                    'description'      => $inv->description ?: 'Pengakuan Piutang Invoice',
                    'amount'           => (float) $inv->amount,
                    'debit_coa_code'   => $coas->get($mappedPiutangCoaId)->code ?? '-',
                    'debit_coa_name'   => $coas->get($mappedPiutangCoaId)->name ?? '-',
                    'credit_coa_code'  => $coas->get($mappedBelumCoaId)->code ?? '-',
                    'credit_coa_name'  => $coas->get($mappedBelumCoaId)->name ?? '-',
                    'entity'           => strtoupper($inv->invoice_type ?? 'KKP'),
                    'invoice_id'       => $inv->invoice_id,
                ];
            }
        }

        // 2. Penerimaan Kas/Bank dari Piutang
        if ($isSeparated && $entity === 'pt') {
            $cashReferenceIds = [1, 4, 9];
        } elseif ($isSeparated && $entity === 'kkp') {
            $cashReferenceIds = [2, 6, 7];
        } else {
            if ($year == 2026 && $month < 4) {
                $cashReferenceIds = [1, 2, 3, 4, 6, 7, 9];
            } elseif ($year < 2026) {
                $cashReferenceIds = [1, 2, 3, 4, 5, 6, 7, 9];
            } else {
                $cashReferenceIds = [1, 2, 4, 6, 7, 9];
            }
        }

        $cashQuery = \DB::table('cash_reports as cr')
            ->join('cash_references as cref', 'cref.id', '=', 'cr.cash_reference_id')
            ->leftJoin('invoices as inv', 'inv.id', '=', 'cr.invoice_id')
            ->leftJoin('clients as cl', 'cl.id', '=', 'cr.client_id')
            ->whereNull('cr.deleted_at')
            ->whereIn('cr.coa_id', $piutangCoaIds)
            ->whereIn('cr.cash_reference_id', $cashReferenceIds)
            ->whereBetween('cr.transaction_date', [$startDate, $endDate])
            ->selectRaw('
                cr.id,
                cr.transaction_date,
                cr.cash_reference_id,
                cref.name as cash_ref_name,
                cref.status_kepemilikan,
                cr.coa_id as piutang_coa_id,
                cr.debit_amount,
                cr.credit_amount,
                cr.description,
                cr.invoice_id,
                inv.invoice_number,
                cl.company_name as client_name
            ');

        foreach ($cashQuery->get() as $cr) {
            $amount = (float) $cr->debit_amount;
            if ($amount <= 0) continue;

            $pendapatanCoaId = $piutangMap[$cr->piutang_coa_id] ?? null;
            $belumCoaId      = $piutangToBelumMap[$cr->piutang_coa_id] ?? 175;

            $include = false;
            if (!$coa) {
                $include = true;
            } else {
                if ($type === 'debit' && $belumCoaId == $coa->id) {
                    $include = true;
                } elseif ($type === 'kredit' && $pendapatanCoaId == $coa->id) {
                    $include = true;
                }
            }

            if ($include) {
                $transactions[] = [
                    'id'               => 'cr-' . $cr->id,
                    'source'           => 'cash_receipt',
                    'source_label'     => 'Penerimaan Kas/Bank',
                    'transaction_date' => $cr->transaction_date,
                    'ref_number'       => $cr->invoice_number ?: $cr->cash_ref_name,
                    'client_name'      => $cr->client_name ?: '-',
                    'description'      => $cr->description ?: 'Penerimaan Piutang di Kas/Bank',
                    'amount'           => $amount,
                    'debit_coa_code'   => $coas->get($belumCoaId)->code ?? '-',
                    'debit_coa_name'   => $coas->get($belumCoaId)->name ?? '-',
                    'credit_coa_code'  => $coas->get($pendapatanCoaId)->code ?? '-',
                    'credit_coa_name'  => $coas->get($pendapatanCoaId)->name ?? '-',
                    'entity'           => strtoupper($cr->status_kepemilikan ?? '-'),
                    'invoice_id'       => $cr->invoice_id,
                ];
            }
        }

        // 3. PPh 23 Invoice PT (Belum Terchecklist)
        if ($entity === 'all' || $entity === 'pt') {
            $pphItems = \DB::table('invoices as inv')
                ->join('cost_list_invoices as cli', 'cli.invoice_id', '=', 'inv.id')
                ->leftJoin('mous as m', 'm.id', '=', 'inv.mou_id')
                ->leftJoin('clients as c_inv', 'c_inv.id', '=', 'inv.client_id')
                ->leftJoin('clients as c_mou', 'c_mou.id', '=', 'm.client_id')
                ->whereNull('inv.deleted_at')
                ->whereNull('cli.deleted_at')
                ->where('inv.is_include_pph23', true)
                ->where('inv.invoice_type', 'pt')
                ->whereBetween('inv.invoice_date', [$startDate, $endDate])
                ->selectRaw('
                    inv.id as invoice_id,
                    inv.invoice_number,
                    inv.invoice_date,
                    cli.id as cli_id,
                    cli.description,
                    cli.amount,
                    c_inv.company_name as client_name_inv,
                    c_mou.company_name as client_name_mou
                ')
                ->get();

            foreach ($pphItems as $item) {
                $pphAmount = ($item->amount / 98) * 2;
                if ($pphAmount <= 0) continue;

                $include = false;
                if (!$coa) {
                    $include = true;
                } else {
                    if ($type === 'debit' && $coa->id == 91) {
                        $include = true;
                    } elseif ($type === 'kredit' && $coa->id == 220) {
                        $include = true;
                    }
                }

                if ($include) {
                    $cName = $item->client_name_inv ?: ($item->client_name_mou ?: '-');
                    $transactions[] = [
                        'id'               => 'pph-' . $item->cli_id,
                        'source'           => 'pph23_invoice',
                        'source_label'     => 'PPh 23 Invoice',
                        'transaction_date' => $item->invoice_date,
                        'ref_number'       => $item->invoice_number,
                        'client_name'      => $cName,
                        'description'      => "[PPh 23 Invoice] " . ($item->description ?: 'Pengakuan PPh 23'),
                        'amount'           => (float) $pphAmount,
                        'debit_coa_code'   => 'AO-108.1',
                        'debit_coa_name'   => 'PPh 23',
                        'credit_coa_code'  => 'AO-208.8',
                        'credit_coa_name'  => 'Pendapatan Yang Belum Diterima/Fee PPh 23',
                        'entity'           => 'PT',
                        'invoice_id'       => $item->invoice_id,
                    ];
                }
            }
        }

        // 4. PPh 23 Checklist (Sudah Terchecklist)
        if ($entity === 'all' || $entity === 'pt') {
            $pphChecked = \DB::table('invoices as inv')
                ->join('cost_list_invoices as cli', 'cli.invoice_id', '=', 'inv.id')
                ->leftJoin('mous as m', 'm.id', '=', 'inv.mou_id')
                ->leftJoin('clients as c_inv', 'c_inv.id', '=', 'inv.client_id')
                ->leftJoin('clients as c_mou', 'c_mou.id', '=', 'm.client_id')
                ->whereNull('inv.deleted_at')
                ->whereNull('cli.deleted_at')
                ->where('inv.is_pph23_checked', true)
                ->where('inv.invoice_type', 'pt')
                ->whereBetween('inv.tanggal_bukti_potong_pph23', [$startDate, $endDate])
                ->selectRaw('
                    inv.id as invoice_id,
                    inv.invoice_number,
                    inv.tanggal_bukti_potong_pph23,
                    inv.nominal_bukti_potong_pph23,
                    cli.id as cli_id,
                    cli.description,
                    cli.amount as item_amount,
                    c_inv.company_name as client_name_inv,
                    c_mou.company_name as client_name_mou
                ')
                ->get();

            $invTotals = [];
            foreach ($pphChecked as $item) {
                $invTotals[$item->invoice_id] = ($invTotals[$item->invoice_id] ?? 0) + $item->item_amount;
            }

            foreach ($pphChecked as $item) {
                $invTotal = $invTotals[$item->invoice_id] ?: $item->item_amount;
                $prop = $item->item_amount / $invTotal;
                $nominalBp = (float) $item->nominal_bukti_potong_pph23;
                $pphAmount = $nominalBp > 0 ? ($nominalBp * $prop) : (($item->item_amount / 98) * 2);
                if ($pphAmount <= 0) continue;

                $include = false;
                if (!$coa) {
                    $include = true;
                } else {
                    if ($type === 'debit' && $coa->id == 220) {
                        $include = true;
                    } elseif ($type === 'kredit' && $coa->id == 91) {
                        $include = true;
                    }
                }

                if ($include) {
                    $cName = $item->client_name_inv ?: ($item->client_name_mou ?: '-');
                    $transactions[] = [
                        'id'               => 'pphchk-' . $item->cli_id,
                        'source'           => 'pph23_checked',
                        'source_label'     => 'PPh 23 Bukti Potong',
                        'transaction_date' => $item->tanggal_bukti_potong_pph23,
                        'ref_number'       => $item->invoice_number,
                        'client_name'      => $cName,
                        'description'      => "[Bukti Potong PPh 23] " . ($item->description ?: 'Penyelesaian PPh 23'),
                        'amount'           => (float) $pphAmount,
                        'debit_coa_code'   => 'AO-208.8',
                        'debit_coa_name'   => 'Pendapatan Yang Belum Diterima/Fee PPh 23',
                        'credit_coa_code'  => 'AO-108.1',
                        'credit_coa_name'  => 'PPh 23',
                        'entity'           => 'PT',
                        'invoice_id'       => $item->invoice_id,
                    ];
                }
            }
        }

        // Summary by Source
        $summaryBySource = [];
        $sourceLabels = [
            'invoice'       => 'Invoice Dibuat',
            'cash_receipt'  => 'Penerimaan Kas/Bank',
            'pph23_invoice' => 'PPh 23 Invoice',
            'pph23_checked' => 'PPh 23 Bukti Potong',
        ];

        $trxCollection = collect($transactions);
        foreach ($sourceLabels as $sKey => $sLabel) {
            $sTrx = $trxCollection->where('source', $sKey);
            $count = $sTrx->count();
            $total = $sTrx->sum('amount');
            if ($count > 0 || !$coa) {
                $summaryBySource[$sKey] = [
                    'key'          => $sKey,
                    'name'         => $sLabel,
                    'count'        => $count,
                    'total_amount' => $total,
                ];
            }
        }

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return [
            'coa'             => $coa,
            'coaId'           => $coaId,
            'month'           => $month,
            'year'            => $year,
            'monthName'       => $monthNames[$month] ?? "Bulan {$month}",
            'type'            => $type,
            'transactions'    => $transactions,
            'summaryBySource' => $summaryBySource,
            'totalAmount'     => $trxCollection->sum('amount'),
            'totalCount'      => $trxCollection->count(),
        ];
    }

    public function exportNeraca(Request $request)
    {
        $month  = (int) $request->get('month', now()->month);
        $year   = (int) $request->get('year', now()->year);
        $entity = strtolower($request->get('entity', 'all'));

        ini_set('memory_limit', '1024M');
        set_time_limit(600);

        // Delegate to the existing Livewire page class export method for Neraca Lajur
        $page = new \App\Filament\Resources\CashReportResource\Pages\NeracaLajurPiutang();
        $page->month  = $month;
        $page->year   = $year;
        $page->entity = $entity;
        $page->exportToExcel();
    }

    public function saveCutOff(Request $request)
    {
        try {
            $month      = (int) $request->get('month', now()->month);
            $year       = (int) $request->get('year', now()->year);
            $entity     = strtolower($request->get('entity', 'all'));
            $targetMode = $request->get('target_mode');

            $result = \App\Services\NeracaLajurCutOffService::cutOff($year, $month, $entity, $targetMode);

            return redirect()->back()->with('success', $result['message']);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal cut off neraca: ' . $e->getMessage());
        }
    }

    /**
     * Halaman Detail Transaksi Kas Bank (Opsi 2).
     */
    public function detailBank(Request $request)
    {
        $month  = (int) $request->get('month', now()->month);
        $year   = (int) $request->get('year', now()->year);
        $coaId  = $request->get('coa_id');
        $type   = strtolower($request->get('type', 'kredit'));
        $from   = $request->get('from', 'admin');
        $entity = strtolower($request->get('entity', 'all'));

        $data = $this->getBankDetailData($coaId, $month, $year, $type, $entity);
        $data['from']   = $from;
        $data['entity'] = $entity;

        return view('neraca-lajur.detail-bank', $data);
    }

    /**
     * Export Excel Detail Transaksi Kas Bank.
     */
    public function exportDetailBank(Request $request)
    {
        $month  = (int) $request->get('month', now()->month);
        $year   = (int) $request->get('year', now()->year);
        $coaId  = $request->get('coa_id');
        $type   = strtolower($request->get('type', 'kredit'));
        $entity = strtolower($request->get('entity', 'all'));

        $data = $this->getBankDetailData($coaId, $month, $year, $type, $entity);

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
    private function getBankDetailData($coaId, int $month, int $year, string $type, string $entity = 'all'): array
    {
        $isSeparated = ($year > 2026) || ($year == 2026 && $month >= 4);

        if ($isSeparated && $entity === 'pt') {
            $bankCashReferenceIds = [1, 4]; // BCA PT, BCA 436
        } elseif ($isSeparated && $entity === 'kkp') {
            $bankCashReferenceIds = [2]; // BCA 425
        } else {
            if ($year == 2026 && $month < 4) {
                // Sebelum cut-off April 2026: BCA 140 (3) masih ada transaksi, Mandiri (5) diexclude
                $bankCashReferenceIds = [1, 2, 3, 4];
            } elseif ($year < 2026) {
                $bankCashReferenceIds = [1, 2, 3, 4, 5];
            } else {
                // Mulai April 2026 konsolidasi: BCA 140 (3) dan Mandiri (5) sudah non-aktif
                $bankCashReferenceIds = [1, 2, 4];
            }
        }

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
                if (!in_array(1, $bankCashReferenceIds)) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->where('cash_reference_id', 1);
                    if ($type === 'debit') {
                        $query->where('debit_amount', '>', 0);
                    } else {
                        $query->where('credit_amount', '>', 0);
                    }
                }
            } elseif ($coa->code === 'AO-102.2') {
                $isBankDirectAccount = true;
                $directCashReferenceId = 3;
                if (!in_array(3, $bankCashReferenceIds)) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->where('cash_reference_id', 3);
                    if ($type === 'debit') {
                        $query->where('debit_amount', '>', 0);
                    } else {
                        $query->where('credit_amount', '>', 0);
                    }
                }
            } elseif ($coa->code === 'AO-102.3') {
                $isBankDirectAccount = true;
                $directCashReferenceId = 2;
                if (!in_array(2, $bankCashReferenceIds)) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->where('cash_reference_id', 2);
                    if ($type === 'debit') {
                        $query->where('debit_amount', '>', 0);
                    } else {
                        $query->where('credit_amount', '>', 0);
                    }
                }
            } elseif ($coa->code === 'AO-102.4') {
                $isBankDirectAccount = true;
                $directCashReferenceId = 4;
                if (!in_array(4, $bankCashReferenceIds)) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->where('cash_reference_id', 4);
                    if ($type === 'debit') {
                        $query->where('debit_amount', '>', 0);
                    } else {
                        $query->where('credit_amount', '>', 0);
                    }
                }
            } elseif ($coa->code === 'AO-102.5') {
                $isBankDirectAccount = true;
                $directCashReferenceId = 5;
                if (!in_array(5, $bankCashReferenceIds)) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->where('cash_reference_id', 5);
                    if ($type === 'debit') {
                        $query->where('debit_amount', '>', 0);
                    } else {
                        $query->where('credit_amount', '>', 0);
                    }
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
