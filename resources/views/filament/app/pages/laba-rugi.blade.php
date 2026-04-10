<x-filament-panels::page>
    @php
        $bulan = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
        $data = $this->getLabaRugiData();

        function formatLabaRugiNumber($number, $useParentheses = false)
        {
            if ($useParentheses && $number < 0) {
                return '(' . number_format(abs($number), 0, ',', '.') . ')';
            }

            return number_format($number, 0, ',', '.');
        }
    @endphp

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <style>
            .dark .bg-white.rounded-xl.shadow-sm.border.border-gray-200.p-6 {
                background-color: #0b0b0d;
                border-color: #3f3f46;
            }

            .dark h2 {
                color: #f4f4f5;
            }

            .dark .text-gray-600 {
                color: #d4d4d8;
            }

            .dark table.w-full th,
            .dark table.w-full td {
                border-color: #3f3f46;
                color: #e5e7eb;
            }

            .dark .border-b-2.border-gray-300 {
                border-bottom-color: #52525b;
            }

            .dark .bg-gray-100 {
                background-color: #27272a;
            }

            .dark .bg-gray-50 {
                background-color: #1f1f23;
            }

            .dark .border-b.border-gray-200 {
                border-bottom-color: #3f3f46;
            }

            .dark tr.hover\:bg-gray-50:hover {
                background-color: #334155;
            }

            .dark .border-t.border-gray-300 {
                border-top-color: #52525b;
            }

            .dark .border-t-2.border-gray-500 {
                border-top-color: #e5e7eb;
            }

            .dark .font-bold.bg-gray-50 {
                background-color: #1f1f23;
                color: #f4f4f5;
            }

            .dark .text-green-600 {
                color: #22c55e;
            }

            .dark .text-red-600 {
                color: #ef4444;
            }

            @media print {

                .dark table.w-full th,
                .dark table.w-full td {
                    color: #000;
                    border-color: #000;
                }

                .dark .bg-gray-100,
                .dark .bg-gray-50 {
                    background-color: #ddd !important;
                    color: #000 !important;
                }

                .dark .border-b-2.border-gray-300,
                .dark .border-b.border-gray-200,
                .dark .border-t.border-gray-300,
                .dark .border-t-2.border-gray-500 {
                    border-color: #000 !important;
                }
            }
        </style>

        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold">Laporan Laba Rugi</h2>
            <p class="text-gray-600">Periode {{ $bulan[$this->month] }} {{ $this->year }}</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <tbody>
                    <tr class="border-b-2 border-gray-300">
                        <td colspan="2" class="text-lg font-bold py-3">PENDAPATAN</td>
                    </tr>

                    @php $totalPendapatanDisplay = 0; @endphp
                    @foreach ($data['items'] as $item)
                        @if ($item['category'] === 'Pendapatan')
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="py-1 pl-8">{{ $item['code'] }} {{ $item['name'] }}</td>
                                <td class="text-right py-1 pr-2">{{ number_format($item['amount'], 0, ',', '.') }}</td>
                            </tr>
                            @php $totalPendapatanDisplay += $item['amount']; @endphp
                        @endif
                    @endforeach

                    <tr class="border-t border-gray-300 font-bold bg-gray-50">
                        <td class="py-3 pl-4">TOTAL PENDAPATAN</td>
                        <td class="text-right py-3 pr-2">{{ number_format($totalPendapatanDisplay, 0, ',', '.') }}</td>
                    </tr>

                    <tr class="border-b-2 border-gray-300">
                        <td colspan="2" class="text-lg font-bold py-3">BEBAN BIAYA</td>
                    </tr>

                    @php $totalBebanDisplay = 0; @endphp
                    @foreach ($data['items'] as $item)
                        @if ($item['category'] === 'Beban')
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="py-1 pl-8">{{ $item['code'] }} {{ $item['name'] }}</td>
                                <td class="text-right py-1 pr-2">{{ formatLabaRugiNumber($item['amount'], true) }}</td>
                            </tr>
                            @php $totalBebanDisplay += $item['amount']; @endphp
                        @endif
                    @endforeach

                    <tr class="border-t border-gray-300 font-bold bg-gray-50">
                        <td class="py-3 pl-4">TOTAL BEBAN BIAYA</td>
                        <td class="text-right py-3 pr-2">{{ formatLabaRugiNumber($totalBebanDisplay, true) }}</td>
                    </tr>

                    <tr class="border-b-2 border-gray-300">
                        <td colspan="2" class="text-lg font-bold py-3">PENGHASILAN (BIAYA) LUAR USAHA</td>
                    </tr>

                    @php $totalExternalDisplay = 0; @endphp
                    @foreach ($data['items'] as $item)
                        @if ($item['category'] === 'Penghasilan (Biaya) Luar Usaha')
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="py-1 pl-8">{{ $item['code'] }} {{ $item['name'] }}</td>
                                <td class="text-right py-1 pr-2">{{ preg_match('/^AO-7/', $item['code']) ? formatLabaRugiNumber($item['amount'], true) : number_format($item['amount'], 0, ',', '.') }}</td>
                            </tr>
                            @php $totalExternalDisplay += $item['amount']; @endphp
                        @endif
                    @endforeach

                    <tr class="border-t border-gray-300 font-bold bg-gray-50">
                        <td class="py-3 pl-4">TOTAL PENGHASILAN (BIAYA) LUAR USAHA</td>
                        <td class="text-right py-3 pr-2">{{ formatLabaRugiNumber($totalExternalDisplay, true) }}</td>
                    </tr>

                    <tr class="border-t-2 border-gray-500 font-bold text-lg">
                        <td class="py-4 pl-4">{{ $data['labaRugiBersih'] >= 0 ? 'LABA' : 'RUGI' }} BERSIH</td>
                        <td class="text-right py-4 pr-2 {{ $data['labaRugiBersih'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format(abs($data['labaRugiBersih']), 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <style>
        @media print {
            .filament-main-content {
                padding: 0 !important;
            }

            .bg-white {
                box-shadow: none !important;
                border: none !important;
            }

            button {
                display: none !important;
            }

            .filament-main {
                padding: 0 !important;
                margin: 0 !important;
            }

            @page {
                margin: 2cm;
            }
        }
    </style>
</x-filament-panels::page>
