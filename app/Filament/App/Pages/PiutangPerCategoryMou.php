<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Resources\MouResource;
use App\Filament\Pages\PiutangPerCategoryMou as BasePiutangPerCategoryMou;
use App\Models\CategoryMou;
use Filament\Tables;
use Filament\Tables\Table;

class PiutangPerCategoryMou extends BasePiutangPerCategoryMou
{
    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 6;

    public function table(Table $table): Table
    {
        $table = parent::table($table);

        // Sesuaikan URL action view_mou ke App Panel MouResource
        return $table->actions([
            Tables\Actions\Action::make('view_detail')
                ->label('Lihat Detail')
                ->icon('heroicon-o-eye')
                ->color('primary')
                ->url(function (CategoryMou $record): string {
                    $filters = request('tableFilters', []);
                    $year = $filters['year']['value'] ?? date('Y');
                    $entity = $filters['entity']['value'] ?? 'all';

                    return route('piutang-category-mou.detail', [
                        'id' => $record->id,
                        'year' => $year,
                        'entity' => $entity,
                    ]);
                }),

            Tables\Actions\Action::make('view_mou')
                ->label('Buka MoU')
                ->icon('heroicon-o-list-bullet')
                ->color('gray')
                ->url(fn (CategoryMou $record): string => MouResource::getUrl('index', [
                    'tableFilters' => [
                        'category_mou_id' => [
                            'value' => $record->id,
                        ],
                    ],
                ]))
                ->openUrlInNewTab(true),
        ]);
    }
}

