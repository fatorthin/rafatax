<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashReportResource\Pages;
use App\Filament\Resources\CashReportResource\RelationManagers;
use App\Models\CashReport;
use App\Models\CashReference;
use App\Models\Coa;
use App\Models\Invoice;
use Filament\Forms; 
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CashReportResource extends Resource
{
    protected static ?string $model = CashReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('description')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('cash_reference_id')
                    ->required()
                    ->options(CashReference::all()->pluck('name', 'id')),
                Forms\Components\Select::make('coa_id')   
                    ->required()
                    ->options(Coa::all()->pluck('name', 'id')),
                Forms\Components\Select::make('invoice_id')
                    ->required()
                    ->options(Invoice::all()->pluck('invoice_number', 'id')),
                Forms\Components\TextInput::make('debit_amount')
                    ->required()
                    ->numeric(),    
                Forms\Components\TextInput::make('credit_amount')
                    ->required()
                    ->numeric(),
                Forms\Components\DatePicker::make('transaction_date')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cashReference.name')
                    ->label('Cash Reference')
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_date')
                    ->dateTime('d-M-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('coa.code')
                    ->label('CoA')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice.mou.id')
                    ->label('MoU ID')
                    ->numeric()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('invoice_id')
                    ->numeric()
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('cost_list_invoice_id')
                    ->numeric()
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('type')
                    ->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('debit_amount')
                    ->numeric()
                    ->money('IDR')
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('credit_amount')
                    ->numeric()
                    ->money('IDR')
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime('d-m-Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashReports::route('/'),
            'create' => Pages\CreateCashReport::route('/create'),
            'edit' => Pages\EditCashReport::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
