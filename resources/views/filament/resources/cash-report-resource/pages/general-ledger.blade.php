<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        <div class="space-y-8">
            @forelse ($groupedTransactions as $coaId => $transactions)
                @php
                    $coa = $coas[$coaId] ?? null;
                    $balance = 0;
                    $totalDebit = 0;
                    $totalCredit = 0;
                @endphp

                @if ($coa)
                    <div
                        class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 dark:bg-gray-900 dark:border-gray-800">
                        <div class="mb-4">
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ $coa->code }} -
                                {{ $coa->name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $coa->type }}</p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                                <thead
                                    class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">Tanggal</th>
                                        <th scope="col" class="px-6 py-3">Deskripsi</th>
                                        <th scope="col" class="px-6 py-3">Kas/Bank Reference</th>
                                        <th scope="col" class="px-6 py-3 text-right">Debit</th>
                                        <th scope="col" class="px-6 py-3 text-right">Kredit</th>
                                        <th scope="col" class="px-6 py-3 text-right">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($transactions as $transaction)
                                        @php
                                            $debit = $transaction->debit_amount ?? 0;
                                            $credit = $transaction->credit_amount ?? 0;

                                            // Simple logic: Assets/Expenses usually increase on Debit
                                            // Liability/Equity/Income usually increase on Credit
                                            // Adjust balance calculation based on Account Type if needed,
                                            // but standard GL usually shows running balance based on D-C or C-D

                                            // Let's assume standard Asset behavior for now: Balance = Balance + Debit - Credit
// If it's a liability/income, it might be Credit - Debit.
                                            // To keep it simple visually:
                                            $balance += $debit - $credit;

                                            $totalDebit += $debit;
                                            $totalCredit += $credit;
                                        @endphp
                                        <tr class="bg-white border-b dark:bg-gray-900 dark:border-gray-700">
                                            <td class="px-6 py-4">
                                                {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y') }}
                                            </td>
                                            <td class="px-6 py-4">{{ $transaction->description }}</td>
                                            <td class="px-6 py-4">{{ $transaction->cashReference->name ?? '-' }}</td>
                                            <td class="px-6 py-4 text-right text-green-600 font-medium">
                                                {{ $debit > 0 ? number_format($debit, 0, ',', '.') : '-' }}
                                            </td>
                                            <td class="px-6 py-4 text-right text-red-600 font-medium">
                                                {{ $credit > 0 ? number_format($credit, 0, ',', '.') : '-' }}
                                            </td>
                                            <td class="px-6 py-4 text-right font-bold">
                                                {{ number_format($balance, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="font-bold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">
                                        <td class="px-6 py-3" colspan="3">Total</td>
                                        <td class="px-6 py-3 text-right text-green-600">
                                            {{ number_format($totalDebit, 0, ',', '.') }}</td>
                                        <td class="px-6 py-3 text-right text-red-600">
                                            {{ number_format($totalCredit, 0, ',', '.') }}</td>
                                        <td class="px-6 py-3 text-right">{{ number_format($balance, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endif
            @empty
                <div class="text-center p-6 bg-white rounded-xl shadow-sm dark:bg-gray-900">
                    <p class="text-gray-500">Tidak ada data transaksi untuk periode ini.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
