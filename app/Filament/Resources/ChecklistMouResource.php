<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChecklistMouResource\Pages;
use App\Filament\Resources\ChecklistMouResource\RelationManagers;
use App\Models\ChecklistMou;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChecklistMouResource extends Resource
{
    protected static ?string $model = ChecklistMou::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Histori Data';

    protected static ?string $navigationLabel = 'Histori Checklist Tagihan MoU';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('mou_id')
                    ->relationship('mou', 'mou_number')
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->mou_number} - {$record->description}")
                    ->searchable(['mou_number', 'description'])
                    ->preload()
                    ->required()
                    ->label('MoU'),
                Forms\Components\Select::make('invoice_id')
                    ->relationship('invoice', 'invoice_number')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Invoice No'),
                Forms\Components\DatePicker::make('checklist_date')
                    ->required()
                    ->label('Checklist Date'),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'overdue' => 'Overdue',
                    ])
                    ->default('pending')
                    ->required()
                    ->label('Status'),
                Forms\Components\TextInput::make('notes')
                    ->label('Notes')
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('mou.mou_number')
                    ->label('MoU Number')
                    ->description(fn($record) => $record->mou->description ?? '')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice.invoice_number')->label('Invoice No')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('checklist_date')->label('Checklist Date')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('notes')->label('Notes')->limit(50),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageChecklistMous::route('/'),
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
