<?php

namespace App\Filament\App\Resources\MouResource\Pages;

use App\Models\Coa;
use App\Models\MoU;
use Filament\Actions;
use App\Models\Invoice;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CostListMou;
use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Filament\App\Widgets\MouInvoicesTable;
use Filament\Infolists\Components\Section;
use App\Filament\App\Resources\MouResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Infolists\Concerns\InteractsWithInfolists;

class ListCostMou extends Page implements HasTable, HasForms, HasInfolists
{
    use InteractsWithTable;
    use InteractsWithForms;
    use InteractsWithInfolists;

    protected static string $resource = MouResource::class;

    protected static ?string $model = CostListMou::class;

    protected static string $view = 'filament.app.resources.mou-resource.pages.list-cost-mou';

    public MoU $mou;

    public $cost_lists;

    public $invoices;

    public function mount($record): void
    {
        $this->mou = MoU::withTrashed()->findOrFail($record);
        $this->cost_lists = CostListMou::where('mou_id', $record)->get();
        $this->invoices = Invoice::where('mou_id', $record)->get();
    }

    public function getTitle(): string
    {
        return 'Detail MoU #' . $this->mou->mou_number;
    }

    protected function getHeaderWidgets(): array
    {
        return [];
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
                Section::make('Informasi MoU')
                    ->schema([
                        TextEntry::make('mou_number')
                            ->label('Nomor MoU')
                            ->weight('bold'),
                        TextEntry::make('client.company_name')
                            ->label('Klien')
                            ->weight('bold'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->weight('bold')
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                'approved' => 'Disetujui',
                                'unapproved' => 'Belum Disetujui',
                                default => ucfirst($state),
                            }),
                        TextEntry::make('type')
                            ->label('Tipe')
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                'pt' => 'PT',
                                'kkp' => 'KKP',
                                default => $state,
                            })
                            ->weight('bold'),
                        TextEntry::make('start_date')
                            ->label('Tanggal Mulai')
                            ->weight('bold')
                            ->dateTime('d F Y'),
                        TextEntry::make('end_date')
                            ->label('Tanggal Berakhir')
                            ->weight('bold')
                            ->dateTime('d F Y'),
                        TextEntry::make('client.contact_person')
                            ->label('Kontak Person')
                            ->weight('bold'),
                        TextEntry::make('client.phone')
                            ->label('Nomor Telepon')
                            ->weight('bold'),
                        TextEntry::make('categoryMou.name')
                            ->label('Kategori MoU')
                            ->weight('bold'),
                    ])
                    ->columns(3)
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn() => CostListMou::where('mou_id', $this->mou->id))
            ->heading('Daftar Biaya')
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('coa.name')
                    ->label('CoA')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->formatStateUsing(fn(string $state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->summarize(Sum::make()->label('Total Jumlah'))
                    ->alignEnd(),
            ])
            ->paginated(false)
            ->filters([
                //
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('editCost')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->visible(fn() => Auth::user()?->hasPermission('mou.edit') ?? false)
                    ->fillForm(function (CostListMou $record): array {
                        return [
                            'coa_id' => $record->coa_id,
                            'description' => $record->description,
                            'amount' => $record->amount,
                        ];
                    })
                    ->form([
                        Select::make('coa_id')
                            ->label('CoA')
                            ->options(Coa::where('group_coa_id', '40')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('description')
                            ->label('Deskripsi')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('amount')
                            ->label('Jumlah')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                    ])
                    ->action(function (array $data, CostListMou $record) {
                        $record->update([
                            'coa_id' => $data['coa_id'],
                            'description' => $data['description'],
                            'amount' => $data['amount'],
                        ]);

                        // Refresh local collections (optional for immediate state)
                        $this->cost_lists = CostListMou::where('mou_id', $this->mou->id)->get();

                        Notification::make()
                            ->title('Biaya berhasil diperbarui')
                            ->success()
                            ->send();
                    }),
                \Filament\Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn() => Auth::user()?->hasPermission('mou.delete') ?? false)
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Biaya')
                    ->modalDescription('Apakah Anda yakin ingin menghapus biaya ini? Tindakan ini tidak dapat dibatalkan.')
                    ->modalSubmitActionLabel('Ya, Hapus')
                    ->after(function () {
                        // Refresh local collections after delete
                        $this->cost_lists = CostListMou::where('mou_id', $this->mou->id)->get();

                        Notification::make()
                            ->title('Biaya berhasil dihapus')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                //
            ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('coa_id')
                    ->label('CoA')
                    ->options(Coa::where('group_coa_id', '40')->pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('Pilih CoA'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createCost')
                ->label('Tambah Biaya')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->visible(fn() => Auth::user()?->hasAnyPermission(['mou.create', 'mou.edit']) ?? false)
                ->form([
                    Select::make('coa_id')
                        ->label('CoA')
                        ->options(Coa::where('group_coa_id', '40')->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    TextInput::make('amount')
                        ->label('Jumlah')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                    TextInput::make('description')
                        ->label('Deskripsi')
                        ->maxLength(255),
                ])
                ->action(function (array $data) {
                    CostListMou::create([
                        'mou_id' => $this->mou->id,
                        'coa_id' => $data['coa_id'],
                        'description' => $data['description'],
                        'amount' => $data['amount'],
                    ]);

                    // Refresh local collections
                    $this->cost_lists = CostListMou::where('mou_id', $this->mou->id)->get();

                    Notification::make()
                        ->title('Biaya berhasil ditambahkan')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('export_pdf')
                ->label('Print PDF MoU')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn() => route('mou.print.view', ['id' => $this->mou->id]))
                ->openUrlInNewTab(),
            Action::make('createInvoice')
                ->label('Buat Invoice')
                ->icon('heroicon-o-document-currency-dollar')
                ->color('info')
                ->visible(fn() => Auth::user()?->hasAnyPermission(['invoice.create', 'mou.edit']) ?? false)
                ->form([
                    Select::make('mou_id')
                        ->label('MoU')
                        ->options(function () {
                            return MoU::query()
                                ->select('id', 'mou_number')
                                ->get()
                                ->pluck('mou_number', 'id');
                        })
                        ->searchable()
                        ->required()
                        ->default($this->mou->id),
                    TextInput::make('invoice_number')
                        ->label('Nomor Invoice')
                        ->required()
                        ->maxLength(255)
                        ->unique(Invoice::class, 'invoice_number'),
                    \Filament\Forms\Components\DatePicker::make('invoice_date')
                        ->label('Tanggal Invoice')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->live()
                        ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                            if ($state) {
                                $dueDate = date('Y-m-d', strtotime($state . ' + 3 weeks'));
                                $set('due_date', $dueDate);
                            }
                        }),
                    \Filament\Forms\Components\DatePicker::make('due_date')
                        ->label('Tanggal Jatuh Tempo')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->required(),
                    Select::make('invoice_status')
                        ->label('Status')
                        ->options([
                            'unpaid' => 'Belum Dibayar',
                            'paid' => 'Sudah Dibayar'
                        ])
                        ->default('unpaid'),
                    Select::make('invoice_type')
                        ->label('Tipe Invoice')
                        ->required()
                        ->options([
                            'pt' => 'PT',
                            'kkp' => 'KKP'
                        ])
                        ->default('pt'),
                    \Filament\Forms\Components\Section::make('Rincian Biaya')
                        ->schema([
                            \Filament\Forms\Components\Repeater::make('costListInvoices')
                                ->schema([
                                    Select::make('coa_id')
                                        ->label('CoA')
                                        ->options(Coa::where('group_coa_id', '40')->pluck('name', 'id'))
                                        ->required()
                                        ->searchable()
                                        ->columnSpan([
                                            'md' => 3,
                                        ]),
                                    TextInput::make('description')
                                        ->label('Deskripsi')
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
                        ])
                ])
                ->modalWidth('7xl')
                ->action(function (array $data) {
                    \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
                        // Extract cost list items
                        $costListItems = $data['costListInvoices'] ?? [];
                        unset($data['costListInvoices']);

                        // Create Invoice
                        $invoice = Invoice::create($data);

                        // Create Cost List Items
                        foreach ($costListItems as $item) {
                            \App\Models\CostListInvoice::create([
                                'invoice_id' => $invoice->id,
                                'mou_id' => $invoice->mou_id,
                                'coa_id' => $item['coa_id'],
                                'description' => $item['description'],
                                'amount' => $item['amount'],
                            ]);
                        }
                    });

                    Notification::make()
                        ->title('Invoice berhasil dibuat')
                        ->success()
                        ->send();

                    $this->dispatch('invoice-created');
                }),
            Action::make('back')
                ->label('Kembali ke Daftar MoU')
                ->url(MouResource::getUrl('index'))
                ->color('primary')
                ->icon('heroicon-o-arrow-left'),
        ];
    }
}
