<x-filament-panels::page>
    @php
        $year = request()->query('year');
        $month = (int) request()->query('month');
        $monthName = \Carbon\Carbon::create()->month($month)->format('F');
        
        // Calculate previous month's balance
        $prevMonth = $month - 1;
        $prevYear = (int)$year;
        
        if ($prevMonth === 0) {
            $prevMonth = 12;
            $prevYear = $prevYear - 1;
        }
        
        $lastDayPrevMonth = \Carbon\Carbon::create($prevYear, $prevMonth)->endOfMonth()->format('Y-m-d');
        
        $prevBalance = \App\Models\CashReport::where('cash_reference_id', $record->id)
            ->where('transaction_date', '<=', $lastDayPrevMonth)
            ->sum(\Illuminate\Support\Facades\DB::raw('debit_amount - credit_amount'));
    @endphp
    
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden mb-6">
        <div class="px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Saldo Awal</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Balance from previous month</p>
                </div>
                <div class="text-xl font-bold {{ $prevBalance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ number_format((float) $prevBalance, 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>
    
    {{ $this->table }}
</x-filament-panels::page> 