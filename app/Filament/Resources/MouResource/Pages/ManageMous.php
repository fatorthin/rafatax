<?php

namespace App\Filament\Resources\MouResource\Pages;

use App\Filament\Resources\MouResource;
use App\Filament\Resources\MouResource\Widgets\MouListStatsOverview;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMous extends ManageRecords
{
    protected static string $resource = MouResource::class;

    protected static ?string $title = 'Kelola Daftar MoU';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_mou_bulanan_spt')
                ->label('Export MoU (Bulanan & SPT)')
                ->icon('heroicon-o-table-cells')
                ->color('warning')
                ->modalHeading('Export MoU Bulanan & SPT')
                ->modalDescription('Export data MoU untuk kategori Bulanan Perorangan, Bulanan Perusahaan, SPT Perorangan, dan SPT Perusahaan.')
                ->modalSubmitActionLabel('Export Excel')
                ->form([
                    \Filament\Forms\Components\Select::make('tahun_pajak')
                        ->label('Tahun Pajak (Opsional)')
                        ->options(function () {
                            return \App\Models\MoU::whereIn('category_mou_id', [1, 2, 3, 4])
                                ->whereNotNull('tahun_pajak')
                                ->distinct()
                                ->orderBy('tahun_pajak', 'desc')
                                ->pluck('tahun_pajak', 'tahun_pajak')
                                ->toArray();
                        })
                        ->placeholder('Semua Tahun Pajak')
                        ->searchable(),
                    \Filament\Forms\Components\Select::make('status')
                        ->label('Berdasarkan Status (Opsional)')
                        ->options([
                            'approved' => 'Approved',
                            'unapproved' => 'Unapproved',
                        ])
                        ->placeholder('Semua Status'),
                ])
                ->action(function (array $data) {
                    $query = \App\Models\MoU::with(['client', 'categoryMou',
                        'feeBulanan' => fn($q) => $q->where('coa_id', 119),
                        'feeSpt'     => fn($q) => $q->where('coa_id', 120),
                    ])
                        ->whereIn('category_mou_id', [1, 2, 3, 4]);

                    if (!empty($data['tahun_pajak'])) {
                        $query->where('tahun_pajak', $data['tahun_pajak']);
                    }
                    if (!empty($data['status'])) {
                        $query->where('status', $data['status']);
                    }

                    $mous = $query->orderBy('created_at', 'desc')->get();

                    return response()->streamDownload(function () use ($mous) {
                        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                        $sheet = $spreadsheet->getActiveSheet();
                        $sheet->setTitle('MoU Bulanan & SPT');

                        // Header
                        $headers = [
                            'No',
                            'No. MoU',
                            'Nama Perusahaan Klien',
                            'Kategori MoU',
                            'Tipe',
                            'Tahun Pajak',
                            'Tanggal Dibuat MoU',
                            'Status MoU',
                            'Nominal Fee Bulanan',
                            'Nominal Fee SPT',
                        ];

                        $colLetters = range('A', 'J');
                        foreach ($headers as $i => $header) {
                            $cell = $colLetters[$i] . '1';
                            $sheet->setCellValue($cell, $header);
                            $sheet->getStyle($cell)->getFont()->setBold(true);
                            $sheet->getStyle($cell)->getFill()
                                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                ->getStartColor()->setRGB('4472C4');
                            $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
                        }

                        $row = 2;
                        foreach ($mous as $index => $mou) {
                            // Fee Bulanan = cost_list_mous with coa_id 119
                            // Fee SPT    = cost_list_mous with coa_id 120
                            $feeBulanan = $mou->feeBulanan->sum('total_amount');
                            $feeSpt     = $mou->feeSpt->sum('total_amount');

                            $sheet->setCellValue('A' . $row, $index + 1);
                            $sheet->setCellValue('B' . $row, $mou->mou_number ?? '-');
                            $sheet->setCellValue('C' . $row, $mou->client ? $mou->client->company_name : '-');
                            $sheet->setCellValue('D' . $row, $mou->categoryMou ? $mou->categoryMou->name : '-');
                            $sheet->setCellValue('E' . $row, strtoupper($mou->type ?? '-'));
                            $sheet->setCellValue('F' . $row, $mou->tahun_pajak ?? '-');
                            $sheet->setCellValue('G' . $row, $mou->created_at ? $mou->created_at->format('d/m/Y') : '-');
                            $sheet->setCellValue('H' . $row, ucfirst($mou->status ?? '-'));
                            $sheet->setCellValue('I' . $row, $feeBulanan);
                            $sheet->setCellValueExplicit('J' . $row, $feeSpt, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

                            // Format currency columns I and J
                            $currencyFormat = '#,##0';
                            $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode($currencyFormat);
                            $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode($currencyFormat);

                            // Alternate row shading
                            if ($row % 2 === 0) {
                                $sheet->getStyle('A' . $row . ':J' . $row)->getFill()
                                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                    ->getStartColor()->setRGB('EBF3FB');
                            }

                            $row++;
                        }

                        foreach ($colLetters as $columnID) {
                            $sheet->getColumnDimension($columnID)->setAutoSize(true);
                        }

                        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                        $writer->save('php://output');
                    }, 'Export_MoU_Bulanan_SPT_' . date('Y-m-d_H-i-s') . '.xlsx');
                }),
            Actions\Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->modalHeading('Export Data MoU')
                ->modalSubmitActionLabel('Export')
                ->form([
                    \Filament\Forms\Components\Select::make('category_mou_id')
                        ->label('Berdasarkan Case (Kategori)')
                        ->options(\App\Models\CategoryMou::pluck('name', 'id'))
                        ->placeholder('Semua Case')
                        ->searchable(),
                    \Filament\Forms\Components\Select::make('year')
                        ->label('Tahun Dibuat (Opsional)')
                        ->options(function () {
                            return \App\Models\MoU::selectRaw('YEAR(created_at) as year')
                                ->whereNotNull('created_at')
                                ->distinct()
                                ->orderBy('year', 'desc')
                                ->pluck('year', 'year')
                                ->toArray();
                        })
                        ->placeholder('Semua Tahun')
                        ->searchable(),
                    \Filament\Forms\Components\Select::make('status')
                        ->label('Berdasarkan Status')
                        ->options([
                            'approved' => 'Approved',
                            'unapproved' => 'Unapproved',
                            'pending' => 'Pending',
                            'batal' => 'Batal',
                        ])
                        ->placeholder('Semua Status'),
                ])
                ->action(function (array $data) {
                    $query = \App\Models\MoU::with(['client', 'categoryMou']);

                    if (!empty($data['category_mou_id'])) {
                        $query->where('category_mou_id', $data['category_mou_id']);
                    }
                    if (!empty($data['year'])) {
                        $query->whereYear('created_at', $data['year']);
                    }
                    if (!empty($data['status'])) {
                        $query->where('status', $data['status']);
                    }

                    $mous = $query->get();

                    return response()->streamDownload(function () use ($mous) {
                        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                        $sheet = $spreadsheet->getActiveSheet();
                        $sheet->setTitle('Data MoU');

                        // Header
                        $headers = ['No', 'No. MoU', 'Nama Klien', 'Case/Kategori', 'Tipe', 'Tahun Pajak', 'Tgl Dibuat', 'Status', 'Total Biaya'];
                        $col = 'A';
                        foreach ($headers as $header) {
                            $sheet->setCellValue($col . '1', $header);
                            $sheet->getStyle($col . '1')->getFont()->setBold(true);
                            $col++;
                        }

                        $row = 2;
                        foreach ($mous as $index => $mou) {
                            $totalFee = \App\Models\CostListMou::where('mou_id', $mou->id)->sum('amount');

                            $sheet->setCellValue('A' . $row, $index + 1);
                            $sheet->setCellValue('B' . $row, $mou->mou_number);
                            $sheet->setCellValue('C' . $row, $mou->client ? $mou->client->company_name : '-');
                            $sheet->setCellValue('D' . $row, $mou->categoryMou ? $mou->categoryMou->name : '-');
                            $sheet->setCellValue('E' . $row, strtoupper($mou->type ?? '-'));
                            $sheet->setCellValue('F' . $row, $mou->tahun_pajak);
                            $sheet->setCellValue('G' . $row, $mou->created_at ? $mou->created_at->format('Y-m-d') : '-');
                            $sheet->setCellValue('H' . $row, ucfirst($mou->status ?? '-'));
                            $sheet->setCellValue('I' . $row, $totalFee);

                            $row++;
                        }

                        foreach (range('A', 'I') as $columnID) {
                            $sheet->getColumnDimension($columnID)->setAutoSize(true);
                        }

                        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                        $writer->save('php://output');
                    }, 'Export_MoU_' . date('Y-m-d_H-i-s') . '.xlsx');
                }),
            Actions\CreateAction::make()
                ->label('Add New MoU')
                ->icon('heroicon-o-plus')
                ->modalHeading('Add New MoU')
                ->modalWidth('7xl'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MouListStatsOverview::class,
        ];
    }
}
