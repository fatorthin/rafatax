<?php

namespace App\Filament\App\Resources\InvoiceResource\Pages;

use App\Models\Coa;
use App\Models\Invoice;
use App\Models\Client;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CostListInvoice;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use App\Filament\App\Resources\InvoiceResource;
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

    protected static string $view = 'filament.app.resources.invoice-resource.pages.list-cost-invoice';

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
                            ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                    ])
                    ->columns(3)
            ]);
    }

    public function table(Table $table): Table
    {
        $isPaid = $this->invoice->invoice_status === 'paid';

        return $table
            ->query(fn() => CostListInvoice::where('invoice_id', $this->invoice->id))
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('coa.name')->label('CoA'),
                TextColumn::make('description')->label('Description'),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn($state) => number_format((float) $state, 0, ',', '.'))
                    ->summarize(Sum::make()->label('Total Amount')->numeric(
                        decimalPlaces: 0,
                        thousandsSeparator: '.',
                        decimalSeparator: ','
                    ))
                    ->alignEnd(),
            ])
            ->paginated(false)
            ->filters([
                //
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make()
                    ->url(fn(CostListInvoice $record): string => '/app/cost-list-invoices/' . $record->id . '/edit')
                    ->visible(!$isPaid),
                \Filament\Tables\Actions\DeleteAction::make()
                    ->visible(!$isPaid),
            ])
            ->bulkActions([
                //
            ]);
    }

    protected function getHeaderActions(): array
    {
        $isPaid = $this->invoice->invoice_status === 'paid';

        return [
            Actions\Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('secondary')
                ->url(fn(): string => route('invoices.pdf', ['id' => $this->invoice->id]))
                ->openUrlInNewTab(true),

            Actions\Action::make('edit_invoice')
                ->label('Edit Invoice')
                ->icon('heroicon-o-pencil')
                ->color('warning')
                ->url(fn() => InvoiceResource::getUrl('edit', ['record' => $this->invoice->id])),

            Actions\Action::make('send_whatsapp')
                ->label('Kirim Invoice')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->action(function () {
                    // Get the mou related to this invoice
                    $mou = $this->invoice->mou;

                    if (!$mou) {
                        $this->notify('error', 'No MoU associated with this invoice!');
                        return;
                    }

                    // Get client from the mou
                    $client = $mou->client;

                    if (!$client || !$client->phone) {
                        $this->notify('error', 'Client phone number not found!');
                        return;
                    }

                    // Clean phone number (remove spaces, dashes, etc)
                    $phone = preg_replace('/[^0-9]/', '', $client->phone);

                    // Add country code if needed
                    if (substr($phone, 0, 1) === '0') {
                        $phone = '62' . substr($phone, 1);
                    } elseif (substr($phone, 0, 2) !== '62') {
                        $phone = '62' . $phone;
                    }

                    // Calculate total amount
                    $totalAmount = CostListInvoice::where('invoice_id', $this->invoice->id)
                        ->sum('amount');

                    // Format as IDR
                    $formattedAmount = number_format($totalAmount, 0, ',', '.');

                    // Create WhatsApp message
                    $message = "Halo {$client->name},\n\n";
                    $message .= "Ini adalah invoice untuk layanan kami:\n";
                    $message .= "No. Invoice: {$this->invoice->invoice_number}\n";
                    $message .= "Tanggal: {$this->invoice->invoice_date}\n";
                    $message .= "Jatuh Tempo: {$this->invoice->due_date}\n";
                    $message .= "Total: Rp {$formattedAmount}\n\n";
                    $message .= "Terima kasih atas kerjasamanya.";

                    // Encode message for URL
                    $encodedMessage = urlencode($message);

                    // Create WhatsApp URL
                    $whatsappUrl = "https://wa.me/{$phone}?text={$encodedMessage}";

                    // Redirect to WhatsApp
                    return redirect()->away($whatsappUrl);
                }),
            Actions\CreateAction::make()
                ->label('Add Cost List')
                ->url(fn(): string => '/app/cost-list-invoices/create?invoice_id=' . $this->invoice->id)
                ->visible(!$isPaid),
            Actions\Action::make('back')
                ->label('Back to Invoice List')
                ->url('/app/invoices')
                ->color('info')
                ->icon('heroicon-o-arrow-left'),
        ];
    }
}
