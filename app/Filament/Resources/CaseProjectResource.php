<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CaseProjectResource\Pages;
use App\Filament\Resources\CaseProjectResource\RelationManagers;
use App\Models\CaseProject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CaseProjectResource extends Resource
{
    protected static ?string $model = CaseProject::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    protected static ?string $navigationLabel = 'Daftar Proyek Kasus';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('description')
                    ->required()
                    ->maxLength(255)
                    ->readonly(),
                Forms\Components\TextInput::make('budget')
                    ->numeric()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('description')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('client.company_name')->label('Client')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('staff.name')->label('Staff')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('case_date')->date('d-m-Y')->sortable(),
                Tables\Columns\TextColumn::make('status')->sortable(),
                Tables\Columns\TextColumn::make('link_dokumen')->label('Link Dokumen')->url(fn($record) => $record->link_dokumen)->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('budget')->formatStateUsing(fn($state) => 'Rp. ' . number_format($state, 0, ',', '.'))->alignEnd()->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('Detail Tim')
                    ->url(fn($record) => static::getUrl('detail', ['record' => $record]))
                    ->icon('heroicon-o-information-circle'),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCaseProjects::route('/'),
            'detail' => Pages\DetailTim::route('/{record}/detail'),
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
