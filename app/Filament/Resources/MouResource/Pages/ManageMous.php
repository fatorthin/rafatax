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
