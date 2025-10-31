<div class="space-y-4">
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <div class="grid grid-cols-3 gap-4 text-sm">
            <div>
                <span class="font-semibold text-gray-700 dark:text-gray-300">Nama Staff:</span>
                <p class="text-gray-900 dark:text-gray-100">{{ $record->staff->name }}</p>
            </div>
            <div>
                <span class="font-semibold text-gray-700 dark:text-gray-300">Posisi:</span>
                <p class="text-gray-900 dark:text-gray-100">{{ $record->staff->positionReference->name ?? '-' }}</p>
            </div>
            <div>
                <span class="font-semibold text-gray-700 dark:text-gray-300">Total Bonus:</span>
                <p class="text-lg font-bold text-primary-600 dark:text-primary-400">Rp {{ number_format($record->amount, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-100 dark:bg-gray-700">
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">No</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Deskripsi Project</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Client</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">Tanggal Project</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Bonus (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($caseProjectDetails as $index => $detail)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center">{{ $index + 1 }}</td>
                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-3">{{ $detail->caseProject->description }}</td>
                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-3">{{ $detail->caseProject->client->company_name ?? '-' }}</td>
                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center">{{ \Carbon\Carbon::parse($detail->caseProject->project_date)->format('d M Y') }}</td>
                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right font-semibold">{{ number_format($detail->bonus, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="border border-gray-300 dark:border-gray-600 px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada detail bonus
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="bg-primary-50 dark:bg-primary-900/20">
                    <th colspan="4" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right font-bold text-gray-900 dark:text-gray-100">TOTAL BONUS:</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right font-bold text-primary-700 dark:text-primary-400">{{ number_format($record->amount, 0, ',', '.') }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

    @if ($caseProjectDetails->count() > 0)
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <div class="text-sm text-blue-800 dark:text-blue-300">
                    <p class="font-semibold mb-1">Informasi</p>
                    <p>Bonus ini berasal dari {{ $caseProjectDetails->count() }} case project yang diselesaikan dalam periode cut off ini.</p>
                </div>
            </div>
        </div>
    @endif
</div>
