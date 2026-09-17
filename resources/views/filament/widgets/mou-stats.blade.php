@php
    $heading = $this->getHeading();
    $description = $this->getDescription();
    $hasHeading = filled($heading);
    $hasDescription = filled($description);
    $stats = $this->getCachedStats();
    // Baris 1: Total MoUs, MoU 2026, MoU 2025 (3 item)
    $row1 = array_slice($stats, 0, 3);
    // Baris 2: Approved MoUs, Unapproved MoUs (2 item)
    $row2 = array_slice($stats, 3);
@endphp

<x-filament-widgets::widget class="fi-wi-stats-overview grid gap-y-6">
    @if ($hasHeading || $hasDescription)
        <div class="fi-wi-stats-overview-header grid gap-y-1">
            @if ($hasHeading)
                <h3 class="fi-wi-stats-overview-header-heading col-span-full text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ $heading }}
                </h3>
            @endif

            @if ($hasDescription)
                <p class="fi-wi-stats-overview-header-description overflow-hidden break-words text-sm text-gray-500 dark:text-gray-400">
                    {{ $description }}
                </p>
            @endif
        </div>
    @endif

    <div
        @if ($pollingInterval = $this->getPollingInterval())
            wire:poll.{{ $pollingInterval }}
        @endif
        class="space-y-6"
    >
        {{-- Baris 1: Total MoU, MoU 2026, MoU 2025 (3 Kolom) --}}
        <div class="fi-wi-stats-overview-stats-ctn grid gap-6 md:grid-cols-3">
            @foreach ($row1 as $stat)
                {{ $stat }}
            @endforeach
        </div>

        {{-- Baris 2: Approved MoUs, Unapproved MoUs (2 Kolom) --}}
        @if (!empty($row2))
            <div class="fi-wi-stats-overview-stats-ctn grid gap-6 md:grid-cols-2">
                @foreach ($row2 as $stat)
                    {{ $stat }}
                @endforeach
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
