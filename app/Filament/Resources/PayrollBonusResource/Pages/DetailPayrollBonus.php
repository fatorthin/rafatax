<?php

namespace App\Filament\Resources\PayrollBonusResource\Pages;

use Filament\Tables\Table;
use App\Models\CaseProject;
use App\Models\PayrollBonus;
use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use App\Models\CaseProjectDetail;
use App\Models\PayrollBonusDetail;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\PayrollBonusResource;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\Summarizers\Summarizer;

class DetailPayrollBonus extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = PayrollBonusResource::class;

    protected static string $view = 'filament.resources.payroll-bonus-resource.pages.detail-payroll-bonus';

    public PayrollBonus $record;

    public function getTitle(): string
    {
        return 'Detail Payroll Bonus - ' . $this->record->description;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cutoff')
                ->label('Cut Off')
                ->icon('heroicon-o-scissors')
                ->requiresConfirmation()
                ->modalHeading('Cut Off Bonus')
                ->modalDescription('Apakah Anda yakin ingin melakukan cut off bonus? Data akan diambil dari Case Project yang tanggalnya sesuai dengan periode cut off.')
                ->modalSubmitActionLabel('Ya, Cut Off')
                ->action(function () {
                    // Hapus data cut off yang sudah ada sebelumnya
                    PayrollBonusDetail::where('payroll_bonus_id', $this->record->id)->delete();

                    // Ambil data case project yang tanggalnya dalam rentang start_date dan end_date
                    $caseProjects = CaseProject::whereBetween('project_date', [
                        $this->record->start_date,
                        $this->record->end_date
                    ])->where('status', 'open')->pluck('id');

                    // Ambil semua detail dari case project tersebut
                    $caseProjectDetails = CaseProjectDetail::whereIn('case_project_id', $caseProjects)->get();

                    // Jika tidak ada data bonus yang ditemukan
                    if ($caseProjectDetails->isEmpty()) {
                        \Filament\Notifications\Notification::make()
                            ->title('Tidak Ada Data')
                            ->body('Tidak ada data bonus yang ditemukan untuk periode cut off ini.')
                            ->warning()
                            ->send();

                        return;
                    }

                    // Group by staff_id dan kumpulkan case_project_detail_id serta total bonus
                    $groupedData = $caseProjectDetails->groupBy('staff_id')->map(function ($details) {
                        return [
                            'total_bonus' => $details->sum('bonus'),
                            'case_project_detail_ids' => $details->pluck('id')->values()->toArray(),
                        ];
                    });

                    // Simpan data ke PayrollBonusDetail
                    foreach ($groupedData as $staffId => $data) {
                        PayrollBonusDetail::create([
                            'payroll_bonus_id' => $this->record->id,
                            'staff_id' => $staffId,
                            'amount' => $data['total_bonus'],
                            'case_project_detail_ids' => json_encode($data['case_project_detail_ids']),
                        ]);
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Berhasil')
                        ->body('Cut off bonus berhasil dilakukan. Total ' . $groupedData->count() . ' staff diproses.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
                Section::make('Informasi payroll Bonus')
                    ->schema([
                        TextEntry::make('description')->label('Deskripsi'),
                        TextEntry::make('start_date')->label('Tanggal Mulai Cut Off')->date('d-m-Y'),
                        TextEntry::make('end_date')->label('Tanggal Selesai Cut Off')->date('d-m-Y'),
                    ])->columns(3)
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(PayrollBonusDetail::query()->where('payroll_bonus_id', $this->record->id))
            ->columns([
                TextColumn::make('index')
                    ->label('No')
                    ->state(fn($record, $rowLoop) => $rowLoop->iteration)
                    ->sortable(),

                TextColumn::make('staff.name')
                    ->label('Nama Staff')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Total Bonus (Rp)')
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable()
                    ->summarize(
                        Summarizer::make()
                            ->label('Total Bonus:')
                            ->using(function ($query) {
                                $total = $query->sum('amount');
                                return 'Rp ' . number_format($total, 0, ',', '.');
                            })
                    ),
            ])
            ->paginated(false)
            ->striped()
            ->actions([]);
    }
}
