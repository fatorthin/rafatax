<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PerformanceReviewReferenceResource\Pages;
use App\Filament\Resources\PerformanceReviewReferenceResource\RelationManagers;
use App\Models\PerformanceReviewReference;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PerformanceReviewReferenceResource extends Resource
{
    protected static ?string $model = PerformanceReviewReference::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Referensi';
    protected static ?string $navigationLabel = 'Referensi Penilaian Kinerja';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Nama Referensi'),
                Forms\Components\TextInput::make('description')
                    ->label('Deskripsi'),
                Forms\Components\Select::make('group')
                    ->options([
                        'Rispek' => 'Rispek',
                        'Antusias' => 'Antusias',
                        'Fatanah' => 'Fatanah',
                        'Amanah' => 'Amanah',
                        'Aspek Tanggung Jawab' => 'Aspek Tanggung Jawab',
                        'Pendidikan' => 'Pendidikan',
                        'Pengalaman Kerja' => 'Pengalaman Kerja',
                    ])
                    ->required(),
                Forms\Components\Select::make('type')
                    ->options([
                        'Kompetensi Dasar' => 'Kompetensi Dasar',
                        'Kompetensi Teknis' => 'Kompetensi Teknis',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe'),
                Tables\Columns\TextColumn::make('group')
                    ->label('Grup'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Referensi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Dibuat Pada')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
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
            'index' => Pages\ListPerformanceReviewReferences::route('/'),
            'create' => Pages\CreatePerformanceReviewReference::route('/create'),
            'edit' => Pages\EditPerformanceReviewReference::route('/{record}/edit'),
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
