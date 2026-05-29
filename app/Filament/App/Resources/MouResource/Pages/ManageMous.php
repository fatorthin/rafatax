<?php

namespace App\Filament\App\Resources\MouResource\Pages;

use App\Filament\App\Resources\MouResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMous extends ManageRecords
{
    protected static string $resource = MouResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah MoU Baru')
                ->icon('heroicon-o-plus')
                ->modalWidth('7xl'),
            Actions\Action::make('export_rekap_payment_piutang')
                ->label('Rekap Payment & Piutang')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('danger')
                ->modalHeading('Export Rekap Payment & Piutang Klien')
                ->modalDescription('Export data rekap payment dan piutang klien per tanggal yang dipilih.')
                ->modalSubmitActionLabel('Export Excel')
                ->form([
                    \Filament\Forms\Components\Select::make('mode_tahun')
                        ->label('Rekap Berdasarkan')
                        ->options([
                            'tahun_pajak'  => 'Tahun Pajak',
                            'tahun_dibuat' => 'Tahun Dibuat',
                        ])
                        ->default('tahun_pajak')
                        ->required()
                        ->live()
                        ->native(false),
                    \Filament\Forms\Components\Select::make('tahun_pajak')
                        ->label('Pilih Tahun Pajak')
                        ->options(function () {
                            return \App\Models\MoU::query()
                                ->whereNotNull('tahun_pajak')
                                ->distinct()
                                ->orderBy('tahun_pajak', 'desc')
                                ->pluck('tahun_pajak', 'tahun_pajak')
                                ->toArray();
                        })
                        ->placeholder('Semua Tahun Pajak')
                        ->searchable()
                        ->visible(fn(\Filament\Forms\Get $get) => $get('mode_tahun') === 'tahun_pajak' || !$get('mode_tahun')),
                    \Filament\Forms\Components\Select::make('tahun_dibuat')
                        ->label('Pilih Tahun Dibuat')
                        ->options(function () {
                            return \App\Models\MoU::query()
                                ->selectRaw('YEAR(created_at) as tahun')
                                ->whereNotNull('created_at')
                                ->distinct()
                                ->orderByRaw('YEAR(created_at) DESC')
                                ->pluck('tahun', 'tahun')
                                ->toArray();
                        })
                        ->placeholder('Semua Tahun Dibuat')
                        ->searchable()
                        ->visible(fn(\Filament\Forms\Get $get) => $get('mode_tahun') === 'tahun_dibuat'),
                    \Filament\Forms\Components\Select::make('type')
                        ->label('Tipe MoU (Opsional)')
                        ->options([
                            'pt' => 'PT',
                            'kkp' => 'KKP',
                        ])
                        ->placeholder('Semua Tipe'),
                    \Filament\Forms\Components\Select::make('category_mou_id')
                        ->label('Kategori/Case MoU (Opsional)')
                        ->options(\App\Models\CategoryMou::pluck('name', 'id'))
                        ->placeholder('Semua Kategori')
                        ->multiple()
                        ->searchable(),
                    \Filament\Forms\Components\Select::make('status')
                        ->label('Status MoU (Opsional)')
                        ->options([
                            'approved' => 'Approved',
                            'unapproved' => 'Unapproved',
                        ])
                        ->placeholder('Semua Status'),
                    \Filament\Forms\Components\Toggle::make('only_with_piutang')
                        ->label('Hanya tampilkan MoU yang masih punya piutang')
                        ->default(false),
                ])
                ->action(function (array $data) {
                    return static::exportRekapPaymentPiutang($data);
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
                    \Filament\Forms\Components\Select::make('tahun_pajak')
                        ->label('Tahun Pajak (Opsional)')
                        ->options(function () {
                            return \App\Models\MoU::query()
                                ->whereNotNull('tahun_pajak')
                                ->distinct()
                                ->orderBy('tahun_pajak', 'desc')
                                ->pluck('tahun_pajak', 'tahun_pajak')
                                ->toArray();
                        })
                        ->placeholder('Semua Tahun Pajak')
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
                    if (!empty($data['tahun_pajak'])) {
                        $query->where('tahun_pajak', $data['tahun_pajak']);
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
        ];
    }

    protected static function exportRekapPaymentPiutang(array $data)
    {
        return \App\Helpers\RekapPaymentExporter::export($data);
    }
}
