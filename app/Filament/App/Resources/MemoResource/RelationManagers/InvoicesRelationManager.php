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
                        ignoreRecord: true
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
                Forms\Components\Repeater::make('costListInvoices')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('coa_id')
                            ->label('CoA')
                            ->options(\App\Models\Coa::where('group_coa_id', '40')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->columnSpan(['md' => 3]),
                        Forms\Components\TextInput::make('description')
                            ->label('Deskripsi')
                            ->columnSpan(['md' => 4]),
                        Forms\Components\TextInput::make('amount')
                            ->label('Harga')
                            ->numeric()
                            ->required()
                            ->columnSpan(['md' => 5]),
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
                    ->url(fn($record) => route('invoices.preview', $record->id))
                    ->openUrlInNewTab()
                    ->color('success'),
                Tables\Actions\Action::make('download_jpeg')
                    ->label('Download JPEG')
                    ->icon('heroicon-o-photo')
                    ->url(fn($record) => route('invoices.jpg', $record->id))
                    ->openUrlInNewTab()
                    ->color('warning'),
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
