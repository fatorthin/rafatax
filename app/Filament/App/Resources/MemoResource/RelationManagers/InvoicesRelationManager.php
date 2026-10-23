<?php

namespace App\Filament\App\Resources\MemoResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('memo_id')
                    ->default(function ($livewire) {
                        return $livewire->ownerRecord->id;
                    }),
                Forms\Components\TextInput::make('invoice_number')
                    ->required()
                    ->maxLength(255)
                    ->readOnly()
                    ->unique(
                        \App\Models\Invoice::class,
                        'invoice_number',
                        ignoreRecord: true,
                        modifyRuleUsing: function ($rule) {
                            return $rule->whereNull('deleted_at');
                        }
                    )
                    ->suffixAction(
                        Forms\Components\Actions\Action::make('refresh_invoice_number')
                            ->icon('heroicon-o-arrow-path')
                            ->action(function (Forms\Set $set, Forms\Get $get) {
                                \App\Filament\App\Resources\InvoiceResource::generateInvoiceNumber($set, $get);
                            })
                    ),
                Forms\Components\Select::make('invoice_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'paid' => 'Paid',
                    ])
                    ->default('unpaid')
                    ->required(),
                Forms\Components\Select::make('invoice_type')
                    ->options([
                        'pt' => 'PT',
                        'kkp' => 'KKP',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                        \App\Filament\App\Resources\InvoiceResource::generateInvoiceNumber($set, $get);
                    }),
                Forms\Components\DatePicker::make('invoice_date')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        if ($state) {
                            $dueDate = date('Y-m-d', strtotime($state . ' + 3 weeks'));
                            $set('due_date', $dueDate);
                        }
                        \App\Filament\App\Resources\InvoiceResource::generateInvoiceNumber($set, $get);
                    }),
                Forms\Components\DatePicker::make('due_date')
                    ->required(),
                Forms\Components\Checkbox::make('is_saldo_awal')
                    ->label('Checklist Invoice Saldo Awal')
                    ->default(false)
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                        \App\Filament\App\Resources\InvoiceResource::generateInvoiceNumber($set, $get);
                    }),

                Forms\Components\TextInput::make('description')
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Select::make('client_id')
                    ->label('Client')
                    ->options(\App\Models\Client::where('status', 'active')->pluck('company_name', 'id'))
                    ->searchable()
                    ->preload()
                    ->visible(fn($livewire): bool => (bool) ($livewire->ownerRecord?->is_memo_talangan))
                    ->required(fn($livewire): bool => (bool) ($livewire->ownerRecord?->is_memo_talangan))
                    ->dehydrated(fn($livewire): bool => (bool) ($livewire->ownerRecord?->is_memo_talangan)),
                Forms\Components\Repeater::make('costListInvoices')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('coa_id')
                            ->label('CoA')
                            ->options(\App\Models\Coa::where('group_coa_id', '40')->orWhere('id', '162')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->columnSpan(['md' => 4]),
                        Forms\Components\TextInput::make('description')
                            ->label('Deskripsi')
                            ->columnSpan(['md' => 4]),
                        Forms\Components\TextInput::make('amount')
                            ->label('Harga')
                            ->numeric()
                            ->required()
                            ->columnSpan(['md' => 4]),
                    ])
                    ->columns(['md' => 12])
                    ->defaultItems(0)
                    ->reorderableWithButtons()
                    ->collapsible()
                    ->itemLabel(fn(array $state): ?string => $state['description'] ?? null)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice_type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pt' => 'info',
                        'kkp' => 'success',
                    }),
                Tables\Columns\SelectColumn::make('invoice_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'paid' => 'Paid',
                    ]),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric(locale: 'id')
                    ->prefix('Rp ')
                    ->getStateUsing(function ($record) {
                        return $record->costListInvoices()->sum('amount');
                    }),
                Tables\Columns\TextColumn::make('due_date')
                    ->date('d/m/Y'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Invoice')
                    ->icon('heroicon-o-plus'),
            ])
            ->actions([
                Tables\Actions\Action::make('preview_pdf')
                    ->label('Preview PDF')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('success')
                    ->form(function ($record) {
                        $record->loadMissing('memo');
                        $memo = $record->memo;
                        return [
                            Forms\Components\Radio::make('recipient_type')
                                ->label('Pilih Nama Kepada:')
                                ->options([
                                    'nama_klien' => 'Nama Klien: ' . ($memo?->nama_klien ?: '-'),
                                    'instansi_klien' => 'Instansi Klien: ' . ($memo?->instansi_klien ?: '-'),
                                ])
                                ->default('nama_klien')
                                ->required(),
                        ];
                    })
                    ->modalHeading('Preview PDF Invoice')
                    ->modalSubmitActionLabel('Preview PDF')
                    ->action(function ($record, array $data, Tables\Actions\Action $action) {
                        $recipient = $data['recipient_type'] ?? 'nama_klien';
                        $url = route('invoices.preview', [
                            'id' => $record->id,
                            'recipient' => $recipient,
                        ]);
                        $action->getLivewire()->js("window.open('{$url}', '_blank');");
                    }),
                Tables\Actions\Action::make('download_jpeg')
                    ->label('Download JPEG')
                    ->icon('heroicon-o-photo')
                    ->color('warning')
                    ->form(function ($record) {
                        $record->loadMissing('memo');
                        $memo = $record->memo;
                        return [
                            Forms\Components\Radio::make('recipient_type')
                                ->label('Pilih Nama Kepada:')
                                ->options([
                                    'nama_klien' => 'Nama Klien: ' . ($memo?->nama_klien ?: '-'),
                                    'instansi_klien' => 'Instansi Klien: ' . ($memo?->instansi_klien ?: '-'),
                                ])
                                ->default('nama_klien')
                                ->required(),
                        ];
                    })
                    ->modalHeading('Download JPEG Invoice')
                    ->modalSubmitActionLabel('Download JPEG')
                    ->action(function ($record, array $data, Tables\Actions\Action $action) {
                        $recipient = $data['recipient_type'] ?? 'nama_klien';
                        $url = route('invoices.jpg', [
                            'id' => $record->id,
                            'recipient' => $recipient,
                        ]);
                        $action->getLivewire()->js("window.open('{$url}', '_blank');");
                    }),
                Tables\Actions\Action::make('send_whatsapp')
                    ->label('Kirim WA')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->form(function ($record) {
                        $record->loadMissing('memo');
                        $memo = $record->memo;
                        $defaultPhone = null;
                        if ($record->client && $record->client->phone) {
                            $defaultPhone = $record->client->phone;
                        } elseif ($memo && !empty($memo->telepon_klien ?? null)) {
                            $defaultPhone = $memo->telepon_klien;
                        }

                        return [
                            Forms\Components\Radio::make('recipient_type')
                                ->label('Pilih Nama Kepada:')
                                ->options([
                                    'nama_klien' => 'Nama Klien: ' . ($memo?->nama_klien ?: '-'),
                                    'instansi_klien' => 'Instansi Klien: ' . ($memo?->instansi_klien ?: '-'),
                                ])
                                ->default('nama_klien')
                                ->required(),
                            Forms\Components\TextInput::make('phone_number')
                                ->label('Nomor WhatsApp')
                                ->required()
                                ->default($defaultPhone)
                                ->helperText('Format: 08123456789 atau 628123456789'),
                        ];
                    })
                    ->modalHeading('Kirim Invoice via WhatsApp')
                    ->modalDescription('Pilih nama kepada dan pastikan nomor WhatsApp sudah benar sebelum mengirim.')
                    ->modalSubmitActionLabel('Ya, Kirim')
                    ->action(function ($record, array $data) {
                        try {
                            $phoneInput = $data['phone_number'];

                            if (empty($phoneInput)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Error')
                                    ->body('Nomor WhatsApp wajib diisi!')
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

                            $recipientType = $data['recipient_type'] ?? 'nama_klien';
                            $record->loadMissing('memo');
                            $memo = $record->memo;
                            $recipientName = ($recipientType === 'instansi_klien')
                                ? ($memo?->instansi_klien ?: $memo?->nama_klien)
                                : ($memo?->nama_klien ?: $memo?->instansi_klien);

                            // Dispatch background job for fast URL-based delivery
                            \App\Jobs\SendInvoiceWhatsapp::dispatch($record->id, $phone, $recipientName);

                            \Filament\Notifications\Notification::make()
                                ->title('Pengiriman Dijadwalkan')
                                ->body('Invoice sedang diproses dan akan dikirim ke WhatsApp client melalui antrean latar belakang.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body('Gagal mengirim WhatsApp: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('view_details')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn($record) => route('filament.app.resources.invoices.viewCostList', ['record' => $record->id]))
                    ->color('info'),
                Tables\Actions\EditAction::make()
                    ->visible(true),
                Tables\Actions\DeleteAction::make()
                    ->visible(true),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
