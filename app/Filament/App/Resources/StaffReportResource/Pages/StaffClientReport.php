<?php

namespace App\Filament\App\Resources\StaffReportResource\Pages;

use App\Models\ClientReport;
use Illuminate\Support\Carbon;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Filament\App\Resources\StaffReportResource;
use Filament\Forms\Form;
use Filament\Pages\Concerns\InteractsWithFormActions;

class StaffClientReport extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithFormActions;

    protected static string $resource = StaffReportResource::class;

    protected static string $view = 'filament.pages.staff-client-report';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $title = 'Penilaian Laporan Klien';

    public Collection $staffReports;

    public $data = [
        'selectedYear' => null,
        'selectedSemester' => null,
    ];

    public function mount(): void
    {
        // $this->data['selectedYear'] = now()->year;
        $this->data['selectedYear'] = '2024'; // Set default year for testing
        $this->data['selectedSemester'] = now()->month <= 6 ? 1 : 2;
        // $this->data['selectedSemester'] = 2; // Set default semester for testing
        $this->loadClientReports();
        $this->form->fill($this->data);
    }

    protected function loadClientReports(): void
    {
        // Ambil data laporan untuk tahun dan semester yang dipilih
        $reports = ClientReport::with(['client', 'staff'])
            ->where('staff_id', auth()->user()->staff_id)
            ->where('is_verified', true)
            ->when($this->data['selectedSemester'] == 1, function ($query) {
                return $query->whereRaw("report_month BETWEEN '{$this->data['selectedYear']}-01' AND '{$this->data['selectedYear']}-06'");
            })
            ->when($this->data['selectedSemester'] == 2, function ($query) {
                return $query->whereRaw("report_month BETWEEN '{$this->data['selectedYear']}-07' AND '{$this->data['selectedYear']}-12'");
            })
            ->get();

        // dd($reports);

        // Definisikan jenis laporan yang akan ditampilkan
        $reportTypes = ['pph25' => 'PPH 25', 'pph21' => 'PPH 21', 'ppn' => 'PPN'];

        // Kelompokkan laporan berdasarkan jenis laporan dan nama klien
        $groupedReports = $reports->groupBy('report_content')
            ->map(function ($reports) {
                return $reports->groupBy('client.company_name');
            });

        // Definisikan mapping bulan Indonesia ke Inggris
        $monthMapping = [
            'Januari' => 'January',
            'Februari' => 'February',
            'Maret' => 'March',
            'April' => 'April',
            'Mei' => 'May',
            'Juni' => 'June',
            'Juli' => 'July',
            'Agustus' => 'August',
            'September' => 'September',
            'Oktober' => 'October',
            'November' => 'November',
            'Desember' => 'December',
        ];

        $structuredReports = collect();
        $months = $this->data['selectedSemester'] == 1 ?
            ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'] :
            ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Filter reports berdasarkan semester
        $startMonth = $this->data['selectedSemester'] == 1 ? 1 : 7;
        $endMonth = $this->data['selectedSemester'] == 1 ? 6 : 12;

        $filteredReports = $reports->filter(function ($report) use ($startMonth, $endMonth) {
            $reportMonth = Carbon::parse($report->report_month)->month;
            return $reportMonth >= $startMonth && $reportMonth <= $endMonth;
        });

        $reports = $filteredReports;

        foreach ($reportTypes as $reportContent => $reportLabel) {
            $reportsForType = $groupedReports->get($reportContent, collect());
            $clientsData = [];

            foreach ($reportsForType as $clientName => $clientReports) {
                $clientData = [
                    'name' => $clientName,
                    'months' => [],
                ];

                foreach ($months as $month) {
                    // Convert to collection if it's an array
                    $clientReportsCollection = $clientReports instanceof Collection ? $clientReports : collect($clientReports);

                    // Cari laporan untuk klien dan bulan tertentu
                    $reportForMonth = $clientReportsCollection->first(function ($report) use ($month, $monthMapping) {
                        $reportMonthDate = Carbon::parse($report->report_month);
                        $reportDate = $report->report_date ? Carbon::parse($report->report_date) : null;

                        // Cek apakah ini laporan untuk bulan yang dicari
                        $matchesMonth = strtolower($reportMonthDate->format('F')) === strtolower($monthMapping[$month]);
                        $matchesYear = $reportMonthDate->year === (int)$this->data['selectedYear'];

                        // Jika tidak cocok, cek apakah ini laporan bulan berikutnya yang dibuat di awal bulan
                        if (!$matchesMonth && $reportDate) {
                            // Hitung bulan yang seharusnya
                            $targetMonth = array_search($month, array_keys($monthMapping)) + 1;

                            // Jika report_date di awal bulan (1-15) dan report_month adalah bulan sebelumnya
                            if ($reportDate->day <= 15) {
                                $expectedReportMonth = $reportDate->copy()->subMonth();
                                if (
                                    $expectedReportMonth->month === $reportMonthDate->month &&
                                    $expectedReportMonth->year === $reportMonthDate->year &&
                                    $reportMonthDate->month === $targetMonth
                                ) {
                                    $matchesMonth = true;
                                }
                            }
                        }

                        return $matchesMonth && $matchesYear;
                    });

                    $clientData['months'][$month] = [
                        'report_date' => $reportForMonth ? Carbon::parse($reportForMonth->report_date)->format('d/m/Y') : '-',
                        'score' => $reportForMonth ? floatval($reportForMonth->score) : null,
                        'score_display' => $reportForMonth ? $reportForMonth->score : '-',
                    ];
                }
                $clientsData[] = $clientData;
            }

            $structuredReports[$reportContent] = [
                'label' => $reportLabel,
                'clients' => $clientsData
            ];
        }

        // dd($structuredReports);

        $this->staffReports = $structuredReports;

        // Debug: Log the final structured reports
        \Illuminate\Support\Facades\Log::info('Final structured reports:', $structuredReports->toArray());
    }

    protected function getFormModel(): string
    {
        return StaffClientReport::class;
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->live()
            ->schema([
                \Filament\Forms\Components\Select::make('selectedYear')
                    ->label('Tahun')
                    ->options(function () {
                        $years = ClientReport::selectRaw('DISTINCT YEAR(report_month) as year')
                            ->whereNotNull('report_month')
                            ->orderBy('year', 'desc')
                            ->pluck('year')
                            ->map(fn($year) => (string) $year)
                            ->toArray();

                        if (empty($years)) {
                            $years = [(string) now()->year];
                        }

                        return array_combine($years, $years);
                    })
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->loadClientReports();
                    })
                    ->default((string) now()->year)
                    ->required(),
                \Filament\Forms\Components\Select::make('selectedSemester')
                    ->label('Semester')
                    ->options([
                        1 => 'Semester 1 (Januari - Juni)',
                        2 => 'Semester 2 (Juli - Desember)',
                    ])
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->loadClientReports();
                    })
                    ->default(now()->month <= 6 ? 1 : 2)
                    ->required(),
            ]);
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }
}
