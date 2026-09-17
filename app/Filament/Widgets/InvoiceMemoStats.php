<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\CostListInvoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class InvoiceMemoStats extends BaseWidget
{
    protected static ?string $widgetId = 'invoice-memo-stats';

    protected static bool $isLazy = false;

    public array $tableFilters = [];

    protected static ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 4;
    }

    public function mount(): void
    {
        $this->tableFilters = request('tableFilters', []);

        $fullUrl = request()->fullUrl();
        $baseUrl = url()->current();

        if ($fullUrl === $baseUrl) {
            $this->tableFilters = [];
        }
    }

    #[On('filament.table.filtered')]
    public function handleTableFiltered(mixed $data): void
    {
        $this->tableFilters = $data ?? [];
        $this->dispatch('refresh');
    }

    #[On('filament.widget-refresh')]
    public function refresh(?array $params = null): void
    {
        if ($params && isset($params['filters'])) {
            $this->tableFilters = $params['filters'] ?? [];
        } else {
            $this->tableFilters = request('tableFilters', []);
        }

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
        $this->tableFilters = [];
        $this->dispatch('refresh');
    }

    protected function getStats(): array
    {
        try {
            // Base query for Memo invoices
            $query = Invoice::query()->whereNotNull('memo_id');

            if (!empty($this->tableFilters)) {
                // Date range filter
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
                } elseif (isset($this->tableFilters['year']) && !empty($this->tableFilters['year']['value'])) {
                    $year = $this->tableFilters['year']['value'];
                    $query->whereYear('invoice_date', $year);
                }

                if (isset($this->tableFilters['month']) && !empty($this->tableFilters['month']['value'])) {
                    $month = $this->tableFilters['month']['value'];
                    $query->whereMonth('invoice_date', $month);
                }

                // Type filter
                if (isset($this->tableFilters['invoice_type']) && !empty($this->tableFilters['invoice_type']['value'])) {
                    $type = $this->tableFilters['invoice_type']['value'];
                    $query->where('invoice_type', $type);
                }

                // Status filter
                if (isset($this->tableFilters['invoice_status']) && !empty($this->tableFilters['invoice_status']['value'])) {
                    $status = $this->tableFilters['invoice_status']['value'];
                    $query->where('invoice_status', $status);
                }
            }

            $totalQuery = clone $query;
            $paidQuery = clone $query;
            $unpaidQuery = clone $query;
            $overdueQuery = clone $query;

            $totalInvoices = $totalQuery->count();
            $paidInvoices = $paidQuery->where('invoice_status', 'paid')->count();
            $unpaidInvoices = $unpaidQuery->where('invoice_status', 'unpaid')->count();
            $overdueInvoices = $overdueQuery->where('invoice_status', 'overdue')->count();

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
            Log::error('Error in InvoiceMemoStats widget: ' . $e->getMessage());
            return [
                Stat::make('Total Invoices', 0)->description('Error loading data'),
                Stat::make('Paid Invoices', 0)->description('Error loading data'),
                Stat::make('Unpaid Invoices', 0)->description('Error loading data'),
                Stat::make('Overdue Invoices', 0)->description('Error loading data'),
            ];
        }
    }

    public static function canView(): bool
    {
        return true;
    }
}
