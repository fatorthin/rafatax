<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl">
            @livewire(App\Filament\App\Resources\MouResource\Widgets\MouStatsOverview::class, ['mouId' => $this->mou->id])
        </div>

        <!-- MoU Information -->
        {{ $this->infolist }}

        <!-- Cost List Table -->
        {{ $this->table }}

        <div class="rounded-xl">
            @livewire(App\Filament\App\Resources\MouResource\Widgets\ChecklistMouWidget::class, ['mouId' => $this->mou->id])
        </div>

        <div class="rounded-xl">
            @livewire(App\Filament\App\Resources\MouResource\Widgets\MouInvoicesTable::class, ['mouId' => $this->mou->id])
        </div>

    </div>
</x-filament-panels::page>
