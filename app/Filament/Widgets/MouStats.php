<?php

namespace App\Filament\Widgets;

use App\Models\MoU;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class MouStats extends BaseWidget
{
    // Set widget ID for targeting with events
    protected static ?string $widgetId = 'mou-stats';
    
    // Filter properties
    public $tableFilters = [];
    
    protected static ?string $pollingInterval = null;
    
    protected function getColumns(): int
    {
        return 3;
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
            $query = MoU::query();
            
            // Apply filters to query if there are any
            if (!empty($this->tableFilters)) {
                // Month filter
                if (isset($this->tableFilters['month']) && !empty($this->tableFilters['month']['value'])) {
                    $month = $this->tableFilters['month']['value'];
                    $query->whereMonth('start_date', $month);
                }
                
                // Year filter
                if (isset($this->tableFilters['year']) && !empty($this->tableFilters['year']['value'])) {
                    $year = $this->tableFilters['year']['value'];
                    $query->whereYear('start_date', $year);
                }
                
                // Status filter - only apply to main query if not related to widgets
                if (isset($this->tableFilters['status']) && !empty($this->tableFilters['status']['value'])) {
                    $status = $this->tableFilters['status']['value'];
                    $query->where('status', $status);
                }
                
                // Type filter
                if (isset($this->tableFilters['type']) && !empty($this->tableFilters['type']['value'])) {
                    $type = $this->tableFilters['type']['value'];
                    $query->where('type', $type);
                }
            }
            
            // Clone the base query for different stats
            $totalQuery = clone $query;
            $approvedQuery = clone $query;
            $unapprovedQuery = clone $query;
            
            // Get total MoUs count
            $totalMous = $totalQuery->count();
            
            // Get approved MoUs count
            $approvedMous = $approvedQuery->where('status', 'approved')->count();
            
            // Get unapproved MoUs count
            $unapprovedMous = $unapprovedQuery->where('status', 'unapproved')->count();
            
            // Show icon for filtered data
            $icon = !empty($this->tableFilters) ? 'heroicon-o-funnel' : null;
            $description = !empty($this->tableFilters) ? 'Filtered data' : 'All data';
            
            return [
                Stat::make('Total MoUs', $totalMous)
                    ->icon($icon)
                    ->description($description),
                Stat::make('Approved MoUs', $approvedMous)
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->description($description),
                Stat::make('Unapproved MoUs', $unapprovedMous)
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->description($description),
            ];
        } catch (\Exception $e) {
            Log::error('Error in MouStats widget: ' . $e->getMessage());
            return [
                Stat::make('Total MoUs', 0)
                    ->description('Error loading data'),
                Stat::make('Approved MoUs', 0)
                    ->description('Error loading data'),
                Stat::make('Unapproved MoUs', 0)
                    ->description('Error loading data'),
            ];
        }
    }
    
    public static function canView(): bool
    {
        return true;
    }
} 