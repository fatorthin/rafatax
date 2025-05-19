<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MouResource\Pages;
use App\Filament\Resources\MouResource\RelationManagers;
use App\Models\Mou;
use App\Models\CostListInvoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MouResource extends Resource
{
    protected static ?string $model = Mou::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'List of MoUs';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('mou_number')->label('MoU Number')
                    ->required(),
                Forms\Components\TextInput::make('description')
                        ->required(),
                Forms\Components\DatePicker::make('start_date')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'approved' => 'Approved',
                        'unapproved' => 'Unapproved',
                    ])
                    ->required(),
                Forms\Components\Select::make('type')
                    ->options([
                        'pt' => 'PT',
                        'kkp' => 'KKP',
                    ])
                    ->required(),
                Forms\Components\Select::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('category_mou_id')
                    ->label('Category MoU')
                    ->relationship('categoryMou', 'name')
                    ->searchable()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('mou_number')->label('MoU Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('cost_lists_sum_amount')
                    ->label('Total MoU Amount')
                    ->money('IDR')
                    ->getStateUsing(function ($record) {
                        return number_format($record->cost_lists()->sum('amount'), 0, ',', '.');
                    })->alignEnd(),
                Tables\Columns\TextColumn::make('total_invoice_amount')
                    ->label('Total Invoice Amount')
                    ->money('IDR')
                    ->getStateUsing(function ($record) {
                        return number_format(CostListInvoice::where('mou_id', $record->id)
                            ->whereNotNull('invoice_id')
                            ->sum('amount'), 0, ',', '.');
                    })->alignEnd(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
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
                Tables\Filters\SelectFilter::make('month')
                    ->label('Month')
                    ->options(
                        collect(range(1, 12))->mapWithKeys(function ($month) {
                            return [$month => \Carbon\Carbon::create()->month($month)->format('F')];
                        })->toArray()
                    ),
                Tables\Filters\SelectFilter::make('year')
                    ->label('Year')
                    ->options(
                        Mou::query()
                            ->selectRaw('YEAR(start_date) as year')
                            ->distinct()
                            ->orderBy('year', 'desc')
                            ->pluck('year', 'year')
                            ->toArray()
                    ),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Unpaid' => 'Unpaid',
                        'Paid' => 'Paid',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'PT' => 'PT',
                        'Consultant' => 'Consultant',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->color('info'),
                Tables\Actions\Action::make('viewCostList')
                    ->label('Detail')
                    ->url(fn($record) => "/admin/mous/{$record->id}/cost-list") // Change this line
                    ->icon('heroicon-o-eye'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListMous::route('/'),
            'create' => Pages\CreateMou::route('/create'),
            'edit' => Pages\EditMou::route('/{record}/edit'),
            'viewCostList' => Pages\ListCostMou::route('/{record}/cost-list'), // Add custom page route
            'cost-create' => Pages\CreateCostMou::route('/{record}/cost-create'), // Add new cost list creation page
            'cost-edit' => Pages\EditCostMou::route('/{record}/cost-edit'), // Add cost list edit page
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->latest('created_at');
    }
}
