<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\StaffAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportAttendanceController extends Controller
{
    public function exportMonthly(Request $request): StreamedResponse
    {
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);

        $startDate = Carbon::create($tahun, $bulan, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $dates = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dates[] = $date->format('Y-m-d');
        }

        $staff = Staff::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $attendance = StaffAttendance::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get()
            ->groupBy(['tanggal', 'staff_id']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kehadiran ' . $startDate->translatedFormat('F Y'));

        // Header
        $sheet->setCellValue('A1', 'Tanggal');
        $colIndex = 2; // B = 2
        foreach ($staff as $s) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue($colLetter . '1', $s->name);
            $colIndex++;
        }

        // Rows per date
        $row = 2;
        foreach ($dates as $date) {
            $dateObj = Carbon::parse($date);
            $sheet->setCellValue('A' . $row, $dateObj->translatedFormat('D, d M Y'));

            $colIndex = 2;
            foreach ($staff as $s) {
                /** @var \App\Models\StaffAttendance|null $att */
                $att = optional(optional($attendance->get($date))->get($s->id))->first();

                if (!$att) {
                    $value = '-';
                } else {
                    $jamMasuk = $att->jam_masuk ? Carbon::parse($att->jam_masuk)->format('H:i') : '-';
                    $jamPulang = $att->jam_pulang ? Carbon::parse($att->jam_pulang)->format('H:i') : '-';
                    $parts = [];
                    $parts[] = strtoupper((string) $att->status);
                    $parts[] = 'In ' . $jamMasuk;
                    $parts[] = 'Out ' . $jamPulang;
                    if ($att->is_late) {
                        $parts[] = 'Late';
                    }
                    if (!empty($att->visit_solo_count) && (int) $att->visit_solo_count > 0) {
                        $parts[] = 'Solo ' . (int) $att->visit_solo_count;
                    }
                    if (!empty($att->visit_luar_solo_count) && (int) $att->visit_luar_solo_count > 0) {
                        $parts[] = 'Luar ' . (int) $att->visit_luar_solo_count;
                    }
                    if (!empty($att->keterangan)) {
                        $parts[] = 'Ket: ' . $att->keterangan;
                    }
                    $value = implode(' | ', $parts);
                }

                $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue($colLetter . $row, $value);
                $colIndex++;
            }

            $row++;
        }

        // Autosize columns
        $highestColumn = $sheet->getHighestColumn();
        foreach (range('A', $highestColumn) as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $fileName = 'kehadiran_' . $startDate->format('Y_m') . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
