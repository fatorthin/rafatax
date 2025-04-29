<?php

namespace App\Filament\Resources\IncomeStatementResource\Pages;

use App\Filament\Resources\IncomeStatementResource;
use Filament\Resources\Pages\ListRecords;

class ListIncomeStatements extends ListRecords
{
    protected static string $resource = IncomeStatementResource::class;
    
    protected function getHeaderActions(): array
    {
        return [];
    }
} 