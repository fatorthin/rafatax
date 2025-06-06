<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ClientReport;
use Illuminate\Support\Carbon;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ClientReportResource\Pages;

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
                Forms\Components\TextInput::make('report_month')
                    ->type('month')
                    ->label('Report Month')
                    ->default(now()->format('Y-m'))
                    ->required(),
                Forms\Components\DatePicker::make('report_date')
                    ->label('Report Date')
                    ->live()
                    ->required()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $reportContent = $get('report_content');
                        $score = self::calculateScore($reportContent, $state);
                        $set('score', $score);
                    }),
                Forms\Components\Select::make('report_content')
                    ->label('Report Content')
                    ->options([
                        'pph25' => 'PPH 25',
                        'pph21' => 'PPH 21',
                        'ppn' => 'PPN',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $reportDate = $get('report_date');
                        $score = self::calculateScore($state, $reportDate);
                        $set('score', $score);
                    }),
                Forms\Components\TextInput::make('score')
                    ->label('Score')
                    ->numeric()
                    ->default(0)
                    ->readOnly(),
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
                Tables\Columns\TextColumn::make('report_month')
                    ->label('Report Month')
                    ->dateTime('M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('report_date')
                    ->label('Report Date')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('report_content')
                    ->label('Report Content')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'pph25' => 'PPH 25',
                        'pph21' => 'PPH 21',
                        'ppn' => 'PPN',
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
                    ->onIcon('heroicon-o-check-circle')
                    ->offIcon('heroicon-o-x-circle')
                    ->onColor('success')
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
            ])->defaultSort('report_date', 'desc');
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

    // Add this method to calculate score before creating
    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['score'] = self::calculateScore($data['report_content'] ?? null, $data['report_date'] ?? null);
        return $data;
    }

    // Add this method to calculate score before saving (updating)
    protected static function mutateFormDataBeforeSave(array $data): array
    {
        $data['score'] = self::calculateScore($data['report_content'] ?? null, $data['report_date'] ?? null);
        return $data;
    }


    private static function calculateScore(?string $reportContent, ?string $reportDate): int
    {
        if (!$reportContent || !$reportDate) {
            return 0;
        }

        // Ensure $reportDate is a valid date string or Carbon instance
        try {
            $date = Carbon::parse($reportDate);
            $day = (int) $date->format('d');
        } catch (\Exception $e) {
            return 0; // Return 0 if date is invalid
        }

        $score = 0;

        if ($reportContent === 'pph25') {
            if ($day >= 1 && $day <= 15) {
                $score = 1;
            } else {
                $score = 0;
            }
        } elseif ($reportContent === 'pph21') {
            if ($day >= 1 && $day <= 15) {
                $score = 2;
            } elseif ($day >= 16 && $day <= 20) {
                $score = 1;
            } else {
                $score = 0;
            }
        } elseif ($reportContent === 'ppn') {
            if ($day >= 16 && $day <= 23) {
                $score = 2;
            } elseif ($day >= 24 && $day <= 31) {
                $score = 1;
            } else {
                $score = 0;
            }
        }

        return $score;
    }
}
