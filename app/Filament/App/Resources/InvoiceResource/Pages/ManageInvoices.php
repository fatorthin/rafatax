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
            Actions\Action::make('export_rekap_piutang_bulanan')
                ->label('Export Rekap Piutang')
                ->icon('heroicon-o-document-chart-bar')
                ->color('warning')
                ->modalHeading('Export Rekap Piutang Bulanan')
                ->modalSubmitActionLabel('Export')
                ->form([
                    \Filament\Forms\Components\Select::make('year')
                        ->label('Tahun')
                        ->options(function () {
                            return \App\Models\Invoice::selectRaw('YEAR(invoice_date) as year')
                                ->whereNotNull('invoice_date')
                                ->distinct()
                                ->orderBy('year', 'desc')
                                ->pluck('year', 'year')
                                ->toArray();
                        })
                        ->default(date('Y'))
                        ->required(),
                    \Filament\Forms\Components\Select::make('month')
                        ->label('Bulan (Opsional)')
                        ->options([
                            '1' => 'Januari', '2' => 'Februari', '3' => 'Maret',
                            '4' => 'April', '5' => 'Mei', '6' => 'Juni',
                            '7' => 'Juli', '8' => 'Agustus', '9' => 'September',
                            '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
                        ])
                        ->placeholder('Semua Bulan'),
                ])
                ->action(function (array $data) {
                    $query = \App\Models\Invoice::with(['costListInvoices'])
                        ->whereNotNull('invoice_date');
                        
                    if (!empty($data['year'])) {
                        $query->whereYear('invoice_date', $data['year']);
                    }
                    if (!empty($data['month'])) {
                        $query->whereMonth('invoice_date', $data['month']);
                    }
                    
                    $invoices = $query->get();
                    
                    $grouped = $invoices->groupBy(function($invoice) {
                        return \Carbon\Carbon::parse($invoice->invoice_date)->format('n');
                    });
                    
                    $monthNames = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                        4 => 'April', 5 => 'Mei', 6 => 'Juni',
                        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                        10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                    ];
                    
                    return response()->streamDownload(function () use ($grouped, $monthNames, $data) {
                        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                        $sheet = $spreadsheet->getActiveSheet();
                        $sheet->setTitle('Rekap Piutang');
                        
                        $yearTitle = $data['year'] ?? 'Semua Tahun';
                        $sheet->setCellValue('A1', 'Rekap Piutang Bulanan - Tahun ' . $yearTitle);
                        $sheet->mergeCells('A1:D1');
                        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                        
                        $headers = ['Bulan', 'Total Tagihan', 'Total Terbayar (Paid)', 'Total Piutang (Unpaid)'];
                        $col = 'A';
                        foreach ($headers as $header) {
                            $sheet->setCellValue($col . '3', $header);
                            $sheet->getStyle($col . '3')->getFont()->setBold(true);
                            $col++;
                        }
                        
                        $row = 4;
                        $grandTotalTagihan = 0;
                        $grandTotalPaid = 0;
                        $grandTotalUnpaid = 0;
                        
                        $monthsToIterate = !empty($data['month']) ? [(int)$data['month']] : range(1, 12);
                        
                        foreach ($monthsToIterate as $m) {
                            $monthInvoices = $grouped->get($m, collect());
                            
                            $totalTagihan = 0;
                            $totalPaid = 0;
                            $totalUnpaid = 0;
                            
                            foreach ($monthInvoices as $inv) {
                                $amt = $inv->costListInvoices->sum('amount');
                                $totalTagihan += $amt;
                                if ($inv->invoice_status === 'paid') {
                                    $totalPaid += $amt;
                                } else {
                                    $totalUnpaid += $amt;
                                }
                            }
                            
                            if ($monthInvoices->isNotEmpty() || !empty($data['month'])) {
                                $sheet->setCellValue('A' . $row, $monthNames[$m]);
                                $sheet->setCellValue('B' . $row, $totalTagihan);
                                $sheet->setCellValue('C' . $row, $totalPaid);
                                $sheet->setCellValue('D' . $row, $totalUnpaid);
                                
                                $grandTotalTagihan += $totalTagihan;
                                $grandTotalPaid += $totalPaid;
                                $grandTotalUnpaid += $totalUnpaid;
                                
                                $row++;
                            }
                        }
                        
                        $sheet->setCellValue('A' . $row, 'TOTAL');
                        $sheet->setCellValue('B' . $row, $grandTotalTagihan);
                        $sheet->setCellValue('C' . $row, $grandTotalPaid);
                        $sheet->setCellValue('D' . $row, $grandTotalUnpaid);
                        $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
                        
                        foreach (['B', 'C', 'D'] as $col) {
                            $sheet->getStyle($col . '4:' . $col . $row)->getNumberFormat()->setFormatCode('#,##0');
                        }
                        foreach (range('A', 'D') as $col) {
                            $sheet->getColumnDimension($col)->setAutoSize(true);
                        }
                        
                        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                        $writer->save('php://output');
                    }, 'Rekap_Piutang_Bulanan_' . date('Y-m-d_H-i-s') . '.xlsx');
                }),
            Actions\CreateAction::make(),
        ];
    }
}
