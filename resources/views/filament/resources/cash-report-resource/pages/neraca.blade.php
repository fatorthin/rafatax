<x-filament-panels::page>
    @php
        $data = $this->getNeracaData();
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
    @endphp

    <div class="flex flex-col gap-4">
        <div class="text-center">
            <h2 class="text-2xl font-bold">Laporan Neraca</h2>
            <p class="text-lg">Periode {{ $bulan[$this->month] }} {{ $this->year }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <!-- Dark mode overrides to ensure table readability -->
            <style>
                /* Container card background in dark mode */
                .dark .bg-white.rounded-lg.shadow.p-4 {
                    background-color: #0b0b0d;
                    /* near zinc-950 */
                }

                /* Table borders & text */
                .dark table.w-full th,
                .dark table.w-full td {
                    border-color: #3f3f46;
                    /* zinc-700 */
                    color: #e5e7eb;
                    /* zinc-200 */
                }

                /* Header rows */
                .dark tr.border-b.bg-gray-50 {
                    background-color: #3f3f46;
                    /* zinc-700 */
                }

                /* Group header & total backgrounds */
                .dark .bg-gray-100 {
                    background-color: #27272a;
                    /* zinc-800 */
                }

                .dark .bg-gray-50 {
                    background-color: #1f1f23;
                    /* near zinc-900 */
                }

                /* Hover state */
                .dark tr.hover\:bg-gray-50:hover {
                    background-color: #334155;
                    /* slate-700 */
                }

                /* Borders used in the layout */
                .dark .border-b {
                    border-bottom-color: #3f3f46;
                }

                .dark .border-r {
                    border-right-color: #3f3f46;
                }

                .dark .border-black {
                    border-color: #e5e7eb;
                    /* lighten for contrast */
                }

                .dark .border-t-2 {
                    border-top-color: #e5e7eb;
                }

                /* Headings readability */
                .dark h2,
                .dark p {
                    color: #f4f4f5;
                }

                /* Numbers */
                .dark .text-right {
                    color: #e5e7eb;
                }

                /* Print safety in dark mode */
                @media print {

                    .dark table.w-full th,
                    .dark table.w-full td {
                        color: #000;
                        border-color: #000;
                    }

                    .dark tr.border-b.bg-gray-50,
                    .dark .bg-gray-100,
                    .dark .bg-gray-50 {
                        background-color: #ddd !important;
                        color: #000 !important;
                    }
                }
            </style>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b bg-gray-50">
                            <th colspan="3" class="text-center py-2 px-2 font-bold border-r">AKTIVA</th>
                            <th colspan="3" class="text-center py-2 px-2 font-bold">PASIVA</th>
                        </tr>
                        <tr class="border-b bg-gray-50">
                            <th class="text-left py-2 px-2 border-r">Kode</th>
                            <th class="text-left py-2 px-2 border-r">Nama Akun</th>
                            <th class="text-right py-2 px-2 border-r">Jumlah</th>
                            <th class="text-left py-2 px-2">Kode</th>
                            <th class="text-left py-2 px-2">Nama Akun</th>
                            <th class="text-right py-2 px-2">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $maxRows = max(count($data['aktiva']), count($data['pasiva']));
                        @endphp

                        @for ($i = 0; $i < $maxRows; $i++)
                            <tr class="border-b hover:bg-gray-50 transition-colors duration-150 ease-in-out">
                                {{-- Aktiva Side --}}
                                @if (isset($data['aktiva'][$i]))
                                    @php $aktivaItem = $data['aktiva'][$i]; @endphp
                                    <td
                                        class="py-2 px-2 border-r {{ $aktivaItem['is_group_header'] ? 'bg-gray-100 font-bold' : '' }} {{ $aktivaItem['is_group_total'] ? 'font-bold bg-gray-50' : '' }}">
                                        {{ $aktivaItem['code'] }}
                                    </td>
                                    <td
                                        class="py-2 px-2 border-r {{ $aktivaItem['is_group_header'] ? 'bg-gray-100 font-bold pl-2' : 'pl-6' }} {{ $aktivaItem['is_group_total'] ? 'font-bold bg-gray-50' : '' }}">
                                        {{ $aktivaItem['name'] }}
                                    </td>
                                    <td
                                        class="text-right py-2 px-2 border-r {{ $aktivaItem['is_group_header'] ? 'bg-gray-100 font-bold' : '' }} {{ $aktivaItem['is_group_total'] ? 'font-bold bg-gray-50' : '' }}">
                                        @if (!$aktivaItem['is_group_header'])
                                            @if ($aktivaItem['is_negative'])
                                                ({{ number_format(abs($aktivaItem['amount']), 2, ',', '.') }})
                                            @else
                                                {{ number_format($aktivaItem['amount'], 2, ',', '.') }}
                                            @endif
                                        @endif
                                    </td>
                                @else
                                    <td class="border-r">&nbsp;</td>
                                    <td class="border-r">&nbsp;</td>
                                    <td class="border-r">&nbsp;</td>
                                @endif

                                {{-- Pasiva Side --}}
                                @if (isset($data['pasiva'][$i]))
                                    @php $pasivaItem = $data['pasiva'][$i]; @endphp
                                    <td
                                        class="py-2 px-2 {{ $pasivaItem['is_group_header'] ? 'bg-gray-100 font-bold' : '' }} {{ $pasivaItem['is_group_total'] ? 'font-bold bg-gray-50' : '' }}">
                                        {{ $pasivaItem['code'] }}
                                    </td>
                                    <td
                                        class="py-2 px-2 {{ $pasivaItem['is_group_header'] ? 'bg-gray-100 font-bold pl-2' : 'pl-6' }} {{ $pasivaItem['is_group_total'] ? 'font-bold bg-gray-50' : '' }} {{ isset($pasivaItem['is_sisa_dana']) && $pasivaItem['is_sisa_dana'] ? 'border-t-2 font-bold italic' : '' }}">
                                        {{ $pasivaItem['name'] }}
                                    </td>
                                    <td
                                        class="text-right py-2 px-2 {{ $pasivaItem['is_group_header'] ? 'bg-gray-100 font-bold' : '' }} {{ $pasivaItem['is_group_total'] ? 'font-bold bg-gray-50' : '' }} {{ isset($pasivaItem['is_sisa_dana']) && $pasivaItem['is_sisa_dana'] ? 'border-t-2 font-bold italic' : '' }}">
                                        @if (!$pasivaItem['is_group_header'])
                                            @if ($pasivaItem['is_negative'])
                                                ({{ number_format(abs($pasivaItem['amount']), 2, ',', '.') }})
                                            @else
                                                {{ number_format($pasivaItem['amount'], 2, ',', '.') }}
                                            @endif
                                        @endif
                                    </td>
                                @else
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                @endif
                            </tr>
                        @endfor

                        {{-- Total Row --}}
                        <tr class="font-bold border-t-2 border-black bg-gray-100">
                            <td class="py-2 px-2 border-r" colspan="2">Total Aktiva</td>
                            <td class="text-right py-2 px-2 border-r">
                                @if ($data['totalAktiva'] < 0)
                                    ({{ number_format(abs($data['totalAktiva']), 2, ',', '.') }})
                                @else
                                    {{ number_format($data['totalAktiva'], 2, ',', '.') }}
                                @endif
                            </td>
                            <td class="py-2 px-2" colspan="2">Total Pasiva</td>
                            <td class="text-right py-2 px-2">
                                @if ($data['totalPasiva'] < 0)
                                    ({{ number_format(abs($data['totalPasiva']), 2, ',', '.') }})
                                @else
                                    {{ number_format($data['totalPasiva'], 2, ',', '.') }}
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
