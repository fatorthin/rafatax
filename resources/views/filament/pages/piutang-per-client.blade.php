<x-filament-panels::page>
    @php
        $currentPeriode = $this->periode ?? 'all';
        $stats = $this->getStats();
    @endphp

    <!-- Filter Tabs / Segmented Control -->
    <div class="flex flex-wrap items-center gap-2 p-1.5 bg-gray-100/90 dark:bg-gray-900/60 rounded-2xl w-fit border border-gray-200/80 dark:border-gray-800 shadow-sm">
        <button 
            type="button"
            wire:click="setPeriode('all')" 
            class="flex items-center gap-2 px-4 py-2 text-xs md:text-sm font-semibold rounded-xl cursor-pointer transition-all duration-200 {{ $currentPeriode === 'all' ? 'bg-white dark:bg-gray-800 text-primary-600 dark:text-primary-400 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-200/50 dark:hover:bg-gray-800/50' }}">
            <x-heroicon-o-squares-2x2 class="w-4 h-4" />
            <span>Semua Periode</span>
        </button>
        <button 
            type="button"
            wire:click="setPeriode('pre_2025')" 
            class="flex items-center gap-2 px-4 py-2 text-xs md:text-sm font-semibold rounded-xl cursor-pointer transition-all duration-200 {{ $currentPeriode === 'pre_2025' ? 'bg-white dark:bg-gray-800 text-primary-600 dark:text-primary-400 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-200/50 dark:hover:bg-gray-800/50' }}">
            <x-heroicon-o-clock class="w-4 h-4" />
            <span>Sebelum Tahun 2025 (&lt; 2025)</span>
        </button>
        <button 
            type="button"
            wire:click="setPeriode('post_2025')" 
            class="flex items-center gap-2 px-4 py-2 text-xs md:text-sm font-semibold rounded-xl cursor-pointer transition-all duration-200 {{ $currentPeriode === 'post_2025' ? 'bg-white dark:bg-gray-800 text-primary-600 dark:text-primary-400 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-200/50 dark:hover:bg-gray-800/50' }}">
            <x-heroicon-o-calendar-days class="w-4 h-4" />
            <span>Tahun 2025 ke Atas (&ge; 2025)</span>
        </button>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Card 1: Saldo Awal -->
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="space-y-1">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Total Saldo Awal
                </div>
                <div class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                    Rp {{ number_format($stats['total_saldo_awal'], 0, ',', '.') }}
                </div>
            </div>
        </div>

        <!-- Card 2: Total Invoice -->
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="space-y-1">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Total Nominal Invoice
                </div>
                <div class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                    Rp {{ number_format($stats['total_invoice'], 0, ',', '.') }}
                </div>
            </div>
        </div>

        <!-- Card 3: Total Pembayaran -->
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="space-y-1">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Total Pembayaran
                </div>
                <div class="text-2xl font-semibold tracking-tight text-emerald-600 dark:text-emerald-400">
                    Rp {{ number_format($stats['total_pembayaran'], 0, ',', '.') }}
                </div>
            </div>
        </div>

        <!-- Card 4: Total Sisa Piutang -->
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="space-y-1">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Total Sisa Piutang
                </div>
                <div class="text-2xl font-semibold tracking-tight {{ $stats['total_piutang'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-950 dark:text-white' }}">
                    Rp {{ number_format($stats['total_piutang'], 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
