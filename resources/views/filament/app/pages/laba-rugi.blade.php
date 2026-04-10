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
        $labaRugiData = $this->getLabaRugiData();
        $labaRugiBersih = $labaRugiData['totalPendapatan'] - $labaRugiData['totalBeban'];
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

                    @foreach ($labaRugiData['pendapatan'] as $item)
                        @if ($item['is_group_header'])
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <td colspan="2" class="font-semibold py-2">{{ $item['name'] }}</td>
                            </tr>
                        @elseif($item['is_group_total'])
                            <tr class="bg-gray-100 border-b border-gray-300">
                                <td class="font-bold py-2 pl-4">{{ $item['name'] }}</td>
                                <td class="text-right font-bold py-2 pr-2">{{ number_format($item['amount'], 0, ',', '.') }}</td>
                            </tr>
                        @else
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="py-1 pl-8">{{ $item['code'] }} {{ $item['name'] }}</td>
                                <td class="text-right py-1 pr-2">{{ number_format($item['amount'], 0, ',', '.') }}</td>
                            </tr>
                        @endif
                    @endforeach

                    <tr class="border-t border-gray-300 font-bold bg-gray-50">
                        <td class="py-3 pl-4">TOTAL PENDAPATAN</td>
                        <td class="text-right py-3 pr-2">{{ number_format($labaRugiData['totalPendapatan'], 0, ',', '.') }}</td>
                    </tr>

                    <tr class="border-b-2 border-gray-300">
                        <td colspan="2" class="text-lg font-bold py-3">BEBAN</td>
                    </tr>

                    @foreach ($labaRugiData['beban'] as $item)
                        @if ($item['is_group_header'])
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <td colspan="2" class="font-semibold py-2">{{ $item['name'] }}</td>
                            </tr>
                        @elseif($item['is_group_total'])
                            <tr class="bg-gray-100 border-b border-gray-300">
                                <td class="font-bold py-2 pl-4">{{ $item['name'] }}</td>
                                <td class="text-right font-bold py-2 pr-2">{{ number_format($item['amount'], 0, ',', '.') }}</td>
                            </tr>
                        @else
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="py-1 pl-8">{{ $item['code'] }} {{ $item['name'] }}</td>
                                <td class="text-right py-1 pr-2">{{ number_format($item['amount'], 0, ',', '.') }}</td>
                            </tr>
                        @endif
                    @endforeach

                    <tr class="border-t border-gray-300 font-bold bg-gray-50">
                        <td class="py-3 pl-4">TOTAL BEBAN</td>
                        <td class="text-right py-3 pr-2">{{ number_format($labaRugiData['totalBeban'], 0, ',', '.') }}</td>
                    </tr>

                    <tr class="border-t-2 border-gray-500 font-bold text-lg">
                        <td class="py-4 pl-4">LABA (RUGI) BERSIH</td>
                        <td class="text-right py-4 pr-2 {{ $labaRugiBersih >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format(abs($labaRugiBersih), 0, ',', '.') }}
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
