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
            Actions\Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->modalHeading('Export Data Invoice')
                ->modalSubmitActionLabel('Export')
                ->form([
                    \Filament\Forms\Components\Select::make('year')
                        ->label('Tahun Invoice')
                        ->options(function () {
                            return \App\Models\Invoice::selectRaw('YEAR(invoice_date) as year')
                                ->whereNotNull('invoice_date')
                                ->distinct()
                                ->orderBy('year', 'desc')
                                ->pluck('year', 'year')
                                ->toArray();
                        })
                        ->placeholder('Semua Tahun')
                        ->searchable(),
                    \Filament\Forms\Components\Select::make('is_send_invoice')
                        ->label('Status Kirim Invoice')
                        ->options([
                            '1' => 'Sudah',
                            '0' => 'Belum',
                        ])
                        ->placeholder('Semua Status Kirim'),
                    \Filament\Forms\Components\Select::make('invoice_type')
                        ->label('Tipe Invoice')
                        ->options(function () {
                            return \App\Models\Invoice::select('invoice_type')
                                ->whereNotNull('invoice_type')
                                ->distinct()
                                ->pluck('invoice_type', 'invoice_type')
                                ->toArray();
                        })
                        ->placeholder('Semua Tipe Invoice'),
                    \Filament\Forms\Components\Select::make('invoice_status')
                        ->label('Status Bayar')
                        ->options([
                            'paid' => 'Paid',
                            'unpaid' => 'Unpaid',
                        ])
                        ->placeholder('Semua Status Bayar'),
                ])
                ->action(function (array $data) {
                    $query = \App\Models\Invoice::with(['mou.client', 'costListInvoices']);

                    if (!empty($data['year'])) {
                        $query->whereYear('invoice_date', $data['year']);
                    }
                    if (isset($data['is_send_invoice']) && $data['is_send_invoice'] !== '') {
                        $query->where('is_send_invoice', $data['is_send_invoice']);
                    }
                    if (!empty($data['invoice_type'])) {
                        $query->where('invoice_type', $data['invoice_type']);
                    }

                    if (!empty($data['invoice_status'])) {
                        $query->where('invoice_status', $data['invoice_status']);
                    }

                    $invoices = $query->get();

                    return response()->streamDownload(function () use ($invoices) {
                        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                        $sheet = $spreadsheet->getActiveSheet();
                        $sheet->setTitle('Data Invoice');

                        // Header
                        $headers = ['No', 'No. Invoice', 'Tanggal Invoice', 'Klien', 'MoU', 'Status', 'Tipe', 'Total Tagihan', 'Dikirim'];
                        $col = 'A';
                        foreach ($headers as $header) {
                            $sheet->setCellValue($col . '1', $header);
                            $sheet->getStyle($col . '1')->getFont()->setBold(true);
                            $col++;
                        }

                        $row = 2;
                        foreach ($invoices as $index => $invoice) {
                            $totalAmount = $invoice->costListInvoices->sum('amount');
                            $clientName = $invoice->mou && $invoice->mou->client ? $invoice->mou->client->company_name : '-';
                            $mouNumber = $invoice->mou ? $invoice->mou->mou_number : '-';
                            $statusKirim = $invoice->is_send_invoice == '1' ? 'Sudah' : 'Belum';

                            $sheet->setCellValue('A' . $row, $index + 1);
                            $sheet->setCellValue('B' . $row, $invoice->invoice_number);
                            $sheet->setCellValue('C' . $row, $invoice->invoice_date);
                            $sheet->setCellValue('D' . $row, $clientName);
                            $sheet->setCellValue('E' . $row, $mouNumber);
                            $sheet->setCellValue('F' . $row, ucfirst($invoice->invoice_status ?? '-'));
                            $sheet->setCellValue('G' . $row, strtoupper($invoice->invoice_type ?? '-'));
                            $sheet->setCellValue('H' . $row, $totalAmount);
                            $sheet->setCellValue('I' . $row, $statusKirim);

                            $row++;
                        }

                        foreach (range('A', 'I') as $columnID) {
                            $sheet->getColumnDimension($columnID)->setAutoSize(true);
                        }

                        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                        $writer->save('php://output');
                    }, 'Export_Invoice_' . date('Y-m-d_H-i-s') . '.xlsx');
                }),
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
                let lastUrl = location.href;
                
                function refreshWidgetWithFilters() {
                    // Check if URL is the base URL (no query parameters)
                    if (window.location.search === '') {
                        console.log('Base URL detected - clearing all filters');
                        Livewire.dispatch('filter-reset');
                        return;
                    }
                    
                    // Collect all active filters from the URL
                    const urlParams = new URLSearchParams(window.location.search);
                    const tableFilters = {};
                    
                    // Parse all tableFilters parameters from URL
                    for (const [key, value] of urlParams.entries()) {
                        // Match tableFilters[] pattern
                        const filterMatch = key.match(/tableFilters\\[(.*?)\\](?:\\[(.*?)\\])?/);
                        if (filterMatch) {
                            const filterName = filterMatch[1];
                            const filterField = filterMatch[2] || 'value';
                            
                            // Create nested structure if needed
                            if (!tableFilters[filterName]) {
                                tableFilters[filterName] = {};
                            }
                            
                            // Special handling for date_range which has nested fields
                            if (filterName === 'date_range' && filterField !== 'value') {
                                tableFilters[filterName][filterField] = value;
                            } else {
                                // For regular filters with value field
                                tableFilters[filterName][filterField] = value;
                            }
                        }
                    }
                    
                    console.log('Parsed filters from URL:', tableFilters);
                    
                    // Refresh the widget with these filters
                    Livewire.dispatch('filament.widget-refresh', { 
                        widgetId: 'invoice-stats',
                        filters: tableFilters
                    });
                    
                    // Also send a direct event to any widgets that listen for table filtered events
                    Livewire.dispatch('filament.table.filtered', tableFilters);
                }
                
                // Check for URL changes (including hash changes)
                function checkUrlChange() {
                    if (location.href !== lastUrl) {
                        lastUrl = location.href;
                        console.log('URL changed, refreshing widgets');
                        
                        // If URL is now the base URL, dispatch a reset event
                        if (window.location.search === '') {
                            console.log('URL reset to base URL - clearing all filters');
                            Livewire.dispatch('filter-reset');
                        } else {
                            setTimeout(refreshWidgetWithFilters, 100);
                        }
                    }
                }
                
                // Set up interval to check for URL changes (needed for browser navigation)
                setInterval(checkUrlChange, 300);
                
                // Set up observer for filter panel interactions
                const filterObserver = new MutationObserver(function(mutations) {
                    for (const mutation of mutations) {
                        if (mutation.type === 'attributes' && 
                            ((mutation.target.classList && mutation.target.classList.contains('fi-dropdown-panel')) ||
                             (mutation.target.classList && mutation.target.classList.contains('fi-ta-filters')))) {
                            console.log('Filter panel changed');
                            setTimeout(refreshWidgetWithFilters, 300);
                            return;
                        }
                    }
                });
                
                // Watch for filter panel changes
                filterObserver.observe(document.body, { 
                    childList: true, 
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['class', 'style']
                });
                
                // Initial refresh when page loads
                if (window.location.search === '') {
                    console.log('Base URL on load - clearing all filters');
                    Livewire.dispatch('filter-reset');
                } else {
                refreshWidgetWithFilters();
                }
                
                // Listen for filter button clicks, reset button clicks, and form submissions
                document.addEventListener('click', function(event) {
                    // Check for filter or reset buttons
                    if (event.target && event.target.closest) {
                        const filterButton = event.target.closest('[wire\\\\:click*=\"filterTable\"]');
                        const resetButton = event.target.closest('[wire\\\\:click*=\"resetTableFilters\"]');
                        
                        if (filterButton) {
                            console.log('Filter button clicked');
                            setTimeout(refreshWidgetWithFilters, 300);
                        }
                        
                        if (resetButton) {
                            console.log('Reset button clicked');
                            setTimeout(function() {
                                if (window.location.search === '') {
                                    console.log('URL reset to base - dispatching filter-reset event');
                                    Livewire.dispatch('filter-reset');
                                } else {
                                    refreshWidgetWithFilters();
                                }
                            }, 300);
                        }
                    }
                });
                
                // Listen for URL changes via History API
                const originalPushState = history.pushState;
                const originalReplaceState = history.replaceState;
                
                history.pushState = function() {
                    originalPushState.apply(this, arguments);
                    setTimeout(function() {
                        if (window.location.search === '') {
                            Livewire.dispatch('filter-reset');
                        } else {
                            refreshWidgetWithFilters();
                        }
                    }, 100);
                };
                
                history.replaceState = function() {
                    originalReplaceState.apply(this, arguments);
                    setTimeout(function() {
                        if (window.location.search === '') {
                            Livewire.dispatch('filter-reset');
                        } else {
                            refreshWidgetWithFilters();
                        }
                    }, 100);
                };
                
                // Listen for popstate events (back/forward navigation)
                window.addEventListener('popstate', function() {
                    setTimeout(function() {
                        if (window.location.search === '') {
                            Livewire.dispatch('filter-reset');
                        } else {
                            refreshWidgetWithFilters();
                        }
                    }, 100);
                });
                
                // Listen for Livewire events
                document.addEventListener('livewire:initialized', () => {
                    Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
                        succeed(({ snapshot, effects }) => {
                            // Check for table-related components or URL changes
                            if (component.name && 
                                (component.name.includes('table') || 
                                 component.name.includes('filter'))) {
                                console.log('Livewire component updated:', component.name);
                                setTimeout(function() {
                                    if (window.location.search === '') {
                                        Livewire.dispatch('filter-reset');
                                    } else {
                                        refreshWidgetWithFilters();
                                    }
                                }, 100);
                            }
                        });
                    });
                    
                    // Listen for specific Livewire events
                    Livewire.on('filterApplied', () => {
                        console.log('Filter applied event received');
                        setTimeout(refreshWidgetWithFilters, 100);
                    });
                    
                    Livewire.on('filtersReset', () => {
                        console.log('Filters reset event received');
                        Livewire.dispatch('filter-reset');
                    });
                });
            });
        ");
    }

    // Refresh widget when filters are updated
    public function updatedTableFilters(): void
    {
        // Get current filters
        $filters = $this->getTableFiltersForm()->getRawState();
        
        // Dispatch event to refresh widget
        $this->dispatch('filament.table.filtered', $filters);
    }
    
    // Refresh widget when a filter is removed
    public function removeTableFilter(string $filterName, ?string $field = null, bool $isRemovingAllFilters = false): void
    {
        parent::removeTableFilter($filterName, $field, $isRemovingAllFilters);
        
        // Get updated filters after removal
        $filters = $this->getTableFiltersForm()->getRawState();
        
        // Dispatch event to refresh widget
        $this->dispatch('filament.table.filtered', $filters);
        
        // If all filters were removed, also dispatch a filter-reset event
        if ($isRemovingAllFilters) {
            $this->dispatch('filter-reset');
        }
    }
    
    // Hook into the reset filters action
    public function resetTableFilters(): void
    {
        parent::resetTableFilters();
        
        // After resetting, send the filter-reset event
        $this->dispatch('filter-reset');
    }
    
    // Refresh widget when search query changes
    public function updatedTableSearch(): void
    {
        // Get current filters
        $filters = $this->getTableFiltersForm()->getRawState();
        
        // Dispatch event to refresh widget
        $this->dispatch('filament.table.filtered', $filters);
    }
}
