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

        {{-- Chart Visualizations via Official Filament Chart Widgets --}}
        @if ($activeTab === 'comparison')
            <div class="financial-charts-row" style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 1.5rem; align-items: stretch;">
                {{-- Chart 1: Bar Chart Komparasi 2 Periode (60% width) --}}
                <div class="chart-col-bar" style="flex: 3; min-width: 320px;">
                    @livewire(\App\Filament\Widgets\FinancialComparisonChartWidget::class, [
                        'primaryMonth' => $this->primaryMonth,
                        'primaryYear' => $this->primaryYear,
                        'compareMonth' => $this->compareMonth,
                        'compareYear' => $this->compareYear,
                        'entity' => $this->entity,
                    ], key('fin-compare-chart-' . $this->primaryMonth . '-' . $this->primaryYear . '-' . $this->compareMonth . '-' . $this->compareYear . '-' . $this->entity))
                </div>

                {{-- Chart 2: Doughnut Chart Komposisi Beban Bulan Utama (40% width) --}}
                <div class="chart-col-donut" style="flex: 2; min-width: 300px;">
                    @livewire(\App\Filament\Widgets\ExpenseStructureChartWidget::class, [
                        'primaryMonth' => $this->primaryMonth,
                        'primaryYear' => $this->primaryYear,
                        'entity' => $this->entity,
                    ], key('fin-expense-chart-' . $this->primaryMonth . '-' . $this->primaryYear . '-' . $this->entity))
                </div>
            </div>
        @else
            {{-- Chart 3: Tren 12 Bulan (Full Year) --}}
            <div>
                @livewire(\App\Filament\Widgets\FinancialTrendChartWidget::class, [
                    'year' => $this->primaryYear,
                    'entity' => $this->entity,
                ], key('fin-trend-chart-' . $this->primaryYear . '-' . $this->entity))
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

        /* Memastikan kedua widget chart memiliki tinggi yang sama persis (Equal Height) */
        .financial-charts-row {
            display: flex !important;
            align-items: stretch !important;
        }
        .financial-charts-row > div {
            display: flex !important;
            flex-direction: column !important;
        }
        .financial-charts-row > div > div,
        .financial-charts-row .fi-wi-chart,
        .financial-charts-row .fi-section {
            height: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            flex: 1 1 auto !important;
        }
        .financial-charts-row .fi-section-content-ctn {
            height: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            flex: 1 1 auto !important;
        }
        .financial-charts-row .fi-section-content {
            height: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            flex: 1 1 auto !important;
        }
        .financial-charts-row .chart-col-bar .fi-section-content > div {
            flex: 1 1 auto !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
        }
    </style>
</x-filament-panels::page>

