<?php

namespace App\Filament\Resources\JournalBookReferenceResource\Pages;

use Filament\Actions;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use App\Models\JournalBookReport;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use App\Models\JournalBookReference;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\JournalBookReferenceResource;

class ViewJournalBookMonthDetail extends Page implements HasTable
{
    use InteractsWithTable;
    protected static string $resource = JournalBookReferenceResource::class;

    protected static string $view = 'filament.resources.journal-book-reference-resource.pages.view-journal-book-month-detail';

    public JournalBookReference $record;

    public function getTitle(): string
    {
        $year = request()->query('year');
        $month = (int) request()->query('month');

        $monthName = Carbon::create()->month($month)->format('F');

        return "Transactions - {$this->record->name} - {$monthName} {$year}";
    }

    public function table(Table $table): Table
    {
        $year = (int) request()->query('year');
        $month = (int) request()->query('month');

        if (!$year || !$month) {
            return $table->query(JournalBookReport::where('id', 0)); // Empty query if no year/month
        }

        // Get monthly transactions
        $query = JournalBookReport::query()
            ->where('journal_book_id', $this->record->id)
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->orderBy('transaction_date')
            ->orderBy('id');

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Date')
                    ->date('d-M-Y'),
                TextColumn::make('coa.code')
                    ->label('CoA')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('coa.name')
                    ->label('CoA Name')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('debit_amount')
                    ->numeric()
                    ->formatStateUsing(function ($state) {
                        return number_format((float) $state, 0, ',', '.');
                    })
                    ->summarize(
                        Sum::make()
                            ->formatStateUsing(function ($state) {
                                return number_format((float) $state, 0, ',', '.');
                            })
                    )
                    ->alignEnd(),
                TextColumn::make('credit_amount')
                    ->numeric()
                    ->formatStateUsing(function ($state) {
                        return number_format((float) $state, 0, ',', '.');
                    })
                    ->summarize(
                        Sum::make()
                            ->formatStateUsing(function ($state) {
                                return number_format((float) $state, 0, ',', '.');
                            })
                    )
                    ->alignEnd(),

            ])
            ->filters([
                // No additional filters needed here since we're already filtering by month/year
            ])
            ->actions([
                // No actions needed here
                \Filament\Tables\Actions\EditAction::make()
                    ->url(fn(JournalBookReport $record) => route('filament.admin.resources.cash-reports.edit', ['record' => $record])),
            ])
            ->striped()
            ->defaultSort('transaction_date', 'asc')
            ->paginated(false);
    }

    public function modifyTableQuery(Builder $query): Builder
    {
        // Just return the original query
        return $query;
    }

    // Generate a unique key for each table record
    public function getTableRecordKey($record): string
    {
        return (string) $record->getKey();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to Monthly View')
                ->url(JournalBookReferenceResource::getUrl('viewMonthly', ['record' => $this->record]))
                ->color('info')
                ->icon('heroicon-o-arrow-left'),
            Actions\Action::make('viewAll')
                ->label('View All Transactions')
                ->url(JournalBookReferenceResource::getUrl('view', ['record' => $this->record]))
                ->color('success')
                ->icon('heroicon-o-list-bullet'),

        ];
    }
}
