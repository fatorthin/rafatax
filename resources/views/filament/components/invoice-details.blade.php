<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4 rounded-lg bg-gray-50 p-4 text-sm dark:bg-gray-850">
        <div>
            <p class="text-gray-500 dark:text-gray-400">No Invoice</p>
            <p class="font-semibold text-gray-900 dark:text-white">{{ $record->invoice_number }}</p>
        </div>
        <div>
            <p class="text-gray-500 dark:text-gray-400">Tanggal Invoice</p>
            <p class="font-semibold text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($record->invoice_date)->format('d/m/Y') }}</p>
        </div>
        <div>
            <p class="text-gray-500 dark:text-gray-400">Nama Klien</p>
            <p class="font-semibold text-gray-900 dark:text-white">{{ $record->client_name }}</p>
        </div>
        <div>
            <p class="text-gray-500 dark:text-gray-400">Status</p>
            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $record->invoice_status === 'paid' ? 'bg-green-50 text-green-700 ring-green-600/20' : 'bg-yellow-50 text-yellow-800 ring-yellow-600/20' }}">
                {{ strtoupper($record->invoice_status) }}
            </span>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-3">Deskripsi Item</th>
                    <th class="px-4 py-3">CoA</th>
                    <th class="px-4 py-3 text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($record->costListInvoices as $item)
                    <tr class="bg-white hover:bg-gray-50 dark:bg-gray-900 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $item->description }}</td>
                        <td class="px-4 py-3">{{ $item->coa?->code }} - {{ $item->coa?->name }}</td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr class="bg-white dark:bg-gray-900">
                        <td colspan="3" class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">Tidak ada item dalam invoice ini.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="bg-gray-50 font-semibold text-gray-900 dark:bg-gray-800 dark:text-white">
                    <td colspan="2" class="px-4 py-3 text-right">Total</td>
                    <td class="px-4 py-3 text-right">Rp {{ number_format($record->total_amount, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
