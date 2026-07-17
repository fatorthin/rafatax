<div class="space-y-6 p-4">
    <!-- Client Info Header -->
    <div class="flex flex-col justify-between border-b border-gray-100 pb-4 sm:flex-row dark:border-gray-800">
        <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                Kartu Piutang Client: [{{ $client->code }}] {{ $client->company_name }}
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Detail riwayat mutasi piutang secara kronologis.
            </p>
        </div>
        <div class="mt-2 sm:mt-0 text-left sm:text-right">
            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium block">Saldo Awal Piutang:</span>
            <span class="text-lg font-semibold text-gray-900 dark:text-white">
                Rp {{ number_format(isset($transactions[0]) && $transactions[0]['type'] === 'Saldo Awal' ? $transactions[0]['debit'] : 0, 0, ',', '.') }}
            </span>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">No</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipe</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Referensi</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Deskripsi</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Debit (+)</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kredit (-)</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Saldo Piutang</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-800">
                @php $no = 1; @endphp
                @forelse($transactions as $tx)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $no++ }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white whitespace-nowrap">
                            {{ $tx['date'] ? \Carbon\Carbon::parse($tx['date'])->translatedFormat('d-M-Y') : '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                            @if($tx['type'] === 'Saldo Awal')
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                    {{ $tx['type'] }}
                                </span>
                            @elseif($tx['type'] === 'Sales Invoice')
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                    Invoice
                                </span>
                            @elseif($tx['type'] === 'Sales Receipt')
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                    Pembayaran
                                </span>
                            @else
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-400">
                                    {{ $tx['type'] }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white whitespace-nowrap font-medium">{{ $tx['ref'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">{{ $tx['description'] }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white whitespace-nowrap font-semibold">
                            {{ $tx['debit'] > 0 ? 'Rp ' . number_format($tx['debit'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white whitespace-nowrap font-semibold">
                            {{ $tx['kredit'] > 0 ? 'Rp ' . number_format($tx['kredit'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-gray-950 dark:text-white whitespace-nowrap font-bold">
                            Rp {{ number_format($tx['running_balance'], 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-sm text-center text-gray-500 dark:text-gray-400">
                            Tidak ada data transaksi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
