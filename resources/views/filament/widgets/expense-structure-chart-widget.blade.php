@php
    use Filament\Support\Facades\FilamentView;

    $color = $this->getColor();
    $heading = $this->getHeading();
    $description = $this->getDescription();
    $summary = $this->getExpenseSummary();
    $totalBeban = $summary['totalBeban'] ?? 0;
    $breakdown = $summary['expenseBreakdown'] ?? [];
    $donutColors = ['#f43f5e', '#0ea5e9', '#10b981', '#8b5cf6', '#f59e0b', '#94a3b8'];
@endphp

<x-filament-widgets::widget class="fi-wi-chart">
    <x-filament::section :description="$description" :heading="$heading">
        <x-slot name="headerEnd">
            <span class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                Total: Rp {{ number_format($totalBeban, 0, ',', '.') }}
            </span>
        </x-slot>

        <div class="space-y-4">
            {{-- Doughnut Canvas Container with All Required Alpine Refs --}}
            <div
                @if (FilamentView::hasSpaMode())
                    x-load="visible"
                @else
                    x-load
                @endif
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                wire:ignore
                x-data="chart({
                            cachedData: @js($this->getCachedData()),
                            options: @js($this->getOptions()),
                            type: @js($this->getType()),
                        })"
                @class([
                    match ($color) {
                        'gray' => null,
                        default => 'fi-color-custom',
                    },
                    is_string($color) ? "fi-color-{$color}" : null,
                ])
            >
                <div class="flex items-center justify-center my-1" style="height: 200px;">
                    <canvas
                        x-ref="canvas"
                        style="max-height: 200px;"
                    ></canvas>
                </div>

                {{-- All required color reference elements for Filament chart Alpine component --}}
                <span
                    x-ref="backgroundColorElement"
                    @class([
                        match ($color) {
                            'gray' => 'text-gray-100 dark:text-gray-800',
                            default => 'text-custom-50 dark:text-custom-400/10',
                        },
                    ])
                    @style([
                        \Filament\Support\get_color_css_variables(
                            $color,
                            shades: [50, 400],
                            alias: 'widgets::chart-widget.background',
                        ) => $color !== 'gray',
                    ])
                ></span>

                <span
                    x-ref="borderColorElement"
                    @class([
                        match ($color) {
                            'gray' => 'text-gray-400',
                            default => 'text-custom-500 dark:text-custom-400',
                        },
                    ])
                    @style([
                        \Filament\Support\get_color_css_variables(
                            $color,
                            shades: [400, 500],
                            alias: 'widgets::chart-widget.border',
                        ) => $color !== 'gray',
                    ])
                ></span>

                <span
                    x-ref="gridColorElement"
                    class="text-gray-200 dark:text-gray-800"
                ></span>

                <span
                    x-ref="textColorElement"
                    class="text-gray-500 dark:text-gray-400"
                ></span>
            </div>

            {{-- Top Cost Drivers Breakdown List --}}
            @if (!empty($breakdown))
                <div class="pt-3 border-t border-gray-100 dark:border-gray-800 space-y-2">
                    @foreach ($breakdown as $idx => $exp)
                        @php
                            $c = $donutColors[$idx % count($donutColors)];
                            $pct = $totalBeban > 0 ? round(($exp['amount'] / $totalBeban) * 100, 1) : 0;
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
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
