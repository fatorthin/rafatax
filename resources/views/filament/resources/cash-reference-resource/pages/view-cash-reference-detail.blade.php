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
    
    @php
        $debitTotal = App\Models\CashReport::where('cash_reference_id', $record->id)->sum('debit_amount');
        $creditTotal = App\Models\CashReport::where('cash_reference_id', $record->id)->sum('credit_amount');
        $balance = $debitTotal - $creditTotal;
    @endphp
    
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden mt-4">
        <div class="px-6 py-4">
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Total Debit</h3>
                    <p class="text-lg font-bold text-success-500">IDR {{ number_format($debitTotal, 0, ',', '.') }}</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Total Credit</h3>
                    <p class="text-lg font-bold text-danger-500">IDR {{ number_format($creditTotal, 0, ',', '.') }}</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Saldo</h3>
                    <p class="text-lg font-bold {{ $balance >= 0 ? 'text-success-500' : 'text-danger-500' }}">
                        IDR {{ number_format(abs($balance), 0, ',', '.') }} {{ $balance >= 0 ? '(+)' : '(-)' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page> 