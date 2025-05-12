<x-filament-panels::page>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden mb-6">
        <div class="px-6 py-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Informasi Kas/Bank</h2>
            <div class="grid grid-cols-2 gap-4 mt-4">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Nama</p>
                    <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $record->name }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Deskripsi</p>
                    <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $record->description ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Histori Transaksi Kas/Bank</h2>
        <div class="space-x-2">
            <a href="{{ route('filament.admin.resources.cash-reports.create', ['cash_reference_id' => $record->id]) }}" 
               class="filament-button filament-button-size-sm inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors focus:outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset dark:focus:ring-offset-0 min-h-[2rem] px-3 text-sm text-white shadow focus:ring-white border-transparent bg-primary-600 hover:bg-primary-500 focus:bg-primary-700 focus:ring-offset-primary-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>Tambah Transaksi</span>
            </a>
        </div>
    </div>
    
    {{ $this->table }}
</x-filament-panels::page> 