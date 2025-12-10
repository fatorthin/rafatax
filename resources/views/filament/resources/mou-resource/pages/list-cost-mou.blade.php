<x-filament-panels::page>
    {{ $this->infolist }}

    <div class="space-y-6">
        {{ $this->table }}

        <div class="rounded-xl">
            @if (in_array($this->mou->category_mou_id, [3, 4]))
                @livewire(App\Filament\Resources\MouResource\Widgets\ChecklistMouTable::class, ['mouId' => $this->mou->id])
            @endif
        </div>

        <div>
            <div class="rounded-xl">
                @livewire(App\Filament\Widgets\MouInvoicesTable::class, ['mouId' => $this->mou->id])
            </div>

            <div
                class="mt-2 p-4 bg-white dark:bg-gray-800 rounded-xl shadow flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                <div class="font-bold text-gray-900 dark:text-gray-100">Total Invoiced Amount:</div>
                <div class="font-bold text-primary-600 dark:text-primary-400">
                    IDR
                    {{ number_format(App\Models\CostListInvoice::where('mou_id', $this->mou->id)->whereNotNull('invoice_id')->sum('amount'), 0, ',', '.') }}
                </div>
            </div>
        </div>


    </div>
</x-filament-panels::page>
