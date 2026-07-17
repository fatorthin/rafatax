<x-filament-panels::page>
    @php
        $stats = $this->getStats();
    @endphp

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
