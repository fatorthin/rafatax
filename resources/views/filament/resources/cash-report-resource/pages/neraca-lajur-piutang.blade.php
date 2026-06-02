<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Info banner konsep piutang --}}
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-600 dark:text-blue-400" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm text-blue-800 dark:text-blue-200">
                    <p class="font-semibold">Konsep Baru: Jurnal Pendapatan berbasis Piutang</p>
                    <ul class="mt-1 list-disc pl-4 space-y-0.5">
                        <li><strong>Debit Piutang (AO-103) / Kredit AO-208 (Pendapatan Yang Belum Diterima)</strong>: MoU KKP approved bulan berjalan</li>
                        <li><strong>Debit AO-208 / Kredit Piutang (AO-103)</strong>: Invoice KKP paid (realisasi bulan berjalan)</li>
                        <li>Memo tidak dimasukkan ke dalam Jurnal Pendapatan karena langsung diakui sebagai pendapatan di kolom Kas & Bank berdasarkan invoice</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Tabel Neraca Lajur --}}
        <div class="shadow rounded-lg overflow-hidden">
            @php
                $data = $this->getTableQuery()->get();

                $totalNeracaAwalDebit = 0;
                $totalNeracaAwalKredit = 0;
                $totalKasBesarDebit = 0;
                $totalKasBesarKredit = 0;
                $totalKasKecilDebit = 0;
                $totalKasKecilKredit = 0;
                $totalBankDebit = 0;
                $totalBankKredit = 0;
                $totalJurnalPendapatanDebit = 0;
                $totalJurnalPendapatanKredit = 0;
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

                foreach ($data as $row) {
                    $totalNeracaAwalDebit += $row->neraca_awal_debit;
                    $totalNeracaAwalKredit += $row->neraca_awal_kredit;
                    $totalKasBesarDebit += $row->kas_besar_debit;
                    $totalKasBesarKredit += $row->kas_besar_kredit;
                    $totalKasKecilDebit += $row->kas_kecil_debit;
                    $totalKasKecilKredit += $row->kas_kecil_kredit;
                    $totalBankDebit += $row->bank_debit;
                    $totalBankKredit += $row->bank_kredit;
                    $totalJurnalPendapatanDebit += $row->jurnal_pendapatan_debit;
                    $totalJurnalPendapatanKredit += $row->jurnal_pendapatan_kredit;
                    $totalJurnalUmumDebit += $row->jurnal_umum_debit;
                    $totalJurnalUmumKredit += $row->jurnal_umum_kredit;
                    $totalAJEDebit += $row->aje_debit;
                    $totalAJEKredit += $row->aje_kredit;
                    $totalNeracaAwalBulanDepanDebit += $row->neraca_awal_bulan_depan_debit;
                    $totalNeracaAwalBulanDepanKredit += $row->neraca_awal_bulan_depan_kredit;

                    $totalDebit =
                        $row->neraca_awal_debit +
                        $row->kas_besar_debit +
                        $row->kas_kecil_debit +
                        $row->bank_debit +
                        $row->jurnal_pendapatan_debit +
                        $row->jurnal_umum_debit;
                    $totalKredit =
                        $row->neraca_awal_kredit +
                        $row->kas_besar_kredit +
                        $row->kas_kecil_kredit +
                        $row->bank_kredit +
                        $row->jurnal_pendapatan_kredit +
                        $row->jurnal_umum_kredit;

                    $selisihSebelumAJE = $totalDebit - $totalKredit;
                    $neracaSebelumAJEDebit = $selisihSebelumAJE > 0 ? $selisihSebelumAJE : 0;
                    $neracaSebelumAJEKredit = $selisihSebelumAJE < 0 ? abs($selisihSebelumAJE) : 0;
                    $totalNeracaSebelumAJEDebit += $neracaSebelumAJEDebit;
                    $totalNeracaSebelumAJEKredit += $neracaSebelumAJEKredit;

                    $selisihSetelahAJE = $selisihSebelumAJE + ($row->aje_debit - $row->aje_kredit);
                    $neracaSetelahAJEDebit = $selisihSetelahAJE > 0 ? $selisihSetelahAJE : 0;
                    $neracaSetelahAJEKredit = $selisihSetelahAJE < 0 ? abs($selisihSetelahAJE) : 0;
                    $totalNeracaSetelahAJEDebit += $neracaSetelahAJEDebit;
                    $totalNeracaSetelahAJEKredit += $neracaSetelahAJEKredit;

                    $showInNeraca = preg_match(
                        '/^AO-(([1-2][0-9]{2}|30[0-5])(\.[1-5])?|(10[1-2])\.[1-5]|1010(\.[1-9])?|1011(\.[1-9])?)$/',
                        $row->code,
                    );
                    $totalNeracaDebit += $showInNeraca ? $neracaSetelahAJEDebit : 0;
                    $totalNeracaKredit += $showInNeraca ? $neracaSetelahAJEKredit : 0;

                    $showInLabaRugi = preg_match(
                        '/^AO-(4[0-9]{2}(\.[1-6])?|501(\.[1-4])?|50[0-9](\.[1-9])?|5[1-9][0-9](\.[1-9])?|6[0-9]{2}|70[0-2])$/',
                        $row->code,
                    );
                    $totalLabaRugiDebit += $showInLabaRugi ? $neracaSetelahAJEDebit : 0;
                    $totalLabaRugiKredit += $showInLabaRugi ? $neracaSetelahAJEKredit : 0;
                }
            @endphp

            <style>
                .table-container-piutang {
                    overflow-x: auto;
                    max-width: 100%;
                    position: relative;
                    max-height: 70vh;
                    overflow-y: auto;
                }

                .piutang-table {
                    border-collapse: collapse;
                    width: 100%;
                }

                .piutang-table th,
                .piutang-table td {
                    border: 1px solid #e5e7eb;
                    padding: 8px;
                    white-space: nowrap;
                }

                .piutang-table thead {
                    position: sticky;
                    top: 0;
                    z-index: 10;
                }

                .piutang-table thead tr:first-child,
                .piutang-table thead tr:last-child,
                .piutang-table thead th {
                    background: rgb(195, 193, 193);
                    font-weight: bold;
                    text-align: center;
                }

                /* Highlight Jurnal Pendapatan header */
                .th-piutang-col {
                    background: #bfdbfe !important;
                    color: #1e40af !important;
                }

                .dark .th-piutang-col {
                    background: #1e3a5f !important;
                    color: #93c5fd !important;
                }

                .sticky-col-piutang {
                    position: sticky;
                    left: 0;
                    background: rgb(195, 193, 193);
                    z-index: 5;
                    min-width: 300px;
                }

                thead th.sticky-col-piutang {
                    z-index: 20;
                }

                .piutang-table tbody tr:nth-child(even) {
                    background-color: #f9fafb;
                }

                .piutang-table tbody tr:hover {
                    background-color: #9ab5eb;
                }

                .piutang-table tbody td {
                    background: inherit;
                }

                .piutang-table tbody tr:nth-child(even) td.sticky-col-piutang {
                    background: #f3f4f6;
                }

                .piutang-table tbody tr:nth-child(odd) td.sticky-col-piutang {
                    background: white;
                }

                .piutang-table tbody tr:hover td.sticky-col-piutang {
                    background: #9ab5eb;
                }

                /* highlight cells di kolom jurnal pendapatan (piutang) */
                .piutang-table tbody td.jp-col {
                    background-color: #eff6ff;
                }

                .piutang-table tbody tr:hover td.jp-col {
                    background-color: #9ab5eb;
                }

                .dark .piutang-table tbody td.jp-col {
                    background-color: #1e3a5f40;
                }

                .total-row-piutang td {
                    font-weight: bold;
                    border-top: 2px solid #000;
                    background: rgb(195, 193, 193) !important;
                }

                .dark .piutang-table th,
                .dark .piutang-table td {
                    border-color: #3f3f46;
                    color: #e5e7eb;
                }

                .dark .piutang-table thead tr:first-child,
                .dark .piutang-table thead tr:last-child,
                .dark .piutang-table thead th {
                    background: #3f3f46;
                    color: #f4f4f5;
                }

                .dark .sticky-col-piutang {
                    background: #3f3f46;
                }

                .dark .piutang-table tbody tr:nth-child(even) {
                    background-color: #18181b;
                }

                .dark .piutang-table tbody tr:nth-child(odd) td.sticky-col-piutang {
                    background: #27272a;
                }

                .dark .piutang-table tbody tr:nth-child(even) td.sticky-col-piutang {
                    background: #1f1f23;
                }

                .dark .piutang-table tbody tr:hover {
                    background-color: #334155;
                }

                .dark .piutang-table tbody tr:hover td.sticky-col-piutang {
                    background-color: #334155;
                }

                .dark .total-row-piutang td {
                    background: #3f3f46 !important;
                    border-top-color: #e5e7eb;
                    color: #f4f4f5;
                }

                .separator-row-piutang td {
                    border-bottom: 2px solid #000;
                    height: 2px;
                    padding: 0;
                }

                .text-right {
                    text-align: right;
                }

                .text-center {
                    text-align: center;
                }

                .number-cell {
                    color: inherit;
                }

                .dark .number-cell {
                    color: #e5e7eb;
                }
            </style>

            <div class="table-container-piutang">
                <table class="piutang-table">
                    <thead>
                        <tr>
                            <th class="sticky-col-piutang" rowspan="3">Kode Akun</th>
                            <th colspan="2" class="text-center">Neraca Awal</th>
                            <th colspan="2" class="text-center">Kas Besar</th>
                            <th colspan="2" class="text-center">Kas Kecil</th>
                            <th colspan="2" class="text-center">Bank</th>
                            <th colspan="2" class="text-center th-piutang-col">Jurnal
                                Pendapatan<br><small>(MoU/Memo/Invoice)</small></th>
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
                            <th class="text-center th-piutang-col">Debit</th>
                            <th class="text-center th-piutang-col">Kredit</th>
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
                        <tr class="total-row-piutang">
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
                            <td class="number-cell text-right">
                                {{ number_format($totalJurnalPendapatanDebit, 0, ',', '.') }}</td>
                            <td class="number-cell text-right">
                                {{ number_format($totalJurnalPendapatanKredit, 0, ',', '.') }}</td>
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
                        @foreach ($data as $row)
                            @php
                                $totalDebit =
                                    $row->neraca_awal_debit +
                                    $row->kas_besar_debit +
                                    $row->kas_kecil_debit +
                                    $row->bank_debit +
                                    $row->jurnal_pendapatan_debit +
                                    $row->jurnal_umum_debit;
                                $totalKredit =
                                    $row->neraca_awal_kredit +
                                    $row->kas_besar_kredit +
                                    $row->kas_kecil_kredit +
                                    $row->bank_kredit +
                                    $row->jurnal_pendapatan_kredit +
                                    $row->jurnal_umum_kredit;

                                $selisihSebelumAJE = $totalDebit - $totalKredit;
                                $neracaSebelumAJEDebit = $selisihSebelumAJE > 0 ? $selisihSebelumAJE : 0;
                                $neracaSebelumAJEKredit = $selisihSebelumAJE < 0 ? abs($selisihSebelumAJE) : 0;

                                $selisihSetelahAJE = $selisihSebelumAJE + ($row->aje_debit - $row->aje_kredit);
                                $neracaSetelahAJEDebit = $selisihSetelahAJE > 0 ? $selisihSetelahAJE : 0;
                                $neracaSetelahAJEKredit = $selisihSetelahAJE < 0 ? abs($selisihSetelahAJE) : 0;

                                $showInNeraca = preg_match(
                                    '/^AO-(([1-2][0-9]{2}|30[0-5])(\.[1-5])?|(10[1-2])\.[1-5]|1010(\.[1-9])?|1011(\.[1-9])?)$/',
                                    $row->code,
                                );
                                $neracaDebit = $showInNeraca ? $neracaSetelahAJEDebit : 0;
                                $neracaKredit = $showInNeraca ? $neracaSetelahAJEKredit : 0;

                                $showInLabaRugi = preg_match(
                                    '/^AO-(4[0-9]{2}(\.[1-6])?|501(\.[1-4])?|50[0-9](\.[1-9])?|5[1-9][0-9](\.[1-9])?|6[0-9]{2}|70[0-2])$/',
                                    $row->code,
                                );
                                $labaRugiDebit = $showInLabaRugi ? $neracaSetelahAJEDebit : 0;
                                $labaRugiKredit = $showInLabaRugi ? $neracaSetelahAJEKredit : 0;

                                // Highlight baris jika COA ini punya nilai di jurnal pendapatan baru
                                $hasJP = $row->jurnal_pendapatan_debit > 0 || $row->jurnal_pendapatan_kredit > 0;
                            @endphp
                            <tr>
                                <td class="sticky-col-piutang {{ $hasJP ? 'font-semibold' : '' }}">
                                    {{ $row->code . ' ' . $row->name }}
                                    @if ($hasJP)
                                        <span
                                            class="ml-1 inline-block rounded-full bg-blue-100 px-1.5 py-0.5 text-xs text-blue-700 dark:bg-blue-900 dark:text-blue-300">JP</span>
                                    @endif
                                </td>
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
                                <td class="number-cell text-right jp-col">
                                    {{ number_format($row->jurnal_pendapatan_debit, 0, ',', '.') }}</td>
                                <td class="number-cell text-right jp-col">
                                    {{ number_format($row->jurnal_pendapatan_kredit, 0, ',', '.') }}</td>
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
                                <td class="number-cell text-right">{{ number_format($neracaKredit, 0, ',', '.') }}
                                </td>
                                <td class="number-cell text-right">{{ number_format($labaRugiDebit, 0, ',', '.') }}
                                </td>
                                <td class="number-cell text-right">{{ number_format($labaRugiKredit, 0, ',', '.') }}
                                </td>
                                <td class="number-cell text-right">
                                    {{ number_format($row->neraca_awal_bulan_depan_debit, 0, ',', '.') }}</td>
                                <td class="number-cell text-right">
                                    {{ number_format($row->neraca_awal_bulan_depan_kredit, 0, ',', '.') }}</td>
                            </tr>
                            @if (in_array($row->code, ['AO-108.3', 'AO-113', 'AO-127', 'AO-211', 'AO-305', 'AO-411', 'AO-526', 'AO-604']))
                                <tr class="separator-row-piutang">
                                    <td colspan="25"></td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <x-filament-actions::modals />
</x-filament-panels::page>
