<?php

namespace App\Filament\Exports;

use App\Models\Client;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ClientExporter
{
    public static function export($clients)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator("Rafatax System")
            ->setTitle("Data Klien")
            ->setSubject("Export Data Klien")
            ->setDescription("Export data klien dari sistem Rafatax");

        // Header columns
        $headers = [
            'A1' => 'Kode Klien',
            'B1' => 'Nama Perusahaan',
            'C1' => 'Alamat',
            'D1' => 'Contact Person',
            'E1' => 'No. Telepon',
            'F1' => 'Nama Pimpinan',
            'G1' => 'Jabatan Pimpinan',
            'H1' => 'NPWP',
            'I1' => 'Jenis WP',
            'J1' => 'Grade',
            'K1' => 'Jenis Klien',
            'L1' => 'PPh 25 Reporting',
            'M1' => 'PPh 23 Reporting',
            'N1' => 'PPh 21 Reporting',
            'O1' => 'PPh 4 Reporting',
            'P1' => 'PPN Reporting',
            'Q1' => 'SPT Tahunan Reporting',
            'R1' => 'Status',
            'S1' => 'Staff Penanggung Jawab',
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

        $sheet->getStyle('A1:S1')->applyFromArray($headerStyle);

        // Auto-size columns
        foreach (range('A', 'S') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set row height
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Populate data
        $row = 2;
        foreach ($clients as $client) {
            $sheet->setCellValue('A' . $row, $client->code);
            $sheet->setCellValue('B' . $row, $client->company_name);
            $sheet->setCellValue('C' . $row, $client->address);
            $sheet->setCellValue('D' . $row, $client->contact_person);
            $sheet->setCellValue('E' . $row, $client->phone);
            $sheet->setCellValue('F' . $row, $client->owner_name);
            $sheet->setCellValue('G' . $row, $client->owner_role);
            $sheet->setCellValue('H' . $row, $client->npwp);
            $sheet->setCellValue('I' . $row, $client->jenis_wp === 'perseorangan' ? 'Perseorangan' : 'Badan');
            $sheet->setCellValue('J' . $row, $client->grade);
            $sheet->setCellValue('K' . $row, strtoupper($client->type));
            $sheet->setCellValue('L' . $row, $client->pph_25_reporting ? 'Ya' : 'Tidak');
            $sheet->setCellValue('M' . $row, $client->pph_23_reporting ? 'Ya' : 'Tidak');
            $sheet->setCellValue('N' . $row, $client->pph_21_reporting ? 'Ya' : 'Tidak');
            $sheet->setCellValue('O' . $row, $client->pph_4_reporting ? 'Ya' : 'Tidak');
            $sheet->setCellValue('P' . $row, $client->ppn_reporting ? 'Ya' : 'Tidak');
            $sheet->setCellValue('Q' . $row, $client->spt_reporting ? 'Ya' : 'Tidak');
            $sheet->setCellValue('R' . $row, ucfirst($client->status ?? 'active'));

            // Get staff names
            $staffNames = $client->staff->pluck('name')->join(', ');
            $sheet->setCellValue('S' . $row, $staffNames);

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

            $sheet->getStyle('A' . $row . ':S' . $row)->applyFromArray($dataStyle);

            // Center align untuk beberapa kolom
            $sheet->getStyle('J' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('K' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('L' . $row . ':R' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        // Auto filter
        $sheet->setAutoFilter('A1:S' . ($row - 1));

        // Freeze first row
        $sheet->freezePane('A2');

        // Create writer and return
        $writer = new Xlsx($spreadsheet);

        // Generate filename
        $filename = 'Data_Klien_' . date('Y-m-d_His') . '.xlsx';
        $filepath = storage_path('app/public/' . $filename);

        // Save file
        $writer->save($filepath);

        return $filename;
    }
}
