<?php

namespace App\Filament\App\Pages;

use App\Models\MoU;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Forms;
use App\Models\ChecklistMou;
use App\Models\Invoice;

class MonitoringChecklist extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Checklist Tagihan MoU';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static string $view = 'filament.app.pages.monitoring-checklist';

    public $year;

    public function mount()
    {
        $this->year = date('Y');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MoU::query()
                    ->with(['checklistMous', 'client', 'categoryMou'])
            )
            ->heading('Monitoring Checklist MoU Tahun ' . $this->year)
            ->columns([
                TextColumn::make('index')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('mou_number')
                    ->label('No MoU')
                    ->searchable()
                    ->sortable()
                    ->description(fn(MoU $record) => $record->client->company_name ?? '-'),
                TextColumn::make('categoryMou.name')
                    ->label('Kategori')
                    ->sortable()
                    ->searchable(),

                ViewColumn::make('checklist_months')
                    ->label('Checklist Bulan')
                    ->view('filament.app.tables.columns.checklist-months')
                    ->viewData(['year' => $this->year]),
            ])
            ->filters([
                SelectFilter::make('year')
                    ->label('Tahun')
                    ->options(
                        collect(range(date('Y') - 5, date('Y') + 5))
                            ->mapWithKeys(fn($year) => [$year => $year])
                    )
                    ->default(date('Y'))
                    ->query(function (Builder $query, array $data) {
                        $year = $data['value'] ?? date('Y');
                        $this->year = $year;
                        return $query->whereYear('start_date', $year);
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public function editChecklistAction(): Action
    {
        return Action::make('editChecklist')
            ->label('Update Checklist Status')
            ->modalWidth('md')
            ->form([
                Forms\Components\Hidden::make('mou_id'),
                Forms\Components\Hidden::make('checklist_date'),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'overdue' => 'Overdue',
                    ])
                    ->required(),
                Forms\Components\Select::make('invoice_id')
                    ->label('Invoice')
                    ->options(function (Forms\Get $get) {
                        $mouId = $get('mou_id');
                        if (!$mouId) return [];
                        return Invoice::where('mou_id', $mouId)->pluck('invoice_number', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Forms\Components\Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(3),
            ])
            ->fillForm(function (array $arguments) {
                $mouId = $arguments['mou_id'];
                $date = $arguments['date'];

                $checklist = ChecklistMou::where('mou_id', $mouId)
                    ->where('checklist_date', $date)
                    ->first();

                return [
                    'mou_id' => $mouId,
                    'checklist_date' => $date,
                    'status' => $checklist ? $checklist->status : 'pending',
                    'invoice_id' => $checklist ? $checklist->invoice_id : null,
                    'notes' => $checklist ? $checklist->notes : null,
                ];
            })
            ->action(function (array $data) {
                ChecklistMou::updateOrCreate(
                    [
                        'mou_id' => $data['mou_id'],
                        'checklist_date' => $data['checklist_date'],
                    ],
                    [
                        'status' => $data['status'],
                        'invoice_id' => $data['invoice_id'],
                        'notes' => $data['notes'],
                    ]
                );

                \Filament\Notifications\Notification::make()
                    ->title('Checklist updated')
                    ->success()
                    ->send();
            });
    }
}
