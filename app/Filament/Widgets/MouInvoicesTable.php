<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\CostListInvoice;
use App\Models\MoU;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class MouInvoicesTable extends BaseWidget
{
    public ?int $mouId = null;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Daftar Invoice MoU';

    // Property to store computed total value
    protected $totalValue = 0;

    protected function getTableQuery(): Builder
    {
        $query = Invoice::query()->when(
            $this->mouId,
            fn(Builder $query) => $query->where('mou_id', $this->mouId),
            fn(Builder $query) => $query->whereNull('id')
        );

        // Calculate total here for footer
        if ($this->mouId) {
            $this->totalValue = CostListInvoice::where('mou_id', $this->mouId)
                ->whereNotNull('invoice_id')
                ->sum('amount');
        }

        return $query;
    }

    public function getTableTotalValue()
    {
        return $this->totalValue;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->heading('Daftar Invoice MoU')
            ->headerActions([
                Tables\Actions\Action::make('createInvoice')
                    ->label('Create New Invoice')
                    ->icon('heroicon-o-plus')
                    ->url(fn() => route('filament.admin.resources.invoices.create', ['mou_id' => $this->mouId]))
                    ->color('primary'),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('Invoice Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Paid' => 'success',
                        'Unpaid' => 'warning',
                        'Overdue' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->formatStateUsing(fn(string $state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->getStateUsing(function ($record) {
                        return $record->costListInvoices()->sum('amount');
                    })
                    ->alignEnd(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn(Invoice $record): string => route('filament.admin.resources.invoices.viewCostList', ['record' => $record->id]))
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('info'),
            ]);
    }

    protected function getFooter(): ?string
    {
        return view('filament.tables.invoice-total-footer', [
            'total' => $this->totalValue,
        ])->render();
    }

    public static function canView(): bool
    {
        return true;
    }
}
