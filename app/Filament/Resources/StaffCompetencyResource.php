<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffCompetencyResource\Pages;
use App\Filament\Resources\StaffCompetencyResource\RelationManagers;
use App\Models\StaffCompetency;
use Filament\Forms;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StaffCompetencyResource extends Resource
{
    protected static ?string $model = StaffCompetency::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Bagian HRD';

    protected static ?string $navigationLabel = 'Daftar Kompetensi Karyawan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('staff_id')
                    ->relationship('staff', 'name')
                    ->required(),
                Forms\Components\TextInput::make('competency')
                    ->required(),
                Forms\Components\DatePicker::make('date_of_assessment')
                    ->required(),   
                Forms\Components\DatePicker::make('date_of_expiry')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('staff.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('competency')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_of_assessment')
                    ->dateTime('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_of_expiry')
                    ->dateTime('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        return $record->date_of_expiry >= now()->format('Y-m-d')
                            ? 'Masih Berlaku'
                            : 'Perlu Diupdate';
                    })
                    ->color(function ($record) {
                        return $record->date_of_expiry >= now()->format('Y-m-d')
                            ? 'success'
                            : 'danger';
                    }),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->recordUrl(null)
            ->recordAction(null);
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
            'index' => Pages\ListStaffCompetencies::route('/'),
            'create' => Pages\CreateStaffCompetency::route('/create'),
            'edit' => Pages\EditStaffCompetency::route('/{record}/edit'),
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
