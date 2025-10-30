<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CaseProjectDetailResource\Pages;
use App\Filament\Resources\CaseProjectDetailResource\RelationManagers;
use App\Models\CaseProjectDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CaseProjectDetailResource extends Resource
{
    protected static ?string $model = CaseProjectDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    protected static ?string $navigationLabel = 'Histori Tim Proyek Kasus';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('case_project_id')
                    ->relationship('caseProject', 'description', modifyQueryUsing: fn(Builder $query) => $query->where('status', 'open'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('staff_id')
                    ->relationship('staff', 'name', modifyQueryUsing: fn(Builder $query) => $query->where('is_active', true))
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('bonus')
                    ->numeric()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('caseProject.client.company_name')->label('Client')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('caseProject.description')->label('Case Project')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('staff.name')->label('Staff')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('bonus')->formatStateUsing(fn($state) => 'Rp. ' . number_format($state, 0, ',', '.'))->alignEnd()->sortable(),
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
            'index' => Pages\ManageCaseProjectDetails::route('/'),
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
