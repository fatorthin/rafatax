<?php

namespace App\Filament\App\Resources\MouResource\Widgets;

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

        // Calculate total here for footer by using the invoices in the query
        if ($this->mouId) {
            $invoiceIds = $query->clone()->pluck('id')->toArray();
            $this->totalValue = CostListInvoice::whereIn('invoice_id', $invoiceIds)
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
                    ->label('Edit')
                    ->icon('heroicon-o-eye')
                    ->color('primary'),
                Tables\Actions\ViewAction::make()
                    // ->url(fn(Invoice $record): string => InvoiceResource::getUrl('cost-list', ['record' => $record]))
                    ->url(fn(Invoice $record): string => route('filament.app.resources.invoices.viewCostList', ['record' => $record->id]))
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('info'),
                Tables\Actions\Action::make('previewPdf')
                    ->label('Preview')
                    ->icon('heroicon-o-printer')
                    ->url(fn(Invoice $record): string => route('invoices.preview', ['id' => $record->id]))
                    ->color('success')
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('send_whatsapp')
                    ->label('Kirim WA')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('phone_number')
                            ->label('WhatsApp Number')
                            ->required()
                            ->default(function (Invoice $record) {
                                if ($record->client && $record->client->phone) {
                                    return $record->client->phone;
                                }
                                $mou = $record->mou;
                                if ($mou && $mou->client) {
                                    return $mou->client->phone;
                                }
                                return null;
                            })
                            ->helperText('Format: 08123456789 or 628123456789'),
                    ])
                    ->modalHeading('Kirim Invoice via WhatsApp')
                    ->modalDescription('Pastikan nomor WhatsApp sudah benar sebelum mengirim.')
                    ->modalSubmitActionLabel('Ya, Kirim')
                    ->action(function (Invoice $record, array $data) {
                        try {
                            $phoneInput = $data['phone_number'];

                            if (empty($phoneInput)) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Phone number is required!')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Clean phone number
                            $phone = preg_replace('/[^0-9]/', '', $phoneInput);
                            if (substr($phone, 0, 1) === '0') {
                                $phone = '62' . substr($phone, 1);
                            } elseif (substr($phone, 0, 2) !== '62') {
                                $phone = '62' . $phone;
                            }

                            // Determine Client Name
                            $clientName = '-';
                            if ($record->memo_id && !$record->client_id) {
                                $clientName = $record->memo?->nama_klien ?? '-';
                            } elseif ($record->memo_id && $record->client_id) {
                                $clientName = $record->client?->company_name ?? '-';
                            } elseif ($record->mou_id && !$record->memo_id && !$record->client_id) {
                                $clientName = $record->mou?->client?->company_name ?? '-';
                            } elseif ($record->client_id) {
                                $clientName = $record->client?->company_name ?? '-';
                            }

                            // Calculate total amount
                            $totalAmount = CostListInvoice::where('invoice_id', $record->id)->sum('amount');
                            $formattedAmount = number_format($totalAmount, 0, ',', '.');

                            // Determine Type for Signature and Bank Details
                            $type = $record->invoice_type
                                ?? optional($record->mou)->type
                                ?? optional($record->memo)->tipe_klien;

                            $typeNormalized = is_string($type) ? strtolower(trim($type)) : '';
                            $isKkp = $typeNormalized === 'kkp';

                            $bankDetails = $isKkp
                                ? "Bank: BCA\nNo. Rekening: 785-1135-425\nAtas nama: Antin Okfitasari"
                                : "Bank: BCA\nNo. Rekening: 785-1260-513\nAtas nama: Aghnia Oasis Konsultindo PT";

                            $dueDate = $record->due_date
                                ? \Carbon\Carbon::parse($record->due_date)->translatedFormat('d F Y')
                                : '-';

                            // Create WhatsApp message
                            $message = "Yth. Bapak/Ibu {$clientName}\n";
                            $message .= "Kami dari Tim Admin RAFATAX Consulting bersama ini mengirimkan Invoice Tagihan.\n\n";
                            $message .= "No Invoice    : {$record->invoice_number}\n";
                            $message .= "Jumlah          : Rp {$formattedAmount}\n";
                            $message .= "Jatuh Tempo: {$dueDate}\n\n";
                            $message .= "Transfer ke: {$bankDetails}\n\n";
                            $message .= "Note:\n";
                            $message .= "1. Cantumkan nomor invoice di kolom \"catatan\" saat proses transfer.\n";
                            $message .= "2. Konfirmasi pembayaran dengan mengirim bukti transfer ke Nomor Admin (+62 813 5997 6015)\n";
                            $message .= "3. Bayar tepat waktu untuk menghindari penghentian layanan kami.\n\n";
                            $message .= "Mohon dapat menjadi periksa & dijadwalkan pembayarannya\n";
                            $message .= "Terima kasih\n";
                            $message .= "Admin Rafatax Consulting";

                            /** @var \App\Services\WablasService $wablasService */
                            $wablasService = app(\App\Services\WablasService::class);

                            // 1. Send Text Message
                            $wablasService->sendMessage($phone, $message);

                            // 2. Generate PDF using DOMPDF
                            $costLists = CostListInvoice::where('invoice_id', $record->id)->get();

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

                            $headerImageBase64 = '';
                            if ($headerImageFile) {
                                $headerImagePath = public_path('images/' . $headerImageFile);
                                if (file_exists($headerImagePath)) {
                                    $headerImageBase64 = $this->optimizeImageHelper($headerImagePath, 600);
                                }
                            }

                            $signatureImageBase64 = '';
                            $signatureImagePath = public_path('images/spesimen-kasir.png');
                            if (file_exists($signatureImagePath)) {
                                $signatureImageBase64 = $this->optimizeImageHelper($signatureImagePath, 250);
                            }

                            $viewData = [
                                'invoice' => $record,
                                'costLists' => $costLists,
                                'headerImage' => $headerImageBase64,
                                'signatureImage' => $signatureImageBase64,
                            ];

                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $viewData)
                                ->setPaper('a4', 'portrait')
                                ->setOption(['compress' => 1]);

                            $tempDir = storage_path('app/temp');
                            if (!file_exists($tempDir)) {
                                mkdir($tempDir, 0755, true);
                            }

                            $companyNameClean = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $clientName);
                            $invoiceNumberClean = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $record->invoice_number ?? $record->id);
                            $filename = 'invoice-(' . $companyNameClean . ')' . $invoiceNumberClean . '.pdf';
                            $tempPath = $tempDir . '/' . $filename;

                            $pdf->save($tempPath);

                            // 3. Send Document
                            $sendResult = $wablasService->sendDocument($phone, $tempPath);

                            if (file_exists($tempPath)) {
                                unlink($tempPath);
                            }

                            if (isset($sendResult['status']) && $sendResult['status']) {
                                $record->update([
                                    'is_send_invoice' => true,
                                    'send_invoice_date' => now()->toDateString(),
                                ]);

                                Notification::make()
                                    ->title('Berhasil')
                                    ->body('Invoice berhasil dikirim via WhatsApp (PDF).')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Warning')
                                    ->body('Pesan teks terkirim, namun pengiriman dokumen PDF mungkin gagal.')
                                    ->warning()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body('Gagal mengirim WhatsApp: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('updateStatusBayar')
                    ->label('Update Status Bayar')
                    ->icon('heroicon-o-check-circle')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Update Status Bayar')
                    ->modalDescription('Pilih rekening transfer untuk menandai invoice sebagai Paid.')
                    ->form([
                        Forms\Components\DatePicker::make('tgl_transfer')
                            ->label('Tanggal Transfer')
                            ->default(now())
                            ->required(),
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
                    ->label('Delete')
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

    protected function optimizeImageHelper($path, $maxWidth)
    {
        if (!file_exists($path)) {
            return '';
        }

        list($width, $height, $type) = getimagesize($path);

        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($path);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($path);
                break;
            default:
                return 'data:image/png;base64,' . base64_encode(file_get_contents($path));
        }

        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = ($height / $width) * $newWidth;
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }

        $destination = imagecreatetruecolor($newWidth, $newHeight);

        if ($type == IMAGETYPE_PNG) {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
            imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        if ($type == IMAGETYPE_PNG) {
            imagepng($destination, null, 8);
            $mime = 'image/png';
        } else {
            imagejpeg($destination, null, 75);
            $mime = 'image/jpeg';
        }
        $contents = ob_get_clean();

        imagedestroy($source);
        imagedestroy($destination);

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    public static function canView(): bool
    {
        return true;
    }
}
