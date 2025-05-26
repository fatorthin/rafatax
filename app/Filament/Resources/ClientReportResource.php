<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientReportResource\Pages;
use App\Filament\Resources\ClientReportResource\RelationManagers;
use App\Models\ClientReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClientReportResource extends Resource
{
    protected static ?string $model = ClientReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Bagian HRD';

    protected static ?string $navigationLabel = 'Laporan Klien';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'company_name')
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('staff_id')
                    ->label('Staff')
                    ->relationship('staff', 'name')
                    ->required()
                    ->searchable(),
                Forms\Components\DatePicker::make('report_date')
                    ->label('Report Date')
                    ->required(),
                Forms\Components\Select::make('report_content')
                    ->label('Report Content')
                    ->options([
                        'pph25' => 'PPH 25',
                        'pph21' => 'PPH 21',
                        'ppn' => 'PPN',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.company_name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Staff')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('report_date')
                    ->label('Report Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('report_content')
                    ->label('Report Content')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'pph25' => 'PPH 25',
                        'pph21' => 'PPH 21',
                        'ppn' => 'PPN',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\SelectColumn::make('score')
                    ->options([
                        '0' => '0',
                        '1' => '1',
                    ])
                    ->searchable(),
                Tables\Columns\ToggleColumn::make('is_verified')
                    ->label('Is Verified')
                    ->sortable(),
                Tables\Columns\TextColumn::make('verified_by.name')
                    ->label('Verified By')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListClientReports::route('/'),
            'create' => Pages\CreateClientReport::route('/create'),
            'edit' => Pages\EditClientReport::route('/{record}/edit'),
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
