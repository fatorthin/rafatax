<?php

namespace App\Filament\Resources\MemoResource\Pages;

use App\Filament\Resources\MemoResource;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;

class ViewMemo extends ViewRecord
{
    protected static string $resource = MemoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview_pdf')
                ->label('Preview PDF')
                ->icon('heroicon-o-eye')
                ->url(fn($record) => route('memos.pdf', ['id' => $record->id]))
                ->openUrlInNewTab(),
            Actions\Action::make('create_invoice')
                ->label('Create Invoice')
                ->icon('heroicon-o-plus')
                ->form([
                    TextInput::make('invoice_number')
                        ->label('Invoice Number')
                        ->required()
                        ->readOnly()
                        ->unique(Invoice::class, 'invoice_number'),
                    Select::make('invoice_status')
                        ->options([
                            'unpaid' => 'Unpaid',
                            'paid' => 'Paid',
                        ])
                        ->default('unpaid')
                        ->required(),
                    Select::make('invoice_type')
                        ->options([
                            'pt' => 'PT',
                            'kkp' => 'KKP',
                        ])
                        ->live()
                        ->afterStateUpdated(function (Actions\Action $action, \Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                            $this->generateInvoiceNumber($set, $get);
                        })
                        ->required(),
                    DatePicker::make('invoice_date')
                        ->required()
                        ->default(now())
                        ->live()
                        ->afterStateUpdated(function (Actions\Action $action, \Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                            $this->generateInvoiceNumber($set, $get);
                        }),
                    DatePicker::make('due_date')
                        ->required()
                        ->default(now()->addDays(30)),
                    \Filament\Forms\Components\Checkbox::make('is_saldo_awal')
                        ->label('Checklist Invoice Saldo Awal')
                        ->default(false)
                        ->live()
                        ->afterStateUpdated(function (Actions\Action $action, \Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                            $this->generateInvoiceNumber($set, $get);
                        }),
                    \Filament\Forms\Components\Repeater::make('cost_lists')
                        ->label('Cost List')
                        ->schema([
                            Select::make('coa_id')
                                ->label('CoA')
                                ->options(\App\Models\Coa::all()->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->columnSpan(['md' => 4]),
                            TextInput::make('description')
                                ->label('Description')
                                ->columnSpan(['md' => 4]),
                            TextInput::make('amount')
                                ->label('Amount')
                                ->numeric()
                                ->prefix('Rp')
                                ->required()
                                ->columnSpan(['md' => 4]),
                        ])
                        ->columns(['md' => 12])
                        ->defaultItems(1)
                        ->required(),
                    TextInput::make('description')
                        ->label('Description')
                        ->default(fn($record) => $record->description),
                ])
                ->action(function (array $data, $record) {
                    $invoice = $record->invoices()->create([
                        'invoice_number' => $data['invoice_number'],
                        'invoice_status' => $data['invoice_status'],
                        'invoice_type' => $data['invoice_type'],
                        'invoice_date' => $data['invoice_date'],
                        'due_date' => $data['due_date'],
                        'description' => $data['description'],
                        'is_saldo_awal' => $data['is_saldo_awal'] ?? false,
                    ]);

                    // Create Cost List items
                    foreach ($data['cost_lists'] as $item) {
                        \App\Models\CostListInvoice::create([
                            'invoice_id' => $invoice->id,
                            'mou_id' => null, // Explicitly null for Memo-based invoices
                            'coa_id' => $item['coa_id'],
                            'description' => $item['description'],
                            'amount' => $item['amount'],
                        ]);
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Invoice Created')
                        ->success()
                        ->send();
                }),
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil'),

            Actions\Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url(MemoResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function generateInvoiceNumber(\Filament\Forms\Set $set, \Filament\Forms\Get $get): void
    {
        $invoiceDate = $get('invoice_date');
        $invoiceType = $get('invoice_type');
        $isSaldoAwal = $get('is_saldo_awal') ?? false;

        if (!$invoiceDate || !$invoiceType) {
            return;
        }

        // 1. Type
        $typeCode = $invoiceType === 'pt' ? 'PT' : 'KKP';

        // 2. Category (Default to LN for Memos)
        $categoryCode = 'LN';

        // 3. Date
        $date = \Carbon\Carbon::parse($invoiceDate);
        $year = $date->year;
        $month = $date->month;

        $romanMonths = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII'
        ];
        $monthRoman = $romanMonths[$month];

        // 4. Sequence
        $lastNumber = 0;

        // Find existing invoices for the same month and year
        $invoices = Invoice::whereYear('invoice_date', $year)
            ->whereMonth('invoice_date', $month)
            ->pluck('invoice_number');

        foreach ($invoices as $inv) {
            $val = 0;
            if (preg_match('/^INV\/(\d+)\//', $inv, $matches)) {
                $val = (int)$matches[1];
            } elseif (preg_match('/^INV\/SA\/(\d+)\//', $inv, $matches)) {
                $val = (int)$matches[1];
            }

            if ($val > $lastNumber) {
                $lastNumber = $val;
            }
        }

        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        // Format
        if ($isSaldoAwal) {
            $result = sprintf('INV/SA/%s/%s/%s/%s/%s', $newNumber, $typeCode, $categoryCode, $monthRoman, $year);
        } else {
            $result = sprintf('INV/%s/%s/%s/%s/%s', $newNumber, $typeCode, $categoryCode, $monthRoman, $year);
        }
        $set('invoice_number', $result);
    }
}
