<x-filament-panels::page>
    <x-filament::card>
        <div class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <h3 class="text-lg font-medium">Nama Aktiva</h3>
                    <p class="text-gray-500">{{ $record->deskripsi }}</p>
                </div>
                <div>
                    <h3 class="text-lg font-medium">Tahun Perolehan</h3>
                    <p class="text-gray-500">{{ \Carbon\Carbon::parse($record->tahun_perolehan)->format('F Y') }}</p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <h3 class="text-lg font-medium">Harga Perolehan</h3>
                    <p class="text-gray-500">Rp {{ number_format($record->harga_perolehan, 0, ',', '.') }}</p>
                </div>
                <div>
                    <h3 class="text-lg font-medium">Tarif Penyusutan</h3>
                    <p class="text-gray-500">{{ number_format($record->tarif_penyusutan, 0, ',', '.') }}%</p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t">
                <div>
                    <h3 class="text-lg font-medium">Total Penyusutan</h3>
                    <p class="text-gray-500">Rp {{ number_format($this->getTotalDepresiasi(), 0, ',', '.') }}</p>
                </div>
                <div>
                    <h3 class="text-lg font-medium">Nilai Buku</h3>
                    <p class="text-gray-500">Rp {{ number_format($record->harga_perolehan - $this->getTotalDepresiasi(), 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </x-filament::card>
      
    {{ $this->table }}
</x-filament-panels::page>
