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

        // Helper function to format numbers with parentheses for negative values
        function formatNumber($number, $isNegative = false, $forceParentheses = false)
        {
            if ($forceParentheses && $isNegative) {
                return '(' . number_format(abs($number), 0, ',', '.') . ')';
            }
            return number_format($number, 0, ',', '.');
        }
    @endphp

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <!-- Dark mode overrides to ensure table readability -->
        <style>
            /* Card container in dark mode */
            .dark .bg-white.rounded-xl.shadow-sm.border.border-gray-200.p-6 {
                background-color: #0b0b0d;
                /* near zinc-950 */
                border-color: #3f3f46;
                /* zinc-700 */
            }

            /* Text in header area */
            .dark h2 {
                color: #f4f4f5;
            }

            .dark .text-gray-600 {
                color: #d4d4d8;
                /* zinc-300 */
            }

            /* Table cells & borders */
            .dark table.w-full th,
            .dark table.w-full td {
                border-color: #3f3f46;
                /* zinc-700 */
                color: #e5e7eb;
                /* zinc-200 */
            }

            /* Header border row */
            .dark .border-b-2.border-gray-300 {
                border-bottom-color: #52525b;
                /* zinc-600 */
            }

            /* Section headers */
            .dark .bg-gray-100 {
                background-color: #27272a;
                /* zinc-800 */
            }

            .dark .bg-gray-50 {
                background-color: #1f1f23;
                /* near zinc-900 */
            }

            /* Normal row borders and hover */
            .dark .border-b.border-gray-200 {
                border-bottom-color: #3f3f46;
            }

            .dark tr.hover\:bg-gray-50:hover {
                background-color: #334155;
                /* slate-700 */
            }

            /* Totals and separators */
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

            /* Positive/negative color accents */
            .dark .text-green-600 {
                color: #22c55e;
            }

            .dark .text-red-600 {
                color: #ef4444;
            }

            /* Print-safe overrides when printing in dark mode */
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
                    @foreach ($data['items'] as $item)
                        @if ($item['category'] === 'Pendapatan')
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $item['code'] }}</td>
                                <td class="px-4 py-2">{{ $item['name'] }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($item['amount'], 0, ',', '.') }}</td>
                            </tr>
                            @php $totalPendapatanDisplay += $item['amount']; @endphp
                        @endif
                    @endforeach
                    {{-- Total Pendapatan (operasional only) --}}
                    <tr class="border-t border-gray-300 font-bold bg-gray-50">
                        <td colspan="2" class="px-4 py-2 text-right">Total Pendapatan</td>
                        <td class="px-4 py-2 text-right">{{ number_format($totalPendapatanDisplay, 0, ',', '.') }}</td>
                    </tr>

                    <!-- Beban -->
                    <tr class="bg-gray-100 mt-4">
                        <td colspan="3" class="px-4 py-2 font-bold">Beban Biaya</td>
                    </tr>
                    @php
                        // add regular Beban items
                        $totalBebanOperasional = 0;
                    @endphp
                    @foreach ($data['items'] as $item)
                        @if ($item['category'] === 'Beban')
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $item['code'] }}</td>
                                <td class="px-4 py-2">{{ $item['name'] }}</td>
                                <td class="px-4 py-2 text-right">{{ formatNumber($item['amount'], $item['is_negative'] ?? false, true) }}</td>
                            </tr>
                            @php $totalBebanOperasional += $item['amount']; @endphp
                        @endif
                    @endforeach

                    {{-- Total Beban (operasional only) --}}
                    <tr class="border-t border-gray-300 font-bold bg-gray-50">
                        <td colspan="2" class="px-4 py-2 text-right">Total Beban Biaya</td>
                        <td class="px-4 py-2 text-right">{{ formatNumber($totalBebanOperasional, $totalBebanOperasional < 0, true) }}</td>
                    </tr>

                    {{-- Penghasilan / Biaya Luar Usaha (subtotal placed under Beban Biaya) --}}
                    <tr class="bg-gray-100 mt-4">
                        <td colspan="3" class="px-4 py-2 font-bold">Penghasilan (Biaya) Luar Usaha</td>
                    </tr>
                    @php
                        $totalPenghasilanLuarDisplay = 0;
                        $totalBebanLuarDisplay = 0;
                    @endphp
                    @foreach ($data['items'] as $item)
                        @if ($item['category'] === 'Penghasilan (Biaya) Luar Usaha')
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $item['code'] }}</td>
                                <td class="px-4 py-2">{{ $item['name'] }}</td>
                                <td class="px-4 py-2 text-right">
                                    @if(preg_match('/^AO-7/', $item['code']))
                                        {{-- 7xx -> biaya luar usaha: show like expense --}}
                                        {{ formatNumber($item['amount'], $item['is_negative'] ?? false, true) }}
                                        @php $totalBebanLuarDisplay += $item['amount']; @endphp
                                    @else
                                        {{-- 6xx -> penghasilan luar usaha: show like pendapatan --}}
                                        {{ number_format($item['amount'], 0, ',', '.') }}
                                        @php $totalPenghasilanLuarDisplay += $item['amount']; @endphp
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach

                    {{-- Total Penghasilan (Biaya) Luar Usaha (subtotal) --}}
                    @php $totalExternalNet = $totalPenghasilanLuarDisplay + $totalBebanLuarDisplay; @endphp
                    <tr class="border-t border-gray-300 font-bold bg-gray-50">
                        <td colspan="2" class="px-4 py-2 text-right">Total Penghasilan (Biaya) Luar Usaha</td>
                        <td class="px-4 py-2 text-right">{{ formatNumber($totalExternalNet, $totalExternalNet < 0, true) }}</td>
                    </tr>

                    <!-- Laba/Rugi Bersih -->
                    <tr class="border-t-2 border-gray-500 font-bold text-lg">
                        <td colspan="2" class="px-4 py-3 text-right">
                            {{ $data['labaRugiBersih'] >= 0 ? 'Laba' : 'Rugi' }} Bersih
                        </td>
                        <td
                            class="px-4 py-3 text-right {{ $data['labaRugiBersih'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
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
