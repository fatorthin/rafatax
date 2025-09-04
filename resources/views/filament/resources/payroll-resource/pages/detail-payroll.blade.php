<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::card>
            {{ $this->infolist }}
        </x-filament::card>

        <x-filament::card>
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Rincian Payroll</h3>
                <p class="text-sm text-gray-600">Daftar detail komponen payroll per karyawan</p>
            </div>
            <div class="p-6">
                {{ $this->table }}
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
