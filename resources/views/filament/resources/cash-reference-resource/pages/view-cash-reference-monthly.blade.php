<x-filament-panels::page>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden mb-6">
        <div class="px-6 py-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Monthly Transactions - {{ $record->name }}</h2>
            <div class="mt-2">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $record->description ?? 'No description available' }}</p>
            </div>
        </div>
    </div>
    
    <div class="mb-4">
        <p class="text-sm text-gray-600 dark:text-gray-400">This report shows transaction summaries grouped by month and year. Click on "View Transactions" to see detailed transactions for a specific month.</p>
    </div>
    
    {{ $this->table }}
</x-filament-panels::page> 