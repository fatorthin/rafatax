<?php

namespace App\Filament\Resources\CashReportResource\Pages;

use App\Filament\Resources\CashReportResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;
use App\Models\CashReference;

class CreateCashReport extends CreateRecord
{
    protected static string $resource = CashReportResource::class;

    public function mount(): void
    {
        parent::mount();
        
        // Get cash_reference_id from query string AFTER parent::mount()
        $cash_reference_id = request()->query('cash_reference_id');
        
        // If cash_reference_id exists, update form after mount
        if ($cash_reference_id) {
            // Set form data directly
            $this->form->fill([
                'cash_reference_id' => (int) $cash_reference_id,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        // Get cash_reference_id from query string
        $cash_reference_id = request()->query('cash_reference_id');
        
        // If cash_reference_id exists, redirect back to the detail page
        if ($cash_reference_id) {
            return route('filament.admin.resources.cash-references.view', ['record' => $cash_reference_id]);
        }
        
        // Otherwise, redirect to the index page
        return $this->getResource()::getUrl('index');
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }
    
    protected function getFormActions(): array
    {
        return array_merge(
            parent::getFormActions(),
            [
                // Actions\Action::make('backToDetail')
                //     ->label('Cancel')
                //     ->url(function() {
                //         // Get cash_reference_id from query string
                //         $cash_reference_id = request()->query('cash_reference_id');
                        
                //         // If cash_reference_id exists, redirect back to the detail page
                //         if ($cash_reference_id) {
                //             return route('filament.admin.resources.cash-references.view', ['record' => $cash_reference_id]);
                //         }
                        
                //         // Otherwise, redirect to the index page
                //         return $this->getResource()::getUrl('index');
                //     })
                //     ->color('secondary'),
            ],
        );
    }
}
