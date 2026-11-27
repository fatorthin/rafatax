<?php

namespace App\Helpers;

use App\Models\DaftarAktivaTetap;
use App\Models\DepresiasiAktivaTetap;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DaftarAktivaTetapExporter
{
    /**
     * Build the shared Filament header action for exporting Daftar Aktiva Tetap to Excel.
     */
    public static function makeHeaderAction(): Action
    {
        return Action::make('export_excel')
            ->label('Export Excel')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->modalHeading('Export Daftar Aktiva Tetap')
            ->modalDescription('Unduh data daftar aktiva tetap ke format file Excel (.xlsx).')
            ->modalSubmitActionLabel('Unduh Excel')
            ->form([
                Select::make('kepemilikan')
                    ->label('Kepemilikan')
                    ->options([
                        'PT' => 'PT',
                        'KKP' => 'KKP',
                    ])
                    ->placeholder('Semua Kepemilikan'),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'aktif' => 'Aktif',
                        'nonaktif' => 'Non Aktif',
                    ])
                    ->placeholder('Semua Status'),
            ])
            ->action(function (array $data, mixed $livewire = null) {
                $query = ($livewire instanceof HasTable)
                    ? $livewire->getTableQueryForExport()
                    : DaftarAktivaTetap::query();

                $subtitleParts = [];

                if (! empty($data['kepemilikan'])) {
                    $query->where('kepemilikan', $data['kepemilikan']);
                    $subtitleParts[] = 'Kepemilikan: ' . $data['kepemilikan'];
                }

                if (! empty($data['status'])) {
                    $query->where('status', $data['status']);
                    $subtitleParts[] = 'Status: ' . ($data['status'] === 'aktif' ? 'Aktif' : 'Non Aktif');
                }

                $records = $query->get();

                if ($records->isEmpty()) {
                    Notification::make()
                        ->title('Data Kosong')
                        ->warning()
                        ->body('Tidak ada data aktiva tetap untuk diekspor.')
                        ->send();

                    return null;
                }

                $subtitle = ! empty($subtitleParts) ? implode(' | ', $subtitleParts) : null;

                return static::export($records, $subtitle);
            });
    }

    /**
     * Build the shared Filament bulk action for exporting selected Daftar Aktiva Tetap records.
     */
    public static function makeBulkAction(): BulkAction
    {
        return BulkAction::make('export_selected')
            ->label('Export Excel (Terpilih)')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->action(function (Collection $records) {
                if ($records->isEmpty()) {
                    Notification::make()
                        ->title('Data Kosong')
                        ->warning()
                        ->body('Tidak ada data aktiva tetap yang dipilih untuk diekspor.')
                        ->send();

                    return null;
                }

                return static::export($records, 'Data Terpilih (' . $records->count() . ' aktiva)');
            });
    }

    /**
     * Export a collection of DaftarAktivaTetap records to an Excel (.xlsx) download response.
     */
    public static function export(Collection $records, ?string $subtitle = null): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daftar Aktiva Tetap');

        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);

        // 1. Title & Subtitle
        $sheet->setCellValue('A1', 'DAFTAR AKTIVA TETAP');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('0F172A'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $downloadInfo = 'Diunduh pada: ' . now()->format('d/m/Y H:i');
        if (! empty($subtitle)) {
            $downloadInfo = $subtitle . ' | ' . $downloadInfo;
        }
        $sheet->setCellValue('A2', $downloadInfo);
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->setColor(new Color('64748B'));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // 2. Table Headers (Row 4)
        $headerRow = 4;
        $headers = [
            'A' => 'No',
            'B' => 'Nama Aktiva',
            'C' => 'Kepemilikan',
            'D' => 'Tahun Perolehan',
            'E' => 'Harga Perolehan',
            'F' => 'Tarif Penyusutan (%)',
            'G' => 'Depresiasi Terakhir',
            'H' => 'Total Penyusutan',
            'I' => 'Nilai Buku',
            'J' => 'Status',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $headerRow, $label);
        }

        $sheet->getStyle("A{$headerRow}:J{$headerRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '334155'],
                ],
            ],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(28);

        // Preload depreciation aggregates to avoid N+1 queries while preserving SoftDeletes scope
        $aktivaIds = $records->pluck('id')->filter()->values();
        $totalPenyusutanMap = collect();
        $latestDepresiasiMap = collect();

        if ($aktivaIds->isNotEmpty()) {
            $totalPenyusutanMap = DepresiasiAktivaTetap::query()
                ->whereIn('daftar_aktiva_tetap_id', $aktivaIds)
                ->selectRaw('daftar_aktiva_tetap_id, SUM(jumlah_penyusutan) as total_penyusutan')
                ->groupBy('daftar_aktiva_tetap_id')
                ->pluck('total_penyusutan', 'daftar_aktiva_tetap_id');

            $latestDepresiasiMap = DepresiasiAktivaTetap::query()
                ->whereIn('daftar_aktiva_tetap_id', $aktivaIds)
                ->orderBy('tanggal_penyusutan', 'desc')
                ->orderBy('id', 'desc')
                ->get(['daftar_aktiva_tetap_id', 'jumlah_penyusutan'])
                ->unique('daftar_aktiva_tetap_id')
                ->pluck('jumlah_penyusutan', 'daftar_aktiva_tetap_id');
        }

        // 3. Data Rows
        $startRow = 5;
        $currentRow = $startRow;
        $no = 1;
        $currencyFormat = '#,##0;(#,##0);"-"';

        foreach ($records as $record) {
            $hargaPerolehan = (float) ($record->harga_perolehan ?? 0);
            $depresiasiTerakhir = (float) ($latestDepresiasiMap[$record->id] ?? 0);
            $totalPenyusutan = (float) ($totalPenyusutanMap[$record->id] ?? 0);
            $nilaiBuku = $hargaPerolehan - $totalPenyusutan;
            $tahunPerolehan = $record->tahun_perolehan
                ? Carbon::parse($record->tahun_perolehan)->format('M-Y')
                : '-';
            $tarif = number_format((float) ($record->tarif_penyusutan ?? 0), 0, ',', '.') . '%';
            $statusLabel = $record->status === 'aktif' ? 'Aktif' : 'Non Aktif';

            $sheet->setCellValue("A{$currentRow}", $no);
            $sheet->setCellValue("B{$currentRow}", $record->deskripsi ?? '-');
            $sheet->setCellValue("C{$currentRow}", $record->kepemilikan ?: '-');
            $sheet->setCellValue("D{$currentRow}", $tahunPerolehan);
            $sheet->setCellValue("E{$currentRow}", $hargaPerolehan);
            $sheet->setCellValue("F{$currentRow}", $tarif);
            $sheet->setCellValue("G{$currentRow}", $depresiasiTerakhir);
            $sheet->setCellValue("H{$currentRow}", $totalPenyusutan);
            $sheet->setCellValue("I{$currentRow}", $nilaiBuku);
            $sheet->setCellValue("J{$currentRow}", $statusLabel);

            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("C{$currentRow}:D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("F{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$currentRow}:I{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            foreach (['E', 'G', 'H', 'I'] as $numCol) {
                $sheet->getStyle("{$numCol}{$currentRow}")->getNumberFormat()->setFormatCode($currencyFormat);
            }

            $sheet->getStyle("J{$currentRow}")->getFont()->setBold(true)->setColor(
                new Color($record->status === 'aktif' ? '047857' : 'B91C1C')
            );

            if ($no % 2 === 0) {
                $sheet->getStyle("A{$currentRow}:J{$currentRow}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            $sheet->getStyle("A{$currentRow}:J{$currentRow}")->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setRGB('E2E8F0');

            $sheet->getRowDimension($currentRow)->setRowHeight(20);

            $currentRow++;
            $no++;
        }

        // 4. Summary / Total Row
        $lastDataRow = $currentRow - 1;
        if ($lastDataRow >= $startRow) {
            $sheet->setCellValue("A{$currentRow}", 'TOTAL');
            $sheet->mergeCells("A{$currentRow}:D{$currentRow}");
            $sheet->getStyle("A{$currentRow}:D{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue("E{$currentRow}", "=SUM(E{$startRow}:E{$lastDataRow})");
            $sheet->setCellValue("G{$currentRow}", "=SUM(G{$startRow}:G{$lastDataRow})");
            $sheet->setCellValue("H{$currentRow}", "=SUM(H{$startRow}:H{$lastDataRow})");
            $sheet->setCellValue("I{$currentRow}", "=SUM(I{$startRow}:I{$lastDataRow})");

            foreach (['E', 'G', 'H', 'I'] as $numCol) {
                $sheet->getStyle("{$numCol}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("{$numCol}{$currentRow}")->getNumberFormat()->setFormatCode($currencyFormat);
            }

            $sheet->getStyle("A{$currentRow}:J{$currentRow}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '0F172A'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'top' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '94A3B8'],
                    ],
                    'bottom' => [
                        'borderStyle' => Border::BORDER_DOUBLE,
                        'color' => ['rgb' => '0F172A'],
                    ],
                    'left' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1'],
                    ],
                    'right' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1'],
                    ],
                    'vertical' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1'],
                    ],
                ],
            ]);
            $sheet->getRowDimension($currentRow)->setRowHeight(24);
        }

        // 5. Auto-size columns with minimum widths
        $minWidths = [
            'A' => 6,
            'B' => 32,
            'C' => 14,
            'D' => 18,
            'E' => 20,
            'F' => 20,
            'G' => 20,
            'H' => 20,
            'I' => 20,
            'J' => 14,
        ];

        foreach ($minWidths as $col => $minWidth) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->calculateColumnWidths();
        foreach ($minWidths as $col => $minWidth) {
            if ($sheet->getColumnDimension($col)->getWidth() < $minWidth) {
                $sheet->getColumnDimension($col)->setAutoSize(false);
                $sheet->getColumnDimension($col)->setWidth($minWidth);
            }
        }

        $filename = 'Daftar_Aktiva_Tetap_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
