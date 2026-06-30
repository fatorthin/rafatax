@php
    $invoices = \App\Models\Invoice::where('client_id', $clientId)
        ->orWhereHas('mou', fn($q) => $q->where('client_id', $clientId))
        ->get();
@endphp

<div class="space-y-4">
    @if($invoices->isEmpty())
        <div class="p-4 text-center text-sm text-gray-500 dark:text-gray-400">
            Tidak ada invoice untuk klien ini.
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">No Invoice</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($invoices as $invoice)
                        <tr class="bg-white hover:bg-gray-50 dark:bg-gray-900 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $invoice->invoice_number }}</td>
                            <td class="px-4 py-3">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $invoice->invoice_status === 'paid' ? 'bg-green-50 text-green-700 ring-green-600/20' : 'bg-yellow-50 text-yellow-800 ring-yellow-600/20' }}">
                                    {{ strtoupper($invoice->invoice_status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
