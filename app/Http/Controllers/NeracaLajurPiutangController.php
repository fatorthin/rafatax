<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class NeracaLajurPiutangController extends Controller
{
    // COA ID untuk Piutang Usaha (AO-103)
    private const COA_PIUTANG_USAHA_ID = 179;

    // COA ID untuk Pendapatan Yang Belum Diterima (AO-208)
    private const COA_PENDAPATAN_BELUM_DITERIMA_ID = 175;

    private array $monthNames = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    // ──────────────────────────────────────────────────────────────────────────
    // HTTP Endpoints
    // ──────────────────────────────────────────────────────────────────────────

    public function exportDetailJP(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year  = (int) $request->get('year', now()->year);

        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        return $this->generateDetailJP($month, $year);
    }

    public function exportNeraca(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year  = (int) $request->get('year', now()->year);

        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        // Delegate to the existing Livewire page class export method
        $page = new \App\Filament\Resources\CashReportResource\Pages\NeracaLajurPiutang();
        $page->month = $month;
        $page->year  = $year;
        $page->exportToExcel();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Generate Detail JP Excel
    // ──────────────────────────────────────────────────────────────────────────

    private function generateDetailJP(int $month, int $year)
    {
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth   = Carbon::create($year, $month, 1)->endOfMonth();
        $periodeLabel = $this->monthNames[$month] . ' ' . $year;

        $headerStyle = [
            'font'      => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BDD7EE']],
        ];
        $totalStyle = [
            'font'    => ['bold' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FCE4D6']],
        ];
        $numberFmt = '#,##0';

        $spreadsheet = new Spreadsheet();

        // ── SHEET 1: RINGKASAN per COA ──────────────────────────────────────
        $sheetSum = $spreadsheet->getActiveSheet();
        $sheetSum->setTitle('Ringkasan JP');
        $sheetSum->setCellValue('A1', 'DETAIL JURNAL PENDAPATAN (KONSEP PIUTANG) - ' . strtoupper($periodeLabel));
        $sheetSum->mergeCells('A1:F1');
        $sheetSum->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheetSum->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (['A3' => 'Kode COA', 'B3' => 'Nama COA', 'C3' => 'MoU (Debit Piutang / Kredit AO-208)',
                  'D3' => 'Invoice (Kredit Piutang / Debit AO-208)', 'E3' => 'Net Debit JP',
                  'F3' => 'Net Kredit JP'] as $cell => $label) {
            $sheetSum->setCellValue($cell, $label);
        }
        $sheetSum->getStyle('A3:F3')->applyFromArray($headerStyle);

        // Data MoU per COA (grouped)
        $mouRows = DB::table('cost_list_mous as clm')
            ->join('mous as m', 'm.id', '=', 'clm.mou_id')
            ->join('coa', 'coa.id', '=', 'clm.coa_id')
            ->whereNull('m.deleted_at')->whereNull('clm.deleted_at')
            ->where('m.status', 'approved')->where('m.type', 'kkp')
            ->whereBetween('m.approved_date', [$startOfMonth, $endOfMonth])
            ->groupBy('clm.coa_id', 'coa.code', 'coa.name')
            ->selectRaw('clm.coa_id, coa.code, coa.name, SUM(clm.total_amount) as total_mou')
            ->get()->keyBy('coa_id');

        // Data Invoice per COA (grouped) — hanya yang PAID, berdasarkan tgl_transfer, exclude Memos
        $invRows = DB::table('cost_list_invoices as cli')
            ->join('invoices as i', 'i.id', '=', 'cli.invoice_id')
            ->join('coa', 'coa.id', '=', 'cli.coa_id')
            ->whereNull('i.deleted_at')->whereNull('cli.deleted_at')
            ->where('i.invoice_type', 'kkp')
            ->where('i.invoice_status', 'paid')
            ->whereNotNull('i.tgl_transfer')
            ->whereNotNull('i.mou_id')
            ->whereBetween('i.tgl_transfer', [$startOfMonth, $endOfMonth])
            ->groupBy('cli.coa_id', 'coa.code', 'coa.name')
            ->selectRaw('cli.coa_id, coa.code, coa.name, SUM(cli.amount) as total_inv')
            ->get()->keyBy('coa_id');

        $coaBelumDiterima = DB::table('coa')->where('id', self::COA_PENDAPATAN_BELUM_DITERIMA_ID)->first();
        $mouGrandTotal    = $mouRows->sum('total_mou');
        $invGrandTotal    = $invRows->sum('total_inv');

        $allCoaIds = collect(array_keys($mouRows->toArray()))
            ->merge(array_keys($invRows->toArray()))
            ->unique()
            ->reject(fn ($id) => $id == self::COA_PENDAPATAN_BELUM_DITERIMA_ID);
        $coaNames = DB::table('coa')->whereIn('id', $allCoaIds)->get()->keyBy('id');

        $sumRow = 4;
        // Baris AO-208 Pendapatan Yang Belum Diterima
        $sheetSum->setCellValue('A' . $sumRow, $coaBelumDiterima?->code ?? 'AO-208');
        $sheetSum->setCellValue('B' . $sumRow, $coaBelumDiterima?->name ?? 'Pendapatan Yang Belum Diterima');
        $sheetSum->setCellValue('C' . $sumRow, '');
        $sheetSum->setCellValue('D' . $sumRow, '');
        $sheetSum->setCellValue('E' . $sumRow, $invGrandTotal ?: '');
        $sheetSum->setCellValue('F' . $sumRow, $mouGrandTotal ?: '');
        $sheetSum->getStyle('C' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
        $sumRow++;

        $sumMou = $sumInv = 0;
        foreach ($allCoaIds as $coaId) {
            $coa     = $coaNames->get($coaId);
            $mouVal  = $mouRows->has($coaId) ? (float)$mouRows->get($coaId)->total_mou : 0;
            $invVal  = $invRows->has($coaId) ? (float)$invRows->get($coaId)->total_inv : 0;

            $sumMou  += $mouVal;
            $sumInv  += $invVal;

            $sheetSum->setCellValue('A' . $sumRow, $coa?->code ?? $coaId);
            $sheetSum->setCellValue('B' . $sumRow, $coa?->name ?? '-');
            $sheetSum->setCellValue('C' . $sumRow, $mouVal ?: '');
            $sheetSum->setCellValue('D' . $sumRow, $invVal ?: '');
            $sheetSum->setCellValue('E' . $sumRow, $mouVal ?: '');
            $sheetSum->setCellValue('F' . $sumRow, $invVal ?: '');
            $sheetSum->getStyle('C' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
            $sumRow++;
        }

        // Total row
        $sheetSum->setCellValue('A' . $sumRow, 'TOTAL');
        $sheetSum->setCellValue('C' . $sumRow, $sumMou ?: '');
        $sheetSum->setCellValue('D' . $sumRow, $sumInv ?: '');
        $sheetSum->setCellValue('E' . $sumRow, $sumMou + $invGrandTotal);
        $sheetSum->setCellValue('F' . $sumRow, $sumInv + $mouGrandTotal);
        $sheetSum->getStyle('A' . $sumRow . ':F' . $sumRow)->applyFromArray($totalStyle);
        $sheetSum->getStyle('C' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
        $sheetSum->getStyle('A3:F' . $sumRow)->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
        foreach (range('A', 'F') as $col) { $sheetSum->getColumnDimension($col)->setAutoSize(true); }

        // ── SHEET 2: DETAIL MoU ─────────────────────────────────────────────
        $sheetMou = $spreadsheet->createSheet();
        $sheetMou->setTitle('MoU (Piutang)');
        $sheetMou->setCellValue('A1', 'DAFTAR MoU KKP APPROVED - ' . strtoupper($periodeLabel));
        $sheetMou->mergeCells('A1:H1');
        $sheetMou->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheetMou->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (['A3' => 'No. MoU', 'B3' => 'Approved Date', 'C3' => 'Perusahaan Klien',
                  'D3' => 'Status', 'E3' => 'Kode COA', 'F3' => 'Nama COA',
                  'G3' => 'Total Amount', 'H3' => 'Keterangan'] as $cell => $label) {
            $sheetMou->setCellValue($cell, $label);
        }
        $sheetMou->getStyle('A3:H3')->applyFromArray($headerStyle);

        $mouDetail = DB::table('cost_list_mous as clm')
            ->join('mous as m', 'm.id', '=', 'clm.mou_id')
            ->join('coa', 'coa.id', '=', 'clm.coa_id')
            ->leftJoin('clients', 'clients.id', '=', 'm.client_id')
            ->whereNull('m.deleted_at')->whereNull('clm.deleted_at')
            ->where('m.status', 'approved')->where('m.type', 'kkp')
            ->whereBetween('m.approved_date', [$startOfMonth, $endOfMonth])
            ->orderBy('m.approved_date')->orderBy('m.mou_number')
            ->select([
                'm.mou_number', 'm.approved_date', 'm.status',
                DB::raw('COALESCE(clients.company_name, "-") as client_name'),
                'coa.code as coa_code', 'coa.name as coa_name',
                'clm.total_amount', 'clm.description',
            ])
            ->get();

        $mouRow = 4; $mouGrand = 0;
        foreach ($mouDetail as $m) {
            $sheetMou->setCellValue('A' . $mouRow, $m->mou_number);
            $sheetMou->setCellValue('B' . $mouRow, $m->approved_date);
            $sheetMou->setCellValue('C' . $mouRow, $m->client_name);
            $sheetMou->setCellValue('D' . $mouRow, $m->status);
            $sheetMou->setCellValue('E' . $mouRow, $m->coa_code);
            $sheetMou->setCellValue('F' . $mouRow, $m->coa_name);
            $sheetMou->setCellValue('G' . $mouRow, $m->total_amount);
            $sheetMou->setCellValue('H' . $mouRow, $m->description);
            $sheetMou->getStyle('G' . $mouRow)->getNumberFormat()->setFormatCode($numberFmt);
            $mouGrand += $m->total_amount;
            $mouRow++;
        }
        $sheetMou->setCellValue('F' . $mouRow, 'TOTAL');
        $sheetMou->setCellValue('G' . $mouRow, $mouGrand);
        $sheetMou->getStyle('A' . $mouRow . ':H' . $mouRow)->applyFromArray($totalStyle);
        $sheetMou->getStyle('G' . $mouRow)->getNumberFormat()->setFormatCode($numberFmt);
        $sheetMou->getStyle('A3:H' . $mouRow)->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
        foreach (range('A', 'H') as $col) { $sheetMou->getColumnDimension($col)->setAutoSize(true); }

        // ── SHEET 3: DETAIL INVOICE ─────────────────────────────────────────
        $sheetInv = $spreadsheet->createSheet();
        $sheetInv->setTitle('Invoice (Realisasi)');
        $sheetInv->setCellValue('A1', 'DAFTAR INVOICE KKP - ' . strtoupper($periodeLabel));
        $sheetInv->mergeCells('A1:J1');
        $sheetInv->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheetInv->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (['A3' => 'No. Invoice', 'B3' => 'Tgl Transfer', 'C3' => 'Tgl Invoice',
                  'D3' => 'Status', 'E3' => 'No. MoU', 'F3' => 'Perusahaan Klien',
                  'G3' => 'Kode COA', 'H3' => 'Nama COA',
                  'I3' => 'Amount', 'J3' => 'Keterangan'] as $cell => $label) {
            $sheetInv->setCellValue($cell, $label);
        }
        $sheetInv->getStyle('A3:J3')->applyFromArray($headerStyle);

        $invDetail = DB::table('cost_list_invoices as cli')
            ->join('invoices as i', 'i.id', '=', 'cli.invoice_id')
            ->join('coa', 'coa.id', '=', 'cli.coa_id')
            ->leftJoin('mous as m', 'm.id', '=', 'i.mou_id')
            ->leftJoin('clients', 'clients.id', '=', 'i.client_id')
            ->whereNull('i.deleted_at')->whereNull('cli.deleted_at')
            ->where('i.invoice_type', 'kkp')
            ->where('i.invoice_status', 'paid')
            ->whereNotNull('i.tgl_transfer')
            ->whereNotNull('i.mou_id')
            ->whereBetween('i.tgl_transfer', [$startOfMonth, $endOfMonth])
            ->orderBy('i.tgl_transfer')->orderBy('i.invoice_number')
            ->select([
                'i.invoice_number', 'i.tgl_transfer', 'i.invoice_date', 'i.invoice_status',
                DB::raw('COALESCE(m.mou_number, "-") as referensi'),
                DB::raw('COALESCE(clients.company_name, "-") as client_name'),
                'coa.code as coa_code', 'coa.name as coa_name',
                'cli.amount', 'cli.description',
            ])
            ->get();

        $invRow = 4; $invGrand = 0;
        foreach ($invDetail as $inv) {
            $sheetInv->setCellValue('A' . $invRow, $inv->invoice_number);
            $sheetInv->setCellValue('B' . $invRow, $inv->tgl_transfer);
            $sheetInv->setCellValue('C' . $invRow, $inv->invoice_date);
            $sheetInv->setCellValue('D' . $invRow, $inv->invoice_status);
            $sheetInv->setCellValue('E' . $invRow, $inv->referensi);
            $sheetInv->setCellValue('F' . $invRow, $inv->client_name);
            $sheetInv->setCellValue('G' . $invRow, $inv->coa_code);
            $sheetInv->setCellValue('H' . $invRow, $inv->coa_name);
            $sheetInv->setCellValue('I' . $invRow, $inv->amount);
            $sheetInv->setCellValue('J' . $invRow, $inv->description);
            $sheetInv->getStyle('I' . $invRow)->getNumberFormat()->setFormatCode($numberFmt);
            $invGrand += $inv->amount;
            $invRow++;
        }
        $sheetInv->setCellValue('H' . $invRow, 'TOTAL');
        $sheetInv->setCellValue('I' . $invRow, $invGrand);
        $sheetInv->getStyle('A' . $invRow . ':J' . $invRow)->applyFromArray($totalStyle);
        $sheetInv->getStyle('I' . $invRow)->getNumberFormat()->setFormatCode($numberFmt);
        $sheetInv->getStyle('A3:J' . $invRow)->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
        foreach (range('A', 'J') as $col) { $sheetInv->getColumnDimension($col)->setAutoSize(true); }

        // Output
        $spreadsheet->setActiveSheetIndex(0);
        $filename = 'detail-jp-piutang-' . strtolower(Carbon::create($year, $month, 1)->format('F-Y')) . '.xlsx';

        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
