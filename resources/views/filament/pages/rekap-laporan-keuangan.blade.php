<x-filament-panels::page>
    <div x-data="{ activeTab: 'laba_rugi' }" class="space-y-6">
        <!-- Tab Navigation -->
        <div class="flex border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 rounded-t-xl p-2 gap-2 shadow-sm">
            <button 
                @click="activeTab = 'laba_rugi'"
                :class="activeTab === 'laba_rugi' ? 'bg-primary-500 text-white font-semibold shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'"
                class="px-4 py-2 rounded-lg text-sm transition-all duration-200 cursor-pointer"
            >
                Rekap Laba Rugi
            </button>
            <button 
                @click="activeTab = 'neraca'"
                :class="activeTab === 'neraca' ? 'bg-primary-500 text-white font-semibold shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'"
                class="px-4 py-2 rounded-lg text-sm transition-all duration-200 cursor-pointer"
            >
                Rekap Neraca
            </button>
        </div>

        <!-- Laba Rugi Tab Content -->
        <div x-show="activeTab === 'laba_rugi'" class="space-y-4">
            @php
                $lrData = $this->getLabaRugiData();
                $matrix = $lrData['matrix'];
                $totals = $lrData['totals'];
                $coas = $lrData['coas'];
            @endphp

            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <div class="mb-4 text-center border-b border-gray-100 dark:border-gray-800 pb-4">
                    <h3 class="text-xl font-bold text-gray-950 dark:text-white uppercase tracking-wider">Rekap Laba Rugi</h3>
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">KKP AO - Tahun {{ $this->year }}</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 border border-gray-200 dark:border-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider border border-gray-200 dark:border-gray-700 min-w-[280px]">Keterangan</th>
                                @for ($m = 1; $m <= 12; $m++)
                                    <th scope="col" class="px-3 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 w-[100px]">{{ $m }}</th>
                                @endfor
                                <th scope="col" class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider border border-gray-200 dark:border-gray-700 w-[140px]">Total / Tahun</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                            <!-- PENDAPATAN SECTION -->
                            <tr class="bg-gray-50 dark:bg-gray-800">
                                <td colspan="14" class="px-4 py-2 text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider border border-gray-200 dark:border-gray-700">Pendapatan</td>
                            </tr>
                            @foreach ($coas as $coa)
                                @if ($coa->group_coa_id == 40)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-300 border border-gray-200 dark:border-gray-700 pl-8">{{ $coa->name }}</td>
                                        @for ($m = 1; $m <= 12; $m++)
                                            <td class="px-3 py-2 text-sm text-right text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                                                {{ $matrix[$coa->code]['months'][$m] != 0 ? number_format($matrix[$coa->code]['months'][$m], 0, ',', '.') : '-' }}
                                            </td>
                                        @endfor
                                        <td class="px-4 py-2 text-sm text-right font-semibold text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/20">
                                            {{ $matrix[$coa->code]['total'] != 0 ? number_format($matrix[$coa->code]['total'], 0, ',', '.') : '-' }}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                            <!-- LABA KOTOR -->
                            <tr class="bg-gray-100 dark:bg-gray-800 font-bold border border-gray-200 dark:border-gray-700">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 pl-4">LABA KOTOR</td>
                                @for ($m = 1; $m <= 12; $m++)
                                    <td class="px-3 py-3 text-sm text-right text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700">
                                        {{ $totals['labaKotor'][$m] != 0 ? number_format($totals['labaKotor'][$m], 0, ',', '.') : '-' }}
                                    </td>
                                @endfor
                                <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 bg-gray-200/50 dark:bg-gray-700/50">
                                    {{ $totals['labaKotorTotal'] != 0 ? number_format($totals['labaKotorTotal'], 0, ',', '.') : '-' }}
                                </td>
                            </tr>

                            <!-- BIAYA - BIAYA SECTION -->
                            <tr class="bg-gray-50 dark:bg-gray-800">
                                <td colspan="14" class="px-4 py-2 text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider border border-gray-200 dark:border-gray-700">Biaya - Biaya</td>
                            </tr>
                            @foreach ($coas as $coa)
                                @if ($coa->group_coa_id == 50)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-300 border border-gray-200 dark:border-gray-700 pl-8">{{ $coa->name }}</td>
                                        @for ($m = 1; $m <= 12; $m++)
                                            <td class="px-3 py-2 text-sm text-right text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                                                {{ $matrix[$coa->code]['months'][$m] != 0 ? number_format($matrix[$coa->code]['months'][$m], 0, ',', '.') : '-' }}
                                            </td>
                                        @endfor
                                        <td class="px-4 py-2 text-sm text-right font-semibold text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/20">
                                            {{ $matrix[$coa->code]['total'] != 0 ? number_format($matrix[$coa->code]['total'], 0, ',', '.') : '-' }}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                            <!-- Total Biaya Usaha -->
                            <tr class="bg-gray-50 dark:bg-gray-850 font-bold border border-gray-200 dark:border-gray-700">
                                <td class="px-4 py-2.5 text-sm text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 pl-4">Total Biaya Usaha</td>
                                @for ($m = 1; $m <= 12; $m++)
                                    <td class="px-3 py-2.5 text-sm text-right text-gray-950 dark:text-gray-100 border border-gray-200 dark:border-gray-700">
                                        {{ $totals['totalBiayaUsaha'][$m] != 0 ? number_format($totals['totalBiayaUsaha'][$m], 0, ',', '.') : '-' }}
                                    </td>
                                @endfor
                                <td class="px-4 py-2.5 text-sm text-right text-gray-950 dark:text-gray-100 border border-gray-200 dark:border-gray-700 bg-gray-200/30 dark:bg-gray-700/30">
                                    {{ $totals['totalBiayaUsahaTotal'] != 0 ? number_format($totals['totalBiayaUsahaTotal'], 0, ',', '.') : '-' }}
                                </td>
                            </tr>
                            <!-- Laba Operasional -->
                            <tr class="bg-slate-50 dark:bg-slate-800/80 font-bold border border-gray-200 dark:border-gray-700">
                                <td class="px-4 py-2.5 text-sm text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 pl-4">Laba Operasional</td>
                                @for ($m = 1; $m <= 12; $m++)
                                    <td class="px-3 py-2.5 text-sm text-right text-gray-950 dark:text-gray-100 border border-gray-200 dark:border-gray-700">
                                        {{ $totals['labaOperasional'][$m] != 0 ? number_format($totals['labaOperasional'][$m], 0, ',', '.') : '-' }}
                                    </td>
                                @endfor
                                <td class="px-4 py-2.5 text-sm text-right text-gray-950 dark:text-gray-100 border border-gray-200 dark:border-gray-700 bg-slate-200/50 dark:bg-slate-700/50">
                                    {{ $totals['labaOperasionalTotal'] != 0 ? number_format($totals['labaOperasionalTotal'], 0, ',', '.') : '-' }}
                                </td>
                            </tr>

                            <!-- PENDAPATAN (BIAYA) LUAR USAHA SECTION -->
                            <tr class="bg-gray-50 dark:bg-gray-800">
                                <td colspan="14" class="px-4 py-2 text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider border border-gray-200 dark:border-gray-700">Pendapatan (Biaya) Luar Usaha</td>
                            </tr>
                            @foreach ($coas as $coa)
                                @if ($coa->group_coa_id == 60 || $coa->group_coa_id == 70)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-300 border border-gray-200 dark:border-gray-700 pl-8">{{ $coa->name }}</td>
                                        @for ($m = 1; $m <= 12; $m++)
                                            <td class="px-3 py-2 text-sm text-right border border-gray-200 dark:border-gray-700 {{ $matrix[$coa->code]['months'][$m] < 0 ? 'text-red-500 font-medium' : 'text-gray-500 dark:text-gray-400' }}">
                                                {{ $matrix[$coa->code]['months'][$m] != 0 ? number_format($matrix[$coa->code]['months'][$m], 0, ',', '.') : '-' }}
                                            </td>
                                        @endfor
                                        <td class="px-4 py-2 text-sm text-right font-semibold border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/20 {{ $matrix[$coa->code]['total'] < 0 ? 'text-red-500' : 'text-gray-900 dark:text-white' }}">
                                            {{ $matrix[$coa->code]['total'] != 0 ? number_format($matrix[$coa->code]['total'], 0, ',', '.') : '-' }}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                            <!-- Net Luar Usaha -->
                            <tr class="bg-gray-50 dark:bg-gray-850 font-bold border border-gray-200 dark:border-gray-700">
                                <td class="px-4 py-2.5 text-sm text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 pl-4">Total Pendapatan (Biaya) Luar Usaha</td>
                                @for ($m = 1; $m <= 12; $m++)
                                    <td class="px-3 py-2.5 text-sm text-right border border-gray-200 dark:border-gray-700 {{ $totals['netLuarUsaha'][$m] < 0 ? 'text-red-500' : 'text-gray-950 dark:text-gray-100' }}">
                                        {{ $totals['netLuarUsaha'][$m] != 0 ? number_format($totals['netLuarUsaha'][$m], 0, ',', '.') : '-' }}
                                    </td>
                                @endfor
                                <td class="px-4 py-2.5 text-sm text-right border border-gray-200 dark:border-gray-700 bg-gray-200/30 dark:bg-gray-700/30 {{ $totals['netLuarUsahaTotal'] < 0 ? 'text-red-500' : 'text-gray-950 dark:text-gray-100' }}">
                                    {{ $totals['netLuarUsahaTotal'] != 0 ? number_format($totals['netLuarUsahaTotal'], 0, ',', '.') : '-' }}
                                </td>
                            </tr>

                            <!-- LABA (RUGI) TAHUN BERJALAN -->
                            <tr class="bg-slate-100 dark:bg-slate-800 font-bold border border-gray-200 dark:border-gray-700">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 pl-4">LABA (RUGI) TAHUN BERJALAN</td>
                                @for ($m = 1; $m <= 12; $m++)
                                    <td class="px-3 py-3 text-sm text-right border border-gray-200 dark:border-gray-700 {{ $totals['labaRugiTahunBerjalan'][$m] < 0 ? 'text-red-500' : 'text-emerald-600 dark:text-emerald-400' }}">
                                        {{ $totals['labaRugiTahunBerjalan'][$m] != 0 ? number_format($totals['labaRugiTahunBerjalan'][$m], 0, ',', '.') : '-' }}
                                    </td>
                                @endfor
                                <td class="px-4 py-3 text-sm text-right border border-gray-200 dark:border-gray-700 bg-slate-200/50 dark:bg-slate-750 {{ $totals['labaRugiTahunBerjalanTotal'] < 0 ? 'text-red-500' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    {{ $totals['labaRugiTahunBerjalanTotal'] != 0 ? number_format($totals['labaRugiTahunBerjalanTotal'], 0, ',', '.') : '-' }}
                                </td>
                            </tr>
                            <!-- % LABA -->
                            <tr class="bg-slate-50 dark:bg-slate-900 font-bold border border-gray-200 dark:border-gray-700">
                                <td class="px-4 py-2.5 text-sm text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 pl-4">% LABA</td>
                                @for ($m = 1; $m <= 12; $m++)
                                    <td class="px-3 py-2.5 text-sm text-right border border-gray-200 dark:border-gray-700 {{ $totals['percentLaba'][$m] < 0 ? 'text-red-500' : 'text-gray-900 dark:text-white' }}">
                                        {{ number_format($totals['percentLaba'][$m], 2, ',', '.') }}%
                                    </td>
                                @endfor
                                <td class="px-4 py-2.5 text-sm text-right border border-gray-200 dark:border-gray-700 bg-slate-200/20 dark:bg-slate-800 {{ $totals['percentLabaTotal'] < 0 ? 'text-red-500' : 'text-gray-900 dark:text-white' }}">
                                    {{ number_format($totals['percentLabaTotal'], 2, ',', '.') }}%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Neraca Tab Content -->
        <div x-show="activeTab === 'neraca'" class="space-y-4" x-cloak>
            @php
                $nData = $this->getNeracaData();
                $nMatrix = $nData['matrix'];
                $nTotals = $nData['totals'];
                $nCoas = $nData['coas'];
                $nGroupCoas = $nData['groupCoas'];
            @endphp

            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <div class="mb-4 text-center border-b border-gray-100 dark:border-gray-800 pb-4">
                    <h3 class="text-xl font-bold text-gray-950 dark:text-white uppercase tracking-wider">Rekap Neraca</h3>
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Tahun {{ $this->year }}</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 border border-gray-200 dark:border-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider border border-gray-200 dark:border-gray-700 min-w-[280px]">Keterangan</th>
                                @for ($m = 1; $m <= 12; $m++)
                                    <th scope="col" class="px-3 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 w-[100px]">{{ $m }}</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                            <!-- AKTIVA SECTION -->
                            <tr class="bg-slate-100 dark:bg-slate-800">
                                <td colspan="13" class="px-4 py-2 text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider border border-gray-200 dark:border-gray-700">AKTIVA</td>
                            </tr>
                            @foreach ($nGroupCoas as $group)
                                @if (in_array($group->id, [10, 11, 12]))
                                    <tr class="bg-gray-50 dark:bg-gray-800/80">
                                        <td colspan="13" class="px-4 py-1.5 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider border border-gray-200 dark:border-gray-700 pl-6">{{ $group->name }}</td>
                                    </tr>
                                    @foreach ($nCoas as $coa)
                                        @if ($coa->group_coa_id == $group->id)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                                <td class="px-4 py-1.5 text-xs text-gray-900 dark:text-gray-300 border border-gray-200 dark:border-gray-700 pl-10">{{ $coa->name }}</td>
                                                @for ($m = 1; $m <= 12; $m++)
                                                    <td class="px-3 py-1.5 text-xs text-right border border-gray-200 dark:border-gray-700 {{ $nMatrix[$coa->code]['months'][$m] < 0 ? 'text-red-500' : 'text-gray-500 dark:text-gray-400' }}">
                                                        {{ $nMatrix[$coa->code]['months'][$m] != 0 ? number_format($nMatrix[$coa->code]['months'][$m], 0, ',', '.') : '-' }}
                                                    </td>
                                                @endfor
                                            </tr>
                                        @endif
                                    @endforeach
                                    <!-- Group Total -->
                                    <tr class="bg-gray-50 dark:bg-gray-800/30 font-bold border border-gray-200 dark:border-gray-700">
                                        <td class="px-4 py-2 text-xs text-gray-950 dark:text-white border border-gray-200 dark:border-gray-700 pl-6">Total {{ $group->name }}</td>
                                        @for ($m = 1; $m <= 12; $m++)
                                            <td class="px-3 py-2 text-xs text-right border border-gray-200 dark:border-gray-700 {{ $nTotals['groupTotals'][$group->id][$m] < 0 ? 'text-red-500' : 'text-gray-950 dark:text-white' }}">
                                                {{ $nTotals['groupTotals'][$group->id][$m] != 0 ? number_format($nTotals['groupTotals'][$group->id][$m], 0, ',', '.') : '-' }}
                                            </td>
                                        @endfor
                                    </tr>
                                @endif
                            @endforeach
                            <!-- TOTAL AKTIVA -->
                            <tr class="bg-slate-200 dark:bg-slate-800 font-extrabold border border-gray-200 dark:border-gray-700">
                                <td class="px-4 py-2.5 text-sm text-slate-900 dark:text-white border border-gray-200 dark:border-gray-700 pl-4">TOTAL AKTIVA</td>
                                @for ($m = 1; $m <= 12; $m++)
                                    <td class="px-3 py-2.5 text-sm text-right border border-gray-200 dark:border-gray-700 {{ $nTotals['totalAktiva'][$m] < 0 ? 'text-red-500' : 'text-slate-900 dark:text-white' }}">
                                        {{ $nTotals['totalAktiva'][$m] != 0 ? number_format($nTotals['totalAktiva'][$m], 0, ',', '.') : '-' }}
                                    </td>
                                @endfor
                            </tr>

                            <!-- PASIVA SECTION -->
                            <tr class="bg-slate-100 dark:bg-slate-800 mt-4">
                                <td colspan="13" class="px-4 py-2 text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider border border-gray-200 dark:border-gray-700">PASIVA</td>
                            </tr>
                            @foreach ($nGroupCoas as $group)
                                @if (in_array($group->id, [20, 21, 30]))
                                    <tr class="bg-gray-50 dark:bg-gray-800/80">
                                        <td colspan="13" class="px-4 py-1.5 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider border border-gray-200 dark:border-gray-700 pl-6">{{ $group->name }}</td>
                                    </tr>
                                    @foreach ($nCoas as $coa)
                                        @if ($coa->group_coa_id == $group->id)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                                <td class="px-4 py-1.5 text-xs text-gray-900 dark:text-gray-300 border border-gray-200 dark:border-gray-700 pl-10">{{ $coa->name }}</td>
                                                @for ($m = 1; $m <= 12; $m++)
                                                    <td class="px-3 py-1.5 text-xs text-right border border-gray-200 dark:border-gray-700 {{ $nMatrix[$coa->code]['months'][$m] < 0 ? 'text-red-500' : 'text-gray-500 dark:text-gray-400' }}">
                                                        {{ $nMatrix[$coa->code]['months'][$m] != 0 ? number_format($nMatrix[$coa->code]['months'][$m], 0, ',', '.') : '-' }}
                                                    </td>
                                                @endfor
                                            </tr>
                                        @endif
                                    @endforeach
                                    
                                    <!-- Special: Sisa Dana Tahun Berjalan for Group 30 -->
                                    @if ($group->id == 30)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                            <td class="px-4 py-1.5 text-xs font-semibold text-gray-900 dark:text-gray-300 border border-gray-200 dark:border-gray-700 pl-10">Sisa (Lebih) Dana Tahun Berjalan</td>
                                            @for ($m = 1; $m <= 12; $m++)
                                                <td class="px-3 py-1.5 text-xs text-right border border-gray-200 dark:border-gray-700 {{ $nTotals['sisaDanaTahunBerjalan'][$m] < 0 ? 'text-red-500' : 'text-gray-500 dark:text-gray-400' }}">
                                                    {{ $nTotals['sisaDanaTahunBerjalan'][$m] != 0 ? number_format($nTotals['sisaDanaTahunBerjalan'][$m], 0, ',', '.') : '-' }}
                                                </td>
                                            @endfor
                                        </tr>
                                    @endif

                                    <!-- Group Total -->
                                    <tr class="bg-gray-50 dark:bg-gray-800/30 font-bold border border-gray-200 dark:border-gray-700">
                                        <td class="px-4 py-2 text-xs text-gray-950 dark:text-white border border-gray-200 dark:border-gray-700 pl-6">Total {{ $group->name }}</td>
                                        @for ($m = 1; $m <= 12; $m++)
                                            @php
                                                $gTotal = $nTotals['groupTotals'][$group->id][$m];
                                                if ($group->id == 30) {
                                                    $gTotal += $nTotals['sisaDanaTahunBerjalan'][$m];
                                                }
                                            @endphp
                                            <td class="px-3 py-2 text-xs text-right border border-gray-200 dark:border-gray-700 {{ $gTotal < 0 ? 'text-red-500' : 'text-gray-950 dark:text-white' }}">
                                                {{ $gTotal != 0 ? number_format($gTotal, 0, ',', '.') : '-' }}
                                            </td>
                                        @endfor
                                    </tr>
                                @endif
                            @endforeach
                            <!-- TOTAL PASIVA -->
                            <tr class="bg-slate-200 dark:bg-slate-800 font-extrabold border border-gray-200 dark:border-gray-700">
                                <td class="px-4 py-2.5 text-sm text-slate-900 dark:text-white border border-gray-200 dark:border-gray-700 pl-4">TOTAL PASIVA</td>
                                @for ($m = 1; $m <= 12; $m++)
                                    <td class="px-3 py-2.5 text-sm text-right border border-gray-200 dark:border-gray-700 {{ $nTotals['totalPasiva'][$m] < 0 ? 'text-red-500' : 'text-slate-900 dark:text-white' }}">
                                        {{ $nTotals['totalPasiva'][$m] != 0 ? number_format($nTotals['totalPasiva'][$m], 0, ',', '.') : '-' }}
                                    </td>
                                @endfor
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
