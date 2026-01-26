<?php

namespace App\Filament\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CoaExporter
{
    public static function export($coas)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator("Rafatax System")
            ->setTitle("Data COA")
            ->setSubject("Export Data COA")
            ->setDescription("Export data COA dari sistem Rafatax");

        // Header columns
        $headers = [
            'A1' => 'Kode COA',
            'B1' => 'Nama COA',
            'C1' => 'Tipe',
            'D1' => 'Group COA',
        ];

        // Set headers
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style header
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];

        $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);

        // Auto-size columns
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set row height
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Populate data
        $row = 2;
        foreach ($coas as $coa) {
            $sheet->setCellValue('A' . $row, $coa->code);
            $sheet->setCellValue('B' . $row, $coa->name);
            $sheet->setCellValue('C' . $row, strtoupper($coa->type));
            $sheet->setCellValue('D' . $row, $coa->groupCoa ? $coa->groupCoa->name : '-');

            // Style data rows
            $dataStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ];

            $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($dataStyle);

            // Center align for some columns
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        // Auto filter
        $sheet->setAutoFilter('A1:D' . ($row - 1));

        // Freeze first row
        $sheet->freezePane('A2');

        // Create writer and return
        $writer = new Xlsx($spreadsheet);

        // Generate filename
        $filename = 'Data_COA_' . date('Y-m-d_His') . '.xlsx';
        $filepath = storage_path('app/public/' . $filename);

        // Save file
        $writer->save($filepath);

        return $filename;
    }
}
