<?php

namespace App\Filament\Resources;

use App\Models\MoU;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CostListInvoice;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\ActionsPosition;
use App\Filament\Resources\MouResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MouResource extends Resource
{
    protected static ?string $model = MoU::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Bagian Keuangan';

    protected static ?string $navigationLabel = 'Daftar MoU';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('mou_number')->label('MoU Number')
                    ->unique(ignoreRecord: true)
                    ->required(),
                Forms\Components\TextInput::make('description')
                    ->required(),
                Forms\Components\DatePicker::make('start_date')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->default(date('Y') . '-01-01'),
                Forms\Components\DatePicker::make('end_date')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->default(date('Y') . '-12-31'),
                Forms\Components\Select::make('status')
                    ->options([
                        'approved' => 'Approved',
                        'unapproved' => 'Unapproved',
                    ])
                    ->default('approved')
                    ->required(),
                Forms\Components\Select::make('type')
                    ->options([
                        'pt' => 'PT',
                        'kkp' => 'KKP',
                    ])
                    ->default('pt')
                    ->required(),
                Forms\Components\Select::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'company_name')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('category_mou_id')
                    ->label('Category MoU')
                    ->relationship('categoryMou', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('percentage_restitution')
                    ->label('Percentage Restitution (optional)')
                    ->numeric()
                    ->default(0)
                    ->suffix('%'),
                Forms\Components\Section::make('Cost List Details')
                    ->schema([
                        Forms\Components\Repeater::make('cost_lists')
                            ->relationship('cost_lists')
                            ->schema([
                                Forms\Components\Select::make('coa_id')
                                    ->label('CoA')
                                    ->options(\App\Models\Coa::where('group_coa_id', '40')->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->columnSpan([
                                        'md' => 4,
                                    ]),
                                Forms\Components\TextInput::make('description')
                                    ->label('Description')
                                    ->required()
                                    ->columnSpan([
                                        'md' => 4,
                                    ]),
                                Forms\Components\TextInput::make('amount')
                                    ->label('Amount')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->columnSpan([
                                        'md' => 4,
                                    ]),
                            ])
                            ->columns([
                                'md' => 12,
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Add Cost Item'),
                    ])
                    ->collapsible(),
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
                    ->dateTime('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'pt' => 'PT',
                        'kkp' => 'KKP',
                    }),
                Tables\Columns\TextColumn::make('client.company_name')
                    ->label('Client Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'approved' => 'Approved',
                        'unapproved' => 'Unapproved',
                    ])
                    ->searchable(),
                Tables\Columns\TextColumn::make('cost_lists_sum_amount')
                    ->label('Total MoU Amount')
                    ->numeric(locale: 'id')
                    ->getStateUsing(function ($record) {
                        return $record->cost_lists()->sum('amount');
                    })->alignEnd(),
                Tables\Columns\TextColumn::make('total_invoice_amount')
                    ->label('Total Invoice Amount')
                    ->numeric(locale: 'id')
                    ->getStateUsing(function ($record) {
                        return CostListInvoice::where('mou_id', $record->id)
                            ->whereNotNull('invoice_id')
                            ->sum('amount');
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
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'PT' => 'PT',
                        'KKP' => 'KKP',
                    ]),
                Tables\Filters\SelectFilter::make('client_id')
                    ->preload()
                    ->label('Client')
                    ->relationship('client', 'company_name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('category_mou_id')
                    ->label('Category')
                    ->relationship('categoryMou', 'name')
                    ->preload()
                    ->searchable(),
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
                        MoU::query()
                            ->selectRaw('YEAR(start_date) as year')
                            ->distinct()
                            ->orderBy('year', 'desc')
                            ->pluck('year', 'year')
                            ->toArray()
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->color('info')
                    ->modalWidth('7xl'),
                Tables\Actions\Action::make('viewCostList')
                    ->label('Detail')
                    ->url(fn($record) => "/admin/mous/{$record->id}/cost-list")
                    ->icon('heroicon-o-eye')
                    ->color('success'),
                Tables\Actions\DeleteAction::make(),
            ], position: ActionsPosition::BeforeCells)
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
            'index' => Pages\ManageMous::route('/'),
            'viewCostList' => Pages\ListCostMou::route('/{record}/cost-list'),
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
