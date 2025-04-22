<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Models\Coa;
use App\Models\Invoice;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CostListInvoice;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use App\Filament\Resources\InvoiceResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Actions;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Concerns\InteractsWithInfolists;

class ListCostInvoice extends Page implements HasTable, HasForms, HasInfolists
{
    use InteractsWithTable;
    use InteractsWithForms;
    use InteractsWithInfolists;

    protected static string $resource = InvoiceResource::class;

    protected static string $view = 'filament.resources.invoice-resource.pages.list-cost-invoice';

    public Invoice $invoice;

    public $cost_lists;

    public function mount($record): void
    {
        $this->invoice = Invoice::findOrFail($record);
        $this->cost_lists = CostListInvoice::where('invoice_id', $record)->get();
    }

    public function getTitle(): string
    {
        return 'Detail Invoice #' . $this->invoice->invoice_number;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->invoice)
            ->schema([
                Section::make('Invoice Information')
                    ->schema([
                        TextEntry::make('invoice_number')   
                            ->label('Invoice Number')
                            ->weight('bold'),
                        TextEntry::make('mou.mou_number')
                            ->label('MoU Number')
                            ->weight('bold'),
                        TextEntry::make('invoice_date')
                            ->label('Invoice Date')
                            ->weight('bold')
                            ->date(),
                        TextEntry::make('due_date')
                            ->label('Due Date')
                            ->weight('bold')
                            ->date(),
                        TextEntry::make('invoice_status')
                            ->label('Status')
                            ->weight('bold')
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                    ])
                    ->columns(3)
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn() => CostListInvoice::where('invoice_id', $this->invoice->id))
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('coa.name')->label('CoA'),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('IDR')
                    ->summarize(Sum::make()->label('Total Amount')),
                TextColumn::make('description')->label('Description'),
            ])
            ->paginated(false)
            ->filters([
                //
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make()
                    ->url(fn (CostListInvoice $record): string => InvoiceResource::getUrl('cost-edit', ['record' => $record->id])),
                \Filament\Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Cost List')
                ->url(fn(): string => InvoiceResource::getUrl('cost-create', ['record' => $this->invoice->id])),
            Actions\Action::make('back')
                ->label('Back to Invoice List')
                ->url(InvoiceResource::getUrl('index'))
                ->color('info')
                ->icon('heroicon-o-arrow-left'),
            Actions\Action::make('backToMou')
                ->label('Back to MoU')
                ->url(fn(): string => '/admin/mous/' . $this->invoice->mou_id . '/cost-list')
                ->color('danger')
                ->icon('heroicon-o-arrow-left'),
        ];
    }
}           