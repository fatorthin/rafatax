<?php

namespace App\Filament\Resources\StaffAttendanceResource\Pages;

use Carbon\Carbon;
use Filament\Actions\Action;
use App\Models\StaffAttendance;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\DatePicker;
use App\Filament\Resources\StaffAttendanceResource;
use Filament\Notifications\Notification;

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
                    DatePicker::make('periode')
                        ->label('Pilih Periode')
                        ->format('Y-m')
                        ->displayFormat('F Y')
                        ->default(fn () => Carbon::create($this->tahun, $this->bulan, 1))
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $date = Carbon::parse($data['periode']);
                    $this->bulan = (int) $date->format('m');
                    $this->tahun = (int) $date->format('Y');
                    $this->periode = $date->format('Y-m');
                    
                    // Notify user dengan FilamentNotification
                    Notification::make()
                        ->title('Periode Berhasil Diubah')
                        ->body('Periode kehadiran berhasil diubah ke ' . $date->format('F Y'))
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
