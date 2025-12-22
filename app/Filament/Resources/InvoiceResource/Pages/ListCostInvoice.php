<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Models\Coa;
use App\Models\Invoice;
use App\Models\Client;
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
                        TextEntry::make('mou.client.company_name')
                            ->label('Client')
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
                    ->url(fn(CostListInvoice $record): string => InvoiceResource::getUrl('cost-edit', ['record' => $record->id]))
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

            Actions\Action::make('download_jpg')
                ->label('Download JPG')
                ->icon('heroicon-o-photo')
                ->color('secondary')
                ->url(fn(): string => route('invoices.jpg', ['id' => $this->invoice->id]))
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
                ->requiresConfirmation()
                ->modalHeading('Kirim Invoice')
                ->modalDescription('Apakah Anda yakin ingin mengirim invoice ini ke klien via WhatsApp?')
                ->modalSubmitActionLabel('Ya, Kirim')
                ->action(function () {
                    try {
                        // Get the mou related to this invoice
                        $mou = $this->invoice->mou;

                        // Check if MoU exists
                        if (!$mou) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body('No MoU associated with this invoice!')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Get client from the mou
                        $client = $mou->client;

                        if (!$client || !$client->phone) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body('Client phone number not found!')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Clean phone number
                        $phone = preg_replace('/[^0-9]/', '', $client->phone);
                        if (substr($phone, 0, 1) === '0') {
                            $phone = '62' . substr($phone, 1);
                        } elseif (substr($phone, 0, 2) !== '62') {
                            $phone = '62' . $phone;
                        }

                        // Calculate total amount
                        $totalAmount = \App\Models\CostListInvoice::where('invoice_id', $this->invoice->id)->sum('amount');
                        $formattedAmount = number_format($totalAmount, 0, ',', '.');

                        // Create WhatsApp message
                        $message = "Yth. {$client->company_name},\n\n";
                        $message .= "Berikut kami lampirkan invoice untuk layanan kami:\n";
                        $message .= "No. Invoice: {$this->invoice->invoice_number}\n";
                        $message .= "Keterangan: {$this->invoice->description}\n";
                        // $message .= "Tanggal: " . \Carbon\Carbon::parse($this->invoice->invoice_date)->translatedFormat('d F Y') . "\n";
                        // $message .= "Jatuh Tempo: " . \Carbon\Carbon::parse($this->invoice->due_date)->translatedFormat('d F Y') . "\n";
                        // $message .= "Total: Rp {$formattedAmount}\n\n";

                        $type = $this->invoice->invoice_type ?? optional($this->invoice->mou)->type;
                        $typeNormalized = is_string($type) ? strtolower(trim($type)) : '';

                        $isKkp = $typeNormalized === 'kkp'; // kkp or pt

                        $bankDetails = $isKkp
                            ? "Bank: BCA\nNo. Rekening: 785-1135-425\nAtas nama: Antin Okfitasari"
                            : "Bank: BCA\nNo. Rekening: 785-1260-513\nAtas nama: Aghnia Oasis Konsultindo PT";

                        $signature = $isKkp
                            ? "Antin Okfitasari - Konsultan Pajak\nGriya Rafa, Jl. Nampan 01, Dusun II\nMadegondo, Grogol, Sukoharjo\nPhone: +62 812-2596-210\nEmail: antin.okfitasari@gmail.com"
                            : "Aghnia Oasis Konsultindo PT\nGriya Rafa, Jl. Nampan 01, Dusun II\nMadegondo, Grogol, Sukoharjo\nPhone: +62 813-5997-6015\nEmail: aghniaoasiskonsultindo@gmail.com";

                        $message .= "Pembayaran dapat dilakukan melalui transfer ke rekening berikut:\n{$bankDetails}\n\n";
                        $message .= "Apabila telah melakukan pembayaran, dimohon untuk mengirim konfirmasi kepada kami dengan melalui nomor ini.\n\n";
                        $message .= "Atas perhatian dan kerjasamanya, kami ucapkan terima kasih.\n\n";
                        $message .= "Best Regards,\nTim Finance\n\n{$signature}\n";

                        /** @var \App\Services\WablasService $wablasService */
                        $wablasService = app(\App\Services\WablasService::class);

                        // 1. Send Text Message
                        $wablasService->sendMessage($phone, $message);

                        // 2. Generate Image using Browsershot
                        $costLists = \App\Models\CostListInvoice::where('invoice_id', $this->invoice->id)->get();

                        // Determine type
                        $type = $this->invoice->invoice_type ?? optional($this->invoice->mou)->type;
                        $typeNormalized = is_string($type) ? strtolower(trim($type)) : '';

                        if ($typeNormalized === 'kkp') {
                            $view = 'invoices.pdf-kkp';
                            $headerImageFile = 'kop-inovice-kkp.png';
                        } elseif ($typeNormalized === 'pt') {
                            $view = 'invoices.pdf-pt';
                            $headerImageFile = 'kop-invoice-pt.png';
                        } else {
                            $view = 'invoices.pdf';
                            $headerImageFile = null;
                        }

                        // Prepare Images
                        $headerImageBase64 = '';
                        if ($headerImageFile) {
                            $headerImagePath = public_path('images/' . $headerImageFile);
                            if (file_exists($headerImagePath)) {
                                $headerImageBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($headerImagePath));
                            }
                        }

                        $signatureImageBase64 = '';
                        $signatureImagePath = public_path('images/spesimen-kasir.png');
                        if (file_exists($signatureImagePath)) {
                            $signatureImageBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($signatureImagePath));
                        }

                        $viewData = [
                            'invoice' => $this->invoice,
                            'costLists' => $costLists,
                            'headerImage' => $headerImageBase64,
                            'signatureImage' => $signatureImageBase64,
                        ];

                        // Render blade view to HTML
                        $html = view($view, $viewData)->render();

                        // Save to temporary file
                        $tempDir = storage_path('app/temp');
                        if (!file_exists($tempDir)) {
                            mkdir($tempDir, 0755, true);
                        }

                        $invoiceNumberClean = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $this->invoice->invoice_number ?? $this->invoice->id);
                        $filename = 'invoice-' . $invoiceNumberClean . '.jpg';
                        $tempPath = $tempDir . '/' . $filename;

                        // Generate Image from HTML
                        \Spatie\Browsershot\Browsershot::html($html)
                            ->setOption('newHeadless', true)
                            ->windowSize(800, 1000) // Smaller width = larger relative text
                            ->deviceScaleFactor(2) // High resolution
                            ->fullPage() // Capture only the content
                            ->save($tempPath);

                        // 3. Send Image
                        $sendResult = $wablasService->sendImage($phone, $tempPath);

                        // Clean up
                        if (file_exists($tempPath)) {
                            unlink($tempPath);
                        }

                        if (isset($sendResult['status']) && $sendResult['status']) {
                            \Filament\Notifications\Notification::make()
                                ->title('Success')
                                ->body('Invoice sent successfully via WhatsApp (Image).')
                                ->success()
                                ->send();
                        } else {
                            // Fallback to PDF if Image fails (Optional, but good for robustness)
                            \Filament\Notifications\Notification::make()
                                ->title('Warning')
                                ->body('Message sent, but failed to send Image. Result: ' . ($sendResult['message'] ?? 'Unknown'))
                                ->warning()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error')
                            ->body('Failed to send WhatsApp: ' . $e->getMessage())
                            ->danger()
                            ->send();
                        \Illuminate\Support\Facades\Log::error($e);
                    }
                }),
            Actions\CreateAction::make()
                ->label('Add Cost List')
                ->url(fn(): string => InvoiceResource::getUrl('cost-create', ['record' => $this->invoice->id]))
                ->visible(!$isPaid),
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
