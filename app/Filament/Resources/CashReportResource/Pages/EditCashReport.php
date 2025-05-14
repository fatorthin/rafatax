<?php

namespace App\Filament\Resources\CashReportResource\Pages;

use App\Filament\Resources\CashReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\CashReference;

class EditCashReport extends EditRecord
{
    protected static string $resource = CashReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        // Get the cash reference ID from the current record
        $cashReferenceId = $this->record->cash_reference_id;
        
        // Get the referrer URL
        $referrer = request()->headers->get('referer');
        
        // Check if the referrer contains viewMonthDetail
        if ($referrer && str_contains($referrer, 'detail')) {
            // Check if referrer contains year and month parameters
            $url = parse_url($referrer);
            $queryParams = [];
            if (isset($url['query'])) {
                parse_str($url['query'], $queryParams);
            }
            
            // If it has year and month, it's likely from the month detail page
            if (isset($queryParams['year']) && isset($queryParams['month'])) {
                return $referrer;
            }
            
            // Otherwise return to the detail page
            return route('filament.admin.resources.cash-references.view', ['record' => $cashReferenceId]);
        }
        
        // Default to resource index
        return $this->getResource()::getUrl('index');
    }
}
