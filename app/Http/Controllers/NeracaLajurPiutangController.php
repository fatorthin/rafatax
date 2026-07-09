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

        // ──────────────────────────────────────────────────────────────────────
        // SHEET 1: RINGKASAN per COA
        // ──────────────────────────────────────────────────────────────────────
        $sheetSum = $spreadsheet->getActiveSheet();
        $sheetSum->setTitle('Ringkasan JP');

        $sheetSum->setCellValue('A1', 'DETAIL JURNAL PENDAPATAN (KONSEP PIUTANG) - ' . strtoupper($periodeLabel));
        $sheetSum->mergeCells('A1:F1');
        $sheetSum->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheetSum->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheetSum->setCellValue('A3', 'Kode COA');
        $sheetSum->setCellValue('B3', 'Nama COA');
        $sheetSum->setCellValue('C3', 'Sumber');
        $sheetSum->setCellValue('D3', 'Nominal');
        $sheetSum->setCellValue('E3', 'JP Debit');
        $sheetSum->setCellValue('F3', 'JP Kredit');
        $sheetSum->getStyle('A3:F3')->applyFromArray($headerStyle);

        $piutangMap = [
            188 => 119, // AO-103.6  -> AO-401   (Fee Bulanan)
            182 => 120, // AO-103.7  -> AO-401.1 (Fee SPT)
            183 => 121, // AO-103.8  -> AO-401.2 (Fee SP2DK)
            184 => 122, // AO-103.9  -> AO-401.3 (Fee Pembetulan)
            185 => 123, // AO-103.10 -> AO-401.4 (Fee Internal)
            186 => 124, // AO-103.11 -> AO-401.5 (Fee Restitusi)
            187 => 125, // AO-103.12 -> AO-401.6 (Fee Pemeriksaan)
        ];
        $piutangCoaIds     = array_keys($piutangMap);
        $pendapatanCoaIds  = array_values($piutangMap);
        $pendapatanCoaList = DB::table('coa')->whereIn('id', $pendapatanCoaIds)->get()->keyBy('id');
        $piutangCoaList    = DB::table('coa')->whereIn('id', $piutangCoaIds)->get()->keyBy('id');
        $coaBelumDiterima  = DB::table('coa')->where('id', self::COA_PENDAPATAN_BELUM_DITERIMA_ID)->first();

        // ── Bagian 1: MoU (DR AO-103.x / CR AO-208) ──
        $mouRows = DB::table('cost_list_mous as clm')
            ->join('mous as m', 'm.id', '=', 'clm.mou_id')
            ->whereNull('m.deleted_at')
            ->whereNull('clm.deleted_at')
            ->where('m.status', 'approved')
            ->where('m.type', 'kkp')
            ->whereBetween('m.approved_date', [$startOfMonth, $endOfMonth])
            ->whereIn('clm.coa_id', $piutangCoaIds)
            ->groupBy('clm.coa_id')
            ->selectRaw('clm.coa_id as piutang_coa_id, SUM(clm.total_amount) as total')
            ->get();

        $sumRow   = 4;
        $mouTotal = $mouRows->sum('total');

        $sheetSum->setCellValue('A' . $sumRow, '─── BAGIAN 1: MoU Approved (Pengakuan Piutang) ───');
        $sheetSum->mergeCells('A' . $sumRow . ':F' . $sumRow);
        $sheetSum->getStyle('A' . $sumRow . ':F' . $sumRow)->getFont()->setBold(true)->setItalic(true);
        $sumRow++;

        foreach ($mouRows as $row) {
            $piutangCoa = $piutangCoaList->get($row->piutang_coa_id);
            $sheetSum->setCellValue('A' . $sumRow, $piutangCoa ? $piutangCoa->code : $row->piutang_coa_id);
            $sheetSum->setCellValue('B' . $sumRow, $piutangCoa ? $piutangCoa->name : '-');
            $sheetSum->setCellValue('C' . $sumRow, 'MoU Approved');
            $sheetSum->setCellValue('D' . $sumRow, $row->total ?: '');
            $sheetSum->setCellValue('E' . $sumRow, $row->total ?: '');
            $sheetSum->setCellValue('F' . $sumRow, '');
            $sheetSum->getStyle('D' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
            $sumRow++;
        }

        $sheetSum->setCellValue('A' . $sumRow, $coaBelumDiterima ? $coaBelumDiterima->code : 'AO-208');
        $sheetSum->setCellValue('B' . $sumRow, $coaBelumDiterima ? $coaBelumDiterima->name : 'Pendapatan Yang Belum Diterima');
        $sheetSum->setCellValue('C' . $sumRow, 'MoU Approved');
        $sheetSum->setCellValue('D' . $sumRow, $mouTotal ?: '');
        $sheetSum->setCellValue('E' . $sumRow, '');
        $sheetSum->setCellValue('F' . $sumRow, $mouTotal ?: '');
        $sheetSum->getStyle('D' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
        $sumRow++;

        // ── Bagian 2: Penerimaan Kas (CR AO-401.x) ──
        $cashRows = DB::table('cash_reports')
            ->whereNull('deleted_at')
            ->whereIn('coa_id', $piutangCoaIds)
            ->whereIn('cash_reference_id', [1, 2, 3, 4, 5, 6, 7, 9])
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->groupBy('coa_id')
            ->selectRaw('coa_id as piutang_coa_id, SUM(debit_amount) as total')
            ->get();

        $cashTotal = $cashRows->sum('total');

        $sheetSum->setCellValue('A' . $sumRow, '─── BAGIAN 2: Penerimaan Kas (Pengakuan Pendapatan) ───');
        $sheetSum->mergeCells('A' . $sumRow . ':F' . $sumRow);
        $sheetSum->getStyle('A' . $sumRow . ':F' . $sumRow)->getFont()->setBold(true)->setItalic(true);
        $sumRow++;

        foreach ($cashRows as $row) {
            $pendapatanCoaId = $piutangMap[$row->piutang_coa_id] ?? null;
            $pendapatanCoa   = $pendapatanCoaId ? $pendapatanCoaList->get($pendapatanCoaId) : null;
            $sheetSum->setCellValue('A' . $sumRow, $pendapatanCoa ? $pendapatanCoa->code : '-');
            $sheetSum->setCellValue('B' . $sumRow, $pendapatanCoa ? $pendapatanCoa->name : '-');
            $sheetSum->setCellValue('C' . $sumRow, 'Penerimaan Kas');
            $sheetSum->setCellValue('D' . $sumRow, $row->total ?: '');
            $sheetSum->setCellValue('E' . $sumRow, '');
            $sheetSum->setCellValue('F' . $sumRow, $row->total ?: '');
            $sheetSum->getStyle('D' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
            $sumRow++;
        }

        $sheetSum->setCellValue('A' . $sumRow, $coaBelumDiterima ? $coaBelumDiterima->code : 'AO-208');
        $sheetSum->setCellValue('B' . $sumRow, $coaBelumDiterima ? $coaBelumDiterima->name : 'Pendapatan Yang Belum Diterima');
        $sheetSum->setCellValue('C' . $sumRow, 'Penerimaan Kas');
        $sheetSum->setCellValue('D' . $sumRow, $cashTotal ?: '');
        $sheetSum->setCellValue('E' . $sumRow, $cashTotal ?: '');
        $sheetSum->setCellValue('F' . $sumRow, '');
        $sheetSum->getStyle('D' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);
        $sumRow++;

        // Baris Grand Total
        $jpDebitTotal  = $mouTotal + $cashTotal;
        $jpKreditTotal = $mouTotal + $cashTotal;
        $sheetSum->setCellValue('A' . $sumRow, 'TOTAL');
        $sheetSum->mergeCells('A' . $sumRow . ':C' . $sumRow);
        $sheetSum->setCellValue('D' . $sumRow, '');
        $sheetSum->setCellValue('E' . $sumRow, $jpDebitTotal ?: '');
        $sheetSum->setCellValue('F' . $sumRow, $jpKreditTotal ?: '');
        $sheetSum->getStyle('A' . $sumRow . ':F' . $sumRow)->applyFromArray($totalStyle);
        $sheetSum->getStyle('D' . $sumRow . ':F' . $sumRow)->getNumberFormat()->setFormatCode($numberFmt);

        $sheetSum->getStyle('A3:F' . $sumRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        foreach (range('A', 'F') as $col) {
            $sheetSum->getColumnDimension($col)->setAutoSize(true);
        }

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

        // ── SHEET 3: DETAIL TRANSAKSI PIUTANG DI KAS/BANK ────────────────────
        $sheetDetail = $spreadsheet->createSheet();
        $sheetDetail->setTitle('Detail Piutang Kas Bank');

        $sheetDetail->setCellValue('A1', 'DETAIL PIUTANG (AO-103.x) DI KAS/BANK - ' . strtoupper($periodeLabel));
        $sheetDetail->mergeCells('A1:I1');
        $sheetDetail->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheetDetail->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $detail_headers = [
            'A3' => 'Tgl Transaksi',
            'B3' => 'Referensi Kas',
            'C3' => 'Kode COA Piutang',
            'D3' => 'Nama COA Piutang',
            'E3' => 'Kode COA Pendapatan',
            'F3' => 'Nama COA Pendapatan',
            'G3' => 'Debit',
            'H3' => 'Kredit',
            'I3' => 'Keterangan',
        ];
        foreach ($detail_headers as $cell => $label) {
            $sheetDetail->setCellValue($cell, $label);
        }
        $sheetDetail->getStyle('A3:I3')->applyFromArray($headerStyle);

        $detailRows = DB::table('cash_reports as cr')
            ->join('coa as c_piutang', 'c_piutang.id', '=', 'cr.coa_id')
            ->join('cash_references as cref', 'cref.id', '=', 'cr.cash_reference_id')
            ->whereNull('cr.deleted_at')
            ->whereIn('cr.coa_id', $piutangCoaIds)
            ->whereIn('cr.cash_reference_id', [1, 2, 3, 4, 5, 6, 7, 9])
            ->whereBetween('cr.transaction_date', [$startOfMonth, $endOfMonth])
            ->orderBy('cr.transaction_date')
            ->orderBy('cr.id')
            ->selectRaw('
                cr.transaction_date,
                cref.name as ref_name,
                c_piutang.code as piutang_code,
                c_piutang.name as piutang_name,
                cr.coa_id as piutang_coa_id,
                cr.debit_amount,
                cr.credit_amount,
                cr.description
            ')
            ->get();

        $detailRow   = 4;
        $detailGrand = 0;
        foreach ($detailRows as $dr) {
            $pendapatanCoaId = $piutangMap[$dr->piutang_coa_id] ?? null;
            $pendapatanCoa   = $pendapatanCoaId ? $pendapatanCoaList->get($pendapatanCoaId) : null;

            $sheetDetail->setCellValue('A' . $detailRow, $dr->transaction_date);
            $sheetDetail->setCellValue('B' . $detailRow, $dr->ref_name);
            $sheetDetail->setCellValue('C' . $detailRow, $dr->piutang_code);
            $sheetDetail->setCellValue('D' . $detailRow, $dr->piutang_name);
            $sheetDetail->setCellValue('E' . $detailRow, $pendapatanCoa ? $pendapatanCoa->code : '-');
            $sheetDetail->setCellValue('F' . $detailRow, $pendapatanCoa ? $pendapatanCoa->name : '-');
            $sheetDetail->setCellValue('G' . $detailRow, $dr->debit_amount ?: '');
            $sheetDetail->setCellValue('H' . $detailRow, $dr->credit_amount ?: '');
            $sheetDetail->setCellValue('I' . $detailRow, $dr->description);
            $sheetDetail->getStyle('G' . $detailRow . ':H' . $detailRow)->getNumberFormat()->setFormatCode($numberFmt);
            $detailGrand += $dr->debit_amount;
            $detailRow++;
        }
        $sheetDetail->setCellValue('F' . $detailRow, 'TOTAL');
        $sheetDetail->setCellValue('G' . $detailRow, $detailGrand);
        $sheetDetail->getStyle('A' . $detailRow . ':I' . $detailRow)->applyFromArray($totalStyle);
        $sheetDetail->getStyle('G' . $detailRow)->getNumberFormat()->setFormatCode($numberFmt);
        $sheetDetail->getStyle('A3:I' . $detailRow)->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
        foreach (range('A', 'I') as $col) {
            $sheetDetail->getColumnDimension($col)->setAutoSize(true);
        }

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
