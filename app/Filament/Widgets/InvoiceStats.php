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
    
    protected function getColumns(): int
    {
        return 3;
    }
    
    #[On('filament.widget-refresh')]
    public function refresh(?array $params = null): void
    {
        if ($params && isset($params['filters'])) {
            $this->tableFilters = $params['filters'];
            Log::info('Widget refreshed with filters:', $this->tableFilters);
        } else {
            // If no filters provided, get from request
            $this->tableFilters = request('tableFilters', []);
            Log::info('Widget refreshed with request filters:', $this->tableFilters);
        }
        
        $this->dispatch('refresh');
    }
    
    protected function getStats(): array
    {
        try {
            Log::info('InvoiceStats widget refreshing with filters:', $this->tableFilters);
            
            // Start with a base query
            $query = Invoice::query();
            
            // Apply filters to query
            if (!empty($this->tableFilters)) {
                // Year filter
                if (isset($this->tableFilters['year']) && !empty($this->tableFilters['year']['value'])) {
                    $year = $this->tableFilters['year']['value'];
                    $query->whereRaw('YEAR(invoice_date) = ?', [$year]);
                    Log::info("Applied year filter: $year");
                }
                
                // Month filter
                if (isset($this->tableFilters['month']) && !empty($this->tableFilters['month']['value'])) {
                    $month = $this->tableFilters['month']['value'];
                    $query->whereRaw('MONTH(invoice_date) = ?', [$month]);
                    Log::info("Applied month filter: $month");
                }
                
                // Client filter
                if (isset($this->tableFilters['client']) && !empty($this->tableFilters['client']['value'])) {
                    $client = $this->tableFilters['client']['value'];
                    $query->whereHas('mou', function (Builder $q) use ($client) {
                        $q->whereHas('client', function (Builder $q2) use ($client) {
                            $q2->where('id', $client);
                        });
                    });
                    Log::info("Applied client filter: $client");
                }
                
                // Type filter
                if (isset($this->tableFilters['type']) && !empty($this->tableFilters['type']['value'])) {
                    $type = $this->tableFilters['type']['value'];
                    $query->whereHas('mou', function (Builder $q) use ($type) {
                        $q->where('type', $type);
                    });
                    Log::info("Applied type filter: $type");
                }
                
                // Status filter - most important for our issue
                if (isset($this->tableFilters['invoice_status']) && !empty($this->tableFilters['invoice_status']['value'])) {
                    $status = $this->tableFilters['invoice_status']['value'];
                    $query->where('invoice_status', $status);
                    Log::info("Applied status filter: $status");
                }
            }
            
            // Get the invoice IDs after applying filters
            $invoiceIds = $query->pluck('id')->toArray();
            Log::info('Found ' . count($invoiceIds) . ' invoices after filtering');
            
            // Calculate stats based on the filtered invoices
            $totalInvoices = count($invoiceIds);
            $totalAmount = empty($invoiceIds) ? 0 : CostListInvoice::whereIn('invoice_id', $invoiceIds)->sum('amount');
            $avgAmount = $totalInvoices > 0 ? ($totalAmount / $totalInvoices) : 0;
            
            // Show icon for filtered data
            $icon = !empty($this->tableFilters) ? 'heroicon-o-funnel' : null;
            $description = !empty($this->tableFilters) ? 'Filtered data' : 'All data';
            
            return [
                Stat::make('Total Invoices', $totalInvoices)
                    ->icon($icon)
                    ->description($description),
                Stat::make('Total Amount', 'IDR ' . number_format($totalAmount, 0, ',', '.'))
                    ->icon($icon)
                    ->description($description),
                Stat::make('Average Amount', 'IDR ' . number_format($avgAmount, 0, ',', '.'))
                    ->icon($icon)
                    ->description($description),
            ];
        } catch (\Exception $e) {
            Log::error('Error in InvoiceStats widget: ' . $e->getMessage());
            return [
                Stat::make('Total Invoices', 0)
                    ->description('Error loading data'),
                Stat::make('Total Amount', 'IDR 0')
                    ->description('Error loading data'),
                Stat::make('Average Amount', 'IDR 0')
                    ->description('Error loading data'),
            ];
        }
    }
    
    public static function canView(): bool
    {
        return true;
    }
} 