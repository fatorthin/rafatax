<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\CashReport;
use App\Models\CostListInvoice;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class MouInvoicesTable extends BaseWidget
{
    public ?int $mouId = null;

    protected $listeners = ['invoice-created' => '$refresh', 'invoice-deleted' => '$refresh'];

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
                ->whereHas('invoice')
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
            ->headerActions([])
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
                Tables\Columns\TextColumn::make('tgl_transfer')
                    ->label('Transfer Date')
                    ->getStateUsing(fn($record) => $record->invoice_status === 'paid' ? ($record->tgl_transfer ?? $record->created_at) : null)
                    ->date()
                    ->placeholder('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->formatStateUsing(fn(string $state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->getStateUsing(function ($record) {
                        return $record->costListInvoices()->sum('amount');
                    })
                    ->alignEnd(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(fn(Invoice $record): string => \App\Filament\Resources\InvoiceResource::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-o-pencil')
                    ->color('warning'),
                Tables\Actions\ViewAction::make()
                    ->url(fn(Invoice $record): string => route('filament.admin.resources.invoices.viewCostList', ['record' => $record->id]))
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
                    ->modalDescription('Pilih tanggal transfer dan rekening transfer untuk menandai invoice sebagai Paid.')
                    ->form([
                        Forms\Components\DatePicker::make('tgl_transfer')
                            ->label('Tanggal Transfer')
                            ->default(now())
                            ->required(),
                        Forms\Components\Select::make('rek_transfer')
                            ->label('Rekening Transfer')
                            ->options([
                                'BCA PT' => 'BCA PT',
                                'BCA BARU' => 'BCA 425',
                                'BCA LAMA' => 'BCA LAMA',
                                'MANDIRI' => 'MANDIRI',
                                'KAS BESAR' => 'KAS BESAR',
                                'KAS KECIL' => 'KAS KECIL',
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
                            'KAS KECIL' => 7,
                        ];

                        $cashReferenceId = $rekTransferMapping[$data['rek_transfer']];
                        $transferDate = \Carbon\Carbon::parse($data['tgl_transfer']);
                        $nextSortOrder = (CashReport::where('cash_reference_id', $cashReferenceId)
                            ->whereYear('transaction_date', $transferDate->year)
                            ->whereMonth('transaction_date', $transferDate->month)
                            ->max('sort_order') ?? 0) + 1;

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
                                'description' => (function () use ($record) {
                                    if ($record->memo_id && !$record->client_id) {
                                        return $record->memo?->nama_klien ?? '';
                                    }
                                    if ($record->memo_id && $record->client_id) {
                                        return $record->client?->company_name ?? '';
                                    }
                                    if ($record->mou_id && !$record->memo_id && !$record->client_id) {
                                        return $record->mou?->client?->company_name ?? '';
                                    }
                                    return '';
                                })() . ' - ' . $costItem->description . ' - ' . $record->invoice_number,
                                'cash_reference_id' => $cashReferenceId,
                                'mou_id' => $record->mou_id,
                                'coa_id' => $costItem->coa_id,
                                'invoice_id' => $record->id,
                                'cost_list_invoice_id' => $costItem->id,
                                'type' => 'debit',
                                'debit_amount' => $costItem->amount,
                                'credit_amount' => 0,
                                'transaction_date' => $data['tgl_transfer'],
                                'sort_order' => $nextSortOrder,
                            ]);

                            $nextSortOrder++;

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
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation(),
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
