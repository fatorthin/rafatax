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
    
    <h2 class="text-xl font-bold mb-4">Histori Transaksi Kas/Bank</h2>
    {{ $this->table }}
</x-filament-panels::page> 