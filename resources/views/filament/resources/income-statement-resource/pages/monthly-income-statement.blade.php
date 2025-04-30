<x-filament::page>
    <div class="space-y-6">
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <table class="w-full table-auto divide-y divide-gray-200 text-left dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-white">
                    <tr>
                        @foreach ($columns as $column)
                            <th class="px-4 py-3.5 text-sm font-semibold whitespace-nowrap dark:bg-white @if($column['align'] ?? false === 'end') text-right @endif"
                                style="color: black !important; background-color: white !important;">
                                {{ $column['label'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-gray-700 dark:text-white">
                    @foreach ($records as $record)
                        @php
                            // Determine row type and set classes
                            $rowType = '';
                            $rowClasses = 'hover:bg-gray-50 dark:hover:bg-gray-700/50';
                            
                            if ($record->coa_code === 'TOTAL') {
                                $rowType = 'total';
                                $rowClasses = 'font-extrabold text-lg bg-gray-100 dark:bg-white';
                            } elseif ($record->coa_code === 'SUBTOTAL') {
                                $rowType = 'subtotal';
                                $rowClasses = 'font-bold bg-gray-50 dark:bg-white';
                            }
                        @endphp
                        
                        <tr class="{{ $rowClasses }}" @if($rowType) style="color: black !important; background-color: white !important;" @endif>
                            @foreach ($columns as $column)
                                <td class="px-4 py-3 text-sm @if($column['align'] ?? false === 'end') text-right @endif"
                                    @if($rowType) style="color: black !important;" @endif>
                                    @php
                                        $colName = $column['name'];
                                        $value = $record->$colName ?? null;
                                        // Format angka jika kolom bukan coa_code atau coa_name
                                        if (!in_array($colName, ['coa_code', 'coa_name']) && is_numeric($value)) {
                                            $value = number_format((float) $value, 0, ',', '.');
                                        }
                                    @endphp
                                    {{ $value }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament::page> 