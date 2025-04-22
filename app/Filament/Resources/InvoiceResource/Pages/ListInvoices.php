<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Widgets\InvoiceStats;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            InvoiceStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // Tambahkan widget lain jika diperlukan
        ];
    }
    
    public function mount(): void
    {
        parent::mount();
        
        // Add global event listener for filter changes using JavaScript
        $this->js("
            document.addEventListener('DOMContentLoaded', function() {
                function refreshWidgetWithFilters() {
                    // Collect all active filters from the URL
                    const urlParams = new URLSearchParams(window.location.search);
                    const tableFilters = {};
                    
                    // Parse tableFilters from URL
                    if (urlParams.has('tableFilters[year][value]')) {
                        tableFilters['year'] = {
                            'value': urlParams.get('tableFilters[year][value]')
                        };
                    }
                    
                    if (urlParams.has('tableFilters[month][value]')) {
                        tableFilters['month'] = {
                            'value': urlParams.get('tableFilters[month][value]')
                        };
                    }
                    
                    if (urlParams.has('tableFilters[client][value]')) {
                        tableFilters['client'] = {
                            'value': urlParams.get('tableFilters[client][value]')
                        };
                    }
                    
                    if (urlParams.has('tableFilters[type][value]')) {
                        tableFilters['type'] = {
                            'value': urlParams.get('tableFilters[type][value]')
                        };
                    }
                    
                    if (urlParams.has('tableFilters[invoice_status][value]')) {
                        tableFilters['invoice_status'] = {
                            'value': urlParams.get('tableFilters[invoice_status][value]')
                        };
                    }
                    
                    console.log('Collected filters:', tableFilters);
                    
                    // Refresh the widget with these filters
                    Livewire.dispatch('filament.widget-refresh', { 
                        widgetId: 'invoice-stats',
                        filters: tableFilters
                    });
                }
                
                // Set up mutations observer to detect URL and filter changes
                const observer = new MutationObserver(function(mutations) {
                    // When DOM changes, check if it was a filter change
                    refreshWidgetWithFilters();
                });
                
                // Watch the entire document for changes
                observer.observe(document.body, { 
                    childList: true, 
                    subtree: true 
                });
                
                // Initial refresh when page loads
                refreshWidgetWithFilters();
                
                // Also listen for filter button clicks
                document.addEventListener('click', function(event) {
                    if (event.target && (
                        event.target.classList.contains('fi-btn') || 
                        event.target.closest('.fi-btn')
                    )) {
                        // Wait for URL to update
                        setTimeout(refreshWidgetWithFilters, 500);
                    }
                });
            });
        ");
    }

    // Refresh table when filters are applied
    public function updatedTableFilters(): void
    {
        Log::info('Table filters updated, refreshing widget');
        
        // Get current filters
        $filters = $this->getTableFiltersForm()->getRawState();
        Log::info('Current filters:', $filters);
        
        // Refresh widget with these filters
        $this->dispatch('filament.widget-refresh', [
            'widgetId' => 'invoice-stats',
            'filters' => $filters
        ]);
    }
    
    // Reset table when filter is removed
    public function removeTableFilter(string $filterName, ?string $field = null, bool $isRemovingAllFilters = false): void
    {
        parent::removeTableFilter($filterName, $field, $isRemovingAllFilters);
        Log::info("Removing filter: $filterName");
        
        // Get updated filters
        $filters = $this->getTableFiltersForm()->getRawState();
        Log::info('Updated filters after removal:', $filters);
        
        // Refresh widget with these filters
        $this->dispatch('filament.widget-refresh', [
            'widgetId' => 'invoice-stats',
            'filters' => $filters
        ]);
    }
    
    // Reset table when search query changes
    public function updatedTableSearch(): void
    {
        Log::info('Table search updated, refreshing widget');
        
        // Get current filters
        $filters = $this->getTableFiltersForm()->getRawState();
        Log::info('Current filters with search:', $filters);
        
        // Refresh widget with these filters
        $this->dispatch('filament.widget-refresh', [
            'widgetId' => 'invoice-stats',
            'filters' => $filters
        ]);
    }
}
