<x-filament-panels::page>
    @php
        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $data = $this->getLabaRugiData();

        // Helper function to format numbers with parentheses for negative values
        function formatNumber($number, $isNegative = false, $forceParentheses = false) {
            if ($forceParentheses && $isNegative) {
                return '(' . number_format(abs($number), 0, ',', '.') . ')';
            }
            return number_format($number, 0, ',', '.');
        }
    @endphp

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold">Laporan Laba Rugi</h2>
            <p class="text-gray-600">Periode {{ $bulan[$month] }} {{ $year }}</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-300">
                        <th class="px-4 py-2 text-left">Kode Akun</th>
                        <th class="px-4 py-2 text-left">Nama Akun</th>
                        <th class="px-4 py-2 text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Pendapatan -->
                    <tr class="bg-gray-100">
                        <td colspan="3" class="px-4 py-2 font-bold">Pendapatan</td>
                    </tr>
                    @php $totalPendapatanDisplay = 0; @endphp
                    @foreach($data['items'] as $item)
                        @if($item['category'] === 'Pendapatan')
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $item['code'] }}</td>
                                <td class="px-4 py-2">{{ $item['name'] }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($item['amount'], 0, ',', '.') }}</td>
                            </tr>
                            @php $totalPendapatanDisplay += $item['amount']; @endphp
                        @endif
                    @endforeach
                    <tr class="border-t border-gray-300 font-bold bg-gray-50">
                        <td colspan="2" class="px-4 py-2 text-right">Total Pendapatan</td>
                        <td class="px-4 py-2 text-right">{{ number_format($totalPendapatanDisplay, 0, ',', '.') }}</td>
                    </tr>

                    <!-- Beban -->
                    <tr class="bg-gray-100 mt-4">
                        <td colspan="3" class="px-4 py-2 font-bold">Beban Biaya</td>
                    </tr>
                    @php $totalBebanDisplay = 0; @endphp
                    @foreach($data['items'] as $item)
                        @if($item['category'] === 'Beban')
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $item['code'] }}</td>
                                <td class="px-4 py-2">{{ $item['name'] }}</td>
                                <td class="px-4 py-2 text-right">{{ formatNumber($item['amount'], $item['is_negative'] ?? false, true) }}</td>
                            </tr>
                            @php $totalBebanDisplay += $item['amount']; @endphp
                        @endif
                    @endforeach
                    <tr class="border-t border-gray-300 font-bold bg-gray-50">
                        <td colspan="2" class="px-4 py-2 text-right">Total Beban Biaya</td>
                        <td class="px-4 py-2 text-right">{{ formatNumber($totalBebanDisplay, $totalBebanDisplay < 0, true) }}</td>
                    </tr>

                    <!-- Laba/Rugi Bersih -->
                    <tr class="border-t-2 border-gray-500 font-bold text-lg">
                        <td colspan="2" class="px-4 py-3 text-right">
                            {{ ($data['labaRugiBersih'] >= 0) ? 'Laba' : 'Rugi' }} Bersih
                        </td>
                        <td class="px-4 py-3 text-right {{ ($data['labaRugiBersih'] >= 0) ? 'text-green-600' : 'text-red-600' }}">
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