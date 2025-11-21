<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryMouResource\Pages;
use App\Filament\Resources\CategoryMouResource\RelationManagers;
use App\Models\CategoryMou;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryMouResource extends Resource
{
    protected static ?string $model = CategoryMou::class;

    protected static ?string $navigationLabel = 'Referensi Kategori MoU';

    protected static ?string $navigationGroup = 'Referensi';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('format_mou_pt')
                    ->maxLength(255),
                Forms\Components\TextInput::make('format_mou_kkp')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('format_mou_pt')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('format_mou_kkp')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('mous_count')
                    ->label('Jumlah MoU')
                    ->counts('mous')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('total_cost_list')
                    ->label('Total Nominal Cost List')
                    ->numeric(locale: 'id')
                    ->getStateUsing(function ($record) {
                        return \App\Models\CostListMou::whereHas('mou', function ($query) use ($record) {
                            $query->where('category_mou_id', $record->id);
                        })->sum('amount');
                    })
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('total_invoice_amount')
                    ->label('Total Nominal Invoice')
                    ->numeric(locale: 'id')
                    ->getStateUsing(function ($record) {
                        return \App\Models\CostListInvoice::whereHas('mou', function ($query) use ($record) {
                            $query->where('category_mou_id', $record->id);
                        })
                            ->whereNotNull('invoice_id')
                            ->sum('amount');
                    })
                    ->alignEnd()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('list-mou')
                    ->label('Lihat MoU')
                    ->url(fn(CategoryMou $record) => CategoryMouResource::getUrl('list-mou', ['record' => $record]))
                    ->icon('heroicon-o-eye')
                    ->color('primary'),
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
            'index' => Pages\ListCategoryMous::route('/'),
            'create' => Pages\CreateCategoryMou::route('/create'),
            'edit' => Pages\EditCategoryMou::route('/{record}/edit'),
            'list-mou' => Pages\ListMou::route('/{record}/list-mou'),
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
