<?php

namespace App\Filament\App\Pages;

use App\Models\CategoryMou;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables;
use App\Filament\App\Resources\MouResource;

class RekapMou extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.app.pages.rekap-mou';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Rekap MoU';

    protected static ?string $title = 'Rekap MoU';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CategoryMou::query()
                    ->withCount([
                        'mous as jumlah_mou_pt' => function (Builder $query) {
                            $query->where('type', 'pt');
                        },
                        'mous as jumlah_mou_kkp' => function (Builder $query) {
                            $query->where('type', 'kkp');
                        },
                    ])
            )
            ->columns([
                TextColumn::make('index')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('name')
                    ->label('Kategori MoU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jumlah_mou_pt')
                    ->label('Jumlah MoU Tipe PT')
                    ->sortable(),
                TextColumn::make('jumlah_mou_kkp')
                    ->label('Jumlah MoU Tipe KKP')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_list')
                    ->label('Lihat List')
                    ->icon('heroicon-o-list-bullet')
                    ->url(fn(CategoryMou $record): string => MouResource::getUrl('index', [
                        'tableFilters' => [
                            'category_mou_id' => [
                                'value' => $record->id,
                            ],
                        ],
                    ])),
                Tables\Actions\Action::make('view_monthly')
                    ->label('Lihat Bulanan')
                    ->icon('heroicon-o-calendar')
                    ->form([
                        \Filament\Forms\Components\Select::make('month')
                            ->label('Bulan')
                            ->options(
                                collect(range(1, 12))->mapWithKeys(function ($month) {
                                    return [$month => \Carbon\Carbon::create()->month($month)->format('F')];
                                })->toArray()
                            )
                            ->default(now()->month)
                            ->required(),
                        \Filament\Forms\Components\Select::make('year')
                            ->label('Tahun')
                            ->options(
                                function () {
                                    return \App\Models\MoU::query()
                                        ->selectRaw('YEAR(created_at) as year')
                                        ->distinct()
                                        ->orderBy('year', 'desc')
                                        ->pluck('year', 'year')
                                        ->toArray();
                                }
                            )
                            ->default(now()->year)
                            ->required(),
                    ])
                    ->action(function (array $data, CategoryMou $record) {
                        return redirect()->to(MouResource::getUrl('index', [
                            'tableFilters' => [
                                'category_mou_id' => [
                                    'value' => $record->id,
                                ],
                                'created_month' => [
                                    'value' => $data['month'],
                                ],
                                'created_year' => [
                                    'value' => $data['year'],
                                ],
                            ],
                        ]));
                    }),
            ])
            ->paginated(false);
    }
}
