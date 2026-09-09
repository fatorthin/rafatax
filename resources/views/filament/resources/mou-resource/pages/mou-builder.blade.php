<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="flex items-center gap-3 mt-6">
            <x-filament::button type="submit" size="lg" color="primary" icon="heroicon-m-check">
                Simpan Konfigurasi MoU
            </x-filament::button>

            <x-filament::button
                tag="a"
                href="{{ \App\Filament\Resources\MouResource::getUrl('viewCostList', ['record' => $mou]) }}"
                color="gray"
                size="lg"
            >
                Batal / Kembali
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
