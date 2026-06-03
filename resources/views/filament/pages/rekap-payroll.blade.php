<x-filament-panels::page>
    @php
        $payrollData = $this->getPayrollData();
        $matrix = $payrollData['matrix'];
        $cols = $payrollData['cols'];
        
        $rowKeys = [
            'salary' => 'Gaji Pokok ALL',
            'overtime' => 'Lemburan',
            'visit_solo' => 'Transport Solo',
            'visit_luar' => 'Transport luar Solo',
            'bonus_lain' => 'Bonus ALL (Project)',
            'bonus_position' => 'Tunjab',
            'bonus_competency' => 'Tunkomp',
            'lain2' => 'Lain2',
            'potongan_all' => 'Potongan ALL',
            'total_potongan_dan_bonus' => 'Total Potongan dan Bonus',
            'gaji_dikeluarkan' => 'Gaji Dikeluarkan',
            'bonus_tgl_15' => 'BONUS TGL 15 / Bulan nya',
            'total_gaji_dan_bonus' => 'TOTAL GAJI DAN BONUS',
        ];
    @endphp

    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
        <div class="mb-4 text-center border-b border-gray-100 dark:border-gray-800 pb-4">
            <h3 class="text-xl font-bold text-gray-950 dark:text-white uppercase tracking-wider">Rekap Payroll Gaji & Bonus</h3>
            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Tahun {{ $this->year }}</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 border border-gray-200 dark:border-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider border border-gray-200 dark:border-gray-700 min-w-[200px]">Keterangan</th>
                        @foreach ($cols as $colName)
                            <th scope="col" class="px-3 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 w-[110px]">{{ $colName }}</th>
                        @endforeach
                        <th scope="col" class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider border border-gray-200 dark:border-gray-700 w-[150px]">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach ($rowKeys as $key => $label)
                        @php
                            $isSummaryRow = in_array($key, ['total_potongan_dan_bonus', 'gaji_dikeluarkan', 'total_gaji_dan_bonus']);
                            $isYellowRow = in_array($key, ['total_potongan_dan_bonus', 'gaji_dikeluarkan', 'bonus_tgl_15']);
                            $isBlueRow = $key === 'total_gaji_dan_bonus';
                            
                            $rowClass = '';
                            if ($isYellowRow) {
                                $rowClass = 'bg-yellow-50/70 dark:bg-yellow-950/20 font-bold';
                            } elseif ($isBlueRow) {
                                $rowClass = 'bg-blue-50/70 dark:bg-blue-950/20 font-extrabold';
                            } elseif ($isSummaryRow) {
                                $rowClass = 'font-bold';
                            }
                        @endphp
                        <tr class="{{ $rowClass }} hover:bg-gray-50/50 dark:hover:bg-gray-800/20 transition-colors">
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-200 border border-gray-200 dark:border-gray-700 {{ $isSummaryRow ? 'font-semibold' : '' }}">{{ $label }}</td>
                            
                            @php $rowTotal = 0; @endphp
                            @foreach ($cols as $colName)
                                @php
                                    $val = $matrix[$key][$colName];
                                    $rowTotal += $val;
                                    
                                    $valCellClass = 'text-gray-500 dark:text-gray-400';
                                    if ($isSummaryRow || $isYellowRow) {
                                        $valCellClass = 'text-gray-900 dark:text-gray-200 font-bold';
                                    } elseif ($isBlueRow) {
                                        $valCellClass = 'text-primary-600 dark:text-primary-400 font-extrabold';
                                    }
                                @endphp
                                <td class="px-3 py-2 text-sm text-right border border-gray-200 dark:border-gray-700 {{ $valCellClass }}">
                                    {{ $val != 0 ? number_format($val, 0, ',', '.') : '-' }}
                                </td>
                            @endforeach
                            
                            <td class="px-4 py-2 text-sm text-right border border-gray-200 dark:border-gray-700 {{ $isBlueRow ? 'text-primary-600 dark:text-primary-400 font-extrabold' : 'text-gray-900 dark:text-white font-bold' }} bg-gray-50 dark:bg-gray-800/50">
                                {{ $rowTotal != 0 ? number_format($rowTotal, 0, ',', '.') : '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
