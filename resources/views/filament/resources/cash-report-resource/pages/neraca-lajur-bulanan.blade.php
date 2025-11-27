<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header dengan periode -->


        <!-- Tabel Neraca Lajur -->
        <div class="shadow rounded-lg overflow-hidden">
            @php
                $data = $this->getTableQuery()->get();

                // Initialize all total variables
                $totalNeracaAwalDebit = 0;
                $totalNeracaAwalKredit = 0;
                $totalKasBesarDebit = 0;
                $totalKasBesarKredit = 0;
                $totalKasKecilDebit = 0;
                $totalKasKecilKredit = 0;
                $totalBankDebit = 0;
                $totalBankKredit = 0;
                $totalJurnalUmumDebit = 0;
                $totalJurnalUmumKredit = 0;
                $totalAJEDebit = 0;
                $totalAJEKredit = 0;
                $totalNeracaSebelumAJEDebit = 0;
                $totalNeracaSebelumAJEKredit = 0;
                $totalNeracaSetelahAJEDebit = 0;
                $totalNeracaSetelahAJEKredit = 0;
                $totalNeracaDebit = 0;
                $totalNeracaKredit = 0;
                $totalLabaRugiDebit = 0;
                $totalLabaRugiKredit = 0;
                $totalNeracaAwalBulanDepanDebit = 0;
                $totalNeracaAwalBulanDepanKredit = 0;
                $grandTotal = 0;

                // Calculate totals for all rows
                foreach ($data as $row) {
                    // Basic totals
                    $totalNeracaAwalDebit += $row->neraca_awal_debit;
                    $totalNeracaAwalKredit += $row->neraca_awal_kredit;
                    $totalKasBesarDebit += $row->kas_besar_debit;
                    $totalKasBesarKredit += $row->kas_besar_kredit;
                    $totalKasKecilDebit += $row->kas_kecil_debit;
                    $totalKasKecilKredit += $row->kas_kecil_kredit;
                    $totalBankDebit += $row->bank_debit;
                    $totalBankKredit += $row->bank_kredit;
                    $totalJurnalUmumDebit += $row->jurnal_umum_debit;
                    $totalJurnalUmumKredit += $row->jurnal_umum_kredit;
                    $totalAJEDebit += $row->aje_debit;
                    $totalAJEKredit += $row->aje_kredit;
                    $totalNeracaAwalBulanDepanDebit += $row->neraca_awal_bulan_depan_debit;
                    $totalNeracaAwalBulanDepanKredit += $row->neraca_awal_bulan_depan_kredit;

                    // Calculate Neraca Sebelum AJE
                    $totalDebit =
                        $row->neraca_awal_debit +
                        $row->kas_besar_debit +
                        $row->kas_kecil_debit +
                        $row->bank_debit +
                        $row->jurnal_umum_debit;

                    $totalKredit =
                        $row->neraca_awal_kredit +
                        $row->kas_besar_kredit +
                        $row->kas_kecil_kredit +
                        $row->bank_kredit +
                        $row->jurnal_umum_kredit;

                    $selisihSebelumAJE = $totalDebit - $totalKredit;
                    $neracaSebelumAJEDebit = $selisihSebelumAJE > 0 ? $selisihSebelumAJE : 0;
                    $neracaSebelumAJEKredit = $selisihSebelumAJE < 0 ? abs($selisihSebelumAJE) : 0;

                    $totalNeracaSebelumAJEDebit += $neracaSebelumAJEDebit;
                    $totalNeracaSebelumAJEKredit += $neracaSebelumAJEKredit;

                    // Calculate Neraca Setelah AJE
                    $selisihSetelahAJE = $selisihSebelumAJE + ($row->aje_debit - $row->aje_kredit);
                    $neracaSetelahAJEDebit = $selisihSetelahAJE > 0 ? $selisihSetelahAJE : 0;
                    $neracaSetelahAJEKredit = $selisihSetelahAJE < 0 ? abs($selisihSetelahAJE) : 0;

                    $totalNeracaSetelahAJEDebit += $neracaSetelahAJEDebit;
                    $totalNeracaSetelahAJEKredit += $neracaSetelahAJEKredit;

                    // Calculate Neraca
                    $showInNeraca = preg_match(
                        '/^AO-(([1-2][0-9]{2}|30[0-5])(\.[1-5])?|(10[1-2])\.[1-5]|1010|1011)$/',
                        $row->code,
                    );
                    $neracaDebit = $showInNeraca ? $neracaSetelahAJEDebit : 0;
                    $neracaKredit = $showInNeraca ? $neracaSetelahAJEKredit : 0;

                    $totalNeracaDebit += $neracaDebit;
                    $totalNeracaKredit += $neracaKredit;

                    // Calculate Laba Rugi
                    $showInLabaRugi = preg_match(
                        '/^AO-(4[0-9]{2}(\.[1-6])?|501(\.[1-4])?|50[0-9]|5[1-9][0-9]|6[0-9]{2}|70[0-2])$/',
                        $row->code,
                    );
                    $labaRugiDebit = $showInLabaRugi ? $neracaSetelahAJEDebit : 0;
                    $labaRugiKredit = $showInLabaRugi ? $neracaSetelahAJEKredit : 0;

                    $totalLabaRugiDebit += $labaRugiDebit;
                    $totalLabaRugiKredit += $labaRugiKredit;
                }
            @endphp

            <style>
                .table-container {
                    overflow-x: auto;
                    max-width: 100%;
                    position: relative;
                    max-height: 70vh;
                    overflow-y: auto;
                }

                .sticky-table {
                    border-collapse: collapse;
                    width: 100%;
                }

                .sticky-table th,
                .sticky-table td {
                    border: 1px solid #e5e7eb;
                    padding: 8px;
                    white-space: nowrap;
                }

                /* Style untuk thead */
                .sticky-table thead {
                    position: sticky;
                    top: 0;
                    z-index: 10;
                }

                .sticky-table thead tr:first-child {
                    background: rgb(195, 193, 193);
                }

                .sticky-table thead tr:last-child {
                    background: rgb(195, 193, 193);
                }

                .sticky-table thead th {
                    font-weight: bold;
                    text-align: center;
                    background: rgb(195, 193, 193);
                }

                .sticky-col-1 {
                    position: sticky;
                    left: 0;
                    background: rgb(195, 193, 193);
                    z-index: 5;
                    min-width: 300px;
                }

                /* Header yang sticky dan di kolom pertama harus punya z-index tertinggi */
                thead th.sticky-col-1 {
                    z-index: 20;
                    background: rgb(195, 193, 193);
                }

                /* Style untuk baris data */
                .sticky-table tbody tr:nth-child(even) {
                    background-color: #f9fafb;
                }

                .sticky-table tbody tr:hover {
                    background-color: #9ab5eb;
                }

                .sticky-table tbody td {
                    background: inherit;
                }

                /* Style untuk kolom sticky pada baris data */
                .sticky-table tbody tr:nth-child(even) td.sticky-col-1 {
                    background: #f3f4f6;
                }

                .sticky-table tbody tr:nth-child(odd) td.sticky-col-1 {
                    background: white;
                }

                .sticky-table tbody tr:hover td.sticky-col-1 {
                    background: #9ab5eb;
                }

                /* Style untuk total row */
                .total-row td {
                    font-weight: bold;
                    border-top: 2px solid #000;
                    background: rgb(195, 193, 193) !important;
                }

                /* Style untuk text alignment */
                .text-right {
                    text-align: right;
                }

                .text-center {
                    text-align: center;
                }

                .separator-row td {
                    border-bottom: 2px solid #000;
                    height: 2px;
                    padding: 0;
                }
            </style>
            <!-- Dark mode overrides to ensure readability -->
            <style>
                /* Use Filament's dark mode root class to scope overrides */
                .dark .sticky-table th,
                .dark .sticky-table td {
                    border-color: #3f3f46;
                    /* zinc-700 */
                    color: #e5e7eb;
                    /* text-zinc-200 */
                }

                .dark .sticky-table thead {
                    background: transparent;
                }

                .dark .sticky-table thead tr:first-child,
                .dark .sticky-table thead tr:last-child,
                .dark .sticky-table thead th {
                    background: #3f3f46;
                    /* zinc-700 */
                    color: #f4f4f5;
                    /* text-zinc-100 */
                }

                .dark .sticky-col-1 {
                    background: #3f3f46;
                    /* zinc-700 */
                }

                .dark .sticky-table tbody tr:nth-child(even) {
                    background-color: #18181b;
                    /* zinc-900 */
                }

                .dark .sticky-table tbody tr:nth-child(odd) td.sticky-col-1 {
                    background: #27272a;
                    /* zinc-800 */
                }

                .dark .sticky-table tbody tr:nth-child(even) td.sticky-col-1 {
                    background: #1f1f23;
                    /* near zinc-900 */
                }

                .dark .sticky-table tbody tr:hover {
                    background-color: #334155;
                    /* slate-700 */
                }

                .dark .sticky-table tbody tr:hover td.sticky-col-1 {
                    background-color: #334155;
                    /* keep unified hover */
                }

                .dark .total-row td {
                    background: #3f3f46 !important;
                    /* zinc-700 */
                    border-top-color: #e5e7eb;
                    /* light border for separation */
                    color: #f4f4f5;
                }

                .number-cell {
                    color: inherit;
                }

                .dark .number-cell {
                    color: #e5e7eb;
                }

                /* Ensure action bar and print mode doesn't affect visibility */
                @media print {

                    .dark .sticky-table th,
                    .dark .sticky-table td {
                        color: #000;
                        border-color: #000;
                    }

                    .dark .sticky-table thead tr:first-child,
                    .dark .sticky-table thead tr:last-child,
                    .dark .sticky-table thead th,
                    .dark .sticky-col-1,
                    .dark .total-row td {
                        background: #ddd !important;
                        color: #000 !important;
                    }
                }
            </style>

            <div class="table-container">
                <table class="sticky-table">
                    <thead>
                        <tr>
                            <th class="sticky-col-1" rowspan="3">Kode Akun</th>
                            <th colspan="2" class="text-center">Neraca Awal</th>
                            <th colspan="2" class="text-center">Kas Besar</th>
                            <th colspan="2" class="text-center">Kas Kecil</th>
                            <th colspan="2" class="text-center">Bank</th>
                            <th colspan="2" class="text-center">Jurnal Umum</th>
                            <th colspan="2" class="text-center">Neraca Sebelum AJE</th>
                            <th colspan="2" class="text-center">AJE</th>
                            <th colspan="2" class="text-center">Neraca Setelah AJE</th>
                            <th colspan="2" class="text-center">Neraca</th>
                            <th colspan="2" class="text-center">Laba Rugi</th>
                            <th colspan="2" class="text-center">Neraca Awal Bulan Depan</th>
                        </tr>
                        <tr>
                            <th class="text-center">Debit</th>
                            <th class="text-center">Kredit</th>
                            <th class="text-center">Debit</th>
                            <th class="text-center">Kredit</th>
                            <th class="text-center">Debit</th>
                            <th class="text-center">Kredit</th>
                            <th class="text-center">Debit</th>
                            <th class="text-center">Kredit</th>
                            <th class="text-center">Debit</th>
                            <th class="text-center">Kredit</th>
                            <th class="text-center">Debit</th>
                            <th class="text-center">Kredit</th>
                            <th class="text-center">Debit</th>
                            <th class="text-center">Kredit</th>
                            <th class="text-center">Debit</th>
                            <th class="text-center">Kredit</th>
                            <th class="text-center">Debit</th>
                            <th class="text-center">Kredit</th>
                            <th class="text-center">Debit</th>
                            <th class="text-center">Kredit</th>
                            <th class="text-center">Debit</th>
                            <th class="text-center">Kredit</th>
                        </tr>
                        <tr class="total-row">
                            <td class="number-cell text-right">{{ number_format($totalNeracaAwalDebit, 0, ',', '.') }}
                            </td>
                            <td class="number-cell text-right">{{ number_format($totalNeracaAwalKredit, 0, ',', '.') }}
                            </td>
                            <td class="number-cell text-right">{{ number_format($totalKasBesarDebit, 0, ',', '.') }}
                            </td>
                            <td class="number-cell text-right">{{ number_format($totalKasBesarKredit, 0, ',', '.') }}
                            </td>
                            <td class="number-cell text-right">{{ number_format($totalKasKecilDebit, 0, ',', '.') }}
                            </td>
                            <td class="number-cell text-right">{{ number_format($totalKasKecilKredit, 0, ',', '.') }}
                            </td>
                            <td class="number-cell text-right">{{ number_format($totalBankDebit, 0, ',', '.') }}</td>
                            <td class="number-cell text-right">{{ number_format($totalBankKredit, 0, ',', '.') }}</td>
                            <td class="number-cell text-right">{{ number_format($totalJurnalUmumDebit, 0, ',', '.') }}
                            </td>
                            <td class="number-cell text-right">{{ number_format($totalJurnalUmumKredit, 0, ',', '.') }}
                            </td>
                            <td class="number-cell text-right">
                                {{ number_format($totalNeracaSebelumAJEDebit, 0, ',', '.') }}</td>
                            <td class="number-cell text-right">
                                {{ number_format($totalNeracaSebelumAJEKredit, 0, ',', '.') }}</td>
                            <td class="number-cell text-right">{{ number_format($totalAJEDebit, 0, ',', '.') }}</td>
                            <td class="number-cell text-right">{{ number_format($totalAJEKredit, 0, ',', '.') }}</td>
                            <td class="number-cell text-right">
                                {{ number_format($totalNeracaSetelahAJEDebit, 0, ',', '.') }}</td>
                            <td class="number-cell text-right">
                                {{ number_format($totalNeracaSetelahAJEKredit, 0, ',', '.') }}</td>
                            <td class="number-cell text-right">{{ number_format($totalNeracaDebit, 0, ',', '.') }}</td>
                            <td class="number-cell text-right">{{ number_format($totalNeracaKredit, 0, ',', '.') }}
                            </td>
                            <td class="number-cell text-right">{{ number_format($totalLabaRugiDebit, 0, ',', '.') }}
                            </td>
                            <td class="number-cell text-right">{{ number_format($totalLabaRugiKredit, 0, ',', '.') }}
                            </td>
                            <td class="number-cell text-right">
                                {{ number_format($totalNeracaAwalBulanDepanDebit, 0, ',', '.') }}</td>
                            <td class="number-cell text-right">
                                {{ number_format($totalNeracaAwalBulanDepanKredit, 0, ',', '.') }}</td>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalNeracaAwalDebit = 0;
                            $totalNeracaAwalKredit = 0;
                            $totalKasBesarDebit = 0;
                            $totalKasBesarKredit = 0;
                            $totalKasKecilDebit = 0;
                            $totalKasKecilKredit = 0;
                            $totalBankDebit = 0;
                            $totalBankKredit = 0;
                            $totalJurnalUmumDebit = 0;
                            $totalJurnalUmumKredit = 0;
                            $grandTotal = 0;
                            $totalNeracaDebit = 0;
                            $totalNeracaKredit = 0;
                            $totalLabaRugiDebit = 0;
                            $totalLabaRugiKredit = 0;
                            $totalNeracaAwalBulanDepanDebit = 0;
                            $totalNeracaAwalBulanDepanKredit = 0;
                        @endphp

                        @foreach ($data as $row)
                            @php
                                $totalDebit =
                                    $row->neraca_awal_debit +
                                    $row->kas_besar_debit +
                                    $row->kas_kecil_debit +
                                    $row->bank_debit +
                                    $row->jurnal_umum_debit;

                                $totalKredit =
                                    $row->neraca_awal_kredit +
                                    $row->kas_besar_kredit +
                                    $row->kas_kecil_kredit +
                                    $row->bank_kredit +
                                    $row->jurnal_umum_kredit;

                                $selisihSebelumAJE = $totalDebit - $totalKredit;
                                $neracaSebelumAJEDebit = $selisihSebelumAJE > 0 ? $selisihSebelumAJE : 0;
                                $neracaSebelumAJEKredit = $selisihSebelumAJE < 0 ? abs($selisihSebelumAJE) : 0;

                                $selisihSetelahAJE = $selisihSebelumAJE + ($row->aje_debit - $row->aje_kredit);
                                $neracaSetelahAJEDebit = $selisihSetelahAJE > 0 ? $selisihSetelahAJE : 0;
                                $neracaSetelahAJEKredit = $selisihSetelahAJE < 0 ? abs($selisihSetelahAJE) : 0;

                                // Perhitungan untuk kolom Neraca
                                $showInNeraca = preg_match(
                                    '/^AO-(([1-2][0-9]{2}|30[0-5])(\.[1-5])?|(10[1-2])\.[1-5]|1010|1011)$/',
                                    $row->code,
                                );
                                $neracaDebit = $showInNeraca ? $neracaSetelahAJEDebit : 0;
                                $neracaKredit = $showInNeraca ? $neracaSetelahAJEKredit : 0;

                                // Perhitungan untuk kolom Laba Rugi
                                $showInLabaRugi = preg_match(
                                    '/^AO-(4[0-9]{2}(\.[1-6])?|501(\.[1-4])?|50[0-9]|5[1-9][0-9]|6[0-9]{2}|70[0-2])$/',
                                    $row->code,
                                );
                                $labaRugiDebit = $showInLabaRugi ? $neracaSetelahAJEDebit : 0;
                                $labaRugiKredit = $showInLabaRugi ? $neracaSetelahAJEKredit : 0;
                            @endphp
                            <tr>
                                <td class="sticky-col-1">{{ $row->code . ' ' . $row->name }}</td>
                                <td class="number-cell text-right">
                                    {{ number_format($row->neraca_awal_debit, 0, ',', '.') }}</td>
                                <td class="number-cell text-right">
                                    {{ number_format($row->neraca_awal_kredit, 0, ',', '.') }}</td>
                                <td class="number-cell text-right">
                                    {{ number_format($row->kas_besar_debit, 0, ',', '.') }}</td>
                                <td class="number-cell text-right">
                                    {{ number_format($row->kas_besar_kredit, 0, ',', '.') }}</td>
                                <td class="number-cell text-right">
                                    {{ number_format($row->kas_kecil_debit, 0, ',', '.') }}</td>
                                <td class="number-cell text-right">
                                    {{ number_format($row->kas_kecil_kredit, 0, ',', '.') }}</td>
                                <td class="number-cell text-right">{{ number_format($row->bank_debit, 0, ',', '.') }}
                                </td>
                                <td class="number-cell text-right">{{ number_format($row->bank_kredit, 0, ',', '.') }}
                                </td>
                                <td class="number-cell text-right">
                                    {{ number_format($row->jurnal_umum_debit, 0, ',', '.') }}</td>
                                <td class="number-cell text-right">
                                    {{ number_format($row->jurnal_umum_kredit, 0, ',', '.') }}</td>
                                <td class="number-cell text-right">
                                    {{ number_format($neracaSebelumAJEDebit, 0, ',', '.') }}</td>
                                <td class="number-cell text-right">
                                    {{ number_format($neracaSebelumAJEKredit, 0, ',', '.') }}</td>
                                <td class="number-cell text-right">{{ number_format($row->aje_debit, 0, ',', '.') }}
                                </td>
                                <td class="number-cell text-right">{{ number_format($row->aje_kredit, 0, ',', '.') }}
                                </td>
                                <td class="number-cell text-right">
                                    {{ number_format($neracaSetelahAJEDebit, 0, ',', '.') }}</td>
                                <td class="number-cell text-right">
                                    {{ number_format($neracaSetelahAJEKredit, 0, ',', '.') }}</td>
                                <td class="number-cell text-right">{{ number_format($neracaDebit, 0, ',', '.') }}</td>
                                <td class="number-cell text-right">{{ number_format($neracaKredit, 0, ',', '.') }}</td>
                                <td class="number-cell text-right">{{ number_format($labaRugiDebit, 0, ',', '.') }}
                                </td>
                                <td class="number-cell text-right">{{ number_format($labaRugiKredit, 0, ',', '.') }}
                                </td>
                                <td class="number-cell text-right">
                                    {{ number_format($row->neraca_awal_bulan_depan_debit, 0, ',', '.') }}</td>
                                <td class="number-cell text-right">
                                    {{ number_format($row->neraca_awal_bulan_depan_kredit, 0, ',', '.') }}</td>
                            </tr>
                            @if (in_array($row->code, ['AO-1011', 'AO-112', 'AO-127', 'AO-211', 'AO-305', 'AO-410', 'AO-525', 'AO-603']))
                                <tr class="separator-row">
                                    <td colspan="23"></td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <style>
            @media print {

                .fi-page-header,
                .fi-sidebar,
                .fi-topbar {
                    display: none !important;
                }

                .fi-page {
                    padding: 0 !important;
                    margin: 0 !important;
                }

                .space-y-6>div:last-child {
                    page-break-inside: avoid;
                }
            }
        </style>
        <x-filament-actions::modals />
</x-filament-panels::page>
