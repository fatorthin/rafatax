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

                    // Simpan daftar case_project_id yang di-cut off ke PayrollBonus dalam bentuk array (akan dipersist JSON oleh cast)
                    $this->record->case_project_ids = $caseProjects->values()->toArray();
                    $this->record->save();

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
                    // Pastikan menyimpan dalam bentuk array (biarkan Eloquent cast menangani JSON)
                    foreach ($groupedData as $staffId => $data) {
                        PayrollBonusDetail::create([
                            'payroll_bonus_id' => $this->record->id,
                            'staff_id' => $staffId,
                            'amount' => $data['total_bonus'],
                            'case_project_detail_ids' => $data['case_project_detail_ids'],
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
                        TextEntry::make('total_amount')->label('Total Amount (Rp)')
                            ->state(function () {
                                $total = PayrollBonusDetail::where('payroll_bonus_id', $this->record->id)
                                    ->sum('amount');
                                return 'Rp ' . number_format($total, 0, ',', '.');
                            }),
                        TextEntry::make('case_projects')->label('Case Projects')
                            ->state(function () {
                                // Ambil daftar case_project_ids dengan aman (tangani kemungkinan string JSON)
                                $ids = $this->record->case_project_ids ?? [];
                                if (!is_array($ids)) {
                                    $decoded = json_decode((string) $ids, true);
                                    $ids = is_array($decoded) ? $decoded : [];
                                }

                                if (empty($ids)) {
                                    return '-';
                                }

                                // Ambil description dan company_name dari CaseProject dengan relasi client
                                $projects = CaseProject::with('client')
                                    ->whereIn('id', $ids)
                                    ->orderBy('project_date')
                                    ->get()
                                    ->map(function ($project) {
                                        $companyName = $project->client->company_name ?? 'N/A';
                                        return $project->description . ' (' . $companyName . ')';
                                    })
                                    ->toArray();

                                return implode(', ', $projects);
                            }),
                    ])->columns(4)
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
            ->actions([
                \Filament\Tables\Actions\Action::make('view_detail')
                    ->label('Lihat Detail Bonus')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn($record) => 'Detail Bonus - ' . $record->staff->name)
                    ->modalContent(function ($record) {
                        // Pastikan nilai berupa array meskipun data lama tersimpan sebagai string JSON
                        $caseProjectDetailIds = $record->case_project_detail_ids ?? [];
                        if (!is_array($caseProjectDetailIds)) {
                            $decoded = json_decode((string) $caseProjectDetailIds, true);
                            $caseProjectDetailIds = is_array($decoded) ? $decoded : [];
                        }
                        $caseProjectDetails = CaseProjectDetail::with(['caseProject', 'caseProject.client'])
                            ->whereIn('id', $caseProjectDetailIds)
                            ->get();

                        return view('filament.modals.payroll-bonus-detail', [
                            'record' => $record,
                            'caseProjectDetails' => $caseProjectDetails,
                        ]);
                    })
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                \Filament\Tables\Actions\Action::make('download_slip')
                    ->label('Download Slip')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->url(fn($record) => route('exports.payroll-bonus.slip', ['detail' => $record->id]))
                    ->openUrlInNewTab(false),
            ]);
    }
}
