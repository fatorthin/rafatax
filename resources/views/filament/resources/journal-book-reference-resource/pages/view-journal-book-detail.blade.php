<x-filament-panels::page>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden mb-6">
        <div class="px-6 py-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Histori Buku Jurnal</h2>
            <div class="grid grid-cols-2 gap-4 mt-4">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Nama</p>
                    <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $record->name }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Deskripsi</p>
                    <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $record->description ?? '-' }}</p>
                </div>
            </div>
            @if ($this->isJurnalPendapatan())
                <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-lg flex items-center gap-3">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0" />
                    <p class="text-xs sm:text-sm text-amber-800 dark:text-amber-300">
                        <strong>Otomasi Jurnal Pendapatan (Read-Only):</strong> Data jurnal ini dihitung secara otomatis dari transaksi Piutang (Invoice, Kas/Bank, Bukti Potong PPh 23, Diskon MoU, dan Pembatalan MoU) sehingga konsisten 100% dengan Neraca Lajur Bulanan (Konsep Piutang) dan tidak dapat diedit manual.
                    </p>
                </div>
            @endif
        </div>
    </div>
    {{ $this->table }}
</x-filament-panels::page>
