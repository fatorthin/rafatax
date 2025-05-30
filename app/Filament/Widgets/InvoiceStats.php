<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\CostListInvoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\On;

class InvoiceStats extends BaseWidget
{
    // Set widget ID for targeting with events
    protected static ?string $widgetId = 'invoice-stats';
    
    // Filter properties
    public $tableFilters = [];
    
    protected static ?string $pollingInterval = null;
    
    protected function getColumns(): int
    {
        return 4;
    }
    
    public function mount(): void
    {
        // Get filters from URL on initial load
        $this->tableFilters = request('tableFilters', []);
        
        // Check if URL is clean (no query parameters)
        $fullUrl = request()->fullUrl();
        $baseUrl = url()->current();
        
        // If URL is base URL with no parameters, reset filters
        if ($fullUrl === $baseUrl) {
            $this->tableFilters = [];
        }
    }
    
    #[On('filament.table.filtered')]
    public function handleTableFiltered($data): void
    {
        // Update filters when table is filtered
        $this->tableFilters = $data ?? [];
        $this->dispatch('refresh');
    }
    
    #[On('filament.widget-refresh')]
    public function refresh(?array $params = null): void
    {
        if ($params && isset($params['filters'])) {
            $this->tableFilters = $params['filters'] ?? [];
        } else {
            // If no filters provided, get from request
            $this->tableFilters = request('tableFilters', []);
        }
        
        // Check if URL has no parameters - then clear filters
        $fullUrl = request()->fullUrl();
        $baseUrl = url()->current();
        
        if ($fullUrl === $baseUrl) {
            $this->tableFilters = [];
        }
        
        $this->dispatch('refresh');
    }
    
    #[On('filter-reset')]
    public function handleFilterReset(): void
    {
        // Clear all filters when filter reset event is received
        $this->tableFilters = [];
        $this->dispatch('refresh');
    }
    
    protected function getStats(): array
    {
        try {
            // Start with a base query
            $query = Invoice::query();
            
            // Apply filters to query if there are any
            if (!empty($this->tableFilters)) {
                // Date range filter (combined year and month)
                if (isset($this->tableFilters['date_range'])) {
                    $dateRange = $this->tableFilters['date_range'];
                    
                    if (isset($dateRange['year']) && !empty($dateRange['year'])) {
                        $year = $dateRange['year'];
                        $query->whereYear('invoice_date', $year);
                    }
                    
                    if (isset($dateRange['month']) && !empty($dateRange['month'])) {
                        $month = $dateRange['month'];
                        $query->whereMonth('invoice_date', $month);
                    }
                }
                
                // Backwards compatibility for separate year filter
                elseif (isset($this->tableFilters['year']) && !empty($this->tableFilters['year']['value'])) {
                    $year = $this->tableFilters['year']['value'];
                    $query->whereYear('invoice_date', $year);
                }
                
                // Backwards compatibility for separate month filter
                if (isset($this->tableFilters['month']) && !empty($this->tableFilters['month']['value'])) {
                    $month = $this->tableFilters['month']['value'];
                    $query->whereMonth('invoice_date', $month);
                }
                
                // Client filter
                if (isset($this->tableFilters['client']) && !empty($this->tableFilters['client']['value'])) {
                    $client = $this->tableFilters['client']['value'];
                    $query->whereHas('mou', function (Builder $q) use ($client) {
                        $q->whereHas('client', function (Builder $q2) use ($client) {
                            $q2->where('id', $client);
                        });
                    });
                }
                
                // Type filter
                if (isset($this->tableFilters['type']) && !empty($this->tableFilters['type']['value'])) {
                    $type = $this->tableFilters['type']['value'];
                    $query->whereHas('mou', function (Builder $q) use ($type) {
                        $q->where('type', $type);
                    });
                }
                
                // Status filter - only apply this to the main query if it's not related to the widgets we're showing
                if (isset($this->tableFilters['invoice_status']) && !empty($this->tableFilters['invoice_status']['value'])) {
                    $status = $this->tableFilters['invoice_status']['value'];
                    $query->where('invoice_status', $status);
                }
            }
            
            // Clone the base query for different stats
            $totalQuery = clone $query;
            $paidQuery = clone $query;
            $unpaidQuery = clone $query;
            $overdueQuery = clone $query;
            
            // Get total invoices count
            $totalInvoices = $totalQuery->count();
            
            // Get paid invoices count
            $paidInvoices = $paidQuery->where('invoice_status', 'paid')->count();
            
            // Get unpaid invoices count
            $unpaidInvoices = $unpaidQuery->where('invoice_status', 'unpaid')->count();
            
            // Get overdue invoices count (due date has passed and still unpaid)
            $overdueInvoices = $overdueQuery
                ->where('invoice_status', 'unpaid')
                ->where('due_date', '<', now()->format('Y-m-d'))
                ->count();
            
            // Show icon for filtered data
            $icon = !empty($this->tableFilters) ? 'heroicon-o-funnel' : null;
            $description = !empty($this->tableFilters) ? 'Filtered data' : 'All data';
            
            return [
                Stat::make('Total Invoices', $totalInvoices)
                    ->icon($icon)
                    ->description($description),
                Stat::make('Paid Invoices', $paidInvoices)
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->description($description),
                Stat::make('Unpaid Invoices', $unpaidInvoices)
                    ->icon('heroicon-o-clock')
                    ->color('danger')
                    ->description($description),
                Stat::make('Overdue Invoices', $overdueInvoices)
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->description($description),
            ];
        } catch (\Exception $e) {
            Log::error('Error in InvoiceStats widget: ' . $e->getMessage());
            return [
                Stat::make('Total Invoices', 0)
                    ->description('Error loading data'),
                Stat::make('Paid Invoices', 0)
                    ->description('Error loading data'),
                Stat::make('Unpaid Invoices', 0)
                    ->description('Error loading data'),
                Stat::make('Overdue Invoices', 0)
                    ->description('Error loading data'),
            ];
        }
    }
    
    public static function canView(): bool
    {
        return true;
    }
} 