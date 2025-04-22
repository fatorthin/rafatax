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
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Balance</h3>
                <p class="text-lg font-bold {{ $balance >= 0 ? 'text-success-500' : 'text-danger-500' }}">
                    IDR {{ number_format(abs($balance), 0, ',', '.') }} {{ $balance >= 0 ? '(+)' : '(-)' }}
                </p>
            </div>
        </div>
    </div>
</div> 