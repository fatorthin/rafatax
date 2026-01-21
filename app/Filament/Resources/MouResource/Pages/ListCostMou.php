<?php

namespace App\Filament\Resources\MouResource\Pages;

use App\Models\Coa;
use App\Models\MoU;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CostListMou;
use App\Models\Invoice;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Contracts\HasForms;
use App\Filament\Resources\MouResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Actions;
use Filament\Actions\Action;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\MouResource\Widgets\MouStatsOverview;

class ListCostMou extends Page implements HasTable, HasForms, HasInfolists
{
    use InteractsWithTable;
    use InteractsWithForms;
    use InteractsWithInfolists;

    protected static string $resource = MouResource::class;

    protected static ?string $model = CostListMou::class;

    protected static string $view = 'filament.resources.mou-resource.pages.list-cost-mou';

    public MoU $mou;

    public $cost_lists;

    public $invoices;

    public $pendingChecklistIds = [];

    public function mount($record): void
    {
        $this->mou = MoU::findOrFail($record);

        // Self-healing: Reset checklist items linked to deleted invoices
        \App\Models\ChecklistMou::where('mou_id', $this->mou->id)
            ->whereNotNull('invoice_id')
            ->whereDoesntHave('invoice')
            ->update(['invoice_id' => null, 'status' => 'pending']);

        $this->cost_lists = CostListMou::where('mou_id', $record)->get();
        $this->invoices = Invoice::where('mou_id', $record)->get();
    }

    public function getTitle(): string
    {
        return 'Detail MoU #' . $this->mou->id;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MouStatsOverview::make([
                'mouId' => $this->mou->id,
            ]),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->mou)
            ->schema([
                Section::make('MoU Information')
                    ->schema([
                        TextEntry::make('mou_number')
                            ->label('MoU Number')
                            ->weight('bold'),
                        TextEntry::make('client.company_name')
                            ->label('Client')
                            ->weight('bold'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->weight('bold')
                            ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                        TextEntry::make('type')
                            ->label('Type')
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                'pt' => 'PT',
                                'kkp' => 'KKP',
                            })
                            ->weight('bold'),
                        TextEntry::make('start_date')
                            ->label('Start Date')
                            ->weight('bold')
                            ->dateTime('d F Y'),
                        TextEntry::make('end_date')
                            ->label('End Date')
                            ->weight('bold')
                            ->dateTime('d F Y'),
                        TextEntry::make('client.contact_person')
                            ->label('Contact Person')
                            ->weight('bold'),
                        TextEntry::make('client.phone')
                            ->label('Contact Number')
                            ->weight('bold'),
                        TextEntry::make('categoryMou.name')
                            ->label('Category MoU')
                            ->weight('bold'),
                    ])
                    ->columns(3)
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn() => CostListMou::where('mou_id', $this->mou->id))
            ->heading('Cost List')
            ->description('Detail biaya untuk MoU ini')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('coa.name')->label('CoA'),
                TextColumn::make('description')->label('Description'),
                TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric(),
                TextColumn::make('satuan_quantity')
                    ->label('Satuan'),
                TextColumn::make('amount')
                    ->label('Price')
                    ->formatStateUsing(fn(string $state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->alignEnd(),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->formatStateUsing(fn(string $state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->summarize(Sum::make()->label('Total Amount')),
            ])
            ->paginated(false)
            ->filters([
                //
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make()
                    ->fillForm(function (CostListMou $record): array {
                        return [
                            'coa_id' => $record->coa_id,
                            'description' => $record->description,
                            'quantity' => $record->quantity,
                            'satuan_quantity' => $record->satuan_quantity,
                            'amount' => $record->amount,
                            'total_amount' => $record->total_amount,
                        ];
                    })
                    ->form([
                        Select::make('coa_id')
                            ->label('CoA')
                            ->options(Coa::whereIn('id', [119, 120, 121, 122, 123, 124, 125, 126])->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('quantity')
                            ->label('Qty')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $price = $get('amount');
                                $set('total_amount', floatval($state) * floatval($price));
                            }),
                        TextInput::make('satuan_quantity')
                            ->label('Satuan'),
                        TextInput::make('amount')
                            ->label('Price')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $qty = $get('quantity') ?? 1;
                                $set('total_amount', floatval($state) * floatval($qty));
                            }),
                        TextInput::make('total_amount')
                            ->label('Total')
                            ->numeric()
                            ->readOnly(),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                    ])
                    ->modalWidth('lg'),
                \Filament\Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit MoU')
                ->icon('heroicon-o-pencil')
                ->record($this->mou)
                ->form(fn(Form $form) => MouResource::form($form)->getComponents())
                ->modalWidth('7xl')
                ->color('danger')
                ->successRedirectUrl(fn() => MouResource::getUrl('viewCostList', ['record' => $this->mou])),
            Actions\CreateAction::make()
                ->label('Add Cost List')
                ->color('info')
                ->icon('heroicon-o-plus')
                ->model(CostListMou::class)
                ->form([
                    Select::make('coa_id')
                        ->label('CoA')
                        ->options(Coa::whereIn('id', [119, 120, 121, 122, 123, 124, 125, 126])->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    TextInput::make('quantity')
                        ->label('Qty')
                        ->numeric()
                        ->default(1)
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $price = $get('amount');
                            $set('total_amount', floatval($state) * floatval($price));
                        }),
                    TextInput::make('satuan_quantity')
                        ->label('Satuan'),
                    TextInput::make('amount')
                        ->label('Price')
                        ->numeric()
                        ->required()
                        ->prefix('Rp')
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $qty = $get('quantity') ?? 1;
                            $set('total_amount', floatval($state) * floatval($qty));
                        }),
                    TextInput::make('total_amount')
                        ->label('Total')
                        ->numeric()
                        ->readOnly(),
                    Textarea::make('description')
                        ->label('Description')
                        ->rows(3),
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    $data['mou_id'] = $this->mou->id;
                    return $data;
                })
                ->modalWidth('lg'),
            Actions\Action::make('preview_pdf')
                ->label('Preview PDF')
                ->icon('heroicon-o-eye')
                ->color('warning')
                ->url(fn() => route('mou.pdf.preview', ['id' => $this->mou->id]))
                ->openUrlInNewTab(),
            Actions\Action::make('export_pdf')
                ->label('Print PDF MoU')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn() => route('mou.print.view', ['id' => $this->mou->id]))
                ->openUrlInNewTab(),
            Action::make('back')
                ->label('Back to MoU List')
                ->url(MouResource::getUrl('index'))
                ->color('primary')
                ->icon('heroicon-o-arrow-left'),
            Actions\CreateAction::make('create_invoice')
                ->label('Create Invoice')
                ->color('secondary')
                ->model(Invoice::class)
                ->icon('heroicon-o-document-plus')
                ->form([
                    \Filament\Forms\Components\Hidden::make('mou_id')
                        ->default($this->mou->id),
                    TextInput::make('invoice_number')
                        ->label('Invoice Number')
                        ->required()
                        ->readOnly()
                        ->unique(
                            'invoices',
                            'invoice_number',
                            modifyRuleUsing: function ($rule) {
                                return $rule->whereNull('deleted_at');
                            }
                        )
                        ->suffixAction(
                            \Filament\Forms\Components\Actions\Action::make('regenerate_number')
                                ->icon('heroicon-o-arrow-path')
                                ->tooltip('Regenerate Invoice Number')
                                ->action(function (\Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                                    InvoiceResource::generateInvoiceNumber($set, $get);
                                })
                        ),
                    DatePicker::make('invoice_date')
                        ->label('Tanggal Invoice')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->live()
                        ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                            if ($state) {
                                $dueDate = \Carbon\Carbon::parse($state)->addWeeks(3)->toDateString();
                                $set('due_date', $dueDate);
                            }
                            InvoiceResource::generateInvoiceNumber($set, $get);
                        })
                        ->afterStateHydrated(function (\Filament\Forms\Set $set, \Filament\Forms\Get $get, $state) {
                            InvoiceResource::generateInvoiceNumber($set, $get);
                        }),
                    DatePicker::make('due_date')
                        ->label('Tanggal Jatuh Tempo')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->required(),
                    Select::make('invoice_status')
                        ->label('Status')
                        ->options([
                            'unpaid' => 'Unpaid',
                            'paid' => 'Paid',
                        ])
                        ->default('unpaid')
                        ->required(),
                    Select::make('invoice_type')
                        ->label('Type')
                        ->options([
                            'pt' => 'PT',
                            'kkp' => 'KKP',
                        ])
                        ->required()
                        ->default(fn() => $this->mou->type)
                        ->live()
                        ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                            InvoiceResource::generateInvoiceNumber($set, $get);
                        }),
                    Select::make('rek_transfer')
                        ->label('Rekening Transfer')
                        ->options([
                            'BCA PT' => 'BCA PT',
                            'BCA BARU' => 'BCA BARU',
                            'BCA LAMA' => 'BCA LAMA',
                            'MANDIRI' => 'MANDIRI'
                        ]),
                    Textarea::make('description')
                        ->label('Description')
                        ->rows(3),
                    \Filament\Forms\Components\Section::make('Rincian Biaya')
                        ->schema([
                            \Filament\Forms\Components\Repeater::make('costListInvoices')
                                ->relationship()
                                ->schema([
                                    \Filament\Forms\Components\Hidden::make('mou_id')
                                        ->default($this->mou->id),
                                    Select::make('coa_id')
                                        ->label('CoA')
                                        ->options(Coa::where('group_coa_id', '40')->orWhere('id', '162')->pluck('name', 'id'))
                                        ->required()
                                        ->searchable()
                                        ->columnSpan([
                                            'md' => 3,
                                        ]),
                                    TextInput::make('description')
                                        ->label('Deskripsi')
                                        ->required()
                                        ->columnSpan([
                                            'md' => 4,
                                        ]),
                                    TextInput::make('amount')
                                        ->label('Harga')
                                        ->numeric()
                                        ->required()
                                        ->columnSpan([
                                            'md' => 5,
                                        ]),
                                ])
                                ->columns([
                                    'md' => 12,
                                ])
                                ->defaultItems(0)
                                ->reorderableWithButtons()
                                ->collapsible()
                                ->itemLabel(fn(array $state): ?string => $state['description'] ?? null),
                        ]),
                    \Filament\Forms\Components\CheckboxList::make('checklist_mou_ids')
                        ->label('Checklist Invoice (Pilih Periode yang akan ditagihkan)')
                        ->options(function () {
                            return \App\Models\ChecklistMou::where('mou_id', $this->mou->id)
                                ->whereNull('invoice_id')
                                ->orderBy('checklist_date', 'asc')
                                ->get()
                                ->mapWithKeys(function ($item) {
                                    $date = \Carbon\Carbon::parse($item->checklist_date)->translatedFormat('F Y');
                                    return [$item->id => "Periode: {$date} (" . ($item->notes ?? '-') . ")"];
                                });
                        })
                        ->visible(fn() => in_array($this->mou->category_mou_id, [3, 4]))
                        ->columns(2)
                        ->gridDirection('row')
                        ->bulkToggleable(),
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    $data['mou_id'] = $this->mou->id;
                    $this->pendingChecklistIds = $data['checklist_mou_ids'] ?? [];
                    unset($data['checklist_mou_ids']);
                    // $data['client_id'] = $this->mou->client_id; // Commented out to be safe based on migration check
                    return $data;
                })
                ->after(function ($record) {
                    if (!empty($this->pendingChecklistIds)) {
                        \App\Models\ChecklistMou::whereIn('id', $this->pendingChecklistIds)->update([
                            'invoice_id' => $record->id,
                            'status' => 'completed'
                        ]);
                    }
                    $this->dispatch('invoice-created');
                })
                ->modalWidth('7xl'),
        ];
    }
}
