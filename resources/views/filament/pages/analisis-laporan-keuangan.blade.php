@php
    $comparison = $this->getComparisonData();
    $trend = $this->getTrendData();

    $p1 = $comparison['period1'];
    $p2 = $comparison['period2'];
    $kpi = $comparison['kpi'];
    $categories = $comparison['categories'];
    $itemsGrouped = $comparison['items_grouped'];

    $entityLabel = match ($this->entity) {
        'pt' => 'PT',
        'kkp' => 'KKP',
        default => 'Konsolidasi (PT & KKP)',
    };
@endphp

<x-filament-panels::page>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="space-y-6">
        {{-- Header Status & Tab Switcher Bar --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between p-4 rounded-xl bg-white dark:bg-gray-900 shadow-sm border border-gray-200 dark:border-gray-800">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                    <x-filament::icon icon="heroicon-o-presentation-chart-line" class="h-6 w-6" />
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">
                        Analisis Keuangan: {{ $p1['period_label'] }} <span class="text-gray-400 font-normal">vs</span> {{ $p2['period_label'] }}
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Cakupan Entitas: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $entityLabel }}</span>
                    </p>
                </div>
            </div>

            {{-- Mode Switch Tabs --}}
            <div class="flex items-center p-1 bg-gray-100 dark:bg-gray-800 rounded-lg self-start sm:self-auto">
                <button
                    wire:click="setTab('comparison')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all flex items-center gap-1.5 {{ $activeTab === 'comparison' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}"
                >
                    <x-filament::icon icon="heroicon-m-arrows-right-left" class="h-4 w-4" />
                    Komparasi Bulanan
                </button>
                <button
                    wire:click="setTab('trend')"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all flex items-center gap-1.5 {{ $activeTab === 'trend' ? 'bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}"
                >
                    <x-filament::icon icon="heroicon-m-chart-bar" class="h-4 w-4" />
                    Tren 12 Bulan ({{ $primaryYear }})
                </button>
            </div>
        </div>

        {{-- 4 Executive KPI Cards via Dedicated Filament Widget --}}
        <div class="financial-kpi-wrapper">
            @livewire(\App\Filament\Widgets\FinancialKpiOverviewWidget::class, [
                'primaryMonth' => $this->primaryMonth,
                'primaryYear' => $this->primaryYear,
                'compareMonth' => $this->compareMonth,
                'compareYear' => $this->compareYear,
                'entity' => $this->entity,
            ], key('financial-kpi-' . $this->primaryMonth . '-' . $this->primaryYear . '-' . $this->compareMonth . '-' . $this->compareYear . '-' . $this->entity))
        </div>

        {{-- Chart Visualizations --}}
        @if ($activeTab === 'comparison')
            <div style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 1.5rem; align-items: stretch;">
                {{-- Chart 1: Bar Chart Komparasi 2 Periode (60% width) --}}
                <div style="flex: 3; min-width: 320px;" class="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col justify-between">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-2">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Perbandingan Finansial Periode</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $p1['period_label'] }} vs {{ $p2['period_label'] }}</p>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <span class="inline-flex items-center gap-1.5 font-medium text-gray-700 dark:text-gray-300">
                                <span class="w-3 h-3 rounded bg-amber-500 inline-block"></span>
                                {{ $p1['period_label'] }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 font-medium text-gray-700 dark:text-gray-300">
                                <span class="w-3 h-3 rounded bg-slate-500 inline-block"></span>
                                {{ $p2['period_label'] }}
                            </span>
                        </div>
                    </div>
                    <div class="relative w-full" style="height: 320px;">
                        <canvas id="comparisonBarChart"></canvas>
                    </div>
                </div>

                {{-- Chart 2: Doughnut Chart Komposisi Beban Bulan Utama (40% width) --}}
                <div style="flex: 2; min-width: 300px;" class="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col justify-between">
                    <div class="flex items-start justify-between mb-3 gap-2">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Struktur Beban (Cost Drivers)</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Komposisi Beban Terbesar {{ $p1['period_label'] }}</p>
                        </div>
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 flex-shrink-0">
                            Total: Rp {{ number_format($p1['totalBeban'], 0, ',', '.') }}
                        </span>
                    </div>

                    <div class="relative w-full flex items-center justify-center my-1" style="height: 190px;">
                        <canvas id="expenseDoughnutChart"></canvas>
                    </div>

                    {{-- Top Cost Drivers List --}}
                    @php
                        $donutColors = ['#f43f5e', '#0ea5e9', '#10b981', '#8b5cf6', '#f59e0b', '#94a3b8'];
                        $totalBebanVal = $p1['totalBeban'] > 0 ? $p1['totalBeban'] : 1;
                    @endphp
                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 space-y-2">
                        @foreach ($p1['expenseBreakdown'] as $idx => $exp)
                            @php
                                $c = $donutColors[$idx % count($donutColors)];
                                $pct = round(($exp['amount'] / $totalBebanVal) * 100, 1);
                            @endphp
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2 truncate pr-2">
                                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $c }};"></span>
                                    <span class="text-gray-700 dark:text-gray-300 truncate" title="{{ $exp['label'] }}">{{ $exp['label'] }}</span>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0 font-medium">
                                    <span class="text-gray-400">{{ $pct }}%</span>
                                    <span class="text-gray-900 dark:text-gray-200">Rp {{ number_format($exp['amount'], 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            {{-- Chart 3: Tren 12 Bulan (Full Year) --}}
            <div class="p-5 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Grafik Tren Finansial 12 Bulan (Tahun {{ $primaryYear }})</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Pergerakan Pendapatan, Beban, dan Laba Bersih dari Januari hingga Desember</p>
                    </div>
                </div>
                <div class="relative w-full" style="height: 360px;">
                    <canvas id="trend12MonthChart"></canvas>
                </div>
            </div>
        @endif

        {{-- Variance Analysis Table --}}
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-gray-200 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Tabel Analisis Variansi COA (Head-to-Head)</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Perbandingan pergerakan nilai per akun antara {{ $p1['period_label'] }} dan {{ $p2['period_label'] }}</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/60 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700 uppercase tracking-wider font-semibold">
                            <th class="py-3 px-4 w-28">Kode Akun</th>
                            <th class="py-3 px-4">Nama Akun</th>
                            <th class="py-3 px-4 text-right">{{ $p1['period_label'] }}</th>
                            <th class="py-3 px-4 text-right">{{ $p2['period_label'] }}</th>
                            <th class="py-3 px-4 text-right">Selisih (Rp)</th>
                            <th class="py-3 px-4 text-right">Perubahan (%)</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($categories as $cat)
                            @php
                                $items = $itemsGrouped->get($cat, collect());
                                if ($items->isEmpty()) continue;

                                $subtotal1 = $items->sum('val1');
                                $subtotal2 = $items->sum('val2');
                                $subtotalDiff = $subtotal1 - $subtotal2;
                                $subtotalPct = $subtotal2 != 0 ? ($subtotalDiff / abs($subtotal2)) * 100 : ($subtotal1 > 0 ? 100 : 0);
                            @endphp

                            {{-- Category Header --}}
                            <tr class="bg-gray-50/80 dark:bg-gray-800/40 font-bold text-gray-800 dark:text-gray-200">
                                <td colspan="7" class="py-2.5 px-4 text-xs tracking-wide">
                                    {{ strtoupper($cat) }}
                                </td>
                            </tr>

                            {{-- Item Rows --}}
                            @foreach ($items as $item)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/20 text-gray-700 dark:text-gray-300">
                                    <td class="py-2 px-4 font-mono font-medium text-gray-500 dark:text-gray-400">{{ $item['code'] }}</td>
                                    <td class="py-2 px-4">{{ $item['name'] }}</td>
                                    <td class="py-2 px-4 text-right font-mono">Rp {{ number_format($item['val1'], 0, ',', '.') }}</td>
                                    <td class="py-2 px-4 text-right font-mono text-gray-500 dark:text-gray-400">Rp {{ number_format($item['val2'], 0, ',', '.') }}</td>
                                    <td class="py-2 px-4 text-right font-mono {{ $item['diff_nominal'] >= 0 ? 'text-gray-900 dark:text-white' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ ($item['diff_nominal'] >= 0 ? '+' : '') . number_format($item['diff_nominal'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-2 px-4 text-right font-mono font-semibold {{ $item['is_favorable'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ ($item['diff_percent'] >= 0 ? '+' : '') . number_format($item['diff_percent'], 1) }}%
                                    </td>
                                    <td class="py-2 px-4 text-center">
                                        @if ($item['diff_nominal'] == 0)
                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] bg-gray-100 dark:bg-gray-800 text-gray-500">Stabil</span>
                                        @elseif ($item['is_favorable'])
                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 font-semibold">
                                                {{ $item['category'] === 'Beban' ? 'Efisien' : 'Meningkat' }}
                                            </span>
                                        @else
                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] bg-rose-100 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400 font-semibold">
                                                {{ $item['category'] === 'Beban' ? 'Membengkak' : 'Menurun' }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Category Subtotal --}}
                            <tr class="bg-gray-100/50 dark:bg-gray-800/50 font-bold border-t border-b border-gray-200 dark:border-gray-700">
                                <td colspan="2" class="py-2.5 px-4 text-right">TOTAL {{ strtoupper($cat) }}:</td>
                                <td class="py-2.5 px-4 text-right font-mono">Rp {{ number_format($subtotal1, 0, ',', '.') }}</td>
                                <td class="py-2.5 px-4 text-right font-mono">Rp {{ number_format($subtotal2, 0, ',', '.') }}</td>
                                <td class="py-2.5 px-4 text-right font-mono">
                                    {{ ($subtotalDiff >= 0 ? '+' : '') . number_format($subtotalDiff, 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-4 text-right font-mono font-bold">
                                    {{ ($subtotalPct >= 0 ? '+' : '') . number_format($subtotalPct, 1) }}%
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <span class="text-xs text-gray-500">Subtotal</span>
                                </td>
                            </tr>
                        @endforeach

                        {{-- Grand Total Laba / (Rugi) Bersih --}}
                        <tr class="bg-amber-500/10 dark:bg-amber-500/15 font-black text-sm border-t-2 border-amber-500">
                            <td colspan="2" class="py-3 px-4 text-right text-gray-900 dark:text-white">LABA / (RUGI) BERSIH:</td>
                            <td class="py-3 px-4 text-right font-mono text-emerald-600 dark:text-emerald-400">Rp {{ number_format($kpi['laba_bersih']['current'], 0, ',', '.') }}</td>
                            <td class="py-3 px-4 text-right font-mono text-gray-600 dark:text-gray-300">Rp {{ number_format($kpi['laba_bersih']['previous'], 0, ',', '.') }}</td>
                            <td class="py-3 px-4 text-right font-mono text-gray-900 dark:text-white">
                                {{ ($kpi['laba_bersih']['diff_nominal'] >= 0 ? '+' : '') . number_format($kpi['laba_bersih']['diff_nominal'], 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-black {{ $kpi['laba_bersih']['diff_nominal'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ ($kpi['laba_bersih']['diff_percent'] >= 0 ? '+' : '') . number_format($kpi['laba_bersih']['diff_percent'], 1) }}%
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2.5 py-1 rounded-md text-xs font-bold {{ $kpi['laba_bersih']['diff_nominal'] >= 0 ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white' }}">
                                    {{ $kpi['laba_bersih']['diff_nominal'] >= 0 ? 'FAVORABLE' : 'UNFAVORABLE' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        /* Memastikan 4 kartu KPI sejajar rapi di desktop dan responsif */
        .financial-kpi-wrapper .fi-wi-stats-overview-stats-ctn {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)) !important;
            gap: 1rem !important;
        }
    </style>

    {{-- Chart Initialization Scripts --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            initCharts();
        });

        document.addEventListener('livewire:navigated', function () {
            initCharts();
        });

        function initCharts() {
            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';
            const textColor = isDark ? '#94a3b8' : '#64748b';

            // 1. Comparison Bar Chart
            const ctxComparison = document.getElementById('comparisonBarChart');
            if (ctxComparison) {
                new Chart(ctxComparison, {
                    type: 'bar',
                    data: {
                        labels: ['Total Pendapatan', 'Total Beban', 'Laba Bersih'],
                        datasets: [
                            {
                                label: @js($p1['period_label']),
                                data: [
                                    @js(round($kpi['pendapatan']['current'])),
                                    @js(round($kpi['beban']['current'])),
                                    @js(round($kpi['laba_bersih']['current']))
                                ],
                                backgroundColor: '#f59e0b', // Amber
                                borderRadius: 6,
                            },
                            {
                                label: @js($p2['period_label']),
                                data: [
                                    @js(round($kpi['pendapatan']['previous'])),
                                    @js(round($kpi['beban']['previous'])),
                                    @js(round($kpi['laba_bersih']['previous']))
                                ],
                                backgroundColor: '#64748b', // Slate
                                borderRadius: 6,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        categoryPercentage: 0.55,
                        barPercentage: 0.8,
                        maxBarThickness: 42,
                        plugins: {
                            legend: {
                                display: false, // Ditampilkan melalui pill header kustom di atas card
                            },
                            tooltip: {
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function (context) {
                                        let val = context.parsed.y || 0;
                                        return ' ' + context.dataset.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(val);
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: textColor, font: { weight: '600' } }
                            },
                            y: {
                                grid: { color: gridColor },
                                ticks: {
                                    color: textColor,
                                    callback: function (val) {
                                        return 'Rp ' + (val / 1000000).toFixed(0) + ' Jt';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // 2. Expense Doughnut Chart
            const ctxExpense = document.getElementById('expenseDoughnutChart');
            if (ctxExpense) {
                const breakdown = @js($p1['expenseBreakdown']);
                const labels = breakdown.map(i => i.label);
                const data = breakdown.map(i => i.amount);

                new Chart(ctxExpense, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: [
                                '#f43f5e', // Rose
                                '#0ea5e9', // Sky
                                '#10b981', // Emerald
                                '#8b5cf6', // Violet
                                '#f59e0b', // Amber
                                '#94a3b8', // Gray
                            ],
                            borderWidth: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false, // Digantikan dengan rincian list terstruktur di bawah donut
                            },
                            tooltip: {
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function (context) {
                                        const dataset = context.dataset;
                                        const total = dataset.data.reduce((acc, current) => acc + current, 0);
                                        const val = context.parsed || 0;
                                        const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                        return ' ' + context.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(val) + ' (' + pct + '%)';
                                    }
                                }
                            }
                        },
                        cutout: '70%'
                    }
                });
            }

            // 3. 12-Month Trend Chart (If on trend tab)
            const ctxTrend = document.getElementById('trend12MonthChart');
            if (ctxTrend) {
                const trend = @js($trend);
                new Chart(ctxTrend, {
                    type: 'bar',
                    data: {
                        labels: trend.labels,
                        datasets: [
                            {
                                type: 'line',
                                label: 'Laba Bersih',
                                data: trend.laba,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                borderWidth: 3,
                                fill: false,
                                tension: 0.3,
                                yAxisID: 'y'
                            },
                            {
                                type: 'bar',
                                label: 'Pendapatan',
                                data: trend.pendapatan,
                                backgroundColor: '#f59e0b',
                                borderRadius: 4,
                                yAxisID: 'y'
                            },
                            {
                                type: 'bar',
                                label: 'Beban',
                                data: trend.beban,
                                backgroundColor: '#f43f5e',
                                borderRadius: 4,
                                yAxisID: 'y'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        categoryPercentage: 0.7,
                        barPercentage: 0.85,
                        maxBarThickness: 32,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: { color: textColor }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        let val = context.parsed.y || 0;
                                        return context.dataset.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(val);
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: textColor }
                            },
                            y: {
                                grid: { color: gridColor },
                                ticks: {
                                    color: textColor,
                                    callback: function (val) {
                                        return 'Rp ' + (val / 1000000).toFixed(0) + ' Jt';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
    </script>
</x-filament-panels::page>

