<?php

namespace App\Filament\Exports;

use App\Models\CaseProject;
use App\Models\Staff;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CaseProjectExporter
{
    public static function export(?string $caseType = null, ?string $status = null)
    {
        $query = CaseProject::with(['client', 'mou'])
            ->withoutTrashed();

        if ($caseType) {
            $query->where('case_type', $caseType);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $caseProjects = $query->orderBy('created_at', 'desc')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $spreadsheet->getProperties()
            ->setCreator('Rafatax System')
            ->setTitle('Data Proyek Kasus')
            ->setSubject('Export Data Proyek Kasus')
            ->setDescription('Export data proyek kasus dari sistem Rafatax');

        $headers = [
            'A1' => 'No',
            'B1' => 'Deskripsi',
            'C1' => 'Kode Client',
            'D1' => 'Nama Client',
            'E1' => 'Kategori',
            'F1' => 'Nama Pelaksana',
            'G1' => 'No MoU',
            'H1' => 'No Surat Kasus',
            'I1' => 'Tanggal Surat Kasus',
            'J1' => 'No Surat Kuasa',
            'K1' => 'Tanggal Surat Kuasa',
            'L1' => 'Tanggal Drive Pengisian',
            'M1' => 'Tanggal Laporan',
            'N1' => 'Tanggal Berikan Client',
            'O1' => 'Status',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

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

        $sheet->getStyle('A1:O1')->applyFromArray($headerStyle);

        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getRowDimension(1)->setRowHeight(25);

        $statusLabels = [
            'open'        => 'OPEN',
            'in_progress' => 'IN PROGRESS',
            'case_done' => 'CASE DONE',
            'bonus_done' => 'BONUS DONE',
            'paid'        => 'PAID',
        ];

        $row = 2;
        foreach ($caseProjects as $index => $item) {
            $staffIds   = $item->staff_id ?? [];
            $staffNames = Staff::whereIn('id', $staffIds)->pluck('name')->join(', ');

            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $item->description);
            $sheet->setCellValue('C' . $row, $item->client?->code);
            $sheet->setCellValue('D' . $row, $item->client?->company_name);
            $sheet->setCellValue('E' . $row, strtoupper($item->case_type ?? ''));
            $sheet->setCellValue('F' . $row, $staffNames);
            $sheet->setCellValue('G' . $row, $item->mou?->mou_number);
            $sheet->setCellValue('H' . $row, $item->case_letter_number);
            $sheet->setCellValue('I' . $row, $item->case_letter_date ? \Carbon\Carbon::parse($item->case_letter_date)->format('d-m-Y') : '');
            $sheet->setCellValue('J' . $row, $item->power_of_attorney_number);
            $sheet->setCellValue('K' . $row, $item->power_of_attorney_date ? \Carbon\Carbon::parse($item->power_of_attorney_date)->format('d-m-Y') : '');
            $sheet->setCellValue('L' . $row, $item->filling_drive ? \Carbon\Carbon::parse($item->filling_drive)->format('d-m-Y') : '');
            $sheet->setCellValue('M' . $row, $item->report_date ? \Carbon\Carbon::parse($item->report_date)->format('d-m-Y') : '');
            $sheet->setCellValue('N' . $row, $item->share_client_date ? \Carbon\Carbon::parse($item->share_client_date)->format('d-m-Y') : '');
            $sheet->setCellValue('O' . $row, $statusLabels[$item->status] ?? strtoupper($item->status ?? ''));

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

            $sheet->getStyle('A' . $row . ':O' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('O' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        $sheet->setAutoFilter('A1:O' . ($row - 1));
        $sheet->freezePane('A2');

        $writer   = new Xlsx($spreadsheet);
        $suffix   = ($caseType ? '_' . strtolower($caseType) : '') . ($status ? '_' . strtolower($status) : '');
        $suffix   = $suffix ?: '_semua';
        $filename = 'Data_Proyek_Kasus' . $suffix . '_' . date('Y-m-d_His') . '.xlsx';
        $filepath = storage_path('app/public/' . $filename);

        $writer->save($filepath);

        return $filename;
    }
}
