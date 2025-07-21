<x-filament-panels::page>
    <x-filament::card>
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-medium">
                Periode: {{ \Carbon\Carbon::create($this->tahun, $this->bulan)->format('F Y') }}
            </h2>
        </div>
    </x-filament::card>

    {{ $this->table }}
</x-filament-panels::page>
