<?php

namespace App\Filament\Resources\PayrollBonusResource\Pages;

use Filament\Tables\Table;
use App\Models\CaseProject;
use App\Models\PayrollBonus;
use Filament\Actions\EditAction;
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
            EditAction::make()
                ->record($this->record)
                ->form([
                    \Filament\Forms\Components\TextInput::make('description')
                        ->required()
                        ->maxLength(255),
                    \Filament\Forms\Components\Select::make('case_project_ids')
                        ->label('Case Project')
                        ->options(\App\Models\CaseProject::pluck('description', 'id'))
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('start_date')
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('end_date')
                        ->required(),
                ]),
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
                                    ->orderBy('case_date')
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
