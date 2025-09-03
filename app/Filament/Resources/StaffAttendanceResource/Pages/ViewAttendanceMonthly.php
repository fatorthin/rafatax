<?php

namespace App\Filament\Resources\StaffAttendanceResource\Pages;

use Carbon\Carbon;
use Filament\Actions\Action;
use App\Models\StaffAttendance;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use App\Filament\Resources\StaffAttendanceResource;

class ViewAttendanceMonthly extends Page
{
    protected static string $resource = StaffAttendanceResource::class;

    protected static string $view = 'filament.resources.staff-attendance-resource.pages.view-attendance-monthly';

    protected static ?string $title = 'Kehadiran Karyawan Bulanan';

    public $bulan;
    public $tahun;
    public $periode;

    public function mount()
    {
        $this->bulan = (int) date('m');
        $this->tahun = (int) date('Y');
        $this->periode = Carbon::create($this->tahun, $this->bulan, 1)->format('Y-m');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filter')
                ->label('Pilih Periode')
                ->icon('heroicon-o-calendar')
                ->modalHeading('Pilih Periode Kehadiran')
                ->modalDescription('Silakan pilih bulan dan tahun untuk melihat data kehadiran')
                ->modalSubmitActionLabel('Terapkan Filter')
                ->modalCancelActionLabel('Batal')
                ->form([
                     Select::make('bulan')
                        ->label('Bulan')
                        ->options(
                            collect(range(1, 12))
                                ->mapWithKeys(fn ($m) => [$m => Carbon::create(null, $m, 1)->translatedFormat('F')])
                                ->toArray()
                        )
                        ->default(fn () => $this->bulan ?? now()->month)
                        ->required(),
                    Select::make('tahun')
                        ->label('Tahun')
                        ->options(
                            collect(range(now()->year, now()->year - 5))
                                ->mapWithKeys(fn ($y) => [$y => (string) $y])
                                ->toArray()
                        )
                        ->default(fn () => $this->tahun ?? now()->year)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->bulan = (int) ($data['bulan'] ?? now()->month);
                    $this->tahun = (int) ($data['tahun'] ?? now()->year);
                    $date = Carbon::create($this->tahun, $this->bulan, 1);
                    $this->periode = $date->format('Y-m');
                    
                    Notification::make()
                        ->title('Periode Berhasil Diubah')
                        ->body('Periode kehadiran berhasil diubah ke ' . $date->translatedFormat('F Y'))
                        ->success()
                        ->send();
                })
                ->modalWidth('md')
                ->closeModalByClickingAway(false)
                ->extraAttributes([
                    'class' => 'filter-period-action',
                    'data-testid' => 'filter-period-button'
                ])
                ->color('primary')
                ->size('sm'),
        ];
    }
}
