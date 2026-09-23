<x-filament-panels::page>
    @if ($this->isJurnalPendapatan())
        <div class="mb-4 p-4 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-xl flex items-center gap-3">
            <x-heroicon-o-information-circle class="w-6 h-6 text-amber-600 dark:text-amber-400 shrink-0" />
            <div>
                <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">
                    Otomasi Jurnal Pendapatan (Read-Only)
                </p>
                <p class="text-xs sm:text-sm text-amber-800 dark:text-amber-300 mt-0.5">
                    Transaksi buku jurnal pendapatan bulan ini terisi otomatis dari transaksi Piutang (Invoice, Kas/Bank, PPh 23, Diskon MoU, dan Pembatalan MoU) dan konsisten 100% dengan Neraca Lajur Bulanan (Konsep Piutang).
                </p>
            </div>
        </div>
    @endif
    {{ $this->table }}
</x-filament-panels::page>
