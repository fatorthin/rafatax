<?php

namespace App\Filament\App\Resources\InvoiceResource\Pages;

use App\Filament\App\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageInvoices extends ManageRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->modalHeading('Export Data Invoice')
                ->modalSubmitActionLabel('Export')
                ->form([
                    \Filament\Forms\Components\Select::make('year')
                        ->label('Tahun Invoice')
                        ->options(function () {
                            return \App\Models\Invoice::selectRaw('YEAR(invoice_date) as year')
                                ->whereNotNull('invoice_date')
                                ->distinct()
                                ->orderBy('year', 'desc')
                                ->pluck('year', 'year')
                                ->toArray();
                        })
                        ->placeholder('Semua Tahun')
                        ->searchable(),
                    \Filament\Forms\Components\Select::make('is_send_invoice')
                        ->label('Status Kirim Invoice')
                        ->options([
                            '1' => 'Sudah',
                            '0' => 'Belum',
                        ])
                        ->placeholder('Semua Status Kirim'),
                    \Filament\Forms\Components\Select::make('invoice_type')
                        ->label('Tipe Invoice')
                        ->options(function () {
                            return \App\Models\Invoice::select('invoice_type')
                                ->whereNotNull('invoice_type')
                                ->distinct()
                                ->pluck('invoice_type', 'invoice_type')
                                ->toArray();
                        })
                        ->placeholder('Semua Tipe Invoice'),
                    \Filament\Forms\Components\Select::make('invoice_status')
                        ->label('Status Bayar')
                        ->options([
                            'paid' => 'Paid',
                            'unpaid' => 'Unpaid',
                        ])
                        ->placeholder('Semua Status Bayar'),
                ])
                ->action(function (array $data) {
                    $query = \App\Models\Invoice::with(['mou.client', 'costListInvoices']);

                    if (!empty($data['year'])) {
                        $query->whereYear('invoice_date', $data['year']);
                    }
                    if (isset($data['is_send_invoice']) && $data['is_send_invoice'] !== '') {
                        $query->where('is_send_invoice', $data['is_send_invoice']);
                    }
                    if (!empty($data['invoice_type'])) {
                        $query->where('invoice_type', $data['invoice_type']);
                    }

                    if (!empty($data['invoice_status'])) {
                        $query->where('invoice_status', $data['invoice_status']);
                    }

                    $invoices = $query->get();

                    return response()->streamDownload(function () use ($invoices) {
                        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                        $sheet = $spreadsheet->getActiveSheet();
                        $sheet->setTitle('Data Invoice');

                        // Header
                        $headers = ['No', 'No. Invoice', 'Tanggal Invoice', 'Klien', 'MoU', 'Status', 'Tipe', 'Total Tagihan', 'Dikirim'];
                        $col = 'A';
                        foreach ($headers as $header) {
                            $sheet->setCellValue($col . '1', $header);
                            $sheet->getStyle($col . '1')->getFont()->setBold(true);
                            $col++;
                        }

                        $row = 2;
                        foreach ($invoices as $index => $invoice) {
                            $totalAmount = $invoice->costListInvoices->sum('amount');
                            $clientName = $invoice->mou && $invoice->mou->client ? $invoice->mou->client->company_name : '-';
                            $mouNumber = $invoice->mou ? $invoice->mou->mou_number : '-';
                            $statusKirim = $invoice->is_send_invoice == '1' ? 'Sudah' : 'Belum';

                            $sheet->setCellValue('A' . $row, $index + 1);
                            $sheet->setCellValue('B' . $row, $invoice->invoice_number);
                            $sheet->setCellValue('C' . $row, $invoice->invoice_date);
                            $sheet->setCellValue('D' . $row, $clientName);
                            $sheet->setCellValue('E' . $row, $mouNumber);
                            $sheet->setCellValue('F' . $row, ucfirst($invoice->invoice_status ?? '-'));
                            $sheet->setCellValue('G' . $row, strtoupper($invoice->invoice_type ?? '-'));
                            $sheet->setCellValue('H' . $row, $totalAmount);
                            $sheet->setCellValue('I' . $row, $statusKirim);

                            $row++;
                        }

                        foreach (range('A', 'I') as $columnID) {
                            $sheet->getColumnDimension($columnID)->setAutoSize(true);
                        }

                        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                        $writer->save('php://output');
                    }, 'Export_Invoice_' . date('Y-m-d_H-i-s') . '.xlsx');
                }),
            Actions\CreateAction::make(),
        ];
    }
}
