<?php

namespace App\Filament\Resources\CategoryMouResource\Pages;

use App\Models\MoU;
use Filament\Forms;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\CategoryMou;
use App\Models\CostListInvoice;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\SelectColumn;
use App\Filament\Resources\CategoryMouResource;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Resources\CategoryMouResource\Widgets\CategoryMouStatsOverview;

class ListMou extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CategoryMouResource::class;

    protected static string $view = 'filament.resources.category-mou-resource.pages.list-mou';

    public CategoryMou $record;

    public function getTitle(): string
    {
        return 'List MoU Kategori ' . $this->record->name;
    }

    public function getSubheading(): ?string
    {
        $mous = MoU::where('category_mou_id', $this->record->id)->get();

        $totalMouAmount = $mous->sum(function ($mou) {
            return $mou->cost_lists()->sum('amount');
        });

        $totalInvoiceAmount = CostListInvoice::whereIn('mou_id', $mous->pluck('id'))
            ->whereNotNull('invoice_id')
            ->sum('amount');

        return 'Total MoU Amount: Rp ' . number_format($totalMouAmount, 0, ',', '.') .
            ' | Total Invoice Amount: Rp ' . number_format($totalInvoiceAmount, 0, ',', '.');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CategoryMouStatsOverview::make([
                'categoryId' => $this->record->id,
            ]),
        ];
    }

    public function table(Table $table): Table
    {
        $query = MoU::query()
            ->where('category_mou_id', $this->record->id);

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('mou_number')->label('MoU Number')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')
                    ->searchable(),
                TextColumn::make('type')
                    ->searchable()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'pt' => 'PT',
                        'kkp' => 'KKP',
                    }),
                TextColumn::make('client.company_name')
                    ->label('Client Name')
                    ->sortable()
                    ->searchable(),
                SelectColumn::make('status')
                    ->options([
                        'approved' => 'Approved',
                        'unapproved' => 'Unapproved',
                    ])
                    ->searchable(),
                TextColumn::make('cost_lists_total')
                    ->label('Total MoU Amount')
                    ->numeric(locale: 'id')
                    ->state(function ($record) {
                        return $record->cost_lists()->sum('amount');
                    })
                    ->alignEnd(),
                TextColumn::make('invoice_total')
                    ->label('Total Invoice Amount')
                    ->numeric(locale: 'id')
                    ->state(function ($record) {
                        return CostListInvoice::where('mou_id', $record->id)
                            ->whereNotNull('invoice_id')
                            ->sum('amount');
                    })
                    ->alignEnd(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'PT' => 'PT',
                        'KKP' => 'KKP',
                    ]),
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'company_name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('category_mou_id')
                    ->label('Category')
                    ->relationship('categoryMou', 'name')
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
                EditAction::make()
                    ->form([
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
                    ])
                    ->color('info')
                    ->modalWidth('2xl'),
                Action::make('viewCostList')
                    ->label('Detail')
                    ->url(fn($record) => "/admin/mous/{$record->id}/cost-list")
                    ->icon('heroicon-o-eye')
                    ->color('success'),
                DeleteAction::make(),
            ])
            ->bulkActions([]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to Categories')
                ->url(CategoryMouResource::getUrl('index'))
                ->color('info')
                ->icon('heroicon-o-arrow-left'),
            Actions\Action::make('create')
                ->label('Create MoU')
                ->form([
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
                        ->options(fn() => \App\Models\Client::pluck('company_name', 'id'))
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('category_mou_id')
                        ->label('Category MoU')
                        ->options(fn() => \App\Models\CategoryMou::pluck('name', 'id'))
                        ->searchable()
                        ->default($this->record->id)
                        ->required(),
                    Forms\Components\TextInput::make('percentage_restitution')
                        ->label('Percentage Restitution (optional)')
                        ->numeric()
                        ->default(0)
                        ->suffix('%'),
                ])
                ->action(function (array $data) {
                    MoU::create($data);
                })
                ->successNotificationTitle('MoU created successfully')
                ->color('success')
                ->icon('heroicon-o-plus')
                ->modalWidth('2xl'),
        ];
    }
}
