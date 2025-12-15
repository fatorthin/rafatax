<?php

namespace App\Filament\Resources\CashReportResource\Pages;

use App\Filament\Resources\CashReportResource;
use Filament\Resources\Pages\Page;

class GeneralLedger extends Page
{
    // InteractsWithForms is inherited from Page
    // HasForms is inherited from Page

    protected static string $resource = CashReportResource::class;

    protected static string $view = 'filament.resources.cash-report-resource.pages.general-ledger';

    protected static ?string $title = 'General Ledger';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->startOfMonth()->format('Y-m-d'),
            'end_date' => now()->endOfMonth()->format('Y-m-d'),
            'cash_reference_id' => null,
        ]);
    }

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Section::make()
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Awal')
                            ->default(now()->startOfMonth())
                            ->required()
                            ->live(),
                        \Filament\Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Akhir')
                            ->default(now()->endOfMonth())
                            ->required()
                            ->live(),
                        \Filament\Forms\Components\Select::make('cash_reference_id')
                            ->label('Kas / Bank')
                            ->options(\App\Models\CashReference::all()->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Semua Kas / Bank')
                            ->live(),
                    ])
                    ->columns(3)
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    $groupedTransactions = $this->getReports();
                    $coas = \App\Models\Coa::whereIn('id', $groupedTransactions->keys())->get()->keyBy('id');

                    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();

                    $row = 1;

                    // Title
                    $sheet->setCellValue('A' . $row, 'General Ledger');
                    $sheet->mergeCells("A{$row}:F{$row}");
                    $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
                    $row++;

                    // Period
                    $startDate = $this->data['start_date'] ?? '-';
                    $endDate = $this->data['end_date'] ?? '-';
                    $sheet->setCellValue('A' . $row, "Periode: $startDate - $endDate");
                    $sheet->mergeCells("A{$row}:F{$row}");
                    $row += 2;

                    foreach ($groupedTransactions as $coaId => $transactions) {
                        $coa = $coas[$coaId] ?? null;
                        if (!$coa) continue;

                        // CoA Header
                        $sheet->setCellValue('A' . $row, "{$coa->code} - {$coa->name} ({$coa->type})");
                        $sheet->mergeCells("A{$row}:F{$row}");
                        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                        $sheet->getStyle('A' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EEEEEE');
                        $row++;

                        // Headers
                        $sheet->setCellValue('A' . $row, 'Tanggal');
                        $sheet->setCellValue('B' . $row, 'Deskripsi');
                        $sheet->setCellValue('C' . $row, 'Ref');
                        $sheet->setCellValue('D' . $row, 'Debit');
                        $sheet->setCellValue('E' . $row, 'Credit');
                        $sheet->setCellValue('F' . $row, 'Balance');
                        $sheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true);
                        $row++;

                        $balance = 0;
                        $totalDebit = 0;
                        $totalCredit = 0;

                        foreach ($transactions as $transaction) {
                            $debit = $transaction->debit_amount ?? 0;
                            $credit = $transaction->credit_amount ?? 0;
                            $balance += ($debit - $credit);
                            $totalDebit += $debit;
                            $totalCredit += $credit;

                            $sheet->setCellValue('A' . $row, \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y'));
                            $sheet->setCellValue('B' . $row, $transaction->description);
                            $sheet->setCellValue('C' . $row, $transaction->cashReference->name ?? '-');
                            $sheet->setCellValue('D' . $row, $debit);
                            $sheet->setCellValue('E' . $row, $credit);
                            $sheet->setCellValue('F' . $row, $balance);
                            $row++;
                        }

                        // Footer Total
                        $sheet->setCellValue('A' . $row, 'Total');
                        $sheet->mergeCells("A{$row}:C{$row}");
                        $sheet->setCellValue('D' . $row, $totalDebit);
                        $sheet->setCellValue('E' . $row, $totalCredit);
                        $sheet->setCellValue('F' . $row, $balance);
                        $sheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true);

                        $row += 2; // Space between CoAs
                    }

                    // Auto Size Columns
                    foreach (range('A', 'F') as $col) {
                        $sheet->getColumnDimension($col)->setAutoSize(true);
                    }

                    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

                    return response()->streamDownload(function () use ($writer) {
                        $writer->save('php://output');
                    }, 'GeneralLedger_' . now()->format('Ymd_His') . '.xlsx');
                }),
        ];
    }

    public function getReports()
    {
        $data = $this->form->getState();
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];
        $cashReferenceId = $data['cash_reference_id'] ?? null;

        $query = \App\Models\CashReport::query()
            ->with(['coa', 'cashReference'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereNotIn('coa_id', [78, 118]);

        if ($cashReferenceId) {
            $query->where('cash_reference_id', $cashReferenceId);
        }

        // Fetch data and group by CoA ID
        $transactions = $query->orderBy('transaction_date')->get();

        return $transactions->groupBy('coa_id');
    }

    protected function getViewData(): array
    {
        return [
            'groupedTransactions' => $this->getReports(),
            'coas' => \App\Models\Coa::whereIn('id', $this->getReports()->keys())->get()->keyBy('id'),
        ];
    }
}
