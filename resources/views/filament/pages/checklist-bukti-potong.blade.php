<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-2 mb-6">
        <!-- Card 1: Sudah Checklist -->
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-zinc-900 dark:ring-white/10">
            <div class="flex items-center gap-x-4">
                <div class="rounded-lg bg-emerald-50 p-3 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Total PPh23 Sudah Checklist
                    </div>
                    <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white mt-1">
                        Rp {{ number_format($this->getStats()['checked'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Belum Checklist -->
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-zinc-900 dark:ring-white/10">
            <div class="flex items-center gap-x-4">
                <div class="rounded-lg bg-amber-50 p-3 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Total PPh23 Belum Checklist
                    </div>
                    <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white mt-1">
                        Rp {{ number_format($this->getStats()['unchecked'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
