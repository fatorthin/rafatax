<?php

namespace App\Filament\Resources\MemoResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\App\Resources\InvoiceResource;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

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
                    ->required(),
                Forms\Components\Select::make('invoice_type')
                    ->options([
                        'pt' => 'PT',
                        'kkp' => 'KKP',
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('invoice_date')
                    ->required(),
                Forms\Components\DatePicker::make('due_date')
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                Forms\Components\TextInput::make('description')
                    ->maxLength(255),
                Forms\Components\Select::make('client_id')
                    ->label('Client')
                    ->options(\App\Models\Client::where('status', 'active')->pluck('name', 'id'))
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
                Tables\Columns\TextColumn::make('invoice_status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'unpaid' => 'danger',
                        'paid' => 'success',
                    }),
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
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([

                Tables\Actions\EditAction::make()
                    ->url(fn($record) => InvoiceResource::getUrl('edit', ['record' => $record]))
                    ->visible(true),
                Tables\Actions\DeleteAction::make()->visible(true),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
