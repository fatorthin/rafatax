@php
    use Filament\Support\Enums\MaxWidth;
@endphp

<x-filament-panels::page>
    {{ $this->infolist }}

    <x-filament::section>
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
