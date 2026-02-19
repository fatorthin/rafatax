<?php

namespace App\Filament\App\Resources\MouResource\Widgets;

use App\Models\MoU;
use App\Models\CashReport;
use Filament\Forms;
use Filament\Tables;
use App\Models\Invoice;
use Filament\Tables\Table;
use App\Models\CostListInvoice;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\App\Resources\InvoiceResource;
use Filament\Widgets\TableWidget as BaseWidget;

class MouInvoicesTable extends BaseWidget
{
    public ?int $mouId = null;

    protected $listeners = ['invoice-created' => '$refresh', 'invoice-deleted' => '$refresh', 'invoice-status-updated' => '$refresh'];

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
                        'paid' => 'success',
                        'unpaid' => 'warning',
                        'overdue' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('rek_transfer')
                    ->label('Rekening Transfer'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->formatStateUsing(fn(string $state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->getStateUsing(function ($record) {
                        return $record->costListInvoices()->sum('amount');
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Total Amount')
                            ->formatStateUsing(function ($state) {
                                return 'Rp ' . number_format($state, 0, ',', '.');
                            })
                            ->using(function ($query) {
                                // Get all invoice IDs from the current query
                                $invoiceIds = $query->pluck('id')->toArray();

                                // Calculate total from the cost_list_invoices table
                                $total = CostListInvoice::whereIn('invoice_id', $invoiceIds)
                                    ->sum('amount');

                                return $total;
                            })
                    )
                    ->alignEnd(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn(Invoice $record): string => InvoiceResource::getUrl('edit', ['record' => $record]))
                    ->label('Edit Invoice')
                    ->icon('heroicon-o-eye')
                    ->color('primary'),
                Tables\Actions\ViewAction::make()
                    // ->url(fn(Invoice $record): string => InvoiceResource::getUrl('cost-list', ['record' => $record]))
                    ->url(fn(Invoice $record): string => route('filament.app.resources.invoices.viewCostList', ['record' => $record->id]))
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('info'),
                Tables\Actions\Action::make('previewPdf')
                    ->label('Preview PDF')
                    ->icon('heroicon-o-printer')
                    ->url(fn(Invoice $record): string => route('invoices.preview', ['id' => $record->id]))
                    ->color('success')
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('updateStatusBayar')
                    ->label('Update Status Bayar')
                    ->icon('heroicon-o-check-circle')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Update Status Bayar')
                    ->modalDescription('Pilih rekening transfer untuk menandai invoice sebagai Paid.')
                    ->form([
                        Forms\Components\Select::make('rek_transfer')
                            ->label('Rekening Transfer')
                            ->options([
                                'BCA PT' => 'BCA PT',
                                'BCA BARU' => 'BCA BARU',
                                'BCA LAMA' => 'BCA LAMA',
                                'MANDIRI' => 'MANDIRI',
                                'KAS BESAR' => 'KAS BESAR',
                            ])
                            ->required(),
                    ])
                    ->action(function (Invoice $record, array $data): void {
                        $rekTransferMapping = [
                            'BCA PT' => 1,
                            'BCA BARU' => 2,
                            'BCA LAMA' => 3,
                            'MANDIRI' => 5,
                            'KAS BESAR' => 6,
                        ];

                        $cashReferenceId = $rekTransferMapping[$data['rek_transfer']];

                        // Update invoice status and rekening transfer
                        $record->update([
                            'invoice_status' => 'paid',
                            'rek_transfer' => $data['rek_transfer'],
                        ]);

                        // Create cash report entry per cost list invoice item (each has its own coa_id)
                        $firstCashReportId = null;
                        $costListInvoices = $record->costListInvoices()->get();
                        foreach ($costListInvoices as $costItem) {
                            $cashReport = CashReport::create([
                                'description' => 'Pembayaran Invoice ' . $record->invoice_number . ' - ' . $costItem->description,
                                'cash_reference_id' => $cashReferenceId,
                                'mou_id' => $record->mou_id,
                                'coa_id' => $costItem->coa_id,
                                'invoice_id' => $record->id,
                                'cost_list_invoice_id' => $costItem->id,
                                'type' => 'debit',
                                'debit_amount' => $costItem->amount,
                                'credit_amount' => 0,
                                'transaction_date' => now(),
                            ]);

                            if ($firstCashReportId === null) {
                                $firstCashReportId = $cashReport->id;
                            }
                        }

                        // Update cash_report_id on invoice
                        if ($firstCashReportId) {
                            $record->update(['cash_report_id' => $firstCashReportId]);
                        }

                        // Update ChecklistMou status to complete for this invoice
                        \App\Models\ChecklistMou::where('invoice_id', $record->id)
                            ->update(['status' => 'completed']);

                        $this->dispatch('invoice-status-updated');

                        Notification::make()
                            ->title('Status invoice berhasil diubah menjadi Paid')
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Invoice $record): bool => $record->invoice_status !== 'paid'),
                Tables\Actions\DeleteAction::make()
                    ->label('Delete Invoice')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Invoice $record) {
                        $record->delete();
                        Notification::make()
                            ->title('Invoice deleted successfully')
                            ->success()
                            ->send();
                    }),
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
