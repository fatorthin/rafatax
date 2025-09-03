<x-filament-panels::page>
    <div class="space-y-6">
        <!-- MoU Information -->
        <div class="bg-white rounded-lg shadow p-6">
            {{ $this->infolist }}
        </div>

        <!-- Cost List Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Daftar Biaya</h3>
                <p class="text-sm text-gray-600 mt-1">Detail biaya untuk MoU ini</p>
            </div>
            <div class="p-6">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
