@php
    $debitAmount = $prevBalanceModel->debit_amount;
    $creditAmount = $prevBalanceModel->credit_amount;
    $date = \Carbon\Carbon::parse($prevBalanceModel->transaction_date)->format('d-M-Y');
    $description = $prevBalanceModel->description;
    $balance = $debitAmount - $creditAmount;
@endphp

<div class="filament-tables-footer">
    <div class="border-t dark:border-gray-700">
        <div class="grid grid-cols-7 gap-2 p-2">
            <div class="col-span-1 flex items-center justify-start">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $date }}</span>
            </div>
            <div class="col-span-1 flex items-center justify-start">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">-</span>
            </div>
            <div class="col-span-1 flex items-center justify-start">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">-</span>
            </div>
            <div class="col-span-1 flex items-center justify-start">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $description }}</span>
            </div>
            <div class="col-span-1 flex items-center justify-end">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ number_format((float) $debitAmount, 0, ',', '.') }}</span>
            </div>
            <div class="col-span-1 flex items-center justify-end">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ number_format((float) $creditAmount, 0, ',', '.') }}</span>
            </div>
            <div class="col-span-1 flex items-center justify-end">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ number_format((float) $balance, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div> 