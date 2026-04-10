<?php

namespace App\Filament\App\Pages;

use App\Models\Coa;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LabaRugi extends Page
{
    protected static string $view = 'filament.app.pages.laba-rugi';
    protected static ?string $title = 'Laporan Laba Rugi';

    public $month;
    public $year;

    public function mount(): void
    {
        $this->month = request('month', now()->month);
        $this->year = request('year', now()->year);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filter')
                ->label('Filter Periode')
                ->icon('heroicon-o-funnel')
                ->form([
                    Select::make('month')
                        ->label('Bulan')
                        ->options([
                            1 => 'Januari',
                            2 => 'Februari',
                            3 => 'Maret',
                            4 => 'April',
                            5 => 'Mei',
                            6 => 'Juni',
                            7 => 'Juli',
                            8 => 'Agustus',
                            9 => 'September',
                            10 => 'Oktober',
                            11 => 'November',
                            12 => 'Desember'
                        ])
                        ->default($this->month)
                        ->required(),
                    Select::make('year')
                        ->label('Tahun')
                        ->options(function () {
                            $years = [];
                            $currentYear = now()->year;
                            for ($i = $currentYear - 5; $i <= $currentYear + 1; $i++) {
                                $years[$i] = $i;
                            }
                            return $years;
                        })
                        ->default($this->year)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->month = $data['month'];
                    $this->year = $data['year'];
                    $this->redirect(route('filament.app.pages.laba-rugi', ['month' => $this->month, 'year' => $this->year]));
                }),
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn() => $this->exportToExcel()),
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('secondary')
                ->url(fn() => route('filament.app.pages.neraca-lajur-bulanan', ['month' => $this->month, 'year' => $this->year])),
        ];
    }

    public function getLabaRugiData()
    {
        $startOfCurrentMonth = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endOfCurrentMonth = Carbon::create($this->year, $this->month, 1)->endOfMonth();

        $data = Coa::query()
            ->select([
                'coa.id',
                'coa.code',
                'coa.name',
                'group_coas.id as group_id',
                'group_coas.name as group_name',
                DB::raw('COALESCE(COUNT(CASE WHEN j.journal_book_id = 3 THEN 1 END), 0) as has_journal'),
                DB::raw('COALESCE(SUM(CASE WHEN j.journal_book_id = 3 THEN j.debit_amount ELSE 0 END), 0) as neraca_awal_debit'),
                DB::raw('COALESCE(SUM(CASE WHEN j.journal_book_id = 3 THEN j.credit_amount ELSE 0 END), 0) as neraca_awal_kredit'),
                DB::raw('COALESCE(SUM(CASE WHEN j.journal_book_id = 1 THEN j.debit_amount ELSE 0 END), 0) as jurnal_umum_debit'),
                DB::raw('COALESCE(SUM(CASE WHEN j.journal_book_id = 1 THEN j.credit_amount ELSE 0 END), 0) as jurnal_umum_kredit'),
                DB::raw('COALESCE(SUM(CASE WHEN j.journal_book_id = 2 THEN j.debit_amount ELSE 0 END), 0) as aje_debit'),
                DB::raw('COALESCE(SUM(CASE WHEN j.journal_book_id = 2 THEN j.credit_amount ELSE 0 END), 0) as aje_kredit')
            ])
            ->leftJoin('group_coas', 'coa.group_coa_id', '=', 'group_coas.id')
            ->leftJoin('journal_book_reports as j', 'coa.id', '=', 'j.coa_id')
            ->where('coa.deleted_at', null)
            ->where('coa.type', 'kkp')
            ->whereRaw("coa.code REGEXP '^AO-(4[0-9]{2}(\\.[1-6])?|501(\\.[1-4])?|50[0-9]|5[1-9][0-9]|6[0-9]{2}|70[0-2])$'")
            ->groupBy('coa.id', 'coa.code', 'coa.name', 'coa.group_coa_id', 'group_coas.id', 'group_coas.name')
            ->orderBy('group_coas.id')
            ->orderBy('coa.code')
            ->get();

        $labaRugiData = [
            'pendapatan' => [],
            'beban' => [],
            'totalPendapatan' => 0,
            'totalBeban' => 0
        ];

        $currentGroup = null;
        $currentGroupName = '';
        $currentGroupTotal = 0;
        $currentGroupType = null;

        $defaultItemStructure = [
            'code' => '',
            'name' => '',
            'amount' => 0,
            'is_group_header' => false,
            'is_group_total' => false
        ];

        foreach ($data as $row) {
            $totalDebit = $row->neraca_awal_debit + $row->jurnal_umum_debit + $row->aje_debit;
            $totalKredit = $row->neraca_awal_kredit + $row->jurnal_umum_kredit + $row->aje_kredit;
            $selisih = $totalDebit - $totalKredit;

            $isPendapatan = preg_match('/^AO-4/', $row->code);
            $amount = $isPendapatan ? abs($selisih) : abs($selisih);
            $type = $isPendapatan ? 'pendapatan' : 'beban';

            if ($currentGroup !== $row->group_id) {
                if ($currentGroup !== null && $currentGroupType !== null) {
                    $labaRugiData[$currentGroupType][] = array_merge($defaultItemStructure, [
                        'name' => 'Jumlah ' . $currentGroupName,
                        'amount' => $currentGroupTotal,
                        'is_group_total' => true
                    ]);
                }

                $currentGroup = $row->group_id;
                $currentGroupName = $row->group_name;
                $currentGroupTotal = 0;
                $currentGroupType = $type;

                $labaRugiData[$type][] = array_merge($defaultItemStructure, [
                    'name' => $currentGroupName,
                    'is_group_header' => true
                ]);
            }

            $labaRugiData[$type][] = array_merge($defaultItemStructure, [
                'code' => $row->code,
                'name' => $row->name,
                'amount' => $amount
            ]);

            $currentGroupTotal += $amount;
            if ($isPendapatan) {
                $labaRugiData['totalPendapatan'] += $amount;
            } else {
                $labaRugiData['totalBeban'] += $amount;
            }
        }

        if ($currentGroup !== null && $currentGroupType !== null) {
            $labaRugiData[$currentGroupType][] = array_merge($defaultItemStructure, [
                'name' => 'Jumlah ' . $currentGroupName,
                'amount' => $currentGroupTotal,
                'is_group_total' => true
            ]);
        }

        return $labaRugiData;
    }

    public function exportToExcel()
    {
        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $title = 'LAPORAN LABA RUGI - ' . strtoupper($monthNames[$this->month]) . ' ' . $this->year;

        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $labaRugiData = $this->getLabaRugiData();

        $row = 3;

        // Pendapatan
        $sheet->setCellValue('A' . $row, 'PENDAPATAN');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $row++;

        foreach ($labaRugiData['pendapatan'] as $item) {
            if ($item['is_group_header']) {
                $sheet->setCellValue('A' . $row, $item['name']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            } elseif ($item['is_group_total']) {
                $sheet->setCellValue('A' . $row, $item['name']);
                $sheet->setCellValue('B' . $row, $item['amount']);
                $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
                $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
            } else {
                $sheet->setCellValue('A' . $row, $item['code']);
                $sheet->setCellValue('B' . $row, $item['name']);
                $sheet->setCellValue('C' . $row, $item['amount']);
                $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0');
            }
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'TOTAL PENDAPATAN');
        $sheet->setCellValue('B' . $row, $labaRugiData['totalPendapatan']);
        $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $row += 2;

        // Beban
        $sheet->setCellValue('A' . $row, 'BEBAN');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $row++;

        foreach ($labaRugiData['beban'] as $item) {
            if ($item['is_group_header']) {
                $sheet->setCellValue('A' . $row, $item['name']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            } elseif ($item['is_group_total']) {
                $sheet->setCellValue('A' . $row, $item['name']);
                $sheet->setCellValue('B' . $row, $item['amount']);
                $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
                $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
            } else {
                $sheet->setCellValue('A' . $row, $item['code']);
                $sheet->setCellValue('B' . $row, $item['name']);
                $sheet->setCellValue('C' . $row, $item['amount']);
                $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0');
            }
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'TOTAL BEBAN');
        $sheet->setCellValue('B' . $row, $labaRugiData['totalBeban']);
        $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $row += 2;

        // Laba Rugi Bersih
        $labaRugiBersih = $labaRugiData['totalPendapatan'] - $labaRugiData['totalBeban'];
        $sheet->setCellValue('A' . $row, 'LABA (RUGI) BERSIH');
        $sheet->setCellValue('B' . $row, $labaRugiBersih);
        $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D0D0D0');
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');

        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'laba-rugi-' . strtolower($monthNames[$this->month]) . '-' . $this->year . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
